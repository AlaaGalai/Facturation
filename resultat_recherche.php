<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(127);
$doc=new prolawyer;
//$doc->tab_affiche(0);

//$doc->tab_affiche(0);

if(isset($_REQUEST["standalone"]) && $_REQUEST["standalone"]) $doc->newPdaMenu = True; //en mode standalone, affichage limité
$xhr = isset($_REQUEST["xhr"]) ? $_REQUEST["xhr"]: "";
if($xhr) $_POST["doRecherche"] = "on";
if(isset($_POST["searchGlobal"]) AND preg_match("#^ *([0-9]+)( *(-) *([0-9]+))* *?$#", $_POST["searchGlobal"], $regs))
{
	$forceNum = $regs[1].$regs[3].$regs[4];
// 	unset($_POST["searchGlobal"]);
// 	die($forceNum);
}
else $forceNum = False;


/*AFFICHAGE DES critères
*
*
*/

//Affichage: suivipar
$couleur = array("ff8080", "80ff80", "8080ff", "80ffff", "ff80ff", "ffff80");
$coulCol = array();

//Affichage: état du dossier
# inutile, automatique dans prolawyer() if(! isset($_POST["nom_client"]) && isset($_GET["nom_client"])) $_POST["nom_client"] = $_GET["nom_client"];
$etat = array(array("ffffff", "actif"), array("4040ff", "dormant"), array("ffff00", "attente_paiement"), array("ff8000", "attente_archivage"), array("808080", "a_boucler"), array("ff0000", "archive"));
if($_POST["tousDormants"]) foreach ($etat as $num =>$arr) $_POST["dormant"][] = $num;
//$doc->tab_affiche(0);
$dm = "<select class=inputsize size=6 multiple name='dormant[]' id=dormant>";
$dormant = ($_POST["dormant"] && is_array($_POST["dormant"])) ? $_POST["dormant"]:array(0);
$affiche_dormant = "";
$sleepColors=array();
foreach($etat as $n => $st)
{
	$noun = $st[1];
	$colo = $st[0];
	$sleepColors[] = $colo;
	$nom_dormant = $doc->lang["modifier_donnees_$noun"];
	if (in_array($n, $dormant)) 
	{
		if($affiche_dormant) $affiche_dormant .= ", ";
		$affiche_dormant .= "<span style='background-color:#$colo'>{$doc->lang["modifier_donnees_$noun"]}</span>";
	}
	$selected = (in_array($n, $dormant)) ? "selected":"";
	$dm .=  "<option class=inputsize style='background-color:#ffffff' value='$n' $selected>$nom_dormant</option>";
}
$dm .= "</select>";

$debut_mysql = ($_POST["debut"] <> "") ? $_POST["debut"] : "0";
$critere_global = ($_POST["searchGlobal"]) ? $_POST["searchGlobal"] : ($resultat_recherche_nocritere);
$critere_nom = ($_POST["client"]) ? $_POST["client"] : ($resultat_recherche_nocritere);
$critere_nom_pa = ($_POST["pa"]) ? $_POST["pa"] : ($resultat_recherche_nocritere);
$critere_nom_pj = ($_POST["pj"]) ? $_POST["pj"] : ($resultat_recherche_nocritere);
$critere_naturemandat = ($_POST["naturemandat"]) ? $_POST["naturemandat"] : ($resultat_recherche_nocritere);
$critere_nodossier = ($_POST["nodossier"]) ? $_POST["nodossier"] : ($resultat_recherche_nocritere);
$critere_noarchive = ($_POST["noarchive"]) ? $_POST["noarchive"] : ($resultat_recherche_nocritere);
$critere_dormant = ($_POST["dormant"]) ? $affiche_dormant : ($resultat_recherche_nocritere);
$critere_groupement = ($_POST["groupement"]) ? $doc->lang["resultat_recherche_groupement"] : "";
$dossier = ($_POST["nodossier"] == "") ? "%" : $_POST["nodossier"];
$archive = ($_POST["noarchive"] == "") ? "%" : $_POST["noarchive"];
$_POST["nom_client"]=strtolower($_POST["nom_client"]); //pour éviter les problèmes liés à la casse
$_POST["nom_pa"]=strtolower($_POST["nom_pa"]); //pour éviter les problèmes liés à la casse
$_POST["naturemandat"]=strtolower($_POST["naturemandat"]); //pour éviter les problèmes liés à la casse


$numofrow = 0;
$resRecherche = "";
if($_POST["doRecherche"])
{
	/*CONDITIONS (critères de recherche
	*
	*
	*/

	$requis = array();
	$condit = array();
	// $ajoute = array();

	//Condition: Nom client et nom PA
	$newReq = "";
	$newWhr = "";
	$leftJn = "";
	$critGlobaux = array();
	$parts = array();
	foreach(array("client" => "noadresse", "pa" => "nopa", "pj" => "nopj") as $type => $clId)
	{
		//construction des champs	
		for($x = "";$x <5;$x++)
		{
			foreach(array("prenom", "nom", "fonction", "adresse", "zip", "ville", "id", "tel", "natel", "mail", "telprive", "natelprive", "mailprive") as $champ)
			{
				if ($newReq) $newReq .= ", ";
				$newReq .= "a{$type}{$x}.$champ as {$champ}_$type{$x}";
			}
			if($leftJn) $leftJn .= " ";
			$leftJn .= "LEFT JOIN adresses a{$type}{$x} on a.{$clId}{$x}= a{$type}{$x}.id";
		}
		
		//construction de la requête
		if($_POST["$type"] || $_POST["searchGlobal"])
		{
			$rType = "";
			for($x = "";$x <5;$x++)
			{
				$rNum = "";
				$toExplode = $_POST["searchGlobal"] ? "1":$_POST["$type"];
				$parts["$type"] = explode(" ", $toExplode);
				foreach($parts["$type"] as $i => $part)
				{
					$part = trim($part);
					if($part)
					{
						if($rNum) $rNum .= " AND ";
						$rPart = "";
						//foreach(array("prenom", "nom", "fonction", "adresse", "zip", "ville", "id", "tel", "natel", "mail", "telprive", "natelprive", "mailprive") as $champ) //TODO: vérifier si virer le champ id est une bonne idée
						foreach(array("prenom", "nom", "fonction", "adresse", "zip", "ville", "tel", "natel", "mail", "telprive", "natelprive", "mailprive") as $champ)
						{
							if($rPart) $rPart .= " OR ";
							$protectPart = addslashes(stripslashes($part));
							if($_POST["searchGlobal"])
							{
								$critGlobaux[] = "aclient{$x}.$champ";
								$critGlobaux[] = "apa{$x}.$champ";
								$critGlobaux[] = "apj{$x}.$champ";
							}
							else $rPart .= "a{$type}{$x}.$champ like '%$protectPart%'";
						}
						$rNum .= "($rPart)";
					}
				}
				if($rType) $rType .= " OR ";
				if(!$_POST["searchGlobal"]) $rType .= "($rNum)";
			}
			$condit["$type"] = "$rType";
			$requis[] = "$type";
		}
	}



	// echo "<br><br>$newWhr + " . strlen("@");
// 	echo $doc->beautifyMysql($condit["client"]);
	// echo beautifyMysql($condit["pa"]);

	//Condition: Nature du dossier
	$condNature = "";
	if($_POST["searchGlobal"]) $critGlobaux[] = "naturemandat";
	elseif($_POST["naturemandat"])
	{
		$parts["naturemandat"]  = explode(" ", $_POST["naturemandat"]);
		foreach($parts["naturemandat"] as $part) if(trim($part) != "")
		{
			if($condNature) $condNature .= " AND ";
			$condNature .= "naturemandat like '%$part%'"; 
		}
	}
	$condit["naturemandat"] = "$condNature";

	//Condition: Suivi par
	$condit["suivipar"] = "suivipar like '%{$_POST["suivipar"]}%'";

	//Condition: Type
	if($dormant)
	{
		$c = "";
		$t = 0;
		foreach($dormant as $dormeur)
		{
			if($t) $c .= " or ";
			$t ++;
			if($dormeur == -1)
			{
				$c .= "(noarchive = 0 AND dormant = 0)";
			}
			elseif($dormeur < 5)
			{
				$c .= "(noarchive = 0 AND dormant = $dormeur)";
			}
			else
			{
				$c .= "noarchive > 0";

			}
		}
		if($c)
		{
			$condit["dormant"] = "$c";
		}
	}


	//Condition: N° du dossier / N° d'archive
	foreach(array("nodossier", "noarchive") as $trait)
	{
		$var = $_POST[$trait];
		$var = trim($var);
		$var = preg_replace("# #", "", $var);
		if($var != "")
		{
			if(preg_match("#-#", $var))
			{
				$var = preg_replace("#-#", "' AND '", $var);
				$var = "$trait BETWEEN '$var'";
			}else{
				$var = "$trait = '$var'";
			}
		}else{
			$var="";
		}
		$condit["$trait"] = $var;
	}

	//Autres conditions (en réalité: champs supplémentaires)
	$condit["datearchivage"] = "";
	for($x = "";$x<5;$x++) foreach(array("noadresse", "nopa") as $no) $condit["{$no}{$x}"] = "";


	/*Construction de la requete
	*
	*
	*/
	
	//Requête: Where
	if($forceNum)
	{
		if(preg_match("#-#", $forceNum))
		{
			$var = preg_replace("#(.+)-(.+)#", "BETWEEN '\1'  AND '\2'", $forceNum);
		}else{
			$var = "= '$forceNum'";
		}
		$critGlobaux[-1] = "(nodossier $var OR noarchive $var)";
	}
	if($_POST["searchGlobal"])
	{
		$where = "";
		$parts["searchGlobal"] = explode(" ", $_POST["searchGlobal"]);
		$parts["client"] = $parts["searchGlobal"];
		$parts["pa"] = $parts["searchGlobal"];
		$parts["naturemandat"] = $parts["searchGlobal"];
		foreach($parts["searchGlobal"] as $part)
		{
			$where .= $where? " AND ": "WHERE (";
			$sub = "";
			foreach($critGlobaux as $k => $cr)
			{
				if($sub) $sub .= ($k < 10 && $k != -1) ? " AND ":" OR ";
				$sub .= ($k < 0) ? $cr:"$cr like '%$part%'";
			}
			$where .= "($sub)";
		}
		//$where = preg_replace("#AND OR#", "OR", $where);
		//if(is_numeric($_POST["searchGlobal"]) && $_POST["searchGlobal"] > 0)
		//if(is_numeric($_POST["searchGlobal"]))
		//{
		//	$where .= " OR nodossier = '{$_POST["searchGlobal"]}'";
		//}
		$where .= ")";
		$newReq .= ", naturemandat, suivipar, dormant, nodossier, noarchive, datearchivage, noadresse, nopa, noadresse1, nopa1, noadresse2, nopa2, noadresse3, nopa3, noadresse4, nopa4";
		if($condit["dormant"])
		{
			$where .= " AND ({$condit["dormant"]})";
		}
 		//echo "<br>'$where'";
	}
	
	else
	{
		$where = "";
		foreach($condit as $nom => $val)
		{
			if($val)
			{
				$where .= $where? " AND ": "WHERE ";
				$where .= "($val)";
			}
			if(!in_array($nom, $requis))
			{
				if($newReq) $newReq .= ", ";
				$newReq .= $nom;
			}
		}
	}

	//Requête: Order By
	$_POST["orderby"] = ($_POST["orderby"])? $_POST["orderby"]:"nodossier";
	$doorderby=$_POST["orderby"];
	
	//Groupement des dossiers liés
	if($_POST["groupement"])
	{
		$spec_nodossier = "spec_nodossier";
		$spec_requ      = " if (liea > 0,liea,nodossier) as $spec_nodossier,";
		$newReq = "$spec_requ $newReq";
		$doorderby = "$spec_nodossier, $doorderby";
		$where .= " GROUP BY $spec_nodossier";
	}
	
	//Lié à n'est pas une colonne, mais on en a besoin
	$newReq .= ", liea";
	
// 	echo "<br>'$where'";
	$where = preg_replace("#nodossier ((=|BETWEEN) '[^']+'( AND '[^']+')?)#", "(nodossier \\1) or (liea  \\1)", $where);
// 	echo "<br>'$where2'";
	if($doorderby == "nodossier") $doorderby = " if (liea > 0,liea,nodossier), nodossier";
// 	echo "<br>'$doorderby'";

	//Requête: Sort
	$sortType = $_POST["sort"]? $_POST["sort"]: "ASC";
	
	//Requête: Honoraires
	$hJoin = "LEFT OUTER JOIN on {$_SESSION["session_avdb"]}.nodossier = {$_SESSION["session_opdb"]}.nodossier";


	//Requête finale:
	$qStop = "SELECT $newReq FROM {$_SESSION["session_avdb"]} a $leftJn $where";// limit $debut_mysql, {$_SESSION["nb_affiche"]}";
	$query = "SELECT $newReq FROM {$_SESSION["session_avdb"]} a $leftJn $where ORDER BY $doorderby $sortType LIMIT $debut_mysql, {$_SESSION["nb_affiche"]}";

// 	echo "<br>'$query'";
// 	echo $doc->beautifyMysql($query);

	$resRecherche .= "{$doc->lang["resultat_recherche_criteres"]}:<br>";
	if($critere_global) $resRecherche .= "<br>{$doc->lang["resultat_recherche_global"]} :  <b>$critere_global</b>";
	if($critere_nom) $resRecherche .= "<br>{$doc->lang["resultat_recherche_nom"]} :  <b>$critere_nom</b>";
	if($critere_nom_pa) $resRecherche .= "<br>{$doc->lang["resultat_recherche_nom_pa"]} :  <b>$critere_nom_pa</b>";
	if($critere_nom_pj) $resRecherche .= "<br>{$doc->lang["data_client_pj"]} :  <b>$critere_nom_pj</b>";
	if($critere_naturemandat) $resRecherche .= "<br>{$doc->lang["resultat_recherche_nature"]} :  <b>$critere_naturemandat</b>";
	if($critere_nodossier) $resRecherche .= "<br>{$doc->lang["resultat_recherche_nodossier"]} :  <b>$critere_nodossier</b>";
	if($critere_noarchive) $resRecherche .= "<br>{$doc->lang["resultat_recherche_noarchive"]} :  <b>$critere_noarchive</b>";
	if($critere_dormant) $resRecherche .= "<br>{$doc->lang["modifier_donnees_typedossier"]} :  <b>$critere_dormant</b>";
	if($critere_groupement) $resRecherche .= "<br><b>{$doc->lang["resultat_recherche_groupement"]}</b>";
	
	$resulStop  = mysqli_query($doc->mysqli, $qStop);
	$stop_mysql = mysqli_num_rows($resulStop);
	$numofrow   = $stop_mysql;
	
	$resultats = mysqli_query($doc->mysqli, $query);
	if($numofrow > 0)
	{
		$resRecherche .= "\n<br>";
		$compt = 0;
		$formcompt = 0;
		if($numofrow > 1) $affiche_result="{$doc->lang["resultat_recherche_records"]}";
		else $affiche_result="{$doc->lang["resultat_recherche_record"]}";
		
		//on décrit les enregistrements trouvés
		$resRecherche .= "\n<br><br><br>$numofrow $affiche_result :<br><br>";
		$resRecherche .= $doc->table_open("border=1 width=\"100%\" align=\"left\"");
		$resRecherche .= "<tr>";
		if(!$doc->newPdaMenu) $resRecherche .= "<th><a onClick='changeAcc()' accesskey=z>+</a></th>";
		$colArray = $doc->newPdaMenu ? array("nodossier" => "#", "nom_client" => $doc->lang["data_client_client"], "nom_pa" => $doc->lang["resultat_recherche_pa"], "suivipar" => $doc->lang["modifier_donnees_suivi"]):array("nodossier" => $doc->lang["resultat_recherche_nodossier"], "noarchive" => $doc->lang["resultat_recherche_noarchive"]."<br>({$doc->lang["modifier_donnees_date_archivage"]})", "nom_client" => $doc->lang["resultat_recherche_coordonnees"],"naturemandat" => $doc->lang["resultat_recherche_nature"], /*"nom_autre" =>"autre nom",*/ "nom_pa" => $doc->lang["resultat_recherche_pa"], "suivipar" => $doc->lang["modifier_donnees_suivi"]);
		foreach($colArray as $orderby => $design)
		{
			$iName = strtolower($sortType);
			$counterSort = ($_POST["sort"] == "ASC")?"DESC":"ASC";
			$class = ($orderby != $_POST["orderby"])? "":"class=\"attention\"";
			$image = ($orderby != $_POST["orderby"])? "":"&nbsp;<img src=\"{$doc->settings["root"]}images/$iName.png\">";
			$resRecherche .= "<th><form action = ./resultat_recherche.php method=POST style='display:inline'><button type=submit $class>$design</button>$image";
			foreach($_POST as $nom => $val)
			{
				if(!in_array($nom, array("sort", "dormant"))) $resRecherche .= ($nom != "orderby")? $doc->input_hidden($nom, 1):$doc->input_hidden("orderby", "", $orderby);
				if($nom == "dormant") foreach($_POST["dormant"] as $d =>$dV) $resRecherche .= $doc->input_hidden("dormant[]", "", $dV);
			}
			$resRecherche .= ($orderby != $_POST["orderby"])? $doc->input_hidden("sort", "", "ASC"): $doc->input_hidden("sort", "", "$counterSort");
			$resRecherche .= "</form></th>";
		}
		if(! $_REQUEST["standalone"]) $resRecherche .= "<th>{$doc->lang["resultat_recherche_action"]}</th>
		</tr>";
		while($row=mysqli_fetch_array($resultats))
		{
			$compt ++;
			$comptname="form".$compt;
			if($compt<10) $accesskey=$compt;
			elseif($compt == 10) $accesskey=0;
			else $accesskey = NULL;
			if(!$row["noarchive"]) $row["noarchive"]="";//évite l'affichage d'un numéro inexistant par 0
			if($compt<11) $no_compt="$compt";
			else $no_compt="&nbsp;";
			if($row["datearchivage"]>0)
			{
					$class="class=\"attention_bg\"";
					if(! $doc->newPdaMenu) $row["noarchive"] .= "<br>(".$doc->mysql_to_print($row["datearchivage"]).")";
			}
			else
			{
				$class="";
			}
			if($row["dormant"])
			{
				$dormeur = $row["dormant"];
				$class2="style='background-color:#{$sleepColors[$dormeur]}'";
			}
			else $class2="";
			$affNodossier = $row["liea"] ? "{$row["nodossier"]}<br><span style='color:grey'>&rarr;{$row["liea"]}</span>":$row["nodossier"];
			if($doc->newPdaMenu)
			{
				$newAffiche = "";
				if($row["prenom_client"]) $newAffiche .= substr(ucfirst($row["prenom_client"]), 0, 1).".";
				$newAffiche .= $row["nom_client"];
				$newPa = "";
				if($row["prenom_pa"]) $newPa .= substr(ucfirst($row["prenom_pa"]), 0, 1).".";
				$newPa .= $row["nom_pa"];
				if($newPa) $newAffiche .= " c. $newPa";
				else $newAffiche .= "/{$row["naturemandat"]}";
				$lineOnclick = ($_REQUEST["standalone"])? "onclick=\"window.opener.document.getElementById('new_file').value='{$row["nodossier"]}';window.opener.document.getElementById('nouveaudossier').innerHTML='$newAffiche';window.close()\"":"";
				$totalNumberInfo = "<span $class2>$affNodossier</span>";
				if($row["noarchive"]) $totalNumberInfo .= "<br>(<span $class>{$row["noarchive"]}</span>)";
				$resRecherche .= "\n<tr $lineOnclick><td>$totalNumberInfo</td>";
			}
			else
			{
				$resRecherche .= "\n<tr><td>$no_compt&nbsp;</td>";
				$resRecherche .= "<td $class2>$affNodossier&nbsp;</td>";
				$resRecherche .= "<td $class>{$row["noarchive"]}&nbsp;</td>";
			}
			foreach(array("client" => "noadresse", "pa" => "nopa") as $typePers => $champPers)
			{
				$pers = "";
				for($x="";$x<5;$x++)
				{
					$champTest = "{$champPers}{$x}";
					$comp=$champPers.$x;
					$id=$row[$comp] ? $row[$comp]:"";
					if($id)
					{
						$affId = $doc->newPdaMenu ? "": "($id)";
						$chain="";
						$chainChamps = array();
						foreach(array("prenom", "nom", "fonction", "adresse", "zip", "ville") as $champ)
						{
							$$champ = "";
							$champrow = "{$champ}_{$typePers}{$x}";
							$aff = trim($row["$champrow"]);
							if(trim($aff) != "") $$champ = $aff;
							if(is_array($parts[$typePers])) $$champ = $doc->make_visible($parts[$typePers], $$champ);
						}
						if($nom) $nom = "<b>$nom</b>";
						if(!$zip) $zip = "";
						if($nom && $prenom) $nom = "$prenom $nom";
						elseif($prenom) $nom = $prenom;
						if($zip && $ville) $ville = "$zip $ville";
						foreach(array("nom", "fonction", "adresse", "ville") as $champ) if($$champ && (!$doc->newPdaMenu || $champ == "nom"))
						{
							if($chain) $chain .= ", ";
							$chain .=  $$champ;
							$formcompt ++;
						}
						$persOnclick = (!$_REQUEST["standalone"])? "onClick=\"document.getElementById('fcompt$formcompt').submit();\"":"";
						$pers .= "\n<tr><td style=\"cursor:pointer\" $persOnclick>$chain $affId</td>";
						$pers .= $doc->form("adresses/modifier.php<td>", "", "", "", "fcompt$formcompt<td>", "id", $id);
						$pers .= "</tr>";
					}
				}
				$pers = "<table>$pers</table>";
				$affPers["$typePers"] = $pers;
			}
			if(is_array($parts["naturemandat"]))
			{
				$row["naturemandat"] = $doc->make_visible($parts["naturemandat"], $row["naturemandat"]);
			}
			if($doc->newPdaMenu) $affPers["client"] .= "<br>" . $row["naturemandat"];
			$resRecherche .= "<td>{$affPers["client"]}</td>";
			if(! $doc->newPdaMenu) $resRecherche .= "<td>{$row["naturemandat"]}&nbsp;</td>";
			if($row["suivipar"])
			{
				$suiveur = $row["suivipar"];
				if(!array_key_exists($suiveur, $coulCol))
				{
					$indice = count($coulCol);
					$actCoul = $couleur[$indice];
					$coulCol[$suiveur] = $actCoul;
				}
				$suiveur = "<span style='background-color:#{$coulCol["$suiveur"]}'>$suiveur</span>";
			}
			else $suiveur = "";
// 			$resRecherche .= "<td>$pers&nbsp;<a href=# onclick=\"window.open('{$doc->settings["root"]}liste_soldes.php?clientReq={$row["nodossier"]}','modifier','scrollbars=yes,width=600,height=600,toolbar=no,directories=no,menubar=no,location=no,status=no')\">{$doc->lang["multi_clients_title"]}</a></td>";
			$resRecherche .= "<td>{$affPers["pa"]}";
			if(!$_REQUEST["standalone"]) $resRecherche .= "&nbsp;<a href=# onclick=\"window.open('{$doc->settings["root"]}liste_soldes.php?clientReq={$row["nodossier"]}','modifier','scrollbars=yes,width=600,height=600,toolbar=no,directories=no,menubar=no,location=no,status=no')\">{$doc->lang["multi_clients_title"]}</a></td>";
			//$resRecherche .= "<td>{$row["suivipar"]}</td>";
			$resRecherche .= "<td>$suiveur</td>";
			if(!$_REQUEST["standalone"])
			{
				$resRecherche .= "<td><table cellspacing=0 cellpadding=0><tr><td>";
				$resRecherche .= $doc->form("operations.php", "<img src='images/operations.png'>", $accesskey, "accyes", "1-$comptname<td>", "nodossier", $row["nodossier"], "secteur", "operations");
				$resRecherche .= "</td><td>";
				$resRecherche .= $doc->form("operations.php", "<img src='images/encaissements.png'>", "", "accno", "2-$comptname<td>", "nodossier", $row["nodossier"], "secteur", "encaissements");
				$resRecherche .= "</td><td>";
				$resRecherche .= $doc->form("modifier_donnees.php", "<img src='images/modifier.png'>", "", "accno", "3-$comptname<td>", "nodossier", $row["nodossier"]);
				$resRecherche .= "</td><td>";
				if ($row["chemin"]) $resRecherche .= "<a href='file://{$row["chemin"]}' target='4-$comptname'><img src='images/folder.png'></a>";
				$resRecherche .= "</td></tr></table></td>";
			}
			$resRecherche .= "</tr>";
		}
		$resRecherche .= $doc->table_close();
	}else {       // S'il n'y a rien qui correspond aux critères, on le dit.
		$resRecherche .= "<br><br><table align=left border=\"0\" width=\"100%\">
		<tr><td>{$doc->lang["resultat_recherche_rien_trouve"]}.</td></tr>";
		$resRecherche .= "</table></td></tr>";
	}
	//Dans les deux cas, on peut effectuer une nouvelle recherche
	
	$checked = $_POST["plusarchive"] ? "checked":"";
	$resRecherche .= "<tr><td>";
	$resRecherche .= "<br>";
}

$fname=($numofrow)?"form":"1-form1";//Affichage de la page
$fnnum=($numofrow)?"0":"0";//Affichage de la page
if(! $_POST["doRecherche"]) $doc->title_name = $doc->lang["recherche_dossier_title"];
if(!$xhr)
{
	$doc->title("<script type=\"text/javascript\" src=\"./externe/XHRConnection.js\"></script>");
	$doc->body("2", "document.getElementById('1-form1').elements[$fnnum].focus()");
	if(! $_REQUEST["standalone"]) $doc->entete();
}
// echo $doc->beautifyMysql($query);


// if(!$xhr) echo "\n<div id=xhrRecherche>";
if($_POST["doRecherche"])
{
	echo "<h2>{$doc->lang["adresses_resultat_h2"]}</h2>";
	echo "<br>$resRecherche<br>";
	if($numofrow>0 AND !$stop) $doc->footer();
	echo "<br>{$doc->lang["resultat_recherche_nouvelle"]}<br>";
}
else echo "\n<h2>{$doc->lang["recherche_dossier_title"]}</h2><h3>({$doc->lang["entete_base_en_cours"]} : {$doc->avocat})</h3>";
if($xhr) die();
// echo "</div>";

$checkAll = $doc->input_checkbox("tousDormants", True, "", "", "onChange=sendGlobalSearch()");
$suivi=$doc->simple_selecteur("", $_POST["suivipar"]);

//if(this.value.length ==0){document.getElementById('xhrRecherche').innerHTML = ''};if(this.value.length > 2){d=document.getElementById('dormant');dv='';for(i=0;i<d.options.length;i++){if(d.options[i].selected){dv += '&dormant[]=' + d.options[i].value}};if(document.getElementById('tousDormants').checked) {dv += '&tousDormants=1'};sendData('searchGlobal=' + this.value + dv + '&xhr','on', 'resultat_recherche.php', 'POST', 'xhrRecherche')}
echo "
<br><form name=\"$fname\" id=\"$fname\" method=post action=\"./resultat_recherche.php\">
<table style=\"white-space:nowrap\">
<tr><td width=\"100\">{$doc->lang["resultat_recherche_global"]} :</td><td><input type=text class=inputsize id=\"searchGlobal\" name=\"searchGlobal\" value=\"{$_POST["searchGlobal"]}\" onfocus=select() onKeyUp=\"sendGlobalSearch()\"></td></tr>
<tr><td style=visibility:hidden>{$doc->lang["resultat_recherche_groupement"]}&nbsp;:</td></tr>
</table>
<input type=hidden name=doRecherche value=on>";
echo $doc->input_hidden("standalone", 1);
echo "</form>
<form name=\"$fname\" id=\"$fname\" method=post action=\"./resultat_recherche.php\">
<div id=xhrRecherche></div>
<table style=\"white-space:nowrap\">
<tr>
<td width=\"100\">{$doc->lang["recherche_dossier_nom"]} :</td><td><input type=text class=inputsize name=\"client\" value=\"{$_POST["client"]}\" onfocus=select()></td>
</tr>
<tr>
<td width=\"100\">{$doc->lang["recherche_dossier_nom_pa"]} :</td><td><input type=text class=inputsize  name=\"pa\" value=\"{$_POST["pa"]}\" onfocus=select()></td>
</tr>
<tr>
<td width=\"100\">{$doc->lang["data_client_pj"]} :</td><td><input type=text class=inputsize  name=\"pj\" value=\"{$_POST["pj"]}\" onfocus=select()></td>
</tr>
<tr>
<td>{$doc->lang["resultat_recherche_nature"]}&nbsp;:</td><td><input type=text class=inputsize name=\"naturemandat\" value=\"{$_POST["naturemandat"]}\" onfocus=select()></td>
</tr>
<tr>
<td>{$doc->lang["resultat_recherche_nodossier"]} :</td><td><input type=text class=inputsize  name=\"nodossier\" value=\"{$_POST["nodossier"]}\" onfocus=select()></td>
</tr>
<tr>
<td>{$doc->lang["resultat_recherche_noarchive"]} :</td><td><input type=text class=inputsize  name=\"noarchive\" value=\"{$_POST["noarchive"]}\" onfocus=select()></td>
</tr>
<tr>
<td>{$doc->lang["modifier_donnees_suivi"]} :</td><td><select class=inputsize name=suivipar>$suivi</select></td>
</tr>
<tr>
<td>{$doc->lang["modifier_donnees_typedossier"]}&nbsp;:</td><td>{$doc->lang["general_tous"]} $checkAll<br>$dm</td>
</tr>";
echo "<td>{$doc->lang["resultat_recherche_groupement"]}&nbsp;:</td><td>";
echo $doc->input_checkbox("groupement", True);
echo "</td></tr>";
echo $doc->input_hidden("standalone", 1);
/*echo "<tr>
<td>{$doc->lang["liste_soldes_y_compris"]}&nbsp;</td><td><input type=checkbox name=plusarchive $checked></td>
</tr>";*/
echo "</table>
<input type=hidden name=doRecherche value=on>
<input type=submit value=\"{$doc->lang["recherche_dossier_recherche"]}\">
</form>";


$doc->close();	
?>
