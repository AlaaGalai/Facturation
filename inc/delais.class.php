<?php
class delais extends prolawyer
{
	function delais()
	{
		parent::__construct();
		if(is_array($_POST["noLine"])) foreach($_POST["noLine"] as $of=>$val)
		{
			$this->iD["$of"] = "{$_POST["annee"]["$of"]}-{$_POST["mois"]["$of"]}-{$_POST["jour"]["$of"]}";
		}
// 		$this->tab_affiche();
	}
	
	function listDelais()
	{
		$liste  = "\n<form method=\"POST\" action=\"./delais.php\" name=\"choose\" id=\"choose\">";
		$liste .= "\n<select name=\"delaisName\" onChange=\"document.getElementById('choose').submit()\">";
		$dir=opendir("./dls");
		while($file = readdir($dir))
		{
			if(preg_match("#rules#", $file))
			{
				list($basename) = explode(".", $file);
				list($country, $filename) = preg_split("#_#", $basename);
				//echo "<br>a$basename";
				require("./dls/$basename.php");
				$titleName = $doc->titleName["fr"];
				$dlName = isset($this->titleName["{$_SESSION["lang"]}"]) ? $this->titleName["{$_SESSION["lang"]}"]: $this->titleName["fr"];
				$selected = $basename == $_POST["delaisName"] ? "selected":"";
				$liste .= "\n<option value=\"$basename\" $selected>$titleName</option>";
			}
		}
		$liste .= "\n</select>";
		$liste .= "\n</form>";
		return $liste;
	}
	
	function getTitle()
	{
		$lang=$_SESSION["lang"];
		$var = $this->title_variable;
		$this->lang["$var"]=(isset($this->titleName["$lang"]))? $this->titleName["$lang"]: $this->titleName["fr"];
	}
	
	function makeSel($no)
	{
// 		$this->tab_affiche();
		$liste  = "\n<select name=liste[$no]>";
		foreach(array("jours" =>"d", "mois" => "m", "semaines" => "w", "annees" => "y") as $val => $abbrev)
		{
			$affiche=$this->lang["general_$val"];
			$selected = $_POST["liste"]["$no"] == $abbrev ? "selected":"";
			$liste .= "\n<option value=\"$abbrev\" $selected>$affiche</option>";
		}
		$liste .= "\n</select>";
		return $liste;
	}
	
	function dlSplitDate()
	{
		$return=array();
		if(is_array($this->iD)) foreach($this->iD as $num => $val)
		{
// 			echo "toto";
			list($y,$m, $d) = preg_split("#-#", $val);
			foreach(array("y" => $y, "m" => $m, "d" => $d) as $num2 =>$val2) $return[$num][$num2]=$val2;
		}
		$this->aSD = $return;
// 		$this->tab_affiche($this->aSD);
	}
	
	function getRules()
	{
// 		$this->tab_affiche($this->manDateName);
		$this->dlSplitDate();
		$rules = file("./dls/{$_POST["delaisName"]}.rules.php");
		foreach($rules as $line)
		{
			if(trim($line) != "")
			{
				$line=trim($line);
				if(substr($line, -1, 1) == ";") $line = substr($line, 0, -1);
				list($no, $rule) = preg_split("#=#", $line);
				$date		= substr($rule, 0, 1);
				$operande	= substr($rule, 1, 1);
				$nb		= substr($rule, 2, -1);
				$periode	= substr($rule, -1, 1);
				$cNb		= $date -1;
				$cNo		= $no -1;
				$nomDate	= addslashes(stripslashes($this->manDateName["$cNb"]));
				if($periode == "m") $nomPeriode = "mois";
				if($periode == "d" || $periode == "j") $nomPeriode = "jours";
				if($periode == "y" || $periode == "a") $nomPeriode = "annees";
				if($periode == "w" || $periode == "s") $nomPeriode = "semaines";
				$langVar	= "general_".$nomPeriode;
				$langVar	= $this->lang["$langVar"];
// 				echo "<br>$langVar, '$cNo'";
				if($operande == "") $this->aVD["$cNo"] = $this->iD["$cNb"];
				else $this->aVD["$cNo"] = $this->operation($cNb, $date, $operande, $nb, $periode);
				$this->methodeCalcul["$cNo"] = trim("$nomDate $operande $nb $langVar");
			}
		}
	}
	
	function operation($cNb, $date, $operande, $nb, $periode)
	{
// 		$this->tab_affiche($this->aSD["$cNb"]);
		if(!is_array($this->aSD["$cNb"])) return;
		else
		{
			foreach($this->aSD["$cNb"] as $per => $v) if(!$v) return;
		}
		if($nb == "n") $nb = $_POST["nbJours"][$cNb];
		
		if($periode == "k") $periode = $_POST["liste"][$cNb];
		//Check date
		list($oY, $oM, $oD) = preg_split("#-#", $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"], $this->aSD["$cNb"]["d"], $this->aSD["$cNb"]["y"])));
		if(($oY - $this->aSD["$cNb"]["y"]) != 0 ||($oM - $this->aSD["$cNb"]["m"]) != 0 ||($oD - $this->aSD["$cNb"]["d"]) != 0)
		{
			echo "<br><span class='attention_bg'>INVALID !!! ($oD.$oM.$oY)</span>";
			return;
		}
		
		if($operande == "+")
		{
			
			if(strtolower($periode) == "m")
			{
				$return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"] + $nb, $this->aSD["$cNb"]["d"], $this->aSD["$cNb"]["y"]));
				$control = ($this->aSD["$cNb"]["m"] + $nb)%12;
				if($control == 0) $control = 12;
				//echo "<div>$return ($control)</div>";
				list($nY, $nM, $nD) = preg_split("#-#", $return);
				if($nM != $control) $return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $nM, 0, $nY));

			}
			if(strtolower($periode) == "d" || strtolower($periode) == "j")
			{
				$return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"], $this->aSD["$cNb"]["d"] + $nb, $this->aSD["$cNb"]["y"]));
// 				echo "<br>$return<br>";
			}
			if(strtolower($periode) == "w" || strtolower($periode) == "s")
			{
				$return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"], $this->aSD["$cNb"]["d"] + 7* $nb, $this->aSD["$cNb"]["y"]));
// 				echo "<br>$return<br>";
			}
			if(strtolower($periode) == "y" || strtolower($periode) == "a")
			{
				$return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"], $this->aSD["$cNb"]["d"], $this->aSD["$cNb"]["y"] + $nb));
				list($nY, $nM, $nD) = preg_split("#-#", $return);
				if($nM + 0 != $this->aSD["$cNb"]["m"] + 0) $return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $nM, 0, $nY));
			}
			

		}
		if($operande == "-")
		{
			if(strtolower($periode) == "m")
			{
				$return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"] - $nb, $this->aSD["$cNb"]["d"], $this->aSD["$cNb"]["y"]));
			}
			if(strtolower($periode) == "d" || strtolower($periode) == "j")
			{
				$return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"], $this->aSD["$cNb"]["d"] - $nb, $this->aSD["$cNb"]["y"]));
				echo "<br>$return<br>";
			}
			if(strtolower($periode) == "y")
			{
				$return = $this->univ_strftime("%Y-%m-%d", mktime(1, 1, 1, $this->aSD["$cNb"]["m"], $this->aSD["$cNb"]["d"], $this->aSD["$cNb"]["y"] - $nb));
			}
		}
		return $return;
	}
	
	function writePage()
	{
// 		$this->tab_affiche();
		echo $this->listDelais();
		foreach($this->manDate["fr"] as $of =>$val) $this->manDateName["$of"]="*$val*";
		if(is_array($this->manDate["{$_SESSION["lang"]}"])) foreach($this->manDate["{$_SESSION["lang"]}"] as $of =>$val) $this->manDateName["$of"]="$val";
		echo $this->writeInput();
		foreach($this->autoDate["fr"] as $of =>$val) $this->autoDateName["$of"]="*$val*";
		if(is_array($this->autoDate["{$_SESSION["lang"]}"])) foreach($this->autoDate["{$_SESSION["lang"]}"] as $of =>$val) $this->autoDateName["$of"]="$val";
		$this->getRules();
		echo "<br><hr><br>";
		echo $this->writeDatas();
	}
	
	function writeInput()
	{
// 		$this->tab_affiche();
		$return  = "\n<form action=\"./delais.php\" method=\"POST\">";
		$return .= $this->input_hidden("delaisName", 1);
		$return .= $this->table_open();
		foreach($this->manDateName as $of => $val)
		{
			$prec = $_POST["delaisName"] == "ch_generique" ? "<td>+</td>".$this->input_texte("nbJours[$of]<td>",1,"", 2)."<td>".$this->makeSel($of)."</td>":"";
			$return .= "\n<tr><td>$val&nbsp;:</td>".$this->split_date("{$this->iD["$of"]}<td>", "[$of]")."$prec</tr>";
			$return .= $this->input_hidden("noLine[$of]", "", $of);
		}
		$return .= $this->button("<td>");
		$return .= $this->table_close();
		$return .= "</form>";
		return $return;
	}
	
	function writeDatas()
	{
		$_POST["print"] = "on";
		$return  = $this->table_open();
		foreach($this->autoDateName as $of => $val)
		{
			$methodeCalcul = $this->methodeCalcul["$of"];
// 			$val = $this->univ_strftime("%d.%m.%Y", $this->mtf_date($val));
			$return .= "\n<tr onMouseover=\"show('$methodeCalcul')\" onMouseout=\"hide()\"><td>$val&nbsp;:</td>".$this->split_date("{$this->aVD["$of"]}<td align=right>")."</tr>";
		}
		$return .= $this->table_close();
		return $return;
	}
}
?>
