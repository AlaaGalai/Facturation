<?php
/****
* Titre........... : Fonctions pour la création de pages web
* Description..... : Ensemble de fonctions pour la mise en place de code HTML à partir de php
* version......... : 4.0
* date............ : 1.3.2010
* fichier......... : functions.class.php
* Auteur.......... : Olivier Subilia (etudeav@users.sourceforge.net)
*
*
* licence......... : The GNU General Public License (GPL) 
*					 http://www.opensource.org/licenses/gpl-license.html
*
****/
		
/*******************************************************************
*
*    class 
*
********************************************************************/

class functions
{
	/*Fonctions relatives au système de fichier*/
	
	
	function open_and_prepare($ftoopen, $ancien_mode=FALSE) //fonction pour lire le fichier, créer un tableau vide s'il n'existe pas et ôter les lignes de commentaire
	{
		if(is_file($ftoopen)) 
		{
			$arr=$this->wipe_control(file("$ftoopen"));
		}else{
			$arr=array();
		}
		foreach($arr as $key=>$elem)
		{
			$arr[$key] = trim($elem); //pour ôter les fins de ligne
			//echo "<br>$key a pour valeur $elem";
		}
		return $arr;
	}
	
	function close_and_write($arr, $ftoclose, $secure=TRUE, $php=TRUE, $sort=TRUE) //fonction pour écrire le fichier en rajoutant les lignes de commentaires
	{
		if($sort) asort($arr);
		if($php) $ins2=array_unshift($arr, "<?php/*");
		if($php) $ins3=array_push($arr, "*/?>");
		$implode=implode($arr, "\n");
		$implode=preg_replace("#\n\n#", "\n", $implode);
		@$file=fopen("$ftoclose", "w+");
		@$write=fwrite($file, "$implode");
		if($secure) $change=@chmod($ftoclose, 0660);
		$close=@fclose($file);
		return $close;
	}
	
	function wipe_array($arr, $string, $needle=",") //fonction pour ôter une ligne
	{
		$arr2=$arr;
		foreach($arr as $key => $elem)
		{
			list($first)=explode($needle, $elem);
			if(trim($first)==$string)
			{
				array_splice($arr, $key, 1);
				break;
			}
		}
		return $arr;
	}

	
	function wipe_control($arr) //fonction pour ôter les lignes de commentaire et les lignes vides
	{
		$arr=$this->wipe_array($arr, "*/?>");
		$arr=$this->wipe_array($arr, "<?php/*");
		for($x=1;; $x++)
		{
			$arr2=$this->wipe_array($arr, "");
			if($arr2==$arr) break;
			$arr=$arr2;
		}
		return $arr;
	}
	
	function do_post_request($url, $data, $optional_headers = null)
	{
		$params = array
		(
			'http' => array
			(
				'method' => 'POST',
				'content' => $data
			)
		);
		if ($optional_headers !== null)
		{
			$params['http']['header'] = $optional_headers;
		}
		$ctx = stream_context_create($params);
		$fp = @fopen($url, 'rb', false, $ctx);
		if (!$fp)
		{
			throw new Exception("Problem with $url, $php_errormsg");
		}
		$response = @stream_get_contents($fp);
		if ($response === false)
		{
			throw new Exception("Problem reading data from $url, $php_errormsg");
		}
		return $response;
	}
	
	
	//fonctions de cryptographie
	function codage($string, $pass)
	{
		$result="";
		for($x=0, $y=0;;$x++, $y++)
		{
			if($x >= strlen($string)) break;
			if (!substr($pass, $y, 1)) $y=0;
			$char1=substr($string, $x, 1);
			$char2=substr($pass, $y, 1);
			$valtemp=sprintf("%03s",ord($char1) + ord($char2));
			$result=$result.$valtemp;
		}
		return $result;
	}
	
	
	function decodage($string, $pass)
	{
		$result2="";
		for($x=0, $y=0;;$x=$x+3, $y++)
		{
			if($x >= strlen($string)) break;
			if (!substr($pass, $y, 1)) $y=0;
			$char1=substr($string, $x, 3);
			$char2=substr($pass, $y, 1);
			$result2=$result2.chr($char1 - ord($char2));
		}
		return $result2;
	}
	
	function code($string, $pass)
	{
		if($_SESSION["crypt_algo"])
		{
			$iv = mcrypt_create_iv($_SESSION["ivsize"], MCRYPT_RAND);
// 			echo "<br>$string, $pass, $iv";
			$key=substr($pass, 0, $_SESSION["keysize"]);
			while(strlen($key) < $_SESSION["keysize"]) $key = $key."\0";
			$code = mcrypt_encrypt($_SESSION["crypt_algo"], $key, $string, $_SESSION["crypt_mode"], $iv);
			$sericode=serialize(array($code, $iv));
			$sericode=rawurlencode($sericode);
			return $sericode;

		}
		$codetmp=$this->codage($string, $pass);
		$revpass=strrev($pass);
		$code=$this->codage($codetmp, $revpass);
		return $code;
	}
	
	function decode($string, $pass)
	{
		if(is_array(unserialize($string)))
		{
// 			$this->tab_affiche($_SESSION);
			$key=substr($pass, 0, $_SESSION["keysize"]);
			while(strlen($key) < $_SESSION["keysize"]) $key = $key."\0";
			$cipher=$_SESSION["crypt_algo"];
			$mode=$_SESSION["crypt_mode"];
			list($code, $iv) = unserialize($string);
			echo "<br>décodage de l'algorythme '$cipher' avec la clé $key et pour le mode $mode<br>";
			$decode = mcrypt_decrypt($_SESSION["crypt_algo"], $key, $code, $mode, $iv);
			return trim($decode);
		}
		elseif(is_array(unserialize(rawurldecode($string))))
		{
// 			$this->tab_affiche($_SESSION);
			$key=substr($pass, 0, $_SESSION["keysize"]);
			while(strlen($key) < $_SESSION["keysize"]) $key = $key."\0";
			$cipher=$_SESSION["crypt_algo"];
			$mode=$_SESSION["crypt_mode"];
			list($code, $iv) = unserialize(rawurldecode($string));
// 			echo "<br>décodage du pass '$string' '$pass' (code: '$code'; iv: '$iv') via l'algorythme '$cipher' avec la clé $key et pour le mode $mode (la clé vaut '$iv'<br>";
			$decode = mcrypt_decrypt($_SESSION["crypt_algo"], $key, $code, $mode, $iv);
			return trim($decode);
		}
		$revpass=strrev($pass);
		$codetmp=$this->decodage($string, $revpass);
		$decode=$this->decodage($codetmp, $pass);
		return $decode;
	}
	
	
	
	/*Fonctions de gestion des erreurs*/
	
	public function catchError($errMsg, $severity, $index=0)
	{
		$this->errorMsg[$index][$errMsg] = $severity;
		if($severity)     $this->errorContSet[$index] = true;
		if($severity == 4) $this->errorStopSet[$index] = true;
	}
	
	public function isError($level="all", $index = 0)
	{
		if($level == "all" || $level == "stop")
		{
			if($this->errorStopSet[$index]) return true;
		}
		if($level == "all" || $level == "cont")
		{
			if($this->errorContSet[$index]) return true;
		}
		return false;
	}


	public function resetError($level="all", $index = 0)
	{
		if($level == "all" || $level == "stop")
		{
			if($this->errorStopSet[$index])
			{
				unset($this->errorStopSet[$index]);
			}
		}
		if($level == "all" || $level == "cont")
		{
			if($this->errorContSet[$index])
			{
				unset($this->errorContSet[$index]);
			}
		}
		if(isset($this->errorMsg[$index]))
		{
			unset($this->errorMsg[$index]);
		}
	}


	public function echoError($errMsg = "auto", $index=0, $mef = "mefLi")
	{
		$this->errStop = false;
		$this->errCont = false;
		if($errMsg == "noStop") $noStop = true;
		if($errMsg == "auto" || $errMsg == "noStop")
		{
			$arr = $this->errorMsg[$index];
			if(!is_array($arr)) return;
			foreach($arr as $errMsg => $severity) $texte .= $this->echoErrorText($errMsg, $severity, $mef);
		}
		
		else $texte = $this->echoErrorText($errMsg, $severity, $mef);

		if($mef && $mef != "mefLiOnly") $texte = "\n<ul>\n\t$texte\n</ul>";
		if($this->errStop && !$noStop) die($texte . "(arrêt automatique par le système en cas d'erreur blocante)");
		return $texte;
	}
	
	public function echoUniqueError($errMsg, $severity, $mef, $noErrCat = False)
	{
		if($noErrCat) $this->noEchoErrCat = True;
		$return = $this->echoErrorText($errMsg, $severity, $mef);
		$this->noEchoErrCat = False;
		return $return; 
	}
	
	private function echoErrorText($errMsg, $severity, $mef)
	{
		if (! $this->noLangRequire)
		{
			$this->getLangFile("err/");
		}
		else
		{
			$root = "./";
			while(!is_file ($root."root.php")) $root .= "../";
			require($root."lang/err/fr.php");
			foreach($langchoisie as $n => $l)
			{
				$this->lang["$n"] = $this->smart_html("$l");
				if(preg_match("#modifier_donnees_societe#", $n))
				{
					$x2 = preg_replace("#modifier_donnees_societe_#", "", $n);
					$this->societes["$x2"] = $this->lang["$n"];
				}
			}
		}
		
		$sevArray = array
		(
			0 => "true",
			1 => "advice",
			2 => "warning",
			4 => "false"
		);

		$sevColors = array
		(
			0 => "#00ff00", //green
			1 => "#ffff00", //yellow
			2 => "#ff8000", //orange
			4 => "#ff0000" //red
		);

		if(preg_match("#^(0)?([0-9]{3}\-[0-9]{3}.*)#", $errMsg, $regs))
		{
			list($errMsg, $inds) = preg_split("#::#", $regs[2]);
			list($errMsg, $reps) = preg_split("/#-#/", $errMsg); //s'il y a un #, cela nettoye la chaîne
			list($inds, $reps2) = preg_split("/#-#/", $inds ); //s'il y a un #, cela nettoye la chaîne
			$errname_string="errors_".$errMsg;
			$errcat_string="errors_".substr($errMsg, 0, 3)."-000";
			$errind_string="errors_".substr($errMsg, 0, 3)."-$inds";
			$errString = $this->lang["$errname_string"];
			$errCat    = $this->lang["$errcat_string"];
			$rem=($inds && isset($this->lang["$errind_string"]))? $this->lang["$errind_string"]:"";
			if($repsa=1) //pas certain que ce soit utile de vérifier si reps est utile et non vide. Avant la condition était "if($reps)"
			{
				$aReps = explode("#", $reps);
				foreach($aReps as $rep)
				{
					$offset = strpos($errString, "{##}");
					if($offset) $errString = substr($errString, 0, $offset). $rep . substr($errString, $offset + 4);
				}
			}
	
			if($inds)
			{
				$aInds = explode("#", $reps2);
				foreach($aInds as $ind)
				{
					$offset = strpos($rem, "{##}");
// 					echo "recherche dans <b>$errString</b> de {##} (trouvé à l'offset $offset)<br>";
					if($offset) $rem = substr($rem, 0, $offset). $ind . substr($rem, $offset + 4);
				}
			}
			
	
			if($severity && $regs[1] != "0" && ! $this->noEchoErrCat) $errMsg = /*"{$this->lang["errors_categorie"]}: ".*/"$errCat. $errString.";
			else $errMsg = "$errString.";
		}
		
		$rem2 = $rem ? "<img src=\"{$this->settings["root"]}images/advice.png\" onmouseover = \"show('".addslashes(stripslashes($rem))."')\" onmouseout = \"hide()\">":"";
		if($mef == "mefOnly") $texte = "\n<img src=\"{$this->settings["root"]}images/{$sevArray[$severity]}.png\">$errMsg $rem2";
		elseif($mef == "mefTextOnly") $texte = "\n$errMsg $rem2";
		elseif($mef) $texte = "\n<li style=\"list-style-image : url({$this->settings["root"]}images/{$sevArray[$severity]}.png);\">$errMsg $rem2</li>";
		else $texte = "$errMsg <i>$rem</i>";
		if($severity == 4) $this->errStop = true;
		if($severity)      $this->errCont = true;
		return $texte;
	}
	
	function advice($texte, $img="advice", $add = "", $size_h=600, $size_v=800, $fName = "_new")
	{
		if(is_file("{$this->settings["root"]}images/$img.png")) $img = "{$this->settings["root"]}images/$img.png";
		elseif(is_file("{$this->settings["root"]}images/$img")) $img = "{$this->settings["root"]}images/$img";
		elseif(is_file($img)) $img = $img;
		else return "(img $img not found)";

		if(preg_match("#^fo:(.*$)#", $texte, $regs)) #fo pour file open
		{
			$onClick = "onclick=\"javascript:window.open('{$this->settings["root"]}todo.php?fichier={$regs[1]}', '$fName', 'toolbar=no,directories=no,menubar=no=no,statusbar=no,toolbar=no,titlebar=no,scrollbars=yes,width=$size_h,height=$size_v')\"";
			return "<img src=\"$img\" $onClick $add>";
		}
		else
		{
			$texte = addslashes(stripslashes($texte));
			return "<img src=\"$img\" onmouseOver = \"show('$texte')\" onmouseout = \"hide()\" $add>";
		}
	}
	
	
	/*Fonctions de texte*/
	
	function detectUtf8($string)
	{
		return preg_match('%(?:
		[\xC2-\xDF][\x80-\xBF]        # non-overlong 2-byte
		|\xE0[\xA0-\xBF][\x80-\xBF]               # excluding overlongs
		|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}      # straight 3-byte
		|\xED[\x80-\x9F][\x80-\xBF]               # excluding surrogates
		|\xF0[\x90-\xBF][\x80-\xBF]{2}    # planes 1-3
		|[\xF1-\xF3][\x80-\xBF]{3}                  # planes 4-15
		|\xF4[\x80-\x8F][\x80-\xBF]{2}    # plane 16
		)+%xs', $string);
	}
	
	function my_htmlentities($nom)
	{
		if($this->detectUtf8($nom)) return htmlentities($nom, ENT_COMPAT | ENT_HTML401 ,'UTF-8');
// 		if(preg_match("#(Ã|Â)#", $nom)) return htmlentities($nom, ENT_COMPAT | ENT_HTML401 ,'UTF-8');
		else return htmlentities($nom, ENT_COMPAT | ENT_HTML401 ,'ISO-8859-1');
	}

	function smart_html($hval)
	{
		$trans=get_html_translation_table(HTML_SPECIALCHARS);
		$trans = array_flip($trans);
		
		//echo "\n<br>1'$hval'";
		//la valeur a-t-elle été codée en html ?
		$hval = html_entity_decode($hval);
		//echo "\n<br>1'$hval'";
		$hval = html_entity_decode($hval);
		//echo "\n<br>1'$hval'";
		$hval = html_entity_decode($hval);
		//echo "\n<br>1'$hval'";
		$hval = html_entity_decode($hval);
		//echo "\n<br>2'$hval'";

		//gestion de l'utf-8
		//problème spécifique aux &nbsp;
		$hval = str_replace("\xc2\xa0",' ',$hval);
		$hval=$this->smart_utf8($hval);
		$hval2=$this->my_htmlentities("$hval");
		//attention au codage intempestif des caractères spéciaux
		$hval3=strtr("$hval2", $trans);
		return $hval3;
		return $hval;
	}
	
	function smart_utf8($hval)
	{
// 		if(preg_match("#Ã|Â#", $hval)) $hval=utf8_decode($hval);
		if($this->detectUtf8($hval)) $hval=utf8_decode($hval);
		return $hval;
	}
	
	function toCsv($hval, $quote = True, $semicolon = ";", $noEnreg = -1, $autoSum = False)
	{
		#Traitement des colonnes
		$hval = html_entity_decode($hval);
		$hval = preg_replace("#<br([ /])*>#", " ", $hval);
		$hval = preg_replace('#"#', '""', $hval);
		if($quote) $hval = "\"$hval\"";
		if($semicolon) $hval .= "$semicolon";

		#Gestion automatique des noms de colonnes
		if($noEnreg === "ENTETE") #Définition de colonnes
		{
// 			echo "csvId = $noEnreg";
			if(!isset ($this->cvsColNames))
			{
				$this->cvsColNames = "";
			}
			$this->cvsColNames .= $hval;
		}
		elseif($noEnreg == 0) #en principe le premier enregistrement d'une série
		{
// 			echo "csvId = $noEnreg";
			if(!isset ($this->cvsCol))
			{
				$this->cvsCol = 0;
				$this->cvsCols = array();
			}
			else $this->cvsCol ++;
			$lt = chr($this->cvsCol + 65);
			$this->cvsCols[$lt] = $autoSum;
		}
		$this->cvsNoEnreg = $noEnreg;
		return($hval);
	}
	
	function csvSum()
	{
		$noEnreg = $this->cvsNoEnreg + 2; //le premier enregistrement commence à zéro au lieu de 1, et en plus la ligne 1 est occupée par les entetes
		echo "\n";
		foreach($this->cvsCols as $a => $b)
		{
			if($b) echo "\"=SOMME(${a}2:${a}$noEnreg)\";";
			else echo '"";';
		}
		echo "\n{$this->cvsColNames}";
	}
	
	function no_accent($str, $encoding='utf-8')
	{
	// transformer les caractères accentués en entités HTML
		//echo "<br>'$str'";
		$str = htmlentities($str, ENT_NOQUOTES, $encoding);
		$str = $this->smart_html($str, ENT_NOQUOTES, $encoding);

		// remplacer les entités HTML pour avoir juste le premier caractères non accentués
		// Exemple : "&ecute;" => "e", "&Ecute;" => "E", "Ã " => "a" ...
		$str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);
		//echo " '$str'";
		// Remplacer les ligatures tel que : Œ, Æ ...
		// Exemple "Å“" => "oe"
		$str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);
		//echo " '$str'";
		// Supprimer tout le reste
		$str = preg_replace('#&[^;]+;#', '', $str);
		//echo " '$str'";

		return $str;
	}

	
	function no_accent_old($string)
	{
		$string = html_entity_decode($string);
		echo " '$string' ";
		$string=strtr($string, "áàâäéèêëíìîïóòôöúùûüýÿçñÁÀÂÄÉÈÊËÍÌÌÏÓÒÔÖÚÙÛÜÝÑ", "aaaaeeeeiiiioooouuuuyycnAAAAEEEEIIIIOOOOUUUUYN");
		return $string;
	}
	
	function liste_ok($liste) //fonction pour lire une liste sans problème
	{
		$n=0;
		$liste_ok="";
		foreach(explode("\n", "$liste") as $val)
		{
			if($n>0 AND trim($val)!="")$liste_ok=$liste_ok."\n";
			$n++;
			$liste_ok=$liste_ok.trim($val);
		}
		return($liste_ok);
	}
	
	function listeArray($liste, $needle=",") //fonction pour lire une liste sans problème
	{
		$array = array();
		foreach(explode("\n", "$liste") as $val)
		{
			if(trim($val)!="")$array[] = preg_split("@$needle@", trim($val));
		}
		return($array);
	}
	
	function make_visible($haystack, $string)
	{
		if(!isset($this->gardefou)) $this->gardefou=0;
		$this->gardefou ++;
		if(is_string($haystack)) $haystack=array($haystack);
		$libellesansaccents=strtolower($this->no_accent($string));
		$libelle=strtolower($string);
		$offset=FALSE;
		$endoffset=FALSE;
		$new=FALSE;
		$restrict="";
		mb_internal_encoding("utf-8");
		foreach($haystack as $temprestrict) if(trim($temprestrict) != "")
		{
			$temprestrict=strtolower($this->no_accent($temprestrict));
			if (mb_strpos($libellesansaccents, $temprestrict) === FALSE) echo "";
			else
			{
				$tempoffset=mb_strpos($libellesansaccents, $temprestrict);
				$tempendoffset=$tempoffset + mb_strlen($temprestrict);
				if($tempoffset < $offset || $new == FALSE || ($tempoffset == $offset && $tempendoffset > $endoffset))
				{
					$new = TRUE;
					$offset=$tempoffset;
					$endoffset=$tempendoffset;
					$restrict = $temprestrict;
				}
			}
		}
		
		$debut=mb_substr($string, 0, $offset);
		$milieu=mb_substr($string,$offset,mb_strlen($restrict));
		$fin=mb_substr($string, $endoffset);
		
		if($restrict AND $this->gardefou <5) $fin=$this->make_visible($haystack, $fin);
		
		$new_libelle=$this->smart_html("$debut<span class=attention>$milieu</span>$fin");
		$this->gardefou --;
		if(!$milieu) return $string;
		return $new_libelle;
	}
	
	function nice_array($array, $cols=3, $table="", $tr="", $td="")
	{
		$compteur=0;
		$return = "<table $table>";
		foreach($array as $cell)
		{
			if($compteur == 0) $return .= "\n\t<tr $tr>";
			$return .= "<td $td>$cell</td>";
			$compteur++;
			if($compteur == $cols)
			{
				$compteur = 0;
				$return .= "</tr>";
			}
		}
		if($compteur != $cols) for($x=$compteur;$x<$cols;$x++) $return .= "<td>&nbsp;</td>";
		$return .= "\n<table>";
		return $return;
	}

	function scientifique($vartotest)
	{
		$neg=FALSE;
		if($vartotest<0)
		{
			$vartotest=0-$vartotest;
			$neg=TRUE;
		}
		if($vartotest <1 AND $vartotest >0)
		{
			for($n=1;$vartotest<10;$n++,$vartotest = $vartotest *10)
			{
				if($vartotest>=1)
				{
					$result["mantisse"]=$vartotest;
					$result["exposant"]= 1-$n;
				}
			}
		}
		elseif($vartotest >10)
		{
			for($n=1;$vartotest>=1;$n++,$vartotest = $vartotest / 10)
			{
				if($vartotest<10)
				{
					$result["mantisse"]=$vartotest;
					$result["exposant"]= $n -1;
				}
			}
		}else{
					$result["mantisse"]=$vartotest;
					$result["exposant"]= 0;
		}
		if($neg) $result["mantisse"] = 0 - $result["mantisse"];
		return $result;

	}

	function button($nom="", $accesskey="", $style="")
	{
		if($nom == "#invisible#")
		{
			$stylekey = "class=\"invisible\"";
			$nom = "&nbsp;";
		}
		if(preg_match("#<td#", $nom) || preg_match("#<th#", $nom)) //permet de gérer correctement les formulaires dans les cellules avec Konqueror
		{	
			if(preg_match("#<td#", $nom))
			{
				$motif="<td";
				$end="</td>";
			}
			if(preg_match("#<th#", $nom))
			{
				$motif="<th";
				$end="</th>";
			}
			$offset=strpos($nom, $motif);
			$td_val=substr($nom, $offset);
			$nom=substr($nom, 0, $offset);
			if(!$nom) $nom = "TODO";
			if($nom == 1) $nom == "";
			$td=TRUE;
		}
		if(preg_match("#<id>#", $accesskey))
		list($accesskey, $id) = preg_split("#<id>#", $accesskey);
		if($accesskey!="")
		{
			$key="accesskey=$accesskey";
			if(!isset($id)) $id=$this->find_accesskey($nom, $accesskey);
		}
		else $key="";
		if($style)
		{
			if(preg_match("#style#", $style)) $stylekey = $style;
			else $stylekey="class=$style";
		}
		else $stylekey="";
		if($id) $id = "id=\"$id\"";
		$string="<button $stylekey type=\"submit\" $key $id>$nom</button>";
		if($td) $string = $td_val.$string.$end;
		return $string;
	}
	
	function input_checkbox($nom, $idem, $check = "", $id="", $ajout="", $value = "1")
	{
		if($idem && isset($_REQUEST["$nom"])) $check=$_REQUEST["$nom"]? "checked":""; #$check peut servir de défaut même avec $idem si la variable n'est pas settée
		elseif($check) $check="checked";
		if(!$id) $id=$nom;
		$value = $value ? "value=\"$value\"":"";
		$string="<input type=\"checkbox\" id=\"$id\" name=\"$nom\" $value $check $ajout>";
		return $string;
	}

	function input_hidden($nom, $idem, $value="", $id="")
	{
		if($idem) $value=$_REQUEST["$nom"];
		if(!$id) $id="id=\"$nom\"";
		if($id == "NOID") $id = "";
		$string="<input type=\"hidden\" $id name=\"$nom\" value=\"$value\">";
		return $string;
	}

	function input_texte($nom, $idem=FALSE, $value="", $size="", $div=False, $event="", $style="", $prefix = "", $sep = "", $mode=0)
	{
		if(! $size) $size = 10;
		if(preg_match("#<td#", $nom) || preg_match("#<th#", $nom)) //permet de gérer correctement les formulaires dans les cellules avec Konqueror
		{	
			if(preg_match("#<td#", $nom))
			{
				$motif="<td";
				$nom_end="</td>";
			}
			if(preg_match("#<th#", $nom))
			{
				$motif="<th";
				$nom_end="</th>";
			}
			$offset=strpos($nom, $motif);
			$tdnom_val=substr($nom, $offset);
			$nom=substr($nom, 0, $offset);
			$tdnom=TRUE;
		}
		$nomOK=$nom;
		$placeholder = $prefix;
		if(preg_match("#<td#", $prefix) || preg_match("#<th#", $prefix)) //permet de gérer correctement les formulaires dans les
		{
			if(preg_match("#<td#", $prefix))
			{
				$motif="<td";
				$end="</td>";
			}
			if(preg_match("#<th#", $prefix))
			{
				$motif="<th";
				$end="</th>";
			}
			$offset=strpos($prefix, $motif);
			$tdprefix_val=substr($prefix, $offset);
			$placeholder=substr($prefix, 0, $offset);
			$tdprefix=TRUE;
		}
		$prefixOK=$prefix;
		if(preg_match("#\[#", $nom))
		{
			$offset2=strpos($nom, "[");
			$of=substr($nom, $offset2);
			$nom=substr($nom, 0, $offset2);
			$of=substr($of, 1);
			$of=substr($of, 0, -1);
		}
		if(!$id) $id=$nomOK;
		if($idem) $value=$_REQUEST["$nom"];
		if($idem && isset($of)) $value=$_REQUEST["$nom"]["$of"];
// 		echo "<br>nom vaut $nom. of vaut $of. ";
		$cValue = preg_replace('#"#', '\'', $value);
		$event = ($event && !preg_match("#^(on|style)#", $event)) ? "onkeypress='$event'":$event;
		$string="<input type=\"texte\" name=\"$nomOK\" id=\"$id\" placeholdertoto=\"$prefix\" value=\"$value\" style=\"width:{$size}em;$style\" onfocus=select() $event>";
		if($div) $string = "<div style='z-index:10000;background-color:#d0d0d0;height:3.5em' id='eDiv_$id' contentEditable=True onFocus='if(event.keyCode != 9)checkEmpty(\"eDiv_$id\", false)' $event onBlur='checkEmpty(\"eDiv_$id\", true)'>$value</div><input type=\"hidden\" name=\"$nomOK\" id=\"$id\" value=\"$cValue\">";
		if($_REQUEST["print"]) $string=$value;
		elseif($tdnom) $string = $tdnom_val.$string.$nom_end;
		if($prefix && ! ($this->pdaSet && $this->pdaNoPrefix)) $string = $prefix.$string;
// 		echo "<br>string vaut '$string'";
		return $string;
	}

	function input_pwd($nom, $idem=FALSE, $value="", $size="10")
	{
		if($idem) $value=$_REQUEST["$nom"];
		$string="<input type=\"password\" name=\"$nom\" value=\"$value\" size=\"$size\">";
		return $string;
	}
	
	function input_liste($nom, $values = "", $addValue = "", $multicheck = False, $size = 1, $onChange = "")
	{
		if($_POST["print"]) return $values.$addValue;
		else
		{
			$multiple = $multicheck ? "multiple":"";
			$string = "<select name='$nom' id='$nom' $multiple size=$size onChange='$onChange'>$values</select>";
			return $string;
		}
	}
	
	function selectJoli($nom, $idem=false, $options=array(), $classInput="", $classFond="", $classSelect="", $jsOptions="")
	{
		$jsOptions=addslashes($jsOptions);
		if(substr($nom, 0, 8) == "<unique>")
		{
			$nom=substr($nom, 8);
			$unique=true;
		}
		if(!$unique) $uniqueID=preg_replace("# #", "", $nom) .time();
		else $uniqueID=$nom;
		$uniqueListID="list$uniqueID";
		$varName = "var$uniqueID";
		
		$var = "var $varName='<TABLE cellspacing=\"0\" cellpadding=\"0\" border=\"1\">";
		foreach($options as $option => $value)
		{
			$var .= "<tr class=\"$classFond\" style=\"cursor:pointer\" onMouseOver=\"this.className=\'$classSelect\'\" onMouseOut=\"this.className=\'$classFond\'\"><td onClick=\"document.getElementById(\'$uniqueID\').innerHTML=\'$value\';document.getElementById(\'$uniqueListID\').innerHTML=\'\';$jsOptions\">$value</td></tr>";
		}
		$var .= "</table>'";
		
		if($idem === true) $value=$_POST["$nom"];
		elseif($idem) $value = $idem;
		else $value="";
		
		$return = "\n<script>$var</script>";
		$return .= "\n<table>\n<tr><td><span name=\"$nom\" id=\"$uniqueID\" onClick=\"doMenu('$uniqueID',$varName)\" class='$classInput' style=\"cursor:pointer\">$value</span>";
		$return .= "\n<br><div class=\"listeDeroul\" id=\"$uniqueListID\">";
		$return .= "</div></td></tr></table>";
		return $return;
	}
	
	function split_date($date, $nom="", $nomjour="", $nommois="", $nomannee="", $onchange="", $other="")
	{
		$avant = "";
		if(preg_match("/##/", $nom)) list($avant, $apres) = preg_split("/##/", $nom);
		else $apres = $nom;
		if($nomjour == "") $nomjour = $avant."jour".$apres;
		if($nommois == "") $nommois = $avant."mois".$apres;
		if($nomannee== "") $nomannee= $avant."annee".$apres;

		if(!$id) $id=$name;

		if($date == "NOW")
		{
			$jour=date("d", time());
			$mois=date("m", time());
			$annee=date("Y", time());
		}
		elseif($date == "POST")
		{
// 			$this->tab_affiche();
			//vérification des données introduites

			$anneeI = $_POST["annee$nom"];
			$moisI = $_POST["mois$nom"];
			$jourI = $_POST["jour$nom"];
                        if($jourI && $moisI && $anneeI)
                        {
                                if(!is_array($this->autoDates)) $this->autoDates = array();
                                $this->autoDates["$nom"] = array("m" => "$anneeI-$moisI-$jourI");
                        }
                        if($anneeI && $moisI && $jourI)
                        
			{
				$d=mktime(1, 0, 0, $moisI, $jourI, $anneeI);
				$annee=$this->univ_strftime("%Y", $d);
				$jour=$this->univ_strftime("%d", $d);
				$mois=$this->univ_strftime("%m", $d);

				if($mois != $moisI || $jour != $jourI || $annee != $anneeI)
				{
					$this->dateErrors["$nom"] = true;
					$mois=$moisI;
					$jour=$jourI;
					$annee=$anneeI;
				}
			}
			else
			{
				$annee="";
				$mois="";
				$jour="";
			}
		}
		else
		{
			if(preg_match("#<td#", $date) || preg_match("#<th#", $date)) //permet de gérer correctement les formulaires dans les cellules avec Konqueror
			{	
				if(preg_match("#<td#", $date))
				{
					$motif="<td";
					$end="</td>";
				}
				if(preg_match("#<th#", $date))
				{
					$motif="<th";
					$end="</th>";
				}
				$offset=strpos($date, $motif);
				$td_val=substr($date, $offset);
				$date=substr($date, 0, $offset);
				$td=TRUE;
			}
	/*		$jour=substr($date, 8, 2);
			$mois=substr($date, 5, 2);
			$annee=substr($date, 0, 4);*/
			list($annee, $mois, $jour) = preg_split("#-#", $date);
		}
		if($mois < 10 && strlen($mois) == 1) $mois = "0$mois";
		if($jour < 10 && strlen($mois) == 1) $mois = "0$mois";
		//$string="\n<input onfocus=select() name=\"$nomjour\" id=\"$nomjour\" value=\"$jour\" class='splitdate' style='width:2em'>&nbsp;<input onfocus=select() name=\"$nommois\" id=\"$nommois\" value=\"$mois\" class='splitdate' style='width:2em'>&nbsp;<input onfocus=select() name=\"$nomannee\" id=\"$nomannee\" value=\"$annee\" class='splitdate' style='width:4em'>";
		$string="\n<input onfocus=select() name=\"$nomjour\" id=\"$nomjour\" value=\"$jour\" onchange='$onchange' $other style='width:2em'><input onfocus=select() name=\"$nommois\" id=\"$nommois\" value=\"$mois\" onchange='$onchange' $other style='width:2em'><input onfocus=select() name=\"$nomannee\" id=\"$nomannee\" value=\"$annee\" onchange='$onchange' $other style='width:4em'>";
		if($_POST["print"]) $string="$jour.$mois.$annee";
		if($string == "..") $string = "";
		if($td) $string = $td_val.$string.$end;
		return $string;
	}

	function split_time($time, $nom="", $nomheure="", $nomminute="", $onchange="", $other="")
	{
		$avant = "";
		if(preg_match("/##/", $nom)) list($avant, $apres) = preg_split("/##/", $nom);
		else $apres = $nom;
		if($nomheure == "") $nomheure = $avant."heure".$apres;
		if($nomminute == "") $nomminute = $avant."minute".$apres;
		list($heure, $minute)=preg_split("#:#", $time);
		$string="\n<input onfocus=select() name=\"$nomheure\" id=\"$nomheure\" value=\"$heure\" onchange='$onchange' size=\"2\" $other>:<input onfocus=select() name=\"$nomminute\" id=\"$nomminute\" value=\"$minute\" onchange='$onchange' size=\"2\">";
		if($_POST["print"]) $string="$heure:$minute";
		return $string;
	}

	function form($url, $nom, $accesskey, $style="", $target="", $option1="", $val1="", $option2="", $val2="", $option3="", $val3="", $option4="", $val4="", $option5="", $val5="", $option6="", $val6="", $option7="", $val7="", $option8="", $val8="", $option9="", $val9="", $option10="", $val10="")
	{
		if(preg_match("#<option#", "$val1")) $selecteur = True;
		else $selecteur = False;
		$keep_val=FALSE;
		if(preg_match("#__liste__#", $url) || (isset($this->startForms[$url]) && $this->startForms[$url] == $nom))
		{
			$keep_val=TRUE;
			$url = preg_replace("#__liste__#", "", $url);
			$act_form=array();
			if(!isset($this->arr_forms)) $this->arr_forms=array();
			$act_form["nom"]= $nom;
			$act_form["style"]=$style;
			$act_form["accesskey"]=$accesskey;
		}
		for($x=1;$x<9;$x++)
		{
			$testname="option".$x;
			$testval=$$testname;
			if($testval != "aPost") $exclusion[]=$testval;
		}
		$string = $this->specPdaForm? "":"\n";
		$pOptions = "";
		list($nom, $butId) = preg_split("#@#", $nom);
		$nom_form=preg_replace("# #", "", $nom);
		$nom_form=preg_replace("#'#", "", $nom_form);
		$nom_form=preg_replace("#&nbsp;#", "", $nom_form); //lorsqu'on a un nom de formulaire automatique pré-traité avec le remplacement par des espaces protégés (cf etude::entete() )
		$td=FALSE;
		if(preg_match("#<pda>#", $url))
		{
			$url = preg_replace("#<pda>#", "", $url);
			$setPda = True;
		}
		else $setPda = False;
		if(preg_match("#<td#", $url) || preg_match("#<th#", $url)) //permet de gérer correctement les formulaires dans les cellules avec Konqueror
		{
			if($this->specPdaForm)
			{
				$url = preg_replace("#<t(d|h)[^>]?>#", "", $url);
				//<? //pour un problème d'affichage dans vim
			}
			else
			{
				if(preg_match("#<td#", $url))
				{
					$motif="<td";
					$end="</td>";
				}
				if(preg_match("#<th#", $url))
				{
					$motif="<th";
					$end="</th>";
				}
				$offset=strpos($url, $motif);
				$td_val=substr($url, $offset);
				$url=substr($url, 0, $offset);
				$td=TRUE;
			}
			if($keep_val) $act_form["url"] = $url;
		}

		if(preg_match("#<td>#", $target)) //oublié de rajouter avant...
		{	
			list($nom_form, $target) = preg_split("#<td>#", $target);
		}

		$nomUtf = html_entity_decode($nom, ENT_COMPAT|ENT_HTML401, "UTF-8");
		if($accesskey!="")
		{
			//$key="accesskey='$accesskey'";
			$key="accesskey=$accesskey";
			$key="accesskey=\"$accesskey\"";
			$nom=$this->find_accesskey($nom, $accesskey);
			$infokey = $this->infobulle($this->getAccessSchema2($accesskey));
		}
		else
		{
			$key="";
			$infokey="";
		}
		//$infokey="";
		if(trim($target)!="") $targetkey="target=\"$target\"";
		else $targetkey="";
		if($style)
		{
			list($style, $styleTarget) = preg_split("#@#", $style);
			if(preg_match("#^style#i", $style)) $stylekey = $style;
			else $stylekey="class=\"$style\"";
			$formStyle = $styleTarget == "form" ? $stylekey:"";
		}
		else $stylekey="";
		if(!preg_match("#http|file://#", $url)) $url=$this->settings["root"].$url;
		if(!is_file($url) && is_file("$url.php")) $url="$url.php"; 
		if($this->keep_val) $this->arr_forms["$nom_form"]["action"] = $url;
		if($td) $string=$string.$td_val;
		$formMethod = $this->formMethod ? $this-formMethod:"post";
		$string=$string."\n<form action=\"$url\" $formStyle name=\"$nom_form\" id=\"$nom_form\" method=\"$formMethod\" $targetkey>";
 		$pString = "$url";
		if(!isset($this->noButtonID)) $this->noButtonID = False;
		$bID = $this->noButtonID ? "":"id='{$nom_form}B'";
		if($butId) $bID = "id='$butId'";
		if($selecteur && $this->newPdaMenu) $string .= "{$nom}&nbsp;";
		else $string .= "\n\t<button $stylekey $bID type=submit $key $infokey>$nom</button>";
		if($option1!="")
		{
			if($val1 === '###') $val1 = $_REQUEST[$option1];
			if($selecteur)
			{
				preg_match_all("#<option +value=['\"]?([[:alnum:]]*)?['\"]? +(selected)? *>([[:alnum:]]+)</option>#", $val1, $regs, PREG_SET_ORDER);
				$string=$string."<select $stylekey name=\"$option1\" onChange=\"document.getElementById('$nom_form').submit()\">$val1</select>";
				$pOptions .= "@select=$option1{";
				$selectText = "";
				foreach($regs as $n => $reg)
				{
					$s = $reg[2] ? "s":"";
					$pOptions .= "{$reg[1]}:$s:{$reg[3]};";
					if($s) $selectText = $reg[3];
				}
				$pOptions = preg_replace("#;$#", "", $pOptions);
				$pOptions .= "}:$selectText:&";
// 				echo "\n\n<br>'$pOptions'";
			}
			else 
			{
				$string=$string."\n\t<input type=\"hidden\" name=\"$option1\" value=\"$val1\">";
				$pOptions .= "$option1=$val1&";
			}
			if($keep_val)
			{
				$act_form["option1"] = $option1;
				$act_form["val1"] = $val1;
			}
		}
		for($nx=2;$nx<11;$nx++) //conserver les valeurs du formulaire dans un tableau séparé.
		{
			$opname="option".$nx;
			$opx=$$opname;
			$valname="val".$nx;
			$valx=$$valname;
// 			echo "\n<br>value: $opx $valx";
			if($opx!="")
			{
				if($opx === 'aPost')
				{
					foreach ($valx as $a => $b)
					{
						$pOptions .= "$a=$b&";
						$string=$string."\n\t<input type=\"hidden\" name=\"$a\" value=\"$b\">";
					}
				}
				else
				{
					if($valx === '###') $valx = $_REQUEST[$opx];
					$pOptions .= "$opx=$valx&";
					$string=$string."\n\t<input type=\"hidden\" name=\"$opx\" value=\"$valx\">";
					if($keep_val)
					{
						$act_form["$opname"] = $opx;
						$act_form["$valname"] = $valx;
					}
				}
			}
		}
		if(isset($this->form_global_var)) //mettre dans le formulaire les options générales, utiles par exemple pour que les pieds de page intègrent automatiquement toutes les variables de page.
		{
			$string .= $this->addGeneralOptions($exclusion);
			$pOptions .= $this->addGeneralOptions($exclusion, True);
		}
		if($keep_val) $this->arr_forms[]=$act_form;
		if(isset($this->forward_test))
		{
			$pOptions .= "last_test={$this->last_test}";
			$string .= "\n\t<input type=\"hidden\" name=\"last_test\" value=\"{$this->last_test}\">";
		}
		$string=$string."\n</form>";
		if($td) $string .= $end;
		if($setPda) $string = "<pda>$string";
		$pOptions = preg_replace("#\?$#", "", $pOptions);
		if($pOptions) $pString .= "?$pOptions";
		$pString .= "|$target||$nomUtf|$accesskey";
		if($this->prolawyerClientForm) return ($pString);
		if($this->setXHR)
		{
// 			$pOptions = substr($pOptions, 5); //suppression du premier &
			$pOptions .= "&amp;solo";
			$string = "<a style=\"cursor:pointer\" $stylekey name=\"$nom_form\" id=\"$nom_form\" onclick=\"sendData('$pOptions', 'on', '$url', 'POST', 'center')\">$nom</a>";
			if($td) $string = "<td>$string</td>";
		}
		if($this->setSpecial)
		{
// 			$pOptions = substr($pOptions, 5); //suppression du premier &
			$pOptions .= "&solo";
			$string = "<a style=\"cursor:pointer\" $stylekey name=\"$nom_form\" id=\"$nom_form\" onclick=\"sendData('$pOptions', 'on', '$url', 'POST', '{$this->setSpecial}')\">$nom</a>";
			if($td) $string = "<td>$string</td>";
		}
		return $string;
	}
	
	function span($text, $class="")
	{
		if($class) $class = "class=$class";
		$string = "<span $class>$text</span>";
		$nomUtf = html_entity_decode($text, ENT_COMPAT|ENT_HTML401, "UTF-8");
		$pString = "|||$nomUtf|";
		if($this->prolawyerClientForm) return ($pString);
		else return $string;
	}
	
	function addGeneralOptions($exclusion, $prolawyer = False)
	{
		
		if(isset($this->exclusion)) foreach($this->exclusion as $var) $exclusion[]=$var;		
		foreach($this->form_global_var as $vkey => $vval)
		{
			if(!in_array($vkey, $exclusion))
			{
				if(is_array($vval))
				{
					$vname = $this->form_global_var["$vkey"];
					foreach ($vval as $subkey => $subval)
					{
						if($prolawyer) $string .= "{$vkey}[$subkey]=$subval&";
						else $string .= "<input type=\"hidden\" name=\"{$vkey}[$subkey]\" value=\"$subval\">";
					}
				}
				else
				{
					if($prolawyer) $string .= "$vkey=$vval&";
					else $string .= "<input type=\"hidden\" name=\"$vkey\" value=\"$vval\">";
				}
			}
		}
		return $string;
	}

	function table_open($options = "")
	{
		$return="\n";
		if(!isset($this->table_level)) $this->table_level = "0";
		$this->table_level ++;
		for($n=1;$n<$this->table_level; $n++) $return .= "\t";
		$name = (!preg_match("#name#i", $options)) ? "name=\"table_".$this->table_level."\"" : "";
		$return .= "<table $options $name>\n";
		for($n=0;$n<$this->table_level; $n++) $return .= "\t";
		return $return;
	}
	
	function table_close($name="UNDEFINED")
	{
		$return = "\n";
		for($n=1;$n<$this->table_level; $n++) $return .= "\t";
// 		$name="table_".$this->table_level;
 		$return .= "</table><!--- name: $name -->\n";
		for($n=1;$n<$this->table_level; $n++) $return .= "\t";
		$this->table_level --;
		return $return;
	}
	
	function find_accesskey($string, $accesskey)
	{
		if(preg_match("#\*#", "$accesskey")) $accesskey="no_key";
		$test=0;
		$suspend = FALSE;
		$string2="";
		for($x=0, $y=0;;$x++, $y++)
		{
			if($x >= strlen($string)) break;
			$char1=substr($string, $x, 1);
			$arr=array($accesskey, strtolower($accesskey), strtoupper($accesskey));
			foreach ($arr as $val)
			{
				//echo "'$accesskey' '$char1'<br>";
				//if($char1 == "&" || $char1 == ">" || $char1 == "<") $suspend=TRUE;
				if(in_array($char1, array('>', '<', '-', '+', '&'))) $suspend=TRUE;
				if(!$suspend) $char2=preg_replace("#$val#", "<u>$val</u>", "$char1");
				else $char2 = $char1;
				if($char1 == ";") $suspend=FALSE;
				if($char1!=$char2 AND $test==0)
				{
					$test=1;
					$char1=$char2;
					break;
				}
			}
			$string2=$string2.$char1;
		}
		return $string2;
	}

	function color_select($onclick="", $onmouseover="", $choisi="", $id="", $id2="", $escape=TRUE, $referer=FALSE, $submit=FALSE)
	{
		$choisi=trim($choisi);
		if(!preg_match("/#/", $choisi)) $choisi = "#".$choisi;	
		$choisi=strtoupper($choisi);
		if($referer) $width="100%";
		else $width="40";
		$string="<table width=\"$width\" border=1 cellspacing=0 cellpading=0>\n\t<tr>";
		for($r=0, $g=0, $b=0, $compteur = 1; $r < 5; $b++, $compteur ++)
		{
			if($b == 5)
			{
				$b=0;
				$g ++;
			}
			if($g == 5)
			{
				$g=0;
				$r ++;
			}
			$color["cur"]="#";
			$color["ccur"]="#";
			foreach(array("cur", "ccur") as $etat)
			{
				if($etat == "cur")
				{
					$array=array($r, $g, $b);
				}else{
					$array=array(4-$r, 4-$g, 4-$b);
					foreach($array as $rang =>$val)
					{
						if($val == "2") $array["$rang"] = "0";
					}
				}
				foreach($array as $cur)
				{
					if($cur <3) $color["$etat"] .= 4 * $cur;
					elseif($cur == 3) $color["$etat"] .= "C";
					elseif($cur == 4) $color["$etat"] .= "FF";
					if($cur != 4) $color["$etat"] .= "0";
				}
			}
		if($onmouseover)
		{
			if($escape) $onover="onMouseover=\"show(\\'{$color["cur"]}\\')\" onMouseout=\"hide()\"";
			else $onover="onMouseover=\"show('{$color["cur"]}')\" onMouseout=\"hide()\"";
		}
		if($onclick)
		{
			$add_submit=($submit) ? ";window.opener.document.getElementById('$submit').submit()" : "";
			if(!$referer) $oncli = "onClick=\"hideandselect(\\'{$color["cur"]}\\')\"";
			else $oncli = "onClick=\"window.opener.document.getElementById('$referer').value='{$color["cur"]}';window.opener.document.getElementById('$referer').style.backgroundColor='{$color["cur"]}'$add_submit;self.close()\"";
		}
		else $onover="";
		if($color["cur"] == $choisi)
		{
			$texte="<li>x</li>";
//			echo "tralala";
		}
//		else $texte=$color["cur"];
		else $texte="&nbsp;&nbsp;";
//		$string .= "<td color = \"{$color["ccur"]}\" bgcolor = \"{$color["cur"]}\" $onover $oncli>$texte</td>";
		if($color["cur"] == $choisi) $string .= "<td style=color:{$color["cur"]};background-color:{$color["ccur"]};font-size:5;cursor:pointer $onover $oncli>$texte</td>";
		else $string .= "<td style=color:{$color["ccur"]};background-color:{$color["cur"]};font-size:8;cursor:pointer $onover $oncli>$texte</td>";
		if($compteur == 25)
		{
			$compteur = 0;
			$string .= "</tr>\n\t<tr>";
		}
		if($color["cur"] == "#FFFFFF") break;
		}
		$string .= "</tr>\n</table>";
	return $string;
	}

	
	function infobulle($text, $position="NULL")
	{
		$position = $position =! "NULL" ? ", '$position'":"";
		$text=addslashes(stripslashes($text));
		$options_infobulle="onMouseover=\"show('$text' $position)\" onMouseout=\"hide()\"";
		return $options_infobulle;
	}
	
	function qui_fait_quoi($np="", $nple="", $mp="", $mple="", $date_format="%c")
	{
		$nple=($nple && $nple != "0000-00-00")?$nple:"";
		$mple=($mple && $mple != "0000-00-00")?$mple:"";
		//$nple=($nple && $nple != "0000-00-00")?$this->mysql_to_print("$nple", "%d.%m.%Y"):"";
		//$mple=($mple && $mple != "0000-00-00")?$this->mysql_to_print("$mple", "%d.%m.%Y"):"";
/*		if($np!="") $np="{$this->lang["general_np"]} $np";
		if($mp!="") $mp="{$this->lang["general_mp"]} $mp";
		if($nple!="") $nple=" ($nple)";
		if($mple!="") $mple=" ($mple)";
		if($np !="" AND $mp !="") $semicolon=" ; ";
		$texte=$np.$nple.$semicolon.$mp.$mple;*/
		if($this->newPdaMenu)
		{
			$options_infobulle="onClick=\"shi('$np','$nple','$mp','$mple')\"";
		}else{
			$options_infobulle="onMouseover=\"shi('$np','$nple','$mp','$mple')\" onMouseout=\"hide()\"";
		}
		return $options_infobulle;
	}

	

	//divers
	function univ_strftime($param, $time="") //hélas, il y a des gens qui utilisent cette cochonnerie de windows qui ne gère pas une bonne partie des options de strftime...
	{
		$temp_percent="temppourcent";
		$return="";
		if(!$time) $time=time();
		$idem=array("a","A","b","B","c","d","H","I","j","m","M","p","S","s", "U","W","w","x","X","y","Y","Z","z");
		$param=preg_replace("#%%#", $temp_percent, $param);
		$arr_param = explode("%", $param);
		foreach($arr_param as $line)
		{
			if(trim($line) != "")
			{
				$lparam=trim(substr($line, 0, 1));
				if(in_array($lparam, $idem))
				{
					$return .= strftime("%$lparam", $time);
				}
				elseif($lparam == "C")
				{
					$annee = strftime("%Y", $time);
					$ansim = strftime("%y", $time);
					if(substr($ansim, 0, 1) == "0") $ansim = substr($ansim, 1);
					if(substr($ansim, 0, 1) == "0") $ansim = substr($ansim, 1);
					$soustr = $annee - $ansim;
					$soustr = ($annee - fmod($annee, 100)) / 100;
					$return .= $soustr;
					
				}
				elseif($lparam == "D")
				{
					$return .= strftime("%m/%d/%y", $time);
					
				}
				elseif($lparam == "e")
				{
					$day=strftime("%d", $time);
					if($day < 10) $day = " ".substr($day, 1);
					$return .= $day;
					
				}
				elseif($lparam == "h")
				{
					$return .= strftime("%b", $time);
					
				}
				elseif($lparam == "n")
				{
					$return .= "\n";
					
				}
				elseif($lparam == "r")
				{
					$m = "AM";
					$heure = strftime("%H", $time);
					$minute = strftime("%M", $time);
					$seconde = strftime("%S", $time);
					if($heure > 12)
					{
						$heure = $heure -12;
						$m = "PM";
					}
					if($heure == "12") $m = "PM";
					if($heure == "00")
					{
						$heure = "12";
					}
					$return .= "$heure:$minute:$seconde $m";
				}
				elseif($lparam == "R")
				{
					$return .= strftime("%H:%M", $time);
					
				}
				elseif($lparam == "T")
				{
					$return .= strftime("%H:%M:%S", $time);
					
				}
				elseif($lparam == "t")
				{
					$return .= "\t";
					
				}
				elseif($lparam == "V")
				{
					$return .= strftime("%U", $time);
					
				}
				elseif($lparam == "u")
				{
					$jnum = strftime("%w", $time);
					if($jnum == 0) $jnum = 7;
					$return .= $jnum;
					
				}else{
					$return .= "";
				}
				$return .= substr($line, 1);
			}
		}
		$return=preg_replace("#$temp_percent#", "%", $return);
// 		$return = trim($return);
		if(preg_match("#Ã#", $return)) $return=utf8_decode($return);
		return $return; 
	}
	
	function tab_affiche($arr="RienDeDefini", $sup="", $level=0) //fonction de contrôle plus jolie (retours à la ligne, indentation) que print_r
	{
		//passer $this->do_return à True hors de la fonction pour forcer un return de la valeur plutôt qu'un affichage.
// 		if(!isset($arr)) return();
		if(!isset($this->do_return)) $this->do_return = False;
		if(!isset($this->do_return1)) $this->do_return1 = False;
		if($this->do_return && $level ==0) $this->ret="";
		$terminate = False;
		if($arr==="RienDeDefini" || $arr === 0)
		{
			$arr=$_POST;
			$echoInit = "<br><b>POST values: </b>";
		}
		elseif($arr === 1)
		{
			$arr=$_GET;
			$echoInit = "<br><b>GET values: </b>";
		}
		elseif($arr === 2)
		{
			$arr=$_SESSION;
			$echoInit = "<br><b>SESSION values: </b>";
		}
		elseif($arr === 3)
		{
			$arr=$_COOKIE;
			$echoInit = "<br><b>COOKIE values: </b>";
		}
		elseif($arr === 4)
		{
			$arr=$_REQUEST;
			$echoInit = "<br><b>REQUEST values: </b>";
		}
		elseif($arr === 5)
		{
			$arr=$_SERVER;
			$echoInit = "<br><b>SERVER values: </b>";
		}
		elseif($arr === "?" || $arr === "help" || $arr === "aide")
		{
			$echoInit = "<br><b>USAGE: </b>\n<br>0 => POST\n<br>1 => GET\n<br>2 => SESSION\n<br>3 => COOKIE\n<br>4 => REQUEST\n<br>5 => SERVER";
			$terminate = True;
		}
// 		$echoInit = "'$arr', '$sup', '$level'";
		elseif((!is_array($arr)) && $level == 0)
		{
			
			$echoInit = ("<br><br>".var_export($arr, True)." n'est pas un tableau");
			$terminate = True;
		}
		elseif(empty($arr) && $level == 0)
		{
			
			$echoInit = ("<br><br>Tableau vide");
			$terminate = True;
		}
		
		if ($this->do_return1) $this->ret .= $echoInit;
		elseif ($this->do_return) $this->ret .= $echoInit;
		else echo $echoInit;
		if($terminate) return $this->ret;
			
		foreach($arr as $key=>$elem)
		{
			if($this->do_return1) $plustab = "   ";
			else $plustab = "&nbsp;&nbsp;&nbsp;";
			$tab="";
			for($compt=0;$compt<$level;$compt++) $tab .= $plustab;
			if(is_array($elem)) {
				if($this->do_return1) $this->ret .= "\n$tab $sup [$key] a pour valeur '[$key]'";
				elseif($this->do_return) $this->ret .= "\n<br>$tab $sup [$key] a pour valeur '[$key]'";
				else echo "\n<br>$tab $sup [$key] a pour valeur '[$key]'";
				$elem=ksort($arr[$key]);
				$oldsup=$sup;
				$sup .= " [$key]";
				$level++;
				$this->tab_affiche($arr[$key], $sup, $level);
				$level--;
				$sup=$oldsup;
			}
			else
			{
				if($this->do_return1) $this->ret .= "\n$tab $sup [$key] a pour valeur '$elem'";
				elseif($this->do_return) $this->ret .= "\n<br>$tab $sup [$key] a pour valeur '$elem'";
				else echo "\n<br>$tab $sup [$key] a pour valeur '$elem'";
			}
		}
		if($level == 0)
		{
			if($this->do_return1)
			{
				$this->ret .= "\n";
				return $this->ret;
			}
			elseif($this->do_return)
			{
				$this->ret .= "\n<br><br>";
				return $this->ret;
			}
			else echo "\n<br><br>";
		}
	}
	
	function computerFormat($n)
	{
		$us = ["O", "K", "M", "G", "T"];
		$i = 0;
		while($n > 1000)
		{
			$n = round($n) / 1000;
			$i ++;
		}
		
		return number_format($n, 3, ".", "'") . $us[$i];
	}
	
	function beautifyMysql($string, $div=false, $tmp = false, $mode = 3)
	{
		$string .= ($string && ! preg_match("#;$#", trim($string))) ? ";":"";
		$string = preg_replace("#=#", "====", $string);
		$rand = rand(0, 32767);
// 		echo "tmp = '$tmp'";
		$mef = $div ? "border-style:solid;border-width:1;":"";
		$output = "\n<br>";
		$level = 0;
		for($n = 0; $n < strlen($string); $n++)
		{
			$ch = substr($string, $n, 1);
			if(!in_array($ch, array("(", ")"))) $output .= $ch;
			elseif($ch == '(')
			{
				$level ++;
				$levPad = $level * 2;
				$output .= "(\n<br><div style='padding-left:{$levPad}em;$mef'>";
			}
			elseif($ch == ')')
			{
				$level --;
				$output .= "</div>\n)	";
			}
		}
		$string = preg_replace("#====#", "=", $string);
		$output = preg_replace("#====#", "<span style='color:green'> = </span>", $output);
		$output = preg_replace("#(AND +|OR +)#", "\\1\n<br>", $output);
		$output = preg_replace_callback("#(select|insert into|update|delete from) #i", function($m){return "<span style='color:red'>".strtoupper($m[1]) ."</span>\n<br><div style='padding-left:1em;$mef'>";}, $output);
		$output = preg_replace_callback("# (set|where|order by|group by|from|limit|(union( all)?)) #i", function($m){$last = (strtoupper($m[1]) == "UNION" || strtoupper($m[1]) == "UNION ALL") ? "<div>":"<div style='padding-left:1em;$mef'>";return "</div><span style='color:red'>".strtoupper($m[1]) ."</span>\n<br>$last";}, $output);
		$output = preg_replace_callback("#( ((left( +inner|outer)? +join)|(as( +\\W))|on) )#i", function($m){return "<span style='color:blue'> ".strtoupper($m[1]) ." </span>";}, $output);
		$output .= "</div>";
		$output = preg_replace("#(,[^'])#", "\\1<br>", $output);
		$clickable = "<br><br><span id=textbox$rand style='color:blue' onclick='var el = document.getElementById(\"textbox$rand\");var range = document.createRange();range.selectNodeContents(el);var sel = window.getSelection();sel.removeAllRanges();sel.addRange(range);'>$string</span><br><br>";
// 		echo "tmp = '$tmp'";
		if($tmp)
		{
// 			echo "ouverture";
			$file = "/tmp/mysql";
			$f = fopen($file, "w");
			$w = fwrite($f, $string);
			$c = fclose($f);
		}
		$ret ="";
		if ($mode & 10) $ret .= $output;
		if ($mode & 1) $ret .= $clickable;
		return "$ret";
	}

	
	function mysql_to_print($mysql_date, $date_format="%d.%m.%Y")
	{
		if( ! preg_match("#[0-9]+-[0-9]+-[0-9]+#", $mysql_date)) return "";
		list($annee_n, $mois_n, $jour_n)=preg_split("#-#", $mysql_date);
		$tmstmp_n=mktime(0, 0, 0, $mois_n, $jour_n, $annee_n);
		$date_print=strftime("$date_format", $tmstmp_n);
		#$date_print=strftime("%x", $tmstmp_n);
		return $date_print;
	}
	
	function mtf_date($date = "") //$date format= yyyy-mm-date
	{
		if($date != "")
		{
			list($year, $month, $day) = preg_split("#-#", $date);
			$timestamp=mktime(1, 0, 0, $month, $day, $year);
		}
		else $timestamp = time();
		return $timestamp;
	}
	
	function date_mtf($timestamp = "")
	{
		if($timestamp == "")
		{
			$timestamp = time();
		}
		$mysql_date=strftime("%Y-%m-%d", $timestamp);
		return $mysql_date;

	}
	
	function checkDate($d1, $mois = "POST", $annee=0)
	{
		if($mois === "POST" || $mois === "GET" || $mois === "REQUEST")
		{
			if($mois == "POST") $val = $_POST;
			if($mois == "GET") $val = $_GET;
			if($mois == "REQUEST") $val = $_REQUEST;
			$jour  = $val["jour$d1"];
			$mois  = $val["mois$d1"];
			$annee = $val["annee$d1"];
		}
		else $jour = $d1;
		
		if(strlen($annee) == 3) return false;

		$q = "select date_add('$annee-$mois-$jour', interval 0 day) as d";
		$e = mysqli_query($this->mysqli, $q);
		while ($r = mysqli_fetch_array($e)) list($anneeCom, $moisCom, $jourCom) = preg_split("/-/", $r["d"]);
// 		print "<br>$jour.$mois.$annee: $jourCom.$moisCom.$anneeCom";
		if($jour + 0 == 0 && $mois + 0 == 0 && $annee + 0 == 0) return -1;
		elseif($jour + 0 == $jourCom + 0 && $mois + 0 == $moisCom + 0 && $annee + 0 == $anneeCom + 0) return 1;
		else return 0;
	}
	
	function getImageName($name, $compPath=false)
	{
		$name = html_entity_decode($name);
		$name = $this->no_accent($name);
		$name = preg_replace("#\'#", "_", $name);
		$name = preg_replace("# #", "_", $name);
		#if($compPath) $name = "{$this->settings["root"]}images/auto/$name.png";
		if($compPath) $name = "{$this->settings["root"]}images/auto/$name.png";
		return($name);
	}
	
	function getImage($name, $size="big", $path=false)
	{
		$compImgName = $this->getImageName($name, true);
		if(is_file("$compImgName"))
		{
			return "<img src=\"$compImgName\">";
		}
		else
		{
// 			return "'$compImgName' n'existe pas";
			if($size == "small")
			{
				$color = "&color={$this->smallColor}";
				$fontSize = "&fontsize={$this->smallFontSize}";
			}
			elseif($size == "big")
			{
				$color = "&color={$this->bigColor}";
				$fontSize = "&fontsize={$this->bigFontSize}";
			}
			if(preg_match("#,#", $size))
			{
				$color = "&color={$this->bigColor}";
				$fontSize = "&fontsize={$this->bigFontSize}";
				list($fSize,$fColor) = preg_split("#,#", $size);
				$fSize = trim($fSize);
				$fColor= trim($fColor);
				if($fSize) $fontSize="&fontsize=$fSize";
				if($fColor) $color="&color=$fColor";
// 				echo "<br>$color";
			}
			
			if($path) return "<img src=\"{$this->settings["root"]}image.php?save=true&nom=$name&$path\">";
			else return "<img src=\"{$this->settings["root"]}image.php?save=true&nom=$name&type=hor$color&bgcolor={$this->bgColor}{$fontSize}{$this->sHeight}\">";
		}
	}
	
	function getClickableItem($design, $val, $int=True)
	{
		if(!isset($this->globalTelRacc)) $this->globalTelRacc = 0;
		if(!isset($this->listeNumbers)) $this->listeNumbers = array();
		//if($design == "rem") return(array("val" => $this->smart_html($val), "sup" => "", "class" => ""));
// 		$regex = '#((\(?0[1-9][0-9]([-. /:()]*[0-9][-. /:()]*){7})|((\+|(00))?([-. /:()]*[0-9][-. /:()]*){8,13}))#';
		$suissereg = "\(?(0)[1-9][0-9]([-. /:()]*[0-9][-. /:()]*){7}";
		$suissereg = "(\(?)0([1-9][0-9])(([-. /:()]*[0-9][-. /:()]*){7})";
		$regex = "(($suissereg)|((\+|(00))?([-. /:()]*[0-9][-. /:()]*){8,13}))";
// 		echo "<br>'$regex' '$suisse'";
// 		if(preg_match("/^(tel|natel|fax)/", $design)||(preg_match("#^other[0-9]+$#", $design) && preg_match("#$regex#", $val, $det)))
		if((preg_match("/^(tel|natel|fax)/", $design)||preg_match("#^other[0-9]+$#", $design)) && preg_match("#$regex#", $val, $det))
		{
			$numero = $det[1];
// 			echo "<br>Numéro: $numero";
			$valInit = $val;
			$prefix = "";
			$found = preg_match("#$regex#", $val, $reg);
			$val =  $reg[0];
// 			echo "$val = $numero";
			$val = preg_replace("#[^0-9+]#", "", $val);
			$suisse = preg_match("#$suissereg#", $numero) ? True:False;
			if(!$val)
			{
				$class = "";
				$sup = "<img src=\"{$this->settings["root"]}images/empty.png\">";
			}
			elseif(strlen($val) <10 || ! preg_match('#[0-9. /\-+:()]{10}#', $val))
			{
				$class = "class=\"attention\"";
				$sup = "<span><img src=\"{$this->settings["root"]}images/empty.png\"></span>";
			}
			else
			{
				//echo "'$val'";
				$valSansPlus = preg_replace("/^\+/", "00", $val);
				if($this->pdaSet)
				{
					$class = "style=\"cursor:pointer\"";
					$sup = "<span id=\"img{$design}\"><a href='tel:$valSansPlus'><img src=\"{$this->settings["root"]}images/tel.png\"></a></span>";
				}
				else
				{
					$this->globalTelRacc ++;
					$class = "style=\"cursor:pointer\" onmouseover='show(\"callto:TOREPLACE\")' onmouseout='hide()' onclick=\"XHR = new XHRConnection();data='callback';XHR.appendData(data, 'on');XHR.appendData('number', '$valSansPlus');XHR.appendData('path', '{$this->settings["root"]}');XHR.appendData('ip', '{$_SERVER["REMOTE_ADDR"]}');XHR.appendData(div, 'on');div='img{$design}';XHR.sendAndLoad( '{$this->settings["root"]}redirect.php', 'GET', remplirChamp)\"";
					$sup = "<span id=\"img{$design}\" style='white-space: nowrap'><span style='border-radius:50%;border:2px solid blue'>{$this->globalTelRacc}</span><img accesskey='{$this->globalTelRacc}' src=\"{$this->settings["root"]}images/telr.png\"></span>";
				}
			}
			if($suisse) 
			{
// 				echo "<br>Val suisse: $val =>";
// 				if ($int) $val = "+ 41 ".substr($val, 1, 2) . " / " .substr($val, 3, 3) . " " . substr($val, 6, 2) . " " . substr($val, 8, 2);
				if ($int) $val = preg_replace("#$suissereg#", "\\1+41\\2\\3", $val);
				else      $val = substr($val, 0, 3) . " / " .substr($val, 3, 3) . " " . substr($val, 6, 2) . " " . substr($val, 8, 2);
// 				echo "$val";
			}
			if(preg_match("/^(\+|00)([0-9]+)/", $val, $reg))
			{
				$val = "+" . $reg[2];
				$indicatifs = array
				(
					"1", //USA et Canada // XXX CCC-CCCC //10
					"7", //Russie // CCC CCC CC CC //10
					"20", //Egypte // CC CC CC CC //8
					"27", //Afrique du Sud // CC CCC CC CC //9
					"30", //Grèce // CCC CCCCCCC //10
					"31", //Pays-Bas // CC CCC CC CC //9
					"32", //Belgique  // ZZ(C) CC CC CC //8-9
					"33", //France // C CC CC CC CC //9
					"34", //Espagne // CCC.CC.CC.CC //9
					"36", //Hongrie // CC CCC CCC(C) //8-9
					"39", //Italie // CC CCCC CCCC //10
					"40", //Roumanie // CC CCC CC CC //9
					"41", //Suisse // CC CCC CC CC //9
					"43", //Autriche // C CCC CCC //7
					"44", //Royaume-Uni
					"45", //Danemark // CC CC CC CC //8
					"46", //Suède // C CC CC CCC //8
					"47", //Norvège // CC CC CC CC //8
					"48", //Pologne // CC CCC CC CC //9
					"49", //Allemagne // CCC CCC CC CCC //11
					"51", //Pérou // (C)CC CCC CCC //8-9
					"52", //Mexique // CC CC CC CC CC //10
					"53", //Cuba
					"54", //Argentine
					"55", //Brésil // CC CCCC-CCCC
					"56", //Chili // CCC CCC CCC
					"57", //Colombie
					"58", //Venezuela // CCC CCC CC CC
					"60", //Malaisie
					"61", //Australie // X CCCC CCCC
					"62", //Indonésie
					"63", //Philippines
					"64", //Nouvelle-Zélande // C CCC CC CC
					"65", //Singapour // CC CC CC CC
					"66", //Thäilande
					"81", //Japon // C CCCC CCCC
					"82", //Corée du Sud
					"84", //Viêt Nam
					"86", //Chine // XX(X).CCCC.CCCC
					"90", //Turquie // CCC CCC CCCC
					"91", //Inde // \(CC\) CC CC CC CC
					"92", //Pakistan // (CC) CCC CC CC
					"93", //Afghanistan // CC CCC CCCC
					"94", //Sri Lanka // CC CCC CC CC
					"95", //Birmanie
					"98", //Iran // \(CC\) CC CC CC CC
				);
				$indicatifs3 = array
				(
					"213", //Algérie // CCCC CCCC
					"215", //Maroc // C CC CC CC CC
					"216", //Tunisie // CC CCC CCC
					"221", //Sénégal // CCXXXXXXX
					"223", //Mali // CC CC CC CC
					"227", //Niger // CC CC CC CC
					"228", //Togo // XX XX XX XX
					"229", //Bénin // CC CC CC CC
					"230", //Maurice // (C)CCC CC CC
					"235", //Tchad // XX XXXXXX
					"237", //Caméroun // CCC CC CC CC
					"238", //Cap vert // CCC CC CC
					"242", //Congo // ********
					"243", //République démocratique du Congo // *********
					"261", //Madagascar // AA XXXXXXX
					"262", //Réunion et Mayotte // CCC CC CC CC
					"291", //Erythrée // CCC CC CC CC
					"351", //Portugal // CCC CCC CCC
					"352", //Luxembourg // CC CC CC
					"353", //Irlande //
					"355", //Albanie
					"358", //Finlande // CC CCC CCCC
					"371", //Lettonie // CCCC CCCC
					"372", //Estonie // CCCC C(CCC)
					"375", //Biélorussie // CC CCC CC CC
					"377", //Monaco // CC CC CC CC
					"381", //Serbie // CC CCC CC CC
					"382", //Monténégro // CC CCC CCC
					"385", //Croatie // C CC CCCCC
					"389", //Macédoine // C CCC CCCC
					"420", //République Tchèque // CCC CCC CCC
					"421", //Slovaquie // CCC CCCCCC
					"423", //Liechtenstein
					"509", //Haïti // CCCC CCCC
					"590", //Guadeloupe // CCC CC CC CC
					"594", //Guyane // CCC CC CC CC
					"596", //Martinique // CCC CC CC CC
					"687", //Nouvelle-Calédonie // CCC CC CC CC
					"852", //Hong-Kong // CC.CC.CC.CC
					"853", //Macao // CC.CC.CC.CC
					"855", //Cambodge // CC CCC CCC
					"961", //Liban // C CC CC CC
					"962", //Jordanie // C CCC CC CC
					"972", //Israël // C CCC(C) CCCC
				);
				foreach($indicatifs as $indicatif) if(preg_match("/(\+$indicatif)([0-9]+)/", $val, $reg))
				{
					$prefix .= $reg[1];
					$val = $reg[2];
					$pasTrois = true;
					break;
				}
				if(!$pasTrois)
				{
					foreach($indicatifs3 as $indicatif) if(preg_match("/(\+$indicatif)([0-9]+)/", $val, $reg))
					{
	// 					preg_match("/(\+[0-9]{3})([0-9]+)/", $val, $reg);
						$prefix .= $reg[1];
						$val = $reg[2];
						break;
					}
				}
				
				if($prefix == "+33") $val = preg_replace("/([0-9]{1})([0-9]{2})([0-9]{2})([0-9]{2})([0-9]{2})/", "$prefix \\1 \\2 \\3 \\4 \\5", $val);
				elseif($prefix == "+41") $val = preg_replace("/([0-9]{2})([0-9]{3})([0-9]{2})([0-9]{2})/", "$prefix \\1 \\2 \\3 \\4", $val);
				else
				{
					$string = $val;
					$val = "$prefix";
					for($x=0;$x<strlen($string) + 2; $x +=3)
					{
						$val .= " " . substr($string, $x, 3);
					}
				}
			}
			$class = preg_replace('#TOREPLACE#', "$val", $class);
			if(preg_match("#^other[0-9]+$#", $design))
			{
// 				if(preg_match('#^(.+)([0-9][. /\-+:()]*{10})#', $valInit, $rf))
				$cat = preg_match("#^([^:]+)(:)$regex#", $valInit) ? preg_replace("#^([^:]+)(:)$regex#", "\\1", $valInit):"(Inconu)";
// 				echo "<br>$cat: $val ($suisse)";
				$this->listeNumbers[] = array($cat =>$val);
			}

		}
		elseif(preg_match("/(^mail)/", $design) || preg_match("/[a-zA-Z0-9._\-]+@[a-zA-Z0-9_\-]+.[a-zA-Z]/", $val))
		{
			if($val)
			{
				$this->globalTelRacc ++;
				$sup = " <span style='white-space: nowrap'><span style='border-radius:50%;border:2px solid blue'>{$this->globalTelRacc}</span><a accesskey='{$this->globalTelRacc}' href=\"mailto:$val\"><img style=\"border:none\" valign=bottom src=\"{$this->settings["root"]}images/courriel.png\"></a>";
			}
			else
			{
				$sup = "<img src=\"{$this->settings["root"]}images/empty.png\"></span>";
			}
		}
		elseif($design == "nosociete")
		{//http://prolawyer/modules/recherche/zefix.php?CHid=CH55010642268&persid=&id=6049&majPage=http://prolawyer/maj_op.php
			$sup = $val ? "<a href=\"#\" onClick=\"window.open('{$this->zefixPath}?searchprovider=zefix&CHid=$val&id=$int','modifier','width={$this->zefixWidth},height={$this->zefixHeight},toolbar=no,directories=no,menubar=no,location=no,status=no,resizable=yes,scrollbars=yes')\"><img style=\"border:none\" valign=bottom src=\"{$this->settings["root"]}images/rc.png\"></a>":"<img src=\"{$this->settings["root"]}images/empty.png\">";
// 			$sup = $val ? "<a href=\"{$this->settings["root"]}modules/recherche/zefix.php?norc=$val\" target=\"_new\"><img style=\"border:none\" valign=bottom src=\"{$this->settings["root"]}images/rc.png\"></a>":"<img src=\"{$this->settings["root"]}images/empty.png\">";
		}
		
		$val = $this->smart_html($val);
		
		$return["sup"] = $sup;
		$return["val"] = strtr($val, chr(146), "'");
		$return["class"] = $class;
		return $return;
	}

	function swissToInt($number)
	{ 
		return;
		//echo "<br>Avant: $number. Apres: ";
		$number = preg_replace("#0([0-9]{2}) / ([0-9]{3} [0-9]{2} [0-9]{2})#", "+41 \\1 \\2", $number);
		//echo $number;
		return $number;
	}

	function mimeGet($file)
	{
		$m = "";
		preg_match("#\.([^.]+$)#", $_POST["fichier"], $reg);
		$ext = $reg[1];
		if($ext == "txt") $m = "text/plain";
		elseif($ext == "rtf") $m = "text/rtf";
		elseif($ext == "odt") $m = "application/vnd.oasis.opendocument.text";
		elseif($ext == "ott") $m = "application/vnd.oasis.opendocument.text-template";
		elseif($ext == "sxw") $m = "application/vnd.sun.xml.writer";
		elseif($ext == "stw") $m = "application/vnd.sun.xml.writer.template";
		if($m) return $m;
		if(function_exists(finfo_file) && function_exists(finfo_open))
		{
			$i = finfo_open(FILEINFO_MIME_TYPE);
			$m = finfo_file($i, $file);
			if ($m && $m != "application/octet-stream") return $m;
		}
		if(function_exists(mime_content_type))
		{
			$m = mime_content_type($file);
			if($m && $m != "application/octet-stream") return $m;
		}
	}
	
	function initZefix()
	{
		if(isset($_SESSION["optionsPath"]))
		{
			if(is_file("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}zefixname")) $this->zefixPath = "{$this->settings["root"]}modules/recherche/zefix.php";
			else $this->zefixPath = "http://www.prolawyer.ch/prolawyergit/modules/recherche/zefix.php";
			if(is_file("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}telsearchkey")) $this->telsearchPath = "{$this->settings["root"]}modules/recherche/zefix.php";
			else $this->telsearchPath = "http://www.prolawyer.ch/prolawyergit/modules/recherche/zefix.php";
			$this->zefixWidth = '600';
			$this->zefixHeight = '600';
		}
	}
	
	function searchBox($provider = "zefix", $nom, $dataId, $nodossier)
	{
		$pathName = "{$provider}Path";
		$path = $this->$pathName;
		$searchBox = "<a href='$path?searchprovider=$provider&searchByNameRequest=$nom&id=$dataId&nodossier=$nodossier' target='$provider'><img style=\"border:none\" valign=bottom src=\"{$this->settings["root"]}images/$provider.png\"></a>";
		return $searchBox;
// 		"<a href='{$this->zefixPath}   ?searchprovider=    zefix&searchByNameRequest=$nomRC&id=$dataId' target='telsearch'><img style=\"border:none\" valign=bottom src=\"{$this->settings["root"]}images/    zefix.png\"></a>&nbsp;
// 		<a href='{$this->telsearchPath}?searchprovider=telsearch&searchByNameRequest=$nomTS&id=$dataId' target='telsearch'><img style=\"border:none\" valign=bottom src=\"{$this->settings["root"]}images/telsearch.png\"></a>";
	}

	function searchZefix($searchTag, $critere, $active = "true", $maxSize = "100", $xml = "both")
	{
		return $this->searchProvider($searchTag, $critere, $active, $maxSize, $xml, "zefix");
	}
	
	function searchTelSearch($searchTag, $critere, $where = "q", $maxSize = "telSearchMaxSize", $xml = "both")
	{
		return $this->searchProvider($searchTag, $critere, $where, $maxSize, $xml, "telsearch");
	}
	
	function searchProvider($searchTag, $critere, $active = "true", $maxSize = "100", $xml = "both", $provider = "zefix")
	{
		$this->telSearchMaxSize = 5;
		$this->trouveTelSearch = -1;
// 		echo "tutu: $provider";
		if(!$critere)
		{
			$string = $this->lang["modules_chaine_vide"];
			echo $this->echoUniqueError("$string", 2, "mefOnly");
			echo "<br><br>";
			return array("string" => "", "error" => $string, "object" => array());
		}
// 		echo "provider: $provider";
// 		echo "\n<br>'$critere' ";
		$critere = $this->no_accent($critere);
		$critere = str_replace("'", " ", $critere);
// 		$critere = addslashes(stripslashes($critere));
// 		echo "'$critere' ";
		$critere = preg_replace("#&#", "", $critere);
		
		if(trim($critere) == "")
		{
			echo "toto";
			echo $this->echoUniqueError("Vide", 2, "mefOnly");
			return array();
		}
// 		echo "tutu: $provider";
// 		if($provider == "telsearch") $critere = rawurlencode($critere);
		
		if($provider == "telsearch")
		{
			if($_REQUEST["TelSearchId"])
			{
				list($crit, $pos) = preg_split("#/#", $critere);
				$maxSize = 1;
// 				echo "<br>liste: $crit; pos: $pos";
			}
			else
			{
				$maxSize = $this->telSearchMaxSize;
				$crit = $critere;
				if(!$_REQUEST["debut"]) $_REQUEST["debut"] = 0;
				$pos = $_REQUEST["debut"] + 1;
			}
			$crit = rawurlencode($crit);
			$requestString = "http://tel.search.ch/api/?$active=$crit&key={$this->telsearchKey}&maxnum=$maxSize&lang=fr&pos=$pos";
// 			echo "<a href='$requestString'>$requestString</a>";
 			$response = @file_get_contents($requestString) or $errors = True;
 			if($errors) //erreurs http
 			{
// 				print_r(error_get_last());
// 				$this->tab_affiche(5);
				$headers = get_headers($requestString);
				preg_match("#([0-9]{3}) (.*)#", $headers[0], $regs);
				$erCode = $regs[1];
				$erText = $regs[2];
// 				echo "headers: {$headers[0]}. Regs: $erCode: $erText";
// 				foreach($headers as $n =>$header) echo "\n<br>: $n: $header";
// 				$errors = http_response_code();
// 				$errors .= "titatu";
			}
 			if($errors)
 			{
// 				$erText = $errors;
				$this->catchError("500-002#-#<br><i>$erCode: $erText (<a href='$requestString' target='_new'>$requestString</a>)</i>", 2);
 			}
//  			echo "\n<br>req: <a target='_new' href='$requestString'>$requestString</a><br>\n";
//  			die($response);
		}
		if($provider == "zefix")
		{
			$xmlns = "xmlns=\"http://www.e-service.admin.ch/zefix/2015-06-26\"";

			$url = "http://www.e-service.admin.ch/ws-zefix-1.7/ZefixService?wsdl";
// 			$url = "http://test-e-service.fenceit.ch/ws-zefix-1.7/ZefixService?wsdl"; //Test zefix

			switch($searchTag)
			{
				case "searchByNameRequest":
					$searchCrit = "name";
					break;
				case "getByCHidFullRequest":
					$searchCrit = "chid";
					break;
				case "getByEHRAidFullRequest":
					$searchCrit = "ehraNr";
					break;
				case "getByUidFullRequest":
					$searchCrit = "uid";
					break;
				case "getByCHidDetailledRequest":
					$searchCrit = "chid";
					break;
				case "getRegistryOfficesRequest":
					$searchCrit = "registerOfficeId";
					break;
				case "getLegalFormsRequest":
					$searchCrit = "chid";
					break;
				case "getSHABMessageRequest":
					$searchCrit = "id";
					break;
			}
			
			$soap_request  = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\n"; //Commentaire: l'analyse syntaxique de vim se plante à cause du <?, que je remets donc ici...
			$soap_request .= "<Envelope xmlns=\"http://schemas.xmlsoap.org/soap/envelope/\">\n";
			$soap_request .= "   <Body>\n";
			$soap_request .= "    <$searchTag $xmlns>\n";
			if($searchTag != "getRegistryOfficesRequest" && $searchTag != "getLegalFormsRequest")
			{
			$soap_request .= "      <$searchCrit>$critere</$searchCrit>\n";
			$soap_request .= "      <active>$active</active>\n";
			$soap_request .= "      <maxSize>$maxSize</maxSize>\n";
			}
			$soap_request .= "    </$searchTag>\n";
			$soap_request .= "  </Body>\n";
			$soap_request .= "</Envelope>";

// 			die($soap_request);

			$soapAction = "http://soap.zefix.admin.ch/SearchByName";
			$soapParam = array(
				"login" => $this->zefixUser,
				"password" => $this->zefixPassword,
				"trace" => true,
				"connection_timeout" => 2,
				"uri" => $soapAction, #rajouté à fins de test
				"location" => $soapAction, #rajouté à fins de test
				"keep_alive" => false, #rajouté à fins de test,
				"cache_wsdl"=>WSDL_CACHE_NONE, #rajouté à fins de test
			);
			//$this->tab_affiche($soapParam);
// 			die($this->xmlformat($soap_request));
			
			//$client = new SoapClient($url, $soapParam);
			$client = new SoapClient(null, $soapParam);
			$response = $client->__doRequest($soap_request, $url, $soapAction, 1);
// 			die($response);
		}
		if($xml)
		{
			$s = simplexml_load_string($response);
// 			$nameSpaces = $s->getNameSpaces(true);
// 			$prefix     = array_keys($nameSpaces);
// 			$this->tab_affiche($prefix);
// // 			echo "<br>Namespaces: '$prefix'";
//  			die( $this->xmlformat($response));
			if($provider == "telsearch" && ! $errors)
			{
				$s->registerXpathNamespace('atom' , 'http://www.w3.org/2005/Atom'); //On doit enregistrer l'espace de nommage par défaut en lui attribuant un nom arbitraire (ici: atom) qui doit correspondre à l'objet fils recherché
 				$objRet = $s->xpath('//atom:entry'); //retourne la liste des enregistrements
				$numTrouv = $s->xpath('//openSearch:totalResults');
				$this->trouveTelSearch = $numTrouv[0]->__toString();
			}
			if($provider == "zefix")
			{
				//$s->registerXpathNamespace('ns2', 'http://www.ech.ch/xmlns/eCH-0010/4');
				$s->registerXpathNamespace('ns2', 'http://www.e-service.admin.ch/zefix/2015-06-26');
				//$s->registerXpathNamespace('ns3', 'http://www.e-service.admin.ch/zefix/2015-06-26');
				$s->registerXpathNamespace('ns3', 'http://www.ech.ch/xmlns/eCH-0097/2');
				//$s->registerXpathNamespace('ns4', 'http://www.ech.ch/xmlns/eCH-0097/2');
				$s->registerXpathNamespace('ns4', 'http://www.ech.ch/xmlns/eCH-0010/4');
				$s->registerXpathNamespace('openSearch', 'http://a9.com/-/spec/opensearchrss/1.0/"');
				$errors = $s->xpath('//ns2:errors');
				if($errors)
				{
					$erCode = $errors[0]->children("ns2", True)->error->code;
					$erText = $errors[0]->children("ns2", True)->error->message;
					$this->catchError("500-001#-#<br><i>$erCode:$erText</i>", 2);
				}
				else
				{
					if($searchTag == "getRegistryOfficesRequest")
					{
						//die($response);
						#$objRet = $s->xpath('//ns3:registryOffice'); //retourne la liste des enregistrements
						$objRet = $s->xpath('//ns2:registryOffice'); //retourne la liste des enregistrements
					}
					else
					{
						#$objRet = $s->xpath('//ns3:companyInfo'); //retourne la liste des enregistrements //il faut vraiment que Zefix se décide entre ns3 et ns2 !!!
						$objRet = $s->xpath('//ns2:companyInfo'); //retourne la liste des enregistrements
					}
				}
			}
			if($errors)
			{
				echo $this->echoError();
				return(array("string" => $response, "object" => array()));
 			}
// 			die($response);
			if($xml == "both") return array("string" => $response, "object" => $objRet);
			else return $objet;
		}

		return $response;
	}
	
	function xmlformat($string)
	{
		$dom = new DOMDocument('1.0');
		$dom->preserveWhiteSpace = false;
		$dom->formatOutput = true;
		$dom->loadXML($string);
		return $dom->saveXML();

	}
	
	/*Fonctions de mise en page*/
	function doTemplate($center, $top=false, $left=false)
	{
		if($_GET["solo"] || $_POST["solo"]) return $center;
		if(!$top) $top = $this->xEntete;
		if(!$left) $left = $this->xMenu;
		if (!isset($this->modele)) die ("ERROR: \$this->modele must be defined");
		$old=$this->sHeight;
		$this->sHeight="&up=true&fontsize=14&base=15&height=9";
// 		$center .= "\n<br>\n<br>"."NE PAS OUBLIER LE COMPTEUR DE VISITES A PRENDRE DANS JP.AVOCATS-CH.CH (texte inséré dans etude::doTemplate()";
		$center.="<br>{$this->debug}";
		$file=file_get_contents($this->modele);
		foreach($this->generalTemplates as $toReplace => $replacement)
		{
			$file = preg_replace("#{".$toReplace."}#", $replacement, $file);
		}
		$file=preg_replace("#{UPTEXT}#", $top, $file);
		$file=preg_replace("#{LEFTTEXT}#", $left, $file);
		$file=preg_replace("#{CENTERTEXT}#", $center, $file);
// 		$file=preg_replace("#{LOGO1}#", "<img style=\"position:relative\" id=rdaflogo src=\"{$this->settings["root"]}images/rdaf_logo7.gif\">", $file);
// 		$file=preg_replace("#{LOGO2}#", $this->getImage("Revue de droit", ",{$this->bodyColor}").$this->getImage("administratif et", ",{$this->bodyColor}").$this->getImage("de droit fiscal", ",{$this->bodyColor}"), $file);
// 		$file=preg_replace("#{LOGO2}#", "<img src=\"{$this->settings["root"]}image.php?nom=Revue de droit&up=true&bgcolor=a0a0a0&type=hor&fontsize=14&base=15&height=9\"><br><img src=\"{$this->settings["root"]}image.php?nom=Administratif et&up=true&bgcolor=a0a0a0&type=hor&fontsize=14&base=15&height=9\"><br><img src=\"{$this->settings["root"]}image.php?nom=de droit fiscal&up=true&bgcolor=a0a0a0&type=hor&fontsize=14&base=15&height=9\">", $file);
		$this->sHeight=$old;
		return $file;
	}
	
	function doSingleTemplate($template, $datas = array(), $store = "return")
	{
		if(is_file($template)) $modele=$template;
		else
		{
// 			if(isset
			$modele = "";
			$path = $this->settings["root"]."templates/html";
			$sub = ($this->pda||$this->newPdaMenu) ? "pda": "desktop";
			$sModele = "$path/$sub/$template";
			$cModele = "$path/common/$template";
			
			foreach(array("", ".html") as $ext)
			{
				if($modele) break;
				foreach(array($sModele, $cModele) as $tModele)
				{
					if(is_file($tModele.$ext))
					{
						$modele = $tModele.$ext;
						break;
					}
					else $tArray[] = $tModele.$ext;
				}
			}
			if(!$modele)
			{
				$error = "";
				foreach($tArray as $t)
				{
					if($error) $error .= " || ";
					$error .= $t;
				}
				die("missing $template in $error");
			}
		}
		$file=file_get_contents($modele);
		foreach($datas as $needle => $replacement)
		{
// 			echo "<br>Replacing $needle by $replacement";
			$file=preg_replace("#{{$needle}}#", $replacement, $file);
		}
		if($store == "return") return $file;
		else
		{
			if($store) $store = $template;
			$this->storeTemplates["$template"] = $file;
		}
	}
	
	function array_insert($value, $offset, $array)
	{
		$arr1 = array_slice($array, 0, $offset);
		$arr2 = array_slice($array, $offset);
		$arr  = array_merge($arr1, array($value), $arr2);
		return $arr;
	}
	
	function array_replace($offset1, $offset2, $array)
	{
		$value = $array[$offset1];
		unset($array[$offset1]);
		return $this->array_insert($value, $offset2, $array);
	}

	function getVcard($version, $titre, $nom, $prenom, $fonction, $adresse, $cp, $zip, $ville, $canton, $pays, $tel, $fax, $natel, $mail, $telprive, $faxprive, $natelprive, $mailprive)
	{
		$arr = get_defined_vars(); //slt variables locales
		foreach($arr as $a => $b) if($a != "this")
		{
// 			$$a = quoted_printable_encode(html_entity_decode($b));
// 			print "\n<br>$a = ".$$a;
		}
// 		die();
		$version="2.1"; //TODO: pour l'instant, verrouille à 2.1

		$completeAddr = "$titre\n".
		"$prenom $nom\n".
		"$fonction\n".
		"$adresse\n".
		"$cCp\n".
		"$zip $ville\n";
		
		$completeAddr = preg_replace("#\n\n#", "\n", $completeAddr);
		$completeAddr = preg_replace("#^ #", "", $completeAddr);
		$completeAddr = preg_replace("#  #", "", $completeAddr);
		
		$vcard  = "BEGIN:VCARD\nVERSION:$version\n";
		$vcard .= "FN:$vcardName\n";
		$vcard .= "N:$nom;$prenom;;$titre;\n"; //Nom;prénom;noms additionnels;titres honorifique, suffixe. Les champs entre';' peuvent contenir plusieurs champs séparés par des ','.
		$vcard .= "ADR:$cp;$fonction;$adresse;$ville;$canton;$zip;$pays\n"; //Boîte postale, Adresse étendue, Nom de rue, Ville, Région (ou état/province), Code postal et Pays 
		$vcard .= "LABEL:$completeAddr\n";
		if($tel) $vcard .= "TEL;WORK;VOICE:$tel\n";
		if($natel) $vcard .= "TEL;WORK;CELL:$natel\n";
		if($fax) $vcard .= "TEL;WORK;FAX:$fax\n";
		if($mail) $vcard .= "EMAIL;WORK:$mail\n";

		if($telprive) $vcard .= "TEL;HOME;VOICE:$telprive\n";
		if($natelprive) $vcard .= "TEL;HOME;CELL:$natelprive\n";
		if($faxprive) $vcard .= "TEL;HOME;FAX:$faxprive\n";
		if($mailprive) $vcard .= "EMAIL;HOME:$mailprive\n";
		$vcard .= "END:VCARD\n";
		$vcard = html_entity_decode($vcard);
		//return utf8_decode($vcard);
		return $vcard;
	}
}
