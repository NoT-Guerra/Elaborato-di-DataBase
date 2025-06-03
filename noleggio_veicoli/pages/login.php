<?php
require_once '../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = trim($_POST['email'] ?? '');
    $password_input = trim($_POST['password'] ?? '');

    if ($email_input === '' || $password_input === '') {
        $error = "Tutti i campi sono obbligatori.";
    } elseif (!filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $error = "Email non valida.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM UTENTE WHERE email = ?");
        $stmt->execute([$email_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password_input, $user['password'])) {
            // Login OK: salva dati in sessione
            $_SESSION['user_id'] = $user['id_utente'];
            $_SESSION['user_nome'] = $user['nome'];
            $_SESSION['user_cognome'] = $user['cognome'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_stato'] = $user['stato_account'];

            header("Location: ../index.php");
            exit();
        } else {
            $error = "Email o password errati.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        .error { color: red; margin-bottom: 15px; }
        form { max-width: 300px; margin: 0 auto; }
        label { display: block; margin-bottom: 10px; }
        input { width: 100%; padding: 8px; margin-top: 5px; }
        button { padding: 10px 20px; margin-top: 10px; cursor: pointer; }
    </style>
</head>
<body>
    <h1>Login</h1>

    <?php if ($error): ?>
        <p class='error'><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>

    <form method="POST">
        <label>
            Email:
            <input type="email" name="email" required>
        </label>
        <label>
            Password:
            <input type="password" name="password" required>
        </label>
        <button type="submit">Accedi</button>
    </form>
</body>
</html>
