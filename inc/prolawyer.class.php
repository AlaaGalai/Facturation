<?php
/****
* Titre........... : Classe Prolawyer
* Description..... : Ensemble de fonctions pour la mise en place d'outils de gestion dans un site web
* version......... : 4.0
* date............ : 1.3.2010
* fichier......... : prolawyer.class.php
* Auteur.......... : Olivier Subilia (etudeav@users.sourceforge.net)
*
* remarques ...... : Cette classe s'appuie sur la classe functions.class.php, qui doit avoir été requise avant l'ouverture de la présente
* licence......... : The GNU General Public License (GPL) 
*					 http://www.opensource.org/licenses/gpl-license.html
*
****/
		
/*******************************************************************
*
*    class 
*
********************************************************************/

class prolawyer extends functions
{
	function __construct($connection = TRUE)
	{
		
		//quelques variables automatiques
		
		$this->accesPlus = "$";
		$this->accesMoins = "à";
		$this->noReload = array();
		
		//avec un développement constant, pour recharger correctement les nouvelles versions des pages
// 		header('Cache-Control: no-cache, max-age=0, must-revalidate');
			
		//Attention à l'exploit sur php_self
		$_SERVER["PHP_SELF"] = htmlentities($_SERVER["PHP_SELF"]);
		
		//GET et POST sont indifferentes mais POST a la préséance
		foreach($_GET as $a => $b) if(!isset($_POST["$a"])) $_POST["$a"] = $b;
		
		//variables de recherche de date, si indéfinies ou définies partiellement dans jour/mois/année et timestamp; timestamp a la préséance
		if(isset($_POST["timestamp_debut"]) AND isset($_POST["timestamp_fin"]) AND  $_POST["timestamp_debut"] AND $_POST["timestamp_fin"])
		{
			$_POST["jour_debut"]=date("d", "{$_POST["timestamp_debut"]}");
			$_POST["mois_debut"]=date("m", "{$_POST["timestamp_debut"]}");
			$_POST["annee_debut"]=date("Y", "{$_POST["timestamp_debut"]}");
			$_POST["jour_fin"]=date("d", "{$_POST["timestamp_fin"]}");
			$_POST["mois_fin"]=date("m", "{$_POST["timestamp_fin"]}");
			$_POST["annee_fin"]=date("Y", "{$_POST["timestamp_fin"]}");
		}
		
		//variables de recherche de date, si forcées dans forceAnnee. Après timestamp_debut et timestamp_fin pour avoir la préséance
		if(isset($_POST["forceAnnee"]) and $_POST["forceAnnee"])
		{
			$_POST["jour_debut"] = 1;
			$_POST["mois_debut"] = 1;
			$_POST["annee_debut"] = $_POST["forceAnnee"];
			$_POST["jour_fin"] = 31;
			$_POST["mois_fin"] = 12;
			$_POST["annee_fin"] = $_POST["forceAnnee"];
		}
		
		#PHP s'obstine désormais à interdire d'obtenir le fuseau horaire du système sans hurler que c'est dangereux. Alors on contourne...
		@$defaultTZ = date_default_timezone_get();
		date_default_timezone_set($defaultTZ);
		
		//définition des dates de recherche par défaut, nécessaires pour TVA et benefice
		if(isset($_POST["secteur"]) AND ($_POST["secteur"] == "tva"|| $_POST["secteur"] == "benefice"))
		{
			if(!isset($_POST["jour_debut"])) $_POST["jour_debut"]=1;
			if(!isset($_POST["mois_debut"])) $_POST["mois_debut"]=1;
			if(!isset($_POST["annee_debut"])) $_POST["annee_debut"]=date("Y",time());
			//Par défaut, jusqu'au jour en cours pour la TVA et jusqu'à la fin de l'année pour le bénéfice
			if(!isset($_POST["jour_fin"])) $_POST["jour_fin"]=$_POST["secteur"] == "tva"?date("d",time()):31;
			if(!isset($_POST["mois_fin"])) $_POST["mois_fin"]=$_POST["secteur"] == "tva"?date("m",time()):12;
			if(!isset($_POST["annee_fin"])) $_POST["annee_fin"]=date("Y",time());
		}
		
		//on peut ensuite reconstruire timestamp_*
		if(isset($_POST["jour_debut"]) AND isset($_POST["mois_debut"]) AND isset($_POST["annee_debut"]) AND isset($_POST["jour_fin"]) AND isset($_POST["mois_fin"]) AND isset($_POST["annee_fin"]) AND basename($_SERVER["PHP_SELF"], ".php") != "maj_op")
		{
			if($_POST["mois_debut"] && $_POST["jour_debut"] && $_POST["annee_debut"]) $_POST["timestamp_debut"]=mktime(0,0,0, $_POST["mois_debut"], $_POST["jour_debut"], $_POST["annee_debut"]);
			if($_POST["mois_fin"] && $_POST["jour_fin"] && $_POST["annee_fin"]) $_POST["timestamp_fin"]=mktime(23,59,59, $_POST["mois_fin"], $_POST["jour_fin"], $_POST["annee_fin"]);
		}
		
		#GPC ne me va pas dans $_REQUEST...
		unset($_REQUEST);
		$_REQUEST = array();
		foreach($_POST as $n => $v) $_REQUEST[$n] = $v;
		
		#Gestion de la déconnexion forcée
 		if(isset($_REQUEST["deconnect"])) $_SESSION = array();

		
		#Il faut impérativement tester l'existence de MySQL, sans quoi le programme plantera, et le fera silencieusement sur de trop nombreuses distributions
		if(!function_exists("mysqli_query"))
		{
			error_reporting(7);
			$this->noLangRequire=True;
			$this->catchError("040-101:111", 4);
			echo $this->echoError(); //C'est une erreur fatale, donc si la fonction manque, le fichier die() dans echoError();
		}
		
		//options spéciales pour prolawyer-client
		if(preg_match("#prolawyer#", $_SERVER["HTTP_USER_AGENT"])) $this->prolawyerClient = True;
		else $this->prolawyerClient = False;
		if(preg_match("#androlawyer#", $_SERVER["HTTP_USER_AGENT"])) $this->androlawyerClient = True;
		else $this->androlawyerClient = False;
// 		$this->prolawyerClient = False;
// 		$this->prolawyerClient = True;

		#Gestion des différents modes: csv (export CSV), pdf (création d'un PDF comme attachement, partiellement implémenté), noHtml (csv ou pdf), print (impression, format HTML)
		$this->csv = False;
		if(isset($_POST["csv"]) && $_POST["csv"]) $this->csv = True;
		$this->pdf = False;
 		if(isset($_POST["pdf"]) && $_POST["pdf"]) $this->pdf = new PDF;
 		$this->noHtml = False;
 		if($this->pdf || $this->csv) $this->noHtml = True;
		$this->print = False;
 		if(isset($_POST["print"]) && $_POST["print"]) $this->print = True;
 
 		$this->debugNow("Début de Prolawyer($connection)");
 		error_reporting(E_ALL & ~E_NOTICE);
 		ini_set("display_errors", 1);
// 		echo "Le niveau d'erreur est ".error_reporting();
		if(function_exists("mysqli_query"))
		{
			$this->isConnected = False;
			$this->getRelPath();
			$this->getVersion($connection); // A demander à chaque page. Cela prend un tout petit peu plus de temps mais assure qu'il n'y a jamais de problème de mise à jour. On peut ensuite retravailler le menu configuration, en distinguant la page de mise à jour des autres pages de configuration.
			$this->getOptions($connection); //options passées en session. En principe appelées une seule fois, sinon la fonction ressort sans relire
			$this->getOptionsPerso($connection); //options passées en session relatives à l'utilisateur. En principe appelées une seule fois, sinon la fonction ressort sans relire
			$this->getPagesOptions(); //options de la page, qui doivent être rechargées à chaque page
			$this->getActLang($connection);
			$this->getLangFile();
			$this->perm_list();
			$this->getNextValues();
			$this->registerPda();
			$this->registerOptionsPerso();
// 			if($connection === "guest") $a = $this->doConnectGuest();
// 			die("res: $a, $connection");
			if($connection && $connection !== "firstCheck")
			{
				if($connection === "guest") $this->guestAllowed = True;
// 				if($connection === "guesta") $isok = $this->doConnectGuest();
// 				else $this->connection("noUserSet", "noPwdSet", false);
				$this->connection();
// 				die("res: $isok, $connection");
				$this->registerDb();
			}
			$this->getTemplates();
		}
		$this->docTypes = array("lettre", "procedure", "facture", "other", "projet", "client_doc");
		$_SESSION["username"] = $this->getUserName();
	}
	
	
	/*Fonctions de gestion des options et des chemins*/
	
	function getRelPath()
	{
		$this->anchor = basename($_SERVER["PHP_SELF"], ".php");
		$this->actPath = dirname($_SERVER["PHP_SELF"]);
		$this->rel_file_name=$this->anchor;
		$this->settings["root"] = "./";
		while (!is_file("root.php"))
		{
			if($this->settings["root"] == "./")
			{
				$this->cur_dir=getcwd();
				$this->settings["root"] = "";
			}
			chdir("..");
			$this->settings["root"] .= "../";
		}
		$this->settings["path"] = getcwd();
		if($this->settings["root"] != "./")
		{
			chdir ($this->cur_dir);
			$this->rel_dir = trim(substr(preg_replace("#(\\|/)#", "_", getcwd()), strlen($this->settings["path"]) + 1));
			$this->anchor = $this->rel_dir."_".$this->anchor;
			$this->rel_file_name=$this->rel_dir."/".$this->rel_file_name;
		}
  		$this->rel_file_name .= ".php";
	}
	
	function getNextValues()
	{
	//pour le changement de base 
		if(($this->anchor == "operations" && $_POST["secteur"] == "tva") || $this->anchor == "liste_soldes" || $this->anchor == "ra")
		{ 
			$this->next_select=$this->anchor;
			$this->next_values="on";
		}else{  
			$this->next_select="resultat_recherche";
			$this->next_values="";
		}
	}
	
	function registerDb()
	{
		if(isset($_POST["new_av"]))  
		{ 
			$_SESSION["db"]=$_POST["new_av"];
			$this->setOptionsPerso("db", $_POST["new_av"]);
			$newOpt = $this->getUtilisateurOptions($_POST["new_av"]);
			foreach($newOpt as $n => $v) if($n != "user" && $n != "nou") $_SESSION["optionGen"]["$n"] = $v;
// 			$this->setCookie("base", $_POST["new_av"]);
		
		}
		$this->dbIsRegistring = true;
		if (!$_SESSION["db"] && $this->getSinglePersoOption("db")) $_SESSION["db"] = $this->getSinglePersoOption("db");
		if(!$_SESSION["db"])
		{
			$test=$this->liste_droits("_{$_SESSION["user"]}", true);
			foreach($test as $init => $arr) if(isset($test["$init"]["{$_SESSION["user"]}"]) && $test["$init"]["{$_SESSION["user"]}"]["lire"])
			{
// 				echo "<br>Bon pour la base '$init'";
				$_SESSION["db"] = $init;
				break;
			}
// 			echo "toto".$this->liste_des_droits;
// 			$this->tab_affiche($this->liste_des_droits);
		}
		if($_SESSION["db"]) $this->setOptionsPerso("db", $_SESSION["db"]);
		unset($this->dbIsRegistring);
// 		if (!$_SESSION["db"] && $this->getCookie("base")) $_SESSION["db"] = $this->getCookie("base");
			
		$_SESSION["session_avdb"] = $_SESSION["db"]."clients";
		$_SESSION["session_tfdb"] = $_SESSION["db"]."tarifs";
		$_SESSION["session_opdb"] = $_SESSION["db"]."op";
		$this->avocat = $this->init_to_name();
//  		$this->tab_affiche(2);
	}
	
	function registerPda()
	{
		if(isset($_REQUEST["pda"]))  
		{ 
			$_SESSION["db"]=$_POST["new_av"];
			$this->setCookie("pda", $_REQUEST["pda"]);
// 			echo "pda set in request";
		}
// 		else echo "pda not set in request";
		if($this->getCookie("pda") == "pda") $this->pdaSet = True;
		else $this->pdaSet = False;
		if($this->pdaSet) $this->newPdaMenu = True;

	}
	
	
	function getLangs($class = "", $style="")
	{
		if(is_file("{$this->settings["root"]}override/lang/langs.php")) $f = "{$this->settings["root"]}override/lang/langs.php";
		else $f = "{$this->settings["root"]}lang/langs.php";
		$arr_options = $this->open_and_prepare($f);

		$selOptions = "";
		foreach($arr_options as $option)
		{
			list($nom, $base, $engname)=preg_split("#,#", $option);
			if(!$selOptions) $selOption = $base;
			$selected = ($_SESSION["lang"] == $base) ? "selected":"";
			if($selected) $selOption = $base;
			$selOptions .= "\n\t<option $selected class=\"sellang $class\" onclick=\"document.getElementById('selectLanguage').style.backgroundImage='url({$this->settings["root"]}/images/lang/$base.png)';document.getElementById('changelang').submit()\" style=\"background-image:url({$this->settings["root"]}/images/lang/$base.png);$style\" value=\"$base\">$base</option>";
		}
		$selOptions = "\n<select onchange=\"document.getElementById('changelang').submit()\" name=langue_choisie style=\"background-image:url({$this->settings["root"]}/images/lang/$selOption.png);$style\" id=\"selectLanguage\" class=\"sellang $class\">$selOptions\n</select>";
		//$selOptions .= $this->button("#invisible#");
		return $selOptions;
	}

	function getModes($class = "", $style="")
	{
		$arr_options = array("desktop", "pda");

		$selOptions = "";
		$selOption = "desktop";
		foreach($arr_options as $mode)
		{
			$selected = ($this->pdaSet && $mode == "pda") ? "selected":"";
			if($selected) $selOption = $mode;
			$selOptions .= "\n\t<option $selected class=\"sellang $class\" onclick=\"document.getElementById('selectMode').style.backgroundImage='url({$this->settings["root"]}/images/$mode.png)';document.getElementById('changemode').submit()\" style=\"background-image:url({$this->settings["root"]}/images/$mode.png);$style\" value=\"$mode\">$mode</option>";
		}
		$selOptions = "\n<select onchange=\"document.getElementById('changemode').submit()\" name=pda style=\"background-image:url({$this->settings["root"]}/images/$selOption.png);$style\" id=\"selectMode\" class=\"sellang $class\">$selOptions\n</select>";
		//$selOptions .= $this->button("#invisible#");
		return $selOptions;
	}

	function registerLocale($force=false)
	{
		if($_SESSION["locale"] && !$force && !$_POST["langue_choisie"])
		{
			$locale = setlocale(LC_ALL, $_SESSION["locale"]);
		}
		$tests = $this->open_and_prepare("{$this->settings["root"]}lang/langs.php");
		$lang = $_SESSION["lang"];
		foreach($tests as $test)
		{
			list(, $l, , $cCodes) = preg_split("#,#", $test);
			if($l == $lang)
			{
				$locales = array();
				$suffixes = array("", ".utf8", ".UTF8", "@euro", "@EURO", ".ISO-8859-15", ".ISO-8859-1");
				$codes = explode(";", $cCodes);
				foreach($codes as $code) foreach($suffixes as $suffixe) $locales[] = "{$lang}_{$code}{$suffixe}";
			}
		}
		$locale = setlocale(LC_ALL, $locales);
		$_SESSION["locale"] = $locale;
	}
	
        function init_to_name($init="")
        {
                $name="";
                if($init=="") if(isset($_SESSION["db"])) $init=$_SESSION["db"];
                $query = "select nom from utilisateurs where initiales like '$init'";
                @$ex=mysqli_query($this->mysqli, $query);
                if($ex)
                {
                        while($row=mysqli_fetch_array($ex)) return $row["nom"];
                }
        }
	
	
	function setOptionsPerso($option, $value, $user ="")
	{
// 		echo "<br>setting1: $option, $value, $user";
// 		$this->tab_affiche(2);
		//inutile sans $this->mysqli
		if(! $this->mysqli)
		{
			$connect = mysqli_connect($_SESSION["mysqlServer"], $_SESSION["dbAdmin"], $_SESSION["dbPwd"], "", $_SESSION["mysqlPort"]);
			$db = mysqli_select_db($connect, $_SESSION["dbName"]);
		}
		else $connect = $this->mysqli;
		if(!$connect) return;
// 		echo "<br>setting2: $option, $value, $user";
		if(!$user) $user = $_SESSION["user"];
// 		echo "<br>setting3: $option, $value, $user";
		if(!$user) return;
		$value = $this->optSerialize($value);
// 		echo "<br>setting: $option, $value, $user";
// 		$value = "toto";
// 		$q1 = "insert into acces set user = '$user', $option = '$value'";
// 		$e1 = mysqli_query($this->mysqli, $q1); //ne marchera pas si l'utilisateur est déjà existant
// 		$q = "update acces set $option = '$value' where user like '$user'";
// 		$e = mysqli_query($this->mysqli, $q);
		$q = "insert into acces set user = '$user', $option = '$value' ON DUPLICATE KEY UPDATE $option = '$value';";
		$e = mysqli_query($connect, $q);
		if($user == $_SESSION["user"]) $_SESSION["$option"] = $value;
// 		echo "<br>$user: $option -> $value :$q<br>";
// 		$q = "select * from acces where user like '$user'";
// 		$e = mysqli_query($connect, $q);
// 		echo mysqli_error($connect);
// 		while ($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $a => $b) echo "$a: '$b'. ";
		
		/*codes de retour: 
		0 = raté, 
		1 = insertion réussie d'une nouvelle donnée mais mise à jour ratée (ne devrait jamais se produire), 
		2 = modification réussie d'une donnée existante, 
		3 = insertion réussie d'une nouvelle donnée*/
		return $e1 + 2* $e;
	}

	function setUtilisateurOption($option, $value, $init ="")
	{
		if(!$init) $init = $_SESSION["db"];
		$qi = "select * from utilisateurs where initiales like '$init'";
		$ei = mysqli_query($this->mysqli, $qi);
		$n  = mysqli_num_rows($ei);
		if($n)	$q = "update utilisateurs set $option = '$value' where initiales like '$init'";
		else	$q = "insert into utilisateurs set initiales = '$init', $option = '$value'";
		#echo "<br>Exécuté. '$q'";
		$e = mysqli_query($this->mysqli, $q);
		if($init == $_SESSION["db"]) $_SESSION["optionGen"]["$n"] = $v;

		/*codes de retour: 
		0 = raté, 
		1 = insertion réussie d'une nouvelle donnée
		2 = modification réussie d'une donnée existante*/
		return ($n + 1)* $e;
	}

	function registerOptionsPerso()
	{
		foreach(array("nb_affiche") as $nom)
		{
// 			echo "<br>aff: {$_POST["nb_affiche"]}";
			if(isset($_POST["$nom"]))
			{
// 				echo "... setting it";
				$this->setOptionsPerso("$nom", $_POST["$nom"]);
				unset($_POST["$nom"]);
			}
// 			echo "<br>affnext: {$_SESSION["nb_affiche"]}";
		}
	}
	
	function unsetOptionsPerso($option, $user ="")
	{
		if(!$user) $user = $_SESSION["user"];
		$q = "update acces set $option = '' where user like '$user'";
		$e = mysqli_query($this->mysqli, $q);
		if($user == $_SESSION["user"]) unset($_SESSION["$option"]);
		
		return $e;
	}
	
	function getUserName($name="default")
	{
		if ($name === "default") $name = $_SESSION["user"];
		foreach(explode("\n", $_SESSION["optionGen"]["soustraitants"]) as $opt)
		{
			list($nom, $user, $gere) = preg_split("#,#", $opt);
			if(trim($user) == trim($name)) return trim($nom);
		}

	}
	
	function getUtilisateurOptions($utilisateur)
	{
		$arr=array();
		//default user options
		$q = "select * from utilisateurs where nom like 'tableDefaultValue'";
		$e = mysqli_query($this->mysqli, $q);
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $n => $v) if($n != "user" && $n != "nou")
		{
			$arr["$n"] = $v;
		}
				
		
		//user options
		$q = "select * from utilisateurs where initiales like '$utilisateur'";
		$e = mysqli_query($this->mysqli, $q);
		if(!$e) return array();
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $n => $v) if($n != "user" && $n != "nou")
		{
			if($v)
			{
				$arr["$n"] = $v;
			}
		}
		return $arr;
	}
	
	function getSinglePersoOption($option, $acces = "default")
	{
		$acces = ($acces == "default") ? $_SESSION["user"]:$acces;
		$q = "select db from acces where user like '$acces'";
		$e = mysqli_query($this->mysqli, $q);
		while ($r = mysqli_fetch_array($e))
		{
			$_SESSION["$option"] = $r["$option"];
		}
		return $r["$option"];
	}
	
	function getOptionsPerso($arg = "", $user ="")
	{
		if(!$user AND !$_SESSION["user"])
		{
			return;
		}
		elseif(!$user AND $_SESSION["user"])
		{
			if(!$arg || $arg == "firstCheck") return; //si on n'a pas forcé la relecture des options, on sort, parce que cela veut dire que c'est déjà défini.
			$user = $_SESSION["user"];
		}
		else $arr = array();
		if(!$this->doConnectDb($_SESSION["dbAdmin"], $_SESSION["dbPwd"])) return array();
		
		//default user options
		$q = "select * from utilisateurs where nom like 'tableDefaultValue'";
		$e = mysqli_query($this->mysqli, $q);
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $n => $v) if($n != "user" && $n != "nou")
		{
			$v = $this->optUnserialize($v);
			if($user == $_SESSION["user"]) $_SESSION["optionGen"]["$n"] = $v;
			else $arr["$n"] = $v;
		}
				
		
		//user options
		$q = "select * from utilisateurs where initiales like '{$_SESSION["db"]}'";
		//echo "<br>$q";
		$e = mysqli_query($this->mysqli, $q);
		if(!$e) return array();
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $n => $v) if($n != "user" && $n != "nou")
		{
			if($v)
			{
				if($user == $_SESSION["user"]) $_SESSION["optionGen"]["$n"] = $v;
				else $arr["$n"] = $v;
			}
		}
		
		//default acces options
		$q = "select * from acces where user like 'tableDefaultValue'";
		$e = mysqli_query($this->mysqli, $q);
		if(!$e) return array();
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $n => $v) if($n != "user")
		{
			$v = $this->optUnserialize($v);
			if($user == $_SESSION["user"]) $_SESSION["$n"] = $v;
			else $arr["$n"] = $v;
		}
		
		//acces options
		$q = "select * from acces where user like '$user'";
		$e = mysqli_query($this->mysqli, $q);
		if(!$e) return array();
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $n => $v) if($n != "user")
		{
			if($v)
			{
// 			echo "<br>$n => $v";
				$v = $this->optUnserialize($v);
// 			echo "<br>$n => $v";
				if($user == $_SESSION["user"]) $_SESSION["$n"] = $v;
				else $arr["$n"] = $v;
			}
		}
// 		echo "$user == {$_SESSION["user"]}";
// 		echo $this->tab_affiche($_SESSION["imap"]);
		
		if(!$this->dbIsRegistring) $this->registerDb(); //sans quoi on a une boucle infinie avec registerDb();
		$this->right = $_SESSION["type"]; // rétrocompatibilité avec l'appel à $this->right qui figure partout.
		return $arr;
	}
	
	function optSerialize($s)
	{
		if(is_array($s)) $s = "serialized".serialize($s);
		return $s;
	}
	
	function optUnserialize($s)
	{
		if(preg_match("#^serialized(.*)#", $s, $regs)) $s = unserialize($regs[1]);
		return $s;
	}
	
	function getPagesOptions()
	{
		//réglages personnels (utilisateurs) qui dépendent de $this->settings[root] ou qui sont susceptibles de changer. Ces réglages doivent être appelés obligatoirement à chaque page, même si getOptions() n'est pas appelée
		if(isset($_SESSION["session_utilisateur"])) $this->styles_user="{$_SESSION["optionsPath"]}{$_SESSION["slash"]}styles_".$_SESSION["session_utilisateur"].".css";
		else $this->styles_user="no_file";
		$this->styles_template=$this->settings["root"]."templates/styles_default.css";
		$this->styles_alternate=$this->settings["root"]."templates/custom/styles_alternate.css";
		if(is_file("{$this->styles_user}")) $_SESSION["stylesfile"]=$this->styles_user;
		elseif(is_file("{$this->styles_alternate}")) $_SESSION["stylesfile"]=$this->styles_alternate;
		else $_SESSION["stylesfile"] = $this->styles_template;
		//echo ($_SESSION["stylesfile"]. " " .$this->styles_alternate);
	}

	function setOption($option, $value, $needle = "=")
	{
		$arr = $this->open_and_prepare($_SESSION["optionsFile"]);
		$arr = $this->wipe_array($arr, $option, $needle);
		if(is_array($value))
		{
			while($arr != $arr2)
			{
				$arr2 = $arr;
// 				echo "tata $arr2 $arr titi";
				$arr = $this->wipe_array($arr2, $option, $needle);
			}
			foreach($value as $val) array_push($arr, $option.$needle.$val);
		}
		else array_push($arr, $option.$needle.$value);
		$this->close_and_write($arr, $_SESSION["optionsFile"]);
	}
	
	function unsetOption($option, $needle = "=")
	{
		$arr = $this->open_and_prepare($_SESSION["optionsFile"]);
		$arr = $this->wipe_array($arr, $option, $needle);
		$this->close_and_write($arr, $_SESSION["optionsFile"]);
		if(isset($_SESSION["$option"])) unset ($_SESSION["$option"]);
	}
	
	function defaultBases($personne = "", $force = false)
	{
		foreach(array("lire", "lire_agenda") as $module)
		{
			if(isset($_SESSION["{$module}DefaultBase"]) && !$force) return;
			
		}
	}
	
	function getVersion($exit=true)
	{
		//version du programme
		$version = is_file("{$this->settings["root"]}version.txt") ? trim(file_get_contents("{$this->settings["root"]}version.txt")):"no_version";
// 		echo "Version: '$version'\n<br>VERSION: {$_SESSION["version"]}";
		if($exit && $exit != "noExit" && (($_SESSION["version"] && $_SESSION["version"] != $version) || ($_SESSION["tablesUpdated"] && $_SESSION["tablesUpdated"] != $version) || ($_SESSION["fonctionsDispo"] && $_SESSION["fonctionsDispo"] != $version)))
		{
				$location = "{$this->settings["root"]}config/index.php?checkState=on";
				header("Location: $location");
				die("Version pas à jour. Veuillez cliquer sur <a href=\"$location\">$location</a>");
		}
		$_SESSION["version"] = $version;
// 		echo "Version: '$version'\n<br>VERSION: {$_SESSION["version"]}";
		
	}
	
	function getTemplates($force = False)
	{
		if(! $force && isset($_SESSION["templates"])) return;
		##Gestion des modèles
		$tplPathPerso = $_SESSION["tplPath"] .$_SESSION["slash"].substr($_SESSION["db"], 0, 2);
		$tplPath = $_SESSION["tplPath"] .$_SESSION["slash"]. "00";
		$pMatch = "(^.*)\.([^.]+$)";
		$this->usableTemplates = array();
		
		$this->knownExts = array(
		"ott",
		"odt",
		"stw",
		"sxw",
		"rtf",
		"txt"
		);
		$files = array();
				
		$lPriority = count($this->knownExts);
		foreach(array($tplPath, $tplPathPerso) as $path)
		{
			if(!is_dir($path)) @mkdir($path);
			if(is_dir($path))
			{

				$dir = opendir($path);
				while($file = readdir($dir))
				{
					if(preg_match("/\.([^.]+)$/", $file, $reg) && $reg[1] != "datas")
// 					if(preg_match("#$pMatch#", $file, $reg))
					{
						$ext = $reg[1];
						$fileData = "$path{$_SESSION["slash"]}$file.datas";
						$fileType = "other";
						if(is_file($fileData))
						{
							$f = file("$fileData");
							foreach($f as $line)
							{
				// 				echo "<br>$line";
								list($key, $value) = preg_split("#=#", $line);
								$value=trim($value);
								if($key == "filetype") $fileType = $value;
								
							}
						}
						$dests = array();
						if($fileType == "lettre") $dests = array("client", "pa", "aut", "ca", "pj");
						if($fileType == "facture") $dests = array("client", "aut", "pj");
						if($fileType == "proce") $dests = array("client", "aut", "pj");
						$this->usableTemplates["$file"] = array("type" => $fileType, "personne" => $dests);
// 						$ext = $reg[2];
						$files[$file] = array_search($ext, $this->knownExts);
					}
				}

				asort ($files);
			}
		}
		$_SESSION["facture"] = "";
		$_SESSION["factures"] = "";
		$_SESSION["templates"] = "";
		$_SESSION["globalTemplates"] = "";
		foreach($files as $file =>$priority)
		{
			preg_match("#$pMatch#", $file, $reg);
			$ext = $reg[2];
			$priority = array_search($ext, $this->knownExts);			
			if($reg[1] == "facture" && $priority < $lPriority)
			{
				$_SESSION["facture"] = $file;
				$lPriority = $priority;
			}
			if($reg[1] == "facture") $_SESSION["factures"] .= "$file;";
			else $_SESSION["templates"] .= "$file;";
			$_SESSION["globalTemplates"] .= "$file;";
		}
	}
	
	function getOptions($arg = "")
	{
			
		if(!isset($_SESSION["optionsFile"]) || $arg === "force" ||$arg === "firstCheck" ||$arg === "forceNoer")
		{
			//Il faut détruire toute la session, sauf les informations de connection et de version (qui ne doivent être détruites que si on le demande expressément: ce n'est pas une option mais une donnée permanente)
			$user    = $_SESSION["user"];
			$pwd     = $_SESSION["pwd"];
			$db      = $_SESSION["db"];
			$version = $_SESSION["version"];
			$_SESSION = array();
			$_SESSION["user"]    = $user;
			$_SESSION["pwd"]     = $pwd;
			$_SESSION["db"]      = $db;
			$_SESSION["version"] = $version;
			
			//idem pour les options, sauf root et path qui ne sont pas lues dans le fichier
			$Root	= $this->settings["root"];
			$Path	= $this->settings["path"];
			$this->settings = array("root" => $Root, "path" => $Path);
			

			//commençons par vérifier quel est le répertoire de base du programme prolawyer
			$this->cur_dir=getcwd();
			while(!is_file("./root.php"))
			{
				chdir("..");
			}
			$this->prolawyerPath = getcwd();
			$_SESSION["softName"] = trim(file_get_contents("root.php"));
			chdir($this->cur_dir);
			
			$_SESSION["prolawyerPath"] = $this->prolawyerPath;

			/*options du programme*/
			
			//Variables particulières
			
			//D'abord vérifier si l'on est sous windows ou sous un *nix (linux, BSD, MacOS X) mais en simplifiant un peu
			if(is_dir(getenv("programfiles"))) // on semble être sous windows
			{
				$slash = "\\";
				$optionsPath = getenv("programfiles")."\prolawyer";
				$autoOptionsPath = getenv("programfiles")."\prolawyer";
				if(!is_dir("$optionsPath") && !@mkdir("$optionsPath"))
				{
					$this->catchError("100-009#-#$optionsPath", 4);
				}
				else
				{
					//$this->catchError("100-011#-#$optionsPath", 0);
					$tempOptionsPath = "$optionsPath";
				}
			}
			
//			if(is_dir("/etc")) // on semble être sous un *nix //Supprimé au profit d'un "else", cf ligne suivante
			else // on semble être sous un *nix. Ne pas traiter /etc permet d'utiliser la directive open_basedir placée à /etc/prolawyer
			{
				$slash = "/";
				$optionsPath = "/etc/prolawyer";
				$autoOptionsPath = "/etc/prolawyer";
				require_once($this->settings["root"]."lang/fr.php");
				if(!is_dir("$optionsPath") && !@mkdir("$optionsPath"))
				{
					$this->catchError("100-009#-#$optionsPath", 4);
				}
				else
				{
					//$this->catchError("100-011#-#$optionsPath", 0);
					$tempOptionsPath = "$optionsPath";
				}
			}
			
			$_SESSION["slash"] = $slash;
			
			//A ce stade, on devrait avoir un répertoire automatique. Si tel n'est pas le cas
			
			if(!isset($tempOptionsPath))
			{
				$testFile = "{$this->prolawyerPath}{$slash}system";
				if (is_file("{$this->prolawyerPath}{$slash}system") && file_get_contents("{$this->prolawyerPath}{$slash}system")) //en désespoir de cause, on cherche à savoir si le nom du répertoire a été inséré manuellement
				{
					$optionsPath = file_get_contents("{$this->prolawyerPath}{$slash}system");
					if(!is_dir("$optionsPath") && !@mkdir("$optionsPath"))
					{
						$this->catchError("100-012#-#{$this->prolawyerPath}{$slash}system#$optionsPath", 4);
					}
					else
					{
						//$this->catchError("100-011#-#$optionsPath", 0);
						$tempOptionsPath = "$optionsPath";
					}
				}
				elseif (is_file("{$this->prolawyerPath}{$slash}system")) 
				{
					$sysUser = getenv("APACHE_RUN_USER");
					$sysUser = $sysUser ? " $sysUser":"";
					$this->catchError("100-013#-#$sysUser#{$this->prolawyerPath}{$slash}system#$autoOptionsPath", 4);
				}
				else
				{
					$sysUser = getenv("APACHE_RUN_USER");
					$sysUser = $sysUser ? " $sysUser":"";
					$this->catchError("100-014#-#{$this->prolawyerPath}{$slash}system#$sysUser#$autoOptionsPath", 4);
				}
			}
			
			
			if(isset($tempOptionsPath))
			{
				foreach($this->open_and_prepare("$tempOptionsPath{$slash}liste") as $liste)
				{
					list($aPath, $install) = preg_split("#,#", $liste);
					if($aPath == $this->prolawyerPath)
					{
						$_SESSION["optionsPath"] = $install;
						$_SESSION["optionsFile"] = "$install{$slash}settings";
					}
					//echo "<br>'$aPath' == '{$this->prolawyerPath}'";
				}
				if(!isset($_SESSION["optionsPath"]))
				{
					$tListe = $this->open_and_prepare("$tempOptionsPath{$slash}liste");
					$tListe = $this->wipe_array($tListe, $this->prolawyerPath);
					for($x=1;;$x++)
					{
						if(!is_dir("$tempOptionsPath{$slash}install$x") && !is_file("$tempOptionsPath{$slash}install$x"))
						{
							//echo "On tente de créer  $tempOptionsPath{$slash}install$x";
							if(@mkdir("$tempOptionsPath{$slash}install$x"))
							{
								if($this->close_and_write(array(), "$tempOptionsPath{$slash}install$x{$slash}settings"))
								{
									$_SESSION["optionsPath"] = "$tempOptionsPath{$slash}install$x";
									$_SESSION["optionsFile"] = "{$_SESSION["optionsPath"]}{$slash}settings";
									//echo "File: {$_SESSION["optionsFile"]}";
								}
								else
								{
									$sysUser = getenv("APACHE_RUN_USER");
									$sysUser = $sysUser ? " par $sysUser":"";
									$this->catchError("100-022#-#$tempOptionsPath{$slash}install$x{$slash}settings#$sysUser", 4);
								}
							}
							else
							{
								$sysUser = getenv("APACHE_RUN_USER");
								$sysUser = $sysUser ? " par $sysUser":"";
								$this->catchError("100-022#-#$tempOptionsPath{$slash}install$x#$tempOptionsPath#$sysUser", 4);
							}
							break;
						}
					}
					$tListe[] = "{$this->prolawyerPath},$tempOptionsPath{$slash}install$x";
					$this->close_and_write($tListe, "$tempOptionsPath{$slash}liste");
					if(!$_SESSION["optionsFile"]) $this->catchError("100-015#-#$tempOptionsPath{$slash}liste", 4);
				}				
			}
			
			if(is_file($_SESSION["optionsFile"]))
			{
				#Répertoire d'images
// 				$autoImagesPath = "{$_SESSION["optionsPath"]}{$slash}autoimages";
				$autoImagesPath = "{$_SESSION["prolawyerPath"]}{$slash}images{$slash}auto";
				if (! is_dir($autoImagesPath)) @mkdir($autoImagesPath);
				$_SESSION["autoImagesPath"] = $autoImagesPath;
				
				#Répertoire de modèles
				$tplPath = "{$_SESSION["optionsPath"]}{$slash}templates";
				if (! is_dir($tplPath)) @mkdir($tplPath);
				$_SESSION["tplPath"] = $tplPath;
				
				foreach($this->open_and_prepare($_SESSION["optionsFile"]) as $opt)
				{
					list($k, $v) = preg_split("#=#", $opt);
					if(preg_match("#\[\]$#", $k))
					{
						$k = preg_replace("#(.*)\[\]$#", "\\1", $k);
// 						echo "<br>voici $k";
						$_SESSION["$k"][] = $v;
						$this->settings["$k"][] = $v;
					}
					else
					{
						$_SESSION["$k"] = $v;
						$this->settings["$k"] = $v;
					}
				}
			}
			
			
// 			echo "'$arg'" .$this->errSet;
			if($this->isError() && $arg == "firstCheck")
			{
				$location = "{$this->settings["root"]}config/index.php?checkState=on";
				header("Location: $location");
				die("mort (devrait renvoyer à $location");
			}
			if($arg != "forceNoer") echo $this->echoError();

		}
	}
	
	
	/*Fonction de gestion du document*/
	
	function unsetCookie($nom, $domain = "thisSoft")
	{
		$expire = time() - 3600;
		$this->setCookie($nom, "", $domain, $expire);
	}
	
	function setCookie($nom, $val, $domain="thisSoft", $expire="1year")
	{
		if($domain == "thisSoft")
		{
			if(!$this->settings["root"]) $this->getRelPath();
			if(!$_SESSION["softName"]) $_SESSION["softName"] = trim(file_get_contents("{$this->settings["root"]}root.php"));
			if (! $_SESSION["softName"]) $_SESSION["softName"] = "noName";
			$nom = "{$_SESSION["softName"]}_$nom";
		}
		if($expire == "1year") $expire = time() +31536000;
		setcookie($nom, $val, $expire);
		$_COOKIE["$nom"] = $val; //positionner le cookie ne sera autrement valable qu'au prochain chargement de la page
	}
	
	function getCookie($nom, $domain="thisSoft")
	{
		if($domain == "thisSoft")
		{
			if(!$this->settings["root"]) $this->getRelPath();
			if(!$_SESSION["softName"]) $_SESSION["softName"] = trim(file_get_contents("{$this->settings["root"]}root.php"));
			if (! $_SESSION["softName"]) $_SESSION["softName"] = "noName";
			$nom = "{$_SESSION["softName"]}_$nom";
		}
		if(isset($_COOKIE["$nom"])) return $_COOKIE["$nom"];
		else return false;
	}
	
	function getActLang($connection=true)
	{
// 		$this->do_return = True;
// 		$p1 =$this->tab_affiche();
// 		$s1 = $this->tab_affiche(2);
// 		$cSession = $_SESSION;
// 		$cPost = $_POST;
// 		die;
		//Traîne-t-on une ancienne option pas encore enregistrée ? NB: ce qui est utilisé ensuite, c'est la variable "lang". La variable "langue_choisie" est une valeur de transition, qui est en principe supprimée ensuite
		if($_SESSION["langue_choisie"]) $_POST["langue_choisie"] = $_SESSION["langue_choisie"];
		
		//1er cas: on vient de changer de langue, respectivement on a changé de langue à la page d'avant mais on n'a pas encore eu l'occasion de le noter
		if($_POST["langue_choisie"])
		{
			if($_SESSION["user"])
			{
				$this->setOptionsPerso("lang", $_POST["langue_choisie"]);
// 				die($_POST["langue_choisie"]);
				unset($_SESSION["langue_choisie"]);
				$_SESSION["lang"] = $_POST["langue_choisie"];
			}
			else
			{
				$_SESSION["langue_choisie"] = $_POST["langue_choisie"];
				$_SESSION["lang"] = $_POST["langue_choisie"];
			}
		}
		
		//2ème cas: la langue de la session n'existe pas. Cela peut provenir de plusieurs raisons
		if(!$_SESSION["lang"])
		{
			if($_SESSION["user"] && $connection && $connection !== "firstCheck")
			{
				//2.1: on essaye d'aller rechercher la langue dans les options utilisateur
				if($this->getOptionsPerso("lang")) $_SESSION["lang"] = $this->getOptionsPerso("lang");
			}
			if(!$_SESSION["lang"] && $this->getCookie("lang"))
			{
				//2.2: on essaye de voir s'il existe un cookie qui pourrait être utile
				$_SESSION["lang"] = $this->getCookie("lang");
			}
			if(!$_SESSION["lang"])
			{
				//2.3: en désespoir de cause, il faut que la langue soit le français
				$_SESSION["lang"] = "fr";
			}
		
		}
		if($_SESSION["lang"])
		{
			$this->setCookie("lang", $_SESSION["lang"]);
			$this->setOptionsPerso("lang", $_SESSION["lang"]);
		}
		$this->do_return = False;
// 		echo $p1;
// 		echo $s1;
	
	}
	function getLangFile($rad = "", $force = false)
	{
		#Variables à définir dans les fichiers de langue
		$temp_nodossier=$_REQUEST["nodossier"];
		$temp_id=$_REQUEST["id"];
		$temp_utilisateur=$_SESSION["user"];
		$temp_avocat=$this->init_to_name($_SESSION["db"]);
		
		$this->socConv = array();
		$this->socId = array();
		$this->missingLangs = "";
// 		$this->lang = array();
// 		$this->langD = array();
// 		$this->debugLang = True;
		
		if(!isset($this->langFiles["{$rad}"]) && !$force)
		{
			require("{$this->settings["root"]}lang/{$rad}fr.php");
 			if(is_file("{$this->settings["root"]}override/lang/fr.php"))
 			{
				require("{$this->settings["root"]}override/lang/fr.php");
                        }
                        if($this->anchor == "modules_modules")
                        {
                                foreach(array($_SESSION["optionsPath"].$_SESSION["slash"], $this->settings["root"]) as $tDir)
                                {
                                        $file = "{$tDir}modules/auto/{$_POST["moduleName"]}.dat.php";
                                        if(is_file($file)) require_once($file);
                                }
                                if(is_array($lM["fr"])) foreach($lM["fr"]as $nom=>$val) $langchoisie["$nom"]=$val;
                        }

			foreach($langchoisie as $n => $l)
			{
				if($this->debugLang)
				{
					$this->lang["$n"] = $this->smart_html("*[$n]*");
					$this->langD["$n"] = $l;
				}
				else $this->lang["$n"] = $this->smart_html("*$l*");
				if(preg_match("#modifier_donnees_societe_(.*)_(.*)#", $n, $regs))
				{
					$x2 = preg_replace("#modifier_donnees_societe_#", "", $n);
					$x2 = preg_replace("#_.*#", "", $x2);
					$this->societes["$x2"] = $this->lang["$n"];
					$this->socConv["{$regs[2]}"] = $this->lang["$n"];
					$this->socId["{$regs[2]}"] = $regs[1];
				}
			}
			$this->langFiles["{$rad}"] = True;
			unset ($langchoisie);
		}
		if(($_SESSION["lang"]) && is_file($this->settings["root"]."lang/{$rad}{$_SESSION["lang"]}.php")) $aLng = $_SESSION["lang"]; //soit que la langue ne soit pas choisie, soit qu'elle soit définie comme vide, soit qu'elle soit définie de manière erronée
		else $aLng = "fr";

		if(!isset($this->langFiles["{$rad}_$aLng"]) && !$force)
		{
			require($this->settings["root"]."lang/{$rad}$aLng.php");
  			if(is_file("{$this->settings["root"]}override/lang/$aLng.php"))
 			{
				require("{$this->settings["root"]}override/lang/$aLng.php");
                        }
                       if($this->anchor == "modules_modules") if(is_array($lM["{$_SESSION["lang"]}"])) foreach($lM["fr"]as $nom=>$val) $langchoisie["$nom"]=$val;

			foreach($langchoisie as $n => $l)
			{
				$this->lang["$n"] = $this->smart_html("$l");
				if(preg_match("#modifier_donnees_societe_(.*)_(.*)#", $n, $regs))
				{
					$x2 = preg_replace("#modifier_donnees_societe_#", "", $n);
					$x2 = preg_replace("#_.*#", "", $x2);
					$this->societes["$x2"] = $this->lang["$n"];
					$this->socConv["{$regs[2]}"] = $this->lang["$n"];
					$this->socId["{$regs[2]}"] = $regs[1];
				}
			}
			$this->langFiles["{$rad}_$aLng"] = True;
			unset ($langchoisie);
		}
		foreach($this->lang as $a => $b) if(preg_match("#^\*(.*)\*$#", $b, $regs)) $this->missingLangs .= "\$langchoisie[\"$a\"]=\"{$this->langD["$a"]}\";\n";

	}
	
	function incSource($file, $type = "j", $force = False)
	{
		if($this->forceInclude || $_REQUEST["forceInclude"]) $force = True;
		if ($type == "j")
		{
			if ($force) $type = "<script type=\"text/javascript\">".file_get_contents($file)."</script>";
			else $type = "<script type=\"text/javascript\" src=\"FILENAME\"></script>";
		}
		if ($type == "c")
		{
			if($force) $type = "<style type=\"text/css\">".file_get_contents($file)."</style>";
			else $type = "<link rel=\"stylesheet\" href=\"FILENAME\" type=\"text/css\" />";
		}
		if ($type == "i")
		{
			if($force) $type = "<link rel=\"icon\" href=\"data:image/x-png;base64,".base64_encode(file_get_contents($file))."\" type=\"image/x-png\" />";
			else $type = "<link rel=\"icon\" href=\"FILENAME\" type=\"image/x-png\" />";
		}
		if(! $force) $type = preg_replace("%FILENAME%", $file, $type);
		return $type;
	}
	
	function title($param=FALSE, $charset = "utf-8")
	{
		mb_internal_encoding($charset);
		if(!isset($this->title_name))
		{
			$this->title_variable=$this->anchor."_title";
			$this->html=TRUE;
			if(isset($this->title_variable)) $var=$this->title_variable;
			else $var="";
			if(isset($this->lang["$var"]))
			{
				$this->title_name=$this->lang["$var"];
				$this->add_title_name = " - ";
			}
			else
			{
				$this->title_name="";
				$this->add_title_name="";
			}
		}
		if($this->pdf)
		{
			$this->pdf->standardFont = "Arial";
			$this->pdf->setTitle($this->title_name);
			$this->pdf->addPage();
			$this->pdf->setFont($this->pdf->standardFont, 'B', '32');


			return;
		}
		if($this->csv) return;
		if($this->addStyle)
		{
			$addStyle = $this->getCookie("pda") == "on" ? "pda":"classic";
		}
		if ($_SESSION["user"]) $user = $_SESSION["user"];
		elseif($_REQUEST["start_utilisateur"]) $user = $_REQUEST["start_utilisateur"];
		else $user = "";
		$addData = ($user and $this->avocat)? "($user@{$this->avocat})":"";
		

		#Autres dérivés de prolawyer
		$patterns = array
		(
			"PROLVER" => $_SESSION["version"],
			"SPECVER" => specific_version,
			"USER" => $_SESSION["user"],
		);
		foreach($patterns as $a => $b)
		{
			$p[] = "#\{$a\}#";
			$r[] = $b;
		}
		
		if(defined("TITLE")) $this->titleSet = preg_replace($p, $r, TITLE);
		
		//die("'{$this->avocat}'");
		echo "<!DOCTYPE html>\n";
		echo "<html>";
		echo "\n	<head>";
		echo "\n		<meta name=\"viewport\" content=\"width=device-width\" />";
		echo "\n		<meta http-equiv=\"Content-Type\" content=\"text/html; charset=$charset\" />";
		if($this->titleSet) 	echo "\n		<title>{$this->titleSet}</title>";
		else echo "\n		<title>{$this->title_name}{$this->add_title_name} Prolawyer {$_SESSION["version"]} $addData</title>";
		echo "\n		<script type=\"text/javascript\">npA='{$this->lang["general_np"]}';mpA='{$this->lang["general_mp"]}';</script>";
		echo "\n		".$this->incSource("{$this->settings["root"]}js/javascript.js");
		//echo "\n		<meta name=\"viewport\" content=\"width=device-width, initial-scale=0.5\">";
		echo "\n		".$this->incSource("{$_SESSION["stylesfile"]}", "c");
		if($this->pdaSet) echo "\n		".$this->incSource("{$this->settings["root"]}templates/pda.css", "c");
		if($this->pdaSet) echo "\n		<script type=\"text/javascript\">pda=true;</script>";
		if($this->pdaSet) echo "\n		".$this->incSource("{$this->settings["root"]}js/pda.js");
// 		if($this->pdaSet) echo "\n		<meta name=\"viewport\" content=\"width=device-width\" />";
		if($this->pdaSet) echo "\n		<meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0, maximum-scale=1, user-scalable=0\" />";
		if($this->addStyle) echo "\n		".$this->incSource("{$this->settings["root"]}templates/{$this->addStyle}.$addStyle.css", "c");
		echo "\n		<script>var root = '{$this->settings["root"]}'</script>";
// 		$sysicon = is_file() ? : "{$this->settings["root"]}images/prolawyer.png";
		echo "\n		".$this->incSource(PROLAWYER_SYSICON, "i");
		if(is_array($this->titleAddons)) foreach($this->titleAddons as $addon) echo "\n		$addon";
		if(is_file("{$this->settings["root"]}specific/ADDCLASS.css")) echo "\n		".$this->incSource("{$this->settings["root"]}specific/ADDCLASS.css", "c");
		if($param) echo "\n		$param";
		echo "\n	</head>\n";
	}

	function body($table=0, $onload="if(document.forms[0] && document.forms[0].name != 'Sereconnecter') document.forms[0].elements[0].focus()", $onclick="hidecondi()", $bgcolor="#ffffff", $text="#000000", $link="#0000ff", $vlink="#800080", $alink="#ff0000", $onunload="", $onclose="")
	{
		if($this->debugLang) echo "\n\n<!-- \n{$this->missingLangs} -->\n\n";
		if($this->pdf || $this->csv) return;
		$this->wait_entete=FALSE;
		if($onunload) $onunload = "onunload=\"$onunload\"";
		if($onclose) $onclose = "onclose=\"$onclose\"";
		if($_REQUEST["nextScrollX"]||$_REQUEST["nextScrollY"])
		{
			$toX = ($_REQUEST["nextScrollX"]) ? $_REQUEST["nextScrollX"]:"0";
			$toY = ($_REQUEST["nextScrollY"]) ? $_REQUEST["nextScrollY"]:"0";
			$onload .= ";window.scrollTo($toX, $toY)";
		}
		
		//echo "\n<body onload=\"$onload\" onclick=\"$onclick\" bgcolor=\"$bgcolor\" text=\"$text\" link=\"$link\" vlink=\"$vlink\" alink=\"$alink\" $onunload $onclose>\n";
		echo "\n<body id=\"body\" onload=\"$onload\" onclick=\"$onclick\" $onunload $onclose $onswipe>\n";
		echo "\n		<div class=\"popup\" id=\"popbox\"></div>";
		echo "\n		<div class=\"popup\" id=\"neant\"></div>";
		if($this->anchor == "agenda") echo $this->self_reload();
		if($table) 
		{
			$this->pageBlock = "\n\n<textepage id='corpsdepage'>\n<!-- begining of centered text -->\n\n";
			if($table == 1)
			{
				$this->table=TRUE;
// 				echo "\n\n<table align=\"center\" width=\"95%\"><tr><td>\n<!-- begining of centered text -->\n\n";
				//echo "\n\n<textepage id='corpsdepage' style='width:95%' \n<!-- begining of centered text -->\n\n";
				echo $this->pageBlock;
			}
			else $this->wait_entete=TRUE;
		}
	}
	
	function entete($echo=TRUE)
	{
		if($this->prolawyerClient) $this->prolawyerClientForm = True;
		else $this->prolawyerClientForm = False;
		$this->debugNow("Debut de l'en-tête");
		$this->forward_test=True;
		$this->noButtonID = True; //les ID des boutons sont créées automatiquement

		$this->debugNow("Appel à selecteur");
		$toPost = array("jour_debut", "mois_debut", "annee_debut", "jour_fin", "mois_fin", "annee_fin", "date_cours", "type", "template", "secteur");
		$oPost = "";
		$aPost = array();
		foreach($toPost as $nVar) if($_POST["$nVar"])
		{
			$oPost .= $this->input_hidden($nVar, false, $_POST[$nVar]);
			$aPost[$nVar] = $_POST["$nVar"];
		}
 		$this->base_selecteur=$this->selecteur("liste_bases", "lire");
 		$this->arch_selecteur=$this->selecteur("liste_archives", "lire");
		$this->debugNow("Fin de l'appel à selecteur");
		$this->url="doc/manuel/".$this->getCookie("lang")."/manuel.php#{$this->anchor}";
		$val_selecteur=(preg_match("#config#", $this->anchor))? $this->lang["entete_choisir_base"]: $this->lang["entete_base_en_cours"];
		$val_archives = $this->newPdaMenu ? $this->lang["config_modify_archives_abb"]:$this->lang["config_modify_archives"];
				
		if(!$this->print){ //c'est l'essentiel de la fonction, soit la barre d'outil ou d'en-tête, à choix
		
			if($this->newPdaMenu) $this->specPdaForm = True;
			else $this->specPdaForm = False;
			$this->debugNow("Debut des menus");
			//on triche avec la langue pour avoir momentanément une valeur avec espaces protégés
			$this->temp_lang=$this->lang;
			foreach($this->lang as $nom => $val) $this->lang["$nom"] = preg_replace("# #", "&nbsp;", $val);
		
			$this->menuitem=array();
			$this->menusubitem=array();
			$this->menusubsubitem=array();
			$this->listitem=array();
			$this->excludeMenu = array();
			$this->keepMenu = array();
			$this->startForms = array();
			$this->override = False;
			$this->insertMenuAfter = False;
			$this->doInsertAfter = False;
			
			if(is_file("{$this->settings["root"]}/override/menu_override.php")) require("{$this->settings["root"]}/override/menu_override.php");

			$this->menuCondi('1', True, (!$this->pdaSet && !$this->prolawyerClient)? "<img src=\"{$this->settings["root"]}images/prolawyer.png\" height=\"24\" width=\"24\" style=vertical-align:middle>&nbsp;<span style='vertical-align:middle'>{$this->lang["entete_general"]}</span>": $this->lang["entete_general"]); #Menu Général
			$this->menuCondi('2', True, $this->lang["entete_dossier"]); #Menu Dossier
			$this->menuCondi('3', True, $this->lang["entete_affichage"]); #Menu Affichage
			$this->menuCondi('4', True, $this->lang["entete_adresses"]); #Menu Adresses
			$this->menuCondi('5', True, $this->lang["entete_agenda"]); #Menu Agenda
			$this->menuCondi('6', True, $this->lang["entete_biblio"]); #Menu Bibliothèque
			$this->menuCondi('7', True, $this->lang["modifier_donnees_mail"]); #Menu Courriel
			$this->menuCondi('8', True, $this->lang["comptes_compta"]); #Menu Comptabilité
			$this->menuCondi('99', True, $this->lang["entete_modules"]); #Menu Modules
			$this->menuCondi('100', True, $this->lang["entete_help"]); #Menu Aide


			$this->menuCondi('1.x', True, $this->form("index.php<pda>", $this->lang["entete_reconnect"], $this->lang["entete_reconnect_accesskey"], "menu", ""));
			$this->menuCondi('1.x', True, $this->form("{$this->next_select}<td>", "$val_selecteur", "", "menu", "selecteur<td>", "new_av", $this->base_selecteur, "next_values", $this->next_values, "aPost", $aPost));
			$this->menuCondi('1.x', True, $this->form("{$this->next_select}<td>", $val_archives, "", "menu", "selecteurArchive<td>", "new_av", $this->arch_selecteur, "next_values", $this->next_values, "aPost", $aPost));
			$this->menuCondi('1.x', True, "<hr>");
			$this->menuCondi('1.x', ($this->testval("admin")), $this->form("config/config.php", $this->lang["entete_settings_gen"], $this->lang["entete_settings_accesskey"], "administration", "", "check_config", "on"));
			$this->menuCondi('1.x', $this->testval("ecrire"), $this->form("config/modify_perso.php<td>", $this->lang["config_modify_options_base"], "", "administration"));
			$this->menuCondi('1.x', True, $this->form("config/modify_options_perso.php<td>", $this->lang["config_modify_options_perso"], "", "administration"));
			$this->menuCondi('1.x', ($_SESSION["type"] == 'admin'), $this->form("config/manage_config.php<td>", $this->lang["config_modify_sauve_config"], "", "administration"));
			$this->menuCondi('1.x', ($_SESSION["type"] == 'admin'), $this->form("config/manage_base.php<td>", $this->lang["config_modify_sauve_base"], "", "administration"));
			$this->menuCondi('1.x', ($_SESSION["type"] == 'admin'), $this->form("config/modify.php<td>", $this->lang["config_modify_sauve_base"], "", "administration", "mode", "partner"));
			$this->menuCondi('1.x', True, $this->form("config/styles.php<td>", $this->lang["config_modify_styles"], "", "administration"));
			$this->menuCondi('1.x', True, $this->form("config/templates.php<td>", $this->lang["config_templates_title"], "", "administration"));
			$this->menuCondi('1.x', True, "<hr>");
			$this->menuCondi('1.x', True, "<form action=\"".$this->settings["root"]."{$this->next_select}.php\" method = POST id=\"changelang\">". $this->getLangs("menu", "") . "$oPost</form>");
			$this->menuCondi('1.x', True, "<form action=\"".$this->settings["root"]."{$this->next_select}.php\" method = POST id=\"changemode\">". $this->getModes("menu", "") . "$oPost</form>");
			$this->menuCondi('1.x', True, "<hr>");
			$this->menuCondi('1.x', True, $this->form("developpement/index.php<td>", $this->lang["entete_developpement"], "", "menu"));


			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements") AND !$this->testval("ecrire") AND $this->testval("lire")), $this->form("./modifier_donnees.php<td>", $this->lang["entete_consulter"], $this->lang["entete_modifier_accesskey"], "menu", "Modifierledossier<td>", "nodossier", "{$_POST["nodossier"]}"));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements") AND $this->testval("ecrire")), $this->form("./modifier_donnees.php<td>", $this->lang["entete_modifier"], $this->lang["entete_modifier_accesskey"], "menu", "", "nodossier", "{$_POST["nodossier"]}"));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") AND $this->testval("lire")), $this->form("operations.php<td>", $this->lang["operations_operations"], $this->lang["operations_operations_accesskey"], "menu", "", "nodossier", $_POST["nodossier"], "secteur", "operations"));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") AND $this->testval("lire")), $this->form("operations.php<td>", $this->lang["operations_encaissements"], $this->lang["operations_encaissements_accesskey"], "menu", "", "nodossier", $_POST["nodossier"], "secteur", "encaissements"));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") AND $this->testval("ecrire")), $this->form("maj_op.php<td>", $this->lang["entete_trash"], $this->lang["entete_trash_accesskey"], "attentionmenu", "", "nodossier", $_REQUEST["nodossier"], "retour", $this->anchor, "action", "delete_file"));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") AND $this->testval("ecrire")), $this->form("facture.php<td>", $this->lang["operations_facture"], "", "menu", "", "nodossier", $_POST["nodossier"],"fichier", $_SESSION["facture"], "timestamp_debut", $_POST["timestamp_debut"], "timestamp_fin", $_POST["timestamp_fin"], "sous_traitant_limite", $_POST["sous_traitant_limite"]));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") AND $this->testval("ecrire") AND $_SESSION["templates"]), $this->span($this->lang["operations_autres_documents"], "menu"));
			$temps = preg_split("';'", $_SESSION["templates"]);
			foreach($temps as $temp) if($temp) $this->addSub($this->form("facture.php<td>", $temp, "", "menu", "aaa", "nodossier", $_POST["nodossier"], "session_utilisateur", "{$_SESSION["session_utilisateur"]}", "db", "{$_SESSION["db"]}", "fichier", $temp));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") AND $this->testval("lire")), "<button class=\"menu\" onclick=\"window.open('{$this->settings["root"]}liste_soldes.php?clientReq={$_POST["nodossier"]}','modifier','width=600,height=600,toolbar=no,directories=no,menubar=no,location=no,status=no')\">{$this->lang["multi_clients_title"]}</button>");
			$this->menuCondi('2.x', ($this->testval("lire") && $_POST["secteur"]),"<hr>");
			$this->menuCondi('2.x', ($this->testval("lire")), $this->form("resultat_recherche.php<td>__liste__", $this->lang["entete_search"], $this->actPath != "/biblio" ? $this->lang["entete_search_accesskey"]:"", "menu"), "", True);
			$this->menuCondi('2.x', ($this->testval("ecrire") AND !preg_match("#config#", $this->anchor)), $this->form("creer_dossier.php<td>", $this->lang["entete_new"], $this->lang["entete_new_accesskey"], "menu", "", "action", "nouveau_dossier"));
			$this->menuCondi('2.x', ($this->testval("ecrire") AND !preg_match("#config#", $this->anchor) AND ($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") ), $this->form("creer_dossier.php<td>", $this->lang["entete_duplicate"], $this->lang["entete_duplicate_accesskey"], "menu", "", "action", "duplication_dossier", "nodossier", $_POST["nodossier"], "retour", $this->anchor));
			$this->menuCondi('2.x', ($this->testval("ecrire") AND !preg_match("#config#", $this->anchor)), $this->form("creer_dossier.php<td>", $this->lang["creer_client_conflits"], "", "menu", "", "action", "cherche_conflits"));
			$this->menuCondi('2.x', (($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements" OR $this->anchor=="modifier_donnees") AND $this->testval("ecrire")), $this->form("nouvelle_affaire.php<td>", $this->lang["entete_new_file"], "", "menu", "", "nodossier", $_POST["nodossier"], "session_utilisateur", "{$_SESSION["session_utilisateur"]}", "db", "{$_SESSION["db"]}"));

			
			$this->menuCondi('3.x', (!preg_match("#config#", $this->anchor) AND $this->testval("journal")), $this->form($root."operations.php<td>", $this->lang["entete_journal"], $this->lang["entete_journal_accesskey"], "menu", "", "secteur", "journal"));
			$this->menuCondi('3.x', (!preg_match("#config#", $this->anchor) AND $this->testval("journal")), $this->form($root."operations.php<td>", $this->lang["entete_journal_op"], $this->lang["entete_journal_op_accesskey"], "menu", "", "secteur", "journal_op"));
			$this->menuCondi('3.x', ($this->testval("lire")), $this->form("liste_soldes.php<td>", $this->lang["entete_liste_soldes"], $this->lang["entete_liste_soldes_accesskey"], "menu"));
			$this->menuCondi('3.x', ($this->testval("lire")), $this->form("liste_compte.php<td>", $this->lang["entete_liste_transit"], $this->lang["entete_liste_transit_accesskey"], "menu"));
			$this->menuCondi('3.x', ($this->testval("lire")), "<button class=\"menu\" accesskey={$this->lang["operations_timesheet_accesskey"]} onclick=\"window.open('{$this->settings["root"]}operations.php?timesheet=1&secteur=journal_op&sous_traitant_limite={$_SESSION["username"]}','timesheet','toolbar=no,directories=no,menubar=no,location=no,status=no')\">{$this->lang["operations_timesheet"]}</button>");
			//$this->menuCondi('3.x', ($this->testval("lire")), "<a class=\"menu\" accesskey={$this->lang["operations_timesheet_accesskey"]} target='timesheet' href='{$this->settings["root"]}operations.php?timesheet=1&secteur=journal_op'>{$this->lang["operations_timesheet"]}</a>");
			$this->menuCondi('3.x', (!preg_match("#config#", $this->anchor) AND $this->testval("tva")), "<hr>");
			$this->menuCondi('3.x', ($this->testval("tva")), $this->form("ra.php<td>", $this->lang["ra_link"], $this->lang["ra_link_accesskey"], "menu", "", "activite", "on"));
			$this->menuCondi('3.x', ($this->testval("tva")), $this->form("ra_new.php<td>", $this->lang["ra_stats"], "", "menu"));
			$this->menuCondi('3.x', ($this->testval("tva")), $this->form("operations.php<td>", $this->lang["entete_ca"], $this->lang["entete_ca_accesskey"], "menu", "", "secteur", "tva"));
			$this->menuCondi('3.x', ($this->testval("tva")), $this->form("operations.php<td>", $this->lang["entete_benefice"], $this->lang["entete_benefice_accesskey"], "menu", "", "secteur", "benefice"));
			$this->menuCondi('3.x', ($this->testval("lire")), $this->form("ra.php<td>", $this->lang["entete_activite_sstraitant"], "", "menu", "", "remuneration", "on"));
			$this->menuCondi('3.x', True, "<hr>");
			$this->menuCondi('3.x', True, "PLEINECRAN");
			$this->menuCondi('3.x', True, "<input type=text onclick='preventHide=true'>");

			
			$this->menuCondi('4.x', ($this->testval("lire")), $this->form("adresses/resultat.php<td>__liste__", $this->lang["entete_adresses"], $this->lang["entete_adresses_accesskey"], "menu"), "", True);
			$this->menuCondi('4.x', ($this->testval("ecrire")), $this->form("adresses/modifier.php<td>", $this->lang["adresses_resultat_nouvelle_fiche"], "", "menu", "", "nouveau", "on"));
			$this->menuCondi('4.x', ($this->testval("ecrire") && $this->anchor == "adresses_modifier"), $this->form("adresses/resultat.php<td>", "{$this->lang["adresses_resultat_supprimer"]}", "", "attentionmenu", "", "id", $_POST["id"], "action", "delete", "nom", $_POST["nom"]));
			$this->menuCondi('4.x', (($this->anchor=="adresses_modifier") AND $_SESSION["templates"]), $this->span($this->lang["operations_autres_documents"], "menu"));
			$temps = preg_split("';'", $_SESSION["templates"]);
			foreach($temps as $temp) if($temp) $this->addSub($this->form("facture.php<td>", $temp, "", "menu", "aaa", "id", $_POST["id"], "fichier", $temp));
			$this->menuCondi('4.x', ($this->testval("lire")), $this->span($this->lang["modifier_donnees_mailing"], "menu"));
			$mailingListes = explode("\n", trim($_SESSION["optionGen"]["mailing"]));
			foreach($mailingListes as $liste) if ($liste) $this->addSub($this->form("random_display.php<td>", $liste, "", "menu", "aaa", "generateMailing", "1", "mailing", $liste));

			
			$aType = $this->newPdaMenu ? "semaine": "mois";
			$this->menuCondi('5.x', ($this->testval("lire")), $this->form("agenda.php#today<td>__liste__", $this->lang["entete_agenda"], $this->lang["entete_agenda_accesskey"], "menu", "", "template", "agenda", "type", $aType), "", True);
			$this->menuCondi('5.x', ($this->testval("lire")), $this->form("agenda.php<td>__liste__", $this->lang["agenda_vacances_planning"], "", "menu", "", "template", "agenda", "type", "vacances"), "", True);
			$this->menuCondi('5.x', ($this->testval("lire")), $this->form("agenda.php<td>", $this->lang["entete_delais"], "", "menu", "", "template", "delais"));
			$this->menuCondi('5.x', ($this->testval("ecrire") && $this->anchor == "agenda"), "<button class=\"menu\" onclick=\"newRdv('$nouvelle_date_cours', '{$this->dbt_jour}', '$personne')\">{$this->lang["agenda_nouveau_rdv"]}</button>");
			$this->menuCondi('5.x', ($this->testval("ecrire") && $this->anchor == "agenda"), "<button class=\"menu\" onclick=\"newDl('$nouvelle_date_cours', '$personne')\">{$this->lang["agenda_nouveau_dl"]}</button>");
			$this->menuCondi('5.x', ($this->testval("ecrire")),$this->form("synchronisation/synchro.php", "synch", "", "attentionmenu", ""));

			
			if($this->testval("lire"))
			{
				$this->biblios = explode("\n", $_SESSION["optionGen"]["bibliotheques"]);
				foreach($this->biblios as $biblio) if(trim($biblio) != "")
				{
					list($nom, $type) = preg_split("/,/", $biblio);
// 					echo "<br>$nom,$type,";
					$this->menuCondi('6.x', True, $this->form("biblio/liste_ouvrages.php<td>", $nom, "", "menu", "", "setBiblioType", $type, "setBiblioNom", $nom));
					$this->addSub($this->form("biblio/recherche_livre.php<td>", $this->lang["recherche_dossier_recherche"], $this->actPath == "/biblio" ? $this->lang["entete_search_accesskey"]:"", "menu", "", "setBiblioType", $type, "setBiblioNom", $nom), True, "6.0");
					$this->addSub($this->form("biblio/liste_ouvrages.php<td>", $this->lang["biblio_prets"], "", "menu", "", "prete", "on", "setBiblioType", $type, "setBiblioNom", $nom, "doRecherche", "on"), True, "6.0");
					$this->addSub($this->form("biblio/liste_ouvrages.php<td>", $this->lang["biblio_liste_status1"], "", "menu", "", "status", "1", "setBiblioType", $type, "setBiblioNom", $nom, "doRecherche", "on"), True, "6.0");
					$this->addSub($this->form("biblio/liste_ouvrages.php<td>", $this->lang["biblio_liste_status2"], "", "menu", "", "status", "2", "setBiblioType", $type, "setBiblioNom", $nom, "doRecherche", "on"), True, "6.0");
					$this->addSub($this->form("biblio/liste_ouvrages.php<td>", $this->lang["biblio_liste_status3"], "", "menu", "", "status", "3", "setBiblioType", $type, "setBiblioNom", $nom, "doRecherche", "on"), True, "6.0");
					$this->addSub($this->form("biblio/creer_livre.php<td>", $this->lang["biblio_nouveau"], $this->actPath == "/biblio" ? $this->lang["biblio_nouveau_accesskey"]:"", "menu", "", "action", "create", "setBiblioType", $type, "setBiblioNom", $nom), True, "6.0");
					if($type == 1) $this->addSub($this->form("biblio/nouveautes.php<td>", $this->lang["biblio_nouveautes"], "", "menu", ""), True, "6.0");
				}
			}			

			
			$this->menuCondi('7.x', ($this->testval("lire")), $this->form("imap/courriel.php<td>", $this->lang["modifier_donnees_mail"], "", "menu"));
			$this->menuCondi('7.x', ($this->testval("lire")), "<button class=menu onclick=\"javascript:window.open('{$this->settings["root"]}imap/compose.php', 'compose', 'toolbar=no,directories=no,menubar=no=no,statusbar=no,toolbar=no,titlebar=no,scrollbars=yes')\">{$this->lang["imap_courriel_nouveau_message"]}</button>");


			$this->menuCondi('8.x', (1), $this->form("comptes.php<td>", $this->lang["comptes_comptes"], "", "menu"));
			$this->menuCondi('8.x', (1), $this->form("compta.php<td>", $this->lang["comptes_compta"], "", "menu"));

			$this->menuCondi('99.x', True, $this->form("modules/delais.php<td>", $this->lang["modules_calcul_delais"], "", "menu", "", "delaisName", "ch_generique"));

			//inclusion des modules automatiques
			$actDir=getcwd();
			foreach(array($_SESSION["optionsPath"].$_SESSION["slash"],$this->settings["root"]) as $path) 
			{
				$tDir = "{$path}modules{$_SESSION["slash"]}auto";
                                if(is_dir($tDir))
				{
                                        $dir=opendir($tDir);
					
					$aL=$_SESSION["lang"];
					while($file=readdir($dir))
					{
						if(substr($file, -8) == ".dat.php")
						{
							$level=false;
							require("$tDir/$file");
							$radical=basename($file, ".dat.php");
							if(isset($modules["$radical"]["$aL"])) $var=$modules["$radical"]["$aL"];
							else $var="*{$modules["$radical"]["fr"]}*";
		
							$this->menuCondi('99.x', ($this->level == false || $this->testval("$level")),$this->form("modules/modules.php<td>", $var, "", "menu", "", "moduleName", "$radical"));
						}
					}
				}
			}
			
			$this->menuCondi('100.x',  True, $this->form("{$this->url}<td>", $this->lang["entete_help"], $this->lang["entete_help_accesskey"], "menu", "help"));
			$this->menuCondi('100.x',  True, $this->form("http://bugs.prolawyer.ch<td>", $this->lang["apropos_bug"], "", "menu", "bug"));
			$this->menuCondi('100.x',  True, $this->form("http://suggestions.prolawyer.ch<td>", $this->lang["apropos_fonctionnalite"], "", "menu", "fonction"));
			$this->menuCondi('100.x',  True, "<button class=menu onclick=\"javascript:window.open('{$this->settings["root"]}todo.php', 'todo', 'toolbar=no,directories=no,menubar=no=no,statusbar=no,toolbar=no,titlebar=no,scrollbars=yes')\">TODO</button>");
			$this->menuCondi('100.x',  True, "<hr>");
			$this->menuCondi('100.x',  True, $this->form("update.php<td>", $this->lang["entete_maj"],"", "menu", ""));
			$this->menuCondi('100.x',  True, "<hr>");
			$this->menuCondi('100.x',  True, "<button class=menu onclick=\"javascript:window.open('{$this->settings["root"]}apropos.php', 'apropos', 'toolbar=no,directories=no,menubar=no=no,statusbar=no,toolbar=no,titlebar=no,scrollbars=yes')\">{$this->lang["entete_apropos"]}</button>");
			
// 			$this->tab_affiche($this->menuitem);
// 			$this->tab_affiche($this->menusubitem);
			
			if($this->insertMenuAfter AND is_file("{$this->settings["root"]}/override/menu_override.php")) require("{$this->settings["root"]}/override/menu_override.php");
			
			$this->specPdaForm = False;
			$this->lang = $this->temp_lang;
			$this->debugNow("Fin des menus - début de la créationdu menu");
			if($echo) $this->create_menu($echo);
			else $return = $this->create_menu($echo);
			$this->debugNow("Fin de la création");
			if($this->wait_entete == TRUE)
			{
				$this->table=TRUE;
// 				if($echo) echo "\n\n<table align=\"center\" width=\"95%\"><tr><td>\n<!-- begining of centered text -->\n\n";
				if($echo) echo $this->pageBlock;
				else $return .= $this->pageBlock;
// 				else $return .= "\n\n<table align=\"center\" width=\"95%\"><tr><td>\n<!-- begining of centered text -->\n\n";
// 				else $return .= "\n\n<textepage id='corpsdepage' style='width:95%;align-self:center' \n<!-- begining of centered text -->\n\n";

			}
			$this->prolawyerClientForm = False;
			$this->debugNow("Fin de l'en-tête");
			$this->noButtonID = False; //On repasse en mode manuel
			unset($this->forward_test);
			return $return;
		}
		
// 		echo "<p id=plagetest>hello</p>";
		unset($this->forward_test);
	}

	function addSub($action, $condition = True, $place = "")
	{
		if($place == "")
		{	
			$a = count($this->menusubitem);
			$b = count($this->menusubitem[$a]) -1;
		}
		else list($a, $b) = preg_split("#\.#", $place);
// 		echo "<br>addsub: $a: $b";
// 		if($this->menusubitem[$a][$b]) $this->menusubsubitem[$a][$b][] = $action;
		if($this->menusubitem[$a][$b]) $this->menuCondi("$a.$b.x", $condition, $action);

	}

	function menuCondi($num, $condition, $valueOui, $valueNon = "", $firstMenu = False)
	{
		$item = preg_replace("#^([0-9]+).*#", "\\1", $num);
// 		echo "<br>$num: $item $subitem";

		if(
			$this->override == True || 
			in_array($item, $this->excludeMenu) || 
			($this->excludeMenu == array("ALL") && !in_array($item, $this->keepMenu))
		)
		{
// 			echo "<br>$num /$valueOui ($firstMenu)";
			if($firstMenu == True) array_splice($this->arr_forms, -1); #Le passage de valueOui crée automatiquement le menu. Il faut donc le supprimer.
			return;
		}
// 		else echo "<br>KEEP: $num /$valueOui";
		if($num === "100") $valueOui .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;"; #pour élargir le menu Aide
		if($condition || $this->prolawyerClient) $item = $valueOui;
		else $item = $valueNon;
		if($this->prolawyerClient)
		{
			if($condition) $item .= '|a';
			else $item .= '|d';
		}
		if(preg_match('#^[0-9]+$#', $num)) $this->menuitem[$num] = $item;
		elseif(preg_match('#^([0-9]+).([0-9x]+)$#', $num, $reg))
		{
			$i = $reg[1];
			$j = $reg[2];
			if($j == "x") $this->menusubitem[$i][] = $item;
			else $this->menusubitem[$i][$j] = $item;
		}
		elseif(preg_match('#^([0-9]+).([0-9]+).([0-9x]+)$#', $num, $reg))
		{
			$i = $reg[1];
			$j = $reg[2];
			$k = $reg[3];
			if($k == "x") $this->menusubsubitem[$i][$j][] = $item;
			else $this->menusubsubitem[$i][$j][$k] = $item;
		}
// 		echo "\n<br>$valueOui: $item";
		
	}
	
	function getAccessSchema($d)
	{
		if(isset($access_letter)) unset($access_letter);
		if(isset($access_schema)) unset($access_schema);
		if(!$this->pdaSet && preg_match("#accesskey#", $d)) 
		{
			$access_string=preg_replace("#accesskey=\"#", "accesskey=", $d);
			$access_string=strstr("$access_string", "accesskey=");
			$access_string=preg_replace("#accesskey=#", "", $access_string);
			if(preg_match("#^[A-Za-z0-9]([ \"'>])#", $access_string))
			{
				$access_letter=substr($access_string, 0, 1);
				$access_letter=strtoupper($access_letter);
				if(preg_match("#konqueror#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema="&nbsp;Ctrl,&nbsp;";
				elseif(preg_match("#opera#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema="&nbsp;Esc,&nbsp;Shift&nbsp;+&nbsp;";
				elseif(preg_match("#firefox#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema="&nbsp;Alt&nbsp;+&nbsp;Shift&nbsp;+&nbsp;";
				else $access_schema="&nbsp;Alt&nbsp;+&nbsp;";
			}
			else $access_schema = "&nbsp;".preg_replace("#^([A-Za-z0-9]+)[ \"'>].*#", "\\1", $access_string);
			if($access_letter) $access_schema .= $access_letter;
			if(preg_match("#msie#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema.=",&nbsp;[Enter]";
		}
		return($access_schema);
	}
	
	function getAccessSchema2($d)
	{
		if(!$this->pdaSet) 
		{
			$access_string=preg_replace("#accesskey=\"#", "accesskey=", $d);
			$access_letter=strtoupper($access_string);
			if(preg_match("#konqueror#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema="&nbsp;Ctrl,&nbsp;";
			elseif(preg_match("#opera#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema="&nbsp;Esc,&nbsp;Shift&nbsp;+&nbsp;";
			elseif(preg_match("#firefox#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema="&nbsp;Alt&nbsp;+&nbsp;Shift&nbsp;+&nbsp;";
			else $access_schema="&nbsp;Alt&nbsp;+&nbsp;";
			if($access_letter) $access_schema .= $access_letter;
			if(preg_match("#msie#", strtolower("{$_SERVER["HTTP_USER_AGENT"]}"))) $access_schema.=",&nbsp;[Enter]";
		}
		return($access_schema);
	}
	
	function create_menu($echo)
	{
		if(($this->prolawyerClient/* || true*/) && $this->anchor != "index")
		{
			echo "\n<!--";
			echo "\npython_begin";
			foreach($this->menuitem as $i => $vi)
			{
				$vi = trim($vi);
				$vi = html_entity_decode($vi, ENT_COMPAT|ENT_HTML401, "UTF-8");
				echo "\n<br>|$i|$vi";
				if(is_array($this->menusubitem[$i]))foreach($this->menusubitem[$i] as $j => $vj)
				{
					$vj = trim($vj);
					$vj = preg_replace("#\n#", " ", $vj);
					$vj = preg_replace('#<hr>\|#', '-|', $vj);
					$vj = preg_replace('#^<span#', '|||<span', $vj);
					echo "\n<br>|$i.$j|$vj";
					if(is_array($this->menusubsubitem[$i][$j]))foreach($this->menusubsubitem[$i][$j] as $k => $vk)
					{
						$vk = trim($vk);
						$vk = preg_replace("#\n#", " ", $vk);
						$vk = preg_replace('#<hr>\|#', '-|', $vk);
						echo "\n<br>|$i.$j.$k|$vk";
					}
				}
				echo "\n<br>|----";
			}
			echo "\npython_end";
			echo "\n-->";
			return;
		}
// 		if($this->newPdaMenu) return "";
		if(isset($_SESSION["module"]))
		{
			if($_SESSION["module"] == "agenda") $this->module_number=5;
// 			echo "this->module_number vaut ", $this->module_number, " et session_module vaut '{$_SESSION["module"]}'";
			
			$this->toparse = $this->menusubitem[$this->module_number];
		}
		
		//suppression des valeurs nulles et gestion de la version pda
		$this->pda_array=array();
		if($this->newPdaMenu) $this->menusubitemback = $this->menusubitem;
		foreach($this->menusubitem as $a =>$b) foreach($this->menusubitem[$a] as $c => $d)
		{
			$idok=FALSE;
			$butid="button".$a."_".$c;
			$selid="select".$a."_".$c;
			$provisoire=preg_replace("#  #", " ", $this->menusubitem[$a][$c]);
/*			$provisoire=preg_replace("# \"#", "\"", $provisoire);
			$provisoire=preg_replace("#class =\"#", "class=\"", $provisoire);*/
			if(preg_match("#class=#", $provisoire))
			{
				$offset=strpos($provisoire, "class=") + 6;
				$substring=substr($provisoire, $offset);
				$newoffset=strpos($substring, " ");
				if(!$newoffset) $newoffset = strlen($substring);
				$tempclass=substr($substring, 0, $newoffset);
				$lastoffset=strpos($tempclass, ">");
				if(preg_match("#>#", $tempclass)) $tempclass=substr($tempclass, 0, $lastoffset);
				$tempclass=preg_replace("#\"#", "", $tempclass); 
				$tempclass=preg_replace("#=#", "", $tempclass); 
				$menuclasses[$a][$c] = $tempclass;
			}
			if(preg_match("#<select#i", $this->menusubitem[$a][$c]))
			{
				$this->menusubitem[$a][$c] = preg_replace("#<select#i", "<select onclick=\"preventHide=true\"", $this->menusubitem[$a][$c]);
				$idok = TRUE;
			}
			if($this->menusubitem[$a][$c] == "PLEINECRAN")
			{
				if(! $this->prolawyerClient) $this->menusubitem[$a][$c] = "<button class=\"menu\" accesskey=\"F11\" onclick=\"pleinEcran()\">{$this->lang["entete_plein_ecran"]}</button>";
				$idok = TRUE;
				$menuclasses[$a][$c] = 'menu';
			}
			if(preg_match("#<button#", $this->menusubitem[$a][$c]))
			{
				$this->menusubitem[$a][$c] = preg_replace("#<button#", "<button id=\"$butid\"", $this->menusubitem[$a][$c]);
				$idok = TRUE;
			}
			if(preg_match("#<select#", $this->menusubitem[$a][$c]))
			{
				$this->menusubitem[$a][$c] = preg_replace("#<select#", "<select id=\"$selid\"", $this->menusubitem[$a][$c]);
			}
			if(preg_match("#<a href#", $this->menusubitem[$a][$c]) && $idok == FALSE)
			{
				$this->menusubitem[$a][$c] = preg_replace("#<a href#", "<a id=\"$butid\" href", $this->menusubitem[$a][$c]);
				$butid = TRUE;
			}
			if(substr($this->menusubitem[$a][$c], 0, 2) == "<p" && substr($this->menusubitem[$a][$c], 0, 5) != "<pda>" && $idok == FALSE) $this->menusubitem[$a][$c] = preg_replace("#<p#", "<p id=\"$butid\"", $this->menusubitem[$a][$c]);
			
			
			if(substr($this->menusubitem[$a][$c], 0, 5) == "<pda>")
			{
				$this->menusubitem[$a][$c] = substr($this->menusubitem[$a][$c], 5);
				$this->pda_array[]=$this->menusubitem[$a][$c];
			}
			if($this->menusubitem[$a][$c]==NULL) unset ($this->menusubitem[$a][$c]);
		}
// 		if($this->newPdaMenu) $this->menusubitem = $this->menusubitemback;
		foreach($this->menusubitem as $a => $b) if($this->menusubitem[$a] == NULL) unset ($this->menusubitem[$a]);
		
		//tri des valeurs
		foreach($this->menuitem as $a => $b) if(is_array($this->menusubitem[$a])) ksort ($this->menusubitem[$a]);
		ksort ($this->menusubsubitem);
		
		//création de la barre principale
		if(!$this->menu) $return = "\n<!-- begining of taskbar -->\n";
		if($this->menu)
		{
			$style = ($this->getCookie("pda") == "on")? ";font-size:6vw":"";
			$return .= "\n<select name=\"next\" id=\"next\" style=\"background-image:url({$this->settings["root"]}/images/arrow-right.png);background-color:#d0d0d0$style\" class=\"sellang\">";
			if($_GET["erreur"] == "rate2")
			{
				$return .= "\n<option value=\"main_form\" style=\"background-color:#d0d0d0$style\" class=\"sellang\" selected>{$this->lang["index_restaure"]}</option>";
				$return .= "\n<option value=\"\">------------</option>";
			}
			if(is_array($this->arr_forms)) foreach ($this->arr_forms as $num=>$val)
			{
				
				$return .= "\n<option value=\"formulaire_$num\" style=\"background-color:#d0d0d0$style\" class=\"sellang\">{$this->arr_forms[$num]["nom"]}</option>";
			}
			$return .= "\n</select>";
		}
		elseif($_SESSION["module"])
		{
			$compteur=0;
			$return .= "\n<table width=100% border=0 cellspacing=0 cellpadding=0>\n\t<tr class=menu>";
			array_unshift($this->menusubitem[$this->module_number], $this->menusubitem[1][0]);
			foreach ($this->menusubitem[$this->module_number] as $num=>$val)
			{	
				if($val != "<hr>")
				{
					$compteur ++;
					if($compteur == 4)
					{
						$compteur = 1;
						$return .= "</tr>\n\t<tr class=menu>";
					}
					if(!preg_match("#<td#", $val)) $val="<td>$val</td>";
					$return .= $val;
				}
			}
			for($x=0;$x<(3-$compteur);$x ++) $return .= "\n\t\t<td>&nbsp;</td>";
			$return .= "\n</tr></table>";
		}
		elseif($this->getCookie("pda_old_version") == "on") //ancienne version Pda, inadaptée
		{
			$compteur=0;
			$return .= "\n<table width=100% border=0 cellspacing=0 cellpadding=0>\n\t<tr class=menu>";
			foreach ($this->pda_array as $val)
			{
				$compteur ++;
				if($compteur == 5)
				{
					$compteur = 1;
					$return .= "</tr>\n\t<tr class=menu>";
				}
				$return .= $val;
			}
			for($x=0;$x<(4-$compteur);$x ++) $return .= "\n\t\t<td>&nbsp;</td>";
			$return .= "\n</tr></table>";
		}else{
			if($this->newPdaMenu)
			{
				$return = "\n<!-- Debut du menu spécifique aux pdas -->";
				$return .= "\n<img src='{$this->settings["root"]}images/prolawyer.png' alt=\"///\" class='prolawyermenu menuinit' onclick=showpdamenu()>";
				$return .= "\n<!-- bloc contenant les menus -->";
				$return .= "\n\t\t<div id=pdamenu class=menucontent>\n<ul>"; //TODO: visibilitiy=hidden doesn't work on android
				$pdaLines = array();
			}
			elseif($this->pdaSet)
			{
				$return = "\n<!-- Debut de la table spécifique aux pdas. Cette table contient à la première ligne une cellule contenant le bouton, et à la deuxième ligne une table en position 'absolute' contenant les menus -->";
				$return .= "\n<table border=0 cellspacing=0 cellpadding=0>\n\t<tr class=menuinit>";
				$return .= "\n\t\t<td onclick=showpdamenu()><img src='{$this->settings["root"]}images/prolawyer.png' class='prolawyermenu'></td>";
				$return .= "\n\t</tr>\n\t<tr><td>";
				$return .= "\n<!-- Table de la deuxième ligne contenant les menus, sur deux colonnes -->";
				$return .= "\n\t\t<table id=pdamenu style='position:absolute;visibility:hidden'>"; //TODO: visibilitiy=hidden doesn't work on android
				$pdaLines = array();
			}
			else $return .= "<table class=tablemenu>\n\t<tr class=menuinit>";
			foreach ($this->menuitem as $a => $b)
			{
// 				$return .= "<br> actuellement, je m'ocupe du menuitem[$a] qui vaut '$b'";
				if(is_array($this->menusubitem[$a]))
				{
					//$complin = (preg_match("#specidfirst#", $b))? ";getElementById('specidfirst').className='bordurefirst'":"";
					//$complout = (preg_match("#specidfirst#", $b))? ";getElementById('specidfirst').className='menu'":"";
					$menuid="menu".$a;
					if($this->pdaSet) $pdaLines[] = "\n\t\t<td class=\"menu\" id=\"$menuid\" onclick=\"showmenu('submenu.$a')\" onmouseover=\"menuover('$a')\" onmouseout=\"menuout($a)\">$b</td>";
					#else $return .= "\n\t\t<td class=\"menu\" id=\"$menuid\" onclick=\"javascript:showmenu('submenu.$a')$complin\" onmouseover=\"javascript:showcondi('submenu.$a');getElementById('$menuid').className='bordurefirst'$complin;hidesubitem()\" onmouseout=\"getElementById('$menuid').className='menu'$complout\">$b</td>";
					else $return .= "\n\t\t<td class=\"menuinit\" id=\"$menuid\" onclick=\"showmenu('submenu.$a')\" onmouseover=\"menuover('$a')\" onmouseout=\"menuout($a)\">$b</td>";
				}
			}
			if(! $this->pdaSet) $return .= "\n\t</tr>
			\n\t<tr class=bg>";
			
			//création des menus déroulants dans la deuxième ligne de la table principale
//			$first="colspan=\"2\"";
			$pdaCtlNum = 0;//numérotation pour la version pda
			foreach ($this->menuitem as $a => $b) 
			{
				if(is_array($this->menusubitem[$a])) 
				{
					if($this->newPdaMenu)
					{
						$return .= "\n\t<li class=menuitem>\n\t<input type=checkbox>\n\t<span>{$this->menuitem[$a]}</span>\n\t<ul class=submenu1>";
					}
					elseif($this->pdaSet) $return .= "\n\t<tr>{$pdaLines[$pdaCtlNum]}\n\t\t\t<td $first><div class=\"popupitem\" id=\"submenu.$a\">\n\t\t<table class=special_border border=1 cellspacing=0 cellpadding=0><tr><td>\n\t\t\t<table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					else $return .= "\n\t\t<td $first><div class=\"popupitem\" id=\"submenu.$a\"><table class=special_border border=1 cellspacing=0 cellpadding=0><tr><td><table border=\"0\" cellpadding=\"0\" cellspacing=\"0\">";
					$first="";
					
					//s'ils existent, création des sous-menus
					foreach($this->menusubitem[$a] as $c=>$d)
					{
// 						if($this->pdaSet) continue;
						$click	= "";
						$subsub	= "";
						$subid	= "";
						
						//s'ils existent, création des sous-sous-menus (latéraux)
						if(is_array($this->menusubsubitem["$a"]["$c"]))
						{
							if($this->newPdaMenu)
							{
								$this->menusubitem[$a][$c] = preg_replace("#</?(span)[^>]*>#", "", $this->menusubitem[$a][$c]);
								$subsubpda = "";
// 								$return .= "\n\t<li class=menuitem>\n\t<input type=checkbox>\n\t{$this->menusubitem[$a][$c]}\n\t<ul class=submenu1></ul>";
// 								continue;
							}
							$subid = "sub{$a}_{$c}";
							foreach($this->menusubsubitem["$a"]["$c"] as $n=> $lastitem)
							{
								$access_schema = $this->getAccessSchema($lastitem);
								$this->access_schema = $access_schema;
// 								$subidL = $subid."_$n";
								$subidB = "button{$a}_{$c}_{$n}";
								$subidL = "line{$a}_{$c}_{$n}";
								$subidS = "select{$a}_{$c}_{$n}";
								if(preg_match("#<form#i", $lastitem))
								{
									$lastitem = preg_replace("#<button#i", "\\0 id=\"$subidB\"", $lastitem);
									$lastitem = preg_replace("#<select#i", "\\0 id=\"$subidS\"", $lastitem);
									$specId	= "";
								}
								else
								{
									$on = "";
								}
								$specId	= "id=\"$subidL\"";
								if(!$subsub) $subsub = "\n<table id=\"$subid\" class=\"submenu\" cellpadding=0 cellspacing=0 style=\"border-top:1px;border-left:1px;border-color:#a0a0a0;border-style:solid\">";
								$subsub .= "\n<tr $subidL $specId abc onmouseover=\"subover('$a', '{$c}_{$n}')\" onmouseout=\"subout('$a', '{$c}_{$n}', 'menu')\">$lastitem<td>$access_schema</td></tr>";
// 								$subsub .= "\n<tr $subidL $specId abc onmouseover=\"getElementById('$subidL').className='bordurefirst'\" onmouseout=\"getElementById('$subidL').className='menu'\">$lastitem<td>$access_schema</td></tr>";
								$subsubpda .= "\n\t\t<li>$lastitem</li>";
							}
							if($subid)
							{
								$subsub .= "</table>";
								$click = "getElementById('$subid').style.visibility='visible';subitem='$subid'";
							}
						}
							/*$return .= "</td>\n\t</tr>";*/
						$access_schema = $this->getAccessSchema($d);;
						$tdStyle = "";
						if(is_array($this->menusubsubitem["$a"]["$c"]))
						{
							$access_schema = " ->";
							$tdStyle = "align=right";
						}
						$butid="button{$a}_{$c}";
						$linid="line{$a}_{$c}";
						if($this->newPdaMenu)
						{
							if($this->menusubitem[$a][$c] == "<hr>") continue;
							$ajoutBox = is_array($this->menusubsubitem["$a"]["$c"]) ? "\n\t\t<input type=checkbox>":"";
							$ajoutUl = is_array($this->menusubsubitem["$a"]["$c"]) ? "\n\t\t<ul class=submenu2>$subsubpda</ul>":"";
							$return .= "\n\t\t<li>$ajoutBox\n\t\t{$this->menusubitem[$a][$c]}$ajoutUl";
							$return .= "\n\t\t</li>";
						}
						else
						{
							if($this->menusubitem["$a"]["$c"] == "<hr>") $colspanHr = "colspan=2";
							else $colspanHr = "";
							if(preg_match_all("#id=\"([^\"]*)\"#", $this->menusubitem["$a"]["$c"], $regs))
							{
								/*$subover1 = $a;
								foreach($regs[1] as $nReg => $vReg) if(!preg_match("#^button[0-9]+_[0-9]+$#", $vReg))
								{
									$subover1 .= ",$vReg";
								}
								$actions="onmouseover=\"subover('$subover1', '$c');$click\" onmouseout=\"subout('$subover1', '$c', '{$menuclasses["$a"]["$c"]}');\"";
								*/
								$actions="onmouseover=\"subover('$a', '$c');$click\" onmouseout=\"subout('$a', '$c', '{$menuclasses["$a"]["$c"]}');\"";
								//echo "<br>Subover: $subover1";
							}
							else $actions = "onmouseover=\"hidesubitem();$click\""; 
							//echo "<br>Regs: {$regs[1][0]},{$regs[1][1]}";
							if(! preg_match("#</td></form>#", $d) && ! preg_match("#</form></td>#", $d)) $this->menusubitem["$a"]["$c"] = "<td $colspanHr>{$this->menusubitem["$a"]["$c"]}</td>";
							$return .= "\n\t\t\t<tr id=\"$linid\" class=\"menu\" $actions>{$this->menusubitem["$a"]["$c"]}";
							if(!$this->pdaSet && ! $colspanHr) $return .= "<td $tdStyle>$access_schema</td>";
							$return .= "<td>&nbsp;</td><td style=\"vertical-align:top\"><span class=\"substyle\" style=\"\">$subsub</span></td></tr>";
						}
					}
					if($this->newPdaMenu) $return .= "\n\t</ul>";
					else $return .= "\n\t\t</table></td></tr>\n\t</table></div>&nbsp;</td>";
				}
				$pdaCtlNum ++;
			}
			
			if(! $this->newPdaMenu)
			{
				$return .= "</tr></table>";
				if($this->pdaSet)
				{
					$return .= "\n<!-- Il faut maintenant refermer la ligne -->";
					$return .= "\n</td></tr>";
				}
			}
		}
		if($this->newPdaMenu) $return .= $this->menu != "noCheck" ? "\n</ul>\n</div>": "";
		elseif($this->pdaSet && $this->menu != "noCheck") $return .= "\n<!-- On referme enfin la table spécifique aux PDAs -->\n</table>\n<div class=menuplaceholder>&nbsp;</div>";
		if(!$this->menu) $return .= "\n<!--end of taskbar -->\n";
		if ($echo) echo $return;
		return $return;
// 		echo "<button onclick=javascript:alert(ouvert)>test</button>";
	}
	
	function joliMenu() //Développé pour la rdaf
	{
		$this->arrName="i1";
		$this->xMenuLevel=1;
// 		$this->xMenu  .= "\n<img src=\"./image.php?nom=toto&type=hor\">";
		$this->xMenu  .= "\n<ul id=x>";
// 		$this->xMenuLevel++;
		if(is_array($this->i1))
		{
			foreach($this->i1 as $key => $item)
			{
				$id="i1".$key;
				$this->uniqueIDs[]=$id;
				if($this->d1["$key"])
				{
					$this->xMenu .= "\n\t<li id=\"$id\">";
					$name=false;
					if(is_array($this->a1))
					{
						if($this->a1[$key] && $this->setXHR) $this->a1[$key] = $this->getXHRFromGET($this->a1[$key], $item);
						if(substr($item, 0, 3) == "<bg")
						{
							$sColor=substr($item, 3, 6);
							$item=substr($item, 10);
						}
						if(substr($item, 0, 8) == "<CENTER>")
						{
							$base=$this->bigFontSize + 5;
							$height=$this->bigFontSize;
							$this->sHeight="&height=$height&base=$base&width=170";
							$item=substr($item, 8);
// 							$sColor="ffffff";
						}
						$this->alone=(substr($item, 0, 7) == "<alone>")?true:false;
						if($this->alone) $item=substr($item, 7);
						if($this->useImages)
						{
							if($sColor)
							{
								$bColor=$this->bgColor;
								$this->bgColor=$sColor;
							}
							if($this->alone) $item=$this->getImage($item, "small");
							else $item=$this->getImage($item);
							if($sColor) $this->bgColor=$bColor;
							unset($sColor);
							unset($this->sHeight);
						}
						if($this->a1[$key]) $name = $this->a1[$key].$item."</a>";
						else $name = $item;
					}
					
					if($this->alone) $this->xMenu .= "<ul><li>";
					$this->xMenu .= $name;
					if($this->alone) $this->xMenu .= "</li></ul>";
					$this->xMenuLevel++;
					$this->lastName=$item;
					$this->addSubMenu($key);
					$this->xMenuLevel--;
					$this->xMenu .= "</li>";
				}
				$this->arrName="i1";
			}
		}
		$this->xMenuLevel --;
		$this->xMenu .="\n</ul>";
		return $this->xMenu;
	}
	
	function addSubMenu($k) //Développé pour la rdaf; complète joliMenu()
	{
		$levelItems=$this->arrName."i".$k;
		$levelDroits=$this->arrName."d".$k;
		$levelActions=$this->arrName."a".$k;
// 		echo "<br>Recherche de $levelItems et de $levelActions";
		if(is_array($this->$levelItems))
		{
			$droits=$this->$levelDroits;
			$this->xMenu .="\n";
			for($x=0;$x<$this->xMenuLevel;$x++) $this->xMenu .= "\t";
			$id="ul".$levelItems;
			$this->uniqueIDs[]=$id;
// 			$this->xMenu .= "<ul id=\"$id\" title = \"{$this->lastName}\">";
			$this->xMenu .= "<ul id=\"$id\" title = \"\">";
	 		$this->xMenuLevel++;
			foreach($this->$levelItems as $key => $item)
			{
				$id=$levelItems.$key;
				$this->uniqueIDs[]=$id;
				$this->xMenu .="\n";
				for($x=0;$x<$this->xMenuLevel;$x++) $this->xMenu .= "\t";
				if($droits["$key"])
				{
					$this->xMenu .= "<li id=\"$id\">";
					$this->xMenuLevel++;
					$name=false;
					$nArr=$this->$levelActions;
					if(is_array($nArr))
					{
						if(isset($nArr[$key]))
						{
							if($this->setXHR) $nArr[$key] = $this->getXHRFromGET($nArr[$key], "$item");
// 							$name = $this->a1[$key].$item."</a>";
							if($this->useImages) $item=$this->getImage($item, "small");//$item="<img src=\"{$this->settings["root"]}image.php?nom=$item&type=hor&color={$this->smallColor}&bgcolor={$this->bgColor}&fontsize={$this->smallFontSize}\">";
							$name = $nArr[$key].$item."</a>";
// 							echo "<br>'".htmlentities($nArr[$key])."' à comparer avec '$this->uri'";
// 							if(ereg($this->uri, $nArr[$key])) echo "victoire pour {$nArr[$key]}";

						}
					}
					if(!$name)
					{
						$name = $item;
						if($this->useImages) $item=$this->getImage($item, "small");//$item="<img src=\"{$this->settings["root"]}image.php?nom=$item&type=hor&color={$this->smallColor}&bgcolor={$this->bgColor}&fontsize={$this->smallFontSize}\">";

					}
					$this->xMenu .= $name;
					$oldlevel = $this->arrName;
					$this->arrName=$this->levelItem;
	// 				$this->xMenuLevel++;
					$this->lastName=$item;
					$this->arrName=$levelItems;
	// 				echo "<br>on va envoyer '$levelItems'";
					if($this->xMenuLevel <5) $this->addSubMenu($key);
					$this->xMenu .="\n";
					for($x=1;$x<$this->xMenuLevel;$x++) $this->xMenu .= "\t";
					$this->arrName=$oldlevel;
					$this->xMenuLevel--;
					$this->xMenu .= "</li>";
				}
			}
			$this->xMenu .="\n";
			for($x=1;$x<$this->xMenuLevel;$x++) $this->xMenu .= "\t";
	 		$this->xMenuLevel--;
			$this->xMenu .= "</ul>";
		}
// 		else echo "<br>$levelItems pas trouvé";
	}
	
	function self_reload()
	{
		$return = "<form method=\"post\" action=\"{$_SERVER["PHP_SELF"]}\" name=\"self_reload\" id=\"self_reload\">";
		foreach($_POST as $nom => $val) if($nom != "nextScrollX" && $nom != "nextScrollY" && !in_array($nom, $this->noReload))
		{
			if(is_array($val)) foreach ($val as $a => $b) $return .= "\n".$this->input_hidden($nom."[$a]", "", $b);
			else $return .= $this->input_hidden($nom, 1);
		}
		$return .= $this->input_hidden("nextScrollX", 0, "0");
		$return .= $this->input_hidden("nextScrollY", 0, "0");
		$return .= "</form>";
		return $return;
	}
	
	function footer($forceDebut="notSet", $forceStop="notSet", $forceNbAffiche = "notSet")
	{
		$this->form_global_var = $_REQUEST;
		global $debut_mysql;
		global $stop_mysql;
		if($forceDebut      != "notSet") $debut_mysql = $forceDebut;
		if($forceStop       != "notSet") $stop_mysql  = $forceStop;
		if($forceNbAffiche  != "notSet" && $forceNbAffiche)
		{
			$oldNbAffiche = $_SESSION["nb_affiche"];
			$_SESSION["nb_affiche"] = $forceNbAffiche;
		}
		
		$sous_traitant_limite = $_POST["sous_traitant_limite"];
		echo "\n<!-- gestion des enregistrements sur plusieurs pages -->";
		echo "\n<table width=\"100%\" align=center border=\"0\">";
		$nomClient = isset($this->nomClient) ? $this->nomClient:""; 
		echo "\n<tr><td colspan=5 align=center><h4>{$this->title_name} $nomClient</h4></td></tr>";
		
		if($_POST["group_modif"] != "on" AND !$this->print AND $this->anchor != "adresses_resultat" AND $this->anchor != "resultat_recherche")
		{
			//impression
			echo "\n<tr><td colspan=5>";
			echo $this->form($this->rel_file_name, $this->lang["pied_impression"], "", "", "", "print", "on");
			echo $this->form($this->rel_file_name, $this->lang["pied_export"], "", "", "csv", "print", "on", "csv", "on");
			echo "</td></tr>";
		}
		
		echo "\n<tr>";
		
		// début
		echo "\n<td align=left width=30><!-- debut -->";
		if ($debut_mysql>0) echo $this->form("{$this->rel_file_name}", "<<", "", "", "", "debut", "0");
		else echo "<<";
		echo "</td>";
		
		//précédent
		echo "\n<td align=left width=30><!-- precedent -->";
		if ($debut_mysql>$_SESSION["nb_affiche"])
		{
			$init=$debut_mysql - $_SESSION["nb_affiche"];
			echo $this->form("{$this->rel_file_name}", "-", $this->accesMoins, "", "moins<td>", "debut", $init);
		}
		elseif ($debut_mysql>0)
		{
			echo $this->form("{$this->rel_file_name}", "-", $this->accesMoins, "", "moins<td>", "debut", "0");
		}
		else echo "-";
		echo "</td>";
		
		// liste des enregistrements
		//creation du formulaire universel
		$formpage = "\n<form style='display:inline' action=\"{$_SERVER["PHP_SELF"]}\" method=\"Post\" id='TOCHANGE'>";
		$formpage .= $this->input_hidden("nodossier", "{$_POST["nodossier"]}");
		$exclusion = array("debut");
		$formpage .= $this->addGeneralOptions($exclusion);
		
		echo "\n<td align=center>$formpage{$this->lang["general_records"]} ", $debut_mysql + 1, "&nbsp;{$this->lang["general_to"]}&nbsp;";
		echo $this->input_hidden("debut", $init);
		if ($debut_mysql<($stop_mysql-$_SESSION["nb_affiche"])) echo $debut_mysql + $_SESSION["nb_affiche"];
		else echo $stop_mysql;
		echo "&nbsp;({$this->lang["pied_vague"]} <input size=3 type=text name=\"nb_affiche\" value=\"{$_SESSION["nb_affiche"]}\">)";
		echo $this->button($this->lang["config_modify_maj_others"]);
		echo "</form></td>";
		
		// suivant
		echo "\n<td align=right width=30><!-- suivant -->";
		if ($debut_mysql<($stop_mysql-$_SESSION["nb_affiche"]))
		{
			$init=$debut_mysql + $_SESSION["nb_affiche"];
			echo $this->form("{$this->rel_file_name}", "+", $this->accesPlus, "", "plus<td>", "debut", $init);
		}
		else echo "-&gt;";
		echo "</td>";
		
		// fin
		echo "\n<td align=right width=30><!-- fin -->";
		if ($debut_mysql<($stop_mysql-$_SESSION["nb_affiche"]))
		{
			$init=$stop_mysql - $_SESSION["nb_affiche"];
			echo $this->form("{$this->rel_file_name}", ">>", "", "", "", "debut", $init);
		}
		else echo ">>";
		echo "</td></tr></table>
		
		<!-- accès direct aux pages -->";
		$nbpages = floor($stop_mysql/$_SESSION["nb_affiche"]) + 1;
		$formpagesbis = preg_replace("#TOCHANGE#", "listepage", $formpage);
		$liste = "\n<select name = 'debut' onchange=\"document.getElementById('listepage').submit()\">";
		for($x=0;$x < $nbpages;$x ++)
		{
			$value = $x * $_SESSION["nb_affiche"];
			$y = $x + 1;
			$selected = ($value == $_POST["debut"]) ? "selected":"";
			$liste .= "<option value='$value' $selected>$y</option>";
		}
		$liste .= "</select>";
		echo "\n<table align=center border=0><tr><td>{$this->lang["general_acces"]} $formpagesbis $liste</form></td></tr></table>";
		echo "\n<table align=center border=0><tr>";
		$noenreg=0;
		$noligne=0;
		if($nbpages > 24)
		{
			$tropdepages = True;
/*			$hasmin = True;
			$hasmax = True;*/
			$even = $nbpages - floor($nbpages / 2) == $nbpages/2 ? True:False;
			echo "<td colspan=20></td></tr><tr>";
			if($even)
			{
				$min = $nbpages/2 -1;
				$max = $min + 3;
			}
			else
			{
				$min = ($nbpages -1 )/2 -1;
				$max = $min + 4;
			}
			
			if ($_POST["debut"] <= ( 7 * $_SESSION["nb_affiche"]))
			{
// 				echo "<br>cas de min";
				$min = False;
				$max = max($_POST["debut"]/$_SESSION["nb_affiche"] + 2, 9);
			}
			elseif ($_POST["debut"] >= ( ($nbpages - 8) * $_SESSION["nb_affiche"]))
			{
// 				echo "<br>cas de max";
				$nomax = True;
				$min = min($_POST["debut"]/$_SESSION["nb_affiche"] + 2, $nbpages -9);
				$max = $nbpages;
			}
			else
			{
				$min = $_POST["debut"] / $_SESSION["nb_affiche"] - 2;
				$max = $min + 5;
			}
		}
		while($noenreg<$stop_mysql)
		{
			$nopage=$noenreg/$_SESSION["nb_affiche"]+1;
			if($noligne==25)
			{
				$noligne=0;
				echo "</tr>\n</table>\n<table align=center border=0><tr>";
			}
			echo "<td>";
			if($noenreg<>$_POST["debut"])
			{
				$class = "attention";
				$racc= $this->noFooterAccesskey ? "":$nopage;
			}else {
				$class = "selected_bg";
				$racc="";
			}
			echo $this->form("{$this->rel_file_name}", $nopage, $racc, "$class", "", "nodossier", "{$_POST["nodossier"]}", "debut", "$noenreg");

//  			echo "(ner: $noenreg/$nopage)</td>";
			if($tropdepages)
			{
				$pts = False;
				if($nextmin)
				{
					$noenreg = ($min -1) * $_SESSION["nb_affiche"];
					$pts = True;
					$nextmin = False;
				}
				elseif($nextmax)
				{
					$noenreg = ($nbpages -6) * $_SESSION["nb_affiche"];
					$pts = True;
					$nextmax = False;
				}
				elseif ($nopage == 5 && $min)
				{
					$noenreg = (floor(($min -6)/2) + 4) * $_SESSION["nb_affiche"];
					$nextmin = True;
				}
				elseif ($nopage == ($max) && !$nomax)
				{
					$noenreg = (floor(($nbpages -7 - $max)/2) + $max) * $_SESSION["nb_affiche"];
					$nextmax = True;
				}
				if($pts||$nextmin||$nextmax) echo "<td>&nbsp;...&nbsp;</td>";
			}
			$noenreg=$noenreg+$_SESSION["nb_affiche"];
			$noligne++;
		}
		echo "</tr>\n</table>";
		unset($this->form_global_var);
		
		if($forceNbAffiche  != "notSet")
		{
			$_SESSION["nb_affiche"] = $oldNbAffiche;
		}
	}
	
	function selecteur($nom="", $test=FALSE, $all=FALSE, $session=TRUE, $post="", $seul_autorise=FALSE,$groups=FALSE, $groups_only=FALSE)
	{
		//****************************************
		//Paramètres:
		//$nom = nom de l'accès, utilisé pour l'éventuelle gestion des droits
		//$test indique si l'on doit tester ou pas les droits
		//$all = inutilisé (servait à gérer une base commune
		//$session = test sur la concordance entre la liste et la session en cours
		//$post = test sur la concordance entre la liste et la valeur passée à post
		//$groups = affiche les groupes
		//$groups_only = n'affiche pas les utilisateurs
		
		

//		$this->liste_des_logins = $this->liste_logins();
		$firstUser = True;
		$this->debugNow("Appel à liste_des_utilisateursgroupes");
		$this->liste_des_utilisateursgroupes = $this->liste_utilisateursgroupes();
		$this->debugNow("Fin. Appel à liste_des_utilisateurs");
		$this->liste_des_utilisateurs = $this->liste_utilisateurs();
		$this->debugNow("Fin de l'appel à liste_des_utilisateurs");
		$selecteur="";
		$temp["liste1"] = "";
		$temp["liste2"] = "";
		$userlist=explode(",", $post);
		if($groups) 
		{
			foreach($this->liste_des_utilisateursgroupes as $groupname)
			{
// 				$val=$groupname."***group***";
				$val = "_".$groupname;
				$selected = in_array($val, $userlist) ? "selected":"";
				$selecteur.="<option value=\"$val\" class=\"attention\" $selected>$groupname</option>";
			}
			if(!$groups_only) $selecteur.="<option value=\"\">----------</option>";
		}
		$exist_selected=0;
		foreach($userlist as $rang => $val) $userlist["$rang"] = trim($val);
		if(!$groups_only)
		{
			$this->debugNow("Parcours de liste_des_utilisateurs");
			$toCheck = ($nom == "liste_archives")? $this->liste_utilisateurs_archives():$this->liste_des_utilisateurs;
			$archMod = ($nom == "liste_archives")? True:false;
			foreach($toCheck as $init =>$array)
			{
// 				$this->debugNow("Début de $init");
				$init=substr($init, 0, 2);
				$seul=$array["seul"];
				$nom=$array["nom"];
// 				echo "<br>Test: '$init': '$nom'";
				$total=$init."clients";
				if(($_SESSION["db"]==$init OR $_SESSION["db"]==$total) AND $session==TRUE) 
				{
					$selected="selected"; 
					$exist_selected=1;
				}
				elseif($post == $init OR $post==$total)
				{
					$selected="selected"; 
					$exist_selected=1;
				}
				elseif(in_array($init, $userlist) OR in_array($total, $userlist))
				{
					$selected="selected"; 
					$exist_selected=1;
				}
				else $selected="";
				if($this->cal_mode) $valtotest="lire_agenda";
				else $valtotest="lire";
				if($test && $test !== true)
				{
					$valtotest=$test;
// 					$test=true;
				}
				$listeChoisie = $selected ? "liste1":"liste2";
// 				echo "<br>On teste $init avec $valtotest";
// 				echo "cal_mode = '{$this->cal_mode}'";
// 				print "<br>$this->testval($valtotest, $init): ". $this->/*testval*/("$valtotest", $init);
				if(($test==FALSE || (/*$this->debugNow("Appel à testval ($valtotest, $init)") AND */$this->testval("$valtotest", $init)/* AND $this->debugNow("Appel à testval fini")*/)) AND (trim($seul) != "1" OR $seul_autorise))
				{
					$temp["$listeChoisie"].="<option value=\"$init\" $selected>$nom</option>";
					if($firstUser)
					{
						$selected = "SELECTEDCONDI";
						$condiUser = $init;
						$firstUser = False;
					}
				}
// 				$this->debugNow("Fin de $init");
// 				echo "<br>'''{$temp["$listeChoisie"]}'''";
			}
			$this->debugNow("Fin du parcours de liste_des_utilisateurs");
// 			if(!$temp["liste1"]) $temp["liste1"] = "<option value=\"\"></option>";
			$selecteur .= $temp["liste1"].$temp["liste2"];
		}
		if($exist_selected == 0 && !$groups_only && ! $archMod)
		{
			$toPreg = "selected";
			$_POST["new_av"] = $condiUser;
			if($this->anchor != "index" && $this->anchor != "config_index") $this->registerDb();
		}
		else $toPreg = "";
		$selecteur .= "<option value=\"\"></option>";
// 		$toPreg = $exist_selected != 0 ? "":"selected";
		$selecteur = preg_replace("#SELECTEDCONDI#", $toPreg, $selecteur);
		return $selecteur;
	}
	
	function simple_selecteur($file="", $post="", $mode=0, $array=false, $addVal = False, $addNull = False, $selectName = "")
	{
		//modes:
		//0 = sélection sur la valeur, affichage de la valeur
		//1 = sélection sur la valeur, affichage de l'indice - non implémenté
		//2 = sélection sur l'indice, affichage de la valeur
		//3 = sélection sur l'indice, affichage de l'indice
		
		if(is_string($file)) list($file, $style) = preg_split("#,#", $file);
		$file=($file) ? $file : explode("\n", $_SESSION["optionGen"]["soustraitants"]);
		if(is_string($post) && preg_match("#(.*)@@(.*)#", $post, $matches))
		{
			$sep = $matches[1];
			$post = $matches[2];
// 			echo "\n<br>matching '$sep'";
			$newFile = array();
			foreach($file as $line)
			{
				$line = trim($line);
				list($a, $b) = preg_split("#$sep#", $line);
				$newFile[$a] = $b;
// 				echo "\n<br>'$a' => '$b'";
			}
			$file = $newFile;
		}
		$userlist=(is_array($post))?$post:explode(",", $post);
		$selecteur = "";
		$affichage = "";
		if($addNull) $selecteur.="\n<option $style value=\"\" selected></option>";
		$exist_selected=0;
		foreach($file as $indice => $ligne) if(trim($ligne) != "")
		{
			list($ligne) = preg_split("/,/", $ligne);
			$val=trim($ligne);
			if($mode < 2)
			{
// 				echo "<br>test de $val dans", $this->tab_affiche($userlist);
				if(in_array($val, $userlist))
				{
					$selected="selected";
					$exist_selected=1;
				}
				else $selected="";
			}
			else
			{
				if(in_array($indice, $userlist))
				{
					$selected="selected";
					$exist_selected=1;
				}
				else $selected="";
			}
			$affiche=$val;
			if($mode == 2) $val = $indice;
			if($array) $selecteur[]=$val;
			else $selecteur.="\n<option $style value=\"$val\" $selected>$affiche</option>";
			if($selected)
			{
				if ($affichage) $affichage .= ", ";
				$affichage .= $affiche;
			}
		}
		if($exist_selected==0)
		{	
			if($addVal && $post) $selecteur .= "\n<option $style value='$post' selected>$post</option>";
			else $selecteur.="\n<option $style value=\"\" selected></option>";
		}
		if($selectName)
		{
			$selecteur = "<select $style name='$selectName'>$selecteur</select>";
		}
		if($_POST["print"]) return $affichage;
		else return $selecteur;
	}
	
	function close($clean=FALSE, $preg="", $dir="temp")
	{
		if($this->pdf) return;
		elseif($this->csv) $this->csvSum();
		else
		{
			if($clean == "now"|$this->setDebugNow)
			{
				$this->debugNow("Fin de la page");
				$clean = false;
				list($s1, $m1) = preg_split("/ /", microtime()); 
				list($s2, $m2) = preg_split("/ /", $this->now[0]);
				$s = number_format($s1 + $m1 - $s2 - $m2, 2);
				echo $this->table_open();
				echo "\n<tr><th>Point</th><th>Secondes écoulées</th></tr>";
				foreach($this->now as $line)
				{
					$nLine = preg_replace("#^[0-9.]+ [0-9.]+ #", "", $line);
					preg_match("/(.*)(-)([^-]*$)/", $nLine , $regs);
					if($regs[3]) echo "\n</tr><td>{$regs[1]}</td><td>{$regs[3]}</td></tr>";
					else echo "\n</tr><td>$nLine</td><td></td></tr>";
				}
				echo "\n<tr><td><b>Total: </b></td><td><b>$s secondes</b></td></tr>";
				echo $this->table_close();
			}
			if($this->table) echo "\n\n<!-- end of centered text -->\n</textepage>\n\n";
			if($this->html) echo "\n\n</body>\n</html>";
			if($clean AND $preg) $this->clean_temp($preg);
		}
	}
	
	
	/*Fonctions de gestion des droits*/
	
	function perm_list()
	{
		require("{$this->settings["root"]}modules/perm_list.php");
	}
	
	
	
	function gerenew($utilisateur, $baseinit, $mode="normal", $vertical=false, $notable=false)
	{
		$utilisateur = trim($utilisateur);
		//notable :
		//1 => ni table, ni tr
		//2 => pas table, mais tr
		//false => table et tr
		
// 		echo "<br>test de '$baseinit'";
		$echoUtilisateur = $utilisateur;
		if(preg_match("#autoprolawyergroupe__#", $utilisateur))
		{
			$echoUtilisateur = preg_replace("#autoprolawyergroupe__#", "", $utilisateur);
			$aGroupe = true;
		}
		if(preg_match("#autoUsergroupe__#", $baseinit))
		{
			$echoBase = preg_replace("#autoUsergroupe__#", "", $baseinit);
// 			echo "<br>bon pour $baseinit";
			$uGroupe = true;
		}
		if(!isset($this->p_inits) || !isset($this->p_names) || !isset($this->p_values)) $this->perm_list();
		$inits=$this->p_inits;
		$names=$this->p_names;
		$values=$this->p_values;
		$gestion["level"]=0;
		if(!$uGroupe && $baseinit != "ALLPROLAWYERUSER") $baseinit=substr($baseinit, 0, 2);
		$emptyset=false;
// 		$base=$baseinit."droits";
		
		if($mode == "mefplus")
		{
			$mode = "mef_inits_only";
			$emptyset=true;
		}
		
		if(preg_match("#mef#", $mode))
		{
			if($notable == "1") $mef="";
			elseif($notable == "2") $mef="<tr>";
			else $mef="<table border=1><tr>";
			if($mode=="mef" || $mode=="mef_inits_only")
			{
				$max=0;
				foreach($names as $key => $val)
				{
					if($vertical)
					{
						$fName = $this->getImageName($val, TRUE);
						if(is_file($fName)) $source = $fName;

						else $source = "../image.php?type=ver&nom=$val&valeurdevert";
						$mef .= "<td style=\"vertical-align:bottom\"><img src=\"$source\"></td>";
					}
					else $mef .="<td>{$inits[$key]}</td>";
					$len=strlen($val) * 5;
// 					echo "<br>len de $val est $len";
					if($len > $max) $max = $len;
				}
				$mef = preg_replace("#valeurdevert#", "vert=$max", $mef);
			}
		}
		
		$this->liste_des_admins = $this->liste_admins();
		
		if(in_array($echoUtilisateur, $this->liste_des_admins)|| ($_SESSION["user"] == $echoUtilisateur && $_SESSION["type"] == "admin"))
		{
			foreach($values as $x => $value)
			{
				$name=$baseinit."__".$echoUtilisateur."__".$values[$x];
				if($mode == "array") $array["$x"]=1;
				if($mode == "mef" || $mode == "mef_rights_only") $mef .= "<td><input type=\"checkbox\" name=\"$name\" checked></td>";
				if($mode == "mef_rights_only_noadmin")
				{
					$mef .= "<td align=\"center\" class=\"attention\">x</td>";
// 					$mode = "mef_rights_only";
				}
			}
			if(preg_match("#mef#", $mode))
			{
				if($notable == "2") $mef .= "</tr>";
				elseif($notable == "1") $mef .= "";
				else $mef .= "</tr></table>";
				return $mef;
			}
			if($mode == "array") return $array;
		}
		
		$limit = "";
		if($emptyset)
		{
			$luser=$echoUtilisateur;
			$linit=$baseinit;
			$utilisateur="%";
			$baseinit="%";
			$limit = "LIMIT 1";
		}
		$gTest = ($aGroupe)? "1":"0";
		if($uGroupe) $test = "select * from droits where personne like '$echoUtilisateur' AND groupname like '$echoBase' AND accesgroupe like '$gTest' $limit";
		else $test = "select * from droits where personne like '$echoUtilisateur' AND init like '$baseinit' AND accesgroupe like '$gTest' $limit";
//    		echo "<br>$test";
		@$ex=mysqli_query($this->mysqli, $test);
		while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
		{
/*			echo "<br>";
			foreach($row as $a => $b) echo "$a a pour valeur $b, ";*/
			
			if($emptyset) $utilisateur = $luser;
			if($emptyset) $baseinit = $linit;
			if($mode == "array" || $mode == "mef" || $mode == "mef_rights_only" || $mode == "mef_rights_only_noadmin" || $emptyset)
			{
				if(($mode == "mef" || $mode == "mef_rights_only" || $mode == "mef_rights_only_noadmin" || $emptyset) && $notable != "1") $mef .= "<tr>";
				foreach($values as $x => $value)
				{
// 					$name = $aGroupe ? "autoprolawyergroupe__$name":$name;
					if($row["admin"] == "1") $row["$value"] = "1";
					$name=$baseinit."__".$utilisateur."__".$values[$x];
					$name = preg_replace("#\.#", "__point__", $name);
					$checked = ($row["$value"] == "1")? "checked":"";
					$checked = ($emptyset)? "":$checked;
					if($mode == "array") $array["$x"]=$row["$value"];
					if($mode == "mef" || $mode == "mef_rights_only" || $mode == "mef_rights_only_noadmin" || $emptyset) $mef .= "<td><input type=\"checkbox\" name=\"$name\" $checked></td>";
				}
				if($mode == "mef" || $mode == "mef_rights_only" || $mode == "mef_rights_only_noadmin")
				{
					if($notable == "2") $mef .= "</tr>";
					elseif($notable == "1") $mef .= "";
					else $mef .= "</tr></table>";
					return $mef;
				}
				if($mode == "array") return $array;
			}
		}
		
		
		//Si on n'est pas sorti à ce stade, c'est que la personne n'est pas administrateur et n'a pas de droits.
		foreach($inits as $x => $val)
		{
			$name=$baseinit."__".trim($utilisateur)."__".$values[$x];
			$name = preg_replace("#\.#", "__point__", $name);
			if($mode == "array") $array["$x"]=0;
			if($mode == "mef" || $mode == "mef_rights_only" || $mode == "mef_rights_only_noadmin") $mef .= "<td><input type=\"checkbox\" name=\"$name\"></td>";
			//echo "<br>'$name'";
		}
		if(preg_match("#mef#", $mode))
		{
			if($notable == "2") $mef .= "</tr>";
			elseif($notable == "1") $mef .= "";
			else $mef .= "</tr></table>";
			return $mef;
		}
		if($mode == "array") return $array;
	}
	
	function liste_utilisateurs($force=false, $all = false)
	{
		if(($this->liste_des_utilisateurs) && $force == false) return $this->liste_des_utilisateurs;
// 		elseif(!isset($this->right)) return array(); //TODO: pourquoi cela n'est-il plus nécessaire ? Peut-être parce que les droits sont gérés dans les options globales appelées avant toute chose.
		else
		{
			$this->liste_des_utilisateurs = array();
			$query = "select * from utilisateurs where nom not like 'tableDefaultValue' and archive not like '1' order by nom";
			@$ex = mysqli_query($this->mysqli, $query);
			if(!$ex)
			{
				#$error = mysqli_error($this->mysqli); //TODO: cette fonction est appelée sur la page de connexion. Si on n'intercepte pas l'ereur, cela fait un message inutile
// 				$this->catchError("010-005#-#$error#\$this->list_utilisateurs($force)", 4);
				return array();
			}
			while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
			{
				$tempar=array();
				foreach($row as $nom => $val) /*if($nom != initiales)*/ $tempar[$nom]=$val;
				$initiales=$row["initiales"];
				$this->liste_des_utilisateurs[$initiales]=$tempar;
			}
			if($all) $this->liste_des_utilisateurs = $this->liste_des_utilisateurs + $this->liste_utilisateurs_archives($force);
			#if($all) $this->liste_des_utilisateurs = array_merge($this->liste_des_utilisateurs, $this->liste_utilisateurs_archives($force));
			return $this->liste_des_utilisateurs;
		}
	}
	
	function liste_utilisateurs_archives($force=false)
	{
		if(($this->liste_des_utilisateurs_archives) && $force == false) return $this->liste_des_utilisateurs_archives;
// 		elseif(!isset($this->right)) return array(); //TODO: pourquoi cela n'est-il plus nécessaire ? Peut-être parce que les droits sont gérés dans les options globales appelées avant toute chose.
		else
		{
			$this->liste_des_utilisateurs_archives = array();
			$query = "select * from utilisateurs where nom not like 'tableDefaultValue' and archive like '1' order by nom";
			@$ex = mysqli_query($this->mysqli, $query);
			if(!$ex)
			{
				#$error = mysqli_error($this->mysqli); //TODO: cette fonction est appelée sur la page de connexion. Si on n'intercepte pas l'ereur, cela fait un message inutile
// 				$this->catchError("010-005#-#$error#\$this->list_utilisateurs($force)", 4);
				return array();
			}
			while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
			{
				$tempar=array();
				foreach($row as $nom => $val) /*if($nom != initiales)*/ $tempar[$nom]=$val;
				$initiales=$row["initiales"];
				$this->liste_des_utilisateurs_archives[$initiales]=$tempar;
			}
			return $this->liste_des_utilisateurs_archives;
		}
	}
	
	function liste_utilisateursgroupes($force=false)
	{
		if(isset($this->liste_des_utilisateursgroupes) && $force == false) return $this->liste_des_utilisateursgroupes;
		else
		{
			$this->liste_des_utilisateursgroupes = array();
			$query = "select * from utilisateursgroupes group by nomgroupe";
			@$ex = mysqli_query($this->mysqli, $query);
			if(!$ex) return array();
			while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
			{
				$this->liste_des_utilisateursgroupes[]=$row["nomgroupe"];
			}
			return $this->liste_des_utilisateursgroupes;
		}
	}

	function liste_utilisateursgroupes_ex($force=false)
	{
		/*fonction qui retourne la liste des membres des groupes sous la forme
		groupe 1 => (0 => membre 1)
		groupe 2 => (0 => membre 3, 1 => membre 4)
		La liste ne donne que les membres des groupes, non les non-membres
		*/
		
		if(isset($this->liste_des_utilisateursgroupes_ex) && $force == false) return $this->liste_des_utilisateursgroupes_ex;
		else
		{
			$this->liste_des_utilisateursgroupes_ex = array();
			$query = "select * from utilisateursgroupesmembres order by groupe, membre";
			@$ex = mysqli_query($this->mysqli, $query);
			if(!$ex) return array();
			while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
			{
				$ngr= $row["groupe"];
				$this->liste_des_utilisateursgroupes_ex["$ngr"][]=$row["membre"];
			}
			
			$this->debugNow("liste_utilisateursgroupes - debut");
			$this->getLdapGroups();
			$this->debugNow("liste_utilisateursgroupes - fin");
			return $this->liste_des_utilisateursgroupes_ex;
		}
	}
	
	function liste_utilisateursgroupes_revert($force=false)
	{
		/*fonction qui retourne la liste des membres des groupes sous la forme
		membre 1 => (0 => groupe 1)
		membre 2 => (0 => groupe 3, 1 => groupe 4)
		La liste ne donne que les membres des groupes, non les non-membres
		*/
		
		if(isset($this->liste_des_utilisateursgroupes_revert) && $force == false) return $this->liste_des_utilisateursgroupes_revert;
		else
		{
			$this->liste_des_utilisateursgroupes_revert = array();
			$templist = $this->liste_utilisateursgroupes_ex();
			foreach($templist as $grpName => $listeUsers)
			{
				foreach($templist[$grpName] as $singleUser)
				{
					if(!is_array($this->liste_des_utilisateursgroupes_revert[$singleUser])) $this->liste_des_utilisateursgroupes_revert[$singleUser] = array();
					if(!in_array($grpName, $this->liste_des_utilisateursgroupes_revert[$singleUser])) $this->liste_des_utilisateursgroupes_revert[$singleUser][] = $grpName;
				}
			}
			return $this->liste_des_utilisateursgroupes_revert;
		}
	}
	
	function getMyRights($user = "", $force = false)
	{
		if(!$user) $user = $_SESSION["user"];
		if(isset($this->myRights["$user"]) && ! $force) return $this->myRights["$user"];
		if(!isset($this->myRights)) $this->myRights = array();
		if(!isset($this->myRights["$user"])) $this->myRights["$user"] = array();
		$utilisateursgroupesMembres = $this->liste_utilisateursgroupes_ex();
		$accesgroupesMembres = $this->liste_accesgroupes_ex();
	}

/*	function AccesGroups($user = "", $force = false) // TODO: voir si cela ne fait pas double emploi avec liste_accesgroupes_ex
	{
		if(!isset($this->accesGroupsMembership)) $this->accesGroupsMembership = array();
		if(!$user) $user = $_SESSION["user"];
		if(isset($this->accesGroupsMembership["$user"]) && ! $force) return $this->accesGroupsMembership["$user"];
		$q = "select ugroupe from accesgroupesmembres where umembre like '$user'";
		$e = mysqli_query($this->mysqli, $q);
		while($r = mysqli_fetch_array($e)) $this->accesGroupsMembership["$user"][] = $r["ugroupe"];
		return $this->accesGroupsMembership["$user"];
	}*/
	
	function liste_acces($force=false)
	{
		if(($this->liste_des_acces) && $force == false) return $this->liste_des_acces;
		else
		{
			$this->liste_des_acces = array();
			$query = "select user, type from acces where user not like 'tableDefaultValue' order by user";
			if($this->mysqli) // la fonction n'a de sens que si la connexion à mysql a déjà eu lieu. 
			{
                            $ex = mysqli_query($this->mysqli, $query);
                            if(!$ex)
                            {
                                    $error = mysqli_error($this->mysqli);
    // 				$this->catchError("010-005#-#$error#\$this->list_acces($force)", 4);
                                    return array();
                            }
                            while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
                            {
                                    $this->liste_des_acces[] = array("user" => $row["user"],"type" => $row["type"]);
                                    if($_SESSION["user"] == $row["user"]) $curUser = true;
                            }
                            }
			if(!$curUser)$this->liste_des_acces[] = array("user" => $_SESSION["user"],"type" => $_SESSION["type"]);
			return $this->liste_des_acces;
		}
	}
	
	function liste_accesgroupes($force=false, $gpradd=false)
	{
		if(isset($this->liste_des_accesgroupes) && $force == false) return $this->liste_des_accesgroupes;
		else
		{
			$this->liste_des_accesgroupes = array();
			$query = "select * from accesgroupes group by nomugroupe";
			@$ex = mysqli_query($this->mysqli, $query);
			if(!$ex) return array();
			while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
			{
				if($gpradd) $row["nomugroupe"] .= "__groupe";
				$this->liste_des_accesgroupes[]=$row["nomugroupe"];
			}
			return $this->liste_des_accesgroupes;
		}
	}
	
	function liste_accesgroupes_ex($force=false)
	{
		/*fonction qui retourne la liste des membres des groupes sous la forme
		groupe 1 => (membre 1)
		groupe 2 => (membre 3, membre 4)
		La liste ne donne que les membres des groupes, non les non-membres
		*/
		if(isset($this->liste_des_accesgroupes_ex) && $force == false) return $this->liste_des_accesgroupes_ex;
		else
		{
			$this->liste_des_accesgroupes_ex = array();
			$query = "select * from accesgroupesmembres order by ugroupe, umembre";
			@$ex = mysqli_query($this->mysqli, $query);
			if(!$ex) return array();
			while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
			{
				$ngr= $row["ugroupe"];
// 				echo "<br>Pour $ngr, membre = {$row["umembre"]}";
				$this->liste_des_accesgroupes_ex["$ngr"][]=$row["umembre"];
			}
			$this->debugNow("liste_accesgroupes - debut");
			$this->getLdapGroups();
			$this->debugNow("liste_accesgroupes - fin");
			return $this->liste_des_accesgroupes_ex;
		}
	}
	
	function getLdapGroups()
	{
		if(in_array("ldap", $_SESSION["connectionMethods"]) && $_SESSION["ldapServer"] && !$this->ldapGroupsGiven)
		{
			$this->doConnectLdap($_SESSION["user"], $_SESSION["pwd"]); //essai de connection authentifiée, sinon on essaie une connection simple
			//$dn2 = "ou=group,dc={$_SESSION["ldapServer"]}";
			$dn2 = "ou=group,dc=internal,dc=avocats-ch,dc=ch";
			$params = array("*");
// 			$trouve  = ($func == "liste_acces") ? $ar["user"]:$ar["initiales"];
// echo "{$this->ldapConnected}, $dn2, \"(&(memberuid=*))\", $params";
			$search = ldap_search($this->ldapConnected, $dn2, "(&(memberuid=*))", $params);
			$infos = ldap_get_entries($this->ldapConnected, $search);
			$personnes = $this->liste_utilisateurs();
			$cListe = array();
			foreach($personnes as $init => $array)
			{
				$nom = strtolower($personnes["$init"]["nom"]);
				$cListe["$nom"] = $init;
			}
// 			$this->tab_affiche($cListe);
			if(is_array($infos))
			{
				foreach($infos as $info)
				{
					$groupe = $info["cn"][0];
					if($groupe)
					{
						foreach($info["memberuid"] as $init => $member) if($init !== "count")
						{
							if(!isset($this->liste_des_accesgroupes_ex["$groupe"]) || !in_array($member, $this->liste_des_accesgroupes_ex["$groupe"])) $this->liste_des_accesgroupes_ex["$groupe"][] = $member;
							if(isset($cListe["$member"]) && (!isset($this->liste_des_utilisateursgroupes_ex["$groupe"]) || !in_array($cListe["$member"], $this->liste_des_utilisateursgroupes_ex["$groupe"]))) $this->liste_des_utilisateursgroupes_ex["$groupe"][] = $cListe["$member"];
						}
					}
				}
			}
			$this->ldapGroupsGiven = true;
// 			$this->tab_affiche($this->liste_des_accesgroupes_ex);
// 			$this->tab_affiche($this->liste_des_utilisateursgroupes_ex);
		}
		
	}
	
	function keepTODOold(&$toComplete, $func)
	{
		if(in_array("ldap", $_SESSION["connectionMethods"]) && $_SESSION["ldapServer"])
		{
			$this->doConnectLdap($_SESSION["user"], $_SESSION["pwd"]); //essai de connection authentifiée, sinon on essaie une connection simple
			//$dn2 = "dc={$_SESSION["ldapServer"]}";
			$dn2 = "dc=internal,dc=avocats-ch,dc=ch";
			$params = array("cn");
			foreach($this->$func() as  $i => $ar)
			{
// 				echo "<br>$i";
				$cherche = ($func == "liste_acces") ? $ar["user"]:$ar["nom"];
				$trouve  = ($func == "liste_acces") ? $ar["user"]:$ar["initiales"];
// 				echo "<br>On traite '$trouve'";
				$search = ldap_search($this->ldapConnected, $dn2, "(&(memberuid=$cherche))", $params);
				$infos = ldap_get_entries($this->ldapConnected, $search);
				if(is_array($infos))
				{
// 					echo "<br><br>$trouve est membre de : ";
					foreach($infos as $info)
					{
						$groupe = $info["cn"][0];
						if($groupe)
						{
// 							echo "<br>'$groupe'";
							$toComplete["$groupe"][] = $trouve;
						}
					}
				}
			}
		}
	}
		
	function liste_admins($force=false)
	{
		if(isset($this->liste_des_admins) && $force == false) return $this->liste_des_admins;
		else
		{
			$this->liste_des_admins = array();
			$query = "select user from acces where type like 'admin' order by user";
			@$ex = mysqli_query($this->mysqli, $query);
			if(!$ex) return array();
			while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
			{
				$this->liste_des_admins[] = $row["user"];
			}
			return $this->liste_des_admins;
		}
	}
		
	function liste_droits($user="ALL", $force=false, $withgroups = false)
// 	function liste_droits($user="ALL", $force=false, $withgroups = true)
	{
		if(!isset($this->liste_des_droits)) $this->liste_des_droits = array();
		if($withgroups)
		{
			$accesgroupes = $this->liste_accesgroupes_ex();
			$utilisateursgroupes = $this->liste_utilisateursgroupes_ex();
		}
		if(!$user) return;
		if((isset($this->liste_des_droits_ok["$user"]) || isset($this->liste_des_droits_ok["ALL"])) && $force == false && (isset($this->liste_des_droits_groups["$user"]) || isset($this->liste_des_droits_groups["ALL"]) ||!$withgroups)) return $this->liste_des_droits;
		else
		{
// 			echo "<br>On la teste...";
			if(!$withgroups) $exclusion = "where accesgroupe like '0' AND init not like ''";
			$this->liste_admins();
			$query="select * from droits $exclusion";
			@$ex=mysqli_query($this->mysqli, $query);
			if($ex)
			{
				if(!isset($this->p_values)) $this->perm_list();
// 				$exclusion=array("nod");
// 				$q_ex="show columns from utilisateurs";
// 				$ex_ex=mysqli_query($this->mysqli, $q_ex);
// 				while($q_row=mysqli_fetch_array($ex_ex, MYSQLI_ASSOC))  $exclusion[] = $q_row["Field"];
				while($row=mysqli_fetch_array($ex, MYSQLI_ASSOC))
				{
					$ky ++;
// 					if($ky != 1) continue;
					$rowinit=$row["init"];
					$rowaccesgroupe=$row["accesgroupe"];
					$rowgroupname=$row["groupname"];
					$rowpersonne = $row["personne"];
// 					echo "<br><br>rowinit: '$rowinit', rowaccesgroupe: '$rowaccesgroupe',rowgroupname: '$rowgroupname', rowpersonne: '$rowpersonne'";
					$inits = array();
					$personnes = array();
					if($rowinit == "" && $rowgroupname)
					{
//  						echo "<br>rowinit vaut $rowinit et rowgroupname vaut $rowgroupname";
 						if(is_array($utilisateursgroupes["$rowgroupname"])) foreach($utilisateursgroupes["$rowgroupname"] as $member) $inits[] = $member;
					}
					else $inits[] = $rowinit;
// 					echo "<br>Inits enregistrés";
// 					$this->tab_affiche($inits);
// 					echo "--------------";
					if($rowaccesgroupe == "1")
					{
//  						echo "<br>rowaccesgroupe vaut $rowaccesgroupe et rowpersonne vaut $rowpersonne";
 						if(is_array($accesgroupes["$rowpersonne"])) foreach($accesgroupes["$rowpersonne"] as $member) $personnes[] = $member;
					}
					else $personnes[] = $rowpersonne;
// 					echo "<br>Personnes enregistrées";
// 					$this->tab_affiche($personnes);
// 					else $inits = $rowinits;
// 					$droit=$row["droit"];
// 					if($row["admin"] == 1) $droit = "admin";
					foreach($row as $nom => $val)if(in_array($nom, $this->p_values))
					{
						foreach($personnes as $personne)foreach($inits as $init) if(substr($user, 1) == $personne || $init == $user || $user == "ALL")
						{
// 							echo "<br>:$init";
							$personne = strtolower($personne);
							if(!isset($this->liste_des_droits[$init][$personne])) $this->liste_des_droits[$init][$personne]=array();
// 							if($init == "os" && $personne == "subilia") echo "<br>$nom => $val: On traite '$personne' comme personne et '$init' comme init";
// 							if(preg_match("#,#", $init)) echo "<br>$nom => $val: On traite '$personne' comme personne et '$init' comme init";
							if(in_array($personne, $this->liste_des_admins)) $this->liste_des_droits[$init][$personne][$nom] = 1;
							elseif($row["admin"] == 1) $this->liste_des_droits[$init][$personne][$nom] = 1;
							elseif($val == 1) $this->liste_des_droits[$init][$personne][$nom] = $val;
						}
					}
// 					$this->tab_affiche($personnes);
// 					$this->tab_affiche($inits);
				}
			}
			$this->liste_des_droits_ok["$user"]=true;
			if($withgroups) $this->liste_des_droits_groups["$user"]=true;
			return $this->liste_des_droits;
		}
	}
	
	function testval($niveau, $base = "", $utilisateur = "")
	{
		//if($this->anchor == "index") return true;
		if($this->menu == "noCheck") return true;
		if(!$utilisateur) $utilisateur = $_SESSION["user"];
		$utilisateur = strtolower($utilisateur);
		if(!$base) $base = $_SESSION["db"];
		//$this->liste_des_admins = $this->liste_admins();
		if($_SESSION["type"] == "admin" && $utilisateur == $_SESSION["user"] /*sinon, si on teste pour autre chose que pour soi alors qu'on est administrateur, la valeur sera toujours vraie*/) return true;
// 		die('ici');
		$this->liste_des_droits = $this->liste_droits("ALL", false, true);
// 		$this->tab_affiche($this->liste_des_droits);
// 		echo "<br>liste des droits[$base][$utilisateur][$niveau] = ".$this->liste_des_droits["$base"]["$utilisateur"]["$niveau"];
		if($niveau == "generique")
		{
			foreach($this->liste_des_droits["$base"]["$utilisateur"] as $key => $value) if($value == "1") return true;
			else return false;
		}
		$this->last_test=$niveau;
		if($this->liste_des_droits["$base"]["$utilisateur"]["$niveau"] == "1") return true;
		else return false;
		
		//TODO: rajouter le test via les groupes. Ne pas oublier d'utiliser les fonctions liste_..._ex() qui vont également récupérer les groupes LDAP dont l'utilisateur est membre.
	}
	
	function lColors()
	{
		$this->liste_des_utilisateurs = $this->liste_utilisateurs();
		foreach ($this->liste_des_utilisateurs as $gugusse => $props)
		{
			$this->liste_des_utilisateurs["$gugusse"]["lCouleur"] = $this->lColor($this->liste_des_utilisateurs["$gugusse"]["couleur"]);
		}
	}
	
	function lColor($couleur)
	{
		$raw_color=preg_replace("/#/", "", trim($couleur));
		$red = substr($raw_color, 0, 2);
		$green = substr($raw_color, 2, 2);
		$blue = substr($raw_color, 4, 2);
		$lred=dechex(hexdec($red) + (255 - hexdec($red)) /2);
		$lgreen=dechex(hexdec($green) + (255 -hexdec($green)) /2);
		$lblue=dechex(hexdec($blue) + (255 -hexdec($blue)) /2);

		return ("#".$lred.$lgreen.$lblue);
	
	}
	
	function describe_table($name, $ordonne=true)
	{
		$r=array();
		$ressource=mysqli_query($this->mysqli, "describe $name");
		while($row=mysqli_fetch_array($ressource, MYSQLI_ASSOC))
		{
			$l="";
			foreach($row as $key => $elem) $l.="'$key' = '$elem'\t";
			$r[]=$l;
		}
		if($ordonne) sort($r);
		return $r;
	}
	
	
	public function connection($user = "noUserSet", $pwd = "noPwdSet", $redirect = true, $forceMethod = false)
	{
		$this->guestConnected = False;
//  		echo "<br>appel à la fonction connection avec $user, $pwd et $forceMethod";
		/*
		Cette fonction sert à connecter selon divers modules:
		- mysql: connection via les mots de passe de mysqli_connect
		- prolawyer: connection via les mots de passe stockés dans la table créée par le programme
		- ldap: connection via les mots de passe stockés dans un serveur LDAP
		
		Le problème existe si prolawyer::connection() est appelée sans paramètre. Par défaut, le programme tente de s'authentifier avec soit des paramètres forcés (forceUser, forcePwd), soit le mot de passe stocké en session.
		Ce problème est résolu en ne donnant pas de paramètre par défaut à doConnectMysql, mais en passant ces paramètre à cette fonction lorsqu'elle est appelée par doConnectProlawyer
		
		On peut également gérer des méthodes supplémentaires. On les passe avec une variable supplémentaire (array) $this->alternateMethods
		*/

		if(is_array($forceMethod))
		{
			foreach($forceMethod as $method) if($this->doConnect($method, $user, $pwd))
			{
				$this->isConnected = True;
				return True;
			}
			return False;
		}
		elseif ($forceMethod)
		{
//  			echo "<br>Connexion...";
			if($this->doConnect($forceMethod, $user, $pwd))
			{
				$this->isConnected = True;
				return True;
			}
			return false;
		}
		else
		{
			if(isset($this->alternateMethods))
			{
				if(is_array($this->alternateMethods))
				{
					foreach($this->alternateMethods as $method) if (! in_array($method, $_SESSION["connectionMethods"])) $_SESSION["connectionMethods"][] = $method;
				}
			}
			foreach($_SESSION["connectionMethods"] as $method) if($this->doConnect($method, $user, $pwd))
			{
				$this->isConnected = True;
				return True;
			}
			//else echo "<br>La méthode $method avec $user et $pwd a échoué";
		}
		//Si la connection a marché jusque là, on a retourné "True". Donc à ce stade, toutes les méthodes ont échoué. Traitement de la connexion guest
		if(isset($this->guestAllowed) && $this->guestAllowed)
		{
			$isOK = $this->doConnectGuest();
			if($isOK)
			{
				$this->guestConnected = True;
				return True;
			}
		}
		if($redirect)
		{
			$nextPage = basename($_SERVER["PHP_SELF"]);
			$location = "./";
			if($this->rel_dir != "config")
			{
				if($_REQUEST["new_check"]) $add = "&erreur=rate1";
				else $add = "&erreur=rate2";
				if($this->rel_dir)
				{
					$add .= "&nextPath={$this->rel_dir}";
					$location = $this->settings["root"];
				}
				$this->setCookie("last_post", serialize($_REQUEST));
			}
			$location .= "index.php?nextPage=$nextPage".$add;
			header ("location: $location");
			die("<br>'$location'");
		}
		return false;
	}
	
	
	protected function doConnect($method, $user, $pwd)
	{
// 		die("$method, $user, $pwd");
		//restauration d'une session antérieure
		if($_REQUEST["restore"])
		{
			$lpost=unserialize(stripslashes($this->getCookie("last_post")));
			$lpost["start_utilisateur"] = $_REQUEST["start_utilisateur"];
			$lpost["start_pwd"] = $_REQUEST["start_pwd"];
			$_REQUEST = $lpost;
			$_REQUEST["new_check"] = "on";
			$this->unsetCookie("last_post");
			$_POST = $_REQUEST;
		}

		if($_REQUEST["start_utilisateur"]) $_REQUEST["forceUser"] = $_REQUEST["start_utilisateur"];
		if($_REQUEST["start_pwd"]) $_REQUEST["forcePwd"] = $_REQUEST["start_pwd"];
		if($user == "noUserSet") $user = $_REQUEST["forceUser"] ? $_REQUEST["forceUser"]: $_SESSION["user"];
		if($pwd == "noPwdSet")  $pwd  = $_REQUEST["forcePwd"] ? $_REQUEST["forcePwd"]: $_SESSION["pwd"];
		if(!$method)  $method  = "(pas de paramètre)";
//     	if($method != "prolawyer")	echo "<br>Connection (méthode $method) avec $user et $pwd ({$_SESSION["user"]} et {$_SESSION["pwd"]})";
		
		$func = "doConnect" .ucfirst($method);
		if(method_exists($this, $func))
		{
// 			echo "<br>Method $method:. Session user: {$_SESSION["user"]}";
			$con = $this->$func($user, $pwd);

			if($con && ($_REQUEST["forceUser"] || $this->guestConnected)) //TODO: vérifier la cohérence, car l'utilisateur ne change en principe pas  Il n'y a donc pas lieu de recharger (à chaque page, puisque doConnect est appelée à chaque page !) les options, surtout après les avoir déjà chargée par __construct();
			{
				$_SESSION = array();
				$_SESSION["user"] = $user;
// 				echo "<br>user: $user";
				$_SESSION["pwd"] = $pwd;
				$this->getOptions("force"); //options passées en session. En principe appelées une seule fois, sinon la fonction ressort sans relire
				$this->getOptionsPerso("force"); //options passées en session relatives à l'utilisateur. En principe appelées une seule fois, sinon la fonction ressort sans relire
				$this->getPagesOptions(); //options de la page, qui doivent être rechargées à chaque page

				$this->getOptionsPerso("force"); //nécessaire, en cas de changement de session, de virer toutes les options..
				$this->getVersion();

			}
			if($con) $this->doConnectDb($_SESSION["dbAdmin"], $_SESSION["dbPwd"], true);
			return $con;
		}
// 		else echo("La méthode de connection '$method' (fonction $func) n'est pas définie");
	}
	
	protected function doConnectApache($user, $pwd)
	{
// 		return false; //franchement pas fonctionnel... quoique...
		if($this->apacheConnected)return true;
		if($_SERVER[PHP_AUTH_USER] == $user && $_SERVER[PHP_AUTH_PW] == $pwd) return true;
		else return false;
	}
	
	protected function doConnectGuest()
	{
		$this->guestConnected = False;
		if(!$this->doConnectDb($_SESSION["dbAdmin"], $_SESSION["dbPwd"], true)) return false;
		$this->guestConnected = True;
		$_SESSION["user"] = "guest";
		return $this->guestConnected;
	}
	
	protected function doConnectProlawyer($user, $pwd)
	{
		if(!$this->doConnectDb($_SESSION["dbAdmin"], $_SESSION["dbPwd"], true)) return false;
		$q = "select * from passwords where user like '$user' AND pwd like password('$pwd')";
// 		echo "'$q'";
		$e = mysqli_query($this->mysqli, $q);
		if(mysqli_num_rows($e)) return true;
		else return false;
	}
	
	protected function doConnectLdap($user, $pwd)
	{
		$dn = "uid=$user,ou=user,dc=internal,dc=avocats-ch,dc=ch";//{$_SESSION["ldapDN"]}"; //ldapDN: ou=user,dc=machin
		//$dn = "uid=$user,ou=user,dc={$_SESSION["ldapServer"]}";
		if($this->ldapBinded) return true;
		if(!$this->doConnectLdapDb()) return false;
		$b = @ldap_bind($this->ldapConnected, $dn, $pwd);
		if($b)
		{
			$this->ldapBinded = $b;
			return true;
		}
		return false;
	}
	
	protected function doConnectLdapDb()
	{
		if($this->ldapConnected) return true;
		$con = @ldap_connect($_SESSION["ldapServer"]);
		if($con)
		{
			$this->ldapConnected = $con;
			ldap_set_option($this->ldapConnected, LDAP_OPT_PROTOCOL_VERSION, 3);
			return true;
		}
		return false;
		
	}
	protected function doConnectMysql($user = "", $pwd = "")
	{
   		#echo "<br>ConnectMysql: méthode appelée avec $user et $pwd";
		if($this->dbConnected) return true;
		$connect = @mysqli_connect($_SESSION["mysqlServer"], $user, $pwd, "", $_SESSION["mysqlPort"]);
		if($connect)
		{
			$this->con1 = $connect;
			$this->mysqli = $connect;
			$this->mysqli->set_charset("utf8");
			if(mysqli_select_db($this->mysqli, $_SESSION["dbName"])) $this->mysqlConnected = true;
			elseif (mysqli_query($this->mysqli, "CREATE DATABASE {$_SESSION["dbName"]}") && mysqli_select_db($this->mysqli, $_SESSION["dbName"])) $this->mysqlConnected = true;
			$this->testConDb = mysqli_select_db($this->mysqli, $_SESSION["dbName"]);
			if($this->testConDb) mysqli_query($this->mysqli, "SET sql_mode = '';");
		}
		if($this->mysqlConnected) return true;
		return false;
	}
	
	
	protected function doConnectDb($user = "", $pwd = "", $calledByProlawyer = false)
	{
 		$comp = ($calledByProlawyer) ? " (appelée par Prolawyer)":"";
 		if($calledByProlawyer)
 		{
			if($_POST["rUser"] && !$user) $user = $_POST["rUser"];
			if($_POST["rPwd"] && !$pwd) $pwd = $_POST["rPwd"];
 		}
//    		echo "<br>ConnectDb: méthode appelée avec '$user' et '$pwd'. calledByProlawyer vaut '$calledByProlawyer' $comp";
		if($this->dbConnected) return true;
		$connect = mysqli_connect($_SESSION["mysqlServer"], $user, $pwd, "", $_SESSION["mysqlPort"]);
		if($connect)
		{
// 			echo "<br>Connected";
			$this->con2 = $connect;
			$this->mysqli = $connect;
			$this->mysqli->set_charset("utf8");
			if(mysqli_select_db($this->mysqli, $_SESSION["dbName"]))
			{
				$this->dbConnected = true;
				mysqli_query($this->mysqli, "SET sql_mode = '';");
			}	
			elseif (mysqli_query($this->mysqli, "CREATE DATABASE {$_SESSION["dbName"]}") && mysqli_select_db($this->mysqli, $_SESSION["dbName"])) $this->dbConnected = true;
		}
// 		else	echo "<br>Not Connected";
		if($this->dbConnected) return true;
		return false;
	}
	
	public function createUser($nom, $pwd, $type, $method="")
	{
		if(!$method)
		{
			if($_SESSION["defaultMethod"]) $method = $_SESSION["defaultMethod"];
			else $method = "Prolawyer";
		}
		$this->setUserType($nom, $type, false, $method);
		if($pwd != "***noChange***") $this->setUserPwd($nom, $pwd);
		
	}

	public function setUserType($user, $type, $check=false)
	{
		if(!$this->doConnectDb($_SESSION["dbAdmin"], $_SESSION["dbPwd"], true)) return false;
		$q1 = "delete from acces where user like '$user'";
		$q2 = "insert into acces set user = '$user', type = '$type'";
		$e1 = mysqli_query($this->mysqli, $q1);
		$e2 = mysqli_query($this->mysqli, $q2);
		if($e1 && $e2) return true;
		return false;
	}
	
	public function setUserPwd($user = "", $pwd = "", $check=false, $forceMethod = false)
	{
		if(is_array($forceMethod))
		{
			foreach($_SESSION["connectionMethods"] as $method) 
			{
				if(in_array($method, $forceMethod) && $this->doSetUser($method, $user, $pwd, $check)) return True;
			}
		}
		else
		{
			foreach($_SESSION["connectionMethods"] as $method) 
			{
				if((!$forceMethod || $method == $forceMethod) && $this->doSetUser($method, $user, $pwd, $check)) return True;
			}
		}
		return false;
	}
	
	protected function doSetUser($method, $user, $pwd, $check)
	{
		$func = "doSetUser" .ucfirst($method);
		if(method_exists($this, $func)) return $this->$func($user, $pwd, $check);
		//else echo ("La méthode de connection '$method' (fonction $func) n'est pas définie");
// 		if($method == "prolawyer") return $this->doSetUserProlawyer($user, $pwd, $check);
// 		if($method == "mysql") return $this->doSetUserMysql($user, $pwd, $check);
		return false;
	}
	
	protected function doSetUserProlawyer($user, $pwd, $check)
	{
// 		echo "toto";
		if(!$this->doConnectDb($_SESSION["dbAdmin"], $_SESSION["dbPwd"], true)) return false;
// 		echo "titi";
		$q1 = "delete from passwords where user like '$user'";
		$q2 = "insert into passwords set user = '$user', pwd = password('$pwd')";
		$q3 = "select *, password('$pwd') as cPwd  from passwords where user like '$user'";
		
		if($check)
		{
			$e3 = mysqli_query($this->mysqli, $q3);
			if(mysqli_num_rows($e3))
			{
				$this->catchError("100-005", 2);
				return false;
			}
		}
		$e1 = mysqli_query($this->mysqli, $q1);
		$e2 = mysqli_query($this->mysqli, $q2);
		$e3 = mysqli_query($this->mysqli, $q3);
		while($r = mysqli_fetch_array($e3))
		{
			if(mysqli_num_rows($e3) == 1 && $user == $r["user"] && $r["cPwd"] == $r["pwd"] && $type == $r["type"]) return true;
			echo " $user == {$r["user"]} && {$r["cPwd"]} == {$r["pwd"]} && $type == {$r["type"]}";
		}
		return false;
	}
	
	
	
	/*Fonctions de configuration*/
	
	function get_prolawyer_tables($force = false)
	{
		if(isset($this->deftables) && $force == false) return $this->deftables;
		else
		{
			$this->deftables = array();
			$dir=opendir("{$this->settings["root"]}config/tables");
			while($file=readdir($dir))
			{
				if(preg_match("#def_#", $file))
				{
					if(preg_match("#_system.sql#", $file))
					{
// 						$tname = preg_replace("#_system.php#", "", preg_replace("#def_#", "", $file));
						$tname= preg_replace("#def_(.*)_system.sql#", "\\1", $file);
						$this->deftables["system"][$file] = $tname;
					}
					if(preg_match("#_partner.sql#", $file))
					{
// 						$tname= preg_replace("#_partner.php#", "", preg_replace("#def_#", "", $file));
						$tname= preg_replace("#def_(.*)_partner.sql#", "\\1", $file);
						$this->deftables["partner"][$file] = $tname;
					}
				}
			}
			return $this->deftables;
		}
	}
	
	function create_prolawyer_table($nom, $type="", $quiet=true, $action="create")
	{
		
		/**************************************************
		Paramètres de la fonction:
		- soit nomDeTableSystem, "", ..
		- soit nomDeTablePartner, clients|op, ..
		où nomDeTablePartner est de type xxclients|xxop
		
		codes de retour:
		false=échec dans la mise à jour ou la création
		1=création avec succès
		2=mise à jour avec succès
		3=la table existe déjà et est à jour
		**************************************************/
// 		echo "<br>Création de la table avec nom='$nom', type='$type', quiet='$quiet', action='$action'<br>";

		//Par sécurité, on augmente temporairement la mémoire disponible et le temps d'exécution
		ini_set("memory_limit", "4096M");
		set_time_limit(180);
		
		
		$deftables=$this->get_prolawyer_tables();
// 		$this->tab_affiche($deftables);
		
		$cherche=($type) ? $type:$nom;
		if(in_array($cherche, $deftables["system"]))
		{
			$mode="system";
		}
		elseif(in_array($cherche, $deftables["partner"]))
		{
			$mode="partner";
		}
		else $cherche .= ": erreur";
// 		echo "<br>table de nom '$nom' et de type '$type' en mode '$mode' ('$cherche' recherché):<br>";

		if(!$type) $type=$nom;
		//vérification de l'existence de la table
		$table_checked=FALSE;
		$check_query="show tables from {$_SESSION["dbName"]}";
//  		echo "<br>$check_query<br> ". $this->con1;
		$do_check=mysqli_query($this->mysqli, $check_query) or die("Erreur: " .mysqli_error($this->mysqli));
		while ($row=mysqli_fetch_array($do_check))
		{
			if($row["Tables_in_{$_SESSION["dbName"]}"]==trim($nom))
			{
//   				echo "<br>la table $nom est déjà dans le système";
				$table_checked=TRUE;
 				$tc="{$cherche}_{$mode}";
//				echo "<br>cherche: ;
				
				if(!is_array($this->comp)) $this->comp = array();
				if(!array_key_exists($tc, $this->comp))
				{
					//echo "<br>Creation de tempzz234{$tc}";
					$this->create_prolawyer_table("tempzz234{$tc}", "$type");
					$this->comp["$tc"]=$this->describe_table("tempzz234{$tc}");
					$dropad=mysqli_query($this->mysqli, "drop table tempzz234{$tc}");
				}
				
// 				print_r($this->describe_table(trim($nom)));
				if ($this->describe_table(trim($nom)) == $this->comp[$tc]) return 3; //table déjà à jour
				else $action = "update";
// 				echo "<br>La table n'est pas à jour. On s'en occupe";
// 				die("non");
			}
		}
		
// 		global $message_ajour;
// 		global $temp_ajour;
		$modele = "{$this->settings["root"]}config/tables/def_{$type}_{$mode}.sql";
		$query = file_get_contents($modele);
//		echo "<br>Modèle pour $nom de type $type: '$modele'";


		if($mode == "partner") $query=preg_replace("#to_preg#", $nom, $query);
		elseif($type != $nom)
		{
			$query=preg_replace("#CREATE TABLE '$type'#", "CREATE TABLE '$nom'", $query);
			$query=preg_replace("#CREATE TABLE `$type`#", "CREATE TABLE `$nom`", $query);
		}
		
		if($action == "create")
		{
			$return = (mysqli_query($this->mysqli, $query) or die("$query" .mysqli_error($this->mysqli)))? 1:0;
			return $return; //return vaut 1 ou 0
		}
		if($action == "update") // pas de mise à jour si la table n'existe pas ou qu'elle est déjà à jour
		{
			$firstUpdated = True;
			$return=2;
			$query_temp_array=explode("\n", $query);
			$colarray=array();
			$compteur=0;
			foreach($query_temp_array as $line)
			{
				if(!preg_match("#CREATE#", $line) AND !preg_match("#KEY#", $line) AND !preg_match("#ENGINE#", $line)) 
				{
					$line=trim($line); //oter les blancs
					$line=substr($line, 1, 200); //oter le premier guillemet simple
					list($column, $column_val) = preg_split("#`#", $line);
					$column=trim($column);
					$column_val=trim($column_val);
					if(substr($column_val, -1, 1) == ",") $column_val = substr($column_val, 0, -1);
					$colarray[$column]["valeur"]=$column_val;
					$colarray[$column]["existe"]=FALSE;
					$compteur++;
				}
			}
			$my_query_texte="show columns from $nom";
			$my_query=mysqli_query($this->mysqli, $my_query_texte);
			while($row=mysqli_fetch_array($my_query, MYSQLI_ASSOC))
			{
				$test=$row["Field"];
				$fComp = "{$row["Type"]}";
				if($row["Null"] == "NO") $fComp .= " NOT NULL";
				if($row["Null"] == "YES") $fComp .= " NULL";
				if($row["Extra"] != "auto_increment") $fComp .= " default '{$row["Default"]}'";
				if($row["Extra"]) $fComp .= " {$row["Extra"]}";
				if(isset($colarray["$test"]))
				{
					$colarray["$test"]["existe"]=TRUE;
					$colarray["$test"]["comp"] = $fComp;
				}
			}
			$last_column="";
			if($_SESSION["ajour"]==FALSE)
			{
				if(!$quiet)
				{
					echo "<br>{$this->lang["config_modify_update"]}&nbsp;: $nom ...";
					flush();
					ob_flush();
				}
				foreach($colarray as $column => $type)
				if($column)
				{
					{
						if($colarray[$column]["existe"]==FALSE) 
						{
							if($firstUpdated == True) echo "<br>{$this->lang["config_modify_update"]}: ";
							else echo ", ";
							echo $column;
							flush();
							ob_flush();
							if($last_column) $after = "after $last_column";
							else $after = "FIRST";
							$firstUpdated = False;
							$mysqli_query_texte="alter table $nom ADD $column {$colarray["$column"]["valeur"]} $after";
// 							$message_ajour .= "\n<br><font color=\"00FF00\" size=1>{$this->lang["config_modify_update"]}</font>: <font size=1><i>$mysqli_query_texte</i></font>";
// 							echo "<br>Création de la colonne manquante: $mysqli_query_texte";
							$test=mysqli_query($this->mysqli, "$mysqli_query_texte") or die("<br>$mysqli_query_texte: ". mysqli_error($this->mysqli));
							if(!$test) $return=false;
						}
						elseif($colarray["$column"]["valeur"] != $colarray["$column"]["comp"])
						{
							if($firstUpdated == True) echo "<br>{$this->lang["config_modify_update"]}: ";
							else echo ", ";
							echo "$column ({$colarray["$column"]["comp"]} => {$colarray["$column"]["valeur"]})";
							flush();
							ob_flush();
// 							echo "\n<br>{$colarray["$column"]["comp"]} =>";
// 							echo "\n<br>{$colarray["$column"]["valeur"]}";
							$firstUpdated = False;
							$mysqli_query_texte="alter table $nom CHANGE $column $column {$colarray["$column"]["valeur"]}";
// 							$this->debugNow("Appel à $mysqli_query_texte");
// 							$message_ajour .= "\n<br><font color=\"00FF00\" size=1>{$this->lang["config_modify_update"]}</font>: <font size=1><i>$mysqli_query_texte</i></font>";
							$test=mysqli_query($this->mysqli, "$mysqli_query_texte");
// 							$this->debugNow("Appel à $mysqli_query_texte exécuté");
//  							echo "<br>Mise à jour: $mysqli_query_texte";
// 							echo "<br>Le test vaut $test";
							if(!$test) 
							{
								$return=false;
								if (! is_array($this->tablesQueryErrors)) $this->tablesQueryErrors = array();
								$this->tablesQueryErrors["$nom"] = $mysqli_query_texte;
							}
						}
						$last_column=$column;
					}
				}
			}
// 		if(!function_exists(sys_get_temp_dir))
// 		{
// 			function sys_get_temp_dir()
// 			{
// 				foreach (array("/tmp", "/var/tmp", "C:\\Windows\\Temp", "D:\\Windows\\Temp") as $testDir) if(is_dir($testDir)) return $testDir;
// 			}
// 		}
// 		$temp_ajour=tempnam(sys_get_temp_dir(), 'majProlawyer');
		//$open=fopen($temp_ajour, "w+");
// 		$config_modify_update_question=html_entity_decode($this->lang["config_config_modify_update_question"]);
// 		$texte_write="<html>\n<head><title>{$this->lang["config_modify_update"]}</title><script language=Javascript>\nfunction quitenreg(){\nconf=confirm(\"$config_modify_update_question?\");\nif(conf==true){\nself.close();\n}\n}\n</script></head>\n<body onclick=\"quitenreg()\">\n".$message_ajour."\n</body>\n</html>";
		//$write=fwrite($open, $texte_write);
		
			return $return;
		}
	}
	
	
	
	/*Fonctions d'affichage et de gestion des clients*/
	//fonction d'affichage des noms
	function affiche_personne($type_personne, $nodossier, $no_de_personne="0", $texte_seul="", $type_affiche="0")
	{
		//Création des données de toutes les personnes
		if(!isset($this->donneesDuDossier))
		{
			$this->donneesDuDossier = array();
			$this->donnee_premier = array();
			$this->als = array();
			$listeChampsVar = array("id", "type", "salut", "titre", "prenom", "nom", "fonction", "adresse", "cp", "zip", "ville", "canton", "pays", "ccp", "tel", "telprive", "fax", "faxprive", "natel", "natelprive", "mail", "mailprive", "nosociete", "typesociete", "rem", "np", "nple", "mp", "mple");
// 			$listeChampsFixes = array("date_format(c.dateouverture, \"%d\") as date_jour_ouverture, date_format(c.dateouverture, \"%c\") as date_mois_ouverture, date_format(c.dateouverture, \"%Y\") as date_annee_ouverture, date_format(c.datearchivage, \"%d\") as date_jour_archive, date_format(c.datearchivage, \"%c\") as date_mois_archive, date_format(c.datearchivage, \"%Y\") as date_annee_archive");
			$q = "";
			$leftJn = "";
			foreach(array("client" => "noadresse", "contact" => "nocontact", "pa" => "nopa", "pj" => "nopj", "ca" => "noca", "aut" => "noaut") as $type => $clId)
			{
				for($x = ""; $x < 5; $x++)
				{
					$prType = "pa";
					$adName = "a{$type}$x";
					$prName = "$type{$x}";
					$prclId = "$clId{$x}";
					foreach($listeChampsVar as $champ)
					{
						
						if($q) $q .= ", ";
						$q .= "$adName.$champ as $champ{$prName}";
						if($type == "aut") $q .= ", c.noautref{$x} as noautrefaut{$x}, c.noautaj{$x} as noautajaut{$x}"; //noautref et noautaj sont des données de xxclients et non de adresses; il faut donc forcer un nom qui les mettra dans les données de l'autorité.
					}
					$leftJn .= " LEFT JOIN adresses $adName on c.$prclId = $adName.id";
				}
			}
			if($type_personne == "identite")
			{
				$q = "";
				foreach($listeChampsVar as $champ)
				{
					$add = ($nodossier == "forceCreate") ? "''" : "$champ";
					if($q) $q .= ", ";
					$q .= "$add as {$champ}identite";
				}
				$from = ($nodossier == "forceCreate") ? "" : "from adresses WHERE id = '$nodossier'";
				$q = "SELECT $q $from";
			}else{
				$q = "SELECT $q from {$_SESSION["session_avdb"]} c $leftJn WHERE nodossier = '$nodossier'";
			}
			$e = mysqli_query($this->mysqli, $q);
			while($r = mysqli_fetch_array($e, MYSQLI_ASSOC)) foreach($r as $n => $v)
			{
				preg_match("#(.*)((identite|client|contact|pa|pj|ca|aut)[1-4]?)#", $n, $r);
				$aPers = $r[2];
// 				echo "<br>$n => $v ($aPers - {$r[1]} - {$r[2]})";
				if(!is_array($this->donneesDuDossier[$aPers])) $this->donneesDuDossier[$aPers] = array();
				if(trim($v) != "" || $nodossier == "forceCreate")
				{
					$v = preg_replace('#"#', "&quot;", $v);
					$this->donneesDuDossier[$aPers]["{$r[1]}"] = $v;
// 					$this->donneesDuDossier[$aPers]["v{$r[1]}"] = html_entity_decode($v, ENT_COMPAT, "UTF-8");
				}
// 				$this->donneesDuDossier[$n] = $v;
			}
			foreach($this->donneesDuDossier as $p => $arrP)
			{
				$this->als[$p] = False;
				if(! $this->donneesDuDossier[$p]["nom"] && ! $this->donneesDuDossier[$p]["prenom"])
				{
					$this->donneesDuDossier[$p]["affNom"] = "<i>({$this->lang["data_client_no_data"]})</i>";
					$this->donneesDuDossier[$p]["vide"] = True;
				}
				else
				{
					$this->donneesDuDossier[$p]["affNom"] = preg_replace("#^[[:space:]]+#", "", "{$this->donneesDuDossier[$p]["prenom"]} <b>{$this->donneesDuDossier[$p]["nom"]}</b>");
					$this->donneesDuDossier[$p]["vide"] = False;
				}
				if(preg_match ("#^(client|pa)$#", $p))
				{
					//die(html_entity_decode("&Eacute;b&eacute;nisterie-Schmied Sarl", ENT_COMPAT|ENT_HTML401, "ISO8859-15"));
					//if($type_personne == "pa") die($row["nom"]);
					$this->donnee_premier["$p"] = trim (strtoupper($this->no_accent(html_entity_decode($this->donneesDuDossier[$p]["nom"], ENT_COMPAT|ENT_HTML401, "ISO8859-15"))));
					if($this->donneesDuDossier[$p]["prenom"]) $this->donnee_premier["$p"] .= " ".trim($this->no_accent(html_entity_decode($this->donneesDuDossier[$p]["prenom"], ENT_COMPAT|ENT_HTML401, "ISO8859-15")));
				}
				elseif(preg_match ("#(client|pa)[1-4]#", $p, $r))
				{
					$f = $r[1];
					if($this->als["$f"] == False)
					if($this->donneesDuDossier[$p]["vide"] == False)
					{
						$this->donnee_premier["$f"] .= " {$this->lang["general_al"]}";
						$this->als[$f] = True;
					}
				}
			}
// 			$this->tab_affiche($this->donneesDuDossier);
// 			echo $this->beautifyMysql($q, "", True);
			
		}
// 		$this->tab_affiche($this->donneesDuDossier);
		if($type_personne === False) return;
		
		//lecture des mailings auxquels la personne est rattachée
		
	
	
		if(!isset($this->affichage_total)) $this->affichage_total="";
		if($no_de_personne==0) $no_de_personne="";
		$suffixe = $no_de_personne;
		
		$column = ($type_personne=="client") ? "noclient":"no{$type_personne}";
		$column .= $no_de_personne;
		$modifier_donnees_modifier_personne=$this->lang["modifier_donnees_modifier_{$type_personne}"];
		$modifier_donnees_changer_personne=$this->lang["modifier_donnees_changer_{$type_personne}"];
		$modifier_donnees_personne=$this->lang["modifier_donnees_{$type_personne}"];
		
		$no_de_personne++;
		
		$row = $this->donneesDuDossier["{$type_personne}{$suffixe}"];
		
// 		$vide=True;
// 		if(mysqli_num_rows($resultat_recherche)!=0){ //SUPPRIME POUR CALCUL EN UNE FOIS
		foreach($row as $nom_val=>$val){ #TODO: déplacé de ce qui suit pour que ce soit inconditionnel, mais pas certain
// 				if($nom_val != "affNom") $vide = False;
//			$$nom_val=preg_replace("#'#", "`", "$val");
// 				$row["$nom_val"]=preg_replace("#'#", "`", $row["$nom_val"]);
			$v = $this->getClickableItem($nom_val, $val, $row["id"]);
			$class["$nom_val"] = $v["class"];
			$sup["$nom_val"] = $v["sup"]."&nbsp;";
			$$nom_val=$v["val"];
//			echo "<br>", $row["$nom_val"];
			$dataId = $row["id"];
			$nomRC = rawurlencode($nom);
			$nomTS = rawurlencode("$prenom $nom");
		}
		if($row["vide"] == False || $type_personne == "identite")
		{
// 			foreach($row as $nom_val=>$val){
// // 				if($nom_val != "affNom") $vide = False;
// 	//			$$nom_val=preg_replace("#'#", "`", "$val");
// // 				$row["$nom_val"]=preg_replace("#'#", "`", $row["$nom_val"]);
// 				$v = $this->getClickableItem($nom_val, $val, $row["id"]);
// 				$class["$nom_val"] = $v["class"];
// 				$sup["$nom_val"] = $v["sup"]."&nbsp;";
// 				$$nom_val=$v["val"];
// 	//			echo "<br>", $row["$nom_val"];
// 				$dataId = $row["id"];
// 				$nomRC = rawurlencode($nom);
// 				$nomTS = rawurlencode("$prenom $nom");
// 			}
			if($no_de_personne == 1)
			{
				//die(html_entity_decode("&Eacute;b&eacute;nisterie-Schmied Sarl", ENT_COMPAT|ENT_HTML401, "ISO8859-15"));
				//if($type_personne == "pa") die($row["nom"]);
				$this->donnee_premier["$type_personne"] = trim (strtoupper($this->no_accent(html_entity_decode($row["nom"], ENT_COMPAT|ENT_HTML401, "ISO8859-15"))));
				if($row["prenom"]) $this->donnee_premier["$type_personne"] .= " ".trim($this->no_accent(html_entity_decode($row["prenom"], ENT_COMPAT|ENT_HTML401, "ISO8859-15")));
			}
			if($no_de_personne == 2)
			{
				$this->donnee_premier["$type_personne"] .= " {$this->lang["general_al"]}";
			}
		}
		else
// 		if($vide == True)
		{
			$modifier_donnees_changer_personne=$this->lang["modifier_donnees_choisir"];
			$req_vide="show columns from adresses";
			$vide=mysqli_query($this->mysqli, "$req_vide");
			while($col=mysqli_fetch_array($vide))
			{
				$valeur=$col["Field"];
				$$valeur="";
			}
		}
		if($zip==0) $zip="";
		$texte="";
// 		if(!$nom && ! $prenom) $affNom = "<i>({$this->lang["data_client_no_data"]})</i>";
// 		else $affNom = preg_replace("#^[[:space:]]+#", "", "$prenom <b>$nom</b>");
		if(!$print)
		{
			$typeOnglet = $type_personne;
			$arrOnglets = array();
			if($type_personne == "contact" || $type_personne == "pj" || $type_personne == "client")
			{
				$typeOnglet = "client";
				$arrOnglets = array("client", "contact", "pj");
			}
			if($type_personne == "ca" || $type_personne == "pa")
			{
				$typeOnglet = "pa";
				$arrOnglets = array("pa", "ca");
			}
			if($type_personne == "aut")
			{
				$typeOnglet = "aut";
				$arrOnglets = array("aut");
			}
			
			$typeNum = $type_personne.$no_de_personne;
			$aid="popup_$typeNum";
			if(!$dataId) $dataId = $column;

			$this->searchBoxes = array();
			$this->searchBoxes[] = $this->searchBox("zefix", $nomRC, $dataId, $nodossier);
			$this->searchBoxes[] = $this->searchBox("telsearch", $nomTS, $dataId, $nodossier);
			$this->searchBoxes[] = "<a href='https://www.linkedin.com/search/results/all/?keywords=$nomTS&origin=GLOBAL_SEARCH_HEADER' target=new><img src=../images/linkedin.png style='width:16px;height:16px'></a>";
			$this->searchBoxes[] = "<a href='https://www.google.ch/maps/search/$adresse $zip $ville' target=new><img src=../images/maps.png></a>";
			foreach($this->searchBoxes as $searchBox) $recherches .= ($recherches ? "&nbsp;":"") . $searchBox;
// 			$recherches = "$zefix&nbsp;$telsearch&nbsp;$google&nbsp;$linkedin";

			$form = $this->form("creer_dossier.php", "$modifier_donnees_changer_personne", "", "style=display:inline@form", "", "action", "nouveau_$type_personne{$suffixe}", "nodossier", "$nodossier");
			$texte .= "<table class=$typeOnglet border=0>";
			$texte .= "<tr><td colspan=4>&nbsp;</td></tr>";
			//if(preg_match("#^(client|contact|pj|pa|ca|aut)$#", $type_personne))
			if(in_array($type_personne, $arrOnglets))
			{
				$texte .= "<tr><td colspan=4 width=100%><table width=100%><tr>";
				$size = 100/count($arrOnglets);
				foreach($arrOnglets as $numOnglet => $arrOnglet)
				{
					if($numOnglet == 0) $hAlign = "left";
					elseif($numOnglet == count($arrOnglets) -1) $hAlign = "right";
					else $hAlign = "center";
					$addForm = $this->anchor != "adresses_modifier" ? $form : "";
					$formRec = $this->donneesDuDossier["{$arrOnglet}{$suffixe}"]["vide"] == False ? $recherches : "";
					$addRech = ($type_personne == $arrOnglet)? "<br>$addForm $formRec": "<br>&nbsp;";
					$pref = $this->lang["modifier_donnees_{$arrOnglet}"];
					$add = ($type_personne != $arrOnglet) ? ";color:#808080":"";
// 					$add =  ? $add:"$add;display:none";
					if ($typeOnglet == $arrOnglet || $this->donneesDuDossier["{$typeOnglet}{$suffixe}"]["vide"] == False)
					{
						$texte .= "<td style='text-align:$hAlign;vertical-align:top;$add;cursor:pointer' onclick=\"activate('popup_$arrOnglet{$no_de_personne}', 'onglet_$typeOnglet', 'onglet2_$typeOnglet', 'bold_$typeOnglet{$no_de_personne}')\">$pref&nbsp;:&nbsp;{$this->donneesDuDossier["$arrOnglet{$suffixe}"]["affNom"]}$addRech</td>";
					}
				}
				
				$texte .= "</tr></table></td></tr>";
			}
			$texte .= "<tr><td colspan=4>";
			if($this->anchor == "adresses_modifier")
			{
// 				$texte .="<td><b><a href=\"{$this->settings["root"]}adresses/modifier.php?id=$id\" target=\"_new\">$modifier_donnees_personne ($no_de_personne)</a></b></td>";
				$texte .= "$recherches";
			}
			$texte .= "</td>";
// 			if($this->anchor != "adresses_modifier") $texte .= "<td style=\"cursor:pointer\" onClick=\"document.getElementById('$aid').className='popupguy'\">[X]</td></tr>";
// 			$texte .= "</table>";
			if($row["vide"] == False || $nodossier == "forceCreate" || $type_personne == "identite" || True)  #TODO: ne comprends plus la condition
			{
				//Traitement de l'adresse complete

				$completeAddr = "$titre\n".
				"$prenom $nom\n".
				"$fonction\n".
				"$adresse\n".
				"$cp\n".
				"$zip $ville\n";
				
				$completeAddr = preg_replace("#\n+#", "\n", $completeAddr);
				$completeAddr = preg_replace("#^[[:space:]]*#", "", $completeAddr);
				$completeAddr = preg_replace("#\n[[:space:]]*#", "\n", $completeAddr);
				$completeAddr = preg_replace("# +#", " ", $completeAddr);
				
				$nomVcard = preg_replace("#[[:space:]]+#", "_", "$prenom $nom");
				$nomVcard = preg_replace("#^_#", "", $nomVcard);
				$nomVcard = html_entity_decode($nomVcard);
				
				$vcard = $this->getVcard($version, $titre, $nom, $prenom, $fonction, $adresse, $cp, $zip, $ville, $canton, $pays, $tel, $fax, $natel, $mail, $telprive, $faxprive, $natelprive, $mailprive);
				$uVcard = urlencode($vcard);
				
				$this->donneesDuDossier["{$type_personne}{$suffixe}"]["vcard"] = $vcard;
				$this->donneesDuDossier["{$type_personne}{$suffixe}"]["nomVcard"] = $nomVcard;
				$this->donneesDuDossier["{$type_personne}{$suffixe}"]["completeAddr"] = $completeAddr;
			
				$tsociete=$this->simple_selecteur($this->societes, $typesociete, 2);
				$ttype=$this->simple_selecteur(explode("\n", $_SESSION["optionGen"]["ltype"]), $type, 2);
				$texte .= "<form action=\"{$this->settings["root"]}maj_op.php\"method=post>";
				$texte .= "<input type=\"hidden\" name=\"nodossier\" value=\"$nodossier\">";
				$texte .= "<input type=\"hidden\" name=\"id\" value=\"$id\">";
				if($this->anchor == "adresses_modifier")
				{
					$actionRetour = "adresses/modifier";
// 					$texte .= "<input type=\"hidden\" name=\"retour\" value=\"adresses/modifier\">";
				}else{
					$actionRetour = "modifier_donnees";
// 					$texte .= "<input type=\"hidden\" name=\"retour\" value=\"modifier_donnees\">";
				}
				$actionSubretour = $type_personne.$no_de_personne;
// 					$texte .= "<input type=\"hidden\" name=\"retour\" value=\"modifier_donnees\">";
				$texte .= $this->input_hidden("retour", "", $actionRetour);
				$texte .= $this->input_hidden("subretour", "", $actionSubretour);
				if($nodossier == "forceCreate")
				{
					$texte .= "<input type=\"hidden\" name=\"action\" value=\"creer_fiche\">";
				}else{
					$texte .= "<input type=\"hidden\" name=\"action\" value=\"modifier_fiche\">";
				}
// 				$texte .= "<table class=$type_personne border=0>";
				
				$this->pdaNoPrefix = True;
// 				echo "'''$titre'''$nom'''|";
				//$texte .= "<tr><td colspan=2><a href=# onMouseOver=hide()>[X]</a></td><td colspan=2 align=right><a href=# onMouseOver=hide()>[X]</a></td></tr>";
// 				$texte .= "<tr>".$this->input_texte("titre<td>", "", $titre, 15, "", "", "", "{$this->lang["adresses_modifier_type"]}<td>", "&nbsp;:")."</tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["adresses_modifier_type"]} :</td>")."<td><select name=type placeholder='{$this->lang["adresses_modifier_type"]}' id=type>$ttype</select></td>".($this->pdaSet ? "":"<td>{$this->lang["adresses_modifier_salut"]} :</td>")."<td><input type=text size=15 name=salut placeholder='{$this->lang["adresses_modifier_salut"]}' id=salut{$typeNum} ordretab=1 value=\"$salut\"></td></tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_titre"]} :</td>")."<td><input type=text size=15 name=titre placeholder='{$this->lang["adresses_modifier_titre"]}' value=\"$titre\"></td>".($this->pdaSet ? "":"<td{$class["nosociete"]}>{$sup["nosociete"]}{$this->lang["modifier_donnees_nosociete"]} :</td>")."<td><input type=text size=15 name=nosociete placeholder='{$this->lang["modifier_donnees_nosociete"]}' id=nosociete{$typeNum} value=\"$nosociete\"></tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_prenom"]} :</td>")."<td><input type=text size=15 name=prenom placeholder='{$this->lang["adresses_modifier_prenom"]}' value=\"$prenom\"></td>".($this->pdaSet ? "":"<td><b>{$this->lang["modifier_donnees_nom"]} :</b></td>")."<td><input type=text size=15 placeholder='nom' required='required' pattern='.{1,255}' name=nom placeholder='{$this->lang["adresses_modifier_nom"]}' id=nom{$typeNum} ordretab=1 value=\"$nom\"></b></td></tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_fonction"]} :</td>")."<td><input type=text size=15 name=fonction value=\"$fonction\" placeholder='{$this->lang["adresses_modifier_fonction"]}'></td>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_type_societe"]} :</td>")."<td><select name=typesociete placeholder='{$this->lang["modifier_donnees_type_societe"]}' id=typesociete{$typeNum}>$tsociete</select></td></tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_adresse"]} :</td>")."<td><input type=text size=15 name=adresse placeholder='{$this->lang["modifier_donnees_adresse"]}' id=adresse{$typeNum} value=\"$adresse\"></td>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_cp"]} :</td>")."<td><input type=text size=15 name=cp placeholder='{$this->lang["modifier_donnees_cp"]}' value=\"$cp\"></td></tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_zip"]} :</td>")."<td><input type=text size=15 name=zip placeholder='{$this->lang["modifier_donnees_zip"]}' id=zip{$typeNum} value=\"$zip\" onKeyUp=\"changeCity(this.value, 'ville{$typeNum}')\"></td>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_ville"]} :</td>")."<td><input type=text size=15 name=ville placeholder='{$this->lang["modifier_donnees_ville"]}' id=ville{$typeNum} value=\"$ville\"></td></tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_canton"]} :</td>")."<td><input type=text size=15 name=canton placeholder='{$this->lang["modifier_donnees_canton"]}' id=canton{$typeNum} value=\"$canton\"></td>".($this->pdaSet ? "":"<td>{$this->lang["modifier_donnees_pays"]} :</td>")."<td><input type=text size=15 name=pays placeholder='{$this->lang["modifier_donnees_pays"]}' value=\"$pays\"></td></tr>";
				$texte .= "<tr>".($this->pdaSet ? "":"<td>{$this->lang["adresses_modifier_ccp"]} :</td>")."<td><input type=text size=15 name=ccp placeholder='{$this->lang["adresses_modifier_ccp"]}' id=value=\"$ccp\"></td></tr>";
				$texte .= "<tr><td colspan=\"2\"><b>{$this->lang["adresses_modifier_prof"]}:</b></td></tr>";
				$texte .= "<tr><td {$class["tel"]}>{$sup["tel"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_tel"]} :</td><td>")."<input type=tel size=15 name=tel placeholder='{$this->lang["modifier_donnees_tel"]}' value=\"$tel\"></td><td {$class["fax"]}>{$sup["fax"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_fax"]} :</td><td>")."<input type=tel size=15 name=fax placeholder='{$this->lang["modifier_donnees_fax"]}' value=\"$fax\"></td></tr>";
				$texte .= "<tr><td {$class["natel"]}>{$sup["natel"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_natel"]} :</td><td>")."<input type=tel size=15 name=natel placeholder='{$this->lang["modifier_donnees_natel"]}' value=\"$natel\"></td><td {$class["mail"]}>{$sup["mail"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_mail"]} :</td><td>")."<input type=email size=15 name=mail placeholder='{$this->lang["modifier_donnees_mail"]}' value=\"$mail\"></td></tr>";
				$texte .= "<tr><td colspan=\"2\"><b>{$this->lang["adresses_modifier_prive"]}:</b></td></tr>";
				$texte .= "<tr><td {$class["telprive"]}>{$sup["telprive"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_tel"]} :</td><td>")."<input type=tel size=15 name=telprive placeholder='{$this->lang["modifier_donnees_tel"]}' value=\"$telprive\"></td><td {$class["faxprive"]}>{$sup["faxprive"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_fax"]} :</td><td>")."<input type=tel size=15 name=faxprive placeholder='{$this->lang["modifier_donnees_fax"]}' value=\"$faxprive\"></td></tr>";
				$texte .= "<tr><td {$class["natelprive"]}>{$sup["natelprive"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_natel"]} :</td><td>")."<input type=tel placeholder='{$this->lang["modifier_donnees_natel"]}' size=15 name=natelprive value=\"$natelprive\"></td><td {$class["mailprive"]}>{$sup["mailprive"]}".($this->pdaSet ? "":"&nbsp;{$this->lang["modifier_donnees_mail"]} :</td><td>")."<input type=email size=15 name=mailprive placeholder='{$this->lang["modifier_donnees_mail"]}' value=\"$mailprive\"></td></tr>";
				//Il faut vérifier si des mailings sont joints. Cela ne vaut que pour les id settées; donc si dataId n'est pas numérique, on saute le test (on se trouve dans le dossier, et c'est une partie non utilisée).
				if(is_numeric($dataId))
				{
					$mailArray = array();
					$q = "select mailingname from mailing where adresseid = {$dataId}";
					$e = mysqli_query($this->mysqli, $q);
					while($rm = mysqli_fetch_array($e))
					{
						$mailArray[] = $rm["mailingname"];
					}
// 					$this->tab_affiche($mailArray);
// 					echo("'".trim($_SESSION["optionGen"]["mailing"])."'");
					$mailings = trim($_SESSION["optionGen"]["mailing"]);
					$mailing = "";
					$mailingArray = explode("\n", trim($_SESSION["optionGen"]["mailing"]));
					$mailingNum = count($mailingArray) + 1; //A cause de la ligne vide à la fin
					$mailingOptions = $this->simple_selecteur($mailingArray, $mailArray);
					$mailingSelect = "<select name=mailingname[] multiple size=$mailingNum>$mailingOptions</select>";
// 					$mailingOptions = $this->simple_selecteur(explode("\n", trim($_SESSION["optionGen"]["mailing"])), $mailArray);
// 					$mailingSelect = "<select name=mailingname[] multiple>$mailingOptions</select>";
					$mailingSelect .= "<br>" . $this->button($this->lang["modifier_donnees_modifier_mailing"], "aut");
					$mailingSelect .= $this->input_hidden("action", "", "modifier_mailing");
					$mailingSelect .= $this->input_hidden("retour", "", "$actionRetour");
					$mailingSelect .= $this->input_hidden("subretour", "", "$actionSubretour");
					$mailingSelect .= $this->input_hidden("id", "", "$dataId");
					$mailingSelect .= $this->input_hidden("nodossier", True);
					$this->mailingbox = "<h3>{$this->lang["modifier_donnees_mailing"]}</h3><form action=\"{$this->settings["root"]}maj_op.php\"method=post>$mailingSelect</form>";
				}
				else
				{
					$this->mailingbox = "";
				}
				if($type_personne == "identite" || 1)
				{
					$remStr = "<span id=remstr class='textbl'>";
					$remNum = 0;
					$remArr = preg_split('#\n#', $rem);
					foreach ($remArr as $line)
					{
						$click = $this->getClickableItem("other$remNum", $line);
						if ($click["val"]) $remStr .= "<span {$click["class"]}>{$click["sup"]}</span>";
						else $remStr .= "&nbsp;";
						$remStr .= "\n<br>";
						$remNum ++;
					}
					if ($remNum < 8) $remNum = 8;
					$remStr .= "</span>";
					$texte .= "<tr><td colspan=\"2\">&nbsp;</td></tr>
					<tr>".($this->pdaSet ? "":"<td>{$this->lang["adresses_modifier_remarques"]}&nbsp;: </td>")."<td colspan=3><table border=0 width=100%><tr><td width=20px style='vertical-align:top'>$remStr</td><td><textarea class='textbl' style=width:100% rows=$remNum name=\"rem\" placeholder='{$this->lang["adresses_modifier_remarques"]}'>$rem</textarea></td></tr></table></td></tr>";
				}
				$texte .= "<tr><td colspan=2><button type=submit>$modifier_donnees_modifier_personne</button></td></tr></table>";
				$texte .= "</form>\n{$this->mailingbox}";
				if($type_personne == "aut")
				{
					$refautname = "noautref$suffixe";
					$refajname  = "noautaj$suffixe";
					$refautval  = $noautref;
					$refajval   = $noautaj	;

					$texte .= "<form action='{$this->settings["root"]}maj_op.php' method='POST' class='aut' style='border-top-style:solid;border-top-width:1px;padding:1px'>{$this->lang["modifier_donnees_no_tribunal"]}&nbsp;:&nbsp;<input type=text size=15 name='$refautname' value='$refautval'><br>{$this->lang["modifier_donnees_no_aj"]}&nbsp;:&nbsp;<input type=text size=15 name='$refajname' value='$refajval'>";
					$texte .= "<br>" . $this->button($this->lang["modifier_donnees_modifier_ref"], "aut");
					$texte .= $this->input_hidden("action", "", "modifier_dossier");
					$texte .= $this->input_hidden("retour", "", "modifier_donnees");
					$texte .= $this->input_hidden("nodossier", True);
					$texte .= "</form>";
				}
			}
			elseif($this->anchor != "adresses_modifier")  $texte .= "</table>";
		}
		if(!$this->print)
		{
			$typeOnglet = $type_personne;
			if($type_personne == "contact" || $type_personne == "pj") $typeOnglet = "client";
			if($type_personne == "ca") $typeOnglet = "pa";
			$doOnclick = "activate('popup_$typeNum', 'onglet_$typeOnglet', 'onglet2_$typeOnglet', 'bold_$typeOnglet{$no_de_personne}')";
			$class=(False && $aid == "popup_client1")? "popupguyshow":"popupguy";
			if($aid == "popup_identite1") $class = "";
			$this->affichage_total .= "\n<div class=\"$class\" id=\"$aid\">{$texte}</div>";
			$texte="<span onMouseOver=\"this.style.cursor='pointer';this.style.color='#a0a0a0'\" onMouseOut=\"this.style.color='#000000'\" onClick=\"$doOnclick\">{$row["affNom"]}</span>";
		}else {
			if($cp) $affiche_cp = "$modifier_donnees_cp $cp";
			else $affiche_cp="";
			$texte = ($row["vide"] == False ||($type_personne != "client" && $type_personne != "pa" && $type_personne != "aut")) ? $row["affNom"]:"";
			$texte .= " $fonction $adresse $affiche_cp $zip <b>$ville</b>";
			foreach (array("", "prive") as $cat)
			{
				$catName = $cat ? $cat: "prof";
				$catAff = $this->lang["adresses_modifier_$catName"];
				$add = "";
				foreach(array("tel", "fax", "natel", "mail") as $dat)
				{
					$vName = "modifier_donnees_" . $dat;
					$dat .= $cat;
					$avName = $this->lang["$vName"];
					$valName = $$dat;
					if($valName)
					{
						if(!$add) $add = "</td></tr>\n<tr><td>&nbsp;</td><td><b>$catAff</b>. ";
						$add .= "$avName: $valName. ";
					}
				}
	// 			$afTel = $tel ? "{$this->lang["modifier_donnees_tel"]}: $tel. ": "";
	// 			$afFax = $fax ? "{$this->lang["modifier_donnees_faxl"]}: $fax. ": "";
				$texte .= $add;
			}
// 			$texte .= "$fonction $adresse $affiche_cp $zip <b>$ville</b>. <br>{$this->lang["modifier_donnees_tel"]}: $tel. {$this->lang["modifier_donnees_fax"]}: $fax. {$this->lang["modifier_donnees_natel"]}: $natel. {$this->lang["modifier_donnees_mail"]}: $mail.";
// 			if($cp) $affiche_cp = "$modifier_donnees_cp $cp";
// 			else $affiche_cp="";
// 			$texte="<tr><td>$no_de_personne: $prenom <b>$nom</b> $fonction $adresse $affiche_cp $zip <b>$ville</b>. <br>{$this->lang["modifier_donnees_tel"]}: $tel. {$this->lang["modifier_donnees_fax"]}: $fax. {$this->lang["modifier_donnees_natel"]}: $natel. {$this->lang["modifier_donnees_mail"]}: $mail.";
// 			if($type_personne=="pa") $texte .= " {$this->lang["modifier_donnees_conseil"]}: $donnees_ca";
// 			$texte .= "</td></tr>";
// 			if($vide) $texte="<tr><td>$no_de_personne:</td></tr>";
		}
		if($texte_seul)
		{
			if($cp) $affiche_cp = "{$this->lang["modifier_donnees_cp"]} $cp";
			else $affiche_cp="";
			if($type_affiche=="1") $texte="$no_de_personne: $prenom $nom $fonction $adresse $affiche_cp $zip $ville.";
			if($type_affiche=="2") $texte="{$this->lang["modifier_donnees_tel"]}: $tel. {$this->lang["modifier_donnees_fax"]}: $fax. {$this->lang["modifier_donnees_natel"]}: $natel. {$this->lang["modifier_donnees_mail"]}: $mail.";
			if($type_affiche=="3") {
			if($donnees_ca != "") $texte="{$this->lang["modifier_donnees_conseil"]}: $donnees_ca";
			else $texte="";
			}
		}
		return $texte;
	}


	function cherche_conflits()
	{
		$this->avocatsArchives = array();
		$newReq = "";
		$newWhr = "";
		$leftJn = "";
		$base   = "";
		$arrbis=explode(" ", $_POST["vnom"]);
		$q="select nom, initiales, archive from utilisateurs where nom not like 'tableDefaultValue' and seul = 0";
		$ex=mysqli_query($this->mysqli, $q);
		$priSel = "nomav, baseav";

		while($row = mysqli_fetch_array($ex))
		{
			if($row["archive"]) $this->avocatsArchives[] = $nom;
			foreach(array("client" => "noadresse", "contact" => "nocontact", "pa" => "nopa") as $type => $clId)
			{
				$champs = "nodossier, '{$row["initiales"]}' as baseav, '{$row["nom"]}' as nomav, '{$row["archive"]}' as archive";
				if($base) $base .= " UNION ALL ";
				$base .= "SELECT ";
				for($x=""; $x < 5; $x ++)
				{
					$champs .= ", noadresse{$x}, nocontact{$x}, nopa{$x}";
					if(! $testLoop)
					{
						$leftJn .= " LEFT JOIN adresses a{$type}{$x} on a.{$clId}{$x}= a{$type}{$x}.id";
					}
				}
				$base .= "$champs FROM {$row["initiales"]}clients";

				//construction de la requête
				if($_POST["vnom"])
				{
					$rType = "";
					for($x = "";$x <5;$x++)
					{
						$rNum = "";
						$oNum = "";
						$parts = explode(" ", $_POST["vnom"]);
						foreach($parts as $i => $part)
						{
							$part = trim($part);
							if($part)
							{
								if($rNum) $rNum .= " AND ";
								if($oNum) $oNum .= " AND ";
								$rPart = "";
								$oPart = "";
								foreach(array("prenom", "nom", "fonction", "adresse", "zip", "ville", "id") as $champ)
								{
									if($rPart) $rPart .= " OR ";
									$protectPart = addslashes(stripslashes($part));
									$rPart .= "a{$type}{$x}.$champ like '%$protectPart%'";
									if($oPart) $oPart .= " OR ";
									$oPart .= "$champ like '%$protectPart%'";
								}
								$rNum .= "($rPart)";
								$oNum .= "($oPart)";
							}
						}
						if(! $testLoop) $condAdresse = $oNum;
						if($rType) $rType .= " OR ";
						$rType .= "($rNum)";
						if(!$testLoop)
						{
							$priSel .= ", if($rNum, a{$type}{$x}.id, 0) as id{$type}{$x}";
							foreach(array("titre", "prenom", "nom", "fonction", "adresse", "zip", "ville", "id") as $champ) $priSel .= ", if($rNum, a{$type}{$x}.$champ, 0) as $champ{$type}{$x}";
						}
					}
					$condit["$type"] = "$rType";
					$requis[] = "$type";
				}
				$cont ++;
			// 	if($cont == 2) break;
			}
			$testLoop = True;
		}

		$where = "";
		if(is_array($condit))foreach($condit as $nom => $val)
		{
			if($val)
			{
				if($where) $where .= " OR "; //Attention: contrairement à ce qui se passe pour les recherches, on ne doit pas cumuler la pa et le client !
				$where .= "($val)";
			}
			if(!in_array($nom, $requis))
			{
				if($newReq) $newReq .= ", ";
				$newReq .= $nom;
			}
		}
		$couleurs = $this->couleurs;
		foreach($couleurs as $type => $couleur) $liste[$type] = array();
		
		//D'abord, recherche dans les clients et les pas
		if($where) $where = "where $where";
		$newReq = "SELECT $priSel, nodossier from ($base) a $leftJn $where order by archive, nomav";
		// echo $this->beautifyMysql($newReq);

		$e = mysqli_query($this->mysqli, $newReq) or die("<br>".mysqli_error($this->mysqli)."\n\n<br><br>".$this->beautifyMysql($newReq));
		while($r = mysqli_fetch_array($e))
		{
// 			$avocat = ucfirst($r["nomav"]);
			$avocat = $r["baseav"];
			for($x=""; $x<5; $x++)
			{
				foreach($couleurs as $type => $couleur)
				{
					if($r["id{$type}{$x}"])
					{
						if(!$r["zip{$type}{$x}"]) $r["zip{$type}{$x}"] = "";//pour éviter l'affichage de numéros postaux vide par "0"
						$found = "$couleur::{$r["nom{$type}{$x}"]}::{$r["prenom{$type}{$x}"]}::{$r["fonction{$type}{$x}"]}::{$r["titre{$type}{$x}"]}::{$r["adresse{$type}{$x}"]}::{$r["zip{$type}{$x}"]}::{$r["ville{$type}{$x}"]}::{$r["id{$type}{$x}"]}";
						if(array_key_exists($found, $liste[$type]))
						{
							if (! array_key_exists($avocat, $liste[$type][$found])) $liste[$type][$found][$avocat] = array();
							$liste[$type][$found][$avocat][$r["nodossier"]] = $r["id{$type}{$x}"];
// 							$liste[$type][$found][$r["nodossier"]] = $r["id{$type}{$x}"];
// // 							if(!preg_match("#(:|,)$avocat \([^)]+\)(,|$)#", $liste[$type][$found])) $liste[$type][$found] .= ",$avocat";
// 							if(!preg_match("#(:|,)$avocat \([^)]+\)(,|$)#", $liste[$type][$found])) $liste[$type][$found] .= ",$avocat";
// //   							if(!preg_match("#(:|,|^)$avocat(,|$)#", $liste[$type][$found])) $liste[$type][$found] .= ",$avocat (toto{$r[nodossier]})";
//   							elseif(!preg_match("#(:|,|^)$avocat \([^)]*{$r["nodossier"]}#", $liste[$type][$found])) $liste[$type][$found] = preg_replace("#$avocat \([^)]+#", "\\0, {$r["nodossier"]}", $liste[$type][$found]);
// //   							else echo "<br>'{$liste[$type][$found]}'";
// // 							$liste[$type][$found] .= ",$avocat ({$r["nodossier"]})";
						}
						else
						{
// 							$liste[$type][$found] = "{$r["nom{$type}{$x}"]}{$r["prenom{$type}{$x}"]}:$avocat ({$r["nodossier"]})";
							$liste[$type][$found][$avocat] = array();
							$liste[$type][$found][$avocat][$r["nodossier"]] = $r["id{$type}{$x}"];
							if($blacklist) $blacklist .= " and ";
							$blacklist .= "id != '{$r["id{$type}{$x}"]}'";
						}
					}
				}
			}
		}

		//Ensuite, recherche dans les autres
		$where = "";
		foreach(array($condAdresse, $blacklist) as $cond)
		{
			if(trim($cond) != "")
			{
				$where .= $where? " AND ": "WHERE";
				$where .= $cond;
// 				if(!$conds) $conds .= "where ";
// 				elseif($conds) $conds .= " and ";
// 				$conds .= $cond;
			}
		}
		$conds = "select titre, prenom, nom, fonction, adresse, zip, ville, id from adresses $where";
		$e = mysqli_query($this->mysqli, $conds) or die("<br>".mysqli_error($this->mysqli)."\n\n<br><br>".$this->beautifyMysql($conds));
		while($r = mysqli_fetch_array($e))
		{
				$found = "{$couleurs["autre"]}::{$r["nom"]}::{$r["prenom"]}::{$r["fonction"]}::{$r["titre"]}::{$r["adresse"]}::{$r["zip"]}::{$r["ville"]}::{$r["id"]}::";
				$liste["autre"][$found]["n/a"] = array();
		}
// 		ksort($liste["client"]);
// 		ksort($liste["pa"]);
// 		$this->tab_affiche($liste);
		return $liste;
	}
		
	function debugNow($add = "", $immediate = False)
	{
		if(!isset ($this->nowNumber)) $this->nowNumber = 0;
		if(!isset ($this->now)) $this->now = array();
		$n1 = $this->nowNumber;
		$n2 = $this->nowNumber -1;
		$add = $add ? " $add":"";
		$this->now[] = microtime(True);
		if($n1)
		{
// 			list($s1, $m1) = preg_split("/ /", $this->now["$n1"]); 
// 			list($s2, $m2) = preg_split("/ /", $this->now["$n2"]);
			$t0 = $this->now[0]; 
			$t1 = $this->now["$n1"]; 
			$t2 = $this->now["$n2"];
			$s = number_format($t1 -$t2, 2);
			$t = number_format($t1 -$t0, 2);
			$m1 = memory_get_peak_usage();
			$m = memory_get_usage();
			$m1 = $this->computerFormat($m1);
			$m = $this->computerFormat($m);
			$add .= " - $s /$t secondes écoulées (Memory: $m /$m1)";// ($s1, $s2, $m1, $m2, '{$this->now["$n2"]}')";
		}
		if($immediate)
		{
			echo "\n<br>$n1. $add";
			flush();
			ob_flush();
		}
		$this->now["$n1"].=" point ".$this->nowNumber . $add;
		$this->nowNumber ++;
		return true;
	}
	
	function checkDebug()
	{
		foreach($this->now as $point => $texte)
		{
			echo "\n<br>$point. $texte";
			if($point > 28)
			{
				$ms = substr($texte, 0, 21);
				$msa = substr($this->now[$point -1], 0, 21);
				list($ma, $sa) = preg_split("/ /", $msa);
				list($m, $s) = preg_split("/ /", $ms);
		// 		echo " --$ms--$msa--|$s|$m|$sa|$ma| ";
				$ma = substr($ma, 0, 5);
				$m  = substr($m, 0, 5);
				$nm = $m -$ma;
				$ns = $s -$sa;
				$nmi = $m -$mi;
				$nsi = $s -$si;
				if($nm < 0)
				{
					$ns -= 1;
					$nm += 1;
				} 
				if($nmi < 0)
				{
					$nsi -= 1;
					$nmi += 1;
				}
				$nm = substr($nm, 2);
				$nmi = substr($nmi, 2);
				$t = "$ns.$nm";
				$tt = "$nsi.$nmi";
				echo " ($t - $tt)";
			}
		}
	}

	function recuperationAssocies($test = False, $return = "", $suite = "") #$test à True se contente d'indiquer s'il existe une base vide ou non. $test à false affiche le formulaire de récupération.
	{
		if(!$test) echo "\n<!-- début de la récupération des associés -->";
		
		$sys_tables=$this->get_prolawyer_tables();
		$arr_system_tables = $sys_tables["system"];
		$arr_perso_tables = $sys_tables["partner"];
		$arr_total_tables = array_merge($arr_system_tables, $arr_perso_tables);
	
		$assoc_recup=array();
		$show_tables = @mysqli_query($this->mysqli, "show tables");
		if(! $show_tables) return false;
		while($row=mysqli_fetch_array($show_tables))
		{
			$coltosee="Tables_in_".$_SESSION["dbName"];
			$table_cours=trim($row["$coltosee"]);
			foreach($arr_perso_tables as $file => $test_cours)
			{
				$init_prob=substr($table_cours, 0, 2);
				if($init_prob.$test_cours == $table_cours)
				{
					$assoc_recup["$init_prob"]["$test_cours"]=TRUE;
				}
			}
		}
		
		$req="select * from utilisateurs";
		$ex=mysqli_query($this->mysqli, $req);
		while($row=mysqli_fetch_array($ex))
		{
			$init=$row["initiales"];
			$compare["$init"]=TRUE;
		}
		$t="";
		$compt = 0;
		if(!$return) $return = $this->rel_file_name;
		foreach($assoc_recup as $init => $type)
		{
			if($assoc_recup[$init]["op"]==TRUE AND $assoc_recup[$init]["clients"]==TRUE AND $assoc_recup[$init]["tarifs"]==TRUE AND $compare["$init"] != TRUE)
			{
				$compt ++;
				$t .= "\n<tr><td>{$this->lang["config_modify_init"]}&nbsp;:&nbsp;$init.</td><td>{$this->lang["config_modify_assoc_name"]}</td><td><input type=\"text\" name=\"nom$compt\">";
				$t .= $this->input_hidden("init$compt", "", "$init");
				$t .= "\n</td></tr>";
			}
			
		}
		
		if ($test) return $compt;
		if($t)
		{
			echo "\n<br><br>\n<h3>{$this->lang["config_modify_recup"]}</h3>\n{$this->lang["config_modify_missing_parts"]} :<br>\n<form action=\"{$this->settings["root"]}config/create.php\" method=\"post\">";
			echo $this->table_open("border=0");
			echo $t;
			echo $this->input_hidden("corriger", "", "on");
			echo $this->input_hidden("mode", "", "partner");
			echo $this->input_hidden("return", "", $return);
			echo $this->input_hidden("suite", "", $suite);
			echo "\n<tr><td colspan=\"2\">".$this->button("{$this->lang["config_modify_corriger"]}", "")."</td></tr>";
			echo $this->table_close();
			echo "</form>";
		}
		echo "\n<!-- fin de la récupération des associés -->";
		return $compt;
	}

	/*Fonctions propre à la gestion des dossiers*/

	function getFileValues($valTypes)
	{
		/*Possible values:
		1=noadresse, nopa, tvadosier
		*/
	}
	
	function config_walkdir($d)
	{
// 		echo "<br>Opening $d";
		$dir=opendir($d);
		while ($arr=readdir($dir)) 
		{
			if($arr != "config.zip" AND $arr!="." AND $arr!="..")
			{
				$f = $d . DIRECTORY_SEPARATOR .$arr;
				$t = is_dir($f) ? "d":"f";
				$this->file_list[$f]= $t;
	// 			$t = $_SESSION["optionsPath"] . DIRECTORY_SEPARATOR . $f; 
	// 			$f = $arr;
// 				echo "<br>$f";
/*				if(is_file($f))
				{
					echo "<br>adding $f";
					$this->file_list[]= $f;
				}
				else*/if(is_dir($f))
				{
// 					echo "<br>adding $f";
// 					$this->file_list[]= $f;
					$this->config_walkdir("$d/$arr");
				}
			}
		}
	}
	
}
