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
               u.id_utente
        FROM inserzione i
        JOIN veicolo v ON v.id_inserzione = i.id_inserzione
        JOIN utente u ON u.id_utente = i.id_utente
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
        JOIN tariffario t ON t.id_periodo = p.id_periodo";

$stmt = $pdo->query($sql);
$periodi_raw = $stmt->fetchAll(PDO::FETCH_ASSOC);

$periodi = array_map(function ($row) {
    return [
        'dataInizioPeriodo' => date('m-d', strtotime($row['dataInizioPeriodo'])),
        'dataFinePeriodo' => date('m-d', strtotime($row['dataFinePeriodo'])),
        'sconto' => (float)$row['sconto']
    ];
}, $periodi_raw);

$periodi_json = json_encode($periodi);

$success = $error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data_inizio    = $_POST['data_inizio'] ?? '';
    $data_fine      = $_POST['data_fine'] ?? '';
    $spedizione     = isset($_POST['spedizione']) ? 1 : 0;
    $luogo_ritiro   = trim($_POST['luogo_ritiro'] ?? '');
    $luogo_deposito = trim($_POST['luogo_deposito'] ?? '');

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
        $pdo->beginTransaction();

        try {
            // Recupera id_periodo relativo alla data di inizio (solo data inizio per sconto)
            $stmt = $pdo->prepare("
                SELECT id_periodo FROM periodo
                WHERE 
                    (
                        (DATE_FORMAT(dataInizioPeriodo, '%m-%d') <= DATE_FORMAT(?, '%m-%d') AND 
                         DATE_FORMAT(dataFinePeriodo, '%m-%d') >= DATE_FORMAT(?, '%m-%d'))
                    )
                    OR
                    (
                        DATE_FORMAT(dataInizioPeriodo, '%m-%d') > DATE_FORMAT(dataFinePeriodo, '%m-%d') 
                        AND 
                        (
                            DATE_FORMAT(?, '%m-%d') >= DATE_FORMAT(dataInizioPeriodo, '%m-%d') 
                            OR 
                            DATE_FORMAT(?, '%m-%d') <= DATE_FORMAT(dataFinePeriodo, '%m-%d')
                        )
                    )
                LIMIT 1
            ");

            // Passiamo 4 volte la stessa data_inizio per coprire i controlli sopra
            $stmt->execute([$d1->format('Y-m-d'), $d1->format('Y-m-d'), $d1->format('Y-m-d'), $d1->format('Y-m-d')]);
            $periodo = $stmt->fetch(PDO::FETCH_ASSOC);
            $id_periodo = $periodo ? $periodo['id_periodo'] : null;

            // Inserisci noleggio
            $stmt = $pdo->prepare("
                INSERT INTO noleggio 
                (data_inizio, data_fine, id_periodo, id_utente, id_luogo, targa)
                VALUES (?, ?, ?, ?, ?, ?)
            ");

            $id_luogo = null; // come prima

            $stmt->execute([
                $d1->format('Y-m-d'),
                $d2->format('Y-m-d'),
                $id_periodo,
                $_SESSION['user_id'],
                $id_luogo,
                $inserzione['targa']
            ]);

            $id_noleggio = $pdo->lastInsertId();

            // Calcola importo con sconto lato server
            $diffGiorni = $d2->diff($d1)->days + 1;
            $baseTotale = $inserzione['prezzo_giornaliero'] * $diffGiorni;

            // Calcola sconto massimo basato solo su data di inizio
            $scontoPercent = 0;
            $md_inizio = (int)$d1->format('md');

            foreach ($periodi as $p) {
                $inizioPeriodoMD = (int)str_replace('-', '', $p['dataInizioPeriodo']);
                $finePeriodoMD = (int)str_replace('-', '', $p['dataFinePeriodo']);

                $inPeriodo = false;
                if ($inizioPeriodoMD <= $finePeriodoMD) {
                    $inPeriodo = ($md_inizio >= $inizioPeriodoMD && $md_inizio <= $finePeriodoMD);
                } else {
                    // periodo che attraversa fine anno
                    $inPeriodo = ($md_inizio >= $inizioPeriodoMD || $md_inizio <= $finePeriodoMD);
                }

                if ($inPeriodo && $p['sconto'] > $scontoPercent) {
                    $scontoPercent = $p['sconto'];
                }
            }

            $importoSconto = $baseTotale * ($scontoPercent / 100);
            $totaleConSconto = $baseTotale - $importoSconto;

            $TASSA_SPEDIZIONE = 50;
            $spedizioneCosto = $spedizione ? $TASSA_SPEDIZIONE : 0;

            $importoFinale = $totaleConSconto + $spedizioneCosto;

            // Inserisci pagamento
            $stmt = $pdo->prepare("
                INSERT INTO pagamento (id_noleggio, data, importo, metodo_pagamento, stato_pagamento)
                VALUES (?, ?, ?, ?, ?)
            ");
            $stmt->execute([$id_noleggio, date('Y-m-d'), $importoFinale, 'da definire', 'in_attesa']);

            $success = 'Noleggio confermato! Contatta il proprietario per il pagamento e i dettagli di consegna.';

            $pdo->commit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Errore durante la prenotazione: ' . $e->getMessage();
        }
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
            color: #28a745;
            font-weight: 600;
            margin-bottom: 15px;
        }
    </style>
</head>
<body>

    <h1>Noleggia la vettura: <?= htmlspecialchars($inserzione['marca'] . ' ' . $inserzione['modello']) ?></h1>
    

    <div class="summary-box">
        <strong>Dettagli veicolo:</strong><br/>
        Targa: <?= htmlspecialchars($inserzione['targa']) ?><br/>
        Anno immatricolazione: <?= htmlspecialchars($inserzione['anno_immatricolazione']) ?><br/>
        Carburante: <?= htmlspecialchars($inserzione['tipologia_carburante']) ?><br/>
        Prezzo giornaliero: €<?= number_format($prezzo_giornaliero, 2, ',', '.') ?><br/>
        <hr>
        <strong>Proprietario:</strong> <?= htmlspecialchars($inserzione['proprietario_nome'] . ' ' . $inserzione['proprietario_cognome']) ?>
    </div>

    <?php if ($error): ?>
        <div class="error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="post" id="formNoleggio">
        <label for="data_inizio">Data inizio noleggio:</label>
        <input type="date" id="data_inizio" name="data_inizio" required min="<?= date('Y-m-d') ?>" />

        <label for="data_fine">Data fine noleggio:</label>
        <input type="date" id="data_fine" name="data_fine" required min="<?= date('Y-m-d') ?>" />

        <label>
            <input type="checkbox" id="spedizione" name="spedizione" />
            Richiedo la spedizione (costo €50)
        </label>

        <div id="spedizione_details" style="display:none;">
            <label for="luogo_ritiro">Luogo ritiro:</label>
            <input type="text" id="luogo_ritiro" name="luogo_ritiro" />

            <label for="luogo_deposito">Luogo deposito:</label>
            <input type="text" id="luogo_deposito" name="luogo_deposito" />
        </div>

        <button type="submit">Conferma noleggio</button>
    </form>

    <div class="summary-box">
        <strong>Riepilogo prezzo:</strong>
        <p id="riepilogoPrezzo">Seleziona le date per vedere il prezzo</p>
    </div>

    <script>
        const prezzoGiornaliero = <?= json_encode($prezzo_giornaliero) ?>;
        const periodi = <?= $periodi_json ?>;
        const TASSA_SPEDIZIONE = 50;

        const dataInizioInput = document.getElementById('data_inizio');
        const dataFineInput = document.getElementById('data_fine');
        const spedizioneCheckbox = document.getElementById('spedizione');
        const spedizioneDetails = document.getElementById('spedizione_details');
        const riepilogoPrezzo = document.getElementById('riepilogoPrezzo');

        spedizioneCheckbox.addEventListener('change', () => {
            spedizioneDetails.style.display = spedizioneCheckbox.checked ? 'block' : 'none';
            calcolaPrezzo();
        });

        dataInizioInput.addEventListener('change', () => {
            if (dataFineInput.value && dataFineInput.value < dataInizioInput.value) {
                dataFineInput.value = dataInizioInput.value;
            }
            dataFineInput.min = dataInizioInput.value;
            calcolaPrezzo();
        });

        dataFineInput.addEventListener('change', calcolaPrezzo);

        function isIntersects(start1, end1, start2, end2) {
            function inRange(x, a, b) {
                if (a <= b) return x >= a && x <= b;
                return x >= a || x <= b; // attraversa fine anno
            }
            return inRange(start1, start2, end2) || inRange(end1, start2, end2) ||
                   inRange(start2, start1, end1) || inRange(end2, start1, end1);
        }

        function calcolaPrezzo() {
            const dataInizio = dataInizioInput.value;
            const dataFine = dataFineInput.value;
            if (!dataInizio || !dataFine) {
                riepilogoPrezzo.textContent = 'Seleziona entrambe le date.';
                return;
            }

            const d1 = new Date(dataInizio);
            const d2 = new Date(dataFine);

            if (d1 > d2) {
                riepilogoPrezzo.textContent = 'La data di fine deve essere successiva alla data di inizio.';
                return;
            }

            const diff = Math.floor((d2 - d1) / (1000 * 60 * 60 * 24)) + 1;
            const baseTotale = prezzoGiornaliero * diff;

            // Date in formato mm-dd
            const mdInizio = ("0" + (d1.getMonth() + 1)).slice(-2) + '-' + ("0" + d1.getDate()).slice(-2);
            const mdFine = ("0" + (d2.getMonth() + 1)).slice(-2) + '-' + ("0" + d2.getDate()).slice(-2);
            const mdInizioNum = parseInt(mdInizio.replace('-', ''), 10);
            const mdFineNum = parseInt(mdFine.replace('-', ''), 10);

            let scontoPercent = 0;
            periodi.forEach(p => {
                const pInizioNum = parseInt(p.dataInizioPeriodo.replace('-', ''), 10);
                const pFineNum = parseInt(p.dataFinePeriodo.replace('-', ''), 10);
                if (isIntersects(mdInizioNum, mdFineNum, pInizioNum, pFineNum)) {
                    if (p.sconto > scontoPercent) scontoPercent = p.sconto;
                }
            });

            const importoSconto = baseTotale * (scontoPercent / 100);
            const totaleConSconto = baseTotale - importoSconto;

            const spedizione = spedizioneCheckbox.checked ? TASSA_SPEDIZIONE : 0;
            const totaleFinale = totaleConSconto + spedizione;

            riepilogoPrezzo.innerHTML = `
                Prezzo base (${diff} giorno${diff > 1 ? 'i' : ''}): €${baseTotale.toFixed(2)}<br/>
                Sconto applicato: ${scontoPercent}% (-€${importoSconto.toFixed(2)})<br/>
                ${spedizioneCheckbox.checked ? `Spedizione: €${TASSA_SPEDIZIONE.toFixed(2)}<br/>` : ''}
                <strong>Totale da pagare: €${totaleFinale.toFixed(2)}</strong>
            `;
        }
    </script>
    <a href="view_vehicles.php?inserzione_id=<?= $inserzione_id ?>">&larr; Torna alla pagina dettaglio</a>
</body>
</html>
