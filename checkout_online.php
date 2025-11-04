<?php
session_start();
include "koneksi.php";

// Pastikan user login (jika kamu punya sistem login)
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pembeli') {
    // Kalau tidak ada sistem login, baris ini boleh kamu hapus
    // header("Location: index.php");
    // exit;
}

// === PERBAIKAN BAGIAN INI ===
// Ambil username dari session login
$username_login = $_SESSION['username'] ?? null;
$nama_otomatis = "";

// Jika user login, ambil nama dari tabel users
if ($username_login) {
    $cek_user = $conn->query("SELECT username FROM users WHERE username = '$username_login' LIMIT 1");
    if ($cek_user && $cek_user->num_rows > 0) {
        $data_user = $cek_user->fetch_assoc();
        $nama_otomatis = $data_user['username'];
    }
}

// Jika session cart kosong, coba pulihkan dari cookie/session sebelumnya
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    if (isset($_COOKIE['cart_backup'])) {
        $_SESSION['cart'] = json_decode($_COOKIE['cart_backup'], true);
    }
}

// Jika tetap kosong, tampilkan alert
if (!isset($_SESSION['cart']) || count($_SESSION['cart']) === 0) {
    echo "<script>alert('Keranjang masih kosong!'); window.location='stok.php';</script>";
    exit;
}

// Simpan backup keranjang setiap kali halaman dibuka
setcookie('cart_backup', json_encode($_SESSION['cart']), time() + 3600, '/');

// Hitung total belanja
$total = 0;
foreach ($_SESSION['cart'] as $item) {
    if (isset($item['harga'], $item['qty'])) {
        $total += $item['harga'] * $item['qty'];
    }
}

// === PROSES CHECKOUT ===
if (isset($_POST['checkout'])) {
    $nama     = $_POST['nama'];
    $alamat   = $_POST['alamat'];
    $hp       = $_POST['hp'];
    $metode   = $_POST['metode'] ?? 'Online';
    $bayar    = $_POST['bayar'] ?? 0;
    $tanggal  = date('Y-m-d H:i:s');

    // Validasi form
    if (empty($nama) || empty($alamat) || empty($hp) || empty($metode)) {
        echo "<script>alert('Data belum lengkap!'); window.history.back();</script>";
        exit;
    }

    if ($bayar < $total) {
        echo "<script>alert('Nominal pembayaran kurang dari total belanja Rp " . number_format($total, 0, ',', '.') . "'); window.history.back();</script>";
        exit;
    }

    $kembalian = $bayar - $total;

    // Simpan ke tabel transaksi utama
    $query_transaksi = "
        INSERT INTO transaksi 
        (nama_pelanggan, alamat, no_hp, total, untung, tanggal, nominal_bayar, kembalian, metode_bayar) 
        VALUES 
        ('$nama', '$alamat', '$hp', '$total', 0, '$tanggal', '$bayar', '$kembalian', '$metode')
    ";

    if ($conn->query($query_transaksi)) {
        $transaksi_id = $conn->insert_id;

        // Simpan ke tabel transaksi_detail
        foreach ($_SESSION['cart'] as $id => $item) {
            if (!isset($item['nama'])) continue;
            $nama_parfum = $conn->real_escape_string($item['nama']);
            $harga = $item['harga'] ?? 0;
            $qty = $item['qty'] ?? 1;
            $subtotal = $harga * $qty;

            $query_detail = "
                INSERT INTO transaksi_detail 
                (transaksi_id, parfum_id, nama_parfum, harga, qty, subtotal, nama_pelanggan, alamat, no_hp) 
                VALUES 
                ('$transaksi_id', '$id', '$nama_parfum', '$harga', '$qty', '$subtotal', '$nama', '$alamat', '$hp')
            ";
            $conn->query($query_detail);

            // Kurangi stok parfum
            $conn->query("UPDATE parfum SET jumlah_stok = GREATEST(jumlah_stok - $qty, 0) WHERE id = $id");
        }

        // Simpan data struk ke session
        $_SESSION['struk'] = [
            'no_struk' => $transaksi_id,
            'nama' => $nama,
            'alamat' => $alamat,
            'hp' => $hp,
            'tanggal' => $tanggal,
            'items' => $_SESSION['cart'],
            'total' => $total,
            'bayar' => $bayar,
            'metode' => $metode
        ];

        // Kosongkan keranjang (tapi simpan backup agar tidak hilang saat reload)
        setcookie('cart_backup', json_encode($_SESSION['cart']), time() + 3600, '/');
        unset($_SESSION['cart']);

        header("Location: checkout_online.php?print_struk=1");
        exit;
    } else {
        echo "<script>alert('Gagal menyimpan transaksi!'); window.history.back();</script>";
        exit;
    }
}

// === CETAK STRUK RESPONSIF ===
if (isset($_GET['print_struk']) && isset($_SESSION['struk'])) {
    $data = $_SESSION['struk'];
    echo '<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Struk Online</title>
<style>
body {
    font-family: "Poppins", sans-serif;
    background: #f9f9f9;
    display: flex;
    justify-content: center;
    align-items: center;
    padding: 20px;
}
.struk-container {
    background: #fff;
    width: 100%;
    max-width: 400px;
    border-radius: 12px;
    box-shadow: 0 4px 10px rgba(0,0,0,0.2);
    padding: 20px;
}
.logo {
    width: 80px;
    display: block;
    margin: 0 auto 10px;
}
h2 {
    text-align: center;
    font-size: 18px;
    margin: 0;
    color: #000;
}
p {
    font-size: 14px;
    margin: 4px 0;
}
hr {
    border: none;
    border-top: 1px dashed #000;
    margin: 8px 0;
}
table {
    width: 100%;
    border-collapse: collapse;
    margin-top: 5px;
}
td {
    font-size: 14px;
    padding: 3px 0;
}
.qty { text-align: center; }
.price { text-align: right; }
.total {
    text-align: right;
    font-weight: bold;
    margin-top: 5px;
    font-size: 15px;
}
p.footer {
    text-align: center;
    font-size: 13px;
    margin-top: 15px;
    color: #333;
}
.btn-kembali {
    display: block;
    width: 100%;
    background: linear-gradient(90deg, #000, #f1c40f);
    color: #fff;
    border: none;
    padding: 10px;
    border-radius: 8px;
    font-size: 14px;
    font-weight: bold;
    margin-top: 15px;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-kembali:hover {
    background: #f1c40f;
    color: #000;
}
@media print {
    body { background: #fff; }
    .btn-kembali { display: none; }
    .struk-container {
        box-shadow: none;
        border-radius: 0;
        width: 100%;
        padding: 0;
    }
}
</style>
<script>
window.onload = function(){
    window.print();
};
</script>
</head>
<body>
<div class="struk-container">
    <img src="assets/logo.png" alt="Logo" class="logo">
    <h2>The Collection Perfume</h2>
    <p style="text-align:center;">Alamat: Jl. KS Tubun Dalam</p>
    <hr>
    <p><strong>Nama:</strong> '.htmlspecialchars($data['nama']).'</p>
    <p><strong>No. HP:</strong> '.htmlspecialchars($data['hp']).'</p>
    <p><strong>Metode:</strong> '.htmlspecialchars($data['metode']).'</p>
    <p><strong>Tanggal:</strong> '.htmlspecialchars($data['tanggal']).'</p>
    <hr>
    <table>';
    foreach ($data['items'] as $item) {
        $sub = $item['harga'] * $item['qty'];
        echo '<tr>
            <td>'.htmlspecialchars($item['nama']).'</td>
            <td class="qty">'.$item['qty'].'</td>
            <td class="price">Rp '.number_format($sub,0,',','.').'</td>
        </tr>';
    }
    echo '</table>
    <hr>
    <p class="total">TOTAL: Rp '.number_format($data['total'],0,',','.').'</p>
    <p class="total">BAYAR: Rp '.number_format($data['bayar'],0,',','.').'</p>
    <p class="total">KEMBALI: Rp '.number_format($data['bayar'] - $data['total'],0,',','.').'</p>
    <p class="footer">Terima kasih atas pembelian Anda 💛</p>
    <button class="btn-kembali" onclick="window.location.href=\'stok.php\'">← Kembali ke Etalase</button>
</div>
</body>
</html>';
    unset($_SESSION['struk']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Checkout Online</title>
<style>
body {
    font-family: "Poppins", sans-serif;
    background: linear-gradient(180deg, #000, #f1c40f);
    margin: 0; padding: 0;
}
.container {
    max-width: 600px;
    margin: 30px auto;
    background: #fff;
    border-radius: 18px;
    box-shadow: 0 4px 15px rgba(0,0,0,0.3);
    padding: 25px 30px;
}
h2 {
    text-align: center;
    color: #f1c40f;
    background: #000;
    padding: 15px;
    border-radius: 12px;
    margin-top: 0;
}
form label {
    font-weight: 600;
    display: block;
    margin-top: 12px;
    color: #333;
}
input, textarea, select {
    width: 100%;
    padding: 10px;
    border-radius: 8px;
    border: 2px solid #ccc;
    margin-top: 5px;
    font-size: 14px;
}
button {
    width: 100%;
    background: linear-gradient(90deg, #000, #f1c40f);
    color: white;
    border: none;
    padding: 12px;
    font-size: 16px;
    font-weight: bold;
    border-radius: 10px;
    margin-top: 18px;
    cursor: pointer;
    transition: all 0.2s ease-in-out;
}
button:hover {
    opacity: 0.9;
    transform: scale(1.02);
}
.table-wrapper { overflow-x:auto; margin-top:15px; }
table {
    width:100%;
    border-collapse:collapse;
    margin-bottom: 10px;
}
table th, table td {
    padding: 8px;
    border-bottom: 1px solid #eee;
    text-align: left;
    font-size: 14px;
}
.total {
    text-align:right;
    font-weight:bold;
    margin-top:10px;
    font-size:16px;
    color:#000;
}
@media (max-width:480px) {
    .container { margin: 10px; padding: 15px; }
    input, textarea, select { font-size: 13px; }
    button { font-size: 14px; }
}
</style>
</head>
<body>

<div class="container">
    <h2>🛒 Checkout Online</h2>

    <form method="post">
        <label>Nama Lengkap:</label>
        <input type="text" name="nama" value="<?= htmlspecialchars($nama_otomatis) ?>" readonly required>

        <label>Alamat Pengiriman:</label>
        <textarea name="alamat" required></textarea>

        <label>No. HP:</label>
        <input type="text" name="hp" required>

        <label>Metode Pembayaran:</label>
        <select name="metode" required>
            <option value="">-- Pilih Metode --</option>
            <option value="Transfer Bank">Transfer Bank</option>
            <option value="E-Wallet (Dana, OVO, Gopay)">E-Wallet (Dana, OVO, Gopay)</option>
            <option value="COD">COD (Bayar di Tempat)</option>
        </select>

        <label>Nominal Pembayaran (Rp):</label>
        <input type="number" name="bayar" min="<?= $total ?>" value="<?= $total ?>" required>

        <h3>Rincian Pesanan</h3>
        <div class="table-wrapper">
            <table>
                <tr><th>Nama Parfum</th><th>Qty</th><th>Subtotal</th></tr>
                <?php foreach ($_SESSION['cart'] as $item): ?>
                <tr>
                    <td><?= htmlspecialchars($item['nama']) ?></td>
                    <td><?= $item['qty'] ?></td>
                    <td>Rp <?= number_format($item['harga']*$item['qty'],0,',','.') ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>

        <div class="total">Total: Rp <?= number_format($total,0,',','.') ?></div>

        <button type="submit" name="checkout">💳 Konfirmasi & Bayar</button>
    </form>
</div>

</body>
</html>
