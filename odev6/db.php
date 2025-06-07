<?php
// Veritabanı bağlantı ayarları
$host = 'localhost';
$user = 'beratkar_odev';
$pass = 'berat123123';
$dbname = 'beratkar_odev';

$db = new mysqli($host, $user, $pass, $dbname);
if ($db->connect_error) {
    die('Veritabanı bağlantı hatası: ' . $db->connect_error);
}
?> 
    