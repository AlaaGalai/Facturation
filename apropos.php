<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->title();
$sizeH = $_REQUEST["size_h"] ? $_REQUEST["size_h"]:400;
$sizeV = $_REQUEST["size_v"] ? $_REQUEST["size_v"]:400;
$doc->body("1", "resize('$sizeH','$sizeV')", "self.close()", "d0ffd0");

if($_REQUEST["fichier"])
{
	if(is_file("./{$_REQUEST["fichier"]}_{$_SESSION["lang"]}.txt")) $fichier = "./{$_REQUEST["fichier"]}_{$_SESSION["lang"]}.txt";
	else $fichier = "./{$_REQUEST["fichier"]}.txt";
	$texte = file_get_contents($fichier);
	echo preg_replace ("/\w[A-Z]{+}\w/", "<h2>\\1</h2>", $doc->smart_html(nl2br($texte)));
}
else
echo "<table><tr><td><img src=\"./images/prolawyer.png\"></td><td><h1\">Prolawyer {$doc->settings["version"]}</h1></td></tr></table>
<br><h2>{$doc->lang["apropos_maintenance"]}</h2>
<ul>
  <li>Olivier Subilia</li>
</ul>
<h2>{$doc->lang["apropos_programmation"]}</h2>
<ul>
  <li>Olivier Subilia ({$doc->lang["apropos_tout"]})</li>
  <li>Lucien ({$doc->lang["apropos_pour"]} ra.php)</li>
</ul>
<h2>{$doc->lang["apropos_traductions"]}</h2>
<ul>
  <li>Olivier Subilia (English)</li>
  <li>Sonia Delgado (English)</li>
  <li>Alessio Igor Bodani (Italiano)</li>
  <li>Paul Peyrot (Deutsch)</li>
  <li>Annette Tauschert (Deutsch)</li>
  <li>Eduardo Redundo (Espa&ntilde;ol)</li>
</ul>
<h2>{$doc->lang["apropos_remerciements"]}</h2>
<ul>
  <li>Liliane Subilia</li>
  <li>Judith Bonzon (Help file)</li>
  <li>{$doc->lang["apropos_toute_etude"]}</li>
</ul>";
$doc->close();
?>
