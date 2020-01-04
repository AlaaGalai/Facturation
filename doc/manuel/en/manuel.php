<html>
<head>
<style type="text/css">
li{text-align: justify}
body{text-align: justify; font-weight:normal; font-size:medium}
#b{list-style-type: decimal; font-size: large; font-weight: bolder}
#c{list-style-type: lower-alpha; list-style-position: inside; font-size: medium; font-weight: bold}
</style>
<title>Manuel du programme Etude 1.2</title>
</head>
<body>
<h1>Manuel du programme Etude 1.2</h1>
<a name="sommaire"><li id="b">Sommaire</a></li>
<ol>
<li id="b"><a href="./manuel.php#installation">Installation</a></li>
<li id="b"><a href="./manuel.php#configuration">Configuration</a></li>
<ol>
<li id="c"><a href="./manuel.php#preliminaires">Préliminaires</a></li>
<li id="c"><a href="./manuel.php#utilitaire">Utilitaire de 
configuration</a></li>
</ol>
<li id="b"><a href="./manuel.php#utilisation">Utilisation</a></li>
<ol>
<li id="c"><a href="./manuel.php#verify">Page de vérification</a></li>
<li id="c"><a href="./manuel.php#recherche">Page de recherche de 
dossier</a></li>
<li id="c"><a href="./manuel.php#operations">Opérations (time-sheet)</a></li>
<li id="c"><a href="./manuel.php#encaissements">Gestion des 
encaissements</a></li>

</ol>
</ol>
<ol>
<li id="b"><a name="installation">Installation</a></li><br>
<span id="c">
En premier lieu, vous devez avoir installé sur votre système un serveur
intégrant le language php. Le mieux est d'utiliser un serveur <a
href="http://www.apache.org" target="_new">apache</a>, qui intègre
automatiquement ce module. Il faut
ensuite avoir installé un serveur <a href="http://www.mysql.org"
target="_new">MySql</a> et avoir
créé l'administrateur principal de la base de données.<br>
Décompressez l'archive dans un répertoire du serveur apache. Vous pouvez donner
à ce répertoire le nom que vous voulez. Pour ma part, je pars de l'idée que ce
répertoire s'appelle "etude" pour la suite des explications. Vous disposez donc
des répertoires suivants:<br><br>
<ul>
<li>etude</li>
<li>etude/associes/</li>
<li>etude/config/</li>
<li>etude/exemples/</li>
<li>etude/config_files/</li>
<li>etude/manuel/</li>
</ul>
<br>
Vous devez vous assurer que vous disposez des droits suffisants pour l'accès en lecture comme
en écriture sur tous les fichiers. Dans ma configuration, vous pouvez utiliser la ligne de commande
suivante:<br>
$su<br>
password:<br>
#cd /votre_repertoire_dans_lequel_est_installe_etude_1.2<br>
#chown apache:apache -R etude_1.2<br>
#chmod -744 -R etude_1.2<br><br>
Tout est installé. Vous pouvez maintenant utiliser le programme.</span><br><br>
<li id="b"><a name="configuration">Configuration</a></li>
<ol>
<li id="c"><a name="preliminaires">Préliminaires</a></li>
Pour démarrer le programme, ouvrez dans un navigateur web la page
nom_de_votre_serveur/etude/index.php. Vous obtiendrez la page suivante:<br><br>
<table border="1">
  <tbody>
    <tr>
      <td>       <h1>
         Bienvenue dans le programme de gestion d'étude
      </h1>

<br>
<br>
Veuillez saisir votre nom et votre mot de passe pour la connection à la base de
donn&eacute;e

   <table>
      <tr>
         <td>
            Nom :
         </td>
         <td>
            <input type=text name="start_utilisateur">
         </td>
      </tr>
      <tr>
         <td>
            Mot de passe :
         </td>
         <td>
            <input type=password name="start_pwd">
         </td>
      </tr>
      <tr>
         <td>
            Quelle base de donn&eacute;e utiliser?
         </td>
         <td>
            <select name="start_avdb"><option value="">Avocat1
<option value="">Avocat2

            </select>
         </td>
      </tr>
      <tr>
         <td>
            <br>
            <input type=submit value="Se connecter"><input type=reset>
         </td>
      </tr>
   </table>
<u><font color=0000ff>Administration du programme</font></u></a>
 </td>
    </tr>
  </tbody>
</table>
<br>
Cliquez sur le lien "Administration du programme". La première fois que vous 
vous connectez, vous arrivez à l'écran
suivant:<br><br>
<table border="1">
  <tbody>
    <tr>
      <td><h1>Vous n'avez pas configur&eacute; Etude 1.2</h1>
<br>Pour ce faire, vous devez commencer par indiquer le nom et le mot de passe
de l'administateur.
<br><b>Vous devez indiquer un utilisateur (y compris mot de passe) qui a les
droits d'administration de la base MySQL.</b>
<br>Je pars de l'id&eacute;e que<br><br>
<ul>
<li>Vous avez les droits n&eacute;cessaires en &eacute;criture sur le
r&eacute;pertoire courant.<br><br></li>
<li>Si vous travaillez sur Linux, le dossier "etude" et tous ses
sous-r&eacute;pertoires ont pour propri&eacute;taire "apache" et pour groupe
"apache"<br><br></li>
<li>Vous utilisez une base de donn&eacute;e de type MySql.<br><br></li>
<li>Vous pouvez cr&eacute;er une base de donn&eacute;e de n'importe quel nom et
une table de n'importe quel nom dans cette base.</br><br></li>
</ul><b>Maintenant, choisissez le nom d'utilisateur pour l'administration du
programme</b><br>
<table>
<tr><td>Nom de l'administrateur&nbsp;:</td><td><input name=user></td></tr>
<tr><td>Mot de passe de l'administrateur&nbsp;:</td><td><input type=password
name=pwd></td></tr>
<tr><td>V&eacute;rifier le mot de passe&nbsp;:</td><td><input type=password
name=vpwd></td></tr>
<tr><td><input type=submit value="Cr&eacute;er l'utilisateur"></td></tr>
</table>
</td>
    </tr>
  </tbody>
</table>
<br>
Vous devez indiquer dans ce tableau le nom et le mot de passe d'une personne qui
a les droits nécessaires pour modifier la base MySql. Ces données sont stockées
dans le fichier etude/config/perm.php. Vous pouvez les modifier manuellement par
la suite en respectant la syntaxe du fichier.<br>
<br>
Si vous fournissez un utilisateur jouissant des droits nécessaires, vous passez
à l'écran de contrôle d'identification.<br><br>
<table border="1">
  <tbody>
    <tr>
      <td><h2>Veuillez vous identifier pour modifier la configuration d'Etude
1.2</h2>
(Remarque: si vous venez de cr&eacute;er ce nom, indiquez-le)
Nom&nbsp;:&nbsp;<input name="user"><br>
Mot de passe&nbsp;:&nbsp;<input type=password name="pwd">
<input type="submit" value="Login">
</td>
    </tr>
  </tbody>
</table>
<br><br>
Indiquez le mot de passe que vous venez de créer. Vous arrivez alors à l'écran
de configuration.<br><br>
<li id="c"><a name="utilitaire">Utilitaire de configuration</a></li>
Le module de configuration se présente comme suit:<br><br>
<table border="1">
  <tbody>
    <tr>
      <td><h2>Modification des options</h2>
<h3>Diagnostic</h3>
<table>

<tr><td><font color="ff0000">La base de donn&eacute;e etude n'existe 
pas</font></td><td><b><font color=C00000>ERROR</b></font></td></tr><tr><td><font 













color="ff0000">Aucun associ&eacute; n'est d&eacute;fini</font></td><td><b><font 
color=C00000>ERROR</b></font></td></tr><tr><td><font color="ff0000">Aucun 
utilisateur n'est d&eacute;fini</font></td><td><b><font 
color=C00000>ERROR</b></font></td></tr>
</table>

<h3>Base de donn&eacute "etude"</h3>
<form action="./create_db.php" method="post">
Vous pouvez cr&eacute;er la base "etude" en cliquant ici.<br>
<input type="submit" value="Cr&eacute;er la base">
</form>
<br>
<//bouton pour rajouter un associé//>
<h3>Associ&eacute;s enregistr&eacute;s</h3>
<form action="./create_partner.php" method="post">
Les associ&eacute;s suivants sont enregistr&eacute;s:<br><br>
Vous devez cr&eacute;er la base "etude" avant de modifier les 
associ&eacute;s<table>
<tr><td>Nom de l'associ&eacute;</td><td><input type="text" name="nom"></td></tr>
<tr><td>Initiales de l'associ&eacute; (2 lettres accol&eacute;es; ex: 
"os")</td><td><input type="text" name="init"></td></tr>
<input type="hidden" value="" name="nouvelle_liste">
</table>
</form>

<//bouton pour rajouter un utilisateur//>
<h3>Utilisateurs autoris&eacute;s &agrave; utiliser le programme</h3>
<form action="./create_user.php" method="post">
Les utilisateurs suivants sont enregistr&eacute;s:<br><br>
Vous devez cr&eacute;er la base "etude" avant de modifier les 
utilisateurs<table>
<tr><td>Nom de l'utilisateur</td><td><input type="text" name="nom"></td><td>Mot 
de passe</td><td><input type="password" size=8 
name="pwd"></td><td>V&eacute;rification</td><td><input type="password" size=8 
name="vpwd"></td></tr>
<tr><td>Type&nbsp;:</td><td><select name="type"><option 
value="administrateur">administrateur
<option value="associe">associe
<option value="secretaire">secretaire
<option value="compta">compta
<option value="destruction">destruction
</select></td></tr>
<input type="hidden" value="" name="nouvelle_liste">
</table>
</form>


<br>
<u><font color=0000ff>Retour au programme</font></u>
</td>
    </tr>
  </tbody>
</table>
<br><br>
L'écran commence par un diagnostic: avez-vous configuré correctement le 
programme&nbsp;?<br> Tant que vous n'avez pas créé la base de donnée nécessaire
(ce qui se fait
automatiquement en cliquant sur le bouton), vous ne pouvez pas utiliser les
autres options. Le programme doit impérativement créer une base de données
appelée "etude" dans laquelle il mettra toutes les données des utilisateurs. Si
cette base ne peut pas être créée automatiquement, c'est vraisemblablement un
problème de nom d'administrateur. Modifiez alors le fichier
etude/config/perm.php en indiquant un utilisateur et un mot de passe qui vous
permettent de créer la base de donnée voulue et réessayez.<br><br>
Dès que la base de donnée est créée, l'écran change et vous donne accès aux 
autres options.<br><br>
<table border="1">
  <tbody>
    <tr>
      <td><h2>Modification des options</h2>
<h3>Diagnostic</h3>
<table>

<tr><td>La base de donn&eacute;e etude existe</td><td><b><font 
color=00C000>OK</font></b></tr></td></tr><tr><td><font color="ff0000">Aucun 
associ&eacute; n'est d&eacute;fini</font></td><td><b><font 
color=C00000>ERROR</b></font></td></tr><tr><td><font color="ff0000">Aucun 
utilisateur n'est d&eacute;fini</font></td><td><b><font 
color=C00000>ERROR</b></font></td></tr>
</table>


<//bouton pour rajouter un associé//>
<h3>Associ&eacute;s enregistr&eacute;s</h3>
Les associ&eacute;s suivants sont enregistr&eacute;s:<br><br>
Vous pouvez rajouter un associ&eacute; en utilisant le formulaire 
suivant.<br><table>
<tr><td>Nom de l'associ&eacute;</td><td><input type="text" name="nom"></td></tr>
<tr><td>Initiales de l'associ&eacute; (2 lettres accol&eacute;es; ex: 
"os")</td><td><input type="text" name="init"></td></tr>
<input type="hidden" value="" name="nouvelle_liste">
<tr><td><input type="submit" value="Cr&eacute;er"></td></tr></table>

<//bouton pour rajouter un utilisateur//>
<h3>Utilisateurs autoris&eacute;s &agrave; utiliser le programme</h3>
Les utilisateurs suivants sont enregistr&eacute;s:<br><br>
Vous pouvez rajouter un utilisateur en utilisant le formulaire 
suivant.<br><table>
<tr><td>Nom de l'utilisateur</td><td><input type="text" name="nom"></td><td>Mot 
de passe</td><td><input type="password" size=8 
name="pwd"></td><td>V&eacute;rification</td><td><input type="password" size=8 
name="vpwd"></td></tr>
<tr><td>Type&nbsp;:</td><td><select name="type"><option 
value="administrateur">administrateur
<option value="associe">associe
<option value="secretaire">secretaire
<option value="compta">compta
<option value="destruction">destruction
</select></td></tr>
<input type="hidden" value="" name="nouvelle_liste">
<tr><td><input type="submit" value="Cr&eacute;er"></td></tr></table>


<br>
<u><font color=0000ff>Retour au programme</font></u>
</td>
    </tr>
  </tbody>
</table>
<br><br>
Vous devez définir deux types de personnes.<br><br>
Les <b>Associés enregistrés</b> sont les personnes dont on suit les opérations 
(dans ma conception, les avocats).<br>
Les <b>Utilisateurs</b> sont les personnes autorisées à se servir du 
programme.<br>
Il n'y a pas forcément de correspondance entre les deux. Prenons un bureau de 
notaires avec deux notaires, Me Bolomey et Me Milliquet, et leurs deux 
secrétaires, Mme Blanc et Mme Rouge. Il faudra enregistrer comme associés Me 
Bolomey et Me Milliquet. Il faudra en outre enregistrer comme utilisateurs les 
quatre personnes. Si Me Bolomey fait partie des associés enregistrés mais pas 
des utilisateurs, il ne pourra pas consulter ses propres opérations.<br><br>
Quant aux utilisateurs, il y en a de cinq types:<br>
<ul>
<li><b>Administrateur</b>: c'est celui qui a accès à toutes les fonctions du
programme. A utiliser avec précautions!</li>
<li><b>Associé</b>: groupe dont devraient faire partie les associés de l'étude.
Cela leur donne accès à toutes les fonctions d'utilisation sauf la destruction
de fichiers.</li>
<li><b>Secrétaire</b>: groupe dont devraient faire partie les secrétaires de
l'étude. Cela leur donne accès à toutes les fonctions des associés, mais pas au
résumé du chiffre d'affaire.</li>
<li><b>Compta</b>: groupe pour le comptable. En l'état, a les mêmes informations

que l'associé. Pourrait servir dans une modification du programme</li>
<li><b>Destruction</b>: indispensable. <b>Un utilisateur ordinaire ne peut pas 
détruire un fichier</b>, cela pour éviter les erreurs. Il faut donc créer un 
utilisateur destruction. Lorsqu'on veut détruire un fichier, on doit se 
déconnecter et se reconnecter en utilisant ce nom d'utilisateur spécifique.</li>
</ul>
<br><br>
Lorsque vous avez terminé la configuration, cliquez sur "Retour au programme". 
Vous revenez alors à l'écran de base. Vous pouvez vous connecter en saisissant 
le nom et le mot de passe <b>de l'utilisateur et non de l'associé</b> que vous 
venez de créer.<br><br><br><br>
</ol>
<li id="b"><a name="utilisation">Utilisation du programme</a></li>
<br>
<ol>
<li id="c"><a name="verify">Page de vérification</a></li>
Cette page vérifie si vous avez fourni les bonnes informations. Si tel est
le cas, vous êtes automatiquement redirigé sur la page de recherche. Dans le cas
contraire, vous devez vous annoncer à nouveau.<br>Il y a malheureusement un bug
dans la gestion des sessions sous PHP. Pour des raisons que je n'ai pas encore
comprises (mais qui paraissent tenir à PHP lui-même, si j'en crois la
documentation), il peut arriver que la gestion des sessions ne fonctionne
temporairement plus. Si tel est le cas, vous pouvez introduire n'importe quel
mot de passe, il sera toujours considéré comme faux. Dans ce cas, la seule
solution est de fermer toutes les fenêtres de navigateur et de les rouvrir pour
que le système marche à nouveau.<br><br>
<li id="c"><a name="recherche">Page de recherche</a></li>
Vous pouvez rechercher un dossier, soit par son numéro, soit par le nom ou le 
prénom du client. La recherche est insensible à la casse (ce qui veut dire 
qu'une recherche sur BolOM trouvera aussi bien BOLOMEY que bolomey), mais elle 
est en revanche sensible aux accents (une recherche de "se" trouvera "serviteur" 






mais pas "sérieux").<br><br>


<li id="c"><a name="resultat_recherche">Résultats de recherche</a></li>
La page se présente comme suit:<br>
<table border="1">
  <tbody>
    <tr>
      <td>Recherche selon les crit&egrave;res suivants:
<br>
Nom du client :  alfred<br> N° du dossier :  (pas de crit&egrave;re)<br><br>J'ai 





trouv&eacute; 2 enregistrements :<br><br>
</td></tr><tr><td><table cellspacing=10 align=left border=1>
<tr>
<th>N° dossier</th><th>Titre</th><th>Coordonn&eacute;es du client <br>(le 
<b>nom</b> est en gras)</th><th>Nature du dossier</th><th>Partie 
adverse</th><th>Action</th>
</tr><tr><td bgcolor=FF6060>1</td><td>Monsieur</td><td>Alfred <b>Bolomey</b>  La
Rippe 1234 Bottoflens</td><td></td><td></td><td><font 
color="0000ff"><u>S&eacute;lectionner</u></font></td></tr><tr><td>2</td><td>Monsieur</td><td>Alfred <b>Miliquet</b>  Ailleurs 1234 Bottoflens</td><td></td><td></td><td><font color="0000ff"><u>S&eacute;lectionner</u></font></td></tr></table></td></tr><tr><td><br>
<br>
Effectuer une nouvelle recherche ?
<br>
<table>
<tr>
<td width="100">Nom du client :</td><td><input type=texte name="nom_client"
value=""></td>
</tr>
<tr>
<td>N° de dossier :</td><td><input type=texte name="no_dossier" value=""></td>
</tr>
</table>
<input type=submit value="Rechercher">
</td>
    </tr>
  </tbody>
</table><br><br>
Les numéros de dossiers qui apparaissent en <font color="ff6060">rouge</font>
sont les dossiers qui sont déjà archivés.<br>
En cliquant sur le lien <font color="0000ff"><u>Sélectionner</u></font>, on 
accède au time-sheet du client.<br><br>


<li id="c"><a name="operations">Gestion des opérations (time-sheet)</a></li>
Voici un exemple de page:<br><br>
<table border=1><tr><td>
<table border=1>
<tr>

<th width=180><a 
href="./modifier_donnees.php?no_dossier=2">Client:</a></th><th>Nature du 
mandat:</th><th>Partie adverse:</th><th>Prix à l'heure:</th><th>R&eacute;sultat 
du dossier:</th></tr><tr><td>Monsieur<br>Alfred 
<b>Miliquet</b><br><br>Ailleurs<br>1234&nbsp;Bottoflens</td><td></td><td>&nbsp;</td><td><a href="./modifier_prix.php?prix=300&no_dossier=2">300.-</a></td><td><table border=0><tr><td width=100>Théorique:</td><td width=100 align=right>0.00</td></tr><tr><td>TVA 7,6%</td><td align=right>0.00</td></tr><tr><td><b>Total th&eacute;orique:</b></td><td align=right><b>0.00</b></td></tr><tr><td width=100>./.&nbsp;Rentr&eacute;es: </td><td width=100 align=right>0.00</td></tr><tr><td>+&nbsp;Avances:  </td><td align=right>0.00</td></tr><tr><td><b>Solde&nbsp;d&ucirc;: </b></td><td align=right><b>0.00</b></td></tr><tr><td>En attente: </td><td align=right>0.00</td></tr></table>
<//Affichage des en-tête du tableau des opérations//>
</tr>
</table>
<br>
<table width=95% align=center>
<tr>
<th><a href="encaissements.php?no_dossier=2">Encaissements<a></th>
</tr><tr>
<th>N° doss.</th><th>Date d'op&eacute;ration</th><th>Op&eacute;ration 
effectu&eacute;e</th><th>Indications suppl&eacute;mentaires</th><th>Temps 
consacr&eacute;</th><form method=post 
action="./afficher_operations.php?no_dossier=2"><td align=right 
valign=bottom>En-t&ecirc;te<input type="checkbox" name=entete><br>Temps<input 
type="checkbox" name=temps><br>Res.<input type="checkbox" name=resume></th><th 
valign=middle><input type=submit value="afficher"></th></form>
</tr>

<//Boutons pour modifier, dernière cellule de chaque ligne//>
<tr><td>&nbsp;</td><td colspan=4><hr></td></tr><tr><td>2</td>
<td><input type=text size=2 name=date_jour value="08"><input type=text size=2 
name=date_mois value="03"><input type=text size=4 name=date_annee 
value="2003"></td>
<td><select name=op>
<option value="">
<option value="Ouverture du dossier">Ouverture du dossier
<option value="Conf&eacute;rence t&eacute;l&eacute;phonique">Conf&eacute;rence 
t&eacute;l&eacute;phonique
<option value="Appel t&eacute;l&eacute;phonique">Appel 
t&eacute;l&eacute;phonique
<option value="Conf&eacute;rence">Conf&eacute;rence
<option value="Ecriture">Ecriture
<option value="Lettre">Lettre
<option value="Fax">Fax
<option value="Vacation">Vacation
<option value="Audience">Audience
<option value="Etude du dossier">Etude du dossier</select></td>
<td><input type=text size=20 name=opavec></td>
<td><input type=text size=2 name="temps_heure">
<input type=text size=2 name="temps_minute"></td>
<td><input type=submit value="Nouveau"></td></tr>

<//Affichage des résultats du dossier (calcul des honoraires théoriques)//>
<tr><td colspan=4><b>Total :</td><td colspan=2><b>
 soit 0 francs</b></td></tr></table>
<// table visant à gérer les enregistrements affichés à l'écran, par vague de 30 




//>

<// début //>
<table width=95% align=center><tr>
<td align=left width=30>
<<-</td>
&nbsp;
<td align=left width=30>

<// précédent //>
<-</td>

<// liste des enregistrements //>
<td align=center>
enregistrements
1&nbsp;&agrave;&nbsp;0</td>

<// suivant //>
<td align=right width=30>
-></td>

<// fin //>
<td align=right width=30>
->></td>
</tr></table></td></tr></table><br><br>
Cette page affiche les principales options d'un dossier.<br>
En cliquant sur le <font color=0000ff><u>prix à l'heure</u></font>, on peut le 
modifier à sa guise. Le montant des honoraires gagnés se modifie en 
conséquence.<br>
En cliquant sur le mot <font color=0000ff><u>client</u></font>, on peut modifier 



ses paramètres (nom, adresse, mandat, archivage, etc...).<br>
En cliquant sur le mot <font color=0000ff><u>Encaissements</u></font>, on accède
à la page gérant les rentrées et sorties d'argent.<br>
En cliquant sur le bouton "Afficher", on obtient une liste des opérations.
Suivant quelles cases sont cochées, la liste est plus ou moins détaillées.<br>
Enfin, lorsqu'un nouveau dossier est créé, il n'y a aucune opération 
enregistrée. Quelles que soient les opérations déjà faites, la page contient 
toujours une dernière ligne où figure un bouton "nouveau". Pour introduire une 
nouvelle opération, on l'inscrit dans cette dernière ligne et on appuie sur le 
bouton. Après confirmation, la page se recharge. L'opération qu'on vient de 
passer apparaît parmi les autres, et une nouvelle ligne vierge est créée au bas 
de la page.<br><br>
<li id="c"><a name="encaissements">Gestion des encaissements</a></li>
</ol>
</ol>
</body>
</html>
