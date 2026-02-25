<?php
require_once 'db_connect.php';

$error = '';
$success = '';
$medium = null;
$exemplare = [];

//------------------------------------------------------
// 1. GET: medium_id aus URL lesen
//------------------------------------------------------
$medium_id = isset($_GET['medium_id']) ? (int) $_GET['medium_id'] : 0;

if ($medium_id < 1) {
    $error = 'Keine gültige Medium-ID übergeben.';
} else {
    // Medium laden
    $stm = $pdo->prepare("SELECT medium_id, ISBN, titel, genre FROM medium WHERE medium_id = ?");
    $stm->execute([$medium_id]);
    $medium = $stm->fetch();

    if (!$medium) {
        $error = 'Das angegebene Medium wurde nicht gefunden.';
    } else {
        // Verfügbare Exemplare laden (status = 1 = verfügbar)
        $stm2 = $pdo->prepare("SELECT exemplar_id, inventarnummer FROM exemplar WHERE medium_id = ? AND status = 1");
        $stm2->execute([$medium_id]);
        $exemplare = $stm2->fetchAll();
    }
}

//------------------------------------------------------
// 2. POST: Ausleihe verarbeiten
//------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $post_medium_id = isset($_POST['medium_id']) ? (int) $_POST['medium_id'] : 0;
    $post_exemplar_id = isset($_POST['exemplar_id']) ? (int) $_POST['exemplar_id'] : 0;
    $post_benutzer_id = isset($_POST['benutzer_id']) ? (int) $_POST['benutzer_id'] : 0;
    $ausleihdatum = $_POST['ausleihdatum'] ?? date('Y-m-d');
    $rueckgabedatum = $_POST['rueckgabedatum'] ?? date('Y-m-d', strtotime('+14 days'));

    // --- Validierung ---
    if ($post_exemplar_id < 1 || $post_benutzer_id < 1) {
        $error = 'Bitte Exemplar und Leser-ID ausfüllen.';
    } else {
        // Benutzer prüfen
        $stm_u = $pdo->prepare("SELECT benutzer_id FROM benutzer WHERE benutzer_id = ?");
        $stm_u->execute([$post_benutzer_id]);
        if (!$stm_u->fetch()) {
            $error = "Leser-ID $post_benutzer_id existiert nicht.";
        } else {
            // Exemplar prüfen: gehört es zum Medium, ist es verfügbar?
            $stm_e = $pdo->prepare("SELECT status FROM exemplar WHERE exemplar_id = ? AND medium_id = ?");
            $stm_e->execute([$post_exemplar_id, $post_medium_id]);
            $ex = $stm_e->fetch();

            if (!$ex) {
                $error = 'Ungültiges Exemplar für dieses Medium.';
            } elseif ($ex['status'] == 0) {
                $error = 'Dieses Exemplar ist bereits ausgeliehen.';
            } else {
                // --- Ausleihe anlegen (Transaktion) ---
                try {
                    $pdo->beginTransaction();

                    $pdo->prepare("INSERT INTO ausleihe (exemplar_id, benutzer_id, ausleihdatum, rueckgabedatum)
                                   VALUES (?, ?, ?, ?)")
                        ->execute([$post_exemplar_id, $post_benutzer_id, $ausleihdatum, $rueckgabedatum]);

                    $pdo->prepare("UPDATE exemplar SET status = 0 WHERE exemplar_id = ?")
                        ->execute([$post_exemplar_id]);

                    $pdo->commit();
                    $success = true;

                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'Datenbankfehler: ' . $e->getMessage();
                }

                // Exemplarliste nach Ausleihe neu laden
                $stm3 = $pdo->prepare("SELECT exemplar_id, inventarnummer FROM exemplar WHERE medium_id = ? AND status = 1");
                $stm3->execute([$post_medium_id]);
                $exemplare = $stm3->fetchAll();
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
    <title>Ausleihen – Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .ausleih-card {
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 35px 40px;
            max-width: 520px;
            margin: 0 auto;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        }

        .medium-info {
            text-align: center;
            margin-bottom: 25px;
        }

        .medium-info .book-icon {
            font-size: 3rem;
            color: #fff;
            margin-bottom: 10px;
        }

        .medium-info h3 {
            font-size: 1.4rem;
            margin: 0 0 5px;
        }

        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 0.85rem;
            margin: 2px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-weight: 600;
            font-size: 0.9rem;
        }

        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 8px;
            background: rgba(255, 255, 255, 0.08);
            color: inherit;
            font-size: 1rem;
            box-sizing: border-box;
        }

        .form-group input:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(255, 255, 255, 0.6);
        }

        .btn-submit {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #28a745, #20c997);
            color: #fff;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: opacity 0.2s;
        }

        .btn-submit:hover {
            opacity: 0.88;
        }

        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
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

        .alert a {
            color: inherit;
            font-weight: 700;
        }

        .back-link {
            display: inline-block;
            margin-bottom: 20px;
            color: #fff;
            opacity: 0.75;
            text-decoration: none;
            font-size: 0.9rem;
        }

        .back-link:hover {
            opacity: 1;
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
                <li><a href="medien.php" class="active">Alle Medien</a></li>
                <li><a href="#">Informationen</a></li>
                <li><a href="#">Mein Konto</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title"><i class="fas fa-hand-holding"></i> Medium ausleihen</h2>
        <a href="medien.php" class="back-link"><i class="fas fa-arrow-left"></i> Zurück zur Medienliste</a>

        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Ausleihe erfolgreich gespeichert! &nbsp;
                <a href="medien.php">← Zurück zur Übersicht</a>
            </div>
        <?php endif; ?>

        <?php if ($medium && !$success): ?>
            <div class="ausleih-card">
                <div class="medium-info">
                    <div class="book-icon"><i class="fas fa-book"></i></div>
                    <h3><?= htmlspecialchars($medium['titel']) ?></h3>
                    <span class="badge"><i class="fas fa-tag"></i> <?= htmlspecialchars($medium['genre']) ?></span>
                    <span class="badge">ISBN: <?= htmlspecialchars($medium['ISBN']) ?></span>
                </div>

                <?php if (empty($exemplare)): ?>
                    <div class="alert alert-danger" style="text-align:center; margin-top:10px;">
                        <i class="fas fa-times-circle"></i>
                        Leider sind aktuell keine Exemplare dieses Mediums verfügbar.
                    </div>
                <?php else: ?>
                    <form action="ausleihen.php?medium_id=<?= $medium_id ?>" method="POST">
                        <input type="hidden" name="medium_id" value="<?= $medium_id ?>">

                        <div class="form-group">
                            <label for="exemplar_id"><i class="fas fa-barcode"></i> Exemplar (Inventarnummer):</label>
                            <select name="exemplar_id" id="exemplar_id" required>
                                <option value="">– Bitte wählen –</option>
                                <?php foreach ($exemplare as $ex): ?>
                                    <option value="<?= (int) $ex['exemplar_id'] ?>">
                                        <?= htmlspecialchars($ex['inventarnummer']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="benutzer_id"><i class="fas fa-user"></i> Leser-ID (benutzer_id):</label>
                            <input type="number" name="benutzer_id" id="benutzer_id" min="1" required placeholder="z.B. 1">
                        </div>

                        <div class="form-group">
                            <label for="ausleihdatum"><i class="fas fa-calendar-alt"></i> Ausleihdatum:</label>
                            <input type="date" name="ausleihdatum" id="ausleihdatum" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <div class="form-group">
                            <label for="rueckgabedatum"><i class="fas fa-calendar-check"></i> Rückgabedatum:</label>
                            <input type="date" name="rueckgabedatum" id="rueckgabedatum"
                                value="<?= date('Y-m-d', strtotime('+14 days')) ?>" required>
                        </div>

                        <button type="submit" class="btn-submit">
                            <i class="fas fa-check"></i> Jetzt ausleihen
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    </main>

    <footer>
        <div class="footer-links">
            <a href="#">Impressum</a>
            <a href="#">Datenschutz</a>
            <a href="#">Kontakt</a>
        </div>
        <p style="margin-top: 1rem;">&copy; 2026 Stadtbibliothek Buxtehude</p>
    </footer>
</body>

</html>