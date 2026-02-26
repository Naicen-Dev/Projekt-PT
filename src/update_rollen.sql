-- ============================================================
--  Datenbankupdate: Rollensystem
--  Ausführen in phpMyAdmin oder via:
--  /opt/lampp/bin/mysql -u root bibliothek < src/update_rollen.sql
-- ============================================================

USE bibliothek;

-- 1) Spalte 'rolle' zur benutzer-Tabelle hinzufügen
ALTER TABLE `benutzer`
  ADD COLUMN `rolle` ENUM('user', 'admin') NOT NULL DEFAULT 'user'
  AFTER `email`;

-- 2) Lisa Weber zur Admin ernennen
UPDATE `benutzer`
SET `rolle` = 'admin'
WHERE `vorname` = 'Lisa' AND `nachname` = 'Weber';

-- 3) Ausleihe-Tabelle: frist_bis und rueckgabe_am sicherstellen
-- (Falls die Tabelle noch die alten Spalten rueckgabedatum hat)
ALTER TABLE `ausleihe`
  ADD COLUMN IF NOT EXISTS `frist_bis`    DATE         NULL AFTER `ausleihdatum`,
  ADD COLUMN IF NOT EXISTS `rueckgabe_am` DATETIME     NULL AFTER `frist_bis`;

-- Bestehende rueckgabedatum-Werte in frist_bis übertragen (falls vorhanden)
UPDATE `ausleihe` SET `frist_bis` = `rueckgabedatum` WHERE `frist_bis` IS NULL AND `rueckgabedatum` IS NOT NULL;

-- Kontrolle
SELECT benutzer_id, vorname, nachname, email, rolle FROM benutzer;
DESCRIBE ausleihe;
