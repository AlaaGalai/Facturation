CREATE TABLE `passwords` (
  `noa` int(11) NOT NULL auto_increment,
  `user` varchar(64) NOT NULL,
  `pwd` varchar(64) NOT NULL,
  PRIMARY KEY  (`noa`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
