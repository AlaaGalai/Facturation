<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->connection();
$doc->title();
$doc->body(2, "creer.vnom.select()");
$doc->entete();
// $doc->tab_affiche();

if($_REQUEST["action"] == "duplication_dossier")
{
	//get actual file datas
	$q = "select * from {$_SESSION["session_avdb"]} where nodossier like '{$_REQUEST["nodossier"]}'";
	$e = mysqli_query($doc->mysqli, $q);
	$doc->form_global_var = array();
	while ($r = mysqli_fetch_array($e, MYSQLI_ASSOC))
	{
		foreach($r as $k => $v)
		{
			if(in_array($k, array("mp", "mple", "np", "nple", nodossier))) continue;
			if($k == "liea" && ! $v) $v = $_REQUEST["nodossier"];
			if($k == "tvadossier") $v = $_SESSION["optionGen"]["tx_tva"];
			if($k == "dateouverture") $v = $doc->univ_strftime("%Y-%m-%d", time());
			$doc->form_global_var[$k] = $v;
		}
	}
	
	echo "<br><h1>{$doc->lang["creer_dossier_h11_dupliquer"]} {$_REQUEST["nodossier"]}</h1>";
	echo $doc->table_open();
	echo "\n<tr><td>";
	echo $doc->form("maj_op", $doc->lang["entete_duplicate"], "", "", "", "action", "dupliquer_dossier", "retour", "modifier_donnees");
	echo "</td>\n<td>";
	echo $doc->form($_REQUEST["retour"], $doc->lang["modifier_donnees_annuler"], "", "attention", "", "nodossier", $_REQUEST["nodossier"]);
	echo "</td></tr>";
	echo $doc->table_close();
	die();
}

if(!isset($_REQUEST["etape"])) $_REQUEST["etape"] = 0;
$_REQUEST["etape"] ++;
if($_POST["action"] == "cherche_conflits") $h1 = $doc->lang["creer_dossier_h11_conflits"];
else
{
	$h1 = preg_replace("#nouveau_([a-z]+)[0-4]?#", "creer_dossier_h11_\\1", $_POST["action"]);
	$h1 = $doc->lang["$h1"];
}
if($_POST["action"] == "nouveau_dossier")
{
	if($_REQUEST["etape"] == 1) $h2 = $doc->lang["creer_dossier_h21_client"];
	if($_REQUEST["etape"] == 3) $h2 = $doc->lang["creer_dossier_h21_pa"];
	if($_REQUEST["etape"] == 5) $h2 = $doc->lang["creer_dossier_h21_ca"];
}
else
{
	$h2 = preg_replace("#nouveau_([a-z]+)[0-4]?#", "creer_dossier_h21_\\1", $_POST["action"]);
	$h2 = $doc->lang["$h2"];
}

//Titres
echo "<br><h1>$h1</h1>\n<h2>{$doc->lang["creer_dossier_etape"]}&nbsp;{$_REQUEST["etape"]}: $h2</h2>";
echo "\n<form name=creer method=post action=creer_client.php>";
echo $doc->input_hidden("action", 1);
echo $doc->input_hidden("nodossier", 1);
echo $doc->input_hidden("noadresse", 1);
echo $doc->input_hidden("nopa", 1);
echo $doc->input_hidden("etape", 1);
echo $doc->input_hidden("cherche_conflits", 1);

echo $doc->lang["creer_dossier_nom"] ."&nbsp;: " . $doc->input_texte("vnom", "");
echo "<br><br>";
echo $doc->button($doc->lang["creer_dossier_recherche"]);
echo "\n</form>";

if($_REQUEST["action"] == "nouveau_dossier" && $_REQUEST["etape"] == 3) echo $doc->form("maj_op.php", $doc->lang["creer_dossier_pas_de_pa"], "", "", "", "noadresse", "###", "drop_reqb", "on", "retour", "modifier_donnees", "action", "###", "finish", "on");
elseif($_REQUEST["action"] == "nouveau_dossier" && $_REQUEST["etape"] == 5) echo $doc->form("maj_op.php", $doc->lang["creer_dossier_pas_de_ca"], "", "", "", "noadresse", "###", "nopa", "###", "drop_reqb", "on", "retour", "modifier_donnees", "action", "###", "finish", "on");
elseif(substr($_REQUEST["action"], 0, 8) == "nouveau_")
{
	$button = preg_replace("#nouveau_([a-z]+)[0-4]?#", "creer_dossier_pas_de_\\1", $_POST["action"]);
	$champ  = preg_replace("#nouveau_#", "no", $_POST["action"]);
	$champ  = preg_replace("#client#", "adresse", $champ);
	echo $doc->form("maj_op.php", $doc->lang["$button"], "", "", "", "nodossier", "###", "drop_reqb", "on", "retour", "modifier_donnees", "action", "maj", $champ, '0');
}
$doc->close();
die();

// //formulaire pour ne pas choisir de personne
// if($_POST["cherche_pa"]=="on" ||$_POST["action"]=="pa")
// {
// 	echo "\n<form method=\"post\"action=\"./maj_op.php\">";
// 	if($_POST["action"]=="pa")
// 	{
// 		echo "<input type=\"hidden\" name=\"nodossier\" value=\"{$_POST["nodossier"]}\">";
// 		echo "<input type=\"hidden\" name=\"action\" value=\"pa\">";
// 		echo "<input type=\"hidden\" name=\"nopa\" value=\"0\">";
// 	}else{
// 		echo $doc->input_hidden("noadresse", "", $_POST["noadresse"]);
// 		echo $doc->input_hidden("action", "", "new");
// 		echo $doc->input_hidden("retour", "", "modifier_donnees");
// 		echo $doc->input_hidden("drop_reqb", "", "on");
// 	}
// 	echo $doc->button($doc->lang["creer_dossier_pas_de_pa"], "");
// 	echo "</form>";
// }
// elseif(preg_match("#noca#", $_POST["action"]) || preg_match("#nopj#", $_POST["action"]) || preg_match("#nopa#", $_POST["action"]) || (preg_match("#noadresse#", $_POST["action"]) AND $_POST["action"] != "noadresse")){
// 	if(preg_match("#noadresse#", $_POST["action"])) $noadresse=$_POST["action"];
// 	if(preg_match("#noca#", $_POST["action"])) $noca=$_POST["action"];
// 	if(preg_match("#nopj#", $_POST["action"])) $nopj=$_POST["action"];
// 	if(preg_match("#nopa#", $_POST["action"]))
// 	{
// 		$nopa=$_POST["action"];
// 		$noca="noca".preg_replace("#nopa#", "", $_POST["action"]);
// 	}
// 	if(preg_match("#noadresse#", $_POST["action"]))
// 	{
// 		$noadresse=$_POST["action"];
// 		$nopj="nopj".preg_replace("#noadresse#", "", $_POST["action"]);
// 	}
// 	echo "<form method=\"post\" action=\"./modifier_donnees.php\">";
// 	echo "<input type=\"hidden\" name=\"nodossier\" value=\"{$_POST["nodossier"]}\">";
// 	echo "<input type=\"hidden\" name=\"action\" value=\"{$_POST["action"]}\">";
// 	if(preg_match("#nopa#", $_POST["action"]))
// 	{
// 		echo $doc->input_hidden("remove_pa", "", "on");
// 		echo $doc->input_hidden("nopa", "", $nopa);
// 		echo $doc->input_hidden("remove_ca", "", "on");
// 		echo $doc->input_hidden("noca", "", $noca);
// 	}
// 	if(preg_match("#noca#", $_POST["action"]))
// 	{
// 		echo $doc->input_hidden("remove_ca", "", "on");
// 		echo $doc->input_hidden("noca", "", $noca);
// 	}
// 	if(preg_match("#noadresse#", $_POST["action"]))
// 	{
// 		echo $doc->input_hidden("remove_adresse", "", "on");
// 		echo $doc->input_hidden("noadresse", "", $noadresse);
// 		echo $doc->input_hidden("remove_pj", "", "on");
// 		echo $doc->input_hidden("nopj", "", $nopj);
// 	}
// 	if(preg_match("#noca#", $_POST["action"])) echo $doc->button($doc->lang["creer_dossier_pas_de_ca"], "");
// 	if(preg_match("#nopj#", $_POST["action"])) echo $doc->button($doc->lang["creer_dossier_pas_de_pj"], "");
// 	if(preg_match("#nopa#", $_POST["action"])) echo $doc->button($doc->lang["creer_dossier_pas_de_pa"], "");
// 	if(preg_match("#noadresse#", $_POST["action"])) echo $doc->button($doc->lang["creer_dossier_pas_de_client"], "");
// 	echo "</form>";
// }
// $doc->tab_affiche();
$doc->close();
?>
