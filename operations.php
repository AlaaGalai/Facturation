<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);


//Gestion du timesheet
if($_REQUEST["timesheet"] AND !$_POST["timestamp_debut"] AND !$_POST["timestamp_fin"])
{
	$_POST["timestamp_debut"]  = $_POST["timestamp_fin"] = time();
// 	die($_POST["timestamp_debut"]."  = ".$_POST["timestamp_fin"]);
}


//Gestion du secteur
if ( !isset($_GET["secteur"]) && ! isset($_POST["secteur"])) $_POST["secteur"] = "operations";


$doc=new prolawyer;
//$doc->tab_affiche();
//Ne jamais afficher une page de dossier si le numéro de dossier n'est pas fourni.
if(!isset($_REQUEST["nodossier"]) && $_POST["secteur"]!= "journal" && $_POST["secteur"]!= "journal_op" && $_POST["secteur"]!= "tva" && $_POST["secteur"]!= "benefice")
{
	header("Location:./resultat_recherche.php");
	die();
}
if($doc->csv)
{
	$av = $doc->init_to_name();
	$filename = "{$av}_{$_POST["secteur"]}_{$_POST["jour_debut"]}.{$_POST["mois_debut"]}.{$_POST["annee_debut"]}-{$_POST["jour_fin"]}.{$_POST["mois_fin"]}.{$_POST["annee_fin"]}.csv";
// 	die($filename);
	header("Content-Disposition: attachment; filename=\"$filename\"");
}



//Titre de la page
if($_POST["secteur"]=="operations") $doc->lang["operations_title"]=$doc->lang["operations_title1"];
if($_POST["secteur"]=="encaissements") $doc->lang["operations_title"]=$doc->lang["operations_title2"];
if($_POST["secteur"]=="journal") $doc->lang["operations_title"]=$doc->lang["operations_title3"];
if($_POST["secteur"]=="journal_op") $doc->lang["operations_title"]=$doc->lang["operations_title3bis"];
if($_POST["secteur"]=="tva") $doc->lang["operations_title"]=$doc->lang["operations_title4"];
if($_POST["secteur"]=="benefice") $doc->lang["operations_title"]=$doc->lang["operations_title6"];


//variables de document
if(!$_POST["secteur"]) $_POST["secteur"]="operations";
if($_POST["sous_traitant_limite"]) $sous_traitant_limite = "AND o.soustraitant like '%{$_POST["sous_traitant_limite"]}%'";
if($_POST["recherche_limite"]) $recherche_limite = stripslashes($_POST["recherche_limite"]);
if($_POST["recherche_limite"]) $texte_recherche = stripslashes($_POST["texte_recherche"]);
$debut_mysql = ($_POST["debut"]) ? $_POST["debut"] : "0";
if ($_POST["anneedaterechdebut"]!="" AND $_POST["moisdaterechdebut"]!="" AND $_POST["jourdaterechdebut"]!="")
{
	if($_POST["secteur"]=="operations" OR $_POST["secteur"]=="journal_op") $_POST["dateop"]="{$_POST["anneedaterechdebut"]}-{$_POST["moisdaterechdebut"]}-{$_POST["jourdaterechdebut"]}";
	elseif ($_POST["secteur"]=="encaissements" OR $_POST["secteur"]=="tva"OR $_POST["secteur"]=="benefice" OR $_POST["secteur"]=="journal") $_POST["dateac"]="{$_POST["anneedaterechdebut"]}-{$_POST["moisdaterechdebut"]}-{$_POST["jourdaterechdebut"]}";
}
if ($_POST["anneedaterechfin"]!="" AND $_POST["moisdaterechfin"]!="" AND $_POST["jourdaterechfin"]!="")
{
	if($_POST["secteur"]=="operations" OR $_POST["secteur"]=="journal_op") $_POST["dateoprechfin"]="{$_POST["anneedaterechfin"]}-{$_POST["moisdaterechfin"]}-{$_POST["jourdaterechfin"]}";
	elseif ($_POST["secteur"]=="encaissements" OR $_POST["secteur"]=="tva" OR $_POST["secteur"]=="benefice"OR $_POST["secteur"]=="journal") $_POST["dateacrechfin"]="{$_POST["anneedaterechfin"]}-{$_POST["moisdaterechfin"]}-{$_POST["jourdaterechfin"]}";
}
if($_POST["temps_heure"] OR $_POST["temps_minute"])
{
	if($_POST["temps_heure"]=="") $_POST["temps_heure"]="0";
	if($_POST["temps_heure"]<9) $_POST["temps_heure"]="0".$_POST["temps_heure"];
	if($_POST["temps_minute"]=="") $_POST["temps_minute"]="0";
	if($_POST["temps_minute"]<9) $_POST["temps_minute"]="0".$_POST["temps_minute"];
	$_POST["tempsop"]="{$_POST["temps_heure"]}:{$_POST["temps_minute"]}:00";
}


// Pour facture; nom different pour eviter toute collistion avec la variable timestamp_(debut|fin) définie ailleurs
if($_POST["op_jour_debut"] AND $_POST["op_mois_debut"] AND $_POST["op_annee_debut"] AND $_POST["op_jour_fin"] AND $_POST["op_mois_fin"] AND $_POST["op_annee_fin"])
{
	$_POST["ts_debut"]=mktime(0,0,0, $_POST["op_mois_debut"], $_POST["op_jour_debut"], $_POST["op_annee_debut"]);
	$_POST["ts_fin"]=mktime(23,59,59, $_POST["op_mois_fin"], $_POST["op_jour_fin"], $_POST["op_annee_fin"]);
}
$doc->getTemplates(True);

#TODO: style ajouté pour ne pas confondre l'ancienne version de la page avec la nouvelle. A supprimer ensuite.
#$doc->title("<style  type='text/css'>body{border-width:2;border-color:#ffff00;border-style:solid}</style>");
//$doc->title();
$doc->title("<script type=\"text/javascript\" src=\"./externe/XHRConnection.js\"></script>\n\t\t<script>af = 'date_jour';</script>");
$doc->body(2);
$doc->entete();
if (! $doc->noHtml) echo $doc->self_reload();


//la page gère plusieurs types d'affichage
if($_POST["secteur"]=="operations" || $_POST["secteur"]=="journal_op")
{
	$optype="op";
}else{
	$optype="acpar";
}

//recherche particulière: pour limiter les opérations d'un dossier à certains critères seulement.
if($_POST["recherche"]=="on")
{
	$compteur=0;
	$texte_recherche="";
	$recherche_limite="";
	$arr=array("nodossier", "dateop" , "op" , "opavec" , "tempsop" , "dateac" , "ac" , "acpar" , "encaissement" , "provision" , "avfrais" , "demande" ,"facture", "transit" , "depens", "facturesanstemps", "soustraitant", "enattente");
	foreach($arr as $nom_rech){
		if($_POST["$nom_rech"])
		{
			if ($nom_rech == "encaissement" || $nom_rech == "provision" || $nom_rech == "avfrais" || $nom_rech ==  "demande" || $nom_rech == "facture" || $nom_rech == "transit" || $nom_rech == "depens" || $nom_rech == "facturesanstemps")
			{
				if(preg_match("/\.\.\./", $_POST["$nom_rech"]))
				{
					list($debut, $fin) = preg_split("/\.\.\./", $_POST[$nom_rech]);
					$recherche_limite .= " AND o.$nom_rech between '". number_format($debut, 2, '.', '')."' AND '". number_format($fin, 2, '.', '')."'";
				}
				elseif(preg_match("/<|>|<=|>=/", $_POST["$nom_rech"], $res1))
				{
					if(preg_match("/<=|>=/", $_POST["$nom_rech"], $res)) $signe = $res[0];
					else $signe = $res1[0];
					list($vide, $nmrech) = preg_split("/$signe/", $_POST["$nom_rech"]);
					$recherche_limite .= " AND o.$nom_rech $signe '". number_format($nmrech, 2, '.', '')."'";
				}
				else $recherche_limite .= " AND o.$nom_rech like '". number_format($_POST["$nom_rech"], 2, '.', '')."'";
			}
			elseif($nom_rech == "nodossier")
			{
				if ($_POST["secteur"] == "journal" || $_POST["secteur"] == "journal_op") $recherche_limite .= " AND o.$nom_rech = {$_POST["nodossier"]}";
			}
			elseif($nom_rech == "opavec" || $nom_rech == "acpar" || $nom_rech == "op" || $nom_rech == "ac")
			{
				$nRech = "%{$_POST["$nom_rech"]}%";
				$recherche_limite .= " AND (o.$nom_rech like '$nRech' OR o.$nom_rech like '".html_entity_decode("{$nRech}")."' OR o.$nom_rech like '".$doc->my_htmlentities("{$nRech}")."')";
			}
			elseif(($nom_rech == "dateop" && $_POST["dateoprechfin"]) || ($nom_rech == "dateac" && $_POST["dateacrechfin"]))
			{
				$nom_fin = $nom_rech."rechfin";
				$recherche_limite .= "AND o.$nom_rech between '{$_POST["$nom_rech"]}' AND '{$_POST["$nom_fin"]}'";
				list($annee, $mois, $jour) = preg_split("/-/", $_POST["$nom_rech"]);
				list($anneef, $moisf, $jourf) = preg_split("/-/", $_POST["$nom_fin"]);
				$_POST["$nom_rech"] = "$jour.$mois.$annee - $jourf.$moisf.$anneef";
			}
			elseif($nom_rech == "dateop" || $nom_rech == "dateac")
			{
				$recherche_limite .= " AND o.$nom_rech = '{$_POST["$nom_rech"]}'";
				list($annee, $mois, $jour) = preg_split("/-/", $_POST["$nom_rech"]);
				$_POST["$nom_rech"] = "$jour.$mois.$annee";
			}
			else $recherche_limite .= " AND o.$nom_rech = '{$_POST["$nom_rech"]}'";
			if($compteur!=0) $texte_recherche=$texte_recherche.", ";
			if($nom_rech=="encaissement") $nom_rech_affiche="rentree";
			else $nom_rech_affiche = $nom_rech;
			$texte_affiche="operations_".$nom_rech_affiche;
			$texte_affiche=$doc->lang["$texte_affiche"];
			$texte_affiche=preg_replace("#<br>#", "", "$texte_affiche");
			$texte_recherche=$texte_recherche."$texte_affiche = {$_POST["$nom_rech"]}";
			$compteur++;
		}
	}
}

//affichage d'une recherche éventuelle:
if($texte_recherche) echo "{$doc->lang["operations_afficher_recherche"]}&nbsp;: $texte_recherche";

##Sélection commune pour une année entière
if(!$doc->print)
{
	$anneeInit = $_POST["forceAnnee"] ? $_POST["forceAnnee"] : $doc->univ_strftime("%Y", time());
	$anneesArr = array();
	for($x = $anneeInit -5; $x < $anneeInit + 6;$x ++) $anneesArr[] = $x;
	$forceAnneeList = $doc->simple_selecteur($anneesArr, $_REQUEST["forceAnnee"]);
	// echo "<br>'{$_REQUEST["forceAnnee"]}'";
	$keep = $doc->form_global_var;
	$doc->form_global_var = $_REQUEST;
	$anneeForm = $doc->form("operations.php", $doc->lang["operations_ou_annee"]."&nbsp;", "", "style='font-size:1em;display:inline'@form", "", "forceAnnee", $forceAnneeList);
	$doc->form_global_var = $keep;
	$searchForm  = "\n<h2><form method=post action=".$root."operations.php style='display:inline'>";
	$searchForm .= "{$doc->lang["operations_resultat"]} {$doc->lang["general_du"]}&nbsp;";
	$searchForm .= $doc->split_date("POST", "_debut");
	$searchForm .= "&nbsp;{$doc->lang["general_au"]}&nbsp;";
	$searchForm .= $doc->split_date("POST", "_fin")."&nbsp;";
	$searchForm .= $doc->button("{$doc->lang["operations_afficher"]}", ""); 
	$searchForm .= $doc->input_hidden("secteur", 1);
	$searchForm .= "\n</form>($anneeForm)</h2>";
}

//limitation par plage de date

if($_POST["timestamp_debut"] AND $_POST["timestamp_fin"])
{
	$date_op_debut = $doc->date_mtf($_POST["timestamp_debut"]);
	$date_op_fin = $doc->date_mtf($_POST["timestamp_fin"]);
	$op_date_limite= " AND dateop between '$date_op_debut' and '$date_op_fin'";
	$ac_date_limite= " AND dateac between '$date_op_debut' and '$date_op_fin'";
	$formated_date_debut = $doc->mysql_to_print($date_op_debut);
	$formated_date_fin = $doc->mysql_to_print($date_op_fin);
	$plage_limite = "$formated_date_debut - $formated_date_fin";
	
	//année précédente ("last year")
	$date_op_debut_ly= $_POST["annee_debut"] -1 ."-".$_POST["mois_debut"]."-".$_POST["jour_debut"];
	$date_op_fin_ly= $_POST["annee_fin"] -1 ."-".$_POST["mois_fin"]."-".$_POST["jour_fin"];
	$op_date_limite_ly = " AND dateop between '$date_op_debut_ly' and '$date_op_fin_ly'";
	$ac_date_limite_ly = " AND dateac between '$date_op_debut_ly' and '$date_op_fin_ly'";	
	
	//année pré-précédente ("last prec year")
	$date_op_debut_lpy= $_POST["annee_debut"] -2 ."-".$_POST["mois_debut"]."-".$_POST["jour_debut"];
	$date_op_fin_lpy= $_POST["annee_fin"] -2 ."-".$_POST["mois_fin"]."-".$_POST["jour_fin"];
	$op_date_limite_lpy = " AND dateop between '$date_op_debut_lpy' and '$date_op_fin_lpy'";
	$ac_date_limite_lpy = " AND dateac between '$date_op_debut_lpy' and '$date_op_fin_lpy'";	
}

if(($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="journal_op") AND $doc->print !="on")
{
	echo "\n\n<!-- Debut de la recherche de date -->";
	echo "\n<br><form method='post' action='{$_SERVER["PHP_SELF"]}'>";
	echo $doc->input_hidden("secteur", "1");
	echo $doc->input_hidden("nodossier", "1");
	if($recherche_limite) echo $doc->input_hidden("recherche_limite", "", "$recherche_limite");
	if($texte_recherche) echo $doc->input_hidden("texte_recherche", "", "$texte_recherche");

	echo $doc->split_date("POST", "_debut");
	echo " {$doc->lang["general_au"]} ";
	echo $doc->split_date("POST", "_fin");
	echo "&nbsp;{$doc->lang["operations_par"]}&nbsp;<select name=sous_traitant_limite>";
	echo $doc->simple_selecteur("", $_POST["sous_traitant_limite"], 0, False, False, True);
	echo "</select>&nbsp;";
	

	echo $doc->button($doc->lang["operations_limiter"], ""), "</form> ($anneeForm)";
	echo "\n<!-- Fin de la recherche de date -->";
}

//définition de stop_mysql et des entêtes à afficher éventuellement
if($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements" || $anchor=="afficher_operations")
{
	// Les requêtes sont déplacées dans data_client.php.
}
//plus simple pour journal, tva et benefice car la requête n'est pas limitée à un fichier
elseif($_POST["secteur"] == "tva")
{
// 	$requete_calcul_global		= "select COUNT(*) as stop_mysql, SUM(encaissement) as 'total_recette' from {$_SESSION["session_opdb"]} where (dateac <> 0 $ac_date_limite $recherche_limite $sous_traitant_limite)";

	//année actuelle (séparée par taux de TVA)
	$gainspartva = array();
	$requete_calcul_global = "select COUNT(*) as stop, tvadossier, SUM(encaissement) + SUM(provision) + SUM(depens) as 'total_recette_partiel' from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (dateac <> 0 $ac_date_limite $recherche_limite $sous_traitant_limite) group by tvadossier order by tvadossier";
// 	echo "<br>'$requete_calcul_global'";
	$calcul_global = mysqli_query($doc->mysqli, $requete_calcul_global);
	$stop_mysql = 0;
	$total_global = 0;
	while($r=mysqli_fetch_array($calcul_global))
	{
		$gainspartva["{$r["tvadossier"]}"] = $r["total_recette_partiel"];
		$stop_mysql += $r["stop"];
		$total_global += $r["total_recette_partiel"];
	}

	//année précédente (sans séparation par taux de TVA)
	$requete_calcul_global_ly = "select SUM(encaissement) + SUM(provision) + SUM(depens) as 'total_recette_ly' from {$_SESSION["session_opdb"]} o where (o.dateac <> 0 $ac_date_limite_ly $recherche_limite $sous_traitant_limite)";
// 	echo "<br>'$requete_calcul_global_ly'";
	$calcul_global_ly = mysqli_query($doc->mysqli, $requete_calcul_global_ly);
	while($r=mysqli_fetch_array($calcul_global_ly)) foreach($r as $a => $b) $$a = $b;
// 	echo $total_recette_ly;
}
elseif($_POST["secteur"] == "benefice")
{
	//1. année actuelle
	//TVA
	$requete_calcul_global = "select SUM(encaissement) + SUM(provision) + SUM(depens) as 'total_recette' from {$_SESSION["session_opdb"]} o where (dateac <> 0 $ac_date_limite $recherche_limite $sous_traitant_limite)";
	$calcul_global = mysqli_query($doc->mysqli, $requete_calcul_global);
	while($r=mysqli_fetch_array($calcul_global)) foreach($r as $a => $b) $$a = $b;
	//Bénéfice
	$requete_benefice = "select COUNT(*) as stop_mysql, SUM(facture) as 'total_facture' from {$_SESSION["session_opdb"]} o where ((o.facture <> 0 OR o.demande <> 0) $ac_date_limite $recherche_limite $sous_traitant_limite)";
	$benefice = mysqli_query($doc->mysqli, $requete_benefice);
	while($r=mysqli_fetch_array($benefice)) foreach($r as $a => $b) $$a = $b;

	//2. année précédente
	//TVA
	$requete_calcul_global_ly = "select SUM(encaissement) + SUM(provision) + SUM(depens) as 'total_recette_ly' from {$_SESSION["session_opdb"]} o where (dateac <> 0 $ac_date_limite_ly $recherche_limite $sous_traitant_limite)";
	$calcul_global_ly = mysqli_query($doc->mysqli, $requete_calcul_global_ly);
	while($r=mysqli_fetch_array($calcul_global_ly)) foreach($r as $a => $b) $$a = $b;
	//Bénéfice
	$requete_benefice_ly = "select SUM(facture) as 'total_facture_ly' from {$_SESSION["session_opdb"]} o where (dateac <> 0 $ac_date_limite_ly $recherche_limite $sous_traitant_limite)";
	$benefice_ly = mysqli_query($doc->mysqli, $requete_benefice_ly);
	while($r=mysqli_fetch_array($benefice_ly)) foreach($r as $a => $b) $$a = $b;
	
	//3. année pré-précédente (slt CA pour travaux en cours)
	//TVA
	$requete_calcul_global_lpy = "select SUM(encaissement) + SUM(provision) + SUM(depens) as 'total_recette_lpy' from {$_SESSION["session_opdb"]} o where (dateac <> 0 $ac_date_limite_lpy $recherche_limite $sous_traitant_limite)";
	$calcul_global_lpy = mysqli_query($doc->mysqli, $requete_calcul_global_lpy);
	while($r=mysqli_fetch_array($calcul_global_lpy)) foreach($r as $a => $b) $$a = $b;
	//Bénéfice
	$requete_benefice_lpy = "select SUM(facture) as 'total_facture_lpy' from {$_SESSION["session_opdb"]} o where (dateac <> 0 $ac_date_limite_lpy $recherche_limite $sous_traitant_limite)";
	$benefice_lpy = mysqli_query($doc->mysqli, $requete_benefice_lpy);
	while($r=mysqli_fetch_array($benefice_lpy)) foreach($r as $a => $b) $$a = $b;
}

//encore plus simple pour les journaux parce qu'ils n'ont pas d'en-tête.
elseif($_POST["secteur"] == "journal")
{
	$requete_calcul_global="select COUNT(*) as stop_mysql from {$_SESSION["session_opdb"]} o where (o.dateac <> 0 $ac_date_limite $recherche_limite $sous_traitant_limite)";
	$calcul_global = mysqli_query($doc->mysqli, $requete_calcul_global);
	while($r=mysqli_fetch_array($calcul_global)) $stop_mysql=$r["stop_mysql"];
}

elseif($_POST["secteur"]=="journal_op")
{
	$requete_op_global="select time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', COUNT(*) as stop_mysql from {$_SESSION["session_opdb"]} o where (o.dateop <> 0 $op_date_limite $recherche_limite $sous_traitant_limite)";
	$op_global=mysqli_query($doc->mysqli, $requete_op_global);
	while($r=mysqli_fetch_array($op_global)) foreach($r as $a => $b) $$a = $b;
}

//afichage du tableau d'en-tête
if(!$doc->noHtml) echo "\n\n<!-- Debut du tableau des en-têtes -->\n";

##Pour les opérations ou les encaissements, on requiert data_client.
if($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements") if(!$doc->noHtml) require("./data_client.php");

##Pour le journal (comptable ou des opérations, il n'y a pas d'en-tête

##Pour le chiffre d'affaires, on traite ci-après.
if($_POST["secteur"]=="tva")
{
	//Définition des variables
	$listeTx = array();
	$tvaDueTotal = 0;
	$listes = preg_split("/;/", $_SESSION["optionGen"]["tx_var_tva"]);
	foreach($listes as $liste)
	{
		list($rf, $tx) = preg_split("#=#", $liste);
		$listeTx[$rf] =  $tx;
	}

	
	//Affichage du tableau du haut de la page avec les colonnes d'en-tête
	if($doc->pdf)
	{
		$doc->pdf->setFont('', '', 20);
		$l = 20;
		$c = 120;
		$r = 0;
		$v = 7;
		$h1 = html_entity_decode("{$doc->lang["operations_resultat"]} {$doc->lang["general_du"]} ". $doc->split_date("POST", "_debut"). " {$doc->lang["general_au"]} ". $doc->split_date("POST", "_fin"));
		$doc->pdf->cell(0, 20, $h1, 1, 1);
		$doc->pdf->setFont('', '', 10);
		$doc->pdf->cell($l+$c, $v, html_entity_decode($doc->lang["operations_rentrees"]), 1);
		$doc->pdf->cell($r, $v, html_entity_decode($doc->lang["operations_tva_due"]), 1, 1, "R");
		$doc->pdf->setFillColor(192,192,255);
		$doc->pdf->cell(0, $v, html_entity_decode($doc->lang["operations_declare"]), 1, 1, '', True);
	}
	elseif($doc->csv){}
	else
	{
		echo $searchForm;
		echo "<table border=\"4\" width=\"100%\" align=\"center\">";
		echo "\n<tr><td colspan=2>{$doc->lang["operations_rentrees"]}</td><td align = right>{$doc->lang["operations_tva_due"]}</td><td align=right width=100>{$doc->lang["entete_ca_net"]}</td></tr>";
		echo "<tr bgcolor=#c0c0ff><td colspan = 2><i>{$doc->lang["operations_declare"]}</i>&nbsp;:</td><td>&nbsp;</td><td align=right><i>{$doc->lang["operations_rentrees_ly"]}</i></td></tr>";
	}
	//Affichage des lignes contenant la TVA à déclarer
	//initialisation des valeurs
	foreach($gainspartva as $txTVA => $recette)
	{
		$txTva  = (string) $txTVA;
		$soumis = number_format($recette, 2);
		if (array_key_exists($txTva, $listeTx))
		{
			$txFft  =  (float) $listeTx["$txTva"];
		}
		else
		{
			$txFft = "<span class=\"attention\">" . $doc->echoUniqueError("100-058#-#$txTva::059", 1, "mefTextOnly", True) . "</span>";
// 			"<span class=\"attention\">Erreur</span>";
		}
		$tvaDue = is_string($txFft) ? "<span class=\"attention\">0.00</span>":$recette*$txFft/100;
		$tvaDueAffiche = is_string($tvaDue) ? $tvaDue: number_format($tvaDue, 2, ".", "'");
		if(is_numeric($tvaDue)) $tvaDueTotal += $tvaDue;
		if($doc->pdf)
		{
			$doc->pdf->setFontSize(10);
			$doc->pdf->cell($l, $v, $soumis, 1, 0, "R");
			$doc->pdf->cell($c, $v, html_entity_decode("{$doc->lang["operations_payer"]} $txTva % {$doc->lang["operations_payer_a"]} $txFft %"), 1);
			$doc->pdf->cell($r, $v, $tvaDueAffiche, 1, 1, "R");
		}
		elseif($doc->csv){}
		else
		{
			echo "\n<tr><td align=right>$soumis</td><td>{$doc->lang["operations_payer"]} $txTva % {$doc->lang["operations_payer_a"]} $txFft %</td><td align=right>$tvaDueAffiche</td><td>&nbsp;</td></tr>";
		}
	}

	//Dernière ligne de l'année (récapitulatif)
// 	$total_global	= $row["total_recette"];
	$total_global_f	= number_format($total_global, 2, ".", "'");
	$total_net	= $total_global - $tvaDueTotal;
	$total_net_f	= number_format($total_net, 2, ".", "'");
	$tvaDueTotal_f	= number_format($tvaDueTotal, 2, ".", "'");
	if($doc->pdf)
	{
		$doc->pdf->setFont('', 'B');
		$doc->pdf->setFillColor(208);
		$doc->pdf->cell($l, $v, html_entity_decode($total_global_f), 1, 0, "R", True);
		$doc->pdf->cell($c, $v, html_entity_decode($tvaDueTotal_f), 1, 0, '', True);
		$doc->pdf->cell($r, $v, $total_net_f, 1, 1, "R", True);
	}
	elseif($doc->csv){}
	else
	{
		echo "<tr style=\"background-color:#d0d0d0;font-weight:bold\"><td align=right>$total_global_f</td><td colspan=2 align = right>$tvaDueTotal_f</td><td align = right>$total_net_f</td></tr>";
	}
	
	if($doc->pdf)
	{
		$doc->pdf->setFont('', 'I');
		$doc->pdf->cell(0, $v, html_entity_decode($doc->lang["operations_ly"]), 1, 1);
	}
	elseif($doc->csv){}
	else
	{
		echo "<tr><td colspan=4><i>{$doc->lang["operations_ly"]}&nbsp;:</i></td></tr>";
	}

	$total_global_ly	= $total_recette_ly;
	$color = ($total_global_ly > $total_global) ? "#ff0000" : "#00a000";
	$progression = $total_global - $total_global_ly;
	$prog_tx = $total_global != 0 ? number_format($progression/$total_global * 100, 2):0;
	$progression = $progression < 0 ? "-&nbsp;" . number_format(0 - $progression, 2, ".", "'"): "+&nbsp;" . number_format($progression, 2, ".", "'");
	$total_global_ly_f	= number_format($total_global_ly, 2, ".", "'");
	if($doc->pdf)
	{
		$doc->pdf->setFont('', '');
		$doc->pdf->cell($l, $v, $total_global_ly_f, 1);
		if($color == "ff0000"||$color == "#ff0000") $doc->pdf->setTextColor(255, 0, 0);
		else $doc->pdf->setTextColor(0, 160, 0);
		$doc->pdf->cell(0, $v, html_entity_decode("$progression ($prog_tx %)"), 1, 1);
		$doc->pdf->setTextColor(0);
	}
	elseif($doc->csv){}
	else
	{
		echo "<tr><td align=right><i>$total_global_ly_f</i></td><td colspan=3><span style = \"color:$color\">$progression ($prog_tx&nbsp;%)</span></td></tr>";
	}

	if($doc->pdf)
	{
			//$doc->pdf->output();
	}
	elseif($doc->csv){}
	else
	{
		echo "\n</table>";
	}
}
if($_POST["secteur"] == "benefice")
{
	
	//Affichage du tableau du haut de la page avec les colonnes d'en-tête
	if($doc->pdf)
	{
		$doc->pdf->setFont('', '', 20);
		$l = 20;
		$c = 120;
		$r = 0;
		$v = 7;
		$h1 = html_entity_decode("{$doc->lang["operations_resultat"]} {$doc->lang["general_du"]} ". $doc->split_date("POST", "_debut"). " {$doc->lang["general_au"]} ". $doc->split_date("POST", "_fin"));
		$doc->pdf->cell(0, 20, $h1, 1, 1);
		$doc->pdf->setFont('', '', 10);
		$doc->pdf->cell($l+$c, $v, html_entity_decode($doc->lang["operations_rentrees"]), 1);
		$doc->pdf->cell($r, $v, html_entity_decode($doc->lang["operations_travaux"]), 1, 1, "R");
		$doc->pdf->setFillColor(192,192,255);
		$doc->pdf->cell(0, $v, html_entity_decode($doc->lang["operations_total"]), 1, 1, '', True);
	}
	elseif($doc->csv){}
	else
	{
		echo $searchForm;
		echo "<table border=\"4\" width=\"100%\" align=\"center\">";
		echo "\n<tr><td>{$doc->lang["config_templates_factures"]}</td><td align = right>+ {$doc->lang["operations_travaux"]}</td><td align=right>{$doc->lang["operations_total"]}</td><td align = right>./. {$doc->lang["operations_travaux"]} ({$doc->lang["operations_ly"]})</td><td align=right>{$doc->lang["operations_total"]}</td></tr>";
	}
	//Affichage des lignes contenant la TVA à déclarer
	$total_facture_f	= number_format($total_facture, 2, ".", "'");
	$total_travaux		= $total_facture * 0.125;
	#$total_travaux		= $total_recette * 0.125;
	$total_travaux_f	= number_format($total_travaux, 2, ".", "'");
	$total_inter		= $total_facture + $total_travaux;
	$total_inter_f		= number_format($total_inter, 2, ".", "'");
	$total_travaux_ly	= $total_facture_ly * 0.125;
	#$total_travaux_ly	= $total_recette_ly * 0.125;
	$total_travaux_ly_f	= number_format($total_travaux_ly, 2, ".", "'");
	$total_net		= $total_inter - $total_travaux_ly;
	$total_net_f		= number_format($total_net, 2, ".", "'");
	if($doc->pdf)
	{
		$doc->pdf->setFont('', 'B');
		$doc->pdf->setFillColor(208);
		$doc->pdf->cell($l, $v, html_entity_decode($total_facture_f), 1, 0, "R", True);
		$doc->pdf->cell($c, $v, html_entity_decode($total_travaux_f), 1, 0, '', True);
		$doc->pdf->cell($c, $v, html_entity_decode($total_inter_f), 1, 0, '', True);
		$doc->pdf->cell($c, $v, html_entity_decode($total_travaux_ly_f), 1, 0, '', True);
		$doc->pdf->cell($r, $v, $total_net_f, 1, 1, "R", True);
	}
	elseif($doc->csv){}
	else
	{
		echo "<tr style=\"background-color:#d0d0d0;font-weight:bold\"><td align=right>$total_facture_f</td><td align = right>$total_travaux_f</td><td align = right>$total_inter_f</td><td align = right>$total_travaux_ly_f</td><td align = right>$total_net_f</td></tr>";
	}
	
	if($doc->pdf)
	{
		$doc->pdf->setFont('', 'I');
		$doc->pdf->cell(0, $v, html_entity_decode($doc->lang["operations_ly"]), 1, 1);
	}
	elseif($doc->csv){}
	else
	{
		echo "<tr><td colspan=5><i>{$doc->lang["operations_ly"]}&nbsp;:</i></td></tr>";
	}

	$total_facture_ly_f	= number_format($total_facture_ly, 2, ".", "'");
	$total_inter_ly		= $total_facture_ly + $total_travaux_ly;
	$total_inter_ly_f	= number_format($total_inter_ly, 2, ".", "'");
	$total_travaux_lpy	= $total_facture_lpy * 0.125;
	#$total_travaux_lpy	= $total_recette_lpy * 0.125;
	$total_travaux_lpy_f	= number_format($total_travaux_lpy, 2, ".", "'");
	$total_net_ly		= $total_inter_ly - $total_travaux_lpy;
	$total_net_ly_f		= number_format($total_net_ly, 2, ".", "'");
	
	$color = ($total_net_ly > $total_net) ? "#ff0000" : "#00a000";
	$progression = $total_facture - $total_facture_ly;
	$prog_tx = $total_facture != 0 ? number_format($progression/$total_facture * 100, 2):0;
	$progression = $progression < 0 ? "-&nbsp;" . number_format(0 - $progression, 2, ".", "'"): "+&nbsp;" . number_format($progression, 2, ".", "'");
	if($doc->pdf)
	{
		$doc->pdf->setFont('', '');
		$doc->pdf->cell($l, $v, $total_facture_ly_f, 1);
		if($color == "ff0000"||$color == "#ff0000") $doc->pdf->setTextColor(255, 0, 0);
		else $doc->pdf->setTextColor(0, 160, 0);
		$doc->pdf->cell(0, $v, html_entity_decode("$progression ($prog_tx %)"), 1, 1);
		$doc->pdf->setTextColor(0);
	}
	elseif($doc->csv){}
	else
	{
		echo "<tr><td align=right><i>$total_facture_ly_f</i></td><td align = right><i>$total_travaux_ly_f</i></td><td align = right><i>$total_inter_ly_f</i></td><td align = right><i>$total_travaux_lpy_f</i></td><td align = right><i>$total_net_ly_f</i></td></tr>";
		echo "<tr><td colspan=5 align=right><span style = \"color:$color\">$progression ($prog_tx&nbsp;%)</span></td></tr>";
	}

	if($doc->pdf)
	{
			//$doc->pdf->output();
	}
	elseif($doc->csv){}
	else
	{
		echo "\n</table>";
	}
}
if(!$doc->noHtml) echo "\n<!-- Fin du tableau des en-têtes -->";

if($doc->pdaSet)
{
	$breakAfter = 4;
	$condiBreak = "<br>";
	$breakCol = "rowspan=2";
}
else
{
	$breakAfter = -1;
	$condiBreak = "";
	$breakCol = "";
}

if(($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements") AND !$doc->print)
{ //affichage des lignes pour les autres pages du dossier (pour operations et encaissements
	echo "\n\n<!-- Debut du formulaire pour l'affichage des ops (annexe à la facture) -->";
	echo "\n<form method=\"post\" action=\"./afficher_operations.php\" target=\"_new\">";
	echo $doc->input_hidden("nodossier", 1);
	echo $doc->input_hidden("sous_traitant_limite", 1);
	echo "\n{$doc->lang["operations_afficher_liste"]}&nbsp;{$doc->lang["operations_operations"]}<input type=\"checkbox\" name=operations>&nbsp;{$doc->lang["operations_encaissements"]}<input type=\"checkbox\" name=encaissements>&nbsp;{$doc->lang["operations_afficher_avec"]}&nbsp;$condiBreak";
	echo "{$doc->lang["operations_entete"]}<input type=\"checkbox\" name=entete>&nbsp;";
	echo "{$doc->lang["operations_tempsop"]}<input type=\"checkbox\" name=temps>&nbsp;";
	echo "{$doc->lang["operations_resume"]}<input type=\"checkbox\" name=resume>&nbsp;";
	echo "{$doc->lang["operations_opavec"]}<input type=\"checkbox\" name=details>";
	echo "{$doc->lang["operations_soustraitant"]}<input type=\"checkbox\" name=affsoustrait>";
// 	echo $doc->input_hidden("timestamp_debut", False, $_POST["timestamp_debut"]);
// 	echo $doc->input_hidden("timestamp_fin", False, $_POST["timestamp_fin"]);
	echo $doc->input_hidden("sous_traitant_limite", False, $sous_traitant_limite);
	echo $doc->input_hidden("recherche_limite", False, $recherche_limite);
	echo $doc->input_hidden("plage_limite", False, $plage_limite);
	if($_POST["secteur"]=="operations") echo $doc->input_hidden("op_date_limite", False, $op_date_limite);
	else echo $doc->input_hidden("op_date_limite", False, preg_replace("/dateop/", "dateac", $op_date_limite));
	echo "&nbsp;";
	echo $doc->button($doc->lang["operations_afficher"], $doc->lang["operations_afficher_accesskey"]);
	echo "</form>";
	//$doc->tab_affiche(4);
}

if(! $doc->noHtml)
{
	echo "\n<!-- Fin du formulaire pour l'affichage des ops (annexe à la facture) -->";
	//affichage des en-têtes du tableau
	echo "\n<br>";
}


/////////////////////////////////////
//
//
//début des opérations individuelles
//
//
////////////////////////////////////


if(!$doc->testval("ecrire") && $doc->testval("lire")) $doc->print = true;

if(! $doc->noHtml)
{
	echo "\n\n<!-- Debut des operations individuelles -->";
	echo $doc->table_open("name='wrapoperations' id='wrapoperations' width=\"100%\" align=\"center\" border=\"0\"");
}
//en-têtes du tableau des opérations individuelles
//les colonnes sont les suivantes

//valeurs: nomColonne, taillePdf, tailleInput, type(d[ecimal]|l[iste]), elementsArray, garderAvecSuivant)

if($_POST["secteur"]=="operations" || $_POST["secteur"]=="journal_op")
{
	$nomCols = array(
		//nom, largeur pdf, largeur html, type (ci-dessous), contenu du menu déroulant si applicable, don't break after
		//d ->entrée numérique
		//t ->entrée de temps
		//l ->Liste déroulante
		//b ->Booleen
		"dateop" => array($doc->lang["operations_dateop"], 30, 0, "", "", 1), //Date
		"$optype" => array($doc->lang["operations_op"], 30, 0, "l", $_SESSION["optionGen"]["op_type"], 1),  //Libellé
		"opavec" => array($doc->lang["operations_opavec"], 20, 12, "", ""), //Détails
		"tempsop" => array($doc->lang["operations_tempsop"], 20, 0, "t", "", 1), //Temps
		"forfait" => array($doc->lang["operations_forfait"], 20, 4, "d", "", 1), //Forfait
// 		"facturable" => array($doc->lang["operations_forfait"], 20, 4, "d", "", 1), //Forfait
		"soustraitant" => array($doc->lang["operations_soustraitant"], 30, "", "l"), //Sous-traitant
		"enattente" => array("?", 10, 3, "b"), //En attente ou non
		"actions" => array("<input type=checkbox id='cycleCheckBox' onclick=cycleCheckAll()>", 0),
		//"actions" => array("", 0),
		);
}
else
{
	$nomCols = array(
		"dateac" => array($doc->lang["operations_dateac"], 30, 0, "", "", 1), //Date
		"ac" => array($doc->lang["operations_ac"], 30, 0, "", "", 1), //Libellé
		"$optype" => array($doc->lang["operations_acpar"], 20, 0, "l", $_SESSION["optionGen"]["ac_type"]), //Compte
		"encaissement" => array($doc->lang["data_client_honoraires"], 20, 4, "d", "", 1), //Honoraires
		"provision" => array($doc->lang["operations_provision"], 20, 4, "d", "", 1), //Provision
		"avfrais" => array($doc->lang["operations_avfrais"], 20, 4, "d"), //Avance de frais
		"demande" => array($doc->lang["operations_demande"], 20, 4, "d", "", 1), //Demande
		"facture" => array($doc->lang["operations_facture"], 20, 4, "d", "", 2), //Facture
		"facturepayee" => array("", 0, 0, "", "", 1), //facturepayee
		"transit" => array($doc->lang["operations_transit"], 20, 4, "d"), //Transit
		"depens" => array($doc->lang["operations_depens"], 20, 4, "d", "", 1), //Dépens
		"facturesanstemps" => array($doc->lang["operations_facturesanstemps"], 20, 4, "d"), //Remise/autre
		"enattente" => array("?", 10, 3, "b"), //En attente ou non
		"actions" => array("<input type=checkbox onclick=cycleCheckAll()>", 0),
	);
}
if($_POST["secteur"]!="operations" && $_POST["secteur"]!="encaissements") $nomCols = array("nodossier" => array($doc->lang["operations_nodossier"], 20, 2)) + $nomCols;
if(!$doc->pdaSet) foreach($nomCols as $k => $datas) $nomCols[$k][5] = "";
$colspan = count($nomCols) + 1;
if($doc->print) $colspan-=2;
$colValues = array();



//Affichage des colonnes

if($doc->pdf)
{
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva" || $_POST["secteur"]=="benefice")
	{
		$doc->pdf->setFont('', 'B');
		$doc->pdf->ln();
		
		foreach($nomCols as $k => $datas)
		{
			$datas[0] = $ncol;
			$datas[1] = $lcol;
			$x = $doc->pdf->getX();
			$y = $doc->pdf->getY();
			$nCol = preg_replace('/\<br(\s*)?\/?\>/i', "\n", html_entity_decode($nCol));
			$doc->pdf->multicell($lCol, 5, $nCol);
			$doc->pdf->setXY($x + $lCol, $y);
		}
		$doc->pdf->ln(20);
		$doc->pdf->setFont('', '', 9);
	}
}
elseif($doc->csv)
{
	foreach($nomCols as $k => $datas)
	{
		if($k == "facturepayee" || $k == "actions") continue;
		echo $doc->toCsv($datas[0], True, True, "ENTETE");
	}
}
else
{
	$isBroken = False;
	$break = False;
	echo "\n<tr><th colspan=$colspan>&nbsp;</th></tr>"; //Ligne vide supplémentaire
	echo "\n\n<!-- Nom de colonnes -->";
	$affCols = "\n<tr style='font-size:0.8em'>";
	$breakN = 1;
	foreach($nomCols as $k => $datas)
	{
		$col = $datas[0];
		$break = $datas[5];
		$colStyle  = ($col == $nomCols[0] && $_POST["secteur"]!="operations" && $_POST["secteur"]!="encaissements") ? "style='text-align:left'":$k == "actions" ? "width=10":"";
		if(! $isBroken) $affCols .= "<th $colStyle>";
		elseif($isBroken === 1) $affCols .= "<br>";
		$isBroken = False;
		$affCols .= $col;
		if(!$break) $affCols .= "</th>";
		else $isBroken = $datas[5];
	}
	$affCols .= "</tr>";
	echo $affCols;
}


//Affichage des opérations, ligne par ligne

//Requêtes MySQL
if(!$doc->print) $limite_nombre="limit $debut_mysql,{$_SESSION["nb_affiche"]}";
else $limite_nombre="";
$nomComplet = "concat(substr(d.prenom, 1, 1), if(d.prenom <> '', '. ', ''), d.nom) as nomcomplet";
$i=0;
$ErreurFiche = "if(o.dateop = '0000-00-00' AND o.dateac = '0000-00-00', 1, 0) as erreur_fiche";
$nomComplet .= ", $ErreurFiche";
if($_POST["secteur"]=="encaissements"||$_POST["secteur"]=="journal"||$_POST["secteur"]=="tva"||$_POST["secteur"]=="benefice")
{
	if($_POST["secteur"]=="encaissements") $clauseDossier = "o.nodossier like '{$_POST["nodossier"]}' and (o.dateac <> 0 OR (o.dateop = '0000-00-00' AND o.dateac = '0000-00-00'))";
	elseif($_POST["secteur"]=="journal"||$_POST["secteur"]=="tva") $clauseDossier = "(o.dateac <> 0 OR (o.dateop = '0000-00-00' AND o.dateac = '0000-00-00'))";
	elseif($_POST["secteur"]=="benefice") $clauseDossier = "(o.facture <> 0 OR o.demande <> 0)";
	
	$query_op="select $nomComplet, o.np, o.nple, o.mp, o.mple, o.nodossier, idop, dateac, date_format(dateac, \"%d\") as date_jour, date_format(dateac, \"%c\") as date_mois, date_format(dateac, \"%Y\") as date_annee, ac, acpar, encaissement, provision, avfrais, demande, facture, facturepayee, transit, depens, facturesanstemps, enattente from {$_SESSION["session_opdb"]}  o LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on a.nodossier = o.nodossier LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on (o.soustraitant = t.soustraitant AND o.nodossier = t.nodossier) LEFT OUTER JOIN adresses d on a.noadresse = d.id where ($clauseDossier $ac_date_limite $recherche_limite $sous_traitant_limite) order by dateac, nodossier, idop $limite_nombre";
	
}

if($_POST["secteur"]=="operations"||$_POST["secteur"]=="journal_op")
{
	$clauseDossier = $_POST["secteur"]=="operations" ? "o.nodossier like '{$_POST["nodossier"]}' and  (o.dateop <> 0 OR (o.dateop = '0000-00-00' AND o.dateac = '0000-00-00'))":"(o.dateop <> 0 OR (o.dateop = '0000-00-00' AND o.dateac = '0000-00-00'))";
	
	$query_op="select $nomComplet, o.np, o.nple, o.mp, o.mple, o.nodossier, idop, forfait, dateop, date_format(dateop, \"%d\") as date_jour, date_format(dateop, \"%c\") as date_mois, date_format(dateop, \"%Y\") as date_annee, op, opavec, tempsop, time_format(tempsop, \"%k\") as temps_heure, time_format(tempsop, \"%i\") as temps_minute, o.soustraitant, a.prixhoraire as prixforfait, t.prixhoraire as prixspec, IF(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) as prixhoraire, if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)/3600 as facturable, enattente from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on a.nodossier = o.nodossier LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on (o.soustraitant = t.soustraitant AND o.nodossier = t.nodossier) LEFT OUTER JOIN adresses d on a.noadresse = d.id  where ($clauseDossier $op_date_limite $recherche_limite $sous_traitant_limite) order by dateop, nodossier, idop $limite_nombre";
}


$resultat_op=mysqli_query($doc->mysqli, "$query_op");
if(! $resultat_op)
{
	echo "<br>'".$resultat."'";
	echo "<br>$query_op";
	echo "<br>".mysqli_error($doc->mysqli);
}


//Formulaire avant toutes les opérations
if(!$doc->print) echo "\n<form method=\"post\" name=\"maj\" action=\"maj_op.php\" $infobulle>";

if($doc->pdf) $initY = $doc->pdf->getY();


//création d'un identifiant unique par ligne
$identifiant=0;

//Pour journal_op: facturable
$totalFacturable = 0;


//Affichage proprement dit
$tab = array();
while($row=mysqli_fetch_array($resultat_op)) $tab[] = $row;
if(empty($tab)) $tab[] = array("special" => "on");
foreach($tab as $row)
{
	//initialisation des valeurs de la ligne
	foreach($nomCols as $k => $datas) $colValues["$k"] = array("v" => "", "a" => "");
	
	//Début de la ligne et infobulles
	$identifiant++;
	$csvId = $identifiant -1;
	$change_select="selectBox(\"$identifiant\", \"norequete-multireq-{$row["idop"]}\")";
	$texte_select="onChange='$change_select'";
	$infobulle=$doc->qui_fait_quoi("{$row["np"]}", "{$row["nple"]}", "{$row["mp"]}", "{$row["mple"]}", "$date_format");
	if($row["facturesanstemps"]!="0.00" && ($_POST["secteur"] == "journal" || $_POST["secteur"] == "encaissements")) $attention="class=attention_bg";
	elseif($row["erreur_fiche"] == "1") $attention="class=attention_bg";
	elseif($row["enattente"] == "1") $attention="class=enattente_bg";
	elseif($_POST["lastid"] && $_POST["lastid"] == $row["idop"]) $attention= "class=inserted_bg";
	elseif($identifiant % 2 == 0) $attention = "class=lignejour3";
	else $attention="class=lignejour1";

	//Nom du client
	$initCl = $row["nomcomplet"];
	if(mb_strlen($initCl) >12)
	{
// 		$specOver = $doc->infobulle($initCl, 0);
		$initCl = mb_substr($initCl, 0, 12)."...";
	}
	if(preg_match("#^(operations|encaissements)$#", $_POST["secteur"])) $doc->nomClient = $initCl;
// 	else $specOver = "";

	//Pour journal_op: facturable
	$totalFacturable += $row["facturable"];
	$affFacturable = number_format($row["facturable"], 2, ".", "'");
	$affFacturable = number_format($facturable, 2, ".", "'");


	if($_POST["secteur"]=="journal" || $_POST["secteur"]=="tva" || $_POST["secteur"]=="benefice") $cible = "encaissements";
	elseif($_POST["secteur"]=="journal_op") $cible = "operations";
	else $cible = "";
// 	if($doc->csv) $colValues["nodossier"]["v"] .= $doc->toCsv($row["nodossier"], True, True, $csvId);
	if($doc->csv) $colValues["nodossier"]["v"] .= "";
	elseif(!$doc->print && $_POST["secteur"]!="operations" && $_POST["secteur"]!="encaissements")
	{
		$colValues["nodossier"]["v"] .= "{$row["nodossier"]} <br><span style=font-size:0.7em $specOver>$initCl</span>";
		$colValues["nodossier"]["a"] .= "onClick='changeDossier(\"{$row["nodossier"]}\", \"$cible\")' style=\"cursor:pointer\"";
	}
	elseif(!$doc->pdf && $_POST["secteur"]!="operations" && $_POST["secteur"]!="encaissements") $colValues["nodossier"]["v"] .= "{$row["nodossier"]}";
	
	//Données génériques
	$genDatas = "";
	$genDatas .= $doc->input_hidden("retour", "", "operations");
	$genDatas .= $doc->input_hidden("debut", True);
	$genDatas .= $doc->input_hidden("secteur", True);
	
	//Données pour la ligne
	$lineDatas = $genDatas;
	if($_POST["lastid"] && $_POST["lastid"] == $row["idop"]) $lineDatas .= "<a name='lastid'></a>";
	$lineDatas .= $doc->input_hidden("nodossier-multireq-{$row["idop"]}", "", "{$row["nodossier"]}");
	$lineDatas .= $doc->input_hidden("idop-multireq-{$row["idop"]}", "", "{$row["idop"]}");

	//Données pour les recherches et les ajouts
	if($identifiant == 1)
	{
		//recherches
		$searchDatas = $genDatas;
		if($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements") $searchDatas .= $doc->input_hidden("nodossier", True);
		$searchDatas .= $doc->input_hidden("recherche", "", "on");

		//ajouts
		$addDatas = $genDatas;
		if($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements") $addDatas .= $doc->input_hidden("nodossier", True);
		$addDatas .= $doc->input_hidden("action", "", "insert");
		//$addDatas .= "<a href=\"#\" onFocus=\"javascript:console.log(document.activeElement.id + ' has focus');aop=document.getElementById('add-op');dj=document.getElementById('date_jour');if(document.activeElement === dj){aop.focus()}else{dj.focus()}\" accesskey=§></a>";
		//$addDatas .= "<a href=\"javascript:document.getElementById('date_jour').focus()\" accesskey=§></a>";
		$addDatas .= "<a href=\"javascript:newFocus()\" accesskey=§></a>";
	}
	
	foreach($nomCols as $k => $datas)
	{
		//Initialisation des cases d'ajout (qui seront le plus souvent les mêmes que celles de recherche)
		if($identifiant == 1)
		{
			$nomCols[$k]["search"] = "";
			if($nomCols[$k][5]) $colspan --;
		}
		
		//Colonnes de date
		if(preg_match("#^date#", $k))
		{
			if(!$doc->print) $colValues["$k"]["v"] .= $doc->split_date($row["$k"], "date_##-multireq-{$row["idop"]}", "", "", "", $change_select);
			elseif($doc->csv) $colValues["$k"]["v"] .= $doc->toCsv($doc->mysql_to_print($row["$k"]), True, True, $csvId);
			elseif(!$doc->pdf) $colValues["$k"]["v"] .= $doc->mysql_to_print($row["$k"]);
			if($identifiant == 1)
			{
				$nomCols[$k]["search"] = $doc->split_date("POST", "daterechdebut") . "<br>" .$doc->split_date("POST", "daterechfin");
				$nomCols[$k]["add"] = $doc->split_date("NOW", "date_##", "", "", "", "", "onMouseover='show(\"Accesskey: ".$doc->getAccessSchema2("§")."\")' onMouseout='hide()'");
			}
		}
		//facturepayee
		elseif($k == "facturepayee")
		{
			if($row["facture"])
			{
				$nImFacture = ($row["facturepayee"]==0)? "false.png": "true.png";
				$imClick = "onclick='changeState({$row["idop"]}, \"facturepayee\", {$row["facturepayee"]})'";
				$imFacture = "<img src='images/$nImFacture'>";
				$imFactureClick = "<img $imClick src='images/$nImFacture'>";
			}
			if($doc->print && ! $doc->noHtml) $colValues[$k]["v"] .= $imFacture;
			elseif(!$doc->print) $colValues[$k]["v"] .= $imFactureClick;
			if($identifiant == 1) $nomCols["$k"]["v"] .= "&nbsp;";
		}
		//Colonnes numériques
		elseif($datas[3] == "d")
		{
			if($row["$k"]==0) $row["$k"]="";
			$imFacture = "";
			$imFactureClick = "";
			if($doc->csv) $colValues["$k"]["v"] .= $doc->toCsv($row["$k"], True, True, $csvId, True);
			elseif($doc->pdf) $colValues["$k"]["v"] .= "";
			elseif($doc->print) $colValues["$k"]["v"] .= $row["$k"].$imFacture;
			else $colValues["$k"]["v"] .= $doc->input_texte("$k-multireq-{$row["idop"]}", "", $row["$k"], $datas[2], False, $texte_select, "text-align:right").$imFactureClick;
			if($identifiant == 1) $nomCols[$k]["search"] = $doc->input_texte($k, "", "", $datas[2]);
		}
		//Colonnes booléennes
		elseif($datas[3] == "b")
		{
			$onClick="console.log(this.checked);c=document.getElementById(\"$k-multireq-{$row["idop"]}-control\");if(this.checked) c.value=\"\";else c.value=\"0\"";
			if($row["$k"]==0) $row["$k"]="";
			if($doc->csv) $colValues["$k"]["v"] .= $doc->toCsv($row["$k"], True, True, $csvId, True);
			elseif($doc->pdf) $colValues["$k"]["v"] .= "";
			elseif($doc->print) $colValues["$k"]["v"] .= $row["$k"].$imFacture;
			else $colValues["$k"]["v"] .= $doc->input_hidden("$k-multireq-{$row["idop"]}", "", "", "id='$k-multireq-{$row["idop"]}-control'") . $doc->input_checkbox("$k-multireq-{$row["idop"]}", "", $row["$k"], "", "onChange='$change_select;$onClick'");
// 			if($identifiant == 1) $nomCols[$k]["search"] = $doc->input_texte($k, "", "", $datas[2]);
			if($identifiant == 1) $nomCols[$k]["search"] = $doc->input_checkbox("$k", "");
		}
		//Autres colonnes, identiques si csv ou print, variées autrement
		else
		{
			if($doc->csv) $colValues["$k"]["v"] .= $doc->toCsv($row["$k"], True, True, $csvId);
			elseif($doc->print) $colValues["$k"]["v"] .= $row["$k"];
			else
			{
				//Colonnes de liste déroulante
				if($datas[3] == "l")
				{
					$ar = is_string($datas[4]) && $datas["4"] != ""?explode("\n", $datas[4]):$datas[4];
					$colValues["$k"]["v"] .= "<select $texte_select name=$k-multireq-{$row["idop"]}>";
					$colValues["$k"]["v"] .= $doc->simple_selecteur($ar, $row["$k"], 0, False, True);
					$colValues["$k"]["v"] .= "</select>";
					if($k == $optype )
					{
						$noteClick = $idnote ? "openNote('$idnote', false, false)": "openNote('$idnote', false, true, $nodossier, {$row["idop"]})";
						$noteAdd = "<img width=16 src=images/note.png onclick=\"$noteClick\">";
						$colValues["$k"]["v"] .= "$noteAdd";
					}
					if($k == "soustraitant" && !$doc->csv && !$doc->pdf && !$doc->pdaSet) $colValues[$k]["v"] .= " ({$row["prixhoraire"]}/{$affFacturable})";
					if($identifiant == 1)
					{
						$nomCols[$k]["search"] = "<select name=$k id=search-$k>";
						$nomCols[$k]["search"] .= $doc->simple_selecteur($ar, "", 0, False, True);
						$nomCols[$k]["search"] .= "</select>";
						$nomCols[$k]["add"] = "<select name=$k id=add-$k>";
						$nomCols[$k]["add"] .= $doc->simple_selecteur($ar, "", 0, False, True);
						$nomCols[$k]["add"] .= "</select>";
						if($k == "soustraitant" && $_SESSION["user"] == $_SESSION["optionGen"]["nom"])
						{
							$ssV = $_SESSION["username"];
							$nomCols[$k]["add"] = "<select name=$k id=add-$k>";
							$nomCols[$k]["add"] .= $doc->simple_selecteur($ar, $ssV, 0, False, True);
							$nomCols[$k]["add"] .= "</select>";
				//$doc->tab_affiche(2);
						}
					}
				}
				//Colonnes de temps
				elseif($datas[3] == "t")
				{
					$colValues["$k"]["v"] .= $doc->split_time($row["$k"], "temps_##-multireq-{$row["idop"]}", "", "", $change_select);
					if($identifiant == 1) $nomCols[$k]["search"] = $doc->split_time("", "temps_##");
				}
				//Colonnes de texte, soit toutes les autres, sauf la colonne "actions"
				elseif($k == "actions")
				{
					$colValues["$k"]["v"] .= $doc->input_checkbox("norequete-multireq-{$row["idop"]}", "", "", "", "onClick='select_color(\"$identifiant\", \"norequete-multireq-{$row["idop"]}\")'");
					if($identifiant == 1)
					{
						$nomCols[$k]["search"] = "<button type=submit><img src='images/search.png'></button>";
						$nomCols[$k]["add"] = "<button type=submit><img src='images/new.png'></button>";
					}
				}
				elseif($k == "nodossier")
				{
					if($identifiant == 1) $nomCols[$k]["search"] = $doc->input_texte($k, "", "", $datas[2]);
				}
				else
				{
					
					$colValues["$k"]["v"] .= $doc->input_texte("$k-multireq-{$row["idop"]}", "", $row["$k"], $datas[2], False, $texte_select);
					if($identifiant == 1) $nomCols[$k]["search"] = $doc->input_texte($k, "", "", $datas[2]);
				}
			}
		}
		if($identifiant == 1 && $nomCols[$k]["add"] == "") $nomCols[$k]["add"] = $nomCols[$k]["search"];
	}

	if($row["special"] == "on") continue; //S'il n'y a pas de ligne, on a tout de même défini les valeurs de recherche et d'ajout.
	
	//Output de la ligne
	$first = True;
	if($doc->csv) echo "\n";
	elseif (! $doc->pdf) echo "\n<tr $infobulle $attention id=\"$identifiant\">";
	foreach($nomCols as $k => $datas)
	{
		if($doc->cvs && ($k == "facturepayee" || $k == "actions")) continue;
		$col = $datas[0];
		$break = $datas[5];
		$tdAjout = $colValues[$k]["a"] ? $colValues[$k]["a"]:"";
		if(!$doc->noHtml && !$isBroken) echo "<td $tdAjout align=center>";
		elseif(!$doc->noHtml && $isBroken === 1) echo "<br>";
		$isBroken = False;
		if($first && !$doc->print) echo $lineDatas;
		echo $colValues[$k]["v"];
		if(!$doc->noHtml && !$break) echo "</td>";
		else $isBroken = $datas[5];
		$first = False;
	}
	if(! $doc->noHtml)echo "</tr>";

}

if($doc->pdf) $doc->pdf->output();

if(!$doc->print)
{
	//Ligne des actions
	echo "<tr><td colspan=$colspan>{$doc->lang["operations_pour_selection"]} :<input type=radio name=\"action\" value=\"update\" checked> {$doc->lang["operations_modifier"]} <input type=radio name=\"action\" value=\"delete\"> {$doc->lang["operations_supprimer"]} $condiBreak<input type=radio name=\"action\" value=\"update_all\"> {$doc->lang["operations_transfert"]} <input type=\"text\" onClick='document.maj.action[2].checked=true' name=\"new_file\" id=\"new_file\" size=\"4\" onKeyUp=\"changeNewFile(this.value)\"> <span id=nouveaudossier>()</span> <img src='{$doc->settings["root"]}images/folder.png' onclick=\"window.open('{$doc->settings["root"]}resultat_recherche.php?standalone=true','rechercheexpress','scrollbars=yes,width=600,height=600,toolbar=no,directories=no,menubar=no,location=no,status=no')\" alt='{$doc->lang["recherche_dossier_recherche"]}'> ", $doc->input_hidden("nodossier", $_POST["nodossier"]), $doc->input_hidden("old_file", "", $_POST["nodossier"]), $doc->button($doc->lang["operations_valider"], ""), "</td></tr>";
	
	//Critères de recherche (en l'état, de date)
	echo $doc->input_hidden("timestamp_debut", 1);
	echo $doc->input_hidden("timestamp_fin", 1);
	
	//Fermeture du formulaire
	echo "\n</form>";
}

if(!$doc->print)
{
	//formulaire pour changer de dossier (caché)
	echo "\n<form action=\"operations.php\" method=\"post\" name=\"changedossier\" target='_new'>";
	echo "\n", $doc->input_hidden("nodossier", "", "2");
	echo "\n", $doc->input_hidden("secteur", "", "encaissements");
	echo "</form>";

	//Ligne de séparation
	echo "\n<tr><td colspan=$colspan><hr></td></tr>\n";

	//Boutons pour rechercher	
	echo $affCols; #Reaffichage de la liste des colonnes
	echo "\n\n<!-- Barre de recherche -->";
	echo "\n<tr>";
	echo "<form method=\"post\" action=\"./operations.php\">";

	$first = True;
	foreach($nomCols as $k => $datas)
	{
		$col = $datas[0];
		$break = $datas[5];
		$tdAjout = $colValues[$k]["a"] ? $colValues[$k]["a"]:"";
		if(!$doc->noHtml && !$isBroken) echo "<td $tdAjout align=center>";
		elseif(!$doc->noHtml && $isBroken === 1) echo "<br>";
		$isBroken = False;
		if($first && !$doc->print) echo $searchDatas;
		echo $nomCols[$k]["search"];
		if(!$doc->noHtml && !$break) echo "</td>";
		else $isBroken = $datas[5];
		$first = False;
	}
	echo "</form>";
	echo "</tr>";

	
	//Ligne de séparation
	echo "\n<tr><td colspan=$colspan><hr></td></tr>\n";
	echo "\n\n<!-- Barre d'ajout -->";

	//Boutons pour ajouter, dernière ligne de chaque page
	echo "\n<tr>";
	echo "<form method=\"post\" action=\"./maj_op.php\">";

	$first = True;
	foreach($nomCols as $k => $datas)
	{
		$col = $datas[0];
		$break = $datas[5];
		$tdAjout = $colValues[$k]["a"] ? $colValues[$k]["a"]:"";
		if(!$doc->noHtml && !$isBroken) echo "<td $tdAjout align=center>";
		elseif(!$doc->noHtml && $isBroken === 1) echo "<br>";
		$isBroken = False;
		if($first && !$doc->print) echo $addDatas;
		echo $nomCols[$k]["add"];
		if(!$doc->noHtml && !$break) echo "</td>";
		else $isBroken = $datas[5];
		$first = False;
	}
	echo "</form>";
	echo "</tr>";
	
// 	echo "\n\n\n\n<tr>";
// 
// // 	//colonne supplémentaire pour aligner sur le bouton d'action
// // 	echo "<td>&nbsp;</td>";
// 	
// 	echo "<td align=center>";
// 		
// 	echo "<input type=text style='width:2em' onfocus=select() id=firstDay name=date_jour value=\"$date_jour\">";
// 	echo "<input type=text style='width:2em' onfocus=select() name=date_mois value=\"$date_mois\">";
// 	echo "<input type=text style='width:4em' onfocus=select() name=date_annee value=\"$date_annee\">";
// 	echo "</td>";
// 	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva" || $_POST["secteur"]=="benefice"){ //cette colonne est avant la liste dans encaissements
// 		echo "<td><input type=text size=20 onfocus=select() name=ac></td>";
// 	}
// 	echo "<td align=center>";
// 	echo "<select name=$optype>";
// 	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva" || $_POST["secteur"]=="benefice") $select=explode("\n", "{$_SESSION["optionGen"]["ac_type"]}");
// 	else $select=explode("\n", "{$_SESSION["optionGen"]["op_type"]}");
// 	$test=0;
// 	if($_POST["secteur"]=="encaissements" && !in_array("", $select)) $select[] = "";
// 	foreach($select as $option){
// 		$selected="";
// 		if(trim($option)=="") $selected="selected";
// 		echo "<option value=\"", trim($option), "\" $selected>$option";
// 	}
// 	echo "</select>";
// 	echo "</td>";
// 	
// 	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva" || $_POST["secteur"]=="benefice"){
// 		echo "<td><input type=text size=6 onfocus=select() name=encaissement></td>";
// 		echo "<td><input type=text size=6 onfocus=select() name=provision></td>";
// 		echo "<td><input type=text size=6 onfocus=select() name=avfrais></td>";
// 		echo "<td><input type=text size=6 onfocus=select() name=demande></td>";
// 		echo "<td><input type=text size=6 onfocus=select() name=facture></td>";
// 		echo "<td><input type=text size=6 onfocus=select() name=transit></td>";
// 		echo "<td><input type=text size=6 onfocus=select() name=depens></td>";
// 		echo "<td><input type=text size=6 onfocus=select() name=facturesanstemps></td>";
// 	} 
// 	else{
// 		echo "<td align=center><input type=text size=20 onfocus=select() name=opavec></td>";
// 		echo "<td align=center><input type=text size=2 onfocus=select() name=temps_heure>:<input type=text size=2 name=temps_minute></td>";
// 		echo "<td align=center><input type=text size=5 onfocus=select() name=\"forfait\"> {$_SESSION["optionGen"]["currency"]}</td>";
// 	echo "<td><select name=soustraitant>";
// 	if($_SESSION["optionGen"]["soustraitants"]) $select=explode("\n", $_SESSION["optionGen"]["soustraitants"]);
// 	else $select=array();
// 	
// 	$select[]=","; //permet d'afficher une ligne blanche
// 	$test=0;
// 	foreach($select as $line){
// 	list($option) = preg_split("#,#", $line);
// 	$selected="";
// 	if(trim($row["soustraitant"])==trim($option))
// 	{
// 		$selected="selected";
// 		$test=1;
// 	}
// 		echo "<option value=\"", trim($option), "\" $selected>$option";
// 	}
// 	if($test==0) echo "<option value=\"", trim($row["soustraitant"]), "\" selected>", trim($row["soustraitant"]);
// 	echo "</select></td>";
// 
// 
// 	}
// 	echo "<td align=right>";
// 	//echo "<button type=submit>{$doc->lang["operations_nouveau"]}</button></td></form></tr>";
// 	echo "<button type=submit><img src='images/new.png'></button></td></form></tr>";
	
	//Affichage des résultats du dossier (calcul des honoraires théoriques)
	if($_POST["secteur"]=="operations" || $_POST["secteur"]=="journal_op")
	{
		$colspan=$colspan-4;
		echo "<tr><td colspan=$colspan>{$doc->lang["operations_total"]} :</td><td colspan=2 align=right><b>$totaltemps {$doc->lang["general_soit"]} $gainactuel {$_SESSION["optionGen"]["currency"]}</b></td></tr>";
	}
	echo $doc->table_close("wrapoperations");
	$doc->footer();
	
	if($_POST["secteur"]=="tva" || $_POST["secteur"]=="benefice")
	{
		echo "<p align=center>";
		echo $doc->form("operations.php", $doc->lang["operations_tva_pdf"], "", "", "", "timestamp_debut", "{$_POST["timestamp_debut"]}", "timestamp_fin", "{$_POST["timestamp_fin"]}", "secteur", "{$_POST["secteur"]}", "print", "on", "pdf", "on");
		echo "</p>";
	}
}
elseif($_POST["secteur"]=="operations")
{
	$colspan=$colspan-2;
	echo "<tr><td colspan=$colspan>{$doc->lang["operations_total"]} :</td><td colspan=2 align=right><b>$totaltemps {$doc->lang["general_soit"]} $gainactuel {$_SESSION["optionGen"]["currency"]}</b></td></tr>";
}
if(! $doc->noHtml) echo $doc->table_close("wrapoperations");

$doc->close();
?>
