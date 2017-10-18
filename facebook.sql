CREATE TABLE _facebook (
	id int(11) NOT NULL,
	user mediumtext NOT NULL,
	pass varchar(11111) NOT NULL,
	email varchar(11111) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

ALTER TABLE _facebook
	ADD PRIMARY KEY (id);

ALTER TABLE _facebook
	MODIFY id int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;COMMIT;
