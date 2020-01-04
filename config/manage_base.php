<?php
require_once("../inc/autoload.php");
require_once("../externe/Mysqldump.php");
session_start();
$doc=new prolawyer();
$t = strftime("%Y-%m-%d-%H:%M:%S");
$dump = new Ifsnop\Mysqldump\Mysqldump("mysql:host={$_SESSION["mysqlServer"]};dbname={$_SESSION["dbName"]}", $_SESSION["dbAdmin"], $_SESSION["dbPwd"]);
header("Content-Disposition: attachment; filename=\"dump_{$_SESSION["dbName"]}_$t.sql\"");
$dump->start('/tmp/dumpprolawyertest.sql');
