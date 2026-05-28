<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Dashboard Admin</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php
session_start();
require_once 'php/connexion.php';

// Vérifier que l'utilisateur est connecté et est admin
if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
  header('Location: connexion.php');
  exit();
}
?>

<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.html">Accueil</a></li>
    <li><a href="dashboard_admin.php" class="actif">Dashboard</a></li>
  </ul>
  <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
</nav>

<div class="dashboard-page">

  <div class="dashboard-header">
    <h1>Tableau de bord Admin</h1>
    <p>Bonjour <?php echo htmlspecialchars($_SESSION['prenom']); ?> 👋</p>
  </div>

  <!-- STATS -->
  <div class="stats-grille">
    <?php
    // Nombre d'utilisateurs
    $r1 = mysqli_query($conn, "SELECT COUNT(*) as total FROM utilisateurs");
    $nb_users = mysqli_fetch_assoc($r1)['total'];

    // Nombre de services
    $r2 = mysqli_query($conn, "SELECT COUNT(*) as total FROM services");
    $nb_services = mysqli_fetch_assoc($r2)['total'];

    // Nombre de réservations
    $r3 = mysqli_query($conn, "SELECT COUNT(*) as total FROM reservations");
    $nb_resa = mysqli_fetch_assoc($r3)['total'];

    // Nombre d'intervenants
    $r4 = mysqli_query($conn, "SELECT COUNT(*) as total FROM utilisateurs WHERE role = 'intervenant'");
    $nb_intervenants = mysqli_fetch_assoc($r4)['total'];
    ?>
    <div class="stat-carte">
      <span class="stat-emoji">👥</span>
      <span class="stat-nombre"><?php echo $nb_users; ?></span>
      <span class="stat-label">Utilisateurs</span>
    </div>
    <div class="stat-carte">
      <span class="stat-emoji">🏃</span>
      <span class="stat-nombre"><?php echo $nb_services; ?></span>
      <span class="stat-label">Services</span>
    </div>
    <div class="stat-carte">
      <span class="stat-emoji">📅</span>
      <span class="stat-nombre"><?php echo $nb_resa; ?></span>
      <span class="stat-label">Réservations</span>
    </div>
    <div class="stat-carte">
      <span class="stat-emoji">🧑‍⚕️</span>
      <span class="stat-nombre"><?php echo $nb_intervenants; ?></span>
      <span class="stat-label">Intervenants</span>
    </div>
  </div>

  <!-- LISTE UTILISATEURS -->
  <div class="dashboard-section">
    <h2>Utilisateurs récents</h2>
    <table class="tableau">
      <tr>
        <th>ID</th>
        <th>Prénom</th>
        <th>Nom</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Inscription</th>
      </tr>
      <?php
      $result = mysqli_query($conn, "SELECT * FROM utilisateurs ORDER BY id DESC");
      while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['prenom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td><span class="badge-role badge-' . $row['role'] . '">' . htmlspecialchars($row['role']) . '</span></td>';
        echo '<td>' . htmlspecialchars($row['date_inscription']) . '</td>';
        echo '</tr>';
      }
      ?>
    </table>
  </div>

  <!-- LISTE RESERVATIONS -->
  <div class="dashboard-section">
    <h2>Réservations récentes</h2>
    <table class="tableau">
      <tr>
        <th>ID</th>
        <th>Utilisateur</th>
        <th>Service</th>
        <th>Statut</th>
        <th>Date</th>
      </tr>
      <?php
      $result2 = mysqli_query($conn, "SELECT r.id, u.prenom, u.nom, s.nom AS service, r.statut, r.date_reservation
                                      FROM reservations r, utilisateurs u, creneaux c, services s
                                      WHERE r.id_utilisateur = u.id
                                      AND r.id_creneau = c.id
                                      AND c.id_service = s.id
                                      ORDER BY r.id DESC");
      while ($row = mysqli_fetch_assoc($result2)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['prenom']) . ' ' . htmlspecialchars($row['nom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['service']) . '</td>';
        echo '<td><span class="badge-role badge-' . $row['statut'] . '">' . htmlspecialchars($row['statut']) . '</span></td>';
        echo '<td>' . htmlspecialchars($row['date_reservation']) . '</td>';
        echo '</tr>';
      }
      ?>
    </table>
  </div>

</div>

</body>
</html>
