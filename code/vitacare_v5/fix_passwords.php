<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Fix mots de passe</title>
</head>
<body>
<?php
require_once 'php/connexion.php';

// Générer le bon hash pour "vitacare123"
$hash = password_hash('vitacare123', PASSWORD_DEFAULT);

// Mettre à jour tous les comptes de test
$sql = "UPDATE utilisateurs SET mot_de_passe = '$hash'";

if (mysqli_query($conn, $sql)) {
  echo '<p style="color:green; font-size:18px;">✅ Mots de passe mis à jour avec succès !</p>';
  echo '<p>Tous les comptes ont maintenant le mot de passe : <strong>vitacare123</strong></p>';
  echo '<p><a href="connexion.php">→ Aller à la page de connexion</a></p>';
} else {
  echo '<p style="color:red;">❌ Erreur : ' . mysqli_error($conn) . '</p>';
}

mysqli_close($conn);
?>
</body>
</html>
