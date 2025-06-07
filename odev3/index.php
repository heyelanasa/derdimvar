<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sayısal Loto Kuponu</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body>
    <div style="margin: 20px 0;"><a href="../index.php" style="background:#4a90e2;color:#fff;padding:10px 22px;border-radius:8px;text-decoration:none;font-weight:600;">Derdimvar Ana Sayfa</a></div>
    <div class="container mt-5">
        <h2>Sayısal Loto Kuponu Oluştur</h2>
        <form action="kuponlar.php" method="GET">
            <div class="mb-3">
                <label for="kuponSayisi" class="form-label">Kaç Kupon İstiyorsunuz?</label>
                <input type="number" class="form-control" id="kuponSayisi" name="kuponSayisi" required>
            </div>
            <button type="submit" class="btn btn-primary">Kuponları Oluştur</button>
        </form>
    </div>

    <!-- Bootstrap JS ve Popper JS -->
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.min.js"></script>
</body>

</html> 
