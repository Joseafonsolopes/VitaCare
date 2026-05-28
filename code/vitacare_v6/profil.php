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

// Réservations à venir
$sql_avenir = "SELECT s.nom, s.emoji, c.date_creneau, c.heure, r.statut,
                      u.prenom, u.nom AS nom_intervenant
               FROM reservations r, creneaux c, services s, utilisateurs u
               WHERE r.id_creneau = c.id
               AND c.id_service = s.id
               AND s.id_intervenant = u.id
               AND r.id_utilisateur = '$id_utilisateur'
               AND c.date_creneau >= CURDATE()
               AND r.statut = 'confirmee'
               ORDER BY c.date_creneau ASC, c.heure ASC";
$result_avenir = mysqli_query($conn, $sql_avenir);

// Réservations passées
$sql_passes = "SELECT s.nom, s.emoji, c.date_creneau, c.heure, r.statut,
                      u.prenom, u.nom AS nom_intervenant
               FROM reservations r, creneaux c, services s, utilisateurs u
               WHERE r.id_creneau = c.id
               AND c.id_service = s.id
               AND s.id_intervenant = u.id
               AND r.id_utilisateur = '$id_utilisateur'
               AND c.date_creneau < CURDATE()
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
                          ip.nb_seances_faites, ip.date_inscription
                   FROM inscriptions_programmes ip, programmes p
                   WHERE ip.id_programme = p.id
                   AND ip.id_utilisateur = '$id_utilisateur'";
$result_programmes = mysqli_query($conn, $sql_programmes);

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

<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="services.php">Services</a></li>
    <li><a href="reservation.php">Prendre rendez-vous</a></li>
    <li><a href="profil.php" class="actif">Mon profil</a></li>
  </ul>
  <div class="navbar-user">
    <span class="navbar-prenom">👤 <?php echo htmlspecialchars($utilisateur['prenom']); ?></span>
    <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
  </div>
</nav>

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
    <div class="profil-colonne">

      <!-- INFORMATIONS -->
      <div class="profil-section">
        <h3>Informations personnelles</h3>
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
            <span class="badge-resa confirmee">Confirmé</span>
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

      <!-- PROGRAMMES -->
      <?php if (mysqli_num_rows($result_programmes) > 0): ?>
      <div class="profil-section">
        <h3>Mes programmes</h3>
        <?php while ($prog = mysqli_fetch_assoc($result_programmes)):
          $pourcentage = $prog['nb_seances'] > 0
            ? round(($prog['nb_seances_faites'] / $prog['nb_seances']) * 100)
            : 0;
          $seance_actuelle = $prog['nb_seances_faites'] + 1;
          if ($seance_actuelle > $prog['nb_seances']) $seance_actuelle = $prog['nb_seances'];
        ?>
        <div class="programme-item">
          <div class="programme-header">
            <p class="programme-nom"><?php echo htmlspecialchars($prog['nom']); ?></p>
            <span class="programme-info"><?php echo $prog['nb_seances']; ?> séances · Séance <?php echo $seance_actuelle; ?>/<?php echo $prog['nb_seances']; ?></span>
          </div>
          <div class="programme-barre">
            <div class="programme-progression" style="width: <?php echo $pourcentage; ?>%"></div>
          </div>
          <span class="programme-pct"><?php echo $pourcentage; ?>%</span>
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
</script>

</body>
</html>
