<?php
require_once("../inc/autoload.php");
session_start();
error_reporting(7);
$doc=new biblio;
if($_REQUEST["action"] == "create") $doc->lang["biblio_creer_livre_title"] = $doc->lang["biblio_nouveau"];
$doc->title();
$doc->body(2, "document.getElementById('form').titre.focus()");
$doc->entete();
// $doc->tab_affiche(4);

if($_REQUEST["action"] == "modify" || $_REQUEST["add"])
{
	$action = $_REQUEST["action"];
	$add = $_REQUEST["add"] ? True:False;
	$q = "select * from biblio where no_fiche like '{$_REQUEST["no_fiche"]}'";
	$e = mysqli_query($doc->mysqli, $q);
	while ($row = mysqli_fetch_array($e)) $_REQUEST = $row;
	$_REQUEST["action"] = $action;
// 	print "{$_REQUEST["add"]} && {$_REQUEST["no_volume"]}";
	if($add && $_REQUEST["no_volume"] !== "")
	{
		$_REQUEST["no_volume"] += 1;
	}
}
echo "\n<h2>{$doc->lang["biblio_creer_livre_title"]}</h2>
<form method=\"post\" action=\"../maj_op.php?action=$action\">";
echo $doc->table_open("");
if($_SESSION["biblioAutoclass"]=="1" ||$_SESSION["biblioType"]=="1")
{
	$titreLargeur = "<th>{$doc->lang["biblio_largeur"]}</th>";
	$valueLargeur = "";
}
if ($_REQUEST["action"] == "create" AND $_REQUEST["largeur"] == 0) $_REQUEST["largeur"] = "1";
echo "<tr>
<th>{$doc->lang["biblio_debut_titre"]}</th><th>{$doc->lang["biblio_recherche_titre"]}</th><th>{$doc->lang["biblio_soustitre"]}</th><th>{$doc->lang["biblio_no_album"]}</th>$titreLargeur<th>{$doc->lang["biblio_edition"]}</th></tr><tr><td>{$doc->input_texte("debut_titre", 1, '', 4)}</td><td>{$doc->input_texte("titre", 1, "", 20)}</td><td>{$doc->input_texte("sous_titre", 1, '', 20)}</td><td>{$doc->input_texte("no_volume", 1, '', 2)}</td><td>{$doc->input_texte("largeur", 1, '', 2)}</td><td>{$doc->input_texte("edition", 1, '', 2)}</td></tr>";
echo $doc->table_close();
echo $doc->table_open();
$status = "";
for($x = 0; $x<4;$x++)
{
	$texte = $doc->lang["biblio_status{$x}"];
	$checked = $_REQUEST["status"] == $x ? "checked":"";
	$status .= "<input type='radio' name='status' value='$x' $checked>&nbsp;$texte ";
}
echo "\n<tr><td><b>{$doc->lang["biblio_date_edition"]}</b>&nbsp;".$doc->split_date($_REQUEST["date_edition"], 'date_edition')."</td><td><b>{$doc->lang["biblio_editeur"]}</b>&nbsp;".$doc->input_texte( 'editeur', 1)."</td><td><b>{$doc->lang["biblio_isbn"]}</b>&nbsp;{$doc->input_texte('isbn', 1)}</td></tr></table>";
echo "\n<table><tr><td><b>{$doc->lang["biblio_commande_chez"]}</b>&nbsp;{$doc->input_texte('commande_chez', 1)}</td><td><b>{$doc->lang["biblio_commande_pour"]}</b>&nbsp;{$doc->input_texte('commande_pour', 1)}</td><td>$status</td></tr>";
echo $doc->table_close();

$doc->liste();

for($x=0; $x<6; $x++) $doc->auteur();

// print $_SESSION["biblioType"];
if($_SESSION["biblioType"]=="0") 
{
	echo "<table><tr><td><br><b>{$doc->lang["biblio_domaines"]}</b></td></tr>";
	echo $doc->categories($_REQUEST["domaine"]);
	echo "</table>";
}

//Pour classer automatiquement
if($_SESSION["biblioAutoclass"]=="1" || $_SESSION["biblioType"]=="1") 
{
	$tablards="./liste_tablards.txt";
	$liste_tablards=file($tablards);
	$somme_tablard=0;
	foreach($liste_tablards as $num)
	{
		if(is_numeric(trim($num))) $somme_tablard += trim($num);
// 		else echo "<br>tab vaut $num";
	}
	
//  	echo "<br>le nombre total de tablards fait $somme_tablard cm<br>";
	
	$requete_nombre = "select sum(largeur) as place from biblio";
	$exec_nombre = mysqli_query($doc->mysqli, $requete_nombre);
	while ($r=mysqli_fetch_array($exec_nombre)) $largeur_totale=$r["place"];
// 	echo "<br>largeur totale: $largeur_totale";
	$requete_total="select *, no_volume/2*2 as no from biblio where type='{$_SESSION["biblioType"]}' order by replace(titre, ' ', ''), replace(debut_titre, ' ', ''), no, sous_titre";
// 	echo "<br>$requete_total";
	$requete=mysqli_query($doc->mysqli, $requete_total);
	$num_rows=mysqli_num_rows($requete);
//  	echo "<br>au début, il y a $num_rows fiches pour une largeur totale de $largeur_totale";
	$noenreg=0;
	while ($r=mysqli_fetch_array($requete))
	{
		$soustitre=(trim($r["sous_titre"] != "")) ?", <i>{$r["sous_titre"]}</i>":"";
		$vol=(trim($r["no"] != "") AND $r["no"] != 0) ?" (vol. {$r["no"]})":"";
		$temp_titre = preg_replace("/  /", " ", "{$r["debut_titre"]} <b>{$r["titre"]}</b>$soustitre$vol");
		if($next_break)
		{
			$next_titre=$temp_titre;
			break;
		}
		if($r["no_fiche"] == $_REQUEST["no_fiche"])
		{
			$no_ordre=$noenreg + $r["largeur"];
// 			echo "<br>place:$somme_tablard / $largeur_totale * $no_ordre)";
//  			echo "<br>noenreg vaut $no_ordre";
			$last_titre=$temp_last_titre;
			$titre=$temp_titre;
			$next_break=TRUE;
		}
		$temp_last_titre = $temp_titre;
		$noenreg += $r["largeur"];
	}
// 	echo "<br>ce livre est le $no_ordre sur $num_rows<br>";
	$place=$somme_tablard / $largeur_totale * $no_ordre;
	
	$tablards="./liste_tablards.txt";
	$liste_tablards=file($tablards);
	$somme_tablard=0;
	$no=1;
	foreach($liste_tablards as $num)
	{
		if(!is_numeric(trim($num))) continue;
		$somme_tablard += $num;
		if($somme_tablard > $place)
		{
			echo "<br><br>Ce livre devrait aller sur le tablard n° $no entre <br>$last_titre et <br>$next_titre<br><br>";
			break;
		}
		$no++;
	}
	
}

else
{
	echo "<br>{$doc->lang["biblio_etagere"]} ".$doc->input_texte("class", 1);
	echo "&nbsp;&nbsp;{$doc->lang["biblio_rayon"]} ".$doc->input_texte("sous_class", 1);
	echo "<br>";
}
echo $doc->input_hidden("action", 1);
echo $doc->input_hidden("retour", "", "biblio/creer_livre");
if ($_REQUEST["action"] != "create") echo $doc->input_hidden("no_fiche", 1);
$button = $_REQUEST["action"] == "create" ? $doc->lang["config_modify_create"]: $doc->lang["modifier_donnees_modifier_dossier"];
echo $doc->button("$button");
if ($_REQUEST["action"] == "create")echo "&nbsp;&nbsp;<a class='button' href=./liste_ouvrages.php?titre=".rawurlencode($_REQUEST['titre']).">{$doc->lang["adresses_modifier_annuler"]}</a>";
echo "</form>";
if ($_REQUEST["action"] != "create")
{	
	echo "\n<form action='../biblio/creer_livre.php' method='post' >
	<button  id='nouveauacc1' type=submit accesskey=1>(+)  (alt - 1)</button>
	<input type='hidden' name='action' value='create'>
	<input type='hidden' name='add' value='on'>";
	echo $doc->input_hidden("no_fiche", 1);
	echo "</form>";
}	
echo $doc->close();
