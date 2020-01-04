<?php
require_once("../inc/autoload.php");
session_start();
$doc=new prolawyer();
$docList = array();
foreach($doc->docTypes as $docType)
{
	$valName = "config_templates_$docType";
	$docList[$docType] = $doc->lang[$valName];
}

$pMatch = "^.+\.([^.]+$)";
$fMatch = "^facture\.([^.]+$)";
if (! $_REQUEST["setcurrent"]) $_REQUEST["setcurrent"] = "00";
$tplPath =  $_SESSION["tplPath"] ."{$_SESSION["slash"]}{$_REQUEST["setcurrent"]}";
if(! is_dir($tplPath)) mkdir($tplPath);

##Download d'un fichier
if($_REQUEST["download"])
{
	preg_match("/$pMatch/", $_REQUEST["download"], $reg);
	$ext = $reg[1];
	if($ext == "txt") header("Content-type: text/plain");
	elseif($ext == "rtf") header("Content-type: text/rtf");
	elseif($ext == "odt") header("Content-type: application/vnd.oasis.opendocument.text");
	elseif($ext == "ott") header("Content-type: application/vnd.oasis.opendocument.text-template");
	else header("Content-type: application/vnd.sun.xml.writer");
	header("Content-Disposition: attachment; filename=\"{$_REQUEST["download"]}\"");
	echo readfile("$tplPath{$_SESSION["slash"]}{$_REQUEST["download"]}");
	die();
}

$doc->getTemplates(True);
$doc->title();
$doc->body();
$doc->entete();
// $doc->tab_affiche($doc->usableTemplates);

##Download d'un fichier
if($_FILES["upload"] && $_FILES["upload"]["tmp_name"])
{
	$cp = copy($_FILES["upload"]["tmp_name"], "$tplPath{$_SESSION["slash"]}{$_FILES["upload"]["name"]}");
	if($_POST["fileType"])
	{
		$fileData = "$tplPath{$_SESSION["slash"]}{$_FILES["upload"]["name"]}.datas";
		$f = fopen("$fileData", "w+");
		$text = "filetype={$_POST["fileType"]}";
		$w = fwrite($f, $text);
		$c = fclose($f);
		
	}
	//if($cp) @chmod("$tplPath{$_SESSION["slash"]}{$_FILES["upload"]["name"]}", 0666);
}

if($_REQUEST["delete"])
{
	if($_REQUEST["setcurrent"] == "00" || $doc->testval("ecrire", $_REQUEST["setcurrent"]))
	{
		if($_REQUEST["confirm"])
		{
			$file = "$tplPath{$_SESSION["slash"]}{$_REQUEST["delete"]}";
			$fileData = "$file.datas";
			if (is_file("$file")) unlink($file);
			if (is_file("$fileData")) unlink($fileData);
		}
		else
		{
			echo "\n<h2 class=attention>{$doc->lang["supprimer_dossier_confirm_h11"]}</h2>";
			echo "\n<br>". preg_replace("/{##}/", $_REQUEST["delete"], $doc->lang["config_templates_confirm"]);
			echo "\n<br>{$doc->lang["supprimer_dossier_confirm_h12"]}";
			echo $doc->table_open();
			echo "<tr><td><form style=\"display:inline\" action=\"./templates.php\" method=\"post\">";
			echo $doc->button($doc->lang["general_oui"], "", "buttonattention");
			echo $doc->input_hidden("delete", 1);
			echo $doc->input_hidden("confirm", "", "on");
			echo $doc->input_hidden("confirm", "", "on");
			echo $doc->input_hidden("setcurrent", 1);
			echo "</form>";
			echo "</td><td>";
			echo $doc->form("config/templates.php", "{$doc->lang["general_non"]}", "{$doc->lang["general_non_accesskey"]}");
			echo "</td></tr>";
			echo $doc->table_close();
		}
	}
}

if($_REQUEST["update"])
{
	$fileData = "$tplPath{$_SESSION["slash"]}{$_REQUEST["update"]}.datas";
	$f = fopen("$fileData", "w+");
	$text = "";
	if($_REQUEST["fileType"]) $text = "filetype={$_POST["fileType"]}\n";
	if($_REQUEST["fileAdd"]) $text .= "fileadd={$_POST["fileAdd"]}\n";
	$w = fwrite($f, $text);
	$c = fclose($f);

}

$liste = $doc->liste_des_utilisateurs;

echo "<form action=\"./templates.php\" method=\"post\" name=formsetcurrent>";
echo "<h2>{$doc->lang["config_templates_h2"]}</h2> ";
echo "&nbsp;<select name=setcurrent onChange=formsetcurrent.submit()>";
echo "<option value=\"00\">{$doc->lang["ra_global"]}";
foreach($liste as $base_avoc => $ligne)
{
	$selected="";
	$initavocat=substr($base_avoc, 0, 2);
	
	if($doc->right=="admin" || ($doc->testval("ecrire", $initavocat)))
	{
		if($_REQUEST["setcurrent"] == $initavocat) $selected="selected";
		echo "<option value=\"$initavocat\" $selected>{$ligne["nom"]}";
		$verif["a_un_droit"]="ok";
	}
}
echo "</select>";
if($verif["a_un_droit"]=="ok") echo $doc->button("{$doc->lang["operations_selectionner"]}", "");
echo "</form>";



$dir = opendir($tplPath);

$exts = array(
"ott",
"odt",
"stw",
"sxw",
"rtf",
"txt"
);
// $documents = array();
// $factures = array();
// while($file = readdir($dir))
// {
// 	if(preg_match("#$fMatch#", $file, $reg) && $reg[1] != "datas") 
// 	{
// 		$ext = $reg[1];
// 		$factures[$file] = array_search($ext, $exts);
// 	}
// 	elseif(preg_match("#$pMatch#", $file, $reg) && $reg[1] != "datas")
// 	{
// 		$ext = $reg[1];
// 		$documents[$file] = array_search($ext, $exts);
// 	}
// }

$documents = array("Autres");
while($file = readdir($dir))
{
	if(preg_match("/\.([^.]+)$/", $file, $reg) && $reg[1] != "datas")
	{
		$ext = $reg[1];
		$fileData = "$tplPath{$_SESSION["slash"]}$file.datas";
		$fileType = "other";
		if(is_file($fileData))
		{
			$f = file("$fileData");
			foreach($f as $line)
			{
// 				echo "<br>$line";
				list($key, $value) = preg_split("#=#", $line);
				$value=trim($value);
				if($key == "filetype") $fileType = $value;
				
			}
		}
		$type = $fileType;
		if(!is_array($documents[$fileType])) $documents[$fileType] = array();
		$documents[$fileType][] = array("nom" => $file, "type" => $type, "ext" => $ext);
	}
}

// asort ($factures);
asort ($documents);
// $doc->tab_affiche($files);

$curFileType = False;

// $doc->tab_affiche($documents);

foreach($documents as $fileType => $ar)
{
	if($curFileType != $fileType)
	{
		echo "<h3>{$doc->lang["config_templates_$fileType"]}</h3>";
		echo "<ul>";
		$docs = $documents[$fileType];	
		asort($docs);
		foreach($docs as $num =>$document)
		{
			$file = $docs[$num]["nom"];
			$ext  = $docs[$num]["ext"];
			$type = $docs[$num]["type"];
			$mod  = "<form action=\"./templates.php\" method=POST style='display:inline'>";
			$mod .= "<select name='fileType'>\n<br>";
			$mod .= $doc->simple_selecteur($docList, $type, 2);
			$mod .= "</select>";
			$mod .= $doc->input_hidden("update", "", $file);
			$mod .= $doc->button("maj");
			$mod .= "</form>";
			$del = $doc->advice($doc->lang["config_template_tip_delete"], "false", "onclick=\" window.location='./templates.php?delete=$file'\" style=\"cursor:pointer\"");
			$download = $doc->advice($doc->lang["config_template_tip_download"], "download", "onclick=\" window.location='./templates.php?download=$file'\" style=\"cursor:pointer\"");
			echo "\n<li style=\"list-style-image : url({$doc->settings["root"]}images/$ext.png);\">$file $del $download $mod</li>";
		}
		echo "</ul>";
	}
}

// $fileTypes = array("facture", "lettre");
echo "<h3>{$doc->lang["config_templates_upload"]}</h3>";
echo "<form action=\"./templates.php\" method=POST enctype=\"multipart/form-data\">\n<input type=file name=upload>\n<br><select name='fileType'>\n<br>";
echo $doc->simple_selecteur($docList, array("lettre"), 2);
echo "\n</select>";
echo $doc->button("{$doc->lang["operations_valider"]}");
echo $doc->input_hidden("setcurrent", 1);
echo "</form>";
echo $doc->close();
?>
