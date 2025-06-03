<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$error = '';

$via = '';
$citta = '';
$cap = '';
$provincia = '';
$nazione = 'Italia';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $via = trim($_POST['indirizzo'] ?? '');
    $citta = trim($_POST['citta'] ?? '');
    $cap = trim($_POST['cap'] ?? '');
    $provincia = trim($_POST['provincia'] ?? '');
    $nazione = trim($_POST['nazione'] ?? 'Italia');

    if ($via && $citta && $cap && $provincia && $nazione) {
        // Validazione semplice CAP: numerico e lunghezza 5 (modifica se serve)
        if (!preg_match('/^\d{5}$/', $cap)) {
            $error = "Il CAP deve essere composto da 5 cifre.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO INDIRIZZO (via, cap, citta, provincia, nazione, id_utente) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$via, $cap, $citta, $provincia, $nazione, $_SESSION['user_id']]);

                header('Location: profile.php');
                exit();
            } catch (PDOException $e) {
                $error = "Errore durante il salvataggio: " . htmlspecialchars($e->getMessage());
            }
        }
    } else {
        $error = "Compila tutti i campi.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Aggiungi indirizzo spedizione</title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            display: flex;
            justify-content: center;
            padding: 50px 20px;
        }
        .container {
            max-width: 500px;
            width: 100%;
            text-align: center;
        }
        h1 {
            margin-bottom: 30px;
        }
        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        label {
            display: flex;
            flex-direction: column;
            text-align: left;
            font-weight: bold;
        }
        input {
            padding: 8px;
            font-size: 1em;
            border: 1px solid #ccc;
            border-radius: 4px;
            margin-top: 5px;
        }
        button {
            background-color: #1e90ff;
            color: white;
            border: none;
            padding: 10px;
            font-size: 1em;
            border-radius: 6px;
            cursor: pointer;
        }
        button:hover {
            background-color: #005bb5;
        }
        .error {
            color: red;
            margin-bottom: 20px;
        }
        .back-link {
            margin-top: 20px;
            display: inline-block;
        }
        .back-link a {
            color: #007BFF;
            text-decoration: none;
            font-weight: bold;
        }
        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Aggiungi indirizzo di spedizione</h1>

    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST" novalidate>
        <label>
            Indirizzo:
            <input type="text" name="indirizzo" required value="<?= htmlspecialchars($via) ?>" />
        </label>

        <label>
            Città:
            <input type="text" name="citta" required value="<?= htmlspecialchars($citta) ?>" />
        </label>

        <label>
            CAP:
            <input type="text" name="cap" required pattern="\d{5}" title="Deve contenere 5 cifre" value="<?= htmlspecialchars($cap) ?>" />
        </label>

        <label>
            Provincia:
            <input type="text" name="provincia" required value="<?= htmlspecialchars($provincia) ?>" />
        </label>

        <label>
            Nazione:
            <input type="text" name="nazione" required value="<?= htmlspecialchars($nazione) ?>" />
        </label>

        <button type="submit">Aggiungi</button>
    </form>

    <div class="back-link">
        <a href="profile.php">&larr; Torna al Profilo</a>
    </div>
</div>
</body>
</html>
