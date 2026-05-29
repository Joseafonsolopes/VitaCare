<?php
require_once 'php/connexion.php';

// Changer l'encodage de la base et des tables pour supporter les emojis
$requetes = array(
  "ALTER DATABASE vitacare CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
  "ALTER TABLE services CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
  "ALTER TABLE utilisateurs CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
  "ALTER TABLE creneaux CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
  "ALTER TABLE reservations CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
  "ALTER TABLE notifications CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci",
);

echo '<h2>Correction des emojis</h2>';
foreach ($requetes as $sql) {
  if (mysqli_query($conn, $sql)) {
    echo '<p style="color:green">✅ ' . htmlspecialchars($sql) . '</p>';
  } else {
    echo '<p style="color:red">❌ Erreur : ' . mysqli_error($conn) . '</p>';
  }
}

// Mettre à jour mysqli pour utiliser utf8mb4
mysqli_set_charset($conn, 'utf8mb4');

echo '<p style="margin-top:20px; font-weight:bold; color:green">✅ Terminé ! Les emojis devraient maintenant s\'afficher correctement.</p>';
echo '<a href="index.php">→ Retour à l\'accueil</a>';

mysqli_close($conn);
?>
