CREATE TABLE `biblio` (
  `debut_titre` varchar(64) NOT NULL default '',
  `titre` varchar(255) NOT NULL default '',
  `sous_titre` varchar(255) NOT NULL default '',
  `no_volume` varchar(32) NOT NULL default '',
  `auteur1` varchar(64) NOT NULL default '',
  `auteur2` varchar(64) NOT NULL default '',
  `auteur3` varchar(64) NOT NULL default '',
  `auteur4` varchar(64) NOT NULL default '',
  `auteur5` varchar(64) NOT NULL default '',
  `auteur6` varchar(64) NOT NULL default '',
  `prete_le` date NOT NULL default '0000-00-00',
  `prete_a` varchar(32) NOT NULL default '',
  `domaine` text NOT NULL,
  `type` varchar(16) NOT NULL default '',
  `class` varchar(16) NOT NULL default '',
  `sous_class` varchar(16) NOT NULL default '',
  `remarques` varchar(255) NOT NULL default '',
  `editeur` varchar(64) NOT NULL default '',
  `edition` int(11) NOT NULL default '1',
  `date_edition` date NOT NULL default '0000-00-00',
  `lieu_edition` varchar(32) NOT NULL default '',
  `no_fiche` int(11) NOT NULL auto_increment,
  `largeur` decimal(10,1) NOT NULL default '1.0',
  `np` varchar(64) NOT NULL,
  `nple` date NOT NULL,
  `mp` varchar(64) NOT NULL,
  `mple` date NOT NULL,
  `isbn` varchar(32) NOT NULL default '',
  `commande_chez` varchar(32) NOT NULL default '',
  `commande_pour` varchar(32) NOT NULL default '',
  `collection` varchar(64) NOT NULL default '',
  `status` int(2) NOT NULL default '0',
  PRIMARY KEY  (`no_fiche`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8
