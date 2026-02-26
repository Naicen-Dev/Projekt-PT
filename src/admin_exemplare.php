<?php
/**
 * admin_exemplare.php – Einzelne Exemplare eines Mediums verwalten
 */
require_once 'auth.php';
require_once 'admin_only.php';
require_once 'db_connect.php';

$error = '';
$success = '';

$medium_id = isset($_GET['medium_id']) ? (int)$_GET['medium_id'] : 0;

if ($medium_id < 1) {
    header('Location: admin_medien.php');
    exit;
}

// Medium-Daten laden
$stmt_m = $pdo->prepare("SELECT titel FROM medium WHERE medium_id = ?");
$stmt_m->execute([$medium_id]);
$medium = $stmt_m->fetch();

if (!$medium) {
    header('Location: admin_medien.php');
    exit;
}

// --- EXEMPLAR HINZUFÜGEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_exemplar') {
    $inv = trim($_POST['inventarnummer'] ?? '');
    if ($inv === '') {
        $error = "Bitte eine Inventarnummer angeben.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO exemplar (inventarnummer, status, medium_id) VALUES (?, 1, ?)");
        if ($stmt->execute([$inv, $medium_id])) {
            $success = "Neues Exemplar wurde hinzugefügt.";
        } else {
            $error = "Fehler beim Hinzufügen.";
        }
    }
}

// --- EXEMPLAR BEARBEITEN ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_exemplar') {
    $ex_id = (int)($_POST['exemplar_id'] ?? 0);
    $inv = trim($_POST['inventarnummer'] ?? '');
    $status = (int)($_POST['status'] ?? 0);

    if ($inv === '') {
        $error = "Inventarnummer darf nicht leer sein.";
    } else {
        $stmt = $pdo->prepare("UPDATE exemplar SET inventarnummer = ?, status = ? WHERE exemplar_id = ? AND medium_id = ?");
        if ($stmt->execute([$inv, $status, $ex_id, $medium_id])) {
            $success = "Exemplar wurde aktualisiert.";
        } else {
            $error = "Fehler beim Aktualisieren.";
        }
    }
}

// --- EXEMPLAR LÖSCHEN ---
if (isset($_GET['delete_ex_id'])) {
    $ex_id = (int)$_GET['delete_ex_id'];
    try {
        $stmt = $pdo->prepare("DELETE FROM exemplar WHERE exemplar_id = ? AND medium_id = ?");
        if ($stmt->execute([$ex_id, $medium_id])) {
            $success = "Exemplar wurde gelöscht.";
        }
    } catch (Exception $e) {
        $error = "Löschen fehlgeschlagen: Das Exemplar ist wahrscheinlich noch in einer Ausleihe verknüpft.";
    }
}

// Liste der Exemplare laden
$stmt_ex = $pdo->prepare("SELECT * FROM exemplar WHERE medium_id = ? ORDER BY inventarnummer ASC");
$stmt_ex->execute([$medium_id]);
$exemplare = $stmt_ex->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <title>Exemplar-Verwaltung – <?= htmlspecialchars($medium['titel']) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .admin-form { background: rgba(255,255,255,0.1); padding: 20px; border-radius: 12px; margin-bottom: 30px; }
        .admin-form input, .admin-form select { padding: 8px; margin-right: 10px; background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3); color: white; border-radius: 4px; }
        .status-badge { padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; font-weight: bold; }
        .status-1 { background: #28a745; color: white; }
        .status-0 { background: #ffc107; color: #333; }
    </style>
</head>
<body>
    <header>
        <nav class="navbar">
            <a href="admin_medien.php" class="logo"><i class="fas fa-arrow-left"></i> Zurück</a>
            <span style="font-weight: 600; color: white;">Exemplar-Verwaltung für: <?= htmlspecialchars($medium['titel']) ?></span>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title"><i class="fas fa-barcode"></i> Exemplare verwalten</h2>

        <?php if ($error): ?><div style="color: #ff6b6b; margin-bottom: 15px;"><?= htmlspecialchars($error) ?></div><?php endif; ?>
        <?php if ($success): ?><div style="color: #20c997; margin-bottom: 15px;"><?= htmlspecialchars($success) ?></div><?php endif; ?>

        <div class="admin-form">
            <h3>Einzelnes Exemplar hinzufügen</h3>
            <form method="POST">
                <input type="hidden" name="action" value="add_exemplar">
                <input type="text" name="inventarnummer" placeholder="Inventarnummer (z.B. BIB-1234)" required>
                <button type="submit" style="background:#28a745; color:white; border:none; padding:8px 15px; border-radius:4px; cursor:pointer;">Hinzufügen</button>
            </form>
        </div>

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
                        <form method="POST">
                            <td><?= $ex['exemplar_id'] ?></td>
                            <td>
                                <input type="hidden" name="action" value="edit_exemplar">
                                <input type="hidden" name="exemplar_id" value="<?= $ex['exemplar_id'] ?>">
                                <input type="text" name="inventarnummer" value="<?= htmlspecialchars($ex['inventarnummer']) ?>" 
                                       style="background: transparent; border: 1px solid rgba(255,255,255,0.2); color:white; padding: 4px; border-radius: 4px;">
                            </td>
                            <td>
                                <select name="status" style="background: transparent; border: 1px solid rgba(255,255,255,0.2); color:white; padding: 4px; border-radius: 4px;">
                                    <option value="1" <?= $ex['status'] == 1 ? 'selected' : '' ?>>Verfügbar</option>
                                    <option value="0" <?= $ex['status'] == 0 ? 'selected' : '' ?>>Ausgeliehen</option>
                                </select>
                            </td>
                            <td>
                                <button type="submit" style="background: #23a6d5; color:white; border:none; padding: 4px 10px; border-radius: 4px; cursor:pointer; margin-right: 5px;">
                                    <i class="fas fa-save"></i>
                                </button>
                                <a href="admin_exemplare.php?medium_id=<?= $medium_id ?>&delete_ex_id=<?= $ex['exemplar_id'] ?>" 
                                   style="color: #ff6b6b;" 
                                   onclick="return confirm('Exemplar wirklich löschen?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </form>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($exemplare)): ?>
                    <tr><td colspan="4">Keine Exemplare vorhanden.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
