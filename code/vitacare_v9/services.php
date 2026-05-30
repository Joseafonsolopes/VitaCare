<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>VitaCare - Services</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/services.css">
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
            <li><a href="services.php" className="actif">Services</a></li>
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

    const categories = ['Toutes', 'Bien-être', 'Nutrition', 'Santé mentale', 'Activités', 'Thérapies', 'Consultations'];
    const optionsTri = [
      { val: 'defaut', label: 'Par défaut' },
      { val: 'prix_asc', label: 'Prix croissant' },
      { val: 'prix_desc', label: 'Prix décroissant' },
      { val: 'duree_asc', label: 'Durée croissante' },
    ];

    function BadgePlaces({ minPlaces }) {
      if (minPlaces === null) return null;
      if (minPlaces === 0) return <span className="badge-places complet">Complet</span>;
      if (minPlaces <= 3) return <span className="badge-places peu">Peu de places</span>;
      return null;
    }

    function CarteService({ service }) {
      function allerReservation() {
        localStorage.setItem('serviceChoisi', JSON.stringify(service));
        window.location.href = 'reservation.php';
      }

      return (
        <div className="carte-catalogue">
          <div className="carte-header" style={{backgroundColor: service.couleur}}>
            <span className="carte-emoji">{service.emoji}</span>
            <div className="carte-badges">
              {service.badge && (
                <span className={service.badge === 'Populaire' ? 'badge badge-vert' : 'badge badge-beige'}>
                  {service.badge}
                </span>
              )}
              <BadgePlaces minPlaces={service.min_places} />
            </div>
          </div>
          <div className="carte-body">
            <h3>{service.nom}</h3>
            <p className="carte-intervenant">{service.intervenant} · {service.duree} min</p>
            {service.description && <p className="carte-description">{service.description}</p>}
            <div className="carte-footer">
              <span className="carte-prix">{service.prix}€</span>
              <div style={{display:'flex', gap:'8px'}}>
                <a href={'service_detail.php?id=' + service.id} className="btn-detail">Détails</a>
                <button className="btn-creneaux" onClick={allerReservation}>
                  Réserver
                </button>
              </div>
            </div>
          </div>
        </div>
      );
    }

    function Services() {
      const [services, setServices] = React.useState([]);
      const [chargement, setChargement] = React.useState(true);
      const [recherche, setRecherche] = React.useState('');
      const [categorieActive, setCategorieActive] = React.useState('Toutes');
      const [filtreduree, setFiltreduree] = React.useState('');
      const [filtrePrix, setFiltrePrix] = React.useState('');
      const [tri, setTri] = React.useState('recent');


      // Charger les services depuis PHP
      React.useEffect(function() {
        setChargement(true);
        let url = 'php/get_services.php?';
        if (recherche) url += 'recherche=' + encodeURIComponent(recherche) + '&';
        if (categorieActive !== 'Toutes') url += 'categorie=' + encodeURIComponent(categorieActive);

        fetch(url)
          .then(function(res) { return res.json(); })
          .then(function(data) {
            setServices(data);
            setChargement(false);
          })
          .catch(function() {
            setChargement(false);
          });
      }, [recherche, categorieActive]);

      function reinitialiserFiltres() {
        setRecherche('');
        setCategorieActive('Toutes');
        setFiltreduree('');
        setFiltrePrix('');
      }

      // Filtres durée et prix côté client
      const servicesFiltres = services.filter(function(s) {
        const matchDuree = !filtreduree ||
          (filtreduree === '30' && s.duree <= 30) ||
          (filtreduree === '30-60' && s.duree > 30 && s.duree <= 60) ||
          (filtreduree === '90' && s.duree > 60);
        const matchPrix = !filtrePrix ||
          (filtrePrix === '0-30' && s.prix <= 30) ||
          (filtrePrix === '30-60' && s.prix > 30 && s.prix <= 60) ||
          (filtrePrix === '60+' && s.prix > 60);
        return matchDuree && matchPrix;
      }).sort(function(a, b) {
        if (tri === 'prix_asc')   return a.prix - b.prix;
        if (tri === 'prix_desc')  return b.prix - a.prix;
        if (tri === 'duree_asc')  return a.duree - b.duree;
        return 0;
      });

      return (
        <div className="services-page">

          <aside className="sidebar-filtres">
            <div className="filtres-header">
              <h3>Filtres</h3>
              <button className="btn-reinitialiser" onClick={reinitialiserFiltres}>Réinitialiser</button>
            </div>

            <div className="filtre-groupe">
              <p className="filtre-titre">Type de service</p>
              {['Toutes', 'Consultations', 'Activités', 'Thérapies', 'Nutrition'].map(function(c) {
                return (
                  <label key={c} className="filtre-option">
                    <input type="radio" name="type" checked={categorieActive === c} onChange={function() { setCategorieActive(c); }} />
                    {c}
                  </label>
                );
              })}
            </div>

            <div className="filtre-groupe">
              <p className="filtre-titre">Durée</p>
              {[{val: '30', label: '30 min'}, {val: '30-60', label: '30 - 60 min'}, {val: '90', label: '90 min+'}].map(function(d) {
                return (
                  <label key={d.val} className="filtre-option">
                    <input type="radio" name="duree" checked={filtreduree === d.val} onChange={function() { setFiltreduree(d.val); }} />
                    {d.label}
                  </label>
                );
              })}
            </div>

            <div className="filtre-groupe">
              <p className="filtre-titre">Prix</p>
              {[{val: '0-30', label: '0 - 30€'}, {val: '30-60', label: '30 - 60€'}, {val: '60+', label: '60€+'}].map(function(p) {
                return (
                  <label key={p.val} className="filtre-option">
                    <input type="radio" name="prix" checked={filtrePrix === p.val} onChange={function() { setFiltrePrix(p.val); }} />
                    {p.label}
                  </label>
                );
              })}
            </div>
          </aside>

          <main className="services-contenu">
            <input
              className="barre-recherche"
              type="text"
              placeholder="Rechercher un service..."
              value={recherche}
              onChange={function(e) { setRecherche(e.target.value); }}
            />

            <div className="onglets-categories">
              {categories.map(function(cat) {
                return (
                  <button key={cat} className={categorieActive === cat ? 'onglet actif' : 'onglet'} onClick={function() { setCategorieActive(cat); }}>
                    {cat}
                  </button>
                );
              })}
            </div>

            <div style={{display:'flex', justifyContent:'space-between', alignItems:'center', marginBottom:'16px'}}>
              <p className="compteur">{servicesFiltres.length} service(s) disponible(s)</p>

            <div className="tri-selecteur">
              <label>Trier par : </label>
              <select value={tri} onChange={function(e) { setTri(e.target.value); }} className="select-tri">
                <option value="recent">Plus récent</option>
                <option value="prix_asc">Prix croissant</option>
                <option value="prix_desc">Prix décroissant</option>
                <option value="duree_asc">Durée croissante</option>
              </select>
            </div>
              <select
                value={tri}
                onChange={function(e) { setTri(e.target.value); }}
                style={{padding:'6px 12px', borderRadius:'50px', border:'1.5px solid #dde8e0', fontSize:'13px', fontFamily:'DM Sans, sans-serif', cursor:'pointer'}}
              >
                {optionsTri.map(function(o) {
                  return <option key={o.val} value={o.val}>{o.label}</option>;
                })}
              </select>
            </div>

            <div className="grille-catalogue">
              {chargement ? (
                <p>Chargement...</p>
              ) : servicesFiltres.length === 0 ? (
                <p style={{color: '#999'}}>Aucun service trouvé.</p>
              ) : (
                servicesFiltres.map(function(s) {
                  return <CarteService key={s.id} service={s} />;
                })
              )}
            </div>
          </main>

        </div>
      );
    }

    const root = ReactDOM.createRoot(document.getElementById('root'));
    root.render(<Services />);
  </script>

</body>
</html>
