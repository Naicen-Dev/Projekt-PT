<?php
session_start();

// Alle Session-Variablen löschen
$_SESSION = [];

// Session-Cookie aus dem Browser löschen
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(
        session_name(),
        '',
        time() - 42000,
        $params['path'],
        $params['domain'],
        $params['secure'],
        $params['httponly']
    );
}

// Session auf dem Server zerstören
session_destroy();

// Zurück zur Login-Seite
header('Location: login.php');
exit;
