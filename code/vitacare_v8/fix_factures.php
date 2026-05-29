<?php
require_once 'php/connexion.php';

$requetes = array(
  // Table factures
  "CREATE TABLE IF NOT EXISTS factures (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_reservation INT NOT NULL,
    numero_facture VARCHAR(50) NOT NULL,
    montant DECIMAL(10,2) NOT NULL,
    date_facture DATE NOT NULL,
    nom_service VARCHAR(150),
    nom_intervenant VARCHAR(150),
    date_creneau DATE,
    heure_creneau TIME,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_reservation) REFERENCES reservations(id)
  )",
  // Colonne nb_places dans réservations
  "ALTER TABLE reservations ADD COLUMN nb_places INT NOT NULL DEFAULT 1"
);

foreach ($requetes as $sql) {
  if (mysqli_query($conn, $sql)) {
    echo '<p style="color:green">✅ OK</p>';
  } else {
    echo '<p style="color:orange">⚠️ ' . mysqli_error($conn) . '</p>';
  }
}

echo '<p style="color:green; font-weight:bold">✅ Tables mises à jour !</p>';
echo '<a href="index.php">→ Retour à l\'accueil</a>';
?>
