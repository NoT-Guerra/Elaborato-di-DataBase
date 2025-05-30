<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$inserzione_id = isset($_GET['inserzione_id']) ? (int)$_GET['inserzione_id'] : 0;
if ($inserzione_id <= 0) {
    die('Inserzione non valida.');
}

// Recupera info inserzione + veicolo + proprietario
$sql = "SELECT i.*, v.targa, v.marca, v.modello, v.anno_immatricolazione, v.tipologia_carburante,
               u.nome AS proprietario_nome, u.cognome AS proprietario_cognome,
               c.id_utente
        FROM inserzione i
        JOIN riguarda r ON r.id_inserzione = i.id_inserzione
        JOIN veicolo v ON v.targa = r.targa
        JOIN crea c ON c.id_inserzione = i.id_inserzione
        JOIN utente u ON u.id_utente = c.id_utente
        WHERE i.id_inserzione = ?";

$stmt = $pdo->prepare($sql);
$stmt->execute([$inserzione_id]);
$inserzione = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inserzione) {
    die('Inserzione non trovata.');
}

// Recupera periodi con sconti e forza formato YYYY-MM-DD
$sql = "SELECT p.dataInizioPeriodo, p.dataFinePeriodo, t.sconto
        FROM periodo p
        JOIN determina_tariffa dtp ON dtp.id_periodo = p.id_periodo
        JOIN tariffario t ON t.id_tariffario = dtp.id_tariffario";

$stmt = $pdo->query($sql);
$periodi_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);
$periodi = array_map(function ($row) {
    return [
        'dataInizioPeriodo' => (new DateTime($row['dataInizioPeriodo']))->format('Y-m-d'),
        'dataFinePeriodo' => (new DateTime($row['dataFinePeriodo']))->format('Y-m-d'),
        'sconto' => (float)$row['sconto']
    ];
}, $periodi_raw);
$periodi_json = json_encode($periodi);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_inizio    = $_POST['data_inizio'] ?? '';
    $data_fine      = $_POST['data_fine'] ?? '';
    $spedizione     = isset($_POST['spedizione']) ? 1 : 0;
    $luogo_ritiro   = $_POST['luogo_ritiro'] ?? '';
    $luogo_deposito = $_POST['luogo_deposito'] ?? '';

    try {
        $d1 = new DateTime($data_inizio);
        $d2 = new DateTime($data_fine);
    } catch (Exception $e) {
        $error = 'Date non valide.';
    }

    if (!$error) {
        if ($d1 > $d2) {
            $error = 'La data di fine deve essere successiva alla data di inizio.';
        } elseif ($d1 < new DateTime('today')) {
            $error = 'La data di inizio non può essere nel passato.';
        }
    }

    if (!$error) {
        $sql = "INSERT INTO noleggio (data_inizio, data_fine, id_cliente, id_proprietario, id_veicolo, luogo_ritiro, luogo_deposito, spedizione)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $d1->format('Y-m-d'),
            $d2->format('Y-m-d'),
            $_SESSION['user_id'],
            $inserzione['id_utente'],
            $inserzione['targa'],
            $luogo_ritiro,
            $luogo_deposito,
            $spedizione
        ]);

        $success = 'Noleggio confermato! Contatta il proprietario per il pagamento e i dettagli di consegna.';
    }
}

$prezzo_giornaliero = isset($inserzione['prezzo_totale']) ? (float)$inserzione['prezzo_totale'] : 0;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Noleggia <?= htmlspecialchars($inserzione['marca'] . ' ' . $inserzione['modello']) ?></title>
    <link rel="stylesheet" href="../assets/styles.css" />
    <style>
        .summary-box { background:#fff; border:1px solid #ccc; padding:15px; margin:15px 0; }
        .error { color: red; }
        .success { color: green; }
    </style>
</head>
<body>
<a href="view_vehicles.php">&laquo; Torna alla lista</a>
<h1><?= htmlspecialchars($inserzione['marca'] . ' ' . $inserzione['modello']) ?></h1>
<p>Proprietario: <?= htmlspecialchars($inserzione['proprietario_nome'].' '.$inserzione['proprietario_cognome']) ?></p>
<p>Carburante: <?= htmlspecialchars($inserzione['tipologia_carburante']) ?></p>
<p>Anno: <?= (int)$inserzione['anno_immatricolazione'] ?></p>

<div class="summary-box">
    <strong>Prezzo giornaliero:</strong> <?= number_format($prezzo_giornaliero, 2) ?> €<br>
</div>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php else: ?>
<form method="POST" id="rentForm">
    <label>Data inizio: <input type="date" name="data_inizio" id="data_inizio" required></label><br>
    <label>Data fine: <input type="date" name="data_fine" id="data_fine" required></label><br>
    <label><input type="checkbox" name="spedizione" id="spedizione"> Richiedo spedizione veicolo</label><br>
    <div id="spedizione_details" style="display:none;">
        <label>Luogo ritiro: <input type="text" name="luogo_ritiro" id="luogo_ritiro"></label><br>
        <label>Luogo deposito: <input type="text" name="luogo_deposito" id="luogo_deposito"></label><br>
    </div>
    <div class="summary-box" id="cost_preview" style="display:none;"></div>
    <button type="submit">Conferma noleggio</button>
</form>
<?php endif; ?>

<script>
const spedizioneCheckbox = document.getElementById('spedizione');
const spedizioneDetails = document.getElementById('spedizione_details');
spedizioneCheckbox.addEventListener('change', () => {
    spedizioneDetails.style.display = spedizioneCheckbox.checked ? 'block' : 'none';
    calc();
});

const d1 = document.getElementById('data_inizio');
const d2 = document.getElementById('data_fine');
const preview = document.getElementById('cost_preview');
const prezzoGiornaliero = <?= json_encode($prezzo_giornaliero) ?>;
const periodi = <?= $periodi_json ?>;
const TASSA_SPEDIZIONE = 50;

function parseDate(str) {
    const [year, month, day] = str.split('-').map(Number);
    return new Date(Date.UTC(year, month - 1, day));
}

function trovaSconto(dataInizioStr) {
    const dataInizio = parseDate(dataInizioStr);
    for (const periodo of periodi) {
        const start = parseDate(periodo.dataInizioPeriodo);
        const end = parseDate(periodo.dataFinePeriodo);
        if (dataInizio >= start && dataInizio <= end) {
            return parseFloat(periodo.sconto);
        }
    }
    return 0;
}

function calc() {
    if (!d1.value || !d2.value) {
        preview.style.display = 'none';
        return;
    }

    const start = parseDate(d1.value);
    const end = parseDate(d2.value);
    if (end < start) {
        preview.style.display = 'none';
        return;
    }

    const diffDays = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
    const sconto = trovaSconto(d1.value);
    const costoBase = diffDays * prezzoGiornaliero;
    const costoScontato = costoBase * (1 - sconto / 100);
    const spedizione = spedizioneCheckbox.checked ? TASSA_SPEDIZIONE : 0;
    const totale = costoScontato + spedizione;

    preview.style.display = 'block';
    preview.innerHTML = `
        Durata: ${diffDays} giorni<br>
        Prezzo base: €${costoBase.toFixed(2)}<br>
        Sconto: ${sconto}%<br>
        Spedizione: €${spedizione.toFixed(2)}<br>
        <strong>Totale stimato: €${totale.toFixed(2)}</strong>
    `;
}

d1.addEventListener('change', calc);
d2.addEventListener('change', calc);
</script>

<a href="../index.php">Torna alla Home</a>
</body>
</html>
