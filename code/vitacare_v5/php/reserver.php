<?php
// ============================================
// API : Créer une réservation
// ============================================
session_start();
require_once 'connexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

// Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['id'])) {
  echo json_encode(array('succes' => false, 'message' => 'Vous devez être connecté pour réserver.'));
  exit();
}

if (!isset($_POST['id_creneau'])) {
  echo json_encode(array('succes' => false, 'message' => 'Créneau invalide.'));
  exit();
}

$id_utilisateur = (int)$_SESSION['id'];
$id_creneau     = (int)$_POST['id_creneau'];
$date_today     = date('Y-m-d');

// Vérifier que le créneau existe et a des places
$sql_check = "SELECT id, places_restantes FROM creneaux WHERE id = '$id_creneau' AND actif = 1";
$result_check = mysqli_query($conn, $sql_check);

if (mysqli_num_rows($result_check) === 0) {
  echo json_encode(array('succes' => false, 'message' => 'Ce créneau n\'existe pas.'));
  exit();
}

$creneau = mysqli_fetch_assoc($result_check);

if ($creneau['places_restantes'] <= 0) {
  echo json_encode(array('succes' => false, 'message' => 'Ce créneau est complet.'));
  exit();
}

// Vérifier que l'utilisateur n'a pas déjà réservé ce créneau
$sql_doublon = "SELECT id FROM reservations WHERE id_utilisateur = '$id_utilisateur' AND id_creneau = '$id_creneau'";
$result_doublon = mysqli_query($conn, $sql_doublon);

if (mysqli_num_rows($result_doublon) > 0) {
  echo json_encode(array('succes' => false, 'message' => 'Vous avez déjà réservé ce créneau.'));
  exit();
}

// Créer la réservation
$sql_resa = "INSERT INTO reservations (id_utilisateur, id_creneau, statut, date_reservation)
             VALUES ('$id_utilisateur', '$id_creneau', 'confirmee', '$date_today')";

if (mysqli_query($conn, $sql_resa)) {
  // Décrémenter les places restantes
  $sql_places = "UPDATE creneaux SET places_restantes = places_restantes - 1 WHERE id = '$id_creneau'";
  mysqli_query($conn, $sql_places);

  // Créer une notification
  $sql_notif = "INSERT INTO notifications (id_utilisateur, message, lu, date_notification)
                VALUES ('$id_utilisateur', 'Votre réservation a été confirmée.', 0, NOW())";
  mysqli_query($conn, $sql_notif);

  echo json_encode(array('succes' => true, 'message' => 'Réservation confirmée !'));
} else {
  echo json_encode(array('succes' => false, 'message' => 'Erreur lors de la réservation.'));
}

mysqli_close($conn);
?>
