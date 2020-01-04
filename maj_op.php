<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->addStyle = "petit";
$doc->connection();
// $charset = (!preg_match("#zefix#", $_POST["retour"]))? false:"utf-8";
$doc->title(false);
//$doc->body(2);
//$doc->entete();
// $_POST["notlike"] = "id__107453__https://doodle.com/poll/b3sny3vm6dwfgbnp__m";

$debugMode = False;
// $debugMode="nonbloque";// pour afficher les requêtes
// $debugMode="bloque";// pour afficher les requêtes sans les exécuter
// $doc->tab_affiche($_POST["mailing"]);

if ($debugMode) $doc->tab_affiche($_POST);

if($_POST["retour"]=="biblio/creer_livre")
{
	$_POST["domaine"] = "";
	foreach($_POST as $n => $v) if(substr($n, 0, 5) == "check")
	{
		$_POST["domaine"] .= preg_replace("/_/", " ", substr($n,5)).",";
		unset($_POST["$n"]);
	}
}

if($_POST["action"]=="update_all")
{
	$veto=FALSE;
	$ver_query="select * from {$_SESSION["session_avdb"]} where nodossier like {$_POST["new_file"]}";
	$check=mysqli_query($doc->mysqli, "$ver_query");
	echo mysqli_error($doc->mysqli); 
	if(mysqli_num_rows($check) == 0) 
	{
		$veto = TRUE;
	}
	foreach($_POST as $a => $b) if(preg_match("#nodossier#", $a)) $_POST["$a"]=$_POST["new_file"];
	$_POST["action"]="update";
}

if($_POST["groups"])
{
	if(preg_match("#modifier_rdv#", $_POST["retour"]))
	{
		if(isset($_POST["groups"])) if(is_array($_POST["groups"])) foreach($_POST["groups"] as $nom) $_POST["rdv_pour"][] = $nom;
		unset($_POST["groups"]);
	}
	if(preg_match("#modifier_delai#", $_POST["retour"]))
	{
		if(isset($_POST["groups"])) if(is_array($_POST["groups"])) foreach($_POST["groups"] as $nom) $_POST["dl_pour"][] = $nom;
		unset($_POST["groups"]);
	}
}

$ancien_nodossier=$_POST["old_file"];
unset ($_POST["old_file"]);
unset ($_POST["new_file"]);


$doc->groups = $doc->liste_utilisateursgroupes_ex();

$multireq_verif=FALSE;
$deja_note=array();
$control = false;
$base = false;
foreach($_POST as $a => $b) 
{
	if($a == "dl_pour" || $a == "rdv_pour")
	{
		$string="";
		$temp=$_POST["$a"];
		if(is_array($temp)) 
		foreach($temp as $nom)
		{
			if(preg_match("#\*\*\*group\*\*\*#", $nom))
			{
				$nudename=preg_replace("#\*\*\*group\*\*\*#", "", $nom);
				foreach($doc->groups["$nudename"] as $val)
				{
					if(trim($val) != "")
					{
						$val=trim($val);
						if(!in_array($val, $deja_note))
						{
							$string .= trim($val).",";
							$deja_note[]=$val;
						}
					}
				}
			}else{
				$string .= "$nom,";
			}
		}
		$_POST["$a"] = $string;
	}
	elseif(preg_match("#^special-([^-]+)-([^-]+)-([^-]+)-([^-]+)$#", $a, $reg))
	{
		$champs = $reg[1];
		$base   = $reg[2];
		$type   = $reg[3];
		$num    = $reg[4];
		#echo "<br>Champs: $champs. Base: $base. Type: $type. Num: $num";

		if ($type == "bloc")
		{
			if (!is_array($bloc)) $bloc = array();
			if (preg_match("#(control)@?(.*)#", $num, $reg))
			{
				$control = $champs;
				$valcontrol = $b;
				if($reg[2])
				{
					$unique = $reg[2];
					#print "<br>unique: '$unique'";
				}
			}
			else 
			{
				if(!is_array($bloc[$num])) $bloc[$num] = array();
				$bloc[$num][$champs] = $b;
			}
		}
		unset ($_POST["$a"]);
	}
	elseif($a == "mailingname")
	{
		$multireq_verif=True;
		foreach($_POST["$a"] as $b) if($b)
		{
			$requetes_multiples[$b]["mailingname"]=$b;
			$requetes_multiples[$b]["adresseid"]=$_POST["id"];
		}
		unset($_POST["mailingname"]);
	}
}

if($_POST["action"] == "modifier_mailing")
{
	$preQ = "delete from mailing where adresseid = {$_POST["id"]}";
	if($debugMode != "bloque")
	{
		$preE = mysqli_query($doc->mysqli, $preQ);
			$preV = $preE? "(b) <span style='color:green'>OK</span>":"<span style='color:red'>KO (".mysqli_error($doc->mysqli).")</span>";
			if($preV) $verif = True;
	}
	if($debugMode) 	echo "\n<br>Prerequete:\n<br>".$doc->beautifyMysql($preQ, false, false, 3). "$preV"."\n<br>"; 
}
// $doc->tab_affiche($_POST);
// $doc->tab_affiche($bloc);

if($control && $base)
{
	$qs = array();
	$un = array();
	foreach($bloc as $id => $suite)
	{
		$qsdelete = "delete from $base where $control like '$valcontrol'";
		$d = "insert into $base set $control = $valcontrol";
		if(trim($bloc[$id]["$unique"] == '')) continue;
		elseif (in_array($bloc[$id]["$unique"], $un)) continue;
		foreach($suite as $a => $b) $d .= ", $a = '$b'";
		$qs[] = $d;
		$un[] = $bloc[$id]["$unique"];
	}

	$result = True;
	$result = mysqli_query($doc->mysqli, $qsdelete);
	echo mysqli_error($doc->mysqli); 
// 	echo "<br>'$qsdelete'";
	if ($result)
	{
		foreach($qs as $qi)
		{
			mysqli_query($doc->mysqli, $qi);
			echo mysqli_error($doc->mysqli); 
			#echo "<br>$qi";
		}
	}

}

// die();

// $doc->tab_affiche($_POST);

$multireq=array();
$action_verif = ($_POST["action"] == "delete")? TRUE : FALSE;
foreach($_POST as $a => $b)
{
	if (!get_magic_quotes_gpc())
	{
		$_POST[$a]=addslashes(stripslashes($b));
	}
	
	//gestion de requêtes multiples
	if(preg_match("#-multireq-#", $a))
	{
		$multireq_verif=TRUE;
		$offset=strpos($a, "-multireq-");
		$length=strlen($a);
		$neglength=$offset -$length;
		$little_offset=$neglength + 10;
		$id=substr($a, $little_offset);
		$id_multireq="norequete-multireq-".$id;
		$anew=substr($a, 0, $neglength);
		if(!preg_match("#norequete-multireq#", $a) AND ($_POST["$id_multireq"] == "on" || $_POST["$id_multireq"] == "1")) $requetes_multiples[$id][$anew]=$b;
		if(!preg_match("#norequete-multireq#", $a)) unset($_POST["$a"]);
	}
}

foreach($_POST as $a => $b)
{
	if(!preg_match("#norequete-multireq#", $a)) $requetes_multiples["solde"][$a]=$b;
	unset($_POST[$a]);
}


//parsing des variables POST et création de la requête
$requete["texteMultiples"] = array();
if($debugMode) $doc->tab_affiche($requetes_multiples);
foreach($requetes_multiples as $nom =>$array)
{
	if(($multireq_verif==FALSE OR $nom != "solde") AND $veto == FALSE)
	{
		if ($requetes_multiples["solde"]["secteur"]=="operations" || $requetes_multiples["solde"]["secteur"]=="journal_op" || $requetes_multiples["solde"]["secteur"]=="benefice")
		{
			$requetes_multiples["$nom"]["dateop"]="{$requetes_multiples["$nom"]["date_annee"]}-{$requetes_multiples["$nom"]["date_mois"]}-{$requetes_multiples["$nom"]["date_jour"]}";
			if($requetes_multiples["$nom"]["temps_heure"]=="") $requetes_multiples["$nom"]["temps_heure"]="0";
			if($requetes_multiples["$nom"]["temps_minute"]=="") $requetes_multiples["$nom"]["temps_minute"]="0";
			$requetes_multiples["$nom"]["tempsop"]="{$requetes_multiples["$nom"]["temps_heure"]}:{$requetes_multiples["$nom"]["temps_minute"]}";
			unset ($requetes_multiples["$nom"]["date_jour"]);
			unset ($requetes_multiples["$nom"]["date_mois"]);
			unset ($requetes_multiples["$nom"]["date_annee"]);
			unset ($requetes_multiples["$nom"]["temps_heure"]);
			unset ($requetes_multiples["$nom"]["temps_minute"]);
		}
	
		elseif ($requetes_multiples["solde"]["secteur"]=="encaissements" OR $requetes_multiples["solde"]["secteur"]=="tva" OR $requetes_multiples["solde"]["secteur"]=="journal")
		{
			$requetes_multiples["$nom"]["dateac"]="{$requetes_multiples["$nom"]["date_annee"]}-{$requetes_multiples["$nom"]["date_mois"]}-{$requetes_multiples["$nom"]["date_jour"]}";
			unset ($requetes_multiples["$nom"]["date_jour"]);
			unset ($requetes_multiples["$nom"]["date_mois"]);
			unset ($requetes_multiples["$nom"]["date_annee"]);
		}
	
		elseif ($requetes_multiples["solde"]["retour"]=="biblio/creer_livre")
		{
			if($requetes_multiples["$nom"]["anneedate_edition"])
			{
				if (!$requetes_multiples["$nom"]["jourdate_edition"]) $requetes_multiples["$nom"]["jourdate_edition"] = '1';
				if (!$requetes_multiples["$nom"]["moisdate_edition"]) $requetes_multiples["$nom"]["moisdate_edition"] = '1';
			}
			$requetes_multiples["$nom"]["date_edition"]="{$requetes_multiples["$nom"]["anneedate_edition"]}-{$requetes_multiples["$nom"]["moisdate_edition"]}-{$requetes_multiples["$nom"]["jourdate_edition"]}";
			unset ($requetes_multiples["$nom"]["jourdate_edition"]);
			unset ($requetes_multiples["$nom"]["moisdate_edition"]);
			unset ($requetes_multiples["$nom"]["anneedate_edition"]);
		}
	
		elseif ($requetes_multiples["solde"]["retour"]=="modifier_delai" || $requetes_multiples["solde"]["retour"]=="modifier_delai?close=true")
		{
			$requetes_multiples["$nom"]["date_debut"]="{$requetes_multiples["$nom"]["annee_debut"]}-{$requetes_multiples["$nom"]["mois_debut"]}-{$requetes_multiples["$nom"]["jour_debut"]}";
			$requetes_multiples["$nom"]["date_fin"]="{$requetes_multiples["$nom"]["annee_fin"]}-{$requetes_multiples["$nom"]["mois_fin"]}-{$requetes_multiples["$nom"]["jour_fin"]}";
			unset ($requetes_multiples["$nom"]["jour_debut"]);
			unset ($requetes_multiples["$nom"]["mois_debut"]);
			unset ($requetes_multiples["$nom"]["annee_debut"]);
			unset ($requetes_multiples["$nom"]["jour_fin"]);
			unset ($requetes_multiples["$nom"]["mois_fin"]);
			unset ($requetes_multiples["$nom"]["annee_fin"]);
		}
		
		elseif ($requetes_multiples["solde"]["retour"]=="modifier_rdv" || $requetes_multiples["solde"]["retour"]=="modifier_rdv?close=true")
		{
			$requetes_multiples["$nom"]["date_debut"]="{$requetes_multiples["$nom"]["annee_debut"]}-{$requetes_multiples["$nom"]["mois_debut"]}-{$requetes_multiples["$nom"]["jour_debut"]}";
			$requetes_multiples["$nom"]["date_fin"]="{$requetes_multiples["$nom"]["annee_fin"]}-{$requetes_multiples["$nom"]["mois_fin"]}-{$requetes_multiples["$nom"]["jour_fin"]}";
			$requetes_multiples["$nom"]["repete_fin"]="{$requetes_multiples["$nom"]["annee_repete_fin"]}-{$requetes_multiples["$nom"]["mois_repete_fin"]}-{$requetes_multiples["$nom"]["jour_repete_fin"]}";
			$requetes_multiples["$nom"]["heure_debut"]="{$requetes_multiples["$nom"]["heure_debut"]}:{$requetes_multiples["$nom"]["minute_debut"]}";
			$requetes_multiples["$nom"]["heure_fin"]="{$requetes_multiples["$nom"]["heure_fin"]}:{$requetes_multiples["$nom"]["minute_fin"]}";
			unset ($requetes_multiples["$nom"]["jour_debut"]);
			unset ($requetes_multiples["$nom"]["mois_debut"]);
			unset ($requetes_multiples["$nom"]["annee_debut"]);
			unset ($requetes_multiples["$nom"]["minute_debut"]);
			unset ($requetes_multiples["$nom"]["jour_fin"]);
			unset ($requetes_multiples["$nom"]["mois_fin"]);
			unset ($requetes_multiples["$nom"]["annee_fin"]);
			unset ($requetes_multiples["$nom"]["minute_fin"]);
			unset ($requetes_multiples["$nom"]["jour_repete_fin"]);
			unset ($requetes_multiples["$nom"]["mois_repete_fin"]);
			unset ($requetes_multiples["$nom"]["annee_repete_fin"]);
		}
	
		elseif ($requetes_multiples["solde"]["retour"]=="agenda")
		{
			$requetes_multiples["$nom"]["heure_debut"]="{$requetes_multiples["$nom"]["heure_debut"]}:{$requetes_multiples["$nom"]["minute_debut"]}";
			$requetes_multiples["$nom"]["heure_fin"]="{$requetes_multiples["$nom"]["heure_fin"]}:{$requetes_multiples["$nom"]["minute_fin"]}";
			unset ($requetes_multiples["$nom"]["minute_debut"]);
			unset ($requetes_multiples["$nom"]["minute_fin"]);
		}
	
		elseif($requetes_multiples["solde"]["action"]=="modifier_dossier")
		{
			if(isset($requetes_multiples["$nom"]["date_jour_ouverture"]) AND isset($requetes_multiples["$nom"]["date_mois_ouverture"]) AND isset($requetes_multiples["$nom"]["date_annee_ouverture"])) $requetes_multiples["$nom"]["dateouverture"]="{$requetes_multiples["$nom"]["date_annee_ouverture"]}-{$requetes_multiples["$nom"]["date_mois_ouverture"]}-{$requetes_multiples["$nom"]["date_jour_ouverture"]}";
			if(isset($requetes_multiples["$nom"]["date_jour_archive"]) AND isset($requetes_multiples["$nom"]["date_mois_archive"]) AND isset($requetes_multiples["$nom"]["date_annee_archive"])) $requetes_multiples["$nom"]["datearchivage"]="{$requetes_multiples["$nom"]["date_annee_archive"]}-{$requetes_multiples["$nom"]["date_mois_archive"]}-{$requetes_multiples["$nom"]["date_jour_archive"]}";
			unset ($requetes_multiples["$nom"]["date_jour_ouverture"]);
			unset ($requetes_multiples["$nom"]["date_mois_ouverture"]);
			unset ($requetes_multiples["$nom"]["date_annee_ouverture"]);
			unset ($requetes_multiples["$nom"]["date_jour_archive"]);
			unset ($requetes_multiples["$nom"]["date_mois_archive"]);
			unset ($requetes_multiples["$nom"]["date_annee_archive"]);
		}
		
		//traitement des ajouts (en l'état BD slt)
		foreach($requetes_multiples["$nom"] as $a => $b)
		{
// 			echo "<br>$nom: $a=> $b";
			if(preg_match("#^(auteur.*)bis$#", $a, $r))
			{
				$n = $r[1];
// 				echo " $a => $n";
 				if($b != "") $requetes_multiples["$nom"][$n] = $b;
				unset($requetes_multiples["$nom"]["$a"]);
			}
		}
	
		if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="dupliquer_dossier" )
		{
			$requete["exclusion"]=array("retour", "action");
			$requete["action"]="insert into";
			$requete["table"]=$_SESSION["session_avdb"];
			$le = strftime("%Y-%m-%d");
			$requete["texteb"] = "insert into {$_SESSION["session_tfdb"]} (nodossier, soustraitant, prixhoraire, np, nple, mp, mple) select '{PREGLID}', soustraitant, prixhoraire, '{$_SESSION["user"]}', '$le', '{$_SESSION["user"]}', '$le' from {$_SESSION["session_tfdb"]} where nodossier like '{$requetes_multiples["solde"]["liea"]}';";
		}
		
		if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="nouveau_dossier" )
		{
			$requete["exclusion"]=array("retour", "action", "drop_reqb", "noadresse", "nopa", "etape");
			$requete["action"]="insert into";
			$requete["table"]="adresses";
			if($requetes_multiples["solde"]["drop_reqb"]) $requete["action"] = True;
			if($requetes_multiples["solde"]["finish"])
			{
				$le = strftime("%Y-%m-%d");
				$requete["textec"] = "insert into {$_SESSION["session_opdb"]} set op='{$doc->lang["config_modify_options_ouverture"]}', tempsop='{$_SESSION["optionGen"]["ouverture"]}', nodossier='{PREGLID}', dateop='$le', np='{$_SESSION["user"]}', nple='$le', mp='{$_SESSION["user"]}', mple='$le'";
				unset($le);
			}
		}
		if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="maj" )
		{
			$requete["exclusion"]=array("retour", "action", "drop_reqb", "nodossier");
			$requete["action"]="update";
			$requete["table"]=$_SESSION["session_avdb"];
			$requete["where"]="nodossier";
// 			if($requetes_multiples["solde"]["drop_reqb"]) $requete["action"] = True;
		}
		if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="nouveau_champ" )
		{
			$requete["exclusion"]=array("retour", "action", "champ", "nodossier");
			$requete["action"]="insert into";
			$requete["table"]="adresses";
			if($requetes_multiples["solde"]["drop_reqb"]) $requete["action"] = True;
// 			if($requetes_multiples["solde"]["drop_reqb"]) $requete["action"] = True;
		}
		if($requetes_multiples["solde"]["retour"]=="creer_client")
		{
			$requete["exclusion"]=array("retour", "action", "etape", "noadresse");
			$requete["action"]="insert into";
			$requete["table"]="adresses";
		}
		
		if($requetes_multiples["solde"]["action"]=="insert" AND $requetes_multiples["$nom"]["nodossier"])
		{
			$requete["exclusion"]=array("secteur", "retour", "action", "debut");
			$requete["action"]="insert into";
			$requete["table"]=$_SESSION["session_opdb"];
		}
		
		if($requetes_multiples["solde"]["action"]=="update" AND $requetes_multiples["$nom"]["nodossier"])
		{
			$requete["exclusion"]=array("secteur", "retour", "action", "debut", "print");
			$requete["action"]="update";
			$requete["table"]=$_SESSION["session_opdb"];
			$requete["where"]="idop";
		}
		
		if($requetes_multiples["solde"]["retour"]=="adresses/modifier")
		{
			$requete["exclusion"]=array("retour");
			$requete["action"]="update";
			$requete["table"]="adresses";
			$requete["where"]="id";
		}
		
		if($requetes_multiples["solde"]["retour"]=="modules/recherche/zefix")
		{
			$requete["exclusion"]=array("retour", "idpers", "nodossier");
			$requete["action"]="update";
			$requete["table"]="adresses";
			$requete["where"]="id";
			if(substr($requetes_multiples["solde"]["id"], 0, 2) == "no")
			{
				$requete["action"] = "insert into";
				$requete["exclusion"][] = "id";
				$requete["texteb"] = "update {$_SESSION["session_avdb"]} set {$requetes_multiples["solde"]["id"]} = '{PREGLID}' where nodossier = '{$requetes_multiples["solde"]["nodossier"]}'";
			}
// 			foreach($requetes_multiples["solde"] as $a => $b)
// 			{
// // 				echo "<br>Traitement de $a ($b)";
// 				if(!in_array($a, array("retour", "id")))
// 				{
// 					if(!preg_match("#_check$#", $a))
// 					{
// 						if(!$requetes_multiples["solde"]["{$a}_check"] ) unset($requetes_multiples["solde"]["$a"]);
// 					}
// 				}
// 			}
// 			foreach($requetes_multiples["solde"] as $a => $b)
// 			{
// 				if(preg_match("#_check$#", $a)) unset($requetes_multiples["solde"]["$a"]);
// 			}
// 			echo "<br>";
		}
		
		if($requetes_multiples["solde"]["retour"]=="biblio/creer_livre" && $requetes_multiples["solde"]["action"] == "modify")
		{
			$requete["exclusion"]=array("retour", "action");
			$requete["action"]="update";
			$requete["table"]="biblio";
			$requete["where"]="no_fiche";
		}
		
		if($requetes_multiples["solde"]["retour"]=="biblio/creer_livre" && $requetes_multiples["solde"]["action"] == "create")
		{
			$requete["exclusion"]=array("retour", "action");
			$requete["action"]="insert into";
			$requete["table"]="biblio";
			$requetes_multiples["solde"]["collection"] = $_SESSION["biblioNom"];
			$requetes_multiples["solde"]["type"] = $_SESSION["biblioType"];
		}
		
		if(($requetes_multiples["solde"]["retour"]=="modifier_rdv" || $requetes_multiples["solde"]["retour"]=="modifier_rdv?close=true") AND $requetes_multiples["solde"]["action"]!="new")
		{
			$requete["exclusion"]=array("retour");
			$requete["action"]="update";
			$requete["table"]="rdv";
			$requete["where"]="id";
		}
		
		if(($requetes_multiples["solde"]["retour"]=="modifier_delai" || $requetes_multiples["solde"]["retour"]=="modifier_delai?close=true") AND $requetes_multiples["solde"]["action"]!="new")
		{
			$requete["exclusion"]=array("retour");
			$requete["action"]="update";
			$requete["table"]="delais";
			$requete["where"]="id";
		}
		
		if(($requetes_multiples["solde"]["retour"]=="modifier_rdv" || $requetes_multiples["solde"]["retour"]=="modifier_rdv?close=true") AND $requetes_multiples["solde"]["action"]=="new")
		{
			$requete["exclusion"]=array("retour", "action");
			$requete["action"]="insert into";
			$requete["table"]="rdv";
		}
		
		if(($requetes_multiples["solde"]["retour"]=="modifier_delai" || $requetes_multiples["solde"]["retour"]=="modifier_delai?close=true") AND $requetes_multiples["solde"]["action"]=="new")
		{
			$requete["exclusion"]=array("retour", "action");
			$requete["action"]="insert into";
			$requete["table"]="delais";
			$requete["where"]="id";
		}
		
		if($requetes_multiples["solde"]["retour"]=="agenda")
		{
			$requete["exclusion"]=array("retour", "date_cours", "type");
			$requete["action"]="update";
			$requete["table"]="rdv";
			$requete["where"]="id";
		}
		
		if($requetes_multiples["solde"]["action"]=="modifier_mailing")
		{
			$requete["exclusion"]=array("retour", "subretour", "action", "type", "nodossier", "id");
			$requete["action"]="insert into";
			$requete["table"]="mailing";
		}
		
		if($requetes_multiples["solde"]["action"]=="delete" AND $requetes_multiples["solde"]["confirm"]=="on")
		{
			$requete["exclusion"]=array("secteur", "retour", "action", "debut");
			$requete["action"]="delete from";
			if($requetes_multiples["solde"]["retour"]=="adresses/resultat")
			{
				$requete["table"]="adresses";
				$requete["where"]="id";
			}elseif($requetes_multiples["solde"]["retour"]=="biblio/liste_ouvrages"){
				$requete["table"]="biblio";
				$requete["where"]="no_fiche";
			}elseif($requetes_multiples["solde"]["retour"]=="modifier_delai" || $requetes_multiples["solde"]["retour"]=="modifier_delai?close=true"){
				$requete["table"]="delais";
				$requete["where"]="id";
// 				$requetes_multiples["solde"]["retour"]="liste_delais";
			}elseif($requetes_multiples["solde"]["retour"]=="modifier_rdv" || $requetes_multiples["solde"]["retour"]=="modifier_rdv?close=true"){
				$requete["table"]="rdv";
				$requete["where"]="id";
				$doc->tab_affiche($requetes_multiples);
				if($requetes_multiples["solde"]["notlike"]) $requete["where"] = "reserveid";
// 				$requetes_multiples["solde"]["retour"]="agenda";
			}else{
				$requete["table"]=$_SESSION["session_opdb"];
				$requete["where"]="idop";
			}
		}
		
		
		//$requete="delete from {$_SESSION["session_opdb"]} where idop like '{$_POST["idop"]}'";
		
		if($requetes_multiples["solde"]["action"]=="creer_fiche")
		{
			$requete["exclusion"]=array("action", "nodossier", "id", "retour", "subretour");
			$requete["action"]="insert into";
			$requete["table"]="adresses";
			$requete["where"]="id";
		}
		
		if($requetes_multiples["solde"]["action"]=="modifier_fiche")
		{
			$requete["exclusion"]=array("action", "nodossier", "id", "retour", "subretour");
			$requete["action"]="update";
			$requete["table"]="adresses";
			$requete["where"]="id";
		}
		
		if($requetes_multiples["solde"]["action"]=="modifier_dossier")
		{
			$requete["exclusion"]=array("action", "nodossier", "retour", "print");
			$requete["action"]="update";
			$requete["table"]="{$_SESSION["session_avdb"]}";
			$requete["where"]="nodossier";
		}
		
		//doodle
		if((!preg_match("#delete#", "{$requetes_multiples["solde"]["action"]}") OR $requetes_multiples["solde"]["confirm"]=="on") AND isset($requetes_multiples["solde"]["manual"]))
		{
			//doodle manuel
			list($a, $b) = preg_split('#__#', $requetes_multiples["solde"]["manual"]);
			if($a) $requete["textec"]= "update rdv set doodleset = '$b' where doodle like '$a'";
			$requete["exclusion"][]="manual";
		}

		//pour les variables POST de toute façon exclues
		$requete["exclusion"][]="nb_affiche";
		$requete["exclusion"][]="norequete";
		if(!preg_match("#delete#", "{$requetes_multiples["solde"]["action"]}") OR $requetes_multiples["solde"]["confirm"]=="on")
		{
			if($requete["action"]==="delete from") $requete["texte"]="{$requete["action"]} {$requete["table"]}";
			else
			{
				$mp=$_SESSION["user"];
				$mple = strftime("%Y-%m-%d");
				$np=($requete["action"]==="insert into") ? ", nple='$mple', np='$mp'" : "";
				$requete["texte"]="{$requete["action"]} {$requete["table"]} set mple='$mple', mp='$mp' $np";
				foreach($requetes_multiples["$nom"] as $a => $b)
				{
					if($a == 'reserveid' && $b == 'AAJOUTER')
					{
						$requete["texteb"]= "update rdv set reserveid = LAST_INSERT_ID() where id like LAST_INSERT_ID()";
					}
					elseif(!in_array("$a", $requete["exclusion"]))
					{
						$b2 = addslashes(stripslashes($b));
						if($debugMode) echo "<br>Setting: \$requetes_multiples[\"$nom\"]' $a -> $b";
						$requete["texte"]=$requete["texte"].", $a = '$b2'";
						if($a == $requete["where"]) $lastmodified = $b;
					}
				}
// 				if($debugMode) echo  "<br>".$requete["texte"];
			}
			if($requete["action"]==="update" || $requete["action"]==="delete from")
			{
				$where=$requete["where"];
				if(trim($where) == "")
				{
					$debugMode="bloque";
					$doc->tab_affiche($requete);
				}
				if($requetes_multiples["solde"]["notlike"])
				{
					list($a, $b) = preg_split('#__#', $requetes_multiples["solde"]["notlike"]);
					$whereplus = "AND $a not like '$b'";
					$requete["texteb"]= "update rdv set reserveid = 0 where $where like '{$requetes_multiples["$nom"]["$where"]}'";

				}
				$requete["texte"]=$requete["texte"]." where $where like '{$requetes_multiples["$nom"]["$where"]}' $whereplus";
			}
			if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="nouveau_dossier")
			{
				$now = date("Y-m-d",time());
				$np = $_SESSION["user"];
				$cols = "";
				$values = "";
				$valuesb = array("np" => $np, "nple" => $now, "mp" => $np, "mple" => $now, "tvadossier" => $_SESSION["optionGen"]["tx_tva"], "prixhoraire" => $_SESSION["optionGen"]["prix_defaut"] , "dateouverture" => $now);
				foreach(array("noaction", "nopa", "noaut", "noca", "nopj") as $noinsert) if($requetes_multiples["solde"]["$noinsert"]) $valuesb[$noinsert] = $requetes_multiples["solde"]["$noinsert"];
				
				
// 				if($requetes_multiples["solde"]["noadresse"])
// 				{
// 					$valuesb["noadresse"] = $requetes_multiples["solde"]["noadresse"];
// 					if(!$requetes_multiples["solde"]["drop_reqb"]) $valuesb["nopa"] = "{PREGLID}";
// 				}
// 				else
// 				{
// 					$valuesb["noadresse"] = "{PREGLID}";
// 				}
				
				
				
				if($requetes_multiples["solde"]["noadresse"])
				{
					$valuesb["noadresse"] = $requetes_multiples["solde"]["noadresse"];
					if($requetes_multiples["solde"]["nopa"])
					{
						$valuesb["nopa"] = $requetes_multiples["solde"]["nopa"];
						if(!$requetes_multiples["solde"]["drop_reqb"]) $valuesb["noca"] = "{PREGLID}";
					}
					else
					{
						if(!$requetes_multiples["solde"]["drop_reqb"]) $valuesb["nopa"] = "{PREGLID}";
// 						$valuesb["nopa"] = "{PREGLID}";
					}
				}
				else
				{
					$valuesb["noadresse"] = "{PREGLID}";
				}
				foreach($valuesb as $a => $b)
				{
					if($cols) $cols .= ", ";
					if($values) $values .= ", ";
					$cols .= $a;
					$values .= "'$b'";
				}
				$requete["texteb"] = "insert into {$_SESSION["session_avdb"]} ($cols) VALUES ($values)";
			}
			if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="nouveau_champ")
			{
				$mple = date("Y-m-d",time());
				$mp   = $_SESSION["user"];
				if($requetes_multiples["solde"]["drop_reqb"])
				{
					$requete["action"] = True;
					$valChamp = '0';
					
				}
				else $valChamp = '{PREGLID}';
				$requete["texteb"] = "update {$_SESSION["session_avdb"]} set mp='$mp', mple='$mple', {$requetes_multiples["solde"]["champ"]}='$valChamp' where nodossier = '{$requetes_multiples["solde"]["nodossier"]}'";

			}
		}

		if($requetes_multiples["solde"]["confirm_file"]=="on" AND $requetes_multiples["solde"]["action"]=="delete_file")
		{
			$requete["texte"]="delete from {$_SESSION["session_avdb"]} where nodossier like '{$requetes_multiples["$nom"]["nodossier"]}'";
			$requete["texteb"]="delete from {$_SESSION["session_opdb"]} where nodossier like '{$requetes_multiples["$nom"]["nodossier"]}'";
			$requete["textec"]="delete from {$_SESSION["session_tfdb"]} where nodossier like '{$requetes_multiples["$nom"]["nodossier"]}'";
		}
		if($requete["texte"]) $requete["texteMultiples"][] = $requete["texte"];
		if($requete["texte"] && $debugMode != "bloque")
		{
			if($requete["action"] === True)
			{
				$verif = True;
				$requete["texte"] = "<i>Set to True</i>";
			}
			elseif($requete["action"] === False) $verif = False;
			else
			{
				$verif=mysqli_query($doc->mysqli, "{$requete["texte"]}");
				echo mysqli_error($doc->mysqli); 
				$lid=mysqli_insert_id($doc->mysqli);
				if(!$lid) $lid = $lastmodified;
			}
			$texte = $verif? "<span style='color:green'>OK (lid: $lid)</span>":"<span style='color:red'>KO (".mysqli_error($doc->mysqli).")</span>";
		}
		if($requete["texteb"] && $debugMode != "bloque")
		{
			$requete["texteb"] = preg_replace("#{PREGLID}#", "$lid", $requete["texteb"]);
			$verifb=mysqli_query($doc->mysqli, $requete["texteb"]);
			echo mysqli_error($doc->mysqli); 
			$lidb = mysqli_insert_id($doc->mysqli);
			$texteb = $verifb? "(b) <span style='color:green'>OK (lid: $lid; lidb:$lidb)</span>":"<span style='color:red'>KO (".mysqli_error($doc->mysqli).")</span>";
		}
		if($requete["textec"] && $debugMode != "bloque")
		{
			$requete["textec"] = preg_replace("#{PREGLID}#", "$lidb", $requete["textec"]);
			$verifc = mysqli_query($doc->mysqli, $requete["textec"]);
			echo mysqli_error($doc->mysqli); 
			$lidc = mysqli_insert_id($doc->mysqli);
			$textec = $verifc? "(c) <span style='color:green'>OK (lid: $lid; lidb:$lidb; lidc:$lidc)</span>":"<span style='color:red'>KO (".mysqli_error($doc->mysqli).")</span>";
		}
		
	}
}


$global_ok=FALSE;
if($requetes_multiples["solde"]["confirm_file"]=="on" AND $requetes_multiples["solde"]["action"]=="delete_file" AND $verif AND $verifb)
{
	$global_ok=TRUE;
	$requetes_multiples["solde"]["retour"]="resultat_recherche";
}
elseif($requetes_multiples["solde"]["confirm_file"]!="on" AND $verif) $global_ok=TRUE;

if($debugMode)
{
	echo "\n<br>"; 
	echo "\n<br>Requetes:";
	echo "\n<ol style='list-style-type:lower-alpha'>";
	foreach($requete["texteMultiples"] as $texteSimple) echo "\n<li>".$doc->beautifyMysql($texteSimple, false, false, 3). " $texte</li>"; 
	echo "\n<li>".$doc->beautifyMysql($requete["texteb"], false, false, 3). " $texteb</li>"; 
	echo "\n<li>".$doc->beautifyMysql($requete["textec"], false, false, 3). " $textec</li>";
	echo "\n</ol>";
	unset($global_ok);
}
if($global_ok)
{
	$onload="document.forms[0].submit()";
}
$doc->body("1", "$onload");
echo "<br>
<br>
<h1>";
	if($requetes_multiples["solde"]["action"]=="delete" AND !isset($requetes_multiples["solde"]["confirm"]) AND !isset($requetes_multiples["solde"]["confirm_file"])) echo $doc->lang["maj_op_h2"];
	else echo $doc->lang["maj_op_h1"];
echo "</h1>
<br>
<br>";
$condition=FALSE;
if($verif) $condition = TRUE;
if($requete["texteb"] && ! $verifb) $condition = FALSE;
if($requete["textec"] && ! $verifc) $condition = FALSE;
if($condition)
{
	if($requetes_multiples["solde"]["retour"]=="adresses/modifier" || $requetes_multiples["solde"]["retour"]=="adresses/resultat" || $requetes_multiples["solde"]["retour"]=="agenda" || $requetes_multiples["solde"]["retour"]=="modifier_delai" || $requetes_multiples["solde"]["retour"]=="modifier_delai?close=true" || $requetes_multiples["solde"]["retour"]=="modifier_rdv" || $requetes_multiples["solde"]["retour"]=="modifier_rdv?close=true") echo $doc->lang["maj_op_insere_f"];
	else echo $doc->lang["maj_op_insere"];
	if($requetes_multiples["solde"]["action"]=="delete_file") echo "<h2>{$doc->lang["maj_op_ok_1"]}, {$doc->lang["maj_op_ok_2"]}</h2>";
}else{
	//print "<br>" .mysqli_error($doc->mysqli)."<br>";
	if(($requetes_multiples["solde"]["action"]=="insert" || $requetes_multiples["solde"]["action"]=="modify") AND !$requetes_multiples["solde"]["nodossier"]) echo $doc->lang["maj_op_vide"];
	if($requetes_multiples["solde"]["action"]=="delete_file" AND $requetes_multiples["solde"]["confirm_file"]=="on")
	{
		if($verif) echo "<h2>{$doc->lang["maj_op_ok_1"]} {$requetes_multiples["solde"]["nodossier"]}</h2>"; else echo "<h2 class=attention>{$doc->lang["maj_op_err_1"]}</h2>";
		if($verifb) echo "<h2>{$doc->lang["maj_op_ok_2"]} {$requetes_multiples["solde"]["nodossier"]}</h2>"; else echo "<h2 class=attention>{$doc->lang["maj_op_err_2"]}</h2>";
	}
}
if($veto) 
{
	echo "<h2>{$doc->lang["maj_op_faux"]}</h2>";
	$requetes_multiples["solde"]["nodossier"]=$ancien_nodossier;
}

echo "<br>
<br>";

if($requetes_multiples["solde"]["timestamp_debut"] && $requetes_multiples["solde"]["timestamp_fin"])
{
	$doc->form_global_var["timestamp_debut"] = $requetes_multiples["solde"]["timestamp_debut"];
	$doc->form_global_var["timestamp_fin"] = $requetes_multiples["solde"]["timestamp_fin"];
	
}
// 	$doc->tab_affiche($doc->form_global_var);

//gestion des requêtes de destruction avant confirmation

if(preg_match("#delete#", $requetes_multiples["solde"]["action"]) AND !isset($requetes_multiples["solde"]["confirm"]) AND !isset($requetes_multiples["solde"]["confirm_file"]))
{
	echo "<h2 class=attention>{$doc->lang["maj_op_confirm_h1"]}</h2>";
	if($requetes_multiples["solde"]["action"]=="delete_file") echo "<h2 class=attention>{$doc->lang["maj_op_confirm_h2"]}</h2>";
	echo "<table><tr><td>";
	if($requetes_multiples["solde"]["action"]=="delete")
	{
		echo "<form style=\"display:inline\" action=\"maj_op.php\" method=\"post\">";
		echo $doc->button($doc->lang["general_oui"], "", "buttonattention");
		echo $doc->input_hidden("nodossier", "", "{$requetes_multiples["solde"]["nodossier"]}");
		echo $doc->input_hidden("no_fiche", "", "{$requetes_multiples["solde"]["no_fiche"]}");
		echo $doc->input_hidden("retour", "", "{$requetes_multiples["solde"]["retour"]}");
		echo $doc->input_hidden("debut", "", "{$requetes_multiples["solde"]["debut"]}");
		echo $doc->input_hidden("secteur", "", "{$requetes_multiples["solde"]["secteur"]}");
		echo $doc->input_hidden("timestamp_debut", "", "{$requetes_multiples["solde"]["timestamp_debut"]}");
		echo $doc->input_hidden("timestamp_fin", "", "{$requetes_multiples["solde"]["timestamp_fin"]}");
		echo $doc->input_hidden("action", "", "delete");
		echo $doc->input_hidden("notlike", "", "{$requetes_multiples["solde"]["notlike"]}");
		echo $doc->input_hidden("confirm", "", "on");
		echo $doc->input_hidden("manual", 1);
		foreach($requetes_multiples as $nom => $array)
		{
			if($nom != "solde" || $multireq_verif == FALSE)
			{
				foreach($array as $a => $b)
				{
					if($a == "idop" || $a == "id" || $a == "reserveid")
					{
// 						echo "<br><br>a vaut $a<br>";
						$new_a="$a-multireq-".$array["$a"];
						$new_c="norequete-multireq-".$array["$a"];
						echo $doc->input_hidden("$new_a", "", $b);
						echo $doc->input_hidden("$new_c", "", "on");
					}
				}
			}
		}
		echo "</form>";
	}else {
		echo "<form style=\"display:inline\" action=\"maj_op.php\" method=\"post\">";
		echo $doc->button($doc->lang["general_oui"], "", "buttonattention");
		echo $doc->input_hidden("nodossier", "", "{$requetes_multiples["solde"]["nodossier"]}");
		echo $doc->input_hidden("id", "", "{$requetes_multiples["solde"]["id"]}");
		echo $doc->input_hidden("retour", "", "{$requetes_multiples["solde"]["retour"]}");
		echo $doc->input_hidden("action", "", "delete_file");
		echo $doc->input_hidden("confirm_file", "", "on");
		foreach($requetes_multiples as $nom => $array)
		{
			if($nom != "solde" || $multireq_verif == FALSE)
			{
				foreach($array as $a => $b)
				{
					if($a == "idop" || $a == "id") 
					{
						//echo "<br><br>a vaut $a<br>";
						$new_a="$a-multireq-".$array["$a"];
						$new_c="norequete-multireq-".$array["$a"];
						echo $doc->input_hidden("$new_a", "", $b);
						echo $doc->input_hidden("$new_c", "", "on");
					}
				}
			}
		}
		echo "</form>";
	}
	//echo $doc->form("maj_op.php", "$general_oui", "", "attention", "", , ,, , , , , "id", "{$requetes_multiples["solde"]["id"]}"); 
	//else echo $doc->form("maj_op.php", "$general_oui", "", "attention", "", "nodossier", "{$_POST["nodossier"]}", "retour", "{$requetes_multiples["solde"]["retour"]}", "action", "delete_file", "confirm_file", "on"); 
	echo "</td><td>";
	$retour=(preg_match("#\?#", $requetes_multiples["solde"]["retour"])) ? preg_replace("#\?#", ".php?", $requetes_multiples["solde"]["retour"]):"{$requetes_multiples["solde"]["retour"]}.php";

	echo $doc->form("$retour", "{$doc->lang["general_non"]}", "{$doc->lang["general_non_accesskey"]}", "", "", "nodossier", "{$requetes_multiples["solde"]["nodossier"]}", "id", "{$requetes_multiples["solde"]["id"]}", "debut", "{$requetes_multiples["solde"]["debut"]}", "secteur", "{$requetes_multiples["solde"]["secteur"]}", "timestamp_debut", "{$requetes_multiples["solde"]["timestamp_debut"]}", "timestamp_fin", "{$requetes_multiples["solde"]["timestamp_fin"]}");
	echo "</td></tr></table>";
}

//confirmation des requêtes exécutées

else{
	if($requetes_multiples["solde"]["retour"] == "creer_client")
	{
		if($requetes_multiples["solde"]["etape"] == "4") echo $doc->form("creer_dossier", "OK", "o", "", "", "action", $requetes_multiples["solde"]["action"], "noadresse", $requetes_multiples["solde"]["noadresse"], "nopa", $lid, "etape", $requetes_multiples["solde"]["etape"]);
		if($requetes_multiples["solde"]["etape"] == "2") echo $doc->form("creer_dossier", "OK", "o", "", "", "action", $requetes_multiples["solde"]["action"], "noadresse", $lid, "etape", $requetes_multiples["solde"]["etape"]);
		die();
	}
	if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="dupliquer_dossier" )
	{
		echo $doc->form("modifier_donnees", "OK", "o", "", "", "nodossier", $lid, "new", "on");
		die();
	}
	if($requetes_multiples["solde"]["retour"]=="modifier_donnees" AND $requetes_multiples["solde"]["action"]=="nouveau_dossier" )
	{
		echo $doc->form("modifier_donnees", "OK", "o", "", "", "nodossier", $lidb, "new", "on", "noadresse", $requetes_multiples["solde"]["noadresse"], "nopa", $lid);
		die();
	}
	if($requetes_multiples["solde"]["retour"]=="adresses/modifier" AND $requetes_multiples["solde"]["action"]=="creer_fiche" )
	{
// 		die("lid: $lid");
		echo $doc->form("adresses/modifier", "OK", "o", "", "", "id", $lid);
		die();
	}
// 	$doc->tab_affiche($requetes_multiples["solde"]);
	if($requetes_multiples["solde"]["action"] == "new" AND ($requetes_multiples["solde"]["retour"]=="modifier_rdv" || $requetes_multiples["solde"]["retour"]=="modifier_rdv?close=true" || $requetes_multiples["solde"]["retour"]=="modifier_delai" || $requetes_multiples["solde"]["retour"]=="modifier_delai?close=true") AND !($requetes_multiples["solde"]["id"]))
	{
		$requetes_multiples["solde"]["id"] = $lid;
// 		echo "voilà, $lid";
	}
	if($requetes_multiples["solde"]["action"]=="delete" && ($requetes_multiples["solde"]["retour"]=="modifier_rdv?close=true" || $requetes_multiples["solde"]["retour"]=="modifier_delai?close=true")) $requetes_multiples["solde"]["retour"] .= "&hasDeleted=true";
	$retour=(preg_match("#\?#", $requetes_multiples["solde"]["retour"])) ? preg_replace("#\?#", ".php?", $requetes_multiples["solde"]["retour"]):"{$requetes_multiples["solde"]["retour"]}.php";
	if($requetes_multiples["solde"]["retour"] == "biblio/creer_livre")
	{
		if($requetes_multiples["solde"]["action"] == "modify") $lid = $requetes_multiples["solde"]["no_fiche"];
		echo $doc->form("$retour", "OK", "o", "", "", "no_fiche", "$lid", "action", "modify");
	}
	else echo $doc->form("$retour#lastid", "OK", "o", "", "", "nodossier", "{$requetes_multiples["solde"]["nodossier"]}", "debut", "{$requetes_multiples["solde"]["debut"]}", "secteur", "{$requetes_multiples["solde"]["secteur"]}", "id", "{$requetes_multiples["solde"]["id"]}", "type", "{$requetes_multiples["solde"]["type"]}", "date_cours", "{$requetes_multiples["solde"]["date_cours"]}", "lastid", $lid, "subretour", $requetes_multiples["solde"]["subretour"]);
}

$doc->close();
?>
