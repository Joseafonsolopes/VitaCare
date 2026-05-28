<?php
// ============================================
// API : Récupérer tous les services actifs
// Renvoie les données en JSON pour React
// ============================================
require_once 'connexion.php';

$recherche = isset($_GET['recherche']) ? $_GET['recherche'] : '';
$categorie = isset($_GET['categorie']) ? $_GET['categorie'] : '';

$sql = "SELECT s.id, s.nom, s.description, s.emoji, s.couleur, s.categorie,
               s.duree, s.prix,
               u.prenom, u.nom AS nom_intervenant,
               COUNT(DISTINCT r.id) AS nb_reservations,
               MIN(c.places_restantes) AS min_places
        FROM services s
        LEFT JOIN utilisateurs u ON s.id_intervenant = u.id
        LEFT JOIN creneaux c ON c.id_service = s.id
        LEFT JOIN reservations r ON r.id_creneau = c.id
        WHERE s.actif = 1";

if (!empty($recherche)) {
  $sql .= " AND (s.nom LIKE '%$recherche%' OR u.prenom LIKE '%$recherche%' OR u.nom LIKE '%$recherche%')";
}

if (!empty($categorie) && $categorie !== 'Toutes') {
  $sql .= " AND s.categorie = '$categorie'";
}

$sql .= " GROUP BY s.id ORDER BY s.id DESC";

$result = mysqli_query($conn, $sql);

$services = array();

while ($row = mysqli_fetch_assoc($result)) {
  // Déterminer le badge
  $badge = null;
  if ($row['nb_reservations'] > 5) {
    $badge = 'Populaire';
  }

  $services[] = array(
    'id'           => (int)$row['id'],
    'nom'          => $row['nom'],
    'description'  => $row['description'],
    'emoji'        => $row['emoji'] ? $row['emoji'] : '🧘',
    'couleur'      => $row['couleur'] ? $row['couleur'] : '#e8f0eb',
    'categorie'    => $row['categorie'],
    'duree'        => (int)$row['duree'],
    'prix'         => (float)$row['prix'],
    'intervenant'  => $row['prenom'] . ' ' . $row['nom_intervenant'],
    'badge'        => $badge,
    'min_places'   => $row['min_places'] !== null ? (int)$row['min_places'] : null,
  );
}

// Renvoyer en JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($services);

mysqli_close($conn);
?>
