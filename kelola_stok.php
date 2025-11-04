<?php
session_start();
include "koneksi.php"; // pastikan $conn tersedia dan valid

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

// helper: escape output
function h($v){ return htmlspecialchars($v, ENT_QUOTES, 'UTF-8'); }

$notice = '';

// ===== Proses Update Stok =====
if (isset($_POST['update_stok'])) {
    $id = (int)($_POST['id'] ?? 0);
    $stok_baru = max(0, (int)($_POST['stok'] ?? 0));

    $stmt = $conn->prepare("UPDATE parfum SET jumlah_stok = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("ii", $stok_baru, $id);
        if ($stmt->execute()) {
            $notice = "Stok parfum berhasil diperbarui.";
        } else {
            $notice = "Gagal memperbarui stok: " . $conn->error;
        }
        $stmt->close();
    }
}

// ===== Proses Update Deskripsi =====
if (isset($_POST['update_deskripsi'])) {
    $id = (int)($_POST['id'] ?? 0);
    $deskripsi = trim($_POST['deskripsi'] ?? '');

    $stmt = $conn->prepare("UPDATE parfum SET deskripsi = ? WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("si", $deskripsi, $id);
        if ($stmt->execute()) {
            $notice = "Deskripsi parfum berhasil diperbarui.";
        } else {
            $notice = "Gagal memperbarui deskripsi: " . $conn->error;
        }
        $stmt->close();
    }
}

// ===== Proses Hapus Parfum =====
if (isset($_POST['delete_parfum'])) {
    $id = (int)($_POST['id'] ?? 0);
    $conn->begin_transaction();
    $stmt = $conn->prepare("DELETE FROM parfum WHERE id = ?");
    if ($stmt) {
        $stmt->bind_param("i", $id);
        if ($stmt->execute()) {
            $conn->commit();
            $notice = "Parfum berhasil dihapus.";
        } else {
            $errno = $conn->errno;
            $error = $conn->error;
            $conn->rollback();
            if ($errno == 1451) {
                $stmt2 = $conn->prepare("UPDATE parfum SET jumlah_stok = 0 WHERE id = ?");
                if ($stmt2) {
                    $stmt2->bind_param("i", $id);
                    $stmt2->execute();
                    $stmt2->close();
                    $notice = "Produk terkait transaksi. Stok diset ke 0 agar tersembunyi.";
                } else {
                    $notice = "Produk tidak dapat dihapus dan gagal set stok ke 0: " . $conn->error;
                }
            } else {
                $notice = "Gagal menghapus produk: " . $error;
            }
        }
        $stmt->close();
    }
}

$produk = $conn->query("SELECT * FROM parfum ORDER BY id DESC");
if (!$produk) {
    die("Gagal mengambil data parfum: " . $conn->error);
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>Kelola Stok & Hapus Produk - Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
<style>
    :root{
        --gold1: #ffd700;
        --gold2: #ffed4e;
        --purple: #8e44ad;
        --danger: #ff4c4c;
    }
    *{box-sizing:border-box}
    body{font-family:Roboto, sans-serif;background:linear-gradient(135deg,#f6f6f8 0%, #e9e7ef 100%);margin:0;padding:0;color:#222}
    header{
        background:linear-gradient(135deg,#000 0%,var(--gold1) 100%);
        color:#fff;padding:22px;text-align:center;position:sticky;top:0;z-index:50;
        box-shadow:0 6px 18px rgba(0,0,0,0.12);
    }
    header h1{font-family:'Playfair Display',serif;margin:0;font-size:1.6rem}
    .container{max-width:1200px;margin:28px auto;padding:18px}
    .notice{background:#fff;padding:12px;border-radius:10px;margin-bottom:16px;box-shadow:0 6px 18px rgba(0,0,0,0.06);}
    .notice.success{border-left:6px solid #28a745}
    .top-actions{display:flex;gap:12px;flex-wrap:wrap;justify-content:center;margin-bottom:18px}
    .btn{background:linear-gradient(135deg,var(--gold1),var(--gold2));padding:10px 16px;border-radius:999px;text-decoration:none;color:#000;font-weight:700;box-shadow:0 6px 18px rgba(255,215,0,0.15)}
    .btn.logout{background:linear-gradient(135deg,#ff5a5a,#ff6b6b);color:#fff}
    .section{background:rgba(255,255,255,0.95);padding:18px;border-radius:14px;box-shadow:0 10px 30px rgba(0,0,0,0.06);margin-bottom:20px}
    .flex-wrap{display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:18px}
    .card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 6px 20px rgba(0,0,0,0.06);display:flex;flex-direction:column}
    .card img{width:100%;height:200px;object-fit:cover;background:#f2f2f2}
    .info{padding:14px;text-align:center;flex:1;display:flex;flex-direction:column}
    .info h4{font-family:'Playfair Display',serif;margin:6px 0;font-size:1.1rem}
    .brand{color:#666;margin-bottom:6px}
    .price{color:var(--gold1);font-weight:700;margin-bottom:8px}
    .stock{color:#444;margin-bottom:12px}
    textarea.deskripsi{width:100%;height:80px;padding:8px;border-radius:8px;border:1px solid #ddd;resize:vertical;margin-top:6px}
    button.admin-btn{padding:10px 12px;border-radius:10px;border:none;cursor:pointer;font-weight:700}
    .admin-btn.update{background:var(--purple);color:#fff}
    .admin-btn.delete{background:var(--danger);color:#fff}
    .small-muted{font-size:13px;color:#777;margin-top:10px}
</style>
</head>
<body>
<header>
    <h1>🛠️ Kelola Stok & Deskripsi Produk</h1>
</header>

<div class="container">

    <?php if ($notice): ?>
        <div class="notice success"><?= h($notice); ?></div>
    <?php endif; ?>

    <div class="top-actions">
        <a class="btn" href="dashboard_admin.php">← Kembali ke Dashboard</a>
        <a class="btn" href="stok.php">Lihat Etalase Pembeli</a>
        <a class="btn" href="etalase_kasir.php">Buka Etalase / Mesin Kasir</a>
    </div>

    <div class="section">
        <h2>⚙️ Kelola Parfum</h2>

        <?php if ($produk->num_rows == 0): ?>
            <div class="empty">Belum ada parfum. Tambahkan produk lewat Upload/Tambah Parfum.</div>
        <?php else: ?>
            <div class="flex-wrap">
                <?php while ($row = $produk->fetch_assoc()): 
                    $harga = (($row['harga_min'] ?? 0) + ($row['harga_max'] ?? 0)) / 2;
                ?>
                    <div class="card">
                        <img src="<?= h($row['gambar']); ?>" alt="<?= h($row['nama']); ?>" onerror="this.src='https://via.placeholder.com/600x400?text=No+Image'">
                        <div class="info">
                            <h4><?= h($row['nama']); ?></h4>
                            <div class="brand"><?= h($row['brand']); ?></div>
                            <div class="price">Rp <?= number_format($harga,0,',','.'); ?></div>
                            <div class="stock">Stok: <?= (int)$row['jumlah_stok']; ?></div>

                            <form method="post" class="inline">
                                <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                <input class="stok-input" type="number" name="stok" value="<?= (int)$row['jumlah_stok']; ?>" min="0" required>
                                <button type="submit" name="update_stok" class="admin-btn update">Update Stok</button>
                            </form>

                            <!-- Form edit deskripsi -->
                            <form method="post" style="margin-top:10px;">
                                <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                <textarea class="deskripsi" name="deskripsi" placeholder="TOP NOTES
MIDDLE NOTES
BASE NOTES"><?= h($row['deskripsi'] ?? ''); ?></textarea>
                                <button type="submit" name="update_deskripsi" class="admin-btn update">Update Deskripsi</button>
                            </form>

                            <form method="post" onsubmit="return confirm('Yakin ingin menghapus parfum ini?');" style="margin-top:10px;">
                                <input type="hidden" name="id" value="<?= (int)$row['id']; ?>">
                                <button type="submit" name="delete_parfum" class="admin-btn delete">Hapus</button>
                            </form>

                            <div class="small-muted">ID: <?= (int)$row['id']; ?></div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php endif; ?>
    </div>

</div>

</body>
</html>
<?php $conn->close(); ?>
