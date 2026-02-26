# 📚 Stadtbibliothek Buxtehude – Bibliotheksverwaltungssystem

Willkommen im Repository der **Stadtbibliothek Buxtehude**. Dieses Projekt ist ein funktionaler Web-Prototyp für ein modernes Bibliotheksverwaltungssystem, basierend auf PHP und MariaDB/MySQL.

## 🚀 Funktionen

### Für Nutzer
*   **Katalogsuche mit Autocomplete**: Dynamisches Suchfeld (AJAX) für Titel, Autoren oder ISBN mit Live-Vorschlägen.
*   **Medien ausleihen**: Einfacher Ausleihprozess mit automatischer Fristberechnung (+14 Tage).
*   **Mein Konto / Rückgabe**: Übersicht der eigenen Ausleihen und Möglichkeit zur schnellen Rückgabe.
*   **Statistiken & Neuheiten**: Die Startseite zeigt automatisch die neuesten Medien sowie Statistiken zum Bestand.

### Für Administratoren
*   **Rollen-System**: Unterscheidung zwischen normalen Nutzern und Administratoren.
*   **Bestandsverwaltung**: Hinzufügen neuer Medienstämme inklusive Angabe der Exemplar-Anzahl.
*   **Exemplar-Verwaltung**: Detailansicht für jedes Medium zum Bearbeiten von Inventarnummern, manuellem Status-Update oder Hinzufügen einzelner Kopien.
*   **Überfälligkeits-Liste**: Übersicht aller Ausleihen, die die Frist überschritten haben, inklusive Berechnung der Verzugstage und Kontaktmöglichkeit des Lesers.

## 🛠️ Technologie-Stack
- **Backend**: PHP 8.x (reines PHP, kein Framework)
- **Datenbank**: MariaDB / MySQL (PDO für sichere Prepared Statements)
- **Frontend**: HTML5, CSS3 (Modernes Glasmorphism-Design), JavaScript (Fetch API für AJAX)
- **Icons**: Font Awesome 6.0

## 📦 Installation & Setup

1.  **Repository klonen**: In das Web-Verzeichnis (z. B. `htdocs`) herunterladen.
2.  **Datenbank importieren**:
    - Erstellen Sie eine Datenbank namens `bibliothek`.
    - Importieren Sie die Datei `src/bibliothek.sql`.
3.  **Konfiguration**:
    - Prüfen Sie die Zugangsdaten in `src/db_connect.php` (Standard: root ohne Passwort für lokale Entwicklung).
4.  **Server starten**:
    - Nutzen Sie XAMPP oder den PHP-eigenen Server:
      ```bash
      php -S localhost:8000 -t src/
      ```

## � Test-Zugang (Admin)

Um alle Funktionen (inkl. Bestandsverwaltung) zu testen, nutzen Sie beim Login:
- **E-Mail**: `lisa.weber@mail.de`
- **Name**: `Lisa Weber`

---
*Entwickelt als Teil des Projekt-PT.*
