<?php session_start(); ?>
<html>
<head>
<title>Liste des nouveautes</title>
<style type="text/css">
#c{color:ff1010}
</style>
<body onload=document.forms[0].elements[0].focus()>
<?php require("./entete.php");
require("functions.php");
require("connection_data.php");
foreach($_POST as $nom => $val) echo "<br>$nom a pour valeur $val";;
?>
<h1>Liste des nouveaut&eacute;s</h1>
<?php 

//détermination de la page actuelle
$sem=strftime("%V", time());
$yer=strftime("%Y", time());
if($_POST["sem"]) $sem = $_POST["sem"];
if($_POST["yer"]) $yer = $_POST["yer"];


//obtention des données
$q = "select * from nouveautes where sem like '$sem' AND yer like '$yer'";
$e = mysqli_query($doc->mysqli, $q) or die(mysqli_error($doc->mysqli));
if(!mysqli_num_rows($e))
{
	echo "Introduction des donn&eacute;es";
	$texte = file_get_contents("http://www.crobar.ch/bandes_dessinees_nouveautes.asp?ep=$sem");
	$motif = "|\<td valign=\"top\" class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck(this,0);\"\>LEMON INK \</td\>[^\>]*\<td valign=\"top\"\>&nbsp;\</td\>[^\>]*\<td valign=\"top\" class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck(this,0);\"\>BAUMANN\</td\>[^\>]*\<td valign=\"top\"\>&nbsp;\</td\>[^\>]*\<td valign=\"top\" nowrap class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck(this,0);\"\>5EME COUCHE\</td\>[^\>]*\</tr\>|m";
	$motif = "|td valign=\"top\" class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck\(this,[0-9]+\);\">([^\<]*)</td>[^<]*<td valign=\"top\">&nbsp;</td>[^<]*<td valign=\"top\" class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck\(this,[0-9]+\);\">([^\<]*)</td>[^<]*<td valign=\"top\">&nbsp;</td>[^<]*<td valign=\"top\" nowrap class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck\(this,[0-9]+\);\">([^<]*)</td>|m";
	$nb = preg_match_all($motif, $texte, $donnees, PREG_SET_ORDER);
	foreach($donnees as $n => $arr)
	{
		//echo "\n<br><br>Tableau $n:";
		foreach($donnees[$n] as $n2 => $donnee)
		{
			if($n2 == 1) //Nom
			{
				$N = $n +1;
				unset($noAlbum);
				unset($soustitre);
				unset($evSTitre);
				$titre = $donnee;
				if(preg_match("#(.*) T([0-9]+(.*))#", $titre, $noms))
				{
					$titre = $noms[1];
					$noAlbum = $noms[2];
					$evSTitre = $noms[3];
					$noAlbum += 0;
				}
				list(, $soustitre) = preg_split("#-#", $evSTitre);
				$titre = trim($titre);
				$soustitre = trim($soustitre);
				echo "\n<br>$N.";
				echo " <b>" .$doc->my_htmlentities($titre)."</b>";
				if($noAlbum) echo " tome $noAlbum";
				if($noAlbum && $soustitre) echo "&nbsp;:";
				if($soustitre) echo " " .$doc->my_htmlentities($soustitre);
			}
			if($n2 == 2) //Auteur
			{
				$donnee = preg_replace("#(/|&)#", "+", $donnee);
				$ord=1;
				$auteurs = explode("+", $donnee);
				$lAuteurs = "";
				foreach ($auteurs as $auteur)
				{
					$auteur = trim($auteur);
					if($lAuteurs) $lAuteurs .= ";";
					$lAuteurs .= "<i> $ord. " .$doc->my_htmlentities(ucfirst(strtolower($auteur)))."</i>";
					$ord ++;
				}
				echo $lAuteurs;
			}
			if($n2 == 3) //Editeur
			{
				echo ", " .$doc->my_htmlentities(ucfirst(strtolower($donnee)));
			}
		}
	}
	//print_r($donnees[0]);
	//echo $texte;
}
while ($r = mysqli_fetch_array($e))
{

}

?>
</body>
</head>
</html>
