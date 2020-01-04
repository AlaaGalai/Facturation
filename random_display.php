<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);

if($_REQUEST["generateMailing"])
{
	$head = 1.5;
	$left = 0.87;
	$pad = 0.2;
	$width = 6.6;
	$height = 2.9;
	$sepWidth = 0.1;
	$sepHeight = 0.2;
	$sepWidth = 0;
	$sepHeight = 0;
	$innerHeight = $height - 2 * $pad;
	$innerWidth = $width - 2 * $pad;
	$cols = 3;
	$doc = new prolawyer;
// 	$doc->tab_affiche(4);
	$mailing = trim($_REQUEST["mailing"]);
	$mode = isset($_REQUEST["mode"]) ? $_REQUEST["mode"]: "html";
	$h1 = ($mode == "pdf") ? "application": "text";
	$q = "select 0, titre, prenom, nom, fonction, adresse, cp, zip, ville from adresses a LEFT JOIN mailing m on a.id = m.adresseid where mailingname like '$mailing'";
// 	for($x = 1;$x <10;$x++) $q .= "UNION " .preg_replace("#select 0#", "select $x", $q);
	$q .= " ORDER BY nom, prenom";
// 	$q = "select * from mailing m where m.nom like '$mailing'";
// 	echo "<br>'$q'";
// 	$q = "select * from mailing m";
	$e = mysqli_query($doc->mysqli, $q) or die (mysqli_error($doc->mysqli));
	
	
// 	header("Content-Disposition: attachment; filename=\"$mailing.$mode\"");
// 	header("Content-Type: $h1/$mode");
	
	//Entete
	switch($mode)
	{
		case "csv":
			echo '"champ1";"champ2";"champ3";"champ4";"champ5";"champ6"';
			break;
		case "pdf":
			break;
		case "html":
			echo "<!DOCTYPE html>";
			echo "\n<html>";
			echo "\n<head>";
			echo "\n\t<meta http-equiv='Content-Type' content='text/html; charset=utf-8' />";
			echo "\n\t<style>@page {margin-top: {$head}cm; margin-bottom: 0cm;margin-left: {$left}cm;margin-right:0cm}</style>";
			echo "\n\t<style>body{border-style:none;border-width:0px;padding:0px;margin:0px;line-height:100%;width: 210mm; /* A4 dimension */}</style>";
			echo "\n\t<style>div{display: inline-block;border-style:none;border-width:0px;padding:0px;margin:0px;font-size:8pt;line-height:1.1}</style>";
// 			echo "\n\t<style>@media print{body {margin-top: {$head}cm; margin-left: {$left}cm}}</style>";
// 			echo "\n\t<style>.head{width:100%;height:$head}</style>";
// 			echo "\n\t<style>.left{width:{$left}cm}</style>";
			echo "\n\t<style>.etiq{overflow: hidden;width:{$innerWidth}cm;height:{$innerHeight}cm;padding:{$pad}cm;margin:0px}</style>";
			echo "\n\t<style>.sepwidth{width:{$sepWidth}cm;background-color:#88ff88}</style>";
			echo "\n\t<style>.sepheight{height:{$sepHeight}cm;width:100%;background-color:#8888ff;margin:0px;padding:0px}</style>";
			echo "\n\t<title>Etiquettes pour $mailing (subilia@subilia)</title>";
			echo "\n</head>";
			echo "\n<body>";
// 			echo "\n<div class=head>&nbsp;</div>\n<br><div class=left>&nbsp;</div>";
			break;
	}
	$x = 1;
	$y = 1;
	while($r = mysqli_fetch_array($e))
	{
		foreach($r as $a => $b) $$a = $b;
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
		$completeAddr = trim($completeAddr);
		$htmlAddr = nl2br($completeAddr);
		
		switch($mode)
		{
			case "csv":
				echo "\n";
				$l = 0;
				foreach(explode("\n", $completeAddr) as $ligne)  if($ligne)
				{
					$l ++;
					echo $doc->toCsv($ligne);
				}
				for($l;$l < 6;$l++) echo $doc->toCsv("", True, ",");
				break;
			case "html":
				$bgcolor = $y %2 == 0? "#dfdfdf": "#808080";
				echo "<div class=etiq style=background-color:$bgcolor>$htmlAddr</div>";
				if($sepWidth > 0 && $x < $cols) echo "<div class=sepwidth>&nbsp;</div>";
				$x ++;
				$y ++;
				if($x == 4)
				{
// 					echo "\n<br>";
// 					echo "\n<br><div class=left>&nbsp;</div>";
					if($sepHeight > 0) echo "<div class=sepheight>&nbsp;</div>";
					$x = 1;
				}
		}
	}
	//Pied
	switch($mode)
	{
		case "csv":
			break;
		case "pdf":
			break;
		case "html":
			echo "\n</body>";
			echo "\n</html>";
			break;
	}
	die();
}

if($_REQUEST["setNewCity"])
{
	$datas = file_get_contents("http://www.codepostaux.com/cpsuisse/cpsuisse.cgi?recherche=&mc={$_REQUEST["zip"]}");
	foreach (preg_split("#\n#", $datas) as $line)
	{
		preg_match("¢<tr bgcolor='#EEEEEE'><TD><B>(.*)</TD></B><TD><B>(.*)</B></TD><TD><B>¢", $line, $regs);
		if($regs[1]) die(trim($regs[1]));
	}
	die("");
}

if($_REQUEST["qrcode"])
{
	$doc = new prolawyer(false);
	$string = 'PHP QR Code :)';
	if($_REQUEST["string"]) $string = rawurldecode($_REQUEST["string"]);
	else $string = "?";
// 	die($string);
// 	$string = $doc->smart_utf8($string);
	if($_REQUEST["txt"]) die($string);
	require_once("./externe/phpqrcode/phpqrcode.php");
	$code = QRcode::png($string);
	die();
}


if($_REQUEST["setNewFile"])
{
	$doc = new prolawyer();
// 	$doc->tab_affiche();
	$q ="select  c.nom as nom_client, c.prenom as prenom_client, p.nom as nom_pa, p.prenom as prenom_pa, a.naturemandat from {$_SESSION["session_avdb"]} a LEFT OUTER JOIN adresses c on a.noadresse = c.id LEFT OUTER JOIN adresses p on a.nopa = p.id  where a.nodossier like '{$_POST["nodossier"]}'";
	$e = mysqli_query($doc->mysqli, $q);
	while($row = mysqli_fetch_array($e))
	{
		$newAffiche = "";
		if($row["prenom_client"]) $newAffiche .= substr(ucfirst($row["prenom_client"]), 0, 1).".";
		$newAffiche .= $row["nom_client"];
		$newPa = "";
		if($row["prenom_pa"]) $newPa .= substr(ucfirst($row["prenom_pa"]), 0, 1).".";
		$newPa .= $row["nom_pa"];
		if($newPa) $newAffiche .= " c. $newPa";
		else $newAffiche .= "/{$row["naturemandat"]}";
	}
// 	echo $q;
	die($newAffiche);
}

if($_REQUEST["changeState"])
{
	$doc = new prolawyer();
	$base = $_SESSION["session_avdb"];
	$field = "nodossier";

	if($_REQUEST["toSet"] == "facturepayee")
	{
		$base = $_SESSION["session_opdb"];
		$field = "idop";
	}
	//foreach($_REQUEST as $a => $b) echo "<br>$a vaut $b";
	$value = ($_REQUEST["value"])? 0:1;
	if ($value && $_REQUEST["toSet"] == "dormant") $value = 4;
	$toSet = $_REQUEST["toSet"];
	$q = "update $base set $toSet=$value where $field like '{$_REQUEST["nodossier"]}'";
	$e = mysqli_query($doc->mysqli, $q) or die(mysqli_error($doc->mysqli));
	die ($q);
}

if($_REQUEST["abandon"])
{
	$doc = new prolawyer();
	foreach($_REQUEST as $a => $b) echo "<br>$a vaut $b";
	echo "'".$_REQUEST["aabandonner"]."'";
	$aabandonner = ($_REQUEST["aabandonner"])? 0:1;
	$q = "update {$_SESSION["session_avdb"]} set abandon=$aabandonner where nodossier like '{$_REQUEST["nodossier"]}'";
	$e = mysqli_query($doc->mysqli, $q) or die(mysqli_error($doc->mysqli));
	die ($q);
}

if($_REQUEST["facturation"])
{
	$doc = new prolawyer();
	foreach($_REQUEST as $a => $b) echo "<br>$a vaut $b";
	echo "'".$_REQUEST["afacturer"]."'";
	$afacturer = ($_REQUEST["afacturer"])? 0:1;
	$q = "update {$_SESSION["session_avdb"]} set afacturer=$afacturer where nodossier like '{$_REQUEST["nodossier"]}'";
	$e = mysqli_query($doc->mysqli, $q) or die(mysqli_error($doc->mysqli));
	die ($q);
}

//affichage d'un texte quelconque
if($_POST["title"] || $_POST["body1"])$doc=new prolawyer;
if($_POST["title"])
{
	$doc->lang["random_display_title"] = $_POST["title"];
	$random_display_title = $_POST["title"];
	$doc->title();
}
if($_POST["body1"]) $doc->body($_POST["body1"], $_POST["body2"], $_POST["body3"], $_POST["body4"]);
echo $_POST["texte"];
if($_POST["body1"]) $doc->close();

//mise à jour d'un dossier

if($_POST["mailbox"])
{
	echo "&nbsp;";
	echo "'{$_POST["mailbox"]}'... ";
	$racine_mbx = $_SESSION["optionGen"]["racine_mbx"];
	//first create structure
	$imap = @imap_open ($racine_mbx, $_SESSION["optionGen"]["user_mbx"], $_SESSION["optionGen"]["pass_mbx"]) or die("<span style='color:#ff0000'>connexion impossible : </span>" . imap_last_error());
// 	$struct = True;
	$listStruct = imap_list($imap, $racine_mbx, "*");

	for($x=0; $x <26; $x++)
	{
		$char = chr(ord("A") + $x);
		$newbox = "$racine_mbx.$char";
		if(!in_array($newbox, $listStruct))
		{
			$create = @imap_createmailbox ($imap , $newbox );
			if(!$create)
			{
				die(imap_last_error());
			}
			else $subscr = @imap_subscribe ($imap , $newbox );
		}

	}


		$mailbox = $_POST["mailbox"];
		$chckRoot = preg_match("#($racine_mbx)(.*)#", $mailbox, $nMail);
		if($chckRoot)
		{
			$parts = preg_split("#\.#", $nMail[2]);
			$test = $racine_mbx;
			foreach($parts as $part) if($part)
			{
				$listPart = imap_list($imap, $test, "*");
				$test .= ".$part";
				$newbox = imap_utf7_encode($test);
				if(!in_array($test, $listPart) && !in_array($newbox, $listPart))
				{
// 					echo "<br>$test ($part) n'existe pas";
// 					$doc->tab_affiche($listPart);
	// 				echo "<br>$test";
					$create = @imap_createmailbox ($imap , $newbox ) or die("<span style='color:#ff0000'>creation impossible : </span>" . imap_last_error());
					$subscr = @imap_subscribe ($imap , $newbox );
				}
				//$delete = imap_deletemailbox ($imap , $newbox ) or die("connexion impossible : " . imap_last_error());;
			}
			die("<span style='color:#40ff40'>OK</span>");
		}
		else die( "<span style='color:#ff0000'>Pas trouvé la racine</span>");
}

if($_POST["chemin"])
{
	$doc = new prolawyer();
	$_POST["chemin"] = trim($_POST["chemin"]);
// 	print_r($_SESSION);
	echo "&nbsp;";
	echo "'{$_POST["chemin"]}'... ";
	$path = "";
	foreach(explode($_SESSION["slash"], $_POST["chemin"]) as $part)
	{
		$path .= $part.$_SESSION["slash"];
		if(!is_dir($path))
		{
			mkdir($path, 0775);
		}
	}
// 	$path1=mkdir ("{$_POST["chemin"]}", 0775); //déjà fait plus haut
	foreach($doc->docTypes as $docType)
	{
		$valName = "config_templates_$docType";
		$ssRep = $doc->lang["$valName"];
		$aRep = "{$_POST["chemin"]}{$_SESSION["slash"]}$ssRep";
		if($aRep && !is_dir($aRep)) $mkPath = mkdir ("$aRep", 0775);
		else $mkPath = True;
		if(!$mkPath) die("<span style='color:#ff0000'>Erreur en cr&eacute;ant '$aRep'</span>");
		$chGrp = chgrp ("$aRep", "dossiers");
		if(!$chGrp) die("<span style='color:#ff0000'>chgrp (\"$aRep\", \"dossiers\"): Erreur en changeant le groupe de $aRep pour 'dossiers'</span>");
                $chMod = chmod ("$aRep", 0775);
		if(!$chMod) die("<span style='color:#ff0000'>Erreur en changeant le mode de $aRep pour '0775'</span>");
	}

	echo "<span style='color:#40ff40'>OK</span>";
}
?>
