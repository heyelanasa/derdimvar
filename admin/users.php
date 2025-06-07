<?php
session_start();
require_once '../config.php';
global $conn;

// Yönetici kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

// Kullanıcı silme işlemi
if (isset($_POST['delete_user'])) {
    $user_id = $_POST['user_id'];
    mysqli_query($conn, "DELETE FROM users WHERE id = $user_id");
    header("Location: users.php");
    exit();
}

// Kullanıcı yetkisi değiştirme işlemi
if (isset($_POST['toggle_admin'])) {
    $user_id = $_POST['user_id'];
    $current_status = mysqli_fetch_assoc(mysqli_query($conn, "SELECT is_admin FROM users WHERE id = $user_id"))['is_admin'];
    $new_status = $current_status ? 0 : 1;
    mysqli_query($conn, "UPDATE users SET is_admin = $new_status WHERE id = $user_id");
    // Eğer kendi hesabımızsa session'ı da güncelle
    if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
        $_SESSION['is_admin'] = $new_status;
    }
    header("Location: users.php");
    exit();
}

// Kullanıcıya mesaj gönderme işlemi
if (isset($_POST['send_message'])) {
    $recipient_id = (int)$_POST['recipient_id'];
    $message = trim($_POST['message']);
    $sender_id = $_SESSION['user_id'];

    if (!empty($message)) {
        // Admin kullanıcısını bul veya oluştur
        $admin_user_id = null;
        $sql = "SELECT id FROM users WHERE username = 'Admin'";
        $result = mysqli_query($conn, $sql);

        if (mysqli_num_rows($result) > 0) {
            $admin_user = mysqli_fetch_assoc($result);
            $admin_user_id = $admin_user['id'];
        } else {
            // Admin kullanıcısı yoksa oluştur
            $hashed_password = password_hash(bin2hex(random_bytes(8)), PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (username, email, password, is_admin) VALUES ('Admin', 'admin@example.com', ?, TRUE)";
            $stmt = mysqli_prepare($conn, $sql);
            mysqli_stmt_bind_param($stmt, "s", $hashed_password);
            mysqli_stmt_execute($stmt);
            $admin_user_id = mysqli_insert_id($conn);
        }

        // Bildirim oluştur
        $sql = "INSERT INTO notifications (user_id, sender_id, type, message, is_read) VALUES (?, ?, 'admin', ?, 0)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "iis", $recipient_id, $admin_user_id, $message);

        if (mysqli_stmt_execute($stmt)) {
            $_SESSION['message_success'] = "Mesaj başarıyla gönderildi.";
        } else {
            $_SESSION['message_error'] = "Mesaj gönderilirken bir hata oluştu.";
        }
    } else {
        $_SESSION['message_error'] = "Mesaj boş olamaz.";
    }

    header("Location: users.php");
    exit();
}

// Kullanıcıları getir
$users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kullanıcı Yönetimi - Derdimvar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        .admin-container {
            max-width: 1200px;
            margin: 50px auto;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #2c3e50;
            color: white;
            border-radius: 10px 10px 0 0 !important;
            padding: 20px;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
        }
        .btn-danger {
            background-color: #e74c3c;
            border-color: #e74c3c;
        }
        .btn-danger:hover {
            background-color: #c0392b;
            border-color: #c0392b;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4" style="font-size:1.15rem;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="../index.php" style="font-weight:900;font-size:1.8rem;color:#2c3e50;">
                <i class="fas fa-comment-dots me-2" style="color:#4a90e2;font-size:2.1rem;"></i> Derdimvar
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="../index.php">Ana Sayfa</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="../odevler.php">Ödevler</a>
                    </li>
                </ul>
                <ul class="navbar-nav mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link active" style="font-weight:700;" href="index.php">Yönetici Paneli</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" style="font-weight:700;" href="../logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="admin-container">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0"><i class="fas fa-users"></i> Kullanıcı Yönetimi</h5>
                <a href="index.php" class="btn btn-light btn-sm">
                    <i class="fas fa-arrow-left"></i> Geri Dön
                </a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Kullanıcı Adı</th>
                                <th>E-posta</th>
                                <th>Kayıt Tarihi</th>
                                <th>Yönetici</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($user = mysqli_fetch_assoc($users)): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php if ($user['is_admin']): ?>
                                            <span class="badge bg-success">Evet</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Hayır</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                            <button type="submit" name="toggle_admin" class="btn btn-primary btn-sm">
                                                <?php echo $user['is_admin'] ? 'Yönetici Yetkisini Al' : 'Yönetici Yap'; ?>
                                            </button>
                                            <button type="button" class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#sendMessageModal" 
                                                    onclick="setRecipient(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars($user['username']); ?>')">
                                                <i class="fas fa-envelope"></i> Mesaj Gönder
                                            </button>
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <button type="submit" name="delete_user" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Bu kullanıcıyı silmek istediğinizden emin misiniz?')">
                                                    <i class="fas fa-trash"></i> Sil
                                                </button>
                                            <?php endif; ?>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Mesaj Gönderme Modal -->
    <div class="modal fade" id="sendMessageModal" tabindex="-1" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="sendMessageModalLabel">Kullanıcıya Mesaj Gönder</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <?php if (isset($_SESSION['message_success'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['message_success']; unset($_SESSION['message_success']); ?></div>
                        <?php endif; ?>
                        <?php if (isset($_SESSION['message_error'])): ?>
                            <div class="alert alert-danger"><?php echo $_SESSION['message_error']; unset($_SESSION['message_error']); ?></div>
                        <?php endif; ?>
                        <input type="hidden" name="recipient_id" id="recipient_id">
                        <div class="mb-3">
                            <label for="recipient_name" class="form-label">Alıcı:</label>
                            <input type="text" class="form-control" id="recipient_name" readonly>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Mesaj:</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">İptal</button>
                        <button type="submit" name="send_message" class="btn btn-primary">Gönder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function setRecipient(userId, username) {
            document.getElementById('recipient_id').value = userId;
            document.getElementById('recipient_name').value = username;
        }
    </script>
</body>
</html> 
