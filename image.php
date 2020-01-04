<?php
require_once("./inc/autoload.php");
session_start();

// die(machin);



if($_GET["type"] == "captcha")
{
	$fontname=(isset($_GET["fontname"])) ? $_GET["fontname"]:"./specific/fonts/uni2.ttf";
	$I = new captcha('PNG');
	$I->setStringLenght(6);
	$I->setFont("$fontname" , 15);
	$I->setBorderColor(0,0,33);
	$I->setBorderWidth(5);
// 	$I->setBackgroundImage('images/bg.jpg');
	$I->setTextAngle(5);
	$I->setShadow();
	$I->setRoundedCorners(5);
	$I->getImage();
	$_SESSION['captcha-control'] = $I->getRandString();
	die();
}

$img=new ra(false, "guest");

if(isset($_GET["date_cours"])) $_POST["date_cours"]= $_GET["date_cours"];

$_GET["fontname"]=(isset($_GET["fontname"])) ? $_GET["fontname"]:"./specific/fonts/unitr.ttf";


//données de l'image 1

if($_GET["type"] == "triangle")
{
	$fontsize=(isset($_GET["fontsize"])) ? $_GET["fontsize"]:10;
	$fontname=(isset($_GET["fontname"])) ? $_GET["fontname"]:"./specific/fonts/uni2.ttf";
	$nom = stripslashes($img->lang["fichier_soumettre"]);
// 	echo "<br>Avant: '$nom'; après: '";
	$nom=html_entity_decode($nom, ENT_COMPAT, "UTF-8");
	
	$hor=200;
	$vert=95;
	$img->img=imagecreate($hor, $vert);
	$pointsgris=array(0, 20, $hor, 20, round($hor/2), $vert);
	$points=array(6, 22, $hor -6, 22, round($hor/2), $vert -2);
	$img->list_colors["0"]=imagecolorallocate($img->img, 255, 255, 255); //blanc
	$img->list_colors["1"]=imagecolorallocate($img->img, 160, 160, 160); //gris
	$img->list_colors["2"]=imagecolorallocate($img->img, 0, 0, 0); //noir
	{
		if($_SESSION["rdaf_partie"]== "administratif")$_SESSION["rdaf_partie"]=$rdaf->colorAdministratif;
		if($_SESSION["rdaf_partie"]== "fiscal")$_SESSION["rdaf_partie"]=$rdaf->colorFiscal;
		$r=hexdec(substr($_SESSION["rdaf_partie"], 0, 2));
		$g=hexdec(substr($_SESSION["rdaf_partie"], 2, 2));
		$b=hexdec(substr($_SESSION["rdaf_partie"], 4, 2));
		
		$img->list_colors["color"]=imagecolorallocate($img->img, $r, $g, $b);
	}
	
	imagefilledpolygon($img->img, $pointsgris, 3, $img->list_colors["1"]);
	imagefilledpolygon($img->img, $points, 3, $img->list_colors["color"]);
	$datas=imagettfbbox(9, 0, $fontname, $nom);
	$width = $datas[2] - $datas[0];
	$height = $datas[1] - $datas[7];
	$base=round($vert/2);
	$left=(round($hor-$width) /2);
	imagettftext($img->img, $fontsize, 0, $left, $base, $img->list_colors["2"], $fontname, $nom);

}

if($_GET["type"] == "fleche")
{
// 	$_GET["save"] = false;
	$fontsize=(isset($_GET["fontsize"])) ? $_GET["fontsize"]:10;
	$fontname=(isset($_GET["fontname"])) ? $_GET["fontname"]:"./specific/fonts/uni2.ttf";
	$nom = stripslashes($img->lang["fichier_soumettre"]);
	$nom = "clic !";
// 	echo "<br>Avant: '$nom'; après: '";
	$nom=html_entity_decode($nom, ENT_COMPAT, "UTF-8");
	
	$hor=40;
	$vert=50;
	$img->img=imagecreate($hor, $vert);
	$pointsgris=array(0, 20, $hor, 20, round($hor/2), $vert);
	$points=array(0, 0, 0, $vert, $hor, round($vert/2));
	$img->list_colors["1"]=imagecolorallocatealpha($img->img, 160, 160, 160, 127); //gris
	$img->list_colors["0"]=imagecolorallocate($img->img, 255, 255, 255); //blanc
	$img->list_colors["2"]=imagecolorallocate($img->img, 0, 0, 0); //noir
	{
		if($_SESSION["rdaf_partie"]== "administratif")$_SESSION["rdaf_partie"]=$rdaf->colorAdministratif;
		if($_SESSION["rdaf_partie"]== "fiscal")$_SESSION["rdaf_partie"]=$rdaf->colorFiscal;
		$r=hexdec(substr($_SESSION["rdaf_partie"], 0, 2));
		$g=hexdec(substr($_SESSION["rdaf_partie"], 2, 2));
		$b=hexdec(substr($_SESSION["rdaf_partie"], 4, 2));
		
		$img->list_colors["color"]=imagecolorallocate($img->img, $r, $g, $b);
	}
	
	imagefilledpolygon($img->img, $pointsgris, 3, $img->list_colors["1"]);
	imagefilledpolygon($img->img, $points, 3, $img->list_colors["0"]);
	$datas=imagettfbbox(9, 0, $fontname, $nom);
	$width = $datas[2] - $datas[0];
	$height = $datas[1] - $datas[7];
	$base=round($vert/2);
	$left=(round($hor-$width) /2);
	imagettftext($img->img, $fontsize, 0, 2, $base +$fontsize/2, $img->list_colors["2"], $fontname, $nom);

}

if($_GET["type"] == "ver")
{
	$nom = stripslashes($_GET["nom"]);
	$vert=(isset($_GET["vert"])) ? $_GET["vert"]:100;
	$base=$vert -5;
	$img->img=imagecreate(20, $vert);
	$img->list_colors["0"]=imagecolorallocate($img->img, 255, 255, 0); //blanc
	$img->list_colors["1"]=imagecolorallocate($img->img, 255, 0, 0); //rouge
	$img->list_colors["2"]=imagecolorallocate($img->img, 0, 0, 0); //noir
	imagestringup($img->img, 2, 2, $base, $nom, $img->list_colors[2]);

}

if($_GET["type"] == "hor")
{
	$fontsize=(isset($_GET["fontsize"]) && $_GET["fontsize"]) ? $_GET["fontsize"]:20;
	$width=(isset($_GET["width"])) ? $_GET["width"]:false;
	$fType=(isset($_GET["fType"])) ? $_GET["fType"]:"middle";
	$height=(isset($_GET["height"])) ? $_GET["height"]:false;
	$base=(isset($_GET["base"])) ? $_GET["base"]:false;
	$angle=(isset($_GET["angle"])) ? $_GET["angle"]:0;
#	$fontname=(isset($_GET["fontname"])) ? $_GET["fontname"]:"./fonts/Univers_Light_Light.ttf";
	$fontname=(isset($_GET["fontname"])) ? $_GET["fontname"]:"./specific/fonts/uni2.ttf";
// 	echo "<br>$fontname";
	$nom = stripslashes($_GET["nom"]);
	$fname=$img->getImageName($nom, true);
	$nom=html_entity_decode($nom, ENT_COMPAT, "UTF-8");
	if($_GET["up"]) $nom = strtoupper($nom);
 	if(! preg_match("#Ã#", $nom) && ! preg_match("#Â#", $hval)) $nom=utf8_encode($nom);
	$hori=(isset($_GET["hori"])) ? $_GET["hori"]:100;
	$bgcolor=(isset($_GET["bgcolor"])) ? $_GET["bgcolor"]:"ffffff";
	$color=(isset($_GET["color"])) ? $_GET["color"]:"000033";
	
	$datas=imagettfbbox($fontsize, $angle, $fontname, $nom);
	$mComp = imagettfbbox($fontsize, $angle, $fontname, "l");
	$tComp = imagettfbbox($fontsize, $angle, $fontname, "lp");
	
	if(!$width) $width = $datas[2] - $datas[0];
	if(!$height) $height = $datas[1] - $datas[7];
	$mHeight = $mComp[1] - $mComp[7];
	$tHeight = $tComp[1] - $tComp[7];
	
	
	if(isset($_GET["height"])) $img->img=imagecreate($width+8, $height+$_GET["height"]);
	else $img->img=imagecreate($width+8, $tHeight+4);
	foreach(array("bgcolor", "color") as $cTest)
	{
		if($$cTest== "ADMINI")$$cTest=$rdaf->plainAdministratif;
		if($$cTest== "FISCAL")$$cTest=$rdaf->plainFiscal;
		$r=hexdec(substr($$cTest, 0, 2));
		$g=hexdec(substr($$cTest, 2, 2));
		$b=hexdec(substr($$cTest, 4, 2));
		
		$img->list_colors["$cTest"]=imagecolorallocate($img->img, $r, $g, $b);
	}
	$img->list_colors["0"]=imagecolorallocate($img->img, 255, 255, 0); //blanc
	$img->list_colors["1"]=imagecolorallocate($img->img, 255, 0, 0); //rouge
	$img->list_colors["2"]=imagecolorallocate($img->img, 0, 0, 0); //noir
	
	if(!$base) $base = $mHeight+ 3;
	$left=4;
	imagettftext($img->img, $fontsize, 0, $left, $base, $img->list_colors["color"], $fontname, $nom);

	if($_GET["nwidth"] && $_GET["nheight"])
	{
		$new_width = $width * $_GET["nwidth"];
		$new_height = $height * $_GET["nheigth"];
		$img->newimg=$img->img;
		$img->img = imagecreatetruecolor($new_width, $new_height);
		imagecopyresampled($img->img, $img->newimg, 0, 0, 0, 0, $new_width, $new_height, $width, $height);
	}
}

if($_GET["type"] == "1")
{
	//1er graphiques Informations générales
	for($i=1;$i<6;$i++) $img->new_display($i); //INITIALISATION DES 5 TABLEAUX
	
	
	//1. honoraires selon timesheet y.c.tva
	$tab1query="select month({$_SESSION["session_opdb"]}.dateop) as mois, year({$_SESSION["session_opdb"]}.dateop) as annee, sum(time_to_sec({$_SESSION["session_opdb"]}.tempsop) * {$_SESSION["session_avdb"]}.prixhoraire /3600) as 'gain' from {$_SESSION["session_opdb"]}, {$_SESSION["session_avdb"]} where {$_SESSION["session_opdb"]}.dateop <> 0 AND {$_SESSION["session_opdb"]}.dateop
	BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) AND {$_SESSION["session_avdb"]}.nodossier = {$_SESSION["session_opdb"]}.nodossier group by mois";
	$exec_tab_query=mysqli_query($img->mysqli, "$tab1query");
	while($row=mysqli_fetch_array($exec_tab_query))
	{
		$tx_tva=(trim($row["tvadossier"] != ""))? trim($row["tvadossier"]) : $_SESSION["optionGen"]["tx_tva"];
		$tval=$row['annee'].".".$row['mois'];
		$img->display[1]["$tval"]= (100 + $tx_tva) * $row["gain"] / 100;
		$img->legende[1]=$img->lang["ra_hono_ts"];
	}
	
	//2. encaissements effectifs, pas séparé par sous-traitant
	$tab2query="select month({$_SESSION["session_opdb"]}.dateac) as mois, year({$_SESSION["session_opdb"]}.dateac) as annee, sum(encaissement) as encaissements
	from {$_SESSION["session_opdb"]}, {$_SESSION["session_avdb"]} where {$_SESSION["session_opdb"]}.dateac <> 0 AND {$_SESSION["session_opdb"]}.dateac
	BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) AND {$_SESSION["session_avdb"]}.nodossier = {$_SESSION["session_opdb"]}.nodossier group by mois";
	$exec_tab_query=mysqli_query($img->mysqli, "$tab2query");
	while($row=mysqli_fetch_array($exec_tab_query))
	{
		$tval=$row['annee'].".".$row['mois'];
		$img->display[2]["$tval"]= $row["encaissements"];
		$img->legende[2]=$img->lang["ra_hono_enc"];
	}
	
	//3. Taux horaire moyen
	$tab3query="select month({$_SESSION["session_opdb"]}.dateop) as mois, year({$_SESSION["session_opdb"]}.dateop) as annee, sum(time_to_sec({$_SESSION["session_opdb"]}.tempsop) * {$_SESSION["session_avdb"]}.prixhoraire /3600) as 'gain', sum(time_to_sec({$_SESSION["session_opdb"]}.tempsop)) as tempstotal
	from {$_SESSION["session_opdb"]}, {$_SESSION["session_avdb"]} where {$_SESSION["session_opdb"]}.dateop <> 0 AND {$_SESSION["session_opdb"]}.dateop
	BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) AND {$_SESSION["session_avdb"]}.nodossier = {$_SESSION["session_opdb"]}.nodossier group by mois";
	$exec_tab_query=mysqli_query($img->mysqli, "$tab3query");
	while($row=mysqli_fetch_array($exec_tab_query))
	{
		$tval=$row['annee'].".".$row['mois'];
		if($row["tempstotal"])//pas de division par zéro !
		{ 
			$txmoyen=$row["gain"] / $row["tempstotal"] * 360000; // et non 3600, car le tx est multiplié par 100 pour des raisons de lisibilité
		}
		else $txmoyen=1; //et non zéro sinon on risque un problème de calcul de maximum avec une éventuelle division par zéro
		$img->display[3]["$tval"]= $txmoyen;
		$img->legende[3]=$img->lang["ra_tx_moyen"];
	}
	
	//4. Nombre de dossiers ouverts pendant chacun des mois
	$tab4query="select month(dateouverture) as mois, year(dateouverture) as annee, sum(1) as dossiersouverts
	from {$_SESSION["session_avdb"]} where dateouverture
	BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) group by mois";
	$exec_tab_query=mysqli_query($img->mysqli, "$tab4query");
	while($row=mysqli_fetch_array($exec_tab_query))
	{
		$tval=$row['annee'].".".$row['mois'];
		$ouverts=$row["dossiersouverts"] * 1000; // le tx est multiplié par 1000 pour des raisons de lisibilité
		$img->display[4]["$tval"]= $ouverts;
		$img->legende[4]=$img->lang["ra_dossiers"];
	}
	
	//5. Nombre total de dossiers ouverts et pas encore fermés au 15 de chaque mois
	$moiscours = $img->moiscours;
	$anncours = $img->anncours;
	for($i=1;$i<13;$i++)
	{
		$tab5query="select sum(1) as dossiersouverts from {$_SESSION["session_avdb"]} where dateouverture < '{$anncours}-{$moiscours}-15' and (datearchivage > '{$anncours}-{$moiscours}-15' OR datearchivage = '0000-00-00' OR datearchivage = '' OR datearchivage = NULL)";
// 		echo "<br><font color=red>$tab5query</font>";
		$exec_tab_query=mysqli_query($img->mysqli, "$tab5query");
		while($row=mysqli_fetch_array($exec_tab_query))
		{
			$tval=$anncours.".".$moiscours;
			$ouverts=$row["dossiersouverts"] * 100; // le tx est multiplié par 100 pour des raisons de lisibilité
			$img->display[5]["$tval"]= $ouverts;
			$img->legende[5]=$img->lang["ra_dossiers_encours"];
		}
		$moiscours++;
		if($moiscours==13)
		{
			$moiscours=1;
			$anncours++;
		}
		
	}	
}

if($_GET["type"] == "2" || $_GET["type"] == "3" || $_GET["type"] == "2bis" || $_GET["type"] == "3bis")
{
	//2ème graphiques Détails par type de mandat
	
	//Nombre de dossiers ouverts au 15 de chaque mois
	if($_GET["type"] == "2" || $_GET["type"] == "2bis")
	{
		$file_type=explode("\n", $_SESSION["optionGen"]["dossiers_type"]);
		$column="typedossier";
	}
	if($_GET["type"] == "3" || $_GET["type"] == "3bis")
	{
		$file_type=explode("\n", $_SESSION["optionGen"]["matiere_type"]);
		$column="matiere";
	}
	array_push($file_type, "**--**,**--**"); //pour s'assurer qu'il y aura bien une ligne "autres" (soit ceux qui ne sont pas notés)
// 	if($_GET["type"] == "2bis" || $_GET["type"] == "3bis") $disp_number = 1;
	$disp_number=0;
	$exclusion = "";
	$img->new_display("total");
	
	foreach ($file_type as $line) if(trim($line != "") AND trim($line != $img->lang["ra_autres"]))
	{
		$anncours=$img->anncours;
		$moiscours = $img->moiscours;
		
		$disp_number++;
		list($init, $val) = preg_split ("/,/", $line);
		$val=trim($val);
		$typecolumn="$column like '$init'";
		if($val == "**--**")
		{
			$val=$img->lang["ra_autres"];
			$typecolumn = (trim($exclusion != ""))? "($exclusion)":"$column like '%'";
		}
		$img->legende["$disp_number"]=trim($val);
/*		if($_GET["type"] == "2bis" || $_GET["type"] == "3bis")
		{
			$exclusion .= ($disp_number != 2)? " AND ":"";
		}else{*/
			$exclusion .= ($disp_number != 1)? " AND ":"";
// 		}
		$exclusion .= "$column NOT LIKE '$init'";
		$img->new_display($disp_number);
		for($i=1;$i<13;$i++)
		{
			$tab1query="select sum(1) as dossiersouverts from {$_SESSION["session_avdb"]} where (dateouverture < '$anncours-$moiscours-15' and (datearchivage > '$anncours-$moiscours-15' OR datearchivage = '0000-00-00' OR datearchivage = '' OR datearchivage = NULL)) AND $typecolumn";
//   			echo "<br>$tab1query";
			$exec_tab_query=mysqli_query($img->mysqli, "$tab1query");
			while($row=mysqli_fetch_array($exec_tab_query))
			{
				$tval=$anncours.".".$moiscours;
				$ouverts=$row["dossiersouverts"];
				$img->display[$disp_number]["$tval"]= $ouverts;
				$img->display["total"]["$tval"] += $ouverts;
// 				echo " <br>$disp_number ($moiscours.$anncours): soit $ouverts ouverts de type {$row["typedossier"]}";
			}
			$moiscours++;
			if($moiscours==13)
			{
				$moiscours=1;
				$anncours++;
			}
		}
	}

	if($_GET["type"] == "2bis" || $_GET["type"] == "3bis")
	{
		foreach($img->display as $number => $array)
		{
			if($number != "total")
			{
				if($number > $max_display) $max_display = $number;
				foreach ($array as $key => $val) $img->display[$number]["$key"] = ($img->display["total"]["$key"])?$img->display[$number]["$key"] / $img->display["total"]["$key"]:0;
			}
		}
		$max_display ++;
		foreach ($img->display["total"] as $key => $val) $img->display[$max_display]["$key"] = 1;
		$img->legende[$max_display] = "100%";
	}
	unset ($img->display["total"]);
}

 if($_GET["type"] == "4")
{
	$file_type=explode("\n", $_SESSION["optionGen"]["soustraitants"]);
/*	$q="select soustraitant from {$_SESSION["session_opdb"]} group by soustraitant";
	$e=mysqli_query($img->mysqli, $q);
	while($r = mysqli_fetch_array($e)) $file_type[] = $r["soustraitant"];*/
	array_push($file_type, "**--**"); //pour s'assurer qu'il y aura bien une ligne "autres" (soit ceux qui ne sont pas notés)
	$disp_number=0;
	foreach ($file_type as $line) if(trim($line != ""))
	{
		$val=trim($line);
		if(preg_match("/,/", $val)) list($val1,) = preg_split("/,/", $val);
		else $val1 = false;
		if($val1) $val = $val1;
		//echo "<br>$val";
		$typecolumn="soustraitant like '$val'";
		if($val == "**--**")
		{
			$val=$img->lang["ra_autres"];
			$typecolumn = (trim($exclusion != ""))? "($exclusion)":"soustraitant like '%'";
		}
		//vérification que les sous-traitants ont effectivement traité...
		$checkQuery = "select sum(time_to_sec({$_SESSION["session_opdb"]}.tempsop)) as gain from {$_SESSION["session_opdb"]} where {$_SESSION["session_opdb"]}.dateop <> 0 AND {$_SESSION["session_opdb"]}.dateop BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) AND $typecolumn";
		$e = mysqli_query($img->mysqli, $checkQuery);
		while($r = mysqli_fetch_array($e))
		{
			$gain = $r["gain"];
		}
		if(! $gain)
		{
			continue;
		}
		$disp_number++;
		$img->legende["$disp_number"]=trim($val);
		$exclusion .= ($exclusion)? " AND ":"";
		$exclusion .= "soustraitant NOT LIKE '$val'";
		$img->new_display($disp_number);
		
		//honoraires selon timesheet y.c.tva, par sous traitant
		$tab1query="select month({$_SESSION["session_opdb"]}.dateop) as mois, year({$_SESSION["session_opdb"]}.dateop) as annee, sum(time_to_sec({$_SESSION["session_opdb"]}.tempsop) * {$_SESSION["session_avdb"]}.prixhoraire /3600) as 'gain' from {$_SESSION["session_opdb"]}, {$_SESSION["session_avdb"]} where {$_SESSION["session_opdb"]}.dateop <> 0 AND {$_SESSION["session_opdb"]}.dateop
		BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) AND {$_SESSION["session_avdb"]}.nodossier = {$_SESSION["session_opdb"]}.nodossier AND $typecolumn group by mois";
 		//echo "<br>$tab1query";
		$exec_tab_query=mysqli_query($img->mysqli, "$tab1query");
		while($row=mysqli_fetch_array($exec_tab_query))
		{
			$tx_tva=(trim($row["tvadossier"] != ""))? trim($row["tvadossier"]) : $_SESSION["optionGen"]["tx_tva"];
			$tval=$row['annee'].".".$row['mois'];
			$img->display["$disp_number"]["$tval"]= (100 + $tx_tva) * $row["gain"] / 100;
		}
	}
}

 if($_GET["type"] == "5" || $_GET["type"] == "6")
{
	$soustrait = ($_GET["soustraitant"])?$_GET["soustraitant"]:"";
	$disp_number=0;
	$img->new_display(0); //sera le total
	foreach ($img->liste_utilisateurs() as $init => $ar)
	{
		$seul=$ar["seul"];
		$nom =$ar["nom"];
		if(/*trim($ligne) != "" && */trim($seul) != "1")
		{
			$temp_display=array();
			$controle=0;
			$opdb=substr("$init", 0, 2)."op";
			$avdb=substr("$init", 0, 2)."clients";
// 			$tab1query="select month($opdb.dateop) as mois, year($opdb.dateop) as annee, sum(time_to_sec($opdb.tempsop) * $avdb.prixhoraire /3600) as 'gain' from $opdb, $avdb where $opdb.dateop <> 0 AND $opdb.dateop BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) AND $avdb.nodossier = $opdb.nodossier AND soustraitant like '$soustrait' group by mois";
			$tab1query="select month($opdb.dateop) as mois, year($opdb.dateop) as annee, sum(time_to_sec($opdb.tempsop)/3600) as 'heures' from $opdb, $avdb where $opdb.dateop <> 0 AND $opdb.dateop BETWEEN '{$img->datedebutann}' and date_sub('{$img->datefinann}', interval 1 day) AND $avdb.nodossier = $opdb.nodossier AND soustraitant like '$soustrait' group by mois";
//  			echo "<br>$tab1query";
			$exec_tab_query=mysqli_query($img->mysqli, "$tab1query");
			while($row=mysqli_fetch_array($exec_tab_query))
			{
				$tval=$row['annee'].".".$row['mois'];
				$temp_display["$tval"]= $row["heures"];
				$controle +=$row["heures"];
			}
			
// 			$img->tab_affiche($temp_display);
			if($controle >0)
			{
				$disp_number ++;
				$img->new_display($disp_number);
				foreach ($temp_display as $key => $val)
				{
					$img->display["$disp_number"]["$key"] = $val;
					$img->display[0]["$key"] += $val;
				}
				$img->legende["$disp_number"] = "$nom";
			}
		}
	}
	
// 	foreach ($img->display as $number => $array) $img->tab_affiche($img->display["$number"]);
	
	if($_GET["type"] == "5")
	{
		foreach($img->display as $number => $array)
		{
			if($number >0)
			{
				foreach ($array as $key => $val) $img->display[$number]["$key"] = ($img->display[0]["$key"])?$img->display[$number]["$key"] / $img->display[0]["$key"]:0;
			}else{
				$disp_number ++;
				foreach ($array as $key => $val) $img->display[$disp_number]["$key"] = 1;
			}
		}
		$img->legende["$disp_number"] = "100%";
	}
	
	if($_GET["type"] == "6")
	{
		$disp_number ++;
		foreach($img->display[0] as $key => $val) $img->display["$disp_number"]["$key"] = $val;
		$img->legende["$disp_number"] = $img->lang["afficher_operations_total"];
	}

	unset ($img->display[0]);
}

/*$img->tab_affiche($img->display);
$img->tab_affiche($img->legende);*/
// header("Content-type: image/png");
// $disposition="Content-Disposition: attachment; filename=\"img{$_GET["type"]}.png\"";
// header($disposition);
if(in_array($_GET["type"], array("1", "2", "2bis", "3", "3bis", "4", "5", "6" ))) $img->write_graphique($img->display);
else
{
	$nom = stripslashes($_GET["nom"]);
	$fName = $img->getImageName("$nom", TRUE);
	//echo "'$fName'";
	$imgsave=imagepng($img->img, "$fName");
}
$imgoutput=imagepng($img->img);
?>
