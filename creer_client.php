<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->title();
$doc->body(2, "");
$doc->entete();
// $doc->tab_affiche();

$_REQUEST["etape"] ++;

if($_POST["action"] == "cherche_conflits") $h1 = $doc->lang["creer_dossier_h11_conflits"];
else
{
	$h1 = preg_replace("#nouveau_([a-z]+)[0-4]?#", "creer_dossier_h11_\\1", $_POST["action"]);
	$h1 = $doc->lang["$h1"];
}
if($_POST["action"] == "nouveau_dossier") $h2 = $doc->lang["creer_client_h21_client"];
else
{
	$h2 = preg_replace("#nouveau_([a-z]+)[0-4]?#", "creer_client_h21_\\1", $_POST["action"]);
	$h2 = $doc->lang["$h2"];
}


echo "<br><h1>$h1</h1>\n<h2>{$doc->lang["creer_dossier_etape"]}&nbsp;{$_REQUEST["etape"]}: {$doc->lang["creer_client_h21"]}</h2>";
echo "{$doc->lang["creer_client_liste_recherche"]} \"{$_POST["vnom"]}\"";

$vert  = "#a0ffa0";
$vfon  = "#80ff80";
$rouge = "#ffa0a0";
$gris  = "#a0a0a0";
$doc->couleurs = array("client" => $vert, "contact" => $vfon, "pa" => $rouge, "autre" => $gris);

$liste = $doc->cherche_conflits();
// $doc->tab_affiche(4);
// echo $doc->beautifyMysql($conds);
echo $doc->table_open("");
$enr = 0;
foreach($doc->couleurs as $type => $array)
{
// 	natcasesort($liste);
	$ar = $liste[$type];
	ksort($ar);
	$numofrows = count($ar);
	$titre = $doc->lang["creer_client_h3_{$type}"];
	echo "\n<tr><td colspan=8><br>$titre ($numofrows)</td></tr>";
	foreach($ar as $trouve => $base)
	{
// 		echo "<br>'$trouve'";
		list($couleur, $nom, $prenom, $fonction, $titre, $adresse, $zip, $ville, $id, $avocats) = explode("::", $trouve);
		if($prenom) $prenom .= "&nbsp;";
		$avs = "";
		foreach($ar[$trouve] as $av => $dossiers)
// 		list($datas, $avocats) = explode(":", $base);
// 		foreach(explode(",", $avocats) as $av)
		{
			$nomAv = $doc->init_to_name($av);
// 			echo "<br>'$av'";
			if($avs) $avs .= ", ";
			if(in_array($av, $doc->avocatsArchives)) $nomAv = "<i>$nomAv</i>";
			$d = "";
			foreach($ar[$trouve][$av] as $dossier => $n)
			{
				if($d != "") $d .= ", ";
// 				$d .= $doc->form("modifier_donnees.php", $dossier, "", "style=display:inline@form", "_new", "nodossier", $dossier);
				$d .= "<a target=_new href=./modifier_donnees.php?nodossier=$dossier&new_av=$av>$dossier</a>";
			}
			if($d) $nomAv .= " [$d]";
			$avs .= $nomAv;
		}
		$formEdit = $doc->form("adresses/modifier.php", "<img src='images/txt.png'>", "", "", "ed$enr<td>_new", "id", "$id");
		if($_REQUEST["action"] == "cherche_conflits") $form=$formEdit;
		elseif($_POST["action"] == "nouveau_dossier")
		{
			if($_REQUEST["etape"] == "6") $form = $doc->form("maj_op.php", "<img src='images/true.png'>", "", "", "ok$enr<td>", "noadresse", "###", "nopa", "###", "noca", $id, "retour", "modifier_donnees", "action", "nouveau_dossier", "drop_reqb", "on", "finish", "on");
			if($_REQUEST["etape"] == "4") $form = $doc->form("creer_dossier.php", "<img src='images/true.png'>", "", "", "ok$enr<td>", "noadresse", "###", "nopa", $id, "action", "###", "etape", "###");
			if($_REQUEST["etape"] == "2") $form = $doc->form("creer_dossier.php", "<img src='images/true.png'>", "", "", "ok$enr<td>", "noadresse", $id, "action", "###", "etape", "###");
// 			if($_REQUEST["etape"] == "4") $form = $doc->form("maj_op.php", $doc->lang["creer_client_accepter"], "", "", "", "noadresse", "###", "nopa", $id, "retour", "modifier_donnees", "action", "nouveau_dossier", "drop_reqb", "on");
// 			if($_REQUEST["etape"] == "2") $form = $doc->form("creer_dossier.php", $doc->lang["creer_client_accepter"], "", "", "", "noadresse", $id, "action", "###", "etape", "###");
			$form = "<table valign=bottom><tr><td>$formEdit</td><td>$form</td></tr></table>";
		}
		else
		{
			preg_match("#nouveau_(.*)([1-4]?)#", $_POST["action"], $reg);
			$no = $reg[2];
			$pers = "no" . preg_replace("#client#", "adresse", $reg[1]).$no;
// 			echo "pers: '$pers'";	
			$form = $doc->form("maj_op.php", $doc->lang["creer_client_accepter"], "", "", "", "$pers", $id, "retour", "modifier_donnees", "action", "maj", "nodossier", "###");
		}	
		$found = "<tr bgcolor='$couleur'><td>$id</td><td>$titre&nbsp;</td><td>{$prenom}<b>$nom</b>&nbsp;</td><td>$fonction&nbsp;</td><td>$adresse&nbsp;</td><td>$zip&nbsp;</td><td>$ville&nbsp;</td><td>($avs)</td><td>$form</td>";
		echo "\n$found";
		$enr++;
	}
}
echo $doc->table_close();

if($_POST["cherche_conflits"]) die();

echo "<br>
<h3>{$doc->lang["creer_client_alternative"]}</h3>";

echo "<form action=./maj_op.php method=post>";
if($_REQUEST["etape"] == "2") echo $doc->input_hidden("retour", "", "creer_client");
if($_REQUEST["etape"] == "4")
{
	echo $doc->input_hidden("retour", "", "creer_client");
	echo $doc->input_hidden("noadresse", 1);
}
if($_REQUEST["etape"] == "6")
{
	echo $doc->input_hidden("retour", "", "modifier_donnees");
	echo $doc->input_hidden("noadresse", 1);
	echo $doc->input_hidden("nopa", 1);
}
if($_REQUEST["action"] == "nouveau_dossier")
{
	echo $doc->input_hidden("action", "", "nouveau_dossier");
	echo $doc->input_hidden("etape", 1);
}
else
{
	preg_match("#nouveau_(.*)([1-4]?)#", $_POST["action"], $reg);
	$no = $reg[2];
	$pers = "no" . preg_replace("#client#", "adresse", $reg[1]).$no;
// 	echo "pers: '$pers'";	
	echo $doc->input_hidden("retour", "", "modifier_donnees");
	echo $doc->input_hidden("action", "","nouveau_champ");
	echo $doc->input_hidden("champ", "", $pers);
	echo $doc->input_hidden("nodossier", 1);
}

/*$doc->affiche_personne("identite", "forceCreate");
echo "youhou";
echo $doc->affichage_total*/;
echo "<table>
<tr><td align=right>{$doc->lang["creer_client_titre"]} :</td><td colspan=3><input type=text size=75 name=titre></td></tr>
<tr><td align=right>{$doc->lang["creer_client_prenom"]} :</td><td><input type=text size=30 name=prenom>&nbsp;</td><td align=right><b>{$doc->lang["creer_client_nom"]} :&nbsp;<input type=text size=30 name=nom ordretab=1></b></td></tr>
<tr><td align=right>{$doc->lang["creer_client_fonction"]} :</td><td colspan=3><input type=text size=75 name=fonction></td></tr>
<tr><td align=right>{$doc->lang["creer_client_adresse"]} :</td><td colspan=3><input type=text size=75 name=adresse></td></tr>
<tr><td align=right>{$doc->lang["creer_client_zip"]} :</td><td><input type=text size=30 name=zip></td><td align=right>{$doc->lang["creer_client_ville"]} :&nbsp;<input type=text size=30 name=ville></td></tr>
<tr><td align=right>{$doc->lang["creer_client_pays"]} :</td><td colspan=3><input type=text size=75 name=pays></td></tr>
<tr><td align=right>{$doc->lang["creer_client_tel"]} :</td><td><input type=text size=30 name=tel>&nbsp;</td><td align=right>{$doc->lang["creer_client_fax"]} :&nbsp;<input type=text size=30 name=fax></td></tr>
<tr><td align=right>{$doc->lang["creer_client_natel"]} :</td><td><input type=text size=30 name=natel></td><td align=right>{$doc->lang["creer_client_mail"]} :&nbsp;<input type=text size=30 name=mail></td></tr>
</table>";
echo $doc->button($doc->lang["creer_client_creer_client"], "");
// echo "&nbsp;<button type=button, onclick=javascript:history.go(-2)>{$doc->lang["creer_client_retour"]}</button>
echo "\n</form>";

$doc->close();
