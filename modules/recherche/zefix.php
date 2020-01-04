<?php
require_once("../../inc/autoload.php");
session_start();
if(!isset($_POST["majPage"]) && !isset($_GET["majPage"]))
{
	$_REQUEST["majPage"] = preg_replace("#(modifier_donnees.php|adresses/modifier.php)#", "maj_op.php", $_SERVER["HTTP_REFERER"]);
	$_POST["majPage"] = $_REQUEST["majPage"];
	$_GET["majPage"] = $_REQUEST["majPage"];
}

if(isset($_REQUEST["lastid"]) && $_REQUEST["lastid"])
{
	die ("<html><body onload=\"window.opener.document.getElementById('self_reload').submit();window.close()\"></body></html>");
}

$doc = new prolawyer();
$doc->title(false, "utf-8");
$doc->body();
// $doc->tab_affiche();
if(is_file(("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}zefixname"))) $doc->zefixUser = file_get_contents("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}zefixname");
if(is_file(("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}zefixpwd"))) $doc->zefixPassword = file_get_contents("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}zefixpwd");
if(is_file("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}telsearchkey")) $doc->telsearchKey = file_get_contents("{$_SESSION["optionsPath"]}{$_SESSION["slash"]}telsearchkey");

if(!isset($_POST["searchprovider"]) && !isset($_GET["searchprovider"]))
{
	die("provider inconnu");
}

$searchMethod = "search{$_REQUEST["searchprovider"]}";

$searchForm = "<form action='{$_SERVER["PHP_SELF"]}' method='POST' id='globalSearchForm'>";
if($_REQUEST["searchByNameRequest"]) $searchForm .= $doc->input_texte("searchByNameRequest", 1, "", "40");
else $searchForm .= $doc->input_texte("searchByNameRequest", "", "IDTOPREG", "40");
$searchForm .= $doc->input_hidden("persid", 1);
$searchForm .= $doc->input_hidden("id", 1);
$searchForm .= $doc->input_hidden("majPage", 1);
$searchForm .= $doc->input_hidden("searchprovider", 1);
$searchForm .= $doc->input_hidden("nodossier", 1);
$searchForm .= $doc->button("{$doc->lang["recherche_dossier_recherche"]} <img src='../../images/{$_REQUEST["searchprovider"]}.png'>");
$searchForm .= "</form>";

if($_REQUEST["CHid"] || $_REQUEST["EHRAid"] || $_REQUEST["Uid"] || $_REQUEST["TelSearchId"])
{
	if($_REQUEST["TelSearchId"])
	{
		$response = $doc->searchTelSearch($method, $_REQUEST["TelSearchId"]);
		$respNum = 0;
		foreach($response["object"] as $node)
		{
			$type = ''; //sinon la forme juridique demeure
			$sTyp = $node->children("tel", true)->type;
			$name = $node->children("tel", true)->name;
	//  				$name = utf8_decode($name);
			$fNam = $node->children("tel", true)->firstname;
	//  				$fNam = utf8_decode($fNam);
			$occu = $node->children("tel", true)->occupation;
	//  				$occu = utf8_decode($occu);
			$addr = $node->children("tel", true)->street;
	//  				$strt = ucfirst(utf8_decode($strt));
			$strN = $node->children("tel", true)->streetno;
			$pBox = $node->children("tel", true)->pobox;
	//  				$strN = ucfirst(utf8_decode($strN));
			if($strN) $addr = "$addr $strN";
			$tsid = $node->children("tel", true)->id;
			$szip = $node->children("tel", true)->zip;
			$pZip = $node->children("tel", true)->poboxzip;
			$vill = $node->children("tel", true)->city;
	//  				$vill = utf8_decode($vill);
			$pVil = $node->children("tel", true)->poboxcity;
	//  				$pVil = utf8_decode($pVil);
			$tel  = $node->children("tel", true)->phone;
			$extra   = $node->children("tel", true)->extra;
			$extra->registerXpathNamespace('tel' , 'http://www.w3.org/2005/Atom');
			foreach(array("Fax", "Mobile", "Email") as $vTest)
			{
				$vName = strtolower($vTest);
				$$vName = "";
				foreach(array($vTest, strtolower($vTest)) as $aVTest)
				{
					if($extra->xpath("//tel:extra[@type='$aVTest']")[0])
					{
						$$vName = preg_replace("#\\*#", "", $extra->xpath("//tel:extra[@type='$aVTest']")[0]);
	// 					echo "<br>trouve: $vName - " . $$vName;
					}
				}
			}
			if($pBox && $pZip) $szip = $pZip;
			if($pBox && $pVil) $vill = $pVil;
			if(strtolower($sTyp) != "organisation")
			{
// 				echo "privé<br>";
				foreach(array("tel", "fax", "mobile", "email") as $nTest)
				{
					$privVar = "{$nTest}p";
					if($$nTest)
					{
// 						echo "{$$nTest} -> $privVar";
						$$privVar = $$nTest;
						unset($$nTest);
						
					}
				}
			}
		}
		$arrVal = array("titre" => "titre", "prenom" => "fNam", "nom" => "name", "fonction" => "occu", "nosociete" => "chid", "typesociete" => "type", "adresse" => "addr", "zip" => "szip", "ville" => "vill", "canton" => "cant", "tel" => "tel", "fax" => "fax", "mail" => "email", "telprive" => "telp", "faxprive" => "faxp", "mailprive" => "emailp");
	}
	else
	{
		foreach(array("CHid", "EHRAid", "Uid") as $tag) if($_REQUEST["$tag"])
		{
			$critere = $_REQUEST["$tag"];
			$method  = "getBy{$tag}FullRequest";
		}
		$response = $doc->$searchMethod($method, $critere);
		$domDoc = new DOMDocument;
		$domDoc->loadXML($response["string"]);
		$zefArr = array("name" => "name", "type" => "legalFormId", "chid" => "chid", "wlin" => "webLink", "addr" => "street", "hnmb" => "houseNumber", "vill" => "town", "szip" => "swissZipCode", "rOff" => "registryOfficeId", "cant" => "registryOfficeCanton");
		foreach($zefArr as $a => $b) $$a = $domDoc->getElementsByTagNameNS("*", "$b")->item($respNum)->firstChild->data;
		$respNum ++;
		$type = $doc->socId["$type"];
		if($addr && $hnmb) $addr .= " $hnmb";
// 		$offices = $doc->searchZefix("getRegistryOfficesRequest", $rOff);
// 		echo "<br>roff: $rOff</br>";
// 		echo "<br>cant: $cant</br>";
// 		die($doc->xmlformat($response["string"]));
// 		$domDoc = new DOMDocument;
// 		$domDoc->loadXML($offices["string"]);
// 		$zefArr = array("cant" => "canton");
// 		foreach($zefArr as $a => $b) $$a = $domDoc->getElementsByTagNameNS("*", "$b")->item(0)->firstChild->data;

// 		foreach($response["object"] as $node)
// 		{
// 	// 		echo $doc->xmlformat($response["string"]);
// 			#$name = $node->children("ns3", true)->name;
// 			$name = $node->children("ns2", true)->name;
// 			#$type = $node->children("ns3", true)->legalform->legalFormId;
// 			$type = $node->children("ns2", true)->legalform->legalFormId;
// 			$type = $doc->socId["$type"];
// 			#$chid = $node->children("ns3", true)->chid;
// 			$chid = $node->children("ns2", true)->chid;
// 			#$wlin = $node->children("ns3", true)->webLink;
// 			$wlin = $node->children("ns2", true)->webLink;
// 			$addr = $node->children("ns2", true)->address->children("ns4", true)->addressInformation->street;
// 			//$addr = $node->children("ns3", true)->address->children("ns2", true)->addressInformation->street;
// 			$hnmb = $node->children("ns2", true)->address->children("ns4", true)->addressInformation->houseNumber;
// 			//$hnmb = $node->children("ns3", true)->address->children("ns2", true)->addressInformation->houseNumber;
// 			$vill = $node->children("ns2", true)->address->children("ns4", true)->addressInformation->town;
// 			//$vill = $node->children("ns3", true)->address->children("ns2", true)->addressInformation->town;
// 			$szip = $node->children("ns2", true)->address->children("ns4", true)->addressInformation->swissZipCode;
// 			//$szip = $node->children("ns3", true)->address->children("ns2", true)->addressInformation->swissZipCode;
// 			$rOff = $node->children("ns2", true)->registerOfficeId;
// 			//$rOff = $node->children("ns3", true)->registerOfficeId;
// 			$offices = $doc->searchZefix("getRegistryOfficesRequest", "None");
// // 			$doc->xmlformat($offices["string"]);
// // 			var_export($offices["object"]);
// 			foreach($offices["object"] as $office)
// 			{
// 				//$office->registerXpathNamespace('ns2', 'http://www.e-service.admin.ch/zefix/2015-06-26');
// 				$office->registerXpathNamespace('ns2', 'http://www.ech.ch/xmlns/eCH-0010/4');
// 				$office->registerXpathNamespace('ns3', 'http://www.e-service.admin.ch/zefix/2015-06-26');
// 				$office->registerXpathNamespace('ns4', 'http://www.ech.ch/xmlns/eCH-0097/2');
// // 				echo "\n<br>";
// 				$cant = $office->children("ns2", true)->canton;
// 				#$cant = $office->children("ns3", true)->canton;
// 				$ofID = $office->children("ns2", true)->id;
// 				#$ofID = $office->children("ns3", true)->id;
// 				$ofNm = $office->children("ns2", true)->address1;
// 				#$ofNm = $office->children("ns3", true)->address1;
// 				if(strval($ofID) == strval($rOff))
// 				{
// // 					echo "<br>$cant: $ofID: $ofNm";
// 					break;
// 				}
// 			}
// 			if($addr && $hnmb) $addr .= " $hnmb";
// 		}
		$arrVal = array("titre" => "titre", "prenom" => "fNam", "nom" => "name", "fonction" => "occu", "nosociete" => "chid", "typesociete" => "type", "adresse" => "addr", "zip" => "szip", "ville" => "vill", "canton" => "cant");
	}
	$oldVals = array();
	$q2 = "";
	foreach($arrVal as $a => $b)
	{
		if($q2) $q2 .= ", ";
		$q2 .= "$a";
	}
	$q = "select $q2 from adresses where id = '{$_REQUEST["id"]}'";
	$e = mysqli_query($doc->mysqli, $q);
	while($r = mysqli_fetch_array($e)) foreach($r as $c => $d)
	{
		$oldVals[$c] = $d;
	}

	$js   = "";
	$data = "";
	$form = "<form name=modifier id=modifier method=\"post\" action=\"{$_POST["majPage"]}\">
";
	foreach($arrVal as $n => $v)
	{
		$v   = $$v;
		if(! $v)
		{
			$classOld = "nomaj";
			$classNew = "majref";
			$checked = "";
		}
		else
		{
			$classOld = "majref";
			$classNew = "maj";
			$checked = "checked";
		}
			$specV = addslashes(stripslashes($v));
// 			$v   = utf8_decode($v);
		$form .= "\n<br>";
// 		$form .= "\n" . $doc->input_hidden($n, "", $v);
// 		$form .= "\n" . $doc->input_hidden($n, "", $v);
// 		$form .= "\n" . $doc->input_checkbox("{$n }_check", "", "$checked", "", "onclick='verifymaj(\"$n\")'");
// 		$form .= "\n" . $doc->input_checkbox($n, "", "$checked", "", "onclick='verifymaj(\"$n\")'");
		$form .= "\n<input type='checkbox' $checked name='$n' id='$n' value=\"$specV\" onclick='verifymaj(\"$n\")'>";
		if($n == "typesociete")
		{
				$v = $doc->societes["$v"];
				$oldVals["$n"] = $doc->societes["{$oldVals["$n"]}"];
		}
		$na = "<i>n/a</i>";
		if(! $v) $v = $na;
		if(!$oldVals["$n"]) $oldVals["$n"] = $na;
		$form .= "$n: (<span class='$classOld' id='{$n}_old'>{$oldVals["$n"]}</span>) => <span class='$classNew' id='{$n}_new'>$v</span>";
		if($js) $js .= ", ";
		$js .= "'$n'";
	}
	$form .= $doc->input_hidden("retour", "", "modules/recherche/zefix");
	$form .= $doc->input_hidden("id", 1);
	$form .= $doc->input_hidden("nodossier", 1);
	$form .= "\n<br><input id=checkAll type=checkbox onclick=\"var toChange;if(document.getElementById('checkAll').checked) toChange=true;else toChange=false;var elem = [$js];for (k in elem) {n = elem[k];document.getElementById(n).checked = toChange;verifymaj(n)}\">&nbsp;{$doc->lang["modules_select_all"]}";
	$form .= "&nbsp;<input type=reset class=button value='{$doc->lang["index_reset"]}' onclick=\"var elem = [$js];for (k in elem) {n = elem[k];document.getElementById(n).checked = document.getElementById(n).defaultChecked;verifymaj(n)}\">";
	$form .= "<br>";
	$form .= $doc->button($doc->lang["creer_client_accepter"]);
	$form .= "</form>";
	if($_REQUEST["searchprovider"] == "telsearch" && $fNam) $name = "$fNam $name";
	if($_REQUEST["searchprovider"] == "zefix") $form .= "\n<span class='button'><a href='$wlin' target=_new>-&gt; $ofNm</a></span>";
	echo preg_replace("#IDTOPREG#", $name, $searchForm);
	
	echo "\n$form";
// 		echo "\n\n".$doc->xmlformat($response["string"]);
}

else

{
	foreach(array("searchByNameRequest", "getByCHidFullRequest") as $method) if(isset($_REQUEST["$method"]))
	{
		$response = $doc->$searchMethod($method, $_REQUEST["$method"]);
		$respNum = 0;
//  		die($doc->xmlformat($response["string"]));

		$int = 1;
		$arrResp = array();
// 		echo $doc->xmlformat($response["string"]);
		foreach($response["object"] as $node)
		{
// 			echo "<br>objet";
			if($_REQUEST["searchprovider"] == "telsearch")
			{
 				$type = $node->children("tel", true)->type;
 				$name = $node->children("tel", true)->name;
//  				$name = utf8_decode($name);
 				$fNam = $node->children("tel", true)->firstname;
//  				$fNam = utf8_decode($fNam);
 				$occu = $node->children("tel", true)->occupation;
//  				$occu = utf8_decode($occu);
 				$strt = $node->children("tel", true)->street;
//  				$strt = ucfirst(utf8_decode($strt));
				$strN = $node->children("tel", true)->streetno;
				$pBox = $node->children("tel", true)->pobox;
//  				$strN = ucfirst(utf8_decode($strN));
 				if($strN) $strt = "$strt $strN";
 				$tsid = $node->children("tel", true)->id;
				$szip = $node->children("tel", true)->zip;
				$pZip = $node->children("tel", true)->poboxzip;
  				$vill = $node->children("tel", true)->city;
//  				$vill = utf8_decode($vill);
  				$pVil = $node->children("tel", true)->poboxcity;
//  				$pVil = utf8_decode($pVil);
 				$tl   = $node->children("tel", true)->phone;
 				
 				if($pBox && $pZip) $szip = $pZip;
 				if($pBox && $pVil) $vill = $pVil;
 				if($fNam) $name = "$fNam $name";
 				
// 				$arrVal = array("nom" => "name", "prenom" => "fNam", "fonction" => "occu", "adresse" => "strt", "zip" => "szip", "ville" => "vill", "tel" => "tel", "fax" => "fax", "mail" => "email", "telprive" => "telp", "faxprive" => "faxp", "mailprive" => "emailp");
				$newUrl = rawurlencode($_REQUEST["searchByNameRequest"]);
				$newInt = $int + $_REQUEST["debut"];
				$arrResp[] = "<b><a href='?searchprovider=telsearch&TelSearchId=$newUrl/$newInt&persid={$_REQUEST["persid"]}&id={$_REQUEST["id"]}&majPage={$_REQUEST["majPage"]}&nodossier={$_REQUEST["nodossier"]}'>$name</a></b>, $strt, $szip $vill";
// 				echo "\n$form";
// 	 			die($doc->xmlformat($response["string"]));
	// 			die();
				$int ++;
			}	
			if($_REQUEST["searchprovider"] == "zefix")
			{
				$domDoc = new DOMDocument;
				$domDoc->loadXML($response["string"]);
				$zefArr = array("name" => "name", "type" => "legalFormId", "chid" => "chid", "ehraid" => "ehraid", "uid" => "uid", "vill" => "legalSeat");
				foreach($zefArr as $a => $b) $$a = $domDoc->getElementsByTagNameNS("*", "$b")->item($respNum)->firstChild->data;
				$respNum ++;
				$openTag = $type == 9 ? "<i>":"";
				$closeTag = $type == 9 ? "</i>":"";
				$type = $doc->socConv["$type"];

// 				$name = $node->children("ns2", true)->name;
// 				#$name = $node->children("ns3", true)->name;
// // 				$name = utf8_decode($name);
// 				$chid = $node->children("ns2", true)->chid;
// 				#$chid = $node->children("ns3", true)->chid;
// 				$ehraid = $node->children("ns2", true)->ehraid;
// 				#$ehraid = $node->children("ns3", true)->ehraid;
// 				$uid= $node->children("ns2", true)->uid;
// 				#$uid= $node->children("ns3", true)->uid;
// 				$type = $node->children("ns2", true)->legalform->legalFormId;
// 				#$type = $node->children("ns3", true)->legalform->legalFormId;
// 				$openTag = $type == 9 ? "<i>":"";
// 				$closeTag = $type == 9 ? "</i>":"";
// 				$type = $doc->socConv["$type"];
// 				$vill = $node->children("ns2", true)->legalSeat;
				#$vill = $node->children("ns3", true)->legalSeat;
// 				$vill = utf8_decode($vill);
				$tagLinks = "";
				foreach(array("CHid" => $chid, "EHRAid" => $ehraid, "Uid" => $uid) as $tag => $tagValue) if($tagValue)
				{
					$tagLinks .= "$tag: <a href='?searchprovider=zefix&$tag=$tagValue&persid={$_REQUEST["persid"]}&id={$_REQUEST["id"]}&majPage={$_REQUEST["majPage"]}&nodossier={$_REQUEST["nodossier"]}'>$tagValue</a>";
					break;
				}
				$arrResp[] = "{$openTag}<b>$name</b>, $vill ($type); {$closeTag}{$tagLinks}";
	// 			die($doc->xmlformat($response["string"]));
	// 			die();
			}	
		}
// 		die($doc->xmlformat($response["string"]));
		sort($arrResp);
		echo preg_replace("#IDTOPREG#", $name, $searchForm);
		if($_REQUEST["searchprovider"] == "telsearch")
		{
			echo "<br><br>";
			$tts = $doc->trouveTelSearch;
			if($tts > 0) echo "$tts {$doc->lang["adresses_resultat_resultat"]}";
			elseif(! $tts) echo $doc->lang["resultat_recherche_rien_trouve"];
		}
		foreach($arrResp as $resp) echo "\n<br>$resp";
		if($_REQUEST["searchprovider"] == "telsearch")
		{
// 			die("probleme avec '{$doc->telSearchMaxSize}'");
			echo $doc->footer($_REQUEST["debut"], min(array($tts, 200)), $doc->telSearchMaxSize);
		}
	}
}
?>
