<?php
require_once("../inc/autoload.php");
session_start();
$vcard =  (isset($_REQUEST["vcard"]) && $_REQUEST["vcard"])? True:False;

$doc=new prolawyer;
$doc->initZefix();
if($_GET["id"]) $_POST["id"] = $_GET["id"];
if(!isset($_POST["id"]) && !isset($_POST["nouveau"]))
{
	header("Location:./resultat.php");
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
	$_POST["id"] = "forceCreate";
}
$doc->affiche_personne("identite", $_POST["id"]);
// $doc->tab_affiche($doc->donneesDuDossier["identite"]);
$uVcard = rawurlencode($doc->donneesDuDossier["identite"]["vcard"]);
foreach(array('np', 'nple', 'mp', 'mple') as $champ) $$champ = $doc->donneesDuDossier["identite"]["$champ"];

if($_REQUEST["vcard"])
{
	header('Content-Type: text/vcard');  
	header("Content-Disposition: attachment; filename=\"{$doc->donneesDuDossier["identite"]["nomVcard"]}.vcf\"");
	echo $doc->donneesDuDossier["identite"]["vcard"];
	die();
}

if($_POST["id"] == "forceCreate") $h2 = $doc->lang["adresses_resultat_nouvelle_fiche"];
else $h2 = "{$doc->lang["adresses_modifier_h2"]} {$_POST["id"]}";

echo "<h2 {$doc->qui_fait_quoi("$np", "$nple", "$mp", "$mple")}>$h2</h2>";
echo $doc->table_open();
echo "<tr><td>";
echo $doc->affichage_total;
echo "</td><td valign=top><textarea class='textbl' onclick=select() cols=30 rows=8 tabindex=1 readonly=1>";
echo $doc->donneesDuDossier["identite"]["completeAddr"];
echo "</textarea>";
echo "<br><img src='../random_display.php?qrcode=true&string=$uVcard'><br>
<a href='modifier.php?id={$_POST["id"]}&vcard=1'>VCARD</a></td></tr>";
echo $doc->table_close();


//On crée un formulaire pour recharger la page
echo $doc->self_reload();

$doc->close();
?>
