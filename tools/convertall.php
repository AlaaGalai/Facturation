<?php
require("../inc/autoload.php");
session_start();
$doc = new prolawyer();
error_reporting(63);

// function m($die = false)
// {
// 	echo "<br>". microtime(). ": ";
// 	$m1 = memory_get_peak_usage();
// 	$m = memory_get_usage();
// 	if($die) die("Memory: $m1");
// 	echo("<br>Memory: $m ($m1)");
// }

//Vérification des droits. Cet outil peut afficher n'importe quel fichier. Il faut donc être prudent à l'utilisation
if($_SESSION["type"] != "admin") die ("Cet outil nécessite d'être administrateur");

if(isset($_REQUEST["echofile"]))
{
	if($_SESSION["echofile"] == $_REQUEST["echofile"])
	{
		$fileName = basename($_REQUEST["echofile"]);
		header("Content-Typw: text/text");
		header("Content-Disposition: attachment; filename=\"$fileName\"");
	$_SESSION["echofile"] = "";
		die(file_get_contents($_REQUEST["echofile"]));
	}
	else echo "Vous n'êtes pas autorisé à affichier le contenu de {$_REQUEST["echofile"]}";
}
$_SESSION["echofile"] = "";

if(!isset($_REQUEST["showTr"])) $_REQUEST["showTr"] = "";
if(!isset($_REQUEST["file"])) $_REQUEST["file"] = "";
$source  = $_REQUEST["file"];

if(isset($_FILES["toconvert"]))
{
	if (is_uploaded_file($_FILES["toconvert"]["tmp_name"]))
	{
		$fichier = $_FILES["toconvert"]["name"];
		$source = $_FILES["toconvert"]["tmp_name"];
		echo "<br>Uploaded: $fichier ($source)";
	}
}

if(!is_file($source))
{
	if($source) echo "\n<br>'$source' doesn't exist<br>	";
	$convertPath = "toconvert";
	$dir = opendir("$convertPath");
	$isFile = False;
	while($file = readdir($dir))
	{
		if(substr($file, 0, 4) == "dump" && substr($file, -4) == ".sql")
		{
			echo "\n<br><a href='./convertall.php?file=$convertPath/$file'>$file</a>";
			$isFile = True;
		}
		elseif(substr($file, 0, 4) == "dump") echo substr($file, -4);
	}
	if(!$isFile)
	{
		echo "<br>No file named dump[a-zA-Z0-9_]+.sql in $convertPath";
	}
	echo "<br><form name=convertfile method='post' action='./convertall.php' enctype='multipart/form-data'>";
	echo "\n<br><input type=file name=toconvert>\n<br><input type=submit>\n</form>";
	die();
}

$memory1 = ini_get("memory_limit");
ini_set("memory_limit", "8192M");
$memory2 = ini_get("memory_limit");

error_reporting(63);
echo "<br>Adjusting memory size: Memory: $memory1 ->$memory2<br>\n";

$dSource = dirname($source);
if(!is_writable($dSource)) die("<br>Attention: $dSource n'est pas accessible en écriture. Abandon");
$fSource = basename($source);
if(preg_match("#(.*)(\.[^.]+)$#", $fSource, $regs))
{
	$rad = $regs[1];
	$ext = $regs[2];
}
else
{
	$rad = $fSource;
	$ext = "";
}
$dName   = "$rad-converted{$ext}";
$fDest   = "$dSource/$dName";
$x = '';
while(is_file($fDest))
{
	$x ++;
	$fDest   = "$dSource/$rad-converted-$x{$ext}";
	
}
echo "$source => $fDest... ";
$hdecode = file_get_contents($source) or die ("$source not loaded");
$doc->debugNow("Fichier chargé dans \$hdecode", True);
$hdecode = html_entity_decode($hdecode); #Double, parce qu'il y a notamment pour l'agenda une double conversion
$doc->debugNow("Conversion de \$hdecode sur lui-même", True);
$hdecode = html_entity_decode($hdecode); #Double, parce qu'il y a notamment pour l'agenda une double conversion
$doc->debugNow("Conversion de \$hdecode sur lui-même (2ème fois)", True);
$hdecode = preg_replace("#(ENGINE.*DEFAULT CHARSET=)latin1#", "\\1utf8", $hdecode);
$hdecode = preg_replace("#CREATE DATABASE.*#", "", $hdecode);
$trans = file("./utf8-conversion");
$doc->debugNow("replacement dans \$hdecode des infos MySQL", True);
// $trans = file("./table-cp1252");

$arrTr = array();
foreach($trans as $line) if(preg_match("#^([^:]+):([^:]+):(.+)#", $line, $regs))
{
	$toChar   = $regs[1];
	$fromChar = $regs[2];
	$fromHex  = $regs[3];
	preg_match_all("#%([0-9A-Fa-f]+)#", $fromHex, $regAll);
	$v = "";
	foreach($regAll[1] as $b)
	{
		$k = hexdec($b);
		$c = chr($k);
		$v .= $c;
		$charsets = array('CP1252', 'ISO-8859-15', 'ISO-8859-1');
		$i = false;
		for($i = false, $x=0; $i == false && $x <3; $x++)
		{
// 			$i = iconv('CP1252', 'UTF-8', $v);
 			$i = @iconv($charsets[$x], 'UTF-8', $v);
// 			if(!$i) $i = iconv('ISO-8859-15', 'UTF-8', $v);
		}
	}
	$err = ($i != $fromChar)? "color:#ff0000":"";
	$chr = ($i == $fromChar)? "'$i'":"**'$i' ($fromChar)";
	$chrset = ($i == $fromChar)? "":"({$charsets[$x]})";
	if($_REQUEST["showTr"]) echo "\n<p style='$err'>$chr =>$v $chrset</p>";
	if($i) $arrTr["$i"] = $regs[1];
}
$doc->debugNow("replacement dans la table de conversion", True);
$hdecode = strtr($hdecode, $arrTr);
$doc->debugNow("Application de la table de conversion à \$hdecode", True);

$f = fopen($fDest, "w");
fwrite($f, $hdecode);
fclose($f);
chmod($fDest, 0666);
echo "<br>Conversion done <span id=echofile>in <a href='convertall.php?echofile=$fDest' onclick=\"h=document.getElementById('echofile');console.log(h.innerHTML);h.innerHTML	=''\">$fDest</a></span>";
$_SESSION["echofile"] = "$fDest";
// die($doc->checkDebug());
?>
