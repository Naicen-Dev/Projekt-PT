<?php ?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                <li><a href="suche.php">Suche</a></li>
                <li><a href="medien.php">Alle Medien</a></li>
                <li><a href="#">Informationen</a></li>
                <li><a href="#">Mein Konto</a></li>
            </ul>
        </nav>
    </header>

    <!-- hero/search section -->
    <section class="hero">
        <h1>Willkommen in der Stadtbibliothek</h1>
        <p>Finden Sie Ihre nächsten Lieblingsbücher.</p>

        <form action="suche.php" method="GET" class="search-box">
            <input type="text" name="titel" class="search-input" placeholder="Titel oder ISBN suchen...">
            <button type="submit" class="search-btn"><i class="fas fa-search"></i> Suchen</button>
        </form>
    </section>

    <!-- main content area -->
    <main class="container">
        <h2 class="section-title">Neuheiten & Empfehlungen</h2>

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
                                    if (item.type === 'Autor') {
                                        window.location.href = `suche.php?autor=${encodeURIComponent(item.text)}`;
                                    } else {
                                        window.location.href = `suche.php?titel=${encodeURIComponent(item.text)}`;
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