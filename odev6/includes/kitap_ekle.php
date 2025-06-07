<?php
require_once __DIR__ . '/../db.php';
$hata = '';
$basari = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $k_adi = trim($_POST['k_adi'] ?? '');
    $k_yazar = trim($_POST['k_yazar'] ?? '');
    $k_yayinevi = trim($_POST['k_yayinevi'] ?? '');
    $k_resmi = trim($_POST['k_resmi'] ?? '');
    $k_fiyat = trim($_POST['k_fiyat'] ?? '');
    $k_etiket = trim($_POST['k_etiket'] ?? '');
    if ($k_adi && $k_yazar && $k_yayinevi && $k_resmi && $k_fiyat && $k_etiket) {
        $stmt = $db->prepare("INSERT INTO kitap (k_adi, k_yazar, k_yayinevi, k_resmi, k_fiyat, k_etiket) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('ssssds', $k_adi, $k_yazar, $k_yayinevi, $k_resmi, $k_fiyat, $k_etiket);
        if ($stmt->execute()) {
            $basari = 'Kitap başarıyla eklendi!';
        } else {
            $hata = 'Bir hata oluştu.';
        }
        $stmt->close();
    } else {
        $hata = 'Lütfen tüm alanları doldurun!';
    }
}
?>
<?php if($hata): ?><div style="color:#b00;"> <?php echo $hata; ?> </div><?php endif; ?>
<?php if($basari): ?><div style="color:#080;"> <?php echo $basari; ?> </div><?php endif; ?>
<form method="post" class="kitap-ekle-form">
    <input type="text" name="k_adi" placeholder="Kitap Adı" required>
    <input type="text" name="k_yazar" placeholder="Yazar" required>
    <input type="text" name="k_yayinevi" placeholder="Yayınevi" required>
    <input type="text" name="k_resmi" placeholder="Kapak Görseli (URL)" required>
    <input type="number" step="0.01" name="k_fiyat" placeholder="Fiyat (TL)" required>
    <input type="text" name="k_etiket" placeholder="Etiket (örn: Roman, Çocuk, Tarih)" required>
    <input type="submit" value="Kitap Ekle">
</form> 