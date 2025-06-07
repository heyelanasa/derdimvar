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
    <div class="container mt-5">

        <form action="index.php" method="get">
            Ana Para<br>
            <input type="text" name="ana" id="ana"><br>
            Taksit Sayısı <br><input type="text" name="taksitS" id="taksitS"><br>
            Aylık Faiz Oranı % <br> <input type="text" name="faiz" id="faiz"><br>
            <label for="faiz">Kredi Türü</label>
            <br>
            <input type="radio" name="tur" value="basit">Basit
            <input type="radio" name="tur" value="bilesik">Bileşik
            <br><input type="submit" name="submit" value="Gönder">
        </form>


        <?php
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