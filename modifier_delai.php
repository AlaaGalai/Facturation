<?php
if(is_file("override/session_override.php"))
{
	require("override/session_override.php");
}else{
	session_name("prolawyer");
}
session_start();
error_reporting(0);
require_once("./inc/autoload.php");
foreach($_GET as $a => $b) $_POST["$a"] = $b;

$cal=new calendar;
$cal->title();
$cal->body(2);

if($_GET["hasDeleted"])
{
	echo "\n<script language=javascript>var hasDeleted=true;reloadFrame()</script>";
	$cal->close();
	die();
}
if($_POST["nouveau"] == "on"||$_POST["copy"] == "on") $titleVar = $cal->lang["agenda_nouveau_dl"];
else $titleVar = $cal->lang["modifier_delai_title"];
echo "\n<h3>$titleVar</h3>";


if($_POST["nouveau"] != "on")
{
	$requete="select * from delais where id like '{$_POST["id"]}'";
// 	echo "$requete";
	$exec=mysqli_query($cal->mysqli, $requete);
	$row=mysqli_fetch_array($exec);
}else{
	if($_POST["date_cours"]) $jdebut=$_POST["date_cours"];
	else $jdebut=$cal->date_jour;
	if($_POST["date_fin"]) $jfin=$_POST["date_fin"];
	else $jfin=$jdebut;
	if($_POST["heure_debut"]) $hdebut=$_POST["heure_debut"];
	else $hdebut=$cal->dbt_jour;
	if($_POST["heure_fin"]) $hfin=$_POST["heure_fin"];
	else $hfin=$cal->addtime($hdebut, "1:00");
	if($_POST["dl_pour"]) $dl_pour=$_POST["dl_pour"];
	else $dl_pour=substr($_SESSION["db"], 0, 2);
	if($_POST["libelle"]) $libelle = $_POST["libelle"];
	else $libelle = "";
	$row=array("dl_pour" => $dl_pour, "date_debut" => $jdebut, "date_fin" => $jfin, "heure_debut" => $hdebut, "heure_fin" => $hfin, "type" => "", "libelle" => $libelle);
}

if ($row["repete"]!= "o")
{
	$repeteUnchecked = "checked";
	$row["date_debut"] = "";
}
else $repeteChecked = "checked";
$biffe_check=($row["biffe"])? "checked":"";
$nonbiffe_check=(!$row["biffe"]||$row["biffe"] == "n")? "checked":"";
//affichage des délais
/*echo "\n<form name=\"modifier\" method=\"post\" action=\"maj_op.php?close=true\">";*/
echo "\n<form style = \"display:inline\" name=\"modifier\" id=\"modifier\" method=\"post\" action=\"javascript:verifyDate('{$cal->lang["modifier_rdv_error_message1"]}','{$cal->lang["modifier_rdv_error_message2"]}')\" accept-charset=\"utf-8\">";
echo "\n<table border=0 style=\"font-size:10pt\">";
echo "\n<tr><td colspan=\"2\"><div id=\"dateError\">&nbsp;</div></td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_date_to"]}</td><td>\n".$cal->split_date($row["date_fin"], "_fin")."</td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_date_rappeler"]}&nbsp;?</td><td>{$cal->lang["general_oui"]}<input type=\"radio\" name=\"repete\" id='repete' $repeteChecked value=\"o\">{$cal->lang["general_non"]}<input type=\"radio\" name=\"repete\" $repeteUnchecked value=\"n\"></td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_date_fr"]}</td><td>\n".$cal->split_date($row["date_debut"], "_debut", "", "", "", "if(this.value != \"\") document.getElementById(\"repete\").checked=\"true\"")."</td></tr>";
//$requete="select * from delais where id like '{$_POST["id"]}' order by date_fin";
	
if($row["fait"] == "on") $faits_checked="checked";
else $faits_unchecked="checked";
echo "\n<tr><td>{$cal->lang["operations_op"]}</td><td>".$cal->input_texte("libelle", "", $cal->smart_html($row["libelle"]), 30) ."</td></tr>";
echo "\n<tr><td>{$cal->lang["modifier_delai_priorite"]}</td><td><select name=priorite>";
$select=explode("\n", "{$_SESSION["optionGen"]["delais_type"]}");
$testval=0;
foreach($select as $option)
{
	list($abrev, $nom)=preg_split("#,#", $option);
	if($row["priorite"]==$abrev)
	{
		$selected=" selected";	
		$testval=1;
	}
	echo "<option value=\"$abrev\"$selected>$nom
	";
	$selected="";
}
if($testval==0) echo "<option value=\"{$row["priorite"]}\" selected>{$row["priorite"]}";
echo "</select></td></tr>";
// $cal->tab_affiche(2);
echo "\n<tr><td>{$cal->lang["modifier_delai_pour"]}</td><td><table><tr><td>\n<select multiple name=\"dl_pour[]\" class=\"semaine\" size=\"6\">\n".$cal->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $row["dl_pour"], TRUE)."</select></td><td>\n<select multiple name=\"groups[]\" size=\"6\" class=\"semaine\">\n".$cal->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $row["dl_pour"], TRUE, TRUE, TRUE)."</select></td></tr></table></td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_termine"]}</td><td>{$cal->lang["general_oui"]}<input type=\"radio\" name=\"fait\" $faits_checked value=\"on\">{$cal->lang["general_non"]}<input type=\"radio\" name=\"fait\" $faits_unchecked value=\"off\"></td></tr>";

echo $cal->input_hidden("retour", "", "modifier_delai?close=true");
if(!$_POST["copy"]) echo $cal->input_hidden("id", "1");
if($_POST["nouveau"]=="on" || $_POST["copy"]=="on")
{
	$button_texte=$cal->lang["config_modify_create"];
	echo $cal->input_hidden("action", "", "new");
	echo $cal->input_hidden("dossier", "on");
}else{
	$button_texte=$cal->lang["modifier_donnees_modifier_dossier"];
}
	 $closeMethod = ($cal->androlawyerClient)? "JCB.finishActivity()" : "self.close()";
	 $reloadMethod = ($cal->androlawyerClient)? "JCB.finishActivity()" : "reloadFrame()";
echo "\n<tr><td>{$cal->lang["agenda_annuler_rdv"]}</td><td>{$cal->lang["general_oui"]}<input type=\"radio\" value=\"y\" $biffe_check name=\"biffe\">{$cal->lang["general_non"]}<input type=\"radio\" value=\"\" $nonbiffe_check name=\"biffe\"></td></tr>";
echo "\n<tr><td colspan=\"2\"><table width=\"100%\"><tr><td class=\"button\"><button type=submit onclick=\"modifier.submit()\" class=\"button\">$button_texte</button></td><td><button type=\"button\" onclick=\"supprimer.submit()\" class=\"buttonattention\">{$cal->lang["operations_supprimer"]}</button></td>";
echo "<td><button type=\"button\" onclick=\"$closeMethod\" class=button>{$cal->lang["general_fermer"]}</button></td></tr></table></td><td><button type=submit>&nbsp;</button></td></tr>";
echo "\n</table>\n</form>";
echo "\n<form name=\"supprimer\" method=\"post\" action=\"maj_op.php\">";
echo $cal->input_hidden("action", "", "delete");
echo $cal->input_hidden("retour", "", "modifier_delai?close=true");
echo $cal->input_hidden("id", "1");
echo "</form>";

if($_GET["close"] == "true") echo "<script>$reloadMethod</script>";

$cal->close();
?> 
