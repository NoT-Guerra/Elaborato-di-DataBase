<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca = trim($_POST['marca']);
    $modello = trim($_POST['modello']);
    $anno = (int)$_POST['anno'];
    $carburante = trim($_POST['carburante']);
    $descrizione = trim($_POST['descrizione']);
    $manutenzione = trim($_POST['manutenzione']);
    $neopatentati = isset($_POST['neopatentati']) ? 1 : 0;
    $categoria = (int)$_POST['categoria'];
    $prezzo_base = (float)$_POST['prezzo_base'];

    $durata_giorni = (int)$_POST['durata_giorni'];
    $prezzo_totale = (float)$_POST['prezzo_totale'];
    $sconto = (float)$_POST['sconto'];
    $extra_descrizione = trim($_POST['extra_descrizione']);
    $disponibilita_giorni = (int)$_POST['disponibilita_giorni'];

    try {
        // Inserisci veicolo
        $stmt = $pdo->prepare("INSERT INTO veicoli (proprietario_id, marca, modello, anno, carburante, descrizione, manutenzione, neopatentati, categoria, prezzo_base) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $marca, $modello, $anno, $carburante, $descrizione, $manutenzione, $neopatentati, $categoria, $prezzo_base]);
        $veicolo_id = $pdo->lastInsertId();

        // Inserisci inserzione
        $stmt2 = $pdo->prepare("INSERT INTO inserzioni (veicolo_id, durata_giorni, prezzo_totale, sconto, extra_descrizione, disponibilita_giorni) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt2->execute([$veicolo_id, $durata_giorni, $prezzo_totale, $sconto, $extra_descrizione, $disponibilita_giorni]);

        $success = "Inserzione creata con successo!";
    } catch (PDOException $e) {
        $error = "Errore durante la creazione dell'inserzione: " . $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Crea Inserzione</title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f8f8f8;
        }
        form {
            max-width: 500px;
            margin: 40px auto;
            background: white;
            padding: 25px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }
        label {
            display: block;
            margin-bottom: 12px;
        }
        input[type="text"],
        input[type="number"],
        textarea,
        select {
            width: 100%;
            padding: 7px 10px;
            margin-top: 4px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 1rem;
            font-family: inherit;
        }
        textarea {
            resize: vertical;
        }
        button {
            margin-top: 15px;
            padding: 10px 20px;
            background-color: #2e86de;
            color: white;
            border: none;
            border-radius: 4px;
            font-size: 1rem;
            cursor: pointer;
        }
        button:hover {
            background-color: #1b4f72;
        }
        .error {
            color: #e74c3c;
            text-align: center;
            margin-bottom: 15px;
        }
        .success {
            color: #27ae60;
            text-align: center;
            margin-bottom: 15px;
        }
        h1, h2 {
            text-align: center;
        }
    </style>
</head>
<body>
<h1>Crea una nuova inserzione</h1>
<?php if ($error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
<?php if ($success) echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; ?>

<form method="POST" autocomplete="off">
    <h2>Dettagli Veicolo</h2>
    <label>Marca:
        <input type="text" name="marca" required value="<?= isset($_POST['marca']) ? htmlspecialchars($_POST['marca']) : '' ?>">
    </label>
    <label>Modello:
        <input type="text" name="modello" required value="<?= isset($_POST['modello']) ? htmlspecialchars($_POST['modello']) : '' ?>">
    </label>
    <label>Anno immatricolazione:
        <input type="number" name="anno" min="1900" max="<?= date('Y') ?>" required value="<?= isset($_POST['anno']) ? (int)$_POST['anno'] : '' ?>">
    </label>
    <label>Tipo carburante:
        <input type="text" name="carburante" required value="<?= isset($_POST['carburante']) ? htmlspecialchars($_POST['carburante']) : '' ?>">
    </label>
    <label>Descrizione:
        <textarea name="descrizione"><?= isset($_POST['descrizione']) ? htmlspecialchars($_POST['descrizione']) : '' ?></textarea>
    </label>
    <label>Manutenzione:
        <textarea name="manutenzione"><?= isset($_POST['manutenzione']) ? htmlspecialchars($_POST['manutenzione']) : '' ?></textarea>
    </label>
    <label>Adatto a neopatentati:
        <input type="checkbox" name="neopatentati" <?= isset($_POST['neopatentati']) ? 'checked' : '' ?>>
    </label>
    <label>Categoria:
        <select name="categoria" required>
            <option value="">Seleziona categoria</option>
            <option value="1" <?= (isset($_POST['categoria']) && $_POST['categoria']==1) ? 'selected' : '' ?>>Utilitaria</option>
            <option value="2" <?= (isset($_POST['categoria']) && $_POST['categoria']==2) ? 'selected' : '' ?>>Berlina</option>
            <option value="3" <?= (isset($_POST['categoria']) && $_POST['categoria']==3) ? 'selected' : '' ?>>Elettrica</option>
        </select>
    </label>
    <label>Prezzo base:
        <input type="number" step="0.01" name="prezzo_base" required value="<?= isset($_POST['prezzo_base']) ? htmlspecialchars($_POST['prezzo_base']) : '' ?>">
    </label>

    <h2>Dettagli Inserzione</h2>
    <label>Durata noleggio (giorni):
        <input type="number" name="durata_giorni" min="1" required value="<?= isset($_POST['durata_giorni']) ? (int)$_POST['durata_giorni'] : '' ?>">
    </label>
    <label>Prezzo totale:
        <input type="number" step="0.01" name="prezzo_totale" min="0" required value="<?= isset($_POST['prezzo_totale']) ? htmlspecialchars($_POST['prezzo_totale']) : '' ?>">
    </label>
    <label>Sconto:
        <input type="number" step="0.01" name="sconto" min="0" max="1" value="<?= isset($_POST['sconto']) ? htmlspecialchars($_POST['sconto']) : '0' ?>">
    </label>
    <label>Descrizione extra:
        <textarea name="extra_descrizione"><?= isset($_POST['extra_descrizione']) ? htmlspecialchars($_POST['extra_descrizione']) : '' ?></textarea>
    </label>
    <label>Disponibilità (giorni):
        <input type="number" name="disponibilita_giorni" min="1" required value="<?= isset($_POST['disponibilita_giorni']) ? (int)$_POST['disponibilita_giorni'] : '' ?>">
    </label>

    <button type="submit">Crea inserzione</button>
</form>

<a href="../index.php" style="display:block; text-align:center; margin-top:20px;">Torna alla Home</a>

</body>
</html>
