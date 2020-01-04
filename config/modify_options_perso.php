<?php
/****
* Titre........... : Modify_options_perso.php
* Description..... : Gestion des options du programme etude pour l'utilisateur
* version......... : 3.0
* date............ : 20.7.2005
* fichier......... : modify.php
* Auteur.......... : Olivier Subilia (etudeav@users.sourceforge.net)
*
* remarques....... : Peut s'utiliser pour configurer le courriel
* licence......... : The GNU General Public License (GPL) 
*					 http://www.opensource.org/licenses/gpl-license.html
*
****/
		
require_once("../inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
// $doc->connection();
$doc->title();
$doc->body(2);
$doc->entete();

// $doc->tab_affiche();




//variables de document
$number=isset($_POST["number"]) ? $_POST["number"] : "0";
$imap="imap".$number;
// $option_gen["imap"] = $doc->option_gen["imap"];
// print_r($option_gen["imap"]);

//mise à jour des options
if($_POST["delete"] == "on" && $_POST["confirm"] != "on")
{
	echo "<h2>$maj_op_confirm_h1</h1>";
	$doc->table_open("align=\"center\"");
	echo "<tr><td>";
	echo $doc->form("config/modify_options_perso.php", $general_oui, "", "attention", "", "delete", "on", "confirm", "on", "number", $_POST["number"]);
	echo "</td><td>";
	echo $doc->form("config/modify_options_perso.php", $general_non, "", "", "", "number", $_POST["number"]);
	echo "</td></tr>";
	$doc->table_close();
	$doc->close();
	die();

}
if($_POST["delete"] == "on" && $_POST["confirm"] == "on")
{
	unset($doc->option_gen["imap"]["$imap"]);
	$insert = $doc->setOptionsPerso("imap", $_SESSION["imap"]);
// 	$file =  $doc->describe_array($doc->option_gen["imap"], "option_gen[\"imap\"]", "array");
// 	$close=$doc->close_and_write($file, $doc->settings["optionconfigfile"]);
// 	
// 	$file=$doc->read_and_prepare($doc->settings["optionconfigfile"]);
// 	foreach($file as $line) eval($line);  //pour recharger les options modifiées
// 	$doc->option_gen["imap"] = $option_gen["imap"];
// 	$imap="imap0";
// 	$number=0;
}

if($_POST["new"] == "on" || $_POST["modify"] == "on")
{
	$values = $doc->code($_POST["mailusername"], $_SESSION["pwd"]).",".$doc->code($_POST["mailpassword"], $_SESSION["pwd"]).",{$_POST["accountname"]},{$_POST["host"]},{$_POST["port"]},{$_POST["type"]}";
	if(!isset($_SESSION["imap"]) || !is_array($_SESSION["imap"])) $_SESSION["imap"] = array();
	$_SESSION["imap"]["$imap"] = $values;
// 	echo "ON insert: $values";
// 	$doc->tab_affiche($_SESSION["imap"]);
	$insert = $doc->setOptionsPerso("imap", $_SESSION["imap"]);
// 	echo "insert: $insert";
	
// 	$doc->option_gen["imap"]["$imap"]["username"]=$doc->code($_POST["mailusername"], $_SESSION["session_pwd"]);
// 	$doc->option_gen["imap"]["$imap"]["password"]=$doc->code($_POST["mailpassword"], $_SESSION["session_pwd"]);
// 	$doc->option_gen["imap"]["$imap"]["accountname"]=$_POST["accountname"];
// 	$doc->option_gen["imap"]["$imap"]["host"]=$_POST["host"];
// 	$doc->option_gen["imap"]["$imap"]["port"]=$_POST["port"];
// 	$doc->option_gen["imap"]["$imap"]["type"]=$_POST["type"];
	
/*	$file[]="\$option_gen[\"imap\"][\"$imap\"][\"username\"]=\"".$doc->code($_POST["mailusername"], $_SESSION["session_pwd"])."\";";
	$file[]="\$option_gen[\"imap\"][\"$imap\"][\"password\"]=\"".$doc->code($_POST["mailpassword"], $_SESSION["session_pwd"])."\";";
	$file[]="\$option_gen[\"imap\"][\"$imap\"][\"host\"]=\"{$_POST["host"]}\";";
	$file[]="\$option_gen[\"imap\"][\"$imap\"][\"port\"]=\"{$_POST["port"]}\";";
	$file[]="\$option_gen[\"imap\"][\"$imap\"][\"type\"]=\"{$_POST["type"]}\";";*/
//	$doc->option_gen["file"] = $file;
// 	$temp = $doc->option_gen["imap"];
// 	$doc->tab_affiche($temp);
// 	$file =  $doc->describe_array($doc->option_gen["imap"], "option_gen[\"imap\"]", "array");
// 	$doc->tab_affiche($file);
// 	$file = $doc->describe_array($doc->option_gen["imap"], "doc->option_gen[\"imap\"]", "array");
	
// 	$file=array();
// 	$close=$doc->close_and_write($file, $doc->settings["optionconfigfile"]);
	
// 	$file=$doc->read_and_prepare($doc->settings["optionconfigfile"]);
// 	foreach($file as $line) eval($line);  //pour recharger les options modifiées
// 	$doc->option_gen["imap"] = $option_gen["imap"];


}

$doc->getOptionsPerso("force");

// $doc->tab_affiche($_SESSION);
//formulaire pour le courriel
//--------------------------------------début du formulaire------------------------
echo "\n<//Gestion du courriel//>";
echo "<form action=\"./modify_options_perso.php\" method=\"post\" name=formsetcurrent>";
echo "&nbsp;<select name=number onChange=formsetcurrent.submit()>";
$max=0;

// if(!is_array($option_gen["imap"])) $option_gen["imap"] = array();
if(isset($_SESSION["imap"]) && is_array($_SESSION["imap"])) foreach($_SESSION["imap"] as $nm => $val)
{
	list($username, $pwd, $accountname, $host, $port, $type) = preg_split("#,#", $val);
// 	echo "\n<br>... en train de tester $nm et $val";
	$selected="";
	if($imap == $nm)
	{
		$selected="selected";
		list($u, $p, $a, $h, $pt, $t) = preg_split("#,#", $val);
	}
	$strict_nm=preg_replace("#imap#", "", $nm);
	echo "\n<option value=\"$strict_nm\" $selected>$accountname";
	if($strict_nm > $max) $max = $strict_nm;
	if($strict_nm - $lastnumber > 1) $next=$lastnumber + 1;
	$lastnumber=$strict_nm;
}

// if($next == 0) $next = 1;

if(!($next)) $next=$max + 1;
if($number == 0)
{
	$number = $next;
	$new = TRUE;
	$button_text=$doc->lang["config_modify_create"]." (imap $number)";
}else{
	$button_text=$doc->lang["config_modify_maj_others"];
}
// if(!$option_gen["imap"]["$imap"]["accountname"]) $option_gen["imap"]["$imap"]["accountname"] = "imap".$number;

echo "\n<option value=\"0\">{$doc->lang["config_modify_nouveau_compte"]}";
echo "</select>";

$sup_cond = (!$new)? "<td class=\"attention\" style=cursor:hand onclick=\"document.getElementById('destruction').submit()\">$operations_supprimer</td>":"<td>&nbsp</td>";

echo $doc->button("{$doc->lang["operations_selectionner"]}", "");
echo "</form>";
echo "<form action=\"./modify_options_perso.php\" method=\"post\" name=modif>";
echo "<h4>{$doc->lang["config_modify_mail"]} :</h4>
	<table>
		<tr><td>{$doc->lang["config_modify_mail_accountname"]}</td><td><input type=text name=accountname value=\"$a\"></td></tr>
		<tr><td>{$doc->lang["config_modify_mail_username"]}</td><td><input type=text name=mailusername value=\"".$doc->decode($u, $_SESSION["pwd"])."\"></td></tr>
		<tr><td>{$doc->lang["config_modify_mail_password"]}</td><td><input type=password name=mailpassword value=\"".$doc->decode($p, $_SESSION["pwd"])."\"></td></tr>
		<tr><td>{$doc->lang["config_modify_mail_host"]}</td><td><input type=text name=host value=\"$h\"></td></tr>
		<tr><td>{$doc->lang["config_modify_mail_port"]}</td><td><input type=text name=port value=\"$pt\"></td></tr>
		<tr><td>{$doc->lang["config_modify_mail_type"]}</td><td><input type=text name=type value=\"$t\"></td></tr>
		<tr><td>".$doc->button("$button_text")."</td>$sup_cond</tr>
		<tr><td colspan=2>&nbsp;</td></tr>
		<tr><td>";
echo $doc->input_hidden("number", "", "$number");
if($new) echo $doc->input_hidden("new", "", "on");
else echo $doc->input_hidden("modify", "", "on");
echo "";
echo "</td></tr>
</table>
</form>";
echo $doc->form("config/modify_options_perso.php", "", "", "", "destruction<td>", "delete", "on", "number", $_POST["number"]);
$doc->close();
?>
