<?php
require_once("../inc/autoload.php");
session_start();
error_reporting(7);
$doc=new biblio;
if($_REQUEST["action"] == "create") $doc->lang["biblio_creer_livre_title"] = $doc->lang["biblio_nouveau"];
$doc->title();
$doc->body(2, "document.getElementById('form').titre.focus()");
$doc->entete();
?>
<h1>Liste des ouvrages pr&ecirc;t&eacute;s</h1>
<br>
<table>
<?php
$titre=strtolower($titre);
$titrebis="lcase(concat(debut_titre, titre, sous_titre)) like '%".str_replace(" ", "%' and lcase(concat(debut_titre, titre, sous_titre)) like '%", $titre)."%'";
$auteurbis="lcase(concat(auteur1, auteur2, auteur3, auteur4, auteur5)) like '%".str_replace(" ", "%' and lcase(concat(auteur1, auteur2, auteur3, auteur4, auteur5) like '%", $auteur)."%'";

$controle="";
$result=mysqli_query($doc->mysqli, "select *, date_format(prete_le, \"%d\") as date_jour, date_format(prete_le, \"%c\") as date_mois, date_format(prete_le, \"%Y\") as date_annee, no_volume/2*2 as no from biblio where $titrebis and $auteurbis and type='{$_SESSION["biblioType"]}' and collection='{$_SESSION["biblioNom"]}' and (prete_le>'0000-00-00' or prete_a<>'') order by prete_a, titre, no, sous_titre limit 0,30");
$nb=mysqli_num_rows($result) ;
if($nb>1) $pluriel="s";
echo "$nb livre".$pluriel." trouv&eacute;".$pluriel."<br>";
while($row=mysqli_fetch_array($result)){
if($row["titre"]<>$controle){
echo "<tr><td colspan=3><br>", trim($row["debut_titre"]);
if(substr(trim($row["debut_titre"]), -1, 1)<>"'") echo " ";
echo "<b>", $row["titre"], "</b></td></tr>";
$controle=$row["titre"];
}
echo "</b></td>
<td>";
if($row["no_volume"]) echo "vol. ", $row["no_volume"];
else echo "&nbsp;";
echo "</td>
<td><i>";
if($row["sous_titre"]) echo $row["sous_titre"];
else {
echo $row["debut_titre"];
if(substr(trim($row["debut_titre"]), -1, 1)<>"'") echo " ";
echo $controle;
}
echo "</i></td>
<td>";
echo "(", $row["auteur1"];
if($row["auteur2"]) echo "&nbsp;/&nbsp;", $row["auteur2"];
if($row["auteur3"]) echo "&nbsp;/&nbsp;", $row["auteur3"];
if($row["auteur4"]) echo "&nbsp;/&nbsp;", $row["auteur4"];
if($row["auteur5"]) echo "&nbsp;/&nbsp;", $row["auteur5"];
echo ")</td>
<td>pr&ecirc;t&eacute; &agrave; ", $row["prete_a"];
if(!$row["prete_a"]) echo "<span style=\"font-style:italic; color:ff1010\">Inconnu</span>";
echo " le ", $row["date_jour"], ".", $row["date_mois"], ".", $row["date_annee"], "</td>";
if($session_utilisateur<>"guest") echo"<form method=post action=\"./restitution.php\">
<td><input type=hidden name=no_fiche value=\"", $row["no_fiche"], "\">
<input type=submit value=restitution></td></form>";
echo "</tr>";
}
?>
</table>
</body>
</head>
</html>
