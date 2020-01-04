CREATE TABLE `utilisateursgroupesmembres` (
  `nogd` int(11) NOT NULL auto_increment,
  `membre` varchar(64) NOT NULL default '',
  `groupe` varchar(64) NOT NULL default '',
  PRIMARY KEY  (`nogd`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
