<?php
$number = $_GET["number"];
$number = preg_replace("#^\+#", "00", $number);
$url = "http://asterisk2.internal.avocats-ch.ch/cgi-bin/call.php?number=$number&ip={$_GET["ip"]}";
//die($number);

$open = fopen($url, "r") or die("err...");


$path = ($_GET["path"])? "..":".";
$path = ($_GET["path"]);
//$path = ".";
echo "<html>
<head>
<title>Tel</title>
</head>
<body onload=\"alert('réussi')\">
<img width=12 src=\"$path/images/tel.png\">
</body>
</html>";
?>
