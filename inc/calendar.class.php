<?php class calendar extends prolawyer
/****
* Titre........... : Classe Agenda
* Description..... : Ensemble de fonctions pour la mise en place d'un calendrier / todo list
* version......... : 3.0
* date............ : 30.6.2005
* fichier......... : document.class.php
* Auteur.......... : Olivier Subilia (etudeav@users.sourceforge.net)
*
* remarques ...... : Cette classe s'appuie sur les classes functions.class.php et document.class.php, qui doivent avoir été requises avant l'ouverture de la présente. Mais elle peut être utilisée sans le cadre proposé par la classe Etude (document.class.php) pourvu que les fonctions et les fichiers de configuration créés automatiquement par cette classe soient accessibles.
calendar.class.php va rechercher dans le répertoire de configuration un fichier avocats.php dont la structure doit être la suivante:

NomDeLaBase1,initiales1,user1=DroitsSurLaBase1;user2=DroitsSurLaBase1;user3=DroitsSurLaBase1,...
NomDeLaBase12,initiales1,user1=DroitsSurLaBase2;user2=DroitsSurLaBase2;user3=DroitsSurLaBase2,...
NomDeLaBase13,initiales1,user1=DroitsSurLaBase3;user2=DroitsSurLaBase3;user3=DroitsSurLaBase3,...
...

* licence......... : The GNU General Public License (GPL) 
*					 http://www.opensource.org/licenses/gpl-license.html
*
****/
		
/*******************************************************************
*
*    class 
*
********************************************************************/
{	
	function __construct($agendaSolo=false, $connection = True)
	{
		$this->cal_mode=TRUE;
		$this->agendaSolo = $agendaSolo;
		parent::__construct($connection);
		$this->date_jour 	= 	$this->univ_strftime("%Y-%m-%d");
		$this->time 		= 	time();
		if(!isset($dbt_jour)) $this->dbt_jour="06:00";
		if(!isset($fin_jour)) $this->fin_jour="22:00";
		if(!isset($interval_rdv)) $this->interval_rdv=15;
		
		$this->titleAddons = array("<script type=\"text/javascript\" src=\"./externe/XHRConnection.js\"></script>", "<script type=\"text/javascript\" src=\"./js/calendar.js\"></script>");
		if($this->pdaSet) $this->titleAddons[] = "<script type=\"text/javascript\" src=\"./js/pda.js\"></script>";
		if($_REQUEST["type"] == "jour") $this->titleAddons[] = "<script type=\"text/javascript\" src=\"./externe/libdeplace.js\"></script>";
		
		$this->registerLocale();
		$this->liste_utilisateurs();
		$this->lColors();
		
		//variables de document
		if($this->anchor == "ra") $this->dest = "ra.php";
		elseif($this->anchor == "ra_new") $this->dest = "ra_new.php";
		else $this->dest = "agenda.php";
		if(isset($_POST["date_cours"]) AND ! $_POST["date_cours"]) unset ($_POST["date_cours"]); //évite l'utilisation de données postées vides intempestives
		if(!isset($_POST["date_cours"]) AND isset($_POST["jour_cours"]) AND isset($_POST["mois_cours"]) AND isset($_POST["annee_cours"])) $_POST["date_cours"] = "{$_POST["annee_cours"]}-{$_POST["mois_cours"]}-{$_POST["jour_cours"]}";
		if(!isset($_POST["date_cours"])) $_POST["date_cours"] = $this->univ_strftime("%Y-%m-%d", time());

		
		if(!isset($_POST["type"]) || !$_POST["type"]) $_POST["type"] = ($this->getCookie("pda") == "pda")? "semaine": "mois";
		$this->liste_personne="";
		$this->liste_soustraitant="";
		if(isset($_POST["soustraitant"])) if(is_array($_POST["soustraitant"])) foreach($_POST["soustraitant"] as $nom) $this->liste_soustraitant .= "$nom,";
		if(isset($_POST["groups"])) if(is_array($_POST["groups"])) foreach($_POST["groups"] as $nom) $_POST["personne"][] = $nom;
		if(isset($_POST["personne"]))
		{
			$this->utilisateursgroupes=$this->liste_utilisateursgroupes_ex();
// 			$this->tab_affiche($this->utilisateursgroupes);
			$deja_note=array(); //pour éviter qu'une personne qui se trouve dans plusieurs groupes apparaisse plusieurs fois
			if(is_array($_POST["personne"]))
			{
				foreach($_POST["personne"] as $nom)
				{
					if(preg_match("#\*\*\*group\*\*\*#", $nom))
					{
						$nudename=preg_replace("#\*\*\*group\*\*\*#", "", $nom);
						foreach($this->utilisateursgroupes["$nudename"] as $val)
						{
							if(trim($val) != "")
							{
								$val=trim($val);
								if(!in_array($val, $deja_note))
								{
									$this->liste_personne .= "$val,";
									$deja_note[] = $val;
								}
							}
						}
					}else{
						if(!in_array($nom, $deja_note))
						{
							$this->liste_personne .= "$nom,";
							$deja_note[] = $nom;
						}
					}
				}
// 				$this->tab_affiche($deja_note);
//  				echo $this->liste_personne;
				$this->setCookie("liste_personne",$this->liste_personne);
				
			}
		}
		elseif($this->getCookie("liste_personne") !== false)
		{
			$this->liste_personne=$this->getCookie("liste_personne");
		}
		if($this->liste_personne == "") 
		{
			if($this->getSinglePersoOption("agenda")) $this->liste_personne = $this->getSinglePersoOption("agenda");
			elseif(isset($_SESSION["session_avdb"])) $this->liste_personne = substr($_SESSION["session_avdb"],0,2);
			else $this->liste_personne = "nobody";
		}
		
		if($_POST["persReload"]) $this->liste_personne = $_POST["persReload"];
		
		if($_POST["template"]) $this->setCookie("template",$_POST["template"]);
		elseif($this->getCookie("template") !== false)
		{
			$_POST["template"] = $this->getCookie("template");
		}
		else
		{
			$this->setCookie("template", "agenda");
			$_POST["template"] = "agenda";
		}
		
		if($_POST["faits"]!="on")
		{
			$this->faits_check = "";
		}else {
			$this->faits_check = "checked";
		}

	}
	
	function ics2prolawyer($ics, $mode="file")
	{
		if(!$ics) return;
		if($mode == "file")
		{
			$ctx = stream_context_create(array(
			    'http' => array(
			            'timeout' => 1
				            )
			        )
			);
			@$extract = file_get_contents($ics, 0, $ctx);
			if(!$extract) return "Doodle Error";
			else $ics = $extract;
		}
		
		$events = array();
		$n = -1;
		$icsArray = explode("\n", $ics);
/*		echo "<br>";
		echo date_default_timezone_get();
		echo "<br>";
 */       foreach($icsArray as $line)
        {
			$line = trim($line);
			if($line == "") continue;
			if(preg_match("#^BEGIN:VCALENDAR#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^X-WR-TIMEZONE#", $line, $r))
			{
				$oldDefaultTZ = date_default_timezone_get;
				date_default_timezone_set($r[1]);
// 				date_default_timezone_set('Europe/London');
			}
			elseif(preg_match("#^VERSION#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^PRODID#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^CALSCALE#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^X-WR-CALNAME#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^METHOD:PUBLISH#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^BEGIN:VEVENT#", $line, $r))
			{
				$n++;
				$events[$n] = array();
			}
			elseif(preg_match("#^ORGANIZER:MAILTO#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^SUMMARY:(.*$)#", $line, $r))
			{
				$events[$n]["libelle"] = preg_replace("#( )*\[(tentative-)?Doodle\]#", "", $r[1]);
				$events[$n]["provisoire"] = preg_match("#\[tentative-Doodle\]#", $r[1]) ? 1:0;
			}
			elseif(preg_match("#^X-MICROSOFT-CDO-BUSYSTATUS:(.*$)#", $line, $r))
			{
				$events[$n]["provisoire"] = preg_match("#TENTATIVE#", $r[1]) ? 1:0;
			}
			elseif(preg_match("#^URL:(.*$)#", $line, $r))
			{
				$events[$n]["url"] = $r[1];
			}
			elseif(preg_match("#^UID:(.*$)#", $line, $r))
			{
				#UID is per event and not per group, so useless
//  				$events[$n]["uid"] = substr(preg_replace("#[^0-9]#", "", $r[1]), 0, 15);
			}
			elseif(preg_match("#^LOCATION:(.*$)#", $line, $r))
			{
				$events[$n]["lieu"] = $r[1];
			}
			elseif(preg_match("#^DESCRIPTION:(.*$)#", $line, $r))
			{
				if(! $events[$n]["lieu"]) $events[$n]["lieu"] = $r[1];
			}
			elseif(preg_match("#^DTSTAMP:(.*$)#", $line, $r))
			{
				#Do nothing: just moment where you retreive the information (use for reserveid)
				$ts = strtotime($r[1]);
// 				$h = $this->univ_strftime("%H:%M:%S", strtotime($r[1]));
// 				$d = $this->univ_strftime("%Y-%m-%d", strtotime($r[1]));
// 				$events[$n]["heure_stamp"] = $h;
// 				$events[$n]["date_stamp"] = $d;
// 				$events[$n]["tsstamp"] = $r[1];
				$events[$n]["ts"] = $ts;
			}
			elseif(preg_match("#^DTSTART:(.*$)#", $line, $r))
			{
				$h = $this->univ_strftime("%H:%M:%S", strtotime($r[1]));
				$d = $this->univ_strftime("%Y-%m-%d", strtotime($r[1]));
				$events[$n]["heure_debut"] = $h;
				$events[$n]["date_debut"] = $d;
// 				$events[$n]["tsdebut"] = $r[1];
			}
			elseif(preg_match("#^DTEND:(.*$)#", $line, $r))
			{
				$h = $this->univ_strftime("%H:%M:%S", strtotime($r[1]));
				$d = $this->univ_strftime("%Y-%m-%d", strtotime($r[1]));
				$events[$n]["heure_fin"] = $h;
				$events[$n]["date_fin"] = $d;
// 				$events[$n]["tsfin"] = $r[1];
			}
			elseif(preg_match("#^END:VEVENT#", $line, $r))
			{
				#Do nothing.
			}
			elseif(preg_match("#^END:VCALENDAR#", $line, $r))
			{
				#Do nothing.
			}
			else $events[$n][] = "Unmatched: $line";
        }
		date_default_timezone_set($oldDefaultTZ);
// 		$this->tab_affiche($events);
		
		//Clean old records
		$mReq = "delete from rdv where doodle not like '' and doodleset like '' and rdv_pour like '{$_SESSION["optionGen"]["initiales"]}'; ";
// 		echo "<br>'$mReq'";
		mysqli_query($this->mysqli, $mReq);
		
		//Select manually treated records
		$rIds = array();
		$kReq = "select doodle from rdv where doodle not like '' and doodleset not like '' and rdv_pour like '{$_SESSION["optionGen"]["initiales"]}'; ";
		$eR = mysqli_query($this->mysqli, $kReq);
		while($rR = mysqli_fetch_array($eR))
		{
			$rIds[] = $rR["doodle"];
		}
		
// 		$this->tab_affiche($events);
// 		die("toto");
		
		//Reinsert automatic doodle datas
		$dValues = "";
		foreach($events as $n => $a)
		{
			foreach($a as $b => $c) $$b = $c;
// // 			echo "<br>Event: $n ($provisoire)";
			$provisoire = $provisoire ? $ts:"";
			if($lieu == $url) $lieu = "";
// 			$mReq .= "insert into rdv set libelle = '$libelle', date_debut = '$date_debut', heure_debut = '$heure_debut', date_fin = '$date_fin', heure_fin = '$heure_fin', reserveid = '$provisoire', doodle = '$url', lieu = '$lieu', rdv_pour = '{$this->optionGen["initiales"]}'";
// 			$imReq = "insert into rdv set libelle = '$libelle', date_debut = '$date_debut', heure_debut = '$heure_debut', date_fin = '$date_fin', heure_fin = '$heure_fin', reserveid = '$provisoire', doodle = '$url', lieu = '$lieu', rdv_pour = '{$_SESSION["optionGen"]["initiales"]}'";
			if(!in_array($url, $rIds))
			{
				if($dValues) $dValues .= ", ";
				$dValues .= "('$libelle', '$date_debut','$heure_debut', '$date_fin', '$heure_fin', '$provisoire', '$url', '$lieu', '{$_SESSION["optionGen"]["initiales"]}')";
			}
// 			mysqli_query($this->mysqli, $imReq);
// // 			if($n
		}
		if($dValues)
		{
			$dCols = "INSERT INTO rdv (libelle, date_debut, heure_debut, date_fin, heure_fin, reserveid, doodle, lieu, rdv_pour) VALUES 
			$dValues";
			mysqli_query($this->mysqli, $dCols);
// 			echo $this->beautifyMysql($dCols);
		}
// 		echo $mReq;
// 		mysqli_multi_query($this->mysqli, $mReq);
// 		echo $ics;
	}
	
	function liste_rdv($restrict="", $personne="", $reserveid = False)
	{
		list($restrict, $dossier) = preg_split("#@#", $restrict);
		$debut_mysql = ($_POST["debut"] <> "") ? $_POST["debut"] : "0";
		if($_POST["print"]!="on") $limite_nombre="limit $debut_mysql,{$_SESSION["nb_affiche"]}";
		
		if($personne == "")
		{
			$clause = "rdv_pour like ''";
		}else{
			$this->liste_utilisateursgroupes_revert();	
			$this->liste_utilisateursgroupes_ex();	
			$compteur=1;
			$array1=preg_split("#,#", $personne);
			$array_clause = array();
			foreach($array1 as $n) if(trim($n) != "")
			{
				if(!in_array($n, $array_clause)) $array_clause[] = $n;
				if(is_array($this->liste_des_utilisateursgroupes_revert["$n"])) foreach($this->liste_des_utilisateursgroupes_revert["$n"] as $grp) if(!in_array("_$grp", $array_clause)) $array_clause[] = "_$grp";
				if(substr(trim($n), 0, 1) == "_")
				{
					$grp = substr(trim($n), 1);
					foreach($this->liste_des_utilisateursgroupes_ex[$grp] as $member) if(!in_array($member, $array_clause)) $array_clause[] = $member;
				}
			}
			
			foreach ($array_clause as $gugusse)
			{
				if(trim($gugusse) != "")
				{
					$gugusse = addslashes(stripslashes(trim($gugusse)));
					if($compteur == 1) $clause = "rdv_pour like '%$gugusse%'";
					else $clause .= " OR rdv_pour like '%$gugusse%'";
					$compteur ++;
				}
			}
		}
		
		$compteur=1;
		$array_limitation=preg_split("# #", $restrict);
		foreach ($array_limitation as $gugusse)
		{
			if(trim($gugusse) != "")
			{
				$gugusse = addslashes(stripslashes(trim($gugusse)));
				if($compteur == 1) $limitation = "libelle like '%$gugusse%'";
				else $limitation .= " AND libelle like '%$gugusse%'";
				$compteur ++;
			}
		}
		
		$limitation = $limitation ? "($limitation) AND":"";
		
		if($reserveid) $limitation .= " reserveid like '$reserveid' AND";
		if($dossier)
		{
			$limitation .= " dossier like '$dossier' AND";
			$_POST["rechercheagenda"] = True;
			$_POST["recherchedelai"] = True;
		}
		
		if($_POST["typerecherche"]=="agenda" || $_POST["rechercheagenda"])
		{
		
			if($_POST["limiterecherche"])
			{
				foreach(array("Debut", "Fin") as $moment)
				{
					if($_POST["jourrecherche$moment"] && $_POST["moisrecherche$moment"] && $_POST["anneerecherche$moment"])
					{
						$jour = $_POST["jourrecherche$moment"];
						$mois = $_POST["moisrecherche$moment"];
						$annee = $_POST["anneerecherche$moment"];
						$comp = ($moment == "Debut") ? "(date_debut >= '$annee-$mois-$jour' OR (date_debut like '0000-00-00' AND date_fin >= '$annee-$mois-$jour'))":"date_fin <= '$annee-$mois-$jour'";
						$limitation = $limitation ? "$limitation $comp AND":"$comp AND";
					}
				}
			}
			
			$query0="select * from rdv where $limitation ($clause)";
			$exec0=mysqli_query($this->mysqli, $query0);
			$stop_mysql = mysqli_num_rows($exec0);
			$query="select * from rdv where $limitation ($clause) ORDER BY date_debut, date_fin $limite_nombre";
//  			echo "<br>$query";
			$exec=mysqli_query($this->mysqli, $query);
			if(mysqli_num_rows($exec)) echo "<h2>{$this->lang["entete_agenda"]}</h2>";
			echo $this->table_open();
			while($row=mysqli_fetch_array($exec))
			{
				$this->dejaTrouve=array();
				$couls=$this->table_open("border=\"0\" cellspacing=\"0\" cellpadding=\"0\"");
				$couls .="<tr>";
				$qqn=FALSE;
				foreach(explode(",", $row["rdv_pour"]) as $value)
				{
					$value = trim($value);
					if(substr($value, 0, 1) == "_")
					{
						$gName = substr($value, 1);
						$groupes = $this->liste_utilisateursgroupes_ex();
						foreach($groupes["$gName"] as $part) if(!in_array($part, $this->dejaTrouve)) $this->dejaTrouve[] = $part;
							
					}
					elseif($value != "" && !in_array($value, $this->dejaTrouve)) $this->dejaTrouve[] = $value;
				}
				foreach($this->dejaTrouve as $value)
				{
					$init=substr($value, 0, 2);
					$actColor = trim($this->liste_des_utilisateurs["$init"]["couleur"]);
					if(!$actColor) $actColor = "#ffffff";
					$couls .= "<td align=\"center\" class=\"$classtexte\" style=\"background-color:$actColor;color:000000\">$init</td>";
					$qqn=TRUE;
				}
				if($qqn==FALSE)
				{
					$init="";
					$couls .= "<td align=\"center\">??</td>";
				}
				$couls .= "</tr>";
				$couls .= $this->table_close();
				
				//$id="form".$row["id"];
				if($row["biffe"]) $row["libelle"] = "<del>{$row["libelle"]}</del> <span class=\"attention\">{$this->lang["agenda_annule"]}</span>";
				$new_libelle = $row["libelle"];
				$new_libelle = $this->make_visible($array_limitation, $new_libelle);
				$dateCours = $row["date_debut"];
				$date_debut=$this->univ_strftime("%d.%m.%Y", $this->mtf_date($row["date_debut"]));
				$heure_debut=substr($row["heure_debut"], 0, -3);
				$form = $this->form("agenda.php#$dateCours", "$date_debut&nbsp;($heure_debut)&nbsp;:", "", "", "", "date_cours", $dateCours, "type", $_REQUEST["type"]);

				$onClick = "onclick=\"openRdv('{$row["id"]}')\"";
				$onDup   = "onclick=\"copyRdv('{$row["id"]}', '0')\"";
				echo "\n<tr style=\"cursor:pointer\"><td>$form</td><td>$couls</td><td $onClick>$new_libelle</td><td $onDup>[+]</td></tr>";//." (offsets: $offset et $endoffset";
			}
			$this->table_close();
			if($stop_mysql >= $_SESSION["nb_affiche"]) echo $this->footer($debut_mysql, $stop_mysql);
			else echo $this->table_close();
		}
		
		if($_POST["typerecherche"]=="delais" || $_POST["recherchedelai"])
		{
		
// 			$this->tab_affiche();
			if($_POST["limiterecherche"])
			{
				foreach(array("Debut", "Fin") as $moment)
				{
					if($_POST["jourrecherche$moment"] && $_POST["moisrecherche$moment"] && $_POST["anneerecherche$moment"])
					{
						$jour = $_POST["jourrecherche$moment"];
						$mois = $_POST["moisrecherche$moment"];
						$annee = $_POST["anneerecherche$moment"];
						$comp = ($moment == "Debut") ? "date_fin >= '$annee-$mois-$jour'":"date_fin <= '$annee-$mois-$jour'";
						$limitation = $limitation ? "$limitation $comp AND":"$comp AND";
					}
				}
			}
			
			if(!$_POST["faits"])
			{
				$limitation = $limitation ? "$limitation fait not like 'on' AND":"fait not like 'on' AND";
			}
			$clause=preg_replace("#rdv_pour#", "dl_pour", $clause);
// 			echo "<br>$clause";
			$query0="select * from delais where $limitation ($clause)";
			$exec0=mysqli_query($this->mysqli, $query0);
			$stop_mysql = mysqli_num_rows($exec0);
			$query="select * from delais where $limitation ($clause) ORDER BY date_fin $limite_nombre";
			$exec=mysqli_query($this->mysqli, $query);
			if(mysqli_num_rows($exec)) echo "<h2>{$this->lang["entete_delais"]}</h2>";
// 			echo "<br>$query";
			echo $this->table_open();
			while($row=mysqli_fetch_array($exec))
			{
				$couls=$this->table_open("border=\"0\" cellspacing=\"0\" cellpadding=\"0\"");
				$couls .="<tr>";
				$qqn=FALSE;
				foreach(explode(",", $row["dl_pour"]) as $value)
				{
					if(trim($value) != "")
					{
						$init=substr($value, 0, 2);
						$actColor = trim($this->liste_des_utilisateurs["$init"]["couleur"]);
						if(substr($value, 0, 1) == "_")
						{
							$init= substr($value, 1);
							$actColor = "ff0000";
						}
						if(!$actColor) $actColor = "#ffffff";
						$couls .= "<td align=\"center\" class=\"$classtexte\" style=\"background-color:$actColor;color:000000\">$init</td>";
						$qqn=TRUE;
					}
				}
				if($qqn==FALSE)
				{
					$init="";
					$couls .= "<td align=\"center\">??</td>";
				}
				$couls .= "</tr>";
				$couls .= $this->table_close();
				
				//$id="form".$row["id"];
				if($row["biffe"]) $row["libelle"] = "<del>{$row["libelle"]}</del> <span class=\"attention\">{$this->lang["agenda_pas_faire"]}</span>";
				$new_libelle = $row["libelle"];
				$new_libelle = $this->make_visible($array_limitation, $new_libelle);
				$date_debut=$this->univ_strftime("%d.%m.%Y", $this->mtf_date($row["date_fin"]));
				//$form = $this->form("modifier_delai.php", "", "", "$classtexte", "$id<td>", "id", $row["id"]);

				echo "\n<tr style=\"cursor:pointer\"><td onclick=\"openDl('{$row["id"]}')\">$date_debut&nbsp;:</td><td onclick=\"openDl('{$row["id"]}')\">$couls</td><td onclick=\"openDl('{$row["id"]}')\">$new_libelle</td><td onclick=\"openDl('{$row["id"]}')\">$form</td><td style=\"text-align:right\"><a class=\"duplicate\" name=\"#\" onclick=\"copyDl('{$row["id"]}')\">[+]</a></td></tr>";//." (offsets: $offset et $endoffset";
			}
			echo $this->table_close();
			if($stop_mysql >= $_SESSION["nb_affiche"]) echo $this->footer($debut_mysql, $stop_mysql);
			else echo $this->table_close();
		}
	}
	
	function display_vacation($date="", $personne="")
	{
		
		//variables
			
		$date=$this->mtf_date($date);//atention: display_vacation est appelée directement, donc avec une date de type mysql. Corriger.
		$year = $this->univ_strftime("%Y", $date);
		$array_verif=array();
		
		//gestion de la clause where relative à la personne
		if($personne == "")
		{
			$clause = "rdv_pour like ''";
//			echo "raté";
		}else{
			$this->liste_utilisateursgroupes_revert();	
			$this->liste_utilisateursgroupes_ex();	
		
			$compteur=1;
			$array1=preg_split("#,#", $personne);
			$array = array();
			$array2 = array();
			foreach($array1 as $n) if(trim($n) != "")
			{
				if(!in_array($n, $array)) $array[] = $n;
				if(!in_array($n, $array2)) $array2[] = $n;
				if(is_array($this->liste_des_utilisateursgroupes_revert["$n"])) foreach($this->liste_des_utilisateursgroupes_revert["$n"] as $grp) if(!in_array("_$grp", $array)) $array[] = "_$grp";
				if(substr(trim($n), 0, 1) == "_")
				{
					$grp = substr(trim($n), 1);
					foreach($this->liste_des_utilisateursgroupes_ex[$grp] as $member)
					{
						if(!in_array($member, $array)) $array[] = $member;
						if(!in_array($member, $array2)) $array2[] = $member;
					}
				}
			}

			//construction de la requête
			foreach ($array as $gugusse)
			{
				if(trim($gugusse) != "")
				{
					$gugusse = trim($gugusse);
					if($compteur == 1) $clause = "rdv_pour like '%$gugusse%'";
					else $clause .= " OR rdv_pour like '%$gugusse%'";
					$compteur ++;
				}
			}
		}
		
		$max=$this->univ_strftime("%j", mktime(5,5,5,12,31,$year));
		foreach($array as $gugusse) for($x=1;$x<($max + 1);$x++) $days["$x"]["$gugusse"] = 0;
		
		//construction de la requête
		$mdate=$this->univ_strftime("%Y-%m-%d", $date);
		$debutdate=$this->univ_strftime("%Y-1-1", $date);
		$findate=$this->univ_strftime("%Y-12-31", $date);
		$mjour=$this->univ_strftime("%d", $date);
		$mmois=$this->univ_strftime("%m", $date);
		$mannee=$this->univ_strftime("%Y", $date);
		$manneemois=$this->univ_strftime("%Y%m", $date);
		
		//en cas d'affichage par semaine ou mois, il faut afficher tous les rendez-vous
		if($type == "jour")
		{
			$semaine_mois = "AND (heure_debut <'{$this->fin_jour}:00' OR date_debut <> '$mdate') 
		AND (heure_fin >'{$this->dbt_jour}:00' OR date_fin <> '$mdate')";
		}else{
			$semaine_mois = "";
		}
		$query="select 
		*, date_format(date_debut, '%e') AS jour_debut, 
		date_format(date_debut, '%m') AS mois_debut, 
		date_format(date_debut, '%w') AS jour_semaine, 
		'$mdate' as debut_jour,
		adddate('$mdate', datediff(date_fin, date_debut)) AS fin_jour,
		adddate(date_debut, 7 * floor(datediff('$mdate', date_debut) / 7)) AS debut_semaine,
		adddate(date_fin, 7 * floor(datediff('$mdate', date_debut) / 7)) AS fin_semaine,
		adddate(date_debut, INTERVAL period_diff('$manneemois', date_format(date_debut, '%Y%m')) month) AS debut_mois,
		adddate(adddate(date_debut, INTERVAL period_diff('$manneemois', date_format(date_debut, '%Y%m')) month), datediff(date_fin, date_debut)) AS fin_mois,
		adddate(date_debut, INTERVAL 12 * floor(period_diff('$manneemois', date_format(date_debut, '%Y%m'))/12) month) AS debut_annee,
		adddate(adddate(date_debut, INTERVAL 12 * floor(period_diff('$manneemois', date_format(date_debut, '%Y%m'))/12) month), datediff(date_fin, date_debut)) AS fin_annee
		from rdv 
		where 
		(
			(
			date_debut <= '$findate' AND date_fin >= '$debutdate' AND repete like '')
			OR (date_debut <= '$mdate' AND (adddate(repete_fin, datediff(date_fin, date_debut)) >= '$mdate' OR repete_fin  like '0000-00-00' OR repete_fin like '') AND 
				(
					repete like 'j' 
					OR repete like 's' AND '$mdate' BETWEEN adddate(date_debut, 7 * floor(datediff('$mdate', date_debut) / 7)) AND adddate(date_fin, 7 * floor(datediff('$mdate', date_debut) / 7)) 
					OR repete like 'm' AND '$mdate' BETWEEN adddate(date_debut, INTERVAL period_diff('$manneemois', date_format(date_debut, '%Y%m')) month) AND adddate(adddate(date_debut, INTERVAL period_diff('$manneemois', date_format(date_debut, '%Y%m')) month), datediff(date_fin, date_debut))
					OR repete like 'a' AND '$mdate' BETWEEN adddate(date_debut, INTERVAL 12 * floor(period_diff('$manneemois', date_format(date_debut, '%Y%m'))/12) month) AND adddate(adddate(date_debut, INTERVAL 12 * floor(period_diff('$manneemois', date_format(date_debut, '%Y%m'))/12) month), datediff(date_fin, date_debut))
				)
			)
		)
		$semaine_mois
		AND (rdv_pour like '%$pers%')
		AND ($clause)
		AND (type like 'vacances')
		order by date_debut, heure_debut, heure_fin";
		
// 		echo nl2br($query);
		$exec=mysqli_query($this->mysqli, $query);
		$amArray = array();
		while($row=mysqli_fetch_array($exec))
		{
			list($year_start, $month_start, $day_start) = preg_split("#-#", $row["date_debut"]);
			list($year_end, $month_end, $day_end) = preg_split("#-#", $row["date_fin"]);
			$date_debut=($year_start < $year)? "$year-1-1" : "$year_start-$month_start-$day_start";
			$date_fin=($year_end > $year)? "$year-12-31" : "$year_end-$month_end-$day_end";
			list($year_start, $month_start, $day_start) = preg_split("#-#", $date_debut);
			list($year_end, $month_end, $day_end) = preg_split("#-#", $date_fin);
			
			$monthArray = array();
			foreach($array as $gugusse) if(preg_match("#$gugusse#", $row["rdv_pour"]))
			{
				if(substr(trim($gugusse), 0, 1) == "_")
				{
					$grp = substr(trim($gugusse), 1);
					foreach($this->liste_des_utilisateursgroupes_ex[$grp] as $member)
					{
						if(!in_array($member, $amArray)) $amArray[] = $member;
						if(!in_array($member, $monthArray)) $monthArray[] = $member;
					}
				}
				else
				{
					if(!in_array($gugusse, $amArray)) $amArray[] = $gugusse;
					if(!in_array($gugusse, $monthArray)) $monthArray[] = $gugusse;
				}
			
			}
			foreach($monthArray as $gugusse)
			{
//  				print "<br>+$gugusse";
				if(!in_array($gugusse, $array_verif)) $array_verif[]= $gugusse;
				$debut=$this->univ_strftime("%j", $this->mtf_date($date_debut));
				$fin=$this->univ_strftime("%j", $this->mtf_date($date_fin));
				for($x=$debut;$x<$fin+1;$x++)
				{
					$x+=0;
					$days[$x][$gugusse]=$row["id"];
				}
			}
		}
		
// 		$this->tab_affiche($amArray);
		
		$y=1;
		for($x=1;$x<13;$x++)
		{
			$test=($x==12)? $this->univ_strftime("%j", mktime(5,5,5,12,31,$year)) +1:($this->univ_strftime("%j", mktime(5,5,5,$x+1,1,$year)));
			for($z=1;$y<$test;$y++,$z++) $vacances["$x"]["$z"] = $days[$y];
		}
		
		
		$tableau = "";
		$tableau .= "<form action=\"./agenda.php \" name=\"change1\" method=\"post\">";
		$tableau .= $this->table_open();
		$tableau .= "<tr><td>";
		$tableau .= $this->input_hidden("date_cours", 1);
		$tableau .= $this->input_hidden("template", 1);
		$tableau .= $this->input_hidden("type", 1);
		$tableau .= $this->button("{$this->lang["apropos_pour"]}&nbsp;->", "", "semaine_entete");
		$tableau .= "</td></tr><tr><td>";
		$tableau .= "<select multiple name=\"personne[]\" size=\"6\" class=\"semaine\">";
		$tableau .= $this->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $this->liste_personne, TRUE, $groups);
		$tableau .= "</select></td><td>";
		$tableau .= "<select multiple name=\"personne[]\" size=\"6\" class=\"semaine\">";
		$tableau .= $this->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $this->liste_personne, TRUE, TRUE, TRUE);
		$tableau .= "</select></td></tr>";
		$tableau .= $this->table_close();
		$tableau .= "</form>";
		
		$tableau .= $this->table_open();
		$tableau .= "<tr>".$this->form("agenda.php<th colspan=16 align=right>","[-]", "", "menu", "", "template", "agenda", "type", "vacances", "date_cours", $year - 1 ."-1-1")."<th colspan=6>".$this->univ_strftime("%Y", mktime(5,5,5,1,1,$year))."</th>".$this->form("agenda.php<th colspan=16 align=left>","[+]", "", "menu", "", "template", "agenda", "type", "vacances", "date_cours", $year + 1 ."-1-1")."</tr>";
		$cur_month=0;
		$oldLineStyle = "ligneplan2";
		for($x=1;$x<13;$x++)
		{
			//test de l'existence de chaque personne dans le mois
			foreach($amArray as $gugusse) 
			{
// 				print "<br>$gugusse";
				$test_mois[$x][$gugusse]=FALSE;
				foreach($vacances[$x] as $jour => $personne)
				{
// 	 				echo "<br>vacances [$x][$jour] [$personne]";
					if($vacances[$x][$jour][$gugusse]>0)
					{
// 						print "$gugusse";
						$test_mois[$x][$gugusse]=$personne;
					}
				}
			}
			
			//écriture du tableau, ligne par ligne selon les personnes
			
			$linestyle=(2 * floor($x /2) == $x)? "ligneplan1":"ligneplan2";
			
			if ($linestyle != $oldLineStyle) $tableau .= "<tr class='ligneplan3'><td colspan=43 style='height:1px'>&nbsp;</td></tr>";
			$oldLineStyle = $linestyle;

// 			$this->tab_affiche($test_mois);
// 			echo "tralala";
			$first_line = FALSE;
			$array3=$array2;
			$array3[] = "blankcontrol";
			$this->lcolor_all["blankcontrol"] = "e0e0e0";
			$this->color_all_all["blankcontrol"] = "c0c0c0";
			$rowspan=0;
			foreach($array2 as $pers) if($test_mois[$x][$pers]) $rowspan ++;
			if($rowspan == 0) $rowspan = 1;
			foreach($array3 as $nom => $gugusse2) if($test_mois[$x][$gugusse2] || ($gugusse2 == "blankcontrol" && $first_line == FALSE))
			{
				if(!$first_line) $add=$this->form("agenda.php<td rowspan = \"$rowspan\">", $this->univ_strftime("%B", mktime(5,5,5,$x,1,$year)), "", "$linestyle", "",  "template", "agenda", "date_cours", "$year-$x-1");
				else $add="";
				$first_line = TRUE;
				$tableau .= "\n<tr class=\"$linestyle\" style='border-style:solid;border-width:1;border-color:000000'>$add";
				for($k=1;$k<$this->univ_strftime("%u", mktime(5,5,5,$x,1,$year));$k++) $tableau .= "<td>&nbsp;</td>";
				foreach($vacances["$x"] as $jour => $val)
				{
					$valeur = $vacances[$x][$jour][$gugusse2];
					$color = ($valeur > 0)? $this->liste_des_utilisateurs["$gugusse2"]["couleur"]/*$this->color_all["$gugusse2"]*/:$this->color_all_all["blankcontrol"];
					$onOver = "onMouseOver='show(\"{$this->liste_des_utilisateurs["$gugusse2"]["nom"]}\")' onMouseOut='hide()'";
					$test_we=$this->univ_strftime("%u", mktime(5,5,5,$x,$jour,$year));
					if($test_we>5)
					{
						$color = ($valeur > 0) ? $this->liste_des_utilisateurs["$gugusse2"]["lCouleur"]:$this->lcolor_all["blankcontrol"];
					}
					$tableau .= $valeur ? "<td $onOver bgcolor=$color><a style=\"cursor:pointer;color:000000\" name=\"#\" onclick=\"openRdv('$valeur')\">$jour</a></td>":"<td bgcolor=$color><a style=\"cursor:pointer;color:000000\" name=\"#\" onclick=\"newRdv('$year-$x-$jour','','','vacances')\">$jour</a></td>";
				}
				for($k=1;$k<39 -$this->univ_strftime("%u", mktime(5,5,5,$x,1,$year)) - $jour;$k++) $tableau .= "<td>&nbsp;</td>";
				$tableau .= "</tr>";
			}
		}
		
		$tableau .= $this->table_close();
		
		$tableau .= "<br><br>".$this->table_open()."\n<tr>";
		foreach($array_verif as $gugusse) $tableau .="<td style=\"border-color:000000;border-style:solid;border-width:1\" bgcolor=\"{$this->liste_des_utilisateurs["$gugusse"]["couleur"]}\">&nbsp;&nbsp;&nbsp;</td><td>$gugusse</td><td>&nbsp;</td>";
		$tableau .= "</tr>".$this->table_close();
		return $tableau;
	}
	
	function afficheDL($e, $class)
	{
		//il faut la liste des délais, qui dépend de l'utilisateur
		if(!isset($this->listeDelaisTypes))
		{
			$this->listeDelaisTypes = array();
			$select=explode("\n", "{$_SESSION["optionGen"]["delais_type"]}");
			foreach($select as $option)
			{
				list($abrev, $nom)=preg_split("#,#", $option);
				$this->listeDelaisTypes[$abrev] = $nom;
			}
		}
		$couls .= $this->getColors($e[2], "delai");
		$rdv .= $this->smart_html($e[1]);
		if($e[4]) $rdv = "<del>$rdv</del> <span class=\"attention\">{$this->lang["agenda_annule"]}</span>";
		
		//infobulle
		$infobulle = $this->qui_fait_quoi($e[6], $e[7], $e[8], $e[9]);
		$firstInfobulle = $this->newPdaMenu ? $infobulle:"";
		$lastInfobulle = $this->newPdaMenu ? "":$infobulle;
		
		//ouverture RDV
		$onclick = "onclick=\"openDl('{$e[3]}')\"";
		
		//priorité
		if($e[10] == "2") $priorite = "<font color=orange class='agendasize'>".$this->mysql_to_print($e[0], "%d.%m.%Y")."</font>";
		else $priorite = $this->listeDelaisTypes[$e[5]];
		if($e[5] == "1") $class = "class = \"attention agendasize\"";
		else $class = "class = \"agendasize\"";
		if($this->newPdaMenu)
		{
			$rdv = "<span $class>$priorite</span>: $rdv";
			$priorite = "";
		}
		else $priorite = "<td $class>$priorite</td>";
		
		//Retour
		$r = "<td $firstInfobulle class='delaitexte agendasize'>$couls</td><td class='delaitexte' $lastInfobulle $onclick>$rdv</td>$priorite<td style=\"text-align:right\"><a class=\"duplicate\" name=\"#\" onclick=\"copyDl('{$e[3]}')\">[+]</a></td>";
		return $r;
	}
	function afficheRDV($e, $class)
	{
		/*e est passé directement dans le while($r = mysqli_fetch_array de la recherche des rendez-vous).
		Structure de e:
		 0 libelle
		 1 debut
		 2 fin
		 3 rdv_pour
		 4 rdv_grp
		 5 priorite
		 6 np
		 7 nple
		 8 mp
		 9 mple
		10 type
		11 repete
		12 biffe
		13 lieu
		14 id
		15 reserveid*/
		//numéro éventuel dans le rdv

		$click = $this->getClickableItem("tel", $e[0]);
		$toClick = ($click["val"])? "<span {$click["class"]}>{$click["sup"]}</span>":"";
		
		
		//Couleurs des personnes concernées
		if($e[10])
		{
			$ajout = "<td><img class=agendasize src=\"images/{$e[10]}.png\"></td>";
			$nump =1;
		}
		else
		{
			$ajout = "";
			$nump = 0;
		}
		$couls .= $this->getColors($e[3], $class, $nump, $ajout, $toClick);
		//Affichage du RDV
		$rdv = "";
		if(!$e[10])
		{
			$ajout = "texte";
			//if(! $this->newPdaMenu) $rdv .= "{$e[1]}-{$e[2]}: ";
			$rdv .= "{$e[1]}-{$e[2]}: ";
		}
		else
		{
			$ajout = "vacances";
		}
		$rdv .= $this->smart_html($e[0]);
		//$rdv .= "'{$tel["val"]}'";
		if($e[12]) $rdv = "<del>$rdv</del> <span class=\"attention\">{$this->lang["agenda_annule"]}</span>";
		
		//infobulle
		$infobulle = $this->qui_fait_quoi($e[6], $e[7], $e[8], $e[9]);
		$firstInfobulle = $this->newPdaMenu ? $infobulle:"";
		$lastInfobulle = $this->newPdaMenu ? "":$infobulle;
		
		//ouverture RDV
		$onclick = $e[16] == 0 ? "onclick=\"openRdv('{$e[14]}')\"":" style=\"cursor:no-drop\"";
		
		//On change le style si le rdv est provisoire
		$specProv = $e[15]? "style=color:#808080;font-style:italic":"";
		$boutAjou = $e[15]? "<img src=\"images/link.png\">":"[+]";
		
		//doodle
		if($e[17])
		{
			$doodleIm = ($e[18] == "m") ? "doodleset":"doodle";
			$boutAjou = "<a href='{$e[17]}' target=doodle><img width=16px height=16px src='{$this->settings["root"]}images/$doodleIm.png'></a>";
		}
		//Le rendez-vous lui-même avec le bouton pour ajouter
		$mefRDV = $this->newPdaMenu && $_POST["type"] == "mois" ? "":"<td class='{$class}{$ajout}' $specProv $lastInfobulle $onclick>$rdv</td><td style=\"text-align:right\"><a class=\"duplicate\" name=\"#\" onclick=\"copyRdv('{$e[14]}', '{$e[15]}')\">$boutAjou</a></td>";
		
		//Retour
		$r = "<td $firstInfobulle class='{$class}texte'>$couls</td>$mefRDV";
		return $r;
	}
	
	function getColors($c, $class, $nump = 0, $ajout = "", $tel = "")
	{
		$hasCouls = False;
		$couls="<table class='{$class}texte' border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr style=padding:2px;margin:2px>$ajout";
		$qqn=FALSE;
		$numpControl = $this->newPdaMenu? 2:3;
		foreach(preg_split("/,/", $c) as $value)
		{
			if(trim($value) != "")
			{
				$hasCouls = True;
				$isGroup = False;
				$nump++;
				$init=substr($value, 0, 2);
				$actColor = $this->liste_des_utilisateurs["$init"]["couleur"];
				$fond="background-";
				if(substr($value, 0, 1) == "_")
				{
					$init= substr($value, 1);
					$actColor = "ff0000";
					$fond="";
					$isGroup = True;
				}
				if(!$actColor) $actColor = "#ffffff";
				if(substr($actColor, 0, 1) != "#") $actColor = "#{$actColor}";
				if(strtolower(substr($actColor, -6)) == "000000" && $fond == "background-") $txColor = ";color:#ffffff";
				else $txColor = "";
				if($isGroup && $hasCouls) $couls .= "</tr><tr>";
				$colSpan = $isGroup ? "colspan=5" : "";
				$couls .= "<td $colSpan align=\"center\" class='agendasize' style=\"{$fond}color:$actColor{$txColor}\" $pstyle>$init</td><td>&nbsp;</td>";
				$qqn=TRUE;
				if($nump == $numpControl or $isGroup)
				{
					$nump=0;
					$couls .= "</tr><tr>";
				}
			}
		}
		$couls .= "<td>$tel</td>";
		if($qqn==FALSE)
		{
			$init="";
			$couls .= "<td align=\"center\">??</td>";
		}
		$couls .= "</tr></table>";
		return $couls;
	}
	
	function getPeriode($date = False, $typePeriode = False, $fDOM = "", $lDOM = "", $native = True)
	{
		if(!$date) 
		{
			//print "{$_POST["date_cours"]} = {$_POST["annee_cours"]}-{$_POST["mois_cours"]}-{$_POST["jour_cours"]}";
			if(! $_POST["date_cours"] && $_POST["jour_cours"] && $_POST["mois_cours"] && $_POST["annee_cours"])
			{
				$_POST["date_cours"] = "{$_POST["annee_cours"]}-{$_POST["mois_cours"]}-{$_POST["jour_cours"]}";
			}
			$date = $_POST["date_cours"];
		}
		if(!$typePeriode) $typePeriode = $_POST["type"];
		if(!$typePeriode) $typePeriode = "mois";
		$this->getClause();
// 		$date = "2013-1-25";
		list($annee, $mois, $jour) = preg_split("/-/", $date);
		//print "<br>'$date'";
		$mois += 0; // supprimer un éventuel zéro initial
		
		if($typePeriode == "mois")
		{
			//getting first and las days of month
			$fDOW = $this->univ_strftime("%u", mktime(1, 0, 0, $mois, 1, $annee));
			$fDOM = $this->univ_strftime("%Y-%m-%d", mktime(1, 0, 0, $mois, 2-$fDOW, $annee));
			
			$lDOW = $this->univ_strftime("%u", mktime(1, 0, 0, $mois + 1, 0, $annee));
			$lDOM = $this->univ_strftime("%Y-%m-%d", mktime(1, 0, 0, $mois + 1, 7 - $lDOW, $annee));
			
		}
		if($typePeriode == "semaine")
		{
			//getting first and las days of month
			$dOW = $this->univ_strftime("%u", mktime(1, 0, 0, $mois, $jour, $annee));
			$fDOM = $this->univ_strftime("%Y-%m-%d", mktime(1, 0, 0, $mois, $jour-$dOW + 1, $annee));
			$lDOM = $this->univ_strftime("%Y-%m-%d", mktime(1, 0, 0, $mois, $jour-$dOW + 7, $annee));
		}
		if($typePeriode == "jour")
		{
			//getting first and las days of month
			$dOW = $this->univ_strftime("%u", mktime(1, 0, 0, $mois, $jour, $annee));
			$fDOM = $_POST["date_cours"];
			$lDOM = $_POST["date_cours"];
		}

// 			list($aTD, $mTD, $jTD) = preg_split("/-/", $fDOM); 
// 			list($aTF, $mTF, $jTF) = preg_split("/-/", $fDOM);
		$this->jourAct = $this->univ_strftime("%Y-%m-%d", time());
		$this->jourActMTF = $this->mtf_date($this->jourAct);
		$this->dateMTF = $this->mtf_date($date);
		//comptons les jours...
		
		if($typePeriode == "mois") $nbJours = $this->univ_strftime("%j", mktime(1, 0, 0, $mois + 1, 7 - $lDOW, $annee) - mktime(1, 0, 0, $mois, 2-$fDOW, $annee)) + 0;
		if($typePeriode == "semaine") $nbJours = 7;
		if($typePeriode == "jour") $nbJours = 1;
		if(substr($typePeriode, 0, 5) == "liste") $nbJours = mktime(1, 0, 0, TODO, TODO, TODO);
		
		//créons le tableau des jours à remplir
		
		$jours   = array();
		$indexes = array();
		list($pAnnee, $pMois, $pJour) = preg_split("/-/", $fDOM);
		for($x = 0; $x < $nbJours; $x ++)
		{
			$uTime = mktime(1, 0, 0, $pMois, $pJour + $x, $pAnnee);
			$jourTraite = $this->univ_strftime("%Y-%m-%d", $uTime);
			if($this->jourAct == $jourTraite) $class = "dujour";
			elseif($mois == $this->univ_strftime("%m", $uTime)) $class = "enmois";
			else $class = "horsmois";
			$jours[$jourTraite] = array("c" => $class, "u" => $uTime, "e" => array(), "d" => array());
			$indexes[] = $jourTraite;
		}
		
		
		
		//getting Month events
		$q="SELECt
		*,
		date_format(date_debut, '%c') as nomdumois,
		if(({$this->clause}),libelle,concat('{$this->lang["agenda_lieu_occupe"]}: ',lieu)) as libelle
		from rdv 
		where 
		(
			(
				date_debut between '$fDOM' AND '$lDOM'
				OR date_fin between '$fDOM' AND '$lDOM'
				OR date_debut <= '$fDOM' AND date_fin >= '$lDOM'
				OR
				(
					date_debut <= '$fDOM' 
					AND (repete_fin >= '$fDOM' OR repete_fin  like '0000-00-00' OR repete_fin like '') 
					AND
					(
						repete like 'j' 
						OR repete like 's' 
						OR repete like 'm'
						OR (repete like 'a' AND (date_format(date_debut, '%c') = '$mois' OR date_format(date_fin, '%c') = '$mois'))
					)
				)
			)
			AND (({$this->clause}) or (doodle like '' AND lieu not like ''))
			AND doodleset not like 'd' 
		)
		order by date_debut, heure_debut, heure_fin
		";
		$q2 = preg_replace("/\t/", "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;", nl2br($q));
//  		print "<br>'$q2'";
		$e = mysqli_query($this->mysqli, $q);
		$this->now[] = microtime().": Début de la ventilation des dates";
		$textePrive = "<span style=color:brown>({$this->lang["modifier_rdv_status_1"]})</span>";
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC))
		{
			$statPrive = 0;
			if($r["status"] == 1)
			{
				if ($r["mp"] == $_SESSION["user"] || ($r["mp"] == "" && $r["np"] == $_SESSION["user"])) $r["libelle"].= " $textePrive";
				else
				{
					$r["libelle"] = $textePrive;
					$statPrive = 1;
				}
			}
			if($r["type"] == "vacances") $r["heure_debut"] = "-1";
			elseif($r["type"] == "anniversaire") $r["heure_debut"] = "-2";
			else
			{
				$r["heure_debut"] = substr($r["heure_debut"], 0, -3);
				$r["heure_fin"] = substr($r["heure_fin"], 0, -3);
			}
			if($r["repete"] == "a")
			{
		//		echo "<br>";
		//		$this->tab_affiche($r);
		//		echo "<br>";
				list($aD, $mD, $jD) = preg_split("/-/", $r["date_debut"]);
				list($aF, $mF, $jF) = preg_split("/-/", $r["date_fin"]);
				$r["date_debut"] = "$annee-$mD-$jD";
				$r["date_fin"] = "$annee-$mF-$jF";
				$r["repete"] = "";
		//		$this->tab_affiche($r);
				//TODO anchorYear: ne joue pas avec un RDV à cheval sur une année.

			}
			if(!$r["repete"])
			{
				$i = $r["date_debut"];
				if($r["date_debut"] == $r["date_fin"])
				{
					$debut = $r["heure_debut"];
					$fin   = $r["heure_fin"];
					$jours[$i]["e"][] = array($r["libelle"], $debut, $fin, $r["rdv_pour"], $r["rdv_grp"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], $r["type"], $r["repete"], $r["biffe"], $r["lieu"], $r["id"], $r["reserveid"], $statPrive, $r["doodle"], $r["doodleset"]);
				}
				else
				{
					list($aD, $mD, $jD) = preg_split("/-/", $r["date_debut"]);
					list($aF, $mF, $jF) = preg_split("/-/", $r["date_fin"]);
					$tD = mktime(1, 0, 0, $mD, $jD, $aD);
					$tF = mktime(1, 0, 0, $mF, $jF, $aF);
					foreach($jours as $j => $arr) if($tD <= $arr["u"] && $tF >= $arr["u"])
					{
						$debut = ($tD < $arr["u"])? "00:00" : $r["heure_debut"];
						if($r["type"] == "vacances") $debut = "-1";
						elseif($r["type"] == "anniversaire") $debut = "-2";
						$fin = ($tF > $arr["u"])? "23:59" : $r["heure_fin"];
						$jours[$j]["e"][] = array($r["libelle"], $debut, $fin, $r["rdv_pour"], $r["rdv_grp"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], $r["type"], $r["repete"], $r["biffe"], $r["lieu"], $r["id"], $r["reserveid"], $statPrive, $r["doodle"], $r["doodleset"]);			
					}
				}
			}
			else
			{
				list($aD, $mD, $jD) = preg_split("/-/", $r["date_debut"]);
				list($aF, $mF, $jF) = preg_split("/-/", $r["repete_fin"]);
				$tD = mktime(1, 0, 0, $mD, $jD, $aD);
				$tF = mktime(1, 0, 0, $mF, $jF, $aF);
				if($aF + $mF + $jF == 0) $tF = False;
				if($r["repete"] == "j")
				{
					foreach($jours as $j => $arr) if($tD <= $arr["u"] && (!$tF || $tF >= $arr["u"]))
					{
						$jours[$j]["e"][] = array($r["libelle"], $r["heure_debut"], $r["heure_fin"], $r["rdv_pour"], $r["rdv_grp"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], $r["type"], $r["repete"], $r["biffe"], $r["lieu"], $r["id"], $r["reserveid"], $statPrive, $r["doodle"], $r["doodleset"]);	
					}
				}
				if($r["repete"] == "s") //TODO: compléter. Les RDV sur plusieurs jours ne sont pas pris en compte.
				{
// 					print "<br>{$r["libelle"]}";
					if($typePeriode == "jour")
					{
						if($this->univ_strftime("%u", $tD) == $dOW)
						{
							foreach($jours as $j => $arr) if($tD <= $arr["u"] && (!$tF || $tF >= $arr["u"]))
							{
								$jours[$j]["e"][] = array($r["libelle"], $r["heure_debut"], $r["heure_fin"], $r["rdv_pour"], $r["rdv_grp"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], $r["type"], $r["repete"], $r["biffe"], $r["lieu"], $r["id"], $r["reserveid"], $statPrive, $r["doodle"], $r["doodleset"]);	
							}
						}
					}
					else
					{
						for($k = $this->univ_strftime("%u", $tD) -1;$indexes[$k];$k += 7) //Attention: les indexes commencent à zéro alors que la semaine commence à 1
						{
							$j = $indexes[$k];
							$arr = $jours[$j];
	// 						print "<br>Test semaine de $k ($j): $tD <= {$arr["u"]} && $tF >= {$arr["u"]}";
							if($tD <= $arr["u"] && (!$tF || $tF >= $arr["u"]))
							{
	// 							print " ... OK";
								$jours[$j]["e"][] = array($r["libelle"], $r["heure_debut"], $r["heure_fin"], $r["rdv_pour"], $r["rdv_grp"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], $r["type"], $r["repete"], $r["biffe"], $r["lieu"], $r["id"], $r["reserveid"], $statPrive, $r["doodle"], $r["doodleset"]);
							}
						}
					}
				}				
				if($r["repete"] == "a") //TODO: idem que ci-dessus. En outre, il faut faire en sorte de vérifier non seulement le mois en cours mais également les débuts et fins de mois précédant ou suivant. Je pense qu'on ne s'en sort pas sans un "adddate"
					//TODO: (suite) je tente de m'en sortir avec une modification de l'année, à TODO: anchorYear
				{
 					print "<br>TAGADA{$r["libelle"]} ({$r["date_debut"]}";
// 					$jourTest = $this->univ_strftime("%d", $tD);
// 					$tD <= $arr["u"] && $tF >= $arr["u"])
// 					{
// 						$jours[$j]["e"][] = array($r["libelle"], $r["heure_debut"], $r["heure_fin"], $r["rdv_pour"], $r["rdv_grp"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], $r["type"], $r["repete"], $r["biffe"], $r["lieu"]);	
// 					}
				}				
			}
// 			if(!$true)
// 			{
// 				print "<br>";
// 				foreach($r as $a => $b) /*if(! is_numeric($a))*/ print "$a: '$b'; ";
// 				$true = True;
// 			}
// 			print "<br>{$r["date_debut"]}, {$r["date_fin"]}, {$r["libelle"]}";
		}
		$this->now[] = microtime().": Fin de la ventilation des dates";
		$this->now[] = microtime().": Debut de l'affichage du mois";
		
		//getting Month todos
		$q="SELECt
		*
		from delais
		where 
		(
			(
				date_debut between '$fDOM' AND '$lDOM'
				OR date_fin between '$fDOM' AND '$lDOM'
			)
			AND ({$this->clause_DL})
			AND fait not like 'on'
		)
		order by date_fin, priorite
		";
// 		echo preg_replace("/\t/", "&nbsp;&nbsp;&nbsp;&nbsp;", nl2br("\n\n$q"));
/*				$ANDrepeteCond = "AND repete not like 'o'";
				$repeteCond = "OR ('$date_jour' between date_debut and date_fin AND repete like 'o')";
// 				echo "<br>$repeteCond";
			}

			
			$requete="select * from delais where ((date_fin $where_cond '$date_jour' $ANDrepeteCond) $repeteCond ) $where $who order by date_fin, priorite";*/		
		$e = mysqli_query($this->mysqli, $q);
		while($r = mysqli_fetch_array($e, MYSQLI_ASSOC))
		{
// 			if(preg_match("/MOUTALATIF/", $r["libelle"]))
// 			{
// 				echo "\n<br>";
// 				foreach($r as $a => $b) echo "$a: $b ";
// 				echo "<br>".$this->mtf_date($r["date_debut"]). "<= {$this->jourActMTF} && {$r["repete"]} == 'o'";
// 			}
			if($jours[$r["date_fin"]]) $jours[$r["date_fin"]]["d"][] = array($r["date_fin"], $r["libelle"], $r["dl_pour"], $r["id"], $r["biffe"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], 1);
			if($r["repete"] == "o" && $r["date_fin"] != $r["date_debut"])
			{
				if($this->mtf_date($r["date_debut"]) <= $this->jourActMTF) $jours[$this->jourAct]["d"][] = array($r["date_fin"], $r["libelle"], $r["dl_pour"], $r["id"], $r["biffe"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], 2);
				else                                                       $jours[$r["date_debut"]]["d"][] = array($r["date_fin"], $r["libelle"], $r["dl_pour"], $r["id"], $r["biffe"], $r["priorite"], $r["np"], $r["nple"], $r["mp"], $r["mple"], 2);
				//TODO: il faut vérifier si la actMTF (= jour où l'on interroge le serveur) est dans la période affichée. Si non, cela ne sert à rien de comparer date_debut avec jourActMTF; il faut le comparer avec fDOM.
			}
			//echo "<br>{$r["date_debut"]}-{$r["date_fin"]}: {$r["libelle"]}";
		}
		$monthname=$this->univ_strftime("%B %Y", $this->mtf_date($date));
		$dateMoins = $_POST["type"] == "semaine" ? mktime(1, 0, 0, $mois, $jour -7, $annee) : mktime(1, 0, 0, $mois -1, 1, $annee);
		$datePlus  = $_POST["type"] == "semaine" ? mktime(1, 0, 0, $mois, $jour +7, $annee) : mktime(1, 0, 0, $mois +1, 1, $annee);
		$affiche  = $this->table_open("align=center");
		if($this->newPdaMenu)
		{
			$selClass = "class=\"semaine selstyle dateselect\"";
			$selStyle = "$selClass onfocus=\"this.size=3\" onblur=this.size=1 size=1";
			$optType = "";
			foreach(array("jour", "semaine", "mois") as $t)
			{
				$afT = $this->lang["agenda_nom_$t"];
				$selected = ($t == $_POST["type"]) ? "selected": "";
				$optType .= "<option value='$t' $selected>$afT</option>";
			}
// 			$affiche .= "<tr><td colspan=5 align=center></td></tr>\n";
		}
		$afficheNom = "";
		switch($_POST["type"])
		{
			case "jour":
				$afficheNom .= $this->univ_strftime("%d %b %Y", mktime(1, 0, 0, $mois, $jour, $annee));
				$dateMoins = mktime(1, 0, 0, $mois, $jour -1, $annee);
				$datePlus  = mktime(1, 0, 0, $mois, $jour +1, $annee);
				break;
			case "semaine":
				$afficheNom .= "{$this->lang["agenda_nom_semaine"]}&nbsp;".$this->univ_strftime("%V (%d %b %Y - ", mktime(1, 0, 0, $pMois, $pJour, $pAnnee)).$this->univ_strftime("%d %b %Y)", mktime(1, 0, 0, $pMois, $pJour+6, $pAnnee));
				$dateMoins = mktime(1, 0, 0, $mois, $jour -7, $annee);
				$datePlus  = mktime(1, 0, 0, $mois, $jour +7, $annee);
				break;
			case "mois":
				$afficheNom .= ucfirst($this->univ_strftime("%B %Y", mktime(1, 0, 0, $mois, $jour, $annee)));
				$dateMoins = mktime(1, 0, 0, $mois -1, 1, $annee);
				$datePlus  = mktime(1, 0, 0, $mois +1, 1, $annee);
				break;
		}
		
		if($this->androlawyerClient) $this->formMethod = "GET";
		$affiche .= "<tr>";
		$affiche .= $this->form("agenda.php<td>", "<<", "", "", "", "date_cours", $this->univ_strftime("%Y-%m-%d", mktime(1, 0, 0, $mois, 1, $annee -1)), "type", $_POST["type"]);
		$affiche .= $this->form("agenda.php<td>", "-", "-", "", "moins<td>", "date_cours", $this->univ_strftime("%Y-%m-%d", $dateMoins), "type", $_POST["type"]);
		$affiche .= "<td>$afficheNom</td>";
		$affiche .= $this->form("agenda.php<td>", "+", "+", "", "plus<td>", "date_cours", $this->univ_strftime("%Y-%m-%d", $datePlus), "type", $_POST["type"]);
		$affiche .= $this->form("agenda.php<td>", ">>", "", "", "", "date_cours", $this->univ_strftime("%Y-%m-%d", mktime(1, 0, 0, $mois, 1, $annee +1)), "type", $_POST["type"]);
		if($this->pdaSet)
		{
			$affiche .= "<td><img src='./images/search.png' onclick=\"if(document.getElementById('tablerecherche').style.display=='table') document.getElementById('tablerecherche').style.display='none';else document.getElementById('tablerecherche').style.display='table'\" id='srcbut'></td>";
		}

		$affiche .= "</tr>";
		$affiche .= $this->table_close();
		unset($this->formMethod);
		if($this->newPdaMenu)
		{
			$optJour = "";
			for($x=1;$x<32;$x++)
			{
				$selected = $x == $jour ? "selected": "";
				$optJour .= "<option $selected value=$x>$x</option>";
			}
			$optJour = "\n<select class='semaine selstyle dateselect' name=jour_cours style='width:2em'>$optJour</select>";
			$optMois = "";
			for($x=1;$x<13;$x++)
			{
				$selected = $x == $mois ? "selected": "";
				$nomX = $this->univ_strftime("%b", mktime(0, 0, 0, $x, 1, 2000));
				$optMois .= "<option $selected value=$x>$nomX</option>";
			}
			$optMois = "\n<select class='semaine selstyle dateselect' name=mois_cours style='width:4em'>$optMois</select>";
			$optAnnee = "";
			for($x=$annee -5;$x<$annee + 6; $x++)
			{
				$selected = $x == $annee ? "selected": "";
				$optAnnee .= "<option $selected value=$x>$x</option>";
			}
			$optAnnee = "\n<select class='semaine selstyle dateselect' name=annee_cours style='width:4em'>$optAnnee</select>";
			$liste = $this->liste_personne;
			$selecteurP = $this->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $liste, TRUE);
			$selecteurG = $this->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $liste, TRUE, True, True);
			
			$affiche .= "<span style=text-align:center;display:inline-block;width:100%;position:relative class='dateselect'><form action=./agenda.php method=POST class=dateselect>";
			$affiche .= "<span style=height:3vw;max-height:3vw;display:inline-block><select name=type $selClass>$optType</select></span>";
// 			$affiche .= "$optJour $optMois $optAnnee&nbsp;";
			$affiche .= "<input type=date name=date_cours value={$_POST["date_cours"]}>&nbsp;";
// 			$affiche .= $this->input_hidden("type", 1);
			$affiche .= $this->button($this->lang["apropos_pour"], "", "dateselect");
			$affiche .= "&nbsp;<span style=height:3vw;width:10em;max-height:3vw;display:inline-block><select $selStyle multiple name=\"personne[]\" id=\"pers\">$selecteurP</select><select $selStyle multiple name=\"groups[]\" id=\"groups\" style=\"right:0px\">$selecteurG</select></span></form></span>";
		}else{
		}
		
		$affiche .= $this->table_open("height=100%");
		$compteur = 1;
		foreach($indexes as $i)
		{
			if($compteur == 1 || $typePeriode == "jour")
			{
				$affiche .= "\n\t<tr>";
				if($typePeriode == "semaine") $rs = "rowspan=4";
				else $rs = "";
				if($typePeriode == "mois" ||($typePeriode == "semaine" && $compteur == 1) || $typePeriode == "jour") $affiche .= $this->form("{$this->dest}<td $rs>", $this->univ_strftime("%V", $jours[$i]["u"]), "", "semaine", "", "date_cours", $i, "type", "semaine");//.$this->univ_strftime("%V", $jours[$i]["u"])."</td>";
			}
			$nomJour = $this->univ_strftime("%a %d", $jours[$i]["u"]);
			$mtfJour = $this->univ_strftime("%Y-%m-%d", $jours[$i]["u"]);
			$nouvelle_date_cours=$this->univ_strftime("%Y-%m-%d", $jours[$i]["u"]);
			if ($jours[$i]["c"] == "dujour") $nomJour = "<a name=today>$nomJour</a>";
			$today = ($jours[$i]["c"] == "dujour") ? "<a name=today></a>":"<a name='$mtfJour'></a>";
			if($typePeriode == "mois") $width = "14";
			if($typePeriode == "semaine") $width = "48";
			if($typePeriode == "jour") $width = "96";
			$jourStyle = ($typePeriode == "jour") ? ";min-height:50vw;height:70vw;width:90vw":"";
			$affiche .= "\n\t\t<td class='{$jours[$i]["c"]}texte' style = 'vertical-align:top$jourStyle' width={$width}% $rowspan>";
			$rowspan = "";
			$affiche .= $this->table_open("width=100% height=100%");
			$affiche .= "<tr><td style='vertical-align:top;height:10px'>";
// 			$affiche .= "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<br>";
			//$affiche .= $today.$this->form("{$this->dest}", $this->univ_strftime("%a", $jours[$i]["u"])."&nbsp;".$this->univ_strftime("%d", $jours[$i]["u"]), "", "nomjourmois", "", "date_cours", $this->univ_strftime("%Y-%m-%d", $jours[$i]["u"]), "type", "jour").$this->form("{$this->dest}", "&nbsp;".$this->univ_strftime("%b", $jours[$i]["u"]), "", "nomjourmois", "", "date_cours", $this->univ_strftime("%Y-%m-%d", $jours[$i]["u"]), "type", "mois");
			
			$addSize = $this->pdaSet ? "text-align:center;vertical-align:middle;font-size:2em":"";
			$newRdv = "<span class=\"delai_seul\" style=\"cursor:pointer;color:#0000ff;float:left;$addSize\"  onclick=\"newRdv('$nouvelle_date_cours', '{$this->dbt_jour}', '{$this->liste_personne}')\">&nbsp;+&nbsp;</span>";
			$newDl   = "<span class=\"delai_seul\" style=\"cursor:pointer;color:#ffffff;float:right;$addSize\" onclick=\"newDl('$nouvelle_date_cours', '{$this->liste_personne}')\">&nbsp;+&nbsp;</span>";
			
			$affiche .= $this->table_open("width=100%");
			$affiche .= "<td align=left>$newRdv</td><td align=center>".$today.$this->form("{$this->dest}", $this->univ_strftime("%a", $jours[$i]["u"])."&nbsp;".$this->univ_strftime("%d", $jours[$i]["u"]), "", "style='display:inline' class=nomjourmois@form", "", "date_cours", $this->univ_strftime("%Y-%m-%d", $jours[$i]["u"]), "type", "jour").$this->form("{$this->dest}", "&nbsp;".$this->univ_strftime("%b", $jours[$i]["u"]), "", "style='display:inline' class=nomjourmois@form", "", "date_cours", $this->univ_strftime("%Y-%m-%d", $jours[$i]["u"]), "type", "mois")."</td><td align=right>$newDl</td>";
			$affiche .= $this->table_close();
			//$affiche .= "<span class='nomjourmois'>$nomJour</span>";

			//Tri par heure_debut (priorité) !
			$rdvs = array();
			$dls = array();
			foreach($jours[$i]["e"] as $j => $e) $rdvs[$j] = $e[1].$e[0];
			foreach($jours[$i]["d"] as $j => $d) $dls[$j]  = $d[5].$d[1];
			asort($rdvs);
			asort($dls);
 //$this->tab_affiche($d);
			$e = $jours[$i]["e"];
			$d = $jours[$i]["d"];
// 			foreach($jours[$i]["e"] as $e)
// 			$rdvs2 = $rdvs;
// 			$this->tab_affiche($rdvs2);
			$affiche .= "</td></tr><tr><td style='vertical-align:top'>\n\t\t\t<table width=100%>";
			foreach($rdvs as $j => $v)
			{
// 				$affiche .= "<br>{$e[$j][1]}-{$e[$j][2]}:{$e[$j][0]}";
				$affiche .= "\n\t\t\t<tr>".$this->afficheRDV($e[$j], $jours[$i]["c"])."</tr>";
			}
			$affiche .= "\n\t\t\t</table></td></tr>";
			if(!$this->newPdaMenu)
			{
				$affiche .= "<tr width=100% valign=bottom><td>";
				$affiche .= "\n\t\t\t<table width=100% class='delaitexte'>";
				foreach($dls as $j => $v)
				{
	// 				$affiche .= "<br>{$e[$j][1]}-{$e[$j][2]}:{$e[$j][0]}";
					$affiche .= "\n\t\t\t<tr>".$this->afficheDL($d[$j], $jours[$i]["c"])."</tr>";
				}
				$affiche .= "\n\t\t\t</table></td></tr>";
			}
			$affiche .= $this->table_close();
			$affiche .= "\n\t\t</td>";
			if($typePeriode == "semaine" && $compteur == 4)
			{
				$rowspan = "rowspan=2";
			}
			if(($typePeriode == "mois" && $compteur == 7) || ($typePeriode == "semaine" && $compteur %2 == 0) || $typePeriode == "jour")
			{
				$affiche .= "\n\t</tr>";
				if ($typePeriode == "mois") $compteur = 0;
			}
			$compteur ++;
		}
		$affiche .= $this->table_close();
		print $affiche;
		$this->now[] = microtime().": Fin de l'affichage du mois";
// 		$this->tab_affiche($jours);
		
	}
	
	function getClause()
	{
		$this->liste_utilisateursgroupes_revert();	
		$this->liste_utilisateursgroupes_ex();	
	
		$personne = $this->liste_personne;
		$compteur=1;
		$array1=preg_split("#,#", $personne);
		$array = array();
		foreach($array1 as $n) if(trim($n) != "")
		{
			if(!in_array($n, $array)) $array[] = $n;
			if(is_array($this->liste_des_utilisateursgroupes_revert["$n"])) foreach($this->liste_des_utilisateursgroupes_revert["$n"] as $grp) if(!in_array("_$grp", $array)) $array[] = "_$grp";
			if(substr(trim($n), 0, 1) == "_")
			{
				$grp = substr(trim($n), 1);
				if(is_array($this->liste_des_utilisateursgroupes_ex[$grp])) foreach($this->liste_des_utilisateursgroupes_ex[$grp] as $member) if(!in_array($member, $array)) $array[] = $member;
			}
		}

		//construction de la requête
		foreach ($array as $gugusse)
		{
			if(trim($gugusse) != "")
			{
				$gugusse = trim($gugusse);
				if($compteur == 1) $clause = "rdv_pour like '%$gugusse%'";
				else $clause .= " OR rdv_pour like '%$gugusse%'";
				$compteur ++;
			}
		}
		$this->clause = $clause;
		$this->clause_DL = preg_replace("/rdv_pour/", "dl_pour", $this->clause);
		$this->clause = "({$this->clause}) or status = 2";
		$this->arrGugusses = $array;
	
	}
	
	function calendarSelect($date="", $with_select=FALSE, $personne="", $native=False)
	{
		if($date == "") $date = time();
		else $date = $this->mtf_date($date);
		$fdate=$this->univ_strftime("%Y-%m-%d", $date);
		$testMonth=$this->univ_strftime("%m", $date);
		$date=$this->mtf_date($this->get_first($this->univ_strftime("%Y-%m-%d", $date), "mois"));
		$var=$this->get_first($this->univ_strftime("%Y-%m-%d", $date), "semaine");
		$fday=$var["first"];
		$lday=$var["last"];
		$this->fBegDay=$fday;
		//il faut déterminer si le mois a 5 ou 6 semaines affichées
		$testNextMonth=$this->univ_strftime("%m", $this->mtf_date($fday) + 35 * 86400);
		$nbSem = ($testNextMonth == $testMonth) ? 6:5;
		$this->fEndDay=$this->univ_strftime("%Y-%m-%d", $this->mtf_date($fday) + (($nbSem * 7)-1) * 86400);
// 		echo "<br>Nb sem: $nbSem. End: {$this->fEndDay}<br>";
		$affiche=$this->table_open("border = \"0\" cellspacing = \"0\" cellpadding = \"0\"");
		if(isset($this->nb_form)) $this->nb_form ++;
		else $this->nb_form = 1;
		$url=$this->settings["root"].$this->dest;
		$name = "change" . $this->nb_form;
		if(!$native) $affiche .= "<form action=\"$url\" name=\"$name\" id=\"$name\" method=\"post\">";
		$affiche .= "\n<tr valign=top>";
		if(!$native)
		{
			$affiche .= "<td colspan = \"9\">";
			$affiche .= $this->table_open("cellpadding=0 cellspacing=0 align=\"right\" border=0")."<tr>";
			$affiche .= "<td>";
			$affiche .= $this->button("->", "", "semaine_entete");
			$affiche .= $this->chdate($date, "mois", FALSE, "semaine");
			$affiche .= $this->chdate($date, "annee", FALSE, "semaine");
			$affiche .= $this->input_hidden("jour_cours", "", $this->univ_strftime("%d", $date));
			$affiche .= $this->input_hidden("type", "", "mois");
			$affiche .= $this->input_hidden("remuneration", True);
			$affiche .= "</td>";
			$affiche .= "</tr>".$this->table_close();
			$nativeCompl = "";
		}
		else
		{
			$nativeCompl = "<input type=date name=date_cours value={$_POST["date_cours"]}>";
// 			$affiche .= "</td></tr>";
		}
		if($with_select)
		{
			$groups=(preg_match("#LSSTRAIT#", $personne))? FALSE:TRUE;
			$colspan=(preg_match("#LSSTRAIT#", $personne))? "1":"2";
			$formname=(preg_match("#LSSTRAIT#", $personne))? "soustraitant[]":"personne[]";
			$liste=(preg_match("#LSSTRAIT#", $personne))? $personne:$this->liste_personne;
			$formulaire=(preg_match("#LSSTRAIT#", $personne))?$this->simple_selecteur("", $liste):$this->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $liste, TRUE);
// 					$formulaire="selecteur";
			if(!$native)
			{
				$affiche .= "<td rowspan=8>&nbsp;</td>";
				$affiche .= "<td class=\"semaine\" rowspan=\"8\">";
			}
			else
			{
 				$affiche .= "<td class=\"semaine\">";
				$affiche .= "<form action=\"$url\" name=\"$name\" id=\"$name\" method=\"post\">";
			}
			$affiche .= "<table><tr><td colspan=$colspan>";
			$affiche .= "$nativeCompl&nbsp;". $this->button("{$this->lang["apropos_pour"]}&nbsp;->", "", "semaine_entete")."</td></tr><tr><td><select multiple name=\"$formname\" id=\"pers\" size=\"6\" class=\"semaine\">".$formulaire."</select>";
/*					$affiche .= $this->input_hidden("type", 1);
			$affiche .= $this->input_hidden("template", 1);*/
			$affiche .= "</td>";
			if(!preg_match("#LSSTRAIT#", $personne)) $affiche .= "<td><select multiple name=\"groups[]\" size=\"6\" class=\"semaine\">".$this->selecteur($_SESSION["session_utilisateur"], TRUE, FALSE, FALSE, $liste, TRUE, $groups, TRUE)."</select></td>";
		}
		$affiche .= "</tr>";
		if($with_select)
		{
			if(!preg_match("#LSSTRAIT#", $personne)) $affiche .= "<tr><td colspan=2>{$this->lang["liste_delais_avec"]}: <input type=\"checkbox\" name=\"faits\" $this->faits_check></td></tr></table></td></tr>";
			else $affiche .= "</table></td></tr>";
		}
		$affiche .= "</form>";
		if(!$native)
		{
			for($d=1; $d<($nbSem *7 +1); $d ++)
			{
				if ($this->date_jour == $fday) $class = "today";
				elseif ($fday == $fdate) $class = "semaine_select";
				elseif($this->univ_strftime("%m", $date) != $this->univ_strftime("%m", $this->mtf_date($fday))) $class="semainehorsmois";
				else $class = "semaine";
				$sem=$this->univ_strftime("%V", $fday);
				if(round(($d -1) / 7) == ($d -1) / 7) 
				{
					$semclass=($petit)? "semaine_entete":"";
					$form=$this->form("{$this->dest}<td>", $this->univ_strftime("%V", $this->mtf_date($fday)), "", "$semclass", "", "date_cours", $fday, "type", "semaine");
					$affiche .= "\n<tr>$form<td>&nbsp;</td>";
				}
				$affiche .= $this->form("{$this->dest}<td $rowspan align=right>", $this->univ_strftime("%d", $this->mtf_date($fday)), "", "$class", "", "date_cours", $fday, "type", "jour");
					
				if(round(($d -7) / 7) == ($d -7) / 7) 
				{
	// 				$val_test=$this->univ_strftime("%m", $date) + 1;
	// 				if($val_test == 13) $val_test = 1;
	// 				$val_ref=$this->univ_strftime("%m", $this->mtf_date($fday));
					$affiche .= "\n</tr>";
	// 				if($val_test != $val_ref) {} 
	// 				else {echo "$val_test == $val_ref"; break;}
				}
				$nextday = $this->mtf_date($fday) + 86400;
				$fday=$this->univ_strftime("%Y-%m-%d", $nextday);
			}
		}
// 		$affiche .= "\n</tr>";
		if(!$native) $affiche .= "<tr><td colspan=9>&nbsp;</td></tr>";
		$affiche .= $this->table_close();
		return $affiche;
	}		
	
	function get_first($date="", $type="semaine")
	{
		if($date == "") $date = time();
		else $date = $this->mtf_date($date);
//		echo "<br>le jour de la semaine est à l'heure actuelle le ".($this->univ_strftime("%u", $date));
		if($type == "semaine")
		{
			$var["first"] = $this->univ_strftime ("%Y-%m-%d", $date - (($this->univ_strftime("%u", $date) -1) * 86400));
			$soustr=$this->univ_strftime("%u", $date);
			$soustr=7-$soustr;
			$var["last"]=$date + ($soustr * 86400);
			$var["last"]=$this->univ_strftime ("%Y-%m-%d", $var["last"]); 
			return $var;
		}
		elseif($type == "mois") 
		{
			$first = $this->univ_strftime ("%Y-%m-%d", mktime(1, 0, 0, $this->univ_strftime("%m", $date), 1, $this->univ_strftime("%Y", $date)));
			return $first;
		}
	}
	
	function chdate($date, $type, $alone=FALSE, $class="")
	{
		if(!preg_match("#-#", $date)) $date=$this->univ_strftime ("%Y-%m-%d", $date);
		list($year, $month, $day) = preg_split("#-#", $date);
		if($type == "semaine")
		{
			for($n=-6; $n<7; $n++)
			{
				$array[]=$year + $n ."-$month-$day";
			}
		}
		if($type == "annee")
		{
			for($n=-6; $n<7; $n++)
			{
				$array[]=$year + $n ."-$month-$day";
			}
		}
		if($type == "mois")
		{
			for($n=-6; $n<6; $n++)
			{
				$curmonth = $month + $n;
				$curyear = $year;
				if($curmonth < 1)
				{
					$curmonth +=12;
					$curyear -= 1;
					
				}
				if($curmonth > 12)
				{
					$curmonth -=12;
					$curyear += 1;
					
				}
				if($curmonth < 10) $curmonth = "0".$curmonth;
				$array[]="$curyear-$curmonth-$day";
			}
		}
		foreach($array as $datechange)
		{
// 			if($alone)
			if($type == "annee") $display = $this->univ_strftime("%Y", $this->mtf_date($datechange));
			if($type == "mois") $display = $this->univ_strftime("%B", $this->mtf_date($datechange));
			$selected="";
			if($date==$datechange) $selected="selected";
			if($alone) $valdate=$datechange;
			elseif($type == "mois") $valdate = $this->univ_strftime("%m", $this->mtf_date($datechange));
			elseif($type == "annee") $valdate = $this->univ_strftime("%Y", $this->mtf_date($datechange));
			$listselect=$listselect."\n<option value=\"$valdate\" $selected>".$this->my_htmlentities($display)."</option>";
		}
		
		$name="ch".$type;
		if($alone) $input = "date_cours";
		elseif($type == "mois") $input = "mois_cours";
		elseif($type == "annee") $input = "annee_cours";
		if($alone) $change="onchange=\"$name.submit()\"";
		if($alone) $list="<form action=\"{$this->dest}\" method=\"post\" name=\"$name\" class=\"\">";
		$list.="<th><select name=\"$input\" $change class=\"$class\">";
		$list.=$listselect;
		$list.="</select></th>";
		if($alone) $list.="</form>";
		return $list;
	}
	
	function addtime($time, $interval)
	{
		list($h, $m) = preg_split("#:#", $time);
		list($h2, $m2) = preg_split("#:#", $interval);
		$h += $h2;
		$m += $m2;
//		echo "<br>$h:$m";
		while($m > 60)
		{
			$h +=1;
			$m -= 60;
//			if($x == 20) break;
		}
		$h=($h<10)?"0".$h:$h;
		$m=($m<10)?"0".$m:$m;
		$new_time="$h:$m";
		return $new_time;
	}
	
	function display_dl($periodes="<,=,>", $date_jour="", $personne="", $petit=FALSE)
	{
		if($_POST["faits"] == "on") $where = "";
		else $where = "and fait not like 'on'";
		if($personne == "") $personne2 = array();
		elseif(is_array($personne)) $personne2 = $personne;
		else $personne2=preg_split("#,#", $personne);
		
		$personne = array();				
		$this->liste_utilisateursgroupes_revert();	
		$this->liste_utilisateursgroupes_ex();	
	
		foreach($personne2 as $n) if(trim($n) != "")
		{
			if(!in_array($n, $personne)) $personne[] = $n;
			if(is_array($this->liste_des_utilisateursgroupes_revert["$n"])) foreach($this->liste_des_utilisateursgroupes_revert["$n"] as $grp) if(!in_array("_$grp", $personne)) $personne[] = "_$grp";
			if(substr(trim($n), 0, 1) == "_")
			{
				$grp = substr(trim($n), 1);
				if(is_array($this->liste_des_utilisateursgroupes_ex[$grp])) foreach($this->liste_des_utilisateursgroupes_ex[$grp] as $member) if(!in_array($member, $personne)) $personne[] = $member;
			}
		}

		//construction de la requête
		$who="";
		foreach ($personne as $gugusse)
		{
			if(trim($gugusse) != "")
			{
				if($who != "") $who .= " OR ";
				else $who = " AND (";
				$who .= "dl_pour like '%$gugusse%'";
			}
		}
						
		if($who != "") $who .= ")";
		if($date_jour == "") $date_jour = $this->date_jour;
		$return="";
		$arr_periodes=array();
		if(preg_match("#<#", $periodes)) $arr_periodes["<"] = $this->lang["liste_delais_delais_passes"];
		if(preg_match("#=#", $periodes)) $arr_periodes["="] = preg_replace("#DATEJOUR#", $this->mysql_to_print($date_jour), $this->lang["liste_delais_delais_jour"]);
		if(preg_match("#>#", $periodes)) $arr_periodes[">"] = $this->lang["liste_delais_delais_prochains"];
		foreach($arr_periodes as $where_cond => $titre)
		{
			if($petit != "abrege")
			{
				$return .= "\n<br><br>";
				if($petit == FALSE) $return .= "\n<h2>$titre</h2>";
				else $return .= "\n<h3>$titre</h3>";
			}
			$return .= "\n<table style=background-color:#ffffff width=100%>";
			
			$ANDrepeteCond = "";
			$repeteCond = "";
			if($where_cond == "=")
			{
				$ANDrepeteCond = "AND repete not like 'o'";
				$repeteCond = "OR ('$date_jour' between date_debut and date_fin AND repete like 'o')";
// 				echo "<br>$repeteCond";
			}

			
			$requete="select * from delais where ((date_fin $where_cond '$date_jour' $ANDrepeteCond) $repeteCond ) $where $who order by date_fin, priorite";
 			/*echo "<br>$requete";*/
			$exec=mysqli_query($this->mysqli, $requete);
			$numrow=mysqli_num_rows($exec);
			if($where_cond == "<") $warning = "<img src=\"./images/warning.png\">";
			else $warning="&nbsp;";
			if($numrow > 0 && $petit != "abrege" && ! $this->pdaSet) $return .= "\n<tr><th>$warning</th><th>".$this->lang["modifier_delai_pour"]."</th><th>".$this->lang["liste_delais_date_to"]."</th><th colspan=\"2\">".$this->lang["operations_op"]."</th><th>".$this->lang["modifier_delai_priorite"]."</th><th>".$this->lang["liste_delais_termine"]."</th></tr>";
			
			$firstDl=true;
		
			while($row=mysqli_fetch_array($exec))
			{
				$nump=0;
				$pstyle=($petit == "abrege")?"class=\"affdl\"":"";
// 				if($petit == "abrege" && $firstDl==true) $return .= "\n<tr><td><hr></td></tr>";
				$firstDl=false;
				$row["libelle"] = $this->smart_html($row["libelle"]);
				if($row["biffe"]) $row["libelle"] = "<del>{$row["libelle"]}</del> <span class=\"attention\">{$this->lang["agenda_pas_faire"]}</span>";
				if($row["fait"] == "on") $row["libelle"] = "<del>{$row["libelle"]}</del>";
				$couls="<table border=\"0\" cellspacing=\"0\" cellpadding=\"0\"><tr>";
				$qqn=FALSE;
				foreach(explode(",", $row["dl_pour"]) as $value)
				{
					if(trim($value) != "")
					{
						$nump++;
						$init=substr($value, 0, 2);
						$actColor = $this->liste_des_utilisateurs["$init"]["couleur"];
						if(substr($value, 0, 1) == "_")
						{
							$init= substr($value, 1);
							$actColor = "ff0000";
						}
						if(!$actColor) $actColor = "#ffffff";
						$couls .= "<td class='agendasize' align=\"center\" style=\"background-color:$actColor\" $pstyle>$init</td>";
						$qqn=TRUE;
						if($nump ==3 && $petit == "abrege")
						{
							$nump=0;
							$couls .= "</tr><tr>";
						}
					}
				}
				if($qqn==FALSE)
				{
					$init="";
					$couls .= "<td align=\"center\">??</td>";
				}
				$couls .= "</tr></table>";
				if($row["fait"]=="on") $fait=$this->lang["general_oui"];
				else $fait=$this->lang["general_non"];
				if($row["fait"]=="on") $fait="true";
				else $fait="false";
				$fait = "<img src='./images/$fait.png'>";
				$onmouseover=$this->qui_fait_quoi($row["np"], $row["nple"], $row["mp"], $row["mple"]);
				//$actColor = trim($this->liste_des_utilisateurs["$init"]["couleur"]);
				if(!$actColor) $actColor = "#ffff00";
				$lClass = ($lClass == "" || $lClass == "lignejour2" ) ? "lignejour1":"lignejour2";
				//$return .= "\n<tr $onmouseover style=\"cursor:pointer\" $pstyle bgcolor=\"$actColor\">";
				$return .= "\n<tr class='$lClass agendasize' $onmouseover style=\"cursor:pointer\" $pstyle>";
				if($petit != "abrege")
				{
					$return .="<td onclick=\"openDl('{$row["id"]}')\">";
				//	$return .= "la couleur vaut ", $this->color_all["$init"];
					if($row["fait"] != "on") $return .= $warning;
					$return .= "&nbsp;</td>";
				}
				$return .="<td onclick=\"openDl('{$row["id"]}')\">$couls</td>";
				if($petit != "abrege") $return .= "<td class='agendasize' onclick=\"openDl('{$row["id"]}')\">".$this->mysql_to_print($row["date_fin"], "%d.%m.%Y")."</td>";
				$return .= "<td class='agendasize' onclick=\"openDl('{$row["id"]}')\">{$row["libelle"]}</td><td class='agendasize' style=\"text-align:right\"><a class=\"duplicate\" name=\"#\" onclick=\"copyDl('{$row["id"]}')\">[+]</a></td>";
				$select=explode("\n", "{$_SESSION["optionGen"]["delais_type"]}");
				$testval=false;
				if($date_jour == $row["date_fin"] || $where_cond != "=")
				{
					foreach($select as $option)
					{
						list($abrev, $nom)=preg_split("#,#", $option);
						if($row["priorite"]==$abrev)
						{
							$testval = true;
							if($abrev == "1" && $petit != "abrege") $class = "class = \"attention_bg agendasize\"";
							elseif($abrev == "1" && $petit == "abrege") $class = "class = \"attention agendasize\"";
							else $class = "class = \"agendasize\"";
							$nom = $this->smart_html($nom);
							if($petit == "abrege") $nom = "<i>$nom</i>";
							$return .= "<td $class onclick=\"openDl('{$row["id"]}')\">$nom</td>";
						}
					}
				}
				else
				{
					$nom = "<font color=orange>".$this->mysql_to_print($row["date_fin"], "%d.%m.%Y")."</font>";
					if($petit == "abrege") $nom = "<i>$nom</i>";
					$return .= "<td class='agendasize' onclick=\"openDl('{$row["id"]}')\">$nom</td>";
				}
// 				if(!$testval) $return .= "<td>&nbsp;</td>"; //TODO: ne comprends plus le sens de $testval
				if($petit != "abrege") $return .="<td class='agendasize' onclick=\"openDl('{$row["id"]}')\">$fait</td>";
				$return .="</tr>";
			}
			$return .= "</table>";
		}
		return $return;

	}
	
	function create_dragboxes()
	{
		$string="<script type=\"text/javascript\"><!-- SET_DHTML(";
		$premier=FALSE;
		foreach($this->liste_divs as $val)
		{
			$string .= ($premier == TRUE)? ", \"":"\"";
			$string .= $val;
			$string .= "\"";
			$premier = TRUE;
		}
		$string .= ");//--></script>";
		return $string;

	}
	
	function create_groups()
	{
		$return = "\nvar noms = new Array();";
		$return .= "\nvar inits = new Array();";
		foreach($this->liste_des_utilisateursgroupes() as $line)
		{
			list($groupname, $membres)=preg_split("#;#", $line);
			$groups["$groupname"]=explode(",", $membres);
		}
		foreach($this->liste_des_utilisateurs() as $line => $ar)
		{
			list($name, $init)=preg_split("#,#", $line);
			$init = substr($init, 0, 2);
			$pers["$init"]=$name;
		}
		
		$act_group="";
		$index=0;
		foreach($groups as $groupname => $array)
		{
			if($groupname != $act_group)
			{
				$act_group = $groupname;
				$xindex=0;
				$return .= "\nnoms[$index] = new Array();";
				$return .= "\ninits[$index] = new Array();";
				foreach($groups[$groupname] as $membre)
				{
					$init = trim($membre);
					$nom = $pers[$init];
					$return .= "\ninits[$index][$xindex] = '$init';";
					$return .= "\nnoms[$index][$xindex] = '$nom';";
					$xindex++;
				}
				$index ++;
			}
		}
		$return .= "\n\n\nfunction chgroup(index){\n
 
		var init = inits[index];
		var nom = noms[index];
		if (init != '')
		{
			opt=document.getElementById('pers');
			opt.options.length = init.length;
			for (i=0; i<init.length; i++)
			{
				opt.options[i].value = init[i];
				opt.options[i].text = nom[i];
			}
			document.monFormulaire.choixVille.options.selectedIndex = 0;
		}
		else
		{
			formulaire.choixVille.options.length = 1;
			formulaire.choixVille.options[0].value = 0;
			formulaire.choixVille.options[0].text = '-- choisissez une ville';
		}";
 		$return .= "\n}";
		return $return;
	}

}
?>
