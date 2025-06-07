<?php
session_start();
require_once 'config.php';
global $conn;

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = trim($_POST['title']);
    $content = trim($_POST['content']);
    $user_id = $_SESSION['user_id'];

    if (empty($title) || empty($content)) {
        $error = "Başlık ve içerik alanları zorunludur.";
    } else {
        // Resim yükleme işlemi
        $image = '';
        if (isset($_FILES['image']) && $_FILES['image']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['image']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $upload_dir = 'uploads/topics/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }

                $new_filename = uniqid() . '.' . $ext;
                $upload_path = $upload_dir . $new_filename;

                if (move_uploaded_file($_FILES['image']['tmp_name'], $upload_path)) {
                    $image = $new_filename;
                } else {
                    $error = "Resim yüklenirken bir hata oluştu.";
                }
            } else {
                $error = "Sadece JPG, JPEG, PNG ve GIF formatları desteklenmektedir.";
            }
        }

        if (empty($error)) {
            $sql = "INSERT INTO topics (user_id, title, content, image) VALUES (?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "isss", $user_id, $title, $content, $image);

            if (mysqli_stmt_execute($stmt)) {
                header("Location: index.php");
                exit();
            } else {
                $error = "Konu oluşturulurken bir hata oluştu: " . mysqli_error($conn);
            }
            mysqli_stmt_close($stmt);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yeni Gönderi - Derdimvar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.css">
    <script type="module" src="https://cdn.jsdelivr.net/npm/emoji-picker-element@^1/index.js"></script>
    <style>
        body {
            background: #fff;
            font-family: 'Poppins', sans-serif;
            color: #2c3e50;
        }
        .navbar {
            background: #fff !important;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 900;
            color: #2c3e50 !important;
        }
        .nav-link {
            color: #2c3e50 !important;
            font-weight: 700;
            font-size: 1.15rem;
        }
        .nav-link:hover {
            color: #4a90e2 !important;
        }
        .card {
            border: none;
            border-radius: 28px;
            box-shadow: 0 8px 32px rgba(80, 120, 200, 0.10);
            margin-top: 40px;
        }
        .card-header {
            background: none;
            color: #2c3e50;
            border-radius: 28px 28px 0 0 !important;
            padding: 32px 40px 0 40px;
            border-bottom: none;
        }
        .card-body {
            padding: 40px 40px 32px 40px;
        }
        .form-label {
            font-weight: 600;
            color: #4a90e2;
        }
        .form-control, textarea.form-control {
            border-radius: 14px;
            border: 2px solid #e0e0e0;
            font-size: 1.1rem;
            padding: 1rem;
            margin-bottom: 1rem;
            font-family: inherit;
        }
        .form-control:focus, textarea.form-control:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74,144,226,0.15);
            outline: none;
        }
        .preview-image {
            max-width: 100%;
            max-height: 260px;
            margin-top: 10px;
            border-radius: 14px;
            display: none;
            box-shadow: 0 2px 8px rgba(80,120,200,0.10);
        }
        .btn-primary {
            background: linear-gradient(135deg, #4a90e2 0%, #357abd 100%);
            border: none;
            padding: 0.8rem 1.5rem;
            font-weight: 500;
            font-size: 1.15rem;
            border-radius: 12px;
            transition: all 0.3s ease;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(74,144,226,0.18);
        }
        .btn-secondary {
            border-radius: 12px;
            font-size: 1.08rem;
        }
        .d-grid.gap-2 {
            gap: 1rem !important;
        }
        @media (max-width: 768px) {
            .card-header, .card-body {
                padding: 1.2rem !important;
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
                    <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
                    <?php if ($current_page != 'index.php'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Ana Sayfa</a>
                    </li>
                    <?php endif; ?>
                    <?php if ($current_page != 'odevler.php'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="odevler.php">Ödevler</a>
                    </li>
                    <?php endif; ?>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
                                    JOIN users u ON n.sender_id = u.id 
                                    LEFT JOIN topics t ON n.topic_id = t.id 
                                    LEFT JOIN comments c ON n.comment_id = c.id 
                                    WHERE n.user_id = ? 
                                    ORDER BY n.created_at DESC 
                                    LIMIT 10";
                            $stmt = mysqli_prepare($conn, $sql);
                            mysqli_stmt_bind_param($stmt, "i", $user_id);
                            mysqli_stmt_execute($stmt);
                            $notifications = mysqli_stmt_get_result($stmt);

                            if (mysqli_num_rows($notifications) > 0):
                                while ($notification = mysqli_fetch_assoc($notifications)):
                                    $is_read_class = $notification['is_read'] ? 'text-muted' : 'fw-bold';
                                    $notification_text = '';
                                    $link = '';

                                    if ($notification['type'] == 'comment') {
                                        $notification_text = "<strong>{$notification['sender_name']}</strong> konunuza yorum yaptı: \"{$notification['topic_title']}\"";
                                        $link = "konu.php?id={$notification['topic_id']}";
                                    } else if ($notification['type'] == 'reply') {
                                        $notification_text = "<strong>{$notification['sender_name']}</strong> yorumunuza yanıt verdi";
                                        $link = "konu.php?id={$notification['topic_id']}#comment-{$notification['comment_id']}";
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
                    <?php if (isset($_SESSION['user_id']) && $current_page != 'profil.php'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> Profilim</a>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['user_id']) && $current_page != 'yeni-konu.php'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="yeni-konu.php"><i class="fas fa-plus"></i> Yeni Gönderi</a>
                    </li>
                    <?php endif; ?>
                    <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="admin/index.php">
                                <i class="fas fa-tools"></i> Yönetici Paneli
                            </a>
                        </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-plus"></i> Yeni Gönderi Oluştur</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="title" class="form-label">Başlık</label>
                                <input type="text" class="form-control" id="title" name="title" required
                                       value="<?php echo isset($_POST['title']) ? htmlspecialchars($_POST['title']) : ''; ?>">
                            </div>

                            <div class="mb-3">
                                <label for="content" class="form-label">İçerik</label>
                                <div class="position-relative">
                                    <textarea class="form-control" id="content" name="content" rows="6" required><?php 
                                        echo isset($_POST['content']) ? htmlspecialchars($_POST['content']) : ''; 
                                    ?></textarea>
                                    <button type="button" class="btn btn-sm btn-outline-secondary position-absolute" style="bottom: 15px; right: 10px;" onclick="toggleEmojiPicker('content-emoji-picker')">
                                        <i class="far fa-smile"></i>
                                    </button>
                                    <div id="content-emoji-picker" class="emoji-picker" style="display: none; position: absolute; bottom: 50px; right: 0; z-index: 1000;"></div>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="image" class="form-label">Resim (İsteğe bağlı)</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*"
                                       onchange="previewImage(this)">
                                <img id="preview" class="preview-image" alt="Resim önizleme">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane"></i> Gönderiyi Paylaş
                                </button>
                                <a href="index.php" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left"></i> Geri Dön
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Emoji picker işlevselliği
        document.addEventListener('DOMContentLoaded', function() {
            // İçerik için emoji picker oluştur
            const contentEmojiPicker = document.createElement('emoji-picker');
            document.getElementById('content-emoji-picker').appendChild(contentEmojiPicker);

            // Emoji seçildiğinde tetiklenecek olay
            contentEmojiPicker.addEventListener('emoji-click', event => {
                const textarea = document.getElementById('content');
                const emoji = event.detail.unicode;

                // İmlecin konumunu al
                const start = textarea.selectionStart;
                const end = textarea.selectionEnd;

                // Emojiyi imlecin olduğu yere ekle
                textarea.value = textarea.value.substring(0, start) + emoji + textarea.value.substring(end);

                // İmleci emojiden sonraya taşı
                textarea.selectionStart = textarea.selectionEnd = start + emoji.length;

                // Emoji picker'ı gizle
                document.getElementById('content-emoji-picker').style.display = 'none';

                // Textarea'ya odaklan
                textarea.focus();
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

        function previewImage(input) {
            const preview = document.getElementById('preview');
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    preview.style.display = 'block';
                }
                reader.readAsDataURL(input.files[0]);
            } else {
                preview.style.display = 'none';
            }
        }
    </script>
    <footer class="footer-bar bg-white text-center py-3 mt-5" style="border-top:1px solid #eee;font-weight:600;font-size:1.1rem;color:#4a90e2;">
        Made by Berat Karataş
    </footer>
</body>
</html> 
