<?php
//
//initially created by lucien, august 2004, lkr@bluewin.ch
//maintained then completely rewritten by Olivier Subilia, etudeav@users.sourceforge.net
//


require_once("./inc/autoload.php");
session_start();

$doc=new ra;
$doc->title();
$doc->body(2);
$doc->entete();

//$doc->tab_affiche();

echo "<h2>$ra_title</h2>";
echo "\n".$doc->table_open("width=\"100%\" border = 0");
echo $doc->calendarSelect($_POST["date_cours"], TRUE, $doc->liste_soustraitant."LSSTRAIT");
echo "</td>";
echo "</tr>".$doc->table_close."\n<br>\n<br>";



echo $doc->table_open("width=100% align=center");
$arr_soustrait=explode(",", $doc->liste_soustraitant);
foreach($arr_soustrait as $nom) if(trim($nom) != "")
{
	echo "\n<tr><th colspan=\"2\">&nbsp;</th></tr>";
	echo "\n<tr><th colspan=\"2\">$nom</th></tr>\n<tr align=center><td>";
	echo "\n<tr><th>$ra_new_pourcents</th><th>$ra_new_heures</th></tr>";
	echo "\n<tr align=center><td>";
	$doc->send_image(5, trim($nom));
	echo "</td><td>";
	$doc->send_image(6, trim($nom));
	echo "</td></tr>";
}
echo $doc->table_close();
$doc->close();
?>
