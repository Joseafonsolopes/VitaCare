<?php
// Compter les notifications non lues
$nb_notif_nonlues = 0;
if (isset($_SESSION['id'])) {
  $sql_notif_count = "SELECT COUNT(*) as nb FROM notifications WHERE id_utilisateur = '" . $_SESSION['id'] . "' AND lu = 0";
  $result_count = mysqli_query($conn, $sql_notif_count);
  if ($result_count) {
    $nb_notif_nonlues = mysqli_fetch_assoc($result_count)['nb'];
  }
}

// Page active
$page_actuelle = basename($_SERVER['PHP_SELF']);
?>
<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.php" <?php echo $page_actuelle === 'index.php' ? 'class="actif"' : ''; ?>>Accueil</a></li>
    <li><a href="services.php" <?php echo $page_actuelle === 'services.php' ? 'class="actif"' : ''; ?>>Services</a></li>
    <li><a href="reservation.php" <?php echo $page_actuelle === 'reservation.php' ? 'class="actif"' : ''; ?>>Prendre rendez-vous</a></li>
    <li><a href="programmes.php" <?php echo $page_actuelle === 'programmes.php' ? 'class="actif"' : ''; ?>>Programmes</a></li>
    <li>
      <a href="notifications.php" <?php echo $page_actuelle === 'notifications.php' ? 'class="actif navbar-notif-badge"' : 'class="navbar-notif-badge"'; ?>>
        Notifications
        <?php if ($nb_notif_nonlues > 0): ?>
          <span class="notif-badge"><?php echo $nb_notif_nonlues > 9 ? '9+' : $nb_notif_nonlues; ?></span>
        <?php endif; ?>
      </a>
    </li>
    <li><a href="panier.php" <?php echo $page_actuelle === 'panier.php' ? 'class="actif"' : ''; ?>>Panier</a></li>
    <li><a href="messages.php" <?php echo $page_actuelle === 'messages.php' ? 'class="actif"' : ''; ?>>Messages</a></li>
    <li><a href="profil.php" <?php echo $page_actuelle === 'profil.php' ? 'class="actif"' : ''; ?>>Mon profil</a></li>
  </ul>
  <div class="navbar-user">
    <span class="navbar-prenom">👤 <?php echo htmlspecialchars($_SESSION['prenom']); ?></span>
    <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
  </div>
</nav>
