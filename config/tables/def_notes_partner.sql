CREATE TABLE `to_preg` (
  `idnote` int(11) unsigned NOT NULL auto_increment,
  `nodossier` int(11) NOT NULL default '0',
  `textenote` text NOT NULL default '',
  PRIMARY KEY  (`idnote`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
