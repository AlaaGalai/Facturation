<?php session_start(); ?>


<?php
$prete_le=date("Y-m-d",time());
require('./connection_data.php');
$succ=mysqli_query($doc->mysqli, "update biblio set prete_a='$prete_a', prete_le='$prete_le' where no_fiche like '$no_fiche'");

if($succ){
echo "<html>

<head>
<title>Modification effectu&eacute;e!</title>
<Meta http-equiv=\"Refresh\" CONTENT=\"0; URL=./liste_ouvrages.php\">
</head>

<body>
<h2>Modification ins&eacute;r&eacute;e<h2>";
}
?>

<br>
<a href="./liste_ouvrages.php">Retour</a>

</body>

</html>
