<?php
/**
 * ueberfaellig.php – Liste überfälliger Medien
 */
require_once 'auth.php';
require_once 'db_connect.php';

// Überfällige Ausleihen: rueckgabe_am IS NULL und frist_bis < HEUTE
$stmt = $pdo->query("
    SELECT a.ausleihe_id, a.ausleihdatum, a.frist_bis, m.titel, b.vorname, b.nachname, b.email, e.inventarnummer,
           DATEDIFF(CURDATE(), a.frist_bis) as tage_drueber
    FROM ausleihe a
    JOIN exemplar e ON a.exemplar_id = e.exemplar_id
    JOIN medium m ON e.medium_id = m.medium_id
    JOIN benutzer b ON a.benutzer_id = b.benutzer_id
    WHERE a.rueckgabe_am IS NULL AND a.frist_bis < CURDATE()
    ORDER BY tage_drueber DESC
");
$ueberfaellig = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <title>Überfällig – Stadtbibliothek Buxtehude</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <header>
        <nav class="navbar">
            <a href="index.php" class="logo"><i class="fas fa-book-reader"></i> Stadtbibliothek Buxtehude</a>
        </nav>
    </header>

    <main class="container">
        <h2 class="section-title" style="border-left-color: #e73c7e;"><i class="fas fa-clock"></i> Überfällige Medien
        </h2>
        <p>Hier sehen Sie alle Medien, deren Leihfrist bereits abgelaufen ist.</p>

        <table style="width:100%; border-collapse: collapse; margin-top: 20px;">
            <thead>
                <tr style="text-align: left; border-bottom: 2px solid rgba(255,255,255,0.2);">
                    <th style="padding: 10px;">Medium</th>
                    <th style="padding: 10px;">Ausleiher</th>
                    <th style="padding: 10px;">Frist war am</th>
                    <th style="padding: 10px;">Tage überfällig</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ueberfaellig as $u): ?>
                    <tr style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                        <td style="padding: 10px;"><?= htmlspecialchars($u['titel']) ?>
                            (<?= htmlspecialchars($u['inventarnummer']) ?>)</td>
                        <td style="padding: 10px;">

                            <?= htmlspecialchars($u['vorname'] . ' ' . $u['nachname']) ?><br><small><?= htmlspecialchars($u['email']) ?></small>
                        </td>

                        <td style="padding: 10px; color: #ff6b6b; font-weight: bold;">
                            <?= htmlspecialchars($u['frist_bis']) ?>
                        </td>
                        <td style="padding: 10px;"><span
                                style="background: rgba(231, 60, 126, 0.3); padding: 4px 10px; border-radius: 12px;"><?= htmlspecialchars($u['tage_drueber']) ?>
                                Tage</span></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (empty($ueberfaellig)): ?>
                    <tr>
                        <td colspan="4" style="padding: 20px; text-align: center;">Aktuell sind keine Medien überfällig. 🎉
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </main>
</body>

</html>