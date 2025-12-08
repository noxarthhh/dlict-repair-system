<?php
// Simple local debug page to inspect `staffs` table schema and sample rows
// Usage: place in `tools/inspect_auth.php` and open in browser: http://localhost/0/tools/inspect_auth.php
// WARNING: This exposes DB metadata and password hashes locally. Delete when finished.

declare(strict_types=1);
error_reporting(E_ALL);
ini_set('display_errors', '1');

require_once __DIR__ . '/../db_connect.php';

header('Content-Type: text/html; charset=utf-8');
echo '<meta name="viewport" content="width=device-width,initial-scale=1">';
echo '<h2>Inspect `staffs` table</h2>';

try {
    // Show columns
    $cols = $pdo->query("SHOW COLUMNS FROM staffs")->fetchAll(PDO::FETCH_ASSOC);
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

    // Show sample rows (mask password/hash partially)
    echo '<h3>Sample users (first 20)</h3>';
    $stmt = $pdo->query("SELECT staff_id, username, full_name, role, password_hash FROM staffs LIMIT 20");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if ($rows) {
        echo '<table border="1" cellpadding="6"><tr><th>staff_id</th><th>username</th><th>full_name</th><th>role</th><th>password_hash (masked)</th></tr>';
        foreach ($rows as $r) {
            $mask = '';
            if (!empty($r['password_hash'])) {
                $h = $r['password_hash'];
                $mask = substr($h,0,8) . '...' . ' (len=' . strlen($h) . ')';
            }
            echo '<tr>';
            echo '<td>' . htmlspecialchars($r['staff_id']) . '</td>';
            echo '<td>' . htmlspecialchars($r['username']) . '</td>';
            echo '<td>' . htmlspecialchars($r['full_name']) . '</td>';
            echo '<td>' . htmlspecialchars($r['role']) . '</td>';
            echo '<td>' . htmlspecialchars($mask) . '</td>';
            echo '</tr>';
        }
        echo '</table>';
    } else {
        echo '<p>No users found in `staffs` table.</p>';
    }

    echo '<hr><p>If the `password_hash` column is empty or missing, the login code expects a column named `password_hash` containing password_hash() output. If your DB uses a different column (e.g. `password`) or stores plain-text passwords, update the login query or migrate passwords to hashed values using password_hash().</p>';

} catch (PDOException $e) {
    echo '<p style="color:red">Database error: ' . htmlspecialchars($e->getMessage()) . '</p>';
}

echo '<p><strong>After debugging:</strong> delete this file to avoid exposing sensitive data.</p>';

?>
