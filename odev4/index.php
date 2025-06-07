<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sayısal Loto Kuponu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="stil.css">
</head>

<body>
    <div style="margin: 20px 0;"><a href="../index.php" style="background:#4a90e2;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600;">Derdimvar Ana Sayfa</a></div>
    <div class="container mt-5">
        <h2>Soru5 </h2>
        <p>Formdan gelen bilgilere göre faiz hesaplayan php betiği</p>
        <?php

        $ana=$_GET["ana"];
        $ts=$_GET["taksitS"];
        $faiz=$_GET["faiz"];
        $tur=$_GET["tur"];

        if($tur=="basit")
        {
            $borc=$ana*(1+$ts*$faiz/100);
        }
        else
        {
            $borc=$ana*pow((1+$faiz/100),$ts);
        }

        $aylik=$borc/$ts;

        echo "toplam borç: $borc, aylık taksit $aylik";

        ?>



        <h2>Soru1 </h2>
        <p>Bu siteler sunucu tabanlı çalışan yapılardır. İstemciden gelen talebe göre otomatik çıktı üretirler.</p>

        <h2>Soru2 </h2>
        <p>$metin olarak gelen değişkenin harf adedince ekrana alt alta yazılması. </p>
        <?php
        $metin = isset($_GET["metin"]) ? $_GET["metin"] : "";
        if ($metin !== "") {
            for ($i = 0; $i < strlen($metin); $i++) {
                echo "$metin <br>";
            }
        }
        ?>

        <h2>Soru3 </h2>
        <p>$sayi1 $sayi2 gelen otomatik tablo. </p>
        <?php
        $sayi1 = isset($_GET["sayi1"]) ? $_GET["sayi1"] : null;
        $sayi2 = isset($_GET["sayi2"]) ? $_GET["sayi2"] : null;
        if ($sayi1 !== null && $sayi2 !== null) {
            echo "  <table>";
            for ($i = 1; $i <= $sayi1; $i++) {
                echo "   <tr>";
                for ($t = 1; $t <= $sayi2; $t++) {
                    echo "<td> $i - $t</td>";
                }
                echo "   </tr>";
            }
            echo "   </table>";
        }
        ?>

        <h2>Soru4</h2>
        <p>elemen sayisi, alt sınır, üst sınır değerlerini başka sayfadan alan ve bu bilgilere göre rastgele dizi
            oluşturup ekrana döken php betiği </p>

        <?php
        $elemansayisi = isset($_GET["es"]) ? $_GET["es"] : null;
        $asinir = isset($_GET["as"]) ? $_GET["as"] : null;
        $usinir = isset($_GET["us"]) ? $_GET["us"] : null;
        if ($elemansayisi !== null && $asinir !== null && $usinir !== null) {
            $dizi = array();
            for ($i = 0; $i < $elemansayisi; $i++) {
                $dizi[$i] = rand($asinir, $usinir);
            }
            echo "<pre>";
            print_r($dizi);
            echo "</pre>";
        }
        ?>
    </div>

    <br>
    <br>
    <br>
    <!-- Bootstrap JS ve Popper JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html> 
