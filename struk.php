<?php
include "koneksi.php";
session_start();

// Pastikan user sudah checkout dan punya data
if (!isset($_GET['id'])) {
    echo "ID transaksi tidak ditemukan!";
    exit;
}

$id_order = (int)$_GET['id'];

// Ambil data order
$order = $conn->query("SELECT * FROM orders WHERE id = $id_order")->fetch_assoc();
if (!$order) {
    echo "Data order tidak ditemukan!";
    exit;
}

$items = $conn->query("SELECT * FROM order_items WHERE order_id = $id_order");
$total = 0;
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Struk Pembelian</title>
<style>
@media print {
    @page {
        size: 80mm auto; /* ukuran thermal printer */
        margin: 5mm;
    }
    body {
        width: 80mm;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        margin: 0;
        padding: 0;
    }
    .no-print { display: none; }
}
body {
    width: 80mm;
    margin: auto;
    font-family: 'Courier New', monospace;
    font-size: 12px;
    background: #fff;
}
h2 {
    text-align: center;
    margin: 0;
    font-size: 14px;
}
hr {
    border: 0;
    border-top: 1px dashed #000;
    margin: 5px 0;
}
.table {
    width: 100%;
    border-collapse: collapse;
}
.table td {
    padding: 2px 0;
}
.right {
    text-align: right;
}
.center {
    text-align: center;
}
.total {
    font-weight: bold;
    border-top: 1px dashed #000;
    border-bottom: 1px dashed #000;
    padding: 5px 0;
}
.footer {
    text-align: center;
    margin-top: 5px;
}
</style>
</head>
<body>
    <h2>CMS VARIASI</h2>
    <hr>
    <p>
        No Nota: <?= htmlspecialchars($order['id']); ?><br>
        Waktu: <?= htmlspecialchars($order['tanggal']); ?><br>
        Kasir: <?= htmlspecialchars($_SESSION['username'] ?? ''); ?><br>
    </p>
    <hr>

    <table class="table">
        <?php while ($item = $items->fetch_assoc()): 
            $subtotal = $item['harga'] * $item['jumlah'];
            $total += $subtotal;
        ?>
        <tr>
            <td><?= htmlspecialchars($item['nama_produk']); ?> (x<?= $item['jumlah']; ?>)</td>
            <td class="right"><?= number_format($subtotal, 0, ',', '.'); ?></td>
        </tr>
        <?php endwhile; ?>
    </table>

    <hr>
    <p class="total">TOTAL: Rp <?= number_format($total, 0, ',', '.'); ?></p>
    <p class="center">Tunai: Rp <?= number_format($total, 0, ',', '.'); ?></p>
    <p class="center">Terbayar: <?= date("d M Y H:i"); ?></p>
    <hr>
    <div class="footer">
        <p>Terima Kasih 🙏<br>CMS VARIASI</p>
    </div>

    <div class="no-print" style="text-align:center; margin-top:10px;">
        <button onclick="window.print()">🖨️ Cetak Struk</button>
    </div>
</body>
</html>
