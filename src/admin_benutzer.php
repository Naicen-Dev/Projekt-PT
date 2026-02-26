<?php
/**
 * admin_benutzer.php – Benutzerverwaltung (Nur für Admins)
 */
require_once 'auth.php';
require_once 'admin_only.php';
require_once 'db_connect.php';

$error = '';
$success = '';

// --- BENUTZER ANLEGEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $vorname = trim($_POST['vorname'] ?? '');
    $nachname = trim($_POST['nachname'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $rolle = $_POST['rolle'] ?? 'user';

    if ($vorname === '' || $nachname === '' || $email === '') {
        $error = 'Bitte alle Pflichtfelder ausfüllen.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Ungültige E-Mail-Adresse.';
    } else {
        $stmt = $pdo->prepare("INSERT INTO benutzer (vorname, nachname, email, rolle) VALUES (?, ?, ?, ?)");
        try {
            if ($stmt->execute([$vorname, $nachname, $email, $rolle])) {
                $success = "Benutzer '$vorname $nachname' wurde erfolgreich angelegt.";
            }
        } catch (Exception $e) {
            $error = "Fehler: Die E-Mail-Adresse wird wahrscheinlich bereits verwendet.";
        }
    }
}

// --- BENUTZER LÖSCHEN ---
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];

    // Selbstlöschung verhindern
    if ($delete_id === $_SESSION['user_id']) {
        $error = "Sie können sich nicht selbst löschen.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM benutzer WHERE benutzer_id = ?");
        try {
            if ($stmt->execute([$delete_id])) {
                $success = "Benutzer wurde gelöscht.";
            }
        } catch (Exception $e) {
            $error = "Löschen fehlgeschlagen. Der Benutzer hat wahrscheinlich noch aktive Ausleihen.";
        }
    }
}

// Alle Benutzer laden
$user_list = $pdo->query("SELECT * FROM benutzer ORDER BY nachname ASC, vorname ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Benutzerverwaltung – Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-form {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

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

        .role-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        .role-admin {
            background: #ffcc00;
            color: #333;
        }

        .role-user {
            background: rgba(255, 255, 255, 0.2);
            color: white;
        }
    </style>
</head>

<body>
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

        <?php if ($error): ?>
            <div style="color: #ff6b6b; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div style="color: #20c997; margin-bottom: 15px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <div class="admin-form">
            <h3>Neuen Benutzer anlegen</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <input type="text" name="vorname" placeholder="Vorname" required>
                <input type="text" name="nachname" placeholder="Nachname" required>
                <input type="email" name="email" placeholder="E-Mail" required>
                <select name="rolle">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                </select>
                <button type="submit"
                    style="background:#28a745; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Anlegen</button>
            </form>
        </div>

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
                        <td>
                            <?= $u['benutzer_id'] ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($u['vorname'] . ' ' . $u['nachname']) ?>
                        </td>
                        <td>
                            <?= htmlspecialchars($u['email']) ?>
                        </td>
                        <td>
                            <span class="role-badge role-<?= $u['rolle'] ?>">
                                <?= ucfirst($u['rolle']) ?>
                            </span>
                        </td>
                        <td>
                            <?php if ($u['benutzer_id'] !== $_SESSION['user_id']): ?>
                                <a href="admin_benutzer.php?delete_id=<?= $u['benutzer_id'] ?>" style="color: #ff6b6b;"
                                    onclick="return confirm('Benutzer wirklich unwiderruflich löschen?')">
                                    <i class="fas fa-user-minus"></i> Löschen
                                </a>
                            <?php else: ?>
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