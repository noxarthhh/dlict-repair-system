<?php
// Simple local debug page to inspect `assets` table schema and sample rows
// Usage: http://localhost/0/tools/inspect_assets.php
// WARNING: This exposes DB metadata locally. Delete when finished.

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../db_connect.php';

header('Content-Type: text/html; charset=utf-8');
echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<h2>Inspect `assets` table</h2>';

try {
    $cols = $pdo->query("SHOW COLUMNS FROM assets")->fetchAll(PDO::FETCH_ASSOC);
    if ($cols) {
        echo '<h3>Columns</h3><table border="1" cellpadding="6"><tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Extra</th></tr>';
        foreach ($cols as $c) {
            echo '<tr>'; 
            echo '<td>' . htmlspecialchars($c['Field']) . '</td>';
            echo '<td>' . htmlspecialchars($c['Type']) . '</td>';
            echo '<td>' . htmlspecialchars($c['Null']) . '</td>';
            echo '<td>' . htmlspecialchars($c['Key']) . '</td>';
            echo '<td>' . htmlspecialchars($c['Extra']) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No columns found or table does not exist.</p>';
    }

    echo '<h3>Sample rows (first 20)</h3>';
    $stmt = $pdo->query("SELECT * FROM assets LIMIT 20");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo '<table border="1" cellpadding="6"><tr>';
        foreach (array_keys($rows[0]) as $col) echo '<th>' . htmlspecialchars($col) . '</th>';
        echo '</tr>';
        foreach ($rows as $r) {
            echo '<tr>';
            foreach ($r as $v) {
                $disp = htmlspecialchars((string)$v);
                if (strlen($disp) > 60) $disp = substr($disp,0,60) . '...';
                echo '<td>' . $disp . '</td>';
            }
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No rows found in `assets`.</p>';
    }

} catch (PDOException $e) {
    echo '<p style="color:red">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<p><strong>After debugging:</strong> delete this file to avoid exposing sensitive data.</p>';

?>
