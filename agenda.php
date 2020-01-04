<?php
require_once("./inc/autoload.php");
session_start();
if(isset($_POST["personnebis"])) $_POST["personne"] = $_POST["personnebis"];
$agendaSolo = isset($_REQUEST["agendaSolo"])? $_REQUEST["agendaSolo"]:"";
$doc=new calendar($agendaSolo);
if($_POST["type"] == "semaine") $doc->addStyle = "rdv";
$doc->title();
$doc->body(2);


echo $doc->entete(FALSE);
// echo $doc->self_reload();

 
if($_POST["typerecherche"]=="delais") $checkdelais="checked";
else $checkagenda="checked";
if($_POST["limiterecherche"]) $checklimite="checked";

// echo $doc->tab_affiche($_SESSION["optionGen"]);
$dd = $doc->ics2prolawyer($_SESSION["optionGen"]["doodle"]); // actuellement, la fonction ne retourne rien. Elle convertit seulement doodle en prolawyer à la volée
if ($dd) echo "<span class=attention>$dd</span>";
// die();

if($_POST["type"] == "vacances")
{
	echo $doc->display_vacation($_POST["date_cours"], $doc->liste_personne);
}else{
	echo "<script language=\"javascript\">\ndateReload = '{$_POST["date_cours"]}';\npersReload='{$doc->liste_personne}';</script>";
	echo "\n<table width=\"100%\">";

	//Boîte de recherche (cachée pour les pda
	$tblVis = $doc->pdaSet? "none":"table";
	if($_POST["template"] == "delais") echo "\n<h1>{$doc->lang["liste_delais_title"]}</h1>";
	echo "<tr style=display:$tblVis id=tablerecherche>";
	if($_SESSION["module"] == "agenda")
	{
		echo "<td><a href=\"./agenda_parts.php?function=selecteur\">{$doc->lang["modifier_delai_pour"]}</a></td>";
	}elseif(!$doc->pdaSet){
		echo "<td width=50%>";
		echo $doc->calendarSelect($_POST["date_cours"], TRUE, "", True);
		echo "</td>";
	}
	$searchButton  = $doc->button($doc->lang["adresses_index_rechercher"], "", "semaine_entete");
	$searchButton2 = $doc->button($doc->lang["modifier_delai_pour"], "", "semaine_entete");
	echo "\n<td class=\"semaine\" style='width:100%' align=right><form name=\"rechercheAgenda\" id=\"rechercheAgenda\" action=\"{$_SERVER["PHP_SELF"]}\" method=\"post\"><table class=\"semaine\" style=border-color:red;border-style:solid;width:100%><tr><td rowspan=2>";
	echo $searchButton;
	echo "&nbsp;<input accesskey=A type=\"text\" size=\"40vw\" name=\"searchtext\" value=\"{$_POST["searchtext"]}\" class=\"semaine\">\n<br>{$doc->lang["agenda_dans"]} ";
	echo $doc->input_checkbox("recherchedelai", 1);
	echo "{$doc->lang["entete_delais"]}";
	echo $doc->input_checkbox("rechercheagenda", 1);
	echo "{$doc->lang["agenda_title"]}";
	echo $doc->input_hidden("template", 1);
	echo $doc->input_hidden("mode", "", "recherche");
	echo "<br><table class=\"semaine\"><tr><td rowspan=2><input type=\"checkbox\" name=\"limiterecherche\" $checklimite></td><td valign=\"top\">{$doc->lang["general_du"]}".$doc->split_date("POST", "rechercheDebut")."</td></tr><tr><td>{$doc->lang["general_au"]}".$doc->split_date("POST", "rechercheFin")."</td></tr></table>";
	echo "{$doc->lang["liste_delais_avec"]}: <input type=\"checkbox\" name=\"faits\" {$doc->faits_check}>";
	echo $searchButton;
	echo "</td>";
	echo "<td colspan=2>$searchButton2</td></tr><tr>";
	echo "<td><select multiple name=\"personnebis[]\" size=\"6\" class=\"semaine\">";
	echo $doc->selecteur($_SESSION["user"], TRUE, FALSE, FALSE, $doc->liste_personne, TRUE, $groups);
	echo "</select></td><td><select multiple name=\"personnebis[]\" size=\"6\" class=\"semaine\">";
	echo $doc->selecteur($_SESSION["user"], TRUE, FALSE, FALSE, $doc->liste_personne, TRUE, TRUE, TRUE);
	echo "</select></td>";
	echo "</tr></table></form></td>";
	echo "</tr><tr>";
	
	if($_POST["mode"] == "recherche")
	{
		echo "<td valign=\"top\" colspan=\"4\">&nbsp;</td></tr></table>";
		echo "\n<br>\n<br>";
		echo $doc->liste_rdv($_POST["searchtext"], $doc->liste_personne, $_POST["reserveid"]);
	}elseif($_POST["template"] == "agenda"){
		echo "<td valign=\"top\" colspan=\"4\">";
		echo $doc->display_dl("=", $_POST["date_cours"], $doc->liste_personne);
		echo "</td></tr></table>";
		echo "\n<br>\n<br>";
		echo $doc->getPeriode();
	}elseif($_POST["template"] == "delais"){
		echo "</tr></table>";
		echo $doc->display_dl("<,=,>", $_POST["date_cours"], $doc->liste_personne);
	}
}

$doc->close();
?>
