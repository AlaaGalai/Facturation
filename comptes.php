<?php
if(is_file("override/session_override.php"))
{
	require("override/session_override.php");
}else{
	session_name("prolawyer");
}
session_start();
// foreach($_SESSION as $n => $v) echo "<br>$n a pour valeur $v";
require_once("./inc/autoload.php");
$doc=new prolawyer;
// $doc->tab_affiche($_SESSION);
$doc->tab_affiche();
$doc->title();
$doc->body(2, "document.getElementById('form').nom_client.focus()");
$doc->entete();
// $name=$doc->init_to_name();

if($_POST["up"])
{
	echo "bouh";
	$q = "insert into comptes set libelle = '{$_POST["libelle"]}', no = '{$_POST["no"]}'";
	$e = mysqli_query($doc->mysqli, $q);
	echo "Resultat: $e ".mysqli_error($doc->mysqli);
}


$q = "select * from comptes order by no";
$e = mysqli_query($doc->mysqli, $q);

echo "\n<table>";
while($r = mysqli_fetch_array($e)) echo "\n<tr><td>No: </td><td><input type=text value={$r["no"]} name=no></td><td>Compte: </td><td><input type=text name=libelle value=\"{$r["libelle"]}\"></td>";
echo "\n</table>";

echo "\n<form action=\"./comptes.php\" method=POST>
<table>
<tr><td>No: </td><td><input type=text name=no></td><td>Compte: </td><td><input type=text name=libelle></td>
<tr><td colspan=2><input type=submit></td></tr>
</table>
<input type=hidden name=up value=on>
</form>";
?>