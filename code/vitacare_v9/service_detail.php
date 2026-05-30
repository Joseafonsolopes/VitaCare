<?php
session_start();
require_once 'php/connexion.php';

$id_service = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($id_service === 0) {
  header('Location: services.php');
  exit();
}

// Récupérer le service
$sql = "SELECT s.*, u.prenom, u.nom AS nom_intervenant, u.email AS email_intervenant, u.telephone AS tel_intervenant
        FROM services s, utilisateurs u
        WHERE s.id = '$id_service' AND s.id_intervenant = u.id AND s.actif = 1";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
  header('Location: services.php');
  exit();
}

$service = mysqli_fetch_assoc($result);

// Créneaux disponibles
$sql_creneaux = "SELECT * FROM creneaux
                 WHERE id_service = '$id_service'
                 AND date_creneau >= CURDATE()
                 AND places_restantes > 0
                 AND actif = 1
                 ORDER BY date_creneau ASC, heure ASC
                 LIMIT 6";
$result_creneaux = mysqli_query($conn, $sql_creneaux);

// Nb total de réservations
$nb_resa = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM reservations r, creneaux c WHERE r.id_creneau = c.id AND c.id_service = '$id_service' AND r.statut = 'confirmee'"))['nb'];

// Autres services du même intervenant
$sql_autres = "SELECT id, nom, emoji, prix, duree FROM services WHERE id_intervenant = '" . $service['id_intervenant'] . "' AND id != '$id_service' AND actif = 1 LIMIT 3";
$result_autres = mysqli_query($conn, $sql_autres);

function formatDateFr2($dateStr) {
  $jours = array('Dimanche','Lundi','Mardi','Mercredi','Jeudi','Vendredi','Samedi');
  $mois  = array('janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre');
  $d = new DateTime($dateStr);
  return $jours[$d->format('w')] . ' ' . $d->format('d') . ' ' . $mois[$d->format('n')-1] . ' ' . $d->format('Y');
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - <?php echo htmlspecialchars($service['nom']); ?></title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/service_detail.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php require_once 'php/navbar_include.php'; ?>

<div class="detail-page">

  <!-- EN-TÊTE SERVICE -->
  <div class="detail-header">
    <div class="detail-header-gauche">
      <a href="services.php" class="detail-retour">← Retour aux services</a>
      <div class="detail-emoji"><?php echo $service['emoji'] ?: '🏃'; ?></div>
      <h1><?php echo htmlspecialchars($service['nom']); ?></h1>
      <div class="detail-meta">
        <span class="detail-badge-cat"><?php echo htmlspecialchars($service['categorie']); ?></span>
        <span>⏱ <?php echo $service['duree']; ?> min</span>
        <span>📅 <?php echo $nb_resa; ?> réservation(s)</span>
      </div>
      <?php if ($service['description']): ?>
        <p class="detail-description"><?php echo nl2br(htmlspecialchars($service['description'])); ?></p>
      <?php endif; ?>
    </div>

    <!-- CARTE RÉSERVATION -->
    <div class="detail-carte-resa">
      <p class="detail-prix"><?php echo number_format($service['prix'], 2); ?>€</p>
      <p class="detail-prix-label">par séance</p>

      <div class="detail-intervenant">
        <div class="detail-interv-avatar">
          <?php echo strtoupper(substr($service['prenom'], 0, 1) . substr($service['nom_intervenant'], 0, 1)); ?>
        </div>
        <div>
          <p class="detail-interv-nom"><?php echo htmlspecialchars($service['prenom'] . ' ' . $service['nom_intervenant']); ?></p>
          <p class="detail-interv-role">Intervenant</p>
        </div>
      </div>

      <?php if ($service['tel_intervenant']): ?>
      <p class="detail-contact">📞 <?php echo htmlspecialchars($service['tel_intervenant']); ?></p>
      <?php endif; ?>
      <?php if ($service['email_intervenant']): ?>
      <p class="detail-contact">✉️ <?php echo htmlspecialchars($service['email_intervenant']); ?></p>
      <?php endif; ?>

      <?php if (isset($_SESSION['id'])): ?>
        <a href="reservation.php" onclick="localStorage.setItem('serviceChoisi', JSON.stringify({id:<?php echo $service['id']; ?>, nom:'<?php echo addslashes($service['nom']); ?>', emoji:'<?php echo $service['emoji']; ?>', intervenant:'<?php echo addslashes($service['prenom'] . ' ' . $service['nom_intervenant']); ?>', duree:<?php echo $service['duree']; ?>, prix:<?php echo $service['prix']; ?>})); return true;" class="btn-reserver-detail">
          📅 Réserver ce service
        </a>
        <?php if (isset($_SESSION['role']) && ($_SESSION['role'] === 'utilisateur' || $_SESSION['role'] === 'admin')): ?>
        <a href="messages.php?avec=<?php echo $service['id_intervenant']; ?>" class="btn-contacter-detail">
          💬 Contacter l'intervenant
        </a>
        <?php endif; ?>
      <?php else: ?>
        <a href="connexion.php" class="btn-reserver-detail">🔐 Se connecter pour réserver</a>
      <?php endif; ?>
    </div>
  </div>

  <div class="detail-grille">

    <!-- CRÉNEAUX DISPONIBLES -->
    <div class="detail-section">
      <h2>📅 Prochains créneaux disponibles</h2>
      <?php if (mysqli_num_rows($result_creneaux) > 0): ?>
      <div class="creneaux-liste">
        <?php while ($cr = mysqli_fetch_assoc($result_creneaux)):
          $couleur = $cr['places_restantes'] <= 2 ? '#f0a030' : '#2d5a3d';
        ?>
        <div class="creneau-card">
          <div class="creneau-card-date">
            <p class="creneau-jour"><?php echo (new DateTime($cr['date_creneau']))->format('d'); ?></p>
            <p class="creneau-mois"><?php
              $mois = array('','jan','fév','mar','avr','mai','jun','jul','aoû','sep','oct','nov','déc');
              echo $mois[(int)(new DateTime($cr['date_creneau']))->format('n')];
            ?></p>
          </div>
          <div class="creneau-card-info">
            <p class="creneau-card-heure">🕐 <?php echo substr($cr['heure'], 0, 5); ?></p>
            <p class="creneau-card-places" style="color:<?php echo $couleur; ?>">
              <?php echo $cr['places_restantes']; ?> place(s) restante(s)
            </p>
          </div>
          <?php if (isset($_SESSION['id'])): ?>
          <a href="reservation.php"
             onclick="localStorage.setItem('serviceChoisi', JSON.stringify({id:<?php echo $service['id']; ?>, nom:'<?php echo addslashes($service['nom']); ?>', emoji:'<?php echo $service['emoji']; ?>', intervenant:'<?php echo addslashes($service['prenom'] . ' ' . $service['nom_intervenant']); ?>', duree:<?php echo $service['duree']; ?>, prix:<?php echo $service['prix']; ?>})); return true;"
             class="btn-creneau-reserver">
            Réserver
          </a>
          <?php else: ?>
          <a href="connexion.php" class="btn-creneau-reserver">Se connecter</a>
          <?php endif; ?>
        </div>
        <?php endwhile; ?>
      </div>
      <?php else: ?>
        <p style="color:#999; padding:20px 0;">Aucun créneau disponible pour le moment.</p>
      <?php endif; ?>
    </div>

    <!-- AUTRES SERVICES DE L'INTERVENANT -->
    <?php if (mysqli_num_rows($result_autres) > 0): ?>
    <div class="detail-section">
      <h2>🧑‍⚕️ Autres services de <?php echo htmlspecialchars($service['prenom']); ?></h2>
      <div class="autres-services">
        <?php while ($autre = mysqli_fetch_assoc($result_autres)): ?>
        <a href="service_detail.php?id=<?php echo $autre['id']; ?>" class="autre-service-card">
          <span class="autre-service-emoji"><?php echo $autre['emoji'] ?: '🏃'; ?></span>
          <div class="autre-service-info">
            <p class="autre-service-nom"><?php echo htmlspecialchars($autre['nom']); ?></p>
            <p class="autre-service-meta"><?php echo $autre['duree']; ?> min · <?php echo number_format($autre['prix'], 2); ?>€</p>
          </div>
          <span class="autre-service-arrow">→</span>
        </a>
        <?php endwhile; ?>
      </div>
    </div>
    <?php endif; ?>

  </div>
</div>

</body>
</html>
