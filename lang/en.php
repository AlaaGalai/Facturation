<?php
//module en.php
//traduit par Olivier Subilia (etudeav@users.sourceforge.net)
//traduit du module fr
//
//dernière modification le 29.05.2005
//
//
//
//
//
//Liste des touches d'accès rapide
//A : Cancel 
//A : Cancel              !!!!!!!!!!!!!!!!
//A : Display              !!!!!!!!!!!!!!!!
//D : Delete file 
//F : Find a record 
//G : Settings 
//H : HELP 
//I : Print 
//J : Journal 
//L : Address list 
//M : Modify the file 
//M : Modify              !!!!!!!!!!!!!!!!
//R : Search a file 
//S : List balances 
//T : Reconnect 
//V : Sales turnover 
//W : New file 
//
//
//
//Liste des variables

$langchoisie["adresses_index_h2"]="To find an address, please make use of the next formulary";
$langchoisie["adresses_index_nom"]="Name to find";
$langchoisie["adresses_index_rechercher"]="Search";
$langchoisie["adresses_index_title"]="Address search";
$langchoisie["adresses_insermodif_h2"]="Inserted changes";
$langchoisie["adresses_insermodif_title"]="Confirmation of insertion";
$langchoisie["adresses_modifier_adresse"]="Address";
$langchoisie["adresses_modifier_annuler"]="Cancel";
$langchoisie["adresses_modifier_annuler_accesskey"]="A";
$langchoisie["adresses_modifier_ccp"]="Bank account";
$langchoisie["adresses_modifier_cp"]="P.B.";
$langchoisie["adresses_modifier_fax"]="Fax";
$langchoisie["adresses_modifier_fonction"]="Function";
$langchoisie["adresses_modifier_h2"]="File nr";
$langchoisie["adresses_modifier_mail"]="Mail";
$langchoisie["adresses_modifier_modifie_par"]="Modified by";
$langchoisie["adresses_modifier_modifier"]="Modify";
$langchoisie["adresses_modifier_natel"]="Cellular";
$langchoisie["adresses_modifier_nom"]="Last name";
$langchoisie["adresses_modifier_note_par"]="Inserted by";
$langchoisie["adresses_modifier_pays"]="Country";
$langchoisie["adresses_modifier_prenom"]="First name";
$langchoisie["adresses_modifier_remarques"]="Notice";
$langchoisie["adresses_modifier_salut"]="Addressing";
$langchoisie["adresses_modifier_tel"]="Tel";
$langchoisie["adresses_modifier_title"]="File nr $id";
$langchoisie["adresses_modifier_titre"]="Title";
$langchoisie["adresses_modifier_type"]="Type";
$langchoisie["adresses_modifier_ville"]="City";
$langchoisie["adresses_modifier_zip"]="Zip";
$langchoisie["adresses_resultat_consulter"]="Consult / Modify";
$langchoisie["adresses_resultat_h2"]=" Search result";
$langchoisie["adresses_resultat_h3"]="To find an address, please make use of the next formulary";
$langchoisie["adresses_resultat_nom"]="Name to find";
$langchoisie["adresses_resultat_nouvelle_fiche"]="Create a new record";
$langchoisie["adresses_resultat_rechercher"]="Find a record";
$langchoisie["adresses_resultat_rechercher_accesskey"]="F";
$langchoisie["adresses_resultat_resultat"]="records found";
$langchoisie["adresses_resultat_supprimer"]="Delete";
$langchoisie["adresses_resultat_title"]="Search result";
$langchoisie["adresses_supprimer_confirm_annuler"]="Cancel";
$langchoisie["adresses_supprimer_confirm_dossier"]="File";
$langchoisie["adresses_supprimer_confirm_exploite"]="The record is used by the following files of";
$langchoisie["adresses_supprimer_confirm_h2"]="Delete the record nr";
$langchoisie["adresses_supprimer_confirm_supprimer"]="Delete";
$langchoisie["adresses_supprimer_confirm_title"]="Confirmation of deleting";
$langchoisie["adresses_supprimer_h2"]="Record deleted";
$langchoisie["adresses_supprimer_retour"]="Back";
$langchoisie["adresses_supprimer_title"]="Confirmation of deleting";
$langchoisie["afficher_operations_client"]="Client";
$langchoisie["afficher_operations_date"]="Date";
$langchoisie["afficher_operations_details"]="Details";
$langchoisie["afficher_operations_dossier"]="File";
$langchoisie["afficher_operations_entete_simple"]="Activities for file nr $id";
$langchoisie["afficher_operations_operation"]="Done";
$langchoisie["afficher_operations_soit"]="which means";
$langchoisie["afficher_operations_temps"]="Devoted time";
$langchoisie["afficher_operations_title"]="Activities for file nr $id (user: $session_utilisateur ; database: $session_avdb)";
$langchoisie["afficher_operations_total"]="Total";
$langchoisie["apropos_maintenance"]="Software maintained by";
$langchoisie["apropos_pour"]="for";
$langchoisie["apropos_programmation"]="Programmation";
$langchoisie["apropos_remerciements"]="Thanks";
$langchoisie["apropos_title"]="About Prolawyer Version 2.5.1";
$langchoisie["apropos_tout"]="Almost everything";
$langchoisie["apropos_toute_etude"]="The whole SQDF legal office for the ideas";
$langchoisie["apropos_traductions"]="Translation";
$langchoisie["config_config_action"]="Now choose the user name of the software's administration";
$langchoisie["config_config_creer"]="Create the user";
$langchoisie["config_config_h1"]="You did not configure Prolawyer";
$langchoisie["config_config_li1"]="You have the rights necessary in writing on the current directory.";
$langchoisie["config_config_li2"]="If your work on Linux, the file \"etude\" and all its subdirectories are owned by user \"apache\" and group \"apache\"";
$langchoisie["config_config_li3"]="You use a database of MySql type.";
$langchoisie["config_config_li4"]="You can create a database of any name and a table of any name in this base.";
$langchoisie["config_config_nom"]="Administrator's name";
$langchoisie["config_config_pwd"]="Administrator's password";
$langchoisie["config_config_root"]="Root username";
$langchoisie["config_config_rpwd"]="Root password";
$langchoisie["config_config_t1"]="To do that, you must start by indicating the name and the password of the administrator.";
$langchoisie["config_config_t2"]="Your must indicate a user (including password) who has the rights of administration of the MySQL base.";
$langchoisie["config_config_t3"]="I suppose that";
$langchoisie["config_config_title"]="Software's administration";
$langchoisie["config_config_verify"]="Check the password";
$langchoisie["config_create_partner_echoue"]="Deleting failure";
$langchoisie["config_create_partner_existe"]="Error ! Name or initials exist allready";
$langchoisie["config_create_partner_nul"]="Error ! Name and initials can't be blank";
$langchoisie["config_create_partner_title"]="Creating or deleting a partner";
$langchoisie["config_create_user_non_concorde"]="Passwords don't match";
$langchoisie["config_create_user_title"]="Gestion des utilisateurs";
$langchoisie["config_create_user_vide"]="Password can't be blank";
$langchoisie["config_login_h2"]="Please login to modify the configuration of Prolawyer";
$langchoisie["config_login_login"]="Connect";
$langchoisie["config_login_nom"]="Name";
$langchoisie["config_login_pwd"]="Password";
$langchoisie["config_login_remarque"]="Note: if you have just created an username, indicate it";
$langchoisie["config_modify_VAT"]="VAT rate";
$langchoisie["config_modify_VAT_f"]="Fixed VAT rate (if applicable)";
$langchoisie["config_modify_administrateur"]="Administrator";
$langchoisie["config_modify_ajout_assoc"]="You can add an user with the following form";
$langchoisie["config_modify_ajout_util"]="You can add a partner with the following form";
$langchoisie["config_modify_ancien"]="Old style";
$langchoisie["config_modify_assoc_init"]="Partner's initials (2 letters ex: \"os\")";
$langchoisie["config_modify_assoc_name"]="Partner's name";
$langchoisie["config_modify_associe"]="Partner";
$langchoisie["config_modify_compta"]="Accountant";
$langchoisie["config_modify_create"]="Create";
$langchoisie["config_modify_create_db"]="Create database";
$langchoisie["config_modify_create_db_text"]="You can create the database \"etude\" clicking here";
$langchoisie["config_modify_create_table"]="Créer la table";
$langchoisie["config_modify_create_table_text"]="You can create the table \"adresses\" clicking here";
$langchoisie["config_modify_currency"]="Currency";
$langchoisie["config_modify_db"]="Database ";
$langchoisie["config_modify_delete"]="Delete";
$langchoisie["config_modify_delete_the_user"]="Delete the user";
$langchoisie["config_modify_delete_user"]="Delete an user";
$langchoisie["config_modify_diag1"]="Database etude exists";
$langchoisie["config_modify_diag2"]="At least one partner exists";
$langchoisie["config_modify_diag3"]="Table adresses exists";
$langchoisie["config_modify_diag4"]="At least one user exists";
$langchoisie["config_modify_err1"]="Database doesn't exist";
$langchoisie["config_modify_err2"]="No partner is defined";
$langchoisie["config_modify_err3"]="Table adresse doesn't exist";
$langchoisie["config_modify_err4"]="No user is defined";
$langchoisie["config_modify_err_assoc"]="Table doesn't exist";
$langchoisie["config_modify_h2"]="Settings modification";
$langchoisie["config_modify_h3"]="Diagnostic";
$langchoisie["config_modify_help"]="HELP";
$langchoisie["config_modify_init"]="initials";
$langchoisie["config_modify_maj"]="Update database";
$langchoisie["config_modify_maj_others"]="Apply";
$langchoisie["config_modify_others"]="Others options";
$langchoisie["config_modify_parts"]="Following partners are registered";
$langchoisie["config_modify_reg_parts"]="Registered partners";
$langchoisie["config_modify_reg_utils"]="Registered users";
$langchoisie["config_modify_secretaire"]="Secretary";
$langchoisie["config_modify_title"]="Settings modification";
$langchoisie["config_modify_types_adresses"]="Types of addresses ";
$langchoisie["config_modify_types_comptes"]="Types of accounts";
$langchoisie["config_modify_update"]="Update";
$langchoisie["config_modify_util_name"]="Name";
$langchoisie["config_modify_util_pwd"]="Password";
$langchoisie["config_modify_util_type"]="Type";
$langchoisie["config_modify_util_verif"]="Check";
$langchoisie["config_modify_utils"]="Following users are registered";
$langchoisie["config_modify_warning"]="You must first create the table \"adresses\"";
$langchoisie["config_verify_title"]="Software settings";
$langchoisie["config_verify_warning"]="Authorization failed. Please try again";
$langchoisie["creer_client_accepter"]="Accept";
$langchoisie["creer_client_adresse"]="Address";
$langchoisie["creer_client_alternative"]="To create a new client, use the form which follows.";
$langchoisie["creer_client_creer_client"]="Create a client";
$langchoisie["creer_client_fax"]="Fax";
$langchoisie["creer_client_fonction"]="Function";
$langchoisie["creer_client_h11"]="Choice of the new client (2ème étape)";
$langchoisie["creer_client_h12"]="Create a new file (2ème étape)";
$langchoisie["creer_client_h21"]="Stage 2: Confront with the found data";
$langchoisie["creer_client_h22"]="Stage 2: Confront with the found data";
$langchoisie["creer_client_h3"]="Files of";
$langchoisie["creer_client_h41"]="The following people are <b>clients</b>";
$langchoisie["creer_client_h42"]="The following people are <b>opposing parties</b>";
$langchoisie["creer_client_h43"]="The following people are <b>not recorded</b>";
$langchoisie["creer_client_liste_recherche"]="You sought a client whose co-ordinates contain";
$langchoisie["creer_client_mail"]="Mail";
$langchoisie["creer_client_natel"]="Cellular";
$langchoisie["creer_client_nom"]="Last name";
$langchoisie["creer_client_pays"]="Country";
$langchoisie["creer_client_prenom"]="First name";
$langchoisie["creer_client_retour"]="Return to the preceding page";
$langchoisie["creer_client_tel"]="Tel";
$langchoisie["creer_client_title"]="Choice of client";
$langchoisie["creer_client_titre"]="Title";
$langchoisie["creer_client_trouves"]="Names founded";
$langchoisie["creer_client_ville"]="City";
$langchoisie["creer_client_zip"]="Zip";
$langchoisie["creer_dossier_cancel"]="Cancel";
$langchoisie["creer_dossier_h11"]="New client's search";
$langchoisie["creer_dossier_h12"]="Create a new file";
$langchoisie["creer_dossier_h21"]="Stage 1: Seek if the data correspond to a known client";
$langchoisie["creer_dossier_nom"]="Name to find";
$langchoisie["creer_dossier_recherche"]="Search";
$langchoisie["data_client_attente"]="On standby";
$langchoisie["data_client_avances"]="Advances";
$langchoisie["data_client_client"]="Client";
$langchoisie["data_client_date"]="Date";
$langchoisie["data_client_encaissements"]="Cashing";
$langchoisie["data_client_nature"]="File's nature";
$langchoisie["data_client_no"]="File nr";
$langchoisie["data_client_pa"]="Opposing party";
$langchoisie["data_client_prix"]="Price per hour";
$langchoisie["data_client_rentrees"]="Cash entries";
$langchoisie["data_client_resultat"]="File result";
$langchoisie["data_client_solde"]="Balance due";
$langchoisie["data_client_theorique"]="Theoretical";
$langchoisie["data_client_total"]="Theoretical total";
$langchoisie["data_client_tva"]="VAT";
$langchoisie["entete_adresses"]="Address list";
$langchoisie["entete_adresses_accesskey"]="L";
$langchoisie["entete_ca"]="Sales turnover";
$langchoisie["entete_ca_accesskey"]="V";
$langchoisie["entete_help"]="HELP";
$langchoisie["entete_help_accesskey"]="H";
$langchoisie["entete_journal"]="Journal";
$langchoisie["entete_journal_accesskey"]="J";
$langchoisie["entete_liste_soldes"]="List balances";
$langchoisie["entete_liste_soldes_accesskey"]="S";
$langchoisie["entete_modifier"]="Modify the file";
$langchoisie["entete_modifier_accesskey"]="M";
$langchoisie["entete_new"]="New file";
$langchoisie["entete_new_accesskey"]="W";
$langchoisie["entete_reconnect"]="Reconnect";
$langchoisie["entete_reconnect_accesskey"]="T";
$langchoisie["entete_search"]="Search a file";
$langchoisie["entete_search_accesskey"]="R";
$langchoisie["entete_settings"]="Settings";
$langchoisie["entete_settings_accesskey"]="G";
$langchoisie["entete_trash"]="Delete file";
$langchoisie["entete_trash_accesskey"]="D";
$langchoisie["general_acces"]="Direct access to the page number";
$langchoisie["general_from"]="from";
$langchoisie["general_non"]="no";
$langchoisie["general_oui"]="yes";
$langchoisie["general_records"]="records";
$langchoisie["general_soit"]="which means";
$langchoisie["general_to"]="to";
$langchoisie["index_admin"]="Software's administration";
$langchoisie["index_connect"]="Connect";
$langchoisie["index_h1"]="Welcome to Prolawyer";
$langchoisie["index_insert_data"]="Please insert your name and password for the connection to the database";
$langchoisie["index_nom"]="Name";
$langchoisie["index_not_config_1"]="You did not configure Prolawyer. Click";
$langchoisie["index_not_config_2"]="here";
$langchoisie["index_not_config_3"]="to continue";
$langchoisie["index_password"]="Password";
$langchoisie["index_reset"]="Restore";
$langchoisie["index_title"]="Page of connections to the activities's database";
$langchoisie["index_what_base"]="Database";
$langchoisie["index_what_lang"]="Language";
$langchoisie["liste_soldes_attente"]="On standby";
$langchoisie["liste_soldes_autres"]="Not classified";
$langchoisie["liste_soldes_deficit"]="Files with deficit of fees higher than";
$langchoisie["liste_soldes_dossiers"]="List files having";
$langchoisie["liste_soldes_h1"]="Balance list";
$langchoisie["liste_soldes_manque"]="Miss provision with height of";
$langchoisie["liste_soldes_nature"]="File's nature";
$langchoisie["liste_soldes_nom"]="Client's name";
$langchoisie["liste_soldes_numero"]="Nr";
$langchoisie["liste_soldes_rechercher"]="Search the files of the type";
$langchoisie["liste_soldes_soumettre"]="Search";
$langchoisie["liste_soldes_title"]="Balances list (user: $session_utilisateur ; database: $session_avdb)";
$langchoisie["liste_soldes_y_compris"]="Including the closed files";
$langchoisie["modifier_donnees_adresse"]="Address";
$langchoisie["modifier_donnees_annuler"]="Cancel";
$langchoisie["modifier_donnees_annuler_accesskey"]="A";
$langchoisie["modifier_donnees_autre"]="Others data";
$langchoisie["modifier_donnees_changer_client"]="Modify client";
$langchoisie["modifier_donnees_changer_pa"]="Modify opposing party";
$langchoisie["modifier_donnees_client"]="Client's data";
$langchoisie["modifier_donnees_cp"]="P.B.";
$langchoisie["modifier_donnees_date_archivage"]="Date of the file closing";
$langchoisie["modifier_donnees_date_ouverture"]="Date of the file opening";
$langchoisie["modifier_donnees_fax"]="Fax";
$langchoisie["modifier_donnees_fonction"]="Function";
$langchoisie["modifier_donnees_h11"]="File's finalization";
$langchoisie["modifier_donnees_h12"]="File's modification";
$langchoisie["modifier_donnees_h2"]="Please insert the data";
$langchoisie["modifier_donnees_imprimer_dossier"]="Print";
$langchoisie["modifier_donnees_imprimer_dossier_accesskey"]="I";
$langchoisie["modifier_donnees_mail"]="E-mail";
$langchoisie["modifier_donnees_modifier_client"]="Save client's data";
$langchoisie["modifier_donnees_modifier_dossier"]="Modify";
$langchoisie["modifier_donnees_modifier_dossier_accesskey"]="M";
$langchoisie["modifier_donnees_modifier_pa"]="Save opposing party's data";
$langchoisie["modifier_donnees_natel"]="Cellular";
$langchoisie["modifier_donnees_nature_mandat"]="File's nature";
$langchoisie["modifier_donnees_no_archive"]="Nr file closed";
$langchoisie["modifier_donnees_nom"]="Last name";
$langchoisie["modifier_donnees_pa"]="Opposing party's data";
$langchoisie["modifier_donnees_pays"]="Country";
$langchoisie["modifier_donnees_prenom"]="First name";
$langchoisie["modifier_donnees_prix"]="Price per hour";
$langchoisie["modifier_donnees_remarques"]="Remarks";
$langchoisie["modifier_donnees_tel"]="Tel";
$langchoisie["modifier_donnees_title"]="File data nr $no_dossier";
$langchoisie["modifier_donnees_titre"]="Title";
$langchoisie["modifier_donnees_type"]="Mandate type";
$langchoisie["modifier_donnees_ville"]="City";
$langchoisie["modifier_donnees_zip"]="Zip";
$langchoisie["modifier_op_h2"]="Inserted modification";
$langchoisie["modifier_op_title"]="Modification of the activities";
$langchoisie["operations_actions"]="Actions";
$langchoisie["operations_afficher"]="Display";
$langchoisie["operations_afficher_accesskey"]="A";
$langchoisie["operations_afficher_liste"]="Display the activities's list with ";
$langchoisie["operations_encaissements"]="Cash entries";
$langchoisie["operations_entete"]="Heading";
$langchoisie["operations_limiter"]="Limit to this period";
$langchoisie["operations_modifier"]="Modify";
$langchoisie["operations_nouveau"]="New";
$langchoisie["operations_operation"]="Activity";
$langchoisie["operations_resume"]="Summary";
$langchoisie["operations_supprimer"]="Delete";
$langchoisie["operations_total"]="Total";
$langchoisie["recherche_dossier_nom"]="Client's name";
$langchoisie["recherche_dossier_numero"]="File nr";
$langchoisie["recherche_dossier_recherche"]="Search";
$langchoisie["recherche_dossier_title"]="Welcome to Prolawyer";
$langchoisie["resultat_recherche_action"]="Action";
$langchoisie["resultat_recherche_coordonnees"]="Details about the client <br>(the last <b>name</b> is in bold)";
$langchoisie["resultat_recherche_criteres"]="Search according the following criterias";
$langchoisie["resultat_recherche_nature"]="File's nature";
$langchoisie["resultat_recherche_nocritere"]="No criteria";
$langchoisie["resultat_recherche_nodossier"]="File nr";
$langchoisie["resultat_recherche_nom"]="Client's name";
$langchoisie["resultat_recherche_nouvelle"]="Carry out a new search ?";
$langchoisie["resultat_recherche_pa"]="Opposing party";
$langchoisie["resultat_recherche_rechercher"]="Search";
$langchoisie["resultat_recherche_record"]="records";
$langchoisie["resultat_recherche_title"]="Search result (user: $session_utilisateur; database: $session_avdb)";
$langchoisie["resultat_recherche_titre"]="Title";
$langchoisie["resultat_recherche_trouves"]="I have found";
$langchoisie["supprimer_dossier_confirm_h11"]="Are you sure?";
$langchoisie["supprimer_dossier_confirm_h12"]="This activity can not be canceled";
$langchoisie["supprimer_dossier_confirm_title"]="Confirmation of destruction";
$langchoisie["supprimer_dossier_donnees"]="Client's data destroyed";
$langchoisie["supprimer_dossier_donnees_no"]="Client's data not destroyed";
$langchoisie["supprimer_dossier_op"]="Client's activities destroyed";
$langchoisie["supprimer_dossier_op_no"]="Client's activites not destroyed";
$langchoisie["supprimer_dossier_title"]="Confirmation of destruction";
$langchoisie["verify_en_cours"]="You currently work on the database $start_avdb";
$langchoisie["verify_nom"]="Client's name";
$langchoisie["verify_numero"]="File nr";
$langchoisie["verify_recherche"]="Search";
$langchoisie["verify_retry"]="New try";
$langchoisie["verify_sorry"]="Sorry, the user does not have the rights of access to the database or introduced a invalid password";
$langchoisie["verify_title"]="Welcome to Prolawyer";
$langchoisie["verify_welcome"]="Welcome";
?>
