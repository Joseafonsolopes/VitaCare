<?php
session_start();
require_once 'php/connexion.php';

$connecte = isset($_SESSION['id']);
$prenom = $connecte ? htmlspecialchars($_SESSION['prenom']) : '';
$role   = $connecte ? htmlspecialchars($_SESSION['role']) : '';

// Récupérer les prochaines réservations de l'utilisateur connecté
$rdv_json = '[]';
$activite_json = 'null';

if ($connecte) {
  $id_user = $_SESSION['id'];

  // Prochaines réservations confirmées
  $sql_rdv = "SELECT s.nom, s.emoji, c.date_creneau, c.heure
              FROM reservations r, creneaux c, services s
              WHERE r.id_creneau = c.id
              AND c.id_service = s.id
              AND r.id_utilisateur = '$id_user'
              AND r.statut = 'confirmee'
              AND c.date_creneau >= CURDATE()
              ORDER BY c.date_creneau ASC, c.heure ASC
              LIMIT 2";
  $result_rdv = mysqli_query($conn, $sql_rdv);
  $rdv_array = array();
  while ($row = mysqli_fetch_assoc($result_rdv)) {
    $rdv_array[] = array(
      'nom'   => $row['nom'],
      'emoji' => $row['emoji'] ? $row['emoji'] : '🧘',
      'date'  => $row['date_creneau'],
      'heure' => $row['heure'],
    );
  }
  $rdv_json = json_encode($rdv_array);

  // Prochaine activité disponible que l'utilisateur n'a PAS réservée
  $sql_activite = "SELECT s.nom, s.emoji, c.date_creneau, c.heure, c.places_restantes
                   FROM creneaux c, services s
                   WHERE c.id_service = s.id
                   AND c.date_creneau >= CURDATE()
                   AND c.places_restantes > 0
                   AND c.id NOT IN (
                     SELECT id_creneau FROM reservations WHERE id_utilisateur = '$id_user'
                   )
                   ORDER BY c.date_creneau ASC, c.heure ASC
                   LIMIT 1";
  $result_activite = mysqli_query($conn, $sql_activite);
  if (mysqli_num_rows($result_activite) > 0) {
    $row = mysqli_fetch_assoc($result_activite);
    $activite_json = json_encode(array(
      'nom'   => $row['nom'],
      'emoji' => $row['emoji'] ? $row['emoji'] : '🧘',
      'date'  => $row['date_creneau'],
      'heure' => $row['heure'],
    ));
  }
}

// Récupérer les services pour la section d'exemple
$sql_services = "SELECT s.nom, s.emoji, s.categorie, u.prenom, u.nom AS nom_intervenant
                 FROM services s, utilisateurs u
                 WHERE s.id_intervenant = u.id
                 AND s.actif = 1
                 ORDER BY s.id ASC
                 LIMIT 6";
$result_services = mysqli_query($conn, $sql_services);
$services_array = array();
while ($row = mysqli_fetch_assoc($result_services)) {
  $services_array[] = array(
    'nom'      => $row['nom'],
    'emoji'    => $row['emoji'] ? $row['emoji'] : '🧘',
    'categorie'=> $row['categorie'],
  );
}
$services_json = json_encode($services_array);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VitaCare - Accueil</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/accueil.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body>

  <div id="navbar"></div>
  <div id="root"></div>

  <script type="text/babel">
    function Navbar() {
      const connecte = <?php echo $connecte ? 'true' : 'false'; ?>;
      const prenom   = "<?php echo $prenom; ?>";
      const role     = "<?php echo $role; ?>";

      function getLienDashboard() { return 'profil.php'; } function getLienDashboardOld() {
        if (role === 'admin') return 'dashboard_admin.php';
        if (role === 'intervenant') return 'dashboard_intervenant.php';
        return 'profil.php';
      }

      return (
        <nav className="navbar">
          <div className="navbar-logo">Vitacare</div>
          <ul className="navbar-liens">
            <li><a href="index.php" className="actif">Accueil</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="reservation.php">Prendre rendez-vous</a></li>
            <li><a href="programmes.php">Programmes</a></li>
            <li><a href="notifications.php">Notifications</a></li>
            <li><a href="panier.php">Panier</a></li>
            <li><a href="profil.php">Mon profil</a></li>
          </ul>
          {connecte ? (
            <div className="navbar-droite">
              <a href={getLienDashboard()} className="navbar-prenom">👤 {prenom}</a>
              <a href="deconnexion.php" className="navbar-btn-connexion">Déconnexion</a>
            </div>
          ) : (
            <a href="connexion.php" className="navbar-btn-connexion">Connexion</a>
          )}
        </nav>
      );
    }
    const navRoot = ReactDOM.createRoot(document.getElementById('navbar'));
    navRoot.render(<Navbar />);
  </script>

  <script type="text/babel">

    const connecte  = <?php echo $connecte ? 'true' : 'false'; ?>;
    const rdvData   = <?php echo $rdv_json; ?>;
    const activite  = <?php echo $activite_json; ?>;
    const services  = <?php echo $services_json; ?>;

    function formatDate(dateStr) {
      const jours = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];
      const mois  = ['jan', 'fév', 'mar', 'avr', 'mai', 'jun', 'jul', 'aoû', 'sep', 'oct', 'nov', 'déc'];
      const d = new Date(dateStr);
      return jours[d.getDay()] + '. ' + d.getDate() + ' ' + mois[d.getMonth()];
    }

    function CarteService({ emoji, categorie, nom }) {
      return (
        <div className="carte-service">
          <div className="carte-icone">{emoji}</div>
          <p className="carte-type">{categorie}</p>
          <p className="carte-nom">{nom}</p>
        </div>
      );
    }

    function Widget() {
      if (!connecte) {
        return (
          <div className="hero-widget">
            <p className="widget-titre">Bienvenue sur VitaCare</p>
            <div className="widget-rdv">Connectez-vous pour voir vos réservations</div>
            <a href="connexion.php" className="widget-btn">Se connecter</a>
          </div>
        );
      }

      return (
        <div className="hero-widget">
          <p className="widget-titre">Prochains rendez-vous</p>
          {rdvData.length > 0 ? (
            rdvData.map(function(rdv, i) {
              return (
                <div key={i} className="widget-rdv">
                  {rdv.emoji} {rdv.nom} · {formatDate(rdv.date)} à {rdv.heure}
                </div>
              );
            })
          ) : (
            <div className="widget-rdv-vide">Aucune réservation à venir</div>
          )}

          {activite && (
            <div>
              <p className="widget-titre" style={{marginTop: '14px'}}>À découvrir</p>
              <div className="widget-rdv widget-activite">
                {activite.emoji} {activite.nom}<br/>
                <small>{formatDate(activite.date)} à {activite.heure}</small>
              </div>
            </div>
          )}
        </div>
      );
    }

    function Accueil() {
      return (
        <div>
          <section className="hero">
            <div className="hero-gauche">
              <span className="hero-badge">Bien-être holistique</span>
              <h1>Votre santé et votre<br />sérénité, au quotidien</h1>
              <p>Réservez vos consultations, participez à des ateliers<br />de bien-être et suivez votre parcours en toute simplicité.</p>
              <div className="hero-boutons">
                {connecte ? (
                  <a href="profil.php" className="btn-primaire">Voir mes réservations</a>
                ) : (
                  <a href="connexion.php" className="btn-primaire">Se connecter</a>
                )}
                <a href="services.php" className="btn-secondaire">Découvrir les services</a>
              </div>
            </div>
            <Widget />
          </section>

          <section className="section-services">
            <h2>Exemples de services proposés</h2>
            <p className="section-sous-titre">Découvrez quelques-unes des activités disponibles sur VitaCare</p>
            <div className="grille-services">
              {services.map(function(s, i) {
                return <CarteService key={i} emoji={s.emoji} categorie={s.categorie} nom={s.nom} />;
              })}
            </div>
            <div className="section-cta">
              <a href="services.php" className="btn-primaire">Voir tous les services</a>
            </div>
          </section>

          <section className="stats">
            <div className="stat-item"><span className="stat-nombre">32</span><span className="stat-label">Services disponibles</span></div>
            <div className="stat-item"><span className="stat-nombre">12</span><span className="stat-label">Intervenants</span></div>
            <div className="stat-item"><span className="stat-nombre">320+</span><span className="stat-label">Membres actifs</span></div>
            <div className="stat-item"><span className="stat-nombre">4,8 ★</span><span className="stat-label">Satisfaction</span></div>
          </section>
        </div>
      );
    }

    const root = ReactDOM.createRoot(document.getElementById('root'));
    root.render(<Accueil />);
  </script>

</body>
</html>
