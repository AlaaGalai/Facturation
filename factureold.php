<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->getTemplates(True); #TODO: on verra s'il faut maintenir absolument la relecture automatique des modèles à chaque chargement d'un modèle
//die();
$debug = False;
//$debug = True;
if($debug) $doc->tab_affiche();
if(!function_exists(sys_get_temp_dir))
{
	function sys_get_temp_dir()
	{
		foreach (array("/tmp", "/var/tmp", "C:\\Windows\\Temp", "D:\\Windows\\Temp") as $testDir) if(is_dir($testDir)) return $testDir;
	}
}

$now=microtime();
$testzip["nomrep"]=sys_get_temp_dir()."/".$_SESSION["user"].$now;
if(!is_dir("{$testzip["nomrep"]}")) $m=mkdir("{$testzip["nomrep"]}", 0777);

if($_POST["fichier"]) //Détermination du modèle de fichier et de son type (par l'extension seulement)
{
	if (is_file("{$_SESSION["tplPath"]}{$_SESSION["slash"]}{$_SESSION["db"]}{$_SESSION["slash"]}{$_POST["fichier"]}")) $tplPath = "{$_SESSION["tplPath"]}{$_SESSION["slash"]}{$_SESSION["db"]}{$_SESSION["slash"]}";
	elseif (is_file("{$_SESSION["tplPath"]}{$_SESSION["slash"]}00{$_SESSION["slash"]}{$_POST["fichier"]}")) $tplPath = "{$_SESSION["tplPath"]}{$_SESSION["slash"]}00{$_SESSION["slash"]}";
	else
	{
		$doc->title();
		$doc->entete();
		$doc->body();
		echo "<div class=\"attention\">".preg_replace("/{##}/", "{$_POST["fichier"]}", $doc->lang["facture_erreur"])."</span>";
		$doc->close();
		die();
	}
	preg_match("#^(.*)\.([^.]+$)#", $_POST["fichier"], $reg);
	$rad = $reg[1];
	$ext = $reg[2];
	if(preg_match("#^(ott|odt|stw|sxw)$#i", $ext))
	{
		$archive=new PclZip($_POST["fichier"]);
		$mode="zip";
		$testzip["file"]="./content.xml";
		copy("$tplPath{$_POST["fichier"]}", "{$testzip["nomrep"]}{$_SESSION["slash"]}{$_POST["fichier"]}");
		$path=chdir("{$testzip["nomrep"]}");
		$archive->extract(PCLZIP_OPT_BY_NAME, "content.xml");
	}
	elseif(preg_match("#^(rtf|txt)$#i", $ext))
	{
		$mode="direct";
		$testzip["file"]=$_POST["fichier"];
		copy("$tplPath{$_SESSION["slash"]}{$_POST["fichier"]}", "{$testzip["nomrep"]}{$_SESSION["slash"]}{$_POST["fichier"]}");
		$path=chdir("{$testzip["nomrep"]}");
	}
	else
	{
		$mode="unknown";
		$testzip["file"]=$_POST["fichier"];
		copy("$tplPath{$_SESSION["slash"]}{$_POST["fichier"]}", "{$testzip["nomrep"]}{$_SESSION["slash"]}{$_POST["fichier"]}");
		$path=chdir("{$testzip["nomrep"]}");
	}

}
if(isset($mode))
{
	if($rad == "facture" && ! $_POST["nodossier"])
	{
		header("Location: {$doc->settings["root"]}resultat_recherche.php");
		die();
	}
	
	if(!$debug)
	{
		$m = $doc->mimeGet($_POST["fichier"]);
		if($m) header("Content-type: $m");
		header("Content-Disposition: attachment; filename=\"{$_POST["fichier"]}\"");
	}
	$file=file_get_contents("./{$testzip["file"]}");

	if($mode != "unknown") //Traitement du fichier
	{
		//récupération des valeurs de TVA ainsi que du numéro d'adresse et de parties adverses //Traité différemment
// 		$r_dossier="select * from {$_SESSION["db"]}clients where nodossier like '{$_POST["nodossier"]}'";
// 		$val_dossier=mysqli_query($doc->mysqli, $r_dossier);
// 		while($ligne=mysqli_fetch_array($val_dossier))
// 		{
// 			$noadresse=$ligne["noadresse"];
// 			$nopa=$ligne["nopa"];
// 			$tva_temp=$ligne["tvadossier"];
// 			$tx_tva = (trim($tva_temp) == "") ? $_SESSION["optionGen"]["tx_tva"]: $tva_temp;
// 		}
		
		$na = False;
		
		//sous-traitants
		if($_POST["sous_traitant_limite"])
		{
			$sous_traitant_limite = "AND o.soustraitant like '{$_POST["sous_traitant_limite"]}'";
			$na = True;
		}
		else $sous_traitant_limite = "";

		//periode
		if($_POST["timestamp_debut"] AND $_POST["timestamp_fin"])
		{
			$deb = $doc->date_mtf($_POST["timestamp_debut"]);
			$fin = $doc->date_mtf($_POST["timestamp_fin"]);
			$print_deb = $doc->mysql_to_print($deb);
			$print_fin = $doc->mysql_to_print($fin);
			$op_date_limite = "AND dateop between '$deb' AND '$fin'";
			$ac_date_limite = "AND dateac between '$deb' AND '$fin'";
			$na = True;
		}
		else $limdate = "";

		
		//requête pour les valeurs d'adresse
		if($_POST["id"])
		{
			$requete_calcul_datas="select titre, nom, prenom, fonction, adresse, cp, zip, ville, pays from adresses where noadresse like '{$_POST["id"]}'";
		}
		elseif($_POST["nodossier"])
		{
			$requete_calcul_datas="select titre, nom, prenom, fonction, adresse, cp, zip, ville, pays from adresses LEFT OUTER JOIN {$_SESSION["session_avdb"]} on {$_SESSION["session_avdb"]}.noadresse = adresses.id where nodossier like {$_POST["nodossier"]}";
		}
		
		if($_POST["nodossier"])
		{
			//requête pour les honoraires théoriques
// 			$requete_calcul_theorique="select time_format(sec_to_time(SUM(time_to_sec(tempsop))), \"%k:%i\") as 'total', SUM(time_to_sec(tempsop)) as 'totalsec' from {$_SESSION["db"]}op where (nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $ss_trait $limdate)";
			$requete_calcul_theorique="select time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', SUM(time_to_sec(o.tempsop)) as 'totalseconde', sum(if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)) as totalprix, tvadossier, naturemandat from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (o.nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limite $recherche_limite $sous_traitant_limite)";
		
			//requête pour les entrées effectives
// 			$requete_calcul_recette="select (SUM(encaissement) - SUM(avfrais)) as total_resultat, SUM(encaissement) as 'total_recette', SUM(avfrais) as 'total_avance', SUM(transit) as 'total_transit', SUM(demande) as total_demande from {$_SESSION["db"]}op where (nodossier like '{$_POST["nodossier"]}' and dateac <> 0) $limdateac";
			$requete_calcul_recette="select (SUM(encaissement) - SUM(avfrais)) as total_resultat, SUM(encaissement) as 'total_recette', SUM(avfrais) as 'total_avance', SUM(transit) as 'total_transit', SUM(demande) as total_demande from {$_SESSION["session_opdb"]} where (nodossier like '{$_POST["nodossier"]}' and dateac <> 0 $ac_date_limite $recherche_limite)";
		}
		
		if($debug)
		{
			echo "<br>'$requete_calcul_recette'";
			echo "<br>'$requete_calcul_theorique'";
			echo "<br>'$requete_calcul_datas'";
		}
		
		//récupération de l'adresse
		$calcul_datas=mysqli_query($doc->mysqli, "$requete_calcul_datas");
		while($row=mysqli_fetch_array($calcul_datas, MYSQLI_ASSOC))
		{
			foreach($row as $val1 => $val2)
			{
				if(!preg_match("/Ã/", html_entity_decode($val2)) && $mode == "zip") $val2=utf8_encode(html_entity_decode($val2));
				else $val2=html_entity_decode($val2);
	// 			$val2=preg_replace("/'/", "`", "$val2");
				$$val1=$val2;
			}
			$change["val_titre"]=$titre;
			if($prenom) $nom="$prenom $nom";
			$change["val_nom"]=$nom;
			$change["val_fonction"]=$fonction;
			$change["val_adresse"]=$adresse;
			$change["val_cp"]=$cp;
			if($zip<>0) $ville="$zip $ville";
			$change["val_ville"]=$ville;
			$change["val_pays"]=$pays;
		}


		//récupération des données de paiement, puis de calcul de valeur du dossier
		$calcul_recette=mysqli_query($doc->mysqli, "$requete_calcul_recette");
		while($row=mysqli_fetch_array($calcul_recette))
		{ //affichage des rentrées effectives
			foreach($row as $val1 => $val2) $$val1=$val2;
			$deb_pr_TVA=$row["total_avance"];
			$change["val_rentrees"]="-" . number_format($row["total_recette"], 2, '.', '\'');
			$change["val_avances"]=number_format($row["total_avance"], 2, '.', '\'');
			$signe_transit = ($row["total_transit"] < 0)? "+":"";
			$change["val_transit"]=$signe_transit . number_format(0 - $row["total_transit"], 2, '.', '\'');
			$total_resultat=$row["total_resultat"];
		}

		$calcul_theorique=mysqli_query($doc->mysqli, $requete_calcul_theorique);
		while($row=mysqli_fetch_array($calcul_theorique))
		{
			//foreach($row as $val1 => $val2) $$val1=$val2;
			foreach($row as $val1 => $val2)
			{
				if(!preg_match("/Ã/", html_entity_decode($val2)) && $mode == "zip") $val2=utf8_encode(html_entity_decode($val2));
				else $val2=html_entity_decode($val2);
	// 			$val2=preg_replace("/'/", "`", "$val2");
				$$val1=$val2;
			}
			$change["val_naturemandat"]=$naturemandat;
			$tx_tva = $tvadossier;
			$change["val_tx_tva"]=$tx_tva;
			$prixseconde=$prixhoraire/3600;
			$totalseconde=$row["totalsec"];
			$gain=round($totalprix/3600*20)/20;
			$tva=round($gain*$tx_tva*0.2)/20;
			$change["val_tva_base"]=number_format($tva, 2, '.', '\'');
			if($_SESSION["optionGen"]["tva_deb"])
			{
				$tva_sur_deb=round($deb_pr_TVA*$tx_tva*0.2)/20;
				$tva += $tva_sur_deb;
				$change["val_tva_deb"]=number_format($tva_sur_deb, 2, '.', '\'');
			}
			$change["val_hono_theorique"]=number_format($gain, 2, '.', '\'');
			$change["val_tva"]=number_format($tva, 2, '.', '\'');
			$change["val_total_intermediaire"]=number_format(($tva+$gain +$total_avance), 2, '.', '\'');
			$change["val_total"]=number_format(($gain + $tva - $total_resultat - $total_transit), 2, '.', '\'');
			if($na)
			{
				$change["val_total"] = $change["val_total_intermediaire"];
				$change["val_transit"] = "(n/a)";
				$change["val_rentrees"] = "(n/a)";
;
			}
		}
		$factname = $limdate ? $doc->lang["facture_partielle"]:$doc->lang["facture_totale"];
		$factname = html_entity_decode($factname, ENT_COMPAT | ENT_HTML401, "UTF-8");
		$change["val_totalpartiel"] = $limdate ? preg_replace("/({##})(.*)({##})/", "{$print_deb} \\2 {$print_fin}", $factname):$factname;
		if($ss_trait)
		{
			$limst = html_entity_decode($doc->lang["facture_operations_par"], ENT_COMPAT | ENT_HTML401, "UTF-8");
			$limst = preg_replace("/{##}/", $_POST["sous_traitant_limite"], $limst);
			$limst = "/ $limst";
		}
		$change["val_soustrait"] = $ss_trait ? "$limst":""; 
		
		//echo $deb . " au " .$fin ."\n";
// 		$doc->tab_affiche($change);
		//die();
		//foreach(array("val_totalpartiel", "val_naturemandat") as $key) unset($change["$key"]);
		foreach($change as $nomchange => $valchange)
		{
			$file=preg_replace("/$nomchange\\b/", "$valchange", $file);
		}
	}
	$op=fopen($testzip["file"], "w+");
	fwrite($op, "$file");
	$cl=fclose($op);
	if($mode=="zip") $archive->add($testzip["file"]);
	
	$dir=opendir("..");
	$path=chdir("..");
	readfile("{$testzip["nomrep"]}/{$_POST["fichier"]}");
	unlink("{$testzip["nomrep"]}/{$_POST["fichier"]}");
	unlink("{$testzip["nomrep"]}/{$testzip["file"]}");
	rmdir("{$testzip["nomrep"]}");
	$cdir=closedir($dir);
	
}else{
	$doc->title();
 	$doc->entete();
	$doc->body();
	if(trim($doc->lang["facture_ligne1ter"]) != "") $doc->lang["facture_ligne1ter"] = " ".$doc->lang["facture_ligne1ter"];
	$doc->lang["facture_ligne1"] = preg_replace("#config_files#", "<a href=\"file:/{$_SESSION["tplPath"]}/\">{$_SESSION["tplPath"]}/</a> ".$doc->advice($doc->lang["facture_tip"]), $doc->lang["facture_ligne1"]);
	$doc->lang["facture_ligne4"] = preg_replace("/{##}/", "<a href=\"file:{$doc->settings["path"]}/templates\">{$doc->settings["path"]}/templates</a>", $doc->lang["facture_ligne4"]);
//	echo $doc->liste_erreur("100-004", TRUE);
	$facture_type[]="OpenOffice 2.x";
	$facture_ext[]="odt";
	$facture_type[]="OpenOffice 1.x";
	$facture_ext[]="sxw";
	$facture_type[]="Rich text format (RTF)";
	$facture_ext[]="rtf";
	$facture_type[]="Texte simple";
	$facture_ext[]="txt";
	$rempl = array("val_titre", "val_nom", "val_fonction", "val_adresse", "val_cp", "val_ville", "val_pays", "val_hono_theorique", "val_avances", "val_tva", "val_tva_base", "val_tva_deb", "val_rentrees", "val_total", "val_total_intermediaire");
	$rempl_texte = array("{$doc->lang["adresses_modifier_titre"]}", "{$doc->lang["adresses_modifier_nom"]}", "{$doc->lang["adresses_modifier_fonction"]}", "{$doc->lang["adresses_modifier_adresse"]}", "{$doc->lang["adresses_modifier_cp"]}", "{$doc->lang["adresses_modifier_ville"]}", "{$doc->lang["adresses_modifier_pays"]}", "$val_hono_theorique", "$val_avances", "$val_tva", "$val_tva_base", "$val_tva_deb", "$val_rentrees", "$val_total", "$val_total_intermediaire");
	echo "<br><br>{$doc->lang["facture_ligne1"]}\n<ul>";
	foreach($facture_type as $num => $val) echo "<li>{$facture_type["$num"]} ({$doc->lang["facture_ligne1bis"]}  <a href=\"file:/{$_SESSION["tplPath"]}/\">{$_SESSION["tplPath"]}/</a>facture.{$facture_ext["$num"]}{$doc->lang["facture_ligne1ter"]})</li>"; 
	echo "</ul>\n<br>{$doc->lang["facture_ligne2"]} {$doc->settings["version"]}.";
	echo "\n<br><br>{$doc->lang["facture_ligne3"]}.";
	echo "\n<br><br>{$doc->lang["facture_ligne4"]}.";
	echo "\n<br><br>{$doc->lang["facture_ligne5"]}&nbsp;:";
	echo "\n<ul>";
	foreach($rempl as $num => $val) echo "<li>{$rempl["$num"]} => {$rempl_texte["$num"]}</li>"; 
	echo "</ul>";
}
?>
