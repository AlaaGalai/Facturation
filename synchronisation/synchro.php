<?php
require_once("../inc/autoload.php");
session_start();

$sync = new synchro;
$sync->getVevents();
$sync->icalClose();

if(!$_POST["mode"]) $_POST["mode"] = "attach";
if($_POST["mode"] == "attach")
{
	header("content-type:text/calendar");
	header("Content-Disposition: inline; filename={$sync->calname}");
	echo $sync->ical;
}

if($_POST["mode"] == "send")
{
	$sync->send_mail("$adresse", "$nom", "$autrenom", $sync->calname);
}
die();



//les lignes commentées qui suivent sont pour vérifier l'agenda.
//echo "<html><head><META http-equiv=\"Content-Type\" content=\"text/html; charset=UTF-8\">
//</head><body><br><strong><em>Enregistrement n° $nb_enregistrement</em></strong><br>";


	foreach($row as $nom=>$val){
		$val=utf8_encode($val);
		$val=oter_ln($val);
		$$nom=$val;
	}
	$cat=$valeur["$cal_login"];

	//voir si le rendez-vous est récurent
	$test_recur=0;
	$rrule="";
	$stop="";
	$val_repeat_2="";
	$recur_type="";
	$recur_type_2="";
	$query_rec="select * from phpgw_cal_repeats where cal_id like '$cal_id'";
	//echo $query_rec;
	$exec_query_rec=mysqli_query($synchro->mysqli, $query_rec);
	while($row_bis=mysqli_fetch_array($exec_query_rec)){
		$test_recur=1;
		if($test_recur==1){
		foreach($row_bis as $nombis=>$valbis){
			$valbis=utf8_encode($valbis);
			$valbis=oter_ln($valbis);
			$$nombis=$valbis;
		}
		//gestion des jours de répétition
		$recur_type_2=extract_repeat_type($recur_type);
		if($recur_type_2=="WEEKLY") $val_repeat_2=";BYDAY=".extract_repeat_days($recur_data);
		elseif($recur_type_2=="MONTHLYBYDATE"){
		$val_repeat_2=";BYMONTHDAY=".extract_repeat_monthdate($datetime);
		$recur_type_2="MONTHLY";
		}
		elseif($recur_type_2=="MONTHLYBYDAY"){
		$val_repeat_2=";BYDAY=".extract_repeat_monthday($datetime);
		$recur_type_2="MONTHLY";
		}
		else $val_repeat_2="";
		if($recur_interval==0) $recur_interval=1;
		if($recur_enddate>0) $recur_enddate=";UNTIL=".date("Ymd\THis", $recur_enddate);
		else $recur_enddate="";
		$rrule="
RRULE
 :FREQ=$recur_type_2".$recur_enddate.";INTERVAL=$recur_interval".$val_repeat_2;
	}
	}


?>