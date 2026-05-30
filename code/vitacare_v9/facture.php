<?php
session_start();
require_once 'php/connexion.php';

if (!isset($_SESSION['id'])) {
  header('Location: connexion.php');
  exit();
}

$id_utilisateur = $_SESSION['id'];
$id_facture     = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$sql = "SELECT f.*, u.prenom, u.nom, u.email, u.adresse
        FROM factures f, utilisateurs u
        WHERE f.id = '$id_facture'
        AND f.id_utilisateur = '$id_utilisateur'
        AND u.id = '$id_utilisateur'";
$result = mysqli_query($conn, $sql);

if (mysqli_num_rows($result) === 0) {
  echo '<p style="text-align:center; padding:40px; color:red;">Facture introuvable.</p>';
  exit();
}

$f = mysqli_fetch_assoc($result);

$sql_resa = "SELECT nb_places FROM reservations WHERE id = '" . $f['id_reservation'] . "'";
$resa = mysqli_fetch_assoc(mysqli_query($conn, $sql_resa));
$nb_places = $resa ? $resa['nb_places'] : 1;
$prix_unitaire = $nb_places > 0 ? number_format($f['montant'] / $nb_places, 2) : $f['montant'];

$date_facture = date('d/m/Y', strtotime($f['date_facture']));
$date_creneau = date('d/m/Y', strtotime($f['date_creneau']));
$heure = substr($f['heure_creneau'], 0, 5);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>Facture VitaCare</title>
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'DM Sans', sans-serif;
      background: #f5f5f5;
      padding: 40px;
      color: #1a1a1a;
    }

    .facture-wrapper {
      max-width: 700px;
      margin: 0 auto;
      background: white;
      border-radius: 12px;
      padding: 50px;
      box-shadow: 0 4px 20px rgba(0,0,0,0.1);
    }

    /* HEADER */
    .facture-header {
      display: flex;
      justify-content: space-between;
      align-items: flex-start;
      margin-bottom: 40px;
      padding-bottom: 24px;
      border-bottom: 2px solid #e8f0ea;
    }

    .facture-logo {
      font-size: 26px;
      font-weight: 700;
      color: #2d5a3d;
      letter-spacing: -0.5px;
    }

    .facture-logo span {
      color: #5a8a6a;
      font-size: 13px;
      display: block;
      font-weight: 400;
      margin-top: 2px;
    }

    .facture-numero-bloc {
      text-align: right;
    }

    .facture-numero-bloc h2 {
      font-size: 22px;
      font-weight: 700;
      color: #2d5a3d;
      margin-bottom: 6px;
    }

    .facture-numero-bloc p {
      font-size: 13px;
      color: #777;
    }

    /* INFOS */
    .facture-infos {
      display: flex;
      justify-content: space-between;
      margin-bottom: 36px;
    }

    .facture-bloc h4 {
      font-size: 11px;
      text-transform: uppercase;
      letter-spacing: 1px;
      color: #999;
      margin-bottom: 8px;
    }

    .facture-bloc p {
      font-size: 14px;
      line-height: 1.7;
      color: #333;
    }

    .facture-bloc strong { color: #1a1a1a; }

    /* TABLEAU */
    .facture-tableau {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 30px;
    }

    .facture-tableau thead tr {
      background: #2d5a3d;
      color: white;
    }

    .facture-tableau th {
      padding: 12px 16px;
      font-size: 12px;
      font-weight: 600;
      text-align: left;
      text-transform: uppercase;
      letter-spacing: 0.5px;
    }

    .facture-tableau td {
      padding: 14px 16px;
      font-size: 14px;
      border-bottom: 1px solid #f0f0f0;
    }

    .facture-tableau tbody tr:last-child td { border-bottom: none; }
    .facture-tableau tbody tr:hover { background: #f9fbf9; }

    .td-right { text-align: right; font-weight: 600; }

    /* TOTAL */
    .facture-total {
      display: flex;
      justify-content: flex-end;
      margin-bottom: 36px;
    }

    .total-bloc {
      min-width: 240px;
    }

    .total-ligne {
      display: flex;
      justify-content: space-between;
      padding: 8px 0;
      font-size: 14px;
      color: #555;
      border-bottom: 1px solid #f0f0f0;
    }

    .total-ligne.total-final {
      border-top: 2px solid #2d5a3d;
      border-bottom: none;
      padding-top: 12px;
      margin-top: 4px;
      font-size: 18px;
      font-weight: 700;
      color: #2d5a3d;
    }

    /* PIED */
    .facture-pied {
      background: #f5faf6;
      border-radius: 8px;
      padding: 16px 20px;
      font-size: 12px;
      color: #777;
      text-align: center;
      line-height: 1.6;
    }

    .statut-paye {
      display: inline-block;
      background: #e8f5e9;
      color: #2d5a3d;
      font-size: 12px;
      font-weight: 700;
      padding: 4px 14px;
      border-radius: 50px;
      border: 1.5px solid #c8e6c9;
      margin-top: 8px;
    }

    /* BOUTON IMPRESSION */
    .btn-imprimer {
      display: block;
      width: 200px;
      margin: 24px auto 0;
      background: #2d5a3d;
      color: white;
      border: none;
      padding: 12px 24px;
      border-radius: 50px;
      font-size: 15px;
      font-weight: 600;
      cursor: pointer;
      font-family: 'DM Sans', sans-serif;
      text-align: center;
    }

    .btn-imprimer:hover { background: #3d7a53; }

    /* IMPRESSION */
    @media print {
      body { background: white; padding: 0; }
      .facture-wrapper { box-shadow: none; border-radius: 0; padding: 30px; }
      .btn-imprimer { display: none; }
      .btn-retour { display: none; }
    }

    .btn-retour {
      display: block;
      text-align: center;
      margin-top: 12px;
      font-size: 13px;
      color: #777;
      cursor: pointer;
      text-decoration: underline;
      background: none;
      border: none;
      font-family: 'DM Sans', sans-serif;
    }
  </style>
</head>
<body>

<div class="facture-wrapper">

  <div class="facture-header">
    <div class="facture-logo">
      VitaCare
      <span>La plateforme de santé et bien-être</span>
    </div>
    <div class="facture-numero-bloc">
      <h2>FACTURE</h2>
      <p><?php echo htmlspecialchars($f['numero_facture']); ?></p>
      <p>Émise le <?php echo $date_facture; ?></p>
      <div class="statut-paye">✓ Payée</div>
    </div>
  </div>

  <div class="facture-infos">
    <div class="facture-bloc">
      <h4>Émetteur</h4>
      <p>
        <strong>VitaCare</strong><br>
        Plateforme de santé et bien-être<br>
        contact@vitacare.fr
      </p>
    </div>
    <div class="facture-bloc">
      <h4>Client</h4>
      <p>
        <strong><?php echo htmlspecialchars($f['prenom'] . ' ' . $f['nom']); ?></strong><br>
        <?php echo htmlspecialchars($f['email']); ?><br>
        <?php if ($f['adresse']) echo htmlspecialchars($f['adresse']); ?>
      </p>
    </div>
  </div>

  <table class="facture-tableau">
    <thead>
      <tr>
        <th>Description</th>
        <th>Intervenant</th>
        <th>Date</th>
        <th>Qté</th>
        <th>Prix unit.</th>
        <th>Total</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td><?php echo htmlspecialchars($f['nom_service']); ?></td>
        <td><?php echo htmlspecialchars($f['nom_intervenant']); ?></td>
        <td><?php echo $date_creneau; ?> à <?php echo $heure; ?></td>
        <td><?php echo $nb_places; ?></td>
        <td class="td-right"><?php echo $prix_unitaire; ?>€</td>
        <td class="td-right"><?php echo number_format($f['montant'], 2); ?>€</td>
      </tr>
    </tbody>
  </table>

  <div class="facture-total">
    <div class="total-bloc">
      <div class="total-ligne">
        <span>Sous-total</span>
        <span><?php echo number_format($f['montant'], 2); ?>€</span>
      </div>
      <div class="total-ligne">
        <span>TVA (0%)</span>
        <span>0.00€</span>
      </div>
      <div class="total-ligne total-final">
        <span>Total TTC</span>
        <span><?php echo number_format($f['montant'], 2); ?>€</span>
      </div>
    </div>
  </div>

  <div class="facture-pied">
    Paiement simulé dans le cadre du projet pédagogique VitaCare — ECE Paris 2026<br>
    Aucun paiement réel n'a été effectué. Ce document est fourni à titre indicatif.
  </div>

  <button class="btn-imprimer" onclick="window.print()">⬇️ Télécharger / Imprimer</button>
  <button class="btn-retour" onclick="window.history.back()">← Retour au profil</button>

</div>

<script>
  // Ouvrir l'impression automatiquement
  window.onload = function() {
    // Petit délai pour laisser les fonts charger
    setTimeout(function() {
      window.print();
    }, 800);
  };
</script>

</body>
</html>
