<?php
require_once 'php/connexion.php';

// D'abord modifier l'ENUM pour accepter les deux valeurs temporairement
$sql1 = "ALTER TABLE utilisateurs MODIFY COLUMN role ENUM('senior','utilisateur','intervenant','admin') NOT NULL DEFAULT 'utilisateur'";
mysqli_query($conn, $sql1);

// Mettre à jour tous les senior en utilisateur
$sql2 = "UPDATE utilisateurs SET role = 'utilisateur' WHERE role = 'senior'";
if (mysqli_query($conn, $sql2)) {
  echo '<p style="color:green">✅ ' . mysqli_affected_rows($conn) . ' compte(s) mis à jour</p>';
}

// Retirer senior de l'ENUM
$sql3 = "ALTER TABLE utilisateurs MODIFY COLUMN role ENUM('utilisateur','intervenant','admin') NOT NULL DEFAULT 'utilisateur'";
if (mysqli_query($conn, $sql3)) {
  echo '<p style="color:green">✅ ENUM mis à jour</p>';
} else {
  echo '<p style="color:orange">⚠️ ' . mysqli_error($conn) . '</p>';
}

echo '<p><a href="dashboard_admin.php">→ Retour au dashboard</a></p>';
?>
