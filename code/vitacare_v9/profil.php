<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Mon Profil</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/profil.css">
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
$succes_profil  = '';
$erreur_profil  = '';

// Modifier le profil
if (isset($_POST['btn_modifier_profil'])) {
  $prenom   = htmlspecialchars(trim($_POST['prenom']));
  $nom      = htmlspecialchars(trim($_POST['nom']));
  $email    = htmlspecialchars(trim($_POST['email']));
  $tel      = htmlspecialchars(trim($_POST['telephone']));
  $adresse  = htmlspecialchars(trim($_POST['adresse']));

  if (empty($prenom) || empty($nom) || empty($email)) {
    $erreur_profil = 'Prénom, nom et email sont obligatoires.';
  } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $erreur_profil = 'Adresse email invalide.';
  } else {
    // Vérifier que l'email n'est pas déjà pris par quelqu'un d'autre
    $check_email = mysqli_query($conn, "SELECT id FROM utilisateurs WHERE email = '$email' AND id != '$id_utilisateur'");
    if (mysqli_num_rows($check_email) > 0) {
      $erreur_profil = 'Cet email est déjà utilisé par un autre compte.';
    } else {
      $sql_update = "UPDATE utilisateurs SET prenom = '$prenom', nom = '$nom', email = '$email', telephone = '$tel', adresse = '$adresse' WHERE id = '$id_utilisateur'";
      if (mysqli_query($conn, $sql_update)) {
        // Mettre à jour la session
        $_SESSION['prenom'] = $prenom;
        $_SESSION['nom']    = $nom;
        $_SESSION['email']  = $email;
        $succes_profil = 'Profil mis à jour avec succès !';
      } else {
        $erreur_profil = 'Erreur lors de la mise à jour.';
      }
    }
  }
}

// Annuler une inscription à un programme
if (isset($_POST['btn_annuler_programme'])) {
  $id_inscription = (int)$_POST['id_inscription'];
  $check = mysqli_query($conn, "SELECT ip.id, p.date_debut, p.duree_jours, ip.nb_seances_faites
                                 FROM inscriptions_programmes ip, programmes p
                                 WHERE ip.id = '$id_inscription'
                                 AND ip.id_programme = p.id
                                 AND ip.id_utilisateur = '$id_utilisateur'");
  if (mysqli_num_rows($check) > 0) {
    $prog = mysqli_fetch_assoc($check);
    $date_fin    = new DateTime($prog['date_debut']);
    $date_fin->modify('+' . $prog['duree_jours'] . ' days');
    $maintenant  = new DateTime();
    $diff_heures = ($date_fin->getTimestamp() - $maintenant->getTimestamp()) / 3600;
    if ($prog['nb_seances_faites'] > 0) {
      $erreur_profil = 'Impossible d\'annuler un programme déjà commencé.';
    } elseif ($diff_heures < 24) {
      $erreur_profil = 'Annulation impossible — le programme commence dans moins de 24h.';
    } else {
      mysqli_query($conn, "DELETE FROM inscriptions_programmes WHERE id = '$id_inscription'");
      $succes_profil = 'Inscription au programme annulée.';
    }
  }
}
if (isset($_POST['btn_annuler'])) {
  $id_resa = (int)$_POST['id_reservation'];
  $check = mysqli_query($conn, "SELECT r.id, c.date_creneau, c.heure FROM reservations r, creneaux c WHERE r.id = '$id_resa' AND r.id_creneau = c.id AND r.id_utilisateur = '$id_utilisateur'");
  if (mysqli_num_rows($check) > 0) {
    $resa = mysqli_fetch_assoc($check);
    // Normaliser l'heure (remplacer "10h00" par "10:00" si besoin)
    $heure_normalisee = str_replace('h', ':', $resa['heure']);
    $date_str = $resa['date_creneau'] . ' ' . $heure_normalisee;
    try {
      $date_activite = new DateTime($date_str);
    } catch (Exception $e) {
      // Si la date est invalide on annule quand même
      $date_activite = new DateTime('2000-01-01');
    }
    $maintenant    = new DateTime();
    $diff          = $maintenant->diff($date_activite);
    $heures_restantes = ($diff->days * 24) + $diff->h;

    if ($date_activite <= $maintenant) {
      $erreur_profil = 'Cette activité est déjà passée.';
    } elseif ($heures_restantes < 24) {
      $erreur_profil = 'Annulation impossible — il reste moins de 24h avant l\'activité.';
    } else {
      mysqli_query($conn, "UPDATE reservations SET statut = 'annulee' WHERE id = '$id_resa'");
      mysqli_query($conn, "UPDATE creneaux SET places_restantes = places_restantes + 1 WHERE id = (SELECT id_creneau FROM reservations WHERE id = '$id_resa')");
      mysqli_query($conn, "INSERT INTO notifications (id_utilisateur, message, lu, date_notification) VALUES ('$id_utilisateur', 'Votre réservation a été annulée.', 0, NOW())");
      $succes_profil = 'Réservation annulée avec succès.';
    }
  } else {
    $erreur_profil = 'Réservation introuvable.';
  }
}

// Infos utilisateur
$sql_user = "SELECT * FROM utilisateurs WHERE id = '$id_utilisateur'";
$result_user = mysqli_query($conn, $sql_user);
$utilisateur = mysqli_fetch_assoc($result_user);

// Stats
$sql_stats = "SELECT COUNT(r.id) as nb_reservations,
                     SUM(s.prix) as total_depenses
              FROM reservations r, creneaux c, services s
              WHERE r.id_creneau = c.id
              AND c.id_service = s.id
              AND r.id_utilisateur = '$id_utilisateur'
              AND r.statut = 'confirmee'";
$stats = mysqli_fetch_assoc(mysqli_query($conn, $sql_stats));
$nb_reservations = $stats['nb_reservations'] ?? 0;
$total_depenses  = $stats['total_depenses'] ?? 0;

// Réservations à venir (date+heure dans le futur)
$sql_avenir = "SELECT r.id, s.nom, s.emoji, c.date_creneau, c.heure, r.statut,
                      u.prenom, u.nom AS nom_intervenant, u.id AS id_intervenant
               FROM reservations r, creneaux c, services s, utilisateurs u
               WHERE r.id_creneau = c.id
               AND c.id_service = s.id
               AND s.id_intervenant = u.id
               AND r.id_utilisateur = '$id_utilisateur'
               AND CONCAT(c.date_creneau, ' ', c.heure) > NOW()
               AND r.statut = 'confirmee'
               ORDER BY c.date_creneau ASC, c.heure ASC";
$result_avenir = mysqli_query($conn, $sql_avenir);

// Réservations passées (date+heure dans le passé, confirmées seulement)
$sql_passes = "SELECT s.nom, s.emoji, c.date_creneau, c.heure, r.statut,
                      u.prenom, u.nom AS nom_intervenant
               FROM reservations r, creneaux c, services s, utilisateurs u
               WHERE r.id_creneau = c.id
               AND c.id_service = s.id
               AND s.id_intervenant = u.id
               AND r.id_utilisateur = '$id_utilisateur'
               AND CONCAT(c.date_creneau, ' ', c.heure) <= NOW()
               AND r.statut = 'confirmee'
               ORDER BY c.date_creneau DESC";
$result_passes = mysqli_query($conn, $sql_passes);

// Réservations annulées
$sql_annules = "SELECT s.nom, s.emoji, c.date_creneau, c.heure,
                       u.prenom, u.nom AS nom_intervenant
                FROM reservations r, creneaux c, services s, utilisateurs u
                WHERE r.id_creneau = c.id
                AND c.id_service = s.id
                AND s.id_intervenant = u.id
                AND r.id_utilisateur = '$id_utilisateur'
                AND r.statut = 'annulee'
                ORDER BY c.date_creneau DESC";
$result_annules = mysqli_query($conn, $sql_annules);

// Intervenants favoris
$sql_favoris = "SELECT u.prenom, u.nom, s.categorie, COUNT(r.id) as nb_seances
                FROM reservations r, creneaux c, services s, utilisateurs u
                WHERE r.id_creneau = c.id
                AND c.id_service = s.id
                AND s.id_intervenant = u.id
                AND r.id_utilisateur = '$id_utilisateur'
                GROUP BY u.id
                ORDER BY nb_seances DESC
                LIMIT 3";
$result_favoris = mysqli_query($conn, $sql_favoris);

// Programmes
$sql_programmes = "SELECT p.nom, p.nb_seances, p.date_debut, p.duree_jours,
                          ip.id, ip.nb_seances_faites, ip.date_inscription
                   FROM inscriptions_programmes ip, programmes p
                   WHERE ip.id_programme = p.id
                   AND ip.id_utilisateur = '$id_utilisateur'";
$result_programmes = mysqli_query($conn, $sql_programmes);

// Factures
$sql_factures = "SELECT f.*, r.statut
                 FROM factures f, reservations r
                 WHERE f.id_reservation = r.id
                 AND f.id_utilisateur = '$id_utilisateur'
                 AND r.statut != 'annulee'
                 ORDER BY f.date_facture DESC";
$result_factures = mysqli_query($conn, $sql_factures);

// Préférences = catégories les plus réservées
$sql_prefs = "SELECT s.categorie, COUNT(r.id) as nb
              FROM reservations r, creneaux c, services s
              WHERE r.id_creneau = c.id
              AND c.id_service = s.id
              AND r.id_utilisateur = '$id_utilisateur'
              GROUP BY s.categorie
              ORDER BY nb DESC
              LIMIT 6";
$result_prefs = mysqli_query($conn, $sql_prefs);
$preferences = array();
while ($row = mysqli_fetch_assoc($result_prefs)) {
  $preferences[] = $row['categorie'];
}

// Formatage date
function formatDateFr($dateStr) {
  $jours = array('Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam');
  $mois  = array('jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jul', 'aoû', 'sep', 'oct', 'nov', 'déc');
  $d = new DateTime($dateStr);
  return $jours[$d->format('w')] . '. ' . $d->format('d') . ' ' . $mois[$d->format('n')-1];
}
?>

<?php require_once 'php/navbar_include.php'; ?>

<div class="profil-page">

  <!-- EN-TÊTE -->
  <div class="profil-header">
    <div class="profil-avatar">
      <?php echo strtoupper(substr($utilisateur['prenom'], 0, 1) . substr($utilisateur['nom'], 0, 1)); ?>
    </div>
    <div class="profil-info">
      <h2><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></h2>
      <p>Membre depuis <?php echo htmlspecialchars($utilisateur['date_inscription']); ?></p>
      <span class="badge-statut">Membre actif</span>
    </div>
    <?php if ($utilisateur['role'] === 'intervenant'): ?>
      <a href="dashboard_intervenant.php" class="btn-espace">🧑‍⚕️ Espace intervenant</a>
    <?php elseif ($utilisateur['role'] === 'admin'): ?>
      <a href="dashboard_admin.php" class="btn-espace">⚙️ Espace administration</a>
    <?php endif; ?>
  </div>

  <!-- STATS -->
  <div class="profil-stats">
    <div class="profil-stat-carte">
      <span class="profil-stat-nombre"><?php echo $nb_reservations; ?></span>
      <span class="profil-stat-label">Séances réalisées</span>
    </div>
    <div class="profil-stat-carte">
      <span class="profil-stat-nombre"><?php echo number_format($total_depenses, 0); ?>€</span>
      <span class="profil-stat-label">Dépenses totales</span>
    </div>
    <div class="profil-stat-carte">
      <span class="profil-stat-nombre"><?php echo mysqli_num_rows($result_avenir); ?></span>
      <span class="profil-stat-label">Activités à venir</span>
    </div>
    <div class="profil-stat-carte">
      <span class="profil-stat-nombre" style="color: #f0a030;">4,8 ★</span>
      <span class="profil-stat-label">Note moyenne</span>
    </div>
  </div>

  <div class="profil-grille">

    <!-- COLONNE GAUCHE -->
    <?php if ($succes_profil): ?>
      <div class="alerte alerte-succes" style="grid-column: 1/-1; margin-bottom: 0;"><?php echo $succes_profil; ?></div>
    <?php endif; ?>
    <?php if ($erreur_profil): ?>
      <div class="alerte alerte-erreur" style="grid-column: 1/-1; margin-bottom: 0;"><?php echo $erreur_profil; ?></div>
    <?php endif; ?>
    <div class="profil-colonne">

      <!-- INFORMATIONS -->
      <div class="profil-section">
        <div class="section-header">
          <h3>Informations personnelles</h3>
          <button type="button" class="btn-modifier-toggle" onclick="toggleModif()">✏️ Modifier</button>
        </div>

        <!-- AFFICHAGE -->
        <div id="infos-affichage">
          <div class="info-ligne">
            <span class="info-cle">Nom complet</span>
            <span class="info-val"><?php echo htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']); ?></span>
          </div>
          <div class="info-ligne">
            <span class="info-cle">Email</span>
            <span class="info-val"><?php echo htmlspecialchars($utilisateur['email']); ?></span>
          </div>
          <?php if ($utilisateur['telephone']): ?>
          <div class="info-ligne">
            <span class="info-cle">Téléphone</span>
            <span class="info-val"><?php echo htmlspecialchars($utilisateur['telephone']); ?></span>
          </div>
          <?php endif; ?>
          <?php if ($utilisateur['adresse']): ?>
          <div class="info-ligne">
            <span class="info-cle">Adresse</span>
            <span class="info-val"><?php echo htmlspecialchars($utilisateur['adresse']); ?></span>
          </div>
          <?php endif; ?>
        </div>

        <!-- FORMULAIRE MODIFICATION -->
        <form id="form-modif" method="POST" action="profil.php" style="display:none;">
          <div class="form-modif-groupe">
            <label>Prénom *</label>
            <input type="text" name="prenom" value="<?php echo htmlspecialchars($utilisateur['prenom']); ?>" required>
          </div>
          <div class="form-modif-groupe">
            <label>Nom *</label>
            <input type="text" name="nom" value="<?php echo htmlspecialchars($utilisateur['nom']); ?>" required>
          </div>
          <div class="form-modif-groupe">
            <label>Email * <span style="font-size:11px; color:var(--texte-gris);">(utilisé pour la connexion)</span></label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($utilisateur['email']); ?>" required>
          </div>
          <div class="form-modif-groupe">
            <label>Téléphone</label>
            <input type="text" name="telephone" value="<?php echo htmlspecialchars($utilisateur['telephone'] ?? ''); ?>">
          </div>
          <div class="form-modif-groupe">
            <label>Adresse</label>
            <input type="text" name="adresse" value="<?php echo htmlspecialchars($utilisateur['adresse'] ?? ''); ?>">
          </div>
          <div class="form-modif-actions">
            <button type="submit" name="btn_modifier_profil" class="btn-sauvegarder">💾 Sauvegarder</button>
            <button type="button" class="btn-annuler-modif" onclick="toggleModif()">Annuler</button>
          </div>
        </form>
      </div>

      <!-- PRÉFÉRENCES -->
      <?php if (!empty($preferences)): ?>
      <div class="profil-section">
        <h3>Mes préférences</h3>
        <div class="preferences-tags">
          <?php foreach ($preferences as $pref): ?>
            <span class="tag-preference"><?php echo htmlspecialchars(trim($pref)); ?></span>
          <?php endforeach; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- INTERVENANTS FAVORIS -->
      <?php if (mysqli_num_rows($result_favoris) > 0): ?>
      <div class="profil-section">
        <h3>Intervenants favoris</h3>
        <?php
        // Reset result
        mysqli_data_seek($result_favoris, 0);
        while ($fav = mysqli_fetch_assoc($result_favoris)):
          $initiales = strtoupper(substr($fav['prenom'], 0, 1) . substr($fav['nom'], 0, 1));
        ?>
        <div class="intervenant-fav">
          <div class="fav-avatar"><?php echo $initiales; ?></div>
          <div class="fav-info">
            <p class="fav-nom"><?php echo htmlspecialchars($fav['prenom'] . ' ' . $fav['nom']); ?></p>
            <p class="fav-cat"><?php echo htmlspecialchars($fav['categorie']); ?></p>
          </div>
          <span class="fav-seances"><?php echo $fav['nb_seances']; ?> séance(s)</span>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>

    </div>

    <!-- COLONNE DROITE -->
    <div class="profil-colonne">

      <!-- HISTORIQUE RÉSERVATIONS -->
      <div class="profil-section">
        <div class="section-header">
          <h3>Historique des réservations</h3>
          <a href="#" class="voir-tout">Voir tout</a>
        </div>

        <!-- ONGLETS -->
        <div class="onglets-resa">
          <button class="onglet-resa actif" onclick="afficherOnglet('avenir', this)">À venir</button>
          <button class="onglet-resa" onclick="afficherOnglet('passes', this)">Passés</button>
          <button class="onglet-resa" onclick="afficherOnglet('annules', this)">Annulés</button>
        </div>

        <!-- À VENIR -->
        <div id="tab-avenir" class="tab-contenu">
          <?php
          mysqli_data_seek($result_avenir, 0);
          if (mysqli_num_rows($result_avenir) > 0):
            while ($resa = mysqli_fetch_assoc($result_avenir)):
              $emoji = $resa['emoji'] ? $resa['emoji'] : '🧘';
          ?>
          <div class="resa-item">
            <div class="resa-emoji"><?php echo $emoji; ?></div>
            <div class="resa-info">
              <p class="resa-nom"><?php echo htmlspecialchars($resa['nom']); ?></p>
              <p class="resa-meta"><?php echo htmlspecialchars($resa['prenom'] . ' ' . $resa['nom_intervenant']); ?> · <?php echo formatDateFr($resa['date_creneau']); ?> · <?php echo htmlspecialchars($resa['heure']); ?></p>
            </div>
            <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
              <span class="badge-resa confirmee">Confirmé</span>
              <a href="messages.php?avec=<?php echo $resa['id_intervenant']; ?>" class="btn-contacter">💬</a>
              <form method="POST" action="profil.php" style="display:inline;">
                <input type="hidden" name="id_reservation" value="<?php echo $resa['id']; ?>">
                <button type="submit" name="btn_annuler" class="btn-annuler" onclick="return confirm('Annuler cette réservation ?')">Annuler</button>
              </form>
            </div>
          </div>
          <?php endwhile; else: ?>
          <p class="resa-vide">Aucune réservation à venir.</p>
          <?php endif; ?>
        </div>

        <!-- PASSÉS -->
        <div id="tab-passes" class="tab-contenu" style="display:none">
          <?php
          if (mysqli_num_rows($result_passes) > 0):
            while ($resa = mysqli_fetch_assoc($result_passes)):
              $emoji = $resa['emoji'] ? $resa['emoji'] : '🧘';
          ?>
          <div class="resa-item">
            <div class="resa-emoji"><?php echo $emoji; ?></div>
            <div class="resa-info">
              <p class="resa-nom"><?php echo htmlspecialchars($resa['nom']); ?></p>
              <p class="resa-meta"><?php echo htmlspecialchars($resa['prenom'] . ' ' . $resa['nom_intervenant']); ?> · <?php echo formatDateFr($resa['date_creneau']); ?> · <?php echo htmlspecialchars($resa['heure']); ?></p>
            </div>
            <span class="badge-resa passee">Passé</span>
          </div>
          <?php endwhile; else: ?>
          <p class="resa-vide">Aucune réservation passée.</p>
          <?php endif; ?>
        </div>

        <!-- ANNULÉS -->
        <div id="tab-annules" class="tab-contenu" style="display:none">
          <?php
          if (mysqli_num_rows($result_annules) > 0):
            while ($resa = mysqli_fetch_assoc($result_annules)):
              $emoji = $resa['emoji'] ? $resa['emoji'] : '🧘';
          ?>
          <div class="resa-item">
            <div class="resa-emoji"><?php echo $emoji; ?></div>
            <div class="resa-info">
              <p class="resa-nom"><?php echo htmlspecialchars($resa['nom']); ?></p>
              <p class="resa-meta"><?php echo htmlspecialchars($resa['prenom'] . ' ' . $resa['nom_intervenant']); ?> · <?php echo formatDateFr($resa['date_creneau']); ?></p>
            </div>
            <span class="badge-resa annulee">Annulé</span>
          </div>
          <?php endwhile; else: ?>
          <p class="resa-vide">Aucune réservation annulée.</p>
          <?php endif; ?>
        </div>
      </div>

      <?php if (mysqli_num_rows($result_programmes) > 0): ?>
      <div class="profil-section">
        <h3>Mes programmes</h3>
        <?php while ($prog = mysqli_fetch_assoc($result_programmes)):
          $pourcentage = $prog['nb_seances'] > 0
            ? round(($prog['nb_seances_faites'] / $prog['nb_seances']) * 100)
            : 0;
          $seance_actuelle = $prog['nb_seances_faites'] + 1;
          if ($seance_actuelle > $prog['nb_seances']) $seance_actuelle = $prog['nb_seances'];
          $peut_annuler = ($prog['nb_seances_faites'] == 0);
        ?>
        <div class="programme-item">
          <div class="programme-header">
            <p class="programme-nom"><?php echo htmlspecialchars($prog['nom']); ?></p>
            <span class="programme-info"><?php echo $prog['nb_seances']; ?> séances · Séance <?php echo $seance_actuelle; ?>/<?php echo $prog['nb_seances']; ?></span>
          </div>
          <div class="programme-barre">
            <div class="programme-progression" style="width: <?php echo $pourcentage; ?>%"></div>
          </div>
          <div style="display:flex; justify-content:space-between; align-items:center; margin-top:4px;">
            <span class="programme-pct"><?php echo $pourcentage; ?>%</span>
            <?php if ($peut_annuler): ?>
            <form method="POST" action="profil.php" style="display:inline;">
              <input type="hidden" name="id_inscription" value="<?php echo $prog['id']; ?>">
              <button type="submit" name="btn_annuler_programme" class="btn-annuler" onclick="return confirm('Annuler ce programme ?')" style="font-size:11px; padding:3px 10px;">Annuler</button>
            </form>
            <?php else: ?>
            <span style="font-size:11px; color:#999;">En cours</span>
            <?php endif; ?>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>

      <!-- FACTURES -->
      <?php if (mysqli_num_rows($result_factures) > 0): ?>
      <div class="profil-section" id="factures">
        <h3>📄 Mes factures</h3>
        <?php while ($facture = mysqli_fetch_assoc($result_factures)): ?>
        <div class="facture-item">
          <div class="facture-info">
            <p class="facture-numero"><?php echo htmlspecialchars($facture['numero_facture']); ?></p>
            <p class="facture-details"><?php echo htmlspecialchars($facture['nom_service']); ?> · <?php echo htmlspecialchars($facture['date_creneau']); ?></p>
          </div>
          <div style="display:flex; align-items:center; gap:12px;">
            <span class="facture-montant"><?php echo number_format($facture['montant'], 2); ?>€</span>
            <a href="facture.php?id=<?php echo $facture['id']; ?>" target="_blank" class="btn-telecharger">⬇️ Télécharger</a>
          </div>
        </div>
        <?php endwhile; ?>
      </div>
      <?php endif; ?>

    </div>
  </div>
</div>

<script>
function afficherOnglet(nom, btn) {
  document.querySelectorAll('.tab-contenu').forEach(function(t) { t.style.display = 'none'; });
  document.querySelectorAll('.onglet-resa').forEach(function(b) { b.classList.remove('actif'); });
  document.getElementById('tab-' + nom).style.display = 'block';
  btn.classList.add('actif');
}

function toggleModif() {
  var affichage = document.getElementById('infos-affichage');
  var form = document.getElementById('form-modif');
  if (form.style.display === 'none') {
    affichage.style.display = 'none';
    form.style.display = 'block';
  } else {
    affichage.style.display = 'block';
    form.style.display = 'none';
  }
}

<?php if ($succes_profil || $erreur_profil): ?>
// Si on vient de soumettre le form, rester en mode affichage
document.addEventListener('DOMContentLoaded', function() {
  document.getElementById('infos-affichage').style.display = 'block';
  document.getElementById('form-modif').style.display = 'none';
});
<?php endif; ?>
</script>

</body>
</html>
