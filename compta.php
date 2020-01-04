<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;

$doc->title();
$doc->body(2);
$doc->entete();





//affichage des en-têtes du tableau
echo "<br>";
$colspan=6;
if($_POST["print"]=="on") $colspan-=2;
if($_POST["ts"]=="on") $colspan+=2;

 //affichage des lignes pour les autres pages du dossier (pour operations et encaissements



//début des opérations individuelles

if(!$doc->testval("ecrire") && $doc->testval("lire")) $_POST["print"]=true;
echo "<table width=\"95%\" align=\"center\" border=\"0\">";
//en-têtes du tableau
$colspan=$colspan+2;

$thsup=($_POST["ts"] == "on") ? "<th>$data_client_prix</th><th>$afficher_operations_soit</th>" : "";
echo "<tr><th colspan=$colspan>&nbsp;</th></tr>";
echo "<tr><th align=\"left\">Numero</th><th>Date</th><th>Libelle</th><th>Montant</th><th>Compte 1</th>$thsup<th>Compte 2</th>";
if($_POST["print"]!="on") echo "<th colspan=2>$operations_actions</th>";
echo "</tr>";

//Affichage des opérations, ligne par ligne
if($_POST["print"]!="on") $limite_nombre="limit $debut_mysql,{$_SESSION["nb_affiche"]}";
else $limite_nombre="";
$i=0;
$query_op="select * from operations order by date";
$resultat_op=mysqli_query($doc->mysqli, "$query_op");
if($_POST["print"]!="on") echo "<form method=\"post\" name=\"maj\" action=\"maj_op.php\" $infobulle>";

//création d'un identifiant unique par ligne
$identifiant=0;
while($row=mysqli_fetch_array($resultat_op)){
	//foreach($row as $rownom => $rowval) $row["$rownom"] = smart_html("$rowval");
	$identifiant++;
	$texte_select="onChange='selectBox(\"$identifiant\", \"norequete-multireq-{$row["idop"]}\")'";
	$infobulle=$doc->qui_fait_quoi("{$row["np"]}", "{$row["nple"]}", "{$row["mp"]}", "{$row["mple"]}", "$date_format");
	if($row["facturesanstemps"]!="0.00") $attention="class=attention_bg";
	else $attention="";
	echo "\n<tr $infobulle $attention id=\"$identifiant\">";
	if(($_POST["secteur"]=="journal" || $_POST["secteur"]=="tva") AND $_POST["print"]!="on") echo "<td onClick='changeDossier(\"{$row["nodossier"]}\", \"encaissements\")' style=\"cursor:pointer\">{$row["nodossier"]}</td>";
	elseif($_POST["secteur"]=="journal_op") echo "<td onClick='changeDossier(\"{$row["nodossier"]}\", \"operations\")' style=\"cursor:pointer\">{$row["nodossier"]}</td>";
	else echo "<td>{$row["nodossier"]}</td>";
	if($_POST["print"]!="on")
	{
/*		echo "<input type=\"hidden\" name=\"np-multireq-{$row["idop"]}\" value=\"{$row["np"]}\">";
		echo "<input type=\"hidden\" name=\"nple-multireq-{$row["idop"]}\" value=\"{$row["nple"]}\">";*/
		echo "<input type=\"hidden\" name=\"nodossier-multireq-{$row["idop"]}\" value=\"{$row["nodossier"]}\">";
		echo "<input type=\"hidden\" name=\"retour\" value=\"operations\">";
		//echo "<input type=\"hidden\" name=\"action\" value=\"update\">";
		echo "<input type=\"hidden\" name=\"debut\" value=\"{$_POST["debut"]}\">";
		echo "<input type=\"hidden\" name=\"idop-multireq-{$row["idop"]}\" value=\"{$row["idop"]}\">";
		echo "<input type=\"hidden\" name=\"secteur\" value=\"{$_POST["secteur"]}\">";
		echo "<td align=center>";
		echo "<input type=text size=2 onfocus=select() $texte_select name=\"date_jour-multireq-{$row["idop"]}\" value=\"{$row["date_jour"]}\">";
		echo "<input type=text size=2 onfocus=select() $texte_select name=\"date_mois-multireq-{$row["idop"]}\" value=\"{$row["date_mois"]}\">";
		echo "<input type=text size=4 onfocus=select() $texte_select name=\"date_annee-multireq-{$row["idop"]}\" value=\"{$row["date_annee"]}\">";
		echo "</td>";
	}else {
		echo "<td align=center>{$row["date_jour"]}.{$row["date_mois"]}.{$row["date_annee"]}</td>";
	}
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva") //cette colonne est avant la liste dans encaissements
	{
		if($_POST["print"]=="on") echo "<td>{$row["ac"]}</td>";
		else echo "<td><input type=text size=20 onfocus=select() $texte_select name=\"ac-multireq-{$row["idop"]}\" value=\"", $row["ac"],"\"></td>";
	}
	echo "<td align=\"center\">";
	if($_POST["print"]!="on")
	{
		echo "<select $texte_select name=$optype-multireq-{$row["idop"]}>";
		if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva") $select=explode("\n", "{$_SESSION["optionGen"]["ac_type"]}");
		else $select=explode("\n", "{$_SESSION["optionGen"]["op_type"]}");
		$test=0;
		foreach($select as $option)
		{
			$selected="";
			if(trim($row["$optype"])==trim($option))
			{
				$selected="selected";
				$test=1;
			}
			echo "<option value=\"", trim($option), "\" $selected>",trim($option);
		}
		if($test==0) echo "<option value=\"", trim($row["$optype"]), "\" selected>", trim($row["$optype"]);
		echo "</select>";
	}
	else echo $row["$optype"];
	echo "</td>";
	
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva")
	{
		if($row["encaissement"]==0) $row["encaissement"]="";
		if($row["avfrais"]==0) $row["avfrais"]="";
		if($row["demande"]==0) $row["demande"]="";
		if($row["transit"]==0) $row["transit"]="";
		if($row["facturesanstemps"]==0) $row["facturesanstemps"]="";
		if($_POST["print"]=="on")
		{
			echo "<td>{$row["encaissement"]}&nbsp;</td>";
			echo "<td>{$row["avfrais"]}&nbsp;</td>";
			echo "<td>{$row["demande"]}&nbsp;</td>";
			echo "<td>{$row["transit"]}&nbsp;</td>";
			echo "<td>{$row["facturesanstemps"]}&nbsp;</td>";
		}else{
			echo "<td><input type=text size=8 onfocus=select() $texte_select name=\"encaissement-multireq-{$row["idop"]}\" value=\"{$row["encaissement"]}\"></td>";
			echo "<td><input type=text size=8 onfocus=select() $texte_select name=\"avfrais-multireq-{$row["idop"]}\" value=\"{$row["avfrais"]}\"></td>";
			echo "<td><input type=text size=8 onfocus=select() $texte_select name=\"demande-multireq-{$row["idop"]}\" value=\"{$row["demande"]}\"></td>";
			echo "<td><input type=text size=8 onfocus=select() $texte_select name=\"transit-multireq-{$row["idop"]}\" value=\"{$row["transit"]}\"></td>";
			echo "<td><input type=text size=8 onfocus=select() $texte_select name=\"facturesanstemps-multireq-{$row["idop"]}\" value=\"{$row["facturesanstemps"]}\"></td>";
		}
	}else{
		if($_POST["print"]=="on")
		{
		echo "<td align=center>{$row["opavec"]}&nbsp;</td>";
		echo "<td align=center>{$row["temps_heure"]}:{$row["temps_minute"]}&nbsp;</td>";
		}else{
			echo "<td align=center><input type=text size=20 onfocus=select() $texte_select name=\"opavec-multireq-{$row["idop"]}\" value=\"", $row["opavec"], "\"></td>";
			echo "<td align=center><input type=text size=2 onfocus=select() $texte_select name=\"temps_heure-multireq-{$row["idop"]}\" value=\"{$row["temps_heure"]}\">:<input type=text size=2 $texte_select name=\"temps_minute-multireq-{$row["idop"]}\" value=\"{$row["temps_minute"]}\"></td>";
		}
		if($_POST["secteur"]=="operations" || $_POST["secteur"]=="journal_op")
		{
			if($_POST["ts"])
			{
				$prixhoraire=$row["prixhoraire"];
				$prixseconde=$prixhoraire / 3600;
				$totalseconde = $row["totalsec"];
				$gain=round($totalseconde*$prixseconde*20)/20;
				$gainactuel=number_format($gain, 2, '.', '\'');
				echo "<td align=\"center\">$prixhoraire</td><td align=\"right\">$gainactuel</td>";
			}
			if(!$_POST["print"])
			{
				echo "<td><select $texte_select name=soustraitant-multireq-{$row["idop"]}>";
				if($_SESSION["optionGen"]["soustraitants"]) $select=explode("\n", $_SESSION["optionGen"]["soustraitants"]);
				else $select=array();
				
				$select[]=","; //permet d'afficher une ligne blanche
				$test=0;
				foreach($select as $line)
				{
				list($option) = preg_split("#,#", $line);
				$selected="";
				if(trim($row["soustraitant"])==trim($option))
				{
					$selected="selected";
					$test=1;
				}
					echo "<option value=\"", trim($option), "\" $selected>", $doc->smart_html($option);
				}
				if($test==0) echo "<option value=\"", trim($row["soustraitant"]), "\" selected>", trim($row["soustraitant"]);
				echo "</select></td>";
			}
			else echo "<td align=center>{$row["soustraitant"]}</td>";
			if($_POST["secteur"] == "journal_op")
			{
				
			}
	
		}
	}
	if($_POST["print"]!="on")
	{
		echo "<td align=right>";
		echo "$operations_selectionner <input type=\"checkbox\" id=norequete-multireq-{$row["idop"]} name=norequete-multireq-{$row["idop"]} onClick='select_color(\"$identifiant\", \"norequete-multireq-{$row["idop"]}\")'>";
	//	echo "<button type=submit>$operations_modifier</button></td></form>";
	//	echo "<td>";
	//	echo $doc->form("./maj_op.php", "$operations_supprimer", "", "attention", "","nodossier", "{$_POST["nodossier"]}", "idop", "{$row["idop"]}","retour", "operations", "debut", "{$_POST["debut"]}", "secteur", "{$_POST["secteur"]}", "action", "delete");
		echo "</td>";
	}
	echo "</tr>";

}


if($_POST["print"]!="on") echo "<tr><td colspan=$colspan>$operations_pour_selection :<input type=radio name=\"action\" value=\"update\" checked> $operations_modifier <input type=radio name=\"action\" value=\"delete\"> $operations_supprimer <input type=radio name=\"action\" value=\"update_all\"> $operations_transfert <input type=\"text\" onClick='document.maj.action[2].checked=true' name=\"new_file\" size=\"4\">", $doc->input_hidden("nodossier", $_POST["nodossier"]), $doc->input_hidden("old_file", "", $_POST["nodossier"]), $doc->button("$operations_valider", ""), "</td></tr></form>";

//formulaire pour changer de dossier (caché)
echo "\n<form action=\"operations.php\" method=\"post\" name=\"changedossier\">";
echo "\n", $doc->input_hidden("nodossier", "", "2");
echo "\n", $doc->input_hidden("secteur", "", "encaissements");
echo "</form>";
//echo $doc->form("operations.php","", "", "", "", "nodossier", "{$row["nodossier"]}", "secteur", "encaissements"), 

if($_POST["print"]!="on")
{
	//Boutons pour rechercher
	
	echo "\n<tr><td colspan=$colspan><hr></td></tr>\n";
	echo "<tr>";
	echo "<form method=\"post\" action=\"./operations.php\">";
	echo "<td>";
	echo $doc->input_hidden("secteur", "1");
	if($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements"){
		echo "<input type=\"hidden\" name=\"nodossier\" value=\"{$_POST["nodossier"]}\">";
		echo "{$_POST["nodossier"]}</td>";
	}
	else echo "<input type=\"text\" size=\"2\" onfocus=select() name=\"nodossier\">";
	echo "<input type=\"hidden\" name=\"secteur\" value=\"{$_POST["secteur"]}\">";
	echo "<input type=\"hidden\" name=\"retour\" value=\"operations\">";
	echo "<input type=\"hidden\" name=\"recherche\" value=\"on\">";
	echo "<input type=\"hidden\" name=\"debut\" value=\"{$_POST["debut"]}\">";
	$date_jour=date(d, time());
	$date_mois=date(m, time());
	$date_annee=date(Y, time());
	
	
	echo "<td align=center>";
	echo "<input type=text size=2 onfocus=select() name=date_jour>";
	echo "<input type=text size=2 onfocus=select() name=date_mois>";
	echo "<input type=text size=4 onfocus=select() name=date_annee>";
	echo "</td>";
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva"){ //cette colonne est avant la liste dans encaissements
		echo "<td><input type=text size=20 onfocus=select() name=ac></td>";
	}
	echo "<td align=center>";
	echo "<select name=$optype>";
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva") $select=explode("\n", "{$_SESSION["optionGen"]["ac_type"]}");
	else $select=explode("\n", "{$_SESSION["optionGen"]["op_type"]}");
	$test=0;
	foreach($select as $option)
	{
		$selected="";
		if(trim($option)=="") $selected="selected";
		echo "<option value=\"", trim($option), "\" $selected>$option";
	}
	echo "</select>";
	echo "</td>";
	
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva"){
		echo "<td><input type=text size=8 onfocus=select() name=encaissement></td>";
		echo "<td><input type=text size=8 onfocus=select() name=avfrais></td>";
		echo "<td><input type=text size=8 onfocus=select() name=demande></td>";
		echo "<td><input type=text size=8 onfocus=select() name=transit></td>";
		echo "<td><input type=text size=8 onfocus=select() name=facturesanstemps></td>";
	} 
	else{
		echo "<td align=center><input type=text size=20 onfocus=select() name=opavec></td>";
		echo "<td align=center><input type=text size=2 onfocus=select() name=temps_heure>:<input type=text size=2 name=temps_minute></td>";
		echo "<td><select name=soustraitant>";
		if($_SESSION["optionGen"]["soustraitants"]) $select=explode("\n", $_SESSION["optionGen"]["soustraitants"]);
		else $select=array();
		
		$select[]=","; //permet d'afficher une ligne blanche
		$test=0;
		foreach($select as $line){
		list($option) = preg_split("#,#", $line);
		$selected="";
		if(trim($row["soustraitant"])==trim($option))
		{
			$selected="selected";
			$test=1;
		}
			echo "<option value=\"", trim($option), "\" $selected>$option";
		}
		if($test==0) echo "<option value=\"", trim($row["soustraitant"]), "\" selected>", trim($row["soustraitant"]);
		echo "</select></td>";

	}
	echo "<td align=center colspan=\"2\">";
	echo "<button type=submit>$operations_recherche</button></td></form></tr>";
	
	//Boutons pour ajouter, dernière ligne de chaque page
	
	echo "\n<tr><td colspan=$colspan><hr></td></tr>\n";
	echo "<tr>";
	echo "<form method=\"post\" action=\"./maj_op.php\">";
	echo "<td>";
	if($_POST["secteur"]=="operations" || $_POST["secteur"]=="encaissements")
	{
		echo "<input type=\"hidden\" name=\"nodossier\" value=\"{$_POST["nodossier"]}\">";
		echo "{$_POST["nodossier"]}</td>";
	}
	else echo "<input type=\"text\" size=\"2\" onfocus=select() name=\"nodossier\" value=\"{$_POST["nodossier"]}\">";
	echo "<input type=\"hidden\" name=\"secteur\" value=\"{$_POST["secteur"]}\">";
	echo "<input type=\"hidden\" name=\"retour\" value=\"operations\">";
	echo "<input type=\"hidden\" name=\"action\" value=\"insert\">";
	echo "<input type=\"hidden\" name=\"debut\" value=\"{$_POST["debut"]}\">";
	$date_jour=date(d, time());
	$date_mois=date(m, time());
	$date_annee=date(Y, time());
	$nple="$date_annee-$date_mois-$date_jour";
	$np=$_SESSION["session_utilisateur"];
		
	echo "<td align=center>";
	echo "<input type=text size=2 onfocus=select() name=date_jour value=\"$date_jour\">";
	echo "<input type=text size=2 onfocus=select() name=date_mois value=\"$date_mois\">";
	echo "<input type=text size=4 onfocus=select() name=date_annee value=\"$date_annee\">";
	echo "</td>";
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva"){ //cette colonne est avant la liste dans encaissements
		echo "<td><input type=text size=20 onfocus=select() name=ac></td>";
	}
	echo "<td align=center>";
	echo "<select name=$optype>";
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva") $select=explode("\n", "{$_SESSION["optionGen"]["ac_type"]}");
	else $select=explode("\n", "{$_SESSION["optionGen"]["op_type"]}");
	$test=0;
	foreach($select as $option){
		$selected="";
		if(trim($option)=="") $selected="selected";
		echo "<option value=\"", trim($option), "\" $selected>$option";
	}
	echo "</select>";
	echo "</td>";
	
	if($_POST["secteur"]=="encaissements" || $_POST["secteur"]=="journal" || $_POST["secteur"]=="tva"){
		echo "<td><input type=text size=8 onfocus=select() name=encaissement></td>";
		echo "<td><input type=text size=8 onfocus=select() name=avfrais></td>";
		echo "<td><input type=text size=8 onfocus=select() name=demande></td>";
		echo "<td><input type=text size=8 onfocus=select() name=transit></td>";
		echo "<td><input type=text size=8 onfocus=select() name=facturesanstemps></td>";
	} 
	else{
		echo "<td align=center><input type=text size=20 onfocus=select() name=opavec></td>";
		echo "<td align=center><input type=text size=2 onfocus=select() name=temps_heure>:<input type=text size=2 name=temps_minute></td>";
	echo "<td><select name=soustraitant>";
	if($_SESSION["optionGen"]["soustraitants"]) $select=explode("\n", $_SESSION["optionGen"]["soustraitants"]);
	else $select=array();
	
	$select[]=","; //permet d'afficher une ligne blanche
	$test=0;
	foreach($select as $line){
	list($option) = preg_split("#,#", $line);
	$selected="";
	if(trim($row["soustraitant"])==trim($option))
	{
		$selected="selected";
		$test=1;
	}
		echo "<option value=\"", trim($option), "\" $selected>$option";
	}
	if($test==0) echo "<option value=\"", trim($row["soustraitant"]), "\" selected>", trim($row["soustraitant"]);
	echo "</select></td>";


	}
	echo "<td align=center colspan=\"2\">";
	echo "<button type=submit>$operations_nouveau</button></td></form></tr>";
	
	//Affichage des résultats du dossier (calcul des honoraires théoriques)
	if($_POST["secteur"]=="operations"){
		$colspan=$colspan-2;
		echo "<tr><td colspan=$colspan>$operations_total :</td><td colspan=2><b>";
		$resultat_total_op_requete="select time_format(sec_to_time(SUM(time_to_sec(tempsop))), \"%k:%i\") as 'total', SUM(time_to_sec(tempsop)) as 'totalsec' from {$_SESSION["session_opdb"]} where (nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limit $recherche_limite $sous_traitant_limite)";
		$resultat_total_op=mysqli_query($doc->mysqli, "$resultat_total_op_requete");
		//echo $resultat_total_op_requete;
		while($row=mysqli_fetch_array($resultat_total_op)){
			echo $row["total"],
			" $general_soit ";
			$totalseconde=$row["totalsec"];
			$gainactuel=round($totalseconde*$prixseconde*20)/20;
			echo $gainactuel,
			" {$_SESSION["optionGen"]["currency"]}</b></td></tr></table>";
		}
	}
	$doc->footer();
	
	if($_POST["secteur"]=="tva"){
	echo "<p align=center>";
	echo $doc->form("operations.php", "$operations_tva_pf", "", "", "", "timestamp_debut", "{$_POST["timestamp_debut"]}", "timestamp_fin", "{$_POST["timestamp_fin"]}", "secteur", "{$_POST["secteur"]}", "print", "on");
	echo "</p>";
	}
}
elseif($_POST["secteur"]=="operations"){
		$colspan=$colspan-2;
		echo "<tr><td colspan=$colspan>$operations_total :</td><td colspan=2 align=right><b>";
		$resultat_total_op_requete="select time_format(sec_to_time(SUM(time_to_sec(tempsop))), \"%k:%i\") as 'total', SUM(time_to_sec(tempsop)) as 'totalsec' from {$_SESSION["session_opdb"]} where (nodossier like '{$_POST["nodossier"]}' and dateop <> 0 $op_date_limit $recherche_limite $sous_traitant_limite)";
		$resultat_total_op=mysqli_query($doc->mysqli, "$resultat_total_op_requete");
		//echo $resultat_total_op_requete;
		while($row=mysqli_fetch_array($resultat_total_op)){
			echo $row["total"],
			" $general_soit ";
			$totalseconde=$row["totalsec"];
			$gainactuel=round($totalseconde*$prixseconde*20)/20;
			echo $gainactuel,
			" {$_SESSION["optionGen"]["currency"]}</b></td></tr></table>";
		}
	}

?>

      </body>
   </html>
