<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$utente_id = $_SESSION['user_id'];

// Aggiunta sinistro
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['add_sinistro'])) {
    $data = $_POST['data'];
    $descrizione = $_POST['descrizione'];
    $costo = $_POST['costo'];

    $stmt = $pdo->prepare("INSERT INTO sinistro (data, descrizione, costo) VALUES (?, ?, ?)");
    $stmt->execute([$data, $descrizione, $costo]);
}

// Recupera sinistri
$sinistri = [];
$result = $pdo->query("SELECT * FROM sinistro ORDER BY data DESC");
if ($result) {
    $sinistri = $result->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Gestione Sinistri</title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }

        h2, h3 {
            color: #333;
        }

        form {
            margin-bottom: 30px;
            padding: 15px;
            border: 1px solid #ccc;
            border-radius: 6px;
            background-color: #f9f9f9;
            max-width: 500px;
        }

        input, textarea {
            width: 100%;
            padding: 8px;
            margin: 6px 0 12px;
            border-radius: 4px;
            border: 1px solid #ccc;
        }

        button {
            background-color: #1e90ff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
        }

        button:hover {
            background-color: #005bb5;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        table, th, td {
            border: 1px solid #ccc;
        }

        th, td {
            padding: 12px;
            text-align: left;
        }

        a {
            text-decoration: none;
            color: #007BFF;
        }

        a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>
    <h2>Gestione Sinistri</h2>

    <h3>Aggiungi Nuovo Sinistro</h3>
    <form method="post">
        <label for="data">Data:</label>
        <input type="date" name="data" id="data" required>

        <label for="descrizione">Descrizione:</label>
        <textarea name="descrizione" id="descrizione" required></textarea>

        <label for="costo">Costo (€):</label>
        <input type="number" step="0.01" name="costo" id="costo" required>

        <button type="submit" name="add_sinistro">Aggiungi Sinistro</button>
    </form>

    <h3>Elenco Sinistri</h3>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Data</th>
                <th>Descrizione</th>
                <th>Costo (€)</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($sinistri) > 0): ?>
                <?php foreach ($sinistri as $s): ?>
                    <tr>
                        <td><?= htmlspecialchars($s['id_sinistro']) ?></td>
                        <td><?= htmlspecialchars($s['data']) ?></td>
                        <td><?= htmlspecialchars($s['descrizione']) ?></td>
                        <td><?= number_format($s['costo'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">Nessun sinistro registrato.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>

    <p><a href="profile.php">&larr; Torna al Profilo</a></p>
</body>
</html>
