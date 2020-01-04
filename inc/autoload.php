<?php

#Define prolawyer root directory
$autoPath = getcwd();
$cwd = getcwd();
$oldCwd = $cwd;
$autoDir = "";
while(!is_file("root.php"))
{
	$oldCwd = getcwd();
	chdir("..");
	$autoDir .= ".." . DIRECTORY_SEPARATOR;
	$autoPath = getcwd();
	if(getcwd() == $oldCwd) die("Can't find root.php. Aborting");
}
define("PROLAWYER_ROOT_DIR", $autoPath.DIRECTORY_SEPARATOR);
define("PROLAWYER_REL_DIR", $autoDir);
chdir($cwd);


#Define session name
if(is_file(PROLAWYER_ROOT_DIR."override/session_override.php"))
{
	require(PROLAWYER_ROOT_DIR."override/session_override.php");
}else{
	session_name("prolawyer");
}

#Define class override
if(is_file(PROLAWYER_ROOT_DIR."override/class_override.php"))
{
	require(PROLAWYER_ROOT_DIR."override/class_override.php");
}else{
	$class_override = array();;
}

#Define sysicon override
if(is_file(PROLAWYER_ROOT_DIR."override/images/prolawyer.png"))
{
	define("PROLAWYER_SYSICON", PROLAWYER_REL_DIR."override/images/prolawyer.png");
}else{
	define("PROLAWYER_SYSICON", PROLAWYER_REL_DIR."images/prolawyer.png");
}

#Define version specific
if(is_file(PROLAWYER_ROOT_DIR."specific/version.txt"))
{
	define("specific_version", file_get_contents(PROLAWYER_ROOT_DIR."specific/version.txt"));
}else{
	define("specific_version", "n/a");
}

#Register autoloading for classes
function __autoload($className)
{
	global $class_override;
	if(isset($class_override["$className"])) $className = $class_override["$className"];
	$autoClassPathBase = PROLAWYER_ROOT_DIR."inc".DIRECTORY_SEPARATOR;
	$autoClassPathOverride = PROLAWYER_ROOT_DIR."specific".DIRECTORY_SEPARATOR."inc".DIRECTORY_SEPARATOR;
	$className = strtolower($className .".class.php");
	foreach(array($autoClassPathBase, $autoClassPathOverride) as $path)
	{
		if(is_file($path.$className)) require($path.$className);
// 		print("<br>$path$className");

	}
}
spl_autoload_register("__autoload");

#Check override
$fileName = $_SERVER["SCRIPT_FILENAME"];
$file_override = PROLAWYER_ROOT_DIR."override/".preg_replace('#'.PROLAWYER_ROOT_DIR.'#', "", $fileName);
if(is_file($file_override))
{
	require($file_override);
	die();
}

?>
