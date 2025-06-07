<?php
session_start();
require_once 'config.php';
global $conn;

if (!isset($_GET['comment_id'])) {
    exit('Geçersiz istek');
}

$comment_id = (int)$_GET['comment_id'];

// Yanıtları getir
$sql = "SELECT c.*, u.username, u.profile_photo
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE c.parent_id = ?
        ORDER BY c.created_at ASC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $comment_id);
mysqli_stmt_execute($stmt);
$replies = mysqli_stmt_get_result($stmt);

while ($reply = mysqli_fetch_assoc($replies)): ?>
    <div class="reply mb-2">
        <div class="d-flex align-items-center mb-2">
            <?php
            $replyProfilePhoto = !empty($reply['profile_photo']) ? (str_starts_with($reply['profile_photo'], 'uploads/') ? $reply['profile_photo'] : 'uploads/profiles/' . $reply['profile_photo']) : 'assets/images/default-avatar.png';
            ?>
            <img src="<?php echo htmlspecialchars($replyProfilePhoto); ?>" alt="<?php echo htmlspecialchars($reply['username']); ?>" class="rounded-circle me-2" style="width: 32px; height: 32px; object-fit: cover;">
            <div>
                <h6 class="mb-0"><?php echo htmlspecialchars($reply['username']); ?></h6>
                <small class="text-muted"><?php echo date('d.m.Y H:i', strtotime($reply['created_at'])); ?></small>
            </div>
        </div>
        <div class="reply-content" style="white-space:normal; word-wrap:break-word; word-break:break-word;">
            <?php echo nl2br(htmlspecialchars($reply['content'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')); ?>
        </div>
    </div>
<?php endwhile; ?> 
