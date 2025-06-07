<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$servername = "localhost";
$username = "beratkar_odev";
$password = "berat123123";
$dbname = "beratkar_odev";

// First connect without specifying a database
$conn = new mysqli($servername, $username, $password);

if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

// Create the database if it doesn't exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if ($conn->query($sql) === TRUE) {
    // Database created successfully or already exists
} else {
    die("Veritabanı oluşturma hatası: " . $conn->error);
}

// Close the connection and reconnect with the database selected
$conn->close();
$conn = new mysqli($servername, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Veritabanı bağlantısı başarısız: " . $conn->connect_error);
}

$conn->set_charset("utf8mb4");

// Tabloları oluştur
$sql = "CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    profile_photo VARCHAR(255) DEFAULT 'assets/images/default-avatar.png',
    is_admin BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS topics (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    view_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS comments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    parent_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS votes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    vote_type ENUM('up', 'down') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_vote (topic_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sql);

$sql = "CREATE TABLE IF NOT EXISTS views (
    id INT AUTO_INCREMENT PRIMARY KEY,  
    topic_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_view (topic_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sql);

// Yorum bildirimleri tablosunu oluştur
$sql = "CREATE TABLE IF NOT EXISTS comment_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    comment_id INT NOT NULL,
    user_id INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_report (comment_id, user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sql);

// Bildirimler tablosunu oluştur
$sql = "CREATE TABLE IF NOT EXISTS notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    sender_id INT NOT NULL,
    topic_id INT,
    comment_id INT,
    type ENUM('comment', 'reply', 'system', 'admin') NOT NULL,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (topic_id) REFERENCES topics(id) ON DELETE CASCADE,
    FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

$conn->query($sql);

// Bildirimler tablosuna message sütunu ekle ve type ENUM değerlerini güncelle
$sql = "SHOW COLUMNS FROM notifications LIKE 'message'";
$result = $conn->query($sql);
if ($result->num_rows == 0) {
    // message sütunu yok, ekleyelim
    $sql = "ALTER TABLE notifications ADD COLUMN message TEXT AFTER type";
    $conn->query($sql);
}

$sql = "SHOW COLUMNS FROM notifications LIKE 'type'";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    if (strpos($row['Type'], 'system') === false || strpos($row['Type'], 'admin') === false) {
        // type ENUM değerlerini güncelle
        $sql = "ALTER TABLE notifications MODIFY COLUMN type ENUM('comment', 'reply', 'system', 'admin') NOT NULL";
        $conn->query($sql);
    }
}

// Tabloları utf8mb4'e dönüştür
$tables = ['users', 'topics', 'comments', 'votes', 'views', 'comment_reports', 'notifications'];
foreach ($tables as $table) {
    $conn->query("ALTER TABLE $table CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
}
?>
