<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->initZefix();

//Ne jamais afficher une page de dossier si le numéro de dossier n'est pas fourni.
if(!isset($_REQUEST["nodossier"]) && $_POST["new"]!= "on")
{
	header("Location:./resultat_recherche.php");
	die();
}

$doc->title("<script type=\"text/javascript\" src=\"./externe/XHRConnection.js\"></script>");



// $doc->tab_affiche();

if($_POST["print"]) $doc->body(2, "", "document.retour.submit()");
else $doc->body();
$doc->entete();
if(!$doc->testval("ecrire")) $_POST["print"]=true;
echo "<script language=JavaScript>
var loading='$imap_courriel_loading';
</script>\n";
$date_jour=date("d", time());
$date_mois=date("m", time());
$date_annee=date("Y", time());
$today="$date_annee-$date_mois-$date_jour";
if(!isset($_POST["nodossier"]) && $_GET["setNodossier"]) $_POST["nodossier"] = $_GET["setNodossier"];
if(!isset($_POST["nodossier"])) $test_nodossier=FALSE;
else $test_nodossier=TRUE; //nécessaire car si on crée un nouveau dossier et qu'on ne test rien la variable titre, définie avant, vaudra 0.
if ($retour=="") $retour=operations;

if($test_nodossier==FALSE) $doc->lang["modifier_donnees_title"]=preg_replace("#0#", "{$_POST["nodossier"]}", "{$doc->lang["modifier_donnees_title"]}"); //il faut tricher pour insérer cas échéant le nouveau numéro
 
$query_autre="select np, nple, mp, mple, naturemandat, originemandat, dormant, matiere, typedossier, prixhoraire, limitehono, alertehono, afacturer, abandon, tvadossier, dateouverture, datearchivage, noarchive, remarques, suivipar, liea, chemin, mailbox from {$_SESSION["session_avdb"]} where nodossier like '{$_POST["nodossier"]}'";
// echo "<br>'$query_autre'";
$resultat_recherche=mysqli_query($doc->mysqli, "$query_autre");
// echo mysqli_error($doc->mysqli);
$row=mysqli_fetch_array($resultat_recherche, MYSQLI_ASSOC);
$i=0;
$doc->dataDossier = array();
foreach($row as $nom_val=>$val)
{
	if($nom_val == "noarchive" AND $val == "0") $val = "";
	if(preg_match("#date(_)?#", $nom_val) AND ($val == "00" || $val == "0000" || $val == "0000-00-00")) $val = "";
	$$nom_val=$val;
	$doc->dataDossier["$nom_val"] = $val;
}

//vérifier quel est le numéro d'archive disponible
$rq="select MAX(noarchive) as plusgrand from {$_SESSION["session_avdb"]}";
$ex=mysqli_query($doc->mysqli, $rq);
while($row=mysqli_fetch_array($ex)) $pr_dispo=$row["plusgrand"] + 1;


if(trim($tvadossier) == "" || $_POST["new"] == "on") $tvadossier = $_SESSION["optionGen"]["tx_tva"];

if(!$_POST["print"])
{
	echo "<h1 ", $doc->qui_fait_quoi("$np", "$nple", "$mp", "$mple"),">";      
	
	if($_POST["new"]=="on") echo $doc->lang["modifier_donnees_h11"];
	else echo $doc->lang["modifier_donnees_h12"];
	echo "</h1>
	<h2>{$doc->lang["modifier_donnees_h2"]}</h2>";
}
else echo "<h1> {$doc->lang["modifier_donnees_title"]}</h1>";
//on insère un grand tableau pour les données du client et de la (des) partie(s) adverse(s).
echo "<table width=100% border=0>";

if(!$_POST["print"] || 1)
{ 
	//affichage "standard" réduit si pas d'affichage pour l'impression
	//un premier tableau interne est destiné à l'affichage des clients et pa
	//initialisation
	$doc->affiche_personne(False, $_POST["nodossier"]);
// 	$doc->tab_affiche($doc->donneesDuDossier);
	echo "<tr>";
	echo "<td valign=top><table border=0>";
	foreach(array("client" => array("contact", "pj"), "pa" => array("ca"), "aut" => array()) as $personne => $array)
	{
		$langData = "modifier_donnees_$personne";
		echo "\n<tr><td valign=bottom colspan=2><h3>{$doc->lang["$langData"]}</h3></td></tr>";
		$reste = "";
		for($x="";$x<5;$x++)
		{
			$afX = $x ? $x + 1: 1;
			$echo = "\n<tr><td>$afX.</td><td>";
			$echo .= $doc->affiche_personne("$personne", "{$_POST["nodossier"]}", "$x");
			$echo .= "</td></tr>\n";
			if(count($doc->donneesDuDossier["$personne{$x}"]) > 0)
			{
				foreach($array as $sub)
				{
					$langDataSub = "modifier_donnees_$sub";
					$echoRet = $doc->affiche_personne($sub, "{$_POST["nodossier"]}", "$x");
					if($echoRet != "NODATA") $echo .= "\n<tr><td>&nbsp;</td><td>{$doc->lang["$langDataSub"]}: $echoRet</td></tr>";
// 					echo "\n<tr><td>&nbsp;</td><td>{$doc->lang["data_client_pj"]}: ";
// 					echo $doc->affiche_personne("pj", "{$_POST["nodossier"]}", "$x");
// 					echo "</td></tr>";
				}
			}
	// 		else $doc->tab_affiche($doc->donneesDuDossier["{client$x}"]);
			if(! $x || count($doc->donneesDuDossier["$personne{$x}"]) > 0) echo $echo;
			elseif(!$reste) $reste = $echo;
		}
		if($_POST["print"])
		{
			if($personne == "client") echo "</table></td><td width=50%><table border=0>";
			elseif($personne == "pa") echo "</table></td></tr><tr><td colspan=2><table border=0>";
		}
		else echo $reste;
	}
	echo "</table></td>";
	
	if(!$_POST["print"])
	{
		//la deuxième cellule renfermera les données
		echo "<td valign=top width=70%>";
	// 	echo "<div class=\"popup_static\" id=\"popbox_static\"></div>";
		echo $doc->affichage_total;
		echo "</td>";
	}
	echo "</tr>";
}else{
	echo "<tr><td><table>";
	echo "\n<tr><td valign=bottom><h3>{$doc->lang["modifier_donnees_client"]}</h3></td></tr>";
	for($x=0;$x<5;$x++)
	{
		echo "\n";
		echo $doc->affiche_personne("client", "{$_POST["nodossier"]}", "$x");
	}
	echo "</table></td><td><table>";
	echo "\n<tr><td valign=bottom><h3>{$doc->lang["modifier_donnees_pa"]}</h3></td></tr>";
	for($x=0;$x<5;$x++)
	{
		echo "\n";
		echo $doc->affiche_personne("pa", "{$_POST["nodossier"]}", "$x");
	}
	echo "</table></td></tr>";
}
//on referme la table de ces deux données
echo "</table>";

//Autres données


$newStyle = ($_POST["new"] == "on") ? "class='attention_bg'" : "";

echo "<form action=\"./maj_op.php\"method=\"post\" name=\"modifOptions\">";
echo "<input type=\"hidden\" name=\"nodossier\" value=\"{$_POST["nodossier"]}\">";
echo "<input type=\"hidden\" name=\"retour\" value=\"modifier_donnees\">";
echo "<input type=\"hidden\" name=\"action\" value=\"modifier_dossier\">";
echo "<table class=autre>
<tr><td>&nbsp;</td><td>&nbsp;</td></tr>
<tr><td colspan=2><h3>{$doc->lang["modifier_donnees_autre"]}</h3></td></tr>";
echo "
<tr><td align=right>{$doc->lang["modifier_donnees_nature_mandat"]} :</td><td>";
if(!$_POST["print"]) echo "<input type=text size=27 name=naturemandat value=\"$naturemandat\">";
else echo "$naturemandat";
$etat = array("actif", "dormant", "attente_paiement", "attente_archivage", "a_boucler");
if($_POST["print"]) echo  ".";
echo  "&nbsp;{$doc->lang["modifier_donnees_typedossier"]}:&nbsp;";
$dm = "<select name='dormant'>";
foreach($etat as $n => $st)
{
	$selected = ($dormant == $n) ? "selected":"";
	if($dormant == $n) $dvalue = $st;
	$vst = $doc->lang["modifier_donnees_{$st}"];
	$dm .=  "<option value='$n' $selected>$vst</option>";
}
$dm .= "</select>";
if(!$_POST["print"]) echo $dm;
else echo $dvalue;
echo "</td></tr>
<tr><td align=right>{$doc->lang["modifier_donnees_type"]} : </td><td>";
if(!$_POST["print"]) echo "<select name=typedossier>";

$select=explode("\n", "{$_SESSION["optionGen"]["dossiers_type"]}");
$testval=0;
foreach($select as $option)
{
	list($abrev, $nom)=preg_split("#,#", $option);
	if($typedossier==$abrev)
	{
		$selected=" selected";
		if($_POST["print"]) echo trim($nom).".&nbsp;&nbsp;";
		$testval=1;
	}
	if(!$_POST["print"]) echo "<option value=\"$abrev\"$selected>$nom
	";
	$selected="";
}
if($testval==0) if(!$_POST["print"]) echo "<option value=\"$type_dossier\" selected>$type_dossier";
if(!$_POST["print"]) echo "</select>";
echo "&nbsp;{$doc->lang["modifier_donnees_prix"]} :&nbsp;";
if(!$_POST["print"]) echo "<input $newStyle type=text size=6 name=prixhoraire value=\"$prixhoraire\">";
else echo "$prixhoraire";
echo "&nbsp;{$_SESSION["optionGen"]["currency"]}. ";
echo "{$doc->lang["modifier_donnees_limitehono"]} :&nbsp;";
if(!$_POST["print"]) echo "<input type=text size=6 name=limitehono value=\"$limitehono\">";
else echo "$limitehono";
echo "&nbsp;{$_SESSION["optionGen"]["currency"]}. ";
echo "{$doc->lang["modifier_donnees_alertehono"]} :&nbsp;";
if(!$_POST["print"]) echo "<input type=text size=6 name=alertehono value=\"$alertehono\">";
else echo "$alertehono";
echo "&nbsp;{$_SESSION["optionGen"]["currency"]}";
echo "</td></tr>";

//tarifs des sous-traitants
$noTarif = 0;
$q = "select soustraitant, prixhoraire, id from {$_SESSION["session_tfdb"]} where nodossier like '{$_POST["nodossier"]}' order by prixhoraire DESC";
$e = mysqli_query($doc->mysqli, $q);
while($r = mysqli_fetch_array($e))
{
	if(!$_POST["print"]) echo "\n<tr><td align=right>{$doc->lang["modifier_donnees_tarif_soustraitant"]}</td><td><select name='special-soustraitant-{$_SESSION["session_tfdb"]}-bloc-{$r["id"]}'><option value=''></option>" . $doc->simple_selecteur("", $r["soustraitant"]) . "</select>&nbsp;" .$doc->input_texte("special-prixhoraire-{$_SESSION["session_tfdb"]}-bloc-{$r["id"]}", "", $r["prixhoraire"]) . "</td></tr>";
	else echo "\n<tr><td align=right>{$doc->lang["modifier_donnees_tarif_soustraitant"]}</td><td>{$r["soustraitant"]}&nbsp;:&nbsp;{$r["prixhoraire"]}</td></tr>";
}
if(!$_POST["print"])
{
	echo $doc->input_hidden("special-nodossier-{$_SESSION["session_tfdb"]}-bloc-control@soustraitant", "", $_POST["nodossier"]);
	echo "\n<tr><td align=right>{$doc->lang["modifier_donnees_tarif_soustraitant"]}</td><td><select name='special-soustraitant-{$_SESSION["session_tfdb"]}-bloc-new'>" . $doc->simple_selecteur("", $r["soustraitant"]) . "</select>&nbsp;" .$doc->input_texte("special-prixhoraire-{$_SESSION["session_tfdb"]}-bloc-new", "", "");
}else{
	echo "\n<tr><td align=right>";
}

echo "&nbsp;{$doc->lang["modifier_donnees_a_facturer"]} :&nbsp;";
if($_POST["print"]) echo "</td><td>";
$factCheck = $afacturer? "checked":"";
$nonFactCheck = $afacturer? "": "checked";
if(!$_POST["print"]) echo "{$doc->lang["general_oui"]}<input type=\"radio\" value=\"1\" $factCheck name=\"afacturer\">{$doc->lang["general_non"]}<input type=\"radio\" value=\"0\" $nonFactCheck name=\"afacturer\">";
elseif ($afacturer) echo $doc->lang["general_oui"].".";
else echo $doc->lang["general_non"].".";
echo "&nbsp;{$doc->lang["modifier_donnees_abandon"]} :&nbsp;";
$factCheck = $abandon? "checked":"";
$nonFactCheck = $abandon? "": "checked";
if(!$_POST["print"]) echo "{$doc->lang["general_oui"]}<input type=\"radio\" value=\"1\" $factCheck name=\"abandon\">{$doc->lang["general_non"]}<input type=\"radio\" value=\"0\" $nonFactCheck name=\"abandon\">";
elseif ($abandon) echo $doc->lang["general_oui"].".";
else echo $doc->lang["general_non"].".";
echo "</td></tr>";
echo "<tr><td align=right>{$doc->lang["modifier_donnees_matiere"]} : </td><td>";
$select=explode("\n", "{$_SESSION["optionGen"]["matiere_type"]}");
echo $doc->input_liste("matiere", $doc->simple_selecteur($select, ",@@$matiere", 2, False, True), ".&nbsp;");
echo "{$doc->lang["modifier_donnees_VAT"]} :&nbsp;";
if(!$_POST["print"]) echo "<input $newStyle type=text size=10 name=tvadossier value=\"$tvadossier\">";
else echo "$tvadossier";

if($noarchive == "") $onchange="onchange=\"idnoarchive.value='$pr_dispo';idnoarchive.style.color='ff0000'\"";

echo "</td></tr>
<tr><td align=right>{$doc->lang["modifier_donnees_date_ouverture"]} :</td><td>";
echo $doc->split_date($dateouverture, "", "date_jour_ouverture", "date_mois_ouverture", "date_annee_ouverture");
echo "&nbsp;{$doc->lang["modifier_donnees_date_archivage"]} :&nbsp;";
echo $doc->split_date($datearchivage, "", "date_jour_archive", "date_mois_archive", "date_annee_archive");

echo "&nbsp;{$doc->lang["modifier_donnees_no_archive"]} :&nbsp;";
if(!$_POST["print"]) echo "<input type=text size=4 id=idnoarchive name=noarchive value=\"$noarchive\">";
else echo "$noarchive";
if($noarchive == "" AND !$_POST["print"]) echo "<font class=\"attention\">{$doc->lang["modifier_donnees_prochain"]} = $pr_dispo</font>";

//echo "&nbsp;{$doc->lang["modifier_donnees_dormant"]} :&nbsp;";
//$dormCheck = $dormant? "checked":"";
//$nonDormCheck = $dormant? "": "checked";
//if(!$_POST["print"]) echo "{$doc->lang["general_oui"]}<input type=\"radio\" value=\"1\" $dormCheck name=\"dormant\">{$doc->lang["general_non"]}<input type=\"radio\" value=\"0\" $nonDormCheck name=\"dormant\">";
//elseif ($dormant) echo $doc->lang["general_oui"];
echo "</td></tr>
<tr><td align=right>{$doc->lang["modifier_donnees_remarques"]} :</td><td>";
if(!$_POST["print"]) echo "<textarea cols=70 rows=5 name=remarques>$remarques</textarea>";
else echo nl2br($remarques);
// $doc->tab_affiche($_SESSION["optionGen"]);
echo "</td></tr>";
echo "<tr><td align=right>{$doc->lang["modifier_donnees_origine_mandat"]} :</td><td>";
$select=explode("\n", "{$_SESSION["optionGen"]["origine_mandat"]}");
echo $doc->input_liste("originemandat", $doc->simple_selecteur($select, "$originemandat", 0, False, True), ".&nbsp;");
echo "</td></tr>";

$chemin=$doc->no_accent($chemin);
$test_chemin=is_dir("$chemin") ? TRUE:FALSE;

//Suggestions
$sugDonClient = $doc->no_accent(html_entity_decode($doc->donnee_premier["client"]));
$sugDonPa = $doc->no_accent(html_entity_decode($doc->donnee_premier["pa"]));
$sugInit = ucfirst(substr($sugDonClient, 0, 1));
$suggestion="{$sugInit}SEPARATORTOPREG{$doc->donnee_premier["client"]}";
if($doc->donnee_premier["pa"]) $suggestion .= "SEPARATORTOPREGc. {$doc->donnee_premier["pa"]}";
elseif($naturemandat) $suggestion .= "SEPARATORTOPREG".$doc->no_accent($naturemandat);
else $suggestion .= "SEPARATORTOPREG".$doc->lang["modifier_donnees_divers"];
$suggestion = preg_replace("#'#", " ", $suggestion);
$suggestion = trim($suggestion);
$suggestion = preg_replace("#  #", " ", $suggestion);
if($test_chemin)
{
	$testSousChemin = true;

	foreach($doc->docTypes as $docType)
	{
		$valName = "config_templates_$docType";
		$ssRep = $doc->lang["$valName"];
		$aRep = "$chemin{$_SESSION["slash"]}$ssRep";
		if(!is_dir($aRep)) $testSousChemin = False;
//		else echo "<br>$aRep existe";
	}
}



if(!$chemin)
{
	$suggestion_path = "{$_SESSION["optionGen"]["racine"]}{$_SESSION["slash"]}" . preg_replace("#SEPARATORTOPREG#", $_SESSION["slash"], $suggestion);

}elseif($test_chemin && !$_POST["print"]){
	$doc->lang["modifier_donnees_chemin"] = "<a href=\"file://$chemin\" target=_new>{$doc->lang["modifier_donnees_chemin"]}</a>";
}
if(!$mailbox)
{
	$suggestion_mbx = "{$_SESSION["optionGen"]["racine_mbx"]}.".preg_replace("#SEPARATORTOPREG#",".", preg_replace("#\.#", "_", "$suggestion"));
}
else
{
	if($_SESSION["optionGen"]["racine_mbx"] && $_SESSION["optionGen"]["user_mbx"] && $_SESSION["optionGen"]["pass_mbx"])
	{
		$mailbox = preg_replace("#\.$#", "", $mailbox);
		$parentArray = preg_split("#\.#", $mailbox);
		array_splice($parentArray, -1);
		$parent = implode(".", $parentArray);
		$imap = imap_open ($_SESSION["optionGen"]["racine_mbx"], $_SESSION["optionGen"]["user_mbx"], $_SESSION["optionGen"]["pass_mbx"]);
		$newbox = imap_utf7_encode($mailbox);
		$liste = imap_list($imap, $parent, "*");
		$test_mailbox = ($liste && in_array($newbox, $liste))? True:False;
// 		$test_mailbox = False; #TODO: à des fins de test. 
	}
}
$suivi=$doc->simple_selecteur("", $suivipar);
if(!$_POST["print"]) $suivi="<select name=suivipar>$suivi</select>";
else
{
	$suivi=$suivipar;
	$doc->lang["modifier_donnees_suivi"] .= " :";
}
echo "\n<tr><td align=right>{$doc->lang["modifier_donnees_suivi"]}</td><td>$suivi";
echo "&nbsp;{$doc->lang["data_client_lie"]}&nbsp;:";
if(!$_POST["print"]) echo "<input type=text size=27 name=liea value=\"$liea\">";
else echo "$liea";
echo "</td></tr>";
echo "\n<tr><td align=right>{$doc->lang["modifier_donnees_chemin"]} :</td><td>";
if(!$_POST["print"])
{
	$chemin_ok=rawurlencode($chemin);
	echo "<input type=text size=80 id=\"chemin\" name=\"chemin\" value=\"$chemin\">&nbsp;<img style=\"cursor:pointer\" src=\"images/folder.png\" onclick=\"window.open('navig.php?dir=$chemin_ok','selecteur','width=400,height=400,toolbar=no,directories=no,menubar=no=no,location=no,status=no,scrollbars=yes');\">";
	if(!$chemin) echo " <a class=\"button\" onclick=\"javascript:document.getElementById('chemin').value='$suggestion_path'\">{$doc->lang["modifier_donnees_suggestion"]}</a>";
	 if($tva_deb == "on") $tva_deb = 1;
	echo "</td></tr>";
	if(!$test_chemin && $chemin) echo "<tr><td colspan=2><div class=button id=creechemin onClick=\"sendData('chemin', '$chemin', './random_display.php', 'POST', 'result');document.getElementById('creechemin').innerHTML=''\">{$doc->lang["modifier_donnees_cree_chemin"]}</div><div id=result></div></td>";
	elseif($test_chemin && !$testSousChemin) echo "<tr><td colspan=2><div class=button id=creemail onClick=\"sendData('chemin', '$chemin', './random_display.php', 'POST', 'result');document.getElementById('creemail').innerHTML=''\">{$doc->lang["modifier_donnees_cree_sous_chemin"]}</div><div id=result></div></td>";
}
else
{
	echo "$chemin</td></tr>";
} 
echo "\n<tr><td align=right>{$doc->lang["modifier_donnees_mailbox"]} :</td><td>";
if(!$_POST["print"])
{
	$mailbox_ok=rawurlencode($mailbox);
	echo "<input type=text size=80 id=\"mailbox\" name=\"mailbox\" value=\"$mailbox\">&nbsp;<img style=\"cursor:pointer\" src=\"images/folder.png\" onclick=\"window.open('navig.php?mbx=$mailbox_ok','selecteur','width=400,height=400,toolbar=no,directories=no,menubar=no=no,location=no,status=no,scrollbars=yes');\">";
	if(!$mailbox) echo " <a class=\"button\" onclick=\"javascript:document.getElementById('mailbox').value='$suggestion_mbx'\">{$doc->lang["modifier_donnees_suggestion"]}</a>";
	echo "</td></tr>";
	if(!$test_mailbox && $mailbox) echo "<tr><td colspan=2><div class=button id=cree onClick=\"sendData('mailbox', '$mailbox', './random_display.php', 'POST', 'resultmail');document.getElementById('cree').innerHTML=''\">{$doc->lang["modifier_donnees_cree_mailbox"]}</div><div id=resultmail></div></td>";
}else{
	echo "$mailbox</td></tr>";
} 
echo "\n<tr><td colspan=2>";
if(!$_POST["print"]) echo $doc->button("{$doc->lang["modifier_donnees_modifier_dossier"]}", "{$doc->lang["modifier_donnees_modifier_dossier_accesskey"]}");
echo "</td></tr>
</table></form>";
$bout1=$doc->form("modifier_donnees.php", "{$doc->lang["modifier_donnees_imprimer_dossier"]}", "{$doc->lang["modifier_donnees_imprimer_dossier_accesskey"]}", "", "", "print", "on", "nodossier", "{$_POST["nodossier"]}");;
$bout2=$doc->form("operations.php", "{$doc->lang["modifier_donnees_retour"]}", "{$doc->lang["modifier_donnees_retour_accesskey"]}", "", "", "nodossier", "{$_POST["nodossier"]}");
if(!$_POST["print"]) echo "<table><tr><td>$bout1</td><td>$bout2</td></tr></table>";

//pour revenir au dossier "normal" si on est en version imprimable
if($_POST["print"]) echo $doc->form("modifier_donnees", "", "", "", "<td>retour", "nodossier", $_POST["nodossier"]);

echo "\n<script language=javascript>var actpersonne;actpersonne=document.getElementById('popup_client1')</script>";

//On crée un formulaire pour recharger la page
echo $doc->self_reload();

$doc->close();
?>
