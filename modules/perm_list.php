<?php
/*
Modes:
1=lecture fichier
2=écriture fichier
4=journal
8=TVA
16=rapports d'activités
32=lecture agenda
64=écriture agenda
128=courriels
*/

$this->p_inits=array($this->lang["init_administration"], $this->lang["init_lire"], $this->lang["init_ecrire"], $this->lang["init_journal"], $this->lang["init_tva"], $this->lang["init_rapports"], $this->lang["init_lire_agenda"], $this->lang["init_ecrire_agenda"], $this->lang["init_courriels"]);

$this->p_names=array($this->lang["init_administration_nom"], $this->lang["init_lire_nom"], $this->lang["init_ecrire_nom"], $this->lang["init_journal_nom"], $this->lang["init_tva_nom"], $this->lang["init_rapports_nom"], $this->lang["init_lire_agenda_nom"], $this->lang["init_ecrire_agenda_nom"], $this->lang["init_courriels_nom"]);

$this->p_values=array("admin", "lire", "ecrire", "journal", "tva", "rapports", "lire_agenda", "ecrire_agenda", "courriels");
?>