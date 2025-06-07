<?php
session_start();
require_once 'config.php';
global $conn;

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $password_confirm = $_POST['password_confirm'];
    $error = '';

    // Kullanıcı adı kontrolü
    $sql = "SELECT id FROM users WHERE username = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_store_result($stmt);
    if (mysqli_stmt_num_rows($stmt) > 0) {
        $error = "Bu kullanıcı adı zaten kullanılıyor.";
    }

    // E-posta kontrolü
    if (empty($error)) {
        $sql = "SELECT id FROM users WHERE email = ?";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "s", $email);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_store_result($stmt);
        if (mysqli_stmt_num_rows($stmt) > 0) {
            $error = "Bu e-posta adresi zaten kullanılıyor.";
        }
    }

    // Şifre kontrolü
    if (empty($error) && $password !== $password_confirm) {
        $error = "Şifreler eşleşmiyor.";
    }

    // Profil fotoğrafı işleme
    $profile_photo = null;
    if (empty($error)) {
        if (isset($_FILES['profile_photo']) && $_FILES['profile_photo']['error'] == 0) {
            $allowed = ['jpg', 'jpeg', 'png', 'gif'];
            $filename = $_FILES['profile_photo']['name'];
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));

            if (in_array($ext, $allowed)) {
                $new_filename = uniqid() . '.' . $ext;
                $upload_path = 'uploads/profiles/' . $new_filename;

                if (!is_dir('uploads/profiles')) {
                    mkdir('uploads/profiles', 0777, true);
                }

                if (move_uploaded_file($_FILES['profile_photo']['tmp_name'], $upload_path)) {
                    $profile_photo = $new_filename;
                } else {
                    $error = "Profil fotoğrafı yüklenirken bir hata oluştu.";
                }
            } else {
                $error = "Sadece JPG, JPEG, PNG ve GIF dosyaları yüklenebilir.";
            }
        } elseif (isset($_POST['selected_avatar'])) {
            $profile_photo = $_POST['selected_avatar'];
        }
    }

    // Kullanıcı kaydı
    if (empty($error)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $sql = "INSERT INTO users (username, email, password, profile_photo) VALUES (?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "ssss", $username, $email, $hashed_password, $profile_photo);

        if (mysqli_stmt_execute($stmt)) {
            // Yeni kullanıcının ID'sini al
            $new_user_id = mysqli_insert_id($conn);

            // Derdimvar kullanıcısını bul veya oluştur
            $derdimvar_user_id = null;
            $sql = "SELECT id FROM users WHERE username = 'Derdimvar'";
            $result = mysqli_query($conn, $sql);

            if (mysqli_num_rows($result) > 0) {
                $derdimvar_user = mysqli_fetch_assoc($result);
                $derdimvar_user_id = $derdimvar_user['id'];
            } else {
                // Derdimvar kullanıcısı yoksa oluştur
                $hashed_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
                $sql = "INSERT INTO users (username, email, password, is_admin) VALUES ('Derdimvar', 'derdimvar@example.com', ?, TRUE)";
                $stmt = mysqli_prepare($conn, $sql);
                if (!$stmt) {
                    die("Prepare failed for Derdimvar user: " . mysqli_error($conn));
                }
                mysqli_stmt_bind_param($stmt, "s", $hashed_password);
                if (!mysqli_stmt_execute($stmt)) {
                    die("Execute failed for Derdimvar user: " . mysqli_stmt_error($stmt));
                }
                $derdimvar_user_id = mysqli_insert_id($conn);
                if (!$derdimvar_user_id) {
                    die("Failed to get Derdimvar user ID: " . mysqli_error($conn));
                }
            }

            // Hoşgeldin mesajı oluştur
            $welcome_message = "DerdimVar'a hoş geldin!
Artık sen de dertlerini, düşüncelerini ve fikirlerini özgürce paylaşabileceğin bir yerdesin. Diğer kullanıcılarla etkileşime geç, yorum yap, fikir alışverişinde bulun. Unutma, burada herkesin bir derdi var!";

            // Debug için mesajı yazdır
            error_log("Welcome message: " . $welcome_message);

            // Bildirim tablosuna ekle
            $sql = "INSERT INTO notifications (user_id, sender_id, type, message, is_read) VALUES (?, ?, 'system', ?, 0)";
            $stmt = mysqli_prepare($conn, $sql);
            if (!$stmt) {
                die("Prepare failed: " . mysqli_error($conn));
            }
            mysqli_stmt_bind_param($stmt, "iis", $new_user_id, $derdimvar_user_id, $welcome_message);
            if (!mysqli_stmt_execute($stmt)) {
                die("Execute failed: " . mysqli_stmt_error($stmt));
            }

            $_SESSION['success'] = "Kayıt başarılı! Şimdi giriş yapabilirsiniz.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Kayıt sırasında bir hata oluştu.";
        }
    }
}

// Hazır profil resimleri
$default_avatars = [
    'avatar1.jpg' => 'https://i.pravatar.cc/150?img=1',
    'avatar2.jpg' => 'https://i.pravatar.cc/150?img=2',
    'avatar3.jpg' => 'https://i.pravatar.cc/150?img=3',
    'avatar4.jpg' => 'https://i.pravatar.cc/150?img=4',
    'avatar5.jpg' => 'https://i.pravatar.cc/150?img=5',
    'avatar6.jpg' => 'https://i.pravatar.cc/150?img=6',
    'avatar7.jpg' => 'https://i.pravatar.cc/150?img=7',
    'avatar8.jpg' => 'https://i.pravatar.cc/150?img=8',
];

// Hazır resimleri indir
$upload_dir = 'uploads/profiles/';
if (!file_exists($upload_dir)) {
    mkdir($upload_dir, 0777, true);
}

foreach ($default_avatars as $filename => $url) {
    if (!file_exists($upload_dir . $filename)) {
        $image_data = file_get_contents($url);
        file_put_contents($upload_dir . $filename, $image_data);
    }
}
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kayıt Ol - Derdimvar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f0f2f5;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        .register-container {
            max-width: 800px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .register-card {
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
            padding: 2rem;
            margin-bottom: 2rem;
        }
        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .register-header h2 {
            color: #2c3e50;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }
        .register-header p {
            color: #6c757d;
            margin-bottom: 0;
        }
        .form-label {
            color: #2c3e50;
            font-weight: 500;
        }
        .form-control {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem 1rem;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
            padding: 0.75rem 1.5rem;
            font-weight: 500;
            border-radius: 8px;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .avatar-options {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .avatar-option {
            position: relative;
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            transition: all 0.3s ease;
        }
        .avatar-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        .avatar-option.selected {
            border: 3px solid #3498db;
        }
        .avatar-option img {
            width: 100%;
            height: 100px;
            object-fit: cover;
        }
        .avatar-option .check-icon {
            position: absolute;
            top: 5px;
            right: 5px;
            background: #3498db;
            color: white;
            border-radius: 50%;
            padding: 2px;
            display: none;
        }
        .avatar-option.selected .check-icon {
            display: block;
        }
        .custom-file-upload {
            border: 2px dashed #dee2e6;
            border-radius: 10px;
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: 1rem;
        }
        .custom-file-upload:hover {
            border-color: #3498db;
            background-color: #f8f9fa;
        }
        .custom-file-upload i {
            font-size: 2rem;
            color: #6c757d;
            margin-bottom: 0.5rem;
        }
        .preview-image {
            max-width: 200px;
            max-height: 200px;
            border-radius: 10px;
            margin-top: 1rem;
            display: none;
        }
        .alert {
            border-radius: 10px;
            margin-bottom: 1.5rem;
        }
        .login-link {
            text-align: center;
            margin-top: 1.5rem;
            color: #6c757d;
        }
        .login-link a {
            color: #3498db;
            text-decoration: none;
            font-weight: 500;
        }
        .login-link a:hover {
            text-decoration: underline;
        }
        .email-suggestions {
            position: absolute;
            background: white;
            border: 1px solid #dee2e6;
            border-radius: 0 0 8px 8px;
            width: 100%;
            max-width: 300px;
            z-index: 1000;
            display: none;
        }
        .email-suggestion {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .email-suggestion:hover {
            background-color: #f8f9fa;
        }
        .email-suggestion.selected {
            background-color: #e9ecef;
        }
        .suggestion-item {
            padding: 8px 12px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .suggestion-item:hover,
        .suggestion-item.selected {
            background-color: #f8f9fa;
        }
        #emailSuggestions {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            z-index: 1000;
            display: none;
        }
        .default-avatar {
            cursor: pointer;
            transition: transform 0.2s, box-shadow 0.2s;
            border: 2px solid transparent;
        }
        .default-avatar:hover {
            transform: scale(1.05);
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .default-avatar.selected {
            border-color: #3498db;
            box-shadow: 0 0 0 2px rgba(52, 152, 219, 0.3);
        }
        #avatar-preview {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #3498db;
            margin: 10px auto;
            display: block;
        }
        .avatar-upload {
            text-align: center;
            margin-top: 15px;
        }
        .avatar-upload label {
            display: inline-block;
            padding: 8px 16px;
            background: #3498db;
            color: white;
            border-radius: 4px;
            cursor: pointer;
            transition: background-color 0.2s;
        }
        .avatar-upload label:hover {
            background: #2980b9;
        }
        .avatar-upload input[type="file"] {
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
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="odevler.php">Ödevler</a>
                    </li>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" style="font-weight:700;" href="register.php">Kayıt Ol</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="login.php">Giriş Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container d-flex justify-content-center align-items-center" style="min-height: 90vh;">
        <div class="register-card w-100" style="max-width: 500px;">
            <div class="register-header">
                <h2>Kayıt Ol</h2>
                <p>Üniversite öğrencileri için şikayet ve öneri platformuna katıl!</p>
            </div>
            <?php if (!empty($error)): ?>
                <div class="alert alert-danger"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="post" enctype="multipart/form-data">
                <div class="mb-3">
                    <label class="form-label">Kullanıcı Adı</label>
                    <input type="text" name="username" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">E-posta</label>
                    <input type="email" name="email" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Şifre (Tekrar)</label>
                    <input type="password" name="password_confirm" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Profil Fotoğrafı (isteğe bağlı)</label>

                    <!-- Hazır avatarlar -->
                    <div class="card mb-3">
                        <div class="card-header bg-light">
                            <h6 class="mb-0">Hazır Avatar Seç</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <?php
                                foreach ($default_avatars as $filename => $url): ?>
                                    <div class="col-3">
                                        <label class="avatar-option w-100">
                                            <input type="radio" name="selected_avatar" value="<?php echo $filename; ?>" style="display:none;">
                                            <img src="uploads/profiles/<?php echo $filename; ?>" alt="Avatar" class="img-fluid rounded-circle border">
                                            <span class="check-icon"><i class="fas fa-check"></i></span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Kendi fotoğrafını yükle - daha belirgin -->
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h6 class="mb-0"><i class="fas fa-upload me-2"></i> Kendi Fotoğrafını Yükle</h6>
                        </div>
                        <div class="card-body">
                            <div class="custom-file-upload p-3 text-center">
                                <input type="file" name="profile_photo" id="profile_photo_upload" class="form-control" style="display: none;">
                                <label for="profile_photo_upload" class="btn btn-primary mb-2">
                                    <i class="fas fa-camera me-2"></i> Dosya Seç
                                </label>
                                <div id="file-name" class="mt-2 text-muted">Henüz dosya seçilmedi</div>
                            </div>
                        </div>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary w-100">Kayıt Ol</button>
            </form>
            <div class="text-center mt-3">
                <span style="color:#2c3e50;font-weight:500;">Zaten hesabın var mı?</span>
                <a href="login.php" style="font-weight:700;color:#3498db;text-decoration:none;">Giriş yap</a>
            </div>
        </div>
    </div>
    <footer class="footer-bar bg-white text-center py-3 mt-5" style="border-top:1px solid #eee;font-weight:600;font-size:1.1rem;color:#4a90e2;">
        Made by Berat Karataş
    </footer>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Avatar seçimi için görselde seçili efekti
        document.querySelectorAll('.avatar-option input[type="radio"]').forEach(function(radio) {
            radio.addEventListener('change', function() {
                document.querySelectorAll('.avatar-option').forEach(function(opt) {
                    opt.classList.remove('selected');
                });
                this.closest('.avatar-option').classList.add('selected');
            });
        });

        // Dosya seçildiğinde dosya adını göster
        document.getElementById('profile_photo_upload').addEventListener('change', function() {
            const fileName = this.files[0] ? this.files[0].name : 'Henüz dosya seçilmedi';
            document.getElementById('file-name').textContent = fileName;

            // Dosya seçildiğinde hazır avatarların seçimini kaldır
            document.querySelectorAll('.avatar-option input[type="radio"]').forEach(function(radio) {
                radio.checked = false;
            });
            document.querySelectorAll('.avatar-option').forEach(function(opt) {
                opt.classList.remove('selected');
            });
        });
    </script>
</body>
</html> 
