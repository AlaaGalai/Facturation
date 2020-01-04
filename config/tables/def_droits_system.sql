CREATE TABLE `droits` (
  `nod` int(11) NOT NULL auto_increment,
  `init` char(2) NOT NULL,
  `groupname` varchar(64) NOT NULL default '',
  `personne` varchar(64) NOT NULL default '',
  `admin` tinyint(1) NOT NULL default '0',
  `lire` tinyint(1) NOT NULL default '0',
  `ecrire` tinyint(1) NOT NULL default '0',
  `journal` tinyint(1) NOT NULL default '0',
  `tva` tinyint(1) NOT NULL default '0',
  `rapports` tinyint(1) NOT NULL default '0',
  `lire_agenda` tinyint(1) NOT NULL default '0',
  `ecrire_agenda` tinyint(1) NOT NULL default '0',
  `courriels` tinyint(1) NOT NULL default '0',
  `accesgroupe` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`nod`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;
