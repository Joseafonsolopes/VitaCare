<?php
session_start();
require_once 'connexion.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

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
$nb_places      = isset($_POST['nb_places']) ? (int)$_POST['nb_places'] : 1;
if ($nb_places < 1) $nb_places = 1;
$date_today     = date('Y-m-d');

// Vérifier que le créneau existe et a assez de places
$sql_check = "SELECT c.id, c.places_restantes, c.date_creneau, c.heure,
                     s.nom AS nom_service, s.prix,
                     u.prenom, u.nom AS nom_intervenant, u.telephone
              FROM creneaux c, services s, utilisateurs u
              WHERE c.id = '$id_creneau'
              AND c.id_service = s.id
              AND s.id_intervenant = u.id
              AND c.actif = 1";
$result_check = mysqli_query($conn, $sql_check);

if (mysqli_num_rows($result_check) === 0) {
  echo json_encode(array('succes' => false, 'message' => 'Ce créneau n\'existe pas.'));
  exit();
}

$creneau = mysqli_fetch_assoc($result_check);

if ($creneau['places_restantes'] < $nb_places) {
  echo json_encode(array('succes' => false, 'message' => 'Pas assez de places disponibles (il reste ' . $creneau['places_restantes'] . ' place(s)).'));
  exit();
}

// Vérifier doublon (hors annulées)
$sql_doublon = "SELECT id FROM reservations WHERE id_utilisateur = '$id_utilisateur' AND id_creneau = '$id_creneau' AND statut != 'annulee'";
$result_doublon = mysqli_query($conn, $sql_doublon);

if (mysqli_num_rows($result_doublon) > 0) {
  echo json_encode(array('succes' => false, 'message' => 'Vous avez déjà réservé ce créneau.'));
  exit();
}

// Créer la réservation
$montant_total = $creneau['prix'] * $nb_places;
$sql_resa = "INSERT INTO reservations (id_utilisateur, id_creneau, statut, date_reservation, nb_places)
             VALUES ('$id_utilisateur', '$id_creneau', 'confirmee', '$date_today', '$nb_places')";

if (mysqli_query($conn, $sql_resa)) {
  $id_reservation = mysqli_insert_id($conn);

  // Décrémenter les places
  mysqli_query($conn, "UPDATE creneaux SET places_restantes = places_restantes - $nb_places WHERE id = '$id_creneau'");

  // Créer la facture
  $numero_facture = 'FAC-' . date('Ymd') . '-' . str_pad($id_reservation, 5, '0', STR_PAD_LEFT);
  $nom_service    = mysqli_real_escape_string($conn, $creneau['nom_service']);
  $nom_interv     = mysqli_real_escape_string($conn, $creneau['prenom'] . ' ' . $creneau['nom_intervenant']);
  $date_creneau   = $creneau['date_creneau'];
  $heure_creneau  = $creneau['heure'];

  $sql_facture = "INSERT INTO factures (id_utilisateur, id_reservation, numero_facture, montant, date_facture, nom_service, nom_intervenant, date_creneau, heure_creneau)
                  VALUES ('$id_utilisateur', '$id_reservation', '$numero_facture', '$montant_total', '$date_today', '$nom_service', '$nom_interv', '$date_creneau', '$heure_creneau')";
  mysqli_query($conn, $sql_facture);

  // Notification
  $tel_interv = $creneau['telephone'] ? $creneau['telephone'] : 'non renseigné';
  $message_notif = "Merci de participer à \"$nom_service\" le $date_creneau à $heure_creneau. Pour toute question, contactez $nom_interv au $tel_interv.";
  $message_notif = mysqli_real_escape_string($conn, $message_notif);
  mysqli_query($conn, "INSERT INTO notifications (id_utilisateur, message, lu, date_notification) VALUES ('$id_utilisateur', '$message_notif', 0, NOW())");

  echo json_encode(array(
    'succes'          => true,
    'message'         => 'Réservation confirmée !',
    'numero_facture'  => $numero_facture,
    'montant'         => $montant_total,
    'id_reservation'  => $id_reservation
  ));
} else {
  echo json_encode(array('succes' => false, 'message' => 'Erreur : ' . mysqli_error($conn)));
}

mysqli_close($conn);
?>
