<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Programmes</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/dashboard.css">
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
$succes = '';
$erreur = '';

// S'inscrire à un programme
if (isset($_POST['btn_inscrire'])) {
  $id_programme = (int)$_POST['id_programme'];

  // Vérifier que l'utilisateur n'est pas déjà inscrit
  $check = mysqli_query($conn, "SELECT id FROM inscriptions_programmes WHERE id_utilisateur = '$id_utilisateur' AND id_programme = '$id_programme'");
  if (mysqli_num_rows($check) > 0) {
    $erreur = 'Vous êtes déjà inscrit à ce programme.';
  } else {
    $date_today = date('Y-m-d');
    $sql = "INSERT INTO inscriptions_programmes (id_utilisateur, id_programme, date_inscription, nb_seances_faites)
            VALUES ('$id_utilisateur', '$id_programme', '$date_today', 0)";
    if (mysqli_query($conn, $sql)) {
      $succes = 'Inscription confirmée !';
    } else {
      $erreur = 'Erreur : ' . mysqli_error($conn);
    }
  }
}

// Récupérer tous les programmes disponibles
$sql_prog = "SELECT p.*, s.nom AS nom_service, s.emoji, u.prenom, u.nom AS nom_intervenant,
                    COUNT(ip.id) as nb_inscrits
             FROM programmes p
             LEFT JOIN services s ON p.id_service = s.id
             LEFT JOIN utilisateurs u ON s.id_intervenant = u.id
             LEFT JOIN inscriptions_programmes ip ON ip.id_programme = p.id
             WHERE p.actif = 1
             GROUP BY p.id
             ORDER BY p.date_debut ASC";
$result_prog = mysqli_query($conn, $sql_prog);

// IDs des programmes où l'utilisateur est déjà inscrit
$sql_inscrits = "SELECT id_programme FROM inscriptions_programmes WHERE id_utilisateur = '$id_utilisateur'";
$result_inscrits = mysqli_query($conn, $sql_inscrits);
$programmes_inscrits = array();
while ($row = mysqli_fetch_assoc($result_inscrits)) {
  $programmes_inscrits[] = $row['id_programme'];
}
?>

<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="services.php">Services</a></li>
    <li><a href="reservation.php">Prendre rendez-vous</a></li>
    <li><a href="programmes.php" class="actif">Programmes</a></li>
    <li><a href="notifications.php">Notifications</a></li>
    <li><a href="panier.php">Panier</a></li>
    <li><a href="profil.php">Mon profil</a></li>
  </ul>
  <div class="navbar-user">
    <span class="navbar-prenom">👤 <?php echo htmlspecialchars($_SESSION['prenom']); ?></span>
    <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
  </div>
</nav>

<div class="dashboard-page">

  <div class="dashboard-header">
    <h1>Programmes disponibles</h1>
    <p>Inscrivez-vous à un programme et suivez votre progression séance après séance.</p>
  </div>

  <?php if ($erreur): ?>
    <div class="alerte alerte-erreur"><?php echo $erreur; ?></div>
  <?php endif; ?>
  <?php if ($succes): ?>
    <div class="alerte alerte-succes"><?php echo $succes; ?> <a href="profil.php">→ Voir mes programmes</a></div>
  <?php endif; ?>

  <div class="programmes-grille">
    <?php if (mysqli_num_rows($result_prog) > 0):
      while ($prog = mysqli_fetch_assoc($result_prog)):
        $deja_inscrit = in_array($prog['id'], $programmes_inscrits);
        $emoji = $prog['emoji'] ? $prog['emoji'] : '🧘';
        $duree_semaines = round($prog['duree_jours'] / 7);
    ?>
    <div class="programme-carte">
      <div class="programme-carte-header">
        <span class="programme-carte-emoji"><?php echo $emoji; ?></span>
        <div>
          <h3><?php echo htmlspecialchars($prog['nom']); ?></h3>
          <p><?php echo htmlspecialchars($prog['nom_service']); ?> · <?php echo htmlspecialchars($prog['prenom'] . ' ' . $prog['nom_intervenant']); ?></p>
        </div>
      </div>

      <?php if ($prog['description']): ?>
        <p class="programme-carte-desc"><?php echo htmlspecialchars($prog['description']); ?></p>
      <?php endif; ?>

      <div class="programme-carte-infos">
        <span>📅 <?php echo $prog['nb_seances']; ?> séances</span>
        <span>⏱ <?php echo $duree_semaines; ?> semaine(s)</span>
        <span>👥 <?php echo $prog['nb_inscrits']; ?> inscrit(s)</span>
        <span>💶 <?php echo number_format($prog['prix'], 2); ?>€</span>
      </div>

      <?php if ($deja_inscrit): ?>
        <div class="btn-inscrit">✅ Déjà inscrit — <a href="profil.php">Voir ma progression</a></div>
      <?php else: ?>
        <form method="POST" action="programmes.php">
          <input type="hidden" name="id_programme" value="<?php echo $prog['id']; ?>">
          <button type="submit" name="btn_inscrire" class="btn-dashboard" style="width:100%; margin-top:12px;">
            S'inscrire au programme
          </button>
        </form>
      <?php endif; ?>
    </div>
    <?php endwhile;
    else: ?>
      <p style="color:#999;">Aucun programme disponible pour le moment.</p>
    <?php endif; ?>
  </div>

</div>

</body>
</html>
