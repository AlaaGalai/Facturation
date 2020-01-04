<?php
session_start();
?>
<html>

<head>
<title>Place des ouvrages sur les rayonnages</title>
</head>

<body onload=document.forms[0].elements[1].select()>
<?php require("./entete.php");
require("functions.php"); ?>

<?php
$auto_classement=TRUE;
if($auto_classement)
{
	$tablards="./liste_tablards.txt";
	$liste_tablards=file($tablards);
	$somme_tablard=0;
	foreach($liste_tablards as $num) if(is_numeric(trim($num)))
	{
		$somme_tablard += $num;
		$val_tablards[] = $num;
	}
	echo "<br>le nombre total de tablards fait $somme_tablard cm<br>";
	
	require("./connection_data.php");
	$requete_nombre = "select sum(largeur) as place from biblio";
	$exec_nombre = mysqli_query($doc->mysqli, $requete_nombre);
	while ($row=mysqli_fetch_array($exec_nombre)) $largeur_totale=$row["place"];
	echo "<br>la largeur totale est de $largeur_totale pour $total_livres livres<br>";
	$requete_total="select *, no_volume/2*2 as no from biblio where type='1' order by replace(titre, ' ', ''), replace(debut_titre, ' ', ''), no, sous_titre";
	$requete=mysqli_query($doc->mysqli, $requete_total);
	$num_rows=mysqli_num_rows($requete);
	$noenreg=0;
	$notablard=0;
	$no_ordre=0;
	$total_tablard=0;
	$nb_sur_tablard=0;
	while ($row=mysqli_fetch_array($requete))
	{
		$noenreg+= $row["largeur"];
		$nb_sur_tablard ++;
		$place=$somme_tablard / $largeur_totale * $noenreg;
		if($place > $total_tablard)
		{
/*			echo "<br>place:$somme_tablard / $largeur_totale * $noenreg)";*/
			$nodutablard = $notablard + 1;
			$place=number_format($place / 100, 2, ".", "'");
			if($notablard>0) echo "<br>Contient $nb_sur_tablard livres";
			$nb_sur_tablard=0;
			echo "<br><br><b>Tablard n° $nodutablard</b>: commence avec {$row["debut_titre"]} <b>{$row["titre"]}</b>, <i>vol {$row["no"]}: {$row["sous_titre"]}</i><br>Tablard de {$val_tablards["$notablard"]} cm, le premier livre étant censé placé à $place m.";
			$total_tablard += $val_tablards[$notablard];
			$notablard ++;
		}
		
	}
	echo "<br>Contient $nb_sur_tablard livres";
}
?>

</body>

</html>
