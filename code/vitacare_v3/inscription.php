<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Inscription</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/auth.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="auth-page">

  <div class="auth-logo">
    <a href="index.html">Vitacare</a>
  </div>

  <div class="auth-carte">
    <h1>Créer un compte</h1>
    <p class="auth-sous-titre">Rejoignez VitaCare et prenez soin de vous</p>

    <?php
    require_once 'php/connexion.php';

    $erreur = '';
    $succes = '';

    if (isset($_POST['btn_inscription'])) {

      $prenom    = htmlspecialchars($_POST['prenom']);
      $nom       = htmlspecialchars($_POST['nom']);
      $email     = htmlspecialchars($_POST['email']);
      $mot_passe = $_POST['mot_de_passe'];
      $confirm   = $_POST['confirmation'];
      $role      = htmlspecialchars($_POST['role']);
      $adresse   = htmlspecialchars($_POST['adresse']);
      $telephone = htmlspecialchars($_POST['telephone']);

      // Vérifications
      if (empty($prenom) || empty($nom) || empty($email) || empty($mot_passe)) {
        $erreur = 'Veuillez remplir tous les champs obligatoires.';
      } elseif ($mot_passe !== $confirm) {
        $erreur = 'Les mots de passe ne correspondent pas.';
      } elseif (strlen($mot_passe) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
      } else {
        // Vérifier si l'email existe déjà
        $sql_check = "SELECT id FROM utilisateurs WHERE email = '$email'";
        $result_check = mysqli_query($conn, $sql_check);

        if (mysqli_num_rows($result_check) > 0) {
          $erreur = 'Cet email est déjà utilisé.';
        } else {
          // Hasher le mot de passe
          $mot_passe_hash = password_hash($mot_passe, PASSWORD_DEFAULT);
          $date_aujourd_hui = date('Y-m-d');

          $sql = "INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role, adresse, telephone, date_inscription)
                  VALUES ('$nom', '$prenom', '$email', '$mot_passe_hash', '$role', '$adresse', '$telephone', '$date_aujourd_hui')";

          if (mysqli_query($conn, $sql)) {
            $succes = 'Compte créé avec succès ! Vous pouvez maintenant vous connecter.';
          } else {
            $erreur = 'Erreur lors de la création du compte : ' . mysqli_error($conn);
          }
        }
      }
    }
    ?>

    <?php if ($erreur): ?>
      <div class="alerte alerte-erreur"><?php echo $erreur; ?></div>
    <?php endif; ?>

    <?php if ($succes): ?>
      <div class="alerte alerte-succes"><?php echo $succes; ?></div>
      <a href="connexion.php" class="btn-auth" style="display:block; text-align:center; margin-top:10px;">Se connecter</a>
    <?php else: ?>

    <form method="POST" action="inscription.php">

      <div class="form-groupe">
        <label>Rôle *</label>
        <div class="role-choix">
          <label class="role-option">
            <input type="radio" name="role" value="senior" checked>
            <div class="role-carte">
              <span class="role-emoji">👤</span>
              <span>Senior</span>
              <small>Je cherche des services</small>
            </div>
          </label>
          <label class="role-option">
            <input type="radio" name="role" value="intervenant">
            <div class="role-carte">
              <span class="role-emoji">🧑‍⚕️</span>
              <span>Intervenant</span>
              <small>Je propose des services</small>
            </div>
          </label>
        </div>
      </div>

      <div class="form-ligne">
        <div class="form-groupe">
          <label>Prénom *</label>
          <input type="text" name="prenom" placeholder="Votre prénom" required>
        </div>
        <div class="form-groupe">
          <label>Nom *</label>
          <input type="text" name="nom" placeholder="Votre nom" required>
        </div>
      </div>

      <div class="form-groupe">
        <label>Email *</label>
        <input type="email" name="email" placeholder="votre@email.fr" required>
      </div>

      <div class="form-groupe">
        <label>Adresse</label>
        <input type="text" name="adresse" placeholder="Votre adresse">
      </div>

      <div class="form-groupe">
        <label>Téléphone</label>
        <input type="text" name="telephone" placeholder="06 00 00 00 00">
      </div>

      <div class="form-ligne">
        <div class="form-groupe">
          <label>Mot de passe *</label>
          <input type="password" name="mot_de_passe" placeholder="6 caractères minimum" required>
        </div>
        <div class="form-groupe">
          <label>Confirmation *</label>
          <input type="password" name="confirmation" placeholder="Répétez le mot de passe" required>
        </div>
      </div>

      <button type="submit" name="btn_inscription" class="btn-auth">Créer mon compte</button>

    </form>

    <?php endif; ?>

    <p class="auth-lien">Déjà un compte ? <a href="connexion.php">Se connecter</a></p>

  </div>
</div>

</body>
</html>
