<?php
session_start();
include "koneksi.php";

// Pastikan user login
if(!isset($_SESSION['role']) || $_SESSION['role']!=='pembeli'){
    header("Location: index.php");
    exit;
}

// Inisialisasi keranjang
if (!isset($_SESSION['cart'])) $_SESSION['cart'] = [];

// === HANDLE ADD TO CART ===
if (isset($_POST['add_to_cart'])) {
    $id = (int)$_POST['parfum_id'];
    $qty = max(1, (int)$_POST['qty']);

    $stmt = $conn->prepare("SELECT * FROM parfum WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prod = $res->fetch_assoc();
    $stmt->close();

    if ($prod) {
        $available = (int)$prod['jumlah_stok'];
        $inCart = isset($_SESSION['cart'][$id]) ? (int)$_SESSION['cart'][$id]['qty'] : 0;

        if ($available <= 0) echo "<script>alert('Stok parfum ini habis.');</script>";
        elseif ($qty + $inCart > $available) echo "<script>alert('Jumlah melebihi stok tersedia.');</script>";
        else {
            $_SESSION['cart'][$id] = [
                'nama' => $prod['nama'],
                'harga' => ($prod['harga_min'] + $prod['harga_max'])/2,
                'gambar' => $prod['gambar'],
                'qty' => $inCart + $qty
            ];
        }
    }
}

// === HANDLE BUY NOW ===
if (isset($_POST['buy_now'])) {
    $id = (int)$_POST['parfum_id'];
    $qty = max(1, (int)$_POST['qty']);

    $stmt = $conn->prepare("SELECT * FROM parfum WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();
    $res = $stmt->get_result();
    $prod = $res->fetch_assoc();
    $stmt->close();

    if ($prod) {
        $available = (int)$prod['jumlah_stok'];
        if ($available <= 0) echo "<script>alert('Stok parfum ini habis.');</script>";
        elseif ($qty > $available) echo "<script>alert('Jumlah melebihi stok tersedia.');</script>";
        else {
            $_SESSION['cart'] = [];
            $_SESSION['cart'][$id] = [
                'nama' => $prod['nama'],
                'harga' => ($prod['harga_min'] + $prod['harga_max'])/2,
                'gambar' => $prod['gambar'],
                'qty' => $qty
            ];
            header("Location: checkout_online.php");
            exit;
        }
    }
}

// === HAPUS ITEM CART ===
if (isset($_GET['hapus_cart'])) {
    $id = (int)$_GET['hapus_cart'];
    unset($_SESSION['cart'][$id]);
    header("Location: stok.php");
    exit;
}

// === FILTER & SEARCH ===
$search = $_GET['search'] ?? '';
$order = $_GET['order'] ?? 'az';
$min_harga = (int)($_GET['min_harga'] ?? 0);
$max_harga = (int)($_GET['max_harga'] ?? 10000000);

switch ($order) {
    case 'za': $orderBy = "nama DESC"; break;
    case 'murah': $orderBy = "harga_min ASC"; break;
    case 'mahal': $orderBy = "harga_max DESC"; break;
    default: $orderBy = "nama ASC"; break;
}

$sql = "SELECT * FROM parfum WHERE (nama LIKE ? OR brand LIKE ? OR deskripsi LIKE ?) AND (harga_min >= ? AND harga_max <= ?) ORDER BY $orderBy";
$stmt = $conn->prepare($sql);
$like = "%$search%";
$stmt->bind_param("sssii",$like,$like,$like,$min_harga,$max_harga);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<title>Etalase Parfum - LuxPerfume</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&family=Merriweather:wght@700&display=swap" rel="stylesheet">
<style>
/* ========== GLOBAL STYLING ========== */
:root{
  --bg:#0e0e10;
  --panel:#0f1113;
  --muted:#bdbdbd;
  --gold:#ffd700;
  --accent:#222326;
  --card-shadow: 0 8px 24px rgba(0,0,0,0.6);
}
*{box-sizing:border-box}
body { margin:0; font-family:Poppins,Arial,Helvetica,sans-serif; background:linear-gradient(180deg,#070708 0%, #0b0b0d 100%); color:#eee; }

/* NAVBAR */
nav{
  background:var(--panel);
  color:#fff;
  padding:18px 28px;
  display:flex;
  align-items:center;
  justify-content:space-between;
  border-bottom:4px solid rgba(255,215,0,0.06);
  position:sticky;
  top:0;
  z-index:50;
}
.brand { display:flex; align-items:center; gap:12px; }
.brand .logo{
  width:36px; height:36px; background:var(--gold); color:#000; border-radius:6px; display:flex; align-items:center; justify-content:center; font-weight:800; font-family:Merriweather, serif;
}
.brand h1{ margin:0; font-size:20px; color:var(--gold); font-family:Merriweather, serif; letter-spacing:0.5px; }
.nav-links{ display:flex; gap:18px; align-items:center; font-weight:600; color:var(--muted); }
.nav-links a{ color:var(--muted); text-decoration:none; padding:6px 10px; border-radius:6px; transition:0.2s; }
.nav-links a:hover{ color:#fff; background:rgba(255,255,255,0.03); }
.header-right{ display:flex; align-items:center; gap:14px; }

/* Tambahan untuk burger menu responsif */
.burger {
  display:none;
  flex-direction:column;
  cursor:pointer;
  gap:4px;
}
.burger div {
  width:25px;
  height:3px;
  background-color:var(--gold);
  border-radius:2px;
}

/* HEADER BANNER */
.header-banner{
  padding:28px;
  text-align:center;
  border-bottom:1px solid rgba(255,255,255,0.03);
}
.header-banner h2{ margin:0; font-family:Merriweather, serif; color:var(--gold); font-size:28px; letter-spacing:1px; }
.header-banner p{ margin:6px 0 0; color:var(--muted); }

/* FILTERS */
.controls{
  display:flex; gap:10px; align-items:center; justify-content:center; padding:14px 18px; flex-wrap:wrap;
}
.controls form{ display:flex; gap:10px; align-items:center; flex-wrap:wrap; }
.controls input[type=text], .controls select, .controls input[type=number]{
  padding:10px 12px; border-radius:10px; border:1px solid rgba(255,255,255,0.06); background:rgba(255,255,255,0.02); color:#fff; outline:none;
}
.controls button { padding:10px 14px; border-radius:10px; border:none; cursor:pointer; font-weight:700; background:var(--gold); color:#000; }

/* GRID */
.container { padding:22px; display:grid; gap:22px; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); }

/* CARD STYLE */
.card {
  background: linear-gradient(180deg, rgba(255,255,255,0.02), rgba(0,0,0,0.05));
  border-radius:14px; overflow:hidden; box-shadow:var(--card-shadow);
  transition: transform .18s ease, box-shadow .18s ease;
  position:relative;
}
.card:hover { transform:translateY(-8px); box-shadow:0 20px 40px rgba(0,0,0,0.6); }
.card .imgwrap{ width:100%; height:220px; background:#111; display:flex; align-items:center; justify-content:center; overflow:hidden; }
.card .imgwrap img{ width:100%; height:100%; object-fit:cover; transition:transform .25s ease; }
.card:hover .imgwrap img{ transform:scale(1.05); }

.card .body{ padding:14px; }
.card .meta { display:flex; align-items:center; justify-content:space-between; gap:10px; }
.card .name { font-weight:700; font-size:16px; color:#fff; margin-bottom:6px; font-family:Merriweather, serif; }
.card .brand { font-size:12px; color:var(--muted); }
.card .price { font-weight:800; color:var(--gold); font-size:15px; margin-top:6px; }
.card .stock { font-size:12px; color:var(--muted); margin-top:4px; }
.card .stock.low { color:#ff7675; font-weight:700; }

/* BUTTON */
.btn {
  flex:1; padding:10px 12px; border-radius:10px; border:none; cursor:pointer; font-weight:700; transition:transform .12s ease;
}
.btn:hover{ transform:translateY(-3px); }
.btn.add { background:var(--gold); color:#000; }
.btn.buy { background:#111; color:var(--gold); border:1px solid rgba(255,215,0,0.12); }

/* BADGE */
.badge { position:absolute; top:12px; left:12px; background:#e74c3c; color:#fff; padding:6px 8px; border-radius:8px; font-weight:700; font-size:12px; }

/* CART SLIDE */
.cart-slide {
  position:fixed; right:18px; top:80px; width:360px; max-height:78vh; background:linear-gradient(180deg,#0b0b0d,#0f1113);
  border:1px solid rgba(255,215,0,0.06); border-radius:12px; padding:14px; overflow:auto; box-shadow:0 20px 60px rgba(0,0,0,0.6);
  transform:translateX(420px); transition:transform .28s cubic-bezier(.2,.9,.3,1); z-index:999;
}
.cart-slide.show{ transform:translateX(0); }
.cart-slide h3{ margin:0 0 10px 0; color:var(--gold); font-family:Merriweather, serif; }
.cart-item{ display:flex; gap:10px; align-items:center; padding:8px 0; border-bottom:1px dashed rgba(255,255,255,0.02); }
.cart-item img{ width:58px; height:58px; object-fit:cover; border-radius:8px; }
.cart-item .meta{ flex:1; color:var(--muted); font-size:13px; }
.cart-total { text-align:right; margin-top:12px; font-weight:800; color:var(--gold); }

/* MODAL */
.modal { display:none; position:fixed; inset:0; background:rgba(0,0,0,0.7); justify-content:center; align-items:center; z-index:1000; }
.modal .dialog { width:92%; max-width:920px; background:var(--panel); border-radius:12px; padding:16px; display:flex; gap:14px; color:#fff; flex-wrap:wrap; }
.modal .left img{ width:380px; max-width:42vw; height:380px; object-fit:cover; border-radius:10px; }
.modal .right{ flex:1; display:flex; flex-direction:column; gap:10px; }
.close-modal{ align-self:flex-end; background:transparent; color:var(--muted); border:none; font-size:28px; cursor:pointer; }

/* RESPONSIVE */
@media(max-width:880px){
  .modal .left img{ width:46vw; height:46vw; }
}
@media(max-width:720px){
  nav{ flex-wrap:wrap; justify-content:space-between; }
  .burger { display:flex; }
  .nav-links{ display:none; flex-direction:column; width:100%; background:#111; padding:10px 0; }
  .nav-links.active{ display:flex; }
  .container{ grid-template-columns:repeat(2,1fr); }
  .cart-slide{ right:10px; width:300px; top:70px; }
}
@media(max-width:420px){
  .container{ grid-template-columns:repeat(1,1fr); }
  .card .imgwrap{ height:200px; }
}
</style>
</head>
<body>

<!-- NAVBAR -->
<nav>
  <div class="brand">
    <div class="logo">LP</div>
    <div>
      <h1>LuxPerfume</h1>
      <div style="font-size:12px;color:var(--muted); margin-top:2px;">Etalase Parfum Premium</div>
    </div>
  </div>

  <div class="burger" id="burger">
    <div></div><div></div><div></div>
  </div>

  <div class="nav-links" id="navLinks">
    <a href="dashboard_pembeli.php">Home</a>
    <a href="stok.php" style="color:#fff;">Products</a>
    <a href="dashboard_pembeli.php#about">About</a>
    <a href="dashboard_pembeli.php#contact">Contact</a>
  </div>

  <div class="header-right">
    <div style="font-size:14px;color:var(--muted);">ID Member: 
      <strong style="color:#fff; margin-left:6px;"><?= htmlspecialchars($_SESSION['member_id']); ?></strong>
    </div>
    <div id="cartBtn" style="background:var(--gold); color:#000; padding:8px 12px; border-radius:10px; font-weight:800; cursor:pointer;">🛒 (<?= isset($_SESSION['cart'])?array_sum(array_column($_SESSION['cart'],'qty')):0; ?>)</div>
  </div>
</nav>

<!-- BANNER -->
<div class="header-banner">
  <h2>Etalase Parfum</h2>
  <p>Temukan aroma eksklusif pilihan kami — kualitas premium dengan harga bersaing.</p>
</div>

<!-- FILTER -->
<div class="controls">
  <form method="get">
    <input type="text" name="search" placeholder="Cari parfum..." value="<?= htmlspecialchars($search); ?>">
    <select name="order">
      <option value="az" <?= $order=='az'?'selected':''; ?>>A-Z</option>
      <option value="za" <?= $order=='za'?'selected':''; ?>>Z-A</option>
      <option value="murah" <?= $order=='murah'?'selected':''; ?>>Harga Murah</option>
      <option value="mahal" <?= $order=='mahal'?'selected':''; ?>>Harga Mahal</option>
    </select>
    <input type="number" name="min_harga" placeholder="Min" value="<?= $min_harga; ?>">
    <input type="number" name="max_harga" placeholder="Max" value="<?= $max_harga; ?>">
    <button type="submit">Filter</button>
  </form>
</div>

<!-- PRODUCT GRID -->
<div class="container">
<?php while($row=$result->fetch_assoc()):
    $avg_price = ($row['harga_min']+$row['harga_max'])/2;
    $low_stock = $row['jumlah_stok'] < 5;
    $id = (int)$row['id'];
?>
  <div class="card">
    <?php if($low_stock && $row['jumlah_stok']>0) echo '<div class="badge">Hampir Habis</div>'; ?>
    <div class="imgwrap" onclick="openModal(<?= $id; ?>)">
      <img src="<?= htmlspecialchars($row['gambar']); ?>" alt="<?= htmlspecialchars($row['nama']); ?>">
    </div>
    <div class="body">
      <div class="meta">
        <div>
          <div class="brand"><?= htmlspecialchars($row['brand']); ?></div>
          <div class="name"><?= htmlspecialchars($row['nama']); ?></div>
        </div>
        <div style="text-align:right">
          <div class="price">Rp <?= number_format($avg_price); ?></div>
          <div class="stock <?= $low_stock ? 'low' : ''; ?>">Stok: <?= (int)$row['jumlah_stok']; ?></div>
        </div>
      </div>

      <div class="actions">
        <form method="post" style="display:flex; gap:8px; width:100%;">
          <input type="hidden" name="parfum_id" value="<?= $id; ?>">
          <input type="number" name="qty" value="1" min="1" max="<?= (int)$row['jumlah_stok']; ?>" style="width:70px; border-radius:8px; padding:8px; border:1px solid rgba(255,255,255,0.04); background:transparent; color:#fff;">
          <button type="submit" name="add_to_cart" class="btn add" <?= $row['jumlah_stok']==0?'disabled':''; ?>>Tambah</button>
<button type="submit" name="buy_now" class="btn buy" <?= $row['jumlah_stok']==0?'disabled':''; ?>>Beli</button>
</form>
</div>
</div>
</div>
<?php endwhile; ?>
</div>

<!-- CART SIDEBAR -->
<div class="cart-slide" id="cartSlide">
  <h3>Keranjang Belanja</h3>
  <?php if(!empty($_SESSION['cart'])): ?>
    <?php $total = 0; foreach($_SESSION['cart'] as $id=>$item): $subtotal=$item['harga']*$item['qty']; $total+=$subtotal; ?>
      <div class="cart-item">
        <img src="<?= htmlspecialchars($item['gambar']); ?>" alt="<?= htmlspecialchars($item['nama']); ?>">
        <div class="meta">
          <strong><?= htmlspecialchars($item['nama']); ?></strong><br>
          <small>Qty: <?= $item['qty']; ?> | Rp <?= number_format($item['harga']); ?></small>
        </div>
        <a href="?hapus_cart=<?= $id; ?>" style="color:#e74c3c; font-weight:bold; text-decoration:none;">✕</a>
      </div>
    <?php endforeach; ?>
    <div class="cart-total">Total: Rp <?= number_format($total); ?></div>
    <div style="text-align:right; margin-top:12px;">
      <a href="checkout_online.php" style="background:var(--gold); color:#000; padding:10px 14px; border-radius:8px; text-decoration:none; font-weight:800;">Checkout</a>
    </div>
  <?php else: ?>
    <p style="color:var(--muted); font-size:14px;">Keranjang masih kosong.</p>
  <?php endif; ?>
</div>

<!-- MODAL PRODUK -->
<div class="modal" id="productModal">
  <div class="dialog">
    <button class="close-modal" onclick="closeModal()">×</button>
    <div class="left">
      <img id="modalImg" src="" alt="Parfum">
    </div>
    <div class="right">
      <h2 id="modalNama" style="color:var(--gold); font-family:Merriweather, serif; margin-bottom:6px;"></h2>
      <div id="modalBrand" style="font-weight:600; color:var(--muted); margin-bottom:4px;"></div>
      <div id="modalHarga" style="font-weight:800; font-size:18px; color:#fff; margin-bottom:10px;"></div>
      <div id="modalDeskripsi" style="white-space:pre-line; line-height:1.5; font-size:14px; color:var(--muted);"></div>
      <div style="margin-top:14px;">
        <button class="btn add" onclick="closeModal()">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- === TAMBAHAN CSS RESPONSIF === -->
<style>
@media (max-width: 1024px) {
  .produk-grid {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 14px;
  }
}
@media (max-width: 768px) {
  .produk-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }
  .produk-card {
    padding: 10px;
    font-size: 14px;
  }
  .produk-card img {
    width: 100%;
    height: auto;
    border-radius: 10px;
  }
  .produk-info {
    padding: 6px 0;
  }
  .produk-info h4 {
    font-size: 15px;
  }
  .btn {
    font-size: 13px;
    padding: 6px 8px;
  }
}
@media (max-width: 480px) {
  .produk-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 10px;
  }
  .produk-card {
    margin-bottom: 10px;
  }
  .cart-slide {
    width: 90%;
  }
  .modal .dialog {
    flex-direction: column;
    width: 90%;
  }
  .modal .left img {
    width: 100%;
  }
  .modal .right {
    padding: 10px;
  }
  #navLinks.active {
    display: flex;
    flex-direction: column;
    background: #111;
    position: absolute;
    top: 60px;
    right: 0;
    width: 100%;
    text-align: center;
    gap: 14px;
    padding: 10px 0;
    border-top: 1px solid #333;
  }
}
</style>

<!-- === SCRIPT === -->
<script>
// === BURGER MENU TOGGLE ===
document.getElementById('burger').addEventListener('click', function(){
  document.getElementById('navLinks').classList.toggle('active');
});

// === CART SIDEBAR ===
const cartBtn = document.getElementById('cartBtn');
const cartSlide = document.getElementById('cartSlide');
let cartOpen = false;
cartBtn.onclick = ()=>{ cartOpen = !cartOpen; cartSlide.classList.toggle('show', cartOpen); };

// === MODAL PRODUK ===
const modal = document.getElementById('productModal');
const modalImg = document.getElementById('modalImg');
const modalNama = document.getElementById('modalNama');
const modalBrand = document.getElementById('modalBrand');
const modalHarga = document.getElementById('modalHarga');
const modalDeskripsi = document.getElementById('modalDeskripsi');

function openModal(id){
  fetch('get_detail_parfum.php?id='+id)
  .then(r=>r.json())
  .then(data=>{
    modalImg.src = data.gambar;
    modalNama.textContent = data.nama;
    modalBrand.textContent = data.brand;
    modalHarga.textContent = "Rp " + new Intl.NumberFormat().format(data.harga);
    modalDeskripsi.textContent = data.deskripsi;
    modal.style.display='flex';
  });
}
function closeModal(){ modal.style.display='none'; }

// Tutup modal jika klik di luar area dialog
window.onclick = function(e){ if(e.target === modal){ closeModal(); } }
</script>

</body>
</html>

