<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$mediaStmt = $pdo->query("SELECT AVG(voto) AS media_voti, COUNT(*) AS totale FROM recensione");
$mediaData = $mediaStmt->fetch(PDO::FETCH_ASSOC);

$stmt = $pdo->query("SELECT voto, commento, data_recensione FROM recensione ORDER BY data_recensione DESC");
$recensioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Recensioni</title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
        body {
            background: #f5f5f5;
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #fefefe;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        h1 {
            text-align: center;
        }
        .media {
            font-size: 1.5em;
            margin: 20px 0;
            text-align: center;
        }
        .recensione-card {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 15px 0;
            background: #fff;
            border-radius: 5px;
        }
        .stars {
            color: gold;
            font-size: 1.2em;
        }
        .back-link {
            display: block;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <div class="container">
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
            <p style="text-align:center;">Nessuna recensione disponibile.</p>
        <?php endif; ?>

        <a href="../index.php" class="back-link">Torna alla Home</a>
    </div>
</body>
</html>
