CREATE TABLE `operations` (
  `noop` int(11) NOT NULL AUTO_INCREMENT,
  `libelle` varchar(64) NOT NULL,
  `compte1` int(11) NOT NULL,
  `compte2` int(11) NOT NULL,
  `piece` int(11) NOT NULL,
  `date` date NOT NULL,
  `montant` int(11) NOT NULL,
  PRIMARY KEY (`noop`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8