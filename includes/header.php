        <nav>
            <a href="index.php">Anasayfa</a>
            <a href="urunler.php">Ürünler</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="admin/index.php">Admin Paneli</a>
            <?php endif; ?>
        </nav> 