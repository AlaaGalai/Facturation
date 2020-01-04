<?php
$this->menuCondi('1', True, "Retour à la RDAF"); #Override
$this->menuCondi('1.x', True, $this->form("index.php<td>", "Retour à la RDAF", "", "menu")); #Add new menu


$this->excludeMenu = array("ALL"); #Exclude Prolawyer
?>
