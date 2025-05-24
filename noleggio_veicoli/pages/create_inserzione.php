<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $marca =   $_POST['marca'];
    $modello =   $_POST['modello'];
    $anno = (int)$_POST['anno'];
    $carburante =   $_POST['carburante'];
    $descrizione =   $_POST['descrizione'];
    $manutenzione =   $_POST['manutenzione'];
    $neopatentati = isset($_POST['neopatentati']) ? 1 : 0;
    $categoria =   $_POST['categoria'];
    $prezzo_base = (float)$_POST['prezzo_base'];

    // Inserisci veicolo
    $stmt = $pdo->prepare("INSERT INTO veicoli (proprietario_id, marca, modello, anno, carburante, descrizione, manutenzione, neopatentati, categoria, prezzo_base) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$_SESSION['user_id'], $marca, $modello, $anno, $carburante, $descrizione, $manutenzione, $neopatentati, $categoria, $prezzo_base]);

    $veicolo_id = $pdo->lastInsertId();

    // Inserisci inserzione
    $durata_giorni = (int)$_POST['durata_giorni'];
    $prezzo_totale = (float)$_POST['prezzo_totale'];
    $sconto = (float)$_POST['sconto'];
    $extra_descrizione =   $_POST['extra_descrizione'];
    $disponibilita_giorni = (int)$_POST['disponibilita_giorni'];

    $stmt2 = $pdo->prepare("INSERT INTO inserzioni (veicolo_id, durata_giorni, prezzo_totale, sconto, extra_descrizione, disponibilita_giorni) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt2->execute([$veicolo_id, $durata_giorni, $prezzo_totale, $sconto, $extra_descrizione, $disponibilita_giorni]);

    $success = "Inserzione creata con successo!";
}
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Crea Inserzione</title>
    <link rel="stylesheet" href="../assets/styles.css" />
</head>
<body>
<h1>Crea una nuova inserzione</h1>
<?php if ($error) echo "<p class='error'>$error</p>"; ?>
<?php if ($success) echo "<p class='success'>$success</p>"; ?>

<form method="POST">
    <h2>Dettagli Veicolo</h2>
    <label>Marca:<input type="text" name="marca" required></label><br>
    <label>Modello:<input type="text" name="modello" required></label><br>
    <label>Anno immatricolazione:<input type="number" name="anno" required></label><br>
    <label>Tipo carburante:<input type="text" name="carburante" required></label><br>
    <label>Descrizione:<textarea name="descrizione"></textarea></label><br>
    <label>Manutenzione:<textarea name="manutenzione"></textarea></label><br>
    <label>Adatto a neopatentati:<input type="checkbox" name="neopatentati"></label><br>
    <label>Categoria:<input type="text" name="categoria" required></label><br>
    <label>Prezzo base:<input type="number" step="0.01" name="prezzo_base" required></label><br>

    <h2>Dettagli Inserzione</h2>
    <label>Durata noleggio (giorni):<input type="number" name="durata_giorni" required></label><br>
    <label>Prezzo totale:<input type="number" step="0.01" name="prezzo_totale" required></label><br>
    <label>Sconto:<input type="number" step="0.01" name="sconto" value="0"></label><br>
    <label>Descrizione extra:<textarea name="extra_descrizione"></textarea></label><br>
    <label>Disponibilità (giorni):<input type="number" name="disponibilita_giorni" required></label><br>

    <button type="submit">Crea inserzione</button>
</form>
<a href="../index.php">Torna alla Home</a>
</body>
</html>
