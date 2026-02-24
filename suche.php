<?php
require_once 'db_connect.php';

$titel_suche = $_GET['titel'] ?? '';
$autor_suche = $_GET['autor'] ?? '';

$medien = [];
$autoren = [];
$suche_ausgefuehrt = false;

if (!empty($titel_suche) || !empty($autor_suche)) {
    $suche_ausgefuehrt = true;

    if (!empty($titel_suche)) {
        $stmt_medien = $pdo->prepare("SELECT * FROM medium WHERE titel LIKE :titel OR ISBN LIKE :titel");
        $stmt_medien->execute(['titel' => '%' . $titel_suche . '%']);
        $medien = $stmt_medien->fetchAll();
    }

    if (!empty($autor_suche)) {
        $stmt_autor = $pdo->prepare("SELECT * FROM autor WHERE nachname LIKE :autor OR vorname LIKE :autor");
        $stmt_autor->execute(['autor' => '%' . $autor_suche . '%']);
        $autoren = $stmt_autor->fetchAll();
    }
}
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Suche - Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .search-form-container {
            background: rgba(255, 255, 255, 0.2);
            padding: 30px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .form-group label {
            font-weight: 600;
        }

        .form-group input {
            padding: 10px 15px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-size: 1rem;
        }

        .form-group input:focus {
            outline: 2px solid #23a6d5;
        }

        .submit-btn {
            background: #23a6d5;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 600;
            transition: 0.3s;
            margin-top: 10px;
            align-self: flex-start;
        }

        .submit-btn:hover {
            background: #23d5ab;
            transform: translateY(-2px);
        }

        .results-section {
            margin-top: 2rem;
        }

        .autor-card {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 15px;
            padding: 20px;
            backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            text-align: center;
        }

        .autor-icon {
            font-size: 3rem;
            color: #fff;
            margin-bottom: 15px;
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
                <li><a href="suche.php" class="active">Suche</a></li>
                <li><a href="medien.php">Alle Medien</a></li>
                <li><a href="#">Informationen</a></li>
                <li><a href="#">Mein Konto</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title">Erweiterte Suche</h2>
        <p>Geben Sie Suchbegriffe für Autor und/oder Titel ein.</p>

        <form action="suche.php" method="GET" class="search-form-container">
            <div class="form-group">
                <label for="titel">Titel / ISBN:</label>
                <input type="text" id="titel" name="titel" value="<?= htmlspecialchars($titel_suche) ?>"
                    placeholder="Z.b. 'Datenbank'">
            </div>
            <div class="form-group">
                <label for="autor">Autor (Vorname / Nachname):</label>
                <input type="text" id="autor" name="autor" value="<?= htmlspecialchars($autor_suche) ?>"
                    placeholder="Z.b. 'Mustermann'">
            </div>
            <button type="submit" class="submit-btn"><i class="fas fa-search"></i> Suchen</button>
        </form>

        <?php if ($suche_ausgefuehrt): ?>
            <div class="results-section">
                <h2 class="section-title">Suchergebnisse</h2>

                <?php if (empty($medien) && empty($autoren)): ?>
                    <p>Leider wurden keine Ergebnisse für Ihre Suchanfrage gefunden.</p>
                <?php else: ?>

                    <?php if (!empty($medien)): ?>
                        <h3>Gefundene Medien</h3>
                        <div class="media-grid">
                            <?php foreach ($medien as $medium): ?>
                                <div class="media-card">
                                    <i class="fas fa-book media-icon"></i>
                                    <h3>
                                        <?= htmlspecialchars($medium['titel']) ?>
                                    </h3>
                                    <p><strong>Genre:</strong>
                                        <?= htmlspecialchars($medium['genre']) ?>
                                    </p>
                                    <p><strong>ISBN:</strong>
                                        <?= htmlspecialchars($medium['ISBN']) ?>
                                    </p>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($autoren)): ?>
                        <h3 style="margin-top: 2rem;">Gefundene Autoren</h3>
                        <div class="media-grid">
                            <?php foreach ($autoren as $autor): ?>
                                <div class="autor-card">
                                    <i class="fas fa-user autor-icon"></i>
                                    <h3>
                                        <?= htmlspecialchars($autor['vorname']) ?>
                                        <?= htmlspecialchars($autor['nachname']) ?>
                                    </h3>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

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