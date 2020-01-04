<?php
require_once("../inc/autoload.php");
session_start();
foreach($_POST as $a => $b)
{
	if(preg_match("#__point__#", $a))
	{
		unset($_POST[$a]);
		$a = preg_replace("#__point__#", ".", $a);
		$_POST[$a] = $b;
	}
}
$doc=new prolawyer;
error_reporting(7);

$doc->partTables=array("clients", "op", "tarifs"); //plutôt que de répéter cette information à chaque fois, ce qui peut générer des oublis...
// $doc->tab_affiche();
// die();

if($_POST["new_name"]) $_POST["new_name"] = preg_replace("# #", "_", $_POST["new_name"]);

// $doc->tab_affiche();

if($_POST["global_user"])
{
	$_POST["mode"] = "partner";
	foreach($_POST as $nom => $val)
	{
		if(preg_match("#^ALLPROLAWYERUSER(__.*)#", $nom, $regs))
		{
			foreach($doc->liste_utilisateurs() as $init => $ar)
			{
				$nombis=$init.$regs[1];
				$_POST["$nombis"] = $val;
			}
			unset ($_POST["$nom"]);
		}
	}
}

//action générique pour setconfirm

if($_POST["setconfirm"] && $_POST["action"] != "update")
{
	if($_POST["init"]) $userName = $doc->init_to_name($_POST["init"]);
	elseif($_POST["user_delete"]) $userName = $_POST["user_delete"];
	else $userName = "(?)";
	$doc->title_name = "{$doc->lang["config_modify_delete_utilisateur"]} $userName";
// 	$doc->tab_affiche();
	$doc->title();
	$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
	$doc->entete();
	$doc->form_global_var = $_POST;
	$doc->exclusion[] = "setconfirm";
	echo "\n<h1>{$doc->title_name}<br>{$doc->lang["supprimer_dossier_confirm_h11"]}";
	echo "\n<br><font color=#ff0000>".$doc->lang["supprimer_dossier_confirm_h12"]."</font></h1>";
	echo "\n<table><tr><td>";
	echo $doc->form("config/create.php", $doc->lang["general_oui"], "", "attention", "");
	echo "</td><td>";
	unset($doc->form_global_var); 
	echo $doc->form("config/modify.php", $doc->lang["general_non"], $doc->lang["general_non_accesskey"], "", "formretour<td>", "mode", $_POST["mode"]);
	echo "</td></tr></table>";
	die();
}



//mise à jour pour le mode partner
if($_POST["mode"] == "partner" || $_POST["mode"] == "droits")
{
	if($_POST["create"])
	{
		foreach(array("nom", "init") as $value) $_POST["$value"] = trim($_POST["$value"]);
		$query="select * from utilisateurs where nom like '{$_POST["nom"]}' or initiales like '{$_POST["init"]}'";
		$exec1=mysqli_query($doc->mysqli, $query);
		$false=mysqli_num_rows($exec1);
// 		echo "False vaut $false";
		if($false != 0) $doc->catchError("100-005", 4);
		if($_POST["nom"] == "" || $_POST["init"] == "") $doc->catchError("100-006", 4);
		if(!$doc->errorStopSet[0])
		{
			$arr_create = ($_POST["seul"])? array(/*"droits"*/):$doc->partTables;
			if(!isset($doc->errCont)) foreach($arr_create as $type) $doc->create_prolawyer_table($_POST["init"].$type, $type);
			$plus = ($_POST["seul"])? ", seul = '1'":"";
			$util_query =  "insert into utilisateurs set nom = '{$_POST["nom"]}', initiales = '{$_POST["init"]}', couleur = '{$_POST["couleur"]}' $plus";
			$exec2=mysqli_query($doc->mysqli, $util_query);
			if(!$exec2) $doc->catchError("010-004", 4);
		}
		if($doc->errorStopSet[0])
		{
			$doc->title();
			$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
// 			foreach($doc->errCont as $key => $no) echo "<br>".$doc->liste_erreur("$no", TRUE);
			echo $doc->echoError("noStop");
			echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"]);
		}
		else
		{
			header("Location: ./modify.php?mode=partner&get=true&alea={$doc->alea}");
			$doc->title();
			$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
			echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"]);		
		}
		die();
	}
	
	if($_POST["corriger"])
	{
		//pour la récupération
		$initArray = array();
		for($x=1;$x<100;$x++)
		{
			if(!$_POST["init$x"]) break;
			if($_POST["nom$x"]) $initArray["{$_POST["nom$x"]}"] = $_POST["init$x"];
		}
		foreach($initArray as $nom => $init)
		{
			$query="select * from utilisateurs where nom like '$nom'";
			$exec1=mysqli_query($doc->mysqli, $query);
			$false=mysqli_num_rows($exec1);
			if($false != 0) $doc->catchError("100-005", 4);
			if($nom == "" || $init == "") $doc->catchError("100-006", 4);
			if(!$doc->errorStopSet[0])
			{
				foreach($doc->partTables as $type) $doc->create_prolawyer_table($init.$type, $type);
				$update_query[]="delete from utilisateurs where initiales like '$init'";
				$update_query[]="insert into utilisateurs set initiales='$init', nom='$nom'";
				foreach($update_query as $requ_query) mysqli_query($doc->mysqli, $requ_query);
			}
		}
		if($doc->errorStopSet[0])
		{
			$doc->title();
			$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
// 			foreach($doc->errCont as $key => $no) echo "<br>".$doc->liste_erreur("$no", TRUE);
			echo $doc->echoError("noStop");
			echo $doc->form($_POST["return"], "ok", "", "", "formretour<td>", "mode", "partner", "checkOrphanTables", "True");
			die();
		}
		else
		{
			header("Location: {$doc->settings["root"]}{$_POST["return"]}?mode=partner&checkOrphanTables=True");
			$doc->title();
			$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
			echo $doc->form($_POST["return"], "ok", "", "", "formretour<td>", "mode", "partner", "checkOrphanTables", "True");
			die();
		}
	}
	
	if($_POST["ajoute"])
	{
		foreach($doc->partTables as $type) $doc->create_prolawyer_table($_POST["init"].$type, $type);
		$update_query[]="update utilisateurs set seul='' where initiales like '{$_POST["init"]}'";
		foreach($update_query as $requ_query) mysqli_query($doc->mysqli, $requ_query);
		header("Location: ./modify.php?mode=partner&get=true&alea={$doc->alea}");
		$doc->title();
		$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
		echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"]);
		die();
	}
	
	if($_POST["setarchive"])
	{
		$archive = $_POST["archive"]? "1":"0";
		$archive_query="update utilisateurs set archive='$archive' where initiales like '{$_POST["init"]}'";
		mysqli_query($doc->mysqli, $archive_query);
		header("Location: ./modify.php?mode=partner&get=true&alea={$doc->alea}");
		$doc->title();
		$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
		echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"]);
		die();
	}
	
	if($_POST["delete_mode"])
	{
		$query = array();
		$rate=false;
		if($_POST["delete_mode"] == "delete")
		{
			$query[]="delete from utilisateurs where initiales like '{$_POST["init"]}'";
			$query[]="delete from droits where init like '{$_POST["init"]}'";
			if(!$_POST["seul"])
			{
				foreach($doc->partTables as $table) $query[]="drop table {$_POST["init"]}$table";
			}
// 			$query[]="drop table {$_POST["init"]}droits";
		}
		
		if($_POST["delete_mode"] == "delete_seul")
		{
			$query[]="update utilisateurs set seul='1' where initiales like '{$_POST["init"]}'";
			foreach($doc->partTables as $table) $query[]="drop table {$_POST["init"]}$table";
		}
		
		//common
		foreach($query as $key => $requete)
		{
			$reuss=mysqli_query($doc->mysqli, $requete);
			if(!$reuss)
			{
				$rate=true;
				echo "<br>$requete: $reuss - ".mysqli_error($doc->mysqli);
			}
		}
		if(!$rate) header("Location: ./modify.php?mode=partner&get=true&alea={$doc->alea}");
		$doc->title();
		$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
		echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"]);
		die();
	}
	
	if ($_POST["action"] == "maj")
	{
//  		$doc->tab_affiche();
//  		die();
		$req=array();
		$del=array();
		$utilisateurs=array();
		$groupes=array();
		$usertable=array();
		$grptable=array();
		$create=array();
		
		if($_POST["mode"] == "droits")
		{
			foreach(array("utils", "gpUtils") as $delInit)
			if($_POST["deletelist$delInit"])
			{
// 				echo "<br>Traitement de $delInit<br>";
				$where = "";
				$col1 = $delInit == "utils" ? "init": "groupname";
				foreach(preg_split("#;#", $_POST["deletelist$delInit"]) as $delBase)
				{
					list($delBase, $delUsers) = preg_split("#:#", $delBase);
					if($delUsers)
					{
						$littleWhere = "";
						foreach (preg_split("#,#", $delUsers) as $delUser)
						{
							$col9 = preg_match("#autoAccesgroupe__(.*)#", $delUser, $delName) ? 1:0; $personne = $col9 ? $delName[1]:$delUser;
// 							echo "<br>Traitement de $delUser. Avant: '$littleWhere'";
							$littleWhere = ($littleWhere) ? "$littleWhere or ":" ";
							$littleWhere .= "(personne like '$personne' AND accesgroupe like '$col9')";
						}
						if($littleWhere)
						{
							$liaison = $where ? " OR ": "";
							$where .= "$liaison($col1 like '$delBase' and ($littleWhere))";
						}
					}
// 					echo "<br>delbase: $delBase ($delUsers)";
				}
// 				echo "<br><font color=red>$where</font>	";
				if ($where) $del[] = "delete from droits where $where";
			}
			//$del[] = "delete from droits";
		}
// 		$doc->tab_affiche($del);
// 		die();
		
		foreach($_POST as $nom => $val)
		{
			if(substr($nom, 0, 11) == "user_mode__")
			{
				$nom=substr($nom, 11);
				list($base, $col) = explode("__", $nom);
				$utilisateurs["$base"]["$col"] = $val;
			}
			elseif(preg_match("#__#", $nom))
			{
// 				echo "<br>Le nom vaut '$nom'";
				$aGroupe = (preg_match("#autoprolawyergroupe__#", $nom))? true:false;
				if($aGroupe) $nom = preg_replace("#autoprolawyergroupe__#", "", $nom);
				$uGroupe = (preg_match("#autoUsergroupe__#", $nom))? true:false;
				if($uGroupe) $nom = preg_replace("#autoUsergroupe__#", "", $nom);
// 				echo "puis '$nom'";
				list($base, $access, $col) = explode("__", $nom);
				if($val == "on") $val = 1;
				$base = $uGroupe ? "autoUsergroupe__$base":$base;
				if($aGroupe) $grptable["$base"]["$access"]["$col"] = $val;	
				else  $usertable["$base"]["$access"]["$col"] = $val;	
			}
		}
		
		if(empty($usertable) && $_POST["global_user"]) $del[]="delete from droits where personne like '{$_POST["global_user"]}'";
		
		foreach($utilisateurs as $base => $array)
		{
			$req["utilisateurs_$base"] = "update utilisateurs set ";
			foreach ($utilisateurs["$base"] as $col => $val) $req["utilisateurs_$base"] .= "$col = '$val', ";
			$req["utilisateurs_$base"] = substr($req["utilisateurs_$base"], 0, -2);
			$req["utilisateurs_$base"] .= " where initiales like '$base'";
		}
		
		foreach($usertable as $base => $array)
		{
			foreach ($usertable["$base"] as $access => $array2)
			{
				if ($_POST["global_user"]) $del[]="delete from droits where personne like '$access'"; ## Plus nécessaire pour les autres cas vu la destruction sélective opérée avant
				$req["{$base}_$access"] = "insert into droits set ";
				foreach($usertable["$base"]["$access"] as $col => $val) $req["{$base}_$access"] .= "$col = '$val', ";
				if(preg_match("#autoUsergroupe__#", $base))
				{
					$sBase = preg_replace("#autoUsergroupe__#", "", $base);
					$req["{$base}_$access"] .= "personne = '$access', groupname = '$sBase'";
				}
				else $req["{$base}_$access"] .= "personne = '$access', init = '$base'";
			}
		}
		
		foreach($grptable as $base => $array)
		{
			foreach ($grptable["$base"] as $access => $array2)
			{
				$del[]="delete from droits where personne like '$access' AND accesgroupe like '1'";
				$req["{$base}_$access"] = "insert into droits set ";
				foreach($grptable["$base"]["$access"] as $col => $val) $req["{$base}_$access"] .= "$col = '$val', ";
				if(preg_match("#autoUsergroupe__#", $base))
				{
					$sBase = preg_replace("#autoUsergroupe__#", "", $base);
					$req["{$base}_$access"] .= "personne = '$access', groupname = '$sBase', accesgroupe = '1'";
				}
				else $req["{$base}_$access"] .= "personne = '$access', init = '$base', accesgroupe = '1'";
			}
		}
		
		foreach($del as $key => $requete)
		{
// 			echo "<br>$requete";
 			mysqli_query($doc->mysqli, $requete);
//  			die();
		}
// 		foreach($create as $key => $requete)
// 		{
// 			mysqli_query($doc->mysqli, $requete);
// 		}
		foreach($req as $key => $requete)
		{
// 			echo "<br>$key: $requete";
 			mysqli_query($doc->mysqli, $requete);
//			echo "<br>$requete";
		}
		
		if($_POST["global_user"]) $_POST["mode"] = "user";
// 		die("tata");
		header("Location: ./modify.php?mode={$_POST["mode"]}&get=true&fromCreate=true");
		
		$doc->title();
		$doc->body("2", "document.getElementById('formretourTODO').elements[1].focus()");
		echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"], "fromCreate", "true");
		die();
	}
}





if(isset($doc->errCont)) unset ($doc->errCont);


if($_POST["mode"] == "user")
{
	
	
	//destruction d'un utilisateur, confirmation donnée
	if($_POST["delete"]=="on")
	{
		//a. destruction de la liste des utilisateurs
		$q="delete from acces where TRIM(user) like '{$_POST["user_delete"]}'";
		$ex=mysqli_query($doc->mysqli, $q);
	
		//b. destruction de la liste des avocats
		
		$q="delete from droits where TRIM(personne) like '{$_POST["user_delete"]}'";
		$ex=mysqli_query($doc->mysqli, $q);
	}
	
	//changement ou insertion de l'utilisateur
	if($_POST["mode"] == "user" AND $_POST["delete"] != "on")
	{
		$same=FALSE; // pour vérifier si l'utilisateur qu'on modifie existe déjà
		
		//On vérifie d'abord si l'utilisateur existe
		
		foreach($doc->liste_acces() as $option)
		{
			if($option["user"] != "") //Problème si un utilisateur vide a été introduit. Pas censé arriver, mais...
			{
				if($option["user"] == trim($_POST["util_new_name"])) //pas censé être le cas: l'utilisateur nouveau ne doit pas correspondre à un ancien
				{
					$doc->catchError("100-005", 4);
					$same=TRUE;
				}
				if($option["user"] == trim($_POST["util_name"])) //on ne modifie pas le mot de passe s'il est vide
				{
					$same=TRUE;
				}
			}
		}
		
		
		//Ensuite, on vérifie si les données sont cohérentes (nouvel utilisteur avec un nom existant, etc...
		if(trim($_POST["new_pwd"]) != trim($_POST["vpwd"])) $doc->catchError("100-001", 4); //concordance des mots de passe
		if((!$same && (trim($_POST["new_pwd"]) == "" ||  trim($_POST["vpwd"]) == ""))  ||  ($same && (trim($_POST["new_pwd"]) != trim($_POST["vpwd"])) && (trim($_POST["new_pwd"]) == "" ||  trim($_POST["vpwd"]) == ""))  ) $doc->catchError("100-008", 4); //mots de passe laissés en blanc par erreur
		
		
		if(!$doc->errorContSet[0] && !$doc->errorStopSet[0])
		{
			$nName=($same)? $_POST["util_name"] : $_POST["util_new_name"];
			$nPwd=($_POST["new_pwd"])? $_POST["new_pwd"] : "***noChange***";
			
			//echo "\$doc->createUser($nName, $nPwd, {$_POST["type"]})";
			$doc->createUser($nName, $nPwd, $_POST["type"]);
		}
		$_POST["mode"] = "user";
	}

// die();
}


if($_POST["mode"] == "groupe" || $_POST["mode"] == "ugroupe")
{
	$users=($_POST["mode"] == "ugroupe")? TRUE:FALSE;
	$table=$users?"accesgroupes":"utilisateursgroupes";
	$table_droits=$users?"accesgroupesmembres":"utilisateursgroupesmembres";
	$nomgroupecol=$users?"nomugroupe":"nomgroupe";
	$groupecol=$users?"ugroupe":"groupe";
	$membrecol=$users?"umembre":"membre";
	foreach($_POST as $nom => $val)
	{
		if(preg_match("#_specialSpace_#", $nom))
		{
			unset($_POST["$nom"]);
			$nnom=preg_replace("#_specialSpace_#", " ", $nom);
			$_POST["$nnom"] = $val;
		}
	}
	
	if($_POST["action"] == "update")
	{
		$modif_groupe = array();
		$details_groupe = array();
		foreach($_POST as $nom => $val)
		{
			if(substr($nom, 0, 13) == "__groupname__")
			{
				$modif_groupe[]=substr($nom, 13);
				unset($_POST["$nom"]);
			}
			elseif(preg_match("#__#", $nom))
			{
				list($groupe, $membre) = preg_split("#__#", $nom);
				$gn="__groupname__".$groupe;
				if($_POST["$gn"] == "on") $details_groupe["$groupe"][] = $membre;
			}
		}
				
		foreach($modif_groupe as $gname)
		{
			$q="delete from $table_droits where $groupecol like '$gname'";
// 			echo "<br>$q";
			$x=mysqli_query($doc->mysqli, $q);
		}
		foreach($details_groupe as $ngrp => $ardet)
		{
			foreach($ardet as $ng => $m)
			{
				$q="insert into $table_droits set $groupecol = '$ngrp', $membrecol = '$m'";
// 				echo "<br>$q";
				$x=mysqli_query($doc->mysqli, $q);
			}
		}
	}
	
	if($_POST["action"] == "delete")
	{
		foreach($_POST as $nom => $val)
		{
			if(substr($nom, 0, 13) == "__groupname__")
			{
				$modif_groupe[]=substr($nom, 13);
				unset($_POST["$nom"]);
			}
		}
		
		foreach($modif_groupe as $k => $nom)
		{
			$q="delete from $table where $nomgroupecol like '$nom'";
			$ex=mysqli_query($doc->mysqli, $q);
			$q="delete from $table_droits where $groupecol like '$nom'";
			$ex=mysqli_query($doc->mysqli, $q);
		}
	}
	if($_POST["action"] == "create")
	{
		$toTest = $_POST["mode"] == "ugroupe" ? $doc->liste_accesgroupes(): $doc->liste_utilisateursgroupes();
// 		$doc->tab_affiche($toTest);
		if(!$_POST["new_name"])
		{
			$doc->catchError("100-006", 4);
		}
		elseif(in_array($_POST["new_name"], $toTest))
		{
			$doc->catchError("100-005", 4);
		}
		else
		{
			$q="insert into $table set $nomgroupecol = '{$_POST["new_name"]}'";
			$ex=mysqli_query($doc->mysqli, $q);
		}
	}
	
		
	if(!$doc->errorStopSet[0]) header("Location: ./modify.php?mode={$_POST["mode"]}&get=true&alea={$doc->alea}");
	
	$doc->title();
	$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
	$doc->entete();
	echo $doc->echoError("noStop");
	echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"]);
	die();
}

if($new_name==$_SESSION["user"]) $retour="config";
else $retour="modify";


if($doc->errorContSet[0] || $doc->errorStopSet[0])
{
	$doc->title();
	$doc->body("2", "document.getElementById('formretour').elements[1].focus()");
	$doc->entete();
	echo $doc->echoError("noStop");
	echo "<a href=\"./$retour.php?mode={$_POST["mode"]}&get=true&alea={$doc->alea}\">ok</a>";
/*	foreach($doc->errCont as $key => $no)
	{
		echo "<br>".$doc->liste_erreur("$no", TRUE);
	}
	echo $doc->form("config/modify", "ok", "", "", "formretour<td>", "mode", $_POST["mode"]);*/
	$doc->close();
}else{
	header("Location: ./$retour.php?mode={$_POST["mode"]}&get=true&alea={$doc->alea}");
	echo "<a href=\"./$retour.php?mode={$_POST["mode"]}&get=true&alea={$doc->alea}\">ok</a>";
	$doc->close();
}

?>
