<?php session_start(); ?>


<?php
require("./functions.php");

# foreach($_REQUEST as $nom => $val) $$nom =  addslashes(stripslashes($val));

if(!$_POST["largeur"] || !isset($_POST["largeur"]))
{
	$_POST["largeur"] = "1";
	#echo "tralala";
}
$date=strftime("%Y-%m-%d", time());
#echo "<br>$date</br>";

// foreach($_POST as $nom => $val) echo "\n<br>$nom a pour valeur $val";
// die();


$n=1;
while($n<7)
{
	$prov="auteur".$n."bis";
	$replace="auteur".$n;
	if(trim($_POST["$prov"])<>"")
	{
		ajoute_auteur($_POST["$prov"]);
		$_POST[$replace]=$_POST["$prov"];
	}
	$n++;
}

$insert="";
reset($_POST);
foreach($_POST as $nom=>$valeur)
{
	$valeur = addslashes(stripslashes($valeur));
	//echo substr($nom, 0, 5), "<br>";
	//echo "<br>$nom: $valeur";
	if(substr($nom, -3, 3)<>"bis" AND substr($nom, 0, 5)<>"check")
	{
		if($insert<>"") $insert=$insert.", ";
		$insert=$insert.$nom."='".htmlspecialchars($valeur)."'";
	}
	if(substr($nom, 0, 5)=="check")
	{
		if($insert<>"") $domaine=$domaine.", ".preg_replace("#_#", " ", substr($nom, 5, 40));
	}
}
$insert=$insert.", domaine='".preg_replace("#'#", "\'", $domaine)."'";
if($_REQUEST["action"]=="new") $insert .= ", nple='$date'";

//echo $insert;
//die();
require('./connection_data.php');
if($_REQUEST["action"]=="new") $succ=mysqli_query($doc->mysqli, "insert into biblio set $insert");
if($_REQUEST["action"]=="modify") $succ=mysqli_query($doc->mysqli, "update biblio set $insert where no_fiche like '$no_fiche'");

if($succ)
{
	$titre=rawurlencode($titre);
	$debut=rawurlencode($debut_titre);
	echo "<html>
	
	<head>
	<title>Modification effectu&eacute;e!<?php echo $titre?></title>
	<Meta http-equiv=\"Refresh\" CONTENT=\"0; URL=./liste_ouvrages.php?titreexact=$titre&debutexact=$debut\">
	</head>
	
	<body>
	<h2>Modification ins&eacute;r&eacute;e<h2>";
}
?>

<br>
<a href="./liste_ouvrages.php?titreexact=<?php echo $titre?>&debutexact=<?php echo $debut?>">Retour</a>


</body>

</html>
