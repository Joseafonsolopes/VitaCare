<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <title>Test connexion BDD - VitaCare</title>
    <style>
        body { font-family: Arial, sans-serif; padding: 40px; background: #f0f5f1; }
        .ok  { color: green; font-weight: bold; }
        .err { color: red;   font-weight: bold; }
        table { border-collapse: collapse; margin-top: 20px; background: white; }
        td, th { border: 1px solid #ccc; padding: 8px 14px; }
        th { background: #3d6b4f; color: white; }
        h2 { color: #3d6b4f; margin-top: 30px; }
    </style>
</head>
<body>

<h1>🧪 Test de la base de données VitaCare</h1>

<?php
require_once 'connexion.php';

echo '<p class="ok">✅ Connexion à la base de données réussie !</p>';

// Test 1 — Afficher les utilisateurs
echo '<h2>👥 Utilisateurs</h2>';
$result = mysqli_query($conn, 'SELECT id, prenom, nom, email, role FROM utilisateurs');

if (mysqli_num_rows($result) > 0) {
    echo '<table>';
    echo '<tr><th>ID</th><th>Prénom</th><th>Nom</th><th>Email</th><th>Rôle</th></tr>';
    while ($row = mysqli_fetch_assoc($result)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id'])     . '</td>';
        echo '<td>' . htmlspecialchars($row['prenom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['nom'])    . '</td>';
        echo '<td>' . htmlspecialchars($row['email'])  . '</td>';
        echo '<td>' . htmlspecialchars($row['role'])   . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p class="err">❌ Aucun utilisateur trouvé.</p>';
}

// Test 2 — Afficher les services
echo '<h2>🏃 Services</h2>';
$result2 = mysqli_query($conn, 'SELECT s.id, s.nom, s.prix, s.duree, u.prenom, u.nom AS nom_intervenant FROM services s, utilisateurs u WHERE s.id_intervenant = u.id');

if (mysqli_num_rows($result2) > 0) {
    echo '<table>';
    echo '<tr><th>ID</th><th>Service</th><th>Prix</th><th>Durée</th><th>Intervenant</th></tr>';
    while ($row = mysqli_fetch_assoc($result2)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id'])               . '</td>';
        echo '<td>' . htmlspecialchars($row['nom'])              . '</td>';
        echo '<td>' . htmlspecialchars($row['prix'])             . '€</td>';
        echo '<td>' . htmlspecialchars($row['duree'])            . ' min</td>';
        echo '<td>' . htmlspecialchars($row['prenom']) . ' ' . htmlspecialchars($row['nom_intervenant']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p class="err">❌ Aucun service trouvé.</p>';
}

// Test 3 — Afficher les réservations
echo '<h2>📅 Réservations</h2>';
$result3 = mysqli_query($conn, 'SELECT r.id, u.prenom, u.nom, s.nom AS service, r.statut, r.date_reservation FROM reservations r, utilisateurs u, creneaux c, services s WHERE r.id_utilisateur = u.id AND r.id_creneau = c.id AND c.id_service = s.id');

if (mysqli_num_rows($result3) > 0) {
    echo '<table>';
    echo '<tr><th>ID</th><th>Utilisateur</th><th>Service</th><th>Statut</th><th>Date réservation</th></tr>';
    while ($row = mysqli_fetch_assoc($result3)) {
        echo '<tr>';
        echo '<td>' . htmlspecialchars($row['id'])               . '</td>';
        echo '<td>' . htmlspecialchars($row['prenom']) . ' ' . htmlspecialchars($row['nom']) . '</td>';
        echo '<td>' . htmlspecialchars($row['service'])          . '</td>';
        echo '<td>' . htmlspecialchars($row['statut'])           . '</td>';
        echo '<td>' . htmlspecialchars($row['date_reservation']) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
} else {
    echo '<p class="err">❌ Aucune réservation trouvée.</p>';
}

mysqli_close($conn);
?>

</body>
</html>
