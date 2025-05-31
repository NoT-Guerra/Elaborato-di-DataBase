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
               u.nome AS proprietario_nome, u.cognome AS proprietario_cognome, i.prezzo_giornaliero,
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

// Recupera periodi con sconti (dataInizioPeriodo e dataFinePeriodo in formato 'MM-DD')
$sql = "SELECT p.dataInizioPeriodo, p.dataFinePeriodo, t.sconto
        FROM periodo p
        JOIN determina_tariffa dtp ON dtp.id_periodo = p.id_periodo
        JOIN tariffario t ON t.id_tariffario = dtp.id_tariffario";

$stmt = $pdo->query($sql);
$periodi_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$periodi = array_map(function ($row) {
    return [
        'dataInizioPeriodo' => $row['dataInizioPeriodo'],  // es. '03-01'
        'dataFinePeriodo' => $row['dataFinePeriodo'],      // es. '05-31'
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

$prezzo_giornaliero = isset($inserzione['prezzo_giornaliero']) ? (float)$inserzione['prezzo_giornaliero'] : 0;
?>

<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8" />
    <title>Noleggia <?= htmlspecialchars($inserzione['marca'] . ' ' . $inserzione['modello']) ?></title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 900px; 
            margin: auto; 
            padding: 20px; 
            background: #f9f9f9;
            color: #333;
        }
        h1 {
            color: #222;
            margin-bottom: 0.5rem;
        }
        a {
            color: #007BFF;
            text-decoration: none;
        }
        a:hover {
            text-decoration: underline;
        }
        .summary-box {
            background: #fff; 
            border: 1px solid #ccc; 
            padding: 15px; 
            margin: 15px 0;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
        }
        form {
            background: #fff;
            padding: 15px;
            border-radius: 5px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        form label {
            display: block;
            margin-bottom: 10px;
            font-weight: 600;
        }
        form input[type="date"],
        form input[type="text"] {
            padding: 8px;
            width: 100%;
            max-width: 250px;
            border: 1px solid #ccc;
            border-radius: 4px;
            font-size: 1rem;
            box-sizing: border-box;
            margin-top: 4px;
        }
        form input[type="checkbox"] {
            margin-right: 6px;
            vertical-align: middle;
        }
        form button {
            background-color: #007BFF;
            border: none;
            color: white;
            padding: 10px 20px;
            font-size: 1rem;
            border-radius: 4px;
            cursor: pointer;
            margin-top: 10px;
            transition: background-color 0.3s ease;
        }
        form button:hover {
            background-color: #0056b3;
        }
        #spedizione_details {
            margin-top: 10px;
        }
        .error {
            color: #cc0000;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .success {
            color: #2a7a2a;
            font-weight: 600;
            margin-bottom: 15px;
        }
        #cost_preview {
            background-color: #e7f3ff;
            border: 1px solid #90b8ff;
            padding: 12px;
            border-radius: 5px;
            margin-top: 15px;
            font-weight: 600;
        }
        p.info {
            margin: 5px 0;
        }
        @media (max-width: 700px) {
            form input[type="date"],
            form input[type="text"] {
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

<a href="view_vehicles.php">&larr; Torna alla lista</a>

<h1><?= htmlspecialchars($inserzione['marca'] . ' ' . $inserzione['modello']) ?></h1>

<p class="info"><strong>Proprietario:</strong> <?= htmlspecialchars($inserzione['proprietario_nome'] . ' ' . $inserzione['proprietario_cognome']) ?></p>
<p class="info"><strong>Carburante:</strong> <?= htmlspecialchars($inserzione['tipologia_carburante']) ?></p>
<p class="info"><strong>Anno immatricolazione:</strong> <?= (int)$inserzione['anno_immatricolazione'] ?></p>

<div class="summary-box">
    <strong>Prezzo giornaliero:</strong> <?= number_format($prezzo_giornaliero, 2) ?> €
</div>

<?php if ($error): ?>
    <p class="error"><?= htmlspecialchars($error) ?></p>
<?php endif; ?>

<?php if ($success): ?>
    <p class="success"><?= htmlspecialchars($success) ?></p>
<?php else: ?>

<form method="POST" id="rentForm" novalidate>
    <label for="data_inizio">Data inizio:
        <input type="date" name="data_inizio" id="data_inizio" required />
    </label>

    <label for="data_fine">Data fine:
        <input type="date" name="data_fine" id="data_fine" required />
    </label>

    <label>
        <input type="checkbox" name="spedizione" id="spedizione" />
        Richiedo spedizione veicolo
    </label>

    <div id="spedizione_details" style="display:none;">
        <label for="luogo_ritiro">Luogo ritiro:
            <input type="text" name="luogo_ritiro" id="luogo_ritiro" placeholder="Indirizzo ritiro" />
        </label>
        <label for="luogo_deposito">Luogo deposito:
            <input type="text" name="luogo_deposito" id="luogo_deposito" placeholder="Indirizzo deposito" />
        </label>
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

function toMMDD(str) {
    const parts = str.split('-');
    return parseInt(parts[1] + parts[2]); // MMDD numerico
}

function isIntersects(start1, end1, start2, end2) {
    function inRange(x, a, b) {
        if (a <= b) return x >= a && x <= b;
        return x >= a || x <= b; // periodo che attraversa fine anno
    }
    return inRange(start1, start2, end2) || inRange(end1, start2, end2) ||
           inRange(start2, start1, end1) || inRange(end2, start1, end1);
}

function trovaScontoTotale(dataInizioNoleggio, dataFineNoleggio) {
    const inizio = toMMDD(dataInizioNoleggio);
    const fine = toMMDD(dataFineNoleggio);

    for (const periodo of periodi) {
        const pInizio = parseInt(periodo.dataInizioPeriodo.replace('-', ''));
        const pFine = parseInt(periodo.dataFinePeriodo.replace('-', ''));

        if (isIntersects(inizio, fine, pInizio, pFine)) {
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

    const start = new Date(d1.value);
    const end = new Date(d2.value);
    if (end < start) {
        preview.style.display = 'none';
        return;
    }

    const diffGiorni = Math.floor((end - start) / (1000 * 60 * 60 * 24)) + 1;
    const baseTotale = prezzoGiornaliero * diffGiorni;
    const sconto = trovaScontoTotale(d1.value, d2.value);
    const importoSconto = baseTotale * (sconto / 100);
    const totaleConSconto = baseTotale - importoSconto;

    const spedizione = spedizioneCheckbox.checked ? TASSA_SPEDIZIONE : 0;
    const totaleFinale = totaleConSconto + spedizione;

    preview.style.display = 'block';
    preview.innerHTML =
        `Durata noleggio: <strong>${diffGiorni} giorni</strong><br>` +
        `Prezzo base: <strong>${baseTotale.toFixed(2)} €</strong><br>` +
        (sconto > 0
            ? `Sconto applicato: <strong>${sconto}%</strong> (-${importoSconto.toFixed(2)} €)<br>`
            : '') +
        (spedizione > 0
            ? `Spedizione: <strong>${spedizione.toFixed(2)} €</strong><br>`
            : '') +
        `<strong>Totale da pagare: ${totaleFinale.toFixed(2)} €</strong>`;
}

d1.addEventListener('change', calc);
d2.addEventListener('change', calc);
spedizioneCheckbox.addEventListener('change', calc);
</script>
<p style="text-align: center;">
  <a href="../index.php" style="color: #007BFF; text-decoration: none;">
    &larr; Torna alla Home
  </a>
</p>
</body>
</html>
