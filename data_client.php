<?php

//Définition des variables à utiliser
//tarifs sous-traitants
$prixsoustraitants = array();
$q = "select soustraitant, prixhoraire from {$_SESSION["session_tfdb"]} where nodossier like '{$_POST["nodossier"]}'";
$e = mysqli_query($doc->mysqli, $q) or die ("<br>'$q'</br>".mysqli_error($doc->mysqli));
while($r = mysqli_fetch_array($e)) $prixsoustraitants["{$r["soustraitant"]}"] = $r["prixhoraire"];

//prix théorique
$requete_calcul_theorique="select COUNT(*) as stop, SUM(time_to_sec(tempsop)) as 'totalseconde' from {$_SESSION["session_opdb"]} where (nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limite $recherche_limite $sous_traitant_limite)";
$requete_calcul_theorique="select COUNT(*) as stop, time_format(sec_to_time(SUM(time_to_sec(o.tempsop))), \"%k:%i\") as 'totaltemps', SUM(time_to_sec(o.tempsop)) as 'totalseconde', sum(if(o.forfait = '0.00', if(t.prixhoraire IS NULL, a.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600)) as totalprix, afacturer from {$_SESSION["session_opdb"]} o LEFT OUTER JOIN {$_SESSION["session_tfdb"]} t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant LEFT OUTER JOIN {$_SESSION["session_avdb"]} a on o.nodossier = a.nodossier where (o.nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limite $recherche_limite $sous_traitant_limite)";
// echo "$requete_calcul_theorique";
$calcul_theorique=mysqli_query($doc->mysqli, $requete_calcul_theorique);
while($r=mysqli_fetch_array($calcul_theorique)) foreach($r as $a => $b) $$a = $b;
if($_POST["secteur"]=="operations" || $anchor=="afficher_operations") $stop_mysql=$stop;

//rentrées effectives
$requete_calcul_recette="select COUNT(*) as stop, (SUM(encaissement) + SUM(depens) - SUM(avfrais)) as total_resultat, SUM(encaissement) as 'total_recette', SUM(provision) as 'total_provision', SUM(avfrais) as 'total_avance', SUM(transit) as 'total_transit', SUM(depens) as 'total_depens', SUM(demande) as total_demande from {$_SESSION["session_opdb"]} o where (nodossier like '{$_POST["nodossier"]}' and dateac <> 0 $ac_date_limite $recherche_limite)";
//echo "<br>$requete_calcul_recette";
$calcul_recette=mysqli_query($doc->mysqli, $requete_calcul_recette);
while($r=mysqli_fetch_array($calcul_recette)) foreach($r as $a => $b) $$a = $b;
if($_POST["secteur"]=="encaissements") $stop_mysql=$stop;

//Données du dossier
$requeteData = "select nodossier, tvadossier, prixhoraire, liea, suivipar, dormant, limitehono, alertehono, naturemandat, noarchive, chemin";
$compreqData = "";
$array_num=array("a", "b", "c", "d", "e");
foreach($array_num as $num => $let)
{
	$suf = (! $num)? "": "$num";
	foreach(array("a" => "client", "p" => "pa", "t" => "aut", "q" => "contact", "c" => "ca", "j" => "pj") as $init => $nom)
	{
		$requeteData .= ", {$init}$let.nom as nom{$nom}$let, {$init}$let.prenom as prenom{$nom}$let, {$init}$let.mail as mail{$nom}$let";
		if($nom == "aut") $requeteData .= ", noautref{$suf}, noautaj{$suf}";
		if($nom == "client") $nom = "adresse";
		$compreqData .= " LEFT OUTER JOIN adresses {$init}$let on {$_SESSION["session_avdb"]}.no{$nom}$suf = {$init}$let.id";
	}
}
$requeteData .= " from {$_SESSION["session_avdb"]}";
$requeteData .= $compreqData;
$requeteData .= " where nodossier like '{$_POST["nodossier"]}'";
// echo "<br>'$requeteData'";
$e = mysqli_query($doc->mysqli, $requeteData);
while($r = mysqli_fetch_array($e))
{
	foreach($r as $a => $b)
	{
		$$a = $b;
		$afclient = "";
		$afpa = "";
		$afaut = "";
		$afca = "";
		foreach($array_num as $num => $let)
		{
			$suf = (! $num)? "": "$num";
			foreach(array("client", "pa", "aut") as $pers)
			{
				$rPers = ($pers == "client") ? "adresse": $pers;
				$varaff = "af$pers";
				$varpersn = "nom{$pers}{$let}";
				$varpersp = "prenom{$pers}{$let}";
				$varpersm = "mail{$pers}{$let}";
				if($pers == "aut" && ($$varpersn || $$varpersp)) $$varaff .= "<tr><td>";
				if ($$varpersp) $$varaff .= $doc->smart_html($$varpersp);
				if ($$varpersn && $$varpersp) $$varaff .= " ";
				if ($$varpersn) $$varaff .= "<b>".$doc->smart_html($$varpersn)."</b>";
				if ($$varpersn || $$varpersp)
				{
// 					$doc->tab_affiche($doc->usableTemplates);
					$$varaff .= "&nbsp;";
					if(is_array($doc->usableTemplates)) foreach($doc->usableTemplates as $file => $personnes) #TODO
					{
// 						$doc->tab_affiche($personnes);
						$imName = "b_doc";
						if($personnes["type"] == "facture") $imName = "facture";
						if(!$doc->pdaSet && in_array($pers, $doc->usableTemplates[$file]["personne"])) $$varaff .= "<a href='facture.php?fichier=$file&nodossier={$_POST["nodossier"]}&type={$personnes["type"]}&dest=$rPers{$suf}'><img src='images/$imName.png' onmouseover=\"show('$file')\" onmouseout=\"hide()\"></a>";
					}
					if ($$varpersm)
					{
						$mail = $$varpersm;
						$$varaff .= "<a href='mailto:$mail?subject=Votreaffaire".rawurlencode("& autres")."&body=toto".rawurlencode(" & tata")."'><img src='images/b_mail.png'></a>";
					}
					if($pers == "pa" || $pers == "client")
					{
						$lt = ($pers == "pa") ? "ca":"pj";
						$arrlt = ($pers == "pa") ? array("ca"):array("contact", "pj");
						foreach($arrlt as $lt)
						{
							$conseil = "";
							$nca = "nom{$lt}{$let}";
							$pca = "prenom{$lt}{$let}";
							if ($$pca) $conseil .= $$pca." ";
							if ($$nca) $conseil .= "<b>" . $$nca . "</b>";
							if($conseil) $$varaff .= "<br>&nbsp;->&nbsp;$conseil&nbsp;";
							if($conseil && is_array($doc->usableTemplates)) foreach($doc->usableTemplates as $file => $personnes) #TODO
							{
		// 						$doc->tab_affiche($personnes);
								$imName = "b_doc";
								if($personnes["type"] == "facture") $imName = "facture";
								if(in_array($pers, $doc->usableTemplates[$file]["personne"])) $$varaff .= "<a href='facture.php?fichier=$file&nodossier={$_POST["nodossier"]}&type={$personnes["type"]}&dest=$lt{$suf}'><img src='images/$imName.png' onmouseover=\"show('$file')\" onmouseout=\"hide()\"></a>";
							}
							$varpersmbis = "mail{$lt}{$let}";
							if ($$varpersmbis)
							{
								$mail = $$varpersmbis;
								$$varaff .= "<a href='mailto:$mail?subject=Votreaffaire".rawurlencode("& autres")."&body=toto".rawurlencode(" & tata")."'><img src='images/b_mail.png'></a>";
							}
						}
					}
					if($pers == "aut")
					{
						$ref = "noautref{$suf}";
						$aj  = "noautaj{$suf}";
						$$varaff .= "</td><td>" . $$ref . "</td><td>" . $$aj . "</td></tr>";
					}
					else $$varaff .= "<br>\n";
				}
			}
		}
	}
	$prixseconde = $prixhoraire/3600;
}
// die($afaut);


$fname=preg_replace("# #", "", $doc->lang["entete_modifier"]);
$onclick=($_POST["secteur"]=="operations" OR $_POST["secteur"]=="encaissements")? "onClick='document.location=\"./modifier_donnees.php?nodossier={$_POST["nodossier"]}\"' style='cursor:pointer'":NULL;

//définition de couleur spéciale si on a introduit une limitation des dates
if($op_date_limite OR $recherche_limite OR $sous_traitant_limite) $attention="class=not_valid";
$oldAttention = $attention;

if(isset($noentete) && $noentete === True)
{
	echo "<table border=4 width=95% align=center>\n<tr><td><b>{$doc->lang["afficher_operations_entete_simple"]}. {$doc->lang["afficher_operations_client"]} : $prenomclienta $nomclienta. {$doc->lang["afficher_operations_dossier"]} : $naturemandat</b>";
	if($op_date_limite) echo"<br><span class=attention>$plage_limite</span>";
	echo "</td></tr></table>
	";
}
else
{
	$colspanPda = $doc->pdaSet ? "colspan=2":"";
	//affichage des données du client dans un tableau
	echo "\n<table border=\"4\" width=\"100%\" align=\"center\">
	<tr>";
	if($doc->pdaSet) $colHeaders = "<th $colspanPda>{$doc->lang["data_client_details"]}:</th><th $colspanPda>{$doc->lang["data_client_resultat"]}:";
	else $colHeaders = "<th><a href='file://$chemin'><img src='images/folder.png'></a>&nbsp;{$doc->lang["data_client_clients"]}</th><th>{$doc->lang["data_client_pas"]}</th><th>{$doc->lang["data_client_details"]}:</th><th>{$doc->lang["data_client_resultat"]}:";
	echo "$colHeaders";
	if($op_date_limite OR $recherche_limite OR $sous_traitant_limite) echo"<br><span class=attention>$plage_limite</span>";
	echo "</th></tr>
	<tr>";

	//Colonne 1: affichage des cinq clients potentiels// rien pour le PDA
	if (! $doc->pdaSet) echo "<td $onnoclick>$afclient</td>";

	//Colonne 2: affichage des cinq pa potentielles // rien pour le PDA
	if (! $doc->pdaSet) echo "<td $onnoclick>$afpa</td>";

	//Colonne 3: affichage des détails du mandat, ou toutes les données pour le PDA

	//Liste des tarifs horaires spéciaux
	$prixhoraire_affiche = "$prixhoraire {$_SESSION["optionGen"]["currency"]}";
	foreach($prixsoustraitants as $soustraitant => $prixhoraire) $prixhoraire_affiche .= "<br><span style='font-style:italic;font-size:smaller'>$soustraitant: $prixhoraire{$_SESSION["optionGen"]["currency"]}</span>";

	if($noarchive) $archive = "<p class=attention>{$doc->lang["modifier_donnees_no_archive"]}: $noarchive</p>";
	else
	{
		$actionBouclage = "onclick='changeState($nodossier, \"dormant\", $dormant)'";
		if($dormant == 4) $img = 'aarchiver';
		else $img = 'pasarchive';
		$infobulle = $doc->infobulle($doc->lang["modifier_donnees_etat_{$img}"]);
		$archive = "<p $actionBouclage $infobulle>{$doc->lang["modifier_donnees_no_archive"]}: <img src='images/$img.png'></p>";

	}
	$afpa = $afpa ? "<br>c. $afpa":"";
	$supPda = $doc->pdaSet ? "$afclient{$afpa}<br>":"";
	echo "<td $colspanPda><table><tr><td $onclick>$supPda<b>{$doc->lang["data_client_no"]} :</b>&nbsp;$nodossier
	<br><b>{$doc->lang["data_client_lie"]} :&nbsp;</b>$liea<br>
	<br><b>{$doc->lang["data_client_nature"]} :</b>
	<br>$naturemandat<br>
	<br><b>{$doc->lang["data_client_prix"]} :</b>
	$prixhoraire_affiche + $tvadossier&nbsp;%<br><b>{$doc->lang["modifier_donnees_limitehono"]} :</b>
	<span $depasseHono>$limitehono</span> {$_SESSION["optionGen"]["currency"]}
	<br><b>{$doc->lang["modifier_donnees_alertehono"]} :</b>
	<span $atteintHono>$alertehono</span> {$_SESSION["optionGen"]["currency"]}</td></tr>
	<tr><td>$archive</td></tr>
	<tr><td><b>{$doc->lang["modifier_donnees_suivi"]} :</b>
	$suivipar</td></table></td>";

	//Colonne 4: résultats


	//affichage des honoraires théoriques et calcul du total théorique
	$affichageHonoraires = "<td $colspanPda>\n<table border=0 style=nowrap width=100%><tr><td $attention>{$doc->lang["data_client_theorique"]} :</td><td width=100 align=right $attention>";
	// 	$totalseconde=$row["totalsec"];

	$gain=round($totalseconde*$prixseconde*20)/20;
	$gain=round($totalprix/3600*20)/20;
	$gainactuel=number_format($gain, 2, '.', '\'');
	$tva=round($gain*$tvadossier*0.2)/20;
	if(($limitehono > 0) && (($gain + $tva) > $limitehono))
	{
		$attention = "class=attention";
		$depasseHono = "class=attention";
	}
	elseif(($alertehono > 0) &&(($gain + $tva) > $alertehono))
	{
		$attention = "class=alerte";
		$atteintHono = "class=alerte";
	}
	$affichageHonoraires .= $gainactuel.
	"</td></tr><tr><td $attention>{$doc->lang["data_client_tva"]} $tvadossier %</td><td align=right $attention>". 
	number_format($tva, 2, '.', '\'').
	"</td></tr><tr><td $attention><b>{$doc->lang["data_client_total"]}&nbsp;:</b></td><td align=right $attention><b>".
	number_format(($tva+$gain), 2, '.', '\'').
	"</b></td></tr>";
	$attention = $oldAttention;
	$alertehono = number_format($alertehono, 2, ".", "'");
	$limitehono = number_format($limitehono, 2, ".", "'");
	$aPayer = $tva+$gain;

	//affichage des honoraires réalisés

	echo $affichageHonoraires;

	// affichage des rentrées effectives
	if($total_demande < 0)
	{
		$data_client_solde = $data_client_solde_retour;
		$solde_att=$attention;
	}
	if($_SESSION["optionGen"]["tva_deb"])
	{
		$tva_deb=round($total_avance * $tvadossier * 0.2)/20;
		$aPayer += $tva_deb;
		$totalDebours = $total_avance + $tva_deb;
		$affDebours = number_format($totalDebours, 2, '.', '\'');
	}
// 	echo "<tr><td colspan=2><hr></td></tr>";
	echo "<tr><td colspan=2><hr></td></tr>";
	echo "<tr><td $attention>{$doc->lang["data_client_debours"]}&nbsp;:  </td><td align=right $attention>", number_format($total_avance, 2, '.', '\''), "</td></tr>";
	if($_SESSION["optionGen"]["tva_deb"])
	{
		echo "<tr><td $attention>{$doc->lang["data_client_tva_deb"]}&nbsp;: </td><td align=right $attention>", number_format($tva_deb, 2, '.', '\''), "</td></tr>";
	}
	$aPayer += $total_avance;
	$affAPayer = number_format($aPayer, 2, '.', '\'');
	if($_SESSION["optionGen"]["tva_deb"])
	{
		echo "<tr><td><b>{$doc->lang["data_client_total_debours"]}&nbsp;:</b></td><td align=right><b>$affDebours</b></td></tr>";
		echo "<tr><td colspan=2><hr></td></tr>";
	}
	echo "<tr><td><b>{$doc->lang["data_client_a_payer"]}&nbsp;:</b></td><td align=right><b>$affAPayer</b></td></tr>";
	echo "<tr><td colspan=2><hr></td></tr>";
	echo "<tr><td $attention>./.&nbsp;{$doc->lang["data_client_rentrees"]}&nbsp;: </td><td width=100 align=right $attention>", number_format($total_recette, 2, '.', '\''),"</td></tr>";
	echo "<tr><td $attention>./.&nbsp;{$doc->lang["operations_depens"]}&nbsp;: </td><td width=100 align=right $attention>", number_format($total_depens, 2, '.', '\''),"</td></tr>";
	$img = $afacturer ? "nonabandon":"facture";
	$infobulle = $doc->infobulle($doc->lang["modifier_donnees_etat_{$img}"]);
	$iconAFacturer = $doc->pdaSet ? "":"<td onClick='changeState($nodossier, \"afacturer\", $afacturer)'><img $infobulle src='images/$img.png'></td>";
	echo "<tr><td $attention $solde_att><b>{$doc->lang["data_client_solde"]}&nbsp;: </b></td><td align=right $attention><b>", number_format(($gain + $tva + $tva_deb - $total_resultat), 2, '.', '\''), "</b></td>$iconAFacturer</tr>";
	echo "<tr><td $attention>{$doc->lang["data_client_attente"]}&nbsp;: </td><td align=right $attention>", number_format($total_demande, 2, '.', '\''), "</td></tr>";
	if($total_transit!=0)
	{
		echo "<tr><td class=attention>{$doc->lang["data_client_solde_transit"]}&nbsp;: </td><td align=right class=attention>", number_format($total_transit, 2, '.', '\''), "</td></tr>";
	}
	if($total_provision!=0)
	{
		echo "<tr><td class=attentionok>{$doc->lang["data_client_solde_provision"]}&nbsp;: </td><td align=right class=attentionok>", number_format($total_provision, 2, '.', '\''), "</td></tr>";
	}
	echo "\n</table></td></tr>";
	
	//Ligne de séparation
	echo "\n<tr><th colspan=4>{$doc->lang["data_client_autorites"]}</th></tr>";
	
	
	//affichage des autorités
	echo "\n<tr><td colspan=4>";
	echo $doc->table_open("width=100%");
	echo "\n<tr><td>{$doc->lang["modifier_donnees_aut"]}</td><td>{$doc->lang["modifier_donnees_no_tribunal"]}</td><td>{$doc->lang["modifier_donnees_no_aj"]}</td></tr>";
// 	die("\n\n\n'''$afaut'''");
	echo "$afaut";
	echo $doc->table_close();
	echo "</td></tr>";


	//fermeture du tableau des données du client
	echo "\n</table>";
}
?>
