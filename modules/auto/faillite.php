<?php
//$doc->tab_affiche();
echo "<form name=\"calculFaillite\" id=\"calculFaillite\" action=\"./modules.php\" method=POST>";
echo $doc->input_texte("nomcreance", 1).": ".$doc->split_date($doc->mDate, "_faillite");
echo "<br><br>\n";
echo $doc->table_open("border=0");
$casecheck = $_POST["print"] ? "":"<td>&nbsp;</td>";
echo "\n<tr>$casecheck<th align=left>{$doc->lang["dateCreance"]}</th><th align=left>{$doc->lang["creance"]}</th><th align=left>{$doc->lang["montantCreance"]}</th><th align=left>{$doc->lang["tauxInterets"]}</th><th align=left>{$doc->lang["montantInterets"]}</th></tr>";
echo $doc->input_hidden("moduleName", 1);
if(is_array($_POST["lines"]))
{
	foreach($_POST["lines"] as $num)
	{
		echo $doc->nLine($doc->lDate["$num"], $_POST["creance"]["$num"], $_POST["montant"]["$num"], $_POST["interets"]["$num"]);
	}
}
echo $doc->nLine("lastLine");
echo "<tr><td></td></tr>";
echo "<tr>";
if(!$_POST["print"]) echo "<th>&nbsp;</th>";
echo "<td colspan=2><b>&nbsp;</b></td><td align=right><b>".number_format($doc->totalCreance, 2, ".", "'")."</b></td><td>&nbsp;</td><td align=right><b>".number_format($doc->totalInterets, 2, ".", "'")."</b></td></tr>";
echo "<tr><td></td></tr>";
echo "<tr>";
if(!$_POST["print"]) echo "<th>&nbsp;</th>";
echo "<td colspan=4><b>{$doc->lang["afficher_operations_total"]}</b></td><td align=right><b>".number_format($doc->totalCreance+$doc->totalInterets, 2, ".", "'")."</b></td></tr>";
if(!$_POST["print"])
{
	echo "\n<tr><td>".$doc->button($doc->lang["config_modify_update"], "<id>toto")."</td><td class=button onclick=\"var formul=document.getElementById('calculFaillite');formul.target='_blank';var inp=document.createElement('input');inp.type='hidden';inp.value='true';inp.name='print';but=document.getElementById('toto');but.parentNode.insertBefore(inp,but);formul.submit();formul.target='';inp.parentNode.removeChild(inp)\">{$doc->lang["operations_tva_pf"]}</td></tr>";
}
else
{
	echo "<tr><td class=button onClick=self.close()>{$doc->lang["general_fermer"]}</td></tr>";
}
echo $doc->table_close();
echo "</form>";
if(!$_POST["print"])
{
	$doc->form_global_var = $doc->futForm;
	echo $doc->form("modules/modules.php", "save", "", "", "serialize", "serialize", "on");
	echo "<form name='loadFaillite' action='./modules.php' method=POST enctype=\"multipart/form-data\"><input type=file name='monfichier'>";
	echo $doc->input_hidden("moduleName", True);
	echo "<button type=submit>load</button></form>";
}
?>
