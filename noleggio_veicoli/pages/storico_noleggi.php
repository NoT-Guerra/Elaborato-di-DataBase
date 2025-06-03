<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$utente_id = $_SESSION['user_id'];

// Recupera i noleggi dell'utente con info sul veicolo
$stmt = $pdo->prepare("
    SELECT n.*, v.marca, v.modello, v.targa
    FROM noleggio n
    JOIN veicolo v ON n.targa = v.targa
    WHERE n.id_utente = ?
    ORDER BY n.data_inizio DESC
");
$stmt->execute([$utente_id]);
$noleggi = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Storico Noleggi</title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h2 {
            color: #333;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 12px;
            border: 1px solid #ccc;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .back-link {
            display: inline-block;
            margin-top: 20px;
            background-color: #1e90ff;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 6px;
        }

        .back-link:hover {
            background-color: #005bb5;
        }
    </style>
</head>
<body>
    <h2>Storico Noleggi</h2>

    <?php if (count($noleggi) > 0): ?>
        <table>
            <thead>
                <tr>
                    <th>Data Inizio</th>
                    <th>Data Fine</th>
                    <th>Veicolo</th>
                    <th>Targa</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($noleggi as $n): ?>
                    <tr>
                        <td><?= htmlspecialchars($n['data_inizio']) ?></td>
                        <td><?= htmlspecialchars($n['data_fine']) ?></td>
                        <td><?= htmlspecialchars($n['marca'] . ' ' . $n['modello']) ?></td>
                        <td><?= htmlspecialchars($n['targa']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Non hai ancora effettuato alcun noleggio.</p>
    <?php endif; ?>

    <a class="back-link" href="profile.php">&larr; Torna al Profilo</a>
</body>
</html>
