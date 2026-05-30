<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'vitacare';

// Connexion sans sélectionner la base pour pouvoir la créer si besoin
$conn_init = mysqli_connect($host, $user, $pass);
if (!$conn_init) {
  die('Erreur de connexion MySQL : ' . mysqli_connect_error());
}
mysqli_set_charset($conn_init, 'utf8mb4');

// Créer la base si elle n'existe pas
mysqli_query($conn_init, "CREATE DATABASE IF NOT EXISTS `vitacare` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
mysqli_select_db($conn_init, 'vitacare');

$conn = $conn_init;

// Créer toutes les tables si elles n'existent pas
$tables = array(

  "CREATE TABLE IF NOT EXISTS utilisateurs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nom VARCHAR(100) NOT NULL,
    prenom VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    mot_de_passe VARCHAR(255) NOT NULL,
    role ENUM('utilisateur','intervenant','admin') NOT NULL DEFAULT 'utilisateur',
    telephone VARCHAR(20),
    adresse VARCHAR(255),
    preferences VARCHAR(255) DEFAULT '',
    actif TINYINT(1) DEFAULT 1,
    date_inscription DATE NOT NULL
  )",

  "CREATE TABLE IF NOT EXISTS services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_intervenant INT NOT NULL,
    nom VARCHAR(150) NOT NULL,
    description TEXT,
    emoji VARCHAR(10),
    categorie VARCHAR(100),
    duree INT NOT NULL DEFAULT 60,
    prix DECIMAL(10,2) NOT NULL DEFAULT 0,
    actif TINYINT(1) DEFAULT 1,
    date_creation DATE,
    FOREIGN KEY (id_intervenant) REFERENCES utilisateurs(id)
  )",

  "CREATE TABLE IF NOT EXISTS creneaux (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_service INT NOT NULL,
    date_creneau DATE NOT NULL,
    heure TIME NOT NULL,
    places_total INT NOT NULL DEFAULT 10,
    places_restantes INT NOT NULL DEFAULT 10,
    actif TINYINT(1) DEFAULT 1,
    FOREIGN KEY (id_service) REFERENCES services(id)
  )",

  "CREATE TABLE IF NOT EXISTS reservations (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    id_creneau INT NOT NULL,
    statut ENUM('confirmee','annulee','en_attente') NOT NULL DEFAULT 'confirmee',
    date_reservation DATE NOT NULL,
    nb_places INT NOT NULL DEFAULT 1,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_creneau) REFERENCES creneaux(id)
  )",

  "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_utilisateur INT NOT NULL,
    message TEXT NOT NULL,
    lu TINYINT(1) DEFAULT 0,
    date_notification DATETIME NOT NULL,
    FOREIGN KEY (id_utilisateur) REFERENCES utilisateurs(id)
  )",

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

  "CREATE TABLE IF NOT EXISTS messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    id_expediteur INT NOT NULL,
    id_destinataire INT NOT NULL,
    message TEXT NOT NULL,
    lu TINYINT(1) DEFAULT 0,
    date_message DATETIME NOT NULL,
    FOREIGN KEY (id_expediteur) REFERENCES utilisateurs(id),
    FOREIGN KEY (id_destinataire) REFERENCES utilisateurs(id)
  )"

);

foreach ($tables as $sql) {
  mysqli_query($conn, $sql);
}

// Insérer les données de test si la table est vide
$check_users = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM utilisateurs"));
if ($check_users['nb'] == 0) {

  $hash = password_hash('vitacare123', PASSWORD_DEFAULT);
  $today = date('Y-m-d');

  // Comptes de base
  mysqli_query($conn, "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, telephone, adresse, date_inscription) VALUES
    ('Admin', 'VitaCare', 'admin@vitacare.fr', '$hash', 'admin', '0600000000', 'Paris', '2025-01-01'),
    ('Dupont', 'Marie', 'marie.dupont@vitacare.fr', '$hash', 'intervenant', '0612345678', 'Paris', '2025-01-15'),
    ('Garnier', 'Élise', 'elise.garnier@vitacare.fr', '$hash', 'intervenant', '0623456789', 'Lyon', '2025-02-01'),
    ('Milliet', 'Nathéo', 'natheo.milliet@vitacare.fr', '$hash', 'utilisateur', '0634567890', '10 rue Sextius Michel, 75015 Paris', '2025-03-10')
  ");

  // Services
  mysqli_query($conn, "INSERT INTO services (id_intervenant, nom, description, emoji, categorie, duree, prix, actif, date_creation) VALUES
    (2, 'Yoga Douceur', 'Séance de yoga adaptée à tous les niveaux pour détendre le corps et l esprit.', '🧘', 'Bien-être', 60, 35, 1, '2025-01-15'),
    (2, 'Méditation guidée', 'Séance de méditation pour retrouver calme et sérénité.', '🌙', 'Bien-être', 45, 25, 1, '2025-01-20'),
    (3, 'Consultation nutrition', 'Bilan nutritionnel complet et conseils personnalisés.', '🥗', 'Nutrition', 60, 60, 1, '2025-02-01'),
    (2, 'Marche nordique', 'Activité sportive en plein air pour tonifier le corps.', '🏃', 'Activités', 90, 20, 1, '2025-02-10')
  ");

  // Créneaux
  $futur1 = date('Y-m-d', strtotime('+7 days'));
  $futur2 = date('Y-m-d', strtotime('+14 days'));
  $futur3 = date('Y-m-d', strtotime('+21 days'));
  mysqli_query($conn, "INSERT INTO creneaux (id_service, date_creneau, heure, places_total, places_restantes, actif) VALUES
    (1, '$futur1', '09:00:00', 10, 10, 1),
    (1, '$futur2', '09:00:00', 10, 10, 1),
    (2, '$futur1', '18:00:00', 8, 8, 1),
    (3, '$futur2', '10:00:00', 5, 5, 1),
    (3, '$futur3', '14:00:00', 5, 5, 1),
    (4, '$futur1', '08:00:00', 15, 15, 1)
  ");
}

// Corrections automatiques
mysqli_query($conn, "UPDATE utilisateurs SET role = 'utilisateur' WHERE role = 'senior' OR role = '' OR role IS NULL");

// Correction mots de passe comptes de test
$comptes_test = array(
  'admin@vitacare.fr'          => 'vitacare123',
  'marie.dupont@vitacare.fr'   => 'vitacare123',
  'elise.garnier@vitacare.fr'  => 'vitacare123',
  'natheo.milliet@vitacare.fr' => 'vitacare123',
);
foreach ($comptes_test as $email => $mdp) {
  $res = mysqli_query($conn, "SELECT mot_de_passe FROM utilisateurs WHERE email = '$email'");
  if ($res && $row = mysqli_fetch_assoc($res)) {
    if (!password_verify($mdp, $row['mot_de_passe'])) {
      $hash = password_hash($mdp, PASSWORD_DEFAULT);
      mysqli_query($conn, "UPDATE utilisateurs SET mot_de_passe = '$hash' WHERE email = '$email'");
    }
  }
}
?>
