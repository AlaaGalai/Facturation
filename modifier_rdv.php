<?php
if(is_file("override/session_override.php"))
{
	require("override/session_override.php");
}else{
	session_name("prolawyer");
}
session_start();
error_reporting(7);
require_once("./inc/autoload.php");

$cal=new calendar;
$cal->addStyle = "rdv";
$cal->title();
$cal->body(2);
$cal->globalUseRichText = preg_match('/iPhone/i', $_SERVER["HTTP_USER_AGENT"])?"":"True";

if($_GET["hasDeleted"])
{
	echo "\n<script language=javascript>var hasDeleted=true;reloadFrame()</script>";
	$cal->close();
	die();
}
?>

<script language=javascript>
function checkEmpty(id, state)
{
	c = document.getElementById(id);
	empty = '<I><?php echo $cal->lang["modifier_texte"]?></I>';
	if (state)
	{
		if (c.innerHTML == '') c.innerHTML = empty;
	}
	if (!state)
	{
		if (c.innerHTML.toLowerCase() == empty.toLowerCase()) {
			c.innerHTML = '&nbsp;';
		}
	}
}
</script>
<?php

if($_POST["nouveau"] != "on")
{
	$checked = "checked";
	$requete="select * from rdv where id like '{$_POST["id"]}'";
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
	if($_POST["rdv_pour"]) $rdv_pour=$_POST["rdv_pour"];
	else $rdv_pour=substr($_SESSION["db"], 0, 2);
	if($_POST["type"]) $type = $_POST["type"];
	else $type = "";
	if($_POST["libelle"]) $libelle = $_POST["libelle"];
	else $libelle = "";
	$row=array("rdv_pour" => $rdv_pour, "date_debut" => $jdebut, "date_fin" => $jfin, "heure_debut" => $hdebut, "heure_fin" => $hfin, "type" => $type, "libelle" => $libelle);
}

if($row["doodle"])
{
	$doodleIm = ($row["doodleset"]) ? "doodleset":"doodle";
	$boutAjou = "<a href='{$row["doodle"]}' target=doodle><img width=16px height=16px src='{$cal->settings["root"]}images/$doodleIm.png'></a>";
}
else $boutAjou = "";


if($_POST["nouveau"] == "on"||$_POST["copy"] == "on") $titleVar = $cal->lang["agenda_nouveau_rdv"];
else $titleVar = $cal->lang["modifier_rdv_title"];
echo "\n<h2>$titleVar $boutAjou</h2>";



//test pour toutes les situations
$checked = "checked";

//pour javascript
list($ad, $md, $jd) = explode("-", $row["date_debut"]);
list($af, $mf, $jf) = explode("-", $row["date_fin"]);
list($hd, $nd) = explode(":", $row["heure_debut"]);
list($hf, $nf) = explode(":", $row["heure_fin"]);
echo "\n<script>var jdi='$jd';var mdi='$md';var adi='$ad';var jfi='$jf';var mfi='$mf';var afi='$af';var hdi='$hd';var ndi='$nd';var hfi='$hf';var nfi='$nf'</script>";
$reserveid = $row["reserveid"]? $row["reserveid"]: $row["id"];
if(! $reserveid) $reserveid = "AAJOUTER";

$biffe_check=($row["biffe"])? "checked":"";
$nonbiffe_check=(!$row["biffe"]||$row["biffe"] == "n")? "checked":"";

$ar = array();
foreach(array(0, 1, 2) as $num)
{
	$nom = "modifier_rdv_status_$num";
	$val = $cal->lang[$nom];
	$ar[$num] = $val;
}
$selectStatus = $cal->simple_selecteur($ar, $row["status"], 2);

$select=explode("\n", "{$_SESSION["optionGen"]["lieux"]}");
foreach($select as $lieu)
{
	$lieu = trim($lieu);
	$selected = ($lieu == $row["lieu"]) ? "selected": "";
	$lieux .= "\n<option value=\"$lieu\" $selected>".$cal->smart_html($lieu)."</option>";
}
$richTextButtons = $cal->globalUseRichText? "<a href=# class=cbutton style='font-weight:bold' onclick='document.execCommand(\"bold\", false, \"\")'>B</a>&nbsp;<a href=# class=cbutton style='font-style:italic' onclick='document.execCommand(\"italic\", false, \"\")'>I</a>&nbsp;<a href=# class=cbutton style='color:red' onclick='document.execCommand(\"forecolor\", false, \"#ff0000\")'>A</a></td>":"";
if($cal->globalUseRichText && !$row["libelle"]) $row["libelle"] = "<I>" . $cal->lang["modifier_texte"] ."</I>";
echo "\n<form style = \"display:inline\" name=\"modifier\" id=\"modifier\" method=\"post\" action=\"javascript:verifyDate('{$cal->lang["modifier_rdv_error_message1"]}','{$cal->lang["modifier_rdv_error_message2"]}')\" accept-charset=\"utf-8\">";
echo "\n<table border=0 width=100%>";
echo "\n<tr><td colspan=\"2\"><div id=\"dateError\">&nbsp;</div></td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_date_from"]}</td><td>".$cal->split_date($row["date_debut"], "_debut", "", "", "", "linkDate()")."</td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_heure_from"]}</td><td>".$cal->split_time($row["heure_debut"], "_debut", "", "", "linkDate()")."</td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_date_fin"]}</td><td>".$cal->split_date($row["date_fin"], "_fin")."<input id=linked type=checkbox $checked><img src='images/link.png'></td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_heure_fin"]}</td><td>".$cal->split_time($row["heure_fin"], "_fin")."</td></tr>";
echo "\n<tr><td>{$cal->lang["operations_op"]}</td><td><table border=0><tr><td>$richTextButtons</td></tr></tr><td>".$cal->input_texte("libelle", "", $cal->smart_html($row["libelle"]), 30, $cal->globalUseRichText, ";if(event.keyCode == 9){document.getElementById(\"selectOption\").focus();return false};if(event.keyCode == 13){verifyDate();return false}")."</td></tr></table></td></tr>";
// echo "\n<tr><td>{$cal->lang["operations_op"]}</td><td>".$cal->input_texte("libelle", "", $row["libelle"], 30)."</td></tr>";
echo "\n<tr><td>{$cal->lang["modifier_rdv_lieu"]}</td><td><select name=\"lieu\" id=\"selectOption\">\n<option value=\"\"></option>$lieux</select></td></tr>";
echo "\n<tr><td>{$cal->lang["modifier_delai_pour"]}</td><td><table><tr><td><select multiple name=\"rdv_pour[]\" size=\"4\">".$cal->selecteur($_SESSION["user"], TRUE, FALSE, FALSE, $row["rdv_pour"], TRUE)."</select></td><td><select multiple name=\"groups[]\" size=\"4\">".$cal->selecteur($_SESSION["user"], TRUE, FALSE, FALSE, $row["rdv_pour"], TRUE, TRUE, TRUE)."</select></td></tr></table></td></tr>";
echo "\n<tr><td>{$cal->lang["agenda_repetition"]}</td><td><table><tr><td><select name=\"repete\" id=\"repete\">";
foreach(array("" => $cal->lang["general_non"], "j" => $cal->lang["agenda_jour"], "s" => $cal->lang["agenda_semaine"], "m" => $cal->lang["agenda_mois"], "a" => $cal->lang["agenda_annee"]) as $in => $data)
{
	$selected = ($in == $row["repete"]) ? "selected": "";
	echo "<option value=\"$in\" $selected>$data</option>";
}
echo "</select></td><td><select name=priorite>";

$select=explode("\n", "{$_SESSION["optionGen"]["rdv_type"]}");
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
echo "</select></td></tr></table></td></tr>";
echo "\n<tr><td>{$cal->lang["liste_delais_date_fin"]}</td><td>".$cal->split_date($row["repete_fin"], "_repete_fin")."</td></tr>";


echo $cal->input_hidden("retour", "", "modifier_rdv?close=true");
if(!$_POST["copy"]) echo $cal->input_hidden("id", "1");
if($_POST["nouveau"]=="on" || $_POST["copy"]=="on")
{
	$button_texte=$cal->lang["config_modify_create"];
	echo $cal->input_hidden("action", "", "new");
	echo $cal->input_hidden("dossier", "on");
}else{
	$button_texte=$cal->lang["modifier_donnees_modifier_dossier"];
}
$manual = "{$row["doodle"]}__m";
$manuali = "{$row["doodle"]}__d";
$manualr = "{$row["doodle"]}__";
if($row["doodle"]) echo $cal->input_hidden("manual", "", $manual);
$selectType = $cal->simple_selecteur(array("" => $cal->lang["agenda_standard"], "vacances" => $cal->lang["agenda_vacances"], "anniversaire" => $cal->lang["agenda_anniversaire"]), $row["type"], 2);
$arrProv = array(0 => $cal->lang["general_non"], $reserveid => $cal->lang["agenda_provisoire"]);
$qProv = "select libelle, reserveid from rdv where reserveid > 0 AND (np like '{$_SESSION["user"]}' OR mp like '{$_SESSION["user"]}' OR rdv_pour like '%{$_SESSION["db"]}%') group by reserveid order by reserveid DESC";
$e = mysqli_query($cal->mysqli, $qProv);
while ($r = mysqli_fetch_array($e)) $arrProv["{$r[reserveid]}"] = $cal->smart_html($r[libelle]);

$searchProv = ($row["reserveid"])? "<a href=# onclick=\"window.opener.location = 'http://prolawyer/agenda.php?mode=recherche&typerecherche=agenda&reserveid={$row["reserveid"]}';window.close()\"><img src=\"images/link.png\"></a>":"";
$provType = $cal-> simple_selecteur($arrProv, $row["reserveid"], 2);
echo "\n<tr><td>{$cal->lang["adresses_modifier_type"]}</td><td><select name=\"type\">$selectType</select>$searchProv <select name=\"status\">$selectStatus</select></td></tr>";
echo "\n<tr><td>{$cal->lang["agenda_provisoire"]}</td><td><select name=\"reserveid\">$provType</select></td></tr>";
echo "\n<tr><td>{$cal->lang["agenda_annule"]}</td><td>{$cal->lang["general_oui"]}<input type=\"radio\" value=\"y\" $biffe_check name=\"biffe\">{$cal->lang["general_non"]}<input type=\"radio\" value=\"\" $nonbiffe_check name=\"biffe\"></td></tr>";
echo "\n<tr><td colspan=\"2\"><table width=\"100%\"><tr>";
echo "<td class=\"button\"><button type=submit onclick=\"modifier.submit()\" class=\"button\">$button_texte</button></td>"; //bouton modifier
if($_POST["nouveau"] != "on" && $_POST["copy"] != "on") echo "<td><button type=\"button\" onclick=\"supprimer.submit()\" class=\"buttonattention\">{$cal->lang["operations_supprimer"]}</button></td>"; //bouton supprimer, conditionnel
if($row["reserveid"]) echo "<td><button type=\"button\" onclick=\"supprimer_autres.submit()\" class=\"buttonattention\">{$cal->lang["agenda_supprimer_autres"]}</button></td>";
if($row["doodle"] && $row["doodleset"] != "d") echo "<td><button type=\"button\" onclick=\"supprimer_doodle.submit()\" class=\"buttonattention\">{$cal->lang["agenda_supprimer_doodle"]}</button></td>";
if($row["doodleset"]) echo "<td><button type=\"button\" onclick=\"reactiver_doodle.submit()\" class=\"buttonattention\">{$cal->lang["agenda_reactiver_doodle"]}</button></td>";
if($cal->androlawyerClient) echo "<td><button type=\"button\" onclick=\"JCB.finishActivity()\" class=button>{$cal->lang["general_fermer"]}</button></td>";
else echo "<td><button type=\"button\" onclick=\"self.close()\" class=button>{$cal->lang["general_fermer"]}</button></td>";
echo "</tr></table></td></tr>";
echo "\n</table>\n</form>";

//Formulaires cachés
echo "\n<form name=\"supprimer\" method=\"post\" action=\"maj_op.php\">";
echo $cal->input_hidden("action", "", "delete");
echo $cal->input_hidden("retour", "", "modifier_rdv?close=true");
echo $cal->input_hidden("id", "1");
if($row["doodle"]) echo $cal->input_hidden("manual", "", $manual);
echo "</form>";
echo "\n<form name=\"supprimer_autres\" method=\"post\" action=\"maj_op.php\">";
echo $cal->input_hidden("action", "", "delete");
echo $cal->input_hidden("retour", "", "modifier_rdv?close=true");
echo $cal->input_hidden("reserveid", "", "{$row[reserveid]}");
echo $cal->input_hidden("notlike", "", "id__{$row[id]}");
if($row["doodle"]) echo $cal->input_hidden("manual", "", $manual);
echo "</form>";
echo "\n<form name=\"supprimer_doodle\" method=\"post\" action=\"maj_op.php\">";
echo $cal->input_hidden("action", "", "delete");
echo $cal->input_hidden("retour", "", "modifier_rdv?close=true");
echo $cal->input_hidden("id", "", "-1");
if($row["doodle"]) echo $cal->input_hidden("manual", "", $manuali);
echo "</form>";
echo "\n<form name=\"reactiver_doodle\" method=\"post\" action=\"maj_op.php\">";
echo $cal->input_hidden("action", "", "delete");
echo $cal->input_hidden("retour", "", "modifier_rdv?close=true");
echo $cal->input_hidden("id", "", "-1");
if($row["doodle"]) echo $cal->input_hidden("manual", "", $manualr);
echo "</form>";
//echo "<a href=# onclick='toto()'>toto</a><br><div id=tata></div>";
if($_GET["close"] == "true")
{
	 if($cal->androlawyerClient) echo "<script>JCB.finishActivity()</script>";
	 else echo "<script>reloadFrame()</script>";
}
//$LASTPOST=unserialize(stripslashes($_COOKIE["last_post"]));
//if($_COOKIE["last_visited"] == "agenda" && $LASTPOST["type"] != "vacances" && $LASTPOST["template"] == "agenda" && $_GET["close"] == "true") echo "<script>window.opener.document.forms['self_reload'].submit();self.close()</script>";
//elseif($_GET["close"] == "true") echo "<script>if(window.opener.document.forms['self_reload']) window.opener.document.forms['self_reload'].submit();//self.close()</script>";
/*$cal->tab_affiche($LASTPOST);
$cal->tab_affiche($_COOKIE);*/

echo "<div id=keyCode></div>";
$cal->close();
?> 
