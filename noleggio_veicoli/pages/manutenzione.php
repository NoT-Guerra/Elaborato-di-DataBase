<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

if (empty($_GET['targa'])) {
    die("Targa veicolo non specificata.");
}

$targa = $_GET['targa'];

// Recupera dati veicolo con la targa
$stmt = $pdo->prepare("SELECT * FROM veicolo WHERE targa = ?");
$stmt->execute([$targa]);
$veicolo = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$veicolo) {
    die("Veicolo non trovato.");
}

// Gestione form POST per inserire manutenzione
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = $_POST['data'] ?? '';
    $tipo = trim($_POST['tipo'] ?? '');
    $costo = $_POST['costo'] ?? '';
    $descrizione = trim($_POST['descrizione'] ?? '');

    if (!$data || !$tipo || $costo === '' || !is_numeric($costo) || !$descrizione) {
        $error = "Compila tutti i campi correttamente.";
    } else {
        $insert = $pdo->prepare("INSERT INTO manutenzione (data, tipo, costo, descrizione, targa) VALUES (?, ?, ?, ?, ?)");
        $insert->execute([$data, $tipo, $costo, $descrizione, $targa]);
        $success = "Manutenzione aggiunta con successo.";
    }
}

// Recupera manutenzioni per questa targa
$stmt = $pdo->prepare("SELECT * FROM manutenzione WHERE targa = ? ORDER BY data DESC");
$stmt->execute([$targa]);
$manutenzioni = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Manutenzione Veicolo - <?= htmlspecialchars($targa) ?></title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        label { display: block; margin-top: 10px; }
        input, textarea { width: 300px; padding: 6px; }
        textarea { height: 100px; }
        button { margin-top: 10px; padding: 8px 12px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ccc; padding: 8px; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        a { text-decoration: none; color: blue; }
    </style>
</head>
<body>
    <h1>Manutenzione Veicolo - Targa: <?= htmlspecialchars($targa) ?></h1>

    <p><strong>Marca:</strong> <?= htmlspecialchars($veicolo['marca']) ?></p>
    <p><strong>Modello:</strong> <?= htmlspecialchars($veicolo['modello']) ?></p>
    <p><strong>Anno:</strong> <?= htmlspecialchars($veicolo['anno_immatricolazione']) ?></p>

    <?php if (!empty($error)): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    <?php if (!empty($success)): ?>
        <p class="success"><?= htmlspecialchars($success) ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="data">Data intervento:</label>
        <input type="date" id="data" name="data" required />

        <label for="tipo">Tipo intervento:</label>
        <input type="text" id="tipo" name="tipo" required />

        <label for="costo">Costo (€):</label>
        <input type="number" step="0.01" min="0" id="costo" name="costo" required />

        <label for="descrizione">Descrizione intervento:</label>
        <textarea id="descrizione" name="descrizione" required></textarea>

        <button type="submit">Aggiungi manutenzione</button>
    </form>

    <h2>Storico manutenzioni</h2>
    <?php if ($manutenzioni): ?>
        <table>
            <thead>
                <tr>
                    <th>Data</th>
                    <th>Tipo</th>
                    <th>Costo (€)</th>
                    <th>Descrizione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($manutenzioni as $m): ?>
                <tr>
                    <td><?= htmlspecialchars($m['data']) ?></td>
                    <td><?= htmlspecialchars($m['tipo']) ?></td>
                    <td><?= number_format($m['costo'], 2) ?></td>
                    <td><?= nl2br(htmlspecialchars($m['descrizione'])) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p>Nessuna manutenzione registrata per questo veicolo.</p>
    <?php endif; ?>

    <p><a href="view_vehicles.php">&larr; Torna alla lista veicoli</a></p>
</body>
</html>
