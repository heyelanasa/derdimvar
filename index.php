<?php
session_start();
require_once 'config.php';
global $conn;

// Arama sorgusu
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$where_clause = '';
$params = [];
$types = '';

if (!empty($search)) {
    $search_terms = explode(' ', $search);
    $search_conditions = [];

    foreach ($search_terms as $term) {
        if (strlen($term) >= 2) {
            $search_conditions[] = "(t.title LIKE ? OR t.content LIKE ?)";
            $term = "%" . $term . "%";
            $params[] = $term;
            $params[] = $term;
            $types .= 'ss';
        }
    }

    if (!empty($search_conditions)) {
        $where_clause = "WHERE " . implode(' AND ', $search_conditions);
    }
}

// Oturum kontrolü
$logged_in = isset($_SESSION['user_id']);
$is_admin = isset($_SESSION['is_admin']) && $_SESSION['is_admin'];

// Kullanıcının oylarını çek
$user_votes = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $vote_query = mysqli_query($conn, "SELECT topic_id, vote_type FROM votes WHERE user_id = $user_id");
    while($row = mysqli_fetch_assoc($vote_query)) {
        $user_votes[$row['topic_id']] = $row['vote_type'];
    }
}

// Konuları getir
$topics_query = "SELECT t.*, u.username, u.profile_photo,
    (SELECT COUNT(*) FROM comments WHERE topic_id = t.id) as comment_count,
    (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'up') -
    (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'down') as vote_count,
    t.view_count as view_count
    FROM topics t
    JOIN users u ON t.user_id = u.id
    $where_clause
    ORDER BY vote_count DESC, t.created_at DESC";

if (!empty($search) && !empty($params)) {
    $stmt = mysqli_prepare($conn, $topics_query);
    if ($stmt === false) {
        die('Prepare failed: ' . htmlspecialchars(mysqli_error($conn)));
    }
    mysqli_stmt_bind_param($stmt, $types, ...$params);
    mysqli_stmt_execute($stmt);
    $topics_result = mysqli_stmt_get_result($stmt);
} else {
    $topics_result = mysqli_query($conn, $topics_query);
}

// En son 5 gönderi (yeni açılan gönderiler)
$new_topics_query = "SELECT id, title, image FROM topics ORDER BY created_at DESC LIMIT 5";
$new_topics_result = mysqli_query($conn, $new_topics_query);
?>

<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Derdimvar - Şikayet Platformu</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
        }

        .search-box {
            position: relative;
            max-width: 500px;
            margin: 0 auto;
            padding: 1rem;
        }

        .search-box input {
            width: 100%;
            padding: 0.8rem 1rem;
            padding-left: 3rem;
            border: 2px solid #e0e0e0;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: #ffffff;
        }

        .search-box input:focus {
            border-color: #4a90e2;
            box-shadow: 0 0 0 0.2rem rgba(74,144,226,0.25);
            outline: none;
        }

        .search-box i {
            position: absolute;
            left: 1.5rem;
            top: 50%;
            transform: translateY(-50%);
            color: #666;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1rem;
        }

        .section-title {
            font-size: 1.8rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            color: #2c3e50;
            position: relative;
            padding-bottom: 0.5rem;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 60px;
            height: 3px;
            background: linear-gradient(90deg, #4a90e2, #357abd);
            border-radius: 3px;
        }

        .trending-topics {
            position: relative;
            padding: 1rem 0;
            margin: 2rem 0;
        }

        .trending-topics-container {
            display: flex;
            gap: 1rem;
            overflow-x: auto;
            scroll-behavior: smooth;
            padding: 1rem 0;
            -ms-overflow-style: none;
            scrollbar-width: none;
        }

        .trending-topics-container::-webkit-scrollbar {
            display: none;
        }

        .trending-topic-card {
            flex: 0 0 300px;
            background: #ffffff;
            border-radius: 12px;
            padding: 1rem;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .trending-topic-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .scroll-indicator {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.9);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            z-index: 10;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .scroll-left {
            left: 0;
        }

        .scroll-right {
            right: 0;
        }

        .trending-topics:hover .scroll-indicator {
            opacity: 1;
        }

        .scroll-indicator i {
            color: #2c3e50;
            font-size: 1.2rem;
        }

        .scroll-indicator:hover {
            background: #ffffff;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .topic-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .user-info {
            display: flex;
            align-items: center;
            gap: 0.8rem;
        }

        .profile-pic {
            width: 40px;
            height: 40px;
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
            font-size: 1.2rem;
            font-weight: 600;
            color: #2c3e50;
            margin-bottom: 0.5rem;
        }

        .topic-content {
            color: #666;
            margin-bottom: 1rem;
            line-height: 1.6;
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

        .hot-topics {
            background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%);
            border-radius: 15px;
            padding: 2rem;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
        }

        .all-topics {
            background: #ffffff;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
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

        @media (max-width: 768px) {
            .container {
                padding: 1rem;
            }

            .section-title {
                font-size: 1.5rem;
            }

            .trending-topics .topic-card {
                min-width: 260px;
            }
        }

        .topic-card {
            cursor: pointer;
            transition: box-shadow 0.2s, transform 0.2s;
            box-shadow: 0 8px 32px rgba(80, 120, 200, 0.10);
            border-radius: 28px;
            padding: 48px 40px 40px 40px;
            margin-bottom: 40px;
            background: #fff;
            position: relative;
        }
        .topic-card:hover {
            box-shadow: 0 16px 40px rgba(80, 120, 200, 0.13);
            transform: translateY(-4px) scale(1.02);
        }
        .topic-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 18px;
        }
        .vote-buttons {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .vote-btn {
            background: none;
            border: none;
            color: #888;
            cursor: pointer;
            padding: 6px;
            font-size: 1.2rem;
            transition: color 0.2s;
        }
        .vote-btn:hover, .vote-btn.active {
            color: #4a90e2;
        }
        .vote-count {
            font-weight: 600;
            color: #222;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <!-- Modern ve sade beyaz navbar başlangıcı -->
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
                                    } else if ($notification['type'] == 'system') {
                                        $notification_text = $notification['message'];
                                        $link = "#";
                                    } else if ($notification['type'] == 'admin') {
                                        $notification_text = "<strong>Admin:</strong> {$notification['message']}";
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
                    <?php if (isset($_SESSION['user_id']) && $current_page != 'profil.php'): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="profil.php"><i class="fas fa-user"></i> Profilim</a>
                    </li>
                    <?php endif; ?>
                    <?php if ($current_page != 'yeni-konu.php'): ?>
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
                    <li class="nav-item">
                        <a class="nav-link" href="register.php"><i class="fas fa-user-plus"></i> Kayıt Ol</a>
                    </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>
    <!-- Modern ve sade beyaz navbar bitişi -->

    <div class="search-box">
        <form action="index.php" method="GET" class="input-group">
            <input type="text" class="form-control" id="searchInput" name="search" placeholder="Gönderilerde ara..." value="<?php echo htmlspecialchars($search); ?>">
            <button class="btn" type="submit">
                <i class="fas fa-search"></i>
            </button>
        </form>
    </div>

    <div class="container" style="max-width: 1600px;">
        <?php if (empty($search)): ?>
        <section class="trending-topics">
            <h2 class="section-title">Gündemdeki Gönderiler</h2>
            <div class="trending-topics-scroll-wrapper" style="position:relative;display:flex;align-items:center;">
                <button class="scroll-indicator scroll-left" type="button" aria-label="Sola Kaydır" style="left:-24px;position:absolute;z-index:2;display:flex;align-items:center;justify-content:center;" onclick="scrollTrending(-1)"><i class="fas fa-chevron-left"></i></button>
                <div class="trending-topics-container" style="overflow-x:auto;scroll-behavior:smooth;display:flex;gap:24px;width:100%;padding:8px 0;">
                    <?php
                    $trending_query = "SELECT t.*, u.username, u.profile_photo,
                        (SELECT COUNT(*) FROM comments WHERE topic_id = t.id) as comment_count,
                        (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'up') - 
                        (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'down') as vote_count,
                        t.view_count as view_count
                        FROM topics t
                        JOIN users u ON t.user_id = u.id
                        WHERE (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'up') - 
                              (SELECT COUNT(*) FROM votes WHERE topic_id = t.id AND vote_type = 'down') >= 1
                          AND t.view_count >= 5
                        ORDER BY vote_count DESC, t.view_count DESC
                        LIMIT 10";
                    $trending_result = $conn->query($trending_query);

                    while($topic = $trending_result->fetch_assoc()):
                    $profilePhoto = !empty($topic['profile_photo']) ? (str_starts_with($topic['profile_photo'], 'uploads/') ? $topic['profile_photo'] : 'uploads/profiles/' . $topic['profile_photo']) : 'assets/images/default-avatar.png';
                    ?>
                    <div class="trending-topic-card" onclick="window.location='konu.php?id=<?php echo $topic['id']; ?>'" style="cursor:pointer;min-width:320px;max-width:340px;">
                        <div class="topic-header">
                            <div class="user-info">
                                <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="<?php echo htmlspecialchars($topic['username']); ?>" class="profile-pic">
                                <span class="username"><?php echo htmlspecialchars($topic['username']); ?></span>
                            </div>
                        </div>
                        <h3 class="topic-title">
                            <a href="konu.php?id=<?php echo $topic['id']; ?>">
                                <?php echo htmlspecialchars($topic['title']); ?>
                            </a>
                        </h3>
                        <p class="homepage-topic-content"><?php echo htmlspecialchars($topic['content']); ?></p>
                        <?php if($topic['image']): ?>
                            <?php
                            $imagePath = $topic['image'];
                            if ($imagePath && !str_starts_with($imagePath, 'assets/') && !str_starts_with($imagePath, 'uploads/')) {
                                $imagePath = 'uploads/topics/' . $imagePath;
                            }
                            ?>
                            <div class="image-container">
                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Konu Resmi">
                            </div>
                        <?php endif; ?>
                        <div class="topic-footer">
                            <div class="topic-stats">
                                <span class="stat-item"><i class="fas fa-comment"></i> <?php echo $topic['comment_count']; ?></span>
                                <span class="stat-item"><i class="fas fa-eye"></i> <?php echo $topic['view_count'] ?? 0; ?></span>
                            </div>
                            <div class="vote-buttons">
                                <button class="vote-btn<?php if(isset($user_votes[$topic['id']]) && $user_votes[$topic['id']] == 'up') echo ' upvoted'; ?>" type="button" onclick="vote(<?php echo $topic['id']; ?>, 'up'); event.stopPropagation();" id="upvote-<?php echo $topic['id']; ?>">
                                    <i class="fas fa-arrow-up"></i>
                                </button>
                                <span class="vote-count" id="vote-count-<?php echo $topic['id']; ?>"><?php echo $topic['vote_count']; ?></span>
                                <button class="vote-btn<?php if(isset($user_votes[$topic['id']]) && $user_votes[$topic['id']] == 'down') echo ' downvoted'; ?>" type="button" onclick="vote(<?php echo $topic['id']; ?>, 'down'); event.stopPropagation();" id="downvote-<?php echo $topic['id']; ?>">
                                    <i class="fas fa-arrow-down"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?php endwhile; ?>
                </div>
                <button class="scroll-indicator scroll-right" type="button" aria-label="Sağa Kaydır" style="right:-24px;position:absolute;z-index:2;display:flex;align-items:center;justify-content:center;" onclick="scrollTrending(1)"><i class="fas fa-chevron-right"></i></button>
            </div>
        </section>
        <?php endif; ?>

        <section class="hot-topics">
            <div class="row" style="margin-left:0;">
                <div class="col-lg-8 col-md-12" style="padding-left:0;">
                    <div class="all-topics">
                        <h3 class="section-title mb-4">Tüm Gönderiler</h3>
                        <div class="row" style="margin-left:0;">
                            <?php while($topic = mysqli_fetch_assoc($topics_result)):
                                $profilePhoto = !empty($topic['profile_photo']) ? (str_starts_with($topic['profile_photo'], 'uploads/') ? $topic['profile_photo'] : 'uploads/profiles/' . $topic['profile_photo']) : 'assets/images/default-avatar.png';
                            ?>
                                <div class="col-12 mb-4" style="padding-left:0;">
                                    <div class="topic-card" onclick="window.location='konu.php?id=<?php echo $topic['id']; ?>'">
                                        <div class="topic-header">
                                            <img src="<?php echo htmlspecialchars($profilePhoto); ?>" alt="Profil Fotoğrafı" class="profile-pic">
                                            <div class="user-info">
                                                <span class="username"><?php echo htmlspecialchars($topic['username']); ?></span>
                                            </div>
                                        </div>
                                        <div class="topic-title">
                                            <a href="konu.php?id=<?php echo $topic['id']; ?>"><?php echo htmlspecialchars($topic['title']); ?></a>
                                        </div>
                                        <div class="homepage-topic-content"><?php echo htmlspecialchars($topic['content']); ?></div>
                                        <?php if ($topic['image']): ?>
                                            <?php
                                            $imagePath = $topic['image'];
                                            if ($imagePath && !str_starts_with($imagePath, 'assets/') && !str_starts_with($imagePath, 'uploads/')) {
                                                $imagePath = 'uploads/topics/' . $imagePath;
                                            }
                                            ?>
                                            <div class="image-container">
                                                <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Konu Resmi">
                                            </div>
                                        <?php endif; ?>
                                        <div class="topic-footer">
                                            <div class="topic-stats">
                                                <span class="stat-item"><i class="fas fa-comment"></i> <?php echo $topic['comment_count']; ?></span>
                                                <span class="stat-item"><i class="fas fa-eye"></i> <?php echo $topic['view_count'] ?? 0; ?></span>
                                            </div>
                                            <div class="vote-buttons">
                                                <button class="vote-btn<?php if(isset(
                                                    $user_votes[$topic['id']]) && $user_votes[$topic['id']] == 'up') echo ' upvoted'; ?>" type="button" onclick="vote(<?php echo $topic['id']; ?>, 'up'); event.stopPropagation();" id="upvote-<?php echo $topic['id']; ?>">
                                                    <i class="fas fa-arrow-up"></i>
                                                </button>
                                                <span class="vote-count" id="vote-count-<?php echo $topic['id']; ?>"><?php echo $topic['vote_count']; ?></span>
                                                <button class="vote-btn<?php if(isset(
                                                    $user_votes[$topic['id']]) && $user_votes[$topic['id']] == 'down') echo ' downvoted'; ?>" type="button" onclick="vote(<?php echo $topic['id']; ?>, 'down'); event.stopPropagation();" id="downvote-<?php echo $topic['id']; ?>">
                                                    <i class="fas fa-arrow-down"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-12" style="padding-right:0;">
                    <div class="card shadow-sm p-3 mb-4 bg-white rounded new-topics-card">
                        <h4 class="mb-3" style="font-weight:700;color:#4a90e2;text-align:center;">Yeni Açılan Gönderiler</h4>
                        <ul class="list-group list-group-flush">
                            <?php 
                            $new_topics_query = "SELECT t.id, t.title, t.image, t.created_at, u.username 
                                               FROM topics t 
                                               JOIN users u ON t.user_id = u.id 
                                               ORDER BY t.created_at DESC 
                                               LIMIT 10";
                            $new_topics_result = mysqli_query($conn, $new_topics_query);
                            while($new_topic = mysqli_fetch_assoc($new_topics_result)): ?>
                                <li class="list-group-item d-flex align-items-center" style="cursor:pointer; text-align: left !important;" onclick="window.location='konu.php?id=<?php echo $new_topic['id']; ?>'">
                                    <?php
                                    $imagePath = $new_topic['image'];
                                    if ($imagePath && !str_starts_with($imagePath, 'assets/') && !str_starts_with($imagePath, 'uploads/')) {
                                        $imagePath = 'uploads/topics/' . $imagePath;
                                    }
                                    ?>
                                    <?php if($imagePath): ?>
                                        <img src="<?php echo htmlspecialchars($imagePath); ?>" alt="Gönderi Resmi" style="width: 50px; height: 50px; object-fit: cover; border-radius: 8px; margin-right: 15px; flex-shrink: 0;">
                                    <?php endif; ?>
                                    <div class="flex-grow-1" style="min-width: 0; text-align: left !important;">
                                        <span class="d-block text-decoration-none" style="font-weight:600;color:#2c3e50;white-space:nowrap;overflow:hidden;text-overflow:ellipsis; text-align: left !important;">
                                            <?php echo htmlspecialchars($new_topic['title']); ?>
                                        </span>
                                        <small class="text-muted d-block" style="white-space:nowrap;overflow:hidden;text-overflow:ellipsis; text-align: left !important;">
                                            <?php echo htmlspecialchars($new_topic['username']); ?> tarafından
                                        </small>
                                    </div>
                                </li>
                            <?php endwhile; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="footer-bar bg-white text-center py-3 mt-5" style="border-top:1px solid #eee;font-weight:600;font-size:1.1rem;color:#4a90e2;">
        Made by Berat Karataş
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
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
                // Tüm sayfalarda aynı mantıkla güncelle
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

    document.addEventListener('DOMContentLoaded', function() {
        const container = document.querySelector('.trending-topics-container');
        const leftBtn = document.querySelector('.trending-scroll-left');
        const rightBtn = document.querySelector('.trending-scroll-right');

        if (container && leftBtn && rightBtn) {
            const scrollAmount = 300; // Her tıklamada kaydırılacak piksel miktarı

            leftBtn.addEventListener('click', () => {
                container.scrollBy({
                    left: -scrollAmount,
                    behavior: 'smooth'
                });
            });

            rightBtn.addEventListener('click', () => {
                container.scrollBy({
                    left: scrollAmount,
                    behavior: 'smooth'
                });
            });

            // Scroll butonlarının görünürlüğünü kontrol et
            const checkScrollButtons = () => {
                leftBtn.style.display = container.scrollLeft > 0 ? 'flex' : 'none';
                rightBtn.style.display = 
                    container.scrollLeft < (container.scrollWidth - container.clientWidth) 
                    ? 'flex' : 'none';
            };

            // Sayfa yüklendiğinde ve scroll edildiğinde butonları kontrol et
            checkScrollButtons();
            container.addEventListener('scroll', checkScrollButtons);
        }

    });

    function scrollTrending(direction) {
        const container = document.querySelector('.trending-topics-container');
        if (!container) return;
        const scrollAmount = 340 + 24; // kart genişliği + gap
        container.scrollBy({
            left: direction * scrollAmount,
            behavior: 'smooth'
        });
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
    </script>
</body>
</html> 
