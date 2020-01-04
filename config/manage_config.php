<?php
require_once("../inc/autoload.php");
session_start();
$doc=new prolawyer();

if(!class_exists(ZipArchive)) $doc->catchError("040-106::116", 1);
else $doc->catchError("040-126", 0);

$e = False;

if(! $doc->isError())
{
	$doc->file_list=array();
	$doc->config_walkdir($_SESSION["optionsPath"]);

	$zip = new ZipArchive();
	$f = tempnam(sys_get_temp_dir(), 'prolawyerbackup').".zip";
	if ($zip->open($f, ZipArchive::CREATE)===TRUE)
	{
		foreach($doc->file_list as $file => $mode)
		{
			if($mode == "f") $zip->addFile($file);
			if($mode == "d") $zip->addEmptyDir($file);
		}
		$zip->close();
		$n = "prolawyer_backup_" .$doc->univ_strftime("%Y-%m-%d_%H-%M-%S").".zip";
		header("Content-type: application/zip");
		header("Content-Disposition: attachment; filename=\"$n\"");
		readfile($f);
	}
	else($e = "Impossible d'ouvrir l'archive");
}
if($doc->isError() || $e)
{
	$doc->title();
	$doc->body();
	$doc->entete();
	if($e) echo $e;
	else echo $doc->echoError();
	$doc->close();
}
?>
