<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $indirizzo =   $_POST['indirizzo'];
    $citta =   $_POST['citta'];
    $cap =   $_POST['cap'];
    $provincia =   $_POST['provincia'];

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
</head>
<body>
<h1>Aggiungi indirizzo di spedizione</h1>
<?php if ($error) echo "<p class='error'>$error</p>"; ?>
<form method="POST">
    <label>Indirizzo:<input type="text" name="indirizzo" required></label><br>
    <label>Città:<input type="text" name="citta" required></label><br>
    <label>CAP:<input type="text" name="cap" required></label><br>
    <label>Provincia:<input type="text" name="provincia" required></label><br>
    <button type="submit">Aggiungi</button>
</form>
</body>
</html>
