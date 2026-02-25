-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Erstellungszeit: 23. Feb 2026 um 14:03
-- Server-Version: 10.4.32-MariaDB
-- PHP-Version: 8.1.25

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Datenbank: `bibliothek`
--

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `autor`
--

CREATE TABLE `autor` (
  `autor_id` int(11) NOT NULL,
  `vorname` text NOT NULL,
  `nachname` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `autor`
--

INSERT INTO `autor` (`autor_id`, `vorname`, `nachname`) VALUES
(1, 'Max', 'Mustermann'),
(2, 'Anna', 'Schmidt');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `benutzer`
--

CREATE TABLE `benutzer` (
  `benutzer_id` int(11) NOT NULL,
  `vorname` text NOT NULL,
  `nachname` text NOT NULL,
  `email` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `benutzer`
--

INSERT INTO `benutzer` (`benutzer_id`, `vorname`, `nachname`, `email`) VALUES
(1, 'Lisa', 'Weber', 'list.weber@mail.de'),
(2, 'Jonas', 'Becker', 'jonas.becker@mail.de');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `exemplar`
--

CREATE TABLE `exemplar` (
  `exemplar_id` int(11) NOT NULL,
  `inventarnummer` text NOT NULL,
  `status` tinyint(1) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `exemplar`
--

INSERT INTO `exemplar` (`exemplar_id`, `inventarnummer`, `status`) VALUES
(1, 'BIB-001', 1),
(2, 'BIB-002', 0);

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `medium`
--

CREATE TABLE `medium` (
  `medium_id` int(11) NOT NULL,
  `ISBN` char(15) NOT NULL,
  `titel` text NOT NULL,
  `genre` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `medium`
--

INSERT INTO `medium` (`medium_id`, `ISBN`, `titel`, `genre`) VALUES
(1, '978-3-12345-001', 'Datenbank Grundlagen', 'Informatik'),
(2, '978-3-12345-002', 'Der große Roman', 'Belletristik');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `rolle`
--

CREATE TABLE `rolle` (
  `rolle_id` int(11) NOT NULL,
  `rollenname` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `rolle`
--

INSERT INTO `rolle` (`rolle_id`, `rollenname`) VALUES
(1, 'Kunde'),
(2, 'Admin');

-- --------------------------------------------------------

--
-- Tabellenstruktur für Tabelle `standort`
--

CREATE TABLE `standort` (
  `standort_id` int(11) NOT NULL,
  `name` text NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Daten für Tabelle `standort`
--

INSERT INTO `standort` (`standort_id`, `name`) VALUES
(1, 'Lesesaal'),
(2, 'Magazin');

--
-- Indizes der exportierten Tabellen
--

--
-- Indizes für die Tabelle `autor`
--
ALTER TABLE `autor`
  ADD PRIMARY KEY (`autor_id`);

--
-- Indizes für die Tabelle `benutzer`
--
ALTER TABLE `benutzer`
  ADD PRIMARY KEY (`benutzer_id`);

--
-- Indizes für die Tabelle `exemplar`
--
ALTER TABLE `exemplar`
  ADD PRIMARY KEY (`exemplar_id`);

--
-- Indizes für die Tabelle `medium`
--
ALTER TABLE `medium`
  ADD PRIMARY KEY (`ISBN`);

--
-- Indizes für die Tabelle `rolle`
--
ALTER TABLE `rolle`
  ADD PRIMARY KEY (`rolle_id`);

--
-- Indizes für die Tabelle `standort`
--
ALTER TABLE `standort`
  ADD PRIMARY KEY (`standort_id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;