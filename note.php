<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->title();
// $doc->tab_affiche();

// $doc->title();
// $doc->body(2, "");
// $doc->entete();

$r = array
(
	"PERSONNE" => $doc->lang["notes_personne"],
	"DATE" => $doc->split_date("NOW"),
);

echo "<form action = maj_op.php method=POST>";
echo $doc->doSingleTemplate("templates/notes.html", $r);
echo "</form>";

$doc->close();
?>
