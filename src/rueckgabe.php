<?php
/**
 * rueckgabe.php – Medium zurückgeben
 */
require_once 'auth.php';
require_once 'db_connect.php';

$error = '';
$success = '';

// Wenn eine ausleihe_id übergeben wurde
if (isset($_GET['ausleihe_id'])) {
    $ausleihe_id = (int) $_GET['ausleihe_id'];

    try {
        $pdo->beginTransaction();

        // 1. Ausleihe finden und das dazugehörige Exemplar
        $stmt = $pdo->prepare("SELECT exemplar_id FROM ausleihe WHERE ausleihe_id = ? AND rueckgabe_am IS NULL");
        $stmt->execute([$ausleihe_id]);
        $ausleihe = $stmt->fetch();

        if ($ausleihe) {
            $exemplar_id = $ausleihe['exemplar_id'];

            // 2. Rückgabedatum setzen
            $stmt2 = $pdo->prepare("UPDATE ausleihe SET rueckgabe_am = NOW() WHERE ausleihe_id = ?");
            $stmt2->execute([$ausleihe_id]);

            // 3. Exemplar wieder als verfügbar markieren (status = 1)
            $stmt3 = $pdo->prepare("UPDATE exemplar SET status = 1 WHERE exemplar_id = ?");
            $stmt3->execute([$exemplar_id]);

            $pdo->commit();
            $success = "Das Medium wurde erfolgreich zurückgegeben.";
        } else {
            $error = "Ausleihe nicht gefunden oder bereits zurückgegeben.";
        }
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = "Fehler bei der Rückgabe: " . $e->getMessage();
    }
}

// Alle aktiven Ausleihen für die Liste laden
$stmt_list = $pdo->query("
    SELECT a.ausleihe_id, a.ausleihdatum, a.frist_bis, m.titel, b.vorname, b.nachname, e.inventarnummer
    FROM ausleihe a
    JOIN exemplar e ON a.exemplar_id = e.exemplar_id
    JOIN medium m ON e.medium_id = m.medium_id
    JOIN benutzer b ON a.benutzer_id = b.benutzer_id
    WHERE a.rueckgabe_am IS NULL
    ORDER BY a.frist_bis ASC
");
$ausleihen = $stmt_list->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Rückgabe – Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo"><i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude</a>
            <ul class="nav-links">
                <li><a href="index.php">Startseite</a></li>
                <li><a href="medien.php">Alle Medien</a></li>
                <li><a href="logout.php">Abmelden</a></li>
            </ul>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title"><i class="fas fa-undo"></i> Medienrückgabe</h2>

        <?php if ($error): ?>
            <div
                style="background: rgba(255,0,0,0.2); border: 1px solid red; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div
                style="background: rgba(0,255,0,0.2); border: 1px solid green; padding: 10px; border-radius: 8px; margin-bottom: 20px;">
                <?= htmlspecialchars($success) ?>
            </div>
        <?php endif; ?>

        <table>
            <thead>
                <tr>
                    <th>Medium</th>
                    <th>Inventarnummer</th>
                    <th>Ausleiher</th>
                    <th>Frist bis</th>
                    <th>Aktion</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ausleihen as $a): ?>
                    <tr>
                        <td><?= htmlspecialchars($a['titel']) ?></td>
                        <td><?= htmlspecialchars($a['inventarnummer']) ?></td>
                        <td><?= htmlspecialchars($a['vorname'] . ' ' . $a['nachname']) ?></td>
                        <td><?= htmlspecialchars($a['frist_bis']) ?></td>
                        <td>
                            <a href="rueckgabe.php?ausleihe_id=<?= $a['ausleihe_id'] ?>"
                                style="background: #28a745; color: white; padding: 5px 10px; text-decoration: none; border-radius: 4px;"
                                onclick="return confirm('Möchten Sie dieses Medium wirklich als zurückgegeben markieren?')">
                                Rückgabe
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ausleihen)): ?>
                    <tr>
                        <td colspan="5">Keine aktiven Ausleihen vorhanden.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>

</html>