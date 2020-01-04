<?php
require_once("./inc/autoload.php");
session_start();
$doc = new prolawyer();

$q = strtolower($_GET["q"]);


$sql="select * from {$_SESSION["session_avdb"]} INNER JOIN adresses on {$_SESSION["session_avdb"]}.noadresse = adresses.id WHERE adresses.nom LIKE '%$q%' && {$_SESSION["session_avdb"]}.noarchive = '0' or {$_SESSION["session_avdb"]}.noautref LIKE '%$q%' && {$_SESSION["session_avdb"]}.noarchive = '0' or {$_SESSION["session_avdb"]}.noautref2 LIKE '%$q%' && {$_SESSION["session_avdb"]}.noarchive = '0' or {$_SESSION["session_avdb"]}.noautref3 LIKE '%$q%' && {$_SESSION["session_avdb"]}.noarchive = '0' or {$_SESSION["session_avdb"]}.noautref4 LIKE '%$q%' && {$_SESSION["session_avdb"]}.noarchive = '0' ORDER by adresses.nom";//

echo "$sql<br>";
$requete1=mysqli_query($doc->mysqli, $sql) or die (mysqli_error($doc->mysqli));

//$resultat1 = mysql_query($requete1);
while($resultat1=mysqli_fetch_array($requete1)){

$noadresse=$resultat1["noadresse"];
$nopa=$resultat1["nopa"];
$nodossier=$resultat1["nodossier"];

	if ($resultat1["noautref"] != ""){
	$showref1 = "{$resultat1["noautref"]}";
	} else {
	$showref1 = "";
	}
	
	if ($resultat1["noautref2"] != ""){
	$showref2 = " {$resultat1["noautref2"]}";
	} else {
	$showref2 = "";
	}
	
	if ($resultat1["noautref3"] != ""){
	$showref3 = " {$resultat1["noautref3"]}";
	} else {
	$showref3 = "";
	}
	
	if ($resultat1["noautref4"] != ""){
	$showref4 = " {$resultat1["noautref4"]}";
	} else {
	$showref4 = "";
	}

	$showref = $showref1 . $showref2 . $showref3 . $showref4;
	
$requete2 = "SELECT * FROM adresses WHERE id = $noadresse";
$resultat2 = mysqli_query($doc->mysqli, $requete2);
while ($enregistrement2 = mysqli_fetch_array($resultat2)) {

$art = $enregistrement2["nom"] . " " . $enregistrement2["prenom"];
}

$requete3 = "SELECT * FROM adresses WHERE id = $nopa";
$resultat3 = mysqli_query($doc->mysqli, $requete3);
while ($enregistrement3 = mysqli_fetch_array($resultat3)) {

$art2 = $enregistrement3["nom"] . " " . $enregistrement3["prenom"];
$cltderoul = $art . " c/ " . $art2;


if ($showref != ""){
	$cltderoul = $cltderoul . "<BR>" . $showref;
}


$cid = $nodossier;
$cname = $cltderoul;
$cname = utf8_encode($cname);
echo "$cname|$cid\n";

}
}

//<p><font color="#000000">recognize </font></p>
?>

