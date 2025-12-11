<?php
$host = 'localhost';
$db   = 'fixrequest';
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
     // Log the real error to a file and show a generic message to users
     $logFile = __DIR__ . '/logs/db_connect_error.log';
     error_log(date('[Y-m-d H:i:s] ') . $e->getMessage() . PHP_EOL, 3, $logFile);
     http_response_code(500);
     die("<h3>❌ เชื่อมต่อฐานข้อมูลไม่ได้ โปรดติดต่อผู้ดูแลระบบ</h3>");
}
?>