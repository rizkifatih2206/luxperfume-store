<?php
session_start();

// Koneksi database
$servername = "localhost";
$username = "root";
$password = "";
$database = "parfum";

$conn = new mysqli($servername, $username, $password, $database);
if ($conn->connect_error) {
    die("Koneksi gagal: " . $conn->connect_error);
}

$role   = isset($_SESSION['role']) ? $_SESSION['role'] : null;
$brand  = isset($_GET['brand']) ? $_GET['brand'] : '';
$search = isset($_GET['q']) ? $_GET['q'] : '';

// Mapping nama parfum ke gambar
$gambarMap = [
    "Dreamscape"             => "dreamscape.jpg",
    "Silent Whisper"         => "silent_whisper.jpg",
    "Mykonos Enchanted"      => "enchanted.jpg",
    "Aphrodite"              => "aphrodite.jpg",
    "California Signature"   => "california_signature.jpg",
    "Farhampton"             => "farhampton.jpg",
    "ORGSM"                  => "orgsm.jpg",
    "Essence Of The Sun"     => "essence_sun.jpg",
    "Essence of The Night"   => "essence_night.jpg",
    "Unrosed"                => "unrosed.jpg",
    "The Perfection"         => "perfection.jpg",
    "The Prestige"           => "prestige.jpg",
    "Afnan 9PM"              => "9pm.jpg",
    "Afnan 9AM Dive"         => "9am_dive.jpg",
    "Supremacy Collector"    => "supremacy_collector.jpg",
    "Turathi Blue"           => "turathi_blue.jpg",
    "9PM Rebel"              => "9pm_rebel.jpg",
    "Zimaya Sharaf Blend"    => "sharaf_blend.jpg",
    "Zimaya Sharaf The Club" => "sharaf_club.jpg",
    "Rasasi Hawas Ice"       => "hawas_ice.jpg"
];

// 🔍 Filter query
$where = "WHERE 1=1";
if ($brand !== '') {
    $where .= " AND brand='" . $conn->real_escape_string($brand) . "'";
}
if ($search !== '') {
    $esc = $conn->real_escape_string($search);
    $where .= " AND (nama LIKE '%$esc%' OR deskripsi LIKE '%$esc%')";
}

// Ambil data parfum + daftar brand
$q      = $conn->query("SELECT * FROM parfum $where ORDER BY brand, nama");
$brands = $conn->query("SELECT DISTINCT brand FROM parfum ORDER BY brand");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Katalog Parfum</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
  :root {
    --primary:#ff4ec4;
    --primary-dark:#d43ca5;
    --bg:#f6f6f7;
    --card:#ffffff;
    --radius:16px;
  }
  body { margin:0; font-family:Arial, Helvetica, sans-serif; background:var(--bg); }
  .wrap { max-width:1200px; margin:24px auto; padding:0 16px; }
  .topbar { background:var(--card); border-radius:var(--radius); box-shadow:0 8px 24px rgba(0,0,0,.06); padding:16px; display:flex; gap:10px; flex-wrap:wrap; align-items:center; justify-content:space-between; }
  .title { font-size:22px; font-weight:bold; }
  .controls { display:flex; gap:10px; flex-wrap:wrap; }
  .input, .select { padding:10px 12px; border:1px solid #ccc; border-radius:10px; }
  .btn { padding:10px 14px; border:none; border-radius:10px; background:var(--primary); color:white; font-weight:bold; cursor:pointer; }
  .btn:hover { background:var(--primary-dark); }
  .grid { margin-top:20px; display:grid; grid-template-columns: repeat(auto-fill, minmax(220px,1fr)); gap:16px; }
  .card { background:var(--card); border-radius:18px; box-shadow:0 8px 24px rgba(0,0,0,.08); overflow:hidden; display:flex; flex-direction:column; }
  .thumb { width:100%; height:180px; object-fit:cover; }
  .body { padding:14px; }
  .brand { font-size:12px; color:#777; text-transform:uppercase; }
  .name { font-size:16px; font-weight:bold; margin:6px 0; }
  .desc { font-size:13px; color:#555; min-height:34px; }
  .meta { display:flex; justify-content:space-between; margin-top:8px; }
  .price { color:#27ae60; font-weight:bold; font-size:14px; }
  .stock { background:#eee; padding:4px 8px; border-radius:8px; font-size:12px; }
</style>
</head>
<body>
<div class="wrap">
  <div class="topbar">
    <div class="title">Katalog Parfum</div>
    <form class="controls" method="get" action="catalog.php">
      <select name="brand" class="select">
        <option value="">Semua Brand</option>
        <?php while($b = $brands->fetch_assoc()): ?>
          <option value="<?= htmlspecialchars($b['brand']) ?>" <?= $brand===$b['brand']?'selected':'' ?>>
            <?= htmlspecialchars($b['brand']) ?>
          </option>
        <?php endwhile; ?>
      </select>
      <input type="text" class="input" name="q" placeholder="Cari nama atau deskripsi..." value="<?= htmlspecialchars($search) ?>">
      <button class="btn" type="submit">Cari</button>
    </form>
  </div>

  <div class="grid">
    <?php while($p = $q->fetch_assoc()): 
      $img = isset($gambarMap[$p['nama']]) ? $gambarMap[$p['nama']] : 'default.jpg';
    ?>
      <div class="card">
        <img src="images/<?= htmlspecialchars($img) ?>" alt="<?= htmlspecialchars($p['nama']) ?>" class="thumb">
        <div class="body">
          <div class="brand"><?= htmlspecialchars($p['brand']) ?></div>
          <div class="name"><?= htmlspecialchars($p['nama']) ?></div>
          <p class="desc"><?= htmlspecialchars($p['deskripsi']) ?></p>
          <div class="meta">
            <div class="price">Rp <?= number_format($p['harga_min'],0,',','.') ?> – <?= number_format($p['harga_max'],0,',','.') ?></div>
            <div class="stock">Stok: <?= (int)$p['stok'] ?></div>
          </div>
        </div>
      </div>
    <?php endwhile; ?>
  </div>
</div>
</body>
</html>

<?php $conn->close(); ?>
