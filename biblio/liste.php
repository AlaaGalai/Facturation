<?php
$file=file_get_contents("http://www.crobar.ch/bandes_dessinees_nouveautes.asp");
$preg=preg_match("@<td valign=\"top\" class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck\(this,5\);\">AGRIPPINE T03 - LES COMBATS D'AGRIPPINE NED </td>.*<td valign=\"top\">&nbsp;</td>.*<td valign=\"top\" class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck\(this,5\);\">BRETECHER/CLAIRE</td>.*<td valign=\"top\">&nbsp;</td>.*<td valign=\"top\" nowrap class=\"produits\" onmouseover=\"this.style.cursor='hand';\" onclick=\"javascript:checkuncheck\(this,5\);\">DARGAUD</td>.*</tr>@s", $regs);
echo $preg;
foreach($regs as $reg) echo $reg;
?>

