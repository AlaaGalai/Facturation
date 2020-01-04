<?php
require_once("../inc/autoload.php");
session_start();

$doc = new prolawyer;
$doc->zefixUser = "prolawyer@avocats-ch.ch";
$doc->zefixPassword = "Wztqtz4&";

$criterea = "CH24130133257";
$critereb = "nestle";

// $response  = $doc->searchZefix("getRegistryOfficesRequest", "");
// echo $doc->xmlformat($response["string"]);
// 
$responseb = $doc->searchZefix("searchByNameRequest", $critereb);

foreach($responseb["object"] as $node)
{
	$name = $node->children("ns2", true)->name;
	$chid = $node->children("ns2", true)->chid;
	$form = $node->children("ns2", true)->legalform->legalFormId;
// 	$chid = $node->children("ns2", true)->chid;
	$wlin = $node->children("ns2", true)->status;
	$chid = $node->children("ns2", true)->chid;
	$chid = $node->children("ns2", true)->chid;
	$chid = $node->children("ns2", true)->chid;
	echo "\n<brNom: $name; CHId: $chid";
	$responsea = $doc->searchZefix("getByCHidFullRequest", $criterea);
	foreach($responsea["object"] as $node)
	{
		$form = $node->children("ns2", true)->legalform->legalFormId;
	// 	$chid = $node->children("ns2", true)->chid;
		$wlin = $node->children("ns2", true)->webLink;
		$chid = $node->children("ns2", true)->chid;
		$chid = $node->children("ns2", true)->chid;
		$chid = $node->children("ns2", true)->chid;
		echo "\n<brNom: $name; CHId: $chid, forme: $form, weblink: <a href='$wlin'>link</a>";
	}
	echo $doc->xmlformat($responsea["string"]);
	die();
	
}

?>
