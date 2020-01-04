<?php 
session_start();
error_reporting(8);

require("./connection_data.php");
$result=mysqli_query($doc->mysqli, "update biblio set prete_le='', prete_a='' where no_fiche like '$no_fiche'");
?>
<html>
<head>
<Meta http-equiv="Refresh" CONTENT="0; URL=./liste_prets.php">
</head>
<body>
</body>
</html>