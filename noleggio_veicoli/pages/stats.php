<?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function mostraStatistiche($pdo, $titolo, $query) {
    echo "<section class='statistica'>";
    echo "<h3>$titolo</h3>";
    try {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<p class='error'>Errore SQL: " . htmlspecialchars($e->getMessage()) . "</p>";
        echo "</section>";
        return;
    }

    if (count($results) === 0) {
        echo "<p>Nessun risultato.</p>";
        echo "</section>";
        return;
    }

    echo "<table><tr>";
    foreach (array_keys($results[0]) as $col) {
        echo "<th>" . htmlspecialchars($col) . "</th>";
    }
    echo "</tr>";
    foreach ($results as $row) {
        echo "<tr>";
        foreach ($row as $value) {
            echo "<td>" . htmlspecialchars($value) . "</td>";
        }
        echo "</tr>";
    }
    echo "</table>";
    echo "</section>";
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche Noleggi</title>
    <style>
        body { 
            font-family: Arial, sans-serif; 
            max-width: 1200px; 
            margin: auto; 
            padding: 20px; 
            background: #f9f9f9;
        }
        #statistiche-container {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            justify-content: space-between;
        }
        .statistica {
            flex: 1 1 calc(50% - 20px); /* due colonne con gap */
            box-sizing: border-box;
            background: #fff;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            max-height: 600px;  /* opzionale: limita altezza */
            overflow: auto;     /* scroll interno se troppo lunga */
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
        .statistica h3 {
            margin-top: 0;
            font-size: 1.1rem;
            margin-bottom: 10px;
            color: #333;
        }
        table { 
            border-collapse: collapse; 
            width: 100%; 
            margin-bottom: 0; 
        }
        th, td { 
            padding: 8px; 
            border: 1px solid #ccc; 
            text-align: left; 
            font-size: 0.9rem;
        }
        th { 
            background-color: #f0f0f0; 
        }
        .error { 
            color: red; 
        }
        @media (max-width: 900px) {
            .statistica {
                flex: 1 1 100%; /* una colonna su schermi piccoli */
                max-height: none;
            }
        }
    </style>
</head>
<body>

<h1>Statistiche Noleggi</h1>

<div id="statistiche-container">
<?php
// 1. Top 5 proprietari con almeno 10 recensioni (media > 4)
mostraStatistiche($pdo, "Top 5 proprietari con almeno 10 recensioni (media > 4)", "
    SELECT u.nome, u.cognome, COUNT(r.id_recensione) AS recensioni_totali, AVG(r.voto) AS media
    FROM recensione r
    JOIN riguardo rg_r ON r.id_recensione = rg_r.id_recensione
    JOIN noleggio n ON rg_r.id_noleggio = n.id_noleggio
    JOIN coinvolge c ON n.id_noleggio = c.id_noleggio
    JOIN riguarda rg_v ON c.targa = rg_v.targa
    JOIN inserzione i ON rg_v.id_inserzione = i.id_inserzione
    JOIN crea cr ON i.id_inserzione = cr.id_inserzione
    JOIN utente u ON cr.id_utente = u.id_utente
    GROUP BY u.id_utente
    HAVING recensioni_totali >= 10 AND media > 4
    ORDER BY media DESC
    LIMIT 5
");

// 2. Proprietari con almeno 10 recensioni sotto la media globale
mostraStatistiche($pdo, "Proprietari sotto la media generale con almeno 10 recensioni", "
    WITH media_globale AS (
        SELECT AVG(voto) AS media FROM recensione
    )
    SELECT u.nome, u.cognome, COUNT(r.id_recensione) AS recensioni_totali, AVG(r.voto) AS media
    FROM recensione r
    JOIN riguardo rg_r ON r.id_recensione = rg_r.id_recensione
    JOIN noleggio n ON rg_r.id_noleggio = n.id_noleggio
    JOIN coinvolge c ON n.id_noleggio = c.id_noleggio
    JOIN riguarda rg_v ON c.targa = rg_v.targa
    JOIN inserzione i ON rg_v.id_inserzione = i.id_inserzione
    JOIN crea cr ON i.id_inserzione = cr.id_inserzione
    JOIN utente u ON cr.id_utente = u.id_utente
    GROUP BY u.id_utente
    HAVING recensioni_totali >= 10 AND media < (SELECT media FROM media_globale)
");

// 3. Veicoli guidabili da neopatentati (calcolo potenza/peso)
mostraStatistiche($pdo, "Veicoli guidabili da neopatentati", "
    SELECT marca, modello, potenza, peso,
           (potenza * 1000 / peso) AS rapporto_kw_per_ton
    FROM veicolo
    WHERE (potenza * 1000 / peso) <= 75
");

// 4. Veicolo più noleggiato
mostraStatistiche($pdo, "Veicolo più noleggiato", "
    SELECT v.marca, v.modello, COUNT(DISTINCT n.id_noleggio) AS totale_noleggi
    FROM noleggio n
    JOIN coinvolge c ON n.id_noleggio = c.id_noleggio
    JOIN riguarda rg ON c.targa = rg.targa
    JOIN veicolo v ON rg.targa = v.targa
    GROUP BY v.targa
    ORDER BY totale_noleggi DESC
    LIMIT 1
");

// 5. Noleggi mensili
mostraStatistiche($pdo, "Noleggi mensili", "
    SELECT YEAR(data_inizio) AS anno, MONTH(data_inizio) AS mese, COUNT(*) AS totale
    FROM noleggio
    GROUP BY anno, mese
    ORDER BY anno DESC, mese DESC
");

// 6. Top 10 veicoli con almeno 30 noleggi e media recensioni > 4.5
mostraStatistiche($pdo, "Top 10 veicoli con recensioni migliori", "
    SELECT v.marca, v.modello, COUNT(DISTINCT n.id_noleggio) AS noleggi, AVG(r.voto) AS media
    FROM noleggio n
    JOIN coinvolge c ON n.id_noleggio = c.id_noleggio
    JOIN veicolo v ON c.targa = v.targa
    JOIN riguardo rg ON rg.id_noleggio = n.id_noleggio
    JOIN recensione r ON r.id_recensione = rg.id_recensione
    GROUP BY v.targa
    HAVING noleggi >= 30 AND media > 4.5
    ORDER BY media DESC
    LIMIT 10
");

// 7. Top 10 città con più veicoli disponibili
mostraStatistiche($pdo, "Top 10 città con più veicoli disponibili", "
    SELECT l.`città`, COUNT(*) AS numero_veicoli
    FROM inserzione i
    JOIN riferita r ON i.id_inserzione = r.id_inserzione
    JOIN localita l ON r.id_localita = l.id_località
    GROUP BY l.`città`
    ORDER BY numero_veicoli DESC
    LIMIT 10
");

// 8. Veicoli più richiesti per categoria
mostraStatistiche($pdo, "Veicoli più richiesti per categoria", "
    SELECT v.tipologia_carburante AS categoria, v.marca, v.modello, COUNT(DISTINCT n.id_noleggio) AS noleggi
    FROM noleggio n
    JOIN coinvolge c ON n.id_noleggio = c.id_noleggio
    JOIN veicolo v ON c.targa = v.targa
    GROUP BY categoria, v.targa
    ORDER BY categoria, noleggi DESC
");

// 9. Veicoli meno noleggiati
mostraStatistiche($pdo, "Veicoli meno noleggiati", "
    SELECT v.marca, v.modello, COUNT(n.id_noleggio) AS noleggi
    FROM veicolo v
    LEFT JOIN coinvolge c ON v.targa = c.targa
    LEFT JOIN noleggio n ON c.id_noleggio = n.id_noleggio
    GROUP BY v.targa
    ORDER BY noleggi ASC
    LIMIT 10
");

// 10. Veicoli con maggiore disponibilità
mostraStatistiche($pdo, "Veicoli con maggiore disponibilità", "
    SELECT v.marca, v.modello, COUNT(*) * 24 AS ore_disponibili
    FROM inserzione i
    JOIN crea cr ON i.id_inserzione = cr.id_inserzione
    JOIN veicolo v ON v.targa = (
        SELECT rg.targa FROM riguarda rg WHERE rg.id_inserzione = i.id_inserzione LIMIT 1
    )
    GROUP BY v.targa
    ORDER BY ore_disponibili DESC
    LIMIT 10
");

// 11. Veicoli con maggior fatturato stimato
mostraStatistiche($pdo, "Veicoli con maggior fatturato stimato", "
    SELECT v.marca, v.modello, COUNT(DISTINCT n.id_noleggio) AS noleggi, 
           SUM(i.prezzo_giornaliero) AS ricavo_stimato
    FROM noleggio n
    JOIN coinvolge c ON n.id_noleggio = c.id_noleggio
    JOIN riguarda rg ON c.targa = rg.targa
    JOIN inserzione i ON rg.id_inserzione = i.id_inserzione
    JOIN veicolo v ON rg.targa = v.targa
    GROUP BY v.targa
    ORDER BY ricavo_stimato DESC
    LIMIT 10
");

// 12. Durata media noleggi per veicolo
mostraStatistiche($pdo, "Durata media noleggi per veicolo", "
    SELECT v.marca, v.modello, AVG(DATEDIFF(data_fine, data_inizio)) AS durata_media_giorni
    FROM noleggio n
    JOIN coinvolge c ON n.id_noleggio = c.id_noleggio
    JOIN veicolo v ON c.targa = v.targa
    GROUP BY v.targa
    ORDER BY durata_media_giorni DESC
    LIMIT 10
");

// 13. Veicoli mai noleggiati
mostraStatistiche($pdo, "Veicoli presenti in inserzioni ma mai noleggiati", "
    SELECT v.marca, v.modello
    FROM veicolo v
    WHERE NOT EXISTS (
        SELECT 1
        FROM coinvolge c
        JOIN noleggio n ON c.id_noleggio = n.id_noleggio
        WHERE c.targa = v.targa
    )
");
?>
</div>

<p style="margin-top: 30px; text-align: center;">
    <a href="../index.php" style="text-decoration: none; color: #007BFF;">&larr; Torna alla Home</a>
</p>

</body>