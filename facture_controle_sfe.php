<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->getTemplates(True); #TODO: on verra s'il faut maintenir absolument la relecture automatique des modèles à chaque chargement d'un modèle
//die();
$webdav = true;
$debug = false;
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
	
	if(!$debug and !$webdav)
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
			$requete_calcul_datas="select dateouverture, noautref, noautaj, titre, nom, prenom, fonction, adresse, cp, zip, ville, pays from adresses LEFT OUTER JOIN {$_SESSION["session_avdb"]} on {$_SESSION["session_avdb"]}.noadresse = adresses.id where nodossier like {$_POST["nodossier"]}";
		}
		
		//modif-SFE -- requête nécessaire pour la création automatique du nom du dossier dans le webdav
		if($_POST["nodossier"])
		{
			$requete_calcul_datas2="select titre, nom, prenom, fonction, adresse, cp, zip, ville, pays from adresses LEFT OUTER JOIN {$_SESSION["session_avdb"]} on {$_SESSION["session_avdb"]}.noadresse = adresses.id where nodossier like {$_POST["nodossier"]}";
		}
		//end modif-SFE
		
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
			//if($prenom) $nom="$prenom $nom";
			$change["val_nom"]=$nom;
			$change["val_fonction"]=$fonction;
			$change["val_adresse"]=$adresse;
			$change["val_cp"]=$cp;
			if($zip<>0) $ville="$zip $ville";
			$change["val_ville"]=$ville;
			$change["val_pays"]=$pays;
			
			$change["val_notreref"]=$_POST["nodossier"];
			
						//modif SFE - personnalisation des valeurs à modifier dans courrier/facture
			if ($titre != ""){
			$adr_titre=$titre . "</text:p><text:p text:style-name='adresse_5f_automatique'>";
			}

			if($prenom) $nom2="$prenom $nom";
			if(!$prenom) $nom2="$nom";
			$adr_nom = $nom2 . "</text:p><text:p text:style-name='adresse_5f_automatique'>";

			if ($fonction != ""){
			$adr_fonction = $fonction . "</text:p><text:p text:style-name='adresse_5f_automatique'>";
			}

			if ($adresse != ""){
			$adr_adresse = $adresse . "</text:p><text:p text:style-name='adresse_5f_automatique'>";
			}

			if ($cp != ""){
			$adr_cp = $cp . "</text:p><text:p text:style-name='adresse_5f_automatique'>";
			}

			if ($zip<>0){
			$adr_zip = $zip . " ";
			}

			if ($ville != ""){
			$adr_ville = $ville . "</text:p><text:p text:style-name='adresse_5f_automatique'>";
			}

			if ($pays != ""){
			$adr_pays=$pays;
			}

			$change["val_adresse_entiere"] = $adr_titre . $adr_nom . $adr_fonction . $adr_adresse . $adr_cp . $adr_zip . $adr_ville . $adr_pays;
			
			if ($_POST["nodossier"] != ""){
			$change["val_naturemandat"]=$naturemandat;
			} else {
			$change["val_naturemandat"]="Divers";
			}
			
			//ancienne fonction de Etude utilisée pour afficher en lettres la date du jour
			$mois["January"] = "janvier";
			$mois["February"] = "février";
			$mois["March"] = "mars";
			$mois["April"] = "avril";
			$mois["May"] = "mai";
			$mois["June"] = "juin";
			$mois["July"] = "juillet";
			$mois["August"] = "août";
			$mois["September"] = "septembre";
			$mois["October"] = "octobre";
			$mois["November"] = "novembre";
			$mois["December"] = "décembre";

			function getMois($month){
			return $mois[$month];
			}

			$jour["01"] = "1er";
			$jour["02"] = "2";
			$jour["03"] = "3";
			$jour["04"] = "4";
			$jour["05"] = "5";
			$jour["06"] = "6";
			$jour["07"] = "7";
			$jour["08"] = "8";
			$jour["09"] = "9";
			$jour["10"] = "10";
			$jour["11"] = "11";
			$jour["12"] = "12";
			$jour["13"] = "13";
			$jour["14"] = "14";
			$jour["15"] = "15";
			$jour["16"] = "16";
			$jour["17"] = "17";
			$jour["18"] = "18";
			$jour["19"] = "19";
			$jour["20"] = "20";
			$jour["21"] = "21";
			$jour["22"] = "22";
			$jour["23"] = "23";
			$jour["24"] = "24";
			$jour["25"] = "25";
			$jour["26"] = "26";
			$jour["27"] = "27";
			$jour["28"] = "28";
			$jour["29"] = "29";
			$jour["30"] = "30";
			$jour["31"] = "31";

			function getJour($day){
			return $jour[$day];
			}

			$month = Date(F);
			getMois($month);

			$day = Date(d);
			getJour($day);

			$datelettre= $jour[$day] ." " . $mois[$month] . " " . Date(Y);

			$datelettre=utf8_encode(html_entity_decode($datelettre));
			$change["val_date"]= $datelettre;
			$change["val_nodossier"]= $_POST["nodossier"];

			$date_ouv = explode("-",$dateouverture);
			$date_ouv_mois = $date_ouv["1"];
			$date_ouv = $date_ouv["2"] . "." . $date_ouv_mois . "." . $date_ouv["0"];
			setlocale (LC_TIME, 'fr_FR.utf8','fra'); 
			$date_ouv = strftime('%d %B %Y', strtotime($date_ouv));

			$change["val_ouverturedossier"]=$date_ouv;

			if ($noautref !=""){
			$change["val_refautorite1"] = "<text:span>V/réf.</text:span><text:tab/>";
			$change["val_refautorite2"] = "<text:span>" . $noautref . "</text:span>";

			$change["val_refautorite1"] = utf8_encode(html_entity_decode($change["val_refautorite1"]));
			$change["val_refautorite2"] = utf8_encode(html_entity_decode($change["val_refautorite2"]));
			//ajouter ici AJ si nécessaire


			} else {
			$change["val_refautorite1"] = "";
			$change["val_refautorite2"] = "";
			}
			
			if ($noautaj !=""){
			$change["val_aj"] = $noautaj;
			} else {
			$change["val_aj"] = "";
			}
			
			//modif-SFE
			//on enregistre le nom de l'adresse pour la création du nom du fichier et on ajoute l'espace nécessaire à la lecture ultérieure du fichier par webdav si un prénom existe
			if ($nom != ""){
			$nom_fich=$nom;
			}
			if ($prenom !=""){
			$prenom_fich=" ".$prenom;
			}
			//endmodif-SFE
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
			$total_depens = $row["total_depens"];
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
			$change["val_tauxtva_client"]=$tx_tva;
			$prixseconde=$prixhoraire/3600;
			$totalseconde=$row["totalsec"];
			$gain=round($totalprix/3600*20)/20;
			$tva=round($gain*$tx_tva-$total_depens*0.2)/20;
			$tva_sur_deb=round($deb_pr_TVA*$tx_tva*0.2)/20;
			$tva += $tva_sur_deb;
			$basetva = $gain+$deb_pr_TVA-$total_depens;
			$change["val_basetva"]=number_format($basetva, 2, '.', '\'');
			
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
		$change["val_listetotal"] = $totaltemps;
		}
		
		
		//récupération de la liste des opérations

		$liste_op=mysqli_query($doc->mysqli, "$requete_liste_op");
		while($row=mysqli_fetch_array($liste_op))
		{
		if ($row["op"] != ""){
		$date_op = $row["dateop"];
		$date_op = explode("-",$date_op);
		$date_op = $date_op["2"] . "." . $date_op["1"] . "." . $date_op["0"];
		$op = $row["op"];
		$op = html_entity_decode($op);
		$op_compte[] = $op;
		$opavec = $row["opavec"];
		$opavec = html_entity_decode($opavec);
		$op_temps = $row["tempsop"];

		if ($date_op != "00.00.0000"){
		$listeoperations = $listeoperations . $date_op .  "<text:tab/>" . $op . "<text:tab/>" . $opavec . "<text:tab/>" . $op_temps . "</text:p><text:p text:style-name='Liste_5f_operations'>";

		}
		}

		if ($row["avfrais"] > 0){
		$enc_compte[] = $row["ac"];
		}

		if ($row["op"] != ""){
		$date_op2 = $row["dateop"];
		$date_op2 = explode("-",$date_op2);
		$date_op2 = $date_op2["2"] . "." . $date_op2["1"] . "." . $date_op2["0"];
		$op2 = $row["op"];
		$op2 = utf8_encode(html_entity_decode($op2));
		$op2_compte[] = $op2;
		$opavec2 = $row["opavec"];
		$opavec2 = utf8_encode(html_entity_decode($opavec2));

		if ($date_op != "00.00.0000"){

		$listeoperations2 = $listeoperations2 . $date_op2 . "<text:tab/>" . $op2 . " " . $opavec2 . "</text:p><text:p text:style-name='Liste_5f_operations'>";
		}
		}
		
		}
		
		$change["val_listop2"]=$listeoperations2;

		
		
		$op_compte_unique = array_unique($op_compte);
		
		$nb = '0';
		foreach($op_compte_unique as $key => $value){

		$requete_calcul_theorique2="select time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', SUM(time_to_sec(o.tempsop)) as 'totalseconde', sum(if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)) as totalprix, tvadossier, naturemandat, op from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (o.nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limite $recherche_limite $sous_traitant_limite AND op like '$value')";
		
		$calcul_theorique2=mysqli_query($doc->mysqli, $requete_calcul_theorique2);
		while($row=mysqli_fetch_array($calcul_theorique2))
		{
		$temps_heure=$row["totaltemps"];	
		}
		
		$op_occurence = array_count_values($op_compte);
		$count_occurence = count($op_occurence);
		if (trim($value) != ""){
		$op_occurence2 = $op_occurence[$value];

		//$value = html_entity_decode($value);

		if ($value != "Fax"){
		if ($op_occurence2 > 1){

		$decompose = explode(" ",$value);
		if (count($decompose) > 1){

		while($value2 = current($decompose)){
		if (key($decompose) == '0'){
		$s = "s";
		} else {
		$s = "";
		}
		$value3 = $value3 . $value2 . $s . " ";
		$value = $value3;
		next($decompose);
		}
		unset($decompose,$value3);

		} else {

		$value = $value . "s";
		}
		}
		}
		}
		$value = utf8_encode($value);
		++$nb;

		$op_resume = $op_resume . $op_occurence2 . "<text:tab/>".  $value . "<text:tab/>". $temps_heure;

		if ($nb <= ($count_occurence - 1)){
		$op_resume = $op_resume . "</text:p><text:p text:style-name='Liste_20_op_20_tribunal'>";
		}
				
		}

		$change["val_listeoperations"]=$listeoperations;
		$change["val_listeop"]= $op_resume;
		
		
		//introduction liste des débours
		$nb = '0';
		$enc_compte_unique = array_unique($enc_compte);

		foreach($enc_compte_unique as $key => $value){

		$value = addslashes($value);
		$requete_calcul_theorique3="select SUM(o.avfrais) as 'total_encaissements', time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', SUM(time_to_sec(o.tempsop)) as 'totalseconde', sum(if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)) as totalprix, tvadossier, naturemandat, op from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (o.nodossier like '{$_POST["nodossier"]}'  AND ac like '$value')";

		$calcul_theorique3=mysqli_query($doc->mysqli, $requete_calcul_theorique3);
		while($row=mysqli_fetch_array($calcul_theorique3))
		{
		$total_deb_unique = $row["total_encaissements"];
		}

		$op_occurence2 = array_count_values($enc_compte);
		$count_occurence2 = count($op_occurence2);


		$value = stripslashes($value);
		$value = utf8_encode($value);

		++$nb;
		$op_resume2 = $op_resume2 . $value . "<text:tab/>".  $total_deb_unique;

		if ($nb <= ($count_occurence2 - 1)){
		$op_resume2 = $op_resume2 . "</text:p><text:p text:style-name='Liste_20_op_20_tribunal'>";
		}
		}

		$change["val_listedebours"] = $op_resume2;
		
		
		
		
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
	
	//ajout webdav par SFE
	if($webdav){
	
			//modif SFE - création du nom automatique du répertoire dans le webdav
		//récupération du nom du dossier nécessaire à la création du répertoire
		$calcul_datas2=mysqli_query($doc->mysqli, "$requete_calcul_datas2");
		while($row=mysqli_fetch_array($calcul_datas2, MYSQLI_ASSOC))
		{
		$nom_rep=$row["nom"];
		$prenom_rep=$row["prenom"];
		}

		if ($prenom_rep){
		$repertoiredecopie = "$nom_repertoire/" . $nom_rep . " " . $prenom_rep . " (" . $_POST['nodossier'] . ")" . "/";
		$testrep = $nom_rep . " " . $prenom_rep . " (" . $_POST['nodossier'] . ")";
		} else {
		$repertoiredecopie = "$nom_repertoire/" . $nom_rep . " (" . $_POST['nodossier'] . ")" . "/";
		$testrep = $nom_rep . " (" . $_POST['nodossier'] . ")";
		}

		$rech_initiales = explode("clients", $_SESSION["session_avdb"]);
		$initiales_fichier = $rech_initiales[0];
		$nom_repertoire_base = "correspondance" . "_" . $initiales_fichier;
		$nom_repertoire_base = "correspondance/" . $nom_repertoire_base;
		
		$repertoiredecopie = $nom_repertoire_base . $repertoiredecopie;

		//vérification de l'existence du répertoire, et création si n'existe pas
		$dir2=opendir("..");
		$path=chdir("/media/DATA/www/Etude/$nom_repertoire_base");

		$testrep = html_entity_decode($testrep);
		if (!file_exists(utf8_encode($testrep))){
		trim($testrep);
		utf8_encode($testrep);
		mkdir(utf8_encode("./$testrep"), 0777);
		chmod (utf8_encode("$testrep"), 0777);
		}
		$cdir=closedir($dir2);
		
		//création du nom du fichier (attention pas d'espace à ajouter entre prenom et nom) + casse-tête utf8
		setlocale (LC_TIME, 'fr');
		$nomdecopie = utf8_decode($nom_fich) . utf8_decode($prenom_fich) . " " . strftime("%Y-%m-%d") . " " .$rad . ".odt";
		
		//si le fichier existe déjà, on ajoute un suffixe + casse tête-utf8
		$fichier = "/media/DATA/www/Etude/".$repertoiredecopie . $nomdecopie;
		$i = 2;
		while (file_exists(utf8_encode($fichier))){
		$nomdecopie = utf8_decode($nom_fich) . utf8_decode($prenom_fich) . " " . strftime("%Y-%m-%d") . " " . $rad . " " . "$i" . ".odt";
		$fichier = "/media/DATA/www/Etude/".$repertoiredecopie.$nomdecopie;
		$i++;
		}
		
		$nomdecopie3 = utf8_encode($repertoiredecopie) . utf8_encode($nomdecopie);
		
		//on copie le fichier dans le bon répertoire du webdav
	$dir=opendir("..");
	$path=chdir("..");
	copy("{$testzip["nomrep"]}/{$_POST["fichier"]}", "/media/DATA/www/Etude/{$nomdecopie3}");
	//readfile("{$testzip["nomrep"]}/{$_POST["fichier"]}");
	unlink("{$testzip["nomrep"]}/{$_POST["fichier"]}");
	unlink("{$testzip["nomrep"]}/{$testzip["file"]}");
	rmdir("{$testzip["nomrep"]}");
	$cdir=closedir($dir);
	
	chmod ("/media/DATA/www/Etude/{$nomdecopie3}", 0777);

	//on ouvre le fichier directemetn dans le webdav
	$repertoiredecopie = utf8_encode($repertoiredecopie);
	$nomdecopie = utf8_encode($nomdecopie);
	$lien = "vnd.sun.star.webdav://" . $ip_serveur . "/Etude/{$repertoiredecopie}{$nomdecopie}";

	$lien = html_entity_decode($lien);

	header("Location:" . $lien );	

	//finalement on redirige vers la page operations.php par javascript, en envoyant le no du dossier
?>
<div id="myformcontainer"></div>
<script type="text/javascript">
function redirect_data(mots)
{
    var mydiv = document.getElementById('myformcontainer').innerHTML = '<form id="form" method="post" action="operations.php"><input name="nodossier" type="hidden" value="<?php echo $_POST['nodossier']; ?>" /></form>';
    f=document.getElementById('form');
    if(f){
    f.submit();
    }
}

redirect_data('');
</script>

<?php

	} else {
	//endmodif-SFE
	//écriture et ouverture fichier méthode prolawyer
	$dir=opendir("..");
	$path=chdir("..");
	readfile("{$testzip["nomrep"]}/{$_POST["fichier"]}");
	unlink("{$testzip["nomrep"]}/{$_POST["fichier"]}");
	unlink("{$testzip["nomrep"]}/{$testzip["file"]}");
	rmdir("{$testzip["nomrep"]}");
	$cdir=closedir($dir);
	}
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
