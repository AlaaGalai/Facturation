<?php
require_once("../inc/autoload.php");
session_start();
$doc=new prolawyer;
$doc->title();
$doc->body(0, "document.forms[0].submit()");


?>