<?php
session_start();
require_once '../config.php';

// Yönetici kontrolü
if (!isset($_SESSION['user_id']) || !isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
    header("Location: ../login.php");
    exit();
}

// İstatistikleri getir
$stats = [
    'users' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM users"))['count'],
    'topics' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM topics"))['count'],
    'comments' => mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as count FROM comments"))['count']
];

// Son eklenen kullanıcıları getir
$recent_users = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC LIMIT 5");

// Son eklenen konuları getir
$recent_topics = mysqli_query($conn, "SELECT t.*, u.username 
                                    FROM topics t 
                                    JOIN users u ON t.user_id = u.id 
                                    ORDER BY t.created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yönetici Paneli - Derdimvar</title>
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
        .stat-card {
            background: linear-gradient(45deg, #2c3e50, #3498db);
            color: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .stat-card i {
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .stat-card h3 {
            font-size: 2rem;
            margin-bottom: 0;
        }
        .btn-primary {
            background-color: #3498db;
            border-color: #3498db;
        }
        .btn-primary:hover {
            background-color: #2980b9;
            border-color: #2980b9;
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
        <div class="row">
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-users"></i>
                    <h3><?php echo $stats['users']; ?></h3>
                    <p class="mb-0">Toplam Kullanıcı</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-comments"></i>
                    <h3><?php echo $stats['topics']; ?></h3>
                    <p class="mb-0">Toplam Konu</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card text-center">
                    <i class="fas fa-reply-all"></i>
                    <h3><?php echo $stats['comments']; ?></h3>
                    <p class="mb-0">Toplam Yorum</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-users"></i> Son Eklenen Kullanıcılar</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php while ($user = mysqli_fetch_assoc($recent_users)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($user['username']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($user['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-comments"></i> Son Eklenen Konular</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group">
                            <?php while ($topic = mysqli_fetch_assoc($recent_topics)): ?>
                                <div class="list-group-item">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($topic['title']); ?></h6>
                                        <small class="text-muted">
                                            <?php echo date('d.m.Y H:i', strtotime($topic['created_at'])); ?>
                                        </small>
                                    </div>
                                    <p class="mb-1"><?php echo htmlspecialchars($topic['username']); ?> tarafından paylaşıldı</p>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="fas fa-cog"></i> Hızlı İşlemler</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-3">
                                <a href="users.php" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-users"></i> Kullanıcıları Yönet
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="topics.php" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-comments"></i> Konuları Yönet
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="comments.php" class="btn btn-primary w-100 mb-2">
                                    <i class="fas fa-reply-all"></i> Yorumları Yönet
                                </a>
                            </div>
                            <div class="col-md-3">
                                <a href="../index.php" class="btn btn-secondary w-100 mb-2">
                                    <i class="fas fa-home"></i> Siteye Dön
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 