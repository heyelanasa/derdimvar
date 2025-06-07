<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
</head>

<body>

    <div style="margin: 20px 0;"><a href="../index.php" style="background:#4a90e2;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600;">Derdimvar Ana Sayfa</a></div>

    <form action="tablo.php" method="get">
        <center><H2>Otomatik Tablo</H2>
            satir: <input type="text" name="sayi1" id="sayi1"> <br>
            sütun: <input type="text" name="sayi2" id="sayi2"> <br>
            <input type="submit" value="Gönder">
        </center>
    </form>
<br><br>

<center><h2>Hesap Makinesi</h2>
    <form action="hesapMah.php" method="get">

        sayi1: <input type="text" name="sayi1" id="sayi1"> <br>
        sayi2: <input type="text" name="sayi2" id="sayi2"> <br>
        <select id="islem" name="islem">
            <option value="toplama">Topla</option>
            <option value="çıkarma">Çıkarma</option>
            <option value="çarpma">Çarpma</option>
            <option value="bölme">Bölme</option>
            <option value="mod">Mod</option>
        </select><br><br>
        <input type="submit">
    </form>

    </center>
</body>

</html> 
