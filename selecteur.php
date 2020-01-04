<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->title();
$doc->body();

// $dir=$_POST["dir"] ? $_POST["dir"]:$_GET["dir"]? $_GET["dir"]:".";
// $doc->browsedir($dir);
$init=$_GET["init"];
// $referer=$_GET["referer"];
$formnamecolor=$_GET["formnamecolor"];
$formname=$_GET["formname"];

// echo $doc->color_all["$init"], ", $init, ", "$formnamecolor<br>"; 
echo $doc->table_open("width=100%");
echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
echo "<tr><td>&nbsp;</td><td>".$doc->color_select(TRUE, TRUE, $doc->color_all["$init"], "$init", "$formnamecolor", FALSE, "$formnamecolor", "$formname")."</td><td>&nbsp;</td></tr>";
echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
echo "<tr><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>";
echo $doc->table_close();
?>