<?php
/**
 * admin_benutzer.php – Benutzerverwaltung (nur für Admins)
 *
 * Ermöglicht dem Administrator:
 *   - Neue Bibliotheksbenutzer anlegen (Vorname, Nachname, E-Mail, Rolle)
 *   - Bestehende Benutzer löschen (Selbstlöschung ist blockiert)
 *
 * Zugriff: nur eingeloggte Benutzer mit Rolle 'admin'
 *          (gesichert durch auth.php + admin_only.php)
 */

// Login-Prüfung: muss eingeloggt sein
require_once 'auth.php';
// Admin-Prüfung: nur Benutzer mit Rolle 'admin' dürfen weiter
require_once 'admin_only.php';
// Datenbankverbindung einbinden
require_once 'db_connect.php';

// Statusvariablen für Feedback-Meldungen
$error = '';
$success = '';

// ------------------------------------------------------------------
// BENUTZER ANLEGEN (POST, action = 'add')
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {

    // Formulardaten bereinigen
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rolle = $_POST['rolle'] ?? 'user'; // Standard-Rolle: normaler Benutzer

    // Pflichtfelder und E-Mail-Format prüfen
    if ($vorname === '' || $nachname === '' || $email === '') {
        $error = 'Bitte alle Pflichtfelder ausfüllen.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ungültige E-Mail-Adresse.';
    } else {
        // Neuen Benutzer in die Datenbank einfügen (Prepared Statement)
        $stmt = $pdo->prepare("INSERT INTO benutzer (vorname, nachname, email, rolle) VALUES (?, ?, ?, ?)");
        try {
            if ($stmt->execute([$vorname, $nachname, $email, $rolle])) {
                $success = "Benutzer '$vorname $nachname' wurde erfolgreich angelegt.";
            }
        } catch (Exception $e) {
            // Häufigste Ursache: doppelte E-Mail-Adresse (UNIQUE-Constraint in DB)
            $error = "Fehler: Die E-Mail-Adresse wird wahrscheinlich bereits verwendet.";
        }
    }
}

// ------------------------------------------------------------------
// BENUTZER LÖSCHEN (GET, delete_id = Benutzer-ID)
// ------------------------------------------------------------------
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];

    // Sicherheitscheck: Admin darf sich nicht selbst löschen
    if ($delete_id === $_SESSION['user_id']) {
        $error = "Sie können sich nicht selbst löschen.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM benutzer WHERE benutzer_id = ?");
        try {
            if ($stmt->execute([$delete_id])) {
                $success = "Benutzer wurde gelöscht.";
            }
        } catch (Exception $e) {
            // Kann scheitern, wenn der Benutzer noch verknüpfte Ausleihen hat (FK-Constraint)
            $error = "Löschen fehlgeschlagen. Der Benutzer hat wahrscheinlich noch aktive Ausleihen.";
        }
    }
}

// Alle Benutzer alphabetisch nach Nachname laden
$user_list = $pdo->query("SELECT * FROM benutzer ORDER BY nachname ASC, vorname ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung – Stadtbibliothek Buxtehude</title>
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Eigenes Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Seitenspezifische Admin-Styles -->
    <style>
        /* Formular-Container im Admin-Bereich */
        .admin-form {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        /* Eingabefelder im Admin-Formular */
        .admin-form input,
        .admin-form select {
            padding: 8px;
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 4px;
            margin-bottom: 10px;
        }

        /* Badge für die Rollenanzeige in der Tabelle */
        .role-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* Admin-Badge: gelb */
        .role-admin {
            background: #ffcc00;
            color: #333;
        }

        /* Normaler Benutzer-Badge: halbtransparent */
        .role-user {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
    </style>
</head>

<body>
    <!-- Admin-Header-Navigation -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo"><i class="fas fa-book-reader"></i> Admin-Bereich</a>
            <ul class="nav-links">
                <li><a href="admin_medien.php">Medien verwalten</a></li>
                <li><a href="index.php">Zur Website</a></li>
                <li><a href="logout.php">Abmelden</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title"><i class="fas fa-users-cog"></i> Benutzerverwaltung</h2>

        <!-- Fehlermeldung anzeigen (z. B. doppelte E-Mail, fehlende Felder) -->
        <?php if ($error): ?>
            <div style="color: #ff6b6b; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <!-- Erfolgsmeldung anzeigen (z. B. Benutzer angelegt / gelöscht) -->
        <?php if ($success): ?>
            <div style="color: #20c997; margin-bottom: 15px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Formular: Neuen Benutzer anlegen -->
        <div class="admin-form">
            <h3>Neuen Benutzer anlegen</h3>
            <form method="POST">
                <!-- Verstecktes Feld zur Unterscheidung der POST-Aktionen -->
                <input type="hidden" name="action" value="add">
                <input type="text" name="vorname" placeholder="Vorname" required>
                <input type="text" name="nachname" placeholder="Nachname" required>
                <input type="email" name="email" placeholder="E-Mail" required>
                <!-- Rollenauswahl: user = normaler Benutzer, admin = Administrator -->
                <select name="rolle">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit"
                    style="background:#28a745; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Anlegen</button>
            </form>
        </div>

        <!-- Tabelle: alle registrierten Benutzer -->
        <h3>Registrierte Benutzer</h3>
        <table>
            <thead>
                <tr style="text-align: left;">
                    <th>ID</th>
                    <th>Name</th>
                    <th>E-Mail</th>
                    <th>Rolle</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_list as $u): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td><?= $u['benutzer_id'] ?></td>
                        <!-- Vor- und Nachname zusammensetzen (XSS-sicher) -->
                        <td><?= htmlspecialchars($u['vorname'] . ' ' . $u['nachname']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td>
                            <!-- Badge-Klasse dynamisch gesetzt: role-admin oder role-user -->
                            <span class="role-badge role-<?= $u['rolle'] ?>">
                                <?= ucfirst($u['rolle']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['benutzer_id'] !== $_SESSION['user_id']): ?>
                                <!-- Löschen-Link: JS-Bestätigung, um versehentliches Löschen zu verhindern -->
                                <a href="admin_benutzer.php?delete_id=<?= $u['benutzer_id'] ?>" style="color: #ff6b6b;"
                                    onclick="return confirm('Benutzer wirklich unwiderruflich löschen?')">
                                    <i class="fas fa-user-minus"></i> Löschen
                                </a>
                            <?php else: ?>
                                <!-- Eigener Account: Löschen-Button wird ausgeblendet -->
                                <small style="color: #aaa;">(Sie selbst)</small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>

</html>