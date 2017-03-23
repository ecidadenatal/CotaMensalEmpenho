<?php
/*
 *     E-cidade Software Publico para Gestao Municipal
 *  Copyright (C) 2014  DBSeller Servicos de Informatica
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

class cl_empenhocotamensalanulacao extends DAOBasica {

  public function __construct() {
    parent::__construct("plugins.empenhocotamensalanulacao");
  }

  public function sql_query_geral($sCampos = "*", $sWhere = "", $sOrder = "") {

    $sSqlBusca  = "  select {$sCampos} ";
    $sSqlBusca .= "    from empenhocotamensal ";
    $sSqlBusca .= "          left join plugins.empenhocotamensalanulacao on empenhocotamensal = e05_sequencial ";
    $sSqlBusca .= "         inner join empempenho        		 		 on e60_numemp        = e05_numemp ";

    if (!empty($sWhere) && $sWhere != "") {
      $sSqlBusca .= " where {$sWhere} ";
    }

    if (!empty($sOrder) && $sOrder != "") {
      $sSqlBusca .= " order by {$sOrder} ";
    }
    return $sSqlBusca;
  }

}
?>