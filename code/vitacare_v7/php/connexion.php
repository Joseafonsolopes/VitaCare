<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'vitacare';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die('Erreur de connexion à la base de données : ' . mysqli_connect_error());
}

// utf8mb4 pour supporter les emojis
mysqli_set_charset($conn, 'utf8mb4');
?>
