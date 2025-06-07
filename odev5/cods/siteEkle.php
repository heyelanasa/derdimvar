<form action="index.php" method="get">
    <input type="hidden" name="link" value="kaydet">
    <input type="hidden" name="kod" value="<?= $_GET["kod"] ?>">

    <div class="mb-3">
        <label for="data">Veri</label>
        <textarea name="data" class="form-control" id="data"></textarea>
    </div>
    <button type="submit" class="btn btn-primary">Kaydet</button>
</form> 