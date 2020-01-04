<?php
require_once("../inc/autoload.php");
session_start();
$doc=new biblio();
$_POST["doRecherche"] = "on";

##################################
#Gestion des critères de recherche
##################################

#1. Domaines
foreach($_REQUEST as $key=>$elem)
{
	$val=substr($key, 5, 40);
	$check=substr($key, 0, 5);
	if($check=="check") $domaine=$domaine."and lcase(domaine) like'%".strtolower($val)."%'";
}

#2. Titre
$titre=addslashes(rawurldecode(strtolower($_REQUEST["titre"])));
$titrebis="lcase(concat(debut_titre, titre, sous_titre)) like '%".str_replace(" ", "%' and lcase(concat(debut_titre, titre, sous_titre)) like '%", addslashes(stripslashes($_REQUEST["titre"])))."%'";
if($_REQUEST["debutexact"] || $_REQUEST["titreexact"])
{
	$titrebis="";
	if($_REQUEST["debutexact"]) $titrebis = "debut_titre like '". addslashes(stripslashes($_REQUEST["debutexact"]))."'";
	if($_REQUEST["debutexact"] && $_REQUEST["titreexact"]) $titrebis .= " AND ";
	if($_REQUEST["titreexact"]) $titrebis .= "titre like '". addslashes(stripslashes($_REQUEST["titreexact"]))."'";
	#die($titrebis);
}
if($titrebis)
{
	$rTitre = "if (titre like '$titre',0,1) as rtitre,";
	$rOrder = "rtitre,";
	$tRech  = "\n<h3>Recherche: <i>{$_REQUEST["titre"]}</i></h3>";
	#Encodage des '&' //à vérifier
	$titrebis = preg_replace("/\&/", "&amp;", $titrebis);

}

#3. Auteur
$auteur=strtolower($_REQUEST["auteur"]);
$smartAuteur = $doc->smart_html($auteur);
$auteurbis="lcase(concat(auteur1, auteur2, auteur3, auteur4, auteur5, auteur6)) like '%".str_replace(" ", "%' and lcase(concat(auteur1, auteur2, auteur3, auteur4, auteur5, auteur6)) like '%", $auteur)."%'";
$auteurbis="(lcase(concat(auteur1, auteur2, auteur3, auteur4, auteur5, auteur6)) like '%$auteur%' OR lcase(concat(auteur1, auteur2, auteur3, auteur4, auteur5, auteur6)) like '%$smartAuteur%')";

#4. Prêts
if($_REQUEST["prete"])
{
	$pretebis = "and (prete_le>'0000-00-00' or prete_a<>'')";
	$orderprete = "prete_a, prete_le, ";
}

#5. Statuts particuliers
if($_REQUEST["status"])
{
	$statusbis = "and status like '{$_REQUEST["status"]}'";
}


#6. Divers
if($_REQUEST["restrict"]=="series")
{
	$group="group by 'titre_complet'";
	$count="count(sous_titre) as nbst,";
}
else
{
	$group = $count = "";
}


$doc->title();
$doc->body(2, "document.forms['modify'].elements['forceUser'].focus()");
$doc->entete();


	
	
##################################
#Exécution de la recherche
##################################

if($_POST["doRecherche"])
{
	$debut = $_REQUEST["debut"];
	//gestion des pages
	if ($debut <> "")
	$debut_mysql=$debut;
	else
	$debut_mysql=0;
	#echo "<br>'$auteurbis'<br>";

	#Détermination de stop_mysql
	$stop_req = "select COUNT(*) as stop_mysql from biblio where $titrebis and $auteurbis $pretebis $statusbis and type='{$_SESSION["biblioType"]}' and collection='{$_SESSION["biblioNom"]}' $domaine";
	$stop_exec=mysqli_query($doc->mysqli, $stop_req);
	while($stop_line=mysqli_fetch_array($stop_exec)) $stop_mysql=$stop_line["stop_mysql"];
	
	#Requête proprement dite
	$controle="";
	$texte_requete="select *, $rTitre concat(debut_titre, \" \", titre) as titre_complet, $count no_volume/2*2 as no from biblio where $titrebis and $auteurbis $pretebis $statusbis and type='{$_SESSION["biblioType"]}' and collection='{$_SESSION["biblioNom"]}' $domaine $group order by $rOrder $orderprete replace(titre, ' ', ''), replace(debut_titre, ' ', ''), no, sous_titre limit $debut_mysql,{$_SESSION["nb_affiche"]}";
	$requete=mysqli_query($doc->mysqli, "$texte_requete");
	$numofrow=mysqli_num_rows($requete);
 	//echo "<br>$texte_requete";
	
	
	if($numofrow >0)
	{
		$titreAffiche = $_REQUEST["restrict"] == "series" ? $doc->lang["biblio_restrict_series"]:$doc->lang["biblio_restrict_ouvrages"];
		if($_REQUEST["status"]) $titreAffiche = $doc->lang["biblio_liste_status{$_REQUEST["status"]}"];
		echo "<h2>$titreAffiche</h2>";
		
		//on décrit les enregistrements trouvés
		echo "{$doc->lang["resultat_recherche_criteres"]}:<br>";
		echo "{$doc->lang["biblio_recherche_auteur"]} :  {$_REQUEST["auteur"]}<br>";	
		echo "{$doc->lang["biblio_recherche_titre"]} :  {$_REQUEST["titre"]}<br>";	
		if($_SESSION["biblioType"] == "0") echo "{$doc->lang["biblio_recherche_domaine"]} :  {$_REQUEST["domaine"]}<br>";	

		echo "\n<br><br><br>$stop_mysql {$doc->lang["resultat_recherche_records"]} :<br><br>";
		if($_REQUEST["restrict"]<>"series")
		{
			$indisponibles = preg_replace("|(.*)({##})(.*)({##})(.*)|", "\\1<span style=\"font-style:italic; color:ff1010\">\\3</span>\\5", $doc->lang["biblio_recherche_indisponible"]);
			echo "($indisponibles)<br><br>";
		}
		echo "<a class=link accesskey='u' onclick='changeTenth()'>Change dizaine (u)</a>&nbsp;";
		echo "<a class=link accesskey='d' onclick='changeTenth(1)'>Change dizaine (d)</a>&nbsp;";
		echo "<a class=link accesskey='c' onclick='changeMode()'>Change mode (c)</a>";

		echo $doc->table_open();
		$no=1;
		while($row=mysqli_fetch_array($requete))
		{
			if($_SESSION["biblioType"] == "0" && $row["debut_titre"])
			{
				if(substr(trim($row["debut_titre"]), -1, 1)<>"'") $row["titre"] = $row["debut_titre"] . " " . $row["titre"];
				else $row["titre"] = $row["debut_titre"] . " " . $row["titre"];
				$row["debut_titre"] = "";
			}
			if(1) // TODO: vérifier les droits
			{
				$affTitre = 1 ? $doc->form("biblio/creer_livre.php", "{$row["titre"]}@modifieracc$no", "", "", "", "action", "modify", "no_fiche", $row["no_fiche"]):$row["titre"];
				$affSSTitre = (1 AND  $_SESSION["biblioType"] != "0") ? $doc->form("biblio/creer_livre.php", "{$row["sous_titre"]}@modifieracc$no", "", "", "", "action", "modify", "no_fiche", $row["no_fiche"]):$row["sous_titre"];
			}
			if(!$row["largeur"]) $row["largeur"] = 1;
			//echo "<tr><td>Titre complet: ".$row["titre_complet"].". Contrôle: $controle</td></tr>";
			if($row["titre_complet"]<>$controle or $_SESSION["biblioType"]=="0")
			{
				echo "<tr><td";
				if($_REQUEST["restrict"]<>"series") echo " colspan=3";
				echo ">";
				if($_SESSION["biblioType"]<>"0")  echo "<br>";
				echo trim($row["debut_titre"]);
				$rech = $doc->no_accent($row["titre"]);
				if(substr(trim($row["debut_titre"]), -1, 1)<>"'") echo " ";
				$bdAdd = $_SESSION["biblioType"] == "1" ? "<a href=\"http://www.bedetheque.com/index.php?R=1&RechSerie=$rech\" target='_new'><img src=\"./images/logobdgest.png\" height=20 alt=\"BDGest\"></a>": "&nbsp;";
				if ($_SESSION["biblioType"]=="0") echo "<b>$affTitre</b></td><td>&nbsp;</td><td>";
				if ($_SESSION["biblioType"]=="1") echo "<b>{$row["titre"]}</b></td><td>$bdAdd</td><td>";
				if($_REQUEST["restrict"]=="series")
				{
					echo "<br>(", $row["nbst"], " volume";
					if($row["nbst"]>1) echo "s";
					echo ")";
				}
				echo "</td>";
				if($_SESSION["biblioType"]<>"0") echo "</tr>";
				$controle=$row["titre_complet"];
			}
			if (($row["prete_le"]>0 or $row["prete_a"]<>"") AND $_REQUEST["restrict"]<>"series" AND $_SESSION["biblioType"]<>"0" ) echo " <tr id=\"c\">";
			elseif($_SESSION["biblioType"]<>"0")  echo "<tr>";
			if($_REQUEST["restrict"]<>"series")
			{
				if($no <10) $nobis=$no;
				elseif($no == 10) $nobis=0;
				else $nobis="";
				if($no <11) $acc=" (alt - $nobis)";
				else $acc="";
				echo "<td>";
				if($row["no_volume"]) echo "vol. ", $row["no_volume"];
				else echo "&nbsp;";
				echo "</td>
				<td><i>";
				if($row["sous_titre"]) echo $affSSTitre;
				elseif($_SESSION["biblioType"]<>"0")
				{
					echo $row["debut_titre"];
					if(substr(trim($row["debut_titre"]), -1, 1)<>"'") echo " ";
					echo $affTitre;
				}
				echo "</i></td>";
			}
			echo "<td>";
			echo "(<a href=\"liste_ouvrages.php?auteur={$row["auteur1"]}\">{$row["auteur1"]}</a>";
			if($row["auteur2"]) echo "&nbsp;/&nbsp;<a href=\"liste_ouvrages.php?auteur={$row["auteur2"]}\">{$row["auteur2"]}</a>";
			if($row["auteur3"]) echo "&nbsp;/&nbsp;<a href=\"liste_ouvrages.php?auteur={$row["auteur3"]}\">{$row["auteur3"]}</a>";
			if($row["auteur4"]) echo "&nbsp;/<br>&nbsp;<a href=\"liste_ouvrages.php?auteur={$row["auteur4"]}\">{$row["auteur4"]}</a>";
			if($row["auteur5"]) echo "&nbsp;/&nbsp;<a href=\"liste_ouvrages.php?auteur={$row["auteur5"]}\">{$row["auteur5"]}</a>";
			if($row["auteur6"]) echo "&nbsp;/&nbsp;<a href=\"liste_ouvrages.php?auteur={$row["auteur6"]}\">{$row["auteur6"]}</a>";
			echo ")</td>";
			if($_REQUEST["restrict"]=="series")
			echo "<td><a href=./liste_ouvrages.php?titre=", $row["titre"], ">Voir d&eacute;tails</a></td>";
			if($_REQUEST["status"] == "3" || $_REQUEST["status"] == "2")
			{
				list($annee_edition) = preg_split("/-/", $row["date_edition"]);
				$commandeChez = $_REQUEST["status"] == "2" ? "{$doc->lang['biblio_commande_chez']} {$row['commande_chez']}":"";
				$commandePour = $row["commande_pour"] ? "{$doc->lang['biblio_commande_pour']} {$row['commande_pour']}":"";
				echo "<td>ISBN <a target='_new' href='http://modules.avocats-ch.ch/?module=rerobis&data={$row['isbn']}'>{$row['isbn']}</a> {$row["editeur"]} $annee_edition $commandeChez $commandePour</td>";
			}
			else
			{
				if($_REQUEST["restrict"]<>"series") //TODO: vérifier les restrictions 
				{
					echo $doc->form("biblio/creer_livre.php<td>", "(+) $acc@nouveauacc$no", "$nobis", "", "", "no_fiche", $row["no_fiche"], "action", "create", "add", "on"); 
					if($row["prete_a"]=="" and $row["prete_le"]=="0000-00-00") echo "<td><form method=post action=\"./preter_livre.php\">
					
					<input type=hidden name=no_fiche value=\"", $row["no_fiche"], "\">
					Pr&ecirc;ter &agrave;
					<input size=12 name=prete_a>
					<input type=submit value=Pr&ecirc;ter>
					</form></td>";
					else
					{
						if (!$row["prete_a"]) $row["prete_a"] = $doc->lang["biblio_inconnu"];
						$prete = preg_replace("/{##}/", "<font color='red'>{$row["prete_a"]}</font>", $doc->lang["biblio_prete"]);
						echo "<td><i>$prete</i></td>";
					}
					echo $doc->form("maj_op<td>", $doc->lang["config_modify_delete"], "", "attention_bg", "", "no_fiche", $row["no_fiche"], "action", "delete", "retour", "biblio/liste_ouvrages");
				}
				if($_SESSION["biblioType"]=="0") echo "<td>Etag&egrave;re ", $row["class"], ", rayon ", $row["sous_class"], "</td>";
			}
			$no ++;
			echo "</tr>";
		}
		echo $doc->table_close();
// 		$series="";
// 		if($_REQUEST["restrict"]=="series") $series="&restrict=series";
// 		$series .= "&debutexact={$_REQUEST["debutexact"]}&titreexact={$_REQUEST["titreexact"]}";
		$doc->footer();
	}else{
		echo "<br><br>{$doc->lang["resultat_recherche_rien_trouve"]}";
		$newId = "id=id1";

	}
}
$doc->close();
?>
