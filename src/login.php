<?php
/**
 * login.php – Login-Seite der Stadtbibliothek
 *
 * Zeigt ein Anmeldeformular für bestehende Bibliotheksbenutzer.
 * Die Authentifizierung erfolgt über E-Mail-Adresse und vollständigen Namen
 * (kein Passwort – Vereinfachung für Schul-/Demo-Zwecke).
 *
 * Nach erfolgreichem Login werden folgende Session-Variablen gesetzt:
 *   $_SESSION['user_id'], ['email'], ['voller_name'], ['rolle']
 */

// Session starten, um den Login-Status zu prüfen und zu setzen
session_start();

// Wenn der Benutzer bereits eingeloggt ist, direkt zur Startseite weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

// Datenbankverbindung einbinden
require_once 'db_connect.php';

// Variable für Fehlermeldungen initialisieren
$fehler = '';

// ------------------------------------------------------------------
// Formular-Verarbeitung (POST-Anfrage)
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Eingaben bereinigen: Leerzeichen entfernen, Standardwert '' bei fehlendem Feld
    $email = trim($_POST['email'] ?? '');
    $voller_name = trim($_POST['voller_name'] ?? '');

    // --- Validierung ---
    if ($email === '' || $voller_name === '') {
        $fehler = 'Bitte füllen Sie alle Felder aus.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        // E-Mail-Format prüfen
        $fehler = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    } else {
        // Benutzer in der Datenbank suchen (Prepared Statement gegen SQL-Injection)
        $stmt = $pdo->prepare(
            'SELECT benutzer_id, vorname, nachname, email, rolle
             FROM benutzer
             WHERE email = ? AND CONCAT(vorname, \' \', nachname) = ?'
        );
        $stmt->execute([$email, $voller_name]);
        $benutzer = $stmt->fetch();

        if ($benutzer) {
            // Login erfolgreich: Session-ID erneuern (Schutz vor Session-Fixation)
            session_regenerate_id(true);

            // Benutzerinformationen in der Session speichern
            $_SESSION['user_id'] = $benutzer['benutzer_id'];
            $_SESSION['email'] = $benutzer['email'];
            $_SESSION['voller_name'] = $benutzer['vorname'] . ' ' . $benutzer['nachname'];
            $_SESSION['rolle'] = $benutzer['rolle'];

            // Zur Startseite weiterleiten
            header('Location: index.php');
            exit;
        } else {
            // Kein passender Benutzer gefunden
            $fehler = 'E-Mail-Adresse oder Name nicht korrekt. Bitte prüfen Sie Ihre Eingaben.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login – Stadtbibliothek Buxtehude</title>
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Globales Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Seitenspezifische Styles für die Login-Karte -->
    <style>
        /* Zentriert die Login-Karte vertikal und horizontal */
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            padding: 2rem;
        }

        /* Glasmorphismus-Karte */
        .login-card {
            background: rgba(255, 255, 255, 0.12);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 24px;
            padding: 2.5rem 3rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 16px 48px rgba(0, 0, 0, 0.2);
            animation: fadeInUp 0.7s ease-out;
        }

        .login-card h2 {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 0.4rem;
            text-align: center;
        }

        .login-card .subtitle {
            text-align: center;
            color: rgba(255, 255, 255, 0.75);
            font-size: 0.92rem;
            margin-bottom: 2rem;
        }

        /* Formulargruppe: Label + Input */
        .form-group {
            margin-bottom: 1.4rem;
        }

        .form-group label {
            display: block;
            font-size: 0.88rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: rgba(255, 255, 255, 0.9);
            letter-spacing: 0.03em;
        }

        /* Icon im Input-Feld (absolut positioniert) */
        .form-group .input-wrapper {
            position: relative;
        }

        .form-group .input-wrapper i {
            position: absolute;
            left: 14px;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255, 255, 255, 0.6);
            font-size: 0.95rem;
        }

        .form-group input {
            width: 100%;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            color: #fff;
            font-size: 0.97rem;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.25s, background 0.25s;
        }

        .form-group input::placeholder {
            color: rgba(255, 255, 255, 0.5);
        }

        /* Fokus-Effekt für Eingabefelder */
        .form-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.2);
        }

        /* Anmelde-Button */
        .btn-login {
            width: 100%;
            padding: 0.85rem;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            border: none;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
            cursor: pointer;
            transition: background 0.3s, transform 0.2s;
            margin-top: 0.5rem;
        }

        .btn-login:hover {
            background: #fff;
            transform: translateY(-2px);
        }

        .btn-login:active {
            transform: translateY(0);
        }

        /* Fehlermeldungs-Box */
        .alert-error {
            background: rgba(231, 60, 126, 0.25);
            border: 1px solid rgba(231, 60, 126, 0.5);
            border-radius: 12px;
            padding: 0.8rem 1rem;
            margin-bottom: 1.4rem;
            font-size: 0.9rem;
            display: flex;
            align-items: center;
            gap: 0.6rem;
        }

        /* Hinweis-Box unterhalb des Formulars */
        .hint-box {
            margin-top: 1.6rem;
            padding: 0.9rem 1rem;
            background: rgba(35, 166, 213, 0.2);
            border: 1px solid rgba(35, 166, 213, 0.4);
            border-radius: 12px;
            font-size: 0.83rem;
            color: rgba(255, 255, 255, 0.85);
            text-align: center;
        }
    </style>
</head>

<body>
    <!-- Vereinfachter Header nur mit Logo -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude
            </a>
        </nav>
    </header>

    <!-- Login-Karte zentriert im Seitenbereich -->
    <div class="login-wrapper">
        <div class="login-card">
            <h2><i class="fas fa-user-circle"></i> Anmelden</h2>
            <p class="subtitle">Geben Sie Ihre registrierten Daten ein</p>

            <!-- Fehlermeldung anzeigen, wenn $fehler nicht leer ist -->
            <?php if ($fehler !== ''): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($fehler) ?>
                </div>
            <?php endif; ?>

            <!-- Login-Formular (POST an diese Seite selbst) -->
            <form method="POST" action="login.php" novalidate>

                <!-- E-Mail-Feld -->
                <div class="form-group">
                    <label for="email">E-Mail-Adresse</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="max.mustermann@mail.de"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                    </div>
                </div>

                <!-- Namensfeld -->
                <div class="form-group">
                    <label for="voller_name">Vollständiger Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="voller_name" name="voller_name" placeholder="Max Mustermann"
                            value="<?= htmlspecialchars($_POST['voller_name'] ?? '') ?>" required autocomplete="name">
                    </div>
                </div>

                <!-- Absende-Button -->
                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Anmelden
                </button>
            </form>

            <!-- Hinweis für nicht registrierte Benutzer -->
            <div class="hint-box">
                <i class="fas fa-info-circle"></i>
                Sind Sie noch nicht registriert? Wenden Sie sich an das Bibliothekspersonal.
            </div>
        </div>
    </div>
</body>

</html>