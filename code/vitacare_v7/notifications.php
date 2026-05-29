<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Notifications</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php
session_start();
require_once 'php/connexion.php';

if (!isset($_SESSION['id'])) {
  header('Location: connexion.php');
  exit();
}

$id_utilisateur = $_SESSION['id'];

// Marquer toutes les notifications comme lues
mysqli_query($conn, "UPDATE notifications SET lu = 1 WHERE id_utilisateur = '$id_utilisateur'");

// Récupérer toutes les notifications
$sql = "SELECT * FROM notifications WHERE id_utilisateur = '$id_utilisateur' ORDER BY date_notification DESC";
$result = mysqli_query($conn, $sql);
?>

<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="services.php">Services</a></li>
    <li><a href="reservation.php">Prendre rendez-vous</a></li>
    <li><a href="programmes.php">Programmes</a></li>
    <li><a href="notifications.php" class="actif">Notifications</a></li>
    <li><a href="panier.php">Panier</a></li>
    <li><a href="profil.php">Mon profil</a></li>
  </ul>
  <div class="navbar-user">
    <span class="navbar-prenom">👤 <?php echo htmlspecialchars($_SESSION['prenom']); ?></span>
    <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
  </div>
</nav>

<div class="dashboard-page">
  <div class="dashboard-header">
    <h1>🔔 Notifications</h1>
    <p>Vos dernières notifications</p>
  </div>

  <div class="dashboard-section">
    <?php if (mysqli_num_rows($result) > 0): ?>
      <?php while ($notif = mysqli_fetch_assoc($result)): ?>
      <div class="notif-item <?php echo $notif['lu'] ? '' : 'non-lue'; ?>">
        <div class="notif-icone">
          <?php echo $notif['lu'] ? '🔔' : '🔴'; ?>
        </div>
        <div class="notif-contenu">
          <p class="notif-message"><?php echo htmlspecialchars($notif['message']); ?></p>
          <p class="notif-date"><?php echo htmlspecialchars($notif['date_notification']); ?></p>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <p style="color:#999; text-align:center; padding:30px;">Aucune notification pour le moment.</p>
    <?php endif; ?>
  </div>
</div>

</body>
</html>
