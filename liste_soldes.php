<?php
require_once("./inc/autoload.php");
session_start();
$doc=new prolawyer;
$doc->connection();
$doc->title("<script type=\"text/javascript\" src=\"./externe/XHRConnection.js\"></script>");
$doc->body(2);
$doc->entete();

if(!isset($_POST["comptes"])) $_POST["comptes"] = "min_solde";
$comptes = array("min_solde" => $doc->lang["liste_soldes_deficit"], "min_non_facture" => $doc->lang["liste_soldes_slt_nonfacture"], "transit" => $doc->lang["liste_soldes_transit"]);
$aComptes = "<select name=comptes>";
$aComptes .= $doc->simple_selecteur($comptes, $_POST["comptes"], 2);
$aComptes .= "</select>";

if(!isset($_POST["criteres"])) $_POST["criteres"] = ">";
$crit = array(">" => $doc->lang["general_superieur"], "<" => $doc->lang["general_inferieur"], "=" => $doc->lang["general_egal"], "!=" => $doc->lang["general_different"], "ALL" => $doc->lang["general_indifferent"]);
$aCrit = "<select name=criteres>";
$aCrit .= $doc->simple_selecteur($crit, $_POST["criteres"], 2);
$acrit .= "</select>";

echo $doc->self_reload();
$soldeAFacturer = 0;
//$doc->tab_affiche();
// $crit = array(">" => $doc->lang["general_superieur"], "<" => $doc->lang["general_inferieur"], "=" => $doc->lang["general_egal"], "!=" => $doc->lang["general_different"]);
// $aCrit = "<select name=condition>";
// foreach($crit as $k => $c) $aCrit .= "<option value='$k'>$c";
// $acrit .= "</select>";
$etat = array("actif", "dormant", "attente_paiement", "attente_archivage", "a_boucler", "archive");
$etat = array(array("ffffff", "actif"), array("4040ff", "dormant"), array("ffff00", "attente_paiement"), array("ff8000", "attente_archivage"), array("808080", "a_boucler"), array("ff0000", "archive"));
$dm = "<select size=6 multiple name='dormant[]'>";
$dormant = ($_POST["dormant"]) ? $_POST["dormant"]:array(0);
$affiche_dormant = "";
foreach($etat as $n => $st)
{
	$noun = $st[1];
	$colo = $st[0];
	$sleepColors[] = $colo;
	$nom_dormant = $doc->lang["modifier_donnees_$noun"];
	if (in_array($n, $dormant)) 
	{
		if($affiche_dormant) $affiche_dormant .= ", ";
		$affiche_dormant .= "<span style='background-color:#$colo'>{$doc->lang["modifier_donnees_$noun"]}</span>";
	}
	$selected = (in_array($n, $dormant)) ? "selected":"";
	$dm .=  "<option style='select:background-color:#ffffff' value='$n' $selected>$nom_dormant</option>";
	/*if (in_array($n, $dormant)) 
	{
		if($affiche_dormant) $affiche_dormant .= ", ";
		$affiche_dormant .= $doc->lang["modifier_donnees_$st"];
	}
	$selected = (in_array($n, $dormant)) ? "selected":"";
	$vst = $doc->lang["modifier_donnees_{$st}"];
	$dm .=  "<option value='$n' $selected>$vst</option>";*/
}
$dm .= "</select>";


$title = $doc->lang["liste_soldes_h1"];
if($dormant)
{
	$c = "";
	$t = 0;
	foreach($dormant as $dormeur)
	{
		if($t) $c .= " or ";
		$t ++;
		if($dormeur == -1)
		{
			$c .= "(noarchive = 0 AND dormant = 0)";
		}
		elseif($dormeur < 5)
		{
			$c .= "(noarchive = 0 AND dormant = $dormeur)";
		}
		else
		{
			$c .= "noarchive > 0";

		}
	//	if($t) $c .= " or ";
	//	$t ++;
	//	$c .= "dormant = $dormeur";
	}
	$restDormant = ($c)? "AND ($c)":"";
}
if($_REQUEST["clientReq"])
{
	$q = "select noadresse, nom, prenom from {$_SESSION["db"]}clients LEFT OUTER JOIN adresses on noadresse = id where nodossier like '{$_REQUEST["clientReq"]}'";
	$e = mysqli_query($doc->mysqli, $q);
	while ($r = mysqli_fetch_array($e))
	{
		$title = "\n<h2>$title {$doc->lang["apropos_pour"]} {$r["prenom"]} <b>{$r["nom"]}</b></h2>";
		$id = $r["noadresse"];
	}
}
else
{
	$title = "\n<h1>$title</h1>";

	$searchType = "<tr><td colspan=8>{$doc->lang["liste_soldes_rechercher"]} :</td></tr>
	<tr>";
	
	//module pour rechercher les types de dossiers ATTENTION: LE TYPE DE DOSSIER N'A PAS DE SENS SI L'ON RECHERCHE POUR UN CLIENT DETERMINE
	$restrict="typedossier like 'rien'";
	$select=preg_split("/\n/", $_SESSION["optionGen"]["dossiers_type"]);
	foreach($select as $option)
	{
		list($abrev, $nom)=preg_split("/,/", $option);
		$test="";
		$checked="";
		$test=$_POST["$abrev"];
		if($test=="on")
		{
			$checked="checked";
			$restrict=$restrict." or typedossier like '$abrev'";
		}
		if(trim($option)!="") $searchType .= "<td>$nom :</td><td width=40><input type=checkbox $checked name=$abrev></td>";
	}
	$checked="";
	if($_POST["others"]=="on")
	{
		$checked="checked";
		$restrict=$restrict." or typedossier like ''";
	}
	$searchType .= "<td>{$doc->lang["liste_soldes_autres"]}</td><td><input type=checkbox $checked name=others></td></tr>";
	if($restrict) $restrict = "and ($restrict)";
}
	$checked="";
	if($_POST["afacturer"]=="on")
	{
		$checked="checked";
		$restrict = " and afacturer = 1"; //cela remplace la condition prévue
	}
	$searchType .= "<tr><td colspan = 5>{$doc->lang["liste_soldes_slt_afacturer"]}&nbsp;<input type=checkbox $checked name=afacturer></td></tr>";
	$checked="checked";
	if(!isset($_POST["abandon"]) or ! $_POST["abandon"])
	{
		$checked="";
		$restrict .= " and abandon = 0"; //cela s'ajoute à la condition prévue et ne la remplace pas
	}
	$searchType .= "\n<tr><td colspan = 5>{$doc->lang["liste_soldes_abandonnes"]}&nbsp;<input type=checkbox $checked name=abandon></td></tr>";


//Formulaire de recherche
echo "$title
<br>
<form method=post action=\"./liste_soldes.php\">
<table><tr><td colspan=8>{$doc->lang["liste_soldes_dossiers"]} $aComptes $aCrit&nbsp;
<input size=4 name=min_solde value={$_POST["min_solde"]}> {$_SESSION["optionGen"]["currency"]} </td></tr>";

$checked="";
if($_POST["nonfacture"]=="on")
{
        $checked="checked";
}
// echo "<tr><td colspan = 5>{$doc->lang["liste_soldes_slt_nonfacture"]}&nbsp;<input type=checkbox $checked name=nonfacture></td></tr>";
echo $searchType;
//$check = ($_POST["archives"]) ? "checked":"";
//echo "<tr><td colspan=8>{$doc->lang["liste_soldes_y_compris"]}&nbsp;<input type=checkbox name=archives $check></td></tr>";
echo "<tr><td colspan=8>{$doc->lang["liste_soldes_date"]}&nbsp;".$doc->split_date("POST", "ouverture") ."</td></tr>";
$check = ($_POST["dormants"]) ? "checked":"";
	//<td>{$doc->lang["modifier_donnees_typedossier"]}&nbsp;:</td><td>$dm</td>
//echo "<tr><td colspan=8>{$doc->lang["liste_soldes_dormants"]} :&nbsp;<input type=checkbox name=dormants $check></td></tr>
echo "<tr><td colspan=8>{$doc->lang["modifier_donnees_typedossier"]} :&nbsp;$dm</td></tr>
</table>
<br>
$affiche_dormant
<br><button type=submit>{$doc->lang["liste_soldes_soumettre"]}</button>";
echo $doc->input_hidden("clientReq", "", $_REQUEST["clientReq"]);
echo "</form>
<br><br>";







//Liste des dossiers
if(! $_REQUEST["clientReq"]) $supReq = "<th>{$doc->lang["liste_soldes_nom"]}</th><th>{$doc->lang["liste_soldes_nompa"]}</th>";
echo "<table width=100% align=center>";
$ligneTitre = "<tr><th></th><th></th><th>{$doc->lang["modifier_donnees_date_ouverture"]}</th><th width=30>{$doc->lang["liste_soldes_numero"]}</th>$supReq<th>{$doc->lang["liste_soldes_nature"]}</th><th align=right>{$doc->lang["operations_transit"]}</th><th align=right>{$doc->lang["liste_soldes_manque"]}</th><th align=right>{$doc->lang["liste_soldes_attente"]}</th><th>{$doc->lang["data_client_honoraires"]}</th><th>&nbsp</th></tr>";

//soldes de départ
$total_manque=0;
$total_demande=0;
$total_transit=0;

//affichage de chaque ligne de dossier.

$clientReq = ($_REQUEST["clientReq"]) ? "and noadresse like '$id'":"";

if($doc->autoDates["ouverture"]["m"])
{
	$ouv = $doc->autoDates["ouverture"]["m"];
	$ouvCond = $doc->mtf_date($ouv);
}
else $ouvCond = False;

$requete_complete="select {$_SESSION["session_opdb"]}.nodossier as nodossier, dormant, afacturer, sum(encaissement) as 's_encaissement', sum(avfrais) as 's_avfrais', sum(demande) as 's_demande', sum(transit) as 's_transit', sum(time_to_sec(tempsop)) as 's_temps_consacre', dateouverture, typedossier, tvadossier, adresses.titre, adresses.nom, adresses.prenom, adresses.fonction, adresses.adresse, adresses.zip, adresses.ville, noarchive, naturemandat, {$_SESSION["session_avdb"]}.prixhoraire, abandon, apa.nom as nompa, apa.prenom as prenompa, sum(if({$_SESSION["session_opdb"]}.forfait = '0.00', if(t.prixhoraire IS NULL, {$_SESSION["session_avdb"]}.prixhoraire, t.prixhoraire) * time_to_sec({$_SESSION["session_opdb"]}.tempsop), {$_SESSION["session_opdb"]}.forfait * 3600)) as prixtotalop from {$_SESSION["session_opdb"]} LEFT OUTER JOIN {$_SESSION["session_avdb"]} on {$_SESSION["session_avdb"]}.nodossier = {$_SESSION["session_opdb"]}.nodossier LEFT OUTER JOIN adresses on adresses.id={$_SESSION["session_avdb"]}.noadresse LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on {$_SESSION["session_opdb"]}.nodossier = t.nodossier and {$_SESSION["session_opdb"]}.soustraitant = t.soustraitant LEFT OUTER JOIN adresses apa on apa.id={$_SESSION["session_avdb"]}.nopa where 1 $restrict $restDormant $clientReq group by nodossier order by nodossier";
		//$requete_2="select  from adresses, {$_SESSION["session_avdb"]} where (nodossier like '$dossier_en_cours' AND  $restrict)";
//echo "<br>'$requete_complete'";
$calcul_recette=mysqli_query($doc->mysqli, "$requete_complete");
if(mysqli_error($doc->mysqli)) echo "\n<br>'$requete_complete': ".mysqli_error($doc->mysqli);
$dossier_en_cours=-1;
$temps_dossier=0;
$ligneplan=0;
$noligne=0;
$dossierTraite = 0;
while($ligne=mysqli_fetch_array($calcul_recette, MYSQLI_ASSOC))
{
		foreach($ligne as $nom_col =>$val_col)
		{
			if($nom_col == "zip" AND $val_col == "0") $val_col = "";
			$$nom_col=$val_col;
		}
		$dossier_en_cours=$ligne["nodossier"];
		$prixseconde=$prixhoraire/3600;
		//le gain est TVA comprise; attention aux taux de TVA spécifiques par dossier
		$tva=($tvadossier != "") ? $tvadossier : $_SESSION["optionGen"]["tx_tva"];
		$gain=round($s_temps_consacre*$prixseconde*20*(100 + $tva)/100)/20;
		$gain=round($prixtotalop/3600*20*(100 + $tva)/100)/20;
		$num_a_encaisser=$gain - $s_encaissement + 20*($s_avfrais*(100 + $tva)/100)/20;
		$gainactuel=number_format($gain, 2, '.', '\'');
		$a_encaisser=number_format(($num_a_encaisser), 2, '.', '\'');
		$attente=number_format($s_demande, 2, '.', '\'');
		if($ouvCond && $ouvCond > $doc->mtf_date($dateouverture)) continue;
		switch($_POST["comptes"])
		{
			case "min_solde":
				$comp = $num_a_encaisser;
				break;
			case "min_non_facture":
				$comp = $num_a_encaisser - $s_demande;
				break;
			case "transit":
				$comp = $s_transit;
				break;
		}
		$comp = (float)$comp;
		$memb = (float)$_POST["min_solde"];
		switch($_POST["criteres"])
		{
			case ">":
				$verif = ($comp > $memb);
				break;
			case "<":
				$verif = ($comp < $memb);
				break;
			case "=":
				$verif = ($comp == $memb);
				break;
			case "!=":
				$verif = ($comp != $memb);
				break;
			case "ALL":
				$verif = True;
				break;
		}
// 		if((($num_a_encaisser>$_POST["min_solde"]) || (!isset($_POST["min_solde"]) || $_POST["min_solde"] === "")) and (!$_POST["nonfacture"]||($num_a_encaisser - $s_demande)>$_POST["min_solde"]))
		if($verif)
		{
			$noligne ++;
			if($dossierTraite % 10 == 0) echo $ligneTitre;
			$dossierTraite ++;
			$ligneplan = $ligneplan ? 0:1;
			if($afacturer) $totalAFacturer += $num_a_encaisser;
                        $total_gain = $total_gain + $gain;
			$total_manque=$total_manque + $num_a_encaisser;
			$total_transit=$total_transit + $s_transit;
			$total_demande=$total_demande + $s_demande;
                        $normBg = $ligneplan ? "ligneplan0":"ligneplan1";
			$specBg = "class=$normBg";
                        if ($afacturer) $specBg = "class=inserted_bg";
			$dorm=$ligne["dormant"];
			$color=$etat[$dorm][0];
			$aboucler = $dormant == 4 ? "checked":"";
			$style = $noarchive?"":"style='background-color:#$color'";
			$actionBouclage = "onclick='changeState($dossier_en_cours, \"dormant\", $dormant)'";
			$caseArchive = $noarchive ? "<td class='attention_bg'>$noarchive</td>":$dorm?"<td $actionBouclage><img src='images/aarchiver.png'></td>": "<td $actionBouclage><img src='images/true.png'></td>";
			echo "\n\t<tr $specBg><td>$noligne</td>$caseArchive<td>".$doc->mysql_to_print($dateouverture)."</td><td><span $style id='champ$dossier_en_cours'>";
			if($_REQUEST["clientReq"]) echo "<a href=# onclick=\"javascript:window.opener.location='{$doc->settings["root"]}operations.php?secteur=encaissements&nodossier=$nodossier&get=true'\">$nodossier</a>";
			else echo "<a href='{$doc->settings["root"]}operations.php?secteur=encaissements&nodossier=$nodossier&get=true'>$nodossier</a>";
			//else echo $doc->form("operations.php", "$nodossier", "", "", "", "nodossier", "$nodossier", "secteur", "encaissements");
			echo "</td>";
			if(!$_REQUEST["clientReq"])
			{
				echo "<td width=400>", $titre;
				if($titre)echo " ";
				echo "$prenom <b>$nom</b> $fonction $adresse $zip $ville";
                                echo "<br><a href=# onclick=\"window.open('{$doc->settings["root"]}liste_soldes.php?clientReq={$nodossier}','modifier','scrollbars=yes,width=600,height=600,toolbar=no,directories=no,menubar=no,location=no,status=no')\">{$doc->lang["multi_clients_title"]}</a>";
				echo "</td><td width=200>";
				$pa = "";
				if($prenompa) $pa = "$prenompa ";
				if($nompa) $pa .= "<b>$nompa</b>";
				if($pa) $pa = "c. $pa";
				echo "$pa</td>";
			}
			echo "<td>",
			$ligne["naturemandat"],
			"&nbsp;</td><td>";
			if($s_transit) echo $s_transit;
			echo "</td><td align=right onClick='changeState($dossier_en_cours, \"afacturer\", $afacturer)'>";
			if($num_a_encaisser)
			{
				echo "<font color=ff4040";
				//echo "onClick='facturation($dossier_en_cours, \"afacturer\", $afacturer)'";
				echo ">$a_encaisser</font>";
			}
			echo "&nbsp;</td><td align=right>";
			if($s_demande) echo "<font color=ffa0a0>$attente";
			echo "&nbsp;</td>";
			echo "&nbsp;</td><td align=right>";
			if($gain) echo "<font color=ffa0a0>$gainactuel";
			echo "&nbsp;</td>";
			echo $doc->form("facture.php<td class='$normBg'>", "<img src='{$doc->settings["root"]}images/facture.png'>", "", "$normBg", "", "nodossier", $dossier_en_cours, "session_utilisateur", "{$_SESSION["session_utilisateur"]}", "db", "{$_SESSION["db"]}", "fichier", $_SESSION["facture"]);
			$aabandonner = $abandon ? "checked":"";
			$imageabandon = $abandon ? "abandon":"nonabandon";
			$stateabandon = $abandon ? $doc->infobulle($doc->lang["liste_soldes_abandon_oui"], 'left'):$doc->infobulle($doc->lang["liste_soldes_abandon_non"], 'left');
			//echo "<br>'$stateabandon'";
			//echo "<td><input type='checkbox' $aabandonner onclick='changeState($dossier_en_cours, \"abandon\", $abandon)'></td>";
			echo "<td><img src='images/$imageabandon.png' onclick='changeState($dossier_en_cours, \"abandon\", $abandon)' $stateabandon></td>";
			//echo "<td><input type='checkbox' $aboucler onclick='changeState($dossier_en_cours, \"dormant\", $dormant)'></td>";
			echo "</tr>";
		}
	}
$affiche_total_transit=number_format($total_transit, 2, '.', '\'');
$affiche_total_manque=number_format($total_manque, 2, '.', '\'');
$affiche_total_demande=number_format($total_demande, 2, '.', '\'');
$affiche_total_gain=number_format($total_gain, 2, '.', '\'');
$affiche_totalAFacturer=number_format($totalAFacturer, 2, '.', '\'');
$colspan = 6;
if($_REQUEST["clientReq"]) $colspan -= 2;
$colspanAFacturer = $colspan +2;
if($dossierTraite)
{
	//echo $ligneTitre;
	echo "<tr class='ligneplan0'><td colspan=$colspan><b>{$doc->lang["afficher_operations_total"]}</b></td><td>$affiche_total_transit&nbsp;</td><td align=right><b>$affiche_total_manque&nbsp;</b></td><td align=right><b>$affiche_total_demande&nbsp;</b></td><td align=right><b>$affiche_total_gain&nbsp;</b></td></tr>";
	if($totalAFacturer) echo "<tr><td colspan=$colspan><span class='inserted_bg'><b>{$doc->lang["liste_soldes_dont_afacturer"]}&nbsp;</b></span></td><td align=right><span class='inserted_bg'><b>$affiche_totalAFacturer&nbsp;</b></span></td><td>&nbsp;</td></tr>";
}
echo "</table>";
$doc->body();
?>
