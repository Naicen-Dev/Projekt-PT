<?php
// Datenbank-Konfiguration
$host = 'localhost';     // Hostname (meistens 'localhost')
$dbname = 'bibliothek'; // Name deiner Datenbank
$username = 'root'; // Datenbank-Benutzername
$password = ''; // Datenbank-Passwort

try {
    // PDO-Instanz erstellen (Verbindung aufbauen)
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, // Fehler als Exceptions werfen
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Standardmäßig assoziative Arrays zurückgeben
        PDO::ATTR_EMULATE_PREPARES => false,                  // Echte Prepared Statements nutzen
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    // Optional: Erfolgsmeldung für Testzwecke (später auskommentieren)
    // echo "Verbindung zur Datenbank erfolgreich hergestellt!";

} catch (\PDOException $e) {
    // Fehlerbehandlung
    die("Verbindung zur Datenbank fehlgeschlagen: " . $e->getMessage());
}
