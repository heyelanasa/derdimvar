<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>
    <?php
    $sayi1 = $_GET["sayi1"];
    $sayi2 = $_GET["sayi2"];
    $islem = $_GET["islem"];

    switch ($islem) {
        case "toplama":
            $sonuc = $sayi1 + $sayi2;
            break;
        case "çıkarma":
            $sonuc = $sayi1 - $sayi2;
            break;
        case "çarpma":
            $sonuc = $sayi1 * $sayi2;
            break;
        case "bölme":
            if ($sayi2 == 0) {
                echo "Bir sayı 0'a bölünemez.";
            } else {
                $sonuc = $sayi1 / $sayi2;
            }
            break;
        case "mod":
            if ($sayi2 == 0) {
                echo "aga 0 mod alınmaz";
            } else {
                $sonuc = $sayi1 % $sayi2;
            }
            break;
        default:
            $sonuc = "Geçersiz işlem!";
    }
    echo "Sonuç: $sonuc";
    ?>
</body>

</html> 