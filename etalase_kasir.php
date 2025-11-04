<?php
session_start();
include "koneksi.php";

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

// Ambil data parfum dengan stok masih tersedia
$etalase = $conn->query("SELECT * FROM parfum WHERE jumlah_stok > 0 ORDER BY id DESC");
$produk_list = [];
while ($row = $etalase->fetch_assoc()) {
    $row['harga_rata'] = ($row['harga_min'] + $row['harga_max']) / 2;
    $produk_list[] = $row;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Etalase Kasir - LuxPerfume Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* ======= GLOBAL ======= */
* { margin: 0; padding: 0; box-sizing: border-box; }
body {
    font-family: 'Roboto', sans-serif;
    background: linear-gradient(135deg, #f7f7f7 0%, #e5e5e5 100%);
    overflow-x: hidden;
    color: #333;
}
body::before {
    content: '';
    position: fixed; top: 0; left: 0; width: 100%; height: 100%;
    background: url('https://source.unsplash.com/1920x1080/?perfume,luxury') center/cover no-repeat;
    opacity: 0.03; z-index: -1;
}

/* ======= HEADER ======= */
header {
    background: linear-gradient(135deg, #000000 0%, #caa300 100%);
    color: white;
    padding: 25px;
    text-align: center;
    border-radius: 0 0 25px 25px;
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
}
header h1 {
    font-family: 'Playfair Display', serif;
    font-size: 2.4rem;
}
header p { opacity: 0.9; }
.back-link {
    display: inline-block;
    margin-top: 10px;
    text-decoration: none;
    color: white;
    background: rgba(255,255,255,0.2);
    padding: 10px 20px;
    border-radius: 30px;
    transition: 0.3s;
}
.back-link:hover { background: rgba(255,255,255,0.3); }

/* ======= CONTAINER & SEARCH ======= */
.container { max-width: 1300px; margin: 30px auto; padding: 0 20px; }
.search-section { text-align: center; margin-bottom: 25px; }
.search-input {
    width: 90%; max-width: 500px; padding: 12px 20px;
    border: 2px solid #000; border-radius: 50px; font-size: 1rem;
    transition: 0.3s;
}
.search-input:focus {
    border-color: gold;
    box-shadow: 0 0 8px rgba(255,215,0,0.4);
    outline: none;
}

/* ======= GRID PRODUK (4 di laptop, 2 di hp) ======= */
.kasir-grid {
    display: grid;
    grid-template-columns: repeat(4, 1fr);
    gap: 25px;
}
.kasir-card {
    background: white;
    border-radius: 20px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.08);
    overflow: hidden;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}
.kasir-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 15px 40px rgba(0,0,0,0.15);
}
.kasir-card img {
    width: 100%;
    height: 250px;
    object-fit: cover;
}
.card-body {
    padding: 20px;
    text-align: center;
}
.card-body h4 { font-family: 'Playfair Display', serif; font-size: 1.3rem; }
.card-body .brand { color: #caa300; font-weight: bold; }
.price {
    color: #caa300; font-size: 1.4rem; margin: 10px 0; font-weight: bold;
}
.stock { color: #28a745; margin-bottom: 10px; }

/* ======= FORM ======= */
.kasir-form {
    background: rgba(255,215,0,0.05);
    padding: 15px;
    border-radius: 15px;
    text-align: left;
}
.kasir-form label { font-weight: 600; font-size: 0.9rem; }
.kasir-form input[type=number] {
    width: 100%; padding: 10px; border-radius: 8px;
    border: 2px solid #000; margin-bottom: 10px;
}
.kasir-form input[type=number]:focus {
    border-color: gold; outline: none;
}
.total-display {
    text-align: center; font-weight: bold; color: #28a745; margin: 10px 0;
}
.kembalian-display {
    text-align: center; font-weight: bold; margin: 8px 0;
}
.kembalian-display.green { color: #28a745; }
.kembalian-display.red { color: #dc3545; }
.kembalian-display.orange { color: #ff9800; }

.kasir-btn {
    width: 100%; background: linear-gradient(135deg, #28a745, #20c997);
    border: none; color: white; padding: 12px; border-radius: 10px;
    font-weight: bold; cursor: pointer; transition: 0.3s;
}
.kasir-btn:hover {
    background: linear-gradient(135deg, #218838, #17a589);
    transform: translateY(-2px);
}
.no-products {
    grid-column: 1 / -1;
    text-align: center;
    padding: 40px;
    background: #000;
    border-radius: 20px;
    color: white;
    font-style: italic;
}

/* ======= RESPONSIVE ======= */
@media (max-width: 992px) {
    .kasir-grid {
        grid-template-columns: repeat(2, 1fr);
    }
}
@media (max-width: 768px) {
    header h1 { font-size: 1.8rem; }
    .search-input { width: 100%; }
    .kasir-card img { height: 200px; }
}
</style>
</head>
<body>

<header>
    <h1><i class="fas fa-store"></i> Etalase / Mesin Kasir</h1>
    <p>Pilih parfum, tentukan jumlah & harga jual, masukkan nominal bayar lalu cetak struk.</p>
    <a href="dashboard_admin.php" class="back-link"><i class="fas fa-arrow-left"></i> Kembali ke Dashboard</a>
</header>

<div class="container">
    <div class="search-section">
        <input type="text" id="searchInput" class="search-input" placeholder="🔍 Cari parfum berdasarkan nama atau brand..." onkeyup="filterProducts()">
    </div>

    <div class="kasir-grid" id="productGrid">
        <?php if (empty($produk_list)): ?>
            <div class="no-products">
                <i class="fas fa-box-open fa-2x"></i>
                <p>Tidak ada stok tersedia. Tambahkan produk baru di menu Upload Produk.</p>
            </div>
        <?php else: ?>
            <?php foreach ($produk_list as $row): ?>
                <div class="kasir-card" data-name="<?= strtolower($row['nama'] . ' ' . $row['brand']); ?>">
                    <img src="<?= htmlspecialchars($row['gambar']); ?>" alt="<?= htmlspecialchars($row['nama']); ?>" 
                         onerror="this.src='https://via.placeholder.com/350x250?text=LuxPerfume&color=ffd700&bg=000'">
                    <div class="card-body">
                        <h4><?= htmlspecialchars($row['nama']); ?></h4>
                        <div class="brand"><i class="fas fa-crown"></i> <?= htmlspecialchars($row['brand']); ?></div>
                        <div class="price">Rp <?= number_format($row['harga_rata'],0,',','.'); ?></div>
                        <div class="stock"><i class="fas fa-warehouse"></i> <?= $row['jumlah_stok']; ?> stok</div>

                        <form method="get" action="checkout_offline.php" class="kasir-form">
                            <input type="hidden" name="parfum_id" value="<?= $row['id']; ?>">
                            
                            <label>Jumlah:</label>
                            <input type="number" name="jumlah" id="jumlah_<?= $row['id']; ?>" min="1" max="<?= $row['jumlah_stok']; ?>" value="1" required oninput="updateTotal(<?= $row['id']; ?>)">
                            
                            <label>Harga Jual (Rp):</label>
                            <input type="number" name="harga_jual" id="harga_jual_<?= $row['id']; ?>" min="<?= $row['harga_min']; ?>" max="<?= $row['harga_max']; ?>" value="<?= $row['harga_rata']; ?>" required oninput="updateTotal(<?= $row['id']; ?>)">
                            
                            <div class="total-display" id="total_<?= $row['id']; ?>">Total: Rp <span id="totalValue_<?= $row['id']; ?>">0</span></div>

                            <label>Nominal Bayar (Rp):</label>
                            <input type="number" id="bayar_<?= $row['id']; ?>" min="0" placeholder="Masukkan nominal pembayaran" oninput="hitungKembalian(<?= $row['id']; ?>)">
                            
                            <div class="kembalian-display" id="kembalian_<?= $row['id']; ?>"></div>

                            <button type="submit" class="kasir-btn"><i class="fas fa-shopping-cart"></i> Checkout Offline & Cetak Struk</button>
                        </form>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
</div>

<script>
function updateTotal(id) {
    const jumlah = parseInt(document.getElementById('jumlah_' + id).value) || 1;
    const harga = parseInt(document.getElementById('harga_jual_' + id).value) || 0;
    const total = jumlah * harga;
    document.getElementById('totalValue_' + id).textContent = new Intl.NumberFormat('id-ID').format(total);
    hitungKembalian(id);
}

function hitungKembalian(id) {
    const total = parseInt(document.getElementById('jumlah_' + id).value) * parseInt(document.getElementById('harga_jual_' + id).value);
    const bayar = parseInt(document.getElementById('bayar_' + id).value) || 0;
    const kembalianEl = document.getElementById('kembalian_' + id);

    if (bayar === 0) {
        kembalianEl.textContent = '';
        return;
    }

    const selisih = bayar - total;

    if (selisih > 0) {
        kembalianEl.textContent = '💵 Kembalian: Rp ' + new Intl.NumberFormat('id-ID').format(selisih);
        kembalianEl.className = 'kembalian-display green';
    } else if (selisih === 0) {
        kembalianEl.textContent = '💰 Uang pas';
        kembalianEl.className = 'kembalian-display orange';
    } else {
        kembalianEl.textContent = '⚠️ Nominal kurang Rp ' + new Intl.NumberFormat('id-ID').format(Math.abs(selisih));
        kembalianEl.className = 'kembalian-display red';
    }
}

function filterProducts() {
    const input = document.getElementById('searchInput').value.toLowerCase();
    const cards = document.querySelectorAll('.kasir-card');
    let visible = 0;

    cards.forEach(card => {
        const name = card.dataset.name;
        if (name.includes(input)) {
            card.style.display = 'block';
            visible++;
        } else {
            card.style.display = 'none';
        }
    });

    if (visible === 0) {
        document.getElementById('productGrid').innerHTML = `
            <div class='no-products'>
                <i class='fas fa-search fa-2x'></i>
                <p>Tidak ditemukan produk. Coba kata lain.</p>
            </div>`;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    <?php foreach ($produk_list as $p): ?>
    updateTotal(<?= $p['id']; ?>);
    <?php endforeach; ?>
});
</script>

</body>
</html>
<?php $conn->close(); ?>
