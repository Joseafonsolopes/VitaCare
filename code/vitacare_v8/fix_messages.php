<?php
require_once 'php/connexion.php';

$sql = "CREATE TABLE IF NOT EXISTS messages (
  id INT AUTO_INCREMENT PRIMARY KEY,
  id_expediteur INT NOT NULL,
  id_destinataire INT NOT NULL,
  message TEXT NOT NULL,
  lu TINYINT(1) DEFAULT 0,
  date_message DATETIME NOT NULL,
  FOREIGN KEY (id_expediteur) REFERENCES utilisateurs(id),
  FOREIGN KEY (id_destinataire) REFERENCES utilisateurs(id)
)";

if (mysqli_query($conn, $sql)) {
  echo '<p style="color:green">✅ Table messages créée !</p>';
} else {
  echo '<p style="color:orange">⚠️ ' . mysqli_error($conn) . '</p>';
}

echo '<a href="index.php">→ Retour à l\'accueil</a>';
?>
