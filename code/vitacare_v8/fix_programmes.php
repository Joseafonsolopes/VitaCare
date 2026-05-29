<?php
require_once 'php/connexion.php';

// Ajouter colonne prix à programmes si elle n'existe pas
$sql = "ALTER TABLE programmes ADD COLUMN prix DECIMAL(10,2) NOT NULL DEFAULT 0";
if (mysqli_query($conn, $sql)) {
  echo '<p style="color:green">✅ Colonne prix ajoutée à la table programmes</p>';
} else {
  echo '<p style="color:orange">⚠️ ' . mysqli_error($conn) . ' (peut-être déjà existante)</p>';
}

echo '<p><a href="dashboard_intervenant.php">→ Retour au dashboard intervenant</a></p>';
?>
