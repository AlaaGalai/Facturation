<?php
require_once("../inc/autoload.php");
session_start();
error_reporting(7);
if(!$_POST["moduleName"]) header("Location: ../resultat_recherche.php");
require_once("../inc/autoload.php");

$required = false;
foreach(array($_SESSION["optionsPath"].$_SESSION["slash"], "../") as $tDir)
{
	if(is_file("{$tDir}modules/auto/{$_POST["moduleName"]}.inc.php"))
	{
		require_once("{$tDir}modules/auto/{$_POST["moduleName"]}.inc.php");
		$doc = new $_POST["moduleName"];
		$required = true;
	}
}
if (! $required)  $doc=new prolawyer;
$doc->title();
$doc->body(2, "");
if(!$_POST["print"]) $doc->entete();
foreach(array($_SESSION["optionsPath"].$_SESSION["slash"], $doc->settings["root"]) as $tDir) if(is_file("{$tDir}modules/auto/{$_POST["moduleName"]}.inc.php"))
{
	require_once("{$tDir}modules/auto/{$_POST["moduleName"]}.php");
}
?>
