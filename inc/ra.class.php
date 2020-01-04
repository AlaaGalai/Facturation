<?php class ra extends calendar
{
	function ra($agendaSolo=false, $connection = True)
	{
		parent::__construct($agendaSolo, $connection);
		
		list($this->anncherche, $this->moischerche, $this->jourcherche) = preg_split("#-#", $_POST["date_cours"]);
		$this->moiscours=$this->moischerche - 11;
		$this->anncours=$this->anncherche;
		if($this->moiscours<1)
		{
			$this->moiscours += 12;
			$this->anncours -= 1;
		}
		$this->annfin = $this->anncours + 1;
		$this->datedebutann="{$this->anncours}-{$this->moiscours}-1"; //on redéfinit la date de début d'année en décalé dans le temps
		$this->datefinann="{$this->annfin}-{$this->moiscours}-1"; //idem
		
// 		setlocale(LC_TIME, $this->getCookie("locale"));
		if(isset($this->display)) unset($this->display);
		if(isset($this->legende)) unset($this->legende);
		$this->plages_interdites = array();


	}
	
	function display_global_result($type, $resume=FALSE)
	{
		if($type == "jour") $time_limit = "OPDB.dateop ='{$_POST["date_cours"]}'";
		else
		{
			//echo "<br>", $_POST["date_cours"];
			list($annee, $mois, $jour) = preg_split("#-#", $_POST["date_cours"]);
			$vannee = $annee -1;
			$debut_an="$annee-1-1";
			$fin_an="$annee-12-31";
			$debut_van="$vannee-1-1";
			$fin_van="$vannee-$mois-$jour";
			$debut_mois="$annee-$mois-1";
			if($mois == "12")
			{
				$nextannee= $annee + 1;
				$nextmois=1;
				$nextma="$nextannee-$nextmois";
			}else{
				$nextmois= $mois + 1;
				$nextma = "$annee-$nextmois";
			}
			$fin_mois="$nextma-1";
			if($type == "mois")
			{
				$time_limit = "(OPDB.dateop >='$debut_mois' AND OPDB.dateop <'$fin_mois')";
			}
			elseif($type == "annee")
			{
				$time_limit = "(OPDB.dateop >='$debut_an' AND OPDB.dateop <'$fin_an')";
			}
			elseif($type == "vannee")
			{
				$time_limit = "(OPDB.dateop >='$debut_van' AND OPDB.dateop <'$fin_van')";
			}
		}
		
		$reqsoustrait = "";
		if($this->liste_soustraitant)
		{
			$reqsoustrait = "AND (";
// 			echo "tralala";
			$liste=explode(",", $this->liste_soustraitant);
			$first=TRUE;
			foreach($liste as $soustrait) if(trim($soustrait) != "")
			{
				if(!$first) $reqsoustrait .= " OR ";
				$reqsoustrait .= "o.soustraitant like '%$soustrait%'";
				$first=FALSE;
			}
			$reqsoustrait .= ")";
			if($first == TRUE) $reqsoustrait = "";
		}
		
		$bases = array();
		
		if($_POST["remuneration"])
		{
			$bases = $this->myBases;
		}
		else
		{
			$bases[] = array($_SESSION["session_avdb"], $_SESSION["session_opdb"], $_SESSION["session_tfdb"]);
		}
		$arr_return = array();
		foreach($bases as $datas)
		{
			$ret = "";
			$avdb = $datas[0];
			$opdb = $datas[1];
			$tfdb = $datas[2];
			//$this->tab_affiche($datas);
			$name = $this->init_to_name(substr($avdb, 0, 2));
			
			if($_POST["remuneration"])
			{
				if(!$resume) $ret .= "<h2><center><a name={$avdb}_details href=\"#{$avdb}\">$name</a></center></h2>";
				elseif ($type == "mois") $ret .= "<h3><a name={$avdb} href=\"#{$avdb}_details\">$name</a></h3>";
				elseif ($type == "annee") $ret .= "<h3>&nbsp;</h3>";
			}
			$actTimeLimit = preg_replace("/OPDB/", "o", $time_limit);
			$query_res="select dateop, tempsop, op, opavec, adresses.nom as 'nom', time_to_sec(o.tempsop) as 'temps', $avdb.prixhoraire, $avdb.tvadossier as 'tva' from $opdb o LEFT OUTER JOIN $avdb on $avdb.nodossier = o.nodossier LEFT OUTER JOIN adresses on adresses.id = $avdb.noadresse where o.dateop <> 0 AND $actTimeLimit $reqsoustrait order by dateop, o.nodossier";
			$query_res="select o.nodossier as nodossier, dateop, tempsop, op, opavec, adresses.nom as 'nom', time_to_sec(o.tempsop) as 'temps', if(t.prixhoraire IS NULL, $avdb.prixhoraire, t.prixhoraire)as prixhoraire, $avdb.tvadossier as 'tva', if(o.forfait = '0.00', if(t.prixhoraire IS NULL, $avdb.prixhoraire, t.prixhoraire) * time_to_sec(o.tempsop), o.forfait * 3600) as prixop, o.forfait as isforfait from $opdb o LEFT OUTER JOIN $avdb on $avdb.nodossier = o.nodossier LEFT OUTER JOIN adresses on adresses.id = $avdb.noadresse LEFT OUTER JOIN $tfdb t on o.nodossier = t.nodossier and o.soustraitant = t.soustraitant where o.dateop <> 0 AND $actTimeLimit $reqsoustrait order by dateop, o.nodossier";
  	  		//$ret .= "<br>$query_res<br>";
			//echo "'<br><br>$query_res'";
			$resultat_mensuel=mysqli_query($this->mysqli, "$query_res");
			if(!$resultat_mensuel) continue;
			$fric=0;
			$frictva=0;
			$tempstotalsec=0;
			$heures = NULL;
			$minutes = NULL;
			$tempstotal = NULL;
			$gain = 0;

			$ratemps="ra_temps" . $type;
			$rafric="ra_fric" . $type;
			$radet="ra_det" . $type;
			if(!$resume)
			{
				$ret .= "\n<br><center><b>{$this->lang["$radet"]}&nbsp;:</b></center>";
				$ret .= "\n".$this->table_open("border=1 align=center bgcolor=ffffff") ."<tr bgcolor=eeffff><th>{$this->lang["ra_date"]}</th><th>{$this->lang["ra_activ"]}</th><th>{$this->lang["ra_activdet"]}</th><th>{$this->lang["ra_tempsindividuel"]}</th><th>{$this->lang["ra_temps"]}</th><th>{$this->lang["ra_prix"]}</th><th>{$this->lang["ra_voirdossier"]}</th></tr>";
			}
			//echo "<br>'$resultat_mensuel'";
			$lastDate = 0;
			$sumFric = 0;
			$sumTemps = 0;
			while($row=mysqli_fetch_array($resultat_mensuel))
			{
				$prixEffectif = $row["prixop"]/3600;
				$prixseconde=$row["prixhoraire"]/3600;
				$fric += ($prixEffectif);
				$frictva += $prixEffectif * (100 + $row["tva"]) / 100;
				$sumTemps += $row["temps"];
// 				print "<br>$fric $frictva {$row["tva"]}";
				$gain=round($fric*20)/20;
				$gain_affiche=number_format(round(($prixEffectif)*20)/20, 2, '.', '\'');
				$tempstotalsec += $row["temps"];
				$tempstotalmin = $tempstotalsec / 60;
				$heures=floor($tempstotalmin/60);
				$minutes = $tempstotalmin - (60 * $heures);
				$tempstotal=date("G\hi",mktime(0,0,$tempstotalsec,1,1,1));
				if(!$resume)
				{
                                        $tr = $row["temps"];
                                        $hr = ($tr - ($tr % 3600))/3600;
                                        $mr = (($tr % 3600) - ($tr % 3600)  % 60) /60;
                                        $mr = ($mr < 10) ? "0".$mr:$mr;
                                        $sr = $tr - $hr * 3600 - $mr * 60;
					$jourmysql=$row["dateop"];
					$jour=implode('.',array_reverse(explode('-',"$jourmysql")));
					if($jour != $lastDate)
					{
						if($lastDate != 0)
						{
							$affSumFric = number_format(round(($sumFric)*20)/20, 2, '.', '\'');
							//$affSumFric = $sumFric;
							$sumFric = 0;
							$sumTemps = 0;
							$ret .= "<tr><td></td><td></td><td></td><td></td><td align=right>$affSumFric</td><td></td><td></td></tr>";
						}
						$lastDate = $jour;
						$jour = "<b>$jour</b>";
					}
					$sumFric += round(($prixEffectif* (100 + $row["tva"]) /100)*20)/20;
					$ret .= "<tr><td>$jour";
					$ret .= "</td><td>";
					$ret .= $row["op"]; 
					$ret .= "</td>";
                                        $ret .= "<td>{$row["opavec"]}</td>";
                                        $ret .= "<td><center>{$hr}h{$mr}</center></td>";
                                        $ret .= "<td><center>";
					$ret .= $row["tempsfact"]; 
					$ret .= $gain_affiche;
					$ret .= "</center></td><td><center>";
					$ret .= $row["isforfait"] == '0.00' ? number_format($row["prixhoraire"], 2):"<i>{$this->lang["operations_forfait"]}</i>"; 
					$ret .= "</center></td><td>";
					$ret .= $this->form("operations.php", "{$row["nom"]} # {$row["nodossier"]}", "", "", "", "nodossier", "{$row["nodossier"]}", "new_av", substr($avdb, 0, 2), "secteur", "operations");
				}
			}
			$actTimeEncLimit = preg_replace("#dateop#", "dateac", $actTimeLimit);
			$qEnc = "select SUM(encaissement) + SUM(provision) as total_resultat from $opdb o where $actTimeEncLimit";
			//echo "<br>$type: '$qEnc'";
			$e = mysqli_query($this->mysqli, $qEnc);
			while($r = mysqli_fetch_array($e)) $gainEffectif = $r["total_resultat"];
			if(!$resume)
			{
				$ret .= $this->table_close();
			}else{
				if ($tempstotal <> NULL)
				{
					$gain_tva=round($frictva*20)/20;
					$gain_affiche=number_format($gain, 2, '.', '\'');
					$gain_tva_affiche=number_format($gain_tva, 2, '.', '\'');
					$gainTotal += $gain;
					$gainTotalTVA += $gain_tva;
					$tempsTotalResume += $tempstotalsec;
					$ret .= "\n{$this->lang["$ratemps"]}&nbsp;: <b>$heures h $minutes";
					$ret .= "</b>\n<br>{$this->lang["$rafric"]}&nbsp;: <b>$gain_affiche</b> {$_SESSION["optionGen"]["currency"]}\n<br>(<b>$gain_tva_affiche</b> {$this->lang["ra_avec_tva"]})";
				}else {
					$rien="ra_rien_" . $type;
					$ret .= $this->lang["$rien"];
				}
				if(!$_POST["remuneration"])
				{
					$encaisse_affiche=number_format($gainEffectif, 2, '.', '\'');
					$ret .= "<br>{$this->lang["ra_encaisse"]}: $encaisse_affiche";
				}
			}
			if($resume && $type != "jour") $arr_return[] = $ret;
			else echo $ret;
		}
		if($resume)
		{
			if($_POST["remuneration"]) return $arr_return;
			$ret = "";
			$gainTotalAffiche=number_format($gainTotal, 2, '.', '\'');
			$gainTotalTVAAffiche=number_format($gainTotalTVA, 2, '.', '\'');
			$tempsTotalResume /= 60;
                        $heuresTotalesResume = $tempsTotalResume / 60;
                        $tauxHoraireMoyen = ($gainTotal) ? number_format($gainTotal / $heuresTotalesResume, 2, '.', '\'') : "";
			$heures=floor($tempsTotalResume/60);
			$minutes = $tempsTotalResume - (60 * $heures);
			$tempsTotalAffiche= "$heures h $minutes";
			$ret .= "<br><b>{$this->lang["ra_total_avec_tva"]}&nbsp;: $gainTotalTVAAffiche {$_SESSION["optionGen"]["currency"]}</b>";
			$ret .= "<br><b>{$this->lang["ra_total_sans_tva"]}&nbsp;: $gainTotalAffiche {$_SESSION["optionGen"]["currency"]}</b>";
			$ret .= "<br><b>{$this->lang["ra_total_temps"]}&nbsp;: $tempsTotalAffiche</b>";
			$ret .= "<br><b>{$this->lang["ra_taux_moyen"]}&nbsp;: $tauxHoraireMoyen {$_SESSION["optionGen"]["currency"]}</b>";
			$arr_return[] = $ret;
			return $arr_return;
		}
// 		else echo $ret;

	}
	
	
	//fonctions de graphiques
	
	function new_display($nd, $val=0) //cration des tableaux à afficher. Ils sont vides mais contiennent toutes les entrées et seront complétés au besoin par la fonction suivante.
	{
		if(!isset($this->display)) $this->display = array();
		$moiscours=$this->moiscours;
		$anncours=$this->anncours;
		for($x=1;$x<13;$x++)
		{
			$this->display[$nd]["$anncours.$moiscours"] = $val;
// 			$this->plages_interdites[$nd]["$anncours.$moiscours"] = 0;
			$moiscours++;
			if($moiscours>12)
			{
				$moiscours -= 12;
				$anncours += 1;
			}
		}
	}
	
	function calcule_max()
	{
		$this->max=0;
		foreach($this->display as $tableau)
		{
			if (max($tableau)>$this->max) $this->max=max($tableau);
		}
//  		echo "<br>le maximum vaut ", $this->max;
// 		return $max;
	}
	
	function nouvelle_image($hor=0, $vertic=0)
	{
		$this->num=count($this->display[1]); //la légende n'est que celle du premier tableau, on part donc de l'idée que le nombre d'élément est constant.
		$this->vertic=($vertic)?$vertic:500;
		$this->hor=($hor)?$hor:500;
		$this->vertic_margin=round($this->vertic/10);
		$this->vertic_disp=$this->vertic-3*$this->vertic_margin;
		$this->marge_legende = 90;
		$this->xorig=$this->hor/($this->num + 1);
		$this->yorig=$this->vertic - $this->marge_legende; //il faut une marge fixe en bas pour la légende
		if($this->max) $this->ratio=$this->vertic_disp/$this->max;
		else $this->ratio=1;
//  		echo "le ratio vaut ", $this->ratio;
		$this->img=imagecreate($this->hor, $this->vertic);
	}
	
	function couleurs_image()
	{
		$this->list_colors["0"]=imagecolorallocate($this->img, 255, 255, 255); //blanc
		$this->list_colors["1"]=imagecolorallocate($this->img, 255, 0, 0); //rouge
		$this->list_colors["2"]=imagecolorallocate($this->img, 0, 0, 0); //noir
		$this->list_colors["3"]=imagecolorallocate($this->img, 0, 255, 0); //vert
		$this->list_colors["4"]=imagecolorallocate($this->img, 0, 0, 255); //bleu
		$this->list_colors["5"]=imagecolorallocate($this->img, 255, 0, 255); //magenta
		$this->list_colors["6"]=imagecolorallocate($this->img, 220, 220, 0); //jaune
		$this->list_colors["7"]=imagecolorallocate($this->img, 0, 255, 255); //cyan
		$this->list_colors["8"]=imagecolorallocate($this->img, 127, 127, 127); //gris
		$this->list_colors["9"]=imagecolorallocate($this->img, 127, 0, 0); //marron
		$this->list_colors["10"]=imagecolorallocate($this->img, 127, 0, 127); //violet
		$this->list_colors["11"]=imagecolorallocate($this->img, 0, 127, 127); //???
		$this->list_colors["12"]=imagecolorallocate($this->img, 0, 0, 127); //???
		$this->list_colors["13"]=imagecolorallocate($this->img, 127, 127, 0); //???
		$this->list_colors["14"]=imagecolorallocate($this->img, 127, 0, 0); //???
		$this->list_colors["15"]=imagecolorallocate($this->img, 255, 165, 0); //orange
// 		return $list_colors;
	}
	
	function axes()
	{
		//cadre de l'image Rappel: x=coordonnées horizontales, y=coordonnées verticales
		imageline($this->img, 0, 0, $this->hor -1, 0, $this->list_colors["2"]);
		imageline($this->img, 0, $this->vertic -1, $this->hor -1, $this->vertic -1, $this->list_colors["2"]);
		imageline($this->img, 0, 0, 0, $this->vertic -1, $this->list_colors["2"]);
		imageline($this->img, $this->hor -1, 0, $this->hor -1, $this->vertic -1, $this->list_colors["2"]);
		
		
		//test du nombre de chiffres de max
		$sci=$this->scientifique($this->max);
		foreach(array(1.1 => 0.1, 2.1 => 0.2, 3.1 => 0.25, 5.1 => 0.5, 10 => 1) as $temptest => $tempmax)
		{
			if($sci["mantisse"] < $temptest)
			{
				$this->ecart_num=$tempmax * pow(10, $sci["exposant"]);
				$this->maxtest=$temptest * pow(10, $sci["exposant"]);
				$this->ecart=round($this->ecart_num*$this->ratio);
				break;
			}
		}
		
		//axes
		$this->xend=$this->num * $this->hor/($this->num + 1);
		$this->xmax=$this->num*$this->hor/($this->num + 1);
		$this->ymax=$this->vertic_margin;
		
		imageline($this->img, $this->xorig, $this->yorig, $this->xmax, $this->yorig, $this->list_colors["3"]); // axe horizontal
		imageline($this->img, $this->xorig, $this->yorig, $this->xorig, $this->ymax, $this->list_colors["3"]); // axe vertical
		
		//écriture des écarts
		$this->ydep=$this->yorig;
		$this->ytest=0;
		$this->xarr=$this->xorig - 2;
		
		if($this->ecart_num) // pas de boucle perpétuelle...
		{
			for($m=1;$m<20;$m++) // ... et il existe en tout cas une sortie de boucle après 20 éléments (ce qui ne devrait pas arriver)!
			{
				if($this->ydep < $this->ymax) break;
//  				echo "<br>, $this->img, $this->xorig, $this->ydep, $this->xarr, $this->ydep, {$this->list_colors["2"]}";
				imageline($this->img, $this->xorig, $this->ydep, $this->xarr, $this->ydep, $this->list_colors[2]);
				if($m>1) imageline($this->img, $this->xorig, $this->ydep, $this->xend, $this->ydep, $this->list_colors[8]);
				imagestring($this->img, 1, $this->xorig -30, $this->ydep, $this->ytest, $this->list_colors[2]);
				$this->ytest=$this->ytest+$this->ecart_num;
				$this->ydep=$this->ydep-$this->ecart;
			}
		}
	}
	
	function graphique($disp_num) //fonction pour créer une courbe graphique
	{
		$color_code=$disp_num;
		$trans=get_html_translation_table(HTML_ENTITIES);
		$trans = array_flip($trans);
		$legende=strtr($this->legende["$color_code"], $trans);
		$legende_totale=$this->anncherche;
		if($this->moischerche!=12) $legende_totale="{$this->anncours} - {$this->anncherche}";
		$xdep=0;
		$ydep=0;
		$enreg=1;
		foreach($this->display["$disp_num"] as $nom => $val)
		{
			$nom=substr($nom, 5, 2);
			$nom=$this->univ_strftime("%b", mktime(1,1,1,$nom, 1, 2004));
			$nom = $this->smart_utf8($nom);
			$xarr=round($enreg*$this->hor/($this->num + 1));
			$yarr=round(($this->vertic - $this->ratio*$val - 90)); //marge fixe pour la légende
			for($k=1;$k<10;$k++)
			{
				if(is_array($this->plages_interdites["$nom"]))
				{
					if(in_array($yarr, $this->plages_interdites["$nom"])) $yarr--;
					else break;
				}
				elseif($yarr == 0) $yarr ++;
				else break;
			}
			$this->plages_interdites["$nom"][] = $yarr;
			
			if($enreg>1)
			{
				imageline($this->img, $xdep, $ydep, $xarr, $yarr, $this->list_colors["$color_code"]); // ligne de courbe, en couleur
				
				//on crée aussi la légende horizontale, toujours en noir
				imagestring($this->img, 2, $xdep, $this->yorig + 21, "$nomtemp", $this->list_colors["2"]); //légende, en noir
				imageline($this->img, $xarr, $this->yorig, $xarr, $this->yorig + 5, $this->list_colors["2"]); //tirets verticaux pour la légende horizontale, en noir
			}
			else{
				$xleg=$xarr;
			}
			$enreg++;
			$xdep=$xarr;
			$ydep=$yarr;
			$nomtemp="$nom";
		}
		//comme la légende a toujours un temps de retard, on écrit la dernière
		imagestring($this->img, 2, $xdep, $this->yorig + 21, "$nomtemp", $this->list_colors["2"]); //légende, en noir
		
		imagestring($this->img, 3, $this->hor/2, $this->yorig + 10, "$legende_totale", $this->list_colors["2"]); //légende générale, en noir
		
		//on écrit enfin la légende (code couleur)
		$corrx=0;
		$corry=0;
		if($disp_num<6) $disp_num = 6 - $disp_num;
		elseif($disp_num<11) $disp_num = 16 - $disp_num;
		elseif($disp_num<16) $disp_num = 26 - $disp_num;
		if($disp_num > 5 && $disp_num <11)
		{
			$corrx=$this->hor/3;
			$corry=50;
		}
		elseif($disp_num > 10)
		{
			$corrx=2*$this->hor/3;
			$corry=100;
		}
		imageline($this->img, $xleg + $corrx, $this->vertic - $disp_num * 10 + $corry, $xleg + 30 + $corrx, $this->vertic - $disp_num * 10 + $corry, $this->list_colors["$color_code"]);
		imagestring($this->img, 2, $xleg + 40 + $corrx, $this->vertic - $disp_num * 10 - 7 + $corry, "$legende", $this->list_colors["$color_code"]);
	}
	
	function write_graphique($legende)
	{
		$this->calcule_max();
		$this->nouvelle_image();
		$this->couleurs_image();
		$this->axes();
		foreach($this->display as $num => $val) $this->graphique($num);
	}
	
	function send_image($n, $soustrait="")
	{
		echo "<img src=\"./image.php?type=$n&liste_soustraitant={$this->liste_soustraitant}&date_cours={$_POST["date_cours"]}&soustraitant=$soustrait\">";
	}

}
?>
