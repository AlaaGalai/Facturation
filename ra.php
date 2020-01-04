<?php
//
//initially created by lucien, august 2004, lkr@bluewin.ch
//maintained then completely rewritten by Olivier Subilia, etudeav@users.sourceforge.net
//
//Trois modes:
//remuneration == "on"	=> Activité comme sous-traitant, permettant à chacun de savoir ce qu'il a time-sheeté;
//soustraitance == "on"	=> Rapport d'activités de ce que chaque sous-traitant a time-sheeté pour l'utilisateur;
//activite == "on"	=> Rapport d'activité global de l'utilisateur


require_once("./inc/autoload.php");
session_start();


$doc=new ra;
$doc->title();
$doc->body(2);
$doc->entete();

//$doc->tab_affiche();

// if(! isset($_POST["remuneration"]) && ! isset($_POST["activite"]) && ! isset($_POST["soustraitant"]) && ! isset($_POST["new_av"]))
// {
// 	header("Location: ./resultat_recherche.php");
// 	die();
// }
// 
if($_POST["remuneration"])
{
	if($_SESSION["type"] == "admin" && $_POST["specsstrait"])
	{
		$_POST["specsstrait"] .= ",";
		$_POST["specsstrait"] = preg_replace("#,,#", ",", $_POST["specsstrait"]);
		$doc->liste_soustraitant = $_POST["specsstrait"];
		$array_soustraitant = preg_split("#,#", $doc->liste_soustraitant);
		array_splice($array_soustraitant, -1);
		foreach($doc->liste_des_utilisateurs as $init => $arr) $doc->myBases[] = array("{$init}clients", "{$init}op", "{$init}tarifs");
	}
	else
	{
		$doc->myBases = array();
		$doc->liste_soustraitant = "{$_SESSION["user"]},";
		$array_soustraitant = array($_SESSION["user"]);
		#$doc->tab_affiche($doc->liste_des_utilisateurs);
		foreach($doc->liste_des_utilisateurs as $init => $arr)
		{
			$listes = preg_split("/\n/", $doc->liste_des_utilisateurs["$init"]["soustraitants"]);
			foreach($listes as $liste)
			{
				list($alias, $nom, $droit) = preg_split("/\,/", $liste);
				if ($droit && ($_SESSION["user"] == trim($alias) || $_SESSION["user"] == trim($nom))) #todo: why do we need trim for ' diserens' in lp['soustraitants'] ?
				{
					$doc->myBases[] = array("{$init}clients", "{$init}op", "{$init}tarifs");
					if (! in_array($alias, $array_soustraitant))
					{
						$doc->liste_soustraitant .= "$alias,";
						$array_soustraitant[] = $alias;
					}
				}
			}
		}
	}
}
echo "<h2>{$doc->lang["ra_title"]}</h2>";
echo "<br>{$doc->lang["operations_soustraitant"]}&nbsp;:&nbsp;".substr($doc->liste_soustraitant, 0, -1);
if($_SESSION["type"] == "admin" && $_POST["remuneration"])
{
	echo "<form action='ra.php' method='POST'>";
	echo $doc->input_hidden("remuneration", 1);
	echo $doc->input_hidden("mois_cours", 1);
	echo $doc->input_hidden("jour_cours", 1);
	echo $doc->input_hidden("annee_cours", 1);
	echo $doc->input_hidden("type", 1);
	echo $doc->input_texte("specsstrait", 1, "", 20);
	echo $doc->button("");
	echo "</form>";
}
echo "\n".$doc->table_open("width=\"100%\" border=0")."<tr>";
if ($_POST["remuneration"])
{
	echo "<td>{$doc->lang["ra_moischer"]}&nbsp;:<b> ".$doc->univ_strftime("%B %Y", $doc->mtf_date($_POST["date_cours"]))."</b></td>";
}
else
{
	echo "<td>{$doc->lang["ra_jourcher"]}&nbsp;:<b> ".$doc->mysql_to_print($_POST["date_cours"])."</b></td>";
	echo "<td align=center>";
	$doc->display_global_result("jour", TRUE);
	echo "</td>";
}
$mois = $doc->display_global_result("mois", TRUE);
$ans =  $doc->display_global_result("annee", TRUE);
$vans =  $doc->display_global_result("vannee", TRUE);

echo "<td align=right>";
$keep = $doc->form_global_var;
$doc->form_global_var = $_POST;
echo $doc->calendarSelect($_POST["date_cours"], TRUE, $doc->liste_soustraitant."LSSTRAIT");
$doc->form_global_var = $keep;
echo "</td>";
echo "</tr>".$doc->table_close()."\n<br>\n<br>";
echo "\n".$doc->table_open("width=100% align=center border=0");
foreach($mois as $init => $val)
{
	echo "<tr><td>".$mois[$init]."</td><td>".$ans[$init]."</td><td>".$vans[$init]."</td></tr>";
}
echo $doc->table_close();
echo "<hr>";
if (! $_POST["remuneration"])
{
	echo $doc->display_global_result("jour", FALSE);
	echo "\n<br>";
}
$doc->display_global_result("mois", FALSE);

if($_POST["remuneration"])
{
	echo $doc->table_close();
	$doc->close();
	die();
}
echo "<br><br>";
echo $doc->table_open("width=100% align=center");
echo "<tr><th>{$doc->lang["ra_details"]}</th><th>{$doc->lang["ra_hono_ts"]}</th></tr>\n<tr align=center><td>";
$doc->send_image("1");
echo "</td><td>";
$doc->send_image("4");
echo "</td></tr>\n<tr><td colspan=2>&nbsp;</td></tr>\n";
echo "<tr><th>{$doc->lang["ra_details_type"]}</th><th>{$doc->lang["ra_details_type"]} (%)</th></tr>\n<tr align=center><td>";
$doc->send_image("2");
echo "</td><td>";
$doc->send_image("2bis");
echo "</td></tr>\n<tr><td colspan=2>&nbsp;</td></tr>\n";
echo "<tr><th>{$doc->lang["ra_details_matiere"]}</th><th>{$doc->lang["ra_details_matiere"]} (%)</th></tr>\n<tr align=center><td>";
$doc->send_image("3");
echo "</td><td>";
$doc->send_image("3bis");
echo "</td></tr>";
echo $doc->table_close();
$doc->close();
?>
