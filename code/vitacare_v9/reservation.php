<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VitaCare - Réservation</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/reservation.css">
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
      <?php if (isset($_SESSION['id'])): ?>
      const connecte = true;
      const prenom   = "<?php echo htmlspecialchars($_SESSION['prenom']); ?>";
      const role     = "<?php echo htmlspecialchars($_SESSION['role']); ?>";
      <?php else: ?>
      const connecte = false;
      const prenom   = "";
      const role     = "";
      <?php endif; ?>

      function getLienDashboard() { return 'profil.php'; } function getLienDashboardOld() {
        if (role === 'admin') return 'dashboard_admin.php';
        if (role === 'intervenant') return 'dashboard_intervenant.php';
        return 'profil.php';
      }

      return (
        <nav className="navbar">
          <div className="navbar-logo">Vitacare</div>
          <ul className="navbar-liens">
            <li><a href="index.php">Accueil</a></li>
            <li><a href="services.php">Services</a></li>
            <li><a href="reservation.php" className="actif">Prendre rendez-vous</a></li>
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

    const etapes = ['Services', 'Date & heure', 'Informations', 'Confirmation'];

    const nomsJours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
    const nomsMois  = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
                       'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];

    function getNomJour(annee, mois, jour) {
      return nomsJours[new Date(annee, mois, jour).getDay()];
    }

    function construireJoursDuMois(annee, mois) {
      const premierJour = new Date(annee, mois, 1).getDay();
      const nbJours     = new Date(annee, mois + 1, 0).getDate();
      const decalage    = premierJour === 0 ? 6 : premierJour - 1;
      const jours = [];
      for (let i = 0; i < decalage; i++) jours.push({ num: null });
      for (let i = 1; i <= nbJours; i++) jours.push({ num: i });
      return jours;
    }

    const serviceParDefaut = {
      id: null, emoji: '🧘', nom: 'Yoga douceur', intervenant: 'Marie Dupont', duree: 60, prix: 20
    };

    // Lire le service UNE SEULE FOIS avant React
    const serviceStocke = localStorage.getItem('serviceChoisi');
    const serviceChoisi = serviceStocke ? JSON.parse(serviceStocke) : serviceParDefaut;

    function Reservation() {
      const aujourdhui    = new Date();
      const [etapeActive, setEtapeActive]           = React.useState(1);
      const [moisActuel, setMoisActuel]             = React.useState(aujourdhui.getMonth());
      const [anneeActuelle, setAnneeActuelle]       = React.useState(aujourdhui.getFullYear());
      const [jourSelectionne, setJourSelectionne]   = React.useState(null);
      const [creneauSelectionne, setCreneauSelectionne] = React.useState(null);
      const [creneaux, setCreneaux]                 = React.useState(null);
      const [message, setMessage]                   = React.useState('');
      const [messageOk, setMessageOk]               = React.useState(false);
      const [nbPlaces, setNbPlaces]                 = React.useState(1);

      const jours = construireJoursDuMois(anneeActuelle, moisActuel);

      // Charger les créneaux depuis PHP au chargement
      React.useEffect(function() {
        if (!serviceChoisi.id) {
          setCreneaux([]);
          return;
        }
        fetch('php/get_creneaux.php?id_service=' + serviceChoisi.id)
          .then(function(res) { return res.json(); })
          .then(function(data) {
            setCreneaux(data);
          })
          .catch(function() {
            setCreneaux([]);
          });
      }, []);

      // Créneaux du jour sélectionné
      function getCreneauxDuJour() {
        if (!jourSelectionne) return [];
        const moisStr = String(moisActuel + 1).padStart(2, '0');
        const jourStr = String(jourSelectionne).padStart(2, '0');
        const dateStr = anneeActuelle + '-' + moisStr + '-' + jourStr;
        return creneaux.filter(function(c) { return c.date_creneau === dateStr; });
      }

      // Jours qui ont des créneaux
      function aDesCreneaux(numJour) {
        if (!numJour || creneaux === null) return false;
        const moisStr = String(moisActuel + 1).padStart(2, '0');
        const jourStr = String(numJour).padStart(2, '0');
        const dateStr = anneeActuelle + '-' + moisStr + '-' + jourStr;
        return creneaux.some(function(c) { return c.date_creneau === dateStr; });
      }

      function estPasse(numJour) {
        const dateJour = new Date(anneeActuelle, moisActuel, numJour);
        const hier = new Date();
        hier.setHours(0, 0, 0, 0);
        return dateJour < hier;
      }

      function getClasseJour(j) {
        if (!j.num) return 'cal-jour vide';
        if (estPasse(j.num)) return 'cal-jour passe';
        if (creneaux === null) return 'cal-jour passe';
        if (!aDesCreneaux(j.num)) return 'cal-jour passe';
        if (j.num === jourSelectionne) return 'cal-jour selectionne';
        return 'cal-jour disponible';
      }

      function moisPrecedent() {
        if (moisActuel === 0) { setMoisActuel(11); setAnneeActuelle(anneeActuelle - 1); }
        else setMoisActuel(moisActuel - 1);
        setJourSelectionne(null);
        setCreneauSelectionne(null);
      }

      function moisSuivant() {
        if (moisActuel === 11) { setMoisActuel(0); setAnneeActuelle(anneeActuelle + 1); }
        else setMoisActuel(moisActuel + 1);
        setJourSelectionne(null);
        setCreneauSelectionne(null);
      }

      function clicJour(j) {
        if (j.num && !estPasse(j.num) && creneaux !== null && aDesCreneaux(j.num)) {
          setJourSelectionne(j.num);
          setCreneauSelectionne(null);
        }
      }

      // Ajouter au panier
      function ajouterAuPanier() {
        if (!creneauSelectionne) {
          setMessage('Veuillez sélectionner un créneau.');
          setMessageOk(false);
          return;
        }

        const panier = JSON.parse(localStorage.getItem('panier_vitacare') || '[]');

        // Vérifier doublon dans le panier
        const dejaDans = panier.some(function(item) { return item.id_creneau === creneauSelectionne.id; });
        if (dejaDans) {
          setMessage('Ce créneau est déjà dans votre panier.');
          setMessageOk(false);
          return;
        }

        // Formater la date
        const moisStr = String(moisActuel + 1).padStart(2, '0');
        const jourStr = String(jourSelectionne).padStart(2, '0');
        const dateStr = anneeActuelle + '-' + moisStr + '-' + jourStr;

        panier.push({
          id_creneau   : creneauSelectionne.id,
          nom_service  : serviceChoisi.nom,
          emoji        : serviceChoisi.emoji,
          intervenant  : serviceChoisi.intervenant,
          duree        : serviceChoisi.duree,
          prix         : (serviceChoisi.prix * nbPlaces).toFixed(2),
          prix_unitaire: serviceChoisi.prix,
          nb_places    : nbPlaces,
          date_creneau : dateStr,
          heure        : creneauSelectionne.heure,
        });

        localStorage.setItem('panier_vitacare', JSON.stringify(panier));
        setMessage('Ajouté au panier ! (' + panier.length + ' article(s))');
        setMessageOk(true);
      }

      // Réserver directement via PHP (gardé pour compatibilité)
      function faireReservation() {
        if (!creneauSelectionne) {
          setMessage('Veuillez sélectionner un créneau.');
          setMessageOk(false);
          return;
        }

        const formData = new FormData();
        formData.append('id_creneau', creneauSelectionne.id);

        fetch('php/reserver.php', { method: 'POST', body: formData })
          .then(function(res) { return res.json(); })
          .then(function(data) {
            setMessage(data.message);
            setMessageOk(data.succes);
            if (data.succes) {
              setEtapeActive(3);
              setCreneaux(creneaux.map(function(c) {
                if (c.id === creneauSelectionne.id) {
                  return Object.assign({}, c, { places_restantes: c.places_restantes - 1 });
                }
                return c;
              }));
            }
          });
      }

      const creneauxDuJour = getCreneauxDuJour();

      return (
        <div className="reservation-page">

          {/* MESSAGE CONFIRMATION */}
          {message && (
            <div className={messageOk ? 'alerte-resa ok' : 'alerte-resa erreur'}>
              {message}
              {messageOk && <a href="profil.php" style={{marginLeft: '10px', fontWeight: '700', color: 'inherit'}}> → Voir mes réservations</a>}
            </div>
          )}

          <div className="reservation-contenu">

            {/* CALENDRIER */}
            <div className="calendrier-section">
              <div className="service-selectionne">
                <div className="service-emoji">{serviceChoisi.emoji}</div>
                <div>
                  <h3>{serviceChoisi.nom}</h3>
                  <p>{serviceChoisi.duree} min · {serviceChoisi.intervenant}</p>
                </div>
                <span className="service-prix">{serviceChoisi.prix}€</span>
              </div>

              <div className="calendrier">
                <div className="cal-header">
                  <button className="cal-nav" onClick={moisPrecedent}>‹</button>
                  <h3>{nomsMois[moisActuel]} {anneeActuelle}</h3>
                  <button className="cal-nav" onClick={moisSuivant}>›</button>
                </div>
                <div className="cal-jours-semaine">
                  {['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'].map(function(j) {
                    return <span key={j}>{j}</span>;
                  })}
                </div>
                <div className="cal-grille">
                  {jours.map(function(j, i) {
                    return (
                      <div key={i} className={getClasseJour(j)} onClick={function() { clicJour(j); }}>
                        {j.num}
                      </div>
                    );
                  })}
                </div>
                <div className="cal-legende">
                  <span className="legende-item"><span className="legende-carre disponible"></span> Disponible</span>
                  <span className="legende-item"><span className="legende-carre sel"></span> Sélectionné</span>
                </div>
              </div>

              <div className="creneaux-section">
                <p className="creneaux-titre">
                  {jourSelectionne
                    ? 'Créneaux · ' + getNomJour(anneeActuelle, moisActuel, jourSelectionne) + ' ' + jourSelectionne + ' ' + nomsMois[moisActuel]
                    : 'Sélectionnez un jour disponible'}
                </p>
                {jourSelectionne && creneauxDuJour.length === 0 && (
                  <p style={{fontSize: '13px', color: '#999'}}>Aucun créneau ce jour.</p>
                )}
                <div className="creneaux-grille">
                  {creneauxDuJour.map(function(c) {
                    const estComplet = c.places_restantes <= 0;
                    const estSelectionne = creneauSelectionne && creneauSelectionne.id === c.id;
                    let classe = 'creneau';
                    if (estComplet) classe = 'creneau indisponible';
                    else if (estSelectionne) classe = 'creneau selectionne';
                    return (
                      <button
                        key={c.id}
                        className={classe}
                        onClick={function() { if (!estComplet) setCreneauSelectionne(c); }}
                        disabled={estComplet}
                      >
                        {c.heure}
                        {c.places_restantes <= 3 && !estComplet && (
                          <span className="creneau-places"> ({c.places_restantes} pl.)</span>
                        )}
                        {estComplet && <span className="creneau-places"> (complet)</span>}
                      </button>
                    );
                  })}
                </div>
              </div>
            </div>

            {/* RÉCAPITULATIF */}
            <div className="recapitulatif">
              <h3>Récapitulatif</h3>
              <div className="recap-liste">
                <div className="recap-item">
                  <span className="recap-icone">{serviceChoisi.emoji}</span>
                  <div>
                    <p className="recap-label">Service</p>
                    <p className="recap-valeur">{serviceChoisi.nom}</p>
                  </div>
                </div>
                <div className="recap-item">
                  <span className="recap-icone">👤</span>
                  <div>
                    <p className="recap-label">Intervenant</p>
                    <p className="recap-valeur">{serviceChoisi.intervenant}</p>
                  </div>
                </div>
                <div className="recap-item">
                  <span className="recap-icone">📅</span>
                  <div>
                    <p className="recap-label">Date</p>
                    <p className="recap-valeur">
                      {jourSelectionne
                        ? getNomJour(anneeActuelle, moisActuel, jourSelectionne) + ' ' + jourSelectionne + ' ' + nomsMois[moisActuel] + ' ' + anneeActuelle
                        : 'Aucun jour sélectionné'}
                    </p>
                  </div>
                </div>
                <div className="recap-item">
                  <span className="recap-icone">🕐</span>
                  <div>
                    <p className="recap-label">Heure · Durée</p>
                    <p className="recap-valeur">
                      {creneauSelectionne ? creneauSelectionne.heure + ' · ' + serviceChoisi.duree + ' min' : 'Aucun créneau sélectionné'}
                    </p>
                  </div>
                </div>
                <div className="recap-item">
                  <span className="recap-icone">💶</span>
                  <div>
                    <p className="recap-label">Tarif</p>
                    <p className="recap-valeur">{serviceChoisi.prix}€ × {nbPlaces} = {(serviceChoisi.prix * nbPlaces).toFixed(2)}€</p>
                  </div>
                </div>
                <div className="recap-item">
                  <span className="recap-icone">👥</span>
                  <div style={{width:'100%'}}>
                    <p className="recap-label">Nombre de places</p>
                    <div style={{display:'flex', alignItems:'center', gap:'10px', marginTop:'4px'}}>
                      <button onClick={function() { if(nbPlaces > 1) setNbPlaces(nbPlaces-1); }} style={{width:'28px', height:'28px', borderRadius:'50%', border:'1.5px solid var(--vert-fonce)', background:'none', cursor:'pointer', fontSize:'16px', fontWeight:'700', color:'var(--vert-fonce)'}}>−</button>
                      <span style={{fontWeight:'700', fontSize:'16px'}}>{nbPlaces}</span>
                      <button onClick={function() { if(creneauSelectionne && nbPlaces < creneauSelectionne.places_restantes) setNbPlaces(nbPlaces+1); }} style={{width:'28px', height:'28px', borderRadius:'50%', border:'1.5px solid var(--vert-fonce)', background:'none', cursor:'pointer', fontSize:'16px', fontWeight:'700', color:'var(--vert-fonce)'}}>+</button>
                      {creneauSelectionne && <span style={{fontSize:'12px', color:'var(--texte-gris)'}}>({creneauSelectionne.places_restantes} disponible(s))</span>}
                    </div>
                  </div>
                </div>
              </div>

              <div className="recap-info">
                ℹ️ Annulation gratuite jusqu'à 24h avant le rendez-vous.
              </div>

              <button className="btn-continuer" onClick={ajouterAuPanier}>
                🛒 Ajouter au panier
              </button>
              {messageOk && (
                <a href="panier.php" className="btn-retour" style={{display:'block', textAlign:'center', marginTop:'8px', color:'var(--vert-fonce)', fontWeight:'600'}}>
                  Voir le panier →
                </a>
              )}
              <button className="btn-retour" onClick={function() { window.history.back(); }}>
                Retour
              </button>
            </div>

          </div>
        </div>
      );
    }

    const root = ReactDOM.createRoot(document.getElementById('root'));
    root.render(<Reservation />);
  </script>

</body>
</html>
