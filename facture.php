<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->registerLocale();
$doc->getTemplates(True); #TODO: on verra s'il faut maintenir absolument la relecture automatique des modèles à chaque chargement d'un modèle
$doc->webdav =  $_SESSION["optionGen"]["use_webdav"];
// $doc->webdav = 1;

if(!isset($_POST["type"])) $_POST["type"] = "facture";
if(!isset($_POST["dest"])) $_POST["dest"] = "adresse";

$debug = False;
// $debug = True;
if($debug) $doc->tab_affiche();
if(!function_exists(sys_get_temp_dir))
{
	function sys_get_temp_dir()
	{
		foreach (array("/tmp", "/var/tmp", "C:\\Windows\\Temp", "D:\\Windows\\Temp") as $testDir) if(is_dir($testDir)) return $testDir;
	}
}

//TODO: A rendre configurable
$concerne_client = "val_nom_client c. val_nom_pa";
$concerne_ca     = "val_nom_client c. val_nom_pa";
$concerne_pj     = "val_nom_client\n V. ref: val_no_pj";
$concerne_aut    = "val_nom_client c. val_nom_pa\nval_autref";


$now=microtime(True);
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
		// au besoin, récupération des noms de paragraphes pour certaines valeurs
		$filePara=file_get_contents("./{$testzip["file"]}");
		$multilines = array("val_adresse_entiere", "val_nom");
		foreach($multilines as $valPara)
		{
			preg_match("#(<text:p[^>]*>)( )*$valPara( )*#", $filePara, $regs);
			$paraStyles[$valPara] = $regs[1];
		}

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
else $mode = "help";

if(isset($mode))
{
	if($rad == "facture" && ! $_POST["nodossier"])
	{
		header("Location: {$doc->settings["root"]}resultat_recherche.php");
		die();
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
			$requete_calcul_datas="select titre, nom, prenom, fonction, adresse, cp, zip, ville, pays from adresses where id like '{$_POST["id"]}'";
		}
		elseif($_POST["nodossier"])
		{
			$requeteData = "";
			$compreqData = "";
			$array_num=array("a", "b", "c", "d", "e");
			foreach($array_num as $num => $let)
			{
				$suf = (! $num)? "": "$num";
				foreach(array("a" => "client", "p" => "pa", "t" => "aut", "c" => "ca", "j" => "pj") as $init => $nom)
				{
					$requeteData .= ", {$init}$let.nom as nom{$nom}$let, {$init}$let.prenom as prenom{$nom}$let, {$init}$let.mail as mail{$nom}$let";
					if($nom == "aut") $requeteData .= ", noautref{$suf}, noautaj{$suf}";
					if($nom == "client") $nom = "adresse";
					$compreqData .= " LEFT OUTER JOIN adresses {$init}$let on {$_SESSION["session_avdb"]}.no{$nom}$suf = {$init}$let.id";
				}
			}
			$requete_calcul_datas="select chemin, dateouverture, noautref, noautref1, noautref2, noautref3, noautref4, noautaj, noautaj1, noautaj2, noautaj3, noautaj4, firstad.titre as titre, firstad.nom as nom, firstad.prenom as prenom, firstad.fonction, firstad.adresse, firstad.cp, firstad.zip, firstad.ville, firstad.pays $requeteData from adresses firstad LEFT OUTER JOIN {$_SESSION["session_avdb"]} on {$_SESSION["session_avdb"]}.no{$_POST["dest"]} = firstad.id $compreqData where nodossier like {$_POST["nodossier"]}";
// 			die($requete_calcul_datas);
		}
		
		if($_POST["nodossier"])
		{
			//requête pour les honoraires théoriques
// 			$requete_calcul_theorique="select time_format(sec_to_time(SUM(time_to_sec(tempsop))), \"%k:%i\") as 'total', SUM(time_to_sec(tempsop)) as 'totalsec' from {$_SESSION["db"]}op where (nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $ss_trait $limdate)";
			$requete_calcul_theorique="select time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', SUM(time_to_sec(o.tempsop)) as 'totalseconde', sum(if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)) as totalprix, tvadossier, naturemandat from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (o.nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limite $recherche_limite $sous_traitant_limite)";
		
			//requête pour les entrées effectives
// 			$requete_calcul_recette="select (SUM(encaissement) - SUM(avfrais)) as total_resultat, SUM(encaissement) as 'total_recette', SUM(avfrais) as 'total_avance', SUM(transit) as 'total_transit', SUM(demande) as total_demande from {$_SESSION["db"]}op where (nodossier like '{$_POST["nodossier"]}' and dateac <> 0) $limdateac";
			$requete_calcul_recette="select (SUM(encaissement) - SUM(avfrais)) as total_resultat, SUM(encaissement) as 'total_recette', SUM(avfrais) as 'total_avance', SUM(transit) as 'total_transit', SUM(demande) as total_demande, SUM(depens) as 'total_depens' from {$_SESSION["session_opdb"]} where (nodossier like '{$_POST["nodossier"]}' and dateac <> 0 $ac_date_limite $recherche_limite)";
			
			$requete_liste_op = "select * from {$_SESSION["session_opdb"]} where nodossier like '{$_POST["nodossier"]}'  order by dateop ASC";
		}
		
		elseif($mode == "help")
		{
			$requete_calcul_datas = "select 1";
			$requete_calcul_theorique = "select 1";
			$requete_calcul_recette = "select 1";
			$requete_liste_op = "select 1";
		}
		else
		{
			$requete_calcul_theorique = "select 1";
			$requete_calcul_recette = "select 1";
			$requete_liste_op = "select 1";
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
			if($prenom && $nom) $nom2="$prenom $nom";
			elseif($nom) $nom2 = $nom;
			else $nom2 = $prenom;
			$change["val_nom"]=$nom;
			$change["val_prenom"]=$prenom;
			$change["val_fonction"]=$fonction;
			$change["val_adresse"]=$adresse;
			$change["val_cp"]=$cp;
			if($zip<>0) $ville="$zip $ville";
			$change["val_ville"]=$ville;
			$change["val_pays"]=$pays;
			$change["val_nodossier"]=$_POST["nodossier"];

			$val_adresse_entiere = "";
			foreach(array("titre", "nom2", "fonction", "adresse", "cp", "ville", "pays") as $var) if($$var)
			{
				if($val_adresse_entiere) $val_adresse_entiere .= "\n";
				$val_adresse_entiere .= $$var;
			}
			if($mode == "zip") $val_adresse_entiere = preg_replace("#\n#", "</text:p>{$paraStyles["val_adresse_entiere"]}", $val_adresse_entiere);
			$change["val_adresse_entiere"] = $val_adresse_entiere;
			
			$change["val_naturemandat"] = $naturemandat ? $naturemandat : "Divers";
			$change["val_date"]= $doc->univ_strftime("%e %B %Y");
			$change["val_ouverturedossier"]=$doc->mysql_to_print($dateouverture, '%d %B %Y');

			if(substr($_POST["dest"], 0, 3) == "aut")
			{
				$suf = substr($_POST["dest"], 3);
// 				$suf = "";
				$autref = "noautref$suf";
				$autaj = "noautaj$suf";
				$change["val_refautorite"] = $$autref;
				$change["val_aj"] = $$autaj;
			}
		}


		//récupération des données de paiement, puis de calcul de valeur du dossier
		$calcul_recette=mysqli_query($doc->mysqli, "$requete_calcul_recette");
		while($row=mysqli_fetch_array($calcul_recette))
		{ //affichage des rentrées effectives
			foreach($row as $val1 => $val2) $$val1=$val2;
			$deb_pr_TVA=$row["total_avance"];
			$change["val_rentrees"]="-" . number_format($row["total_recette"], 2, '.', '\'');
			$change["val_avances"]=number_format($row["total_avance"], 2, '.', '\'');
			$change["val_depens"]=number_format($row["total_depens"], 2, '.', '\'');
			$signe_transit = ($row["total_transit"] < 0)? "+":"";
			$change["val_transit"]=$signe_transit . number_format(0 - $row["total_transit"], 2, '.', '\'');
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
			$basetva = $gain+$deb_pr_TVA-$total_depens; #TODO: verifier ces deux lignes avec SFE
			$change["val_basetva"]=number_format($basetva, 2, '.', '\'');
			
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
			$change["val_listetotal"] = $totaltemps;
		}
		

//MODIFICATION SFE. LAISSE EN L'ETAT, MAIS A REPRENDRE

		//récupération de la liste des opérations
		$enc_compte = array();
		$op_compte = array();
		$op2_compte = array();
		
		$liste_op=mysqli_query($doc->mysqli, "$requete_liste_op");
		while($row=mysqli_fetch_array($liste_op))
		{
			if ($row["op"] != "")
			{
				$date_op = $row["dateop"];
				$date_op = explode("-",$date_op);
				$date_op = $date_op["2"] . "." . $date_op["1"] . "." . $date_op["0"];
				$op = $row["op"];
				$op = html_entity_decode($op);
				$op_compte[] = $op;
				$opavec = $row["opavec"];
				$opavec = html_entity_decode($opavec);
				$op_temps = $row["tempsop"];

				if ($date_op != "00.00.0000")
				{
					$listeoperations = $listeoperations . $date_op .  "<text:tab/>" . $op . "<text:tab/>" . $opavec . "<text:tab/>" . $op_temps . "</text:p><text:p text:style-name='Liste_5f_operations'>";
				}
			}

			if ($row["avfrais"] > 0)
			{
				$enc_compte[] = $row["ac"];
			}

			if ($row["op"] != "")
			{
				$date_op2 = $row["dateop"];
				$date_op2 = explode("-",$date_op2);
				$date_op2 = $date_op2["2"] . "." . $date_op2["1"] . "." . $date_op2["0"];
				$op2 = $row["op"];
				$op2 = utf8_encode(html_entity_decode($op2));
				$op2_compte[] = $op2;
				$opavec2 = $row["opavec"];
				$opavec2 = utf8_encode(html_entity_decode($opavec2));

				if ($date_op != "00.00.0000")
				{
					$listeoperations2 = $listeoperations2 . $date_op2 . "<text:tab/>" . $op2 . " " . $opavec2 . "</text:p><text:p text:style-name='Liste_5f_operations'>";
				}
			}
		
		}
		
		$change["val_listop2"]=$listeoperations2;

		
		
		$op_compte_unique = array_unique($op_compte);
		
		$nb = '0';
		foreach($op_compte_unique as $key => $value)
		{

			$requete_calcul_theorique2="select time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', SUM(time_to_sec(o.tempsop)) as 'totalseconde', sum(if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)) as totalprix, tvadossier, naturemandat, op from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (o.nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limite $recherche_limite $sous_traitant_limite AND op like '$value')";
			
			$calcul_theorique2=mysqli_query($doc->mysqli, $requete_calcul_theorique2);
			while($row=mysqli_fetch_array($calcul_theorique2))
			{
				$temps_heure=$row["totaltemps"];	
			}
			
			$op_occurence = array_count_values($op_compte);
			$count_occurence = count($op_occurence);
			if (trim($value) != "")
			{
				$op_occurence2 = $op_occurence[$value];

				//$value = html_entity_decode($value);

				if ($value != "Fax")
				{
					if ($op_occurence2 > 1)
					{

						$decompose = explode(" ",$value);
						if (count($decompose) > 1)
						{

							while($value2 = current($decompose))
							{
								if (key($decompose) == '0') $s = "s";
								else $s = "";
								$value3 = $value3 . $value2 . $s . " ";
								$value = $value3;
								next($decompose);
							}
							unset($decompose,$value3);

						}
						else $value = $value . "s";
					}
				}
			}
			$value = utf8_encode($value);
			++$nb;

			$op_resume = $op_resume . $op_occurence2 . "<text:tab/>".  $value . "<text:tab/>". $temps_heure;

			if ($nb <= ($count_occurence - 1)) $op_resume = $op_resume . "</text:p><text:p text:style-name='Liste_20_op_20_tribunal'>";
					
		}

		$change["val_listeoperations"]=$listeoperations;
		$change["val_listeop"]= $op_resume;
		
		
		//introduction liste des débours
		$nb = '0';
		$enc_compte_unique = array_unique($enc_compte);

		foreach($enc_compte_unique as $key => $value)
		{
			$value = addslashes($value);
			$requete_calcul_theorique3="select SUM(o.avfrais) as 'total_encaissements', time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', SUM(time_to_sec(o.tempsop)) as 'totalseconde', sum(if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)) as totalprix, tvadossier, naturemandat, op from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (o.nodossier like '{$_POST["nodossier"]}'  AND ac like '$value')";

			$calcul_theorique3=mysqli_query($doc->mysqli, $requete_calcul_theorique3);
			while($row=mysqli_fetch_array($calcul_theorique3)) $total_deb_unique = $row["total_encaissements"];

			$op_occurence2 = array_count_values($enc_compte);
			$count_occurence2 = count($op_occurence2);

			$value = stripslashes($value);
			$value = utf8_encode($value);

			++$nb;
			$op_resume2 = $op_resume2 . $value . "<text:tab/>".  $total_deb_unique;

			if ($nb <= ($count_occurence2 - 1)) $op_resume2 = $op_resume2 . "</text:p><text:p text:style-name='Liste_20_op_20_tribunal'>";
		}

		$change["val_listedebours"] = $op_resume2;
		
//FIN MODIFICATION SFE. LAISSE EN L'ETAT, MAIS A REPRENDRE
		
		
		
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
		
		//gestion des noms automatiques
		if($mode == "zip" && $_POST["nodossier"])
		{
			$lVar = "config_templates_{$_POST["type"]}";
			$saveFilePath = "$chemin{$_SESSION["slash"]}{$doc->lang["$lVar"]}{$_SESSION["slash"]}";
			if($_POST["type"] == "facture") $radical = $doc->lang["config_templates_facture"];
			else $radical = $change["val_nom"];
			$radical .= "_" . $doc->univ_strftime("%d.%m.%Y");
			$ext2 = $ext;
			if($ext2 == "ott") $ext2 = "odt";
			$saveFileRadical = "$radical.$ext2";
			$saveFileName = "{$saveFilePath}{$saveFileRadical}";
			$aj = 0;
			while(is_file($saveFileName))
			{
				$aj ++;
				$saveFileRadical = "$radical-$aj.$ext2";
				$saveFileName = "{$saveFilePath}{$saveFileRadical}";
			}
// 			die($saveFileName);
// 			$testArchive = $archive->extract(PCLZIP_OPT_BY_NAME, "content.xml");
			$testBasic = $archive->extract(PCLZIP_OPT_BY_NAME, "Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}script-lb.xml");
			$listDirs  = array("Basic", "META-INF", "Basic{$_SESSION["slash"]}Standard");
			$listFiles = array("Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml", "Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}script-lb.xml");
			$addFiles  = array();
// 			$doc->tab_affiche($testBasic);
	// 			die($testzip["nomrep"]);
			if($testBasic)
			{
// 				die("toto");
				$testProlawyer = $archive->extract(PCLZIP_OPT_BY_NAME, "Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml");
				if(!$testProlawyer)
				{
					$archive->extract(PCLZIP_OPT_BY_NAME, "META-INF{$_SESSION["slash"]}manifest.xml");
					$listFiles[] = "META-INF{$_SESSION["slash"]}manifest.xml";
					$addFiles = array("META-INF{$_SESSION["slash"]}manifest.xml");
					
					$fileScript=file_get_contents("{$testzip["nomrep"]}{$_SESSION["slash"]}Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}script-lb.xml");
					$fileScript = preg_replace("#</library:library>#", " <library:element library:name=\"Prolawyer\"/>\n</library:library>", $fileScript);
					$op = fopen("{$testzip["nomrep"]}{$_SESSION["slash"]}Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}script-lb.xml", "w+");
					fwrite($op, "$fileScript");
					$cl=fclose($op);
				}
				else
				{
					unlink("{$testzip["nomrep"]}{$_SESSION["slash"]}Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml");
					copy("{$_SESSION["prolawyerPath"]}{$_SESSION["slash"]}Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml", "{$testzip["nomrep"]}{$_SESSION["slash"]}Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml");
				}
			}
			else
			{
				$archive->extract(PCLZIP_OPT_BY_NAME, "META-INF{$_SESSION["slash"]}manifest.xml");
				$listFiles = array("Basic{$_SESSION["slash"]}script-lc.xml", "Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}script-lb.xml", "Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Module1.xml", "Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml", "META-INF{$_SESSION["slash"]}manifest.xml");
				$addFiles = $listFiles;
				foreach($listDirs as $dir) if(!is_dir("{$testzip["nomrep"]}{$_SESSION["slash"]}$dir")) mkdir("{$testzip["nomrep"]}{$_SESSION["slash"]}$dir", 0777);
			}
			foreach($listFiles as $f) if(!is_file("{$testzip["nomrep"]}{$_SESSION["slash"]}$f")) copy("{$_SESSION["prolawyerPath"]}{$_SESSION["slash"]}$f", "{$testzip["nomrep"]}{$_SESSION["slash"]}$f");
			
			if($addFiles)
			{
				$fileManifest=file_get_contents("META-INF{$_SESSION["slash"]}manifest.xml");
				$chain = "";
				foreach($addFiles as $addFile) $chain .= " <manifest:file-entry manifest:full-path=\"$addFile\" manifest:media-type=\"text/xml\"/>\n";
				$fileManifest = preg_replace("#</manifest:manifest>#", "$chain</manifest:manifest>", $fileManifest);
				$op = fopen("META-INF{$_SESSION["slash"]}manifest.xml", "w+");
				fwrite($op, "$fileManifest");
				$cl=fclose($op);
			}
				
			$fileBasic = file_get_contents("{$testzip["nomrep"]}{$_SESSION["slash"]}Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml", "w+");
			$fileBasic = preg_replace("#PLACEHOLDERTOREPLACE#", "$saveFileName", $fileBasic, 1);
			$op = fopen("{$testzip["nomrep"]}{$_SESSION["slash"]}Basic{$_SESSION["slash"]}Standard{$_SESSION["slash"]}Prolawyer.xml", "w+");
			fwrite($op, "$fileBasic");
			$cl=fclose($op);
			
			if(preg_match("#<office:scripts/>#", $file))
			{
				$file = preg_replace("#<office:scripts/>#", "<office:scripts></office:scripts>", $file);
			}
			if(preg_match("#<office:event-listeners/>#", $file))
			{
				$file = preg_replace("#<office:event-listeners/>#", "<office:event-listeners></office:event-listeners>", $file);
			}
			if( ! preg_match("#<office:event-listeners>#", $file))
			{
				$file = preg_replace("#<office:scripts>#", "<office:scripts><office:event-listeners></office:event-listeners>", $file);
			}
			$file = preg_replace("#<office:event-listeners>#", '<office:event-listeners><script:event-listener script:language="ooo:script" script:event-name="office:new" xlink:href="vnd.sun.star.script:Standard.Prolawyer.ForceSave?language=Basic&amp;location=document" xlink:type="simple"/><script:event-listener script:language="ooo:script" script:event-name="office:load-finished" xlink:href="vnd.sun.star.script:Standard.Prolawyer.ForceSave?language=Basic&amp;location=document" xlink:type="simple"/><script:event-listener script:language="ooo:script" script:event-name="dom:load" xlink:href="vnd.sun.star.script:Standard.Prolawyer.ForceSave?language=Basic&amp;location=document" xlink:type="simple"/>', $file);
// 			copy("$tplPath{$_POST["fichier"]}", "{$testzip["nomrep"]}{$_SESSION["slash"]}{$_POST["fichier"]}{$_SESSION["slash"]}")
// 			die("$file");
		}
		else
		{
			$listFiles = array();
		}
	}
	if(!$debug && $mode != "help")
	{
		$op=fopen($testzip["file"], "w+");
		fwrite($op, "$file");
		$cl=fclose($op);
		if($mode=="zip")
		{
			$archive->delete($testzip["file"]);
			$archive->add($testzip["file"]);
			foreach($listFiles as $f)
			{
	// 			echo "<br>$f";
				$archive->delete("$f");
				$archive->add("$f");
			}
		}
		
		if($doc->webdav)
		{
			copy("{$testzip["nomrep"]}/{$_POST["fichier"]}", "$saveFileName");
			chmod ("$saveFileName", 0666);
			
			$webdavFile = preg_replace("#{$_SESSION["optionGen"]["racine"]}#", "{$_SESSION["optionGen"]["racine_webdav"]}", $saveFileName);
			if(substr($webdavFile, 0, 1) == "/") $webdavFile = substr($webdavFile, 1);

			//on ouvre le fichier directement dans le webdav
			$lien = "vnd.sun.star.webdav://{$_SERVER["SERVER_NAME"]}/$webdavFile";


			$lien = html_entity_decode($lien);

// 			die($lien);
			header("Location:$lien");
		}
		else
		{
			$m = $doc->mimeGet($_POST["fichier"]);
			if($m) header("Content-type: $m");
			header("Content-Disposition: attachment; filename=\"$saveFileRadical\"");
		}
	}
	if($mode != "help")
	{
		if($doc->webdav)
		{
			$referer = $_SERVER["HTTP_REFERER"];
			if(preg_match("#(.*)\?#", $referer, $matches)) $referer = $matches[1];
			echo "<html><body onload=\"document.location='$referer?nodossier={$_POST['nodossier']}'\"></body></html>";

		}
		else
		{
			readfile("{$testzip["nomrep"]}/{$_POST["fichier"]}");
		}
		if($debug) die();
		unlink("{$testzip["nomrep"]}{$_SESSION["slash"]}{$_POST["fichier"]}");
		unlink("{$testzip["nomrep"]}{$_SESSION["slash"]}{$testzip["file"]}");
		foreach($listFiles as $f) if(is_file("{$testzip["nomrep"]}{$_SESSION["slash"]}$f")) unlink("{$testzip["nomrep"]}{$_SESSION["slash"]}$f");
		foreach(array_reverse($listDirs) as $d) if(is_dir("{$testzip["nomrep"]}{$_SESSION["slash"]}$d")) rmdir("{$testzip["nomrep"]}{$_SESSION["slash"]}$d");
		rmdir("{$testzip["nomrep"]}");
	}
}

if(!isset($mode) || $mode == "help")
{
	$doc->title();
 	$doc->entete();
	$doc->body();
	if(trim($doc->lang["facture_ligne1ter"]) != "") $doc->lang["facture_ligne1ter"] = " ".$doc->lang["facture_ligne1ter"];
	$doc->lang["facture_ligne1"] = preg_replace("#config_files#", "<a href=\"file:/{$_SESSION["tplPath"]}/\">{$_SESSION["tplPath"]}/</a> ".$doc->advice($doc->lang["facture_tip"]), $doc->lang["facture_ligne1"]);
	$doc->lang["facture_ligne4"] = preg_replace("/{##}/", "<a href=\"file:{$doc->settings["path"]}/templates\">{$doc->settings["path"]}/templates</a>", $doc->lang["facture_ligne4"]);
//	echo $doc->liste_erreur("100-004", TRUE);
	$facture_type[]="OpenOffice 2.x";
	$facture_ext[]="odt/ott";
	$facture_type[]="OpenOffice 1.x";
	$facture_ext[]="sxw/stw";
	$facture_type[]="Rich text format (RTF)";
	$facture_ext[]="rtf";
	$facture_type[]="Texte simple";
	$facture_ext[]="txt";
	$rempl = array("val_titre", "val_nom", "val_fonction", "val_adresse", "val_cp", "val_ville", "val_pays", "val_hono_theorique", "val_avances", "val_tva", "val_tva_base", "val_tva_deb", "val_rentrees", "val_total", "val_total_intermediaire");
	$rempl_texte = array("{$doc->lang["adresses_modifier_titre"]}", "{$doc->lang["adresses_modifier_nom"]}", "{$doc->lang["adresses_modifier_fonction"]}", "{$doc->lang["adresses_modifier_adresse"]}", "{$doc->lang["adresses_modifier_cp"]}", "{$doc->lang["adresses_modifier_ville"]}", "{$doc->lang["adresses_modifier_pays"]}", "$val_hono_theorique", "$val_avances", "$val_tva", "$val_tva_base", "$val_tva_deb", "$val_rentrees", "$val_total", "$val_total_intermediaire");
	foreach($change as $a => $b) if(!in_array($a, $rempl))
	{
		$rempl[] = $a;
		$rempl_texte[] = $b;
	}
	echo "<br><br>{$doc->lang["facture_ligne1"]}\n<ul>";
	foreach($facture_type as $num => $val) echo "<li>{$facture_type["$num"]} ({$doc->lang["facture_ligne1bis"]}  <a href=\"file:/{$_SESSION["tplPath"]}/\">{$_SESSION["tplPath"]}/</a>facture.{$facture_ext["$num"]}{$doc->lang["facture_ligne1ter"]})</li>"; 
	echo "</ul>\n<br>{$doc->lang["facture_ligne2"]} {$doc->settings["version"]}.";
	echo "\n<br><br>{$doc->lang["facture_ligne3"]}.";
	echo "\n<br><br>{$doc->lang["facture_ligne4"]}.";
	echo "\n<br><br>{$doc->lang["facture_ligne5"]}&nbsp;:";
	echo "\n<ul>";
	foreach($rempl as $num => $val) echo "<li>{$rempl["$num"]} => {$rempl_texte["$num"]}</li>"; 
	echo "</ul>";
// 	$doc->tab_affiche(5);
}
?>
