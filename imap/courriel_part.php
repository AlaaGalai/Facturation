<?php
require_once("../inc/autoload.php");
session_start();

$doc=new prolawyer;
$imap=new IMAP; //si on initialise la classe IMAP après avoir chargé etude::body(), la classe IMAP ne peut pas réinitialiser la classe etude dont elle a besoin
$doc->connection();
$doc->title();


foreach($_GET as $nom=> $val) $_POST["$nom"] = $val;
// $doc->tab_affiche($_POST);
// echo "ce sont les options de la page secondaire";
// $doc->tab_affiche($_GET);
// $msgid=($_POST["msgid"])?$_POST["msgid"]:"";
// $mid=($_POST["mid"])?$_POST["mid"]:"1";
// $mbox=($_POST["mailbox"])?$_POST["mailbox"]:"INBOX";
// $id=(isset($_POST["id"]))?$_POST["id"]:"";
// $encoding=($_POST["encoding"])?$_POST["encoding"]:"";
// $name=($_POST["name"])?$_POST["name"]:"";
// $type=($_POST["type"])?$_POST["type"]:"";
// $subtype=($_POST["subtype"])?$_POST["subtype"]:"";

$imap->connection("", "", "", "", $_POST["mailbox"]);
$mailboxes = $imap->get_mailboxes();

// $imap->connection("", "", "", "", $mbox);
// $mailboxes = $imap->get_mailboxes();
// $headers = $imap->get_message_headers($_POST["msgid"]);
// $structure = $imap->get_structure($_POST["mid"]);
// $body = $imap->get_message_body($_POST["mid"], $_POST["partid"], $_POST["encoding"], $_POST["subtype"], $_POST["nonl2br"], $_POST["type"], $_POST["name"], $_POST["disposition"]);


if($_POST["headers"])
{
	$headers = $imap->get_message_headers($_POST["msgid"]);
}	echo $headers;

if($_POST["body"])
{
	$structure = $imap->get_structure($_POST["mid"]);
	$body = $imap->get_message_body($_POST["mid"], $_POST["partid"], $_POST["encoding"], $_POST["subtype"], $_POST["nonl2br"], $_POST["type"], $_POST["name"], $_POST["disposition"]);
	echo $doc->table_open("width=100%");
	echo "<tr><td>$structure</td></tr>";
	echo "<tr><td><hr></td></tr>";
	echo "<tr><td>$body</td></tr>";
	echo $doc->table_close();
}
if($_POST["structure"]) echo $structure;
?>
