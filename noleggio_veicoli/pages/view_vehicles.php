<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

// 1. Filtro e parametri
$where = [];
$params = [];

if (!empty($_GET['marca'])) {
    $where[] = "v.marca LIKE ?";
    $params[] = "%" . $_GET['marca'] . "%";
}
if (!empty($_GET['modello'])) {
    $where[] = "v.modello LIKE ?";
    $params[] = "%" . $_GET['modello'] . "%";
}
if (!empty($_GET['tipologia_carburante'])) {
    $where[] = "v.tipologia_carburante = ?";
    $params[] = $_GET['tipologia_carburante'];
}
if (!empty($_GET['prezzo_min'])) {
    $where[] = "i.prezzo_giornaliero >= ?";
    $params[] = (float)$_GET['prezzo_min'];
}
if (!empty($_GET['prezzo_max'])) {
    $where[] = "i.prezzo_giornaliero <= ?";
    $params[] = (float)$_GET['prezzo_max'];
}
if (!empty($_GET['neopatentati'])) {
    $where[] = "(v.potenza * 1000 / v.peso) <= 75";
}

// 2. Query SQL
$query = "
    SELECT
        i.id_inserzione AS inserzione_id,
        v.marca,
        v.modello,
        v.anno_immatricolazione AS anno,
        v.tipologia_carburante AS carburante,
        v.potenza,
        v.peso,
        i.descrizione,
        i.prezzo_giornaliero AS importo
    FROM inserzione i
    JOIN riguarda r ON r.id_inserzione = i.id_inserzione
    JOIN veicolo v ON v.targa = r.targa
";

if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inserzioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Veicoli Disponibili</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1200px; 
            margin: auto; 
            padding: 20px; 
            background: #f9f9f9;
        }
        h1 {
            color: #333;
        }
        form {
            margin-bottom: 20px;
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            align-items: center;
        }
        form input[type="text"],
        form input[type="number"] {
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            width: 150px;
            box-sizing: border-box;
        }
        form label {
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 5px;
        }
        form button {
            padding: 8px 15px;
            font-size: 1rem;
            background-color: #007BFF;
            border: none;
            border-radius: 4px;
            color: white;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #0056b3;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            background: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            border-radius: 5px;
            overflow: hidden;
        }
        th, td { 
            padding: 10px 12px; 
            border: 1px solid #ddd; 
            text-align: left; 
            font-size: 0.95rem;
        }
        th { 
            background-color: #f0f0f0; 
            font-weight: 600;
            color: #333;
        }
        tbody tr:hover {
            background-color: #f9f9f9;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        p {
            margin-top: 30px;
            text-align: center;
        }
        @media (max-width: 700px) {
            form {
                flex-direction: column;
                align-items: stretch;
            }
            form input[type="text"],
            form input[type="number"] {
                width: 100%;
            }
            table, th, td {
                font-size: 0.85rem;
            }
        }
    </style>
</head>
<body>
<h1>Veicoli disponibili</h1>

<form method="GET" action="">
    <input type="text" name="marca" placeholder="Marca" value="<?= htmlspecialchars($_GET['marca'] ?? '') ?>" />
    <input type="text" name="modello" placeholder="Modello" value="<?= htmlspecialchars($_GET['modello'] ?? '') ?>" />
    <input type="text" name="tipologia_carburante" placeholder="Carburante" value="<?= htmlspecialchars($_GET['tipologia_carburante'] ?? '') ?>" />
    <input type="number" name="prezzo_min" placeholder="Prezzo min" value="<?= htmlspecialchars($_GET['prezzo_min'] ?? '') ?>" step="0.01"/>
    <input type="number" name="prezzo_max" placeholder="Prezzo max" value="<?= htmlspecialchars($_GET['prezzo_max'] ?? '') ?>" step="0.01"/>
    <label>
        <input type="checkbox" name="neopatentati" <?= isset($_GET['neopatentati']) ? 'checked' : '' ?> /> Adatto a neopatentati
    </label>
    <button type="submit">Cerca</button>
</form>

<table>
    <thead>
        <tr>
            <th>Marca</th>
            <th>Modello</th>
            <th>Anno</th>
            <th>Carburante</th>
            <th>Potenza (kW)</th>
            <th>Rapporto kW/tonnellata</th>
            <th>Neopatentati</th>
            <th>Prezzo Giornaliero</th>
            <th>Descrizione</th>
            <th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php if ($inserzioni): ?>
            <?php foreach ($inserzioni as $i): 
                $rapporto = ($i['peso'] > 0) ? ($i['potenza'] * 1000 / $i['peso']) : 0;
                $neopatentati_ok = $rapporto <= 75;
            ?>
            <tr>
                <td><?= htmlspecialchars($i['marca']) ?></td>
                <td><?= htmlspecialchars($i['modello']) ?></td>
                <td><?= htmlspecialchars($i['anno']) ?></td>
                <td><?= htmlspecialchars($i['carburante']) ?></td>
                <td><?= htmlspecialchars($i['potenza']) ?></td>
                <td><?= number_format($rapporto, 2) ?> kW/ton</td>
                <td><?= $neopatentati_ok ? "Sì" : "No" ?></td>
                <td><?= number_format($i['importo'], 2) ?> €</td>
                <td><?= htmlspecialchars($i['descrizione']) ?></td>
                <td><a href="rent_vehicle.php?inserzione_id=<?= urlencode($i['inserzione_id']) ?>">Noleggia</a></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="10">Nessuna inserzione trovata.</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<p><a href="../index.php">&larr; Torna alla Home</a></p>
</body>
</html>
