<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$user_id = $_SESSION['user_id'];

// Carica dati utente
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Carica indirizzi spedizione
$stmt2 = $pdo->prepare("SELECT * FROM indirizzi_spedizione WHERE user_id = ?");
$stmt2->execute([$user_id]);
$indirizzi = $stmt2->fetchAll();

?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Profilo Utente</title>
    <link rel="stylesheet" href="../assets/styles.css" />
</head>
<body>
<h1>Profilo di <?= htmlspecialchars($user['nome'] . ' ' . $user['cognome']) ?></h1>

<h2>Indirizzi di spedizione</h2>
<ul>
    <?php foreach ($indirizzi as $indirizzo): ?>
        <li><?= htmlspecialchars($indirizzo['indirizzo'] . ', ' . $indirizzo['citta'] . ', ' . $indirizzo['cap'] . ', ' . $indirizzo['provincia']) ?></li>
    <?php endforeach; ?>
</ul>

<a href="add_shipping_address.php">Aggiungi indirizzo di spedizione</a>

</body>
</html>
