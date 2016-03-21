create table plugins.empenhocotamensalanulacao (sequencial        serial  not null primary key ,
                                                empenhocotamensal integer not null,
                                                valoranulado      double precision not null,
                                                constraint empenhocotamensal_sequencial_fk foreign key (empenhocotamensal) references empenhocotamensal);
