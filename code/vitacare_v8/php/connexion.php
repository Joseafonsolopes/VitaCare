<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$db   = 'vitacare';

$conn = mysqli_connect($host, $user, $pass, $db);

if (!$conn) {
  die('Erreur de connexion à la base de données : ' . mysqli_connect_error());
}

mysqli_set_charset($conn, 'utf8mb4');

// Correction automatique des rôles
mysqli_query($conn, "UPDATE utilisateurs SET role = 'utilisateur' WHERE role = 'senior' OR role = '' OR role IS NULL");

// Correction automatique des mots de passe des comptes de test
$comptes_test = array(
  'admin@vitacare.fr'          => 'vitacare123',
  'marie.dupont@vitacare.fr'   => 'vitacare123',
  'elise.garnier@vitacare.fr'  => 'vitacare123',
  'natheo.milliet@vitacare.fr' => 'vitacare123',
);

foreach ($comptes_test as $email => $mdp) {
  $hash = password_hash($mdp, PASSWORD_DEFAULT);
  // Vérifier si le mot de passe actuel fonctionne déjà
  $res = mysqli_query($conn, "SELECT mot_de_passe FROM utilisateurs WHERE email = '$email'");
  if ($res && $row = mysqli_fetch_assoc($res)) {
    if (!password_verify($mdp, $row['mot_de_passe'])) {
      // Le mot de passe ne fonctionne pas, on le remet
      mysqli_query($conn, "UPDATE utilisateurs SET mot_de_passe = '$hash' WHERE email = '$email'");
    }
  }
}
?>
