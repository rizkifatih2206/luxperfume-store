<?php
include 'db.php';
$id = $_GET['id'];
$result = $conn->query("SELECT * FROM parfum WHERE id=$id");
$parfum = $result->fetch_assoc();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title><?= $parfum['nama'] ?></title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="detail-container">
    <img src="images/<?= $parfum['gambar'] ?>" alt="<?= $parfum['nama'] ?>" class="detail-img">

    <div class="detail-info">
        <h2><?= $parfum['nama'] ?></h2>
        <h4><?= $parfum['brand'] ?></h4>
        <p><?= $parfum['deskripsi'] ?></p>
        <p><b>Notes:</b> <?= $parfum['notes'] ?></p>
        <p><b>Ukuran:</b> <?= $parfum['ukuran'] ?> ml</p>
        <h3>Rp <?= number_format($parfum['harga'], 0, ',', '.') ?></h3>

        <form action="keranjang.php" method="POST">
            <input type="hidden" name="id" value="<?= $parfum['id'] ?>">
            <label>Jumlah: </label>
            <input type="number" name="jumlah" value="1" min="1" max="<?= $parfum['stok'] ?>">
            <button type="submit" name="tambah">Tambah ke Keranjang</button>
            <button type="submit" name="beli">Beli Sekarang</button>
        </form>
    </div>
</div>
</body>
</html>
