<?php
session_start();
require_once '../config.php';

// Yönetici kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

// Konu silme işlemi
if (isset($_POST['delete_topic'])) {
    $topic_id = $_POST['topic_id'];
    mysqli_query($conn, "DELETE FROM comments WHERE topic_id = $topic_id");
    mysqli_query($conn, "DELETE FROM topics WHERE id = $topic_id");
    header("Location: topics.php");
    exit();
}

// Konuları getir
$topics = mysqli_query($conn, "SELECT t.*, u.username 
                             FROM topics t 
                             JOIN users u ON t.user_id = u.id 
                             ORDER BY t.created_at DESC");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konu Yönetimi - Derdimvar</title>
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
                <h5 class="mb-0"><i class="fas fa-comments"></i> Konu Yönetimi</h5>
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
                                <th>Başlık</th>
                                <th>İçerik</th>
                                <th>Yazar</th>
                                <th>Tarih</th>
                                <th>İşlemler</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($topic = mysqli_fetch_assoc($topics)): ?>
                                <tr>
                                    <td><?php echo $topic['id']; ?></td>
                                    <td><?php echo htmlspecialchars($topic['title']); ?></td>
                                    <td><?php echo mb_substr(htmlspecialchars($topic['content']), 0, 100) . '...'; ?></td>
                                    <td><?php echo htmlspecialchars($topic['username']); ?></td>
                                    <td><?php echo date('d.m.Y H:i', strtotime($topic['created_at'])); ?></td>
                                    <td>
                                        <form method="POST" action="" class="d-inline">
                                            <input type="hidden" name="topic_id" value="<?php echo $topic['id']; ?>">
                                            <a href="../konu.php?id=<?php echo $topic['id']; ?>" class="btn btn-primary btn-sm">
                                                <i class="fas fa-eye"></i> Görüntüle
                                            </a>
                                            <button type="submit" name="delete_topic" class="btn btn-danger btn-sm" 
                                                    onclick="return confirm('Bu konuyu ve tüm yorumlarını silmek istediğinizden emin misiniz?')">
                                                <i class="fas fa-trash"></i> Sil
                                            </button>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 