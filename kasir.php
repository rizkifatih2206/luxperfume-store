<?php
session_start();
include "koneksi.php";
if (!isset($_SESSION['role']) || $_SESSION['role']!='admin'){ header("Location:index.php"); exit; }

$msg = '';
if(isset($_POST['parfum_id'], $_POST['qty'])){
  $pid = (int)$_POST['parfum_id'];
  $qty = max(1,(int)$_POST['qty']);
  $member = trim($_POST['member_id']);
  $p = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM parfum WHERE id=$pid"));
  if(!$p){ $msg="Produk tidak valid."; }
  else if($qty > (int)$p['stok']){ $msg="Stok tidak mencukupi."; }
  else {
    // Cari user_id dari member_id (optional)
    $u = mysqli_fetch_assoc(mysqli_query($conn, "SELECT id, nama_lengkap FROM users WHERE member_id='".mysqli_real_escape_string($conn,$member)."' LIMIT 1"));
    $user_id = $u ? (int)$u['id'] : null;
    $nama = $u ? $u['nama_lengkap'] : 'Non-member';

    $total = $qty * (int)$p['harga_min'];
    mysqli_query($conn, "INSERT INTO orders(user_id, member_id, parfum_id, qty, total, channel)
                         VALUES (".($user_id?:'NULL').", ".($member?"'".mysqli_real_escape_string($conn,$member)."'":"NULL").", $pid, $qty, $total, 'offline')");
    mysqli_query($conn, "UPDATE parfum SET stok = stok - $qty WHERE id=$pid");
    $msg = "Transaksi berhasil. Pelanggan: <b>".htmlspecialchars($nama)."</b> • Total: <b>Rp ".number_format($total,0,',','.')."</b>";
  }
}

$produk = mysqli_query($conn, "SELECT id,brand,nama,stok FROM parfum ORDER BY brand,nama");
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8"><title>Kasir Offline</title>
<style>
  body{font-family:Arial;background:#f6f6f7;margin:0}
  .box{max-width:720px;margin:40px auto;background:#fff;border-radius:16px;box-shadow:0 8px 24px rgba(0,0,0,.08);padding:20px}
  h1{margin:0 0 8px}
  label{font-weight:700}
  .row{display:grid;grid-template-columns:1fr 1fr;gap:14px}
  input,select{padding:10px;border:1px solid #ddd;border-radius:10px;width:100%}
  .btn{background:#ff4ec4;color:#fff;border:none;padding:12px 16px;border-radius:10px;font-weight:700;cursor:pointer;margin-top:10px}
  .btn:hover{background:#d43ca5}
  .muted{color:#666}
  .msg{margin-top:12px}
  .link{display:inline-block;margin-top:12px;color:#ff4ec4;text-decoration:none;font-weight:700}
</style>
</head>
<body>
  <div class="box">
    <h1>Kasir (Offline) — Admin</h1>
    <p class="muted">Masukkan ID Member (opsional). Jika valid, nama member akan terdeteksi saat transaksi diproses.</p>
    <form method="post">
      <div class="row">
        <div>
          <label>ID Member (opsional)</label>
          <input type="text" name="member_id" placeholder="contoh: AB12CD">
        </div>
        <div>
          <label>Produk</label>
          <select name="parfum_id" required>
            <option value="">— pilih parfum —</option>
            <?php while($p=mysqli_fetch_assoc($produk)): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['brand'].' — '.$p['nama'].' (Stok: '.$p['stok'].')') ?></option>
            <?php endwhile; ?>
          </select>
        </div>
      </div>
      <div style="margin-top:10px">
        <label>Qty</label>
        <input type="number" name="qty" min="1" value="1" required>
      </div>
      <button class="btn" type="submit">Proses</button>
    </form>
    <?php if($msg): ?><div class="msg"><?= $msg ?></div><?php endif; ?>
    <a class="link" href="catalog.php">← Kembali ke Katalog</a>
  </div>
</body>
</html>
