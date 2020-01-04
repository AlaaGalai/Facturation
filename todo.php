<?php
require_once("./inc/autoload.php");
session_start();
$doc=new prolawyer();
$doc->title();
if($_REQUEST["size_h"] || $_REQUEST["size_v"])
{
	$sizeH = $_REQUEST["size_h"] ? $_REQUEST["size_h"]:400;
	$sizeV = $_REQUEST["size_v"] ? $_REQUEST["size_v"]:400;
	$doc->body(1, "resize('$sizeH','$sizeV')", "self.close()", "d0ffd0");
}
else
$doc->body(1, "", "self.close()");

$x=0;
$nfile = array();
if($_REQUEST["fichier"])
{
	if(is_file("./{$_REQUEST["fichier"]}_{$_SESSION["lang"]}.txt")) $fichier = "./{$_REQUEST["fichier"]}_{$_SESSION["lang"]}.txt";
	else $fichier = "./{$_REQUEST["fichier"]}.txt";
}
else $fichier = "TODO";
$file = file($fichier);
foreach($file as $x =>$line)
{
	$line = $doc->smart_html(trim($line));
	$y = $x -1;
	$z = $x + 1;
	$l = "";
	if(preg_match("/(^[[:space:]]*$)/", $line) && preg_match("/(^[[:space:]]*$)/", trim($file[$z])))
	{
		$nfile[] = "\n<br>";
	}
	if(preg_match("/(^[[:space:]]*$)|(^-+$)/", $line))
	{
		continue;
	}
	if(substr("$line", 0, 1) == "*")
	{
		if($listOpen)
		{
			$l = "</ul>\n";
			$listOpen = false;
		}
		$nfile[] = "$l<h3>".substr($line, 1)."</h3>";
	}
	elseif(preg_match("/^-+$/", trim($file[$z]), $regs))
	{
		if($listOpen)
		{
			$l = "</ul>\n";
			$listOpen = false;
		}
		$nfile[] = "$l<h2>$line</h2>";
	}
	elseif($fichier == "TODO" || substr("$line", 0, 1) == "-")
	{
		if(!$listOpen)
		{
			$l = "<ul>\n";
			$listOpen = true;
		}
		if(substr("$line", 0, 1) == "-") $line = trim(substr($line, 1));
		$line = "$l\t<li>$line</li>";
		$nfile[] = $line;
	}
	else
	{
		if($listOpen)
		{
			$l = "</ul>\n";
			$listOpen = false;
		}
		$nfile[] = "$l\n<br>$line";
	}
}

foreach($nfile as $line) echo "\n$line";
$doc->close
?>