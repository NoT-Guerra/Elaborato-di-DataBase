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
if (!empty($_GET['prezzo_min']) && is_numeric($_GET['prezzo_min'])) {
    $where[] = "i.prezzo_giornaliero >= ?";
    $params[] = (float)$_GET['prezzo_min'];
}
if (!empty($_GET['prezzo_max']) && is_numeric($_GET['prezzo_max'])) {
    $where[] = "i.prezzo_giornaliero <= ?";
    $params[] = (float)$_GET['prezzo_max'];
}
if (!empty($_GET['neopatentati']) && $_GET['neopatentati'] === 'on') {
    $where[] = "v.peso > 0 AND (v.potenza * 1000 / v.peso) <= 75";
}

// 2. Query SQL
$query = "
    SELECT
        i.id_inserzione AS inserzione_id,
        v.targa,
        v.marca,
        v.modello,
        v.anno_immatricolazione AS anno,
        v.tipologia_carburante AS carburante,
        v.potenza,
        v.peso,
        i.descrizione,
        i.prezzo_giornaliero AS importo
    FROM inserzione i
    JOIN veicolo v ON v.id_inserzione = i.id_inserzione
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
            margin: 20px;
        }
        table {
            border-collapse: collapse;
            width: 100%;
            margin-top: 20px;
        }
        th, td {
            padding: 8px 12px;
            border: 1px solid #ccc;
            text-align: left;
        }
        th {
            background-color: #f2f2f2;
        }
        form input[type="text"],
        form input[type="number"] {
            padding: 6px;
            margin-right: 10px;
            margin-bottom: 10px;
            width: 150px;
        }
        form label {
            margin-right: 15px;
        }
        button {
            padding: 6px 12px;
            cursor: pointer;
        }
    </style>
</head>
<body>
<h1>Veicoli disponibili</h1>

<form method="GET" action="">
    <input type="text" name="marca" placeholder="Marca" value="<?= htmlspecialchars($_GET['marca'] ?? '') ?>" />
    <input type="text" name="modello" placeholder="Modello" value="<?= htmlspecialchars($_GET['modello'] ?? '') ?>" />
    <input type="text" name="tipologia_carburante" placeholder="Carburante" value="<?= htmlspecialchars($_GET['tipologia_carburante'] ?? '') ?>" />
    <input type="number" name="prezzo_min" placeholder="Prezzo min" value="<?= htmlspecialchars($_GET['prezzo_min'] ?? '') ?>" step="0.01" min="0" />
    <input type="number" name="prezzo_max" placeholder="Prezzo max" value="<?= htmlspecialchars($_GET['prezzo_max'] ?? '') ?>" step="0.01" min="0" />
    <label>
        <input type="checkbox" name="neopatentati" <?= (!empty($_GET['neopatentati']) && $_GET['neopatentati'] === 'on') ? 'checked' : '' ?> /> Adatto a neopatentati
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
            <th>Manutenzione</th>
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
                <td><?= number_format($i['potenza'], 2) ?></td>
                <td><?= number_format($rapporto, 2) ?> kW/ton</td>
                <td><?= $neopatentati_ok ? "Sì" : "No" ?></td>
                <td><?= number_format($i['importo'], 2) ?> €</td>
                <td><?= htmlspecialchars($i['descrizione']) ?></td>
                <td><a href="rent_vehicle.php?inserzione_id=<?= urlencode($i['inserzione_id']) ?>">Noleggia</a></td>
                <td><a href="manutenzione.php?targa=<?= urlencode($i['targa']) ?>">Manutenzione</a></td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="11">Nessuna inserzione trovata.</td></tr> 
        <?php endif; ?>
    </tbody>
</table>

<a href="../index.php">&larr; Torna alla pagina dettaglio</a>
</body>
</html>
