<?php
/****
* Titre........... : Modify.php
* Description..... : Gestion des options de base du programme etude
* version......... : 4.0
* date............ : 15.03.2010
* fichier......... : modify.php
* Auteur.......... : Olivier Subilia (etudeav@users.sourceforge.net)
*
* licence......... : The GNU General Public License (GPL) 
*					 http://www.opensource.org/licenses/gpl-license.html
*
****/
		
require_once("../inc/autoload.php");
session_start();

$doc=new prolawyer("noExit");

//$doc->tab_affiche();

if($_REQUEST["fromCreate"])
{
	$_POST["listeutils"] = unserialize(stripslashes($doc->getCookie("keeputils")));
	$_POST["listeacces"] = unserialize(stripslashes($doc->getCookie("keepacces")));
	$_POST["listegpAcces"] = unserialize(stripslashes($doc->getCookie("keepgpAcces")));
	$_POST["listegpUtils"] = unserialize(stripslashes($doc->getCookie("keepgpUtils")));
}
else
{
	$doc->setCookie("keeputils", serialize($_POST["listeutils"]));
	$doc->setCookie("keepacces", serialize($_POST["listeacces"]));
	$doc->setCookie("keepgpAcces", serialize($_POST["listegpAcces"]));
	$doc->setCookie("keepgpUtils", serialize($_POST["listegpUtils"]));
}


if(!$_SESSION["optionsFile"] || !$_SESSION["fonctionsDispo"] || !$_SESSION["dbName"] || !$_SESSION["connectionMethods"] || ! $_SESSION["dbAdmin"])
{
	header("Location: ./config.php");
	die();
}
$doc->title();
$doc->body(2, "");
$doc->entete();

echo "<h2>{$doc->lang["config_modify_h2"]}</h2>";

echo $doc->table_open()."<tr>";
foreach(array("utils" => "user", "assoc" => "partner", "usergroups" => "ugroupe", "groups" => "groupe", "droits" => "droits") as $nom => $mode)
{
	$bName = "<img style=\"display:block;margin:auto\" src=\"../images/$mode.png\"><br>{$doc->lang["config_modify_gestion_$nom"]}";
	#echo "<td align=center>".$doc->form("config/modify.php", $bName, "", "", "goto_$mode<td>", "mode", "$mode")."</td>";
	echo "<td align=center><form action=\"./modify.php\" method=\"POST\"><input type=\"hidden\" name=\"mode\" value=\"$mode\"><button type=\"submit\"><img style=\"display:block;margin:auto\" src=\"../images/$mode.png\"></button><br><button type=\"submit\">{$doc->lang["config_modify_gestion_$nom"]}</button></form></td>";
}
echo "</tr>".$doc->table_close();
$doc->now[] = time(). " (on est au début de la page)";
if($_REQUEST["mode"] == "droits")
{

	//formulaire pour afficher et gérer les associés, visible aussi par l'associé pour ce qui le concerne
	//--------------------------------------début du formulaire------------------------
	echo "\n<//gestion des associés//>
	<h3>{$doc->lang["config_modify_reg_parts"]}</h3>";
	
	//Liste des menus déroulants possible
	$u1i = $doc->liste_utilisateurs("force") + $doc->liste_utilisateurs_archives("force");
	foreach($u1i as $index => $ar) $u1c[] = $ar["nom"].",".$index;
	$u2c = $doc->liste_utilisateursgroupes(true);
	
	$a1i = $doc->liste_acces(true);
	foreach($a1i as $ar) $a1c[] = $ar["user"].",".$ar["type"];
	$a2c = $doc->liste_accesgroupes(true);
	
	foreach(array("utils" => $u1c, "gpUtils" => $u2c, "acces" => $a1c, "gpAcces" => $a2c) as $actName => $actAr)
	{
		$actAffiche = "config_modify_affiche_{$actName}";
		$postval = "liste{$actName}";
		$echo =  "{$doc->lang["$actAffiche"]}&nbsp:\n<br><select size=6 name=\"{$postval}[]\" multiple>\n<option value=\"all\">{$doc->lang["general_tous"]}</option>\n<option>------</option>";
		foreach($actAr as $nm)
		{
			list($n1, $n2, $n3) = preg_split("#,#", $nm);
			$selected = (@in_array($nm, $_POST["$postval"]) || @in_array("all", $_POST["$postval"])) ? "selected":"";
			if($selected && !in_array($nm, $_POST["$postval"])) $_POST["$postval"][] = $nm;
			$echo .= "\<option value=\"$nm\" $selected>$n1</option>";
		}
		$echo .= "\n</select>";
		$selecteurs[] = $echo;
		$selecteurs[] = "&nbsp;";
	}
	
	//liste des menus déroulants choisis
	$u1 = $_POST["listeutils"]? $_POST["listeutils"]:array();
	$u2 = $_POST["listegpUtils"]? $_POST["listegpUtils"]:array();
	$r1 = $_POST["listeacces"]? $_POST["listeacces"]:array();
	$r2 = $_POST["listegpAcces"]? $_POST["listegpAcces"]:array();
	
	//nettoyage de "all"
	$all=array_keys($u1, "all");
	foreach($all as $k) unset($u1["$k"]);
	$all=array_keys($u2, "all");
	foreach($all as $k) unset($u2["$k"]);
	$all=array_keys($r1, "all");
	foreach($all as $k) unset($r1["$k"]);
	$all=array_keys($r2, "all");
	foreach($all as $k) unset($r2["$k"]);
	
	$ps = $u1 + $u2;
	
	$selecteurs[] = $doc->button($doc->lang["operations_valider"], "");
	
	echo "\n<form action = \"./modify.php\" method=post>";
	echo $doc->nice_array($selecteurs, 8, "", "", "style=\"vertical-align:top\"");
	echo $doc->input_hidden("mode", false, "droits");
	echo "\n<br><br>";
	echo "\n</form>";
	
// 	echo "<h3>TODO: faire en sorte que toutes les cases n'apparaissent pas en même temps (temps infini à charger)</h3>";
	echo "\n<form action=\"./create.php\" method=post name=\"maj\" id=\"maj\">";
	
	$doc->liste_des_droits=$doc->liste_droits("ALL", true);
// 	$doc->tab_affiche($doc->liste_des_droits);
// 	$doc->tab_affiche($doc->liste_des_droits);
	
	$deleteList = array();
	foreach(array("utils" => $u1, "gpUtils" => $u2) as $curI => $curA)
	{
		//$doc->tab_affiche($u1i);
		$num_form=0;
		$spec_forms=array();
		$ass_forms=array();
		foreach($curA as $p)
		{
			$cuser=$_SESSION["user"];
			list(,$pbis) = preg_split("#,#", $p);
			if($doc->liste_des_droits[$pbis][$cuser]["admin"] == 1 || $_SESSION["type"] == "admin")
			{
				$colspanInit = count($doc->p_inits) + 1;;
				$num_form ++;
				list($a,$init)=preg_split("#,#", $p);
				$t_ass="";
				$formname="maj";
				$formnamecolor="user_mode__".$init."__couleur";
				$t_ass .= $doc->input_hidden("mode", 1);
				$t_ass .= $doc->input_hidden("action", "", "maj");
				$t_ass .= "\n<table border=1 style=\"border-top:#bcc2fc double 5px;border-left:#bcc2fc double 5px;border-right:#bcc2fc double 5px;border-bottom:#bcc2fc double 5px\" cellspacing=0 cellpadding=0 width=100%>";
				$t_ass .= "\n<tr>\n";
				$t_ass .= "\n<th colspan=$colspanInit>".ucfirst("{$a}");
				if($init) $t_ass .= " ($init). ";
				else $init = "autoUsergroupe__$a";
				$deleteTempList = array();
				$t_ass .= "</tr>";
				$t_ass .= "\n<tr>
				\n<td>";
				//TODO: pourquoi cette regle if($curI == "utils" && trim($a["seul"]) == "1") $t_ass .= $doc->input_hidden("user_mode__".$init."__seul", "", "1");			
	
				$vertical = ($num_form < 4) ? true:false;
				$t_ass .= "\n\t\t<tr><td>&nbsp;</td>".$doc->gerenew("xx", "xxclients", "mef_inits_only", $vertical, "1")."</tr>";
				$t_ass .= "\n\t\t<tr><td colspan = $colspanInit style=\"color:#5e61fc\">{$doc->lang["config_modify_reg_utils"]}</tr>";
				foreach($r1 as $key => $l)
				{
					list($l, $type)=preg_split("#,#", $l);
					$style = ($type == "admin")? "class=\"attention\"":"";
					$t_ass .= "\n\t\t<tr><td $style>{$l}&nbsp;:&nbsp;</td>";
					$t_ass .= $doc->gerenew($l, $init, "mef_rights_only_noadmin", false, "1")."</tr>";
					$deleteTempList[] = "$l";
				}
				$t_ass .= "\n\t\t<tr><td colspan = $colspanInit style=\"color:#5e61fc\">{$doc->lang["config_modify_reg_groups"]}</tr>";
				foreach($r2 as $key => $l)
				{
					$t_ass .= "\n\t\t<tr><td>$l&nbsp;:&nbsp;</td>";
					$t_ass .= $doc->gerenew("autoprolawyergroupe__$l", $init, "mef_rights_only_noadmin", false, "1")."</tr>";
					$deleteTempList[] = "autoAccesgroupe__$l";
				}
				$delInit = preg_match("#autoUsergroupe#", $init)?$a:$init;
				$deleteList[$curI][] = "$delInit:".implode(",", $deleteTempList);
				
				$t_ass .= "</tr></table>";
				
				$ass_forms[]=$t_ass;
			}
		}
		if(is_array($ass_forms) && !empty($ass_forms))
		{
			echo "<h3 class=\"\">{$doc->lang["config_modify_affiche_{$curI}"]}";
			echo $doc->nice_array($ass_forms);
			echo $doc->button("{$doc->lang["config_modify_maj_others"]}", "");
		}
		else echo "<h2 class=attention_bg>$config_modify_err2</h2>";
	}
	foreach($deleteList as $init => $arr) echo $doc->input_hidden("deletelist$init", "",implode(";", $arr));
	echo "</form>";
	foreach($spec_forms as $form) echo "<span class=invisible>$form</span>";
	
	echo "\n<//fin de la gestion des associés//>";
	//----------------------------------------fin du formulaire------------------my------
	
}


if($_REQUEST["mode"] == "partner")
{
	if($_SESSION["type"] == "admin")
	{	
		$ps=$doc->liste_utilisateurs("force");
		$doc->liste_des_droits=$doc->liste_droits();
		$ps=$doc->liste_utilisateurs("force", "all");
// 		$doc->tab_affiche($ps);
		$num_form = 0;
		echo $doc->table_open("border=0");
		echo "\n<tr style=text-align:left><th>{$doc->lang["config_modify_util_name"]}</th><th>{$doc->lang["config_modify_init"]}</th><th>{$doc->lang["config_modify_util_couleur"]}</th><th colspan=3>{$doc->lang["operations_actions"]}</th></tr>";
		foreach($ps as $p => $array)
		{
			$num_form ++;
			$a=$ps[$p];
			$init=$p;
			$archive = $a["archive"] ? "":"1";
			$arcButt = $a["archive"] ? $doc->lang["config_modify_archive_off"]:$doc->lang["config_modify_archive_on"];
			if(!$doc->hasArchive && !$archive)
			{
				$doc->hasArchive = True;
				echo "\n<tr><th colspan=\"6\"><br>{$doc->lang["config_modify_archives"]}".$doc->advice($doc->lang["config_modify_advice"])."</th></tr>";
			}
			$arcStyle = $a["archive"] ? "style=\"font-style:italic;font-color:d0d0d0\"":"";
			$arcColor = $a["archive"] ? "d0d0d0":$array["couleur"];
			$formname="maj__{$init}";
			$formnamecolor="user_mode__".$init."__couleur";
			$t_ass="";
			$t_ass .= "\n<tr $arcStyle>";
			$t_ass .= "\n<td>{$a["nom"]}</td><td>($init)</td>";
			$t_ass .= "<td>";
			$t_ass .= "\n<form action=\"./create.php\" method=post name=\"$formname\" id=\"$formname\" style='display:inline'>";
			$t_ass .= $doc->input_hidden("mode", 1);
			$t_ass .= $doc->input_hidden("action", "", "maj");
			$t_ass .= "<input style= \"background-color:$arcColor;cursor:pointer\"";
			$t_ass .= " onClick=\"window.open('../selecteur.php?init=$init&formname=$formname&formnamecolor=$formnamecolor','selecteur','width=400,height=200,toolbar=no,directories=no,menubar=no,location=no,status=no,scrollbars=no');\" ";
			$t_ass .= "type=\"text\" size=8 name=\"$formnamecolor\" id=\"$formnamecolor\" value=\"{$array["couleur"]}\">";
			$t_ass .= "</form>";
			$t_ass .= "</td>";
		
			//$t_ass .= "</td>";
			$t_ass .= "<td class = \"attention\" style=\"cursor:pointer\" onclick=\"document.getElementById('detruire_$init').submit()\">".$doc->lang["config_modify_delete"]."</td>";
			
			if(!$a["seul"])
			{
				$spec_forms[] = $doc->form("config/create.php", "", "", "", "detruire_$init<td>", "init", "$init", "setconfirm", "on", "mode", "partner", "delete_mode", "delete");
				$t_ass .= "\n<td class = \"attentionmild\" style=\"cursor:pointer\" onclick=\"document.getElementById('detruire_tables_$init').submit()\">".$doc->lang["config_modify_delete_seul"]."</td>";
				$spec_forms[] = $doc->form("config/create.php", "", "", "attention", "detruire_tables_$init<td>", "init", "$init", "setconfirm", "on", "mode", "partner", "delete_mode", "delete_seul");
			}
			if($a["seul"])
			{
				$spec_forms[] = $doc->form("config/create.php", "", "", "", "detruire_$init<td>", "init", "$init", "setconfirm", "on", "mode", "partner", "delete_mode", "delete", "seul", "on");
				$t_ass .= "\n<td class = \"attentionok\" style=\"cursor:pointer\" onclick=\"document.getElementById('rajouter_$init').submit()\">".$doc->lang["config_modify_rajouter_seul"]."</td>";
				$spec_forms[] = $doc->form("config/create.php", "", "", "invisible", "rajouter_$init<td>", "init", "$init", "mode", "partner", "ajoute", "true");
			}
			$t_ass .= "\n<td><form style=\"display:inline\" id=\"archive_$init\" action=\"{$doc->settings["root"]}config/create.php\" method=\"POST\"><input type=\"hidden\" name=\"init\" value=\"$init\"><input type=\"hidden\" name=\"setarchive\" value=\"on\"><input type=\"hidden\" name=\"mode\" value=\"partner\"><input type=\"hidden\" name=\"archive\" value=\"$archive\"><button type=\"submit\">$arcButt</button></form></td>";
			$t_ass .= "</tr>";
		
			echo $t_ass;
		
		}
		if(!$doc->hasArchive) echo "\n<tr><th colspan=\"6\"><br>{$doc->lang["config_modify_archives"]}".$doc->advice($doc->lang["config_modify_advice"])."</th></tr>";
		echo $doc->table_close();
		//echo "\n</form>";
	
		//Affichage des formulaires de destruction (invisibles) en dehors du formulaire principal
		if(is_array($spec_forms)) foreach($spec_forms as $form) echo "<span class=invisible>$form</span>";
		
		//-------------------------------------------------------------------------------
		echo "\n<//début de la création des associés//>";
		if($_SESSION["type"] == "admin")
		{
			echo "<form action=\"./create.php\" method=\"post\">";
			echo $doc->input_hidden("mode", "", "partner");
			echo $doc->input_hidden("create", "", "on");
			
			echo "<br>";
			if ($db_ok=true) echo "<br>{$doc->lang["config_modify_ajout_assoc"]}.<br>";
			else echo $doc->lang["config_modify_warning_db"];
			
			echo "<table border=0>
			<tr><td>{$doc->lang["config_modify_assoc_name"]}</td><td><input type=\"text\" name=\"nom\"></td></tr>
			<tr><td>{$doc->lang["config_modify_assoc_init"]}</td><td><input type=\"text\" name=\"init\"></td></tr>
			<tr><td>{$doc->lang["config_modify_assoc_seul"]}</td><td><input type=\"checkbox\" name=\"seul\"></td></tr>
			<tr><td>{$doc->lang["config_modify_util_couleur"]}</td><td><input onfocus=\"window.open('../selecteur.php?formnamecolor=couleur','selecteur','width=400,height=200,toolbar=no,directories=no,menubar=no,location=no,status=no,scrollbars=no');\" onclick=\"window.open('../selecteur.php?formnamecolor=couleur','selecteur','width=400,height=200,toolbar=no,directories=no,menubar=no,location=no,status=no,scrollbars=no');\" type=\"text\" size=8 name=\"couleur\" id=\"couleur\" style=\"cursor:pointer\"></td></tr>";
			if($db_ok=true)
			{
				echo "<tr><td>";
				echo $doc->button($doc->lang["config_modify_create"], "");
				echo "</td></tr>";
			}
			
			echo "\n</table>
			</form>";
		}	
		echo "\n<//fin de la création des associés//>";
		
		
		//-------------------------------------------------------------------------------
		//Récupération des associés manquants
		//-------------------------------------------------------------------------------


		$doc->recuperationAssocies(False, "", "mode=partner");
		
	}
	else
	{
		echo "Ts, ts, ts...";
	}
}


if($_REQUEST["mode"] == "user")
{
	//-------------------------------------------------------------------------------
	echo "\n<//gestion des accès//>";
	if($_SESSION["type"] == "admin")
	{
		
		$colspanInit = count($doc->p_inits) + 1;;
		echo "<h3>{$doc->lang["config_modify_reg_utils"]}</h3>";
		//affichage des accès (sert également à créer la liste des accès à détruire) et test du nombre d'administrateurs
		$nbadm=0;
		$util=FALSE; // s'il n'y a aucun accès, la valeur $util sera FAUSSE
		$liste=$doc->liste_acces();
		$util_list=array();
		$valeurs  = "\nvar admin = new Array();";
		$valeurs .= "\nvar assoc = new Array();";
		$valeurs .= "\nvar secret = new Array();";
		$valeurs .= "\nvar compta = new Array();";
		echo $doc->table_open("border=1");
		echo "\n\t\t<tr><th>&nbsp;</th>".$doc->gerenew("xx", "xxclients", "mef_inits_only", true, "1")."<th>{$doc->lang["operations_actions"]}</th></tr>";
		foreach($liste as $option)
		{
			$nom = $option["user"];
			$type = $option["type"];
			if($type == "admin")
			{
				$nbadm++;
				$valeurs .= "\nadmin[admin.length] = '$nom';";
			}elseif($type == "associe"){
				$valeurs .= "\nassoc[assoc.length] = '$nom';";
			}elseif($type == "secretaire"){
				$valeurs .= "\nsecret[secret.length] = '$nom';";
			}elseif($type == "compta"){
				$valeurs .= "\ncompta[compta.length] = '$nom';";
			}
			$nom_fonction="config_modify_".$type;
		
			echo "\n<form action=\"./create.php\" method=post>";
			echo $doc->input_hidden("mode", "", "partner");
			echo "<tr><td>$nom (".$doc->lang["$nom_fonction"].")</td>"; //affichage des données en clair
			if($type == "admin") echo "<td colspan = \"$colspanInit\">&nbsp;</td>";
			else echo $doc->gerenew($nom, "ALLPROLAWYERUSER", "mef_rights_only", false, 1)."<input type=hidden name=nom value=$nom><input type=hidden name=modify_all value=on></td><td><button type=submit class=\"attentionok\">{$doc->lang["config_modify_all"]}</button>";
			echo $doc->input_hidden("global_user","", "$nom"); 
			echo $doc->input_hidden("action","", "maj"); 
			echo "</td></tr>";
			echo "</form>";
			
			if($nom<>"") 
			{
				$util_list["$nom"]=$doc->lang["$nom_fonction"];
				$util=TRUE;
			}
		}
		
		echo $doc->table_close();
		$liste_a_detruire=""; //initialisation du menu de destruction
		$liste_a_modifier="<option value=\"\">$config_modify_nouveau_compte\n<option value=\"------\">------\n"; //initialisation du menu de modification
		foreach($util_list as $nom => $val)
		{
			if($_SESSION["user"] != $nom)	$liste_a_detruire=$liste_a_detruire."<option value=$nom>$nom\n";
			$liste_a_modifier=$liste_a_modifier."<option value=$nom>$nom\n";
		} 
		
		if ($db_ok=true)
		{
			echo "\n\n<br>{$doc->lang["config_modify_ajout_util"]}.<br>";
			echo "\n<script language=javascript>
			$valeurs
			
			function chval(nom)
			{
				for(i=0;i<admin.length;i++)
				{
					if(admin[i] == nom) return 0;
				}
				for(i=0;i<assoc.length;i++)
				{
					if(assoc[i] == nom) return 1;
				}
				for(i=0;i<secret.length;i++)
				{
					if(secret[i] == nom) return 2;
				}
				for(i=0;i<compta.length;i++)
				{
					if(compta[i] == nom) return 3;
				}
				return 4;
			}
			
			function modifname()
			{
				testname = document.getElementById('util_name').value;
				if(testname != '')
				{
					document.getElementById('autoname').innerHTML='';
					document.getElementById('type').selectedIndex=chval(testname);
					document.getElementById('tochange').innerHTML='{$doc->lang["operations_modifier"]}';
				}
				else
				{
					document.getElementById('autoname').innerHTML='<input type=\"text\" name=\"util_new_name\" id=\"util_new_name\">';
					document.getElementById('type').selectedIndex=4;
					document.getElementById('util_new_name').focus();
					document.getElementById('tochange').innerHTML='{$doc->lang["config_modify_create"]}';
				}
			}
			function verif()
			{
				var test=false;
				testname = document.getElementById('util_name').value;
				testname2 = document.getElementById('util_new_name').value;
				if((testname != '' && testname2 == '')|| (testname == '' && testname2 != '')) return true;
				else
				{
					alert('Donnees erronees');
					return false;
				}
			}
			</script>";
			
			$createMethods = array("prolawyer"); // Pour l'instant, on ne peut pas créer des utilisateurs ailleurs que dans Prolawyer
			echo "\n<form action=\"./create.php\" method=\"post\" onSubmit=\"return verif()\">
			<table border=0>
			<tr>
				<td>{$doc->lang["config_modify_util_name"]}</td>
				<td><select name=\"util_name\" id=\"util_name\" onchange=modifname()>$liste_a_modifier</select></td>
				<td>&nbsp;</td>
				<td><div id=\"autoname\"><input type=\"text\" name=\"util_new_name\" id=\"util_new_name\"></div></td>
				<td>{$doc->lang["config_modify_util_pwd"]}</td>
				<td><input type=\"password\" size=8 name=\"new_pwd\"></td>
				<td>{$doc->lang["config_modify_util_verif"]}</td>
				<td><input type=\"password\" size=8 name=\"vpwd\"></td>
				<td>{$doc->lang["config_modify_util_type"]}&nbsp;:</td>
				<td><select name=\"type\" id=\"type\">
				<option value=\"admin\">{$doc->lang["config_modify_admin"]}
				<option value=\"associe\">{$doc->lang["config_modify_associe"]}
				<option value=\"secretaire\">{$doc->lang["config_modify_secretaire"]}
				<option value=\"compta\">{$doc->lang["config_modify_compta"]}
				<option value=\"utilisateur\" selected>{$doc->lang["config_modify_utilisateur"]}
				</select></td>
				<td>{$doc->lang["config_modify_connection_method"]}&nbsp;:</td>
				<td><select name=\"connectionMethod\" id=\"connectionMethod\">";
				foreach($_SESSION["connectionMethods"] as $method) if(in_array($method, $createMethods)) echo "\n<option value=\"$method\">$method</option";
				echo "\t\t\t\t</select></td>
			</tr><tr>
				<td>";
			echo $doc->input_hidden("mode", "", "user");
			echo $doc->button("{$doc->lang["config_modify_create"]}", "<id>tochange");
			echo "</td></tr>";
			
			echo "</table>
			</form>";
		}
		else echo "{$doc->lang["config_modify_warning_db"]}";
		
		//Formulaire de destruction des utilisateurs
		if($util && $liste_a_detruire)
		{
			echo "<form action=\"./create.php\" method=\"post\">
			<h3>{$doc->lang["config_modify_delete_user"]}</h3>
			{$doc->lang["config_modify_delete_the_user"]} <select name=\"user_delete\">$liste_a_detruire</select><br>";
			echo $doc->input_hidden("delete", "", "on");
			echo $doc->input_hidden("setconfirm", "", "on");
			echo $doc->input_hidden("mode", "", "user");
			echo $doc->button("{$doc->lang["config_modify_delete"]}", "");
			echo "</form>";
		}
	
		echo "\n<//fin de la gestion des utilisateurs//>";
	}
	else //TODO: ne fonctionne pas
	{
		$liste=$doc->open_and_prepare("{$doc->settings["passfile"]}");
		foreach($liste as $line)
		{
			list($nom,,,$fonction) = explode(",", $line);
			if(trim($nom == $_SESSION["session_utilisateur"]))
			{
				echo "\n<h3>{$doc->lang["config_modify_changer_pw"]}</h3>
				<script language=javascript>
				function verif()
				{
					var test=false;
					testname = document.getElementById('util_name').value;
					testname2 = document.getElementById('util_new_name').value;
					if((testname != '' && testname2 == '')|| (testname == '' && testname2 != '')) return true;
					else
					{
						alert('Donnees erronees');
						return false;
					}
				}
				</script>";
				
				echo "\n<form action=\"./create.php\" method=\"post\" onSubmit=\"return verif()\">
				<table border=0>
				<tr>
					<td>{$doc->lang["config_modify_util_pwd"]}</td>
					<td><input type=\"password\" size=8 name=\"new_pwd\"></td>
					<td>{$doc->lang["config_modify_util_verif"]}</td>
					<td><input type=\"password\" size=8 name=\"vpwd\"></td>
				</tr><tr>
					<td>";
				echo $doc->input_hidden("type", "$fonction");
				echo $doc->input_hidden("util_name", "$nom");
				echo $doc->input_hidden("mode", "", "user");
				echo $doc->button("{$doc->lang["operations_modifier"]}", "<id>tochange");
				echo "</td></tr>";
				
				echo "</table>
				</form>";
			
			}
		}
	}
	echo $doc->echoError();
}

if(($_REQUEST["mode"] == "groupe" || $_REQUEST["mode"] == "ugroupe") && ($_SESSION["type"] == "admin"))
{
	$users=($_REQUEST["mode"] == "ugroupe")? TRUE:FALSE;
	
	echo "\n<//gestion des groupes//>
	
	<h3>{$doc->lang["config_modify_reg_groups"]}</h3>";
	
	
	echo $doc->table_open("border=1");
	echo "<form name=\"maj\" action=\"./create.php\" method=\"post\"><tr><th>&nbsp;</th>";
	echo $doc->input_hidden("mode", true);
	$l = $users ? $doc->liste_accesgroupes(true):$doc->liste_utilisateursgroupes(true);
	$lx = $users ? $doc->liste_accesgroupes_ex(true):$doc->liste_utilisateursgroupes_ex(true);
	$u = $users ? $doc->liste_acces(true):$doc->liste_utilisateurs("force")+ $doc->liste_utilisateurs_archives("force");
	$colspan=1;
	
	foreach($l as $groupname)
	{
		$fName = $doc->getImageName("$groupname", TRUE);

		$colspan ++;
		if(is_file("$fName")) $source = $fName;
		else $source = "../image.php?type=ver&nom=$groupname";
		echo "<th style=\"vertical-align:bottom\"><img src=\"$source\"></th>";
	}
	echo "</tr>";
	$util=FALSE; // s'il n'y a aucun utilisateur, la valeur $util sera FAUSSE
	echo "{$doc->lang["config_modify_groups"]}:<br>";
	foreach($u as $utilisateur => $array)
	{
		if($users || 1)
		{
			if($users) $u["$utilisateur"]["nom"] = $u["$utilisateur"]["user"];
			if($users) $u["$utilisateur"]["initiales"] = $u["$utilisateur"]["user"];
			$actUser = $u["$utilisateur"]["initiales"];
		}
		echo "\n<tr><td>{$u["$utilisateur"]["nom"]}</td>"; //affichage des données en clair
		foreach($l as $nomgroupe)
		{
			$id1=preg_replace("# #", "_specialSpace_", $nomgroupe);
			$id="__groupname__".$id1;
			$checked="";
			if(is_array($lx["$nomgroupe"])) $checked = (in_array($actUser, $lx["$nomgroupe"]) || in_array(preg_replace("# #", "_", $actUser), $lx["$nomgroupe"]))? "checked":"";
			echo "<td><input type=\"checkbox\" name=\"{$id1}__{$actUser}\" $checked  onClick=\"document.maj.action[0].checked=true;document.maj.$id.checked=true\"></td>";
			
		}
		echo "</tr>";
	}
	echo "\n<tr><td colspan=$colspan>&nbsp;</td></tr>";
	echo "\n<tr><td>{$doc->lang["operations_selectionner"]}</td>";
	foreach($l as $nomgroupe)
	{
		$id=preg_replace("# #", "_specialSpace_", $nomgroupe);
		$id="__groupname__".$id;
		echo "<td><input type=\"checkbox\" id=\"$id\" name=\"$id\"></td>";
	}
	echo "</tr>";
	echo "<tr><td colspan=$colspan>{$doc->lang["operations_pour_selection"]} :<input type=radio name=\"action\" value=\"update\" checked> {$doc->lang["operations_modifier"]} <input type=radio name=\"action\" value=\"delete\"> {$doc->lang["operations_supprimer"]}",
	$doc->input_hidden("setconfirm", "", "on"),
	$doc->button("{$doc->lang["operations_valider"]}", ""), "</td></tr></form>";
	echo $doc->table_close();
	
	
	echo "<br>";
	echo "{$doc->lang["config_modify_ajout_group"]}.<br>";
	
	echo "<form action=\"./create.php\" method=\"post\">";
	echo $doc->input_hidden("mode", true);
	echo "<table border=0>
	<tr>
		<td>{$doc->lang["config_modify_util_name"]}</td>
		<td><input type=\"text\" name=\"new_name\"></td>
	</tr><tr>
		<td>";
	echo $doc->input_hidden("action", "", "create");
	echo $doc->button("{$doc->lang["config_modify_create"]}", "");
	echo "</td></tr>";
	
	echo "</table>
	</form>";
}
$doc->now[] = time();
$doc->close();
?>
