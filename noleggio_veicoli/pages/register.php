<?php
require_once '../includes/db.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nome = $_POST['nome'];
    $cognome = $_POST['cognome'];
    $email = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $password = $_POST['password'];

    if (!$email) {
        $error = "Email non valida.";
    } elseif (strlen($password) < 6) {
        $error = "Password troppo corta.";
    }

    if (!$error) {
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("INSERT INTO utente (nome, cognome, email, password) VALUES (?, ?, ?, ?)");
        try {
            $stmt->execute([$nome, $cognome, $email, $password_hash]);
            header("Location: login.php?registered=1");
            exit();
        } catch (PDOException $e) {
            // Mostra il vero errore durante lo sviluppo (da rimuovere in produzione)
            $error = "Errore: " . $e->getMessage();
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
</head>
<body>
    <h1>Registrati</h1>
    <?php if ($error) echo "<p class='error'>$error</p>"; ?>
    <form method="POST">
        <label>Nome:<input type="text" name="nome" required></label><br>
        <label>Cognome:<input type="text" name="cognome" required></label><br>
        <label>Email:<input type="email" name="email" required></label><br>
        <label>Password:<input type="password" name="password" required></label><br>
        <button type="submit">Registrati</button>
    </form>
</body>
</html>
