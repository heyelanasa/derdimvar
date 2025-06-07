<?php
session_start();
require_once 'config.php';
global $conn;

if (!isset($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$topic_id = (int)$_GET['id'];

// Konuyu getir
$sql = "SELECT t.*, u.username, u.profile_photo, 
        (SELECT COUNT(*) FROM comments WHERE topic_id = t.id) as comment_count,
        (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'up') - 
        (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'down') as vote_count
        FROM topics t 
        JOIN users u ON t.user_id = u.id 
        WHERE t.id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $topic_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$topic = mysqli_fetch_assoc($result);

if (!$topic) {
    header('Location: index.php');
    exit;
}

// Görüntülenme sayısını artır
$sql = "UPDATE topics SET view_count = view_count + 1 WHERE id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $topic_id);
mysqli_stmt_execute($stmt);

// Yorumları getir
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'newest';
$order_by = match($sort) {
    'oldest' => 'c.created_at ASC',
    'most_reported' => '(SELECT COUNT(*) FROM comment_reports WHERE comment_id = c.id) DESC, c.created_at DESC',
    default => 'c.created_at DESC'
};

$sql = "SELECT c.*, u.username, u.profile_photo,
        (SELECT COUNT(*) FROM comments WHERE parent_id = c.id) as reply_count,
        (SELECT COUNT(*) FROM comment_reports WHERE comment_id = c.id) as report_count
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.topic_id = ? AND c.parent_id IS NULL
        ORDER BY " . ($sort === 'most_reported' ? '(SELECT COUNT(*) FROM comment_reports WHERE comment_id = c.id) DESC, c.created_at DESC' : $order_by);

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $topic_id);
mysqli_stmt_execute($stmt);
$comments_result = mysqli_stmt_get_result($stmt);
$comments = mysqli_fetch_all($comments_result, MYSQLI_ASSOC);

// Kullanıcının bu konuya verdiği oyu çek
$user_vote = null;
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $vote_query = mysqli_prepare($conn, "SELECT vote_type FROM votes WHERE topic_id = ? AND user_id = ?");
    mysqli_stmt_bind_param($vote_query, "ii", $topic_id, $user_id);
    mysqli_stmt_execute($vote_query);
    $vote_result = mysqli_stmt_get_result($vote_query);
    if ($row = mysqli_fetch_assoc($vote_result)) {
        $user_vote = $row['vote_type'];
    }
}

// Yorum ekleme işlemi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['content'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit;
    }

    $content = trim($_POST['content']);
    $parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : null;

    if (!empty($content)) {
        $sql = "INSERT INTO comments (topic_id, user_id, parent_id, content) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iiis", $topic_id, $_SESSION['user_id'], $parent_id, $content);

        if (mysqli_stmt_execute($stmt)) {
            $comment_id = mysqli_insert_id($conn);
            $sender_id = $_SESSION['user_id'];

            // Bildirim oluştur
            if ($parent_id) {
                // Yanıt bildirimi
                $sql = "SELECT user_id FROM comments WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $parent_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $parent_comment = mysqli_fetch_assoc($result);

                // Kendi yorumuna yanıt vermiyorsa bildirim oluştur
                if ($parent_comment && $parent_comment['user_id'] != $sender_id) {
                    $sql = "INSERT INTO notifications (user_id, sender_id, topic_id, comment_id, type) VALUES (?, ?, ?, ?, 'reply')";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iiii", $parent_comment['user_id'], $sender_id, $topic_id, $comment_id);
                    mysqli_stmt_execute($stmt);
                }
            } else {
                // Konu yorumu bildirimi
                $sql = "SELECT user_id FROM topics WHERE id = ?";
                $stmt = mysqli_prepare($conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $topic_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $topic_info = mysqli_fetch_assoc($result);

                // Kendi konusuna yorum yapmıyorsa bildirim oluştur
                if ($topic_info && $topic_info['user_id'] != $sender_id) {
                    $sql = "INSERT INTO notifications (user_id, sender_id, topic_id, comment_id, type) VALUES (?, ?, ?, ?, 'comment')";
                    $stmt = mysqli_prepare($conn, $sql);
                    mysqli_stmt_bind_param($stmt, "iiii", $topic_info['user_id'], $sender_id, $topic_id, $comment_id);
                    mysqli_stmt_execute($stmt);
                }
            }
        }

        header("Location: konu.php?id=$topic_id");
        exit;
    }
}

// Yorum silme işlemi
if (isset($_POST['delete_comment']) && isset($_SESSION['user_id'])) {
    $comment_id = (int)$_POST['comment_id'];
    $user_id = $_SESSION['user_id'];
    $is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

    // Yorumu getir
    $sql = "SELECT * FROM comments WHERE id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $comment_id);
    mysqli_stmt_execute($stmt);
    $comment = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

    // Kullanıcı yorumun sahibi veya admin ise sil
    if ($comment && ($comment['user_id'] == $user_id || $is_admin)) {
        $sql = "DELETE FROM comments WHERE id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "i", $comment_id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: konu.php?id=$topic_id");
    exit;
}

// Yorum düzenleme işlemi
if (isset($_POST['edit_comment']) && isset($_SESSION['user_id'])) {
    $comment_id = (int)$_POST['comment_id'];
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (!empty($content)) {
        $sql = "UPDATE comments SET content = ? WHERE id = ? AND user_id = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sii", $content, $comment_id, $user_id);
        mysqli_stmt_execute($stmt);
    }

    header("Location: konu.php?id=$topic_id");
    exit;
}

// Yorum bildirme işlemi
if (isset($_POST['report_comment']) && isset($_SESSION['user_id'])) {
    $comment_id = (int)$_POST['comment_id'];
    $user_id = $_SESSION['user_id'];

    // Bildirim tablosunu oluştur
    $sql = "CREATE TABLE IF NOT EXISTS comment_reports (
        id INT AUTO_INCREMENT PRIMARY KEY,
        comment_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (comment_id) REFERENCES comments(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        UNIQUE KEY unique_report (comment_id, user_id)
    )";
    mysqli_query($conn, $sql);

    // Bildirimi ekle
    $sql = "INSERT IGNORE INTO comment_reports (comment_id, user_id) VALUES (?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $comment_id, $user_id);
    mysqli_stmt_execute($stmt);

    header("Location: konu.php?id=$topic_id");
    exit;
}

$imagePath = $topic['image'];
if ($imagePath && !str_starts_with($imagePath, 'assets/') && !str_starts_with($imagePath, 'uploads/')) {
    $imagePath = 'uploads/topics/' . $imagePath;
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($topic['title']); ?> - DerdimVar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            color: #222;
        }

        .navbar {
            background: #fff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .navbar-brand {
            color: #2c3e50 !important;
            font-weight: 900;
            font-size: 1.8rem;
        }

        .nav-link {
            color: #2c3e50 !important;
            font-weight: 700;
            font-size: 1.15rem;
        }

        .nav-link:hover {
            color: #4a90e2 !important;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .topic-card {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .topic-header {
            display: flex;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .profile-pic {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #4a90e2;
        }

        .username {
            font-weight: 500;
            color: #2c3e50;
            margin: 0;
        }

        .topic-title {
            font-size: 1.8rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 1rem;
        }

        .topic-content {
            color: #666;
            line-height: 1.7;
            margin-bottom: 1.5rem;
        }

        /* Responsive image styles for topic detail page */
        @media (max-width: 900px) {
            .image-container {
                height: 350px !important;
                max-height: 350px !important;
            }
        }

        @media (max-width: 600px) {
            .image-container {
                height: 250px !important;
                max-height: 250px !important;
            }
        }

        .topic-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .topic-stats {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .stat-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #666;
            font-size: 0.9rem;
        }

        .stat-item i {
            color: #4a90e2;
        }

        .vote-buttons {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .vote-btn {
            background: none;
            border: none;
            color: #666;
            cursor: pointer;
            padding: 0.5rem;
            transition: all 0.3s ease;
            border-radius: 50%;
        }

        .vote-btn:hover {
            background: #f8f9fa;
            color: #4a90e2;
        }

        .vote-btn.active {
            color: #4a90e2;
        }

        .vote-count {
            font-weight: 600;
            color: #2c3e50;
            min-width: 30px;
            text-align: center;
        }

        .comments-section {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .comment-form {
            margin-bottom: 2rem;
        }

        .comment-form textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            resize: vertical;
            min-height: 100px;
            margin-bottom: 1rem;
            font-family: inherit;
        }

        .comment-form textarea:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74,144,226,0.25);
            outline: none;
        }

        .btn-primary {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            border: none;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(74,144,226,0.3);
        }

        .comment {
            background: #f8f9fa;
            border-radius: 10px;
            padding: 1.5rem;
            margin-bottom: 1rem;
        }

        .comment-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1rem;
        }

        .comment-user {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .comment-content {
            color: #666;
            line-height: 1.6;
            margin-bottom: 1rem;
            word-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        .comment-footer {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding-top: 1rem;
            border-top: 1px solid #eee;
        }

        .comment-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .reply-btn {
            background: none;
            border: none;
            color: #4a90e2;
            cursor: pointer;
            font-size: 0.9rem;
            padding: 0.5rem;
            transition: all 0.3s ease;
        }

        .reply-btn:hover {
            color: #357abd;
            text-decoration: underline;
        }

        .replies {
            margin-left: 3rem;
            margin-top: 1rem;
        }

        .reply {
            background: #ffffff;
            border-radius: 10px;
            padding: 1rem;
            margin-bottom: 0.5rem;
            border: 1px solid #e0e0e0;
        }

        .reply-form {
            margin-left: 3rem;
            margin-top: 1rem;
            display: none;
        }

        .reply-form.active {
            display: block;
        }

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .topic-title {
                font-size: 1.5rem;
            }

            .replies {
                margin-left: 1rem;
            }

            .reply-form {
                margin-left: 1rem;
            }
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4" style="font-size:1.15rem;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php" style="font-weight:900;font-size:1.8rem;color:#2c3e50;">
                <i class="fas fa-comment-dots me-2" style="color:#4a90e2;font-size:2.1rem;"></i> Derdimvar
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="odevler.php">Ödevler</a>
                    </li>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false" style="font-weight:700;">
                            <i class="fas fa-bell"></i>
                            <?php
                            // Okunmamış bildirim sayısını getir
                            $user_id = $_SESSION['user_id'];
                            $sql = "SELECT COUNT(*) as count FROM notifications WHERE user_id = ? AND is_read = 0";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $result = mysqli_stmt_get_result($stmt);
                            $unread_count = mysqli_fetch_assoc($result)['count'];

                            if ($unread_count > 0): 
                            ?>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                                <?php echo $unread_count; ?>
                                <span class="visually-hidden">okunmamış bildirim</span>
                            </span>
                            <?php endif; ?>
                        </a>
                        <ul class="dropdown-menu" aria-labelledby="notificationsDropdown" style="width: 350px; max-height: 400px; overflow-y: auto; left: auto; right: auto;">
                            <?php
                            // Bildirimleri getir
                            $sql = "SELECT n.*, u.username as sender_name, t.title as topic_title, c.content as comment_content 
                                    FROM notifications n 
                                    LEFT JOIN users u ON n.sender_id = u.id 
                                    LEFT JOIN topics t ON n.topic_id = t.id 
                                    LEFT JOIN comments c ON n.comment_id = c.id 
                                    WHERE n.user_id = ? 
                                    ORDER BY n.created_at DESC 
                                    LIMIT 10";

                            // Debug için SQL sorgusunu yazdır
                            error_log("Notifications SQL: " . $sql);
                            $stmt = mysqli_prepare($conn, $sql);
                            if (!$stmt) {
                                error_log("Prepare failed: " . mysqli_error($conn));
                            } else {
                                mysqli_stmt_bind_param($stmt, "i", $user_id);
                                if (!mysqli_stmt_execute($stmt)) {
                                    error_log("Execute failed: " . mysqli_stmt_error($stmt));
                                } else {
                                    $notifications = mysqli_stmt_get_result($stmt);
                                    error_log("Found " . mysqli_num_rows($notifications) . " notifications");
                                }
                            }

                            if (mysqli_num_rows($notifications) > 0):
                                while ($notification = mysqli_fetch_assoc($notifications)):
                                    // Debug için bildirim verilerini yazdır
                                    error_log("Notification data: " . json_encode($notification));

                                    $is_read_class = $notification['is_read'] ? 'text-muted' : 'fw-bold';
                                    $notification_text = '';
                                    $link = '';

                                    if ($notification['type'] == 'comment') {
                                        $sender_name = isset($notification['sender_name']) ? $notification['sender_name'] : 'Bilinmeyen Kullanıcı';
                                        $notification_text = "<strong>{$sender_name}</strong> konunuza yorum yaptı: \"{$notification['topic_title']}\"";
                                        $link = "konu.php?id={$notification['topic_id']}";
                                    } else if ($notification['type'] == 'reply') {
                                        $sender_name = isset($notification['sender_name']) ? $notification['sender_name'] : 'Bilinmeyen Kullanıcı';
                                        $notification_text = "<strong>{$sender_name}</strong> yorumunuza yanıt verdi";
                                        $link = "konu.php?id={$notification['topic_id']}#comment-{$notification['comment_id']}";
                                    } else if ($notification['type'] == 'system') {
                                        $sender_name = isset($notification['sender_name']) && !empty($notification['sender_name']) ? $notification['sender_name'] : 'Derdimvar';
                                        $message = isset($notification['message']) && !empty($notification['message']) ? $notification['message'] : "Hoşgeldiniz! Derdimvar'da yeni gönderiler oluşturabilir, diğer kullanıcıların gönderilerine bakabilir, onlarla konuşabilir ve dertlerini paylaşabilirsiniz.";

                                        // Debug için bilgileri yazdır
                                        error_log("System notification - ID: " . $notification['id'] . ", Sender: " . $sender_name . ", Message: " . $message);

                                        $notification_text = "<strong>{$sender_name}</strong>: {$message}";
                                        $link = "#";
                                    } else if ($notification['type'] == 'admin') {
                                        $notification_text = "<strong>Admin</strong>: {$notification['message']}";
                                        $link = "#";
                                    }
                            ?>
                            <li>
                                <div class="dropdown-item">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-grow-1">
                                            <a class="<?php echo $is_read_class; ?>" href="<?php echo $link; ?>" onclick="markAsRead(<?php echo $notification['id']; ?>)" style="text-decoration: none; color: inherit;">
                                                <p class="mb-0"><?php echo $notification_text; ?></p>
                                                <small class="text-muted d-block mt-1"><?php echo date('d.m.Y H:i', strtotime($notification['created_at'])); ?></small>
                                            </a>
                                        </div>
                                        <div class="ms-2">
                                            <button class="btn btn-sm text-danger" onclick="deleteNotification(<?php echo $notification['id']; ?>); event.stopPropagation();" title="Bildirimi Sil">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </li>
                            <?php 
                                endwhile;
                            else:
                            ?>
                            <li><span class="dropdown-item text-center">Bildirim yok</span></li>
                            <?php endif; ?>
                            <?php if (mysqli_num_rows($notifications) > 0): ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-center" href="#" onclick="markAllAsRead(); return false;">Tümünü okundu olarak işaretle</a></li>
                            <?php endif; ?>
                        </ul>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="profil.php"><i class="fas fa-user"></i> Profilim</a>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="yeni-konu.php"><i class="fas fa-plus"></i> Yeni Konu</a>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li class="nav-item">
                            <a class="nav-link" style="font-weight:700;" href="admin/index.php">
                                <i class="fas fa-tools"></i> Yönetici Paneli
                            </a>
                        </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="login.php"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
        <div class="topic-card w-100" style="max-width: 700px; margin: 2rem auto; padding: 2.5rem 2rem; background: #fff; border-radius: 24px; box-shadow: 0 4px 24px rgba(80,120,200,0.10);">
            <div class="topic-header" style="display:flex; align-items:center; gap:12px; margin-bottom:18px;">
                <?php
                $profilePhoto = !empty($topic['profile_photo']) ? (str_starts_with($topic['profile_photo'], 'uploads/') ? $topic['profile_photo'] : 'uploads/profiles/' . $topic['profile_photo']) : 'assets/images/default-avatar.png';
                ?>
                <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="<?php echo htmlspecialchars($topic['username']); ?>" class="profile-pic" style="width:48px;height:48px;border-radius:50%;object-fit:cover;">
                <div>
                    <div class="username" style="font-weight:600;color:#2c3e50;font-size:1.1rem;"> <?php echo htmlspecialchars($topic['username']); ?> </div>
                    <div style="color:#888;font-size:0.95rem;"> <?php echo date('d M Y H:i', strtotime($topic['created_at'])); ?> </div>
                </div>
            </div>
            <h2 class="topic-title" style="font-size:2rem;font-weight:700;color:#2c3e50;margin-bottom:1rem;text-align:center;"> <?php echo htmlspecialchars($topic['title']); ?> </h2>
            <div class="topic-content" style="color:#444;font-size:1.15rem;margin-bottom:1.5rem;text-align:center;white-space:normal;word-wrap:break-word;word-break:break-word;"> <?php echo nl2br(htmlspecialchars($topic['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?> </div>
            <?php if($imagePath): ?>
                <div class="image-container" style="height: 450px; max-height: 450px; width: 100%; max-width: 800px; margin-left: auto; margin-right: auto; display: flex; align-items: center; justify-content: center; overflow: hidden; margin-bottom: 18px; border-radius: 16px; background: #f5f6fa; box-shadow: 0 2px 8px rgba(80,120,200,0.10);">
                    <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Konu Resmi" style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; border-radius: 16px; image-rendering: high-quality; transition: transform 0.3s ease; box-shadow: 0 4px 12px rgba(0,0,0,0.1); backface-visibility: hidden; transform: translateZ(0);">
                </div>
            <?php endif; ?>
            <div class="topic-footer" style="display:flex;justify-content:center;gap:32px;margin-top:1.5rem;">
                <span class="stat-item" style="color:#888;"><i class="fas fa-eye"></i> <?php echo $topic['view_count']; ?> görüntülenme</span>
                <span class="stat-item" style="color:#888;"><i class="fas fa-comment"></i> <?php echo $topic['comment_count']; ?> yorum</span>
                <div class="vote-buttons">
                    <button class="vote-btn<?php if($user_vote == 'up') echo ' upvoted'; ?>" type="button" onclick="vote(<?php echo $topic['id']; ?>, 'up'); event.stopPropagation();" id="upvote-<?php echo $topic['id']; ?>">
                        <i class="fas fa-arrow-up"></i>
                    </button>
                    <span class="vote-count" id="vote-count-<?php echo $topic['id']; ?>"><?php echo $topic['vote_count']; ?></span>
                    <button class="vote-btn<?php if($user_vote == 'down') echo ' downvoted'; ?>" type="button" onclick="vote(<?php echo $topic['id']; ?>, 'down'); event.stopPropagation();" id="downvote-<?php echo $topic['id']; ?>">
                        <i class="fas fa-arrow-down"></i>
                    </button>
                </div>
            </div>
            <hr style="margin:2rem 0;">
            <div class="comments-section">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="mb-0">Yorumlar</h4>
                    <div class="btn-group">
                        <a href="?id=<?php echo $topic_id; ?>&sort=newest" class="btn btn-outline-primary btn-sm <?php echo $sort === 'newest' ? 'active' : ''; ?>">
                            <i class="fas fa-clock"></i> En Yeni
                        </a>
                        <a href="?id=<?php echo $topic_id; ?>&sort=oldest" class="btn btn-outline-primary btn-sm <?php echo $sort === 'oldest' ? 'active' : ''; ?>">
                            <i class="fas fa-history"></i> En Eski
                        </a>
                    </div>
                </div>

                <?php if (isset($_SESSION['user_id'])): ?>
                    <form method="post" class="comment-form mb-4">
                        <div class="position-relative">
                            <textarea name="content" id="comment-textarea" class="form-control mb-2" rows="3" placeholder="Yorumunuzu yazın..." required></textarea>
                            <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="bottom: 15px; right: 10px;" onclick="toggleEmojiPicker('comment-emoji-picker')">
                                <i class="far fa-smile"></i>
                            </button>
                            <div id="comment-emoji-picker" class="emoji-picker" style="display: none; position: absolute; bottom: 50px; right: 0; z-index: 1000;"></div>
                        </div>
                        <button type="submit" class="btn btn-primary">Yorum Yap</button>
                    </form>
                <?php else: ?>
                    <div class="alert alert-info">
                        Yorum yapmak için <a href="login.php">giriş yapın</a> veya <a href="register.php">kayıt olun</a>.
                    </div>
                <?php endif; ?>

                <?php if (empty($comments)): ?>
                    <div class="alert alert-info">Henüz yorum yapılmamış.</div>
                <?php else: ?>
                    <?php foreach ($comments as $comment): ?>
                        <div class="comment mb-3">
                            <div class="comment-header d-flex justify-content-between align-items-center mb-2">
                                <div class="d-flex align-items-center">
                                    <?php
                                    $commentProfilePhoto = !empty($comment['profile_photo']) ? (str_starts_with($comment['profile_photo'], 'uploads/') ? $comment['profile_photo'] : 'uploads/profiles/' . $comment['profile_photo']) : 'assets/images/default-avatar.png';
                                    ?>
                                    <img src="<?php echo htmlspecialchars($commentProfilePhoto); ?>" alt="<?php echo htmlspecialchars($comment['username']); ?>" class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                    <div>
                                        <h6 class="mb-0"><?php echo htmlspecialchars($comment['username']); ?></h6>
                                        <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?></small>
                                    </div>
                                </div>
                                <div class="comment-actions">
                                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteComment(<?php echo $comment['id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="editComment(<?php echo $comment['id']; ?>, '<?php echo htmlspecialchars(addslashes($comment['content']), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); ?>')">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                    <?php endif; ?>
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['user_id'] != $comment['user_id']): ?>
                                        <form method="post" class="d-inline">
                                            <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                            <button type="submit" name="report_comment" class="btn btn-sm btn-outline-warning" onclick="return confirm('Bu yorumu bildirmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-flag"></i>
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="comment-content mb-2" id="comment-content-<?php echo $comment['id']; ?>" style="white-space:normal; word-wrap:break-word; word-break:break-word;">
                                <?php echo nl2br(htmlspecialchars($comment['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?>
                            </div>
                            <div class="comment-footer">
                                <button type="button" class="btn btn-sm btn-link reply-btn" onclick="showReplyForm(<?php echo $comment['id']; ?>)">
                                    <i class="fas fa-reply"></i> Yanıtla
                                </button>
                                <?php if ($comment['reply_count'] > 0): ?>
                                    <button type="button" class="btn btn-sm btn-link" onclick="toggleReplies(<?php echo $comment['id']; ?>)">
                                        <i class="fas fa-comments"></i> <?php echo $comment['reply_count']; ?> Yanıt
                                    </button>
                                <?php endif; ?>
                            </div>
                            <div id="reply-form-<?php echo $comment['id']; ?>" class="reply-form mt-2" style="display: none;">
                                <form method="post">
                                    <input type="hidden" name="parent_id" value="<?php echo $comment['id']; ?>">
                                    <div class="position-relative">
                                        <textarea name="content" id="reply-textarea-<?php echo $comment['id']; ?>" class="form-control mb-2" rows="2" placeholder="Yanıtınızı yazın..." required></textarea>
                                        <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="bottom: 15px; right: 10px;" onclick="toggleEmojiPicker('reply-emoji-picker-<?php echo $comment['id']; ?>')">
                                            <i class="far fa-smile"></i>
                                        </button>
                                        <div id="reply-emoji-picker-<?php echo $comment['id']; ?>" class="emoji-picker" style="display: none; position: absolute; bottom: 50px; right: 0; z-index: 1000;"></div>
                                    </div>
                                    <button type="submit" class="btn btn-primary btn-sm">Yanıtla</button>
                                    <button type="button" class="btn btn-secondary btn-sm" onclick="hideReplyForm(<?php echo $comment['id']; ?>)">İptal</button>
                                </form>
                            </div>
                            <div id="replies-<?php echo $comment['id']; ?>" class="replies mt-2" style="display: none;"></div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <footer class="footer-bar bg-white text-center py-3 mt-5" style="border-top:1px solid #eee;font-weight:600;font-size:1.1rem;color:#4a90e2;">
        Made by Berat Karataş
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    // Emoji picker işlevselliği
    document.addEventListener('DOMContentLoaded', function() {
        // Ana yorum için emoji picker oluştur
        const commentEmojiPicker = document.createElement('emoji-picker');
        document.getElementById('comment-emoji-picker').appendChild(commentEmojiPicker);

        // Emoji seçildiğinde tetiklenecek olay
        commentEmojiPicker.addEventListener('emoji-click', event => {
            const textarea = document.getElementById('comment-textarea');
            const emoji = event.detail.unicode;

            // İmlecin konumunu al
            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;

            // Emojiyi imlecin olduğu yere ekle
            textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);

            // İmleci emojiden sonraya taşı
            textarea.selectionStart = textarea.selectionEnd = start + emoji.length;

            // Emoji picker'ı gizle
            document.getElementById('comment-emoji-picker').style.display = 'none';

            // Textarea'ya odaklan
            textarea.focus();
        });

        // Yanıt formları için emoji picker'ları oluştur
        document.querySelectorAll('[id^="reply-emoji-picker-"]').forEach(pickerContainer => {
            const commentId = pickerContainer.id.replace('reply-emoji-picker-', '');
            const replyEmojiPicker = document.createElement('emoji-picker');
            pickerContainer.appendChild(replyEmojiPicker);

            replyEmojiPicker.addEventListener('emoji-click', event => {
                const textarea = document.getElementById(`reply-textarea-${commentId}`);
                const emoji = event.detail.unicode;

                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;

                textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);
                textarea.selectionStart = textarea.selectionEnd = start + emoji.length;

                pickerContainer.style.display = 'none';
                textarea.focus();
            });
        });
    });

    // Emoji picker'ı göster/gizle
    function toggleEmojiPicker(pickerId) {
        const picker = document.getElementById(pickerId);
        picker.style.display = picker.style.display === 'none' ? 'block' : 'none';
    }

    // Bildirimi okundu olarak işaretle
    function markAsRead(notificationId) {
        fetch('mark_notification_read.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `notification_id=${notificationId}`
        });
    }

    // Tüm bildirimleri okundu olarak işaretle
    function markAllAsRead() {
        fetch('mark_all_notifications_read.php', {
            method: 'POST'
        })
        .then(() => {
            // Sayfa yenileme
            location.reload();
        });
    }

    // Bildirimi sil
    function deleteNotification(notificationId) {
        if (confirm('Bu bildirimi silmek istediğinize emin misiniz?')) {
            fetch('delete_notification.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `notification_id=${notificationId}`
            })
            .then(() => {
                // Sayfa yenileme
                location.reload();
            });
        }
    }

    function deleteComment(commentId) {
        if (confirm('Bu yorumu silmek istediğinizden emin misiniz?')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="comment_id" value="${commentId}">
                <input type="hidden" name="delete_comment" value="1">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    }

    function editComment(commentId, content) {
        const contentDiv = document.getElementById(`comment-content-${commentId}`);
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = `
            <input type="hidden" name="comment_id" value="${commentId}">
            <input type="hidden" name="edit_comment" value="1">
            <div class="position-relative">
                <textarea name="content" class="form-control mb-2" required>${content}</textarea>
                <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="bottom: 15px; right: 10px;" onclick="toggleEmojiPicker('edit-emoji-picker-${commentId}')">
                    <i class="far fa-smile"></i>
                </button>
                <div id="edit-emoji-picker-${commentId}" class="emoji-picker" style="display: none; position: absolute; bottom: 50px; right: 0; z-index: 1000;"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-sm">Kaydet</button>
            <button type="button" class="btn btn-secondary btn-sm" onclick="cancelEdit(${commentId}, '${content}')">İptal</button>
        `;
        contentDiv.innerHTML = '';
        contentDiv.appendChild(form);

        // Düzenleme formu için emoji picker oluştur
        const editEmojiPicker = document.createElement('emoji-picker');
        document.getElementById(`edit-emoji-picker-${commentId}`).appendChild(editEmojiPicker);

        editEmojiPicker.addEventListener('emoji-click', event => {
            const textarea = form.querySelector('textarea');
            const emoji = event.detail.unicode;

            const start = textarea.selectionStart;
            const end = textarea.selectionEnd;

            textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);
            textarea.selectionStart = textarea.selectionEnd = start + emoji.length;

            document.getElementById(`edit-emoji-picker-${commentId}`).style.display = 'none';
            textarea.focus();
        });
    }

    function cancelEdit(commentId, content) {
        const contentDiv = document.getElementById(`comment-content-${commentId}`);
        contentDiv.innerHTML = content;
    }

    function showReplyForm(commentId) {
        document.getElementById(`reply-form-${commentId}`).style.display = 'block';
    }

    function hideReplyForm(commentId) {
        document.getElementById(`reply-form-${commentId}`).style.display = 'none';
    }

    function toggleReplies(commentId) {
        const repliesDiv = document.getElementById(`replies-${commentId}`);
        if (repliesDiv.style.display === 'none') {
            // Yanıtları getir
            fetch(`get_replies.php?comment_id=${commentId}`)
                .then(response => response.text())
                .then(html => {
                    repliesDiv.innerHTML = html;
                    repliesDiv.style.display = 'block';
                });
        } else {
            repliesDiv.style.display = 'none';
        }
    }

    function vote(topicId, voteType) {
        fetch('vote.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `topic_id=${topicId}&vote_type=${voteType}`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Dinamik olarak UI'ı güncelle
                const upvoteBtn = document.querySelectorAll(`#upvote-${topicId}`);
                const downvoteBtn = document.querySelectorAll(`#downvote-${topicId}`);
                const voteCountElements = document.querySelectorAll(`#vote-count-${topicId}`);

                // Oy butonlarının stillerini güncelle
                upvoteBtn.forEach(btn => btn.classList.remove('upvoted'));
                downvoteBtn.forEach(btn => btn.classList.remove('downvoted'));

                if (data.user_vote === 'up') {
                    upvoteBtn.forEach(btn => btn.classList.add('upvoted'));
                } else if (data.user_vote === 'down') {
                    downvoteBtn.forEach(btn => btn.classList.add('downvoted'));
                }

                // Oy sayısını güncelle
                voteCountElements.forEach(element => {
                    element.textContent = data.vote_count;
                });
            } else {
                alert(data.message);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Bir hata oluştu. Lütfen tekrar deneyin.');
        });
    }
    </script>
</body>
</html> 
