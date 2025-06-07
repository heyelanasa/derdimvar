<!doctype html>
<html lang="tr">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bootstrap demo</title>
</head>

<body>
    <div class="container-md">




        <h1>Form Örnekleri</h1>

        <form action="#">
            <label for="name">Adınız/soyadınız</label><br>
            <input type="text" id="name" name="name"><br>

            <label for="email">E-Posta Adresiniz</label><br>
            <input type="email" id="email" name="email"><br><br>

            <input type="submit" value="Submit">
            <input type="reset" value="Reset">
        </form>


        <form>
            <label for="username">Username:</label><br>
            <input type="text" id="username" name="username"><br>
            <label for="pwd">Password:</label><br>
            <input type="password" id="pwd" name="pwd">
        </form>



        <h2>Radio</h2>

        <form action="test.php">
            En sevdiğiniz Dil <br>
            <input type="radio" id="html" name="fav_language" value="HTML">
            <label for="html">HTML</label><br>
            <input type="radio" id="css" name="fav_language" value="CSS">
            <label for="css">CSS</label><br>
            <input type="radio" id="javascript" name="fav_language" value="JavaScript">
            <label for="javascript">JavaScript</label>
            <br>
            Cinsiyet <br>
            <input type="radio" id="erkek" name="cins" value="Erkek">
            <label for="erkek">Erkek</label><br>
            <input type="radio" id="kadin" name="cins" value="Kadin">
            <label for="kadin">Kadın</label>
            <br>   
            <input type="checkbox" id="vehicle1" name="vehicle1" value="Bike">
            <label for="vehicle1"> I have a bike</label><br>
            <input type="checkbox" id="vehicle2" name="vehicle2" value="Car">
            <label for="vehicle2"> I have a car</label><br>
            <input type="checkbox" id="vehicle3" name="vehicle3" value="Boat">
            <label for="vehicle3"> I have a boat</label>

         
            <input type="submit" value="Submit">
<br>
<br>
<br>
        </form>




    </div>
</body>

</html> 