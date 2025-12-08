<?php
// tools/migrate_passwords.php
// Safe migration helper to move plain-text password values into a `password_hash` column
// Usage (browser): http://localhost/0/tools/migrate_passwords.php  (shows plan)
// To execute migration: http://localhost/0/tools/migrate_passwords.php?run=1
// To purge plain password column after migration add &purge=1
// Only allow local requests for safety
if (!in_array($_SERVER['REMOTE_ADDR'] ?? '127.0.0.1', ['127.0.0.1','::1','localhost'])) {
    header('HTTP/1.1 403 Forbidden');
    echo "Forbidden: run this locally only.";
    exit;
}

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../db_connect.php';

function colExists(PDO $pdo, string $table, string $col): bool {
    $stmt = $pdo->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$col]);
    return (bool)$stmt->fetch();
}

$table = 'staffs';
$plainCols = ['password','passwd','pwd','user_password'];
$foundPlain = null;

// find which plain-text column exists
foreach ($plainCols as $c) {
    if (colExists($pdo, $table, $c)) { $foundPlain = $c; break; }
}

$hasHash = colExists($pdo, $table, 'password_hash');

echo '<meta charset="utf-8"><style>body{font-family:Arial,Helvetica,sans-serif;padding:16px}</style>';
echo "<h2>Password migration helper</h2>";
echo "<p>Table: <code>$table</code></p>";
echo '<ul>';
echo '<li>password_hash column exists: ' . ($hasHash ? '<strong>yes</strong>' : '<strong>no</strong>') . '</li>';
echo '<li>Detected plain-text column: ' . ($foundPlain ? '<strong>' . htmlspecialchars($foundPlain) . '</strong>' : '<em>none found</em>') . '</li>';
echo '</ul>';

if (!$foundPlain && $hasHash) {
    echo '<p>No plain-text column found and password_hash already exists. Nothing to do.</p>';
    exit;
}

if (!$hasHash) {
    echo '<p><strong>Note:</strong> `password_hash` column does not exist. The script will create it when you run migration.</p>';
}

$run = isset($_GET['run']) && $_GET['run'] == '1';
$purge = isset($_GET['purge']) && $_GET['purge'] == '1';

if (!$run) {
    echo '<p>To perform migration, visit this URL with <code>?run=1</code>. To purge plain text after migration, add <code>&purge=1</code>.</p>';
    echo '<h3>Preview (first 20 rows)</h3>';
    try {
        $stmt = $pdo->query("SELECT * FROM `$table` LIMIT 20");
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { echo '<p>No rows found.</p>'; exit; }
        echo '<table border="1" cellpadding="6"><tr>';
        foreach (array_keys($rows[0]) as $col) echo '<th>' . htmlspecialchars($col) . '</th>';
        echo '</tr>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($r as $v) {
                $display = htmlspecialchars((string)$v);
                // mask long hashes
                if (strpos($display, '$2y$') === 0 || strpos($display, '$argon') === 0) {
                    $display = substr($display,0,8) . '...';
                }
                echo '<td>' . $display . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    } catch (PDOException $e) {
        echo '<p style="color:red">Error: ' . htmlspecialchars($e->getMessage()) . '</p>';
    }
    exit;
}

// RUN migration
try {
    $pdo->beginTransaction();
    if (!$hasHash) {
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `password_hash` VARCHAR(255) NULL AFTER `password`");
        echo '<p>`password_hash` column created.</p>';
    }

    if ($foundPlain) {
        $select = $pdo->prepare("SELECT staff_id, `$foundPlain` AS plain FROM `$table` WHERE (password_hash IS NULL OR password_hash = '') AND `$foundPlain` IS NOT NULL AND `$foundPlain` != ''");
        $select->execute();
        $rows = $select->fetchAll(PDO::FETCH_ASSOC);
        $count = 0;
        $update = $pdo->prepare("UPDATE `$table` SET password_hash = ? WHERE staff_id = ?");
        foreach ($rows as $r) {
            $plain = $r['plain'];
            $hash = password_hash($plain, PASSWORD_DEFAULT);
            $update->execute([$hash, $r['staff_id']]);
            $count++;
        }
        echo "<p>Migrated $count users to password_hash.</p>";
    } else {
        echo '<p>No plain-text column to migrate from.</p>';
    }

    if ($purge && $foundPlain) {
        $pdo->exec("UPDATE `$table` SET `$foundPlain` = NULL WHERE `$foundPlain` IS NOT NULL");
        echo '<p>Purged plain-text password column values (set to NULL).</p>';
    }

    $pdo->commit();
    echo '<p style="color:green">Migration completed successfully.</p>';
    echo '<p><strong>Important:</strong> Remove this script after use.</p>';
} catch (PDOException $e) {
    $pdo->rollBack();
    echo '<p style="color:red">Migration failed: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

?>
