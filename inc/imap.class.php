<?php

class imap extends prolawyer
{

	function imap()
	{
		parent::__construct(False);
		$this->host="localhost";
		$this->port="143";
		$this->username="";
		$this->password="";
		$this->mailbox="INBOX";
		$this->nb="10";
		$this->sortcriteria = SORTDATE;
		if($_POST["sortcriteria"]) $this->sortcriteria = $_POST["sortcriteria"];
//  		if(!is_array($this->option_gen["imap"]) && !preg_match("#modify_options_perso#", $this->anchor)) header("location: {$this->settings["root"]}config/modify_options_perso.php");
		$cookie_account=($_COOKIE["account"])? $_COOKIE["account"] : NULL;
		$account=($_POST["account"])? $_POST["account"] : $cookie_account;
		$this->account = "imap".$account;
		if(trim($account) == "imap")
		{
			foreach($this->option_gen["imap"] as $ac_number => $ac_val)
			{
				$this->account = $ac_number;
				$_POST["account"] = preg_replace("#imap#", "", $account);
				break;
			}
		}
		
		if($_POST["account"])
		{
			setcookie("account", $_POST["account"], time()+2600000);
		}
		foreach($_SESSION as $nm => $val)
		{
			if(preg_match("#imap[0-9]+#", $nm))
			{
				$strict_nm=preg_replace("#imap#", "", $nm);
				echo "{$this->account} | $strict_nm";
				if($this->account == $strict_nm | $this->account == $nm)
				{
					list($username, $password, $accountname, $host, $port, $type) = preg_split("#,#", $val);
					$this->host=$host;
					$this->port=$port;
					$this->username=$this->decode($username, $_SESSION["pwd"]);
					$this->password=$this->decode($password, $_SESSION["pwd"]);
					$_POST["mailbox"]=($_POST["mailbox"])?$_POST["mailbox"]:"INBOX";
					$_POST["mid"]=($_POST["mid"])?$_POST["mid"]:"1";
					if(!isset($_POST["partid"])) $_POST["partid"] = "all";
				}
			}
		}
	}
	
	function connection($host="", $port="", $username="", $password="", $mailbox="")
	{
		if($host == "") $host = $this->host;
		if($port == "") $port = $this->port;
		if($username == "") $username = $this->username;
		if($password == "") $password = $this->password;
		if($mailbox == "") $mailbox = $this->mailbox;
		
		$this->mbox="{"."$host:$port/tls"."}"."$mailbox";
		$this->imaphost="{"."$host:$port/tls"."}";
		@$this->cnx=imap_open($this->mbox, $username, $password);
		$text="\n<br>host =>$host;\n<br>port:$port;\n<br>username:$username;\n<br>password:****\n<br>";
		$text.= imap_last_error();
		$text .= "<br><br>".$this->list_accounts();
		if(!$this->cnx) die ("$text");
// 		echo "connecté à la boîte $mailbox";
	}
	
	function list_accounts()
	{
		$string	= "<form action=\"./courriel.php\" method=\"post\" name=formsetcurrent>";
		$string .= "&nbsp;<select name=account onChange=formsetcurrent.submit()>";
		
		foreach($_SESSION as $nm => $val)
		{
			if(preg_match("#imap[0-9]+#", $nm))
			{
				list($username, $pwd, $accountname, $host, $port, $type) = preg_split("#,#", $val);
				$selected="";
				$strict_nm=preg_replace("#imap#", "", $nm);
				if($_POST["account"] == $strict_nm)
				{
					$selected="selected";
				}
				echo "\n<option value=\"$strict_nm\" $selected>$accountname";
				if($strict_nm > $max) $max = $strict_nm;
				if($strict_nm - $lastnumber > 1) $next=$lastnumber + 1;
				$lastnumber=$strict_nm;
			}
		}
// 		foreach($this->option_gen["imap"] as $nm => $val)
// 		{
// 			$selected="";
// 			$strict_nm=preg_replace("#imap#", "", $nm);
// 			if($_POST["account"] == $strict_nm) $selected="selected";
// 			$strict_nm=preg_replace("#imap#", "", $nm);
// 			$string .= "\n<option value=\"$strict_nm\" $selected>{$this->option_gen["imap"]["$nm"]["accountname"]}";
// 			if($strict_nm > $max) $max = $strict_nm;
// 			if($strict_nm - $lastnumber > 1) $next=$lastnumber + 1;
// 			$lastnumber=$strict_nm;
// 		}
		
		$string .= "</select>";
		$string .= $this->button("{$this->lang["operations_selectionner"]}", "");
		$string .= "</form>";
		return $string;
	}
	
	function get_mailboxes()
	{
		$id="";
		$mbox=$this->mbox;
		$mbox_name=preg_replace("#{$this->imaphost}#", "", $mbox);
		
		$string = $this->list_accounts();
	 	$string .= "<li class=\"open\"><a href=\"#\" id=\"firstMessage\" class=\"mbselect\" onclick=\"sendData('mailbox', '$newname', './courriel_part.php', 'POST', 'headers');this.className='mbselect';if(lastMbSelected != this) lastMbSelected.className='mbnoselect';lastMbSelected=this;document.getElementById('body').innerHTML='<br><br><br><br><br>&nbsp;'\">".preg_replace("#{$this->imaphost}#", "", $mbox)."</a>";
	 	$this->nbr_mails = imap_num_msg($this->cnx);
	 	$list = imap_getmailboxes($this->cnx, "$mbox", "*");
	 	$mbox_array=array();
	 	for($x=0;$x<11;$x++) $compteur["$x"]=0;
	 	if(is_array($list))
	 	{
	 		asort($list);
	 		foreach($list as $val)
	 		{
	 			$temp_mbox=array();
	 			$act_mbox=$temp_mbox;
	 			$newname=preg_replace("#{$this->imaphost}#", "", $val->name);
	 			$temp_box=explode($val->delimiter, $newname);
	 			$number=count($temp_box);
	 			$compteur["$number"] ++;
	 			$id++;
	 			$actid="a".$id;
	 			$loffset=$number -1;
	 			unset($temp_box["$loffset"]);
	 			$remove=implode($val->delimiter, $temp_box);
	 			$tab="";
	 			for($x=0;$x<$number;$x++) $tab .= "\t";
	 			$postname = (imap_utf7_decode($newname)) ? imap_utf7_decode($newname): $newname;
	 			$postname=mb_convert_encoding(preg_replace("#$remove".$val->delimiter."#", "", $newname), "ISO_8859-1", "UTF7-IMAP");
 	 			$newli="<li label=\"$newname\"><a href=\"#\" onclick=\"sendData('mailbox', '$newname', './courriel_part.php', 'POST', 'headers');this.className='mbselect';if(lastMbSelected != this) lastMbSelected.className='mbnoselect';lastMbSelected=this;document.getElementById('body').innerHTML='<br><br><br><br><br>&nbsp;'\">$postname</a>";
 	 			if($number == $last_number)
 	 			{
 	 				$string .="\n$tab</li>\n$tab$newli";
 	 			}
 	 			if($number > $last_number) $string .="\n$tab<ul id=$actid>\n$tab$newli";
 	 			if($number < $last_number)
 	 			{
 	 				$string .="\n$tab</li>";
 	 				$diff=$last_number - $number;
 	 				for($x=0;$x<$diff;$x++)
 	 				{
 	 					$newnumber=$number - $x;
 	 					$newtab="";
 	 					for($y=0;$y<$newnumber;$y++) $newtab .= "\t";
 	 					$string .= "\n$newtab</ul>\n$newtab</li>";
 	 				}
 	 				$string .="\n$tab$newli";
	 			}
	 			$last_number=$number;
	 			$last_name=$newname;
	 		}
	 	}
	 	$string .= "\n<script language=JavaScript>lastMbSelected=document.getElementById('firstMessage')</script>";
	 	return $string;
	}
	
	function get_message_headers($range="", $sortcriteria="")
	{
		if(!$sortcriteria) $sortcriteria = $this->sortcriteria;
		$nb=$this->nb -1; //il y a 6 messages dans les messages 1 à 6, et non 5
		if($range == "")
		{
			$midto=$this->nbr_mails;
			if($midto >= $nb) $midfrom=$midto -$nb + 1;
			else $midfrom = 1;
		}else{
			list($midfrom, $midto) = preg_split("#:#", $range);
		}
		$cgt_last=($nb + 1 >= $this->nbr_mails)? $this->nbr_mails: $nb + 1;
		$messages_array_first=imap_sort($this->cnx, $sortcriteria, 0);
		$fmessage = $midfrom -1;
		$lmessage = $midto - $this->nbr_mails;

		$messages_array = ($lmessage != 0) ? array_slice($messages_array_first, $fmessage,  $lmessage) : array_slice($messages_array_first, $fmessage);
		
		$string = "\n<table width=\"100%\">";
		$string .= "\n<tr><th align=\"left\">{$this->lang["operations_nodossier"]}</th><th>&nbsp;</th>";
		$keyarray = array(SORTDATE => "{$this->lang["imap_courriel_date"]}", SORTSUBJECT => "{$this->lang["imap_courriel_sujet"]}", SORTFROM => "{$this->lang["imap_courriel_expediteur"]}");
		foreach($keyarray as $cletri => $nomcletri)
		{
			if($sortcriteria != $cletri) 
			{
				$string .="<th align=\"left\"><a href=\"#\" onclick=\"sendData('sortcriteria=$cletri&msgid=1:$cgt_last&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'headers')\">$nomcletri</a></th>";
// 				$string .= "<th align=\"left\">".$this->form("imap/courriel.php", $nomcletri, "", "button", "", "sortcriteria", $cletri, "msgid", "1:$cgt_last")."</th>";
// 				$string .="<";
			}else{
				$string .= "<th align=\"left\">$nomcletri</th>";
			}
		}
		$string .= "</tr>";
		$nomessage=$fmessage + 1;
		foreach($messages_array as $uniqueid)
		{	
			$headers_global=imap_fetch_overview($this->cnx, $uniqueid);
			foreach ($headers_global as $val)
			{
				$headers=imap_fetchstructure($this->cnx, $val->msgno);
				$attach=($headers->bytes) ? "": "<img src=\"../images/attach.png\">";
				if($val->msgno == $_POST["mid"])
				{
					$class= $val->seen ? "select":"select_bold";
				}
				else
				{
					$class= $val->seen ? "noselect":"noselect_bold";
				}
				$formname="form".$nomessage;
				$valsubject=$this->flatMimeDecode($val->subject);
				if(trim($valsubject) == "") $valsubject = $this->lang["imap_courriel_pas_sujet"];
				$valfrom=$this->flatMimeDecode($val->from);
//				$string .= "\n<tr class=$class onclick=\"$formname.submit()\">";
				$string .= "\n<tr class=\"$class\" onclick=\"sendData('msgid={$_POST["msgid"]}&mid={$val->msgno}&partid=all&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'body');lastSelected.className='noselect';lastSelected=this;this.className='select'\">";
				$string .= "<td>$nomessage</td>";
				//$string .= "<td>".$this->form("imap/courriel.php", $nomessage, "", "noselect", "$formname<td>", "mid", $val->msgno, "msgid", $_POST["msgid"], "mailbox", $_POST["mailbox"])."</td>";
				$string .= "<td>$attach</td><td>". $this->univ_strftime("%d.%m.%Y - %H:%M", strtotime($val->date))."</td><td>$valsubject</td><td>$valfrom</td></tr>\n";
				$nomessage ++;
	 		}
	 	}
 		$string .= "</table>";
 		
 		//affichage du choix des messages
 		//création des valeurs possibles
 		$previous=($midfrom > $nb) ? $midfrom - $nb : 1;
 		$next=($midto + $nb + 1 < $this->nbr_mails) ? $midto + $nb + 1: $this->nbr_mails;
 		$first=1;
 		$last=$this->nbr_mails;
 		$previousto=($previous + $nb < $last)? $previous + $nb : $last;
 		$nextfrom=($next - $nb > 1)? $next - $nb : 1;
 		$firstto=($first + $nb < $last)? $first + $nb : $last;
 		$lastfrom=($last - $nb > 1)? $last - $nb : 1;
 		$string .= "<table width=\"100%\"><tr>";
 		$string .= "<td class=\"noselect\"><a href=\"#\" onclick=\"sendData('msgid=$first:$firstto&mid={$_POST["mid"]}&sortcriteria=$sortcriteria&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'headers')\">&lt;&lt;</a></td>";
 		$string .= "<td class=\"noselect\"><a href=\"#\" onclick=\"sendData('msgid=$previous:$previousto&mid={$_POST["mid"]}&sortcriteria=$sortcriteria&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'headers')\">&lt;</a></td>";
 		//$string .= $this->form("imap/courriel.php<td align=left>", "<<", "", "", "", "msgid", "$first:$firstto", "mid", $_POST["mid"], "sortcriteria", "$sortcriteria", "mailbox", $_POST["mailbox"]);
//  		$string .= $this->form("imap/courriel.php<td align=left>", "<", "", "", "", "msgid", "$previous:$previousto", "mid", $_POST["mid"], "sortcriteria", "$sortcriteria", "mailbox", $_POST["mailbox"]);
 		
 		$string .= "<td align=\"center\"><table align=center><tr>";
 		for($x=1; $x<$this->nbr_mails; $x += ($nb + 1))
 		{
 			$nextx=($x + $nb < $this->nbr_mails)? $x + $nb : $this->nbr_mails;
 			if($y == 10)
 			{
 				$string .= "</tr><tr>";
 				$y = 0;
 			}
			$string .= "<td align=center class=\"noselect\"><a href=\"#\" onclick=\"sendData('msgid=$x:$nextx&mid={$_POST["mid"]}&sortcriteria=$sortcriteria&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'headers')\">$x&nbsp;</a></td>";
// 	 		$string .= $this->form("imap/courriel.php<td align=right>", "$x", "", "", "", "msgid", "$x:$nextx", "mid", $_POST["mid"], "sortcriteria", "$sortcriteria", "mailbox", $_POST["mailbox"]);
	 		$y ++;
 			
 		}
 		$string .= "</tr></table></td>";
 		
 		$string .= "<td align=right class=\"noselect\"><a href=\"#\" onclick=\"sendData('msgid=$nextfrom:$next&mid={$_POST["mid"]}&sortcriteria=$sortcriteria&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'headers')\">&gt;</a></td>";
 		$string .= "<td align=right class=\"noselect\"><a href=\"#\" onclick=\"sendData('msgid=$lastfrom:$last&mid={$_POST["mid"]}&sortcriteria=$sortcriteria&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'headers')\">&gt;&gt</a></td>";
//  		$string .= $this->form("imap/courriel.php<td align=right>", ">", "", "", "", "msgid", "$nextfrom:$next", "mid", $_POST["mid"], "sortcriteria", "$sortcriteria", "mailbox", $_POST["mailbox"]);
//  		$string .= $this->form("imap/courriel.php<td align=right>", ">>", "", "", "", "msgid", "$lastfrom:$last", "mid", $_POST["mid"], "sortcriteria", "$sortcriteria", "mailbox", $_POST["mailbox"]);
 		$string .= "</tr></table>";
	return $string;
	}
	
/*	  function buildparts ($struct, $pno = "") {
	$parttypes = array ("text", "multipart", "message", "application", "audio", "image", "video", "other");
	    switch ($struct->type):
	      case 1:
	        $r = array (); $i = 1;
	        foreach ($struct->parts as $part)
	          $r[] = $this->buildparts ($part, $pno.".".$i++);
	 
	        return implode (", ", $r);
	      case 2: 
	        return "{".$this->buildparts ($struct->parts[0], $pno)."}";
	      default:
	        return '<a href="?p='.substr ($pno, 1).'">'.$parttypes[$struct->type]."/".strtolower ($struct->subtype)."</a>";
	    endswitch;
	  }*/
 

   	function get_structure($mid="0", $create_attachment=FALSE, $level="")
	{
		@$structure=imap_fetchstructure($this->cnx, $mid);
//  		$test="<tr><td>get_structure requiert get-all_parts(structure, $mid, \"\", $create_attachment)</td></tr>";
		$string = "<table border=2>$test";
		$string .= $this->get_all_parts($structure, $mid, $level, $create_attachment);
		$string .= "</table>";
//    $struct = $structure;
//    echo $this->buildparts ($struct);
		if($this->first_html_partid && $this->first_partid)
		{
			
			if (substr($this->first_partid, 0, -2) != substr($this->first_html_partid, 0, -2))
			{
				$x=array($this->first_html_partid, $this->first_partid);
				natcasesort($x);
				$x=array_values($x);
				$this->first_html_partid = $x["0"];
				$this->first_partid = $x["0"];
			}
		}
		
		return $string;
	}
	
	function get_nice_headers($struct, $style="default")
	{
		$from=$struct->from["0"];
		$openpar=$from->personal ? " (":"";
		$closepar=$from->personal ? ")":"";
		$adresse_from=$this->flatMimeDecode($from->personal)."$openpar<a href=\"mailto:".$from->mailbox."@".$from->host."\">".$from->mailbox."@".$from->host."</a>$closepar";
		$to=$struct->to;
		$to_array=array();
		$adresse_to = "";
// 		echo nl2br(print_r($to, TRUE));
		foreach($to as $index => $val) if(!in_array($index, $to_array)) $to_array[] = $index;
		foreach($to_array as $index)
		{
			$openpar=$to[$index]->personal ? "(":"";
			$closepar=$to[$index]->personal ? ")":"";
			$virg=$adresse_to?", ": "";
			$adresse_to .= $virg.$this->flatMimeDecode($to[$index]->personal)." $openpar<a href=\"mailto:".$to[$index]->mailbox."@".$to[$index]->host."\">".$to[$index]->mailbox."@".$to[$index]->host."</a>$closepar";
		}
		
		switch($style)
		{
		case("default"):
			$return  = $this->table_open("width=100% border=2");
			$return .= "<tr style=\"background-color:808080\"><td colspan=\"2\">".$this->flatMimeDecode($struct->subject)."</td></tr>";
			$return .= "<tr><td class=\"messageline\"><b>{$this->lang["imap_courriel_from"]}&nbsp;:</b></td><td class=\"messageline\">$adresse_from</td></tr>";
			$return .= "<tr><td class=\"messageline\"><b>{$this->lang["imap_courriel_to"]}&nbsp;:</b></td><td class=\"messageline\">$adresse_to</td></tr>";
// 			echo nl2br(print_r($from, TRUE));
// 			$this->tab_affiche($struct);
// 			$return .= "<tr><td>{$this->lang["imap_courriel_to"]}&nbsp;:&nbsp;".$this->flatMimeDecode($struct->toaddress)."</td></tr>";
			$return .= $this->table_close();
			break;
		}
		return $return;
	}
	
	function get_message_body($mid="", $partid="", $encoding="", $subtype="", $nonl2br="", $type="", $name="", $disposition="", $nice_headers=TRUE)
	{
// 		$att_string = (!$partid) ? "\n<br>".$this->get_structure($mid, TRUE):"";
		$return = "";
// 		$return = "mid=$mid, partid=$partid, encoding=$encoding, subtype=$subtype, nonl2br=$nonl2br, type=$type, name=$name, disposition=$disposition, nice_headers=$nice_headers";
		if($partid) $actid=$partid;
		if(($partid == "all") && $nice_headers)
		{
			$struct=imap_headerinfo($this->cnx, $mid);
			$return .= $this->get_nice_headers($struct);
// 			$test=(partid='$partid' && nice_headers=$nice_headers);
 	 		echo "\n<table border=1><tr><td>$test";
// 			echo nl2br(print_r(imap_mime_header_decode($this->get_message_body($mid, "0")), TRUE));
		}
// 		$partid="0";
//		$return .= "<br>type= $type, nonl2br=$nonl2br";
		if($this->option_gen["plain_html"] == "plain")	
		{
			if(!$encoding) $encoding = $this->first_encoding;
			if(!$subtype) $subtype = $this->first_subtype;
			if($partid == "all") $partid = $this->first_partid;
		}else{
			if(!$encoding) $encoding = (isset($this->first_html_encoding)) ? $this->first_html_encoding : $this->first_encoding;
			if(!$subtype) $subtype = (isset($this->first_html_subtype)) ? $this->first_html_subtype : $this->first_subtype;
			if($partid == "all") $partid = (isset($this->first_html_partid)) ? $this->first_html_partid : $this->first_partid;
		}
		
		if($type == "3") $tname = "application";
		if($type == "4") $tname = "audio";
		if($type == "5") $tname = "image";
		if($type == "6") $tname = "video";
		if($type == "7") $tname = "other";
		if($partid != "source") $body=imap_fetchbody($this->cnx, $mid, $partid);
		else $body=imap_fetchbody($this->cnx, $mid, "0").imap_body($this->cnx, $mid);
		if($type == "0") $body=$this->repair_broken_lines($body);
// 		echo "<br>partie $partid du message $mid<br>";
// 		echo "<br>avant transfo, name vaut $name";
		if($encoding == "3") $body=base64_decode($body);
		if($encoding == "4") $body=quoted_printable_decode($body);
		if($name == "utf-8") $body=mb_convert_encoding($body, "ISO-8859-15","UTF-8");
		$print_name=$this->flatMimeDecode($name);
		$string="<a href=\"./standalone.php?mid=$mid&mailbox={$_POST["mailbox"]}&partid=$partid&encoding=$encoding&type=$tname&subtype=$subtype&disposition=$disposition&name=$name\">$print_name ($tname/$subtype)</a>";
		if($type == "5" AND ! $nonl2br)
		{
			$return .= "<br><img src=\"./standalone.php?mid=$mid&mailbox={$_POST["mailbox"]}&partid=$partid&encoding=$encoding&type=$tname&subtype=$subtype&disposition=$disposition&name=$name&nonl2br=nonl2br\"><br>$string";
		}
		elseif($type == "3" || $type == "4" || $type == "6" || $type == "7")
		{
			$return .= "<br>$string";
		}
		elseif($subtype == "HTML" || $nonl2br)
		{
			$return .= $body;
		}
		else
		{
			$return .= nl2br($body);
		}
//		$this->get_first_part($structure, $mid);
//  		echo "\n<br>partid vaut maintenant '$partid'";
		if($actid == "all")
		{
	 		$att_string = "\n<br>".$this->get_structure($mid, TRUE)."</td></tr></table>";
		}
		return $return.$att_string;
	}
	
/*	function display_structure($structure, $mid)
	{
		$parts=$structure->parts;
		$return .= count($parts), " (";
		if(count($parts)>0) foreach ($parts as $obj) 
		{
			$string="id=$obj->id, encoding=$obj->encoding, type=$obj->type, subtype=$obj->subtype, disposition=$obj->disposition, parties=".count($obj->parts)."; ";
		}
		$return .= "); encoding=";
		$return .= $structure->encoding;
		$bodys=imap_fetchbody($this->cnx, $mid, $partid);
		$return .= "\n<br>corps:<br>";
		
	}*/
	
	function get_all_parts($structure, $mid, $level="", $create_attachment=FALSE, $sublevel=FALSE)
	{
// 		$result = "<tr class=noselect><td><a href=\"#\" onclick=\"window.open('{$this->settings["root"]}/imap/standalone.php?mid=$mid&mailbox={$_POST["mailbox"]}&id=source','source','width=400,height=400,toolbar=no,directories=no,menubar=no=no,location=no,status=no')\">source</a></td></tr>";
// 		$result = "<tr class=noselect><td><a href=\"./standalone.php?mid=$mid&mailbox={$_POST["mailbox"]}&id=source\">source (level: $level)</a></td></tr>";
// 		$this->rtab_affiche($structure);
// 		$this->rtab_affiche($structure);
// 		echo "<br>create attachment vaut $create_attachment";
		$result = "";
		$partid=($sublevel) ? $level:"source";
		$append = " (<a href=\"./standalone.php?mid=$mid&mailbox=".rawurlencode($_POST["mailbox"])."&partid=$partid&source=on\">source</a>)";
		
		if (count($structure->parts)==0)
		{
			$nonl2br = ($structure->subtype == "PLAIN" && $structure->type == "0") ? "": "nonl2br";
			if(!$sublevel) $level="1";
			$level_writen=$this->lang["imap_courriel_message"]." non récurent";
			if($structure->type == "0") $tname = "text";
			if($structure->type == "1") $tname = "multipart";
			if($structure->type == "2") $tname = "message";
			if($structure->type == "3") $tname = "application";
			if($structure->type == "4") $tname = "audio";
			if($structure->type == "5") $tname = "image";
			if($structure->type == "6") $tname = "video";
			if($structure->type == "7") $tname = "other";
			if($structure->encoding == "0") $ename = "7bit";
			if($structure->encoding == "1") $ename = "8bit";
			if($structure->encoding == "2") $ename = "binary";
			if($structure->encoding == "3") $ename = "base64";
			if($structure->encoding == "4") $ename = "quoted printable";
			if($structure->encoding == "5") $ename = "other";
			if($structure->parameters) $dname=$structure->parameters[0]->value;
			elseif($structure->dparameters) $dname=$structure->dparameters[0]->value;
//			echo "<br>dparameters sont à {$structure->dparameters}; parameters sont à {$structure->dparameters}, de sorte que dname vaut $dname";
			$acttype = $structure->type;
			$disposition = $structure->disposition;
			$rename=$structure->encoding;
			$tname=strtolower($tname);
			$rtname=$structure->type;
			$sname=strtolower($structure->subtype);
			$string = "$tname/$sname ($ename)";
			$result .= "<tr class=\"noselect\">";
			//$result .= $this->form("imap/courriel.php<td>", "$level_writen", "", "", "", "id", $level, "encoding", $structure->encoding, "subtype", $structure->subtype, "mid", $mid, "msgid", $_POST["msgid"], "type", $structure->type, "name", $structure->dparameters[0]->value);
// 			if($create_attachment && $tname > 2) $lbody=$this->get_message_body($mid, $partid, $rename, $sname, $nonl2br, $rtname, $dname, $disposition);
			$result .= "<td><a href=\"#\" onclick=\"sendData('partid=$level&encoding=$rename&subtype=$sname&mid=$mid&msgid={$_POST["msgid"]}&type=$rtname&name=$dname&nonl2br=$nonl2br&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'body')\">$level_writen</a>$append</td>";
// 			$result .= "<td><a target=\"_blank\" href=\"./courriel_part.php?partid=&encoding=$ename&subtype=$sname&mid=$mid&msgid={$_POST["msgid"]}&type=$rtname&name=$dname&nonl2br=$nonl2br&mailbox={$_POST["mailbox"]}&body=on\">$level_writen</a>$append</td>";
			$result .= "<td>$string</td>";
			$result .= "</tr>";
// 			if($create_attachment) $result .= "<tr><td colspan=\"2\">$lbody</td></tr>";
			$this->first_encoding=$structure->encoding;
			$this->first_subtype=$structure->subtype;
			$this->first_partid=1;
			if($create_attachment && $acttype > "2")
			{
						$newlevel=($level)?"$level.$sublevel":$sublevel;
	// 					"<br><br>on requiert get_message_body avec un type de $acttype ($mid, $level, $rename, $sname, $nonl2br, $rtname, $dname, $disposition)<br><br>".
						$lbody=$this->get_message_body($mid, $level, $rename, $sname, $nonl2br, $rtname, $dname, $disposition, FALSE);
						$result2 .= "<tr><td colspan=\"2\">$lbody ($tname)</td></tr>";
			}
		}else{
			$result .= $this->get_all_parts_r($structure, $mid, $level, "", $create_attachment, $sublevel, $append);
		}

	return $result;
	}

	
	function get_all_parts_r($structure, $mid, $level="", $result2 = "", $create_attachment=FALSE, $sublevel=FALSE, $append=FALSE)
	{
// 		echo "<br>create attachment vaut $create_attachment";
		if($structure->subtype == "PLAIN")
		{
			if(!isset($this->first_encoding)) $this->first_encoding=$structure->encoding;
			if(!isset($this->first_subtype)) $this->first_subtype=$structure->subtype;
			if(!isset($this->first_partid)) $this->first_partid=$level;
		}
		if($structure->subtype == "HTML")
		{
			if(!isset($this->first_html_encoding)) $this->first_html_encoding=$structure->encoding;
			if(!isset($this->first_html_subtype)) $this->first_html_subtype=$structure->subtype;
			if(!isset($this->first_html_partid)) $this->first_html_partid=$level;
		}
		if($structure->subtype != "PLAIN") $nonl2br = "nonl2br";
		if($structure->type == "5") $nonl2br = FALSE;
		$level_writen=($structure->type != "1")?$level:$this->lang["imap_courriel_message"];
		$level_require = ($level === "")? "all":$level;
// 		$id=$level?$level:"0";
		if($structure->type == "0") $tname = "text";
		if($structure->type == "1") $tname = "multipart";
		if($structure->type == "2") $tname = "message";
		if($structure->type == "3") $tname = "application";
		if($structure->type == "4") $tname = "audio";
		if($structure->type == "5") $tname = "image";
		if($structure->type == "6") $tname = "video";
		if($structure->type == "7") $tname = "other";
		if($structure->encoding == "0") $ename = "7bit";
		if($structure->encoding == "1") $ename = "8bit";
		if($structure->encoding == "2") $ename = "binary";
		if($structure->encoding == "3") $ename = "base64";
		if($structure->encoding == "4") $ename = "quoted printable";
		if($structure->encoding == "5") $ename = "other";
		if($structure->parameters && is_array($structure->parameters)) $dname=$structure->parameters[0]->value;
		elseif($structure->dparameters) $dname=$structure->dparameters[0]->value;
//		$dname=$this->flatMimeDecode($dname);
// 		echo "<br>dparameters sont à {$structure->dparameters}; parameters sont à {$structure->parameters}, de sorte que dname vaut $dname";
		$acttype = $structure->type;
		$disposition = $structure->disposition;
		$rename=$structure->encoding;
		$tname=strtolower($tname);
		$rtname=$structure->type;
		$sname=strtolower($structure->subtype);
		$string = "$tname/$sname ($ename)";
		$string = strtolower($tname)."/".strtolower($structure->subtype)." ($ename)";
// 		$result2 .= "<tr class=\"noselect\">";
// 		if($create_attachment && $structure->type > 2) $lbody="<br><br>on requiert get_message_body($mid, $partid, $rename, $sname, $nonl2br, $rtname, $dname, $disposition)<br><br>".$this->get_message_body($mid, $partid, $rename, $sname, $nonl2br, $rtname, $dname, $disposition);
		if(!$create_attachment)
		{
			$result2 = "<tr>";
			if($structure->type == "2")
			{
				$result2 .= "<td>&nbsp;</td>";
			}else{
				$result2 .= "<td><a href=\"#\" onclick=\"sendData('partid=$level_require&encoding=$rename&subtype=$sname&mid=$mid&msgid={$_POST["msgid"]}&type=$rtname&name=$dname&nonl2br=$nonl2br&mailbox', '{$_POST["mailbox"]}', './courriel_part.php', 'POST', 'body')\">$level_writen</a> $append</td>";
		// 		$result2 .= $this->form("imap/courriel.php<td>", "$level_writen", "", "", "", "id", $level, "encoding", $structure->encoding, "subtype", $structure->subtype, "mid", $mid, "msgid", $_POST["msgid"], "type", $structure->type, "name", $structure->dparameters[0]->value);
			}
			$result2 .= "<td>$string</td>";
			$result2 .= "</tr>";
		}
		
/*		if($create_attachment) $result2 .= "<tr><td colspan=\"2\">yahoo '$lbody'($tname)</td></tr>";*/
//		if($structure->type == "3" || $structure->type == "4" || $structure->type == "5" || $structure->type == "6" || $structure->disposition == "ATTACHMENT") echo "<a href=\"./standalone.php?mid=$mid&id=$level_writen&encoding=$structure->encoding&type=$tname&subtype=$structure->subtype&disposition=$structure->disposition\" target=new></a>";
//		echo "<br><br>nouvelle partie: $level_writen<br>";
//		$this->tab_affiche($structure);
		if($create_attachment && $acttype > "2")
		{
					$newlevel=($level)?"$level.$sublevel":$sublevel;
// 					"<br><br>on requiert get_message_body avec un type de $acttype ($mid, $level, $rename, $sname, $nonl2br, $rtname, $dname, $disposition)<br><br>".
					$lbody=$this->get_message_body($mid, $level, $rename, $sname, $nonl2br, $rtname, $dname, $disposition, FALSE);
					$result2 .= "<tr><td colspan=\"2\">$lbody ($tname)</td></tr>";
		}
		if($create_attachment && $acttype == "2")
		{
					$newlevel=($level)?"$level.$sublevel":$sublevel;
// 					"<br><br>on requiert get_message_body avec un type de $acttype ($mid, $level, $rename, $sname, $nonl2br, $rtname, $dname, $disposition)<br><br>".
					$lbody=$this->get_structure($mid, FALSE, $level);
					$result2 .= "<tr><td colspan=\"2\">$lbody ($tname: mid=$mid, attach=FALSE et level=$level)</td></tr>";
		}
		if (count($structure->parts) >0)
		{	
			$sublevel="1";
			foreach($structure->parts as $subobj) 
			{
// 				echo "\n<br>en train de traiter {$structure->type}";
//nouvelle version
				if($structure->type == "2")
				{
					$newlevel = $level;
					if(!$create_attachment) $result2 .= $this->get_all_parts($subobj, $mid, $newlevel, $create_attachment, TRUE);
				}else{
					$newlevel=($level)?"$level.$sublevel":$sublevel;
					$result2 .= $this->get_all_parts_r($subobj, $mid, $newlevel, "", $create_attachment, FALSE);
					$lbody=$this->get_message_body($mid, $level, $rename, $sname, $nonl2br, $rtname, $dname, $disposition, FALSE);
//  					if($create_a ttachment && $structure->type >2) $result2 .= "<tr><td>on requiert get_message_body($mid, $level, $rename, $sname, $nonl2br, $rtname, $dname, $disposition, FALSE)</td></tr><tr><td colspan=\"2\">yahoo '$lbody'($tname)</td></tr>";
				}
				$sublevel ++;
//fin de la nouvelle version

//ancienne version
/*				if($structure->type == "2") $newlevel = $level;
				else $newlevel=($level)?"$level.$sublevel":$sublevel;
				$result2 .= $this->get_all_parts_r($subobj, $mid, $newlevel, "", $create_attachment);
				$sublevel ++;*/
//fin de l'ancienne version
			}
		}
	return $result2;
	}
	
	function flatMimeDecode($string) //(hans at lintoo dot dk)
	{
		$array = imap_mime_header_decode($string);
		$str = "";
		foreach ($array as $key => $part)
		{
			$str .= $part->text;
// 			$str .= " ( {$part->charset})";
		}
		return $str;
	}
	
	function repair_broken_lines($texte)
	{
		$return="";
		$arr=explode("<", $texte);
		foreach($arr as $offset =>$line)
		{
			if($offset == "0") $return .= $line;
			else
			{
				$return .= "<";
				$subarr=explode("\n", $line);
				$next_return=FALSE;
				foreach ($subarr as $subline)
				{
					$subline=trim($subline, "\n\r");
					$return .= $next_return ? "\n" : "";
					$return .= $subline; 
					if(preg_match("#>#", $subline)) $next_return=TRUE;
				}
			}
		}
		return $return;
	}

}
?>
