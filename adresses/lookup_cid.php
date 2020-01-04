<?php
require_once("../inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer("guest");

header('Content-Type: text/html; charset=utf-8');
$nom=False;
if(! $_GET["NUMBER"] || $_GET["NUMBER"] == "anonymous") die("Numero cache");
// $datas = file('datas.php');
// foreach($datas as $data)
// {
/*// 	if(preg_match("/[<?>]/", $data)) continue;*/
// 	list ($n, $v) = preg_split("/=/", $data);
// 	$$n = trim($v);
// }
// $con = @mysql_connect("localhost:3306", $user, $pwd) or die("?c:{$_GET["NUMBER"]}.'$user' '$pwd'");
// $db = @mysql_select_db("prolawyer") or die("?s:{$_GET["NUMBER"]}");
$nToSearch = preg_replace("#^(\\+|0(0)?)#", "", $_GET["NUMBER"]);
$nToSearch = trim($nToSearch);
$nToSearch = preg_replace("# #", "", $nToSearch);
$nToSearch = "%$nToSearch";
//echo "<br>cherche: '$nToSearch'<br>";
$q = "select id, trim(concat(prenom, ' ', nom)) as newnom from adresses where REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(tel, '(', ''),')', ''),'-', ''),' ', ''),'+', ''), '/', ''), '.', '') like '$nToSearch' OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(natel, '(', ''),')', ''),'-', ''),' ', ''),'+', ''), '/', ''), '.', '') like '$nToSearch' OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(telprive, '(', ''),')', ''),'-', ''),' ', ''),'+', ''), '/', ''), '.', '') like '$nToSearch' OR REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(natelprive, '(', ''),')', ''),'-', ''),' ', ''),'+', ''), '/', ''), '.', '') like '$nToSearch'";
// echo $doc->beautifyMysql($q);
$q = "select id, trim(concat(prenom, ' ', nom)) as newnom from adresses where ";
$cl = "";
foreach(array("tel", "telprive", "natel", "natelprive") as $champ)
{
// 	echo "<br>$champ";
	$cl1 = "REGEXP_REPLACE($champ, '[-() +/.]', '')  like '$nToSearch'";
	if($cl) $cl .= " OR ";
	$cl .= $cl1;
}
$q .= $cl;
// echo $doc->beautifyMysql($q);
$e = mysqli_query($doc->mysqli, $q);
while($r = mysqli_fetch_array($e)) $nom = $r["newnom"];
$nom = preg_replace("#  #", " ", $nom);
if(!$nom)
{
	$nToSearch2 = preg_replace("# #", "", $_GET["NUMBER"]);
	$n =1;
	$ans = file("http://tel.search.ch/api/?was={$_GET["NUMBER"]}&key=d0a0e12ab2ce8cae2312e24d634a421a&maxnum=1&lang=fr");
	$ans = file("http://tel.search.ch/api/?was={$nToSearch2}&key=d0a0e12ab2ce8cae2312e24d634a421a&maxnum=1&lang=fr");
	foreach($ans as $line)
	{
		//echo "\n$n. $line";
		if (preg_match("#<tel:name>(.*)</tel:name>#", $line, $arr))
		{
			$nom = strtoupper("{$arr[1]}");
		}
		if (preg_match("#<tel:firstname>(.*)</tel:firstname>#", $line, $arr))
		{
			//echo "ON A TROUVE $nom";
			$nom = "{$arr[1]} $nom";
		}
		$n++;
	}
	if($nom) $nom = "*".$nom;
}
if(!$nom)
{
	$nom = $_GET["NUMBER"];
}
echo $nom;
?>
