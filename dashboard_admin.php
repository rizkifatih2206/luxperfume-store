<?php
session_start();
include "koneksi.php";

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LuxPerfume</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: #fff;
            background: #000;
        }

        /* HEADER */
        header {
            background: linear-gradient(135deg, #000 0%, #ffd700 100%);
            color: white;
            padding: 20px;
            text-align: center;
            position: sticky;
            top: 0;
            z-index: 100;
            box-shadow: 0 4px 10px rgba(255,215,0,0.4);
        }
        header h1 { font-size: 2rem; font-family: 'Playfair Display', serif; }
        header p { font-size: 1rem; opacity: 0.9; }

        .burger {
            display: none;
            background: none;
            border: none;
            font-size: 2rem;
            color: white;
            cursor: pointer;
            position: absolute;
            top: 20px;
            right: 20px;
            z-index: 101;
        }
        @media (max-width: 768px) { .burger { display: block; } }

        /* CONTAINER */
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            display: flex;
            min-height: 100vh;
        }

        /* SIDEBAR */
        .sidebar {
            width: 250px;
            background: #111;
            padding: 20px;
            box-shadow: 2px 0 10px rgba(255,215,0,0.3);
            transition: transform 0.3s ease;
            z-index: 200;
        }
        .sidebar ul { list-style: none; }
        .sidebar li { margin-bottom: 15px; }

        .nav-link {
            display: block;
            background: linear-gradient(135deg, #ffd700 0%, #ffea00 100%);
            color: #000;
            padding: 12px 15px;
            text-decoration: none;
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            text-align: center;
        }
        .nav-link:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 10px rgba(255,215,0,0.4);
        }
        .nav-link.logout {
            background: linear-gradient(135deg, #ff4c4c 0%, #ff6b6b 100%);
            color: white;
        }
        .nav-link.logout:hover { box-shadow: 0 4px 10px rgba(255,76,76,0.4); }

        /* MAIN CONTENT */
        .main-content { flex: 1; padding-left: 20px; }
        .section {
            background: rgba(34,34,34,0.95);
            padding: 30px;
            border-radius: 20px;
            margin: 30px 0;
            box-shadow: 0 10px 30px rgba(255,215,0,0.2);
            animation: fadeInUp 0.8s ease-out;
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .section h3 {
            font-size: 1.8rem;
            color: #ffd700;
            margin-bottom: 20px;
            text-align: center;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 10px;
        }

        /* PROFIL ADMIN */
        .profil-section {
            display: flex;
            align-items: center;
            gap: 20px;
            margin-bottom: 20px;
        }
        .profil-avatar {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background: #ffd700;
            color: #000;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2rem;
            font-weight: bold;
        }
        .profil-info p { color: #ddd; margin-bottom: 5px; }

        .btn {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            padding: 10px 20px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
            transition: 0.3s;
        }
        .btn:hover {
            background: #ffed4e;
            box-shadow: 0 0 10px #ffd700;
        }

        /* RESPONSIVE */
        .sidebar.mobile-hidden {
            transform: translateX(-100%);
            position: fixed;
            height: 100vh;
            top: 0;
            left: 0;
        }
        .overlay {
            display: none;
            position: fixed;
            top: 0; left: 0;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 150;
        }
        .overlay.active { display: block; }

        @media (max-width: 768px) {
            .container { flex-direction: column; padding: 10px; }
            .sidebar { transform: translateX(-100%); position: fixed; height: 100vh; top: 0; left: 0; width: 250px; }
            .sidebar.active { transform: translateX(0); }
            .main-content { padding-left: 0; }
            .profil-section { flex-direction: column; text-align: center; }
            .btn { width: 100%; justify-content: center; padding: 12px; margin: 5px 0; }
        }

        /* ==== TAMBAHAN: tampilkan sidebar normal di laptop ==== */
        @media (min-width: 769px) {
            .sidebar.mobile-hidden {
                transform: none !important;
                position: relative !important;
                height: auto !important;
            }
            .overlay { display: none !important; }
        }
    </style>
</head>
<body>
    <header>
        <h1>🛠️ Dashboard Admin</h1>
        <p>Selamat datang, Admin. Kelola koleksi parfum premium LuxPerfume.</p>
        <button class="burger" onclick="toggleSidebar()">☰</button>
    </header>

    <div class="overlay" onclick="toggleSidebar()"></div>

    <div class="container">
        <!-- SIDEBAR -->
        <nav class="sidebar mobile-hidden" id="sidebar">
            <ul>
                <li><a href="upload_produk.php" class="nav-link">➕ Upload Produk Baru</a></li>
                <li><a href="etalase_kasir.php" class="nav-link">🛍️ Etalase / Mesin Kasir</a></li>
                <li><a href="kelola_stok.php" class="nav-link">⚙️ Update Stok & Hapus Produk</a></li>
                <li><a href="data_penjualan.php" class="nav-link">📊 Data Penjualan</a></li>
                <li><a href="logout.php" class="nav-link logout">🚪 Logout</a></li>
            </ul>
        </nav>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <div class="section">
                <h3>👤 Profil Admin</h3>
                <div class="profil-section">
                    <div class="profil-avatar">A</div>
                    <div class="profil-info">
                        <p><strong>Nama:</strong> <?= htmlspecialchars($_SESSION['username'] ?? 'Admin'); ?></p>
                        <p><strong>Role:</strong> Admin</p>
                        <p><strong>Terakhir Login:</strong> <?= date('Y-m-d H:i:s'); ?></p>
                        <a href="edit_profil.php" class="btn">Edit Profil</a>
                    </div>
                </div>

                <?php
                if (!empty($_SESSION['map_embed'])) {
                    echo '<div style="margin-top:20px;border-radius:10px;overflow:hidden;">';
                    echo $_SESSION['map_embed'];
                    echo '</div>';
                }
                ?>
            </div>

            <div class="section">
                <h3>📋 Menu Utama</h3>
                <p style="text-align: center; font-size: 1.1rem; color: #ccc;">
                    Pilih menu di sidebar kiri untuk mengelola toko. Di mobile, klik ikon ☰ untuk membuka menu.
                </p>
            </div>
        </main>
    </div>

    <script>
        function toggleSidebar() {
            const sidebar = document.getElementById('sidebar');
            const overlay = document.querySelector('.overlay');
            sidebar.classList.toggle('active');
            overlay.classList.toggle('active');
        }
    </script>
</body>
</html>

<?php $conn->close(); ?>
