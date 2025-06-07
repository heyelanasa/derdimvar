<?php
session_start();
require_once 'config.php';
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Site Ödevlerim - DerdimVar</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .assignments-container {
            max-width: 800px;
            margin: 50px auto;
        }
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 0 20px rgba(0,0,0,0.07);
            margin-bottom: 20px;
        }
        .card-header {
            background-color: #4a90e2;
            color: white;
            border-radius: 20px 20px 0 0 !important;
            padding: 24px;
        }
    </style>
</head>
<body>
    <!-- Modern Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm mb-4" style="font-size:1.15rem;">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="index.php" style="font-weight:900;font-size:1.8rem;color:#2c3e50;">
                <i class="fas fa-comment-dots me-2" style="color:#4a90e2;font-size:2.1rem;"></i> Derdimvar
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="#navbarNav" aria-expanded="false" aria-label="Toggle navigation">
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
                    <?php if (isset($_SESSION['user_id'])): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php"><i class="fas fa-sign-out-alt"></i> Çıkış Yap</a>
                    </li>
                    <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="login.php"><i class="fas fa-sign-in-alt"></i> Giriş Yap</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <div class="assignments-container">
        <div class="card">
            <div class="card-header">
                <h3 class="mb-0"><i class="fas fa-tasks"></i> Site Ödevlerim</h3>
            </div>
            <div class="card-body">
                <div class="odevler-list">
                    <div class="odev-card">
                        <h3>1. Ödev: PHP Tablo ve Hesap Makinesi</h3>
                        <a href="odev1/index.php" class="btn btn-primary">1. Ödev</a>
                    </div>
                    <div class="odev-card">
                        <h3>2. Ödev: Form ve Proje Kayıt Ekranı</h3>
                        <a href="odev2/index.php" class="btn btn-primary">2. Ödev</a>
                    </div>
                    <div class="odev-card">
                        <h3>3. Ödev: PHP Koşul, Döngü ve Loto Kuponu</h3>
                        <a href="odev3/index.php" class="btn btn-primary">3. Ödev</a>
                    </div>
                    <div class="odev-card">
                        <h3>4. Ödev: PHP Faiz, Tablo, Dizi ve Loto Uygulamaları</h3>
                        <a href="odev4/form.php" class="btn btn-primary">4. Ödev</a>
                    </div>
                    <div class="odev-card">
                        <h3>5. Ödev: PHP Çiçekçi Muzaffer</h3>
                        <a href="odev5/index.php" class="btn btn-primary">5. Ödev</a>
                    </div>
                    <div class="odev-card">
                        <h3>6. Ödev: PHP Kitapçı Uygulaması</h3>
                        <a href="odev6/index.php" class="btn btn-primary">6. Ödev</a>
                    </div>
                </div>
                <div clss = "odev-card">
                    <h3>Github Linki</h3>
                        <a href="https://github.com/heyelanasa/Derdimvar" target="_blank" style="background-color: #111827; color: white; padding: 6px 12px; border-radius: 6px; text-decoration: none;">GitHub</a>
                </div>
            </div>
        </div>
    </div>

    <footer class="footer-bar bg-white text-center py-3 mt-5" style="border-top:1px solid #eee;font-weight:600;font-size:1.1rem;color:#4a90e2;">
        Made by Berat Karataş
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 
