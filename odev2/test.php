<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
   
        crossorigin="anonymous">
</head>

<body>
    <div class="container-md">
        <h1>Form Örnekleri</h1>
        <?php

        $fLang=$_GET["fav_language"];
        $cins=$_GET["cins"];

        echo "$fLang $cins";



        if(isset($_GET["vehicle1"]))
            echo " Bisiklet Sever";
        if(isset($_GET["vehicle2"]))
            echo " Araba Sever";
        if(isset($_GET["vehicle3"]))
            echo " Tekne Sever";

        
        ?>


    </div>
</body>

</html> 