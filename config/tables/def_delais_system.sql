CREATE TABLE `delais` (
  `date_debut` date NOT NULL default '0000-00-00',
  `repete` char(1) NOT NULL default '',
  `biffe` char(1) NOT NULL default '',
  `date_fin` date NOT NULL default '0000-00-00',
  `dossier` varchar(255) NOT NULL default '',
  `dl_pour` varchar(255) NOT NULL default '',
  `dl_grp` varchar(255) NOT NULL default '',
  `type` varchar(32) NOT NULL default '',
  `libelle` varchar(255) NOT NULL default '',
  `id` int(11) unsigned NOT NULL auto_increment,
  `mple` date NOT NULL default '0000-00-00',
  `mp` varchar(32) NOT NULL default '',
  `nple` date NOT NULL default '0000-00-00',
  `np` varchar(32) NOT NULL default '',
  `fait` varchar(32) NOT NULL default '',
  `priorite` varchar(255) NOT NULL default '',
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;