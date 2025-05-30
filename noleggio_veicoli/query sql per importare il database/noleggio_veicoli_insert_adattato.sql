
USE noleggio_veicoli;

-- 1. INDIRIZZI
INSERT INTO indirizzo (id_indirizzo, via, cap, città, provincia, nazione) VALUES
(1, 'Via Roma 10', '00100', 'Roma', 'RM', 'Italia'),
(2, 'Via Milano 20', '20100', 'Milano', 'MI', 'Italia');

-- 2. UTENTI
INSERT INTO utente (nome, cognome, email, password, stato_account, id_indirizzo) VALUES
('Mario', 'Rossi', 'mario.rossi@email.it', 'pwd1', "attivo", 1),
('Luigi', 'Verdi', 'luigi.verdi@email.it', 'pwd2', "attivo", 1),
('Anna', 'Bianchi', 'anna.bianchi@email.it', 'pwd3', "attivo", 2),
('Sara', 'Neri', 'sara.neri@email.it', 'pwd4', "attivo", 2);

-- 3. CLIENTI e PROPRIETARI
INSERT INTO cliente (id_utente) VALUES (2), (4);
INSERT INTO proprietario (id_utente) VALUES (3), (5);

-- 4. LOCALITÀ
INSERT INTO localita (id_località, città, provincia, regione) VALUES
(1, 'Roma', 'RM', 'Lazio'),
(2, 'Milano', 'MI', 'Lombardia');

-- 5. CATEGORIE VEICOLO
INSERT INTO categoria_veicolo (id_categoria, nome_categoria) VALUES
(1, 'Utilitaria'), (2, 'Berlina'), (3, 'Elettrica');

-- 6. INSERZIONI
INSERT INTO inserzione (id_inserzione, descrizione, id_utente, prezzo_giornaliero) VALUES
(1, 'Fiat Panda, ottimo stato, noleggio minimo 2 giorni', 3, 35.00),
(2, 'Tesla Model 3, guida autonoma disponibile', 5, 70.00),
(3, 'Ford Fiesta, ottimo stato, noleggio minimo 3 giorni', 3, 40.00),
(4, 'Renault Clio, benzina, perfetta per città', 3, 38.00),
(5, 'BMW Serie 3, berlina sportiva, noleggio minimo 1 settimana', 5, 85.00),
(6, 'Audi A4, elegante e confortevole, disponibile da subito', 5, 80.00);

-- 7. VEICOLI
INSERT INTO veicolo (targa, peso, marca, modello, anno_immatricolazione, tipologia_carburante, potenza, id_categoria) VALUES
('AA111AA', 950.00, 'Fiat', 'Panda', 2020, 'benzina', 70, 1),
('BB222BB', 1250.00, 'Tesla', 'Model 3', 2022, 'elettrico', 150, 3),
('CC333CC', 1080.00, 'Ford', 'Fiesta', 2019, 'benzina', 85, 1),
('DD444DD', 1040.00, 'Renault', 'Clio', 2017, 'benzina', 75, 1),
('EE555EE', 1500.00, 'BMW', 'Serie 3', 2021, 'diesel', 190, 2),
('FF666FF', 1450.00, 'Audi', 'A4', 2020, 'benzina', 180, 2);

-- 8. RELAZIONE INSERZIONE-VEICOLO
INSERT INTO riguarda (id_inserzione, targa) VALUES
(1, 'AA111AA'),
(2, 'BB222BB'),
(3, 'CC333CC'),
(4, 'DD444DD'),
(5, 'EE555EE'),
(6, 'FF666FF');

-- 9. LOCALITÀ DEI VEICOLI (usiamo riferita invece di comprende)
INSERT INTO riferita (id_inserzione, id_localita) VALUES
(1, 1), (2, 2), (3, 1), (4, 1), (5, 2), (6, 2);

-- 10. COINVOLGIMENTO PROPRIETARI (usiamo crea come relazione)
INSERT INTO crea (id_utente, id_inserzione) VALUES
(3, 1), (3, 3), (3, 4),
(5, 2), (5, 5), (5, 6);

-- 11. LUOGHI
INSERT INTO luogo (id_luogo, nome, tipologia, ritiro, deposito) VALUES
(1, 'Stazione Termini', 'ritiro', TRUE, FALSE),
(2, 'Aeroporto Linate', 'deposito', FALSE, TRUE);

-- 12. PERIODI
INSERT INTO periodo (id_periodo, dataInizioPeriodo, dataFinePeriodo) VALUES
(1, '2025-06-01', '2025-06-15'),
(2, '2025-07-01', '2025-07-10');

-- 13. TARIFFARIO
INSERT INTO tariffario (id_tariffario, sconto) VALUES
(1, 0.10),
(2, 0.15);

-- 14. ASSOCIAZIONE TARIFFARIO-PERIODO
INSERT INTO determina_tariffa (id_tariffario, id_periodo) VALUES
(1, 1),
(2, 2);

-- 15. PAGAMENTI
INSERT INTO pagamento (id_pagamento, data, importo, metodo_pagamento, stato_pagamento) VALUES
(1, '2025-06-01', 250.00, 'carta_credito', 'completato'),
(2, '2025-07-01', 180.00, 'paypal', 'completato'),
(3, '2025-06-05', 300.00, 'carta_credito', 'completato'),
(4, '2025-07-02', 400.00, 'bonifico', 'in_attesa');

-- 16. NOLEGGI
INSERT INTO noleggio (id_noleggio, data_inizio, data_fine, id_cliente, id_proprietario, id_veicolo, id_pagamento) VALUES
(1, '2025-06-01', '2025-06-10', 2, 3, 'AA111AA', 1),
(2, '2025-07-01', '2025-07-05', 4, 5, 'BB222BB', 2),
(3, '2025-06-05', '2025-06-12', 2, 3, 'CC333CC', 3),
(4, '2025-07-02', '2025-07-09', 4, 3, 'DD444DD', 4);

-- 17. RELAZIONI aggiuntive
INSERT INTO deposita (id_luogo, id_noleggio) VALUES (1, 1), (2, 2), (1, 3), (2, 4);
INSERT INTO sostenuto (id_noleggio, id_periodo) VALUES (1, 1), (2, 2), (3, 1), (4, 2);

-- 18. SINISTRI
INSERT INTO sinistro (id_sinistro, data, descrizione, costo) VALUES
(1, '2025-06-08', 'Piccolo graffio sulla portiera', 50.00),
(2, '2025-06-10', 'Trequarti anteriore graffiato', 150.00),
(3, '2025-07-05', 'Problema al sistema frenante', 300.00);

INSERT INTO avviene_durante (id_noleggio, id_sinistro) VALUES
(1, 1), (3, 2), (4, 3);

-- 19. RECENSIONI
INSERT INTO recensione (id_recensione, voto, commento, data_recensione) VALUES
(1, 5, 'Servizio eccellente, auto pulita e affidabile!', '2025-06-12'),
(2, 4, 'Buon servizio, auto comoda e pulita.', '2025-07-06'),
(3, 3, 'Auto in buone condizioni, ma un po'' rumorosa.', '2025-06-15'),
(4, 5, 'Esperienza fantastica, tornerò a noleggiare qui!', '2025-07-12');

INSERT INTO riguardo (id_recensione, id_noleggio) VALUES
(1, 1), (2, 2), (3, 3), (4, 4);

-- 20. ACCESSORI
INSERT INTO accessorio (id_accessorio, nome, descrizione) VALUES
(1, 'GPS', 'Navigatore satellitare'),
(2, 'Seggiolino bambini', 'Adatto a bambini fino a 4 anni');

INSERT INTO accessori_inclusi (id_accessorio) VALUES (1);
INSERT INTO accessori_extra (id_accessorio, prezzo) VALUES (2, 10.00);
