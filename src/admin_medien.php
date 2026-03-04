<?php
/**
 * admin_medien.php – Medienverwaltung (nur für Admins)
 *
 * Ermöglicht dem Administrator:
 *   - Neue Medien anlegen (Titel, Autor, Genre, ISBN, Anzahl Exemplare)
 *     → Autor wird automatisch angelegt, wenn noch nicht vorhanden
 *     → Exemplare werden mit automatischer Inventarnummer generiert (BIB-XXXX-XX)
 *   - Bestehende Medien samt allen Exemplaren löschen
 *
 * Zugriff: nur eingeloggte Admins (auth.php + admin_only.php)
 */

// Login-Prüfung: muss eingeloggt sein
require_once 'auth.php';
// Admin-Prüfung: nur Admins dürfen weiter
require_once 'admin_only.php';
// Datenbankverbindung einbinden
require_once 'db_connect.php';

// Statusvariablen für Feedback-Meldungen
$error = '';
$success = '';

// ------------------------------------------------------------------
// MEDIUM HINZUFÜGEN (POST, action = 'add')
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {

    // Formulardaten bereinigen
    $titel = trim($_POST['titel'] ?? '');
    $genre = trim($_POST['genre'] ?? '');
    $isbn = trim($_POST['isbn'] ?? '');
    $anzahl = (int) ($_POST['anzahl'] ?? 1); // Anzahl der Exemplare, die automatisch angelegt werden

    // Pflichtfelder prüfen
    if ($titel === '' || $isbn === '') {
        $error = 'Titel und ISBN sind Pflichtfelder.';
    } else {
        try {
            $pdo->beginTransaction();

            // --- Autor verarbeiten ---
            $autor_input = trim($_POST['autor'] ?? '');
            $autor_id = null;

            if (!empty($autor_input)) {
                // Namen in Vorname + Nachname aufteilen (beim ersten Leerzeichen)
                $name_parts = explode(' ', $autor_input, 2);
                $vorname = $name_parts[0];
                $nachname = $name_parts[1] ?? '';

                // Prüfen, ob der Autor bereits in der DB vorhanden ist
                $stmt_a = $pdo->prepare("SELECT autor_id FROM autor WHERE (vorname = ? AND nachname = ?) OR (nachname = '' AND vorname = ?)");
                $stmt_a->execute([$vorname, $nachname, $autor_input]);
                $a_res = $stmt_a->fetch();

                if ($a_res) {
                    // Vorhandenen Autor verwenden
                    $autor_id = $a_res['autor_id'];
                } else {
                    // Neuen Autor anlegen und seine ID holen
                    $stmt_ins_a = $pdo->prepare("INSERT INTO autor (vorname, nachname) VALUES (?, ?)");
                    $stmt_ins_a->execute([$vorname, $nachname]);
                    $autor_id = $pdo->lastInsertId();
                }
            }

            // --- Medium in die DB einfügen ---
            $stmt = $pdo->prepare("INSERT INTO medium (titel, genre, ISBN, autor_id) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$titel, $genre, $isbn, $autor_id])) {
                $medium_id = $pdo->lastInsertId(); // ID des neu angelegten Mediums

                // --- Exemplare automatisch generieren ---
                $stmt_ex = $pdo->prepare("INSERT INTO exemplar (inventarnummer, status, medium_id) VALUES (?, 1, ?)");
                for ($i = 1; $i <= $anzahl; $i++) {
                    // Inventarnummer-Schema: BIB-0001-01 (medium_id vierstellig, Laufnummer zweistellig)
                    $inv = "BIB-" . str_pad($medium_id, 4, "0", STR_PAD_LEFT) . "-" . str_pad($i, 2, "0", STR_PAD_LEFT);
                    $stmt_ex->execute([$inv, $medium_id]);
                }

                $pdo->commit();
                $success = "Medium '$titel' wurde mit $anzahl Exemplaren erfolgreich hinzugefügt.";
            } else {
                $pdo->rollBack();
                $error = "Fehler beim Speichern des Mediums.";
            }
        } catch (Exception $e) {
            // Transaktion rückgängig machen bei Fehler (z. B. doppelte ISBN)
            $pdo->rollBack();
            $error = "Datenbankfehler: " . $e->getMessage();
        }
    }
}

// ------------------------------------------------------------------
// MEDIUM LÖSCHEN (GET, delete_id = Medium-ID)
// Löscht das Medium und alle zugehörigen Exemplare in einer Transaktion
// ------------------------------------------------------------------
if (isset($_GET['delete_id'])) {
    $delete_id = (int) $_GET['delete_id'];

    try {
        $pdo->beginTransaction();

        // Zuerst Exemplare löschen (Fremdschlüssel-Abhängigkeit beachten)
        $pdo->prepare("DELETE FROM exemplar WHERE medium_id = ?")->execute([$delete_id]);
        // Dann das Medium selbst löschen
        $pdo->prepare("DELETE FROM medium WHERE medium_id = ?")->execute([$delete_id]);

        $pdo->commit();
        $success = "Medium und alle zugehörigen Exemplare wurden entfernt.";
    } catch (Exception $e) {
        $pdo->rollBack();
        // Scheitert, wenn noch verknüpfte Ausleihen existieren (FK-Constraint)
        $error = "Löschen fehlgeschlagen: Es gibt wahrscheinlich noch verknüpfte Daten (z.B. Ausleihen).";
    }
}

// Alle Medien laden: inkl. Anzahl der Exemplare (COUNT) und Autorenname
// GROUP BY wegen der Aggregat-Funktion COUNT()
$medien = $pdo->query("
    SELECT m.*, COUNT(e.exemplar_id) as exemplar_count, a.vorname as autor_vorname, a.nachname as autor_nachname
    FROM medium m 
    LEFT JOIN exemplar e ON m.medium_id = e.medium_id 
    LEFT JOIN autor a ON m.autor_id = a.autor_id
    GROUP BY m.medium_id 
    ORDER BY m.titel ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Medienverwaltung – Stadtbibliothek Buxtehude</title>
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Eigenes Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Seitenspezifische Admin-Styles -->
    <style>
        /* Formular-Container für das Anlegen-Formular */
        .admin-form {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        /* Eingabefelder im Admin-Formular */
        .admin-form input {
            padding: 8px;
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 4px;
        }
    </style>
</head>

<body>
    <!-- Admin-Header-Navigation -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo"><i class="fas fa-book-reader"></i> Admin-Bereich</a>
            <ul class="nav-links">
                <li><a href="admin_benutzer.php">Benutzer verwalten</a></li>
                <li><a href="index.php">Zur Website</a></li>
                <li><a href="logout.php">Abmelden</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title"><i class="fas fa-tools"></i> Medienverwaltung</h2>

        <!-- Fehlermeldung (z. B. fehlende Pflichtfelder, DB-Fehler) -->
        <?php if ($error): ?>
            <div style="color: #ff6b6b; margin-bottom: 15px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>
        <!-- Erfolgsmeldung (Medium angelegt oder gelöscht) -->
        <?php if ($success): ?>
            <div style="color: #20c997; margin-bottom: 15px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <!-- Formular: Neues Medium anlegen -->
        <div class="admin-form">
            <h3>Neues Medium anlegen</h3>
            <form method="POST">
                <!-- Verstecktes Feld zur Unterscheidung der POST-Aktionen -->
                <input type="hidden" name="action" value="add">
                <input type="text" name="titel" placeholder="Titel" required>
                <!-- Autor optional: "Vorname Nachname" – wird automatisch in DB angelegt -->
                <input type="text" name="autor" placeholder="Autor (Vorname Nachname)">
                <input type="text" name="genre" placeholder="Genre">
                <input type="text" name="isbn" placeholder="ISBN" required>
                <!-- Anzahl der Exemplare, die automatisch mit generierter Inventarnummer angelegt werden -->
                <input type="number" name="anzahl" placeholder="Anzahl Exemplare" value="1" min="1" max="100"
                    style="width: 80px;">
                <button type="submit"
                    style="background:#28a745; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Speichern</button>
            </form>
        </div>

        <!-- Tabelle: aktueller Medienbestand -->
        <h3>Aktueller Bestand</h3>
        <table>
            <thead>
                <tr style="text-align: left;">
                    <th>ID</th>
                    <th>Titel</th>
                    <th>Autor</th>
                    <th>ISBN</th>
                    <th>Exemplare</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($medien as $m): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td><?= $m['medium_id'] ?></td>
                        <td><?= htmlspecialchars($m['titel']) ?></td>
                        <!-- Autorenname zusammensetzen (Vor- + Nachname) -->
                        <td><?= htmlspecialchars(($m['autor_vorname'] ?? '') . ' ' . ($m['autor_nachname'] ?? '')) ?></td>
                        <td><?= htmlspecialchars($m['ISBN']) ?></td>
                        <td>
                            <!-- Anzahl der Exemplare als Badge anzeigen -->
                            <span
                                style="background: rgba(255,255,255,0.1); padding: 2px 8px; border-radius: 10px; margin-right: 5px;">
                                <?= $m['exemplar_count'] ?>
                            </span>
                            <!-- Link zur Exemplarverwaltung (admin_exemplare.php) -->
                            <a href="admin_exemplare.php?medium_id=<?= $m['medium_id'] ?>"
                                style="font-size: 0.85rem; color: #23a6d5;">
                                <i class="fas fa-list"></i> verwalten
                            </a>
                        </td>
                        <td>
                            <!-- Löschen-Link mit JS-Bestätigung (löscht Medium + alle Exemplare) -->
                            <a href="admin_medien.php?delete_id=<?= $m['medium_id'] ?>" style="color: #ff6b6b;"
                                onclick="return confirm('Medium und alle Exemplare wirklich löschen?')">
                                <i class="fas fa-trash"></i> Löschen
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>

</html>