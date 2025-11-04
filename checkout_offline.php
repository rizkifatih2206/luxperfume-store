<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

// Ambil params dari GET
$parfum_id = (int)($_GET['parfum_id'] ?? 0);
$jumlah = (int)($_GET['jumlah'] ?? 0);
$harga_jual = (int)($_GET['harga_jual'] ?? 0);

// Validasi params
if ($parfum_id <= 0 || $jumlah <= 0 || $harga_jual <= 0) {
    echo "<script>alert('Data transaksi tidak valid!'); window.location='etalase_kasir.php';</script>";
    exit;
}

// Ambil detail produk
$produk = $conn->prepare("SELECT * FROM parfum WHERE id = ?");
$produk->bind_param("i", $parfum_id);
$produk->execute();
$result = $produk->get_result();
if ($result->num_rows == 0) {
    echo "<script>alert('Produk tidak ditemukan!'); window.location='etalase_kasir.php';</script>";
    exit;
}
$row = $result->fetch_assoc();
$stok_saat_ini = (int)$row['jumlah_stok'];
$nama_produk = htmlspecialchars($row['nama'] . ' - ' . $row['brand']);
$harga_min = $row['harga_min'];
$harga_max = $row['harga_max'];
$produk->close();

// Validasi stok & range harga
if ($jumlah > $stok_saat_ini) {
    echo "<script>alert('Stok tidak mencukupi! Stok tersedia: " . $stok_saat_ini . "'); window.location='etalase_kasir.php';</script>";
    exit;
}
if ($harga_jual < $harga_min || $harga_jual > $harga_max) {
    echo "<script>alert('Harga jual harus antara Rp " . number_format($harga_min, 0, ',', '.') . " - Rp " . number_format($harga_max, 0, ',', '.') . "!'); window.location='etalase_kasir.php';</script>";
    exit;
}

// Proses transaksi
$total = $jumlah * $harga_jual;
$stok_baru = $stok_saat_ini - $jumlah;

// Update stok parfum
$update_stok = $conn->prepare("UPDATE parfum SET jumlah_stok = ? WHERE id = ?");
$update_stok->bind_param("ii", $stok_baru, $parfum_id);
$update_success = $update_stok->execute();
$update_stok->close();

if (!$update_success) {
    echo "<script>alert('Error update stok!'); window.location='etalase_kasir.php';</script>";
    exit;
}

// Insert ke tabel transaksi utama
$nama_pelanggan = "admin";
$alamat = "-";
$no_hp = "-";
$tanggal = date('Y-m-d H:i:s');
$metode = "Offline";
$bayar = $total;
$kembalian = 0;

$query_transaksi = "
    INSERT INTO transaksi 
    (nama_pelanggan, alamat, no_hp, total, untung, tanggal, nominal_bayar, kembalian, metode_bayar)
    VALUES 
    ('$nama_pelanggan', '$alamat', '$no_hp', '$total', 0, '$tanggal', '$bayar', '$kembalian', '$metode')
";
$conn->query($query_transaksi);
$transaksi_id = $conn->insert_id;

// Simpan ke tabel transaksi_detail
$query_detail = "
    INSERT INTO transaksi_detail 
    (transaksi_id, parfum_id, nama_parfum, harga, qty, subtotal, nama_pelanggan, alamat, no_hp)
    VALUES 
    ('$transaksi_id', '$parfum_id', '$nama_produk', '$harga_jual', '$jumlah', '$total', '$nama_pelanggan', '$alamat', '$no_hp')
";
$conn->query($query_detail);

// Simpan juga ke tabel penjualan
$insert_penjualan = $conn->prepare("INSERT INTO penjualan (parfum_id, jumlah, harga_jual, total, tanggal) VALUES (?, ?, ?, ?, NOW())");
$insert_penjualan->bind_param("iiid", $parfum_id, $jumlah, $harga_jual, $total);
$insert_success = $insert_penjualan->execute();
$insert_penjualan->close();

if (!$insert_success) {
    $rollback = $conn->prepare("UPDATE parfum SET jumlah_stok = ? WHERE id = ?");
    $rollback->bind_param("ii", $stok_saat_ini, $parfum_id);
    $rollback->execute();
    echo "<script>alert('Error simpan transaksi! Stok dikembalikan.'); window.location='etalase_kasir.php';</script>";
    exit;
}

// === STRUK CETAK ===
$tanggal_transaksi = date('d/m/Y H:i:s');
$struk_content = "
=== STRUK KASIR LUXPERFUME ===
Tanggal: $tanggal_transaksi
----------------------------------------
Produk: $nama_produk
Jumlah: $jumlah
Harga Satuan: Rp " . number_format($harga_jual, 0, ',', '.') . "
----------------------------------------
TOTAL: Rp " . number_format($total, 0, ',', '.') . "
----------------------------------------
Kasir: Admin
Terima kasih telah berbelanja!
Stok tersisa: $stok_baru
";
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Offline - LuxPerfume Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Roboto', sans-serif; line-height: 1.6; color: #333; background: linear-gradient(135deg, #f9f9f9 0%, #e0e0e0 100%); position: relative; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: url('https://source.unsplash.com/1920x1080/?perfume,luxury') no-repeat center/cover; z-index: -1; opacity: 0.03; }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .container { max-width: 800px; margin: 0 auto; padding: 20px; }
        header { background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(255,215,0,0.8) 100%); color: white; padding: 25px; text-align: center; position: sticky; top: 0; z-index: 100; box-shadow: 0 8px 32px rgba(0,0,0,0.2); border-radius: 0 0 20px 20px; backdrop-filter: blur(10px); }
        header h1 { font-size: 2.5rem; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        header p { font-size: 1.1rem; opacity: 0.9; }
        .back-link { display: inline-flex; align-items: center; gap: 8px; background: rgba(255,255,255,0.2); color: white; padding: 12px 20px; text-decoration: none; border-radius: 50px; margin-top: 10px; transition: all 0.3s ease; backdrop-filter: blur(5px); }
        .back-link:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(255,215,0,0.3); }
        .checkout-section { background: rgba(255,255,255,0.95); padding: 40px; border-radius: 20px; margin: 30px 0; box-shadow: 0 10px 40px rgba(0,0,0,0.1); backdrop-filter: blur(10px); text-align: center; }
        .checkout-section h3 { font-size: 2rem; color: #28a745; margin-bottom: 20px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        .konfirmasi-card { background: rgba(40,167,69,0.1); padding: 30px; border-radius: 15px; margin: 20px 0; border-left: 5px solid #28a745; }
        .konfirmasi-card p { margin: 10px 0; font-size: 1.1rem; }
        .total-final { font-size: 2rem; color: #ffd700; font-weight: bold; margin: 20px 0; text-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .struk-section { background: white; padding: 40px; border-radius: 15px; margin: 30px 0; box-shadow: 0 5px 20px rgba(0,0,0,0.1); font-family: 'Courier New', monospace; white-space: pre-line; line-height: 1.5; max-width: 400px; margin: 30px auto; }
        .struk-section h4 { text-align: center; color: #000; margin-bottom: 20px; font-size: 1.5rem; }
        @media print { .struk-section { box-shadow: none; margin: 0; padding: 20px; } header, .back-link, .checkout-section { display: none; } body { background: white; } }
        .print-btn { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #000; padding: 15px 30px; border: none; border-radius: 15px; font-weight: bold; cursor: pointer; transition: all 0.3s ease; margin: 10px; font-size: 1rem; }
        .print-btn:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(255,215,0,0.3); }
        .back-btn { background: linear-gradient(135deg, #6c757d 0%, #adb5bd 100%); color: white; }

        /* ===== RESPONSIVE TAMBAHAN ===== */
        @media (max-width: 768px) {
            header h1 { font-size: 1.8rem; flex-direction: column; }
            header p { font-size: 0.95rem; }
            .checkout-section { padding: 20px; margin: 20px 10px; }
            .struk-section { padding: 20px; max-width: 90%; }
            .print-btn { width: 100%; margin: 10px 0; font-size: 0.95rem; }
            .konfirmasi-card p { font-size: 1rem; }
            .total-final { font-size: 1.5rem; }
        }

        @media (max-width: 480px) {
            header h1 { font-size: 1.5rem; }
            .checkout-section h3 { font-size: 1.4rem; }
            .struk-section { font-size: 0.85rem; }
        }
    </style>
</head>
<body onload="window.print();">
    <header>
        <h1><i class="fas fa-receipt"></i> Checkout Offline</h1>
        <p>Transaksi berhasil diproses. Siap cetak struk!</p>
        <a href="dashboard_admin.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </header>

    <div class="container">
        <div class="checkout-section">
            <h3><i class="fas fa-check-circle"></i> Transaksi Berhasil!</h3>
            <div class="konfirmasi-card">
                <p><strong>Produk:</strong> <?= $nama_produk ?></p>
                <p><strong>Jumlah:</strong> <?= $jumlah ?> pcs</p>
                <p><strong>Harga Jual:</strong> Rp <?= number_format($harga_jual, 0, ',', '.'); ?> / pcs</p>
                <p><strong>Stok Sebelum:</strong> <?= $stok_saat_ini ?> → <strong>Setelah:</strong> <?= $stok_baru ?></p>
                <div class="total-final">Total: Rp <?= number_format($total, 0, ',', '.'); ?></div>
            </div>
            <button onclick="window.print()" class="print-btn">
                <i class="fas fa-print"></i> Cetak Struk
            </button>
            <a href="etalase_kasir.php" class="print-btn back-btn">
                <i class="fas fa-shopping-cart"></i> Transaksi Lagi
            </a>
        </div>

        <div class="struk-section">
            <h4>LUXPERFUME</h4>
            <?= $struk_content ?>
        </div>
    </div>
</body>
</html>
<?php $conn->close(); ?>
