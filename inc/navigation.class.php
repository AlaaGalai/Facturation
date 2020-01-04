<?php
class navigation extends prolawyer
{

	function navigation()
	{
		parent::__construct();
		$this->webdav =  $_SESSION["optionGen"]["use_webdav"];
	}
	
	function browsedir($dir=".", $newpage="", $mode="2", $dir_action="", $file_action="")
	{
		//mode:[y]x
		// x => dossiers
		// y => fichiers
		//0 = ne pas lire, 1 = afficher seulement, 2 = lien cliquable, 3 = click in browser.
		
		if($_GET["display"])
		{
			if($this->webdav)
			{
// 				echo "<br>{$_SESSION["optionGen"]["racine"]}";
// 				echo "<br>{$_SESSION["optionGen"]["racine_webdav"]}";
// 				echo "<br>''$dir''";
// 				echo "<br>";
				$webdavFile = preg_replace("#{$_SESSION["optionGen"]["racine"]}#", "{$_SESSION["optionGen"]["racine_webdav"]}", $dir);
				if(substr($webdavFile, 0, 1) == "/") $webdavFile = substr($webdavFile, 1);
// 				$webdavFile = preg_replace("#webdav://prolawyer#", "prolawyer/webdav", $webdavFile);

				//on ouvre le fichier directement dans le webdav
				$lien = $webdavFile;
// 				$lien = preg_replace("#^webdav#", "vnd.sun.star.webdav", $lien);
// 				$lien = "vnd.sun.star.webdav://$webdavFile";


				$lien = html_entity_decode($lien);
				header("Location:$lien");
				die($lien);
			}
			
			$c = file_get_contents($dir);
			$m = $this->mimeGet($dir);
// 			echo "toto";
			header("Content-Disposition: attachment; filename=\"$dir\"");
			header("Content-Type: $m");
			echo $c;
			die();
		}
		if($_REQUEST["mode"]) $mode = $_REQUEST["mode"];
		$checked = $_GET["hidden"]? "":"&hidden=true";
		$noChecked = $_GET["hidden"]? "&hidden=true":"";
		$montre  = $_GET["hidden"]? "true":"false";
		if($newpage == "") $newpage=$_SERVER["PHP_SELF"];
		if($dir == ".") $dir = getcwd();
		if($dir == "..") $dir = dirname(getcwd());
		$dirs_list = array();
		$files_list = array();
		$mode="0000".$mode; //pour éviter une lecture depuis la fin qui ne donnerait rien
		$curdir=getcwd();
		$parent=dirname($dir);
		$opt_dir=substr($mode, -1, 1);
		$opt_file=substr($mode, -2, 1);
		$arrDirs = array();
/*		echo "$mode, $opt_dir, $opt_file<br>";*/
		if(is_dir($dir))
		{
			$readdir=scandir($dir);
			if(substr($dir, -1, 1) == "/" || substr($dir, -1, 1) == "\\") $dir=substr($dir, 0, -1);
			foreach($readdir as $scan)
			{
				if($scan == "..") continue;
				elseif(substr($scan, 0, 1) == "." && !$_GET["hidden"]) continue;
				$arrDirs[] = $scan;
			}
			natcasesort($arrDirs);
			array_unshift($arrDirs, "..");
			foreach($arrDirs as $scan)
			{
				if($scan == "..")
				{
					$newdir = $parent;
					$aff = "..";
				}
				else
				{
					$newdir = "$dir/$scan";
					$aff = $scan;
					if(preg_match("#Ã#", $aff)) $aff = utf8_decode($aff);
				}
				if(is_dir($newdir) && $scan != "." && !($scan == ".." && $dir == ""))
				{
					if(is_readable($newdir) && is_executable($newdir)) $img="folder.png";
					else $img="folder_locked.png";
					$affDir = (preg_match("#Ã#", $newdir))? utf8_decode($newdir):$newdir;
					if(!$dir_action) $dselect="window.opener.document.getElementById('chemin').value='$newdir';self.close()";
					if($opt_dir == "2" && is_readable($newdir) && is_executable($newdir)) $dirs_list[] =  "\n<tr><td width=20 style=\"cursor:pointer\"><img src=\"images/$img\" onclick=\"$dselect\"></td><td><a href=\"$newpage?dir=$affDir{$noChecked}&mode={$_REQUEST["mode"]}\">$aff</a></td></tr>";
					elseif($opt_dir == "1" || ($opt_dir == "2" && (!is_readable($newdir) || !is_executable($newdir)))) $dirs_list[] =  "\n<tr><td width=20><img src=\"images/$img\"></td><td>$aff $opt_dir</td></tr>";
				}
				if(is_file($newdir))
				{
					if(!$file_action) $dselect="window.opener.document.getElementById('chemin').value='$newdir';self.close()";
					$ext1=strtolower(substr($scan, -1, 3));
					$ext2=strtolower(substr($scan, -1, 4));
					if($ext1 == "txt" || $ext1 == "doc") $img="txt.png";
					if($ext1 == "ico" || $ext1 == "jpg" || $ext1 == "gif" || $ext2 == "jpeg" || $ext2 == "tiff") $img="image.png";
					else $img="doc.png";
					if($opt_file == "3") $files_list[] = "\n<tr><td width=20><img src=\"images/$img\"></td><td><a href=\"$newpage?dir=$newdir&display=true&mode={$_REQUEST["mode"]}\">$aff</a></td></tr>";
					elseif($opt_file == "2") $files_list[] = "\n<tr><td width=20><img src=\"images/$img\"></td><td><a href=\"file://$newdir\">$aff</a></td></tr>";
					elseif($opt_file == "1") $files_list[] =  "\n<tr><td width=20><img src=\"images/$img\"></td><td>$aff</td></tr>";
				}
			}
		}
		echo $this->table_open();
		echo "\n<tr><td style=\"cursor:pointer;background-color:e0e0e0\" onclick=\"window.opener.document.getElementById('chemin').value='$dir';self.close()\">$dir</td><td><a href=\"$newpage?dir=$dir{$checked}\"><img src=\"{$doc->settings["root"]}images/$montre.png\"></a></td></tr>";
		echo "\n<tr><td>&nbsp;</tr></tr>";
		echo $this->table_close();
		echo $this->table_open();
		foreach($dirs_list as $line) echo $line;
		foreach($files_list as $line) echo $line;
		echo $this->table_close();
	}
	
	function browseMbx($mailbox)
	{
		$parentArray = preg_split("#\.#", $mailbox);
		$count = count($parentArray);
		array_splice($parentArray, -1);
		$parent = implode(".", $parentArray);
		$newparent = imap_utf7_encode($parent);
		$imap = imap_open ($_SESSION["optionGen"]["racine_mbx"], $_SESSION["optionGen"]["user_mbx"], $_SESSION["optionGen"]["pass_mbx"]);
		$newbox = imap_utf7_encode($mailbox);
		$liste = imap_list($imap, $newbox, "*");
		$listeOk = $count > 2 ? array($parent):array();
		if($liste) foreach($liste as $el) $listeOk[] = imap_utf7_decode($el);
		sort($listeOk);
		echo $this->table_open();
		foreach($listeOk as $el)
		{
			$current = preg_split("#\.#", $el);
			$countC = count($current);
			if($countC < ($count + 2))
			{
				list(, $suf) = preg_split("#}#", $el);
				echo "\n<tr><td width=20 style=\"cursor:pointer\"><img src=\"images/true.png\" onclick=\"window.opener.document.getElementById('mailbox').value='$el';self.close()\"></td><td><a href=\"$newpage?mbx=$el\">$suf</a></td></tr>";
			}
		}
		echo $this->table_close();
	}
}
?>
