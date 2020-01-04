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
<li id="c"><a href="./manuel.php#preliminaires">Pr�liminaires</a></li>
<li id="c"><a href="./manuel.php#utilitaire">Utilitaire de 
configuration</a></li>
</ol>
<li id="b"><a href="./manuel.php#utilisation">Utilisation</a></li>
<ol>
<li id="c"><a href="./manuel.php#verify">Page de v�rification</a></li>
<li id="c"><a href="./manuel.php#recherche">Page de recherche de 
dossier</a></li>
<li id="c"><a href="./manuel.php#operations">Op�rations (time-sheet)</a></li>
<li id="c"><a href="./manuel.php#encaissements">Gestion des 
encaissements</a></li>

</ol>
</ol>
<ol>
<li id="b"><a name="installation">Installation</a></li><br>
<span id="c">
En premier lieu, vous devez avoir install� sur votre syst�me un serveur
int�grant le language php. Le mieux est d'utiliser un serveur <a
href="http://www.apache.org" target="_new">apache</a>, qui int�gre
automatiquement ce module. Il faut
ensuite avoir install� un serveur <a href="http://www.mysql.org"
target="_new">MySql</a> et avoir
cr�� l'administrateur principal de la base de donn�es.<br>
D�compressez l'archive dans un r�pertoire du serveur apache. Vous pouvez donner
� ce r�pertoire le nom que vous voulez. Pour ma part, je pars de l'id�e que ce
r�pertoire s'appelle "etude" pour la suite des explications. Vous disposez donc
des r�pertoires suivants:<br><br>
<ul>
<li>etude</li>
<li>etude/associes/</li>
<li>etude/config/</li>
<li>etude/exemples/</li>
<li>etude/config_files/</li>
<li>etude/manuel/</li>
</ul>
<br>
Vous devez vous assurer que vous disposez des droits suffisants pour l'acc�s en lecture comme
en �criture sur tous les fichiers. Dans ma configuration, vous pouvez utiliser la ligne de commande
suivante:<br>
$su<br>
password:<br>
#cd /votre_repertoire_dans_lequel_est_installe_etude_1.2<br>
#chown apache:apache -R etude_1.2<br>
#chmod -744 -R etude_1.2<br><br>
Tout est install�. Vous pouvez maintenant utiliser le programme.</span><br><br>
<li id="b"><a name="configuration">Configuration</a></li>
<ol>
<li id="c"><a name="preliminaires">Pr�liminaires</a></li>
Pour d�marrer le programme, ouvrez dans un navigateur web la page
nom_de_votre_serveur/etude/index.php. Vous obtiendrez la page suivante:<br><br>
<table border="1">
  <tbody>
    <tr>
      <td>       <h1>
         Bienvenue dans le programme de gestion d'�tude
      </h1>

<br>
<br>
Veuillez saisir votre nom et votre mot de passe pour la connection � la base de
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
Cliquez sur le lien "Administration du programme". La premi�re fois que vous 
vous connectez, vous arrivez � l'�cran
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
a les droits n�cessaires pour modifier la base MySql. Ces donn�es sont stock�es
dans le fichier etude/config/perm.php. Vous pouvez les modifier manuellement par
la suite en respectant la syntaxe du fichier.<br>
<br>
Si vous fournissez un utilisateur jouissant des droits n�cessaires, vous passez
� l'�cran de contr�le d'identification.<br><br>
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
Indiquez le mot de passe que vous venez de cr�er. Vous arrivez alors � l'�cran
de configuration.<br><br>
<li id="c"><a name="utilitaire">Utilitaire de configuration</a></li>
Le module de configuration se pr�sente comme suit:<br><br>
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
<//bouton pour rajouter un associ�//>
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
L'�cran commence par un diagnostic: avez-vous configur� correctement le 
programme&nbsp;?<br> Tant que vous n'avez pas cr�� la base de donn�e n�cessaire
(ce qui se fait
automatiquement en cliquant sur le bouton), vous ne pouvez pas utiliser les
autres options. Le programme doit imp�rativement cr�er une base de donn�es
appel�e "etude" dans laquelle il mettra toutes les donn�es des utilisateurs. Si
cette base ne peut pas �tre cr��e automatiquement, c'est vraisemblablement un
probl�me de nom d'administrateur. Modifiez alors le fichier
etude/config/perm.php en indiquant un utilisateur et un mot de passe qui vous
permettent de cr�er la base de donn�e voulue et r�essayez.<br><br>
D�s que la base de donn�e est cr��e, l'�cran change et vous donne acc�s aux 
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


<//bouton pour rajouter un associ�//>
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
Vous devez d�finir deux types de personnes.<br><br>
Les <b>Associ�s enregistr�s</b> sont les personnes dont on suit les op�rations 
(dans ma conception, les avocats).<br>
Les <b>Utilisateurs</b> sont les personnes autoris�es � se servir du 
programme.<br>
Il n'y a pas forc�ment de correspondance entre les deux. Prenons un bureau de 
notaires avec deux notaires, Me Bolomey et Me Milliquet, et leurs deux 
secr�taires, Mme Blanc et Mme Rouge. Il faudra enregistrer comme associ�s Me 
Bolomey et Me Milliquet. Il faudra en outre enregistrer comme utilisateurs les 
quatre personnes. Si Me Bolomey fait partie des associ�s enregistr�s mais pas 
des utilisateurs, il ne pourra pas consulter ses propres op�rations.<br><br>
Quant aux utilisateurs, il y en a de cinq types:<br>
<ul>
<li><b>Administrateur</b>: c'est celui qui a acc�s � toutes les fonctions du
programme. A utiliser avec pr�cautions!</li>
<li><b>Associ�</b>: groupe dont devraient faire partie les associ�s de l'�tude.
Cela leur donne acc�s � toutes les fonctions d'utilisation sauf la destruction
de fichiers.</li>
<li><b>Secr�taire</b>: groupe dont devraient faire partie les secr�taires de
l'�tude. Cela leur donne acc�s � toutes les fonctions des associ�s, mais pas au
r�sum� du chiffre d'affaire.</li>
<li><b>Compta</b>: groupe pour le comptable. En l'�tat, a les m�mes informations

que l'associ�. Pourrait servir dans une modification du programme</li>
<li><b>Destruction</b>: indispensable. <b>Un utilisateur ordinaire ne peut pas 
d�truire un fichier</b>, cela pour �viter les erreurs. Il faut donc cr�er un 
utilisateur destruction. Lorsqu'on veut d�truire un fichier, on doit se 
d�connecter et se reconnecter en utilisant ce nom d'utilisateur sp�cifique.</li>
</ul>
<br><br>
Lorsque vous avez termin� la configuration, cliquez sur "Retour au programme". 
Vous revenez alors � l'�cran de base. Vous pouvez vous connecter en saisissant 
le nom et le mot de passe <b>de l'utilisateur et non de l'associ�</b> que vous 
venez de cr�er.<br><br><br><br>
</ol>
<li id="b"><a name="utilisation">Utilisation du programme</a></li>
<br>
<ol>
<li id="c"><a name="verify">Page de v�rification</a></li>
Cette page v�rifie si vous avez fourni les bonnes informations. Si tel est
le cas, vous �tes automatiquement redirig� sur la page de recherche. Dans le cas
contraire, vous devez vous annoncer � nouveau.<br>Il y a malheureusement un bug
dans la gestion des sessions sous PHP. Pour des raisons que je n'ai pas encore
comprises (mais qui paraissent tenir � PHP lui-m�me, si j'en crois la
documentation), il peut arriver que la gestion des sessions ne fonctionne
temporairement plus. Si tel est le cas, vous pouvez introduire n'importe quel
mot de passe, il sera toujours consid�r� comme faux. Dans ce cas, la seule
solution est de fermer toutes les fen�tres de navigateur et de les rouvrir pour
que le syst�me marche � nouveau.<br><br>
<li id="c"><a name="recherche">Page de recherche</a></li>
Vous pouvez rechercher un dossier, soit par son num�ro, soit par le nom ou le 
pr�nom du client. La recherche est insensible � la casse (ce qui veut dire 
qu'une recherche sur BolOM trouvera aussi bien BOLOMEY que bolomey), mais elle 
est en revanche sensible aux accents (une recherche de "se" trouvera "serviteur" 






mais pas "s�rieux").<br><br>


<li id="c"><a name="resultat_recherche">R�sultats de recherche</a></li>
La page se pr�sente comme suit:<br>
<table border="1">
  <tbody>
    <tr>
      <td>Recherche selon les crit&egrave;res suivants:
<br>
Nom du client :  alfred<br> N� du dossier :  (pas de crit&egrave;re)<br><br>J'ai 





trouv&eacute; 2 enregistrements :<br><br>
</td></tr><tr><td><table cellspacing=10 align=left border=1>
<tr>
<th>N� dossier</th><th>Titre</th><th>Coordonn&eacute;es du client <br>(le 
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
<td>N� de dossier :</td><td><input type=texte name="no_dossier" value=""></td>
</tr>
</table>
<input type=submit value="Rechercher">
</td>
    </tr>
  </tbody>
</table><br><br>
Les num�ros de dossiers qui apparaissent en <font color="ff6060">rouge</font>
sont les dossiers qui sont d�j� archiv�s.<br>
En cliquant sur le lien <font color="0000ff"><u>S�lectionner</u></font>, on 
acc�de au time-sheet du client.<br><br>


<li id="c"><a name="operations">Gestion des op�rations (time-sheet)</a></li>
Voici un exemple de page:<br><br>
<table border=1><tr><td>
<table border=1>
<tr>

<th width=180><a 
href="./modifier_donnees.php?no_dossier=2">Client:</a></th><th>Nature du 
mandat:</th><th>Partie adverse:</th><th>Prix � l'heure:</th><th>R&eacute;sultat 
du dossier:</th></tr><tr><td>Monsieur<br>Alfred 
<b>Miliquet</b><br><br>Ailleurs<br>1234&nbsp;Bottoflens</td><td></td><td>&nbsp;</td><td><a href="./modifier_prix.php?prix=300&no_dossier=2">300.-</a></td><td><table border=0><tr><td width=100>Th�orique:</td><td width=100 align=right>0.00</td></tr><tr><td>TVA 7,6%</td><td align=right>0.00</td></tr><tr><td><b>Total th&eacute;orique:</b></td><td align=right><b>0.00</b></td></tr><tr><td width=100>./.&nbsp;Rentr&eacute;es: </td><td width=100 align=right>0.00</td></tr><tr><td>+&nbsp;Avances:  </td><td align=right>0.00</td></tr><tr><td><b>Solde&nbsp;d&ucirc;: </b></td><td align=right><b>0.00</b></td></tr><tr><td>En attente: </td><td align=right>0.00</td></tr></table>
<//Affichage des en-t�te du tableau des op�rations//>
</tr>
</table>
<br>
<table width=95% align=center>
<tr>
<th><a href="encaissements.php?no_dossier=2">Encaissements<a></th>
</tr><tr>
<th>N� doss.</th><th>Date d'op&eacute;ration</th><th>Op&eacute;ration 
effectu&eacute;e</th><th>Indications suppl&eacute;mentaires</th><th>Temps 
consacr&eacute;</th><form method=post 
action="./afficher_operations.php?no_dossier=2"><td align=right 
valign=bottom>En-t&ecirc;te<input type="checkbox" name=entete><br>Temps<input 
type="checkbox" name=temps><br>Res.<input type="checkbox" name=resume></th><th 
valign=middle><input type=submit value="afficher"></th></form>
</tr>

<//Boutons pour modifier, derni�re cellule de chaque ligne//>
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

<//Affichage des r�sultats du dossier (calcul des honoraires th�oriques)//>
<tr><td colspan=4><b>Total :</td><td colspan=2><b>
 soit 0 francs</b></td></tr></table>
<// table visant � g�rer les enregistrements affich�s � l'�cran, par vague de 30 




//>

<// d�but //>
<table width=95% align=center><tr>
<td align=left width=30>
<<-</td>
&nbsp;
<td align=left width=30>

<// pr�c�dent //>
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
En cliquant sur le <font color=0000ff><u>prix � l'heure</u></font>, on peut le 
modifier � sa guise. Le montant des honoraires gagn�s se modifie en 
cons�quence.<br>
En cliquant sur le mot <font color=0000ff><u>client</u></font>, on peut modifier 



ses param�tres (nom, adresse, mandat, archivage, etc...).<br>
En cliquant sur le mot <font color=0000ff><u>Encaissements</u></font>, on acc�de
� la page g�rant les rentr�es et sorties d'argent.<br>
En cliquant sur le bouton "Afficher", on obtient une liste des op�rations.
Suivant quelles cases sont coch�es, la liste est plus ou moins d�taill�es.<br>
Enfin, lorsqu'un nouveau dossier est cr��, il n'y a aucune op�ration 
enregistr�e. Quelles que soient les op�rations d�j� faites, la page contient 
toujours une derni�re ligne o� figure un bouton "nouveau". Pour introduire une 
nouvelle op�ration, on l'inscrit dans cette derni�re ligne et on appuie sur le 
bouton. Apr�s confirmation, la page se recharge. L'op�ration qu'on vient de 
passer appara�t parmi les autres, et une nouvelle ligne vierge est cr��e au bas 
de la page.<br><br>
<li id="c"><a name="encaissements">Gestion des encaissements</a></li>
</ol>
</ol>
</body>
</html>
