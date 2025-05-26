<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

// Verifica se l'utente è loggato
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Calcola la media dei voti
$mediaStmt = $pdo->query("SELECT AVG(voto) AS media_voti, COUNT(*) AS totale FROM recensioni");
$mediaData = $mediaStmt->fetch(PDO::FETCH_ASSOC);

// Recupera tutte le recensioni (puoi limitare o filtrare se necessario)
$stmt = $pdo->query("SELECT voto, commento, data_recensione FROM recensioni ORDER BY data_recensione DESC");
$recensioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Recensioni</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        .recensione-card { border: 1px solid #ccc; padding: 10px; margin: 10px 0; background: #fff; border-radius: 5px; }
        .stars { color: gold; font-size: 1.2em; }
        .media { font-size: 1.5em; margin: 20px 0; }
    </style>
</head>
<body>
    <h1>Recensioni degli utenti</h1>

    <div class="media">
        ⭐ Media: <?= number_format($mediaData['media_voti'], 1) ?> / 5 (<?= $mediaData['totale'] ?> recensioni)
    </div>

    <?php if (count($recensioni) > 0): ?>
        <?php foreach ($recensioni as $r): ?>
            <div class="recensione-card">
                <div class="stars"><?= str_repeat('★', (int)$r['voto']) . str_repeat('☆', 5 - (int)$r['voto']) ?></div>
                <p><strong>Data:</strong> <?= htmlspecialchars($r['data_recensione']) ?></p>
                <p><?= nl2br(htmlspecialchars($r['commento'])) ?></p>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nessuna recensione disponibile.</p>
    <?php endif; ?>
    <a href="../index.php">Torna alla Home</a>
</body>
</html>
