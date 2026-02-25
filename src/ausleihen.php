<?php
require_once 'db_connect.php';

$error = '';
$success = '';

// GET request variables
$medium_id = $_GET['medium_id'] ?? null;
$medium = null;
$exemplare = [];

// Handle GET: Load medium data and available exemplars
if ($medium_id) {
    // Check if medium exists
    $stmt = $pdo->prepare("SELECT * FROM medium WHERE medium_id = ?");
    $stmt->execute([$medium_id]);
    $medium = $stmt->fetch();

    if ($medium) {
        // Fetch available exemplars (status = 1)
        $stmt_ex = $pdo->prepare("SELECT exemplar_id, inventarnummer FROM exemplar WHERE medium_id = ? AND status = 1");
        $stmt_ex->execute([$medium_id]);
        $exemplare = $stmt_ex->fetchAll();
    } else {
        $error = "Das angegebene Medium wurde nicht gefunden.";
    }
} else {
    $error = "Keine Medium-ID übergeben.";
}

// Handle POST request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $post_medium_id = $_POST['medium_id'] ?? '';
    $exemplar_id = $_POST['exemplar_id'] ?? '';
    $benutzer_id = $_POST['benutzer_id'] ?? '';
    $ausleihdatum = $_POST['ausleihdatum'] ?? date('Y-m-d');
    $rueckgabedatum = $_POST['rueckgabedatum'] ?? date('Y-m-d', strtotime('+14 days'));

    if (empty($exemplar_id) || empty($benutzer_id)) {
        $error = "Bitte füllen Sie alle Pflichtfelder aus (Exemplar und Leser-ID).";
    } else {
        // Verify User exists
        $stmt_usr = $pdo->prepare("SELECT benutzer_id FROM benutzer WHERE benutzer_id = ?");
        $stmt_usr->execute([$benutzer_id]);
        if (!$stmt_usr->fetch()) {
            $error = "Die angegebene Leser-ID existiert nicht.";
        } else {
            // Verify Exemplar exists, belongs to Medium, and is available
            $stmt_ex_check = $pdo->prepare("SELECT status FROM exemplar WHERE exemplar_id = ? AND medium_id = ?");
            $stmt_ex_check->execute([$exemplar_id, $post_medium_id]);
            $ex_data = $stmt_ex_check->fetch();

            if (!$ex_data) {
                $error = "Ungültiges Exemplar für dieses Medium.";
            } elseif ($ex_data['status'] == 0) {
                $error = "Dieses Exemplar ist bereits ausgeliehen.";
            } else {
                // All good: create ausleihe and update exemplar
                try {
                    $pdo->beginTransaction();

                    $stmt_insert = $pdo->prepare("INSERT INTO ausleihe (exemplar_id, benutzer_id, ausleihdatum, rueckgabedatum) VALUES (?, ?, ?, ?)");
                    $stmt_insert->execute([$exemplar_id, $benutzer_id, $ausleihdatum, $rueckgabedatum]);

                    $stmt_update = $pdo->prepare("UPDATE exemplar SET status = 0 WHERE exemplar_id = ?");
                    $stmt_update->execute([$exemplar_id]);

                    $pdo->commit();
                    $success = "Erfolgreich ausgeliehen! <a href='medien.php'>Zurück zur Übersicht</a>";
                    // Reload available exemplars if still showing the form
                    $stmt_ex->execute([$post_medium_id]);
                    $exemplare = $stmt_ex->fetchAll();

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = "Es gab ein Problem bei der Ausleihe: " . $e->getMessage();
                }
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ausleihen - Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .ausleih-form {
            background: rgba(255, 255, 255, 0.1);
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 6px;
        }

        .btn-submit {
            background: #28a745;
            color: #fff;
            border: none;
            padding: 10px 20px;
            font-size: 16px;
            border-radius: 6px;
            cursor: pointer;
            width: 100%;
        }

        .btn-submit:hover {
            background: #218838;
        }

        .alert {
            padding: 15px;
            margin-bottom: 20px;
            border-radius: 6px;
        }

        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
    </style>
</head>

<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Startseite</a></li>
                <li><a href="medien.php">Alle Medien</a></li>
                <li><a href="#">Informationen</a></li>
                <li><a href="#">Mein Konto</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title">Medium Ausleihen</h2>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <?= $success ?>
            </div>
        <?php endif; ?>

        <?php if ($medium): ?>
            <div style="text-align: center; margin-bottom: 20px;">
                <h3>
                    <?= htmlspecialchars($medium['titel']) ?>
                </h3>
                <p><strong>ISBN:</strong>
                    <?= htmlspecialchars($medium['ISBN']) ?> | <strong>Genre:</strong>
                    <?= htmlspecialchars($medium['genre']) ?>
                </p>
            </div>

            <?php if (empty($exemplare)): ?>
                <div class="alert alert-danger" style="text-align: center;">
                    Aktuell sind leider keine Exemplare dieses Mediums verfügbar.
                </div>
            <?php else: ?>
                <form action="ausleihen.php?medium_id=<?= htmlspecialchars($medium_id) ?>" method="POST" class="ausleih-form">
                    <input type="hidden" name="medium_id" value="<?= htmlspecialchars($medium_id) ?>">

                    <div class="form-group">
                        <label for="exemplar_id">Verfügbares Exemplar (Inventarnummer):</label>
                        <select name="exemplar_id" id="exemplar_id" required>
                            <option value="">-- Bitte wählen --</option>
                            <?php foreach ($exemplare as $ex): ?>
                                <option value="<?= htmlspecialchars($ex['exemplar_id']) ?>">
                                    <?= htmlspecialchars($ex['inventarnummer']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="benutzer_id">Leser-ID (Benutzer ID):</label>
                        <input type="number" name="benutzer_id" id="benutzer_id" required placeholder="z.B. 1">
                    </div>

                    <div class="form-group">
                        <label for="ausleihdatum">Ausleihdatum:</label>
                        <input type="date" name="ausleihdatum" id="ausleihdatum" value="<?= date('Y-m-d') ?>" required>
                    </div>

                    <div class="form-group">
                        <label for="rueckgabedatum">Rückgabedatum:</label>
                        <input type="date" name="rueckgabedatum" id="rueckgabedatum"
                            value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                    </div>

                    <button type="submit" class="btn-submit"><i class="fas fa-check"></i> Jetzt Ausleihen</button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer style="margin-top: 50px;">
        <div class="footer-links">
            <a href="#">Impressum</a>
            <a href="#">Datenschutz</a>
            <a href="#">Kontakt</a>
        </div>
        <p style="margin-top: 1rem;">&copy; 2026 Stadtbibliothek Buxtehude</p>
    </footer>
</body>

</html>