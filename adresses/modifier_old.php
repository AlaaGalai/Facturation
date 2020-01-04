<?php
require_once("../inc/autoload.php");
session_start();
$vcard =  (isset($_REQUEST["vcard"]) && $_REQUEST["vcard"])? True:False;

$doc=new prolawyer;
$doc->initZefix();
if($_GET["id"]) $_POST["id"] = $_GET["id"];
if(!isset($_POST["id"]) && !isset($_POST["nouveau"]))
{
	header("Location:./index.php");
	die();
}
if (!$vcard)
{
	$doc->title("<script type=\"text/javascript\" src=\"../externe/XHRConnection.js\"></script>");
	$doc->body(2, "document.modifier.elements[0].focus()");
	$doc->entete();
}

if($_POST["nouveau"]=="on")
{
	$requete="insert into adresses set nom=''";
	$exec_requete=mysqli_query($doc->mysqli, "$requete");
//	echo "<br>$requete";
	$_POST["id"]=mysqli_insert_id($doc->mysqli);
}
//phpinfo();
$requete="select * from adresses where id like '{$_POST["id"]}'";
$exec_requete=mysqli_query($doc->mysqli, "$requete");
while($row=mysqli_fetch_array($exec_requete, MYSQLI_ASSOC))
{
	foreach($row as $design=>$val)
	{
//  		echo "<br>'$design' a pour valeur $val";
// 		if($design == "tel" || $design == "telprive" ||$design == "natel" || $design == "natelprive" ||$design == "fax" || $design == "faxprive")
		$v = $doc->getClickableItem($design, $val);
		$vdesign = "v$design";
		if(!$_REQUEST["vcard"])$val = $v["val"];
		$class["$design"] = $v["class"];
		$sup["$design"] = $v["sup"];
		$$design = $val;
		
		//echo "<br><br>utf8: ", iconv('UTF-8', 'ISO-8859-1', $val);
		//echo "<br><br>iso: ", iconv('ISO-8859-1', 'UTF-8', $val);
		//if(md5(iconv('UTF8', 'UTF8', $val)) == md5($val)) $$vdesign = html_entity_decode($val).".utf8";
		//else $$vdesign = html_entity_decode(iconv('ISO-8859-1', 'UTF8', $val)).".iso";
		//$val = $doc->swissToInt($val);
		//echo "<br>$vdesign => $val";
		$$vdesign = html_entity_decode($val, ENT_COMPAT, "UTF-8");
	}
}

//Traitement de l'adresse complete

$completeAddr = '';
$completeAddr .= stripslashes($titre);
if($titre<>"") $completeAddr .= "\n";
$vcardName = '';
$vcardName .= stripslashes($prenom);
if($prenom<>"") $vcardName .= " ";
$vcardName .= stripslashes($nom);
$completeAddr .= "$vcardName\n";
$completeAddr .= stripslashes($fonction);
if($fonction<>"") $completeAddr .= "\n";
$completeAddr .= stripslashes($adresse);
if($adresse<>"") $completeAddr .= "\n";
$completeAddr .= stripslashes($cp);
if($cp<>"") $completeAddr .= "\n";
$completeAddr .= $zip;
if($zip<>0) $completeAddr .= " ";
$completeAddr .= $ville;
$completeVAddr = preg_replace('#\n#', "\\n ", html_entity_decode($completeAddr, ENT_COMPAT, "UTF-8"));
$vcard = "BEGIN:VCARD\nVERSION:2.1\n";
$vcard .= "FN:$vcardName\n";
$vcard .= "N:$nom;$prenom;;$titre;\n"; //Nom;prénom;autres,prenoms;titre;titre honorifique
$vcard .= "ADR:$vcp;$vfonction;$vadresse;$vville;$vcanton;$vzip;$vpays\n";
$vcard .= "LABEL:$completeVAddr\n";
if($tel) $vcard .= "TEL;WORK:$vtel\n";
if($natel) $vcard .= "TEL;WORK;CELL:$vnatel\n";
if($fax) $vcard .= "TEL;WORK;FAX:$vfax\n";
if($mail) $vcard .= "EMAIL;WORK:$mail\n";

if($telprive) $vcard .= "TEL;HOME:$vtelprive\n";
if($natelprive) $vcard .= "TEL;HOME;CELL:$vnatelprive\n";
if($faxprive) $vcard .= "TEL;HOME;FAX:$vfaxprive\n";
if($mailprive) $vcard .= "EMAIL;HOME:$mailprive\n";
$vcard .= "END:VCARD\n";
$vcard = html_entity_decode($vcard);
$uVcard = urlencode($vcard);
//echo "<br>'".urlencode(preg_replace("#'#", "", $vcard))."'<br>";
//echo "<br>'".$vcard."'<br>";
// die();
//echo "<img src='http://chart.apis.google.com/chart?chs=500x500&cht=qr&chld=H&chl=\"" . urlencode(preg_replace("#'#", "", $vcard)) . "'/>";
if($_REQUEST["vcard"])
{
	$nom = preg_replace("#[[:space:]]+#", "_", $nom);
	header('Content-Type: text/vcard');  
	header("Content-Disposition: attachment; filename=\"$nom.VCF\"");
	echo $vcard;
	die();
}

//Traitement des infos contenues dans les remarques
$remArr = preg_split('#\n#', $rem);
$remStr = "<span id=remstr class='textbl'>";
$remNum = 0;
foreach ($remArr as $line)
{
	$click = $doc->getClickableItem("other$remNum", $line);
	if ($click["val"]) $remStr .= "<span {$click["class"]}>{$click["sup"]}</span>";
	else $remStr .= "&nbsp;";
	$remStr .= "\n<br>";
	$remNum ++;
}
if ($remNum < 8) $remNum = 8;
$remStr .= "</span>";
echo "<table width=\"95%\" align=center border=\"0\">
<tr><td colspan=\"2\">
<h2 onMouseOver=\"show('{$doc->lang["adresses_modifier_note_par"]} $np ($nple) ; {$doc->lang["adresses_modifier_modifie_par"]} $mp ($mple)')\">{$doc->lang["adresses_modifier_h2"]} {$_POST["id"]}&nbsp;:</h2>".$doc->searchZefixBox("zefix", "$prenom $nom", $_POST["id"], "")."&nbsp;".$doc->searchZefixBox("telsearch", "$prenom $nom", $_POST["id"], "")."&nbsp;<a href='https://www.google.ch/maps/search/$adresse $zip $ville' target=new><img src=../images/maps.png></a></td></tr>

<tr><td>
<form name=modifier id=modifier method=\"post\" action=\"../maj_op.php\">
<table>";
echo $doc->input_hidden("retour", "", "adresses/modifier");
echo "\n<tr><td>{$doc->lang["adresses_modifier_type"]}&nbsp;:</td><td><select name=\"type\">";
$select=explode("\n", $_SESSION["optionGen"]["ltype"]);
$ok=0;
foreach($select as $option)
{
	echo "<option value=\"", trim($option), "\"";
	if(trim($type)==trim($option))
	{
		echo " selected";
		$ok=1;
	}
	echo ">$option";
}

if($ok==0) echo "<option value=\"$type\" selected>$type";
$tsociete=$doc->simple_selecteur($doc->societes, $typesociete, 2);
echo "</select></td>
<td>{$doc->lang["adresses_modifier_salut"]}&nbsp;:</td><td><input type=\"text\" name=\"salut\" value=\"$salut\"></td></tr>
<tr><td>{$doc->lang["adresses_modifier_titre"]}&nbsp;: </td><td><input type=\"text\" name=\"titre\" value=\"$titre\"></td><td>{$doc->lang["modifier_donnees_nosociete"]}&nbsp;: </td><td><input type=text size=15 name=nosociete id=nosociete value=\"$nosociete\"></td></tr>
<tr><td>{$doc->lang["adresses_modifier_prenom"]}&nbsp;: </td><td><input type=\"text\" name=\"prenom\" value=\"$prenom\"></td><td>{$doc->lang["adresses_modifier_nom"]}&nbsp;: </td><td><input type=\"text\" name=\"nom\" value=\"$nom\"></td></tr>
<tr><td>{$doc->lang["adresses_modifier_fonction"]}&nbsp;: </td><td><input type=\"text\" name=\"fonction\" value=\"$fonction\"></td><td>{$doc->lang["modifier_donnees_type_societe"]}&nbsp;: </td><td><select name=typesociete>$tsociete</select></td></tr>
<tr><td>{$doc->lang["adresses_modifier_adresse"]}&nbsp;: </td><td><input type=\"text\" name=\"adresse\" value=\"$adresse\"></td><td>{$doc->lang["adresses_modifier_cp"]}&nbsp;: </td><td><input type=\"text\" name=\"cp\" value=\"$cp\"></td></tr>
<tr><td>{$doc->lang["adresses_modifier_zip"]}&nbsp;: </td><td><input type=\"text\" name=\"zip\" value=\"$zip\"></td><td>{$doc->lang["adresses_modifier_ville"]}&nbsp;: </td><td><input type=\"text\" name=\"ville\" value=\"$ville\"></td></tr>
<tr><td>{$doc->lang["modifier_donnees_canton"]}&nbsp;: </td><td><input type=\"text\" name=\"canton\" value=\"$canton\"></td><td>{$doc->lang["adresses_modifier_pays"]}&nbsp;: </td><td><input type=\"text\" name=\"pays\" value=\"$pays\"></td></tr>
<tr><td>{$doc->lang["adresses_modifier_ccp"]}&nbsp;: </td><td><input type=\"text\" name=\"ccp\" value=\"$ccp\"></td></tr>
<tr><td colspan=\"2\">&nbsp;</td></tr>
<tr><td colspan=\"2\"><b>{$doc->lang["adresses_modifier_prof"]}&nbsp;:</b></td></tr>
<tr><td {$class["tel"]}>{$sup["tel"]}&nbsp;{$doc->lang["adresses_modifier_tel"]}&nbsp;: </td><td><input type=\"text\" name=\"tel\" value=\"$tel\"></td>
<td {$class["natel"]}>{$sup["natel"]}&nbsp;{$doc->lang["adresses_modifier_natel"]}&nbsp;: </td><td><input type=\"text\" name=\"natel\" value=\"$natel\"></td></tr>
<tr><td {$class["fax"]}>{$sup["fax"]}&nbsp;{$doc->lang["adresses_modifier_fax"]}&nbsp;: </td><td><input type=\"text\" name=\"fax\" value=\"$fax\"></td>
<td>{$sup["mail"]}&nbsp;{$doc->lang["adresses_modifier_mail"]}&nbsp;: </td><td><input type=\"text\" name=\"mail\" value=\"$mail\"></td></tr>
<tr><td colspan=\"2\"><b>{$doc->lang["adresses_modifier_prive"]}&nbsp;:</b></td></tr>
<tr><td {$class["telprive"]}>{$sup["telprive"]}&nbsp;{$doc->lang["adresses_modifier_tel"]}&nbsp;: </td><td><input type=\"text\" name=\"telprive\" value=\"$telprive\"></td>
<td {$class["natelprive"]}>{$sup["natelprive"]}&nbsp;{$doc->lang["adresses_modifier_natel"]}&nbsp;: </td><td><input type=\"text\" name=\"natelprive\" value=\"$natelprive\"></td></tr>
<tr><td {$class["faxprive"]}>{$sup["faxprive"]}&nbsp;{$doc->lang["adresses_modifier_fax"]}&nbsp;: </td><td><input type=\"text\" name=\"faxprive\" value=\"$faxprive\"></td>
<td>{$sup["mailprive"]}&nbsp;{$doc->lang["adresses_modifier_mail"]}&nbsp;: </td><td><input type=\"text\" name=\"mailprive\" value=\"$mailprive\"></td></tr>
<tr><td colspan=\"2\">&nbsp;</td></tr>
<tr><td>{$doc->lang["adresses_modifier_remarques"]}&nbsp;: </td><td colspan=3><table><tr><td width=20 style='vertical-align:top'>$remStr</td><td><textarea class='textbl' cols=40 rows=$remNum name=\"rem\">$rem</textarea></td></tr></table></td></tr>
<tr><td colspan=2 class=button onClick=\"document.getElementById('modifier').submit()\">{$doc->lang["adresses_modifier_modifier"]}<input type=\"hidden\" name=\"id\" value=\"$id\"><button type=submit></button></td></tr>";


echo "\n</table></form>";

echo "\n<br>";
/*<table><tr><td </td>";/*<td class=button onClick=\"document.getElementById('annuler').submit()\">{$doc->lang["adresses_modifier_annuler"]}</td>";
$vadresse=$fonction.$adresse.$zip.$ville;
echo "</td></tr></table></td>*/
echo "<td valign=top align=\"center\" width=\"50%\">
<br>
<textarea class='textbl' cols=30 rows=8 tabindex=1 readonly=1>";
echo $completeAddr;
echo "</textarea>
<br>
<img src='../random_display.php?qrcode=true&string=$uVcard'><br>
<a href='modifier.php?id={$_POST["id"]}&vcard=1'>VCARD</a>
</td>
</tr>
</table>";

//On crée un formulaire pour recharger la page
echo $doc->self_reload();

$doc->close();
?>
