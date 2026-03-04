<?php
/**
 * ausleihen.php – Medium ausleihen
 *
 * Ablauf:
 *   1. GET: medium_id aus der URL lesen → Medium + verfügbare Exemplare laden
 *   2. POST: Ausleihformular verarbeiten → Ausleihe in DB speichern, Exemplar-Status auf 0 setzen
 *
 * Zugriff nur für eingeloggte Benutzer (auth.php).
 */

// Zugriffsprüfung: Nur eingeloggte Benutzer dürfen ausleihen
require_once 'auth.php';

// Datenbankverbindung einbinden
require_once 'db_connect.php';

// Statusvariablen initialisieren
$error = '';
$success = '';
$medium = null;
$exemplare = [];

// Eingeloggte Benutzer-ID sicher aus der Session holen
$session_benutzer_id = $_SESSION['user_id'];

// ------------------------------------------------------------------
// SCHRITT 1 – GET: medium_id aus der URL auslesen
// ------------------------------------------------------------------
$medium_id = isset($_GET['medium_id']) ? (int) $_GET['medium_id'] : 0;

if ($medium_id < 1) {
    // Ungültige oder fehlende medium_id → Fehlermeldung setzen
    $error = 'Keine gültige Medium-ID übergeben.';
} else {
    // Medium-Datensatz aus der Datenbank laden
    $stm = $pdo->prepare("SELECT medium_id, ISBN, titel, genre FROM medium WHERE medium_id = ?");
    $stm->execute([$medium_id]);
    $medium = $stm->fetch();

    if (!$medium) {
        $error = 'Das angegebene Medium wurde nicht gefunden.';
    } else {
        // Nur verfügbare Exemplare anzeigen (status = 1 = verfügbar)
        $stm2 = $pdo->prepare("SELECT exemplar_id, inventarnummer FROM exemplar WHERE medium_id = ? AND status = 1");
        $stm2->execute([$medium_id]);
        $exemplare = $stm2->fetchAll();
    }
}

// ------------------------------------------------------------------
// SCHRITT 2 – POST: Ausleihformular verarbeiten
// ------------------------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Formulardaten sicher aus POST holen und numerisch casten
    $post_medium_id = isset($_POST['medium_id']) ? (int) $_POST['medium_id'] : 0;
    $post_exemplar_id = isset($_POST['exemplar_id']) ? (int) $_POST['exemplar_id'] : 0;
    // Benutzer-ID kommt aus der Session (nicht vom Formular – sicherheitsrelevant!)

    // Datum: Standardmäßig heute und in 14 Tagen (Rückgabefrist)
    $ausleihdatum = $_POST['ausleihdatum'] ?? date('Y-m-d');
    $frist_bis = $_POST['frist_bis'] ?? date('Y-m-d', strtotime('+14 days'));

    // --- Eingabevalidierung ---
    if ($post_exemplar_id < 1) {
        $error = 'Bitte wählen Sie ein Exemplar aus.';
    } else {
        // Prüfen: Gehört das Exemplar zum Medium und ist es verfügbar? (status = 1)
        $stm_e = $pdo->prepare("SELECT status FROM exemplar WHERE exemplar_id = ? AND medium_id = ?");
        $stm_e->execute([$post_exemplar_id, $post_medium_id]);
        $ex = $stm_e->fetch();

        if (!$ex) {
            $error = 'Ungültiges Exemplar für dieses Medium.';
        } elseif ($ex['status'] == 0) {
            // Exemplar ist bereits ausgeliehen
            $error = 'Dieses Exemplar ist bereits ausgeliehen.';
        } else {
            // --- Ausleihe anlegen (atomare Datenbank-Transaktion) ---
            try {
                $pdo->beginTransaction();

                // Neuen Ausleihe-Datensatz einfügen
                $stmt_ins = $pdo->prepare("INSERT INTO ausleihe (exemplar_id, benutzer_id, ausleihdatum, frist_bis) 
                                           VALUES (?, ?, ?, ?)");
                $stmt_ins->execute([$post_exemplar_id, $session_benutzer_id, $ausleihdatum, $frist_bis]);

                // Exemplar-Status auf 0 (ausgeliehen) setzen
                $pdo->prepare("UPDATE exemplar SET status = 0 WHERE exemplar_id = ?")
                    ->execute([$post_exemplar_id]);

                $pdo->commit();
                $success = true; // Erfolgsmeldung im HTML anzeigen

            } catch (Exception $e) {
                // Bei Fehler: Transaktion rückgängig machen
                $pdo->rollBack();
                $error = 'Datenbankfehler: ' . $e->getMessage();
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
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Globales Stylesheet -->
    <link rel="stylesheet" href="style.css">
    <!-- Seitenspezifische Styles -->
    <style>
        /* Hauptkarte für das Ausleih-Formular */
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

        /* Medium-Infoanzeige (Titel, Genre, ISBN) */
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

        /* Badges für Genre / ISBN */
        .badge {
            display: inline-block;
            background: rgba(255, 255, 255, 0.15);
            border-radius: 20px;
            padding: 3px 12px;
            font-size: 0.85rem;
            margin: 2px;
        }

        /* Formulargruppe: Label + Input oder Select */
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

        /* Absende-Button mit grünem Verlauf */
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

        /* Allgemeine Alert-Box */
        .alert {
            padding: 14px 18px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-weight: 500;
        }

        /* Fehlermeldung (rot) */
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        /* Erfolgsmeldung (grün) */
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert a {
            color: inherit;
            font-weight: 700;
        }

        /* Link zurück zur Medienliste */
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
    <!-- Header-Navigation -->
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
        <!-- Zurück-Link zur Medienliste -->
        <a href="medien.php" class="back-link"><i class="fas fa-arrow-left"></i> Zurück zur Medienliste</a>

        <!-- Fehlermeldung, falls $error gesetzt -->
        <?php if ($error): ?>
            <div class="alert alert-danger"><i class="fas fa-exclamation-circle"></i> <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <!-- Erfolgsmeldung nach abgeschlossener Ausleihe -->
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                Ausleihe erfolgreich gespeichert! &nbsp;
                <a href="medien.php">← Zurück zur Übersicht</a>
            </div>
        <?php endif; ?>

        <!-- Formular nur anzeigen, wenn Medium gefunden und noch keine erfolgreiche Ausleihe -->
        <?php if ($medium && !$success): ?>
            <div class="ausleih-card">
                <!-- Medium-Informationen anzeigen -->
                <div class="medium-info">
                    <div class="book-icon"><i class="fas fa-book"></i></div>
                    <h3><?= htmlspecialchars($medium['titel']) ?></h3>
                    <span class="badge"><i class="fas fa-tag"></i> <?= htmlspecialchars($medium['genre']) ?></span>
                    <span class="badge">ISBN: <?= htmlspecialchars($medium['ISBN']) ?></span>
                </div>

                <?php if (empty($exemplare)): ?>
                    <!-- Hinweis: Keine verfügbaren Exemplare vorhanden -->
                    <div class="alert alert-danger" style="text-align:center; margin-top:10px;">
                        <i class="fas fa-times-circle"></i>
                        Leider sind aktuell keine Exemplare dieses Mediums verfügbar.
                    </div>
                <?php else: ?>
                    <!-- Ausleihformular: POST an dieselbe URL mit medium_id als Query-Parameter -->
                    <form action="ausleihen.php?medium_id=<?= $medium_id ?>" method="POST">
                        <!-- Verstecktes Feld: medium_id für die serverseitige Zuordnung -->
                        <input type="hidden" name="medium_id" value="<?= $medium_id ?>">

                        <!-- Exemplar-Auswahl -->
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

                        <!-- Ausleihdatum (Standard: heute) -->
                        <div class="form-group">
                            <label for="ausleihdatum"><i class="fas fa-calendar-alt"></i> Ausleihdatum:</label>
                            <input type="date" name="ausleihdatum" id="ausleihdatum" value="<?= date('Y-m-d') ?>" required>
                        </div>

                        <!-- Rückgabedatum (Standard: in 14 Tagen) -->
                        <div class="form-group">
                            <label for="frist_bis"><i class="fas fa-calendar-check"></i> Rückgabe-Frist:</label>
                            <input type="date" name="frist_bis" id="frist_bis"
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