<?php
session_set_cookie_params(7200); // 2 jam
session_start();
include "koneksi.php";

// Cek login dan role
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'pembeli') {
    header("Location: index.php");
    exit;
}

// Timeout otomatis 2 jam
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 7200)) {
    session_unset();
    session_destroy();
    header("Location: index.php?expired=true");
    exit;
}
$_SESSION['last_activity'] = time();

// Ambil profil admin
$stmt = $conn->prepare("SELECT * FROM users WHERE role='admin' LIMIT 1");
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();
$stmt->close();

$nama_toko = $profil['nama_perusahaan'] ?? 'Toko Parfum Etam';
$contact   = $profil['contact_person'] ?? 'Belum diatur';
$alamat    = $profil['alamat'] ?? 'Alamat belum diatur.';
$email     = $profil['email'] ?? 'Belum diatur';
$telepon   = $profil['telepon'] ?? 'Belum diatur';
$maps      = $profil['maps'] ?? '';
$logo      = !empty($profil['logo']) ? "uploads/" . htmlspecialchars($profil['logo']) : "assets/default-bg.jpg";
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($nama_toko) ?> - Dashboard Pembeli</title>
  <style>
    body { font-family: "Poppins", sans-serif; background-color: #111; color: #fff; margin: 0; }
    header {
      background: url("<?= $logo ?>") no-repeat center/cover;
      padding: 60px 20px; text-align: center; position: relative;
    }
    header::before {
      content: ""; position: absolute; inset: 0; background: rgba(0,0,0,0.6);
    }
    .brand { position: relative; z-index: 2; }
    .brand img { width: 90px; height: 90px; border-radius: 50%; border: 3px solid #FFD700; }
    .brand h1 { color: #FFD700; font-size: 2.5em; margin-top: 10px; }

    /* Navigasi default (desktop) */
    nav {
      display: flex;
      justify-content: center;
      background-color: #000;
      border-bottom: 3px solid #FFD700;
      position: relative;
      z-index: 3;
    }
    nav a {
      color: #FFD700;
      padding: 15px 30px;
      text-decoration: none;
      font-weight: 600;
      transition: 0.3s;
    }
    nav a:hover { background-color: #FFD700; color: #000; }

    /* Struktur umum */
    section { padding: 40px 20px; max-width: 900px; margin: auto; }
    h2, h3 { color: #FFD700; border-bottom: 2px solid #FFD700; display: inline-block; padding-bottom: 5px; }
    .about-info, .contact-info {
      background: #222; border: 1px solid #FFD700; padding: 20px; border-radius: 10px; margin-top: 20px;
    }
    iframe { width: 100%; height: 300px; border: none; border-radius: 10px; margin-top: 15px; }

    footer {
      background-color: #000; color: #FFD700; text-align: center;
      padding: 15px 0; margin-top: 50px; border-top: 2px solid #FFD700;
    }

    /* ===== Burger Button ===== */
    .menu-toggle {
      display: none;
      flex-direction: column;
      justify-content: center;
      cursor: pointer;
      padding: 15px;
      position: absolute;
      top: 15px;
      right: 20px;
      z-index: 5;
    }
    .menu-toggle span {
      background: #FFD700;
      height: 3px;
      margin: 4px 0;
      width: 28px;
      border-radius: 2px;
      transition: 0.3s;
    }

    /* Animasi X */
    .menu-toggle.active span:nth-child(1) {
      transform: rotate(45deg) translateY(8px);
    }
    .menu-toggle.active span:nth-child(2) {
      opacity: 0;
    }
    .menu-toggle.active span:nth-child(3) {
      transform: rotate(-45deg) translateY(-8px);
    }

    /* ===== Responsif HP ===== */
    @media (max-width: 768px) {
      nav {
        display: none; /* Hilang dari tampilan utama */
        flex-direction: column;
        align-items: center;
        width: 100%;
        position: absolute;
        top: 100%;
        left: 0;
        background: #000;
        border-top: 3px solid #FFD700;
        border-bottom: 3px solid #FFD700;
      }
      nav.active {
        display: flex; /* Muncul ketika burger ditekan */
      }
      nav a {
        width: 100%;
        text-align: center;
        border-top: 1px solid rgba(255,215,0,0.2);
        padding: 15px;
      }
      .menu-toggle {
        display: flex;
      }
    }
  </style>
</head>
<body>
  <header>
    <div class="brand">
      <img src="<?= $logo ?>" alt="Logo">
      <h1><?= htmlspecialchars($nama_toko) ?></h1>
      <p><?= htmlspecialchars($contact) ?></p>
    </div>
    <!-- Tombol burger -->
    <div class="menu-toggle" id="menu-toggle">
      <span></span>
      <span></span>
      <span></span>
    </div>
    <!-- Navigasi -->
    <nav id="navbar">
      <a href="dashboard_pembeli.php">Home</a>
      <a href="stok.php">Product</a>
      <a href="#about">About</a>
    </nav>
  </header>

  <section id="home">
    <h2>Selamat Datang di <?= htmlspecialchars($nama_toko) ?></h2>
    <div class="about-info">
      <p>Temukan koleksi parfum terbaik kami yang memadukan kemewahan dan karakter khas setiap wangi. 
         Semua produk kami 100% original dan dikurasi langsung oleh tim profesional.</p>
    </div>
  </section>

  <section id="about">
    <h3>Tentang Kami</h3>
    <div class="about-info">
      <p><strong>Nama Toko:</strong> <?= htmlspecialchars($nama_toko) ?></p>
      <p><strong>Alamat:</strong> <?= htmlspecialchars($alamat) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
      <p><strong>Telepon:</strong> <?= htmlspecialchars($telepon) ?></p>
    </div>
    <?php if (!empty($maps)): ?>
      <iframe src="<?= htmlspecialchars($maps) ?>"></iframe>
    <?php endif; ?>
  </section>

  <section id="contact">
    <h3>Kontak Kami</h3>
    <div class="contact-info">
      <p><strong>Contact Person:</strong> <?= htmlspecialchars($contact) ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($email) ?></p>
      <p><strong>Telepon:</strong> <?= htmlspecialchars($telepon) ?></p>
    </div>
  </section>

  <footer>
    <p>© <?= date('Y') ?> <?= htmlspecialchars($nama_toko) ?>. All Rights Reserved.</p>
  </footer>

  <!-- Script untuk burger -->
  <script>
    const toggle = document.getElementById('menu-toggle');
    const navbar = document.getElementById('navbar');
    toggle.addEventListener('click', () => {
      toggle.classList.toggle('active');
      navbar.classList.toggle('active');
    });
  </script>
</body>
</html>
