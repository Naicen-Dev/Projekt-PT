<?php
/**
 * logout.php – Benutzer-Abmeldung
 *
 * Löscht die aktuelle Session vollständig und leitet danach
 * zur Login-Seite weiter. Muss kein HTML ausgeben.
 */

// Session öffnen, um auf die Session-Daten zugreifen zu können
session_start();

// Alle Session-Variablen (user_id, rolle, etc.) leeren
$_SESSION = [];

// Session-Cookie aus dem Browser löschen (sofern Cookies für Sessions verwendet werden)
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(), // Name des Session-Cookies
        '',             // Leerer Wert → löscht den Cookie
        time() - 42000, // Ablaufzeit in der Vergangenheit → Browser löscht den Cookie
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Session auf dem Server zerstören
session_destroy();

// Benutzer zur Login-Seite weiterleiten
header('Location: login.php');
exit;
