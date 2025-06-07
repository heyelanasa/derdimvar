<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
</head>

<body>
    <div style="margin: 20px 0;"><a href="../index.php" style="background:#4a90e2;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600;">Derdimvar Ana Sayfa</a></div>
    <div class="container-md">
        <h1>Proje Kayıt Ekranı</h1>

        <form action="ozet.php" method="get" target="_blank" autocomplete="on">
            <div><label for="ad">Proje Adı: </label>
                <input type="text" name="ad" id="" size="50" maxlength="40" autofocus>
            </div>
            <div>
                <label for="ozet">Proje Özeti: </label><br>
                <textarea name="ozet" id="ozet" rows="5" cols="50" class="m-3"></textarea>
            </div>
            <div>
                <label for="start">Başlangıç Tarihi: </label>
                <input type="date" name="start" id="start" required>
            </div>
            <div>
                <label for="sure">Başlangıç Tarihi: </label>
                <input type="number" name="sure" id="sure" min="12" max="64" step="12">
            </div>
            <fieldset>
                <legend>Proje Tipi: </legend>
                <input type="radio" name="tip" id="urun" value="Ürün Geliştirme">
                <label for="urun">Ürün Geliştirme</label><BR>
                <input type="radio" name="tip" id="yenileme" value="Yenileme">
                <label for="yenileme">Yenileme</label><br>
                <input type="radio" name="tip" id="transfer" value="Teknoloji Transferi">
                <label for="transfer">Teknoloji Transferi</label>
            </fieldset>
            <br>
            Proje İlgi Alanları: <br>
            <input type="checkbox" name="ilgi" id="mat" value="ilgi">
            <label for="mat">Matematik</label>

            <input type="checkbox" name="ilgi" id="sanat" value="sanat">
            <label for="sanat">Sanat</label>

            <input type="checkbox" name="ilgi" id="tarih" value="tarih">
            <label for="tarih">Tarih</label><br>

            <input type="checkbox" name="ilgi" id="ai" value="ai">
            <label for="ai">Yapay Zeka</label>

            <input type="checkbox" name="ilgi" id="saglik" value="saglik">
            <label for="saglik">Sağlik</label>

            <input type="checkbox" name="ilgi" id="Tek" value="Tek">
            <label for="Tek">Teknoloji</label>


            <br>
            <br>
            <input type="submit" value="Kaydet">
        </form>

    </div>

</body>

</html> 
