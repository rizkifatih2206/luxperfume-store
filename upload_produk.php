<?php
session_start();
include "koneksi.php";

// Hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

// === PROSES TAMBAH PRODUK ===
if (isset($_POST['submit'])) {
    $nama = mysqli_real_escape_string($conn, $_POST['nama']);
    $brand = mysqli_real_escape_string($conn, $_POST['brand']);
    $deskripsi = mysqli_real_escape_string($conn, $_POST['deskripsi']);
    $harga_min = (int)$_POST['harga_min'];
    $harga_max = (int)$_POST['harga_max'];
    $stok = (int)$_POST['stok'];
    $gambar = mysqli_real_escape_string($conn, $_POST['gambar']);
    $harga_beli = (int)$_POST['harga_beli'];

    $stmt = $conn->prepare("INSERT INTO parfum (brand, nama, deskripsi, harga_min, harga_max, harga_beli, jumlah_stok, gambar) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("sssiiiis", $brand, $nama, $deskripsi, $harga_min, $harga_max, $harga_beli, $stok, $gambar);

    if ($stmt->execute()) {
        echo "<script>alert('✅ Parfum baru berhasil ditambahkan!'); window.location='dashboard_admin.php';</script>";
    } else {
        echo "<script>alert('❌ Gagal menambah parfum: " . $stmt->error . "'); window.location='upload_produk.php';</script>";
    }
    $stmt->close();
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Produk - LuxPerfume Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">

    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            background: #000;
            color: #fff;
            line-height: 1.6;
        }

        header {
            background: linear-gradient(135deg, #000 0%, #ffd700 100%);
            color: #fff;
            text-align: center;
            padding: 30px 10px;
            box-shadow: 0 4px 10px rgba(255, 215, 0, 0.3);
        }
        header h1 {
            font-family: 'Playfair Display', serif;
            font-size: 2rem;
            margin-bottom: 8px;
        }
        header p {
            opacity: 0.9;
            font-size: 1rem;
        }

        .back-link {
            display: inline-block;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            padding: 10px 18px;
            border-radius: 10px;
            text-decoration: none;
            font-weight: bold;
            margin-top: 15px;
            transition: 0.3s;
        }
        .back-link:hover {
            box-shadow: 0 0 10px #ffd700;
            transform: scale(1.05);
        }

        .container {
            max-width: 700px;
            margin: 40px auto;
            background: rgba(30, 30, 30, 0.95);
            padding: 30px;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(255, 215, 0, 0.2);
        }

        .section h2 {
            font-family: 'Playfair Display', serif;
            color: #ffd700;
            text-align: center;
            margin-bottom: 25px;
            font-size: 1.8rem;
            border-bottom: 2px solid #ffd700;
            padding-bottom: 8px;
        }

        form { display: flex; flex-direction: column; gap: 15px; }

        label {
            font-weight: bold;
            color: #ffd700;
            margin-bottom: 5px;
            display: block;
        }

        input, textarea {
            width: 100%;
            padding: 10px;
            border: none;
            border-radius: 10px;
            font-size: 1rem;
            background: #222;
            color: #fff;
        }

        input:focus, textarea:focus {
            outline: none;
            box-shadow: 0 0 8px #ffd700;
        }

        textarea { resize: vertical; min-height: 80px; }

        button {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            border: none;
            padding: 12px;
            border-radius: 10px;
            font-size: 1rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 0 15px #ffd700;
        }

        @media (max-width: 768px) {
            .container { margin: 20px; padding: 20px; }
            header h1 { font-size: 1.6rem; }
        }

        /* === TAMBAHAN: RESPONSIF LEBIH LENGKAP === */
        @media (max-width: 600px) {
            header {
                padding: 20px 10px;
            }
            .container {
                width: 95%;
                padding: 18px;
                margin: 15px auto;
            }
            label {
                font-size: 0.9rem;
            }
            input, textarea {
                font-size: 0.9rem;
                padding: 9px;
            }
            button {
                padding: 10px;
                font-size: 0.95rem;
            }
            .section h2 {
                font-size: 1.5rem;
            }
        }

        @media (max-width: 400px) {
            header h1 {
                font-size: 1.3rem;
            }
            header p {
                font-size: 0.85rem;
            }
            .back-link {
                padding: 8px 14px;
                font-size: 0.9rem;
            }
        }

        /* Animasi halus saat form muncul */
        .container {
            animation: fadeIn 0.8s ease-in-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <header>
        <h1>➕ Upload Produk Baru</h1>
        <p>Tambahkan parfum baru untuk ditampilkan di etalase LuxPerfume.</p>
        <a href="dashboard_admin.php" class="back-link">← Kembali ke Dashboard</a>
    </header>

    <div class="container">
        <div class="section">
            <h2>🧴 Formulir Tambah Parfum</h2>

            <form method="post">
                <div class="form-group">
                    <label for="brand">Brand</label>
                    <input type="text" id="brand" name="brand" placeholder="Contoh: Dior, Chanel, Zara" required>
                </div>

                <div class="form-group">
                    <label for="nama">Nama Produk</label>
                    <input type="text" id="nama" name="nama" placeholder="Contoh: Sauvage Eau de Parfum" required>
                </div>

                <div class="form-group">
                    <label for="deskripsi">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" placeholder="Deskripsikan aroma dan karakter parfum..." required></textarea>
                </div>

                <div class="form-group">
                    <label for="harga_min">Harga Minimum (Rp)</label>
                    <input type="number" id="harga_min" name="harga_min" min="0" required>
                </div>

                <div class="form-group">
                    <label for="harga_max">Harga Maksimum (Rp)</label>
                    <input type="number" id="harga_max" name="harga_max" min="0" required>
                </div>

                <div class="form-group">
                    <label for="harga_beli">Harga Beli (Rp)</label>
                    <input type="number" id="harga_beli" name="harga_beli" min="0" required>
                </div>

                <div class="form-group">
                    <label for="stok">Jumlah Stok</label>
                    <input type="number" id="stok" name="stok" min="0" required>
                </div>

                <div class="form-group">
                    <label for="gambar">Nama File Gambar (dalam folder /assets)</label>
                    <input type="text" id="gambar" name="gambar" placeholder="Contoh: sauvage.jpg" required>
                </div>

                <button type="submit" name="submit">💾 Tambah Parfum</button>
            </form>
        </div>
    </div>
</body>
</html>

<?php $conn->close(); ?>
