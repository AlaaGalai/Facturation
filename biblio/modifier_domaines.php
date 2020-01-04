<?php
session_start();
//$open=fopen("./liste_domaines.txt", "w+");
$liste_domaines=readfile("./liste_domaines.txt");
echo $liste_domaines;
?>
