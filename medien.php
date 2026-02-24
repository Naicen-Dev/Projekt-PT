<?php
require_once 'db_connect.php';

// Alle Medien aus der Datenbank abrufen
$stmt = $pdo->query("SELECT * FROM medium");
$medien = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Alle Medien - Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Startseite</a></li>
                <li><a href="suche.php">Suche</a></li>
                <li><a href="medien.php" class="active">Alle Medien</a></li>
                <li><a href="#">Informationen</a></li>
                <li><a href="#">Mein Konto</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title">Alle Medien</h2>
        <p>Hier finden Sie eine Übersicht aller verfügbaren Medien in unserer Bibliothek.</p>

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