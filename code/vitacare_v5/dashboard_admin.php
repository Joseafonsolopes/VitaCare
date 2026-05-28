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

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'admin') {
  header('Location: connexion.php');
  exit();
}

$erreur = '';
$succes = '';

// Créer un compte intervenant ou admin
if (isset($_POST['btn_creer_compte'])) {
  $prenom    = htmlspecialchars($_POST['prenom']);
  $nom       = htmlspecialchars($_POST['nom']);
  $email     = htmlspecialchars($_POST['email']);
  $mot_passe = $_POST['mot_de_passe'];
  $role      = htmlspecialchars($_POST['role']);
  $date_today = date('Y-m-d');

  if (empty($prenom) || empty($nom) || empty($email) || empty($mot_passe)) {
    $erreur = 'Veuillez remplir tous les champs.';
  } elseif (strlen($mot_passe) < 6) {
    $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
  } else {
    $check = mysqli_query($conn, "SELECT id FROM utilisateurs WHERE email = '$email'");
    if (mysqli_num_rows($check) > 0) {
      $erreur = 'Cet email est déjà utilisé.';
    } else {
      $hash = password_hash($mot_passe, PASSWORD_DEFAULT);
      $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, date_inscription)
              VALUES ('$nom', '$prenom', '$email', '$hash', '$role', '$date_today')";
      if (mysqli_query($conn, $sql)) {
        $succes = 'Compte ' . $role . ' créé pour ' . $prenom . ' ' . $nom . ' !';
      } else {
        $erreur = 'Erreur : ' . mysqli_error($conn);
      }
    }
  }
}

// Supprimer un utilisateur
if (isset($_POST['btn_supprimer'])) {
  $id_suppr = (int)$_POST['id_utilisateur'];
  if ($id_suppr !== (int)$_SESSION['id']) {
    mysqli_query($conn, "UPDATE utilisateurs SET actif = 0 WHERE id = '$id_suppr'");
    $succes = 'Compte désactivé avec succès.';
  } else {
    $erreur = 'Vous ne pouvez pas désactiver votre propre compte.';
  }
}
?>

<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="dashboard_admin.php" class="actif">Dashboard</a></li>
  </ul>
  <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
</nav>

<div class="dashboard-page">

  <div class="dashboard-header">
    <h1>Tableau de bord Admin</h1>
    <p>Bonjour <?php echo htmlspecialchars($_SESSION['prenom']); ?> 👋</p>
  </div>

  <?php if ($erreur): ?>
    <div class="alerte alerte-erreur"><?php echo $erreur; ?></div>
  <?php endif; ?>
  <?php if ($succes): ?>
    <div class="alerte alerte-succes"><?php echo $succes; ?></div>
  <?php endif; ?>

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

  <!-- CRÉER UN COMPTE -->
  <div class="dashboard-section">
    <h2>Créer un compte</h2>
    <form method="POST" action="dashboard_admin.php" class="form-service">
      <div class="form-ligne">
        <div class="form-groupe">
          <label>Prénom *</label>
          <input type="text" name="prenom" placeholder="Prénom" required>
        </div>
        <div class="form-groupe">
          <label>Nom *</label>
          <input type="text" name="nom" placeholder="Nom" required>
        </div>
        <div class="form-groupe">
          <label>Rôle *</label>
          <select name="role">
            <option value="intervenant">Intervenant</option>
            <option value="admin">Admin</option>
            <option value="senior">Senior</option>
          </select>
        </div>
      </div>
      <div class="form-ligne">
        <div class="form-groupe">
          <label>Email *</label>
          <input type="email" name="email" placeholder="email@vitacare.fr" required>
        </div>
        <div class="form-groupe">
          <label>Mot de passe *</label>
          <input type="password" name="mot_de_passe" placeholder="6 caractères minimum" required>
        </div>
      </div>
      <button type="submit" name="btn_creer_compte" class="btn-dashboard">Créer le compte</button>
    </form>
  </div>

  <!-- LISTE UTILISATEURS -->
  <div class="dashboard-section">
    <h2>Utilisateurs</h2>
    <table class="tableau">
      <tr>
        <th>ID</th>
        <th>Prénom</th>
        <th>Nom</th>
        <th>Email</th>
        <th>Rôle</th>
        <th>Inscription</th>
        <th>Statut</th>
        <th>Action</th>
      </tr>
      <?php
      $result = mysqli_query($conn, "SELECT * FROM utilisateurs ORDER BY id DESC");
      while ($row = mysqli_fetch_assoc($result)) {
        $statut = $row['actif'] ? '<span class="badge-role badge-confirmee">Actif</span>' : '<span class="badge-role badge-annulee">Inactif</span>';
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['prenom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['email']) . '</td>';
        echo '<td><span class="badge-role badge-' . $row['role'] . '">' . htmlspecialchars($row['role']) . '</span></td>';
        echo '<td>' . htmlspecialchars($row['date_inscription']) . '</td>';
        echo '<td>' . $statut . '</td>';
        echo '<td>';
        if ($row['id'] !== (int)$_SESSION['id']) {
          echo '<form method="POST" action="dashboard_admin.php" style="display:inline;">';
          echo '<input type="hidden" name="id_utilisateur" value="' . $row['id'] . '">';
          if ($row['actif']) {
            echo '<button type="submit" name="btn_supprimer" class="btn-danger" onclick="return confirm(\'Désactiver ce compte ?\')">Désactiver</button>';
          }
          echo '</form>';
        } else {
          echo '<span style="color:#999; font-size:12px;">Vous</span>';
        }
        echo '</td>';
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
