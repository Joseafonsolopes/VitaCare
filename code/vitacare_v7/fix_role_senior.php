<?php
require_once 'php/connexion.php';

// Modifier l'ENUM de la colonne role
$sql1 = "ALTER TABLE utilisateurs MODIFY COLUMN role ENUM('utilisateur', 'intervenant', 'admin') NOT NULL DEFAULT 'utilisateur'";

// Mettre à jour les anciens comptes "senior" en "utilisateur"
$sql2 = "UPDATE utilisateurs SET role = 'utilisateur' WHERE role = 'senior'";

if (mysqli_query($conn, $sql1)) {
  echo '<p style="color:green">✅ Colonne role mise à jour</p>';
} else {
  echo '<p style="color:red">❌ ' . mysqli_error($conn) . '</p>';
}

if (mysqli_query($conn, $sql2)) {
  echo '<p style="color:green">✅ Anciens comptes "senior" convertis en "utilisateur"</p>';
} else {
  echo '<p style="color:red">❌ ' . mysqli_error($conn) . '</p>';
}

echo '<p><a href="dashboard_admin.php">→ Retour au dashboard admin</a></p>';
?>
