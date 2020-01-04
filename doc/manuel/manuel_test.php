<?php
session_start();
require("./fonctions.php");

//définition de fonctions particulières
function readsection($section_to_read){
global $lang_utilisee;
$data="./manuel/$lang_utilisee.php";
$select=file("$data");
$liste=join($select);
//echo "$liste<br><br>";
$tableau=explode("{", "$liste");
$n=1;
foreach($tableau as $section)
{
list($nom_section, $val_section)=split("}", $section);
$$nom_section=$val_section;
if($nom_section==$section_to_read) 
{
$return=$val_section;
}
}
return $return;
}

function readdatas($readsection){
global $langue;
$list_of_datas=explode("\n", "$readsection");
foreach ($list_of_datas as $line){
list($valeur, $fr, $de, $en, $it)=split("\t", $line);
if($$langue<>"") $$valeur=$$langue;
else $$valeur=$fr;
$datas["$valeur"]=htmlentities($$valeur);
}
$datas["courriel"]=$nom.".netmail@avocats-ch.ch";
return $datas;
}

function mise_en_page($texte, $global_string){
$array_mep=array("*" => "h2", "_" => "h3");
$test=0;
foreach($array_mep as $code=>$mep){
if(substr($global_string, 0, 1)=="$code") {
$test=1;
$texte="<br><$mep>$texte</$mep>";
if($code=="*") $code="\*";
$texte=ereg_replace("$code", "", "$texte");
}
}
//tableau
if(ereg("\|", "$global_string")) $test3=1;


if(substr($global_string, 0, 4)=="<div") $GLOBALS[test2]=1;
if(substr($global_string, 0, 5)=="</div") $test2=0;
if(substr($global_string, 0, 2)=="--"){
list($auteur, $titre_ouvrage, $suite)=split(",", $texte);
$texte="<img src=\"../images/bullet1.gif\" WIDTH=15 HEIGHT=13>&nbsp;<b>$auteur</b>, <i>$titre_ouvrage</i>, $suite";
$texte=ereg_replace("--", "", "$texte");
}
if(substr($global_string, 0, 3)=="<1>"){
$texte=ereg_replace("<1>", "", "$texte");
$texte="<IMG SRC=\"../images/bullet1.gif\" WIDTH=15 HEIGHT=13>&nbsp;".$texte;
}
if(substr($global_string, 0, 3)=="<2>"){
$texte=ereg_replace("<2>", "", "$texte");
$texte="<IMG SRC=\"../images/bullet2.gif\" WIDTH=15 HEIGHT=13>&nbsp;".$texte;
}
if(substr($global_string, 0, 3)=="<3>"){
$texte=ereg_replace("<3>", "", "$texte");
$texte="<IMG SRC=\"../images/bullet3.gif\" WIDTH=15 HEIGHT=13>&nbsp;".$texte;
}
if($test==0 AND $GLOBALS[test2]==0 AND $test3==0) $texte="<p id=corps>$texte</p>";
if($GLOBALS[test2]==1) $texte="<br>$texte";
if($test3==1){
$chaine="";
$tableau_cases=explode("|", "$texte");
foreach($tableau_cases as $case){
$chaine=$chaine."<td id=corps>$case</td>";
}
$texte="<tr>$chaine</tr>";
}

return $texte;
}



function readtextdatas($readsection_texte){
$readsection_global="";
$readsection_array=explode("\n", "$readsection_texte");
global $langue;
foreach ($readsection_array as $readsection_section){
list($fr, $de, $en, $it)=split("\t", "$readsection_section");
if($$langue!="") $readsection_details=$$langue;
else $readsection_details=$fr;

$readsection_details=trim($readsection_details);
$readsection_details=htmlentities($readsection_details);
$readsection_details=ereg_replace("&lt;", "<", "$readsection_details");
$readsection_details=ereg_replace("&gt;", ">", "$readsection_details");
$readsection_details=ereg_replace("&amp;", "&", "$readsection_details");
$readsection_details=mise_en_page("$readsection_details", "$readsection_section");
$readsection_details=trim($readsection_details);
$readsection_global=$readsection_global.$readsection_details;
}
return $readsection_global;
}


function liens($fichier){
global $langue;
$array_fichier=file("$fichier");
foreach($array_fichier as $line){

if(substr($line, 0, 1)=="*"){
$line="*".$line;
$line=ereg_replace("\*\*", "<h2>", "$line");
$line=ereg_replace("\*", "</h2>", "$line");
echo $line;
}

elseif(substr($line, 0, 1)=="_"){
$line="*".$line;
$line=ereg_replace("\*_", "<h3>", "$line");
$line=ereg_replace("_", "</h3>", "$line");
echo $line;
}

else {
list($img, $invar_lien, $lien_fr, $fr, $lien_de, $de, $lien_en, $en, $lien_it, $it)=split("\t", $line);
if($$langue=="") $nom_lien=$fr;
else $nom_lien=$$langue;
$nom_lien=htmlentities($nom_lien);
$nom_lien=trim($nom_lien);
$val_lien="lien_".$langue;
if($$val_lien=="") $valeur_lien=$lien_fr;
else $valeur_lien=$$val_lien;

$lien_complet=$invar_lien.$valeur_lien;
$lien_complet=ereg_replace("http://", "", $lien_complet);
echo "
<p id=corps><img src=\"../images/$img\">&nbsp;<a href=\"http://$lien_complet\" target=\"_blank\">$nom_lien</a></p>";
}
}
}


//fin des définitions


require("./title.php");
echo "</head>\n";
body();
$section = readsection("principal");
echo readtextdatas($section);
//manuel("$etude_lang_cookie");
echo "</body>\n";
echo "</html>";
?>