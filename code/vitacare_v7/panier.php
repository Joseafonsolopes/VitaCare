<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Panier</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/panier.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
  <script src="https://unpkg.com/react@18/umd/react.development.js"></script>
  <script src="https://unpkg.com/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://unpkg.com/@babel/standalone/babel.min.js"></script>
</head>
<body>

<?php
session_start();
require_once 'php/connexion.php';
if (!isset($_SESSION['id'])) { header('Location: connexion.php'); exit(); }
$id_utilisateur = $_SESSION['id'];
$prenom = htmlspecialchars($_SESSION['prenom']);
?>

<nav class="navbar">
  <div class="navbar-logo">Vitacare</div>
  <ul class="navbar-liens">
    <li><a href="index.php">Accueil</a></li>
    <li><a href="services.php">Services</a></li>
    <li><a href="reservation.php">Prendre rendez-vous</a></li>
    <li><a href="programmes.php">Programmes</a></li>
    <li><a href="notifications.php">Notifications</a></li>
    <li><a href="panier.php" class="actif">Panier</a></li>
    <li><a href="profil.php">Mon profil</a></li>
  </ul>
  <div class="navbar-user">
    <span class="navbar-prenom">👤 <?php echo htmlspecialchars($_SESSION['prenom']); ?></span>
    <a href="deconnexion.php" class="navbar-btn-connexion">Déconnexion</a>
  </div>
</nav>

<div id="root"></div>

<script type="text/babel">

  function Panier() {
    const [panier, setPanier]       = React.useState([]);
    const [etape, setEtape]         = React.useState(1);
    const [message, setMessage]     = React.useState('');
    const [loading, setLoading]     = React.useState(false);

    React.useEffect(function() {
      const data = localStorage.getItem('panier_vitacare');
      if (data) setPanier(JSON.parse(data));
    }, []);

    function supprimerItem(index) {
      const newPanier = panier.filter(function(_, i) { return i !== index; });
      setPanier(newPanier);
      localStorage.setItem('panier_vitacare', JSON.stringify(newPanier));
    }

    function viderPanier() {
      setPanier([]);
      localStorage.removeItem('panier_vitacare');
    }

    const total = panier.reduce(function(acc, item) { return acc + parseFloat(item.prix); }, 0);

    function confirmerPaiement() {
      if (panier.length === 0) return;
      setLoading(true);
      setMessage('');

      let promesses = panier.map(function(item) {
        const formData = new FormData();
        formData.append('id_creneau', item.id_creneau);
        return fetch('php/reserver.php', { method: 'POST', body: formData })
          .then(function(res) { return res.json(); });
      });

      Promise.all(promesses).then(function(resultats) {
        setLoading(false);
        const erreurs = resultats.filter(function(r) { return !r.succes; });
        if (erreurs.length === 0) {
          viderPanier();
          setEtape(3);
        } else {
          setMessage('Certaines réservations ont échoué : ' + erreurs.map(function(e) { return e.message; }).join(', '));
        }
      });
    }

    // CONFIRMATION
    if (etape === 3) {
      return (
        <div className="panier-page">
          <div className="confirmation-bloc">
            <div className="confirmation-icone">✅</div>
            <h1>Réservations confirmées !</h1>
            <p>Toutes vos réservations ont été enregistrées avec succès.</p>
            <p>Vous avez reçu une notification pour chaque activité.</p>
            <div className="confirmation-actions">
              <a href="profil.php" className="btn-primaire">Voir mes réservations</a>
              <a href="services.php" className="btn-secondaire" style={{marginLeft:'12px'}}>Continuer à explorer</a>
            </div>
          </div>
        </div>
      );
    }

    // PAIEMENT SIMULÉ
    if (etape === 2) {
      return (
        <div className="panier-page">
          <h1 className="panier-titre">💳 Paiement sécurisé (simulé)</h1>
          <p className="panier-sous-titre">Le paiement est simulé dans le cadre du projet pédagogique.</p>

          {message && <div className="alerte-panier erreur">{message}</div>}

          <div className="paiement-grille">
            <div className="paiement-carte">
              <h3>Informations de paiement</h3>
              <div className="carte-bancaire">
                <div className="carte-numero">4242 4242 4242 4242</div>
                <div className="carte-bas"><span>12/28</span><span style={{fontWeight:'700'}}>VISA</span></div>
              </div>
              <div className="form-groupe" style={{marginTop:'16px'}}>
                <label>Numéro de carte</label>
                <input type="text" defaultValue="4242 4242 4242 4242" />
              </div>
              <div style={{display:'flex', gap:'12px'}}>
                <div className="form-groupe" style={{flex:1}}>
                  <label>Expiration</label>
                  <input type="text" defaultValue="12/28" />
                </div>
                <div className="form-groupe" style={{flex:1}}>
                  <label>CVV</label>
                  <input type="text" defaultValue="123" />
                </div>
              </div>
            </div>

            <div className="resume-commande">
              <h3>Résumé de la commande</h3>
              {panier.map(function(item, i) {
                return (
                  <div key={i} className="recap-ligne">
                    <span>{item.emoji} {item.nom_service}</span>
                    <span>{item.prix}€</span>
                  </div>
                );
              })}
              <div className="recap-total">
                <span>Total</span>
                <span>{total.toFixed(2)}€</span>
              </div>
              <p className="recap-nb">pour {panier.length} réservation(s)</p>

              <div className="verifications">
                <p className="verif-titre">✅ Vérifications effectuées</p>
                <p className="verif-item">✓ Disponibilités confirmées</p>
                <p className="verif-item">✓ Conditions respectées</p>
                <p className="verif-item">✓ Aucune limite dépassée</p>
              </div>

              <button className="btn-payer" onClick={confirmerPaiement} disabled={loading}>
                {loading ? 'Traitement...' : '🔒 Valider et payer (simulation)'}
              </button>
              <p className="paiement-info">Aucun paiement réel ne sera effectué.</p>
              <button className="btn-retour-panier" onClick={function() { setEtape(1); }}>← Retour au panier</button>
            </div>
          </div>
        </div>
      );
    }

    // PANIER
    return (
      <div className="panier-page">
        <h1 className="panier-titre">🛒 Panier de réservations</h1>
        <p className="panier-sous-titre">{panier.length} réservation(s) sélectionnée(s)</p>

        <div className="panier-grille">
          <div className="panier-items">
            {panier.length === 0 ? (
              <div className="panier-vide">
                <p style={{fontSize:'16px', color:'#999'}}>🛒 Votre panier est vide.</p>
                <a href="services.php" className="btn-primaire" style={{display:'inline-block', marginTop:'14px'}}>
                  Découvrir les services
                </a>
              </div>
            ) : (
              panier.map(function(item, i) {
                return (
                  <div key={i} className="panier-item">
                    <div className="panier-item-emoji">{item.emoji}</div>
                    <div className="panier-item-info">
                      <p className="panier-item-nom">{item.nom_service}</p>
                      <p className="panier-item-meta">{item.intervenant} · {item.date_creneau} à {item.heure} · {item.duree} min</p>
                    </div>
                    <span className="panier-item-prix">{item.prix}€</span>
                    <button className="panier-item-suppr" onClick={function() { supprimerItem(i); }}>✕</button>
                  </div>
                );
              })
            )}
            {panier.length > 0 && (
              <div className="panier-actions">
                <a href="services.php" className="btn-ajouter">+ Ajouter un service</a>
                <button className="btn-vider" onClick={viderPanier}>Vider le panier</button>
              </div>
            )}
          </div>

          {panier.length > 0 && (
            <div className="panier-recap">
              <h3>Récapitulatif</h3>
              {panier.map(function(item, i) {
                return (
                  <div key={i} className="recap-ligne">
                    <span>{item.nom_service}</span>
                    <span>{item.prix}€</span>
                  </div>
                );
              })}
              <div className="recap-total"><span>Total</span><span>{total.toFixed(2)}€</span></div>
              <p className="recap-nb">pour {panier.length} réservation(s)</p>
              <button className="btn-valider" onClick={function() { setEtape(2); }}>
                Continuer vers le paiement →
              </button>
            </div>
          )}
        </div>
      </div>
    );
  }

  const root = ReactDOM.createRoot(document.getElementById('root'));
  root.render(<Panier />);
</script>

</body>
</html>
