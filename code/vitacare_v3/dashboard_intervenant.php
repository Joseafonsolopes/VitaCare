<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Espace Intervenant</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php
session_start();
require_once 'php/connexion.php';

if (!isset($_SESSION['id']) || $_SESSION['role'] !== 'intervenant') {
  header('Location: connexion.php');
  exit();
}

$id_intervenant = $_SESSION['id'];
$erreur = '';
$succes = '';

// Créer un service
if (isset($_POST['btn_ajouter_service'])) {
  $nom         = htmlspecialchars($_POST['nom']);
  $description = htmlspecialchars($_POST['description']);
  $categorie   = htmlspecialchars($_POST['categorie']);
  $duree       = (int)$_POST['duree'];
  $prix        = (float)$_POST['prix'];
  $emoji       = htmlspecialchars($_POST['emoji']);
  $date_today  = date('Y-m-d');

  if (empty($nom) || empty($categorie) || $duree <= 0 || $prix <= 0) {
    $erreur = 'Veuillez remplir tous les champs obligatoires.';
  } else {
    $sql = "INSERT INTO services (id_intervenant, nom, description, emoji, categorie, duree, prix, date_creation)
            VALUES ('$id_intervenant', '$nom', '$description', '$emoji', '$categorie', '$duree', '$prix', '$date_today')";
    if (mysqli_query($conn, $sql)) {
      $succes = 'Service "' . $nom . '" créé avec succès !';
    } else {
      $erreur = 'Erreur : ' . mysqli_error($conn);
    }
  }
}

// Ajouter un créneau
if (isset($_POST['btn_ajouter_creneau'])) {
  $id_service      = (int)$_POST['id_service'];
  $date_creneau    = $_POST['date_creneau'];
  $heure           = htmlspecialchars($_POST['heure']);
  $places_total    = (int)$_POST['places_total'];

  if (empty($date_creneau) || empty($heure) || $places_total <= 0) {
    $erreur = 'Veuillez remplir tous les champs du créneau.';
  } else {
    // Vérifier que le service appartient bien à cet intervenant
    $check = mysqli_query($conn, "SELECT id FROM services WHERE id = '$id_service' AND id_intervenant = '$id_intervenant'");
    if (mysqli_num_rows($check) === 0) {
      $erreur = 'Service invalide.';
    } else {
      $sql = "INSERT INTO creneaux (id_service, date_creneau, heure, places_total, places_restantes)
              VALUES ('$id_service', '$date_creneau', '$heure', '$places_total', '$places_total')";
      if (mysqli_query($conn, $sql)) {
        $succes = 'Créneau ajouté avec succès !';
      } else {
        $erreur = 'Erreur : ' . mysqli_error($conn);
      }
    }
  }
}
?>

<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.html">Accueil</a></li>
    <li><a href="dashboard_intervenant.php" class="actif">Mon espace</a></li>
  </ul>
  <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
</nav>

<div class="dashboard-page">

  <div class="dashboard-header">
    <h1>Mon espace intervenant</h1>
    <p>Bonjour <?php echo htmlspecialchars($_SESSION['prenom']); ?> 👋</p>
  </div>

  <?php if ($erreur): ?>
    <div class="alerte alerte-erreur"><?php echo $erreur; ?></div>
  <?php endif; ?>
  <?php if ($succes): ?>
    <div class="alerte alerte-succes"><?php echo $succes; ?></div>
  <?php endif; ?>

  <!-- CRÉER UN SERVICE -->
  <div class="dashboard-section">
    <h2>Créer un nouveau service</h2>
    <form method="POST" action="dashboard_intervenant.php" class="form-service">
      <div class="form-ligne">
        <div class="form-groupe">
          <label>Nom du service *</label>
          <input type="text" name="nom" placeholder="Ex: Yoga Douceur" required>
        </div>
        <div class="form-groupe">
          <label>Emoji</label>
          <input type="text" name="emoji" placeholder="🧘" maxlength="2" value="🧘">
        </div>
      </div>
      <div class="form-groupe">
        <label>Description</label>
        <input type="text" name="description" placeholder="Décrivez votre service...">
      </div>
      <div class="form-ligne">
        <div class="form-groupe">
          <label>Catégorie *</label>
          <select name="categorie">
            <option value="Bien-être">Bien-être</option>
            <option value="Nutrition">Nutrition</option>
            <option value="Santé mentale">Santé mentale</option>
            <option value="Activités">Activités</option>
            <option value="Thérapies">Thérapies</option>
            <option value="Consultations">Consultations</option>
          </select>
        </div>
        <div class="form-groupe">
          <label>Durée (minutes) *</label>
          <input type="number" name="duree" placeholder="60" min="15" required>
        </div>
        <div class="form-groupe">
          <label>Prix (€) *</label>
          <input type="number" name="prix" placeholder="45" min="0" step="0.01" required>
        </div>
      </div>
      <button type="submit" name="btn_ajouter_service" class="btn-dashboard">
        Créer le service
      </button>
    </form>
  </div>

  <!-- MES SERVICES + CRÉNEAUX -->
  <div class="dashboard-section">
    <h2>Mes services et créneaux</h2>
    <?php
    $result_services = mysqli_query($conn, "SELECT s.*, COUNT(DISTINCT r.id) as nb_reservations
                                           FROM services s
                                           LEFT JOIN creneaux c ON c.id_service = s.id
                                           LEFT JOIN reservations r ON r.id_creneau = c.id
                                           WHERE s.id_intervenant = '$id_intervenant'
                                           GROUP BY s.id
                                           ORDER BY s.id DESC");
    if (mysqli_num_rows($result_services) > 0) {
      while ($service = mysqli_fetch_assoc($result_services)) {
        echo '<div class="service-bloc">';
        echo '<div class="service-bloc-header">';
        echo '<span class="service-bloc-emoji">' . htmlspecialchars($service['emoji']) . '</span>';
        echo '<div>';
        echo '<strong>' . htmlspecialchars($service['nom']) . '</strong>';
        echo '<span class="service-bloc-meta">' . htmlspecialchars($service['categorie']) . ' · ' . htmlspecialchars($service['duree']) . ' min · ' . htmlspecialchars($service['prix']) . '€</span>';
        echo '</div>';
        echo '<span class="badge-reservations">' . htmlspecialchars($service['nb_reservations']) . ' réservation(s)</span>';
        echo '</div>';

        // Créneaux de ce service
        $id_s = $service['id'];
        $result_creneaux = mysqli_query($conn, "SELECT c.*, COUNT(r.id) as nb_inscrits
                                               FROM creneaux c
                                               LEFT JOIN reservations r ON r.id_creneau = c.id
                                               WHERE c.id_service = '$id_s'
                                               ORDER BY c.date_creneau ASC, c.heure ASC");

        if (mysqli_num_rows($result_creneaux) > 0) {
          echo '<table class="tableau" style="margin-top:12px;">';
          echo '<tr><th>Date</th><th>Heure</th><th>Places total</th><th>Places restantes</th><th>Inscrits</th></tr>';
          while ($creneau = mysqli_fetch_assoc($result_creneaux)) {
            $places_restantes = isset($creneau['places_restantes']) ? (int)$creneau['places_restantes'] : 0;
            $places_total     = isset($creneau['places_total'])     ? (int)$creneau['places_total']     : 0;
            $nb_inscrits      = isset($creneau['nb_inscrits'])      ? (int)$creneau['nb_inscrits']      : 0;
            $date_creneau     = isset($creneau['date_creneau'])     ? $creneau['date_creneau']           : '';
            $heure            = isset($creneau['heure'])            ? $creneau['heure']                 : '';
            $classe_places = $places_restantes === 0 ? 'badge-annulee' : ($places_restantes <= 3 ? 'badge-en_attente' : 'badge-confirmee');
            echo '<tr>';
            echo '<td>' . htmlspecialchars($date_creneau) . '</td>';
            echo '<td>' . htmlspecialchars($heure) . '</td>';
            echo '<td>' . $places_total . '</td>';
            echo '<td><span class="badge-role ' . $classe_places . '">' . $places_restantes . ' places</span></td>';
            echo '<td>' . $nb_inscrits . '</td>';
            echo '</tr>';
          }
          echo '</table>';
        } else {
          echo '<p class="aucun-creneau">Aucun créneau pour ce service.</p>';
        }

        // Formulaire ajouter créneau
        echo '<div class="form-creneau">';
        echo '<p class="form-creneau-titre">Ajouter un créneau</p>';
        echo '<form method="POST" action="dashboard_intervenant.php">';
        echo '<input type="hidden" name="id_service" value="' . $id_s . '">';
        echo '<div class="creneau-ligne">';
        echo '<div class="form-groupe"><label>Date *</label><input type="date" name="date_creneau" required></div>';
        echo '<div class="form-groupe"><label>Heure *</label><input type="time" name="heure" required></div>';
        echo '<div class="form-groupe"><label>Nb places *</label><input type="number" name="places_total" min="1" value="10" required></div>';
        echo '<button type="submit" name="btn_ajouter_creneau" class="btn-dashboard">Ajouter</button>';
        echo '</div>';
        echo '</form>';
        echo '</div>';

        echo '</div>';
      }
    } else {
      echo '<p style="color:#999;">Aucun service créé pour le moment.</p>';
    }
    ?>
  </div>

  <!-- SUIVI DES INSCRIPTIONS EN TEMPS RÉEL -->
  <div class="dashboard-section">
    <h2>Inscriptions récentes</h2>
    <table class="tableau">
      <tr>
        <th>Utilisateur</th>
        <th>Service</th>
        <th>Date créneau</th>
        <th>Heure</th>
        <th>Statut</th>
        <th>Réservé le</th>
      </tr>
      <?php
      $result_inscrits = mysqli_query($conn, "SELECT u.prenom, u.nom, s.nom AS service, c.date_creneau, c.heure, r.statut, r.date_reservation
                                             FROM reservations r, utilisateurs u, creneaux c, services s
                                             WHERE r.id_utilisateur = u.id
                                             AND r.id_creneau = c.id
                                             AND c.id_service = s.id
                                             AND s.id_intervenant = '$id_intervenant'
                                             ORDER BY r.id DESC");
      if (mysqli_num_rows($result_inscrits) > 0) {
        while ($row = mysqli_fetch_assoc($result_inscrits)) {
          echo '<tr>';
          echo '<td>' . htmlspecialchars($row['prenom']) . ' ' . htmlspecialchars($row['nom']) . '</td>';
          echo '<td>' . htmlspecialchars($row['service']) . '</td>';
          echo '<td>' . htmlspecialchars($row['date_creneau']) . '</td>';
          echo '<td>' . htmlspecialchars($row['heure']) . '</td>';
          echo '<td><span class="badge-role badge-' . $row['statut'] . '">' . htmlspecialchars($row['statut']) . '</span></td>';
          echo '<td>' . htmlspecialchars($row['date_reservation']) . '</td>';
          echo '</tr>';
        }
      } else {
        echo '<tr><td colspan="6" style="text-align:center; color:#999;">Aucune inscription pour le moment.</td></tr>';
      }
      ?>
    </table>
  </div>

</div>
</body>
</html>
