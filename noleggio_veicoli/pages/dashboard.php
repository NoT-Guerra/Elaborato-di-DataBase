<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: ./pages/login.php");
    exit();
}

$email = $_SESSION['user_email'];
$initials = strtoupper(substr($_SESSION['user_nome'], 0, 1) . substr($email, 0, 1));
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Dashboard</title>
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

        .header {
            background-color: #1e1e1e;
            padding: 10px 20px;
            display: flex;
            justify-content: flex-end;
            align-items: center;
            color: white;
        }

        .user-icon {
            width: 35px;
            height: 35px;
            border-radius: 50%;
            background-color: #9c27b0;
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-left: 10px;
        }

        .email {
            font-size: 0.9em;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="email"><?= htmlspecialchars($email) ?></div>
    <div class="user-icon"><?= $initials ?></div>
</div>

<h1 style="padding: 20px;">Benvenuto nella Dashboard</h1>

<!-- Qui puoi aggiungere il contenuto personalizzato -->
</body>
</html>
