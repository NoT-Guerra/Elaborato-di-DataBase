<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
check_logged_in();

$error = '';
$success = '';

// Carica accessori dal DB
try {
    $stmt = $pdo->query("
        SELECT 
          a.id_accessorio, 
          a.nome, 
          a.descrizione,
          ae.prezzo AS prezzo_extra,
          CASE WHEN ai.id_accessorio IS NOT NULL THEN 1 ELSE 0 END AS incluso
        FROM accessorio a
        LEFT JOIN accessori_extra ae ON a.id_accessorio = ae.id_accessorio
        LEFT JOIN accessori_inclusi ai ON a.id_accessorio = ai.id_accessorio
        ORDER BY a.nome
    ");
    $accessori = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Errore nel caricamento accessori: " . $e->getMessage();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$error) {
    $targa = strtoupper(trim($_POST['targa']));
    $marca = trim($_POST['marca']);
    $modello = trim($_POST['modello']);
    $anno = (int)$_POST['anno'];
    $potenza = isset($_POST['potenza']) ? (float)$_POST['potenza'] : 0;
    $carburante = trim($_POST['carburante']);
    $categoria_nome = trim($_POST['categoria']);
    $peso = isset($_POST['peso']) ? (float)$_POST['peso'] : 1000.00;
    $descrizione_extra = trim($_POST['extra_descrizione']);
    $prezzo_giornaliero_base = isset($_POST['prezzo_giornaliero']) ? (float)$_POST['prezzo_giornaliero'] : 0;
    $accessori_selezionati = isset($_POST['accessori']) ? $_POST['accessori'] : [];

    $citta = trim($_POST['citta']);
    $provincia = trim($_POST['provincia']);
    $regione = trim($_POST['regione']);

    try {
        $pdo->beginTransaction();

        // Trova o inserisci localita
        $stmt = $pdo->prepare("SELECT id_localita FROM localita WHERE citta = ? AND provincia = ?");
        $stmt->execute([$citta, $provincia]);
        $id_localita = $stmt->fetchColumn();

        if (!$id_localita) {
            if (empty($regione)) {
                throw new Exception("Per una nuova località è obbligatorio indicare la regione.");
            }
            $stmt = $pdo->prepare("INSERT INTO localita (citta, provincia, regione) VALUES (?, ?, ?)");
            $stmt->execute([$citta, $provincia, $regione]);
            $id_localita = $pdo->lastInsertId();
        }

        // Ottieni id_categoria oppure inserisci nuova categoria
        $stmt = $pdo->prepare("SELECT id_categoria FROM categoria_veicolo WHERE nome_categoria = ?");
        $stmt->execute([$categoria_nome]);
        $categoria_id = $stmt->fetchColumn();

        if (!$categoria_id) {
            $stmt = $pdo->prepare("INSERT INTO categoria_veicolo (nome_categoria) VALUES (?)");
            $stmt->execute([$categoria_nome]);
            $categoria_id = $pdo->lastInsertId();
        }

        // Calcola somma prezzi accessori extra
        $prezzo_accessori_extra = 0.0;
        $accessori_map = [];
        foreach ($accessori as $acc) {
            $accessori_map[$acc['id_accessorio']] = $acc;
        }

        foreach ($accessori_selezionati as $id_acc) {
            if (isset($accessori_map[$id_acc])) {
                $acc = $accessori_map[$id_acc];
                if (!$acc['incluso'] && !is_null($acc['prezzo_extra'])) {
                    $prezzo_accessori_extra += (float)$acc['prezzo_extra'];
                }
            }
        }

        // Prezzo totale = base + accessori extra
        $prezzo_giornaliero_totale = $prezzo_giornaliero_base + $prezzo_accessori_extra;

        // Inserisci inserzione con prezzo totale
        $stmt = $pdo->prepare("INSERT INTO inserzione (descrizione, prezzo_giornaliero, id_localita, id_utente) VALUES (?, ?, ?, ?)");
        $stmt->execute([$descrizione_extra, $prezzo_giornaliero_totale, $id_localita, $_SESSION['user_id']]);
        $id_inserzione = $pdo->lastInsertId();

        // Inserisci veicolo
        $stmt = $pdo->prepare("INSERT INTO veicolo (targa, peso, marca, modello, anno_immatricolazione, tipologia_carburante, potenza, id_inserzione, id_categoria) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$targa, $peso, $marca, $modello, $anno, $carburante, $potenza, $id_inserzione, $categoria_id]);

        // Inserisci proprietario se non esiste
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM proprietario WHERE id_utente = ?");
        $stmt->execute([$_SESSION['user_id']]);
        if (!$stmt->fetchColumn()) {
            $stmt = $pdo->prepare("INSERT INTO proprietario (id_utente) VALUES (?)");
            $stmt->execute([$_SESSION['user_id']]);
        }

        // Inserisci accessori associati
        if (!empty($accessori_selezionati)) {
            foreach ($accessori_selezionati as $id_acc) {
                $acc = $accessori_map[$id_acc] ?? null;
                if ($acc) {
                    $stmt = $pdo->prepare("INSERT INTO accessorio (id_accessorio, nome, descrizione, id_inserzione) VALUES (?, ?, ?, ?)
                                           ON DUPLICATE KEY UPDATE nome=VALUES(nome), descrizione=VALUES(descrizione), id_inserzione=VALUES(id_inserzione)");
                    $stmt->execute([$id_acc, $acc['nome'], $acc['descrizione'], $id_inserzione]);

                    if (!$acc['incluso'] && !is_null($acc['prezzo_extra'])) {
                        $stmt2 = $pdo->prepare("INSERT INTO accessori_extra (id_accessorio, prezzo) VALUES (?, ?) 
                                                ON DUPLICATE KEY UPDATE prezzo = VALUES(prezzo)");
                        $stmt2->execute([$id_acc, $acc['prezzo_extra']]);
                    }
                }
            }
        }

        $pdo->commit();
        $success = "Inserzione creata con successo!";
        header("Location: ../index.php");
        exit;
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Errore durante la creazione dell'inserzione: " . $e->getMessage();
    }
}
?>


<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8" />
<title>Crea inserzione</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background: #fff;
        color: #333;
        margin: 30px auto;
        max-width: 720px;
        padding: 0 20px;
    }
    h1, h2 {
        font-weight: 600;
        margin-bottom: 15px;
    }
    form {
        background: #f9f9f9;
        border-radius: 10px;
        padding: 25px;
        box-shadow: 0 0 10px #ddd;
    }
    label {
        display: block;
        margin-bottom: 10px;
        font-weight: 500;
    }
    input[type="text"], input[type="number"], textarea {
        font-size: 15px;
        border: 1px solid #ddd;
        border-radius: 5px;
        padding: 7px 10px;
        margin-top: 3px;
        width: 100%;
        box-sizing: border-box;
        font-family: Arial, sans-serif;
        resize: vertical;
    }
    textarea {
        min-height: 60px;
    }
    input[type="checkbox"] {
        margin-right: 6px;
        vertical-align: middle;
    }
    button {
        display: block;
        background-color: #0b6cf7;
        border: none;
        border-radius: 7px;
        color: white;
        padding: 14px 25px;
        font-size: 16px;
        font-weight: 700;
        margin: 30px auto 0 auto;
        cursor: pointer;
        width: 100%;
        max-width: 300px;
        transition: background-color 0.3s ease;
    }
    button:hover {
        background-color: #084cb3;
    }
    .accessorio-item {
        margin-bottom: 6px;
    }
    .error {
        padding: 12px;
        background: #ffb3b3;
        border-radius: 7px;
        margin-bottom: 25px;
        font-weight: 600;
        color: #9b0000;
        max-width: 720px;
        margin-left: auto;
        margin-right: auto;
    }
    .success {
        padding: 12px;
        background: #a5d6a7;
        border-radius: 7px;
        margin-bottom: 25px;
        font-weight: 600;
        color: #2e7d32;
        max-width: 720px;
        margin-left: auto;
        margin-right: auto;
    }
</style>
</head>
<body>
<h1>Crea una nuova inserzione</h1>
<?php if ($error) echo "<p class='error'>" . htmlspecialchars($error) . "</p>"; ?>
<?php if ($success) echo "<p class='success'>" . htmlspecialchars($success) . "</p>"; ?>

<form method="POST" autocomplete="off">
    <h2>Dettagli Veicolo</h2>
    <label>Marca:
        <input type="text" name="marca" required value="<?= isset($_POST['marca']) ? htmlspecialchars($_POST['marca']) : '' ?>">
    </label>
    <label>Modello:
        <input type="text" name="modello" required value="<?= isset($_POST['modello']) ? htmlspecialchars($_POST['modello']) : '' ?>">
    </label>
    <label>Anno immatricolazione:
        <input type="number" name="anno" min="1900" max="<?= date('Y') ?>" required value="<?= isset($_POST['anno']) ? (int)$_POST['anno'] : '' ?>">
    </label>
    <label>Potenza (CV):
        <input type="number" step="0.1" name="potenza" required value="<?= isset($_POST['potenza']) ? htmlspecialchars($_POST['potenza']) : '' ?>">
    </label>
    <label>Targa:
        <input type="text" name="targa" maxlength="7" required value="<?= isset($_POST['targa']) ? htmlspecialchars($_POST['targa']) : '' ?>">
    </label>
    <label>Tipo carburante:
        <input type="text" name="carburante" required value="<?= isset($_POST['carburante']) ? htmlspecialchars($_POST['carburante']) : '' ?>">
    </label>
    <label>Categoria:
        <input type="text" name="categoria" required value="<?= isset($_POST['categoria']) ? htmlspecialchars($_POST['categoria']) : '' ?>">
    </label>
    <label>Peso (kg):
        <input type="number" step="0.1" name="peso" required value="<?= isset($_POST['peso']) ? htmlspecialchars($_POST['peso']) : '1000' ?>">
    </label>

    <h2>Località</h2>
    <label>Città:
        <input type="text" name="citta" required value="<?= isset($_POST['citta']) ? htmlspecialchars($_POST['citta']) : '' ?>">
    </label>
    <label>Provincia:
        <input type="text" name="provincia" required value="<?= isset($_POST['provincia']) ? htmlspecialchars($_POST['provincia']) : '' ?>">
    </label>
    <label>Regione (solo se nuova località):
        <input type="text" name="regione" value="<?= isset($_POST['regione']) ? htmlspecialchars($_POST['regione']) : '' ?>">
    </label>

    <h2>Accessori</h2>
<?php if (!empty($accessori)): ?>
    <?php foreach ($accessori as $accessorio): ?>
    <div class="accessorio-item">
        <label>
            <input 
                type="checkbox" 
                name="accessori[]" 
                value="<?= $accessorio['id_accessorio'] ?>"
                <?php if (isset($_POST['accessori']) && in_array($accessorio['id_accessorio'], $_POST['accessori'])) echo 'checked'; ?>
            >
            <?= htmlspecialchars($accessorio['nome']) ?>
            <?php if ($accessorio['incluso']): ?>
                <em>(incluso nel prezzo)</em>
            <?php elseif (!$accessorio['incluso'] && $accessorio['prezzo_extra'] !== null): ?>
                <em>(extra €<?= number_format($accessorio['prezzo_extra'], 2) ?>)</em>
            <?php endif; ?>
        </label>
    </div>
    <?php endforeach; ?>
<?php else: ?>
    <p>Nessun accessorio disponibile.</p>
<?php endif; ?>


    <h2>Dettagli Inserzione</h2>
    <label>Descrizione extra:
        <textarea name="extra_descrizione" rows="3"><?= isset($_POST['extra_descrizione']) ? htmlspecialchars($_POST['extra_descrizione']) : '' ?></textarea>
    </label>
    <label>Prezzo base giornaliero (€):
        <input type="number" step="0.01" name="prezzo_giornaliero" required value="<?= isset($_POST['prezzo_giornaliero']) ? htmlspecialchars($_POST['prezzo_giornaliero']) : '' ?>">
    </label>
    <div id="prezzo-totale" style="margin-top:15px; font-weight: bold;">
    Prezzo totale: €<span id="totale">0.00</span>
</div>

    <button type="submit">Crea Inserzione</button>
</form>
<a href="../index.php">&larr; Torna alla pagina dettaglio</a>
<script>
document.addEventListener('DOMContentLoaded', function () {
    const accessori = document.querySelectorAll('input[name="accessori[]"]');
    const prezzoBaseInput = document.querySelector('input[name="prezzo_giornaliero"]');
    const totaleEl = document.getElementById('totale');

    // Mappa con i prezzi extra (server-side generato in JS)
    const prezziExtra = {
        <?php foreach ($accessori as $acc): 
            if (!$acc['incluso'] && $acc['prezzo_extra'] !== null): ?>
                <?= (int)$acc['id_accessorio'] ?>: <?= (float)$acc['prezzo_extra'] ?>,
        <?php endif; endforeach; ?>
    };

    function aggiornaTotale() {
        let base = parseFloat(prezzoBaseInput.value) || 0;
        let extra = 0;

        accessori.forEach(acc => {
            if (acc.checked) {
                let id = acc.value;
                if (prezziExtra[id]) {
                    extra += parseFloat(prezziExtra[id]);
                }
            }
        });

        const totale = (base + extra).toFixed(2);
        totaleEl.textContent = totale;
    }

    // Eventi su checkbox e campo prezzo base
    accessori.forEach(acc => acc.addEventListener('change', aggiornaTotale));
    prezzoBaseInput.addEventListener('input', aggiornaTotale);

    // Calcolo iniziale al caricamento
    aggiornaTotale();
});
</script>

</body>
</html>
