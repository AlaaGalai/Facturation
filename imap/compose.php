<?php
require_once("../inc/autoload.php");
session_start();

$doc=new prolawyer;
$imap=new IMAP; //si on initialise la classe IMAP après avoir chargé etude::body(), la classe IMAP ne peut pas réinitialiser la classe etude dont elle a besoin
$doc->connection();
$doc->title();


if($_POST["send"] == "on")
{
	$_POST["texte"] = wordwrap($_POST["texte"], 68, "\n> ");
	$envelope["from"]= "joe@example.com";
	$envelope["to"]  = $_POST["destinataire"];
	$envelope["cc"]  = $_POST["cc"];
	
	$part1["type"] = TYPEMULTIPART;
	$part1["subtype"] = "mixed";
	
	$part2["type"] = TYPETEXT;
	$part2["subtype"] = "plain";
	$part2["description"] = "description3";
	$part2["contents.data"] = $_POST["texte"];
	
	$body[1] = $part1;
 	$body[2] = $part2;
	$mimetexte = imap_mail_compose($envelope, $body);
	
//	ini_set("SMTP", "toto");
//	phpinfo();
// 	$success = mail($_POST["destinataire"], $_POST["sujet"], $_POST["texte"]);
	$success = mail($_POST["destinataire"], $_POST["sujet"], $mimetexte);
}

if($success)
{
	$doc->body("1", "resize('700','500')", "self.close()", "d0ffd0");
	echo nl2br(imap_mail_compose($envelope, $body));
	echo "<h2>$imap_compose_success</h2>";
	
}else{
	$doc->body("1", "resize('700','500')");
	
	if($_POST["send"] == "on") echo "<h2 class=\"attention\">$imap_compose_error</h2>";
	echo "\n<table width=\"100%\">";
	echo "\n<form action=\"{$doc->settings["root"]}imap/compose.php\" method=\"post\">";
	echo "\n<tr><td>$imap_courriel_destinataire :</td><td><input type=\"text\" name=\"destinataire\" value=\"{$_POST["destinataire"]}\"></td></tr>";
	echo "\n<tr><td>$imap_courriel_sujet :</td><td><input type=\"text\" name=\"sujet\" value=\"{$_POST["sujet"]}\"></td></tr>";
	echo "\n<tr><td>$imap_courriel_texte :</td><td><textarea name=\"texte\" rows=\"20\" cols=\"70\">{$_POST["texte"]}</textarea></td></tr>";
	echo "\n<tr><td>", $doc->button("$operations_valider"), "</td></tr>";
	echo $doc->input_hidden("send", "", "on");
	echo "\n</table>";
	echo "\n</form>";
}
$doc->close();
?>
