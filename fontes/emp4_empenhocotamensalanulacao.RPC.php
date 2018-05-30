<?php
/*
 *     E-cidade Software Publico para Gestao Municipal
 *  Copyright (C) 2014  DBselller Servicos de Informatica
 *                            www.dbseller.com.br
 *                         e-cidade@dbseller.com.br
 *
 *  Este programa e software livre; voce pode redistribui-lo e/ou
 *  modifica-lo sob os termos da Licenca Publica Geral GNU, conforme
 *  publicada pela Free Software Foundation; tanto a versao 2 da
 *  Licenca como (a seu criterio) qualquer versao mais nova.
 *
 *  Este programa e distribuido na expectativa de ser util, mas SEM
 *  QUALQUER GARANTIA; sem mesmo a garantia implicita de
 *  COMERCIALIZACAO ou de ADEQUACAO A QUALQUER PROPOSITO EM
 *  PARTICULAR. Consulte a Licenca Publica Geral GNU para obter mais
 *  detalhes.
 *
 *  Voce deve ter recebido uma copia da Licenca Publica Geral GNU
 *  junto com este programa; se nao, escreva para a Free Software
 *  Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA
 *  02111-1307, USA.
 *
 *  Copia da licenca no diretorio licenca/licenca_en.txt
 *                                licenca/licenca_pt.txt
 */

require_once(modification("dbforms/db_funcoes.php"));
require_once(modification("libs/JSON.php"));
require_once(modification("libs/db_stdlib.php"));
require_once(modification("libs/db_utils.php"));
require_once(modification("libs/db_app.utils.php"));
require_once(modification("libs/db_conecta_plugin.php"));
require_once(modification("libs/db_sessoes.php"));
require_once(modification("std/db_stdClass.php"));
require_once(modification("classes/db_empenhocotamensalanulacao_classe.php"));

$json               = new services_json();
$oParam             = $json->decode(str_replace("\\","",$_POST["json"]));

$oRetorno           = new stdClass();
$oRetorno->status   = 1;
$oRetorno->mensagem = '';
$oRetorno->erro     = false;
$sMensagem          = "";
$iEmpenho           = $oParam->iEmpenho;
$aCotasAnulacao     = array();

switch ($oParam->method) {

    case 'getCotasMensaisAnulacao' :

      $oEmpenhoCotaMensalAnulacao = new cl_empenhocotamensalanulacao();

      $sSqlTotalLiquidadoAnterior = "select coalesce(sum(e70_vlrliq), 0) 
                                from empnotaele 
                                    inner join empnota on e69_codnota = e70_codnota 
                                where e69_numemp = $oParam->iEmpenho and extract(month from e69_dtinclusao) <= ".date('m',db_getsession('DB_datausu'));

      $sSqlTotalLiquidadoMes = "select coalesce(sum(e70_vlrliq), 0) 
                                from empnotaele 
                                    inner join empnota on e69_codnota = e70_codnota 
                                where e69_numemp = $oParam->iEmpenho and extract(month from e69_dtinclusao) = e05_mes";

      $sSqlSaldoAnterior = $oEmpenhoCotaMensalAnulacao->sql_query_geral("(sum(e05_valor) - ($sSqlTotalLiquidadoAnterior) - sum(coalesce(valoranulado, 0)))", "e05_mes <= ".date('m',db_getsession('DB_datausu'))." and e05_numemp = $oParam->iEmpenho");

      $sCampos     = "e05_sequencial, ";
      $sCampos    .= "e05_mes,";
      $sCampos    .= " e05_valor,";
      $sCampos    .= " coalesce(valoranulado, 0) as valoranulado,";
      $sCampos    .= " (case 
                          when e05_mes < ".date('m',db_getsession('DB_datausu'))." then 0
                          when e05_mes = ".date('m',db_getsession('DB_datausu'))." then ($sSqlSaldoAnterior)
                          else e05_valor-($sSqlTotalLiquidadoMes)-coalesce(valoranulado, 0) 
                        end) as saldocota";
      $sSqlCotaAnu = $oEmpenhoCotaMensalAnulacao->sql_query_geral($sCampos, "e05_numemp = $oParam->iEmpenho", "e05_mes");
      $rsCotasAnu  = $oEmpenhoCotaMensalAnulacao->sql_record($sSqlCotaAnu);
      $iNumRows    = $oEmpenhoCotaMensalAnulacao->numrows;
      
      if ($iNumRows > 0) {

        $aCotasAnu = db_utils::getCollectionByRecord($rsCotasAnu);
        $oRetorno->numCotas  = $iNumRows;
        $oRetorno->aCotas    = $aCotasAnu;
      } else {

        $oRetorno->mensagem = "Dados não encontrados.";
        $oRetorno->aCotas   = null;
      }
      echo $json->encode($oRetorno);
      exit;
      break;

    case 'salvar' :

      $aCotasAnulacao = $json->decode($oParam->aCotas);
      
      foreach ($aCotasAnulacao as $oCota) {
        
        $oEmpenhoCotaMensal = new cl_empenhocotamensal();
        $oEmpenhoCotaMensalAnulacao = new cl_empenhocotamensalanulacao();
        
        //pegar dados da cota mensal
        $sSqlCotaMensal     = $oEmpenhoCotaMensal->sql_query_file(null, "*", null, "e05_mes = $oCota->mes and e05_numemp = $oParam->iEmpenho");
        $rsCotaMensal       = db_query($sSqlCotaMensal);
        $oCotaMensal        = db_utils::fieldsMemory($rsCotaMensal, 0);

        //tentar obter dados de anulaÃ§Ã£o de cota
        $sSqlBuscaCotaMensalAnulacao = $oEmpenhoCotaMensalAnulacao->sql_query(null, "*",  null, "e05_sequencial = $oCotaMensal->e05_sequencial");
        $rsBuscaCotaMensalAnulacao   = $oEmpenhoCotaMensalAnulacao->sql_record($sSqlBuscaCotaMensalAnulacao);
        $oCotaMensalAnulacao = db_utils::fieldsMemory($rsBuscaCotaMensalAnulacao, 0);

        //Se jÃ¡ tiver registro, altera. Se nÃ£o tiver, insere.
        if ($oEmpenhoCotaMensalAnulacao->numrows > 0) {

          $oEmpenhoCotaMensalAnulacao->empenhocotamensal = $oCotaMensal->e05_sequencial;
          $oEmpenhoCotaMensalAnulacao->valoranulado      = $oCotaMensalAnulacao->valoranulado + ((float)$oCota->valor > 0 ? (float)$oCota->valor : 0);
          $oEmpenhoCotaMensalAnulacao->alterar($oCotaMensalAnulacao->sequencial);
          
          if ($oEmpenhoCotaMensalAnulacao->erro_status == 0) {
            $oRetorno->status   = 0;
            $oRetorno->mensagem = $oEmpenhoCotaMensalAnulacao->erro_sql;
            throw new Exception("Erro ao alterar registro.", 1);            
          }
        } else {

          $oCotaAnulacao = new cl_empenhocotamensalanulacao();
          $oCotaAnulacao->empenhocotamensal = $oCotaMensal->e05_sequencial;
          $oCotaAnulacao->valoranulado      = $oCota->valor ? $oCota->valor : 0;
          $oCotaAnulacao->incluir(null);
          
          if ($oCotaAnulacao->erro_status == 0) {
            $oRetorno->status   = 0;
            $oRetorno->mensagem = $oCotaAnulacao->erro_sql;
            throw new Exception("Erro ao inserir registro. ".$oCotaAnulacao->erro_msg, 1);            
          }
        }

      }
      if ($oEmpenhoCotaMensalAnulacao->erro_status == 0) {
        throw new DBException("ERRO [ 1 ] - Incluindo detalhe - " .  $oEmpenhoCotaMensalAnulacao->erro_msg);
      }    
      
      $oRetorno->mensagem = 'Anulação de Cotas salva com sucesso.';
      echo $json->encode($oRetorno);
      exit;
    break;
  }

$oRetorno->mensagem = urlencode($oRetorno->mensagem);
echo $oJson->encode($oRetorno);
exit;
?>