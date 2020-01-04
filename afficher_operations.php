<?php
require_once("./inc/autoload.php");
session_start();
error_reporting(7);
$doc=new prolawyer;
$doc->connection();
$doc->title();
$doc->body(1);

//Restrictions en provenance de operations.php
foreach(array("op_date_limite", "recherche_limite", "sous_traitant_limite", "plage_limite") as $key) if($_POST["$key"]) $$key = stripslashes($_POST["$key"]);

if($_POST["op_date_limite"]) $ac_date_limite = preg_replace("/dateop/", "dateac", $op_date_limite);

//affichage des données du client dans un tableau, si on l'a demandé
// $resultat_recherche=mysqli_query($doc->mysqli, "select adresses.*, d1.noadresse, d1.prixhoraire, d1.naturemandat from adresses, {$_SESSION["session_avdb"]} as d1 where nodossier like '{$_POST["nodossier"]}' and noadresse = id");
if(! $_POST["entete"]=="on") $noentete = True;
require("./data_client.php");

echo "<br>";

$affichage=array("operations", "encaissements");
foreach($affichage as $encours)
{
	if($_POST["$encours"] == "on")
	{

		if($encours == "operations")
		{
			$aff_op="op";
			$aff_dateop="dateop";
		}else{
			$aff_op="ac";
			$aff_dateop="dateac";
		}

		//Affichage des en-tête du tableau des opérations//
		echo "\n\n<table width=100% align=center border=0>
		\n\t<tr>";
		if($encours=="operations")
		{
			$classement_op= "rang, $aff_op,";
			echo "<th align=left>{$doc->lang["afficher_operations_date"]}</th>";
			echo "<th align=left>{$doc->lang["afficher_operations_operation"]}</th>";
			if($_POST["details"]=="on") echo "<th align=left>{$doc->lang["afficher_operations_details"]}</th>";
			if($_POST["affsoustrait"]=="on") echo "<th align=right>{$doc->lang["operations_soustraitant"]}</th>";
			if($_POST["temps"]=="on") echo "<th align=right>{$doc->lang["afficher_operations_temps"]}</th>";
		}
		if($encours=="encaissements")
		{
			$classement_op="";
			echo "<th align=left>{$doc->lang["operations_dateac"]}</th><th align=left>{$doc->lang["operations_ac"]}</th><th>{$doc->lang["operations_acpar"]}</th><th align=right>{$doc->lang["operations_rentree"]}</th><th align=right>{$doc->lang["operations_avfrais"]}</th><th align=right>{$doc->lang["operations_demande"]}</th><th align=right>{$doc->lang["operations_transit"]}</th><th align=right>{$doc->lang["operations_facturesanstemps"]}</th>";
		}
		//définition du nombre de colonnes
		$colspan=2;
		if($_POST["details"]=="on") $colspan++;
		if($_POST["temps"]=="on") $colspan++;
		if($_POST["affsoustrait"]=="on") $colspan++;
// 		if($_POST["details"]=="on" and $_POST["temps"]=="on") $res_colspan="colspan=2";
		$res_colspan = $colspan -2;
		$last_colspan = $colspan -1;
		$last_colspan=$colspan -1;
		$res_colspan = ($res_colspan) ? "colspan=$res_colspan":"";
		$last_colspan = ($last_colspan) ? "colspan=$last_colspan":"";
// 		if($_POST["resume"] and !$_POST["details"] and !$_POST["temps"]) $last_colspan++;
// 		if($last_colspan>1) $last_colspan="colspan=".$last_colspan;
// 		else $last_colspan="";
		if($encours=="encaissements") $colspan=8;
		echo "</tr>";


		//Affichage des opérations, ligne par ligne
		$ctrl_heure="0";
		$ctrl_min="0";
		$controle_nb_op="";
		$controle_type_op="";
		$grand_tableau=array();

		$requete_affichage="select *, o.soustraitant, dateop, nodossier, date_format(dateop, \"%d\") as date_jour, date_format(dateop, \"%c\") as date_mois, date_format(dateop, \"%Y\") as date_annee, op, opavec, time_format(tempsop, \"%k\") as temps_heure, time_format(tempsop, \"%i\") as temps_minute, tempsop, idop, if(op='Ouverture du dossier', '0', '1') as rang from {$_SESSION["session_opdb"]} o where (o.nodossier like '{$_POST["nodossier"]}' and $aff_dateop > 0 $op_date_limite $sous_traitant_limite $recherche_limite) order by $classement_op $aff_dateop";
		$resultat_op=mysqli_query($doc->mysqli, $requete_affichage);
		// echo "<br>$requete_affichage<br>";
		while($row=mysqli_fetch_array($resultat_op, MYSQLI_ASSOC))
		{
			$op_actuelle=$row["op"];
			$op_actuelle=$doc->smart_html($op_actuelle); //pour éviter un affichage d'opérations groupées différemmentes si certaines sont encodées en html et d'autres en iso/utf8
			$index=$row["dateop"].$row["idop"];
			foreach($row as $rownom => $rowval)
			{
				$grand_tableau["$op_actuelle"]["$index"]["$rownom"]=$rowval;
			}
			if($encours=="encaissements")
			{
				echo "<tr>";
				foreach(array("dateac", "ac", "acpar", "encaissement", "avfrais", "demande", "transit", "facturesanstemps") as $cont_val)
				{
					if($cont_val=="dateac" || $cont_val=="ac") $align="left";
					elseif($cont_val=="acpar") $align="center";
					else $align="right";
					echo "<td align=$align>{$row["$cont_val"]}</td>";
					$total["$cont_val"]+=$row["$cont_val"];
				}
				echo "</tr>";
			}
			
		}

		if($encours=="operations")
		{
			foreach ($grand_tableau as $groupeop)
			{ //séparation des opérations par groupe
				ksort($groupeop);// tri des opérations par date dans le groupe
				$ctrl_heure=0;
				$ctrl_min=0;
				$controle_nb_op=0;
				foreach($groupeop as $opprecise)
				{ //séparation de chaque opération et affichage de celle-ci
					$controle_type_op=$doc->smart_html($opprecise["$aff_op"]);
					$ctrl_heure=$ctrl_heure+$opprecise["temps_heure"];
					$ctrl_min=$ctrl_min+$opprecise["temps_minute"];
					$controle_nb_op++;
					echo "\n\t<tr><td>{$opprecise["date_jour"]}.{$opprecise["date_mois"]}.{$opprecise["date_annee"]}</td><td>{$opprecise["$aff_op"]}</td>";
					if($_POST["details"]=="on")
					{
						echo "<td>", $doc->smart_html($opprecise["opavec"]), "&nbsp;</td>";
					}
					if($_POST["affsoustrait"]=="on")
					{
						echo "<td>", $doc->smart_html($opprecise["soustraitant"]), "&nbsp;</td>";
					}
					if($_POST["temps"]=="on")
					{
						echo "<td align=right>{$opprecise["temps_heure"]}:{$opprecise["temps_minute"]}</td>";
					}
					echo "</tr>";
				}
				//Affichage du résumé
				
				while ($ctrl_min>59)
				{ //affichage correct du format de l'heure
					$ctrl_heure=$ctrl_heure+1;
					$ctrl_min=$ctrl_min-60;
				}
				echo "\n\t<tr><td align=right><b>&nbsp;{$controle_nb_op}&nbsp;*</b></td><td><b>&nbsp;$controle_type_op</b></td>";
				if($_POST["resume"]=="on")
				{
					echo "<td align=right $res_colspan>";
					if($ctrl_heure<>"0" or $ctrl_min<>"0")
					{
						echo "<b>", $ctrl_heure;
						if($ctrl_heure<>"0" or $ctrl_min<>"0") echo ":";
						if($ctrl_min<10) echo "0"; 
						echo $ctrl_min, "</b></td>";
					}
				}
				echo "</tr><tr><td colspan=$colspan>&nbsp;</td></tr>";
			
				
				
			}
		}


		echo "\n\n\t<!-- Affichage des résultats du dossier (calcul des honoraires théoriques), si on l'a demandé -->\n";

		if($_POST["resume"]=="on")
		{
			if($encours=="operations")
			{
				echo "\n\t<tr><td $last_colspan><b>{$doc->lang["afficher_operations_total"]}&nbsp;:</b></td><td align=right><b>$totaltemps";
				if($_POST["entete"]=="on")
				{
					echo " {$doc->lang["general_soit"]} $gainactuel {$_SESSION["optionGen"]["currency"]}</b>";
				}
			}
			if($encours=="encaissements")
			{
				echo "\n\t<tr><td colspan=3><b>{$doc->lang["afficher_operations_total"]} :</td>";
				foreach(array("encaissement", "avfrais", "demande", "transit", "facturesanstemps") as $cont_val)
				{
					$prix=number_format($total["$cont_val"], 2, '.', '\'');
					echo "<td align=right><b>$prix</b></td>";
				}
			}
			echo "</td></tr>";
		}
		echo "\n</table>\n\n<br>\n";
	}
}
$doc->close();
?>
