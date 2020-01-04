<?php
require_once("../inc/autoload.php");
session_start();
error_reporting(7);
$doc=new biblio;
$doc->connection();
$doc->title();
$doc->body(2, "document.getElementById('form').titre.focus()");
$doc->entete();

echo "<h2>{$doc->lang["biblio_recherche_recherche"]} :</h2>
<form name=\"form\"  id=\"form\" method=\"post\" action=\"./liste_ouvrages.php\">
{$doc->lang["biblio_recherche_titre"]} :&nbsp;<input type=\"text\" name=\"titre\"><br><br>
{$doc->lang["biblio_recherche_auteur"]} :&nbsp;<input type=\"text\" name=\"auteur\"><br><br>";
if($_SESSION["biblioType"] == "0")
{
	echo $doc->table_open();
	echo $doc->categories();
	echo $doc->table_close();
}
echo $doc->button("{$doc->lang["adresses_index_rechercher"]}");
echo $doc->input_hidden("doRecherche", "", "on");
echo "\n</form>";
$doc->close();
?>