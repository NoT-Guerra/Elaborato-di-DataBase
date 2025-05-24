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
$sql = "SELECT i.*, v.marca, v.modello, v.anno_immatricolazione, v.tipologia_carburante,
               u.nome AS proprietario_nome, u.cognome AS proprietario_cognome
        FROM inserzioni i
        JOIN veicoli v ON v.id_veicolo = i.veicolo_id
        JOIN utente u ON u.id_utente = v.proprietario_id
        WHERE i.id_inserzione = ?";
$stmt = $pdo->prepare($sql);
$stmt->execute([$inserzione_id]);
$inserzione = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inserzione) {
    die('Inserzione non trovata.');
}

// Calcola disponibilità residua (giorni non ancora prenotati)
$stmt = $pdo->prepare("
    SELECT COALESCE(SUM(DATEDIFF(data_fine, data_inizio) + 1),0) AS giorni_prenotati
    FROM noleggio
    WHERE id_inserzione = ?
");
$stmt->execute([$inserzione_id]);
$gi_prenotati = (int)$stmt->fetchColumn();
$disponibilita_residua = (int)$inserzione['disponibilita_giorni'] - $gi_prenotati;

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_inizio  = $_POST['data_inizio']  ?? '';
    $data_fine    = $_POST['data_fine']    ?? '';
    $spedizione   = isset($_POST['spedizione']) ? 1 : 0;
    $luogo_ritiro = $_POST['luogo_ritiro'] ?? '';
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

    $giorni_richiesti = 0;
    if (!$error) {
        $giorni_richiesti = $d1->diff($d2)->days + 1;
        if ($giorni_richiesti > $disponibilita_residua) {
            $error = 'Non ci sono abbastanza giorni di disponibilità (' . $disponibilita_residua . ' rimasti).';
        }
    }

    if (!$error) {
        // Tutto ok: registra noleggio
        $sql = "INSERT INTO noleggio (inserzione_id, user_id, data_inizio, data_fine, luogo_ritiro, luogo_deposito, spedizione)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $inserzione_id,
            $_SESSION['user_id'],
            $d1->format('Y-m-d'),
            $d2->format('Y-m-d'),
            $luogo_ritiro,
            $luogo_deposito,
            $spedizione
        ]);

        // Aggiorna disponibilità residua
        $stmt = $pdo->prepare("UPDATE inserzioni SET disponibilita_giorni = disponibilita_giorni - ? WHERE id = ?");
        $stmt->execute([$giorni_richiesti, $inserzione_id]);

        $success = 'Noleggio confermato! Contatta il proprietario per il pagamento e i dettagli di consegna.';
        // ricalcola disponibilità
        $disponibilita_residua -= $giorni_richiesti;
    }
}

// Calcola prezzo giornaliero e totale (senza sconto)
$prezzo_giornaliero = $inserzione['prezzo_totale'] / $inserzione['durata_giorni'];
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Noleggia <?= htmlspecialchars($inserzione['marca'] . ' ' . $inserzione['modello']) ?></title>
    <link rel="stylesheet" href="../assets/styles.css">
    <style>
      .summary-box{background:#fff;border:1px solid #ccc;padding:15px;margin:15px 0;}
    </style>
</head>
<body>
<a href="view_vehicles.php">&laquo; Torna alla lista</a>
<h1><?= htmlspecialchars($inserzione['marca'] . ' ' . $inserzione['modello']) ?></h1>
<p>Proprietario: <?= htmlspecialchars($inserzione['proprietario_nome'].' '.$inserzione['proprietario_cognome']) ?></p>
<p>Carburante: <?= htmlspecialchars($inserzione['tipologia_carburante']) ?></p>
<p>Anno: <?= (int)$inserzione['anno_immatricolazione'] ?></p>
</p>

<div class="summary-box">
    <strong>Disponibilità residua:</strong> <?= $disponibilita_residua ?> giorni<br>
    <strong>Prezzo giornaliero:</strong> <?= number_format($prezzo_giornaliero,2) ?> €<br>
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
const spedizione = document.getElementById('spedizione');
const details   = document.getElementById('spedizione_details');
spedizione.addEventListener('change',()=>details.style.display=spedizione.checked?'block':'none');

const d1 = document.getElementById('data_inizio');
const d2 = document.getElementById('data_fine');
const preview = document.getElementById('cost_preview');
const prezzoGiornaliero = <?= (float)$prezzo_giornaliero ?>;

function calc(){
    if(!d1.value || !d2.value) return preview.style.display='none';
    const start = new Date(d1.value);
    const end   = new Date(d2.value);
    if(end < start) { preview.style.display='none'; return;}
    const diffDays = Math.floor((end - start)/(1000*60*60*24))+1;
    let costo = diffDays * prezzoGiornaliero;
    preview.style.display='block';
    preview.textContent = `Durata: ${diffDays} giorni - Costo stimato: € ${costo.toFixed(2)}`;
}
d1.addEventListener('change',calc);
d2.addEventListener('change',calc);
</script>
<a href="../index.php">Torna alla Home</a>
</body>
</html>
