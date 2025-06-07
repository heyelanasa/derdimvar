<?php
session_start();
require_once 'config.php';
global $conn;

// Kullanıcı giriş yapmış mı kontrol et
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthorized');
}

$user_id = $_SESSION['user_id'];

// Kullanıcının tüm bildirimlerini okundu olarak işaretle
$sql = "UPDATE notifications SET is_read = 1 WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

http_response_code(200);
exit('Success');
?>