<?php
$host = 'localhost';
$db   = 'fixrequest'; // 🔴 เช็คชื่อ Database ให้ตรง
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
} catch (\PDOException $e) {
    // ถ้า connect ไม่ได้ ให้ throw error ออกไปเลย
    // เพื่อให้ send_reset_link.php จับได้
    throw new Exception("Database Connection Failed: " . $e->getMessage());
}
?>