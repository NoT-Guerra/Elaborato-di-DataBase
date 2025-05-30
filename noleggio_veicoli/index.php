<?php
require_once './includes/db.php';
require_once './includes/functions.php';
session_start();

$user_logged_in = isset($_SESSION['user_id']);

if ($user_logged_in) {
    $email = $_SESSION['user_email'];
    $initials = strtoupper(substr($_SESSION['user_nome'], 0, 1) . substr($email, 0, 1));
}

$stmt = $pdo->query("
    SELECT 
        i.id_inserzione, 
        i.descrizione, 
        v.marca, 
        v.modello, 
        v.anno_immatricolazione AS anno, 
        v.tipologia_carburante AS carburante
    FROM inserzione i
    JOIN riguarda r ON i.id_inserzione = r.id_inserzione
    JOIN veicolo v ON r.targa = v.targa
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
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .header {
            background-color: #1e1e1e;
            padding: 10px 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            color: white;
        }

        .user-info {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .email {
            font-size: 0.9em;
            margin-bottom: 5px;
        }

        .user-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #9c27b0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .user-icon a {
            color: white;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 35px;
            height: 35px;
            border-radius: 50%;
        }

        .logout-button {
            background-color: #f44336;
            color: white;
            border: none;
            padding: 5px 10px;
            font-size: 0.8em;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
        }

        .logout-button:hover {
            background-color: #c62828;
        }

        .nav {
            margin: 20px;
        }

        .nav a {
            margin-right: 15px;
            text-decoration: none;
            padding: 6px 12px;
            background-color: #1e90ff;
            color: white;
            border-radius: 4px;
            font-weight: bold;
        }

        .nav a:hover {
            background-color: #005bb5;
        }

        .inserzione-card {
            border: 1px solid #ccc;
            padding: 15px;
            margin: 15px 0;
            background: #f9f9f9;
        }
    </style>
</head>
<body>

<?php if ($user_logged_in): ?>
    <div class="header">
        <div class="user-info">
            <div class="email"><?= htmlspecialchars($email) ?></div>
            <div class="user-icon">
                <a href="pages/profile.php"><?= $initials ?></a>
            </div>
            <a href="pages/logout.php" class="logout-button">Logout</a>
        </div>
    </div>
<?php endif; ?>

<h1 style="padding: 20px;">Benvenuto su Noleggio Veicoli</h1>

<div class="nav">
    <?php if ($user_logged_in): ?>
        <a href="pages/create_inserzione.php">+ Crea Inserzione</a>
        <a href="pages/view_vehicles.php">Visualizza Veicoli</a>
        <a href="pages/stats.php">Statistiche</a>
        <a href="pages/recensioni.php">Recensioni</a>
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
