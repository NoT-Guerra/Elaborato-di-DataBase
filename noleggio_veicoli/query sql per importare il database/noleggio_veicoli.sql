CREATE DATABASE IF NOT EXISTS `noleggio_veicoli`
/*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci */
/*!80016 */
USE `noleggio_veicoli`;

CREATE TABLE `accessori_extra` (
  `id_accessorio` int DEFAULT NULL,
  `prezzo` decimal(10, 2) DEFAULT NULL,
  KEY `id_accessorio` (`id_accessorio`),
  CONSTRAINT `accessori_extra_ibfk_1` FOREIGN KEY (`id_accessorio`) REFERENCES `accessorio` (`id_accessorio`)
);

CREATE TABLE `accessori_inclusi` (
  `id_accessorio` int NOT NULL,
  PRIMARY KEY (`id_accessorio`),
  CONSTRAINT `accessori_inclusi_ibfk_1` FOREIGN KEY (`id_accessorio`) REFERENCES `accessorio` (`id_accessorio`)
);

CREATE TABLE `accessorio` (
  `id_accessorio` int NOT NULL,
  `nome` varchar(50) DEFAULT NULL,
  `descrizione` text,
  PRIMARY KEY (`id_accessorio`)
);

CREATE TABLE `avviene_durante` (
  `id_noleggio` int NOT NULL,
  `id_sinistro` int NOT NULL,
  PRIMARY KEY (`id_noleggio`, `id_sinistro`),
  KEY `id_sinistro` (`id_sinistro`),
  CONSTRAINT `avviene_durante_ibfk_1` FOREIGN KEY (`id_noleggio`) REFERENCES `noleggio` (`id_noleggio`),
  CONSTRAINT `avviene_durante_ibfk_2` FOREIGN KEY (`id_sinistro`) REFERENCES `sinistro` (`id_sinistro`)
);

CREATE TABLE `categoria_veicolo` (
  `id_categoria` int NOT NULL,
  `nome_categoria` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_categoria`)
);

CREATE TABLE `cliente` (
  `id_utente` int NOT NULL,
  PRIMARY KEY (`id_utente`),
  CONSTRAINT `cliente_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id_utente`)
);

CREATE TABLE `coinvolge` (
  `targa` varchar(7) NOT NULL,
  `id_noleggio` int NOT NULL,
  PRIMARY KEY (`id_noleggio`, `targa`),
  KEY `coinvolge_ibfk_2` (`targa`),
  CONSTRAINT `coinvolge_ibfk_2` FOREIGN KEY (`targa`) REFERENCES `veicolo` (`targa`)
);

CREATE TABLE `comprende` (
  `id_inserzione` int NOT NULL,
  `id_accessorio` int NOT NULL,
  PRIMARY KEY (`id_inserzione`, `id_accessorio`),
  KEY `fk_accessorio` (`id_accessorio`),
  CONSTRAINT `fk_accessorio` FOREIGN KEY (`id_accessorio`) REFERENCES `accessorio` (`id_accessorio`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_inserzione` FOREIGN KEY (`id_inserzione`) REFERENCES `inserzione` (`id_inserzione`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `crea` (
  `id_utente` int NOT NULL,
  `id_inserzione` int NOT NULL,
  PRIMARY KEY (`id_utente`, `id_inserzione`),
  KEY `id_inserzione` (`id_inserzione`),
  CONSTRAINT `crea_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `proprietario` (`id_utente`) ON DELETE CASCADE,
  CONSTRAINT `crea_ibfk_2` FOREIGN KEY (`id_inserzione`) REFERENCES `inserzione` (`id_inserzione`) ON DELETE CASCADE
);

CREATE TABLE `deposita` (
  `id_luogo` int NOT NULL,
  `id_noleggio` int NOT NULL,
  PRIMARY KEY (`id_luogo`, `id_noleggio`),
  KEY `id_noleggio` (`id_noleggio`),
  CONSTRAINT `deposita_ibfk_1` FOREIGN KEY (`id_luogo`) REFERENCES `luogo` (`id_luogo`),
  CONSTRAINT `deposita_ibfk_2` FOREIGN KEY (`id_noleggio`) REFERENCES `noleggio` (`id_noleggio`)
);

CREATE TABLE `determina_tariffa` (
  `id_tariffario` int NOT NULL,
  `id_periodo` int NOT NULL,
  PRIMARY KEY (`id_tariffario`, `id_periodo`),
  KEY `id_periodo` (`id_periodo`),
  CONSTRAINT `determina_tariffa_ibfk_1` FOREIGN KEY (`id_tariffario`) REFERENCES `tariffario` (`id_tariffario`),
  CONSTRAINT `determina_tariffa_ibfk_2` FOREIGN KEY (`id_periodo`) REFERENCES `periodo` (`id_periodo`)
);

CREATE TABLE `indirizzo` (
  `id_indirizzo` int NOT NULL,
  `via` varchar(100) DEFAULT NULL,
  `cap` varchar(10) DEFAULT NULL,
  `città` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `nazione` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_indirizzo`)
);

CREATE TABLE `inserzione` (
  `id_inserzione` int NOT NULL,
  `descrizione` text,
  `id_utente` int DEFAULT NULL,
  `prezzo_giornaliero` decimal(10, 2) NOT NULL DEFAULT '0.00',
  PRIMARY KEY (`id_inserzione`),
  KEY `id_utente` (`id_utente`),
  CONSTRAINT `inserzione_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `proprietario` (`id_utente`)
);

CREATE TABLE `localita` (
  `id_località` int NOT NULL,
  `città` varchar(50) DEFAULT NULL,
  `provincia` varchar(50) DEFAULT NULL,
  `regione` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_località`)
);

CREATE TABLE `luogo` (
  `id_luogo` int NOT NULL,
  `nome` varchar(100) DEFAULT NULL,
  `tipologia` varchar(50) DEFAULT NULL,
  `ritiro` tinyint(1) DEFAULT NULL,
  `deposito` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id_luogo`)
);

CREATE TABLE `manutenzione` (
  `id_manutenzione` int NOT NULL,
  `data` date DEFAULT NULL,
  `tipo` varchar(50) DEFAULT NULL,
  `costo` decimal(10, 2) DEFAULT NULL,
  `descrizione` text,
  `targa` varchar(7) DEFAULT NULL,
  PRIMARY KEY (`id_manutenzione`),
  KEY `targa` (`targa`),
  CONSTRAINT `manutenzione_ibfk_1` FOREIGN KEY (`targa`) REFERENCES `veicolo` (`targa`)
);

CREATE TABLE `noleggio` (
  `id_noleggio` int NOT NULL,
  `data_inizio` date DEFAULT NULL,
  `data_fine` date DEFAULT NULL,
  `id_cliente` int DEFAULT NULL,
  `id_proprietario` int DEFAULT NULL,
  `id_veicolo` varchar(7) DEFAULT NULL,
  `id_pagamento` int DEFAULT NULL,
  PRIMARY KEY (`id_noleggio`),
  KEY `id_cliente` (`id_cliente`),
  KEY `id_proprietario` (`id_proprietario`),
  KEY `id_veicolo` (`id_veicolo`),
  CONSTRAINT `noleggio_ibfk_1` FOREIGN KEY (`id_cliente`) REFERENCES `cliente` (`id_utente`),
  CONSTRAINT `noleggio_ibfk_2` FOREIGN KEY (`id_proprietario`) REFERENCES `proprietario` (`id_utente`),
  CONSTRAINT `noleggio_ibfk_3` FOREIGN KEY (`id_veicolo`) REFERENCES `veicolo` (`targa`)
);

CREATE TABLE `pagamento` (
  `id_pagamento` int NOT NULL,
  `data` date DEFAULT NULL,
  `importo` decimal(10, 2) DEFAULT NULL,
  `metodo_pagamento` varchar(50) DEFAULT NULL,
  `stato_pagamento` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id_pagamento`)
);

CREATE TABLE `periodo` (
  `id_periodo` int NOT NULL,
  `dataInizioPeriodo` varchar(5) DEFAULT NULL,
  `dataFinePeriodo` varchar(5) DEFAULT NULL,
  PRIMARY KEY (`id_periodo`)
);


CREATE TABLE `possiede` (
  `id_utente` int NOT NULL,
  `id_indirizzo` int NOT NULL,
  PRIMARY KEY (`id_utente`, `id_indirizzo`),
  KEY `id_indirizzo` (`id_indirizzo`),
  CONSTRAINT `possiede_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id_utente`) ON DELETE CASCADE,
  CONSTRAINT `possiede_ibfk_2` FOREIGN KEY (`id_indirizzo`) REFERENCES `indirizzo` (`id_indirizzo`) ON DELETE CASCADE
);

CREATE TABLE `proprietario` (
  `id_utente` int NOT NULL,
  PRIMARY KEY (`id_utente`),
  CONSTRAINT `proprietario_ibfk_1` FOREIGN KEY (`id_utente`) REFERENCES `utente` (`id_utente`)
);

CREATE TABLE `recensione` (
  `id_recensione` int NOT NULL,
  `voto` int DEFAULT NULL,
  `commento` text,
  `data_recensione` date DEFAULT NULL,
  PRIMARY KEY (`id_recensione`)
);

CREATE TABLE `riferita` (
  `id_inserzione` int NOT NULL,
  `id_localita` int NOT NULL,
  PRIMARY KEY (`id_inserzione`, `id_localita`),
  KEY `id_localita` (`id_localita`),
  CONSTRAINT `riferita_ibfk_1` FOREIGN KEY (`id_inserzione`) REFERENCES `inserzione` (`id_inserzione`),
  CONSTRAINT `riferita_ibfk_2` FOREIGN KEY (`id_localita`) REFERENCES `localita` (`id_località`)
);

CREATE TABLE `riguarda` (
  `id_inserzione` int DEFAULT NULL,
  `targa` varchar(7) DEFAULT NULL,
  KEY `id_inserzione` (`id_inserzione`),
  KEY `targa` (`targa`),
  CONSTRAINT `riguarda_ibfk_1` FOREIGN KEY (`id_inserzione`) REFERENCES `inserzione` (`id_inserzione`),
  CONSTRAINT `riguarda_ibfk_2` FOREIGN KEY (`targa`) REFERENCES `veicolo` (`targa`)
);

CREATE TABLE `riguardo` (
  `id_recensione` int NOT NULL,
  `id_noleggio` int NOT NULL,
  PRIMARY KEY (`id_recensione`, `id_noleggio`),
  KEY `id_noleggio` (`id_noleggio`),
  CONSTRAINT `riguardo_ibfk_1` FOREIGN KEY (`id_recensione`) REFERENCES `recensione` (`id_recensione`),
  CONSTRAINT `riguardo_ibfk_2` FOREIGN KEY (`id_noleggio`) REFERENCES `noleggio` (`id_noleggio`)
);

CREATE TABLE `saldato` (
  `id_noleggio` int NOT NULL,
  `id_pagamento` int NOT NULL,
  PRIMARY KEY (`id_noleggio`, `id_pagamento`),
  KEY `fk_saldato_pagamento` (`id_pagamento`),
  CONSTRAINT `fk_saldato_noleggio` FOREIGN KEY (`id_noleggio`) REFERENCES `noleggio` (`id_noleggio`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `fk_saldato_pagamento` FOREIGN KEY (`id_pagamento`) REFERENCES `pagamento` (`id_pagamento`) ON DELETE CASCADE ON UPDATE CASCADE
);

CREATE TABLE `sinistro` (
  `id_sinistro` int NOT NULL,
  `data` date DEFAULT NULL,
  `descrizione` text,
  `costo` decimal(10, 2) DEFAULT NULL,
  PRIMARY KEY (`id_sinistro`)
);

CREATE TABLE `sostenuto` (
  `id_noleggio` int NOT NULL,
  `id_periodo` int NOT NULL,
  PRIMARY KEY (`id_noleggio`, `id_periodo`),
  KEY `id_periodo` (`id_periodo`),
  CONSTRAINT `sostenuto_ibfk_1` FOREIGN KEY (`id_noleggio`) REFERENCES `noleggio` (`id_noleggio`),
  CONSTRAINT `sostenuto_ibfk_2` FOREIGN KEY (`id_periodo`) REFERENCES `periodo` (`id_periodo`)
);

CREATE TABLE `tariffario` (
  `id_tariffario` int NOT NULL,
  `sconto` decimal(5, 2) DEFAULT NULL,
  PRIMARY KEY (`id_tariffario`)
);

CREATE TABLE `utente` (
  `id_utente` int NOT NULL AUTO_INCREMENT,
  `nome` varchar(50) DEFAULT NULL,
  `cognome` varchar(50) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `password` varchar(100) DEFAULT NULL,
  `stato_account` tinyint(1) DEFAULT NULL,
  `id_indirizzo` int DEFAULT NULL,
  PRIMARY KEY (`id_utente`),
  KEY `id_indirizzo` (`id_indirizzo`),
  CONSTRAINT `utente_ibfk_1` FOREIGN KEY (`id_indirizzo`) REFERENCES `indirizzo` (`id_indirizzo`)
);

CREATE TABLE `veicolo` (
  `targa` varchar(7) NOT NULL,
  `peso` decimal(10, 2) DEFAULT NULL,
  `marca` varchar(50) DEFAULT NULL,
  `modello` varchar(50) DEFAULT NULL,
  `anno_immatricolazione` int DEFAULT NULL,
  `tipologia_carburante` varchar(50) DEFAULT NULL,
  `potenza` decimal(10, 2) DEFAULT NULL,
  `id_categoria` int DEFAULT NULL,
  PRIMARY KEY (`targa`),
  KEY `id_categoria` (`id_categoria`),
  CONSTRAINT `veicolo_ibfk_1` FOREIGN KEY (`id_categoria`) REFERENCES `categoria_veicolo` (`id_categoria`)
);