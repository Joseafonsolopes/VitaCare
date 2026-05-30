<?php
session_start();
require_once 'php/connexion.php';

if (!isset($_SESSION['id'])) {
  header('Location: connexion.php');
  exit();
}

$id_utilisateur = $_SESSION['id'];

// Supprimer toutes les notifications
if (isset($_POST['btn_tout_supprimer'])) {
  mysqli_query($conn, "DELETE FROM notifications WHERE id_utilisateur = '$id_utilisateur'");
  header('Location: notifications.php');
  exit();
}

// Marquer toutes comme lues
mysqli_query($conn, "UPDATE notifications SET lu = 1 WHERE id_utilisateur = '$id_utilisateur'");

// Compter les non lues avant de les marquer
$nb_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as nb FROM notifications WHERE id_utilisateur = '$id_utilisateur'"))['nb'];

// Récupérer toutes les notifications
$result = mysqli_query($conn, "SELECT * FROM notifications WHERE id_utilisateur = '$id_utilisateur' ORDER BY date_notification DESC");
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Notifications</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/notifications.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php require_once 'php/navbar_include.php'; ?>

<div class="notif-page">

  <div class="notif-page-header">
    <div>
      <h1>🔔 Notifications</h1>
      <p><?php echo $nb_total; ?> notification(s) au total</p>
    </div>
    <?php if ($nb_total > 0): ?>
    <form method="POST" action="notifications.php">
      <button type="submit" name="btn_tout_supprimer" class="btn-supprimer-tout"
        onclick="return confirm('Supprimer toutes les notifications ?')">
        🗑️ Tout supprimer
      </button>
    </form>
    <?php endif; ?>
  </div>

  <div class="notif-liste">
    <?php if (mysqli_num_rows($result) === 0): ?>
      <div class="notif-vide">
        <div class="notif-vide-icone">🔔</div>
        <p>Aucune notification pour le moment.</p>
        <a href="services.php" class="btn-primaire" style="display:inline-block; margin-top:16px;">Découvrir les services</a>
      </div>
    <?php else: ?>
      <?php while ($notif = mysqli_fetch_assoc($result)):
        // Déterminer le type de notification
        $message = $notif['message'];
        if (strpos($message, 'annulée') !== false) {
          $icone = '❌';
          $type  = 'annulation';
        } elseif (strpos($message, 'Merci de participer') !== false) {
          $icone = '✅';
          $type  = 'confirmation';
        } else {
          $icone = '📢';
          $type  = 'info';
        }

        // Formater la date
        $date = new DateTime($notif['date_notification']);
        $maintenant = new DateTime();
        $diff = $maintenant->diff($date);
        if ($diff->days == 0 && $diff->h == 0) {
          $temps = 'Il y a ' . $diff->i . ' min';
        } elseif ($diff->days == 0) {
          $temps = 'Il y a ' . $diff->h . 'h';
        } elseif ($diff->days == 1) {
          $temps = 'Hier';
        } else {
          $temps = $date->format('d/m/Y');
        }
      ?>
      <div class="notif-card notif-<?php echo $type; ?>">
        <div class="notif-card-icone"><?php echo $icone; ?></div>
        <div class="notif-card-contenu">
          <p class="notif-card-message"><?php echo htmlspecialchars($message); ?></p>
          <p class="notif-card-temps"><?php echo $temps; ?> · <?php echo $date->format('d/m/Y à H:i'); ?></p>
        </div>
      </div>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
