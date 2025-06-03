<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$utente_id = $_SESSION['user_id'];

// Carica dati utente
$stmt = $pdo->prepare("SELECT * FROM UTENTE WHERE id_utente = ?");
$stmt->execute([$utente_id]);
$utente = $stmt->fetch();

if (!$utente) {
    header("Location: ../index.php");
    exit;
}

// Carica indirizzi spedizione associati all'utente
$stmt2 = $pdo->prepare("SELECT * FROM INDIRIZZO WHERE id_utente = ?");
$stmt2->execute([$utente_id]);
$indirizzi = $stmt2->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Profilo Utente</title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
        }

        .container {
            max-width: 700px;
            margin-top: 40px;
            padding: 20px;
            text-align: center;
        }

        h1, h2 {
            margin-bottom: 20px;
        }

        ul {
            list-style-type: none;
            padding: 0;
        }

        li {
            margin: 10px 0;
            padding: 10px;
            background-color: #f3f3f3;
            border-radius: 6px;
        }

        a {
            text-decoration: none;
            color: #007BFF;
            font-weight: bold;
        }

        a:hover {
            text-decoration: underline;
        }

        .button-link {
            display: inline-block;
            margin-top: 20px;
            background-color: #1e90ff;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
        }

        .button-link:hover {
            background-color: #005bb5;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>Profilo di <?= htmlspecialchars($utente['nome'] . ' ' . $utente['cognome']) ?></h1>

    <h2>Indirizzi di spedizione</h2>

    <?php if (count($indirizzi) > 0): ?>
        <ul>
            <?php foreach ($indirizzi as $indirizzo): ?>
                <li>
                    <?= htmlspecialchars(
                        ($indirizzo['via'] ?? 'Indirizzo non disponibile') . ', ' .
                        ($indirizzo['citta'] ?? 'Città non disponibile') . ', ' .
                        ($indirizzo['cap'] ?? 'CAP non disponibile') . ', ' .
                        ($indirizzo['provincia'] ?? 'Provincia non disponibile') . ', ' .
                        ($indirizzo['nazione'] ?? 'Nazione non disponibile')
                    ) ?>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>Nessun indirizzo di spedizione inserito.</p>
    <?php endif; ?>

    <p><a class="button-link" href="add_shipping_address.php">Aggiungi indirizzo di spedizione</a></p>
    <p><a class="button-link" href="manage_sinistri.php">Gestisci Sinistri</a></p>
    <p><a class="button-link" href="storico_noleggi.php">Visualizza Storico Noleggi</a></p>
    <p><a href="../index.php">&larr; Torna alla Home</a></p>
</div>
</body>
</html>
