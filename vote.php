<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Oy vermek için giriş yapmalısınız.']);
    exit;
}

if (!isset($_POST['topic_id']) || !isset($_POST['vote_type'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz istek.']);
    exit;
}

$topic_id = (int)$_POST['topic_id'];
$user_id = (int)$_SESSION['user_id'];
$vote_type = $_POST['vote_type'];

if (!in_array($vote_type, ['up', 'down'])) {
    echo json_encode(['success' => false, 'message' => 'Geçersiz oy türü.']);
    exit;
}

// Veritabanı bağlantısını kontrol et
if (!isset($conn) || $conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Veritabanı bağlantısı başarısız.']);
    exit;
}

// Kullanıcının daha önce oy verip vermediğini kontrol et
$stmt = $conn->prepare("SELECT vote_type FROM votes WHERE topic_id = ? AND user_id = ?");
$stmt->bind_param("ii", $topic_id, $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user_vote = null;

if ($result->num_rows > 0) {
    $current_vote = $result->fetch_assoc();
    $user_vote = $current_vote['vote_type'];
    
    if ($current_vote['vote_type'] === $vote_type) {
        // Aynı oyu tekrar verirse, oyu kaldır
        $stmt = $conn->prepare("DELETE FROM votes WHERE topic_id = ? AND user_id = ?");
        $stmt->bind_param("ii", $topic_id, $user_id);
        $user_vote = null; // Oy kaldırıldı
    } else {
        // Farklı oy verirse, oyu güncelle
        $stmt = $conn->prepare("UPDATE votes SET vote_type = ? WHERE topic_id = ? AND user_id = ?");
        $stmt->bind_param("sii", $vote_type, $topic_id, $user_id);
        $user_vote = $vote_type;
    }
} else {
    // İlk kez oy veriyor
    $stmt = $conn->prepare("INSERT INTO votes (topic_id, user_id, vote_type) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $topic_id, $user_id, $vote_type);
    $user_vote = $vote_type;
}

if (!$stmt->execute()) {
    echo json_encode(['success' => false, 'message' => 'Oy verme işlemi başarısız oldu.']);
    exit;
}

// Yeni oy sayısını hesapla - ayrı sorgularla upvote ve downvote sayılarını al
$upvotes_stmt = $conn->prepare("SELECT COUNT(*) as count FROM votes WHERE topic_id = ? AND vote_type = 'up'");
$upvotes_stmt->bind_param("i", $topic_id);
$upvotes_stmt->execute();
$upvotes_result = $upvotes_stmt->get_result();
$upvotes = $upvotes_result->fetch_assoc()['count'];

$downvotes_stmt = $conn->prepare("SELECT COUNT(*) as count FROM votes WHERE topic_id = ? AND vote_type = 'down'");
$downvotes_stmt->bind_param("i", $topic_id);
$downvotes_stmt->execute();
$downvotes_result = $downvotes_stmt->get_result();
$downvotes = $downvotes_result->fetch_assoc()['count'];

// Net oy sayısını hesapla
$vote_count = $upvotes - $downvotes;

// Debug bilgisi ekle
$debug_info = [
    'upvotes' => $upvotes,
    'downvotes' => $downvotes,
    'net_votes' => $vote_count,
    'user_id' => $user_id,
    'topic_id' => $topic_id,
    'vote_type' => $vote_type
];

echo json_encode([
    'success' => true,
    'vote_count' => $vote_count,
    'user_vote' => $user_vote,
    'message' => 'Oy başarıyla kaydedildi.',
    'debug' => $debug_info
]);
?>