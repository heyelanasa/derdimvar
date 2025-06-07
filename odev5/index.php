<?php
include("cods/baglan.php");
include("cods/yonlendir.php");
include("cods/head.php");
?>
<body>
    <div class="container-sm">
        <?php $current_page = basename($_SERVER['PHP_SELF']); ?>
        <div style="margin: 20px 0;"><a href="../index.php" style="background:#4a90e2;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600;">Derdimvar Ana Sayfa</a></div>
        <?php
        include("cods/navbar.php")
            ?>
        <div class="row">
            <?php
            include("cods/" . $page);
            ?>
        </div>
    </div>
    <?php
    include("cods/footer.php");
    ?>

</body>
</rewritten_file> 
