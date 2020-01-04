#!/bin/bash
if [[ $1 ]];then
	db=$1;
else
	db=prolawyer
fi

suf=`date +%Y-%m-%d-%H-%M-%S`
while [[ true ]];do
	echo "use $db"|mysql && break
	echo "show databases" |mysql |sed -r "s/Database/\nBases:/g"
	echo ""
	echo -n "Nom de la base: "
	read db
done

echo "Sauvegarde de $db..."
mysqldump --databases $db --skip-extended-insert>dump-$db-${suf}.sql
