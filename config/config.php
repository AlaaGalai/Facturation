<?php
require_once("../inc/autoload.php");
session_start();
$doc=new prolawyer(false);
// $doc->tab_affiche();
if($_SESSION["isSetAdmin"] && ! $_REQUEST["setDbData"])
{
// 	$doc->getOptions("firstCheck");
	$doc->connection(); //TODO !!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!!
// 	$doc->getOptionsPerso("firstCheck");
//  	echo $_SESSION["type"];
	if($_SESSION["type"] != "admin")
	{
// 		echo ("<a href=\"./index.php?nextPage=config.php\">(type = '{$_SESSION["type"]}')</a>");
		header ("location: ./index.php?nextPage=config.php");
		die();
	}
}
if($_POST["check_global_install"])
{
	header("Location:./config.php?checkPhpInstall=True");
	die();
}

$doc->title();
$doc->body();
$doc->entete();

//$doc->tab_affiche(2);

/*******************************************
MISE A JOUR DES INFORMATIONS
*******************************************/

if($_POST["setDbData"] && $_REQUEST["db"] == "ldap")
{
	unset($doc->ldapConnected);
	$_SESSION["ldapServer"] = $_POST["ldapServer"];
	$_SESSION["ldapPort"] = $_POST["ldapPort"];
	$con = ldap_connect($_SESSION["ldapServer"], $_SESSION["ldapPort"]);
	if($con)
	{
		$doc->ldapConnected = $con;
		ldap_set_option($doc->ldapConnected, LDAP_OPT_PROTOCOL_VERSION, 3);
		$doc->setOption("ldapPort", $_POST["ldapPort"]);
		$doc->setOption("ldapServer", $_POST["ldapServer"]);
	}
}

if($_REQUEST["checkOrphanTables"])
{
	$recup = $doc->recuperationAssocies();
	if($recup)
	{
		$doc->close();
		die();
	}
}

if($_REQUEST["updateTablesDefault"])
{
	$dir = opendir("{$doc->settings["root"]}config/default");
	while($file = readdir($dir))
	{
		if(substr($file, 0, 1) != "." && $file != "CVS")
		{
			$defaultOption = array();
			$table = basename($file, ".php");
// 			echo "<br>Traitement de $table. ";
			require ("{$doc->settings["root"]}config/default/$file");
// 			echo "La colonne par défaut est $defaultColumn";
			$q1 = "delete from $table where $defaultColumn like 'tableDefaultValue'";
			$e1 = mysqli_query($doc->mysqli, $q1);
			$err1 = mysqli_error($doc->mysqli);
			if(!$e1) $doc->catchError("010-006#-#'$q1' - $err1", 4);
			if($e1)
			{
				$q2 = "insert into $table set $defaultColumn = 'tableDefaultValue'";
				foreach($defaultOption as $n => $v)
				{
					if(is_array($v))
					{
						$v2 = "";
						foreach($v as $vx) $v2 .= $doc->smart_html($vx)."\n";
						$v = trim($v2);
					}
					$q2 .= ", $n = '$v'";
				}
// 				$q2 = "'$q2'";
// 				echo "<br>'$q2'";
				$e2 = mysqli_query($doc->mysqli, $q2);
				$err2 = mysqli_error($doc->mysqli);
				$q2 = preg_replace("#\n#m", " ", $q2);
				if(!$e2) $doc->catchError("010-007#-#'<font color=red>$q2</font>' - $err2", 4);
			}
		}
	}
 	echo $doc->echoError("noStop");
	
	if(!$doc->isError()) $doc->setOption("tablesDefaultUpdated", $_SESSION["version"]);
	$doc->resetError();
}

if($_REQUEST["updateTables"])
{
	$tables = $doc->get_prolawyer_tables();
	//print_r ($tables);
	$actTables = array();
//	$ckdTables = array();
	$q="show tables from {$_SESSION["dbName"]}";
	$e=mysqli_query($doc->mysqli, $q);
	while ($r=mysqli_fetch_array($e)) $actTables[] = $r["Tables_in_{$_SESSION["dbName"]}"];
// 	$doc->tab_affiche($actTables);
	
	echo "\n<h2>{$doc->lang["config_actu_tables"]}</h2>";
	echo "\n<ul>";
	
	foreach($tables["system"] as $tname)
	{
		$allready = (in_array($tname, $actTables)) ? true:false;
// 		if($allready) echo "<br>La table $tname existe déjà. On la met à jour";
// 		else echo "<br>La table $tname n'existe pas. On la crée";
// 		echo "<br>On tente de créer la table $tname dans la base {$_SESSION["dbName"]}. L'accès vaut $acces avant... ";
		$state = $doc->create_prolawyer_table($tname);
		if(!$state)
		{
			if($allready) $doc->catchError("100-032#-#$tname#{$doc->tablesQueryErrors["$tname"]}", 4);
			else $doc->catchError("100-031#-#$tname#{$doc->tablesQueryErrors["$tname"]}", 4);
			$noUp=true;
		}
		if($state == 1) $doc->catchError("0100-033#-#$tname", 1);
		if($state == 2) $doc->catchError("0100-034#-#$tname", 1);
		if($state == 3) $doc->catchError("0100-037#-#$tname", 0);
		echo $doc->echoError("noStop", 0, "mefLiOnly");
		$doc->resetError("cont");
		flush();
		ob_flush();
		$kNo = array_keys($actTables, "$tname");
		foreach($kNo as $kN) unset($actTables[$kN]);
// 		echo "et $acces après";
	}
	
	$partners = array();

	foreach($actTables as $tname)
	{
// 		echo "<br>$tname...";
// 		flush();
// 		ob_flush();
		$match=False;
		foreach($tables["partner"] as $matchControl)
		{
			if(preg_match("#^(.{2})($matchControl)$#", $tname, $regs)) 
			{
 				//echo "<br>{$regs[0]}, {$regs[1]}, {$regs[2]}";
				if (!in_array($regs[1], $partners)) $partners[] = $regs[1];
				$match=True;
				break;
			}
		}
		if($match)
		{
			//echo "<br>{$regs[0]}, {$regs[1]}, {$regs[2]}";
	// 		if($allready) echo "<br>La table $tname existe déjà. On la met à jour";
	// 		else echo "<br>La table $tname n'existe pas. On la crée";
	// 		echo "<br>On tente de créer la table $tname dans la base {$_SESSION["dbName"]}. L'accès vaut $acces avant... ";
			$state = $doc->create_prolawyer_table($regs[0], $regs[2]);
			if(!$state)
			{
				if($allready) $doc->catchError("100-032#-#$tname#{$doc->tablesQueryErrors["$tname"]}", 4);
				else $doc->catchError("100-031#-#$tname#{$doc->tablesQueryErrors["$tname"]}", 4);
				$noUp=true;
			}
			if($state == 1) $doc->catchError("0100-033#-#$tname", 1);
			if($state == 2) $doc->catchError("0100-034#-#$tname", 1);
			if($state == 3) $doc->catchError("0100-037#-#$tname", 0);
			echo $doc->echoError("noStop", 0, "mefLiOnly");
			$doc->resetError("cont");
			flush();
			ob_flush();
			$kNo = array_keys($actTables, "$tname");
			foreach($kNo as $kN) unset($actTables[$kN]);
	// 		echo "et $acces après";
		}
		else $orphanTables .= $orphanTables ? ", $tname": $tname;
	}

	//création des tables partner éventuellement manquantes
	foreach($partners as $partner)
	{
		foreach($tables["partner"] as $suffixe)
		{
			$tname = $partner.$suffixe;
// 			echo "<br>$tname...";
// 			flush();
// 			ob_flush();
			if(! in_array($tname, $actTables))
			{
				$state = $doc->create_prolawyer_table($tname, $suffixe);
				if(!$state) $doc->catchError("100-032#-#$tname#{$doc->tablesQueryErrors["$tname"]}", 4);
				elseif($state == 1) $doc->catchError("0100-033#-#$tname", 1);
				echo $doc->echoError("noStop", 0, "mefLiOnly");
				$doc->resetError("cont");
				flush();
				ob_flush();
			}
		}
	}

	echo "\n</ul>";
	
	//$doc->tab_affiche($partners);
	if($orphanTables) $doc->catchError("0100-038#-#$orphanTables", 2);
	
	echo $doc->echoError("noStop");
	if(!$doc->isError("stop")) $doc->setOption("tablesUpdated", $_SESSION["version"]);
	$doc->resetError();
	echo $doc->echoError("noStop");
// 	$doc->checkDebug();
	
// 	$doc->tab_affiche($actTables);
}

if($_POST["setDbData"] && $_REQUEST["db"] == "mysql")
{
	$_SESSION["mysqlServer"] = $_POST["mysqlServer"];
	$_SESSION["mysqlPort"] = $_POST["mysqlPort"];
// 	echo "premier<br>";
	$doc->noPerso = true;
	$connect = $doc->connection($_POST["rUser"], $_POST["rPwd"], false, "mysql");
	$doc->noPerso = false;
// 	die("\$doc->connection({$_POST["rUser"]}, {$_POST["rPwd"]}, false, \"mysql\")");
	if($connect)
	{
//  		echo "connection réussie";
		$acces = true;
		foreach($doc->liste_acces(True) as $ar) if($ar["user"] == $_POST["user"])
		{
			$acces = false;
			$doc->catchError("100-005", 4);
 			echo "<br>Le nom existe déjà";
		}
		if($acces)
		{
			$tables = $doc->get_prolawyer_tables();
			foreach($tables["system"] as $tname)
			{
// 	 			echo "<br>On tente de créer la table $tname dans la base {$_SESSION["dbName"]}. L'accès vaut $acces avant... ";
				if(!$acces || !$doc->create_prolawyer_table($tname)) $acces = false;
// 				echo "et $acces après";
			}
		}
	}
	if($_POST["pwd"]==$_POST["vPwd"] AND $_POST["user"]<>"" AND $_POST["pwd"]<>"" AND $connect AND $acces)
	{
		$_SESSION["dbAdmin"] = $_POST["rUser"];
		$_SESSION["dbPwd"] = $_POST["rPwd"];
		$insAdmin = $doc->setUserPwd($_POST["user"], $_POST["pwd"]);
		$insOptions = $doc->setOptionsPerso("type", "admin", $_POST["user"]);
	}
// 	echo "Connection ratée avec {$_POST["rUser"]}, {$_POST["rPwd"]}, false, mysql";
	if( $insAdmin AND $insOptions)
	{
//  		echo "tout est bon";
		$texte=$_POST["user"].",".$doc->code($_POST["ruser"], $_POST["pwd"]).",".$doc->code($_POST["rpwd"], $_POST["pwd"]).",administrateur,";
		$doc->setOption("dbAdmin", $_POST["rUser"]);
		$doc->setOption("dbPwd", $_POST["rPwd"]);
		$doc->setOption("mysqlPort", $_POST["mysqlPort"]);
		$doc->setOption("mysqlServer", $_POST["mysqlServer"]);
		$doc->setOption("mysqlServer", $_POST["mysqlServer"]);
		$doc->setOption("isSetAdmin", "true");
		$_SESSION["user"] = $_POST["user"];
		$_SESSION["pwd"] = $_POST["pwd"];
	}else {
		unset($_SESSION["dbAdmin"]);
		unset($_SESSION["dbPwd"]);
		if(!function_exists('mysqli_connect')) $doc->catchError("020-001", 4);
		if(!$connect) $doc->catchError("010-002", 4);
		if($_POST["pwd"]!=$_POST["vPwd"]) $doc->catchError("100-001", 4);
		if($_POST["user"]=="") $doc->catchError("100-002", 4);
		if($_POST["pwd"]=="") $doc->catchError("100-003", 4);
		echo $doc->echoError("noStop");
		echo "<br><br><a href='./config.php?checkDbData=on'> Y'a un problème ..</a>";
		$doc->close();
		die();
	}
	$_REQUEST["checkState"] = true;
}

if($_POST["updateTables"])
{
	$connect = $doc->connection($_POST["rUser"], $_POST["rPwd"], false, "mysql");
	if($connect)
	{
		$acces = true;
		$tables = $doc->get_prolawyer_tables();
		foreach($tables["system"] as $tname)
		{
// 			echo "<br>On tente de mettre à jour la table $tname dans la base {$_SESSION["dbName"]}. L'accès vaut $acces avant... ";
			if(!$doc->create_prolawyer_table($tname)) $acces = false;
// 			echo "et $acces après";
		}
	}
	$_REQUEST["checkState"] = true;
}

if($_POST["setDb"])
{
	$doc->setOption("dbName", $_POST["dbName"]);
}

if($_POST["setMethod"])
{
	foreach($_REQUEST as $n => $v) if(preg_match("#method_#", $n)) $methods[] = preg_replace("#method_(.*)#", "\\1", $n);
	$doc->setOption("connectionMethods[]", $methods);
}

if($_POST["setIgnore"])
{
	$doc->setOption("fonctionsDispo", $_SESSION["version"]);
}

if($_REQUEST["ignoreTVADossier"]) $doc->setOption("TVADossierChecked", "True");

if($_REQUEST["ignoreTVAOP"]) $doc->setOption("TVAOpChecked", "True");



/*******************************************
VERIFICATION DE LA CONFIGURATION
*******************************************/
if($_REQUEST["checkPhpInstall"]) //Etape 1. Test des fonctions php disponibles
{
	$doc->unsetOption("fonctionsDispo", "=");
	if(!function_exists(mysqli_query)) $doc->catchError("040-101::111", 4);
	else $doc->catchError("040-121", 0);
	if(!function_exists(imagecolorallocate)) $doc->catchError("040-102::112", 2);
	else $doc->catchError("040-122", 0);
	if(!function_exists(mcrypt_list_algorithms)) $doc->catchError("040-103::113", 1);
	else $doc->catchError("040-123", 0);
	if(!function_exists(ldap_bind)) $doc->catchError("040-104::114", 1);
	else $doc->catchError("040-124", 0);
	if(!function_exists(imap_open)) $doc->catchError("040-105::115", 1);
	else $doc->catchError("040-125", 0);
	if(!class_exists(ZipArchive)) $doc->catchError("040-106::116", 1);
	else $doc->catchError("040-126", 0);
	
// 	$doc->echoError("noStop");
	echo "<h2>{$doc->lang["config_config_h"]}</h2>";
	if($doc->isError()) echo "\n{$doc->lang["config_config_h0"]}.\n<br>".preg_replace("#okok#", "<img src=../images/true.png>", $doc->lang["config_config_h01"]).".";
	echo $doc->echoError("noStop");

	if(function_exists(mcrypt_list_algorithms)) //test de l'existence des modules de cryptographies
	{
// 		echo "<br>Testing modules";
		$modules = mcrypt_list_algorithms();
		$modes = mcrypt_list_modes();
		$test_crypt=FALSE;
		foreach(array("rc2", "blowfish", "tripledes") as $module)
		{
			array_unshift($modules, $module);
		}
		foreach($modules as $module)
		{
// 			echo "<br>Testing $module with mode ";
			foreach($modes as $mode)
			{
// 				echo "$mode ";
				if($ivsize=mcrypt_get_iv_size($module, $mode))
				{
					$keysize = mcrypt_module_get_algo_key_size($module);
// 					echo "OK (ivsize = $ivsize; keysize = $keysize). Saving (previously in '{$doc->settings["principal"]}')";
					$doc->setOption("crypt_algo", $module);
					$doc->setOption("crypt_mode", $mode);
					$doc->setOption("ivsize", $ivsize);
					$doc->setOption("keysize", $keysize);
					break 2	;
				}
			}
		}
	}
	
	
	if($doc->isError())
	{
		echo $doc->table_open();
		echo "\n<br><br>".$doc->form("config/config.php<td>", $doc->lang["config_config_ignorer"], "", "", "", "setIgnore", "true", "checkState", "true");
		echo "&nbsp;".$doc->form("config/config.php<td>", $doc->lang["modifier_donnees_annuler"], "", "", "", "checkState", "true");
		echo $doc->table_close();
	}
	else
	{
		echo $doc->form("config/config.php<td>", $doc->lang["config_config_continuer"], "", "", "", "checkState", "true");
		$doc->setOption("fonctionsDispo", $_SESSION["version"], "=");
	}

	$doc->close();
	die();
}

if($_REQUEST["checkDb"]) //Etape 2. Test de la base de données
{
	$dbName = ($_SESSION["dbName"]) ? $_SESSION["dbName"]:"prolawyer";
	echo "\n<h3>$config_config_choisir_db</h3>";
	echo "\n<h3>$config_config_choisir_db2</h3>";
	echo "\n<form action=\"./config.php\" method=\"POST\">
	<input type=\"text\" name=\"dbName\" value=\"$dbName\">";
	echo "\n\t".$doc->input_hidden("setDb", "", "on");
	echo "\n\t".$doc->input_hidden("checkState", "", "on");
	echo "\n\t".$doc->button($doc->lang["operations_valider"], "");
	echo "</form>";
	
	$doc->close();
	die();
}

if($_REQUEST["checkAuth"]) //Etape 3. Test des moyens d'authentification
{
	echo "\n<form action=\"./config.php\" method=post>";
	echo $doc->input_hidden("setMethod", "", "on");
	echo "\n<table>";
	
	foreach(array("prolawyer", "mysql", "ldap", "guest" /*, "apache"*/) as $method) //apache pose trop de problèmes pour l'instant...
	{
		$checked = ((is_array($_SESSION["connectionMethods"]) && in_array($method, $_SESSION["connectionMethods"])) || $method == "prolawyer") ? "checked":"";
		$input = ($method != "ldap" || function_exists(ldap_bind)) ? "<input type = \"checkbox\" name =\"method_$method\" $checked>": $doc->echoUniqueError("040-104::114", 1, "mefTextOnly", True);
// 		$add = ($method == "ldap" || function_exists(ldap_bind)) ? "<td>{$doc->lang["config_ldap_server"]}</td><td><input type = \"text\" name =\"ldapServer\"></td>": "";
		echo "\n<tr><td>$method&nbsp;:</td><td>$input</td></tr>";
	}
	
	echo "\n<tr><td colspan=\"2\"><button type=submit>{$doc->lang["config_config_creer"]}</button></td></tr>";
	echo "\n</table>";
	echo "\n</form>";
	$doc->close();
	die();
}

if($_REQUEST["checkDbData"]) //Etape 4. Test des informations de connexion
{
	$_REQUEST["db"] = $_REQUEST["db"] ? $_REQUEST["db"]:"mysql";
	$sName = $_REQUEST["db"]."Server";
	$sPort = $_REQUEST["db"]."Port";
	$server = $_SESSION["$sName"] ? $_SESSION["$sName"]:"localhost";
	$port = $_SESSION["$sPort"] ? $_SESSION["$sPort"]:"default";
	$chaine = "config_modify_{$_REQUEST["db"]}_server";
	if($port == "default" && $_REQUEST["db"] == "ldap") $port = "389";
	if($port == "default" && $_REQUEST["db"] == "mysql") $port = "3306";
	echo "\n<form action=\"./config.php\" method=post style=\"display:inline\">";
	echo $doc->input_hidden("setDbData", "", "on");
	echo $doc->input_hidden("db", 1);
	echo "\n<table>";
	if($_REQUEST["db"] == "mysql") echo "\n<tr><td>{$doc->lang["config_config_nom"]}&nbsp;:</td><td><input name=user></td></tr>";
	if($_REQUEST["db"] == "mysql") echo "\n<tr><td>{$doc->lang["config_config_pwd"]}&nbsp;:</td><td><input type=password name=pwd></td><td>{$doc->lang["config_config_verify"]}&nbsp;:</td><td><input type=password name=vPwd></td></tr>";
	if($_REQUEST["db"] == "mysql") echo "\n<tr><td>{$doc->lang["config_config_root"]}&nbsp;:</td><td><input type=password name=rUser></td></tr>";
	if($_REQUEST["db"] == "mysql") echo "\n<tr><td>{$doc->lang["config_config_rpwd"]}&nbsp;:</td><td><input type=password name=rPwd></td></tr>";
	echo "\n<tr><td>{$doc->lang["$chaine"]}&nbsp;:</td><td><input type=text name={$_REQUEST["db"]}Server value=\"$server\"></td><td>{$doc->lang["config_modify_mail_port"]}&nbsp;:</td><td><input type=text name={$_REQUEST["db"]}Port value=$port></td></tr>";
	echo "\n<tr><td><button type=submit>{$doc->lang["config_config_creer"]}</button></td></tr>";
	echo "\n</table>";
	echo "\n</form>";
	echo "<form action = \"config.php\" method = \"POST\" style=\"display:inline\"><button type=submit>annuler</button></form>";
	
	$doc->close();
	die();
}

if($_REQUEST["updateTVADossier"])
{
	$txTva = preg_replace("/,/", ".", $_POST["txtva"]);
	if($_POST["choice"] === "0") $txTva = 0;
	$q = "show tables";
	$e = mysqli_query($doc->mysqli, $q);
	$tables = array();
	$in = "Tables_in_".$_SESSION["dbName"];
	while($r = mysqli_fetch_array($e)) if(preg_match('/.*clients$/', $r[$in], $p)) $tables[] = $p[0];
	foreach($tables as $table)
	{
		$q = "update $table set tvadossier = '$txTva' where tvadossier like ''";
		$e = mysqli_query($doc->mysqli, $q);
	}
	$_REQUEST["checkTVADossier"] = True;
}

if($_REQUEST["updateTVAOp"])
{
// 	$doc->tab_affiche(4);
	$cond = $_POST["appliquer"] == "partie" ? "and tvaop = -100.00":"";
	$q = "show tables";
	$e = mysqli_query($doc->mysqli, $q);
	$tables = array();
	$in = "Tables_in_".$_SESSION["dbName"];
	while($r = mysqli_fetch_array($e)) if(preg_match('/(.*)clients$/', $r[$in], $p)) $tables[] = $p[1];
	if($_POST["choice"] == "0")
	{
		foreach($tables as $table)
		{
			print "<br>$table";
			if($_POST["limiter"] == "tous" || (is_array($_POST["personne"]) && in_array($table."op", $_POST["personne"])))
			{
				$q = "update {$table}op, {$table}clients set {$table}op.tvaop = {$table}clients.tvadossier where {$table}clients.nodossier = {$table}op.nodossier $cond";
				$e = mysqli_query($doc->mysqli, $q);
				print "<br>$q";
			}
		}
	}
	elseif($_POST["choice"] == "1")
	{
		for($x=1;$x<4;$x++)
		{
			if($_POST["per$x"])
			{
				if (!$doc->checkDate("from$x"))
				{
					print "<br><font color=\"red\">{$_POST["jourfrom$x"]}.{$_POST["moisfrom$x"]}.{$_POST["anneefrom$x"]}: {$doc->lang["config_config_date_invalide"]}</font>";
					continue;
				}
				if (!$doc->checkDate("to$x"))
				{
					print "<br><font color=\"red\">{$_POST["jourto$x"]}.{$_POST["moisto$x"]}.{$_POST["anneeto$x"]}: {$doc->lang["config_config_date_invalide"]}</font>";
					continue;
				}

				$txTva = preg_replace("/,/", ".", $_POST["txtva$x"]);
				foreach($tables as $table)
				{
					if($_POST["limiter"] == "tous" || (is_array($_POST["personne"]) && in_array($table."op", $_POST["personne"])))
					{
						$q = "update {$table}op set tvaop = '$txTva' where ((dateop not like '0000-00-00' and dateop between '{$_POST["anneefrom$x"]}-{$_POST["moisfrom$x"]}-{$_POST["jourfrom$x"]}' and '{$_POST["anneeto$x"]}-{$_POST["moisto$x"]}-{$_POST["jourto$x"]}') OR (dateac not like '0000-00-00' and dateac between '{$_POST["anneefrom$x"]}-{$_POST["moisfrom$x"]}-{$_POST["jourfrom$x"]}' and '{$_POST["anneeto$x"]}-{$_POST["moisto$x"]}-{$_POST["jourto$x"]}')) $cond"; ##todo: voir entre dateop et dateac comment sélectionner correctement l'une et l'autre...
						$e = mysqli_query($doc->mysqli, $q) or die(mysqli_error($doc->mysqli));
// 						print "<br>$q";
					}
				}
			}
		}
// 			$e = mysqli_query($doc->mysqli, $q);
	}
	$_REQUEST["checkTVAOP"] = True;
}

if($_REQUEST["checkTVADossier"])
{
	$doc->unsetOption("TVADossierChecked");
	$q = "show tables";
	$e = mysqli_query($doc->mysqli, $q);
	$tables = array();
	$in = "Tables_in_".$_SESSION["dbName"];
	while($r = mysqli_fetch_array($e)) if(preg_match('/.*clients$/', $r[$in], $p)) $tables[] = $p[0];
	foreach($tables as $table)
	{
		$field = False;
		$q1 = "describe $table";
		$e1 = mysqli_query($doc->mysqli, $q1);
		while($r1 = mysqli_fetch_array($e1))
		{
			if($r1["Field"] == "tvadossier") $field = True;
		}
		if(! $field) #La colonne tvadossier n'existe pas (vieille version de Prolawyer)
		{
			$q2 = "ALTER table $table ADD tvadossier varchar(64) NOT NULL DEFAULT ''"; #Modification volontairement fausse (sous forme de champs varchar plutôt que decimal) pour permettre la correction ultérieure.
			$e2 = mysqli_query($doc->mysqli, $q2);
			if(!$e2) echo mysqli_error($doc->mysqli);
		}
		$q = "select nodossier from $table where tvadossier like '' or  tvadossier like NULL";
		$e = mysqli_query($doc->mysqli, $q);
		$n = mysqli_num_rows($e);
		if ($n) $doc->catchError("100-045#-#$n#$table", 2);
	}
	echo $doc->echoError();
	if($doc->isError())
	{
		if(!$_SESSION["optionGen"]["tx_tva"]) $_SESSION["optionGen"]["tx_tva"] = 8.0;
		print "\n<form style=\"display:inline\" action=\"config.php\" method=POST>";
		print "\n<br>{$doc->lang["config_config_tva1"]}";
		print "\n<br>{$doc->lang["config_config_tva2"]}&nbsp;:";
		print "\n<ul>";
		print "\n<li><input type=\"radio\" name=\"choice\" value=\"0\">{$doc->lang["config_config_tva3"]}</li>";
		print "\n<li><input type=\"radio\" name=\"choice\" value=\"1\" checked>{$doc->lang["config_config_tva4"]} <input type=\"text\" name=\"txtva\" size=\"3\" value=\"{$_SESSION["optionGen"]["tx_tva"]}\">%</li>";
		print "\n</ul>";
		print "\n<i>{$doc->lang["config_config_tva5"]}</i>";
		print "\n<br>";
		print $doc->input_hidden("updateTVADossier", "", "on");
		print $doc->button("Choisir");
		print "\n</form>";
		print "\n<form style=\"display:inline\" action=\"config.php\" method=POST>";
		print $doc->button($doc->lang["modifier_donnees_annuler"]);
		print "\n</form>";
		$doc->close();
		die();
	}
	else $doc->setOption("TVADossierChecked", "True");
}

if($_REQUEST["checkTVAOP"] || $_REQUEST["modifyTVAOP"])
{	
	$doc->unsetOption("TVAOpChecked");
	$q = "show tables";
	$e = mysqli_query($doc->mysqli, $q);
	$tables = array();
	$in = "Tables_in_".$_SESSION["dbName"];
	while($r = mysqli_fetch_array($e)) if(preg_match('/.*op$/', $r[$in], $p)) $tables[] = $p[0];
	foreach($tables as $table)
	{
		$q = "select idop from $table where tvaop like '-100.00'";
		$e = mysqli_query($doc->mysqli, $q);
		$n = mysqli_num_rows($e);
		if ($n) $doc->catchError("100-046#-#$n#$table", 2);
	}
	echo $doc->echoError();
	if($doc->isError() || $_REQUEST["modifyTVAOP"])
	{
		if(!$_SESSION["optionGen"]["tx_tva"]) $_SESSION["optionGen"]["tx_tva"] = 7.6;
		print "\n<form style=\"display:inline\" action=\"config.php\" method=POST>";
		if($doc->isError())
		{
			print "\n<br>{$doc->lang["config_config_tvaop1"]}";
			print "\n<br>{$doc->lang["config_config_tvaop2"]}&nbsp;:";
		}
		else
		{
			print "\n<h2>{$doc->lang["config_config_modify_TVA"]}</h2>";
			print "\n<br>{$doc->lang["config_config_modify_tvaop"]}&nbsp;?";
		}
		print "\n<ul>";
		print "\n<li><input type=\"radio\" name=\"choice\" value=\"0\">{$doc->lang["config_config_tvaop3"]}</li>";
		print "\n<li><input type=\"radio\" name=\"choice\" value=\"1\" checked>{$doc->lang["config_config_tvaop4"]}&nbsp;:";
		print "<br><input type=\"checkbox\" name=\"per1\" checked><input type=\"text\" size=\"3\" name=\"txtva1\" value=\"7.6\">% ".$doc->lang["general_du"] . $doc->split_date("0000-00-00", "from1").$doc->lang["general_au"] . $doc->split_date("2010-12-31", "to1");
		print "<br><input type=\"checkbox\" name=\"per2\" checked><input type=\"text\" size=\"3\" name=\"txtva2\" value=\"8.0\">% ".$doc->lang["general_du"] . $doc->split_date("2011-01-01", "from2").$doc->lang["general_au"] . $doc->split_date("2099-12-31", "to2");
		print "<br><input type=\"checkbox\" name=\"per2\"><input type=\"text\" size=\"3\" name=\"txtva3\" value=\"\">% ".$doc->lang["general_du"] . $doc->split_date("", "from3").$doc->lang["general_au"] . $doc->split_date("", "to3");
		print "</li>";
		print "\n</ul>";
		print "\n{$doc->lang["config_config_tvaop_appliquer"]}<input type=\"radio\" name=\"appliquer\" value=\"tous\">{$doc->lang["config_config_tvaop_appliquer_tous"]}<input type=\"radio\" checked name=\"appliquer\" value=\"partie\">{$doc->lang["config_config_tvaop_appliquer_partie"]}";
		print "\n<br>{$doc->lang["config_config_tvaop_appliquer"]}<input type=\"radio\" name=\"limiter\" value=\"tous\" checked>{$doc->lang["config_config_limiter_tous"]}<input type=\"radio\" name=\"limiter\" value=\"\">{$doc->lang["config_config_limiter_personnes"]}&nbsp;<select size=3 multiple name=\"personne[]\">";
		foreach($tables as $table) print "<option value=\"$table\">$table</option>";
		print "\n</select>";
		print "\n<br><br><i>{$doc->lang["config_config_tvaop5"]}</i>";
		print "\n<br>";
		print $doc->input_hidden("updateTVAOp", "", "on");
		print $doc->input_hidden("modifyTVAOP", 1);
		print $doc->button("Choisir");
		print "\n</form>";
		print "\n<form style=\"display:inline\" action=\"config.php\" method=POST>";
		print $doc->button($doc->lang["modifier_donnees_annuler"]);
		print "\n</form>";
		$doc->close();
		die();
	}
	else $doc->setOption("TVAOpChecked", "True");
}



if($_SESSION["crypt_algo"] && function_exists(mcrypt_list_algorithms)) //test de l'existence des modules de cryptographies
{
	$modules = mcrypt_list_algorithms();
	$modes = mcrypt_list_modes();
	$test_crypt=FALSE;
	foreach(array("rc2", "blowfish", "tripledes") as $module)
	{
		array_unshift($modules, $module);
	}
	foreach($modules as $module)
	{
		foreach($modes as $mode)
		{
			if($ivsize=mcrypt_get_iv_size($module, $mode))
			{
				$keysize = mcrypt_module_get_algo_key_size($module);
				$arr = $doc->open_and_prepare($doc->settings["principal"]);
				$arr = $doc->wipe_array($arr, "crypt_algo", "=");
				$arr = $doc->wipe_array($arr, "crypt_mode", "=");
				$arr = $doc->wipe_array($arr, "ivsize", "=");
				$arr = $doc->wipe_array($arr, "keysize", "=");
				array_push($arr, "crypt_algo=$module");
				array_push($arr, "crypt_mode=$mode");
				array_push($arr, "ivsize=$ivsize");
				array_push($arr, "keysize=$keysize");
				$doc->close_and_write($arr, $doc->settings["principal"]);
				break 2	;
			}
		}
	}
}

//----------fin du test des variables globales------------------------


/*******************************************
ETAT DE LA CONFIGURATION (page par défaut)
*******************************************/
$doc->getOptions("force");
$doc->getOptionsPerso("force");
if($_SESSION["optionsFile"]) //Devrait être inutile, car pas moyen d'arriver ici sinon (il y a un die dans etude::getOptions() sinon). Mais on ne sait jamais !
{
	#Détermination de l'utilisateur apache
	$apache = getenv("APACHE_RUN_USER")?getenv("APACHE_RUN_USER"):"APACHE";
	echo "\n<h2>{$doc->lang["config_config_h2"]}</h2>";
	
	#Vérification de l'installation de PHP
	$add = " <a href=\"./config.php?checkPhpInstall=true\">{$doc->lang["config_config_new_check"]}</a>";
	if($_SESSION["fonctionsDispo"] == $_SESSION["version"]) $doc->catchError("100-016#-#$add", 0);
	else $doc->catchError("100-017#-#$add", 4);
	
	if($doc->errorContSet[0])
	{
		echo $doc->echoError("noStop");
		$doc->close();
		die();
	}
	
	#Vérification de l'existence de la base de donnée choisie
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?checkDb=true\">{$doc->lang["config_config_new_check"]}</a>";
	if($_SESSION["dbName"] && !$doc->errorContSet[0]) $doc->catchError("100-018#-#{$_SESSION["dbName"]}#$add", 0);
	else $doc->catchError("100-019#-#$add", 4);
	
	#Vérification des méthodes de connection
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?checkAuth=true\">{$doc->lang["config_config_new_check"]}</a>";
	if($_SESSION["connectionMethods"] && !$doc->errorContSet[0]) $doc->catchError("100-027#-#$add", 0);
	else $doc->catchError("100-028#-#$add", 4);
	
	#Vérification des options de connection à MySQL
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?checkDbData=true\">{$doc->lang["config_config_new_check"]}</a>";
	if($_SESSION["dbAdmin"] && isset($_SESSION["dbPwd"]) && !$doc->errorContSet[0]) $doc->catchError("100-020#-#$add", 0);
	else $doc->catchError("100-021#-#$add", 4);
	
	#Vérification des valeurs de TVA par dossier
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?checkTVADossier=true\">{$doc->lang["config_config_new_check"]}</a>";
	if($_SESSION["TVADossierChecked"] && !$doc->errorContSet[0]) $doc->catchError("100-041#-#$add", 0);
	else $doc->catchError("100-042#-#$add", 4);
	
	#Vérification de l'état de définition des tables
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?updateTables=true\">{$doc->lang["config_config_new_check"]}</a>";
	if($_SESSION["tablesUpdated"] == $_SESSION["version"] && !$doc->errorContSet[0]) $doc->catchError("100-035#-#$add", 0);
	else $doc->catchError("100-036#-#$add", 4);
	
	#Vérification des valeurs par défaut des tables
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?updateTablesDefault=true\">{$doc->lang["config_config_new_check"]}</a>";
	if($_SESSION["tablesDefaultUpdated"] == $_SESSION["version"] && !$doc->errorContSet[0]) $doc->catchError("100-039#-#$add", 0);
	else $doc->catchError("100-040#-#$add", 4);
	
	#Vérification des tables orphelines
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?checkOrphanTables=true\">{$doc->lang["config_config_new_check"]}</a>";
	if(($_SESSION["orphanTables"]|| !$doc->recuperationAssocies(True)) && !$doc->errorContSet[0]) $doc->catchError("100-047#-#$add", 0);
	else $doc->catchError("100-048#-#$add", 4);

/*	#Modification de la TVA dans les opérations - en l'état abandonné
	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?checkTVAOP=true\">{$doc->lang["config_config_new_check"]}</a>&nbsp;|&nbsp;<a href=\"./config.php?modifyTVAOP=true\">{$doc->lang["config_config_modify_TVA"]}</a>"; #Contrairement à la mise à jour de la TVA par dossier, qui doit être faite AVANT la mise à jour en raison d'un changement de type (varchar vers decimal), la mise à jour de la TVA par opération peut se faire après, car elle est rajoutée ultérieurement.
	
	#Vérification de la TVA dans les opérations - en l'état abandonné
	if($_SESSION["TVAOpChecked"] && !$doc->errorContSet[0]) $doc->catchError("100-043#-#$add", 0);
	else $doc->catchError("100-044#-#$add", 4);*/
	
	#Vérification des options de connection LDAP (seulement si la méthode LDAP est définie
	if(@in_array("ldap", $_SESSION["connectionMethods"]))
	{
		$add = ($doc->errorContSet[0]) ? "": " <a href=\"./config.php?checkDbData=true&db=ldap\">{$doc->lang["config_config_new_check"]}</a>";
		if($_SESSION["ldapServer"] && !$doc->errorContSet[0]) $doc->catchError("100-029#-#$add", 0);
		else $doc->catchError("100-030#-#$add", 4);
	}

	#Vérification de l'existence du répertoire des images automatiques
	$rep = "{$_SESSION["autoImagesPath"]}";
	$add = "<a href=\"file:///$rep \" target = _blank>$rep</a>";
	if(! is_dir($rep) && !@mkdir($rep))$doc->catchError("100-049#-#$add::055#-#$apache", 4);
	else
 	{
		#Vérification des droits du répertoire des images automatiques
		$rep = "{$_SESSION["autoImagesPath"]}";
		$add = "<a href=\"file:///$rep \" target = _blank>$rep</a>";
		if(is_writable($rep)) $doc->catchError("100-051#-#$add", 0);
		else $doc->catchError("100-050#-#$add::057#-#$apache", 4);
	}

	#Vérification de l'existence du répertoire des modèles
	$rep = "{$_SESSION["tplPath"]}";
	$add = "<a href=\"file:$rep \" target = _blank>$rep</a>";
	if(! is_dir($rep) && ! @mkdir($rep)) $doc->catchError("100-052#-#$add::056#-#$apache", 4);
 	else
 	{
		#Vérification des droits du répertoire des modèles
		$rep = "{$_SESSION["tplPath"]}";
		$add = "<a href=\"file:///$rep \" target = _blank>$rep</a>";
		if(is_writable($rep)) $doc->catchError("100-054#-#$add", 0);
		else $doc->catchError("100-053#-#$add::057#-#$apache", 4);
	}

	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./modify.php?mode=user\">{$doc->lang["config_config_new_check"]}</a>";
	if($doc->liste_acces() && !$doc->errorContSet[0]) $doc->catchError("100-023#-#$add", 0);
	else $doc->catchError("100-024#-#$add", 4);

	$add = ($doc->errorContSet[0]) ? "": " <a href=\"./modify.php?mode=partner\">{$doc->lang["config_config_new_check"]}</a>";
	if($doc->liste_utilisateurs() && !$doc->errorContSet[0]) $doc->catchError("100-025#-#$add", 0);
	else $doc->catchError("100-026#-#$add", 4);
}

echo $doc->echoError("noStop");
$doc->close();
die();



?>
