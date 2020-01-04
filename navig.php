<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new navigation;
if(!$_REQUEST["display"])
{
	$doc->title();
	$doc->body();
}

$mbx=$_POST["mbx"] ? $_POST["mbx"]:$_GET["mbx"]? $_GET["mbx"]:"";
if($mbx)
{
	$doc->browseMbx($mbx);
	die();
}

$dir=$_POST["dir"] ? $_POST["dir"]:$_GET["dir"]? $_GET["dir"]:".";
$doc->browsedir($dir);

?>
