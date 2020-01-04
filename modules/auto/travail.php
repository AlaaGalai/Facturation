<?php

//Calcul si des données ont été introduites
if($_POST["calculs"])
{
	$jour        = $_POST["jourdateLicenciement"];
	$mois        = $_POST["moisdateLicenciement"];
	$annee       = $_POST["anneedateLicenciement"];

	//calcul du délai de congé
	if($_POST["nombreterme"] == "jour") $jour += $_POST["delaiConge"];
	if($_POST["nombreterme"] == "semaine") $jour += $_POST["delaiConge"]*7;
	if($_POST["nombreterme"] == "mois")
	{
		$mois += $_POST["delaiConge"];
	}
	if($_POST["nombreterme"] == "trimestre")
	{
		$mois += $_POST["delaiConge"]*3;
	}
	if($_POST["nombreterme"] == "annee")
	{
		$annee += $_POST["delaiConge"];
	}
	if($_POST["nombreterme"] == "mois" || $_POST["nombreterme"] == "trimestre" || $_POST["nombreterme"] == "annee") //si le quantième n'existe pas dans le mois d'arrivée, cf art. 77 al. 1 ch. 3 CO
	{
		if($doc->univ_strftime("%d", mktime(1,1,1,$mois, $jour, $annee)) != $jour) $jour = $doc->univ_strftime("%d", mktime(1,1,1,$mois + 1, 0, $annee));
	}
	$jourLicenciement = $doc->univ_strftime("%d", mktime(1,1,1,$mois, $jour, $annee));
	$moisLicenciement = $doc->univ_strftime("%m", mktime(1,1,1,$mois, $jour, $annee));
	$anneeLicenciement = $doc->univ_strftime("%Y", mktime(1,1,1,$mois, $jour, $annee));
	
	$dateLicenciement = $doc->univ_strftime("%d.%m.%Y", mktime(1,1,1,$mois, $jour, $annee));
	$mktimeLicenciement = mktime(1,1,1,$mois, $jour, $annee);

	//traitement du terme

	switch ($_POST["terme"])
	{
		case "semaine":
			$jourSemaine = $doc->univ_strftime("%u", $mktimeLicenciement);
			if($jourSemaine != 7)
			{
				$nomJourSemaine = $doc->univ_strftime("%A", $mktimeLicenciement);
				$jourLicenciement += (7 - $jourSemaine);
				$reportTexte = "Le jour du terme est un $nomJourSemaine. Report au dimanche suivant, soit le ";
			}
			else
			{
				$nonReportTexte = "Le jour du terme est un dimanche. Pas de report au prochain terme.";
			}
			break;
		case "mois":
			$jourLicenciement2 = $doc->univ_strftime("%d", mktime(1,1,1,$moisLicenciement + 1, 0, $anneeLicenciement));
			//echo "<br>jourLicenciement: $jourLicenciement. jourLicenciement2: $jourLicenciement2";
			if($jourLicenciement2 != $jourLicenciement)
			{
				$reportTexte = "Le jour du terme ($jourLicenciement) n'est pas le dernier du mois. Report au ";
				$jourLicenciement = $jourLicenciement2;
			}
			else
			{
				$nonReportTexte = "Le jour du terme est le dernier du mois. Pas de report au prochain terme.";
			}
			break;
		case "trimestre":
			if($moisLicenciement < 4)
			{
				$moisLicenciement2 = 3;
				$jourLicenciement2 = 31;
			}
			elseif($moisLicenciement < 7)
			{
				$moisLicenciement2 = 6;
				$jourLicenciement2 = 30;
			}
			elseif($moisLicenciement < 10)
			{
				$moisLicenciement2 = 9;
				$jourLicenciement2 = 30;
			}
			else
			{
				$moisLicenciement2 = 12;
				$jourLicenciement2 = 31;
			}
			if($jourLicenciement2 != $jourLicenciement || $moisLicenciement2 != $moisLicenciement)
			{
				$reportTexte = "Le jour du terme ($jourLicenciement.$moisLicenciement) n'est pas le dernier jour du trimestre. Report au ";
				$jourLicenciement = $jourLicenciement2;
				$moisLicenciement = $moisLicenciement2;
			}
			else
			{
				$nonReportTexte = "Le jour du terme est le dernier du trimestre. Pas de report au prochain terme.";
			}
			break;
		case "annee":
			$jourLicenciement2 = 31;
			$moisLicenciement2 = 12;
			if($jourLicenciement2 != $jourLicenciement || $moisLicenciement2 != $moisLicenciement)
			{
				$reportTexte = "Le jour du terme ($jourLicenciement.$moisLicenciement) n'est pas le dernier jour de l'année. Report au ";
				$jourLicenciement = $jourLicenciement2;
				$moisLicenciement = $moisLicenciement2;
			}
			else
			{
				$nonReportTexte = "Le jour du terme est le dernier de l'année. Pas de report au prochain terme.";
			}
			break;
		case "net":
			$nonReportTexte = "Pas de report faute de terme.";
			break;
		default:
			echo "Le type de terme ({$_POST["terme"]}) n'est pas géré";
			break;
	}
	$jourbis = "$jour.$mois.$annee";
	
	$dateLicenciementReport = $doc->univ_strftime("%d.%m.%Y", mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciement));

	//calcul du délai de congé retrospectif
	if($_POST["terme"] == "net")
	{
		$jourAjuste  = $_POST["jourdateLicenciement"];
		$moisAjuste  = $_POST["moisdateLicenciement"];
		$anneeAjuste = $_POST["anneedateLicenciement"];
		echo "\n<br>$jourAjuste.$moisAjuste.$anneeAjuste";
	}
	else
	{
		$jourAjuste  = $jourLicenciement;
		$moisAjuste  = $moisLicenciement;
		$anneeAjuste = $anneeLicenciement;
		if($_POST["nombreterme"] == "jour") $jourAjuste -= $_POST["delaiConge"];
		if($_POST["nombreterme"] == "semaine") $jourAjuste -= $_POST["delaiConge"]*7;
		if($_POST["nombreterme"] == "mois")
		{
			$moisAjuste -= $_POST["delaiConge"];
			$tMois=true;
		}
		if($_POST["nombreterme"] == "trimestre")
		{
			$moisAjuste -= $_POST["delaiConge"]*3;
			$tMois=true;
		}
		if($_POST["nombreterme"] == "annee")
		{
			$anneeAjuste -= $_POST["delaiConge"];
			$tMois=true;
		}
		if($tMois == true)
		{
			if($_POST["terme"] == "mois" || $_POST["terme"] == "trimestre" || $_POST["terme"] == "annee")
			{
				//echo "ici";
				$moisAjuste++;
				$jourAjuste = 0;
			}
		//if($_POST["nombreterme"] == "mois" || $_POST["nombreterme"] == "trimestre" || $_POST["nombreterme"] == "annee") //si le quantième n'existe pas dans le mois de départ rétrospectif, cf art. 77 al. 1 ch. 3 CO
			else
			{
				if($doc->univ_strftime("%d", mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste)) != $jourAjuste) $jourAjuste = $doc->univ_strftime("%d", mktime(1,1,1,$moisAjuste+1, 0, $anneeAjuste));
			}
		}
	}
	$jourAjuste += 1; //le dies a quo ne compte pas
	$dateCongeRetrospectif = $doc->univ_strftime("%d.%m.%Y", mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste));
	$nbJours = ($doc->univ_strftime("%s", mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciement)) - $doc->univ_strftime("%s", mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste))) /86400;
	$nbJours = round($nbJours);
	$nbJours +=1; //on ne compte pas le dies a quo
	//echo "seconde départ: ". $doc->univ_strftime("%s", mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciementeecho "; seconde arrivée: ". $doc->univ_strftime("%s", mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste));
}

//Données à introduire


echo "<form action=\"modules.php\" method=\"POST\">";

//a) données contractuelles
echo "\n<h4>{$doc->lang["donneesContrat"]}</h4>";
echo $doc->table_open("border=1");
foreach(array("dateEmbauche", "dateLicenciement" ) as $l)
{
	echo "\n<tr><td>{$doc->lang["$l"]}:</td><td>" .$doc->split_date("POST", "$l");
	$error=(isset($doc->dateErrors["$l"]))? "Attention !":"";
	echo "$error</td></tr>";
}
echo "\n<tr><td>{$doc->lang["delaiConge"]}: </td><td>".$doc->input_texte("delaiConge", 1)." <select name=nombreterme>";
foreach(array("jour", "semaine", "mois", "trimestre", "annee") as $terme)
{
	$selected=$_POST["nombreterme"] == $terme ? "selected": "";
	echo "\n<option $selected value=$terme>{$doc->lang["$terme"]}</option>";
}
echo "\n</select>\n<select name=terme>";
foreach(array("semaine", "mois", "trimestre", "annee", "net") as $terme)
{
	$selected=$_POST["terme"] == $terme ? "selected": "";
	echo "\n<option $selected value=$terme>{$doc->lang["fin_$terme"]}</option>";
}
echo "\n</select></td></tr>";
echo $doc->table_close();

//b) données prolongation
echo "\n<h4>{$doc->lang["donneesProl"]}</h4>";
echo $doc->table_open("border=1");

$nb               = array();
$nbRechutes       = array();

$d                = array();
$d["maladie"]    = array();
$d["grossesse"]  = array();
$d["service"]    = array();
$d["rechute"]    = array();
$empechements    = array();
$nbEmpechements  = 0;

$pTest=$_POST;

//variables automatiques créées ailleurs et qui m'énervent.
unset($_POST["jour_debut"]);
unset($_POST["mois_debut"]);
unset($_POST["annee_debut"]);
unset($_POST["jour_fin"]);
unset($_POST["mois_fin"]);
unset($_POST["annee_fin"]);

// $doc->tab_affiche();
foreach(array("maladie", "grossesse", "service", "rechute") as $typeProl)
{
	foreach($pTest as $nom => $val)
	{
		if(substr($nom, 0, strlen($typeProl) + 6) == "{$typeProl}Active" || substr($nom, 0, strlen($typeProl) + 9) == "{$typeProl}Supprimer")
		{
			$num = substr($nom, strlen($typeProl) + 7);
			$indicatif = $doc->univ_strftime("%Y%m%d", mktime (1, 1, 1, $_POST["mois{$typeProl}Debut_{$num}"], $_POST["jour{$typeProl}Debut_{$num}"], $_POST["annee{$typeProl}Debut_{$num}"]));
			
			if(! $_POST["{$typeProl}Supprimer_{$num}"] && preg_match("#Active#", $nom))
			{
				$d["{$typeProl}"]["$indicatif"]["$num"]["num"] = $num;
				$d["{$typeProl}"]["$indicatif"]["$num"]["jd"] = $_POST["jour{$typeProl}Debut_{$num}"];
				$d["{$typeProl}"]["$indicatif"]["$num"]["md"] = $_POST["mois{$typeProl}Debut_{$num}"];
				$d["{$typeProl}"]["$indicatif"]["$num"]["ad"] = $_POST["annee{$typeProl}Debut_{$num}"];
				$d["{$typeProl}"]["$indicatif"]["$num"]["jf"] = $_POST["jour{$typeProl}Fin_{$num}"];
				$d["{$typeProl}"]["$indicatif"]["$num"]["mf"] = $_POST["mois{$typeProl}Fin_{$num}"];
				$d["{$typeProl}"]["$indicatif"]["$num"]["af"] = $_POST["annee{$typeProl}Fin_{$num}"];
			}
			unset($_POST["jour{$typeProl}Debut_{$num}"]);
			unset($_POST["mois{$typeProl}Debut_{$num}"]);
			unset($_POST["annee{$typeProl}Debut_{$num}"]);
			unset($_POST["jour{$typeProl}Fin_{$num}"]);
			unset($_POST["mois{$typeProl}Fin_{$num}"]);
			unset($_POST["annee{$typeProl}Fin_{$num}"]);
			if (preg_match("#Active#", $nom)) unset($_POST["$nom"]);
		}
	}
	
	//tri des données trouvées
	
	$k = $d["{$typeProl}"];
	ksort($k);
	
/*	if($typeProl == "rechute")
	{
		//réaffectation des rechutes
		$corRechutes = array();
		foreach
	}*/
	$nb["$typeProl"]=0;
	foreach($k as $indicatif => $arrNum)
	{
		foreach($k[$indicatif] as $numBis =>$arVar)
		{
			$num = $nb[$typeProl];

			if(!isset($_POST["{$typeProl}Active_{$num}"]))
			{
				$_POST["{$typeProl}Active_{$num}"] = true;
				if(preg_match("#rechute#", $typeProl))
				{
// 					echo "<br>Traitement de $numBis pour une rechute; nbRechute ($mal) = ".$nbRechutes[$mal];
					list($mal, $nMal) = preg_split("#_#", $numBis);
					if(!isset($nbRechutes[$mal]) || !$nbRechutes[$mal]) $nbRechutes[$mal] = "0";
					$cor = $corRechute[$mal];
					$num = $cor ."_" .$nbRechutes[$mal];
// 					echo ". $nMal e rechute de la maladie $mal: num vaut '$num'";
					$nbRechutes[$cor]++;
				}
				else $cor = $num;
				$_POST["jour{$typeProl}Debut_{$num}"] = $k["$indicatif"]["$numBis"]["jd"] ;
				$_POST["mois{$typeProl}Debut_{$num}"] = $k["$indicatif"]["$numBis"]["md"] ;
				$_POST["annee{$typeProl}Debut_{$num}"] = $k["$indicatif"]["$numBis"]["ad"] ;
				$_POST["jour{$typeProl}Fin_{$num}"] = $k["$indicatif"]["$numBis"]["jf"] ;
				$_POST["mois{$typeProl}Fin_{$num}"] = $k["$indicatif"]["$numBis"]["mf"] ;
				$_POST["annee{$typeProl}Fin_{$num}"] = $k["$indicatif"]["$numBis"]["af"] ;
				if($typeProl == "maladie") $corRechute[$numBis] = $num;
				if($typeProl == "service")
				{
					$nbJoursService = round(mktime(1,1,1,$_POST["mois{$typeProl}Fin_{$num}"], $_POST["jour{$typeProl}Fin_{$num}"], $_POST["annee{$typeProl}Fin_{$num}"])/86400) - round(mktime(1,1,1,$_POST["mois{$typeProl}Debut_{$num}"], $_POST["jour{$typeProl}Debut_{$num}"], $_POST["annee{$typeProl}Debut_{$num}"])/86400);
					if($nbJoursService > 10) //Comme on compte de jour compris à jour compris, la différence est inférieure de 1 au résultat réel. Mais il faut que le service soit strictement supérieur à 11 jours
					{
						$jd = $doc->univ_strftime("%d", mktime(1,1,1,$_POST["mois{$typeProl}Debut_{$num}"], $_POST["jour{$typeProl}Debut_{$num}"] - 28, $_POST["annee{$typeProl}Debut_{$num}"]));
						$md = $doc->univ_strftime("%m", mktime(1,1,1,$_POST["mois{$typeProl}Debut_{$num}"], $_POST["jour{$typeProl}Debut_{$num}"] - 28, $_POST["annee{$typeProl}Debut_{$num}"]));
						$ad = $doc->univ_strftime("%Y", mktime(1,1,1,$_POST["mois{$typeProl}Debut_{$num}"], $_POST["jour{$typeProl}Debut_{$num}"] - 28, $_POST["annee{$typeProl}Debut_{$num}"]));
						$jf = $doc->univ_strftime("%d", mktime(1,1,1,$_POST["mois{$typeProl}Fin_{$num}"], $_POST["jour{$typeProl}Fin_{$num}"] + 28, $_POST["annee{$typeProl}Fin_{$num}"]));
						$mf = $doc->univ_strftime("%m", mktime(1,1,1,$_POST["mois{$typeProl}Fin_{$num}"], $_POST["jour{$typeProl}Fin_{$num}"] + 28, $_POST["annee{$typeProl}Fin_{$num}"]));
						$af = $doc->univ_strftime("%Y", mktime(1,1,1,$_POST["mois{$typeProl}Fin_{$num}"], $_POST["jour{$typeProl}Fin_{$num}"] + 28, $_POST["annee{$typeProl}Fin_{$num}"]));
						$indicatif = $ad.$md.$jd;
						$fin = $af.$mf.$jf;
					}
				}
				
				$nb["$typeProl"] ++;
				$empechements[$indicatif][$nbEmpechements]["type"] = $typeProl;
				$empechements[$indicatif][$nbEmpechements]["fin"] = ($typeProl == "service" && $nbJoursService>10) ? $fin:$doc->univ_strftime("%Y%m%d", mktime (1, 1, 1, $_POST["mois{$typeProl}Fin_{$num}"], $_POST["jour{$typeProl}Fin_{$num}"], $_POST["annee{$typeProl}Fin_{$num}"]));
				$empechements[$indicatif][$nbEmpechements]["groupe"] = $cor;
				if($typeProl == "service") $empechements[$indicatif][$nbEmpechements]["dureeService"] = $nbJoursService;
				$nbEmpechements ++;
			}
		
		}
	}
}

// echo "<br><br>Après<br><br>";
// $doc->tab_affiche();

foreach(array("maladie", "grossesse", "service") as $typeProl)
{
 	if($_POST["ajout"] == $typeProl) $nb["$typeProl"] ++;
 	
 	$texte  = "date".ucfirst($typeProl);
	$texte  = $doc->lang["$texte"];
	$oTexte = $doc->lang["$typeProl"];
	$debut  = $typeProl."Debut_";
	$fin    = $typeProl."Fin_";
 	
 	$ajouteOptions .= "\n<option value = \"$typeProl\">$oTexte</option>";
	
	
	for($x=0;$x<$nb["$typeProl"];$x++)
	{
		$nbDeja["$typeProl"] ++;
		$supp = "{$typeProl}Supprimer_{$x}";
		$ajout = $typeProl == "maladie" ? "<td>{$doc->lang["ajouterRechute"]} <input type=checkbox name=\"ajoutMaladie_{$x}\"></td>":"";
		$suppression = "<td>{$doc->lang["supprimer"]} <input type=checkbox name=\"$supp\"></td>";
		$tErreur = "<span class=attention>&nbsp;Attention !</span>";
		echo "\n<tr><td>$texte:</td><td>" .$doc->split_date("POST", $debut.$x);
		$error=(isset($doc->dateErrors[$debut.$x]))? "$tErreur":"";
		echo "$error</td><td>{$doc->lang["general_au"]}" .$doc->split_date("POST", $fin.$x);
		$error=(isset($doc->dateErrors[$fin.$x]))? "$tErreur":"";
		echo "$error</td>$suppression{$ajout}</tr>";
		echo $doc->input_hidden($typeProl."Active_{$x}", 0, "1");
		if($typeProl == "maladie")
		{
// 		 	$nbRechutes[$x] = $_POST["rechute_{$x}Deja"];
 			if($_POST["ajoutMaladie_{$x}"]) $nbRechutes[$x] ++;
			
			for($y=0;$y<$nbRechutes[$x];$y++)
			{
				$nbRechuteDeja["$x"] ++;
				$suppression = "<td>{$doc->lang["supprimer"]} <input type=checkbox name=\"rechute{_$x}_{$y}Supprimer\"></td>";
				echo "\n<tr><td>&nbsp;-&nbsp;{$doc->lang["dateRechute"]}:</td><td>" .$doc->split_date("POST", "rechuteDebut_{$x}_$y");
				$error=(isset($doc->dateErrors["rechuteDebut_{$x}_$y"]))? "$tErreur":"";
				echo "$error</td><td>{$doc->lang["general_au"]}" .$doc->split_date("POST", "rechuteFin_{$x}_$y");
				$error=(isset($doc->dateErrors["rechuteFin_{$x}_$y"]))? "$tErreur":"";
				echo "$error</td>$suppression</tr>";
				echo $doc->input_hidden("rechuteActive_{$x}_$y", 0, "1");
			}
		}
	}
}

echo "\n<tr><td colspan=4>&nbsp;</td></tr>";
echo "\n<tr><td colspan=4>{$doc->lang["ajouter"]}: <select name=ajout><option value=\"\"></option>$ajouteOptions</select></td></tr>";
echo $doc->input_hidden("moduleName", 1);
echo $doc->input_hidden("calculs", "", 1);

echo "\n<h4>";
echo "\n<tr><td colspan=4>".$doc->button("Calculer")."</td></tr>";
echo $doc->table_close();
echo "\n</form>";

if($_POST["calculs"])
{
	echo "\n<h4>a) Calcul de la date du congé</h4>";
	echo "\nEchéance du congé: <b>$dateLicenciement</b>";
	echo "\n<h4>b) Calcul de la date du congé compte tenu du terme</h4>";
	if($reportTexte)
	{
		echo "\n$reportTexte <b>$dateLicenciementReport</b>";
	}
		else
	{
		echo "\n$nonReportTexte";
	}
	echo "\n<h4>c) Calcul de la date du début de congé calculé rétrospectivement</h4>";
	echo "\nDélai de congé rétrospectif: du <b>$dateCongeRetrospectif</b> (compris) au <b>$dateLicenciementReport</b> (compris), soit $nbJours jours";
	echo "\n<h4>d) Calcul des différentes prolongations</h4>";
	
	$tUnAn  = mktime(1,1,1,$_POST["moisdateEmbauche"],$_POST["jourdateEmbauche"],$_POST["anneedateEmbauche"] + 1);
	$tCinqAn = mktime(1,1,1,$_POST["moisdateEmbauche"],$_POST["jourdateEmbauche"],$_POST["anneedateEmbauche"] + 5);
	$now    = time();
	
	$eCinq = strftime("%d.%m.%Y", $tCinqAn);
//  	echo "<br>Now: $now. Cinq ans: $tCinqAn ($eCinq) {$_POST["moisdateEmbauche"]},{$_POST["jourdateEmbauche"]},{$_POST["anneedateEmbauche"]} + 5\n<br>";
	
	ksort($empechements);
// 	$doc->tab_affiche($empechements);
	foreach($empechements as $debut => $ar)
	{
// 	echo "<br>Now: $now. Cinq ans: $CinqAn {$_POST["moisdateEmbauche"]},{$_POST["jourdateEmbauche"]},{$_POST["anneedateEmbauche"]} + 5";
		$nE++;
		foreach($empechements["$debut"] as $id => $arBis)
		{
			$fin  = $empechements["$debut"][$id]["fin"];
			$grp  = $empechements["$debut"][$id]["groupe"];
			$type = $empechements["$debut"][$id]["type"];
			$nJS  = $empechements["$debut"][$id]["dureeService"];
			$jDebut=substr($debut, 6, 2);
			$mDebut=substr($debut, 4, 2);
			$aDebut=substr($debut, 0, 4);
			$jFin=substr($fin, 6, 2);
			$mFin=substr($fin, 4, 2);
			$aFin=substr($fin, 0, 4);
			
			if($nE >1) echo "\n<br><br>";
			echo "$nE. <i>Empêchement de type \"".strtolower($doc->lang["$type"])."\"</i> du $jDebut.$mDebut.$aDebut au $jFin.$mFin.$aFin.";
			switch($type)
			{
				case ($type == "maladie" || $type == "rechute"):
					$nbJourProt = round(mktime(1,1,1,$mFin, $jFin, $aFin)/86400) - round(mktime(1,1,1,$mDebut, $jDebut, $aDebut)/86400) + 1; //Comme on compte de jour compris à jour compris, la différence est inférieure de 1 au résultat réel.
					$nbJourProt0 = $nbJourProt;
					$noProt=false;
					if(mktime(1,1,1, $mFin, $jFin, $aFin) < mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste)) $noProt=true;
					elseif(mktime(1,1,1, $mDebut, $jDebut, $aDebut) < $tUnAn)
					{
						echo "\n<br>L'empêchement commence durant la première année de service et dure $nbJourProt jours. La durée maximale de l'empêchement est de 30 jours (éventuellement 90 jours en cas d'empêchement à cheval sur la première et la deuxième année). ";
						if($cons["$grp"])
						{
							echo "\n<br>{$cons["$grp"]} jours ont cependant déjà été consommés.";
						}
						$nbJourProt1 = ($nbJourProt > 30 -$cons["$grp"])? 30 -$cons["$grp"]:$nbJourProt;
						$nbJourProt2 = ($nbJourProt > 90 -$cons["$grp"])? 90 -$cons["$grp"]:$nbJourProt;
						$tTest = mktime(1,1,1, $moisLicenciement, $jourLicenciement + $nbJourProt1, $anneeLicenciement);
						$dateTest = $doc->univ_strftime("%d.%m.%Y", $tTest);
						$dateTestUn = $doc->univ_strftime("%d.%m.%Y", $tUnAn);
						$nbJourProt  = ($tTest >= $tUnAn)?$nbJourProt2:$nbJourProt1;
						if(($tTest >= $tUnAn && $cons["$grp"]>179) || $tTest < $tUnAn && $cons["$grp"]>89) $noProt2=true;
						if($tTest >= $tUnAn && !$noProt2) echo "<br>En l'occurence, le délai de congé applicable est celui de la sixième année de service et non celui de la cinquième parce que le délai de congé prolongé en raison de la période de protection de 90 jours (soit au $dateTest <b>sans tenir compte du délai supplémentaire de l'art. 336c al. 3 CO</b>) n'est pas encore échu à la fin de la cinquième année de service ($dateTestUn).";
					}
					elseif(mktime(1,1,1, $mDebut, $jDebut, $aDebut) < $tCinqAn)
					{
						echo "\n<br>L'empêchement commence entre la deuxième et la cinquième année de service et dure $nbJourProt jours. La durée maximale de l'empêchement est de 90 jours (éventuellement 180 jours en cas d'empêchement à cheval sur la cinquième et la sixième année). ";
						if($cons["$grp"])
						{
							echo "\n<br>{$cons["$grp"]} jours ont cependant déjà été consommés.";
						}
						$nbJourProt1 = ($nbJourProt > 90 -$cons["$grp"])? 90 -$cons["$grp"]:$nbJourProt;
						$nbJourProt2 = ($nbJourProt > 180 -$cons["$grp"])? 180 -$cons["$grp"]:$nbJourProt;
						$tTest = mktime(1,1,1, $moisLicenciement, $jourLicenciement + $nbJourProt1, $anneeLicenciement);
						$dateTest = $doc->univ_strftime("%d.%m.%Y", $tTest);
						$dateTestCinq = $doc->univ_strftime("%d.%m.%Y", $tCinqAn);
						$nbJourProt  = ($tTest >= $tCinqAn)?$nbJourProt2:$nbJourProt1;
						if(($tTest >= $tCinqAn && $cons["$grp"]>179) || $tTest < $tCinqAn && $cons["$grp"]>89) $noProt2=true;
						if($tTest >= $tCinqAn && !$noProt2) echo "<br>En l'occurence, le délai de congé applicable est celui de la sixième année de service et non celui de la cinquième parce que le délai de congé prolongé en raison de la période de protection de 90 jours (soit au $dateTest <b>sans tenir compte du délai supplémentaire de l'art. 336c al. 3 CO</b>) n'est pas encore échu à la fin de la cinquième année de service ($dateTestCinq).";
					}
					elseif(mktime(1,1,1, $mDebut, $jDebut, $aDebut) >= $tCinqAn)
					{
						echo "\n<br>L'empêchement est entièrement postérieur à la cinquième année de service. La durée maximale de l'empêchement est de 180 jours.";
						if($cons["$grp"])
						{
							echo "\n<br>{$cons["$grp"]} jours ont cependant déjà été consommés.";
						}
						if($cons["$grp"]>179) $noProt=true;
						else $nbJourProt = ($nbJourProt > 180)? 180:$nbJourProt;
					}
					
					if(($nbJourProt0 != $nbJourProt) && !$noProt2)
					{
						echo " Compte tenu de cette limitation, l'empêchement n'est compté que pour $nbJourProt jours.";
						$jourASoustraire = $nbJourProt;
					}
					else
					{
						$jourASoustraire = $nbJourProt0;
					}
					
					$nbJourProt --; //On a augmenté le nb de jours pour l'affichage; mais pour les calculs de date, il faut de nouveau soustraire 1
					$jFin = $doc->univ_strftime("%d", mktime(1,1,1,substr($debut, 4, 2), substr($debut, 6, 2) +$nbJourProt, substr($debut, 0, 4)));
					$mFin = $doc->univ_strftime("%m", mktime(1,1,1,substr($debut, 4, 2), substr($debut, 6, 2) +$nbJourProt, substr($debut, 0, 4)));
					$aFin = $doc->univ_strftime("%Y", mktime(1,1,1,substr($debut, 4, 2), substr($debut, 6, 2) +$nbJourProt, substr($debut, 0, 4)));
					
					$cons["$grp"] += $jourASoustraire;
					
					break;
					
				case "grossesse":
					$jFin = $doc->univ_strftime("%d", mktime(1,1,1,substr($fin, 4, 2), substr($fin, 6, 2) +112, substr($fin, 0, 4)));
					$mFin = $doc->univ_strftime("%m", mktime(1,1,1,substr($fin, 4, 2), substr($fin, 6, 2) +112, substr($fin, 0, 4)));
					$aFin = $doc->univ_strftime("%Y", mktime(1,1,1,substr($fin, 4, 2), substr($fin, 6, 2) +112, substr($fin, 0, 4)));
					echo "\n<br>La travailleuse est également protégée durant les 16 semaines qui suivent l'accouchement.";
					break;
					
				case "service":
					$nJS ++;
					if($nJS > 11) echo "\n<br>Il s'agit d'un service ayant duré plus de 11 jours (soit $nJS jours). On doit donc rajouter 4 semaines avant et 4 semaines après.";
					else echo "\n<br>Le service n'ayant pas duré plus de 11 jours (soit $nJS jours), on ne doit pas rajouter 4 semaines avant et 4 semaines après.";
					break;
			}
			$nbJour = round(mktime(1,1,1,$mFin, $jFin, $aFin)/86400) - round(mktime(1,1,1,$mDebut, $jDebut, $aDebut)/86400) + 1; //Comme on compte de jour compris à jour compris, la différence est inférieure de 1 au résultat réel.
			if(!$noProt && !$noProt2) echo "\n<br>La protection est ainsi donnée du $jDebut.$mDebut.$aDebut au $jFin.$mFin.$aFin, soit $nbJour jours.";
			if(mktime(1,1,1,$mFin, $jFin, $aFin) < mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste)) echo "\n<br>L'empêchement est entièrement antérieur au délai de congé. La prolongation est donc sans effet.";
			elseif(mktime(1,1,1,$mDebut, $jDebut, $aDebut) <= mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciement))
			{
				if(!$noProt2) echo "\n<br>Le début de l'empêchement est antérieur à la fin du délai de congé reporté à ce jour. La prolongation est ainsi efficace.";// . mktime(1,1,1,$mDebut, $jDebut, $aDebut);// . " ? " .mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste);
				$inutile = false;
				if(!isset($derniereProl)) $derniereProl = mktime(1,1,1, $moisAjuste, $jourAjuste, $anneeAjuste);
				if($noProt2) echo " Il n'y a donc pas de prolongation.";
				elseif($derniereProl < mktime(1,1,1,$mDebut, $jDebut, $aDebut)) echo "\n<br>La présente période ne se confond avec aucune période déjà écoulée. On peut donc l'utiliser entièrement.";
				elseif(mktime(1,1,1,$mDebut, $jDebut, $aDebut) < mktime(1,1,1,$moisAjuste, $jourAjuste, $anneeAjuste))
				{
					$mDebut = $moisAjuste;
					$jDebut = $jourAjuste;
					$aDebut = $anneeAjuste;
					$nbJour = round(mktime(1,1,1, $mFin, $jFin, $aFin)/86400) - round(mktime(1,1,1,$mDebut, $jDebut, $aDebut)/86400) + 1; //Comme on compte de jour compris à jour compris, la différence est inférieure de 1 au résultat réel.
					echo "\n<br>La présente période a toutefois commencé avant le délai de congé rétrospectif. On ne peut donc la compter que du $jDebut.$mDebut.$aDebut au $jFin.$mFin.$aFin, soit $nbJour jours.";
				}
				else
				{
					$jDebut = $doc->univ_strftime ("%d", $derniereProl);
					$mDebut = $doc->univ_strftime ("%m", $derniereProl);
					$aDebut = $doc->univ_strftime ("%Y", $derniereProl);
					$inutile = $derniereProl >= mktime(1,1,1,$mFin, $jFin, $aFin) ? true:false;
					if(!$inutile)
					{
						$nbJour = round(mktime(1,1,1,$mFin, $jFin, $aFin)/86400) - round(mktime(1,1,1,$mDebut, $jDebut, $aDebut)/86400) + 1; //Comme on compte de jour compris à jour compris, la différence est inférieure de 1 au résultat réel.
						echo "\n<br>La présente période se confond toutefois partiellement avec une ou plusieurs périodes déjà écoulées. On ne peut donc la compter que du $jDebut.$mDebut.$aDebut au $jFin.$mFin.$aFin, soit $nbJour jours.";
					}
					else echo "\n<br>La présente période se confond toutefois entièrement avec une ou plusieurs périodes déjà écoulées. On ne peut donc pas la compter.";
				}
				if(!$inutile && !$noProt2)
				{
					$derniereProl = mktime(1,1,1,$mFin, $jFin +1, $aFin); //jFin + 1 parce que ce n'est que le jour SUIVANT du prochain empêchement qui devra être compté
					$timeLicenciement = mktime(1,1,1, $moisLicenciement, $jourLicenciement + $nbJour, $anneeLicenciement);
					$jourLicenciement = $doc->univ_strftime("%d", $timeLicenciement);
					$moisLicenciement = $doc->univ_strftime("%m", $timeLicenciement);
					$anneeLicenciement = $doc->univ_strftime("%Y", $timeLicenciement);
					$dateLicenciementReport = $doc->univ_strftime("%d.%m.%Y", mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciement));
					echo "\n<br>Le congé est reporté de $nbJour jours au <b>$dateLicenciementReport</b>.";
				}
			}
			else echo "\n<br>Le début de l'empêchement est postérieur à la fin du délai de congé reporté à ce jour. La prolongation est donc sans effet.";
			$dateLicenciementReport = $doc->univ_strftime("%d.%m.%Y", mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciement));
		}
	}
	
	//traitement du terme

	echo "\n<h4>e) Calcul de l'éventuel report</h4>";
	$mktimeLicenciement = mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciement);
	switch ($_POST["terme"])
	{
		case "semaine":
			$jourSemaine = $doc->univ_strftime("%u", $mktimeLicenciement);
			if($jourSemaine != 7)
			{
				$nomJourSemaine = $doc->univ_strftime("%A", $mktimeLicenciement);
				$jourLicenciement += (7 - $jourSemaine);
				$reportTexte = "Le jour du terme est un $nomJourSemaine. Report au dimanche suivant, soit le ";
			}
			else
			{
				$nonReportTexte = "Le jour du terme est un dimanche. Pas de report au prochain terme.";
			}
			break;
		case "mois":
			$jourLicenciement2 = $doc->univ_strftime("%d", mktime(1,1,1,$moisLicenciement + 1, 0, $anneeLicenciement));
			//echo "<br>jourLicenciement: $jourLicenciement. jourLicenciement2: $jourLicenciement2";
			if($jourLicenciement2 != $jourLicenciement)
			{
				$reportTexte = "Le jour du terme ($jourLicenciement) n'est pas le dernier du mois. Report au ";
				$jourLicenciement = $jourLicenciement2;
			}
			else
			{
				$nonReportTexte = "Le jour du terme est le dernier du mois. Pas de report au prochain terme.";
			}
			break;
		case "trimestre":
			if($moisLicenciement < 4)
			{
				$moisLicenciement2 = 3;
				$jourLicenciement2 = 31;
			}
			elseif($moisLicenciement < 7)
			{
				$moisLicenciement2 = 6;
				$jourLicenciement2 = 30;
			}
			elseif($moisLicenciement < 10)
			{
				$moisLicenciement2 = 9;
				$jourLicenciement2 = 30;
			}
			else
			{
				$moisLicenciement2 = 12;
				$jourLicenciement2 = 31;
			}
			if($jourLicenciement2 != $jourLicenciement || $moisLicenciement2 != $moisLicenciement)
			{
				$reportTexte = "Le jour du terme ($jourLicenciement.$moisLicenciement) n'est pas le dernier jour du trimestre. Report au ";
				$jourLicenciement = $jourLicenciement2;
				$moisLicenciement = $moisLicenciement2;
			}
			else
			{
				$nonReportTexte = "Le jour du terme est le dernier du trimestre. Pas de report au prochain terme.";
			}
			break;
		case "annee":
			$jourLicenciement2 = 31;
			$moisLicenciement2 = 12;
			if($jourLicenciement2 != $jourLicenciement || $moisLicenciement2 != $moisLicenciement)
			{
				$reportTexte = "Le jour du terme ($jourLicenciement.$moisLicenciement) n'est pas le dernier jour de l'année. Report au ";
				$jourLicenciement = $jourLicenciement2;
				$moisLicenciement = $moisLicenciement2;
			}
			else
			{
				$nonReportTexte = "Le jour du terme est le dernier de l'année. Pas de report au prochain terme.";
			}
			break;
		case "net":
			$nonReportTexte = "Pas de report faute de terme.";
			break;
		default:
			echo "Le type de terme ({$_POST["terme"]}) n'est pas géré";
			break;
	}
	$jourbis = "$jour.$mois.$annee";
	
	$dateLicenciementReport = $doc->univ_strftime("%d.%m.%Y", mktime(1,1,1,$moisLicenciement, $jourLicenciement, $anneeLicenciement));
	if($reportTexte)
	{
		echo "\n$reportTexte <b>$dateLicenciementReport</b>";
	}
		else
	{
		echo "\n$nonReportTexte";
	}
}
?>
