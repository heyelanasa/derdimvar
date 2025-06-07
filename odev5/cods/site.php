<?php
if (isset($_GET["kod"])) {
    $kod = $_GET["kod"];

    $sql = "SELECT * FROM site WHERE si_kod='$kod'";
    $result = mysqli_query($conn, $sql);

    if ($result) {
        while ($row = mysqli_fetch_array($result)) {
            $id = $row["si_id"];
            $data = $row["si_data"];

            echo "<a href=\"index.php?link=sil&t=site&id=$id&kod=$kod\"><i class=\"bi bi-trash\"></i></a> ";
            echo "<a href=\"index.php?link=duzenle&t=site&id=$id&kod=$kod\"><i class=\"bi bi-pencil\"></i></a> ";
            echo $data . "<hr>";
        }
    }

    echo "<br><br><br>Yeni bir satır eklemek için <a href='index.php?link=ekle&t=site&kod=$kod'>tıklayınız</a>";
} else {
    // Eksik kod değeri durumunda hiçbir şey gösterme
} 