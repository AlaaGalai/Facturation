<?php
class biblio extends prolawyer
{
	function __construct()
	{
		parent::__construct();
		error_reporting(7);

		$this->noFooterAccesskey = True;
		$this->accesPlus = "$";
		$this->accesMoins = "&agrave;";

		$this->titleAddons[] = "<script type=\"text/javascript\" src=\"{$this->settings["root"]}js/biblio.js\"></script>";

		#gestion de la collection
		
		if(isset($_POST["setBiblioType"])) $_SESSION["biblioType"] = $_POST["setBiblioType"];
		if(isset($_POST["setBiblioNom"])) $_SESSION["biblioNom"] = $_POST["setBiblioNom"];
		
		if(!isset($_SESSION["biblioNom"]))
		{
			#Défaut
			$this->biblios = explode("\n", $_SESSION["optionGen"]["bibliotheques"]);
			foreach($this->biblios as $biblio) if(trim($biblio) != "")
			{
				list($nom, $type) = preg_split("/,/", $biblio);
				$_SESSION["biblioNom"] = $nom;
				$_SESSION["biblioType"] = $type;
				break; // on ne veut que la première
			}
		
		}
	}
	
	function liste_old()
	{
		$liste=file("./liste_auteurs.txt");
		natcasesort($liste);
		$this->liste_auteurs="<option value=>";
		foreach($liste as $aut_tri)
		{
			$aut_tri=stripslashes($aut_tri);
			$this->liste_auteurs=$this->liste_auteurs."\n<option value=\"$aut_tri\">$aut_tri";
		}
		return $this->liste_auteurs;
	}
	function liste()
	{
		$autArr = array();
		$k = 0;
		$q = "select auteur1, auteur2, auteur3, auteur4, auteur5, auteur6 from biblio";
// 		echo "<br>'$q'";
		$e = mysqli_query($this->mysqli, $q);
		while ($r = mysqli_fetch_array($e))
		{
			foreach($r as $a => $b) if($b)
			{
				if(trim($b) != "" && !in_array(trim($b), $autArr)) $autArr[] = trim($b);
				$k ++;
			}
		}
		//array_flip($autArr);
		natcasesort($autArr);
		//print_r($autArr);
		//die();
// 		$liste=file("./liste_auteurs.txt");
// 		natcasesort($liste);
		$this->liste_auteurs="<option value=\"\">";
		foreach($autArr as $aut_tri)
		{
			$aut_tri=stripslashes($aut_tri);
			$this->liste_auteurs=$this->liste_auteurs."\n<option value=\"$aut_tri\">$aut_tri";
		}
		return $this->liste_auteurs;
	}

	//fonction pour obtenir la liste des catégories
	function categories($domaine="")
	{
		$i=0;
		$select=file("./liste_categories.txt");
		$liste_categ="<tr><td style=\"font-size:12\">";
		foreach($select as $cat)
		{
			$cat=stripslashes(trim($cat));
			//echo $cat, "<br>";
			if($cat<>"-")
			{
				$liste_categ=$liste_categ."
				<input type=checkbox name=\"check".trim($cat)."\"";
				if($domaine AND preg_match("#(^|,)$cat($|,)#", $domaine)) $liste_categ=$liste_categ." checked";
				$liste_categ=$liste_categ.">".$cat."<br>";
			}
			else $liste_categ=$liste_categ."<br>";
			$i++;
			if($i==20)
			{
				$liste_categ=$liste_categ."</td><td style=\"font-size:12\">";
				$i=0;
			}
		}
		$liste_categ=$liste_categ."</td></tr>";
		return $liste_categ;
	}

	//fonction pour ajouter un auteur à la liste existante
	function ajoute_auteur($nouveau)
	{
		$i=0;
		$select=file("./liste_auteurs.txt");
		foreach($select as $aut)
		{
			$aut=trim($aut);
			$liste[$i]=$aut;
			$i++;
		}
		$i++;
		$liste[$i]=$nouveau;

		asort($liste);
		foreach($liste as $aut_tri)
		{
			$ajout=trim($aut_tri);
			if($ajout<>"") $liste_nouvelle=$liste_nouvelle.$ajout."\n";
		}
		$file=fopen("./liste_auteurs.txt", "w+");
		fwrite($file, "$liste_nouvelle");
		//echo "c'est fait";
	}


	//fonction pour générer la liste déroulante des auteurs, y compris celui en cours
	function auteur($nom = "autoName")
	{
		if(!isset($this->actAuteur)) $this->actAuteur = 1;
		else $this->actAuteur ++;
		$affiche="Auteur {$this->actAuteur}";
		if($nom == "autoName") $nom = $_REQUEST["auteur{$this->actAuteur}"];
		$nom = trim($nom);
		if($_SESSION["biblioType"]=="1")
		{
			echo "<br><b>$affiche</b>&nbsp;\n<select name=\"auteur{$this->actAuteur}\">\n";
			echo "<option value=\"$nom\" SELECTED>$nom</option>\n";
			echo $this->liste_auteurs;
			echo "</select> ou nouvel auteur: <input size=10 name=\"auteur{$this->actAuteur}bis\">\n";
		}
		else
		{
			echo "<br><b>".$affiche."&nbsp;</b><input name=\"auteur{$this->actAuteur}\" value=\"$nom\">\n";
		
		}
	}
}
?>
