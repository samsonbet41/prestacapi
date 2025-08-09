<?php
// Choisissez un mot de passe fort
$motDePasseEnClair = 'admin@prestacapi.com';

// Hachez le mot de passe avec l'algorithme par défaut (BCRYPT)
$motDePasseHache = password_hash($motDePasseEnClair, PASSWORD_DEFAULT);

// Affichez le résultat pour le copier
echo $motDePasseHache;
?>