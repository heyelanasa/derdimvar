<?php
include("cods/head.php");
?>

<body>
    <div class="container-md">
        <h1>PHP'de Koşul ve Döngüler</h1>
        <?php

        $sayi1 = $_GET["sayi1"];
        $sayi2 = $_GET["sayi2"];

        echo "<h2>Ön tanımlı fonksiyonlar.</h2>";

        $fx = array(
            "print",
            "echo",
            "array",
            "include",
            "rand",
            "sqrt",
            "abs",
            "ceil",
            "floor",
            "isset",
            "empty",
            "define",
            "die",
            "range"
            ,"shuffle"
            ,"array_slice"
        );
        foreach ($fx as $deger) {
            echo "$deger <br>";
        }
       // echo pow(9, 2);

        ?>


    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        crossorigin="anonymous"></script>
</body>

</html> 