<?php
/**
 * statistik.php – Bibliotheks-Statistik
 *
 * Zeigt eine Übersicht wichtiger Kennzahlen:
 *   - Gesamtanzahl aller Ausleihen
 *   - Anzahl der Medien im Bestand
 *   - Top-10 der meistausgeliehenen Medien
 *
 * Zugriff nur für eingeloggte Benutzer (auth.php).
 */

// Zugriffsprüfung: nur eingeloggte Benutzer
require_once 'auth.php';

// Datenbankverbindung einbinden
require_once 'db_connect.php';

// ------------------------------------------------------------------
// Datenbankabfragen für die Statistik-Kennzahlen
// ------------------------------------------------------------------

// 1. Gesamtanzahl aller Ausleihen (inkl. abgeschlossener)
$stmt1 = $pdo->query("SELECT COUNT(*) FROM ausleihe");
$gesamt_ausleihen = $stmt1->fetchColumn();

// 2. Top-10 der meistausgeliehenen Medien (Medium + Anzahl Ausleihen)
$stmt2 = $pdo->query("
    SELECT m.titel, COUNT(a.ausleihe_id) as anzahl
    FROM medium m
    JOIN exemplar e ON m.medium_id = e.medium_id
    JOIN ausleihe a ON e.exemplar_id = a.exemplar_id
    GROUP BY m.medium_id
    ORDER BY anzahl DESC
    LIMIT 10
");
$medien_statistik = $stmt2->fetchAll();

// 3. Gesamtanzahl der Medien im Bestand
$stmt3 = $pdo->query("SELECT COUNT(*) FROM medium");
$anzahl_medien = $stmt3->fetchColumn();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Statistik – Stadtbibliothek Buxtehude</title>
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Eigenes Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Chart.js für zukünftige Diagramme (bereits eingebunden) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>

<body>
    <!-- Vereinfachter Header nur mit Logo -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo"><i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude</a>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title"><i class="fas fa-chart-bar"></i> Bibliotheks-Statistik</h2>

        <!-- Kennzahlen-Kacheln nebeneinander -->
        <div style="display: flex; gap: 20px; margin-bottom: 40px;">
            <!-- Kachel 1: Gesamtanzahl Ausleihen -->
            <div
                style="flex: 1; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; text-align: center;">
                <h4 style="margin:0; opacity:0.7;">Gesamtanzahl Ausleihen</h4>
                <div style="font-size: 2.5rem; font-weight: bold;">
                    <?= $gesamt_ausleihen ?>
                </div>
            </div>
            <!-- Kachel 2: Anzahl Medien im Bestand -->
            <div
                style="flex: 1; background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; text-align: center;">
                <h4 style="margin:0; opacity:0.7;">Medien im Bestand</h4>
                <div style="font-size: 2.5rem; font-weight: bold;">
                    <?= $anzahl_medien ?>
                </div>
            </div>
        </div>

        <!-- Rangliste: Top-10 meistausgeliehene Medien -->
        <h3>Die meistausgeliehenen Medien</h3>
        <ul style="list-style: none; padding:0; margin-top: 20px;">
            <?php foreach ($medien_statistik as $m): ?>
                <!-- Jede Zeile zeigt Titel links und Ausleihanzahl rechts -->
                <li
                    style="display: flex; justify-content: space-between; padding: 10px; border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <span>
                        <?= htmlspecialchars($m['titel']) ?>
                    </span>
                    <!-- Anzahl der Ausleihen (× = Mal-Zeichen) -->
                    <span style="font-weight: bold;">
                        <?= $m['anzahl'] ?> ×
                    </span>
                </li>
            <?php endforeach; ?>
        </ul>
    </main>
</body>

</html>