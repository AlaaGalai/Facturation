<?php

class synchro extends prolawyer
{

	function synchro()
	{
		parent::__construct();
		$this->calname = strftime("Calendar_%d.%m.%Y_%Hh%M.ics", time());
		$this->ical    = "BEGIN:VCALENDAR";
		$this->ical   .= "\nPRODID:-//Etude//NONSGML Etude {$this->settings["version"]}//EN";
		$this->ical   .= "\nVERSION:2.0";
		$this->ical   .= "\nX-WR-CALNAME:Etude {$this->settings["version"]} - {$this->lang["agenda_title"]}";
	}	
	
	function addVevent($created, $uid, $modified, $description, $summary, $categories, $xpilotstat, $debut, $fin, $rrule)
	{
		if($description != $summary) $summary = "$summary ($description)";
		
		$this->ical .= "\nBEGIN:VEVENT";
		$this->ical .= "\nCREATED:$created";
		$this->ical .= "\nUID:$uid";
		$this->ical .= "\nSEQUENCE:0";
		$this->ical .= "\nLAST_MODIFIED:$modified";
		$this->ical .= "\nORGANIZER:Mailto:NoOrganizer@nowhere";
		$this->ical .= "\nDESCRIPTION:$description";
		$this->ical .= "\nSUMMARY:$summary";
		$this->ical .= "\nCLASS:PUBLIC";
		$this->ical .= "\nPRIORITY:3";
		foreach($categories as $categorie) if(trim($categorie) != "") $this->ical .= "\nCATEGORIES:$categorie";
		$this->ical .= "\nX-PILOTID:0";
		$this->ical .= "\nX-PILOTSTAT:1";
		if($rrule)  $this->ical .= "\nRRULE:$rrule";
		$this->ical .= "\nDTSTART:$debut";
		$this->ical .= "\nDTEND:$fin";
		$this->ical .= "\nEND:VEVENT";
	}
	
	function addVtodo($created, $uid, $modified, $summary, $categories, $xpilotstat, $debut, $fin, $todo_status)
	{
		if(!$debut) $debut = $created;
		
		$this->ical .= "\nBEGIN:VTODO";
		$this->ical .= "\nCREATED:$created";
		$this->ical .= "\nUID:$uid";
		$this->ical .= "\nSEQUENCE:0";
		$this->ical .= "\nLAST_MODIFIED:$modified";
		$this->ical .= "\nORGANIZER:Mailto:NoOrganizer@nowhere";
		$this->ical .= "\nSUMMARY:$summary";
		$this->ical .= "\nCLASS:PUBLIC";
		$this->ical .= "\nPRIORITY:3";
		foreach($categories as $categorie) if(trim($categorie) != "") $this->ical .= "\nCATEGORIES:$categorie";
		$this->ical .= "\nX-PILOTID:0";
		$this->ical .= "\nX-PILOTSTAT:1";
		$this->ical .= "\nDUE;VALUE=DATE:$fin";
		$this->ical .= "\nDTSTART;VALUE=DATE:$debut";
		$this->ical .= "\nPERCENT-COMPLETE:$todo_status";
		$this->ical .= "\nEND:VTODO";
	}
	
	function icalClose()
	{
		$this->ical .= "\nEND:VCALENDAR";
	}
	
	
	function getVevents()
	{
		$q="select * from rdv where rdv_pour like '%{$_SESSION["session_db"]}%' order by date_debut";
		$x=mysqli_query($this->mysqli, $q);
		while($r=mysqli_fetch_array($x))
		{
			if(!$r["date_debut"] || $r["date_debut"] == "0000-00-00") continue;
			if(!$r["date_fin"] || $r["date_fin"] == "0000-00-00") $r["date_fin"] = $r["date_debut"];
			if(!$r["nple"] || $r["nple"] == "0000-00-00") $r["nple"] = $r["date_debut"];
			if(!$r["mple"] || $r["mple"] == "0000-00-00") $r["mple"] = $r["nple"];
			$created	= $this->getFTime($r["nple"], "01:00:00");
			$modifed	= $this->getFTime($r["mple"], "01:00:00");
			$debut		= $this->getFTime($r["date_debut"], $r["heure_debut"]);
			$fin		= $this->getFTime($r["date_fin"], $r["heure_fin"]);
			$uid		= "Etude".$r["id"]."-".$created;
			$description	= $r["libelle"];
			$summary	= $r["libelle"];
			$categories=explode(",", $r["rdv_pour"]);
			if(!preg_match("#Ã#", $description)) $description = str_replace(",", "\,", utf8_encode($description));
			if(!preg_match("#Ã#", $summary)) $summary = str_replace(",", "\,", utf8_encode($summary));
			$rrule = (trim($r["repete"]) != "")? $this->getRRule($r["repete"], $r["date_debut"], $r["repete_fin"]):"";
			$this->addVevent($created, $uid, $modified, $description, $summary, $categories, $xpilotstat, $debut, $fin, $rrule);
		}
	}
	
	function getFTime($date, $time)
	{
		return str_replace("-", "", $date)."T".str_replace(":", "", $time);
	}
	
	function extractRepeatType($rType)
	{
		$lType=array("j"=>"DAILY", "s"=>"WEEKLY","m"=>"MONTHLYBYDATE", "a"=>"YEARLY");
		return $lType["$rType"];
	}
	
	function getRRule($rType, $debut, $fin)
	{
		$fin = ($fin != "0000-00-00" && trim($fin) != "")? ";UNTIL=".$this->getFTime($fin, "23:59:59"):"";
		$rType=$this->extractRepeatType($rType);
// 		$day = ($rType == "WEEKLY")? ";BYDAY=".$this->extractRepeatWeekdate($debut):"";
		return "FREQ=$rType$fin;INTERVAL=1$day";
	}
	
	function extractRepeatMonthdate($datetime)
	{
		if(preg_match("#-#", $datetime)) $datetime = $this->mtf_date($datetime);
		$monthdate=date("j", $datetime);
		return $monthdate;
	}
	
	function extractRepeatWeekdate($datetime)
	{
		if(preg_match("#-#", $datetime)) $datetime = $this->mtf_date($datetime);
		$monthdate=strftime("%u", $datetime);
		$ar=array("1" => "MO", "2" => "TU", "3" => "WE", "4" => "TH", "5" => "FR", "6" => "SA", "7" => "SU");
// 		echo "nous cherchons le mois correspondant à '$monthdate', soit ", $ar[$monthdate];
		return $ar[$monthdate];
	}
	
	function extract_repeat_day($datetime)
	{
		$monthday=date("w", $this->mtf_date($datetime));
		$monthday=str_replace("0", "MO",$monthday);
		$monthday=str_replace("1", "TU",$monthday);
		$monthday=str_replace("2", "WE",$monthday);
		$monthday=str_replace("3", "TH",$monthday);
		$monthday=str_replace("4", "FR",$monthday);
		$monthday=str_replace("5", "SA",$monthday);
		$monthday=str_replace("6", "SU",$monthday);
		
		$cur_day=date("j", $datetime);
		$num_day_of_month=1;
		while($cur_day>7)
		{
			$cur_day=$cur_day-7;
			$num_day_of_month++;
		}
		
		$monthday=$num_day_of_month.$monthday;
		return $monthday;
	}
	
	function extract_repeat_days($recur_data)
	{
		$test=64;
		$no_val=7;
		$val_repeat="";
		while($recur_data>0)
		{
			if($recur_data>=$test)
			{
				if($val_repeat!="") $val_repeat=$val_repeat.",";
				$val_repeat=$val_repeat."$no_val";
				$recur_data=$recur_data - $test;
			}
			$no_val=$no_val-1;
			$test=$test/2;
		}
		$val_repeat=str_replace("2", "MO",$val_repeat);
		$val_repeat=str_replace("3", "TU",$val_repeat);
		$val_repeat=str_replace("4", "WE",$val_repeat);
		$val_repeat=str_replace("5", "TH",$val_repeat);
		$val_repeat=str_replace("6", "FR",$val_repeat);
		$val_repeat=str_replace("7", "SA",$val_repeat);
		$val_repeat=str_replace("1", "SU",$val_repeat);
		return $val_repeat;
	}

}
?>
