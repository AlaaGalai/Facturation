<?php
session_start();
// $a=microtime();
require_once("./inc/autoload.php");
$doc = new SPECIAL_CLASS(); #SPECIAL_CLASS can be defined in specific/inc and extends prolawyer.class.php
$doc->title();
$doc->body(2);

#......#

$doc->close();
?>
