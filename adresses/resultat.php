<?php
require_once("../inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
// $doc->tab_affiche();
$doc->addStyle = "rdv";
if(! isset($_POST["searchGlobal"]) && isset($_GET["searchGlobal"])) $_POST["searchGlobal"] = $_GET["searchGlobal"];
$xhr = isset($_REQUEST["xhr"]) ? $_REQUEST["xhr"]: "";
if($xhr) $_POST["doRecherche"] = "on";
if($_POST["action"]!="delete")
{
	if($_POST["doRecherche"])
	{
		//affichage de la recherche de fiche, état normal
		//initialisation des variables d'affichage dans la page
		if ($_POST["debut"] <> "")
		$debut_mysql=$_POST["debut"];
		else
		$debut_mysql=0;
		$mailingAdd = "";
		
		//il faut tricher pour adapter la fonction footer()
		if($_POST["nodossier"]) $_POST["searchGlobal"] = $_POST["nodossier"];
		$_POST["nodossier"] = $_POST["searchGlobal"];
		
		$cat=array("titre", "prenom", "nom", "fonction", "adresse", "zip", "ville", "rem");
		
		$searchGlobalbis="";
		
		$arrbis=explode(" ", $_POST["searchGlobal"]);
		foreach($arrbis as $arrbis_line)
		{
			$arrbis_line = trim($arrbis_line);
			if($searchGlobalbis != "") $searchGlobalbis .= " AND ";
			$searchGlobalbis .= "(";
			foreach($cat as $cat_elt)
			{
				if($cat_elt != "titre") $searchGlobalbis .= " or ";
				//$searchGlobalbis .= "adresses.$cat_elt like '%$arrbis_line%' or adresses.$cat_elt like '%".$doc->my_htmlentities($arrbis_line)."%'";
				$searchGlobalbis .= "a.$cat_elt like '%".addslashes(stripslashes($arrbis_line))."%'";
			}
			$searchGlobalbis .= ")";
		}
		if($_POST["searchID"]) $searchGlobalbis = "id like '{$_POST["searchID"]}'";
		if($_POST["mailingname"])
		{
			foreach($_POST["mailingname"] as $m) if($m) $searchGlobalbis .= " AND mailingname like '$m'";
			$mailingAdd = "LEFT JOIN mailing m on a.id = m.adresseid";
		}
		
		
		#Détermination de stop_mysql
		$stop_req="select COUNT(*) as stop_mysql from adresses a $mailingAdd where $searchGlobalbis";
		$stop_exec=mysqli_query($doc->mysqli, $stop_req);
		while($stop_line=mysqli_fetch_array($stop_exec)) $stop_mysql=$stop_line["stop_mysql"];
// 		$resRecherche .= "<br>$stop_req<br>" .mysqli_error($doc->mysqli);	
		
		#Requête proprement dite
		$texte_requete="select *, a.id as cardid from adresses a $mailingAdd where $searchGlobalbis order by nom, prenom, fonction, adresse, titre limit $debut_mysql,{$_SESSION["nb_affiche"]}";
		$requete=mysqli_query($doc->mysqli, "$texte_requete");
		$numofrow=mysqli_num_rows($requete);
		
		//on décrit les enregistrements trouvés
		$resRecherche .= "{$doc->lang["resultat_recherche_criteres"]}:<br>";
		if($_POST["searchGlobal"]) $resRecherche .= "<br>{$doc->lang["adresses_resultat_nom"]} :  <b>{$_POST["searchGlobal"]}</b>";	
		if($_POST["searchID"]) $resRecherche .= "<br>{$doc->lang["adresses_resultat_numero"]} :  <b>{$_POST["searchID"]}</b>";	

		if($numofrow > 0)
		{
			if(!$doc->newPdaMenu) $resRecherche .= "<a onClick='changeAcc(3)' accesskey=z>&nbsp;</a>";
			if($numofrow > 1) $affiche_result="{$doc->lang["resultat_recherche_records"]}";
			else $affiche_result="{$doc->lang["resultat_recherche_record"]}";
			$resRecherche .= "\n<br><br><br>$stop_mysql $affiche_result :<br><br>";
			$resRecherche .= $doc->table_open();
	// 		$compteur=2;
			$nb=1;
			
			//on écrit ce qui se passe ligne par ligne 
			while($row = mysqli_fetch_array($requete))
			{
				
				//définition des variables qui seront utiliséespar la suite
				foreach($row as $var=>$val)
				{
					$$var=$val;
				}
				
				//on écrit chaque ligne de texte html
				if($nb % 2==0)
				{
					$class="lignejour1";
				}
				else $class="lignejour2";
				$resRecherche .= "<tr class=\"$class\"><td>";
				if($nb<10) $resRecherche .= $nb.".";
				elseif($nb == 10) $resRecherche .= "0.";
				else $resRecherche .= "&nbsp;";
				$resRecherche .= "</td><td>"; 
	// 			$compteur++;
				$resRecherche .= "$titre&nbsp;$prenom&nbsp;$nom&nbsp; - &nbsp;$fonction&nbsp; - &nbsp;$adresse&nbsp; - &nbsp;$zip&nbsp;$ville&nbsp;</td>";
				//boutons
				//bouton modifier
				$resRecherche .= $doc->form("adresses/modifier.php<td>", "<img src='../images/modifier.png'>", "$nb", "accyes", "1-form$nb<td>", "id", "$cardid");
				//bouton supprimer
				$resRecherche .= $doc->form("adresses/resultat.php<td>", "<img src='../images/b_drop.png'>", "", "accno", "2-form$nb<td>", "id", "$cardid", "action", "delete", "searchGlobal", $_POST["searchGlobal"]);
				$nb++;
			}
			$resRecherche .= "</table>";
		}else{
			$resRecherche .= "<br><br>{$doc->lang["resultat_recherche_rien_trouve"]}";
			$newId = "id=id1";
		}

	}
	
	$fname=($numofrow)?"form":"1-form1";//Affichage de la page
	$fnnum=($numofrow)?"0":"0";//Affichage de la page
if(!$xhr)
{
	if(! $_POST["doRecherche"]) $doc->title_name = $doc->lang["adresses_index_title"];
	$doc->title("<script type=\"text/javascript\" src=\"../externe/XHRConnection.js\"></script>");
	$doc->body("2", "document.getElementById('1-form1').elements[$fnnum].focus()");
	if(! $_REQUEST["standalone"]) $doc->entete();
}
// 	if(! $_POST["doRecherche"]) $doc->title_name = $doc->lang["adresses_index_title"];
// 	$doc->title();
// 	$doc->body("2", "document.getElementById('1-form1').elements[$fnnum].focus()");
// 	if(! $_REQUEST["standalone"]) $doc->entete();
	// $resRecherche .= $doc->beautifyMysql($query);



	if($_POST["doRecherche"])
	{
		echo "<h2>{$doc->lang["adresses_resultat_h2"]}</h2>";
		echo "<br>$resRecherche<br>";
		if($numofrow>0 AND !$stop) $doc->footer();
		echo "<br>{$doc->lang["resultat_recherche_nouvelle"]}<br>";
	}
	else echo "\n<h2>{$doc->lang["adresses_index_title"]}</h2>";
	if($xhr) die();

	$mailings = trim($_SESSION["optionGen"]["mailing"]);
	$mailing = "";
	$mailingArray = explode("\n", trim($_SESSION["optionGen"]["mailing"]));
	$mailingNum = count($mailingArray) + 1; //A cause de la ligne vide à la fin
	$mailingOptions = $doc->simple_selecteur($mailingArray, $_POST["mailingname"]);
	$mailingSelect = "<select name=mailingname[] multiple size=$mailingNum>$mailingOptions</select>";

	echo "<div id=xhrRecherche></div>
	<form method=\"post\" id=\"$fname\" action=\"./resultat.php\" $newId>
	<br>{$doc->lang["adresses_resultat_nom"]}&nbsp;:&nbsp;<input type=\"text\" id=\"searchGlobal\" name=\"searchGlobal\" value=\"{$_POST["searchGlobal"]}\" accesskey=\"{$doc->lang["adresses_resultat_rechercher_accesskey"]}\" onKeyUp=\"sendGlobalSearch('resultat.php')\">
	<br>$mailingSelect<br><button type=\"submit\">{$doc->lang["adresses_index_rechercher"]}</button>
	<input type=hidden name=doRecherche value='on'>
	</form>";
}

if($_POST["action"]=="delete")
{	//mode de recherche de fiches au besoin
	//recherche des fiches employées dans les dossiers associés
	$controle=NULL;
	$doc->title();
	$doc->body(2, "document.getElementById('id1').elements[1].focus()");
	$doc->entete();
	
	$where = "";
	$lftJn = "";
	$chSup = "";
	$ut = 0;
	foreach($doc->liste_utilisateurs(true, true) as $option => $ar)
	{
		if($ar["seul"] != "1")
		{
			$ut ++;
			$nom  = $ar["nom"];
			$base = $option. "clients";
			$chSup .= ", dossiers$option.nodossier as dossiers$option";
			$lftJn .= " LEFT JOIN $base dossiers$option on ";
			$jn = "";
			foreach(array("client" => "noadresse", "pa" => "nopa", "ca" => "noca", "aut" =>"noaut") as $type => $champ)
			{
				for($x = "";$x < 5;$x++)
				{
					if($jn) $jn .= " OR ";
					$jn .= "dossiers{$option}.$champ{$x} = adresses.id";
				}
			}
			$lftJn .= "($jn)";
			$baseTest = substr($option, 0, 2);
			//echo "<h1>en l'état, le nom vaut $nom et la base vaut $base</h1>";
// 			$requete="select * from $base, adresses where ((noadresse=id or noadresse1=id or noadresse2=id or noadresse3=id or noadresse4=id or nopa=id or nopa1=id or nopa2=id or nopa3=id or nopa4=id or noca=id or noca1=id or noca2=id or noca3=id or noca4=id) and id like '{$_POST["id"]}')";
		}
	}
	$requete = "SELECT titre, nom, prenom, fonction, adresse, zip, ville $chSup from adresses $lftJn where id like '{$_POST["id"]}' ";
	$exec_requete=mysqli_query($doc->mysqli, "$requete");
// 	echo "<br>Error:'$requete' : ".mysqli_error($doc->mysqli);
// 	if($exec_requete === False)
	if(mysqli_num_rows($exec_requete)>0)
	{
		$arrRes = array();
		while($r = mysqli_fetch_array($exec_requete, MYSQLI_ASSOC))
		{
// 			foreach($r as $n =>$a) echo "<br> $n => {$r["$n"]}";
			foreach($doc->liste_utilisateurs(true, true) as $option => $ar)
			{
				if(!is_array($arrRes["$option"])) $arrRes["$option"] = array();
				$champ = "dossiers$option";
				if($r[$champ])
				{
					$controle = 1;
					$nodossier = $r["$champ"];
					$arrRes["$option"][] = $nodossier;
				}
			}
		}
	}
	if($controle)
	{
		echo "<font color=ff0000><br><br>{$doc->lang["adresses_supprimer_confirm_exploite"]}&nbsp;:<br></font><table>";
		foreach($arrRes as $init => $nodossiers)
		{
			$arrAv = $arrRes[$init];
			if(count($arrAv) > 0)
			{
				$avocat = $doc->liste_des_utilisateurs["$init"]["nom"];
				echo "<tr><td>{$doc->lang["creer_client_h3"]} $avocat";
				$dossAv = "";
				$arrAv = array_unique($arrAv);
				foreach($arrAv as $nodossier)
				{
					if($dossAv) $dossAv .= ", ";
					if($doc->testval("ecrire", $option)) $dossAv .= $doc->form("modifier_donnees.php", "$nodossier", "", "style=display:inline@form", "", "nodossier", "$nodossier", "new_av", "$init");
					else echo "$nodossier";
				}
				echo " ($dossAv)</td></tr>";
			}
		}
		echo "</table>\n";
	
	}
// 	$doc->beautifyMysql($requete, false, true);
	
	if($controle==NULL) echo "<h2>{$doc->lang["adresses_supprimer_confirm_h2"]} {$_POST["id"]}</h2>";
	echo "<table><tr><td>";
	if($controle==NULL) echo $doc->form("maj_op.php", "{$doc->lang["adresses_supprimer_confirm_supprimer"]}", "", "", "", "action", "delete", "id", "{$_POST["id"]}", "retour", "adresses/resultat");
	echo "</td><td>";
	if($_POST["searchGlobal"]) echo $doc->form("adresses/resultat.php", "{$doc->lang["adresses_modifier_annuler"]}", "{$doc->lang["adresses_modifier_annuler_accesskey"]}", "", "", "searchGlobal", "{$_POST["searchGlobal"]}", "doRecherche", "on");
	else              echo $doc->form("adresses/resultat.php", "{$doc->lang["adresses_modifier_annuler"]}", "{$doc->lang["adresses_modifier_annuler_accesskey"]}", "", "", "searchID", "{$_POST["id"]}", "doRecherche", "on");
	echo "</td></tr></table>
	";
}

$doc->close();
?>
