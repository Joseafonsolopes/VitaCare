<?php
require_once 'php/connexion.php';

$requetes = array(
  "CREATE TABLE IF NOT EXISTS programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_service INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    nb_seances INT NOT NULL DEFAULT 4,
    duree_jours INT NOT NULL DEFAULT 30,
    date_debut DATE NOT NULL,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_service) REFERENCES services(id)
  )",
  "CREATE TABLE IF NOT EXISTS inscriptions_programmes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_programme INT NOT NULL,
    date_inscription DATE NOT NULL,
    nb_seances_faites INT DEFAULT 0,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_programme) REFERENCES programmes(id)
  )",
  "ALTER TABLE utilisateurs ADD COLUMN preferences VARCHAR(255) DEFAULT ''",
);

echo '<h2>Installation des nouvelles tables</h2>';
foreach ($requetes as $sql) {
  if (mysqli_query($conn, $sql)) {
    echo '<p style="color:green">✅ OK</p>';
  } else {
    echo '<p style="color:orange">⚠️ ' . mysqli_error($conn) . '</p>';
  }
}

// Données de test pour les programmes
$sql_test = "INSERT IGNORE INTO programmes (id_service, nom, description, nb_seances, duree_jours, date_debut)
             VALUES (1, 'Programme bien-être 4 semaines', 'Un programme complet de yoga douceur', 4, 28, '2026-05-01')";
mysqli_query($conn, $sql_test);

// Inscription de test pour Nathéo
$sql_inscrit = "INSERT IGNORE INTO inscriptions_programmes (id_utilisateur, id_programme, date_inscription, nb_seances_faites)
                VALUES (4, 1, '2026-05-01', 2)";
mysqli_query($conn, $sql_inscrit);

echo '<p style="color:green; font-weight:bold; margin-top:20px">✅ Installation terminée !</p>';
echo '<a href="profil.php">→ Aller au profil</a>';
?>

<?php
// Ajouter la colonne prix si elle n'existe pas
$sql_prix = "ALTER TABLE programmes ADD COLUMN prix DECIMAL(10,2) NOT NULL DEFAULT 0";
mysqli_query($conn, $sql_prix); // On ignore l'erreur si la colonne existe déjà
echo '<p style="color:green">✅ Colonne prix ajoutée</p>';
?>
