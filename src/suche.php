<?php
require_once 'db_connect.php';

$suchbegriff = $_GET['suche'] ?? '';
$autor_id = isset($_GET['autor_id']) ? (int) $_GET['autor_id'] : 0;

$medien = [];
$autoren = [];
$suche_ausgefuehrt = false;

if ($autor_id > 0) {
    $suche_ausgefuehrt = true;
    // Medien eines bestimmten Autors suchen
    $stmt_medien = $pdo->prepare("
        SELECT m.*, a.vorname, a.nachname 
        FROM medium m 
        JOIN autor a ON m.autor_id = a.autor_id 
        WHERE a.autor_id = ?
    ");
    $stmt_medien->execute([$autor_id]);
    $medien = $stmt_medien->fetchAll();

    // Autor-Info für Überschrift laden
    $stmt_a_info = $pdo->prepare("SELECT vorname, nachname FROM autor WHERE autor_id = ?");
    $stmt_a_info->execute([$autor_id]);
    $a_info = $stmt_a_info->fetch();
    if ($a_info) {
        $suchbegriff = "Bücher von " . $a_info['vorname'] . " " . $a_info['nachname'];
    }
} elseif (!empty($suchbegriff)) {
    $suche_ausgefuehrt = true;

    // Suche nach Titel oder ISBN in Medien (inkl. Autor)
    $stmt_medien = $pdo->prepare("
        SELECT m.*, a.vorname, a.nachname 
        FROM medium m 
        LEFT JOIN autor a ON m.autor_id = a.autor_id 
        WHERE m.titel LIKE :q1 OR m.ISBN LIKE :q2
    ");
    $stmt_medien->execute(['q1' => '%' . $suchbegriff . '%', 'q2' => '%' . $suchbegriff . '%']);
    $medien = $stmt_medien->fetchAll();

    // Suche nach Autor (Vorname oder Nachname)
    $stmt_autor = $pdo->prepare("SELECT * FROM autor WHERE CONCAT(vorname, ' ', nachname) LIKE :q1 OR nachname LIKE :q2 OR vorname LIKE :q3");
    $stmt_autor->execute(['q1' => '%' . $suchbegriff . '%', 'q2' => '%' . $suchbegriff . '%', 'q3' => '%' . $suchbegriff . '%']);
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
                                    <p><strong>Autor:</strong>
                                        <a href="suche.php?autor_id=<?= $medium['autor_id'] ?>"
                                            style="color: #23a6d5; text-decoration: none;">
                                            <?= htmlspecialchars(($medium['vorname'] ?? '') . ' ' . ($medium['nachname'] ?? '')) ?>
                                        </a>
                                    </p>
                                    <p><strong>Genre:</strong> <?= htmlspecialchars($medium['genre']) ?></p>
                                    <p><strong>ISBN:</strong> <?= htmlspecialchars($medium['ISBN']) ?></p>
                                    <div style="margin-top: 15px;">
                                        <a href="ausleihen.php?medium_id=<?= $medium['medium_id'] ?>" class="btn-ausleihen"
                                            style="display:inline-block; background-color:#28a745; color:white; padding:8px 12px; text-decoration:none; border-radius:5px; font-weight:bold;">
                                            <i class="fas fa-hand-holding"></i> Ausleihen
                                        </a>
                                    </div>
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
                                        <a href="suche.php?autor_id=<?= $autor['autor_id'] ?>"
                                            style="color: white; text-decoration: none;">
                                            <?= htmlspecialchars($autor['vorname']) ?>                 <?= htmlspecialchars($autor['nachname']) ?>
                                        </a>
                                    </h3>
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