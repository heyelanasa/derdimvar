<?php
if (isset($_GET["id"]) && isset($_GET["kod"])) {
    $id = $_GET["id"];
    $kod = $_GET["kod"];

    $sql = "SELECT * FROM site WHERE si_id=$id";
    $result = mysqli_query($conn, $sql);
    $veri = mysqli_fetch_assoc($result);
}
?>

<form action="index.php" method="get">
    <input type="hidden" name="link" value="guncelle">
    <input type="hidden" name="id" value="<?= $veri["si_id"] ?>">
    <input type="hidden" name="kod" value="<?= $kod ?>">

    <div class="mb-3">
        <label for="data">Veri</label>
        <textarea name="data" class="form-control" id="data"><?= $veri["si_data"] ?></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Güncelle</button>
</form> 