<?php
require_once("../inc/autoload.php");
session_start();

error_reporting(7);
if($_GET["delaisName"]) $_POST["delaisName"]=$_GET["delaisName"];
require_once("../inc/autoload.php");
$doc=new delais;
// $doc->tab_affiche();
if(!$_POST["delaisName"])$_POST["delaisName"]="ch_generique";
require("./dls/{$_POST["delaisName"]}.php");
$doc->getTitle();
$doc->title();
if(!$_POST["standalone"])
{
	$doc->body(2);
	$doc->entete();
}
else $doc->body();
$doc->writePage();
$doc->close();
?>
