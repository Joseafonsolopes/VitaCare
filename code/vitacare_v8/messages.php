<?php
session_start();
require_once 'php/connexion.php';

if (!isset($_SESSION['id'])) {
  header('Location: connexion.php');
  exit();
}

$id_moi = $_SESSION['id'];

// Envoyer un message
if (isset($_POST['btn_envoyer'])) {
  $id_destinataire = (int)$_POST['id_destinataire'];
  $contenu = trim($_POST['contenu']);

  if (!empty($contenu) && $id_destinataire > 0) {
    $date_now = date('Y-m-d H:i:s');

    // Insérer le message
    $contenu_safe = mysqli_real_escape_string($conn, $contenu);
    $sql_msg = "INSERT INTO messages (id_expediteur, id_destinataire, message, lu, date_message)
                VALUES ('$id_moi', '$id_destinataire', '$contenu_safe', 0, '$date_now')";
    mysqli_query($conn, $sql_msg);

    // Notification pour le destinataire
    $nom_expediteur = htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom'] ?? '');
    $notif_msg = mysqli_real_escape_string($conn, "Nouveau message de " . $_SESSION['prenom'] . " : \"" . substr($contenu, 0, 60) . (strlen($contenu) > 60 ? '...' : '') . "\"");
    mysqli_query($conn, "INSERT INTO notifications (id_utilisateur, message, lu, date_notification)
                         VALUES ('$id_destinataire', '$notif_msg', 0, '$date_now')");

    header('Location: messages.php?avec=' . $id_destinataire);
    exit();
  }
}

// Marquer les messages comme lus
$id_avec = isset($_GET['avec']) ? (int)$_GET['avec'] : 0;
if ($id_avec > 0) {
  mysqli_query($conn, "UPDATE messages SET lu = 1 WHERE id_destinataire = '$id_moi' AND id_expediteur = '$id_avec'");
}

// Liste des conversations (toutes les personnes avec qui j'ai échangé)
$sql_convs = "SELECT DISTINCT
                CASE WHEN m.id_expediteur = '$id_moi' THEN m.id_destinataire ELSE m.id_expediteur END AS id_autre,
                u.prenom, u.nom, u.role,
                (SELECT COUNT(*) FROM messages WHERE id_destinataire = '$id_moi' AND id_expediteur = CASE WHEN m.id_expediteur = '$id_moi' THEN m.id_destinataire ELSE m.id_expediteur END AND lu = 0) as nb_non_lus,
                (SELECT message FROM messages WHERE (id_expediteur = '$id_moi' AND id_destinataire = CASE WHEN m.id_expediteur = '$id_moi' THEN m.id_destinataire ELSE m.id_expediteur END) OR (id_destinataire = '$id_moi' AND id_expediteur = CASE WHEN m.id_expediteur = '$id_moi' THEN m.id_destinataire ELSE m.id_expediteur END) ORDER BY date_message DESC LIMIT 1) as dernier_message,
                (SELECT date_message FROM messages WHERE (id_expediteur = '$id_moi' AND id_destinataire = CASE WHEN m.id_expediteur = '$id_moi' THEN m.id_destinataire ELSE m.id_expediteur END) OR (id_destinataire = '$id_moi' AND id_expediteur = CASE WHEN m.id_expediteur = '$id_moi' THEN m.id_destinataire ELSE m.id_expediteur END) ORDER BY date_message DESC LIMIT 1) as date_dernier
              FROM messages m, utilisateurs u
              WHERE (m.id_expediteur = '$id_moi' OR m.id_destinataire = '$id_moi')
              AND u.id = CASE WHEN m.id_expediteur = '$id_moi' THEN m.id_destinataire ELSE m.id_expediteur END
              ORDER BY date_dernier DESC";
$result_convs = mysqli_query($conn, $sql_convs);

// Messages de la conversation active
$messages_conv = array();
$interlocuteur = null;
if ($id_avec > 0) {
  $sql_interl = "SELECT id, prenom, nom, role FROM utilisateurs WHERE id = '$id_avec'";
  $interlocuteur = mysqli_fetch_assoc(mysqli_query($conn, $sql_interl));

  $sql_msgs = "SELECT m.*, u.prenom, u.nom FROM messages m, utilisateurs u
               WHERE u.id = m.id_expediteur
               AND ((m.id_expediteur = '$id_moi' AND m.id_destinataire = '$id_avec')
               OR (m.id_expediteur = '$id_avec' AND m.id_destinataire = '$id_moi'))
               ORDER BY m.date_message ASC";
  $result_msgs = mysqli_query($conn, $sql_msgs);
  while ($msg = mysqli_fetch_assoc($result_msgs)) {
    $messages_conv[] = $msg;
  }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <title>VitaCare - Messages</title>
  <link rel="stylesheet" href="css/global.css">
  <link rel="stylesheet" href="css/messages.css">
  <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet">
</head>
<body>

<?php require_once 'php/navbar_include.php'; ?>

<div class="messages-page">

  <!-- SIDEBAR CONVERSATIONS -->
  <div class="messages-sidebar">
    <h2>Messages</h2>
    <?php if (mysqli_num_rows($result_convs) === 0): ?>
      <p class="conv-vide">Aucune conversation.</p>
    <?php else: ?>
      <?php while ($conv = mysqli_fetch_assoc($result_convs)):
        $actif = ($conv['id_autre'] == $id_avec) ? 'actif' : '';
        $initiales = strtoupper(substr($conv['prenom'], 0, 1) . substr($conv['nom'], 0, 1));
        $role_label = $conv['role'] === 'intervenant' ? '🧑‍⚕️' : '👤';
      ?>
      <a href="messages.php?avec=<?php echo $conv['id_autre']; ?>" class="conv-item <?php echo $actif; ?>">
        <div class="conv-avatar"><?php echo $initiales; ?></div>
        <div class="conv-info">
          <p class="conv-nom"><?php echo $role_label . ' ' . htmlspecialchars($conv['prenom'] . ' ' . $conv['nom']); ?></p>
          <p class="conv-dernier"><?php echo htmlspecialchars(substr($conv['dernier_message'], 0, 40)) . (strlen($conv['dernier_message']) > 40 ? '...' : ''); ?></p>
        </div>
        <?php if ($conv['nb_non_lus'] > 0): ?>
          <span class="conv-badge"><?php echo $conv['nb_non_lus']; ?></span>
        <?php endif; ?>
      </a>
      <?php endwhile; ?>
    <?php endif; ?>
  </div>

  <!-- ZONE CONVERSATION -->
  <div class="messages-conv">
    <?php if ($id_avec > 0 && $interlocuteur): ?>

      <div class="conv-header">
        <div class="conv-avatar" style="width:40px; height:40px; font-size:14px;">
          <?php echo strtoupper(substr($interlocuteur['prenom'], 0, 1) . substr($interlocuteur['nom'], 0, 1)); ?>
        </div>
        <div>
          <p class="conv-nom" style="font-size:16px; font-weight:700;"><?php echo htmlspecialchars($interlocuteur['prenom'] . ' ' . $interlocuteur['nom']); ?></p>
          <p style="font-size:12px; color:var(--texte-gris);"><?php echo ucfirst($interlocuteur['role']); ?></p>
        </div>
      </div>

      <div class="conv-messages" id="conv-messages">
        <?php if (empty($messages_conv)): ?>
          <p class="conv-vide" style="text-align:center; margin-top:40px;">Commencez la conversation !</p>
        <?php else: ?>
          <?php foreach ($messages_conv as $msg):
            $est_moi = ($msg['id_expediteur'] == $id_moi);
            $classe = $est_moi ? 'msg-moi' : 'msg-autre';
            $date = new DateTime($msg['date_message']);
            $maintenant = new DateTime();
            $diff = $maintenant->diff($date);
            if ($diff->days == 0 && $diff->h == 0) $temps = 'Il y a ' . $diff->i . ' min';
            elseif ($diff->days == 0) $temps = 'Il y a ' . $diff->h . 'h';
            else $temps = $date->format('d/m/Y à H:i');
          ?>
          <div class="msg-bloc <?php echo $classe; ?>">
            <div class="msg-bulle">
              <p class="msg-texte"><?php echo nl2br(htmlspecialchars($msg['message'])); ?></p>
              <p class="msg-temps"><?php echo $temps; ?></p>
            </div>
          </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <form method="POST" action="messages.php?avec=<?php echo $id_avec; ?>" class="conv-form">
        <input type="hidden" name="id_destinataire" value="<?php echo $id_avec; ?>">
        <textarea name="contenu" class="conv-input" placeholder="Écrivez votre message..." rows="2" required onkeydown="if(event.ctrlKey && event.key==='Enter') this.form.submit();"></textarea>
        <button type="submit" name="btn_envoyer" class="btn-envoyer">Envoyer ↗</button>
      </form>

    <?php else: ?>
      <div class="conv-vide-centre">
        <p style="font-size:40px; margin-bottom:16px;">💬</p>
        <p style="font-size:16px; color:var(--texte-gris);">Sélectionnez une conversation</p>
        <p style="font-size:13px; color:#bbb; margin-top:8px;">ou contactez un intervenant depuis votre profil</p>
      </div>
    <?php endif; ?>
  </div>

</div>

<script>
  // Scroll en bas automatiquement
  var conv = document.getElementById('conv-messages');
  if (conv) conv.scrollTop = conv.scrollHeight;
</script>

</body>
</html>
