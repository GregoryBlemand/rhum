create table llx_dispo_rhumerie
(
  rowid           			integer AUTO_INCREMENT PRIMARY KEY,
  fk_product    			integer DEFAULT NULL,
  fk_rhumerie   			integer DEFAULT NULL
)ENGINE=innodb;