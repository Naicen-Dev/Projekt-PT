<?php
require_once 'db_connect.php';

$suchbegriff = $_GET['suche'] ?? '';

$medien = [];
$autoren = [];
$suche_ausgefuehrt = false;

if (!empty($suchbegriff)) {
    $suche_ausgefuehrt = true;

    // Suche nach Titel oder ISBN in Medien
    $stmt_medien = $pdo->prepare("SELECT * FROM medium WHERE titel LIKE :suche OR ISBN LIKE :suche");
    $stmt_medien->execute(['suche' => '%' . $suchbegriff . '%']);
    $medien = $stmt_medien->fetchAll();

    // Suche nach Autor (Vorname oder Nachname)
    $stmt_autor = $pdo->prepare("SELECT * FROM autor WHERE CONCAT(vorname, ' ', nachname) LIKE :suche OR nachname LIKE :suche OR vorname LIKE :suche");
    $stmt_autor->execute(['suche' => '%' . $suchbegriff . '%']);
    $autoren = $stmt_autor->fetchAll();
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
                <li><a href="medien.php">Alle Medien</a></li>
                <li><a href="#">Informationen</a></li>
                <li><a href="#">Mein Konto</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title">Suchergebnisse für "<?= htmlspecialchars($suchbegriff) ?>"</h2>

        <?php if ($suche_ausgefuehrt): ?>
            <div class="results-section">

                <?php if (empty($medien) && empty($autoren)): ?>
                    <p>Leider wurden keine Ergebnisse für Ihre Suchanfrage gefunden.</p>
                <?php else: ?>

                    <?php if (!empty($medien)): ?>
                        <h3>Gefundene Bücher / Medien</h3>
                        <div class="media-grid">
                            <?php foreach ($medien as $medium): ?>
                                <div class="media-card">
                                    <i class="fas fa-book media-icon"></i>
                                    <h3><?= htmlspecialchars($medium['titel']) ?></h3>
                                    <p><strong>Genre:</strong> <?= htmlspecialchars($medium['genre']) ?></p>
                                    <p><strong>ISBN:</strong> <?= htmlspecialchars($medium['ISBN']) ?></p>
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
                                    <h3><?= htmlspecialchars($autor['vorname']) ?>                 <?= htmlspecialchars($autor['nachname']) ?></h3>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>

                <?php endif; ?>
            </div>
        <?php else: ?>
            <p>Bitte geben Sie einen Suchbegriff in die Suchleiste ein.</p>
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