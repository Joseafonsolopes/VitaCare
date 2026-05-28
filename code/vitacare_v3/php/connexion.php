<?php
// ============================================
// VITACARE - Connexion à la base de données
// Ce fichier est inclus dans tous les autres
// fichiers PHP avec : require_once 'connexion.php'
// ============================================

$host = 'localhost';
$user = 'root';
$pass = '';           // Mot de passe WAMP (vide par défaut)
$db   = 'vitacare';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
    die('Erreur de connexion à la base de données : ' . mysqli_connect_error());
}

// Encodage UTF-8 pour les accents
mysqli_set_charset($conn, 'utf8');
?>
