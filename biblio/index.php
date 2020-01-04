<?php session_start();
?>
<HTML>
   <head>
      <title>
         Page de connection au fichier biblioth&egrave;que
      </title>
   </head>
   <body onload="document.form.start_utilisateur.focus()">
      <br>
      <br>
      <h1>
         Bienvenue dans le programme de gestion de biblioth&egrave;que
      </h1>

<br>
<br>
<?php
session_destroy();
session_unregister("session_utilisateur");
session_unregister("session_pwd");
session_unregister("session_type");
echo "Veuillez saisir votre nom et votre mot de passe pour la connection à la base de donn&eacute;e.<br>Pour consulter, mettez \"guest\" (en minuscules) comme nom, et laissez le mot de passe vide.

<form name=\"form\" method=post action=./verify.php?>
   <table>
      <tr>
         <td>
            Nom :
         </td>
         <td>
            <input type=text name=\"start_utilisateur\" value=$session_utilisateur>
         </td>
      </tr>
      <tr>
         <td>
            Mot de passe :
         </td>
         <td>
            <input type=password name=\"start_pwd\" value=$session_pwd>
         </td>
      </tr>
      <tr>
         <td>
            Type :
         </td>
         <td>
            <select name=session_type><option value=\"1\">1
<option value=\"0\">0
<option value=\"Romans\">Romans
</select>

         </td>
      </tr>
      <tr>
         <td>
            <br>
            <input type=submit value=\"Se connecter\"><input type=reset>
         </td>
      </tr>
   </table>
</form>";
?>
<body>
</HTML>
