<?php
/**
 * admin_exemplare.php – Exemplarverwaltung eines einzelnen Mediums (nur für Admins)
 *
 * Wird aus admin_medien.php verlinkt, immer mit ?medium_id=...
 * Ermöglicht dem Administrator:
 *   - Neue Exemplare zu einem Medium hinzufügen (mit Inventarnummer)
 *   - Inventarnummer und Status bestehender Exemplare bearbeiten
 *   - Einzelne Exemplare löschen (nur wenn keine aktive Ausleihe vorhanden)
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

// medium_id aus der URL lesen (Pflichtparameter für diese Seite)
$medium_id = isset($_GET['medium_id']) ? (int) $_GET['medium_id'] : 0;

// Ohne gültige medium_id → zurück zur Medienverwaltung
if ($medium_id < 1) {
    header('Location: admin_medien.php');
    exit;
}

// Medium-Titel laden (für die Seitenüberschrift)
$stmt_m = $pdo->prepare("SELECT titel FROM medium WHERE medium_id = ?");
$stmt_m->execute([$medium_id]);
$medium = $stmt_m->fetch();

// Medium existiert nicht → zurück
if (!$medium) {
    header('Location: admin_medien.php');
    exit;
}

// ------------------------------------------------------------------
// EXEMPLAR HINZUFÜGEN (POST, action = 'add_exemplar')
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_exemplar') {
    $inv = trim($_POST['inventarnummer'] ?? '');
    if ($inv === '') {
        $error = "Bitte eine Inventarnummer angeben.";
    } else {
        // Neues Exemplar mit Status 1 (verfügbar) anlegen
        $stmt = $pdo->prepare("INSERT INTO exemplar (inventarnummer, status, medium_id) VALUES (?, 1, ?)");
        if ($stmt->execute([$inv, $medium_id])) {
            $success = "Neues Exemplar wurde hinzugefügt.";
        } else {
            $error = "Fehler beim Hinzufügen.";
        }
    }
}

// ------------------------------------------------------------------
// EXEMPLAR BEARBEITEN (POST, action = 'edit_exemplar')
// Jede Tabellenzeile hat ein eigenes kleines Formular zum Speichern
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_exemplar') {
    $ex_id = (int) ($_POST['exemplar_id'] ?? 0);
    $inv = trim($_POST['inventarnummer'] ?? '');
    $status = (int) ($_POST['status'] ?? 0); // 1 = verfügbar, 0 = ausgeliehen

    if ($inv === '') {
        $error = "Inventarnummer darf nicht leer sein.";
    } else {
        // AND medium_id = ? stellt sicher, dass nur Exemplare dieses Mediums bearbeitet werden
        $stmt = $pdo->prepare("UPDATE exemplar SET inventarnummer = ?, status = ? WHERE exemplar_id = ? AND medium_id = ?");
        if ($stmt->execute([$inv, $status, $ex_id, $medium_id])) {
            $success = "Exemplar wurde aktualisiert.";
        } else {
            $error = "Fehler beim Aktualisieren.";
        }
    }
}

// ------------------------------------------------------------------
// EXEMPLAR LÖSCHEN (GET, delete_ex_id = Exemplar-ID)
// ------------------------------------------------------------------
if (isset($_GET['delete_ex_id'])) {
    $ex_id = (int) $_GET['delete_ex_id'];
    try {
        // AND medium_id = ? verhindert, dass Exemplare anderer Medien gelöscht werden
        $stmt = $pdo->prepare("DELETE FROM exemplar WHERE exemplar_id = ? AND medium_id = ?");
        if ($stmt->execute([$ex_id, $medium_id])) {
            $success = "Exemplar wurde gelöscht.";
        }
    } catch (Exception $e) {
        // Kann scheitern, wenn das Exemplar noch in einer aktiven Ausleihe verknüpft ist (FK-Constraint)
        $error = "Löschen fehlgeschlagen: Das Exemplar ist wahrscheinlich noch in einer Ausleihe verknüpft.";
    }
}

// Alle Exemplare dieses Mediums alphabetisch nach Inventarnummer laden
$stmt_ex = $pdo->prepare("SELECT * FROM exemplar WHERE medium_id = ? ORDER BY inventarnummer ASC");
$stmt_ex->execute([$medium_id]);
$exemplare = $stmt_ex->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <!-- Seitentitel enthält den Medientitel für schnelle Orientierung -->
    <title>Exemplar-Verwaltung – <?= htmlspecialchars($medium['titel']) ?></title>
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Eigenes Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Seitenspezifische Admin-Styles -->
    <style>
        /* Formular-Container */
        .admin-form {
            background: rgba(255, 255, 255, 0.1);
            padding: 20px;
            border-radius: 12px;
            margin-bottom: 30px;
        }

        /* Eingabefelder im Formular */
        .admin-form input,
        .admin-form select {
            padding: 8px;
            margin-right: 10px;
            background: rgba(255, 255, 255, 0.1);
            border: 1px solid rgba(255, 255, 255, 0.3);
            color: white;
            border-radius: 4px;
        }

        /* Badge für den Verfügbarkeitsstatus */
        .status-badge {
            padding: 2px 8px;
            border-radius: 10px;
            font-size: 0.8rem;
            font-weight: bold;
        }

        /* Grün = verfügbar */
        .status-1 {
            background: #28a745;
            color: white;
        }

        /* Gelb = ausgeliehen */
        .status-0 {
            background: #ffc107;
            color: #333;
        }
    </style>
</head>

<body>
    <!-- Header: Zurück-Link zur Medienliste + aktueller Medientitel -->
    <header>
        <nav class="navbar">
            <a href="admin_medien.php" class="logo"><i class="fas fa-arrow-left"></i> Zurück</a>
            <span style="font-weight: 600; color: white;">Exemplar-Verwaltung für:
                <?= htmlspecialchars($medium['titel']) ?></span>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title"><i class="fas fa-barcode"></i> Exemplare verwalten</h2>

        <!-- Fehlermeldung (z. B. fehlende Inventarnummer, FK-Constraint verletzt) -->
        <?php if ($error): ?>
            <div style="color: #ff6b6b; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <!-- Erfolgsmeldung (hinzugefügt, bearbeitet oder gelöscht) -->
        <?php if ($success): ?>
            <div style="color: #20c997; margin-bottom: 15px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <!-- Formular: Neues Exemplar hinzufügen -->
        <div class="admin-form">
            <h3>Einzelnes Exemplar hinzufügen</h3>
            <form method="POST">
                <!-- Verstecktes Feld zur Unterscheidung der POST-Aktionen -->
                <input type="hidden" name="action" value="add_exemplar">
                <input type="text" name="inventarnummer" placeholder="Inventarnummer (z.B. BIB-1234)" required>
                <button type="submit"
                    style="background:#28a745; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Hinzufügen</button>
            </form>
        </div>

        <!-- Tabelle: alle Exemplare dieses Mediums -->
        <table>
            <thead>
                <tr style="text-align: left;">
                    <th>ID</th>
                    <th>Inventarnummer</th>
                    <th>Status</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($exemplare as $ex): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <!-- Jede Zeile hat ein eigenes Mini-Formular zum direkten Bearbeiten -->
                        <form method="POST">
                            <td><?= $ex['exemplar_id'] ?></td>
                            <td>
                                <!-- Versteckte Felder für Aktion und Exemplar-ID -->
                                <input type="hidden" name="action" value="edit_exemplar">
                                <input type="hidden" name="exemplar_id" value="<?= $ex['exemplar_id'] ?>">
                                <!-- Inventarnummer direkt bearbeitbar in der Tabellenzelle -->
                                <input type="text" name="inventarnummer"
                                    value="<?= htmlspecialchars($ex['inventarnummer']) ?>"
                                    style="background: transparent; border: 1px solid rgba(255,255,255,0.2); color:white; padding: 4px; border-radius: 4px;">
                            </td>
                            <td>
                                <!-- Status-Dropdown: 1 = verfügbar, 0 = ausgeliehen -->
                                <select name="status"
                                    style="background: transparent; border: 1px solid rgba(255,255,255,0.2); color:white; padding: 4px; border-radius: 4px;">
                                    <option value="1" <?= $ex['status'] == 1 ? 'selected' : '' ?>>Verfügbar</option>
                                    <option value="0" <?= $ex['status'] == 0 ? 'selected' : '' ?>>Ausgeliehen</option>
                                </select>
                            </td>
                            <td>
                                <!-- Speichern-Button für dieses Exemplar -->
                                <button type="submit"
                                    style="background: #23a6d5; color:white; border:none; padding: 4px 10px; border-radius: 4px; cursor:pointer; margin-right: 5px;">
                                    <i class="fas fa-save"></i>
                                </button>
                                <!-- Löschen-Link (GET) mit JS-Bestätigung -->
                                <a href="admin_exemplare.php?medium_id=<?= $medium_id ?>&delete_ex_id=<?= $ex['exemplar_id'] ?>"
                                    style="color: #ff6b6b;" onclick="return confirm('Exemplar wirklich löschen?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($exemplare)): ?>
                    <!-- Hinweis wenn noch keine Exemplare existieren -->
                    <tr>
                        <td colspan="4">Keine Exemplare vorhanden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>

</html>