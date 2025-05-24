<?php
require_once './includes/db.php';
require_once './includes/functions.php';
session_start();

$user_logged_in = isset($_SESSION['user_id']);

// Recupera 5 inserzioni recenti con i dati del veicolo
$stmt = $pdo->query("
    SELECT 
        i.id_inserzione, 
        i.descrizione, 
        v.marca, 
        v.modello, 
        v.anno_immatricolazione AS anno, 
        v.tipologia_carburante AS carburante
    FROM inserzioni i
    JOIN veicolo_inserzione vi ON i.id_inserzione = vi.id_inserzione
    JOIN veicoli v ON vi.id_veicolo = v.id_veicolo
    ORDER BY i.id_inserzione DESC
    LIMIT 5
");
$inserzioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Home - Noleggio Veicoli</title>
    <link rel="stylesheet" href="assets/styles.css">
    <style>
        .inserzione-card { border: 1px solid #ccc; padding: 15px; margin: 15px 0; background: #f9f9f9; }
        .nav { margin-bottom: 20px; }
        .nav a { margin-right: 15px; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Benvenuto su Noleggio Veicoli</h1>

    <div class="nav">
    <?php if ($user_logged_in): ?>
        <a href="pages/create_inserzione.php">+ Crea Inserzione</a>
        <a href="pages/view_vehicles.php">Visualizza Veicoli</a>
        <a href="pages/stats.php">Statistiche</a>
        <a href="pages/recensioni.php">Recensioni</a> <!-- 👈 Aggiunto qui -->
        <a href="pages/logout.php">Logout</a>
    <?php else: ?>
        <a href="pages/login.php">Login</a>
        <a href="pages/register.php">Registrati</a>
    <?php endif; ?>
</div>

    <h2>Ultime Inserzioni</h2>
    <?php if (count($inserzioni) > 0): ?>
        <?php foreach ($inserzioni as $ins): ?>
            <div class="inserzione-card">
                <h3><?= htmlspecialchars($ins['descrizione']) ?></h3>
                <p><?= htmlspecialchars($ins['marca']) ?> <?= htmlspecialchars($ins['modello']) ?> (<?= (int)$ins['anno'] ?>)</p>
                <p>Carburante: <?= htmlspecialchars($ins['carburante']) ?></p>
                <a href="pages/rent_vehicle.php?inserzione_id=<?= $ins['id_inserzione'] ?>">Noleggia ora</a>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <p>Nessuna inserzione disponibile al momento.</p>
    <?php endif; ?>
</body>
</html>
