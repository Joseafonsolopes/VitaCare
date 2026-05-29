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
        header('Location: dashboard_intervenant.php?succes=creneau');
        exit();
      } else {
        $erreur = 'Erreur : ' . mysqli_error($conn);
      }
    }
  }
}

// Modifier un créneau (règle 72h)
if (isset($_POST['btn_modifier_creneau'])) {
  $id_creneau    = (int)$_POST['id_creneau_modif'];
  $nouvelle_date = $_POST['nouvelle_date'];
  $nouvelle_heure = htmlspecialchars($_POST['nouvelle_heure']);
  $nouvelles_places = (int)$_POST['nouvelles_places'];

  $check = mysqli_query($conn, "SELECT c.id, c.date_creneau, c.heure, c.places_restantes, c.places_total
                                 FROM creneaux c, services s
                                 WHERE c.id = '$id_creneau' AND c.id_service = s.id AND s.id_intervenant = '$id_intervenant'");
  if (mysqli_num_rows($check) > 0) {
    $cr = mysqli_fetch_assoc($check);
    $date_obj = new DateTime($cr['date_creneau'] . ' ' . str_replace('h', ':', $cr['heure']));
    $maintenant = new DateTime();
    $heures_restantes = ($date_obj->getTimestamp() - $maintenant->getTimestamp()) / 3600;

    if ($heures_restantes < 72) {
      $erreur = 'Impossible de modifier ce créneau — il a lieu dans moins de 72h.';
    } elseif ($nouvelles_places <= 0) {
      $erreur = 'Le nombre de places doit être supérieur à 0.';
    } else {
      // Calculer les places restantes selon les nouvelles places
      $places_prises = $cr['places_total'] - $cr['places_restantes'];
      $nouvelles_restantes = max(0, $nouvelles_places - $places_prises);
      mysqli_query($conn, "UPDATE creneaux SET date_creneau = '$nouvelle_date', heure = '$nouvelle_heure', places_total = '$nouvelles_places', places_restantes = '$nouvelles_restantes' WHERE id = '$id_creneau'");
      $succes = 'Créneau modifié avec succès !';
    }
  }
}

// Supprimer un créneau (règle 72h)
if (isset($_POST['btn_supprimer_creneau'])) {
  $id_creneau = (int)$_POST['id_creneau'];
  $check = mysqli_query($conn, "SELECT c.id, c.date_creneau, c.heure FROM creneaux c, services s
                                 WHERE c.id = '$id_creneau' AND c.id_service = s.id AND s.id_intervenant = '$id_intervenant'");
  if (mysqli_num_rows($check) > 0) {
    $cr = mysqli_fetch_assoc($check);
    $date_creneau = new DateTime($cr['date_creneau'] . ' ' . str_replace('h', ':', $cr['heure']));
    $maintenant   = new DateTime();
    $heures_restantes = ($date_creneau->getTimestamp() - $maintenant->getTimestamp()) / 3600;
    if ($heures_restantes < 72) {
      $erreur = 'Impossible de supprimer ce créneau — il a lieu dans moins de 72h.';
    } else {
      mysqli_query($conn, "DELETE FROM reservations WHERE id_creneau = '$id_creneau'");
      mysqli_query($conn, "DELETE FROM creneaux WHERE id = '$id_creneau'");
      $succes = 'Créneau supprimé.';
    }
  }
}

// Supprimer un service (seulement si aucun créneau dans les 72h)
if (isset($_POST['btn_supprimer_service'])) {
  $id_service = (int)$_POST['id_service_suppr'];
  $check = mysqli_query($conn, "SELECT id FROM services WHERE id = '$id_service' AND id_intervenant = '$id_intervenant'");
  if (mysqli_num_rows($check) > 0) {
    // Vérifier si un créneau est dans moins de 72h
    $maintenant = new DateTime();
    $limite = clone $maintenant;
    $limite->modify('+72 hours');
    $limite_str = $limite->format('Y-m-d H:i:s');
    $check72 = mysqli_query($conn, "SELECT id FROM creneaux WHERE id_service = '$id_service' AND CONCAT(date_creneau, ' ', heure) <= '$limite_str' AND CONCAT(date_creneau, ' ', heure) >= NOW()");
    if (mysqli_num_rows($check72) > 0) {
      $erreur = 'Impossible de supprimer ce service — un créneau a lieu dans moins de 72h.';
    } else {
      mysqli_query($conn, "DELETE FROM reservations WHERE id_creneau IN (SELECT id FROM creneaux WHERE id_service = '$id_service')");
      mysqli_query($conn, "DELETE FROM creneaux WHERE id_service = '$id_service'");
      mysqli_query($conn, "UPDATE services SET actif = 0 WHERE id = '$id_service'");
      $succes = 'Service désactivé avec succès.';
    }
  }
}
if (isset($_GET['succes']) && $_GET['succes'] === 'creneau') {
  $succes = 'Créneau ajouté avec succès !';
}
if (isset($_GET['succes']) && $_GET['succes'] === 'programme') {
  $succes = 'Programme créé avec succès !';
}

// Créer un programme
if (isset($_POST['btn_ajouter_programme'])) {
  $nom_prog    = htmlspecialchars($_POST['nom_programme']);
  $desc_prog   = htmlspecialchars($_POST['desc_programme']);
  $id_service  = (int)$_POST['id_service_prog'];
  $nb_seances  = (int)$_POST['nb_seances'];
  $duree_jours = (int)$_POST['duree_jours'];
  $prix_prog   = (float)$_POST['prix_programme'];
  $date_debut  = $_POST['date_debut'];

  if (empty($nom_prog) || $nb_seances <= 0 || $duree_jours <= 0 || empty($date_debut)) {
    $erreur = 'Veuillez remplir tous les champs du programme.';
  } else {
    $check = mysqli_query($conn, "SELECT id FROM services WHERE id = '$id_service' AND id_intervenant = '$id_intervenant'");
    if (mysqli_num_rows($check) === 0) {
      $erreur = 'Service invalide.';
    } else {
      $sql = "INSERT INTO programmes (id_service, nom, description, nb_seances, duree_jours, prix, date_debut)
              VALUES ('$id_service', '$nom_prog', '$desc_prog', '$nb_seances', '$duree_jours', '$prix_prog', '$date_debut')";
      if (mysqli_query($conn, $sql)) {
        header('Location: dashboard_intervenant.php?succes=programme');
        exit();
      } else {
        $erreur = 'Erreur : ' . mysqli_error($conn);
      }
    }
  }
}
?>

<?php require_once 'php/navbar_include.php'; ?>

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
                                               GROUP BY c.id
                                               ORDER BY c.date_creneau ASC, c.heure ASC");

        if (mysqli_num_rows($result_creneaux) > 0) {
          echo '<table class="tableau" style="margin-top:12px;">';
          echo '<tr><th>Date</th><th>Heure</th><th>Places total</th><th>Places restantes</th><th>Inscrits</th><th>Action</th></tr>';
          while ($creneau = mysqli_fetch_assoc($result_creneaux)) {
            $places_restantes = isset($creneau['places_restantes']) ? (int)$creneau['places_restantes'] : 0;
            $places_total     = isset($creneau['places_total'])     ? (int)$creneau['places_total']     : 0;
            $nb_inscrits      = isset($creneau['nb_inscrits'])      ? (int)$creneau['nb_inscrits']      : 0;
            $date_creneau     = isset($creneau['date_creneau'])     ? $creneau['date_creneau']           : '';
            $heure            = isset($creneau['heure'])            ? $creneau['heure']                 : '';
            $classe_places = $places_restantes === 0 ? 'badge-annulee' : ($places_restantes <= 3 ? 'badge-en_attente' : 'badge-confirmee');

            // Vérifier règle 72h
            $heure_norm = str_replace('h', ':', $heure);
            try {
              $date_obj = new DateTime($date_creneau . ' ' . $heure_norm);
              $maintenant = new DateTime();
              $heures_restantes = ($date_obj->getTimestamp() - $maintenant->getTimestamp()) / 3600;
              $peut_supprimer = $heures_restantes >= 72;
            } catch (Exception $e) {
              $peut_supprimer = false;
            }

            echo '<tr>';
            echo '<td>' . htmlspecialchars($date_creneau) . '</td>';
            echo '<td>' . htmlspecialchars($heure) . '</td>';
            echo '<td>' . $places_total . '</td>';
            echo '<td><span class="badge-role ' . $classe_places . '">' . $places_restantes . ' places</span></td>';
            echo '<td>' . $nb_inscrits . '</td>';
            echo '<td>';
            if ($peut_supprimer) {
              // Bouton modifier
              echo '<button type="button" onclick="toggleModifCreneau(' . $creneau['id'] . ')" class="btn-dashboard" style="font-size:11px; padding:4px 10px; margin-right:4px;">✏️ Modifier</button>';
              // Bouton supprimer
              echo '<form method="POST" action="dashboard_intervenant.php" style="display:inline;">';
              echo '<input type="hidden" name="id_creneau" value="' . $creneau['id'] . '">';
              echo '<button type="submit" name="btn_supprimer_creneau" class="btn-danger" style="font-size:11px; padding:4px 10px;" onclick="return confirm(\'Supprimer ce créneau ?\')">🗑️</button>';
              echo '</form>';
            } else {
              echo '<span style="font-size:11px; color:#bbb;">⏰ &lt; 72h</span>';
            }
            echo '</td>';
            echo '</tr>';

            // Formulaire de modification inline (caché par défaut)
            if ($peut_supprimer) {
              echo '<tr id="modif-creneau-' . $creneau['id'] . '" style="display:none; background:#f5faf6;">';
              echo '<td colspan="6" style="padding:14px;">';
              echo '<form method="POST" action="dashboard_intervenant.php" style="display:flex; gap:12px; align-items:flex-end; flex-wrap:wrap;">';
              echo '<input type="hidden" name="id_creneau_modif" value="' . $creneau['id'] . '">';
              echo '<div class="form-groupe"><label style="font-size:12px;">Nouvelle date</label><input type="date" name="nouvelle_date" value="' . htmlspecialchars($date_creneau) . '" required></div>';
              echo '<div class="form-groupe"><label style="font-size:12px;">Nouvelle heure</label><input type="time" name="nouvelle_heure" value="' . htmlspecialchars($heure) . '" required></div>';
              echo '<div class="form-groupe"><label style="font-size:12px;">Nb places total</label><input type="number" name="nouvelles_places" value="' . $places_total . '" min="1" required></div>';
              echo '<button type="submit" name="btn_modifier_creneau" class="btn-dashboard" style="font-size:13px;">💾 Sauvegarder</button>';
              echo '<button type="button" onclick="toggleModifCreneau(' . $creneau['id'] . ')" class="btn-retour-panier" style="font-size:13px; padding:8px 16px;">Annuler</button>';
              echo '</form>';
              echo '</td>';
              echo '</tr>';
            }
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

        // Bouton supprimer le service
        echo '<div style="margin-top:12px; text-align:right;">';
        echo '<form method="POST" action="dashboard_intervenant.php" style="display:inline;">';
        echo '<input type="hidden" name="id_service_suppr" value="' . $id_s . '">';
        echo '<button type="submit" name="btn_supprimer_service" class="btn-danger" onclick="return confirm(\'Désactiver ce service et supprimer ses créneaux ?\')">🗑️ Désactiver le service</button>';
        echo '</form>';
        echo '</div>';

        echo '</div>';
      }
    } else {
      echo '<p style="color:#999;">Aucun service créé pour le moment.</p>';
    }
    ?>
  </div>

  <!-- SUIVI DES INSCRIPTIONS PAR ACTIVITÉ -->
  <div class="dashboard-section">
    <h2>Suivi des inscriptions par activité</h2>

    <?php
    // Récupérer les services de l'intervenant
    $result_mes_services = mysqli_query($conn, "SELECT id, nom, emoji FROM services WHERE id_intervenant = '$id_intervenant' AND actif = 1 ORDER BY nom ASC");
    $services_liste = array();
    while ($s = mysqli_fetch_assoc($result_mes_services)) {
      $services_liste[] = $s;
    }
    ?>

    <?php if (empty($services_liste)): ?>
      <p style="color:#999;">Vous n'avez pas encore de services actifs.</p>
    <?php else: ?>

    <!-- Sélecteur de service -->
    <div class="inscrits-filtres">
      <label>Choisir une activité :</label>
      <select id="select-activite" onchange="chargerCreneaux()" class="select-tri" style="min-width:220px;">
        <option value="">— Sélectionner une activité —</option>
        <?php foreach ($services_liste as $s): ?>
          <option value="<?php echo $s['id']; ?>"><?php echo htmlspecialchars($s['emoji'] . ' ' . $s['nom']); ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Zone créneaux -->
    <div id="zone-creneaux" style="display:none; margin-top:20px;">
      <h3 id="titre-activite" style="font-size:15px; font-weight:700; margin-bottom:16px;"></h3>

      <!-- Tableau des créneaux -->
      <table class="tableau" id="tableau-creneaux">
        <tr>
          <th>Date</th>
          <th>Heure</th>
          <th>Places totales</th>
          <th>Inscrits confirmés</th>
          <th>Annulés</th>
          <th>Places restantes</th>
          <th>Détails</th>
        </tr>
        <?php
        // On charge tous les créneaux de tous les services — JS filtrera
        foreach ($services_liste as $srv):
          $result_creneaux = mysqli_query($conn, "SELECT c.id, c.date_creneau, c.heure, c.places_restantes,
                                                         c.places_restantes + COALESCE(SUM(CASE WHEN r.statut='confirmee' THEN r.nb_places ELSE 0 END), 0) as places_totales,
                                                         COUNT(CASE WHEN r.statut='confirmee' THEN 1 END) as nb_confirmes,
                                                         COUNT(CASE WHEN r.statut='annulee' THEN 1 END) as nb_annules
                                                  FROM creneaux c
                                                  LEFT JOIN reservations r ON r.id_creneau = c.id
                                                  WHERE c.id_service = '" . $srv['id'] . "'
                                                  GROUP BY c.id
                                                  ORDER BY c.date_creneau ASC, c.heure ASC");
          while ($cr = mysqli_fetch_assoc($result_creneaux)):
            $couleur_places = $cr['places_restantes'] == 0 ? '#e74c3c' : ($cr['places_restantes'] <= 2 ? '#f0a030' : '#2d5a3d');
        ?>
          <tr class="ligne-creneau" data-service="<?php echo $srv['id']; ?>" data-creneau="<?php echo $cr['id']; ?>" data-nom="<?php echo htmlspecialchars($srv['emoji'] . ' ' . $srv['nom']); ?>" style="display:none;">
            <td><?php echo htmlspecialchars($cr['date_creneau']); ?></td>
            <td><?php echo htmlspecialchars($cr['heure']); ?></td>
            <td style="text-align:center;"><?php echo $cr['places_totales']; ?></td>
            <td style="text-align:center; font-weight:700; color:#2d5a3d;"><?php echo $cr['nb_confirmes']; ?></td>
            <td style="text-align:center; color:#e74c3c;"><?php echo $cr['nb_annules']; ?></td>
            <td style="text-align:center; font-weight:700; color:<?php echo $couleur_places; ?>;"><?php echo $cr['places_restantes']; ?></td>
            <td>
              <?php if ($cr['nb_confirmes'] > 0): ?>
              <button onclick="voirParticipants(<?php echo $cr['id']; ?>, '<?php echo addslashes($cr['date_creneau']); ?>', '<?php echo addslashes($cr['heure']); ?>')" class="btn-voir-inscrits">
                👥 Voir (<?php echo $cr['nb_confirmes']; ?>)
              </button>
              <?php else: ?>
              <span style="color:#bbb; font-size:12px;">Aucun inscrit</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endwhile; endforeach; ?>
      </table>
    </div>

    <!-- Modal participants -->
    <div id="modal-participants" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.5); z-index:1000; align-items:center; justify-content:center;">
      <div style="background:white; border-radius:16px; padding:32px; max-width:700px; width:90%; max-height:80vh; overflow-y:auto; position:relative;">
        <button onclick="fermerModal()" style="position:absolute; top:16px; right:16px; background:none; border:none; font-size:20px; cursor:pointer; color:#999;">✕</button>
        <h3 id="modal-titre" style="font-size:18px; font-weight:700; margin-bottom:20px;"></h3>
        <div id="modal-contenu"></div>
      </div>
    </div>

    <script>
    // Données participants pour chaque créneau
    var participantsData = {
      <?php
      foreach ($services_liste as $srv):
        $result_parts = mysqli_query($conn, "SELECT r.id_creneau, u.prenom, u.nom, u.email, u.telephone, COALESCE(r.nb_places,1) as nb_places
                                             FROM reservations r, utilisateurs u, creneaux c, services s
                                             WHERE r.id_utilisateur = u.id
                                             AND r.id_creneau = c.id
                                             AND c.id_service = s.id
                                             AND s.id = '" . $srv['id'] . "'
                                             AND r.statut = 'confirmee'");
        while ($p = mysqli_fetch_assoc($result_parts)):
          echo $p['id_creneau'] . ': ' . $p['id_creneau'] . ',';
        endwhile;
      endforeach;
      ?>
    };

    // Stocker les participants par créneau
    var participants = {};
    <?php
    foreach ($services_liste as $srv):
      $result_parts = mysqli_query($conn, "SELECT r.id_creneau, u.prenom, u.nom, u.email, u.telephone, COALESCE(r.nb_places,1) as nb_places
                                           FROM reservations r, utilisateurs u, creneaux c
                                           WHERE r.id_utilisateur = u.id
                                           AND r.id_creneau = c.id
                                           AND c.id_service = '" . $srv['id'] . "'
                                           AND r.statut = 'confirmee'");
      while ($p = mysqli_fetch_assoc($result_parts)):
        $id_c = $p['id_creneau'];
        echo "if (!participants[$id_c]) participants[$id_c] = [];\n";
        echo "participants[$id_c].push({nom: '" . addslashes($p['prenom'] . ' ' . $p['nom']) . "', email: '" . addslashes($p['email']) . "', tel: '" . addslashes($p['telephone'] ?? '—') . "', places: " . $p['nb_places'] . "});\n";
      endwhile;
    endforeach;
    ?>

    function toggleModifCreneau(id) {
      var ligne = document.getElementById('modif-creneau-' + id);
      if (ligne) {
        ligne.style.display = ligne.style.display === 'none' ? '' : 'none';
      }
    }

    function chargerCreneaux() {
      var idService = document.getElementById('select-activite').value;
      var zone = document.getElementById('zone-creneaux');
      var lignes = document.querySelectorAll('.ligne-creneau');
      var titre = document.getElementById('titre-activite');

      if (!idService) { zone.style.display = 'none'; return; }

      // Trouver le nom du service
      var select = document.getElementById('select-activite');
      titre.textContent = 'Créneaux — ' + select.options[select.selectedIndex].text;

      lignes.forEach(function(l) {
        l.style.display = l.dataset.service == idService ? '' : 'none';
      });

      zone.style.display = 'block';
    }

    function voirParticipants(idCreneau, date, heure) {
      var modal = document.getElementById('modal-participants');
      var titre = document.getElementById('modal-titre');
      var contenu = document.getElementById('modal-contenu');

      titre.textContent = '👥 Participants — ' + date + ' à ' + heure;

      var parts = participants[idCreneau] || [];
      if (parts.length === 0) {
        contenu.innerHTML = '<p style="color:#999;">Aucun inscrit confirmé.</p>';
      } else {
        var html = '<table style="width:100%; border-collapse:collapse;">';
        html += '<tr style="background:#2d5a3d; color:white;"><th style="padding:10px; text-align:left;">Nom</th><th style="padding:10px; text-align:left;">Email</th><th style="padding:10px; text-align:left;">Téléphone</th><th style="padding:10px; text-align:center;">Places</th></tr>';
        parts.forEach(function(p, i) {
          var bg = i % 2 === 0 ? '#f9fbf9' : 'white';
          html += '<tr style="background:' + bg + ';">';
          html += '<td style="padding:10px; font-weight:600;">' + p.nom + '</td>';
          html += '<td style="padding:10px; font-size:13px;">' + p.email + '</td>';
          html += '<td style="padding:10px;">' + (p.tel || '—') + '</td>';
          html += '<td style="padding:10px; text-align:center;">' + p.places + '</td>';
          html += '</tr>';
        });
        html += '</table>';
        contenu.innerHTML = html;
      }

      modal.style.display = 'flex';
    }

    function fermerModal() {
      document.getElementById('modal-participants').style.display = 'none';
    }

    // Fermer modal en cliquant dehors
    document.getElementById('modal-participants').addEventListener('click', function(e) {
      if (e.target === this) fermerModal();
    });
    </script>

    <?php endif; ?>
  </div>

  <!-- CRÉER UN PROGRAMME -->
  <div class="dashboard-section">
    <h2>Créer un programme</h2>
    <p style="font-size:13px; color:var(--texte-gris); margin-bottom:16px;">Un programme est une série de séances sur une période définie. Les utilisateurs peuvent s'y inscrire et suivre leur progression.</p>

    <form method="POST" action="dashboard_intervenant.php" class="form-service">
      <div class="form-ligne">
        <div class="form-groupe">
          <label>Nom du programme *</label>
          <input type="text" name="nom_programme" placeholder="Ex: Programme bien-être 4 semaines" required>
        </div>
        <div class="form-groupe">
          <label>Service associé *</label>
          <select name="id_service_prog">
            <?php
            $result_s = mysqli_query($conn, "SELECT id, nom FROM services WHERE id_intervenant = '$id_intervenant' AND actif = 1");
            while ($s = mysqli_fetch_assoc($result_s)) {
              echo '<option value="' . $s['id'] . '">' . htmlspecialchars($s['nom']) . '</option>';
            }
            ?>
          </select>
        </div>
      </div>
      <div class="form-groupe">
        <label>Description</label>
        <input type="text" name="desc_programme" placeholder="Décrivez votre programme...">
      </div>
      <div class="form-ligne">
        <div class="form-groupe">
          <label>Nombre de séances *</label>
          <input type="number" name="nb_seances" min="1" value="4" required>
        </div>
        <div class="form-groupe">
          <label>Durée totale (jours) *</label>
          <input type="number" name="duree_jours" min="1" value="28" required>
        </div>
        <div class="form-groupe">
          <label>Prix (€) *</label>
          <input type="number" name="prix_programme" min="0" step="0.01" value="0" required>
        </div>
        <div class="form-groupe">
          <label>Date de début *</label>
          <input type="date" name="date_debut" required>
        </div>
      </div>
      <button type="submit" name="btn_ajouter_programme" class="btn-dashboard">Créer le programme</button>
    </form>
  </div>

  <!-- MES PROGRAMMES -->
  <div class="dashboard-section">
    <h2>Suivi des inscriptions par programme</h2>

    <?php
    $result_prog = mysqli_query($conn, "SELECT p.*, s.nom AS nom_service, COUNT(ip.id) as nb_inscrits
                                        FROM programmes p
                                        LEFT JOIN services s ON p.id_service = s.id
                                        LEFT JOIN inscriptions_programmes ip ON ip.id_programme = p.id
                                        WHERE s.id_intervenant = '$id_intervenant'
                                        GROUP BY p.id
                                        ORDER BY p.id DESC");
    $programmes_liste = array();
    while ($prog = mysqli_fetch_assoc($result_prog)) {
      $programmes_liste[] = $prog;
    }
    ?>

    <?php if (empty($programmes_liste)): ?>
      <p style="color:#999;">Aucun programme créé pour le moment.</p>
    <?php else: ?>

    <div class="inscrits-filtres">
      <label>Choisir un programme :</label>
      <select id="select-programme" onchange="chargerProgramme()" class="select-tri" style="min-width:260px;">
        <option value="">— Sélectionner un programme —</option>
        <?php foreach ($programmes_liste as $prog): ?>
          <option value="prog-<?php echo $prog['id']; ?>"><?php echo htmlspecialchars($prog['nom']); ?> (<?php echo $prog['nb_inscrits']; ?> inscrit(s))</option>
        <?php endforeach; ?>
      </select>
    </div>

    <!-- Infos programme -->
    <div id="zone-programme" style="display:none; margin-top:20px;">
      <div id="info-programme" style="background:var(--vert-tres-clair); border-radius:var(--radius-sm); padding:14px 18px; margin-bottom:16px; display:flex; gap:24px; flex-wrap:wrap;"></div>

      <table class="tableau">
        <tr>
          <th>Participant</th>
          <th>Email</th>
          <th>Téléphone</th>
          <th>Date inscription</th>
          <th>Séances faites</th>
          <th>Progression</th>
        </tr>
        <?php foreach ($programmes_liste as $prog):
          $result_inscrits_prog = mysqli_query($conn, "SELECT u.prenom, u.nom, u.email, u.telephone,
                                                              ip.date_inscription, ip.nb_seances_faites
                                                       FROM inscriptions_programmes ip, utilisateurs u
                                                       WHERE ip.id_utilisateur = u.id
                                                       AND ip.id_programme = '" . $prog['id'] . "'
                                                       ORDER BY ip.date_inscription ASC");
          if (mysqli_num_rows($result_inscrits_prog) > 0):
            while ($inscrit = mysqli_fetch_assoc($result_inscrits_prog)):
              $pct = $prog['nb_seances'] > 0 ? round(($inscrit['nb_seances_faites'] / $prog['nb_seances']) * 100) : 0;
        ?>
          <tr class="ligne-programme prog-<?php echo $prog['id']; ?>" style="display:none;">
            <td><strong><?php echo htmlspecialchars($inscrit['prenom'] . ' ' . $inscrit['nom']); ?></strong></td>
            <td style="font-size:12px;"><?php echo htmlspecialchars($inscrit['email']); ?></td>
            <td><?php echo $inscrit['telephone'] ? htmlspecialchars($inscrit['telephone']) : '<span style="color:#bbb;">—</span>'; ?></td>
            <td><?php echo htmlspecialchars($inscrit['date_inscription']); ?></td>
            <td style="text-align:center;"><?php echo $inscrit['nb_seances_faites']; ?> / <?php echo $prog['nb_seances']; ?></td>
            <td style="min-width:140px;">
              <div style="background:#e8f0ea; border-radius:50px; height:8px; overflow:hidden;">
                <div style="background:var(--vert-fonce); height:100%; width:<?php echo $pct; ?>%; border-radius:50px;"></div>
              </div>
              <span style="font-size:11px; color:var(--texte-gris);"><?php echo $pct; ?>%</span>
            </td>
          </tr>
        <?php endwhile; else: ?>
          <tr class="ligne-programme prog-<?php echo $prog['id']; ?>" style="display:none;">
            <td colspan="6" style="text-align:center; color:#999;">Aucun inscrit pour ce programme.</td>
          </tr>
        <?php endif; endforeach; ?>
      </table>
    </div>

    <script>
    var infoProgrammes = {
      <?php foreach ($programmes_liste as $prog): ?>
      'prog-<?php echo $prog['id']; ?>': {
        nom: '<?php echo addslashes($prog['nom']); ?>',
        service: '<?php echo addslashes($prog['nom_service']); ?>',
        seances: <?php echo $prog['nb_seances']; ?>,
        duree: <?php echo $prog['duree_jours']; ?>,
        debut: '<?php echo $prog['date_debut']; ?>',
        prix: '<?php echo number_format($prog['prix'], 2); ?>',
        inscrits: <?php echo $prog['nb_inscrits']; ?>
      },
      <?php endforeach; ?>
    };

    function chargerProgramme() {
      var idProg = document.getElementById('select-programme').value;
      var zone = document.getElementById('zone-programme');
      var info = document.getElementById('info-programme');
      var lignes = document.querySelectorAll('.ligne-programme');

      if (!idProg) { zone.style.display = 'none'; return; }

      var p = infoProgrammes[idProg];
      info.innerHTML = '<span style="font-size:13px;"><strong>' + p.nom + '</strong></span>'
        + '<span style="font-size:13px; color:var(--texte-gris);">📋 ' + p.service + '</span>'
        + '<span style="font-size:13px; color:var(--texte-gris);">📅 ' + p.seances + ' séances · ' + p.duree + ' jours</span>'
        + '<span style="font-size:13px; color:var(--texte-gris);">🗓 Début : ' + p.debut + '</span>'
        + '<span style="font-size:13px; color:var(--texte-gris);">💶 ' + p.prix + '€</span>'
        + '<span style="font-size:13px; font-weight:700; color:var(--vert-fonce);">👥 ' + p.inscrits + ' inscrit(s)</span>';

      lignes.forEach(function(l) {
        l.style.display = l.classList.contains(idProg) ? '' : 'none';
      });

      zone.style.display = 'block';
    }
    </script>

    <?php endif; ?>
  </div>

</div>
</body>
</html>
