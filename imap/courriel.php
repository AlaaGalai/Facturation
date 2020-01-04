<?php
require_once("../inc/autoload.php");
session_start();

$doc=new prolawyer;

$imap=new IMAP; //si on initialise la classe IMAP après avoir chargé etude::body(), la classe IMAP ne peut pas réinitialiser la classe etude dont elle a besoin
$doc->connection();
$doc->title("<link rel=\"stylesheet\" type=\"text/css\" href=\"../externe/xc/xc.css\" />\n		<script type=\"text/javascript\" src=\"../externe/xc/xc.js\"></script><ul id=\"x\">\n		<script type=\"text/javascript\" src=\"../externe/XHRConnection.js\"></script>");
$doc->body(2, "xcSet('x', 'xc', 'js')");
$doc->entete();
// $doc->tab_affiche($_POST);
// echo "ce sont les options de la page principale";


echo "\n<div id=\"noid\"></div>\n<script language=JavaScript>\nvar loading = '$imap_courriel_loading';\nvar lastSelected=document.getElementById('noid');lastMbSelected=lastSelected;</script>";
echo $doc->form("imap/courriel.php", "", "", "", "change_mailbox<td>", "mailbox", "");

$imap->connection("", "", "", "", $mbox);
$mailboxes = $imap->get_mailboxes();
$headers = $imap->get_message_headers($_POST["msgid"]);
$structure = $imap->get_structure($_POST["mid"]);
$body = $imap->get_message_body($_POST["mid"], $_POST["partid"], $_POST["encoding"], $_POST["subtype"], $_POST["nonl2br"], $_POST["type"], $_POST["name"], $_POST["disposition"]);
// $body = $imap->get_message_body($mid, $_POST["partid"], $encoding, $subtype, $nonl2br, $type, $name);
//$raw_body = $imap->get_message_body($mid);

echo "<table border=\"1\" width=\"100%\" style=border-style:none><tr><td rowspan=3 valign=top style=border-style:none>";
echo $mailboxes;
echo "</td><td valign=top><div id=headers>";
echo $headers;
echo "\n</div></td></tr><tr><td valign=top><div id=\"body\">";
echo $doc->table_open("width=100%");
echo "<tr><td>$structure</td></tr>";
echo "<tr><td><hr></td></tr>";
echo "<tr><td>$body</td></tr>";
echo $doc->table_close();
echo "\n</div></td></tr></table>";

$doc->close();
?>
