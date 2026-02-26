<?php
session_start();
require_once 'db_connect.php';

// Neueste 4 Medien für die Startseite laden
$stmt = $pdo->query("SELECT * FROM medium ORDER BY medium_id DESC LIMIT 4");
$neuheiten = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stadtbibliothek Buxtehude</title>
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
                <li><a href="index.php" class="active">Startseite</a></li>
                <li><a href="medien.php">Alle Medien</a></li>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <li><a href="rueckgabe.php"><i class="fas fa-undo"></i> Rückgabe</a></li>
                    <li><a href="ueberfaellig.php"><i class="fas fa-clock"></i> Überfällig</a></li>

                    <?php if ($_SESSION['rolle'] === 'admin'): ?>
                        <li><a href="admin_medien.php" style="color: #ffcc00;"><i class="fas fa-user-shield"></i>
                                Admin-Medien</a></li>
                    <?php endif; ?>

                    <li><a href="#"><i class="fas fa-user"></i> <?= htmlspecialchars($_SESSION['voller_name']) ?></a></li>
                    <li><a href="logout.php"><i class="fas fa-sign-out-alt"></i> Abmelden</a></li>
                <?php else: ?>
                    <li><a href="login.php"><i class="fas fa-sign-in-alt"></i> Anmelden</a></li>
                <?php endif; ?>
            </ul>
        </nav>
    </header>

    <!-- hero/search section -->
    <section class="hero">
        <h1>Willkommen in der Stadtbibliothek</h1>
        <p>Finden Sie Ihre nächsten Lieblingsbücher.</p>

        <!-- Single search box submitting to suche.php with parameter 'suche' -->
        <form action="suche.php" method="GET" class="search-box">
            <input type="text" name="suche" class="search-input" placeholder="Titel, Autor oder ISBN suchen...">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i> Suchen</button>
        </form>
    </section>

    <!-- main content area -->
    <main class="container">
        <h2 class="section-title">Neuheiten & Empfehlungen</h2>

        <div class="media-grid">
            <?php foreach ($neuheiten as $medium): ?>
                <div class="media-card">
                    <i class="fas fa-book media-icon"></i>
                    <h3><?= htmlspecialchars($medium['titel']) ?></h3>
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
    </main>

    <!-- location / info section -->
    <section class="container">
        <h2 class="section-title">Informationen</h2>
        <p>Wir haben für Sie geöffnet:</p>
        <ul style="list-style-type: none; margin-top: 10px;">
            <li>Mo-Fr: 09:00 - 18:00 Uhr</li>
            <li>Sa: 10:00 - 14:00 Uhr</li>
        </ul>
    </section>

    <!-- footer -->
    <footer>
        <div class="footer-links">
            <a href="#">Impressum</a>
            <a href="#">Datenschutz</a>
            <a href="#">Kontakt</a>
        </div>
        <p style="margin-top: 1rem;">&copy; 2026 Stadtbibliothek Buxtehude</p>
    </footer>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const searchInput = document.querySelector('.search-input');
            const searchBox = document.querySelector('.search-box');

            if (searchInput && searchBox) {
                // Erstelle den Container für die Vorschläge (Suggestions)
                const suggestionsContainer = document.createElement('div');
                suggestionsContainer.classList.add('autocomplete-suggestions');
                searchBox.appendChild(suggestionsContainer);

                searchInput.addEventListener('input', async function () {
                    const query = this.value.trim();
                    if (query.length < 2) {
                        suggestionsContainer.innerHTML = '';
                        suggestionsContainer.style.display = 'none';
                        return;
                    }

                    try {
                        const response = await fetch(`ajax_suche.php?q=${encodeURIComponent(query)}`);
                        const data = await response.json();

                        suggestionsContainer.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(item => {
                                const div = document.createElement('div');
                                div.classList.add('suggestion-item');
                                div.innerHTML = `<span class="suggestion-text">${item.text}</span> <span class="suggestion-type">${item.type}</span>`;
                                div.addEventListener('click', () => {
                                    if (item.type === 'Buch' && item.medium_id) {
                                        window.location.href = `ausleihen.php?medium_id=${item.medium_id}`;
                                    } else if (item.type === 'Autor' && item.autor_id) {
                                        window.location.href = `suche.php?autor_id=${item.autor_id}`;
                                    } else {
                                        window.location.href = `suche.php?suche=${encodeURIComponent(item.text)}`;
                                    }
                                });
                                suggestionsContainer.appendChild(div);
                            });
                            suggestionsContainer.style.display = 'block';
                        } else {
                            suggestionsContainer.style.display = 'none';
                        }
                    } catch (e) {
                        console.error('Fehler beim Abrufen der Suchvorschläge:', e);
                    }
                });

                // Versteckt das Popup, wenn irgendwo anders hingeklickt wird
                document.addEventListener('click', function (e) {
                    if (!searchBox.contains(e.target)) {
                        suggestionsContainer.style.display = 'none';
                    }
                });

                // Behält das Popup bei Fokus bei
                searchInput.addEventListener('focus', function () {
                    if (suggestionsContainer.innerHTML.trim() !== '') {
                        suggestionsContainer.style.display = 'block';
                    }
                });
            }
        });
    </script>
</body>

</html>