<?php
require_once("../inc/autoload.php");
session_start();
$doc=new prolawyer(false);
$doc->addStyle = "petit";
//$doc->connection();
$doc->title();
$doc->body(2, "document.forms['modify'].elements['forceUser'].focus()");
$doc->entete();

$nextPage=($_GET["update"]) ? "actu.php":"";
$nextPage=($_REQUEST["nextPage"]) ? $_REQUEST["nextPage"]:"";
if(!$nextPage) $nextPage = "modify.php";
echo "<h2>{$doc->lang["config_login_h2"]} {$doc->version}</h2>
({$doc->lang["config_login_remarque"]})";
echo "<form method=\"post\" action=\"./$nextPage\" id=\"modify\">";
echo $doc->lang["config_login_nom"]."&nbsp;:&nbsp;".$doc->input_texte("forceUser")."<br>";
echo $doc->lang["config_login_pwd"]."&nbsp;:&nbsp;".$doc->input_pwd("forcePwd")."<br>";
echo $doc->input_hidden("new_check", "", "on");
echo $doc->input_hidden("check_global_install", "", "on");
echo $doc->button($doc->lang["config_login_login"]);
echo "\n</form>";
$doc->close();
?>
