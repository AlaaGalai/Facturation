<?php
$xml =
'<?xml version="1.0" encoding="ISO-8859-1" ?>
<feed xml:lang="de" xmlns="http://www.w3.org/2005/Atom" xmlns:openSearch="http://a9.com/-/spec/opensearchrss/1.0/" xmlns:tel="http://tel.search.ch/api/spec/result/1.0/">
  <id>https://tel.search.ch/api/MEINPRIVATERAPIKEY/68e7af8f8efa353de6d0b05f798598f4</id>
  <title type="text">tel.search.ch API Search Results</title>
  <generator version="1.0" uri="https://tel.search.ch">tel.search.ch</generator>
  <updated>2007-03-22T03:00:00Z</updated>
  <link href="https://tel.search.ch/result.html?name=john+meier&amp;maxnum=2" rel="alternate" type="text/html" />
  <link href="https://tel.search.ch/api/?was=john+meier&amp;maxnum=2&amp;key=MEINPRIVATERAPIKEY" type="application/atom+xml" rel="self" />
  <link href="https://tel.search.ch/api/?was=john+meier&amp;maxnum=2&amp;pos=3&amp;key=MEINPRIVATERAPIKEY" rel="next" type="application/atom+xml" />
  <openSearch:totalResults>14</openSearch:totalResults>
  <openSearch:startIndex>1</openSearch:startIndex>
  <openSearch:itemsPerPage>2</openSearch:itemsPerPage>
  <openSearch:Query role="request" searchTerms="john meier" startPage="1" />
  <entry>
    <id>urn:uuid:b4f420fda52419f2</id>
    <updated>2007-03-22T03:00:00Z</updated>
    <published>2007-03-22T03:00:00Z</published>
    <title type="text">Meier, John</title>
    <content type="text">Meier, John
    Marienfeldstrasse 92
    8252 Schlatt/TG
    *052 654 42 30</content>
    <autor>
      <name>tel.search.ch</name>
    </autor>
    <link href="https://tel.search.ch/detail/b4f420fda52419f2" title="Details" rel="alternate" type="text/html" />
    <link href="https://tel.search.ch/vcard/Meier.vcf?key=b4f420fda52419f2" type="text/x-vcard" title="VCard Download" rel="alternate" />
    <link href="https://tel.search.ch/edit/?id=b4f420fda52419f2" rel="edit" type="text/html" />
    <tel:pos>1</tel:pos>
    <tel:id>b4f420fda52419f2</tel:id>
    <tel:type>Person</tel:type>
    <tel:name>Meier</tel:name>
    <tel:firstname>John</tel:firstname>
    <tel:occupation></tel:occupation>
    <tel:street>Marienfeldstrasse</tel:street>
    <tel:streetno>92</tel:streetno>
    <tel:zip>8252</tel:zip>
    <tel:city>Schlatt</tel:city>
    <tel:canton>TG</tel:canton>
    <tel:phone>+41526544230</tel:phone>
  </entry>
  <entry>
    <id>urn:uuid:c8c043412a3ce526</id>
    <updated>2007-03-22T03:00:00Z</updated>
    <published>2007-03-22T03:00:00Z</published>
    <title type="text">John Meier IT-Consulting</title>
    <content type="text">John Meier IT-Consulting
    Unterdorfstrasse 22
    4143 Dornach/SO
    061 723 62 92</content>
    <autor>
      <name>tel.search.ch</name>
    </autor>
    <link href="https://tel.search.ch/detail/c8c043412a3ce526" title="Details" rel="alternate" type="text/html" />
    <link href="https://tel.search.ch/vcard/Meier.vcf?key=c8c043412a3ce526" type="text/x-vcard" title="VCard Download" rel="alternate" />
    <link href="https://tel.search.ch/edit/?id=c8c043412a3ce526" rel="edit" type="text/html" />
    <tel:pos>2</tel:pos>
    <tel:id>c8c043402a3ce526</tel:id>
    <tel:type>Organisation</tel:type>
    <tel:name>John Meier IT Consulting</tel:name>
    <tel:occupation>Your Personal IT-Consultant</tel:occupation>
    <tel:street>Unterdorfstrasse</tel:street>
    <tel:streetno>22</tel:streetno>
    <tel:zip>4143</tel:zip>
    <tel:city>Dornach</tel:city>
    <tel:canton>SO</tel:canton>
    <tel:category>Software Grosshandel</tel:category>
    <tel:category>Software &amp; Consulting</tel:category>
    <tel:phone>+41617236292</tel:phone>
    <tel:extra type="fax">+41617236393</tel:extra>
    <tel:extra type="mobile">+41763341010</tel:extra>
    <tel:extra type="email">john.meier@mymail.com</tel:extra>
    <tel:extra type="website">http://www.johnmeierconsult.com</tel:extra>
  </entry>
</feed>
';

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

		if($mef) $texte = "\n<ul>\n\t$texte\n</ul>";
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
	
	function advice($texte, $img="advice", $add = "")
	{
		if(is_file("{$this->settings["root"]}images/$img.png")) $img = "{$this->settings["root"]}images/$img.png";
		elseif(is_file("{$this->settings["root"]}images/$img")) $img = "{$this->settings["root"]}images/$img";
		elseif(is_file($img)) $img = $img;
		else return "";
		$texte = addslashes(stripslashes($texte));
		return "<img src=\"$img\" onmouseOver = \"show('$texte')\" onmouseout = \"hide()\" $add>";
	}


$s = simplexml_load_string($xml);
$s->registerXpathNamespace('atom' , 'http://www.w3.org/2005/Atom'); //On doit enregistrer l'espace de nommage par défaut en lui attribuant un nom arbitraire (ici: atom) qui doit correspondre à l'objet fils recherché
$objRet = $s->xpath('//atom:entry'); //retourne la liste des enregistrements

$x = 0;
foreach($objRet as $node)
{
	if($x == 0)
	{
		$x ++;
		continue;
	}
	echo "\n<br>$x";
	$fx   = $node->children("tel", true)->extra;
	$fx->registerXpathNamespace('tel' , 'http://www.w3.org/2005/Atom');
	print_r($fx);
	$fax = (sizeof($fx->xpath('//tel:extra[@type="fax"]')) != 0)? $fx->fax:"pas de fax";
	print "\n<br>attributes ($x): fax = '$fax'";
	if(sizeof($fx->xpath("//tel:extra[@type='fax']")) != 0)
	{
 		print_r($fx ->attributes());
		foreach($fx->attributes()->type as $n => $aName)
		{
			echo "\n$n: $aName";
		}
		echo "\nDetails:\n";
		print_r($fx->xpath("//tel:extra[@type='fax']")[0]->__tostring());
// 		print_r($n);
// 		print(simplexml_load_string($fx->xpath('//tel:extra[@type="fax"]')[0]->asXml()));
// 		print_r($fx->fax->asXml());
	}
// 	var_export($fx->xpath('//tel:extra[@type="fax"]'));
// 	if($fx->attributes()->fax) print_r($fx->attributes()->fax);
	$x ++;
}

?>