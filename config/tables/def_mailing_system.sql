CREATE TABLE `mailing` (
  `mailingname` varchar(64) NOT NULL default '',
  `adresseid` int(11) unsigned NOT NULL,
  `id` int(11) unsigned NOT NULL auto_increment,
  `np` varchar(64) NOT NULL default '',
  `nple` date NOT NULL default '0000-00-00',
  `mp` varchar(64) NOT NULL default '',
  `mple` date NOT NULL default '0000-00-00',
  KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
