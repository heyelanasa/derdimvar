<?php
session_start();
require_once 'config.php';

// Oturum kontrolü
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Hata ayıklama için
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Kullanıcı bilgilerini getir
$user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = '$user_id'");
if (!$user_query) {
    die("Veritabanı hatası: " . mysqli_error($conn));
}

$user_data = mysqli_fetch_assoc($user_query);
if (!$user_data) {
    die("Kullanıcı bulunamadı. User ID: " . $user_id);
}

// Profil güncelleme işlemi
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['update_profile'])) {
        $username = trim($_POST['username']);
        $email = trim($_POST['email']);
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];

        // Kullanıcı adı ve email kontrolü
        $check_sql = "SELECT id FROM users WHERE (username = '$username' OR email = '$email') AND id != $user_id";
        $check_result = mysqli_query($conn, $check_sql);

        if (mysqli_num_rows($check_result) > 0) {
            $error = "Bu kullanıcı adı veya email adresi başka bir kullanıcı tarafından kullanılıyor.";
        } else {
            $update_fields = [];

            // Profil resmi yükleme
            if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
                $allowed = ['jpg', 'jpeg', 'png', 'gif'];
                $filename = $_FILES['profile_photo']['name'];
                $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

                if (in_array($ext, $allowed)) {
                    $upload_dir = 'uploads/profiles/';
                    if (!file_exists($upload_dir)) {
                        mkdir($upload_dir, 0777, true);
                    }

                    $new_filename = uniqid() . '.' . $ext;
                    $upload_path = $upload_dir . $new_filename;

                    if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                        // Eski profil resmini sil
                        if (!empty($user_data['profile_photo'])) {
                            $old_file = $upload_dir . $user_data['profile_photo'];
                            if (file_exists($old_file)) {
                                unlink($old_file);
                            }
                        }
                        $update_fields[] = "profile_photo = '$new_filename'";
                    } else {
                        $error = "Profil resmi yüklenirken bir hata oluştu.";
                    }
                } else {
                    $error = "Sadece JPG, JPEG, PNG ve GIF formatları desteklenmektedir.";
                }
            }

            // Şifre değişikliği
            if (!empty($current_password)) {
                if (password_verify($current_password, $user_data['password'])) {
                    if (!empty($new_password)) {
                        if ($new_password === $confirm_password) {
                            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                            $update_fields[] = "password = '$hashed_password'";
                        } else {
                            $error = "Yeni şifreler eşleşmiyor.";
                        }
                    }
                } else {
                    $error = "Mevcut şifre yanlış.";
                }
            }

            // Kullanıcı adı ve email güncelleme
            if ($username !== $user_data['username']) {
                $update_fields[] = "username = '" . mysqli_real_escape_string($conn, $username) . "'";
            }
            if ($email !== $user_data['email']) {
                $update_fields[] = "email = '" . mysqli_real_escape_string($conn, $email) . "'";
            }

            if (empty($error) && !empty($update_fields)) {
                $update_sql = "UPDATE users SET " . implode(", ", $update_fields) . " WHERE id = $user_id";
                if (mysqli_query($conn, $update_sql)) {
                    $success = "Profil başarıyla güncellendi.";
                    // Güncel kullanıcı bilgilerini al
                    $user_query = mysqli_query($conn, "SELECT * FROM users WHERE id = $user_id");
                    $user_data = mysqli_fetch_assoc($user_query);
                } else {
                    $error = "Profil güncellenirken bir hata oluştu: " . mysqli_error($conn);
                }
            }
        }
    }
}

// Kullanıcının konularını getir
$topics = mysqli_query($conn, "SELECT t.*, 
                             (SELECT COUNT(*) FROM comments WHERE topic_id = t.id) as comment_count 
                             FROM topics t 
                             WHERE t.user_id = $user_id 
                             ORDER BY t.created_at DESC");

// Kullanıcının yorumlarını getir
$comments = mysqli_query($conn, "SELECT c.*, t.title as topic_title 
                               FROM comments c 
                               JOIN topics t ON c.topic_id = t.id 
                               WHERE c.user_id = $user_id 
                               ORDER BY c.created_at DESC");

$current_page = basename($_SERVER['PHP_SELF']);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profilim - Derdimvar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background: #f5f6fa;
            color: #222;
        }
        .profile-header {
            background: #fff;
            color: #2c3e50;
            padding: 40px 0 24px 0;
            margin-bottom: 30px;
            border-radius: 24px;
            box-shadow: 0 4px 24px rgba(80,120,200,0.10);
        }
        .profile-picture {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #e0e0e0;
            box-shadow: 0 0 12px rgba(0,0,0,0.08);
        }
        .card {
            border: none;
            border-radius: 18px;
            box-shadow: 0 8px 32px rgba(80, 120, 200, 0.10);
            margin-bottom: 20px;
        }
        .card-header {
            background: #fff;
            color: #2c3e50;
            border-radius: 18px 18px 0 0 !important;
            padding: 20px;
            font-weight: 700;
            font-size: 1.2rem;
        }
        .nav-pills .nav-link.active {
            background-color: #4a90e2;
            color: #fff !important;
            font-weight: 600;
            text-shadow: 0 1px 1px rgba(0,0,0,0.1);
        }
        .nav-pills .nav-link {
            color: #2c3e50;
            font-weight: 600;
        }
        .preview-image {
            max-width: 120px;
            max-height: 120px;
            border-radius: 50%;
            margin-top: 10px;
            display: none;
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

    <div class="profile-header">
        <div class="container text-center">
            <img src="<?php echo !empty($user_data['profile_photo']) ? 'uploads/profiles/' . htmlspecialchars($user_data['profile_photo']) : 'https://via.placeholder.com/150'; ?>" 
                 alt="Profil Resmi" class="profile-picture mb-3">
            <h2><?php echo htmlspecialchars($user_data['username']); ?></h2>
            <p class="text-light mb-0">
                <i class="fas fa-envelope"></i> <?php echo htmlspecialchars($user_data['email']); ?>
            </p>
        </div>
    </div>

    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-user-edit"></i> Profil Düzenle</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>

                        <form method="POST" action="" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="username" class="form-label">Kullanıcı Adı</label>
                                <input type="text" class="form-control" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user_data['username']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">E-posta Adresi</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user_data['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="profile_photo" class="form-label">Profil Resmi</label>
                                <input type="file" class="form-control" id="profile_photo" name="profile_photo" 
                                       accept="image/*" onchange="previewImage(this)">
                                <img id="preview" class="preview-image" alt="Profil resmi önizleme">
                            </div>

                            <hr>

                            <div class="mb-3">
                                <label for="current_password" class="form-label">Mevcut Şifre</label>
                                <input type="password" class="form-control" id="current_password" name="current_password">
                            </div>

                            <div class="mb-3">
                                <label for="new_password" class="form-label">Yeni Şifre</label>
                                <input type="password" class="form-control" id="new_password" name="new_password">
                            </div>

                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Yeni Şifre (Tekrar)</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password">
                            </div>

                            <div class="d-grid">
                                <button type="submit" name="update_profile" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Değişiklikleri Kaydet
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <ul class="nav nav-pills mb-4" id="profileTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="topics-tab" data-bs-toggle="tab" data-bs-target="#topics" 
                                type="button" role="tab">
                            <i class="fas fa-comments"></i> Konularım
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="comments-tab" data-bs-toggle="tab" data-bs-target="#comments" 
                                type="button" role="tab">
                            <i class="fas fa-reply"></i> Yorumlarım
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="profileTabsContent">
                    <div class="tab-pane fade show active" id="topics" role="tabpanel">
                        <?php if (mysqli_num_rows($topics) > 0): ?>
                            <?php while ($topic = mysqli_fetch_assoc($topics)): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <h5 class="card-title">
                                            <a href="konu.php?id=<?php echo $topic['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($topic['title']); ?>
                                            </a>
                                        </h5>
                                        <p class="card-text">
                                            <?php 
                                            $content = htmlspecialchars($topic['content']);
                                            echo mb_strlen($content) > 150 ? mb_substr($content, 0, 150) . '...' : $content;
                                            ?>
                                        </p>
                                        <div class="d-flex justify-content-between align-items-center">
                                            <small class="text-muted">
                                                <i class="fas fa-comments"></i> <?php echo $topic['comment_count']; ?> yorum
                                            </small>
                                            <small class="text-muted">
                                                <?php echo date('d.m.Y H:i', strtotime($topic['created_at'])); ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Henüz konu açmamışsınız.
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="tab-pane fade" id="comments" role="tabpanel">
                        <?php if (mysqli_num_rows($comments) > 0): ?>
                            <?php while ($comment = mysqli_fetch_assoc($comments)): ?>
                                <div class="card">
                                    <div class="card-body">
                                        <h6 class="card-subtitle mb-2 text-muted">
                                            <a href="konu.php?id=<?php echo $comment['topic_id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($comment['topic_title']); ?>
                                            </a>
                                            konusuna yorum yaptı
                                        </h6>
                                        <p class="card-text">
                                            <?php echo htmlspecialchars($comment['content']); ?>
                                        </p>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($comment['created_at'])); ?>
                                        </small>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> Henüz yorum yapmamışsınız.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-bar bg-white text-center py-3 mt-5" style="border-top:1px solid #eee;font-weight:600;font-size:1.1rem;color:#4a90e2;">
        Made by Berat Karataş
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
</body>
</html> 
