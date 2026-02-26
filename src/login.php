<?php
session_start();

// Wenn bereits eingeloggt, direkt weiterleiten
if (isset($_SESSION['user_id'])) {
    header('Location: index.php');
    exit;
}

require_once 'db_connect.php';

$fehler = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Eingaben bereinigen & validieren
    $email = trim($_POST['email'] ?? '');
    $voller_name = trim($_POST['voller_name'] ?? '');

    if ($email === '' || $voller_name === '') {
        $fehler = 'Bitte füllen Sie alle Felder aus.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $fehler = 'Bitte geben Sie eine gültige E-Mail-Adresse ein.';
    } else {
        // Benutzer in der Datenbank suchen (Prepared Statement)
        $stmt = $pdo->prepare(
            'SELECT benutzer_id, vorname, nachname, email, rolle
             FROM benutzer
             WHERE email = ? AND CONCAT(vorname, \' \', nachname) = ?'
        );
        $stmt->execute([$email, $voller_name]);
        $benutzer = $stmt->fetch();

        if ($benutzer) {
            // Session absichern und Variablen setzen
            session_regenerate_id(true);
            $_SESSION['user_id'] = $benutzer['benutzer_id'];
            $_SESSION['email'] = $benutzer['email'];
            $_SESSION['voller_name'] = $benutzer['vorname'] . ' ' . $benutzer['nachname'];
            $_SESSION['rolle'] = $benutzer['rolle'];

            header('Location: index.php');
            exit;
        } else {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .login-wrapper {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            padding: 2rem;
        }

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

        .form-group input:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.7);
            background: rgba(255, 255, 255, 0.2);
        }

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
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude
            </a>
        </nav>
    </header>

    <div class="login-wrapper">
        <div class="login-card">
            <h2><i class="fas fa-user-circle"></i> Anmelden</h2>
            <p class="subtitle">Geben Sie Ihre registrierten Daten ein</p>

            <?php if ($fehler !== ''): ?>
                <div class="alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?= htmlspecialchars($fehler) ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="login.php" novalidate>
                <div class="form-group">
                    <label for="email">E-Mail-Adresse</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input type="email" id="email" name="email" placeholder="max.mustermann@mail.de"
                            value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required autocomplete="email">
                    </div>
                </div>

                <div class="form-group">
                    <label for="voller_name">Vollständiger Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input type="text" id="voller_name" name="voller_name" placeholder="Max Mustermann"
                            value="<?= htmlspecialchars($_POST['voller_name'] ?? '') ?>" required autocomplete="name">
                    </div>
                </div>

                <button type="submit" class="btn-login">
                    <i class="fas fa-sign-in-alt"></i> Anmelden
                </button>
            </form>

            <div class="hint-box">
                <i class="fas fa-info-circle"></i>
                Sind Sie noch nicht registriert? Wenden Sie sich an das Bibliothekspersonal.
            </div>
        </div>
    </div>
</body>

</html>