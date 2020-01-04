CREATE TABLE `acces` (
  `user` varchar(64) NOT NULL,
  `type` varchar(64) NOT NULL,
  `lang` varchar(64) NOT NULL,
  `db` varchar(64) NOT NULL,
  `agenda` varchar(64) NOT NULL,
  `nb_affiche` int(11) NOT NULL,
  `imap` text NOT NULL,
  PRIMARY KEY  (`user`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
