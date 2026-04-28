<?php
$host = 'localhost';
$dbname = 'ossiqntv';
$username = 'root';
$password = '';

try {
    $temp_db = new PDO("mysql:host=$host;charset=utf8mb4", $username, $password);
    $temp_db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);


    $temp_db->exec("CREATE DATABASE IF NOT EXISTS `$dbname` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $db = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password);
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch(PDOException $e) {
    die("<h1>Veritabanı Hatası!</h1><p>Bağlantı kurulamadı: " . $e->getMessage() . "</p>");
}
?>
