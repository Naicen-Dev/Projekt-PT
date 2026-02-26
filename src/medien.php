<?php
session_start();
require_once 'db_connect.php';

// Alle Medien aus der Datenbank abrufen (inkl. Autor)
$stmt = $pdo->query("
    SELECT m.*, a.vorname, a.nachname 
    FROM medium m 
    LEFT JOIN autor a ON m.autor_id = a.autor_id
");
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
                <li><a href="medien.php" class="active">Alle Medien</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="rueckgabe.php"><i class="fas fa-undo"></i> Rückgabe</a></li>
                    <li><a href="ueberfaellig.php"><i class="fas fa-clock"></i> Überfällig</a></li>

                    <?php if ($_SESSION['rolle'] === 'admin'): ?>
                        <li><a href="admin_medien.php" style="color: #ffcc00;"><i class="fas fa-user-shield"></i>
                                Admin-Medien</a></li>
                        <li><a href="admin_benutzer.php" style="color: #ffcc00;"><i class="fas fa-users-cog"></i> Benutzer</a>
                        </li>
                    <?php endif; ?>

                    <li><a href="#"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['voller_name']) ?></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Abmelden</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Anmelden</a></li>
                <?php endif; ?>
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
                    <p><strong>Autor:</strong>
                        <a href="suche.php?autor_id=<?= $medium['autor_id'] ?>"
                            style="color: #23a6d5; text-decoration: none;">
                            <?= htmlspecialchars(($medium['vorname'] ?? '') . ' ' . ($medium['nachname'] ?? '')) ?>
                        </a>
                    </p>
                    <p><strong>Genre:</strong>
                        <?= htmlspecialchars($medium['genre']) ?>
                    </p>
                    <p><strong>ISBN:</strong>
                        <?= htmlspecialchars($medium['ISBN']) ?>
                    </p>
                    <div style="margin-top: 15px;">
                        <a href="ausleihen.php?medium_id=<?= $medium['medium_id'] ?>" class="btn-ausleihen"
                            style="display:inline-block; background-color:#28a745; color:white; padding:8px 12px; text-decoration:none; border-radius:5px; font-weight:bold;">
                            <i class="fas fa-hand-holding"></i> Ausleihen
                        </a>
                    </div>
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