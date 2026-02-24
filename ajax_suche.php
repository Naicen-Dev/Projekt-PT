<?php
require_once 'db_connect.php';

$query = $_GET['q'] ?? '';

if (strlen($query) < 2) {
    echo json_encode([]);
    exit;
}

$results = [];

// Suche in Medien
$stmt_medien = $pdo->prepare("SELECT titel, ISBN FROM medium WHERE titel LIKE :q OR ISBN LIKE :q LIMIT 5");
$stmt_medien->execute(['q' => '%' . $query . '%']);
$medien = $stmt_medien->fetchAll();

foreach ($medien as $m) {
    $results[] = [
        'type' => 'Buch',
        'text' => $m['titel']
    ];
}

// Suche in Autoren
$stmt_autor = $pdo->prepare("SELECT vorname, nachname FROM autor WHERE CONCAT(vorname, ' ', nachname) LIKE :q OR vorname LIKE :q OR nachname LIKE :q LIMIT 5");
$stmt_autor->execute(['q' => '%' . $query . '%']);
$autoren = $stmt_autor->fetchAll();

foreach ($autoren as $a) {
    $results[] = [
        'type' => 'Autor',
        'text' => $a['vorname'] . ' ' . $a['nachname']
    ];
}

header('Content-Type: application/json');
echo json_encode($results);
