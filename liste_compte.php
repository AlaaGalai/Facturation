<?php
require_once("./inc/autoload.php");
session_start();
$doc=new prolawyer;
// $doc->tab_affiche();
$doc->title();
$doc->body(2);
$doc->entete();

$comptes = array("transit" => $doc->lang["operations_transit"]);


$formComptes = "<form action='./liste_compte.php' method='POST'>\n<select name=compte[] multiple>\n<option value=''></option>";

$select = $doc->simple_selecteur($comptes, $_POST["compte"], 2);

$formComptes .= "$select</select>";
$formComptes .= $doc->button("select");
$formComptes .= "</form>";

echo $formComptes;
//formulaire pour changer de dossier (caché)
echo "\n<form action=\"operations.php\" method=\"post\" name=\"changedossier\" target='_new'>";
echo "\n", $doc->input_hidden("nodossier", "", "2");
echo "\n", $doc->input_hidden("secteur", "", "encaissements");
echo "</form>";


$requetes = array();
$totaux = array();

echo $doc->table_open();

foreach ($_POST["compte"] as $champ)
{
	$requete = "select sum($champ) as 'total', nodossier from {$_SESSION["session_opdb"]} group by nodossier order by nodossier";
	$requetes[] = $requete;
}

foreach($requetes as $q)
{
	$total = 0;
	echo "\n<tr><td colspan=2>$q</td></tr>";
	$e = mysqli_query($doc->mysqli, $q);
	while($r = mysqli_fetch_array($e))
	{
		if($r["total"] != 0)
		{
			echo "\n<tr><td onClick='changeDossier(\"{$r["nodossier"]}\", \"encaissements\")' style=\"cursor:pointer\">{$r["nodossier"]}&nbsp;:</td><td>{$r["total"]}</td></tr>";
			$total += $r["total"];
		}
	}
	echo "\n<tr><td>Total&nbsp;:</td><td> $total</td></tr>";
}
echo $doc->table_close();

?>
