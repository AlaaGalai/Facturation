<?php
require_once("../inc/autoload.php");
session_start();
$doc=new prolawyer;
$doc->title();
$doc->body(0, "document.forms[0].submit()");
// $doc->tab_affiche();
// die();

$var_vat = "";
$var_vat_array = array();
$replace = array();
foreach($_POST as $n => $v)
{
	if(substr($n, 0, 10) == "var_vat_tx")
	{
		$num = substr($n, 10);
		if(trim($_POST["$n"]) !== "" && trim($_POST["var_vat_rf$num"]) !== "") 
		{
			$var_vat_array[number_format(preg_replace('/,/', '.',$_POST["$n"]), 2)] = number_format(preg_replace('/,/', '.',$_POST["var_vat_rf$num"]), 2);
		}
	}
	elseif(preg_match("#^place_(.*)#", $n, $regs))
	{
		if(trim($v) != "")
		{
			$v = (int) $v -1; #les indices apparents pour l'utilisateur sont une unité plus élevés que les indices de tableaux
			$l = (int) $regs[1] -1; #idem
// 			var_dump($l, $v);
			$replace[$l] = $v;
		}
	}
}

$arrSSTrait = array();
$soustraitantsListe = "";
// $soustraitantsListe2 = "";
foreach($_POST["soustraitant_alias"] as $n => $value)
{
	if($value)
	{
		$check=$_POST["soustraitant_droit"][$n] ? 1:"";
// 		$soustraitantsListe .= "$value,{$_POST["soustraitant_nom"][$n]},$check,\n";
		$arrSSTrait[] = "$value,{$_POST["soustraitant_nom"][$n]},$check,\n";
	}
}

// $doc->tab_affiche($arrSSTrait);
foreach($replace as $num => $replaced)
{
// 	echo ("<br>Remplacement de $num vers $replaced");
	$arrSSTrait = $doc->array_replace($num, $replaced, $arrSSTrait);
}
foreach($arrSSTrait as $line) $soustraitantsListe .= $line;

foreach($_POST["biblioName"] as $n => $value)
{
	if($value)
	{
		$type=$_POST["biblioType"][$n];
		$biblioListe .= "$value,$type,\n";
	}
}

ksort($var_vat_array);
// $doc->tab_affiche();
foreach($var_vat_array as $tx => $rf)
{
	if ($var_vat) $var_vat  .= ";";
	$var_vat .= "$tx=$rf";
}

if(isset($_POST["types_comptes"])) $ac_type=$_POST["types_comptes"]."\n";
else $ac_type="\n";

#bibliotheques est défini supra comme $biblioListe

if(isset($_POST["currency"])) $currency=$_POST["currency"];
else $currency="";

if(isset($_POST["types_delais"]))$delais_type=$_POST["types_delais"]."\n";
else $delais_type="\n";

if(isset($_POST["types_dossiers"])) $dossiers_type=$_POST["types_dossiers"]."\n";
else $dossiers_type="\n";

if(isset($_POST["lieux"])) $lieux=$_POST["lieux"];
else $lieux="";

if(isset($_POST["types_adresses"])) $ltype=$_POST["types_adresses"]."\n";
else $ltype="\n";

if(isset($_POST["mailing"])) $mailing=$_POST["mailing"];
else $mailing="";

if(isset($_POST["types_matieres"]))$matiere_type=$_POST["types_matieres"]."\n";
else $matiere_type="\n";

if(isset($_POST["types_operations"])) $op_type=$_POST["types_operations"]."\n";
else $op_type="\n";

if(isset($_POST["origine_mandat"])) $origine_mandat=$_POST["origine_mandat"]."\n";
else $origine_mandat="\n";

if(isset($_POST["ouv_heure"])) if($_POST["ouv_heure"]) $ouv_heure=$_POST["ouv_heure"];
else $ouv_heure="0";

if(isset($_POST["ouv_minute"])) if($_POST["ouv_minute"]) $ouv_minute=$_POST["ouv_minute"];
else $ouv_minute="0";
$ouverture="$ouv_heure:$ouv_minute";

if(isset($_POST["pass_mbx"]))$pass_mbx=$_POST["pass_mbx"];
else $pass_mbx="";

if(isset($_POST["prix_defaut"])) if($_POST["prix_defaut"]) $prix_defaut=preg_replace('/,/', '.', "{$_POST["prix_defaut"]}");
else $prix_defaut="0";

if(isset($_POST["racine"]))$racine=$_POST["racine"];
else $racine="";

if(isset($_POST["racine_mbx"]))$racine_mbx=$_POST["racine_mbx"];
else $racine_mbx="";

if(isset($_POST["racine_webdav"]))$racine_webdav=$_POST["racine_webdav"];
else $racine_webdav="";

$soustraitants=$soustraitantsListe;

if(isset($_POST["tva_deb"])) $tva_deb=$_POST["tva_deb"];
else $tva_deb="";

if(isset($_POST["VAT_f"])) if($_POST["VAT_f"]) $tx_f_tva=preg_replace('/,/', '.', "{$_POST["VAT_f"]}");
else $tx_f_tva="0";

if(isset($_POST["VAT"])) if($_POST["VAT"]) $tx_tva=preg_replace('/,/', '.', "{$_POST["VAT"]}");
else $tx_tva="0";

#tx_var_tva est défini supra avec $var_vat

if(isset($_POST["use_webdav"]))$use_webdav=$_POST["use_webdav"];
else $use_webdav="0";

if(isset($_POST["user_mbx"]))$user_mbx=$_POST["user_mbx"];
else $user_mbx="";



#Options pour Imap


if(isset($_POST["username"])) $username=$_POST["username"];
else $username="";

if(isset($_POST["password"])) $password=$_POST["password"];
else $password="";

if(isset($_POST["host"])) $host=$_POST["host"];
else $host="MyServer.com";

if(isset($_POST["port"])) $port=$_POST["port"];
else $port="443";

if(isset($_POST["type"])) $type=$_POST["type"];
else $type="imap";

foreach(array(
	#Options de la base
	"ac_type" =>"$ac_type",
	"bibliotheques" => $biblioListe,
	"currency" =>"$currency",
	"delais_type" =>"$delais_type",
	"dossiers_type" =>"$dossiers_type",
	"lieux" =>"$lieux",
	"ltype" =>"$ltype",
	"matiere_type" =>"$matiere_type",
	"mailing" =>"$mailing",
	"op_type" =>"$op_type",
	"origine_mandat" => $origine_mandat,
	"ouverture" =>"$ouverture",
	"pass_mbx" =>"$pass_mbx",
	"prix_defaut" =>"$prix_defaut",
	"racine" =>"$racine",
	"racine_mbx" =>"$racine_mbx",
	"racine_webdav" =>"$racine_webdav",
	"soustraitants" =>"$soustraitants",
	"tva_deb" =>"$tva_deb",
	"tx_f_tva" =>"$tx_f_tva",
	"tx_tva" =>"$tx_tva",
	"tx_var_tva" => "$var_vat",
	"use_webdav" =>"$use_webdav",
	"user_mbx" =>"$user_mbx"
	
	#Options IMAP - not implemented for now
) as $option => $value)
{
	$doc->setUtilisateurOption($option, $value, $_POST["setcurrent"]);
 	//echo "<br>$option -> $value";
}

if(isset($_POST["doodle"])) $doc->setUtilisateurOption("doodle", $_POST["doodle"], $_POST["setcurrent"]);


//die();
echo $doc->form("config/modify_perso.php", "OK", "", "", "", "setcurrent", "{$_POST["setcurrent"]}");
$doc->close();
?>
