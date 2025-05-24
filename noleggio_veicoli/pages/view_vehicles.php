<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$query = "SELECT i.id_inserzione AS inserzione_id, v.*, i.descrizione, i.prezzo_totale 
          FROM inserzioni i
          JOIN veicoli v ON i.veicolo_id = v.id_veicolo";

// Filtro ricerca
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
    $where[] = "i.prezzo_totale >= ?";
    $params[] = (float)$_GET['prezzo_min'];
}
if (!empty($_GET['prezzo_max'])) {
    $where[] = "i.prezzo_totale <= ?";
    $params[] = (float)$_GET['prezzo_max'];
}
if (!empty($_GET['neopatentati'])) {
    $where[] = "v.neopatentati = 1";
}

if ($where) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$inserzioni = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Veicoli Disponibili</title>
    <link rel="stylesheet" href="../assets/styles.css" />
</head>
<body>
<h1>Veicoli disponibili</h1>

<form method="GET">
    <input type="text" name="marca" placeholder="Marca" value="<?= htmlspecialchars($_GET['marca'] ?? '') ?>" />
    <input type="text" name="modello" placeholder="Modello" value="<?= htmlspecialchars($_GET['modello'] ?? '') ?>" />
    <input type="text" name="tipologia_carburante" placeholder="Carburante" value="<?= htmlspecialchars($_GET['tipologia_carburante'] ?? '') ?>" />
    <input type="number" name="prezzo_min" placeholder="Prezzo min" value="<?= htmlspecialchars($_GET['prezzo_min'] ?? '') ?>" step="0.01"/>
    <input type="number" name="prezzo_max" placeholder="Prezzo max" value="<?= htmlspecialchars($_GET['prezzo_max'] ?? '') ?>" step="0.01"/>
    <label><input type="checkbox" name="neopatentati" <?= isset($_GET['neopatentati']) ? 'checked' : '' ?> /> Adatto a neopatentati</label>
    <button type="submit">Cerca</button>
</form>

<table>
    <thead>
        <tr>
            <th>Marca</th><th>Modello</th><th>Anno</th><th>Carburante</th><th>Prezzo Totale</th><th>Descrizione</th><th>Azioni</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($inserzioni as $i): ?>
        <tr>
            <td><?= htmlspecialchars($i['marca']) ?></td>
            <td><?= htmlspecialchars($i['modello']) ?></td>
            <td><?= htmlspecialchars($i['anno_immatricolazione']) ?></td>
            <td><?= htmlspecialchars($i['tipologia_carburante']) ?></td>
            <td><?= number_format($i['prezzo_totale'], 2) ?> €</td>
            <td><?= htmlspecialchars($i['descrizione']) ?></td>
            <td><a href="rent_vehicle.php?inserzione_id=<?= $i['inserzione_id'] ?>">Noleggia</a></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<p> 
<a href="../index.php">Torna alla Home</a>
</body>
</html>
