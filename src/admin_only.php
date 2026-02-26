<?php
/**
 * admin_only.php – Admin-Guard
 *
 * Einbinden am Anfang jeder Admin-Seite (NACH auth.php):
 *   require_once 'auth.php';
 *   require_once 'admin_only.php';
 *
 * Leitet nicht-Admins zur Startseite weiter.
 */

// Sicherstellen dass auth.php bereits geladen wurde
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nicht eingeloggt → Login
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

// Eingeloggt, aber kein Admin → Startseite mit Fehlermeldung
if (($_SESSION['rolle'] ?? '') !== 'admin') {
    header('Location: index.php?fehler=kein_zugriff');
    exit;
}
