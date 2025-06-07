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

// Bildirim ID'si gönderilmiş mi kontrol et
if (!isset($_POST['notification_id'])) {
    http_response_code(400);
    exit('Bad Request');
}

$notification_id = (int)$_POST['notification_id'];

// Bildirimin kullanıcıya ait olduğunu doğrula
$sql = "SELECT * FROM notifications WHERE id = ? AND user_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $notification_id, $user_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($result) === 0) {
    http_response_code(403);
    exit('Forbidden');
}

// Bildirimi okundu olarak işaretle
$sql = "UPDATE notifications SET is_read = 1 WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $notification_id);
mysqli_stmt_execute($stmt);

http_response_code(200);
exit('Success');
?>