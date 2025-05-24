
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <title>Statistiche Noleggi</title>
    <style>
        body  { font-family: Arial, sans-serif; }
        table { border-collapse: collapse; width: 100%; margin-bottom: 30px; }
        th, td { padding: 8px 12px; border: 1px solid #ccc; }
        th      { background: #f0f0f0; }
        h3      { margin-top: 40px; }
        .error  { color: red; }
    </style>
</head>
<body>
    <h1>Statistiche Noleggi</h1>
    <?php
require_once '../includes/db.php';
require_once '../includes/functions.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

function mostraStatistiche($pdo, $titolo, $query) {
    echo "<h3>$titolo</h3>";
    try {
        $stmt = $pdo->query($query);
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        echo "<p>Errore SQL: " . htmlspecialchars($e->getMessage()) . "</p>";
        return;
    }

    if (count($results) === 0) {
        echo "<p>Nessun risultato.</p>";
        return;
    }

    echo "<table border='1' cellpadding='5'><tr>";
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
    echo "</table><br>";
}

// 1. Top 5 proprietari con almeno 100 recensioni (media > 4)
mostraStatistiche($pdo, "Top 5 proprietari con almeno 100 recensioni (media > 4 stelle)", "
    SELECT u.nome, u.cognome, COUNT(r.id_recensione) AS recensioni_totali, AVG(r.voto) AS media
    FROM recensione r
    JOIN noleggio n ON r.id_noleggio = n.id_noleggio
    JOIN inserzioni i ON n.id_noleggio = i.id_inserzione
    JOIN utente u ON i.id_proprietario = u.id_utente
    GROUP BY u.id_utente
    HAVING COUNT(r.id_recensione) >= 100 AND AVG(r.voto) > 4
    ORDER BY media DESC
    LIMIT 5
");

// 2. Proprietari con almeno 10 recensioni sotto la media globale
mostraStatistiche($pdo, "Proprietari sotto la media generale con almeno 10 recensioni", "
 WITH media_globale AS (
    SELECT AVG(voto) AS media FROM recensione
)
SELECT 
    u.nome, 
    u.cognome, 
    COUNT(r.id_recensione) AS recensioni_totali, 
    AVG(r.voto) AS media
FROM recensione r
JOIN noleggio n ON r.id_noleggio = n.id_noleggio
JOIN inserzioni i ON n.id_inserzione = i.id_inserzione
JOIN utente u ON i.id_proprietario = u.id_utente
GROUP BY u.id_utente
HAVING recensioni_totali >= 10 
   AND media < (SELECT media FROM media_globale);
");

// 3. Veicoli guidabili da neopatentati
mostraStatistiche($pdo, "Veicoli guidabili da neopatentati", "
    SELECT marca, modello, potenza, adatto_neopatentati
    FROM veicoli
    WHERE adatto_neopatentati = 1
");

// 4. Veicolo più noleggiato (marca e modello)
mostraStatistiche($pdo, "Veicolo più noleggiato", "
    SELECT v.marca, v.modello, COUNT(n.id_noleggio) AS totale_noleggi
    FROM noleggio n
    JOIN inserzioni i ON n.id_noleggio = i.id_inserzione
    JOIN veicoli v ON i.veicolo_id = v.id_veicolo
    GROUP BY v.id_veicolo
    ORDER BY totale_noleggi DESC
    LIMIT 1
");

// 5. Noleggi per mese/anno
mostraStatistiche($pdo, "Noleggi mensili", "
    SELECT YEAR(data_inizio) AS anno, MONTH(data_inizio) AS mese, COUNT(*) AS totale
    FROM noleggio
    GROUP BY anno, mese
    ORDER BY anno DESC, mese DESC
");

// 6. Top 10 veicoli con almeno 30 noleggi e media recensioni > 4.5
mostraStatistiche($pdo, "Top 10 veicoli con recensioni migliori", "
SELECT v.marca, v.modello, COUNT(n.id_noleggio) AS noleggi, AVG(r.voto) AS media
FROM noleggio n
JOIN inserzioni i ON n.id_inserzione = i.id_inserzione
JOIN veicoli v ON i.veicolo_id = v.id_veicolo
JOIN recensione r ON r.id_noleggio = n.id_noleggio
GROUP BY v.id_veicolo
HAVING noleggi >= 30 AND media > 4.5
ORDER BY media DESC
LIMIT 10;
");

// 7. Top 10 città con più veicoli disponibili
mostraStatistiche($pdo, "Top 10 città con più veicoli disponibili", "
    SELECT loc.città AS citta, COUNT(i.id_inserzione) AS numero_veicoli
    FROM inserzioni i
    JOIN localita loc ON i.id_localita = loc.id_localita
    GROUP BY loc.id_localita
    ORDER BY numero_veicoli DESC
    LIMIT 10
");

// 8. Veicoli più richiesti per categoria
mostraStatistiche($pdo, "Veicoli più richiesti per categoria", "
    SELECT v.tipologia_carburante AS categoria, v.marca, v.modello, COUNT(n.id_noleggio) AS noleggi
    FROM veicoli v
    JOIN inserzioni i ON v.id_veicolo = i.veicolo_id
    JOIN noleggio n ON n.id_noleggio = i.id_inserzione
    GROUP BY categoria, v.id_veicolo
    ORDER BY categoria, noleggi DESC
");

// 9. Veicoli meno noleggiati
mostraStatistiche($pdo, "Veicoli meno noleggiati", "
    SELECT v.marca, v.modello, COUNT(n.id_noleggio) AS noleggi
    FROM veicoli v
    LEFT JOIN inserzioni i ON v.id_veicolo = i.veicolo_id
    LEFT JOIN noleggio n ON n.id_noleggio = i.id_inserzione
    GROUP BY v.id_veicolo
    ORDER BY noleggi ASC
    LIMIT 10
");

// 10. Veicoli con maggiore disponibilità oraria (somma differenza tra data_fine e data_inizio in ore)
mostraStatistiche($pdo, "Veicoli con maggiore disponibilità", "
    SELECT v.marca, v.modello, 
           SUM(TIMESTAMPDIFF(HOUR, i.data_inizio, i.data_fine)) AS ore_disponibili
    FROM inserzioni i
    JOIN veicoli v ON i.veicolo_id = v.id_veicolo
    GROUP BY v.id_veicolo
    ORDER BY ore_disponibili DESC
    LIMIT 10
");


// 11. Veicoli con maggior fatturato (se non hai un campo importo, commenta o rimuovi questa query)
mostraStatistiche($pdo, "Veicoli con maggior fatturato", "
    SELECT v.marca, v.modello, COUNT(n.id_noleggio) AS numero_noleggi
    FROM noleggio n
    JOIN inserzioni i ON n.id_noleggio = i.id_inserzione
    JOIN veicoli v ON i.veicolo_id = v.id_veicolo
    GROUP BY v.id_veicolo
    ORDER BY numero_noleggi DESC
    LIMIT 10
");

// 12. Durata media noleggi per veicolo (in giorni)
mostraStatistiche($pdo, "Durata media noleggi per veicolo (in giorni)", "
SELECT n.data_inizio,n.data_fine
FROM noleggio n
JOIN inserzioni i ON n.id_inserzione = i.id_inserzione
JOIN veicoli v ON i.veicolo_id = v.id_veicolo;
");

// 13. Veicoli presenti in inserzioni ma mai noleggiati
mostraStatistiche($pdo, "Veicoli presenti in inserzioni ma mai noleggiati", "
    SELECT v.marca, v.modello
    FROM veicoli v
    JOIN inserzioni i ON v.id_veicolo = i.veicolo_id
    LEFT JOIN noleggio n ON n.id_noleggio = i.id_inserzione
    WHERE n.id_noleggio IS NULL
");

?>
    <a href="../index.php">Torna alla Home</a>
</body>
</html>




