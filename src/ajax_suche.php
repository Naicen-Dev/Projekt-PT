<?php
/**
 * ajax_suche.php – AJAX-Endpunkt für die Suchvorschläge (Autovervollständigung)
 *
 * Wird von index.php über einen Fetch-Request aufgerufen.
 * Gibt passende Medien und Autoren als JSON-Array zurück.
 *
 * GET-Parameter:
 *   q (string) – Der Suchbegriff (mindestens 2 Zeichen)
 */

// Datenbankverbindung einbinden
require_once 'db_connect.php';

// Suchbegriff aus GET-Parameter auslesen, Standardwert: leer
$query = $_GET['q'] ?? '';

// Mindestlänge prüfen – bei weniger als 2 Zeichen leere Liste zurückgeben
if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

// Ergebnis-Array für alle Treffer
$results = [];

// ------------------------------------------------------------------
// 1. Mediensuche: nach Titel oder ISBN (max. 5 Treffer)
// ------------------------------------------------------------------
$stmt_medien = $pdo->prepare("SELECT medium_id, titel, ISBN FROM medium WHERE titel LIKE :q1 OR ISBN LIKE :q2 LIMIT 5");
$stmt_medien->execute(['q1' => '%' . $query . '%', 'q2' => '%' . $query . '%']);
$medien = $stmt_medien->fetchAll();

// Medien-Treffer dem Ergebnis-Array hinzufügen
foreach ($medien as $m) {
    $results[] = [
        'type' => 'Buch',
        'text' => $m['titel'],
        'medium_id' => $m['medium_id']
    ];
}

// ------------------------------------------------------------------
// 2. Autorensuche: nach Vor-/Nachname oder vollständigem Namen (max. 5)
// ------------------------------------------------------------------
$stmt_autor = $pdo->prepare("SELECT autor_id, vorname, nachname FROM autor WHERE CONCAT(vorname, ' ', nachname) LIKE :q1 OR vorname LIKE :q2 OR nachname LIKE :q3 LIMIT 5");
$stmt_autor->execute(['q1' => '%' . $query . '%', 'q2' => '%' . $query . '%', 'q3' => '%' . $query . '%']);
$autoren = $stmt_autor->fetchAll();

// Autoren-Treffer dem Ergebnis-Array hinzufügen
foreach ($autoren as $a) {
    $results[] = [
        'type' => 'Autor',
        'text' => $a['vorname'] . ' ' . $a['nachname'],
        'autor_id' => $a['autor_id']
    ];
}

// Antwort als JSON ausgeben (wird von JavaScript in index.php ausgewertet)
header('Content-Type: application/json');
echo json_encode($results);
