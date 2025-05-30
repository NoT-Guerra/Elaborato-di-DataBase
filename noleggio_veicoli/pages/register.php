<?php
require_once '../includes/db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = trim($_POST['nome']);
    $cognome = trim($_POST['cognome']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];

    // Checkbox "aggiungi_indirizzo"
    $aggiungi_indirizzo = isset($_POST['aggiungi_indirizzo']);

    // Campi indirizzo opzionali (prendiamo solo se checkbox selezionata)
    $via = $aggiungi_indirizzo ? trim($_POST['via'] ?? '') : '';
    $cap = $aggiungi_indirizzo ? trim($_POST['cap'] ?? '') : '';
    $citta = $aggiungi_indirizzo ? trim($_POST['citta'] ?? '') : '';
    $provincia = $aggiungi_indirizzo ? trim($_POST['provincia'] ?? '') : '';
    $nazione = $aggiungi_indirizzo ? trim($_POST['nazione'] ?? '') : '';

    // Validazione base
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email non valida.";
    } elseif (strlen($password) < 6) {
        $error = "La password deve contenere almeno 6 caratteri.";
    } else {
        // Controllo se email esiste già
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM utente WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email già registrata.";
        }
    }

    if (!$error) {
        try {
            $pdo->beginTransaction();

            if ($aggiungi_indirizzo && ($via || $cap || $citta || $provincia || $nazione)) {
                $stmtInd = $pdo->prepare("INSERT INTO indirizzo (via, cap, città, provincia, nazione) VALUES (?, ?, ?, ?, ?)");
                $stmtInd->execute([$via, $cap, $citta, $provincia, $nazione]);
                $id_indirizzo = $pdo->lastInsertId();
            } else {
                $id_indirizzo = null;
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmtUt = $pdo->prepare("INSERT INTO utente (nome, cognome, email, password, stato_account, id_indirizzo) VALUES (?, ?, ?, ?, 1, ?)");
            $stmtUt->execute([$nome, $cognome, $email, $password_hash, $id_indirizzo]);

            $pdo->commit();

            header("Location: login.php?registered=1");
            exit();

        } catch (PDOException $e) {
            $pdo->rollBack();
            $error = "Errore DB: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Registrazione</title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        .error { color: red; margin-bottom: 15px; }
        form { max-width: 400px; margin: 0 auto; }
        label { display: block; margin-bottom: 10px; }
        input { width: 100%; padding: 8px; margin-top: 5px; }
        fieldset { margin-top: 20px; padding: 10px; }
        legend { font-weight: bold; }
        button { padding: 10px 20px; margin-top: 10px; }
    </style>
</head>
<body>
    <h1>Registrati</h1>
    <?php if ($error): ?>
        <p class="error"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>Nome:
            <input type="text" name="nome" required>
        </label>
        <label>Cognome:
            <input type="text" name="cognome" required>
        </label>
        <label>Email:
            <input type="email" name="email" required>
        </label>
        <label>Password:
            <input type="password" name="password" required>
        </label>

        <label>
            <input type="checkbox" id="toggleIndirizzo" name="aggiungi_indirizzo" <?= isset($_POST['aggiungi_indirizzo']) ? 'checked' : '' ?>>
            Aggiungi indirizzo
        </label>

        <fieldset id="indirizzoFieldset" style="display:none;">
            <legend>Indirizzo (opzionale)</legend>
            <label>Via:
                <input type="text" name="via" value="<?= htmlspecialchars($_POST['via'] ?? '') ?>">
            </label>
            <label>CAP:
                <input type="text" name="cap" value="<?= htmlspecialchars($_POST['cap'] ?? '') ?>">
            </label>
            <label>Città:
                <input type="text" name="citta" value="<?= htmlspecialchars($_POST['citta'] ?? '') ?>">
            </label>
            <label>Provincia:
                <input type="text" name="provincia" value="<?= htmlspecialchars($_POST['provincia'] ?? '') ?>">
            </label>
            <label>Nazione:
                <input type="text" name="nazione" value="<?= htmlspecialchars($_POST['nazione'] ?? '') ?>">
            </label>
        </fieldset>

        <button type="submit">Registrati</button>
    </form>

<script>
    const checkbox = document.getElementById('toggleIndirizzo');
    const fieldset = document.getElementById('indirizzoFieldset');

    function toggleIndirizzo() {
        if (checkbox.checked) {
            fieldset.style.display = 'block';
        } else {
            fieldset.style.display = 'none';
        }
    }

    checkbox.addEventListener('change', toggleIndirizzo);
    window.addEventListener('load', () => {
        toggleIndirizzo();
    });
</script>

</body>
</html>
