<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new calendar;
$doc->initZefix();

//initialisation
// echo "init";
$doc->affiche_personne(False, $_POST["nodossier"]);
// 	$doc->tab_affiche($doc->donneesDuDossier);
// $doc->tab_affiche($doc->donnee_premier);

//Ne jamais afficher une page de dossier si le numéro de dossier n'est pas fourni.
if(!isset($_REQUEST["nodossier"]) && $_POST["new"]!= "on")
{
	header("Location:./resultat_recherche.php");
	die();
}

$doc->titleAddons[] = $doc->incSource("{$doc->settings["root"]}js/calendar.js");
$doc->title("<script type=\"text/javascript\" src=\"./externe/XHRConnection.js\"></script>");

//Quelle fiche afficher ?
$onload = "";
if(isset ($_POST["subretour"]) && $_POST["subretour"])
{
		$sub = $_POST["subretour"];
		$ong = substr($sub, 0, -1);
		if($ong == "contact" || $ong == "pj") $ong = "client";
		elseif($ong == "ca") $ong = "pa";
		$onload = "activate('popup_$sub', 'onglet_$ong', 'onglet2_$ong', 'bold_$sub')";
}
else
$onload = "activate('otherdatas', 'otheronglet', 'otheronglet2', 'otheronglet2')";

// $doc->tab_affiche();

if($_POST["print"]) $doc->body(2, "", "document.getElementById('retour').submit()");
else $doc->body(0, $onload);
$doc->entete();
if(!$doc->testval("ecrire")) $_POST["print"]=true;
// echo "<script language=JavaScript>
// var loading='$imap_courriel_loading';
// </script>\n";
// $date_jour=date("d", time());
// $date_mois=date("m", time());
// $date_annee=date("Y", time());
// $today="$date_annee-$date_mois-$date_jour";
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

$autresDonnees = "\n<div class='popupguyshow' id='otherdatas'>";
//Autres données


$newStyle = ($_POST["new"] == "on") ? "class='attention_bg'" : "";
$autreClass = $_POST["print"] ? "":"class=autresdonnees";

$pt = ($_POST["print"]) ?  "." : "";
$autresDonnees .= "<form action=\"./maj_op.php\" method=\"post\" name=\"modifOptions\">";
$autresDonnees .= "<input type=\"hidden\" name=\"nodossier\" value=\"{$_POST["nodossier"]}\">";
$autresDonnees .= "<input type=\"hidden\" name=\"retour\" value=\"modifier_donnees\">";
$autresDonnees .= "<input type=\"hidden\" name=\"action\" value=\"modifier_dossier\">";
$autresDonnees .= "<table $autreClass>";
$autresDonnees .= "\n<tr><td>&nbsp;</td>";
$autresDonnees .= "</tr>";
$autresDonnees .= "\n<tr><td>{$doc->lang["modifier_donnees_nature_mandat"]} :</td><td>";
$autresDonnees .= $doc->input_texte("naturemandat", "", $naturemandat, 17);
if($_POST["print"]) $autresDonnees .=  ".";
$etat = array("actif", "dormant", "attente_paiement", "attente_archivage", "a_boucler");
$autresDonnees .=  "&nbsp;{$doc->lang["modifier_donnees_typedossier"]}:&nbsp;";
$dm = "<select name='dormant'>";
foreach($etat as $n => $st)
{
	$selected = ($dormant == $n) ? "selected":"";
	if($dormant == $n) $dvalue = $st;
	$vst = $doc->lang["modifier_donnees_{$st}"];
	$dm .=  "<option value='$n' $selected>$vst</option>";
}
$dm .= "</select>";
if(!$_POST["print"]) $autresDonnees .= $dm;
else $autresDonnees .= $dvalue;
$autresDonnees .= "</td></tr>
<tr><td>{$doc->lang["modifier_donnees_type"]} : </td><td>";
if(!$_POST["print"]) $autresDonnees .= "<select name=typedossier>";

$select=explode("\n", "{$_SESSION["optionGen"]["dossiers_type"]}");
$testval=0;
foreach($select as $option)
{
	list($abrev, $nom)=preg_split("#,#", $option);
	if($typedossier==$abrev)
	{
		$selected=" selected";
		if($_POST["print"]) $autresDonnees .= trim($nom).".&nbsp;&nbsp;";
		$testval=1;
	}
	if(!$_POST["print"]) $autresDonnees .= "<option value=\"$abrev\"$selected>$nom
	";
	$selected="";
}
if($testval==0) if(!$_POST["print"]) $autresDonnees .= "<option value=\"$type_dossier\" selected>$type_dossier";
if(!$_POST["print"]) $autresDonnees .= "</select>";

$autresDonnees .= "&nbsp;{$doc->lang["modifier_donnees_matiere"]}&nbsp;:&nbsp;";

$select=explode("\n", "{$_SESSION["optionGen"]["matiere_type"]}");
$autresDonnees .= $doc->input_liste("matiere", $doc->simple_selecteur($select, ",@@$matiere", 2, False, True), ".&nbsp;");



$autresDonnees .= "</td></tr><tr><td>";
$autresDonnees .= "{$doc->lang["modifier_donnees_prix"]} :</td><td>";
if(!$_POST["print"]) $autresDonnees .= "<input $newStyle type=text size=6 name=prixhoraire value=\"$prixhoraire\">";
else $autresDonnees .= "$prixhoraire";
$autresDonnees .= "&nbsp;{$_SESSION["optionGen"]["currency"]}. {$doc->lang["modifier_donnees_VAT"]}&nbsp;: ";
if(!$_POST["print"]) $autresDonnees .= "<input $newStyle type=text size=10 name=tvadossier value=\"$tvadossier\">";
else $autresDonnees .= "$tvadossier";

$autresDonnees .= "</td></tr>";

//tarifs des sous-traitants
$q = "select soustraitant, prixhoraire, id from {$_SESSION["session_tfdb"]} where nodossier like '{$_POST["nodossier"]}' order by prixhoraire DESC";
$e = mysqli_query($doc->mysqli, $q);
while($r = mysqli_fetch_array($e))
{
	if(!$_POST["print"]) $autresDonnees .= "\n<tr><td>{$doc->lang["modifier_donnees_tarif_soustraitant"]}</td><td><select name='special-soustraitant-{$_SESSION["session_tfdb"]}-bloc-{$r["id"]}'><option value=''></option>" . $doc->simple_selecteur("", $r["soustraitant"]) . "</select>&nbsp;" .$doc->input_texte("special-prixhoraire-{$_SESSION["session_tfdb"]}-bloc-{$r["id"]}", "", $r["prixhoraire"]) . "</td></tr>";
	else $autresDonnees .= "\n<tr><td>{$doc->lang["modifier_donnees_tarif_soustraitant"]}</td><td>{$r["soustraitant"]}&nbsp;:&nbsp;{$r["prixhoraire"]}</td></tr>";
}
if(!$_POST["print"])
{
	$autresDonnees .= $doc->input_hidden("special-nodossier-{$_SESSION["session_tfdb"]}-bloc-control@soustraitant", "", $_POST["nodossier"]);
	$autresDonnees .= "\n<tr><td>{$doc->lang["modifier_donnees_tarif_soustraitant"]}</td><td><select name='special-soustraitant-{$_SESSION["session_tfdb"]}-bloc-new'>" . $doc->simple_selecteur("", $r["soustraitant"]) . "</select>&nbsp;" .$doc->input_texte("special-prixhoraire-{$_SESSION["session_tfdb"]}-bloc-new", "", "");
}else{
	$autresDonnees .= "\n<tr><td>";
}

$autresDonnees .= "\n<tr><td></td><td>";
$autresDonnees .= "{$doc->lang["modifier_donnees_limitehono"]} :&nbsp;";
if(!$_POST["print"]) $autresDonnees .= "<input type=text size=6 name=limitehono value=\"$limitehono\">";
else $autresDonnees .= "$limitehono";
$autresDonnees .= "&nbsp;{$_SESSION["optionGen"]["currency"]}. ";
$autresDonnees .= "{$doc->lang["modifier_donnees_alertehono"]} :&nbsp;";
if(!$_POST["print"]) $autresDonnees .= "<input type=text size=6 name=alertehono value=\"$alertehono\">";
else $autresDonnees .= "$alertehono";
$autresDonnees .= "&nbsp;{$_SESSION["optionGen"]["currency"]}";

if($noarchive == "")
{
	$infobulle = $doc->infobulle("cliquez");
	$onchange="document.getElementById(\"idnoarchive\").value=\"$pr_dispo\";idnoarchive.style.color=\"#ff0000\"";
	list($jT, $mT, $aT) = preg_split("#:#", $doc->univ_strftime("%d:%m:%Y"));
	$arClick = "document.getElementById(\"date_jour_archive\").value=\"$jT\";document.getElementById(\"date_mois_archive\").value=\"$mT\";document.getElementById(\"date_annee_archive\").value=\"$aT\";$onchange";
	$arImg = "<img src='./images/aarchiver.png' onclick='$arClick' $infobulle>";
}

$autresDonnees .= "</td></tr>";
$autresDonnees .= "\n<tr><td></td><td>";
$autresDonnees .= "&nbsp;{$doc->lang["modifier_donnees_a_facturer"]} :&nbsp;";
// if($_POST["print"]) $autresDonnees .= "</td><td>";
$factCheck = $afacturer? "checked":"";
$nonFactCheck = $afacturer? "": "checked";
if(!$_POST["print"]) $autresDonnees .= "{$doc->lang["general_oui"]}<input type=\"radio\" value=\"1\" $factCheck name=\"afacturer\">{$doc->lang["general_non"]}<input type=\"radio\" value=\"0\" $nonFactCheck name=\"afacturer\">";
elseif ($afacturer) $autresDonnees .= $doc->lang["general_oui"].".";
else $autresDonnees .= $doc->lang["general_non"].".";
$autresDonnees .= "&nbsp;{$doc->lang["modifier_donnees_abandon"]} :&nbsp;";
$factCheck = $abandon? "checked":"";
$nonFactCheck = $abandon? "": "checked";
if(!$_POST["print"]) $autresDonnees .= "{$doc->lang["general_oui"]}<input type=\"radio\" value=\"1\" $factCheck name=\"abandon\">{$doc->lang["general_non"]}<input type=\"radio\" value=\"0\" $nonFactCheck name=\"abandon\">";
elseif ($abandon) $autresDonnees .= $doc->lang["general_oui"].".";
else $autresDonnees .= $doc->lang["general_non"].".";
$autresDonnees .= "</td></tr>";
$autresDonnees .= "<tr><td>{$doc->lang["modifier_donnees_date_ouverture"]} :</td><td>";
$autresDonnees .= $doc->split_date($dateouverture, "", "date_jour_ouverture", "date_mois_ouverture", "date_annee_ouverture");
$autresDonnees .= "&nbsp;{$doc->lang["modifier_donnees_date_archivage"]} :&nbsp;";
$autresDonnees .= $doc->split_date($datearchivage, "", "date_jour_archive", "date_mois_archive", "date_annee_archive", $onchange);

if(! $_POST["print"]) $autresDonnees .= $arImg;

$autresDonnees .= "&nbsp;{$doc->lang["modifier_donnees_no_archive"]} :&nbsp;";
if(!$_POST["print"]) $autresDonnees .= "<input type=text size=4 id=idnoarchive name=noarchive placeholder=\"$pr_dispo\" value=\"$noarchive\">";
else $autresDonnees .= "$noarchive";
// if($noarchive == "" AND !$_POST["print"]) $autresDonnees .= "<font class=\"attention\">{$doc->lang["modifier_donnees_prochain"]} = $pr_dispo</font>";

$autresDonnees .= "</td></tr>
<tr><td>{$doc->lang["modifier_donnees_remarques"]} :</td><td>";
if(!$_POST["print"]) $autresDonnees .= "<textarea cols=70 rows=5 name=remarques>$remarques</textarea>";
else $autresDonnees .= nl2br($remarques);
$autresDonnees .= "</td></tr>";
$autresDonnees .= "<tr><td>{$doc->lang["modifier_donnees_origine_mandat"]} :</td><td>";
$select=explode("\n", "{$_SESSION["optionGen"]["origine_mandat"]}");
$autresDonnees .= $doc->input_liste("originemandat", $doc->simple_selecteur($select, "$originemandat", 0, False, True), ".&nbsp;");
$autresDonnees .= "</td></tr>";

$chemin=$doc->no_accent($chemin);
$test_chemin=is_dir("$chemin") ? TRUE:FALSE;

// $doc->tab_affiche($doc->donnee_premier);
//Suggestions
$sugDonClient = $doc->donnee_premier["client"];
$sugDonPa = $doc->donnee_premier["pa"];
$sugInit = ucfirst(substr($sugDonClient, 0, 1));
if($doc->donnee_premier["pa"]) $sugDonPa = "c. {$sugDonPa}";
elseif($naturemandat) $sugDonPa = $naturemandat;
else $sugDonPa = $doc->lang["modifier_donnees_divers"];
$nomDossier = "$sugDonClient ($sugDonPa)";
$suggestion="{$sugInit}SEPARATORTOPREG{$doc->donnee_premier["client"]}SEPARATORTOPREG{$sugDonPa}";
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
//		else $autresDonnees .= "<br>$aRep existe";
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
$autresDonnees .= "\n<tr><td>{$doc->lang["modifier_donnees_suivi"]}</td><td>$suivi";
$autresDonnees .= "&nbsp;{$doc->lang["data_client_lie"]}&nbsp;:";
if(!$_POST["print"]) $autresDonnees .= "<input type=text size=27 name=liea value=\"$liea\">";
else $autresDonnees .= "$liea";
$autresDonnees .= "</td></tr>";
$autresDonnees .= "\n<tr><td>{$doc->lang["modifier_donnees_chemin"]} :</td><td>";
if(!$_POST["print"])
{
	$chemin_ok=rawurlencode($chemin);
	$autresDonnees .= "<input type=text size=80 id=\"chemin\" name=\"chemin\" value=\"$chemin\">";
// 	$autresDonnees .= "&nbsp;<img style=\"cursor:pointer\" src=\"images/folder.png\" onclick=\"window.open('navig.php?dir=$chemin_ok','selecteur','width=400,height=400,toolbar=no,directories=no,menubar=no=no,location=no,status=no,scrollbars=yes');\">";
	if(!$chemin) $autresDonnees .= " <a class=\"button\" tabindex=0 onclick=\"javascript:document.getElementById('chemin').value='$suggestion_path'\" onkeypress=\"javascript:document.getElementById('chemin').value='$suggestion_path'\">{$doc->lang["modifier_donnees_suggestion"]}</a>";
	else $autresDonnees .= "&nbsp;<img style=\"cursor:pointer\" src=\"images/folder.png\" onclick=\"window.open('navig.php?dir=$chemin_ok&mode=32','selecteur','width=400,height=400,toolbar=no,directories=no,menubar=no=no,location=no,status=no,scrollbars=yes');\">";
	 if($tva_deb == "on") $tva_deb = 1;
	$autresDonnees .= "</td></tr>";
	if(!$test_chemin && $chemin) $autresDonnees .= "\n<tr><td colspan=2><div class=button id=creechemin onClick=\"sendData('chemin', '$chemin', './random_display.php', 'POST', 'result');document.getElementById('creechemin').innerHTML=''\">{$doc->lang["modifier_donnees_cree_chemin"]}</div><div id=result></div></td>";
	elseif($test_chemin && !$testSousChemin) $autresDonnees .= "\n<tr><td colspan=2><div class=button id=creemail onClick=\"sendData('chemin', '$chemin', './random_display.php', 'POST', 'result');document.getElementById('creemail').innerHTML=''\">{$doc->lang["modifier_donnees_cree_sous_chemin"]}</div><div id=result></div></td>";
}
else
{
	$autresDonnees .= "$chemin</td></tr>";
} 
$autresDonnees .= "\n<tr><td>{$doc->lang["modifier_donnees_mailbox"]} :</td><td>";
if(!$_POST["print"])
{
	$mailbox_ok=rawurlencode($mailbox);
	$autresDonnees .= "<input type=text size=80 id=\"mailbox\" name=\"mailbox\" value=\"$mailbox\">&nbsp;<img style=\"cursor:pointer\" src=\"images/folder.png\" onclick=\"window.open('navig.php?mbx=$mailbox_ok','selecteur','width=400,height=400,toolbar=no,directories=no,menubar=no=no,location=no,status=no,scrollbars=yes');\">";
	if(!$mailbox) $autresDonnees .= " <a class=\"button\" tabindex=0 onclick=\"javascript:document.getElementById('mailbox').value='$suggestion_mbx'\" onkeypress=\"javascript:document.getElementById('mailbox').value='$suggestion_mbx'\">{$doc->lang["modifier_donnees_suggestion"]}</a>";
	$autresDonnees .= "</td></tr>";
	if(!$test_mailbox && $mailbox) $autresDonnees .= "\n<tr><td colspan=2><div class=button id=cree onClick=\"sendData('mailbox', '$mailbox', './random_display.php', 'POST', 'resultmail');document.getElementById('cree').innerHTML=''\">{$doc->lang["modifier_donnees_cree_mailbox"]}</div><div id=resultmail></div></td>";
}else{
	$autresDonnees .= "$mailbox</td></tr>";
} 
$autresDonnees .= "\n<tr><td colspan=2>";
if(!$_POST["print"]) $autresDonnees .= $doc->button("{$doc->lang["modifier_donnees_modifier_dossier"]}", "{$doc->lang["modifier_donnees_modifier_dossier_accesskey"]}");
$autresDonnees .= "</td></tr>
</table></form>";
$bout1=$doc->form("modifier_donnees.php", "{$doc->lang["modifier_donnees_imprimer_dossier"]}", "{$doc->lang["modifier_donnees_imprimer_dossier_accesskey"]}", "", "", "print", "on", "nodossier", "{$_POST["nodossier"]}");;
$bout2=$doc->form("operations.php", "{$doc->lang["modifier_donnees_retour"]}", "{$doc->lang["modifier_donnees_retour_accesskey"]}", "", "", "nodossier", "{$_POST["nodossier"]}", "secteur", "operations");
$bout3="<form action=\"javascript:newRdv(getDate('dateRdv'), getTime('heureRdv'), '$personne', '', document.getElementById('libelleRdv').value, '&dossier={$_SESSION["db"]}{$_POST["nodossier"]}')\">".$doc->split_date("NOW", "dateRdv").$doc->split_time("8:00", "heureRdv")."&nbsp;<input id=libelleRdv value='{$doc->donnee_premier["client"]}:'>&nbsp;<button class=\"menu\" >{$doc->lang["agenda_nouveau_rdv"]}</button></form>";
$bout4="<form action=\"javascript:newDl(getDate('dateDl'), '$personne', document.getElementById('libelleDl').value, '&dossier={$_SESSION["db"]}{$_POST["nodossier"]}')\">".$doc->split_date("NOW", "dateDl")."&nbsp;<input id=libelleDl value='{$doc->donnee_premier["client"]}:'>&nbsp;<button class=\"menu\" >{$doc->lang["agenda_nouveau_dl"]}</button></form>";

$autresDonnees .= "</div>";


$disp = $_POST["print"]? "":"style=display:none;width:25%";
$otherDatas = "";
$otherDatas .= "<table border=0 width=100%>";
// $otherDatas .= "<td id=colDatas valign=top><table border=0>";
$headers1 = "";
$headers2 = "";
foreach(array("client" => array("contact", "pj"), "pa" => array("ca"), "aut" => array()) as $personne => $array)
{
	$headers2 .= "<td id=onglet2_$personne class=$personne>";
	$langData = "modifier_donnees_$personne";
// 	$headers2 = "";
	$otherDatas .= "\n<tr><td valign=bottom colspan=2><h3>{$doc->lang["$langData"]}</h3></td></tr>";
	$reste = "";
	for($x="";$x<5;$x++)
	{
		$afX = $x ? $x + 1: 1;
		$afY = $doc->donneesDuDossier["$personne{$x}"]["vide"] == True ? "<span style='color:#808080'>($afX)</span>" : $afX;
		$headers2 .= "<span  id=bold_$personne{$afX} class='onglet2' onclick = \"activate('popup_$personne{$afX}', 'onglet_$personne', 'onglet2_$personne', 'bold_$personne{$afX}')\">$afY</span>";
		$echo = "\n<tr><td style=vertical-align:top>$afX.</td><td>";
		$echo .= $doc->affiche_personne("$personne", "{$_POST["nodossier"]}", "$x");
		$echo .= "</td></tr>\n";
// 		if(count($doc->donneesDuDossier["$personne{$x}"]) > 0)
		if($doc->donneesDuDossier["$personne{$x}"]["vide"] == False)
		{
// 			$otherDatas .= "<br>$personne $x vide ? {$doc->donneesDuDossier["$personne{$x}"]["vide"]}";
			foreach($array as $sub)
			{
				$langDataSub = "modifier_donnees_$sub";
				$echoRet = $doc->affiche_personne($sub, "{$_POST["nodossier"]}", "$x");
				if($echoRet != "NODATA") $echo .= "\n<tr><td>&nbsp;</td><td>{$doc->lang["$langDataSub"]}: $echoRet</td></tr>";
// 					$otherDatas .= "\n<tr><td>&nbsp;</td><td>{$doc->lang["data_client_pj"]}: ";
// 					$otherDatas .= $doc->affiche_personne("pj", "{$_POST["nodossier"]}", "$x");
// 					$otherDatas .= "</td></tr>";
			}
			$otherDatas .= $echo;
		}
// 		else $doc->tab_affiche($doc->donneesDuDossier["{client$x}"]);
// /*TODO: modifié ici*/	if(/*! $x ||*/ count($doc->donneesDuDossier["$personne{$x}"]) > 0) $otherDatas .= $echo;
		elseif(!$reste) $reste = $echo;
	}
	$headers1 .= "\n\n\n<td width=25% id=onglet_$personne align=center class=\"$personne onglet\" onclick = \"activate('popup_${personne}1', 'onglet_$personne', 'onglet2_$personne', 'bold_${personne}1')\">{$doc->lang["$langData"]}</td>";
	$headers2 .= "</td>";
	if($_POST["print"])
	{
		if($personne == "client") $otherDatas .= "</table></td><td width=50% valign=top><table border=0>";
		elseif($personne == "pa") $otherDatas .= "</table></td></tr><tr><td colspan=2><table border=0>";
	}
	else $otherDatas .= $reste;
}
$headers1 .= "\n<td id=otheronglet align=center class=\"autresdonnees onglet\">&nbsp;</td>";
$headers2 .= "\n<td id=otheronglet2 class=autresdonnees style=border-bottom:none;cursor:pointer;font-weight:bold align=center onclick = \"activate('otherdatas', 'otheronglet', 'otheronglet2', 'otheronglet2')\">{$doc->lang["modifier_donnees_autre"]}</td>";
// $headers1 .= $doc->table_close();
if(! $_POST["print"])
{
	$otherDatas .= "<tr><td colspan=2 style='cursor:pointer' onclick = \"activate('otherdatas', 'otheronglet', 'otheronglet2', 'otheronglet2')\"><h3>{$doc->lang["modifier_donnees_autre"]}</h3></td></tr>";
}
$otherDatas .= "\n</table>";


if(!$_POST["print"])
{
	echo "<h2 ", $doc->qui_fait_quoi("$np", "$nple", "$mp", "$mple"),">";      
	
	if($_POST["new"]=="on") echo $doc->lang["modifier_donnees_h11"];
	else echo $doc->lang["modifier_donnees_title"];
	echo "</h2>\n<span>$nomDossier</span><br>&nbsp;";
}
else echo "<h1> {$doc->lang["modifier_donnees_title"]}</h1>";
//on insère un grand tableau pour les données du client et de la (des) partie(s) adverse(s).

echo "<table width=100% border=0>";
//affichage "standard" réduit si pas d'affichage pour l'impression
//un premier tableau interne est destiné à l'affichage des clients et pa
if(! $doc->pdaSet)
{
	echo "<tr><td>&nbsp;</td><td align=center><span onmouseover=show('{$doc->accesMoins}') onmouseout=hide() accesskey={$doc->accesMoins} style=cursor:pointer onclick=activate('left')>&lt;-</span>";
// 	for($x=1;$x<6;$x++) echo "<span accesskey=$x onclick=activate('$x')>&nbsp;</span>";
	echo "<span onmouseover=show('{$doc->accesPlus}') onmouseout=hide() accesskey={$doc->accesPlus} style=cursor:pointer onclick=activate('right')>-&gt;</span></div></tr>";
}
echo "\n<tr>";

if(!$_POST["print"])
{
	if(! $doc->pdaSet) echo "<td valign=top style=height:1em;width:1em><span id=arrow onclick=affCol() style=width:1em;text-align:right;float:right;display:inline-block;cursor:pointer;border-width:1px;border-style:solid;padding:0px;margin:0px>&rarr;</span></td>";
	//$cellWidth = true ? "width=75%":"";
	//la deuxième cellule renfermera les données
	echo "<td valign=top rowspan=2>";
	echo $doc->table_open("width=100% style='padding:0px;margin:0px;border-spacing:0px'");
	echo "<tr>$headers1</tr>";
	echo "<tr>$headers2</tr>";
	echo $doc->table_close();
// 	echo $headers1;
// 	echo "<div class=\"popup_static\" id=\"popbox_static\"></div>";
	echo $doc->affichage_total;
	echo $autresDonnees;
	echo "</td>";
}
echo "</tr><tr>";
echo "<td valign=top $disp id=colDatas>";
echo $otherDatas;
echo "</td></tr><tr>";
if(! $doc->pdaSet) echo "<td></td>";
if(! $doc->print) echo "<td>$bout3<br>$bout4<td>";
echo "</tr>";
//on referme la table de ces deux données
if(!$doc->print)
{
	echo "<tr><td>&nbsp;</td><td>";
	$doc->liste_rdv("@{$_SESSION["db"]}{$_POST["nodossier"]}", "%");
	echo "</td></tr>";
}
echo "</table>";
// echo "toto1 $headers1 toto2";
// echo "$bout3<br>$bout4";


//pour revenir au dossier "normal" si on est en version imprimable
if(!$_POST["print"]) echo "<table><tr><td>$bout1</td><td>$bout2</td></tr></table>";
else
{
	echo $autresDonnees; 
	echo $doc->form("modifier_donnees", $doc->lang["modifier_donnees_retour"], "", "specialprint", "retour<td>", "nodossier", $_POST["nodossier"]);
}
echo "\n<script language=javascript>actpersonne=document.getElementById('otherdatas');actonglet=document.getElementById('otheronglet');actonglet2=document.getElementById('otheronglet2');actbold=document.getElementById('otheronglet2')</script>";

//On crée un formulaire pour recharger la page
echo $doc->self_reload();

$doc->close();
?>
