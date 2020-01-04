<?php
require_once("./inc/autoload.php");
session_start();

$_SESSION=array(); //si on se reconnecte, c'est pour supprimer la session existante, pas pour en garder une trace !

// foreach($_POST as $a => $b) echo "<br>$a vaut $b";
$doc=new prolawyer("firstCheck");
if(!$doc->getCookie("pda") && ! $_REQUEST["pda"] && (preg_match("/mobile/i", $_SERVER["HTTP_USER_AGENT"])) || $doc->androlawyerClient)
{
	$doc->setCookie("pda", "pda");
//  	echo "ici c'est bon";
}
$doc->addStyle = "petit";
$doc->title();
$doc->body(1, "document.getElementById('start_utilisateur').focus()");

//  $doc->tab_affiche(5);
//1. Test de l'existence des fonctions générales (le reste est vérifié ultérieurement par getOptions();
if(!function_exists("mysqli_query")) $doc->catchError("040-101:111", 4);
{
	echo $doc->echoError(); //C'est une erreur fatale, donc si la fonction manque, le fichier die() dans echoError();
}

//$verif1=FALSE;
$verif2=FALSE;
$verif3=FALSE;

//2. Test de l'existence d'un administrateur
if($_SESSION["isSetAdmin"]) $verif2 = true;
	
	
//3. Test pour vérifier si la configuration est à jour
if($_SESSION["tablesUpdated"] == $_SESSION["version"] && $_SESSION["fonctionsDispo"] == $_SESSION["version"]) $verif3=TRUE;




// echo $doc->doSingleTemplate("accueil", array("JAVASCRIPT" => "toto"));

$bColor="#d0d0d0";
$width = ($doc->getCookie("pda") == "pda")? "90vw":"30%";
$height = ($doc->getCookie("pda") == "pda") ? ";height:80vh;padding-left:5vw;padding-right:5vw":"";
$iHeig = ($doc->getCookie("pda") == "pda") ? "height:6vw":"height:14px";
$dH = 'margin-top:0%;vertical-align:bottom;border-style:none;border-width:1px;border-color:#0000ff;display:inline-block';
echo "\n<div style='margin-left:auto;margin-right:auto;position:relative;width:$width{$height};align-self:center;align-content:center;padding:5px;background-color:$bColor;border-style:solid;border-width:1px;margin-top:10%;$fHeig'><br><br>";
echo "<style>img{border-bottom-style:solid;border-bottom-width:2px;padding-bottom:2px;border-bottom-color:$bColor} button{background-color:$bColor}</style>";
echo "\n<div style=';margin-left:0px;margin-right:auto;width:40%;$dH'>";
echo "\n<form action=./index.php method = POST id=changelang>";
echo $doc->getLangs("menu", "background-color:#d0d0d0");
echo "</form>";
echo "\n</div>";
echo "\n<div style='margin-left:auto;margin-right:auto;text-align:center;$dH'>";
echo "\n<form action=./index.php method = POST id=changemode>";
echo $doc->getModes("menu", "background-color:#d0d0d0");
echo "</form>";
echo "\n</div>";

if($verif2 AND $verif3)
{
	echo "\n<div style='float:right;width:0vwx;text-align:right;$dH'>";
	echo "\n<a href=\"./config/config.php\"><img class='img' src='images/locked.png' style='$iHeig'></a>";
	echo "\n</div>";
}

// echo "\n<br><h1>{$doc->lang["index_h1"]}</h1>";
// echo "\n<h3>({$doc->lang["general_version"]} {$_SESSION["version"]})</h3>";
echo "\n<br><h1 style='padding-top:10vh'><img style='height:1em;width:1em;border-style:none;vertical-align:bottom' src='images/prolawyer_logo2.png'>&nbsp;Prolawyer {$_SESSION["version"]}</h1>";
echo "\n<br>";



if(/*$verif1==FALSE || */$verif2==FALSE)
{
	echo "{$doc->lang["index_not_config_1"]} <a href=\"./config/config.php?checkState=true\">{$doc->lang["index_not_config_2"]}</a> {$doc->lang["index_not_config_3"]}.";
	echo "\n<h3>{$doc->lang["index_restaurer"]}</h3>";
	echo "\n<form method=\"post\" action=\"./index.php\" enctype=\"multipart/form-data\">
	{$doc->lang["index_choisir_fichier"]} <input type=file name=\"fichier\">\n";
	echo $doc->input_hidden("restauration", "", "on");
	echo $doc->button("{$doc->lang["operations_valider"]}");
	echo "\n</form>";
}elseif($verif3==FALSE){
	echo "\n{$doc->lang["index_not_config_1bis"]}.\n<br>{$doc->lang["index_not_config_1ter"]} <a href=\"./config/config.php\">{$doc->lang["index_not_config_2"]}</a> {$doc->lang["index_not_config_3"]}.";
}else{
	if($doc->prolawyerClient || $doc->androlawyerClient	)
	{
		$affuser = "value={$_SERVER["PHP_AUTH_USER"]}";
		$affpwd  = "value={$_SERVER["PHP_AUTH_PW"]}";
	}
	$action="submition()";
	if($_GET["erreur"] == "rate1") echo "<span class=\"attention\">{$doc->lang["index_rate1"]}.</span>";
	if($_GET["erreur"] == "rate2")
	{
		echo "<span class=\"attention\">{$doc->lang["index_rate2"]}.</span>";
		$visit=$_GET["nextPage"];
		if(!$visit) $visit="resultat_recherche";
		$path=$_GET["nextPath"];
		if($path) $path .= "/";
		$lvisit="./".$path.$visit;
		$action=$lvisit;
		if(!$visit) $action="./resultat_recherche.php";
	}
	
	$javascript = "\n<script language=javascript>
		function submition()
		{
			selection=document.getElementById('next').selectedIndex;
			fname=document.getElementById('next').options[selection].value;
			aform=document.getElementById('main_form');
			nform=document.getElementById(fname);
			nform.start_utilisateur.value=aform.start_utilisateur.value;
			nform.start_pwd.value=aform.start_pwd.value;
// 			alert('fname= ' + fname + ' et selected vaut ' + selection + ' fin');
			nform.submit();
			return false;
		}\n</script>";
	echo $javascript;
	echo "\n<br>";
// 	echo "\n{$doc->lang["index_insert_data"]}";
	echo "\n<form $bSize id=\"main_form\" name=\"form\" method=\"post\" action=\"$action\" onSubmit=\"return submition()\">";
// 	echo "\n	<table>";
// 	echo "\n		<tr>";
	echo "\n<mtable><mtr>";
	echo "\n			<label for='start_utilisateur' style='display:table-cell;vertical-align:bottom;width:50%;$bAdd'>{$doc->lang["index_nom"]} :</label><input style='display:table-cell;vertical-align:bottom;width:50%;' type=\"text\" name=\"start_utilisateur\" id=\"start_utilisateur\" onfocus=\"select()\" $affuser><br>";
	echo "\n</mtr><mtr>";
// 	echo "\n		</tr>";
// 	echo "\n		<tr>";
	echo "\n			<label for='start_pwd' style='display:table-cell;width:50%;'>{$doc->lang["index_password"]} :</label><input style='display:table-cell;width:50%;' type=\"password\" name=\"start_pwd\" onfocus=\"select()\" $affpwd><br>";
	echo "\n</mtr></mtable><br><br>";
	// 	echo "\n		</tr>";
// 	echo "\n		<tr>";
// 	echo "\n			<td>";
	$doc->menu="noCheck";
	$doc->entete();
// 	echo "</td>";
// 	echo "\n		</tr>";
// 	echo "\n		<tr>";
// 	echo "\n			<td>";
	echo $doc->input_hidden("new_check", "", "on");
	echo "\n<br>";
	if($_GET["erreur"] == "rate2") echo $doc->input_hidden("restore", "", TRUE);
if($doc->getCookie("pda") == "pda")	echo "<div style=bottom:5vw;border-style:solid;border-width:1px;position:absolute>";
	echo $doc->button("{$doc->lang["index_connect"]}", "", $bSize);
if($doc->getCookie("pda") == "pda")	echo "</div>";
// 	echo "</td>";
// 	echo "\n		</tr>";
// 	echo "\n	</table>";
	echo "\n</form>";
}
if ($doc->arr_forms) foreach($doc->arr_forms as $num => $val)
{
	echo $doc->form($val["url"], "&nbsp;", "", "style=max-height:0px", "formulaire_$num<td>", $val["option1"], $val["val1"], $val["option2"], $val["val2"], $val["option3"], $val["val3"], $val["option4"], $val["val4"], $val["option5"], $val["val5"], "start_pwd", "", "start_utilisateur", "", "new_check", "on");
}
// if(! $doc->newPdaMenu) echo "</div>";
echo "</div>";
session_unset("lang");
$doc->close();
?>
