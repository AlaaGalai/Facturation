<?php
class faillite extends prolawyer
{
	function faillite()
	{
// 		$this->tab_affiche();
		$this->elements=array("jour", "mois", "annee", "creance", "montant", "interets");
		if(is_array($_POST["ajout"]))
		{
			foreach($_POST["ajout"] as $n => $on)
			{
// 				echo $n;
				foreach($this->elements as $element)
				{
// 					$this->tab_affiche($_POST["$element"]);
					$arrA = array_splice($_POST["$element"], 0, $n);
					$arrB = array_splice($_POST["$element"], 0);
					$_POST["$element"][] = NULL;
					$_POST["$element"] = array_merge($_POST["$element"], $arrA);
					array_unshift($arrB, NULL);
					$_POST["$element"] = array_merge($_POST["$element"], $arrB);
// 					$this->tab_affiche($_POST["$element"]);
					unset($_POST["$element"][0]);
					
					
	// 				$this->tab_affiche($arrA);
	// 				$this->tab_affiche($arrB);
// 					$this->tab_affiche($_POST["$element"]);
				}
// 					$this->tab_affiche($_POST["lines"]);
			}
			foreach($_POST["lines"] as $n => $on) $_POST["lines"][$n] = $n;
		}
		if(is_array($_FILES) && is_file($_FILES["monfichier"]["tmp_name"]))
		{
			$serialize  = file_get_contents($_FILES["monfichier"]["tmp_name"]);
			$_POST = unserialize($serialize);
		}
		elseif($_POST["serialize"])
		{
			header("Content-Typw: text/text");
			header("Content-Disposition: attachment; filename=\"serialize.txt\"");
			$serPost = serialize($_POST);
			echo $serPost;
			die();

		}
		$this->futForm = $_REQUEST;
// 		$this->noNewLine=FALSE;
		parent::__construct();
		$this->lastLineNum=0;
		$this->totalCreance=0;
		$this->totalInterets=0;
		if(!$_POST["jour_faillite"]) $_POST["jour_faillite"] = $this->univ_strftime("%d", time());
		if(!$_POST["mois_faillite"]) $_POST["mois_faillite"] = $this->univ_strftime("%m", time());
		if(!$_POST["annee_faillite"]) $_POST["annee_faillite"] = $this->univ_strftime("%Y", time());
		$this->mDate="{$_POST["annee_faillite"]}-{$_POST["mois_faillite"]}-{$_POST["jour_faillite"]}";
		if(is_array($_POST["lines"]))
		{
			foreach($_POST["lines"] as $no)
			{
				$nMoins = $no -1;
// 				echo "<br>voici $no avec ajout à $nMoins {$_POST["ajout"]["$nMoins"]}";
				if(!$_POST["jour"]["$no"] && !$_POST["mois"]["$no"] && !$_POST["annee"]["$no"] && !$_POST["creance"]["$no"] && !$_POST["montant"]["$no"] && !$_POST["interets"]["$no"] && !$_POST["ajout"]["$nMoins"] && !$_POST[""]["$no"])
				{
// 					echo "<br>Pour $no, c'est faux";
					unset ($_POST["lines"]["$no"]);
// 					$this->noNewLine=TRUE;
				}
				$this->lDate["$no"] = "{$_POST["annee"]["$no"]}-{$_POST["mois"]["$no"]}-{$_POST["jour"]["$no"]}";
			}
		}
// 		$this->max=max($_POST["lines"]);
	}
	
	function nLine($date="", $creance="", $montant="", $interets="")
	{
		$isLastLine = False;
		if($date === "lastLine")
		{
			$date = "";
			$isLastLine = True;
		}
		$montant=preg_replace("#,#", ".", $montant);
		$montant=preg_replace("#\'#", "", stripslashes($montant));
// 		echo "<br>montant vaut $montant";
		if($montant) $montant=number_format($montant, 2, ".", "");
		$this->lastLineNum++;
		$n=$this->lastLineNum;
		$tsDate=$this->mtf_date($date);
		$tsFDate=$this->mtf_date($this->mDate);
		$yDate=$this->univ_strftime("%Y", $tsDate);
		$yFDate=$this->univ_strftime("%Y", $tsFDate);
		$dDate=$this->univ_strftime("%j", $tsDate);
		$dFDate=$this->univ_strftime("%j", $tsFDate);
// 		echo "<br>Avant transfo, dDate=$dDate et dFDate=$dFDate. Après, vu que le dernier jour vaut ", $this->univ_strftime("%j", mktime(1, 1, 1, "12", "31", $yDate)), " et que le dernier de la faillite vaut ", $this->univ_strftime("%j", mktime(1, 1, 1, "12", "31", $yFDate));
		if($dDate > 58 && $this->univ_strftime("%j", mktime(1, 1, 1, "12", "31", $yDate)) == 365) $dDate++;
		if($dFDate >58 && $this->univ_strftime("%j", mktime(1, 1, 1, "12", "31", $yFDate)) == 365) $dFDate++;
// 		echo ", dDate=$dDate et dFDate=$dFDate";
		$intDate=$yDate+$dDate/366;
		$intFDate=$yFDate+$dFDate/366;
		$nbAnnees = $intFDate -$intDate;
// 		echo "<br>Le nombre d'années vaut $nbAnnees ({$_POST["interets"]["$n"]}%*{$_POST["montant"]["$n"]}) pour ", $this->univ_strftime("%j", $tsFDate) - $this->univ_strftime("%j", $tsDate), " jours du ", $this->univ_strftime("%j", $tsDate), " au ", $this->univ_strftime("%j", $tsFDate), " ème jour";
		
		
		$checked = $_POST['inclusion'][$n]||$isLastLine ? "checked": "";
		if($nbAnnees && $date)
		{
			$montantInterets=round($interets*$montant/100*$nbAnnees*2, 1)/2;
			if($checked)
			{
				$this->totalInterets += $montantInterets;
				$this->totalCreance += $montant;
			}
			$montantInterets=number_format($montantInterets, 2, ".", "'");
			$montant=number_format($montant, 2, ".", "'");
		}
		
		$onClick="onClick=\"";
		foreach($this->elements as $element) $onClick .= "document.getElementById('$element\[$n\]').value='';";
		$onClick.="\"";
		$checkBox = $_POST["print"] ? "":"<td><input type=checkbox name=\"inclusion[$n]\" $checked></td>";
		$creance = $_POST["print"] ? "<td>$creance</td>":$this->input_texte("creance[$n]<td>", false, $creance, 20);
		$montant = $_POST["print"] ? "<td align=right>$montant</td>":$this->input_texte("montant[$n]<td align=right>", false, $montant);
		$return = "\n".$this->input_hidden("lines[$n]", "", $n)."<tr>$checkBox".$this->split_date("$date<td>", "[$n]").$creance.$montant."<td align=right>".$this->input_texte("interets[$n]", false, $interets, "2")."&nbsp;%</td><td align=right>$montantInterets</td>";
		if($date && !$_POST["print"]) $return .= "<td class=button $onClick>{$this->lang["adresses_resultat_supprimer"]}</td>";
		if($date && !$_POST["print"]) $return .= "<td>{$this->lang["adresses_resultat_ajouter"]}<input type=checkbox name=\"ajout[$n]\"></td>";
// 		if(!$date || $date == "--") $return .= $this->input_hidden("newLine", "", "false");
		return $return;
	}
}

?>
