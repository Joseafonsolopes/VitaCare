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

<?php
// STATS GLOBALES
$stats_utilisateurs = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM utilisateurs WHERE role = 'utilisateur' AND actif = 1"));
$stats_intervenants = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM utilisateurs WHERE role = 'intervenant' AND actif = 1"));
$stats_services     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM services WHERE actif = 1"));
$stats_reservations = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM reservations WHERE statut = 'confirmee'"));
$stats_revenus      = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COALESCE(SUM(montant), 0) as total FROM factures f, reservations r WHERE f.id_reservation = r.id AND r.statut = 'confirmee'"));
$stats_messages     = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM messages"));
$stats_programmes   = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM inscriptions_programmes"));

// Services les plus populaires
$result_top_services = mysqli_query($conn, "SELECT s.nom, s.emoji, COUNT(r.id) as nb_resa, SUM(f.montant) as revenus
                                            FROM services s
                                            LEFT JOIN creneaux c ON c.id_service = s.id
                                            LEFT JOIN reservations r ON r.id_creneau = c.id AND r.statut = 'confirmee'
                                            LEFT JOIN factures f ON f.id_reservation = r.id
                                            WHERE s.actif = 1
                                            GROUP BY s.id
                                            ORDER BY nb_resa DESC
                                            LIMIT 5");

// Intervenants les plus actifs
$result_top_interv = mysqli_query($conn, "SELECT u.prenom, u.nom, COUNT(DISTINCT s.id) as nb_services, COUNT(r.id) as nb_resa
                                          FROM utilisateurs u
                                          LEFT JOIN services s ON s.id_intervenant = u.id AND s.actif = 1
                                          LEFT JOIN creneaux c ON c.id_service = s.id
                                          LEFT JOIN reservations r ON r.id_creneau = c.id AND r.statut = 'confirmee'
                                          WHERE u.role = 'intervenant' AND u.actif = 1
                                          GROUP BY u.id
                                          ORDER BY nb_resa DESC
                                          LIMIT 5");

// Réservations des 7 derniers jours
$result_recent = mysqli_query($conn, "SELECT DATE(r.date_reservation) as jour, COUNT(*) as nb
                                      FROM reservations r
                                      WHERE r.statut = 'confirmee'
                                      AND r.date_reservation >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                                      GROUP BY DATE(r.date_reservation)
                                      ORDER BY jour ASC");
?>

<?php require_once 'php/navbar_include.php'; ?>

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

  <!-- STATS GLOBALES -->
  <div class="admin-stats-grille">
    <div class="admin-stat-carte">
      <span class="admin-stat-icone">👥</span>
      <span class="admin-stat-nb"><?php echo $stats_utilisateurs['nb']; ?></span>
      <span class="admin-stat-label">Utilisateurs actifs</span>
    </div>
    <div class="admin-stat-carte">
      <span class="admin-stat-icone">🧑‍⚕️</span>
      <span class="admin-stat-nb"><?php echo $stats_intervenants['nb']; ?></span>
      <span class="admin-stat-label">Intervenants actifs</span>
    </div>
    <div class="admin-stat-carte">
      <span class="admin-stat-icone">🗓️</span>
      <span class="admin-stat-nb"><?php echo $stats_reservations['nb']; ?></span>
      <span class="admin-stat-label">Réservations confirmées</span>
    </div>
    <div class="admin-stat-carte">
      <span class="admin-stat-icone">💶</span>
      <span class="admin-stat-nb" style="color:var(--vert-fonce);"><?php echo number_format($stats_revenus['total'], 0); ?>€</span>
      <span class="admin-stat-label">Revenus totaux</span>
    </div>
    <div class="admin-stat-carte">
      <span class="admin-stat-icone">🏃</span>
      <span class="admin-stat-nb"><?php echo $stats_services['nb']; ?></span>
      <span class="admin-stat-label">Services actifs</span>
    </div>
    <div class="admin-stat-carte">
      <span class="admin-stat-icone">📋</span>
      <span class="admin-stat-nb"><?php echo $stats_programmes['nb']; ?></span>
      <span class="admin-stat-label">Inscriptions programmes</span>
    </div>
    <div class="admin-stat-carte">
      <span class="admin-stat-icone">💬</span>
      <span class="admin-stat-nb"><?php echo $stats_messages['nb']; ?></span>
      <span class="admin-stat-label">Messages échangés</span>
    </div>
  </div>

  <!-- TOP SERVICES + TOP INTERVENANTS -->
  <div class="admin-deux-colonnes">

    <div class="dashboard-section">
      <h2>🏆 Services les plus populaires</h2>
      <table class="tableau">
        <tr><th>Service</th><th>Réservations</th><th>Revenus</th></tr>
        <?php if (mysqli_num_rows($result_top_services) > 0):
          while ($s = mysqli_fetch_assoc($result_top_services)): ?>
          <tr>
            <td><?php echo htmlspecialchars($s['emoji'] . ' ' . $s['nom']); ?></td>
            <td style="text-align:center; font-weight:700;"><?php echo $s['nb_resa']; ?></td>
            <td style="text-align:right; font-weight:700; color:var(--vert-fonce);"><?php echo number_format($s['revenus'] ?? 0, 2); ?>€</td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="3" style="text-align:center; color:#999;">Aucune donnée.</td></tr>
        <?php endif; ?>
      </table>
    </div>

    <div class="dashboard-section">
      <h2>⭐ Intervenants les plus actifs</h2>
      <table class="tableau">
        <tr><th>Intervenant</th><th>Services</th><th>Réservations</th></tr>
        <?php if (mysqli_num_rows($result_top_interv) > 0):
          while ($i = mysqli_fetch_assoc($result_top_interv)): ?>
          <tr>
            <td><strong><?php echo htmlspecialchars($i['prenom'] . ' ' . $i['nom']); ?></strong></td>
            <td style="text-align:center;"><?php echo $i['nb_services']; ?></td>
            <td style="text-align:center; font-weight:700;"><?php echo $i['nb_resa']; ?></td>
          </tr>
          <?php endwhile; else: ?>
          <tr><td colspan="3" style="text-align:center; color:#999;">Aucune donnée.</td></tr>
        <?php endif; ?>
      </table>
    </div>

  </div>

  <!-- RÉSERVATIONS 7 DERNIERS JOURS -->
  <div class="dashboard-section">
    <h2>📈 Réservations des 7 derniers jours</h2>
    <?php if (mysqli_num_rows($result_recent) > 0): ?>
    <div class="admin-graphe">
      <?php
      $jours_data = array();
      while ($r = mysqli_fetch_assoc($result_recent)) {
        $jours_data[$r['jour']] = $r['nb'];
      }
      $max = max($jours_data) ?: 1;
      foreach ($jours_data as $jour => $nb):
        $hauteur = round(($nb / $max) * 120);
        $date_obj = new DateTime($jour);
        $label = $date_obj->format('d/m');
      ?>
      <div class="admin-barre-wrap">
        <span class="admin-barre-nb"><?php echo $nb; ?></span>
        <div class="admin-barre" style="height:<?php echo $hauteur; ?>px;"></div>
        <span class="admin-barre-label"><?php echo $label; ?></span>
      </div>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <p style="color:#999;">Aucune réservation cette semaine.</p>
    <?php endif; ?>
  </div>

  <!-- REVENUS PAR INTERVENANT -->
  <div class="dashboard-section">
    <h2>💰 Revenus par intervenant</h2>
    <?php
    $result_revenus_interv = mysqli_query($conn, "SELECT u.prenom, u.nom,
                                                         COUNT(DISTINCT s.id) as nb_services,
                                                         COUNT(r.id) as nb_reservations,
                                                         COALESCE(SUM(f.montant), 0) as revenus
                                                  FROM utilisateurs u
                                                  LEFT JOIN services s ON s.id_intervenant = u.id AND s.actif = 1
                                                  LEFT JOIN creneaux c ON c.id_service = s.id
                                                  LEFT JOIN reservations r ON r.id_creneau = c.id AND r.statut = 'confirmee'
                                                  LEFT JOIN factures f ON f.id_reservation = r.id
                                                  WHERE u.role = 'intervenant' AND u.actif = 1
                                                  GROUP BY u.id
                                                  ORDER BY revenus DESC");

    $total_revenus_global = 0;
    $intervenants_revenus = array();
    while ($row = mysqli_fetch_assoc($result_revenus_interv)) {
      $intervenants_revenus[] = $row;
      $total_revenus_global += $row['revenus'];
    }
    ?>

    <!-- Carte revenu global -->
    <div style="background:var(--vert-fonce); border-radius:var(--radius-lg); padding:20px 28px; margin-bottom:20px; display:flex; justify-content:space-between; align-items:center;">
      <div>
        <p style="color:rgba(255,255,255,0.7); font-size:13px; margin-bottom:4px;">Revenus totaux de la plateforme</p>
        <p style="color:white; font-size:32px; font-weight:700; font-family:'Playfair Display', serif;"><?php echo number_format($total_revenus_global, 2); ?>€</p>
      </div>
      <div style="text-align:right;">
        <p style="color:rgba(255,255,255,0.7); font-size:13px; margin-bottom:4px;">Réservations confirmées</p>
        <p style="color:white; font-size:28px; font-weight:700;"><?php echo $stats_reservations['nb']; ?></p>
      </div>
    </div>

    <table class="tableau">
      <tr>
        <th>Intervenant</th>
        <th>Services actifs</th>
        <th>Réservations</th>
        <th>Revenus générés</th>
        <th>Part du total</th>
      </tr>
      <?php if (!empty($intervenants_revenus)):
        foreach ($intervenants_revenus as $interv):
          $part = $total_revenus_global > 0 ? round(($interv['revenus'] / $total_revenus_global) * 100) : 0;
      ?>
      <tr>
        <td><strong><?php echo htmlspecialchars($interv['prenom'] . ' ' . $interv['nom']); ?></strong></td>
        <td style="text-align:center;"><?php echo $interv['nb_services']; ?></td>
        <td style="text-align:center; font-weight:600;"><?php echo $interv['nb_reservations']; ?></td>
        <td style="text-align:right; font-weight:700; color:var(--vert-fonce); font-size:15px;"><?php echo number_format($interv['revenus'], 2); ?>€</td>
        <td style="min-width:140px;">
          <div style="background:#e8f0ea; border-radius:50px; height:8px; overflow:hidden; margin-bottom:3px;">
            <div style="background:var(--vert-fonce); height:100%; width:<?php echo $part; ?>%; border-radius:50px;"></div>
          </div>
          <span style="font-size:12px; color:var(--texte-gris);"><?php echo $part; ?>%</span>
        </td>
      </tr>
      <?php endforeach; else: ?>
        <tr><td colspan="5" style="text-align:center; color:#999;">Aucun intervenant actif.</td></tr>
      <?php endif; ?>
    </table>
  </div>
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
            <option value="utilisateur">Utilisateur</option>
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
    <div class="section-header" style="margin-bottom:16px;">
      <h2>Utilisateurs</h2>
      <span id="compteur-users" style="font-size:13px; color:var(--texte-gris);"></span>
    </div>

    <!-- Recherche et filtres -->
    <div class="inscrits-filtres" style="margin-bottom:16px; flex-wrap:wrap;">
      <input type="text" id="recherche-user" placeholder="🔍 Rechercher par nom, prénom, email..." oninput="filtrerUsers()"
        style="flex:1; min-width:220px; padding:9px 14px; border:1.5px solid #dde8e0; border-radius:50px; font-size:14px; font-family:'DM Sans',sans-serif; outline:none;">
      <select id="filtre-role" onchange="filtrerUsers()" class="select-tri">
        <option value="">Tous les rôles</option>
        <option value="utilisateur">Utilisateur</option>
        <option value="intervenant">Intervenant</option>
        <option value="admin">Admin</option>
      </select>
      <select id="filtre-statut-user" onchange="filtrerUsers()" class="select-tri">
        <option value="">Tous les statuts</option>
        <option value="actif">Actif</option>
        <option value="inactif">Inactif</option>
      </select>
      <select id="tri-user" onchange="filtrerUsers()" class="select-tri">
        <option value="id_desc">Plus récent</option>
        <option value="id_asc">Plus ancien</option>
        <option value="nom_asc">Nom A→Z</option>
        <option value="nom_desc">Nom Z→A</option>
        <option value="date_asc">Inscription ↑</option>
        <option value="date_desc">Inscription ↓</option>
      </select>
      <button onclick="reinitFiltres()" class="btn-annuler-modif" style="padding:8px 16px; white-space:nowrap;">↺ Réinitialiser</button>
    </div>

    <table class="tableau" id="tableau-users">
      <tr>
        <th style="cursor:pointer;" onclick="trierColonne('id')">ID ↕</th>
        <th style="cursor:pointer;" onclick="trierColonne('prenom')">Prénom ↕</th>
        <th style="cursor:pointer;" onclick="trierColonne('nom')">Nom ↕</th>
        <th>Email</th>
        <th style="cursor:pointer;" onclick="trierColonne('role')">Rôle ↕</th>
        <th style="cursor:pointer;" onclick="trierColonne('date')">Inscription ↕</th>
        <th>Statut</th>
        <th>Action</th>
      </tr>
      <?php
      $result = mysqli_query($conn, "SELECT * FROM utilisateurs ORDER BY id DESC");
      while ($row = mysqli_fetch_assoc($result)) {
        $statut_txt = $row['actif'] ? 'actif' : 'inactif';
        $statut_badge = $row['actif'] ? '<span class="badge-role badge-confirmee">Actif</span>' : '<span class="badge-role badge-annulee">Inactif</span>';
        echo '<tr class="ligne-user" data-nom="' . strtolower($row['nom']) . '" data-prenom="' . strtolower($row['prenom']) . '" data-email="' . strtolower($row['email']) . '" data-role="' . $row['role'] . '" data-statut="' . $statut_txt . '" data-date="' . $row['date_inscription'] . '" data-id="' . $row['id'] . '">';
        echo '<td>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['prenom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nom']) . '</td>';
        echo '<td style="font-size:13px;">' . htmlspecialchars($row['email']) . '</td>';
        $role_affiche = ($row['role'] === 'senior') ? 'utilisateur' : $row['role'];
        echo '<td><span class="badge-role badge-' . $role_affiche . '">' . htmlspecialchars($role_affiche) . '</span></td>';
        echo '<td>' . htmlspecialchars($row['date_inscription']) . '</td>';
        echo '<td>' . $statut_badge . '</td>';
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

  <script>
  function filtrerUsers() {
    var recherche = document.getElementById('recherche-user').value.toLowerCase();
    var role      = document.getElementById('filtre-role').value;
    var statut    = document.getElementById('filtre-statut-user').value;
    var lignes    = document.querySelectorAll('.ligne-user');
    var nb = 0;

    lignes.forEach(function(l) {
      var matchRecherche = !recherche ||
        l.dataset.nom.includes(recherche) ||
        l.dataset.prenom.includes(recherche) ||
        l.dataset.email.includes(recherche);
      var matchRole   = !role   || l.dataset.role   === role;
      var matchStatut = !statut || l.dataset.statut === statut;

      var visible = matchRecherche && matchRole && matchStatut;
      l.style.display = visible ? '' : 'none';
      if (visible) nb++;
    });

    document.getElementById('compteur-users').textContent = nb + ' résultat(s)';

    // Tri
    var tri = document.getElementById('tri-user').value;
    var tbody = document.getElementById('tableau-users');
    var rows = Array.from(document.querySelectorAll('.ligne-user'));

    rows.sort(function(a, b) {
      if (tri === 'id_asc')    return parseInt(a.dataset.id) - parseInt(b.dataset.id);
      if (tri === 'id_desc')   return parseInt(b.dataset.id) - parseInt(a.dataset.id);
      if (tri === 'nom_asc')   return a.dataset.nom.localeCompare(b.dataset.nom);
      if (tri === 'nom_desc')  return b.dataset.nom.localeCompare(a.dataset.nom);
      if (tri === 'date_asc')  return a.dataset.date.localeCompare(b.dataset.date);
      if (tri === 'date_desc') return b.dataset.date.localeCompare(a.dataset.date);
      return 0;
    });

    rows.forEach(function(r) { tbody.appendChild(r); });
  }

  function trierColonne(col) {
    var select = document.getElementById('tri-user');
    var actuel = select.value;
    if (col === 'id')     select.value = actuel === 'id_desc' ? 'id_asc' : 'id_desc';
    if (col === 'nom')    select.value = actuel === 'nom_asc' ? 'nom_desc' : 'nom_asc';
    if (col === 'prenom') select.value = actuel === 'nom_asc' ? 'nom_desc' : 'nom_asc';
    if (col === 'date')   select.value = actuel === 'date_desc' ? 'date_asc' : 'date_desc';
    if (col === 'role')   select.value = 'id_desc';
    filtrerUsers();
  }

  function reinitFiltres() {
    document.getElementById('recherche-user').value = '';
    document.getElementById('filtre-role').value = '';
    document.getElementById('filtre-statut-user').value = '';
    document.getElementById('tri-user').value = 'id_desc';
    filtrerUsers();
  }

  // Init compteur
  document.addEventListener('DOMContentLoaded', function() { filtrerUsers(); });
  </script>

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
