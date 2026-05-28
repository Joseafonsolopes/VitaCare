<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Connexion</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/auth.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<div class="auth-page">

  <div class="auth-logo">
    <a href="index.php">Vitacare</a>
  </div>

  <div class="auth-carte">
    <h1>Connexion</h1>
    <p class="auth-sous-titre">Bon retour sur VitaCare</p>

    <?php
    session_start();
    require_once 'php/connexion.php';

    $erreur = '';

    if (isset($_POST['btn_connexion'])) {

      $email     = htmlspecialchars($_POST['email']);
      $mot_passe = $_POST['mot_de_passe'];

      if (empty($email) || empty($mot_passe)) {
        $erreur = 'Veuillez remplir tous les champs.';
      } else {
        // Chercher l'utilisateur par email
        $sql = "SELECT * FROM utilisateurs WHERE email = '$email' AND actif = 1";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) === 1) {
          $utilisateur = mysqli_fetch_assoc($result);

          // Vérifier le mot de passe
          if (password_verify($mot_passe, $utilisateur['mot_de_passe'])) {
            // Créer la session
            $_SESSION['id']     = $utilisateur['id'];
            $_SESSION['prenom'] = $utilisateur['prenom'];
            $_SESSION['nom']    = $utilisateur['nom'];
            $_SESSION['email']  = $utilisateur['email'];
            $_SESSION['role']   = $utilisateur['role'];

            // Redirection selon le rôle
            if ($utilisateur['role'] === 'admin') {
              header('Location: dashboard_admin.php');
            } elseif ($utilisateur['role'] === 'intervenant') {
              header('Location: dashboard_intervenant.php');
            } else {
              header('Location: index.php');
            }
            exit();
          } else {
            $erreur = 'Email ou mot de passe incorrect.';
          }
        } else {
          $erreur = 'Email ou mot de passe incorrect.';
        }
      }
    }
    ?>

    <?php if ($erreur): ?>
      <div class="alerte alerte-erreur"><?php echo $erreur; ?></div>
    <?php endif; ?>

    <form method="POST" action="connexion.php">

      <div class="form-groupe">
        <label>Email</label>
        <input type="email" name="email" placeholder="votre@email.fr" required>
      </div>

      <div class="form-groupe">
        <label>Mot de passe</label>
        <input type="password" name="mot_de_passe" placeholder="Votre mot de passe" required>
      </div>

      <button type="submit" name="btn_connexion" class="btn-auth">Se connecter</button>

    </form>

    <!-- Comptes de test -->
    <div class="comptes-test">
      <p class="test-titre">Comptes de test (mot de passe : vitacare123)</p>
      <div class="test-liste">
        <span>👤 Senior : natheo.milliet@vitacare.fr</span>
        <span>🧑‍⚕️ Intervenant : marie.dupont@vitacare.fr</span>
        <span>⚙️ Admin : admin@vitacare.fr</span>
      </div>
    </div>

    <p class="auth-lien">Pas encore de compte ? <a href="inscription.php">S'inscrire</a></p>

  </div>
</div>

</body>
</html>
