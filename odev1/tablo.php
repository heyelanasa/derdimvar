<!DOCTYPE html>
<html lang="tr">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>
    <link rel="stylesheet" href="stil.css">
</head>

<body>
    <?php
    $sayi1 = $_GET["sayi1"];
    $sayi2 = $_GET["sayi2"];
    echo "<p> Gelen sayi1 ve sayi2 değerine göre tablo yapımı</p>";
    echo "<table border=1>";
    for ($i = 1; $i <= $sayi1; $i++) {
        echo "<tr>";
        for ($t = 1; $t <= $sayi2; $t++) {
            $r = rand(0,  255);
            $g = rand(0, 255);
            $b = rand(0, 255);
            echo "<td style=\"background-color:rgb($r, $g, $b);\">$i -$t</td>";
        }
        echo "</tr>";
    }
    echo "<table>";
    ?>
</body>

</html> 