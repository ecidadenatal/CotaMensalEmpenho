create table plugins.empenhocotamensalanulacao (sequencial        serial  not null primary key ,
                                                empenhocotamensal integer not null,
                                                valoranulado      double precision not null);

--ALTER TABLE plugins.empenhocotamensalanulacao 
--ADD CONSTRAINT empenhocotamensalanulacao_empenhocotamensal
--FOREIGN KEY (empenhocotamensal) 
--REFERENCES empenhocotamensal 
--ON DELETE CASCADE;
