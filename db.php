<?php
// db.php
$host = 'localhost';
$db = 'db1azfjcaqqk2a';
$user = 'ur9iyguafpilu';
$pass = '51gssrtsv3ei';
 
try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}
?>
 
