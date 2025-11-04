<?php
session_start();
require 'koneksi.php';

// cek login
if (!isset($_SESSION['user_id'])) {
    // arahkan ke halaman login, bukan cuma pesan
    header("Location: login.php");
    exit;
}

// cek apakah ada id parfum
if (!isset($_POST['parfum_id'])) {
    echo "Parfum tidak ditemukan!";
    exit;
}

$parfum_id = intval($_POST['parfum_id']);
$qty = isset($_POST['qty']) ? intval($_POST['qty']) : 1;

// ambil data parfum
$stmt = $conn->prepare("SELECT * FROM parfum WHERE id=?");
$stmt->bind_param("i", $parfum_id);
$stmt->execute();
$result = $stmt->get_result();
$parfum = $result->fetch_assoc();

if (!$parfum) {
    echo "Parfum tidak ditemukan!";
    exit;
}

// total harga
$total = $parfum['harga_min'] * $qty;
?>
<!doctype html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Form Pembelian</title>
<style>
    body{font-family:Arial,sans-serif;background:#f6f6f7;margin:0;padding:20px}
    .box{background:#fff;max-width:600px;margin:40px auto;padding:20px;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,.1)}
    h2{margin-top:0}
    .row{margin-bottom:12px}
    label{display:block;font-weight:bold;margin-bottom:4px}
    input[type=number]{padding:6px;width:100px}
    .btn{background:#ff4ec4;color:#fff;padding:10px 14px;border:none;border-radius:8px;cursor:pointer;font-weight:bold}
    .btn:hover{background:#d43ca5}
</style>
</head>
<body>
<div class="box">
    <h2>Konfirmasi Pembelian</h2>
    <p><b>Nama Parfum:</b> <?php echo htmlspecialchars($parfum['nama']); ?></p>
    <p><b>Harga Satuan:</b> Rp <?php echo number_format($parfum['harga_min'],0,',','.'); ?></p>
    <form action="beli_proses.php" method="post">
        <input type="hidden" name="parfum_id" value="<?php echo $parfum_id; ?>">
        <div class="row">
            <label>Jumlah</label>
            <input type="number" name="qty" value="<?php echo $qty; ?>" min="1" max="<?php echo $parfum['stok']; ?>">
        </div>
        <p><b>Total Awal:</b> Rp <?php echo number_format($total,0,',','.'); ?></p>
        <button type="submit" class="btn">Lanjutkan Pembelian</button>
    </form>
</div>
</body>
</html>
