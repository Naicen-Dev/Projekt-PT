<?php
/**
 * medien.php – Übersichtsseite aller verfügbaren Medien
 *
 * Lädt alle Medien aus der Datenbank (inkl. zugehörigem Autor)
 * und stellt sie als Karten-Grid dar. Von hier aus kann direkt
 * zur Ausleihseite navigiert werden.
 */

// Session starten, um den Login-Status in der Navigation zu prüfen
session_start();

// Datenbankverbindung einbinden
require_once 'db_connect.php';

// Alle Medien zusammen mit dem zugehörigen Autor laden
// LEFT JOIN, damit Medien ohne Autor-Eintrag trotzdem erscheinen
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
    <!-- Font Awesome für Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Eigenes Stylesheet -->
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <!-- ============================================================
         HEADER / NAVIGATION
         ============================================================ -->
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo">
                <i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude
            </a>
            <ul class="nav-links">
                <li><a href="index.php">Startseite</a></li>
                <li><a href="medien.php" class="active">Alle Medien</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <!-- Zusätzliche Links für angemeldete Benutzer -->
                    <li><a href="rueckgabe.php"><i class="fas fa-undo"></i> Rückgabe</a></li>
                    <li><a href="ueberfaellig.php"><i class="fas fa-clock"></i> Überfällig</a></li>

                    <?php if ($_SESSION['rolle'] === 'admin'): ?>
                        <!-- Admin-Links: nur bei Rolle 'admin' sichtbar -->
                        <li><a href="admin_medien.php" style="color: #ffcc00;"><i class="fas fa-user-shield"></i>
                                Admin-Medien</a></li>
                        <li><a href="admin_benutzer.php" style="color: #ffcc00;"><i class="fas fa-users-cog"></i> Benutzer</a>
                        </li>
                    <?php endif; ?>

                    <!-- Benutzername anzeigen (XSS-sicher escaped) -->
                    <li><a href="#"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['voller_name']) ?></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Abmelden</a></li>
                <?php else: ?>
                    <!-- Login-Link für nicht angemeldete Besucher -->
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Anmelden</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- ============================================================
         HAUPTINHALT – Medien-Grid
         ============================================================ -->
    <main class="container">
        <h2 class="section-title">Alle Medien</h2>
        <p>Hier finden Sie eine Übersicht aller verfügbaren Medien in unserer Bibliothek.</p>

        <!-- Karten-Grid: ein card pro Medium -->
        <div class="media-grid">
            <?php foreach ($medien as $medium): ?>
                <div class="media-card">
                    <i class="fas fa-book media-icon"></i>
                    <h3>
                        <?= htmlspecialchars($medium['titel']) ?>
                    </h3>
                    <!-- Autor als klickbarer Link → zeigt alle Bücher dieses Autors in suche.php -->
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
                        <!-- Ausleihen-Button: leitet zur Ausleihseite mit der entsprechenden medium_id weiter -->
                        <a href="ausleihen.php?medium_id=<?= $medium['medium_id'] ?>" class="btn-ausleihen"
                            style="display:inline-block; background-color:#28a745; color:white; padding:8px 12px; text-decoration:none; border-radius:5px; font-weight:bold;">
                            <i class="fas fa-hand-holding"></i> Ausleihen
                        </a>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </main>

    <!-- ============================================================
         FOOTER
         ============================================================ -->
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