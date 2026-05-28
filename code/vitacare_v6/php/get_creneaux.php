<?php
// ============================================
// API : Récupérer les créneaux d'un service
// ============================================
require_once 'connexion.php';

$id_service = isset($_GET['id_service']) ? (int)$_GET['id_service'] : 0;

if ($id_service === 0) {
  echo json_encode(array());
  exit();
}

$sql = "SELECT id, date_creneau, heure, places_total, places_restantes
        FROM creneaux
        WHERE id_service = '$id_service'
        AND actif = 1
        AND date_creneau >= CURDATE()
        ORDER BY date_creneau ASC, heure ASC";

$result = mysqli_query($conn, $sql);

$creneaux = array();
while ($row = mysqli_fetch_assoc($result)) {
  $creneaux[] = array(
    'id'               => (int)$row['id'],
    'date_creneau'     => $row['date_creneau'],
    'heure'            => $row['heure'],
    'places_total'     => (int)$row['places_total'],
    'places_restantes' => (int)$row['places_restantes'],
  );
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
echo json_encode($creneaux);

mysqli_close($conn);
?>
