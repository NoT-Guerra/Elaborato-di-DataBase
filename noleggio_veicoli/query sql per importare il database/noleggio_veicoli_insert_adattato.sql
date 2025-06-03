USE noleggio_veicoli;

-- 1. UTENTE
INSERT INTO UTENTE (nome, cognome, email, password, stato_account) VALUES
('Mario', 'Rossi', 'mario.rossi@email.it', 'pwd1', 1),
('Luigi', 'Verdi', 'luigi.verdi@email.it', 'pwd2', 1),
('Anna', 'Bianchi', 'anna.bianchi@email.it', 'pwd3', 1),
('Giorgio', 'Neri', 'giorgio.neri@email.it', 'pwd4', 1),
('Lucia', 'Blu', 'lucia.blu@email.it', 'pwd5', 1);

-- 2. CLIENTE
INSERT INTO CLIENTE (id_utente) VALUES (1), (2), (3), (4), (5);

-- 3. PROPRIETARIO
-- Per esempio proprietari sono 4 e 5 (ma qui inserisco 2 e 3 per variabilità)
INSERT INTO PROPRIETARIO (id_utente) VALUES (4), (5);

-- 4. INDIRIZZO
INSERT INTO INDIRIZZO (via, cap, citta, provincia, nazione, id_utente) VALUES
('Via Roma 10', '00100', 'Roma', 'RM', 'Italia', 1),
('Via Milano 22', '20100', 'Milano', 'MI', 'Italia', 2),
('Via Firenze 5', '50100', 'Firenze', 'FI', 'Italia', 3),
('Via Torino 77', '10100', 'Torino', 'TO', 'Italia', 4),
('Via Napoli 33', '80100', 'Napoli', 'NA', 'Italia', 5);

-- 5. LOCALITA
INSERT INTO LOCALITA (citta, provincia, regione) VALUES
('Roma', 'RM', 'Lazio'),
('Milano', 'MI', 'Lombardia'),
('Firenze', 'FI', 'Toscana'),
('Torino', 'TO', 'Piemonte'),
('Napoli', 'NA', 'Campania');

-- 6. CATEGORIA_VEICOLO
INSERT INTO CATEGORIA_VEICOLO (nome_categoria) VALUES
('Utilitaria'), ('SUV'), ('Elettrica'), ('Furgone'), ('Moto');

-- 7. INSERZIONE
INSERT INTO INSERZIONE (descrizione, prezzo_giornaliero, id_localita, id_utente) VALUES
('Fiat Panda economica', 30.00, 1, 4),
('Tesla Model 3', 90.00, 2, 5),
('Renault Kangoo furgone', 50.00, 3, 4),
('BMW X3 SUV', 70.00, 4, 5),
('Volkswagen Golf a metano', 45.00, 5, 4);

-- 8. VEICOLO
INSERT INTO VEICOLO (targa, peso, marca, modello, anno_immatricolazione, tipologia_carburante, potenza, id_inserzione, id_categoria) VALUES
('AB123CD', 950.00, 'Fiat', 'Panda', 2020, 'benzina', 69, 1, 1),
('CD456EF', 1600.00, 'Tesla', 'Model 3', 2022, 'elettrico', 200, 2, 3),
('GH789IJ', 1800.00, 'Renault', 'Kangoo', 2019, 'diesel', 90, 3, 4),
('LM123NO', 2000.00, 'BMW', 'X3', 2021, 'diesel', 190, 4, 2),
('VW789MT', 1300.00, 'Volkswagen', 'Golf', 2021, 'metano', 85, 5, 1);

-- 9. ACCESSORIO
INSERT INTO ACCESSORIO (descrizione, nome, id_inserzione) VALUES
('GPS integrato', 'GPS', 1),
('Tetto panoramico', 'Tetto', 2),
('Carico posteriore', 'Bagagliaio', 3),
('Sedili riscaldati', 'Sedili caldi', 4),
('Sensori di parcheggio', 'Parcheggio', 5);

-- 10. ACCESSORI_INCLUSI
INSERT INTO ACCESSORI_INCLUSI (id_accessorio) VALUES (1), (3);

-- 11. ACCESSORI_EXTRA
INSERT INTO ACCESSORI_EXTRA (id_accessorio, prezzo) VALUES
(2, 12.00),
(4, 17.50),
(5, 20.00);

-- 12. PERIODO
INSERT INTO PERIODO (dataInizioPeriodo, dataFinePeriodo) VALUES
('01-01', '02-28'),
('03-01', '04-30'),
('05-01', '06-30'),
('07-01', '08-31'),
('09-01', '10-31'),
('11-01', '12-31');

INSERT INTO TARIFFARIO (id_periodo, sconto) VALUES
(1, 0.00),
(2, 5.00),
(3, 0.00),
(4, 10.00),
(5, 5.00),
(6, 0.00);

-- 14. LUOGO
INSERT INTO LUOGO (nome, tip_ritiro, tip_deposito) VALUES
('Roma Centro', TRUE, TRUE),
('Milano Nord', TRUE, FALSE),
('Firenze Stazione', FALSE, TRUE),
('Torino Aeroporto', TRUE, TRUE),
('Napoli Porto', FALSE, TRUE);

-- 15. NOLEGGIO
INSERT INTO NOLEGGIO (data_inizio, data_fine, id_periodo, id_utente, id_luogo, targa) VALUES
('2025-06-01', '2025-06-07', 1, 1, 1, 'AB123CD'),
('2025-06-08', '2025-06-14', 2, 2, 2, 'CD456EF'),
('2025-06-15', '2025-06-21', 3, 3, 3, 'GH789IJ'),
('2025-06-22', '2025-06-28', 4, 4, 4, 'LM123NO'),
('2025-07-01', '2025-07-07', 5, 5, 5, 'VW789MT');

-- 16. PAGAMENTO
INSERT INTO PAGAMENTO (id_noleggio, data, importo, metodo_pagamento, stato_pagamento) VALUES
(1, '2025-06-01', 210.00, 'Carta di credito', 'Completato'),
(2, '2025-06-08', 630.00, 'PayPal', 'Completato'),
(3, '2025-06-15', 350.00, 'Carta di credito', 'Completato'),
(4, '2025-06-22', 490.00, 'Bonifico', 'In attesa'),
(5, '2025-07-01', 280.00, 'Contanti', 'Completato');

-- 17. RECENSIONE
INSERT INTO RECENSIONE (voto, commento, data_recensione, id_noleggio) VALUES
(5, 'Ottimo servizio!', '2025-06-08', 1),
(4, 'Veicolo pulito e affidabile.', '2025-06-15', 2),
(3, 'Noleggio regolare.', '2025-06-22', 3),
(2, 'Ottimo veicolo, assistenza un pelo meno.', '2025-06-29', 4),
(5, 'Esperienza fantastica!', '2025-07-08', 5);

-- 18. SINISTRO
INSERT INTO SINISTRO (data, descrizione, costo, id_noleggio) VALUES
('2025-06-03', 'Danno al paraurti', 150.00, 1),
('2025-06-10', 'Finestrino rotto', 200.00, 2),
('2025-06-17', 'Gomme forate', 100.00, 3),
('2025-06-24', 'Graffi alla carrozzeria', 80.00, 4),
('2025-07-04', 'Danno al fanale', 90.00, 5);

-- 19. MANUTENZIONE
INSERT INTO MANUTENZIONE (data, tipo, costo, descrizione, targa) VALUES
('2025-05-01', 'Tagliando', 120.00, 'Controllo generale', 'AB123CD'),
('2025-05-02', 'Cambio olio', 60.00, 'Olio motore', 'CD456EF'),
('2025-05-03', 'Gomme nuove', 250.00, 'Sostituzione gomme', 'GH789IJ'),
('2025-05-04', 'Freni', 180.00, 'Sostituzione freni', 'LM123NO'),
('2025-05-05', 'Batteria', 90.00, 'Batteria nuova', 'VW789MT');
