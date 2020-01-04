<?php

$defaultColumn = "nom";
$defaultOption["initiales"] = "00";

$defaultOption["currency"]="CHF";
$defaultOption["tx_tva"]=8.0;
$defaultOption["tx_f_tva"]=6.1;
$defaultOption["tx_var_tva"]="0.00=0.00;7.60=5.80;8.00=6.10";
$defaultOption["prix_defaut"]=350;

$defaultOption["ltype"][]="CLIENT";
$defaultOption["ltype"][]="PA";
$defaultOption["ltype"][]="TRIBUNAL";
$defaultOption["ltype"][]="MAGISTRAT";
$defaultOption["ltype"][]="AVOCAT";
$defaultOption["ltype"][]="AAB";
$defaultOption["ltype"][]="ADMIN";
$defaultOption["ltype"][]="DIVERS";
$defaultOption["ltype"][]="PRIVE";

$defaultOption["dossiers_type"][]="AJ,Assistance judiciaire";
$defaultOption["dossiers_type"][]="P,Privé";
$defaultOption["dossiers_type"][]="PJ,Protection juridique";
$defaultOption["dossiers_type"][]="L,LAVI";

$defaultOption["origine_mandat"][]="Confrères / notaires";
$defaultOption["origine_mandat"][]="Associés";
$defaultOption["origine_mandat"][]="Clients";
$defaultOption["origine_mandat"][]="Est déjà client";
$defaultOption["origine_mandat"][]="Site internet";
$defaultOption["origine_mandat"][]="Permanence OAV";
$defaultOption["origine_mandat"][]="Autres (préciser)";

$defaultOption["op_type"][]="Ouverture du dossier";
$defaultOption["op_type"][]="Téléphone";
$defaultOption["op_type"][]="Conférence";
$defaultOption["op_type"][]="Ecriture";
$defaultOption["op_type"][]="Lettre";
$defaultOption["op_type"][]="Vacation";
$defaultOption["op_type"][]="Audience";
$defaultOption["op_type"][]="Etude du dossier";
$defaultOption["op_type"][]="Tentative de joindre";

$defaultOption["ac_type"][]="Caisse";
$defaultOption["ac_type"][]="Banque";

$defaultOption["matiere_type"][]="T,Travail";
$defaultOption["matiere_type"][]="AS,Assurances sociales";
$defaultOption["matiere_type"][]="P,Pénal";
$defaultOption["matiere_type"][]="B,Bail";
$defaultOption["matiere_type"][]="Co,Construction";
$defaultOption["matiere_type"][]="C,Contrats";
$defaultOption["matiere_type"][]="Fa,Famille";
$defaultOption["matiere_type"][]="F,Fiscal";
$defaultOption["matiere_type"][]="E,Etrangers";
$defaultOption["matiere_type"][]="A,Administratif";
$defaultOption["matiere_type"][]="S,Successions";

$defaultOption["delais_type"][]="1,ddl";
$defaultOption["delais_type"][]="2,déjà prolongé";
$defaultOption["delais_type"][]="3,dl judiciaire";
$defaultOption["delais_type"][]="4,rappel simple";
$defaultOption["delais_type"][]="5,dl pa";

$defaultOption["rdv_type"][]="!,Audience";
$defaultOption["rdv_type"][]="*,rdv simple";

$defaultOption["lieux"][]="Conférence 1";
$defaultOption["lieux"][]="Conférence 2";
$defaultOption["lieux"][]="Conférence 3";

$defaultOption["tva_deb"]="1";
$defaultOption["ouverture"]="0:10";
?>
