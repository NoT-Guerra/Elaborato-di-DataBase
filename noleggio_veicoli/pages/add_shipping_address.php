<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $indirizzo = $_POST['indirizzo'];
    $citta = $_POST['citta'];
    $cap = $_POST['cap'];
    $provincia = $_POST['provincia'];

    if ($indirizzo && $citta && $cap && $provincia) {
        $stmt = $pdo->prepare("INSERT INTO indirizzi_spedizione (user_id, indirizzo, citta, cap, provincia) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['user_id'], $indirizzo, $citta, $cap, $provincia]);
        header('Location: profile.php');
        exit();
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

    <form method="POST">
        <label>
            Indirizzo:
            <input type="text" name="indirizzo" required>
        </label>

        <label>
            Città:
            <input type="text" name="citta" required>
        </label>

        <label>
            CAP:
            <input type="text" name="cap" required>
        </label>

        <label>
            Provincia:
            <input type="text" name="provincia" required>
        </label>

        <button type="submit">Aggiungi</button>
    </form>

    <div class="back-link">
        <a href="profile.php">&larr; Torna al Profilo</a>
    </div>
</div>
</body>
</html>
