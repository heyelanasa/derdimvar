<?php
if (isset($_GET['kuponSayisi']) && is_numeric($_GET['kuponSayisi']) && $_GET['kuponSayisi'] > 0) {
    $kuponSayisi = $_GET['kuponSayisi'];
} else {
    die("Lütfen geçerli bir kupon sayısı giriniz.");
}

// Rastgele 6 sayı seçen fonksiyon
function rastgeleKupon(): array {
    $sayilar = range(1, 90);
    shuffle($sayilar);  // Sayıları karıştır
    return array_slice($sayilar, 0, 6);  // İlk 6 sayıyı seç
}

// Kuponları oluştur
$kuponlar = [];
for ($i = 0; $i < $kuponSayisi; $i++) {
    $kuponlar[] = rastgeleKupon();
}
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuponlar</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2>Oluşturduğunuz Sayısal Loto Kuponları</h2>
        <div class="row">
            <?php foreach ($kuponlar as $index => $kupon): ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Kupon <?php echo $index + 1; ?></h5>
                            <p class="card-text">
                                <?php sort($kupon); // Sayıları küçükten büyüğe sırala ?>
                                <?php echo implode(' - ', $kupon); ?>
                            </p>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
        <a href="index.php" class="btn btn-secondary">Yeni Kuponlar Oluştur</a>
    </div>

    <!-- Bootstrap JS ve Popper JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>
</html> 