<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kitapçı</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
    <header>
        <h1>Kitapçı</h1>
        <nav>
            <a href="index.php">Anasayfa</a>
            <a href="urunler.php">Ürünler</a>
            <?php if (isset($_SESSION['is_admin']) && $_SESSION['is_admin']): ?>
                <a href="../admin/index.php">Admin Paneli</a>
            <?php endif; ?>
        </nav>
    </header>
    <main> 