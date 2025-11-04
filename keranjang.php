<?php
session_start();
include 'db.php';

// Tambah ke keranjang
if (isset($_POST['tambah'])) {
    $id = $_POST['id'];
    $jumlah = $_POST['jumlah'];

    if (!isset($_SESSION['keranjang'])) $_SESSION['keranjang'] = [];

    if (isset($_SESSION['keranjang'][$id])) {
        $_SESSION['keranjang'][$id] += $jumlah;
    } else {
        $_SESSION['keranjang'][$id] = $jumlah;
    }

    header("Location: keranjang.php");
    exit;
}

// Tampilkan isi keranjang
$keranjang = $_SESSION['keranjang'] ?? [];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Keranjang Belanja</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<h1>Keranjang Anda</h1>

<?php if (empty($keranjang)): ?>
    <p>Keranjang masih kosong 😅</p>
    <a href="stok.php">Kembali ke katalog</a>
<?php else: ?>
    <table>
        <tr>
            <th>Nama</th>
            <th>Jumlah</th>
            <th>Harga</th>
            <th>Total</th>
        </tr>
        <?php
        $total = 0;
        foreach ($keranjang as $id => $jumlah):
            $result = $conn->query("SELECT * FROM parfum WHERE id=$id");
            $p = $result->fetch_assoc();
            $subtotal = $p['harga'] * $jumlah;
            $total += $subtotal;
        ?>
        <tr>
            <td><?= $p['nama'] ?></td>
            <td><?= $jumlah ?></td>
            <td>Rp <?= number_format($p['harga'], 0, ',', '.') ?></td>
            <td>Rp <?= number_format($subtotal, 0, ',', '.') ?></td>
        </tr>
        <?php endforeach; ?>
    </table>

    <h3>Total Belanja: Rp <?= number_format($total, 0, ',', '.') ?></h3>
    <button>Checkout</button>
<?php endif; ?>
</body>
</html>
