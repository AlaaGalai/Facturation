#!/bin/bash

function conversion()
{
	echo "Traitement de $1"
	if [[ `echo $1|egrep "\.inc\.php$"` ]];then
		v=this
	else
		v=$suf
	fi

	bak="$1.bak"
	target=$1
	cp "$1" "$bak" || exit 1
	echo "Traitement de $1"
	sed -ri "s/^<\?([[:space:]])*\$/<?php/g" "$target"
	
	sed -ri "s/mysql_query\(/mysqli_query(\$$v->mysqli, /g" "$target"
	sed -ri "s/mysql_(error|affected_rows|insert_id)\(/mysqli_\1(\$$v->mysqli/g" "$target"
	sed -ri "s/mysql_/mysqli_/g" "$target"
	sed -ri "s/MYSQL_/MYSQLI_/g" "$target"
	
	sed -ri "s/ereg\(\"([^\"]+)\"/preg_match(\"#\1#\"/g" "$target"
	sed -ri "s/\bsplit\(\"([^\"]+)\"/preg_split(\"#\1#\"/g" "$target"
	sed -ri "s/ereg_replace\(\"([^\"]+)\"/preg_replace(\"#\1#\"/g" "$target"
	sed -ri "s/univGetElementById/document.getElementById/g" "$target"
	
	diff $1 $bak
}

liste=""
suf=doc
while [[ $1 ]];do
	par=$1
	if [[ ${par:0:2} == "-s" ]];then
		suf=${par:2}
	else
		liste="$liste$par "
	fi
	shift
done

for f in $liste;do
	conversion $f
done
