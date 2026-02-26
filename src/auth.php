<?php
/**
 * auth.php – Login-Guard
 *
 * Einbinden am Anfang jeder geschützten Seite:
 *   require_once 'auth.php';
 *
 * Speichert nach Login: $_SESSION['user_id'], ['email'],
 *                        ['voller_name'], ['rolle']
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Nicht eingeloggt → zur Login-Seite
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
