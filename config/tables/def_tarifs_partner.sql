CREATE TABLE `to_preg` (
  `nodossier` int(11) NOT NULL default '0',
  `id` int(11) NOT NULL auto_increment,
  `soustraitant` varchar(255) NOT NULL default '',
  `prixhoraire` decimal(10,2) NOT NULL default '0.00',
  `np` varchar(64) NOT NULL default '',
  `nple` date NOT NULL default '0000-00-00',
  `mp` varchar(64) NOT NULL default '',
  `mple` date NOT NULL default '0000-00-00',
  PRIMARY KEY  (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
