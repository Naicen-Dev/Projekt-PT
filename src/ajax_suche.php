<?php
require_once 'db_connect.php';

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// Suche in Medien
$stmt_medien = $pdo->prepare("SELECT medium_id, titel, ISBN FROM medium WHERE titel LIKE :q1 OR ISBN LIKE :q2 LIMIT 5");
$stmt_medien->execute(['q1' => '%' . $query . '%', 'q2' => '%' . $query . '%']);
$medien = $stmt_medien->fetchAll();

foreach ($medien as $m) {
    $results[] = [
        'type' => 'Buch',
        'text' => $m['titel'],
        'medium_id' => $m['medium_id']
    ];
}

// Suche in Autoren
$stmt_autor = $pdo->prepare("SELECT vorname, nachname FROM autor WHERE CONCAT(vorname, ' ', nachname) LIKE :q1 OR vorname LIKE :q2 OR nachname LIKE :q3 LIMIT 5");
$stmt_autor->execute(['q1' => '%' . $query . '%', 'q2' => '%' . $query . '%', 'q3' => '%' . $query . '%']);
$autoren = $stmt_autor->fetchAll();

foreach ($autoren as $a) {
    $results[] = [
        'type' => 'Autor',
        'text' => $a['vorname'] . ' ' . $a['nachname']
    ];
}

header('Content-Type: application/json');
echo json_encode($results);
