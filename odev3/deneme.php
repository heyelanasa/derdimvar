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
            <?php
            for ($i = 1; $i <= 25; $i++):
                ?>
                <div class="col-md-4">
                    <div class="card mb-4">
                        <div class="card-body">
                            <h5 class="card-title">Kupon <?php echo $i ?> </h5>
                            <p class="card-text">
                                9 - 10 - 31 - 58 - 81 - 90 </p>
                        </div>
                    </div>
                    
                </div>

            <?php endfor; ?>

            ?>
        </div>
    </div>
</body>

</html> 