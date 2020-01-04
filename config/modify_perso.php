<?php
require_once("../inc/autoload.php");
session_start();
$doc=new prolawyer;
$doc->connection();
$doc->title();
// $doc->tab_affiche();

if(isset($_POST["restaurer"]))
{
	if(isset($_POST["confirm"]))
	{
		if(is_file($_POST["setcurrent"])) unlink($_POST["setcurrent"]);
		$doc->body(1, "document.forms[0].submit()");
		echo $doc->form("config/modify_perso.php<td>", "OK", "", "", "","setcurrent", "{$_POST["setcurrent"]}");
	}else{
		echo "<h1>{$doc->lang["maj_op_confirm_h1"]}</h1>";
		echo "<table><tr>";
		echo $doc->form("config/modify_perso.php<td>", "{$doc->lang["general_oui"]}", "", "", "", "restaurer", "on", "setcurrent", "{$_POST["setcurrent"]}", "confirm", "on");
		echo $doc->form("config/modify_perso.php<td>", "{$doc->lang["general_non"]}", "", "", "","setcurrent", "{$_POST["setcurrent"]}");
		echo "</tr></table>";
	}
	

}else{
	$doc->body(2);
	$doc->entete();

	if(isset($_POST["setcurrent"])) $optionsListe = $doc->getUtilisateurOptions($_POST["setcurrent"]);
	else $optionsListe = $doc->getUtilisateurOptions("00");
	
	$liste = $doc->liste_des_utilisateurs;

	echo "<form action=\"./modify_perso.php\" method=\"post\" name=formsetcurrent>";
	echo "<h3>{$doc->lang["config_modify_others"]}</h3> ";
	echo "&nbsp;<select name=setcurrent onChange=formsetcurrent.submit()>";
	if($_SESSION["type"]=="admin") echo "<option value=\"00\">{$doc->lang["ra_global"]}";
	foreach($liste as $base_avoc => $ligne)
	{
		$selected="";
		$initavocat=substr($base_avoc, 0, 2);
		
		if($_SESSION["type"]=="admin" || ($doc->testval("admin", $initavocat)))
		{
			if($_POST["setcurrent"] == $initavocat) $selected="selected";
			echo "<option value=\"$initavocat\" $selected>{$ligne["nom"]}";
			$verif["a_un_droit"]="ok";
		}
	}
	echo "</select>";
	if($verif["a_un_droit"]=="ok") echo $doc->button("{$doc->lang["operations_selectionner"]}", "");
	echo "</form>";
	
	if($optionsListe["tva_deb"]) $tva_checked="checked";
	else $tva_unchecked="checked";
	
	if($optionsListe["use_webdav"]) $use_webdav_check="checked";
	else $use_webdav_uncheck="checked";
	list($ouv_heure, $ouv_minute)=preg_split("#:#", $optionsListe["ouverture"]);
	
	if($verif["a_un_droit"]=="ok" AND (isset($_POST["setcurrent"]))) //pas de modification si aucun droit n'existe
	{
// 		$doc->tab_affiche($optionsListe);
		$advTVA = $doc->advice("fo:README_TVA");
		echo "
		<form action=\"./modify_options.php\" method=\"post\">
		<table width=\"100%\" align=\"center\">
		<tr><td>
		{$doc->lang["config_modify_currency"]} : <input name=currency value=\"{$optionsListe["currency"]}\" size=4><br>
		{$doc->lang["config_modify_VAT"]} : <input name=VAT value=\"{$optionsListe["tx_tva"]}\" size=3>%<br>
		{$doc->lang["config_modify_VAT_f"]} : $advTVA<br>"; //<input name=VAT_f value=\"{$optionsListe["tx_f_tva"]}\" size=3>%<br>
		
		#Table séparée pour les taux de TVA
		echo $doc->table_open("");
		echo "\n<tr><th>{$doc->lang["config_modify_perso_rf"]}</th><th>{$doc->lang["config_modify_perso_tx"]}</th></tr>";
		$listes = preg_split("/;/", $optionsListe["tx_var_tva"]);
		$n=1;
		foreach($listes as $liste)
		{
			list($tx, $rf) = preg_split("#=#", $liste);
			echo "\n<tr><td><input name=\"var_vat_tx$n\" value=\"$tx\" size=3>%</td><td><input name=\"var_vat_rf$n\" value=\"$rf\" size=3>%</td></tr>";
			$n++;
		}
		echo "\n<tr><td><input name=\"var_vat_tx0\" value=\"\" size=3>%</td><td><input name=\"var_vat_rf0\" value=\"\" size=3>%</td></th><br>";
		echo $doc->table_close();
		
		try
		{
			$ctx = stream_context_create(array(
			    'http' => array(
			            'timeout' => 1
				            )
			        )
			);
			//die($optionsListe["doodle"]);
			$doodle = @file_get_contents($optionsListe["doodle"], 'r', $ctx);
		}
		catch (Exception $e)
		{
			$doodle = False;
		}
		
		$doodleImage = $doodle ? "../images/true.png": "../images/false.png";
		
		echo "
		{$doc->lang["config_modify_tva_deb"]} : <input type=radio name=tva_deb $tva_checked value=\"1\">{$doc->lang["general_oui"]}&nbsp;&nbsp;<input type=radio name=tva_deb $tva_unchecked value=\"0\">{$doc->lang["general_non"]}</td></tr>
		<tr><td colspan=3>{$doc->lang["config_modify_prix_defaut"]}: <input name=prix_defaut value=\"{$optionsListe["prix_defaut"]}\" size=3>{$optionsListe["currency"]}</td></tr>
		<tr><td colspan=3>{$doc->lang["config_modify_ouverture_defaut"]}: <input name=ouv_heure value=\"$ouv_heure\" size=2>:<input name=ouv_minute value=\"$ouv_minute\" size=2></td></tr>
		<tr><td colspan=3>{$doc->lang["config_modify_racine_fichiers"]}: <input name=racine value=\"{$optionsListe["racine"]}\" size=40></td></tr>
		<tr><td colspan=3>{$doc->lang["config_modify_use_webdav"]}: {$doc->lang["general_oui"]}<input type=radio name=use_webdav value=\"1\" $use_webdav_check> {$doc->lang["general_non"]}<input type=radio name=use_webdav value=\"0\" $use_webdav_uncheck></td></tr>
		<tr><td colspan=3>{$doc->lang["config_modify_racine_webdav"]}: <input name=racine_webdav value=\"{$optionsListe["racine_webdav"]}\" size=40></td></tr>
		<tr><td colspan=3>{$doc->lang["config_modify_racine_mbx"]}: <input name=racine_mbx value=\"{$optionsListe["racine_mbx"]}\" size=40></td></tr>
		<tr><td>{$doc->lang["config_modify_mail_username"]}: <input name=user_mbx value=\"{$optionsListe["user_mbx"]}\" size=10>{$doc->lang["config_modify_mail_password"]}: <input type='password' name=pass_mbx value=\"{$optionsListe["pass_mbx"]}\" size=10></td></tr>
		<tr><td colspan=3>DoodleID: <input type=text name=doodle value=\"{$optionsListe["doodle"]}\" size=40><img src='$doodleImage'></td></tr>
		</table>
		<br>";
		
		#Deuxième partie de la page, avec les boîtes de sélection multiple
		echo "
		<table width=\"100%\" align=\"center\">
		<tr><td><h4>{$doc->lang["config_modify_types_adresses"]} :</h4><textarea cols=30 rows=6 name=types_adresses>", $doc->liste_ok($optionsListe["ltype"]), "</textarea></td>
		<td><h4>{$doc->lang["config_modify_types_comptes"]} :</h4><textarea cols=30 rows=6 name=types_comptes>", $doc->liste_ok($optionsListe["ac_type"]), "</textarea></td><td><h4>{$doc->lang["config_modify_perso_type_delais"]} :</h4><textarea cols=30 rows=6 name=types_delais>", $doc->liste_ok($optionsListe["delais_type"]), "</textarea></td></tr>
		<tr><td colspan=3 style=\"border-left:none;border-right:none\">&nbsp;</td></tr>
		<tr><td><h4>{$doc->lang["config_modify_types_operations"]} :</h4><textarea cols=30 rows=6 name=types_operations>", $doc->liste_ok($optionsListe["op_type"]), "</textarea></td>
		<td><h4>{$doc->lang["config_modify_types_dossiers"]} :</h4><textarea cols=30 rows=6 name=types_dossiers>", $doc->liste_ok($optionsListe["dossiers_type"]), "</textarea></td><td valign=top><h4>{$doc->lang["config_modify_perso_type_lieux"]} :</h4><textarea cols=30 rows=6 name=lieux>", $doc->liste_ok($optionsListe["lieux"]), "</textarea></td>
		
		</tr>";
		
		#Gestion des soustraitants
		$listeSS = $doc->listeArray($optionsListe["soustraitants"]);
		$maListe = "";
		$n = 0;
		$acces = array();
		foreach ($doc->liste_acces() as $int => $ar)
		{
			$acces[] = $ar["user"];
		}
		foreach($listeSS as $arr)
		{
			$n++;
			list($alias, $nom, $droit) = $arr;
			$maListe .= "\n<tr><td>$n</td><td><input type=text name=place_$n size=1></td><td><input type=text name=\"soustraitant_alias[$n]\" value=\"$alias\"></td><td><select name=\"soustraitant_nom[$n]\"><option></option>";
			foreach($acces as $acc)
			{
				$selected = ($nom == $acc) ? "selected":"";
				$maListe .= "\n<option value=\"$acc\" $selected>$acc</option>";
			}
			$check = $droit ? "checked" : "";
			$maListe .=  "</select></td><td align=right><input type=checkbox name=\"soustraitant_droit[$n]\" $check></td></tr>";
		}
		$maListe .= "\n<tr><td colspan=2>&nbsp;</td><td><input type=text name=\"soustraitant_alias[0]\" value=\"\"></td><td><select name=\"soustraitant_nom[0]\">".$doc->simple_selecteur($acces)."</select></td><td align=right><input type=checkbox name=\"soustraitant_droit[0]\" ></td></tr>";
		echo "<tr><td colspan=3>&nbsp;</td></tr>";
		echo "<tr valign=top><td><h4>{$doc->lang["config_modify_matiere"]} :</h4><textarea cols=30 rows=6 name=types_matieres>", $doc->liste_ok($optionsListe["matiere_type"]), "</textarea></td>";
		echo "<td rowspan=2>";
		if($verif["a_un_droit"]=="ok" AND (isset($_POST["setcurrent"])))
		//if($doc->testval("admin", $_POST["setcurrent"]))
		{
			$x = 1;
			$adv0 = $doc->advice($doc->lang["config_modify_perso_vers_explication"]);
			$adv1 = $doc->advice($doc->lang["config_modify_perso_nom_explication"]);
			$adv2 = $doc->advice($doc->lang["config_modify_perso_droit_explication"]);
			echo "<h4>{$doc->lang["config_modify_soustraitants"]} :</h4><table><tr><th colspan=2>&rarr; $adv0</th><th align=left>{$doc->lang["biblio_title"]}</th><th>{$doc->lang["config_modify_associe"]} $adv1</th><th>{$doc->lang["config_modify_perso_droit"]} $adv2</th></tr>";
			echo $maListe;
			echo "</table></td>";
		}
		else echo $_POST["setcurrent"];
		
		#Gestion des bibliothèques
		$maListe = "";
		$types = array($doc->lang["biblio_type0"], $doc->lang["biblio_type1"]);
		$listeBB = $doc->listeArray($optionsListe["bibliotheques"]);
		array_push($listeBB, array("", ""));
// 		$doc->tab_affiche($listeBB);
		foreach($listeBB as $arr)
		{
			list($biblioName, $biblioType) = $arr;
			$tListe = "";
			foreach($types as $n => $t)
			{
				$check = ($n == $biblioType) ? "selected":"";
				$tListe .= "<option value=\"$n\" $check>$t</option>";
			}
			$maListe .= "\n<tr><td><input type=text name=\"biblioName[]\" value=\"$biblioName\"></td><td><select name=\"biblioType[]\">$tListe</select></td></tr>";
		}
		echo "<td><h4>{$doc->lang["biblio_titre"]} :</h4><table border=0><tr><th align=left>{$doc->lang["config_modify_perso_alias"]}</th><th>{$doc->lang["config_modify_util_type"]}</th></tr>$maListe</table></td></tr>";
		$origine = $doc->liste_ok($optionsListe["origine_mandat"]);
		$mailing = $doc->liste_ok($optionsListe["mailing"]);
		echo "<tr><td><h4>{$doc->lang["modifier_donnees_origine_mandat"]}</h4><textarea cols=30 rows=6 name=origine_mandat>$origine</textarea></td><td><h4>{$doc->lang["modifier_donnees_mailing"]}</h4><textarea cols=30 rows=6 name=mailing>$mailing</textarea></td></tr>";
		echo "<tr><td colspan=3>";
		
		echo $doc->button("{$doc->lang["config_modify_maj_others"]}", "");
		echo $doc->input_hidden("setcurrent", 1);
		echo "
		</td></tr>
		</table>
		</form>";
		echo $doc->form("config/modify_perso.php", "{$doc->lang["config_styles_restaurer"]}", "", "", "", "restaurer", "on", "setcurrent", "{$_POST["setcurrent"]}");
	}
}
$doc->close();
?>
