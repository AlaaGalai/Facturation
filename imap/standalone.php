<?php
require_once("../inc/autoload.php");
session_start();

$doc=new prolawyer;
$imap=new IMAP; //si on initialise la classe IMAP après avoir chargé etude::body(), la classe IMAP ne peut pas réinitialiser la classe etude dont elle a besoin
$doc->connection();
//$doc->tab_affiche($_GET);

$account=($_GET["account"])? $_GET["account"] : "imap1";
$msgid=($_GET["msgid"])?$_GET["msgid"]:"1";
$mid=($_GET["mid"])?$_GET["mid"]:"1";
$mbox=($_GET["mailbox"])?rawurldecode($_GET["mailbox"]):"INBOX";
$partid=($_GET["partid"])?$_GET["partid"]:"1";
$encoding=($_GET["encoding"])?$_GET["encoding"]:"";
$name=($_GET["name"])? $imap->flatMimeDecode($_GET["name"]):"";
$type=($_GET["type"])?$_GET["type"]:"";
$subtype=($_GET["subtype"])?$_GET["subtype"]:"";
//$imap->mailbox=($_GET["mailbox"])? $_GET["mailbox"] : "INBOX.Informatique";

$imap->host=$doc->option_gen["$account"]["host"];
$imap->port=$doc->option_gen["$account"]["port"];
$imap->username=$doc->option_gen["$account"]["username"];
$imap->password=$doc->option_gen["$account"]["password"];

$imap->connection("", "", "", "", $mbox);
$content=$_GET["source"] ? "text/plain" : $_GET["type"]."/".$_GET["subtype"];
	
header("Content-type: $content");
$disposition=$_GET["source"] ? "Content-Disposition: attachment; filename=\"source.txt\"" : "Content-Disposition: attachment; filename=\"$name\"";
header($disposition);
echo $imap->get_message_body($mid, $partid, $encoding, $subtype, "nonl2br", $type, $name);
?>
