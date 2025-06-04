CREATE DATABASE noleggio_veicoli;
USE noleggio_veicoli;

CREATE TABLE UTENTE (
    id_utente INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(50) NOT NULL,
    cognome VARCHAR(50) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(100) NOT NULL,
    stato_account BOOLEAN NOT NULL DEFAULT 1
);

CREATE TABLE CLIENTE (
    id_utente INT PRIMARY KEY,
    FOREIGN KEY (id_utente) REFERENCES UTENTE(id_utente) ON DELETE CASCADE
);

CREATE TABLE PROPRIETARIO (
    id_utente INT PRIMARY KEY,
    FOREIGN KEY (id_utente) REFERENCES UTENTE(id_utente) ON DELETE CASCADE
);

CREATE TABLE INDIRIZZO (
    id_indirizzo INT PRIMARY KEY AUTO_INCREMENT,
    via VARCHAR(100),
    cap VARCHAR(10),
    citta VARCHAR(50),
    provincia VARCHAR(50),
    nazione VARCHAR(50),
    id_utente INT,
    FOREIGN KEY (id_utente) REFERENCES UTENTE(id_utente) ON DELETE CASCADE
);

CREATE TABLE LOCALITA (
    id_localita INT PRIMARY KEY AUTO_INCREMENT,
    citta VARCHAR(50) NOT NULL,
    provincia VARCHAR(50) NOT NULL,
    regione VARCHAR(50) NOT NULL
);

CREATE TABLE INSERZIONE (
    id_inserzione INT PRIMARY KEY AUTO_INCREMENT,
    descrizione TEXT,
    prezzo_giornaliero DECIMAL(10,2) NOT NULL,
    id_localita INT NOT NULL,
    id_utente INT NOT NULL,
    FOREIGN KEY (id_localita) REFERENCES LOCALITA(id_localita) ON DELETE CASCADE,
    FOREIGN KEY (id_utente) REFERENCES UTENTE(id_utente) ON DELETE CASCADE
);

CREATE TABLE CATEGORIA_VEICOLO (
    id_categoria INT PRIMARY KEY AUTO_INCREMENT,
    nome_categoria VARCHAR(50) NOT NULL
);

CREATE TABLE VEICOLO (
    targa VARCHAR(20) PRIMARY KEY,
    peso DECIMAL(10,2),
    marca VARCHAR(50),
    modello VARCHAR(50),
    anno_immatricolazione INT,
    tipologia_carburante VARCHAR(30),
    potenza DECIMAL(10,2),
    id_inserzione INT,
    id_categoria INT,
    FOREIGN KEY (id_inserzione) REFERENCES INSERZIONE(id_inserzione) ON DELETE CASCADE,
    FOREIGN KEY (id_categoria) REFERENCES CATEGORIA_VEICOLO(id_categoria) ON DELETE SET NULL
);

CREATE TABLE ACCESSORIO (
    id_accessorio INT PRIMARY KEY AUTO_INCREMENT,
    descrizione TEXT,
    nome VARCHAR(50),
    id_inserzione INT,
    FOREIGN KEY (id_inserzione) REFERENCES INSERZIONE(id_inserzione) ON DELETE CASCADE
);

CREATE TABLE ACCESSORI_INCLUSI (
    id_accessorio INT PRIMARY KEY,
    FOREIGN KEY (id_accessorio) REFERENCES ACCESSORIO(id_accessorio) ON DELETE CASCADE
);

CREATE TABLE ACCESSORI_EXTRA (
    id_accessorio INT PRIMARY KEY,
    prezzo DECIMAL(10,2),
    FOREIGN KEY (id_accessorio) REFERENCES ACCESSORIO(id_accessorio) ON DELETE CASCADE
);

CREATE TABLE PERIODO (
    id_periodo INT PRIMARY KEY AUTO_INCREMENT,
    dataInizioPeriodo DATE NOT NULL,
    dataFinePeriodo DATE NOT NULL
);

CREATE TABLE TARIFFARIO (
    id_tariffario INT PRIMARY KEY AUTO_INCREMENT,
    id_periodo INT NOT NULL,
    sconto DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    FOREIGN KEY (id_periodo) REFERENCES PERIODO(id_periodo) ON DELETE CASCADE
);

CREATE TABLE LUOGO (
    id_luogo INT PRIMARY KEY AUTO_INCREMENT,
    nome VARCHAR(100) NOT NULL,
    tip_ritiro BOOLEAN NOT NULL DEFAULT FALSE,
    tip_deposito BOOLEAN NOT NULL DEFAULT FALSE
);

CREATE TABLE NOLEGGIO (
    id_noleggio INT PRIMARY KEY AUTO_INCREMENT,
    data_inizio DATE NOT NULL,
    data_fine DATE NOT NULL,
    id_periodo INT NOT NULL,
    id_utente INT NOT NULL,
    id_luogo INT NOT NULL,
    targa VARCHAR(20) NOT NULL,
    FOREIGN KEY (id_periodo) REFERENCES PERIODO(id_periodo) ON DELETE CASCADE,
    FOREIGN KEY (id_utente) REFERENCES UTENTE(id_utente) ON DELETE CASCADE,
    FOREIGN KEY (id_luogo) REFERENCES LUOGO(id_luogo) ON DELETE CASCADE,
    FOREIGN KEY (targa) REFERENCES VEICOLO(targa) ON DELETE CASCADE
);

CREATE TABLE PAGAMENTO (
    id_pagamento INT PRIMARY KEY AUTO_INCREMENT,
    id_noleggio INT NOT NULL,
    data DATE NOT NULL,
    importo DECIMAL(10,2) NOT NULL,
    metodo_pagamento VARCHAR(50) NOT NULL,
    stato_pagamento VARCHAR(50) NOT NULL,
    FOREIGN KEY (id_noleggio) REFERENCES NOLEGGIO(id_noleggio) ON DELETE CASCADE
);

CREATE TABLE RECENSIONE (
    id_recensione INT PRIMARY KEY AUTO_INCREMENT,
    voto INT NOT NULL,
    commento TEXT,
    data_recensione DATE NOT NULL,
    id_noleggio INT NOT NULL,
    FOREIGN KEY (id_noleggio) REFERENCES NOLEGGIO(id_noleggio) ON DELETE CASCADE
);

CREATE TABLE SINISTRO (
    id_sinistro INT PRIMARY KEY AUTO_INCREMENT,
    data DATE NOT NULL,
    descrizione TEXT,
    costo DECIMAL(10,2),
    id_noleggio INT NOT NULL,
    FOREIGN KEY (id_noleggio) REFERENCES NOLEGGIO(id_noleggio) ON DELETE CASCADE
);

CREATE TABLE MANUTENZIONE (
    id_manutenzione INT PRIMARY KEY AUTO_INCREMENT,
    data DATE NOT NULL,
    tipo VARCHAR(50),
    costo DECIMAL(10,2),
    descrizione TEXT,
    targa VARCHAR(20),
    FOREIGN KEY (targa) REFERENCES VEICOLO(targa) ON DELETE CASCADE
);
