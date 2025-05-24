<?php
require_once '../includes/db.php';
session_start();

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email_input = $_POST['email'];
    $password_input = $_POST['password'];

    // Valida email
    if (filter_var($email_input, FILTER_VALIDATE_EMAIL)) {
        $stmt = $pdo->prepare("SELECT * FROM utente WHERE email = ?");
        $stmt->execute([$email_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password_input, $user['password'])) {
            // Login OK: salva in sessione
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
    } else {
        $error = "Email non valida.";
    }
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Login</title>
    <link rel="stylesheet" href="../assets/styles.css" />
</head>
<body>
    <h1>Login</h1>
    <?php if ($error): ?>
        <p class='error' style="color: red;"><?= htmlspecialchars($error) ?></p>
    <?php endif; ?>
    
    <form method="POST">
        <label>Email:<input type="email" name="email" required></label><br>
        <label>Password:<input type="password" name="password" required></label><br>
        <button type="submit">Accedi</button>
    </form>
</body>
</html>
