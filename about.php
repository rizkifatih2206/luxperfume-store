<?php
session_start();
include "koneksi.php";
if (!isset($_SESSION['role']) || $_SESSION['role'] != "pembeli") {
    header("Location: index.php");
    exit;
}

$stmt = $conn->prepare("SELECT * FROM profil_toko LIMIT 1");
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();
$stmt->close();
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Tentang Kami</title>
<style>
body{font-family:Poppins,Arial,sans-serif;background:#0b0b0d;color:#eee;margin:0}
section{padding:40px 20px;text-align:center;}
h2{color:#ffd700;font-family:Merriweather,serif;margin-bottom:20px;}
iframe{width:90%;height:400px;border-radius:14px;border:none;margin-top:20px;}
.contact{margin-top:30px;}
.contact p{font-size:16px;color:#bbb;}
</style>
</head>
<body>

<?php include "header_pembeli.php"; ?>

<section>
  <h2><?= htmlspecialchars($profil['nama_perusahaan'] ?? 'Tentang Kami'); ?></h2>
  <p><?= nl2br(htmlspecialchars($profil['deskripsi'] ?? '')); ?></p>

  <?php if(!empty($profil['lokasi'])): ?>
    <iframe src="<?= htmlspecialchars($profil['lokasi']); ?>" allowfullscreen></iframe>
  <?php endif; ?>

  <div class="contact">
    <p><b>Alamat:</b> <?= htmlspecialchars($profil['alamat'] ?? 'Belum diisi'); ?></p>
    <p><b>Kontak:</b> <?= htmlspecialchars($profil['kontak'] ?? '-'); ?></p>
    <p><b>Email:</b> <?= htmlspecialchars($profil['email'] ?? '-'); ?></p>
  </div>
</section>

</body>
</html>
