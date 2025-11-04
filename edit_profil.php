<?php
session_start();
include "koneksi.php";

// Pastikan hanya admin yang bisa akses
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: index.php");
    exit;
}

// Ambil data profil admin saat ini
$stmt = $conn->prepare("SELECT * FROM users WHERE role='admin' LIMIT 1");
$stmt->execute();
$profil = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Jika form disubmit
if (isset($_POST['simpan'])) {
    $nama_perusahaan = $_POST['nama_perusahaan'];
    $contact_person   = $_POST['contact_person'];
    $alamat           = $_POST['alamat'];
    $telepon          = $_POST['telepon'];
    $email            = $_POST['email'];
    $maps             = $_POST['maps'];
    $logo_name        = $profil['logo']; // default logo lama

    // Jika ada file diupload
    if (!empty($_FILES['logo']['name'])) {
        $target_dir = "uploads/";
        $file_name = time() . "_" . basename($_FILES["logo"]["name"]);
        $target_file = $target_dir . $file_name;

        // Pastikan folder uploads ada
        if (!is_dir($target_dir)) mkdir($target_dir, 0777, true);

        if (move_uploaded_file($_FILES["logo"]["tmp_name"], $target_file)) {
            $logo_name = $file_name;
        }
    }

    // Update data admin
    $update = $conn->prepare("
        UPDATE users 
        SET nama_perusahaan=?, contact_person=?, alamat=?, telepon=?, email=?, maps=?, logo=?
        WHERE role='admin'
    ");
    $update->bind_param("sssssss", $nama_perusahaan, $contact_person, $alamat, $telepon, $email, $maps, $logo_name);
    $update->execute();
    $update->close();

    echo "<script>alert('Profil berhasil diperbarui!'); window.location='dashboard_admin.php';</script>";
    exit;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<title>Edit Profil Perusahaan</title>
<style>
body {
  font-family: 'Poppins', sans-serif;
  background-color: #0e0e10;
  color: white;
  margin: 0;
}
.container {
  width: 60%;
  margin: 50px auto;
  background: #1a1a1a;
  padding: 30px 40px;
  border-radius: 12px;
  border: 2px solid #FFD700;
  box-shadow: 0 0 20px rgba(255,215,0,0.15);
}
h2 {
  text-align: center;
  color: #FFD700;
  margin-bottom: 30px;
}
form label {
  display: block;
  font-weight: 600;
  margin-top: 15px;
  color: #FFD700;
}
form input[type="text"],
form input[type="email"],
form textarea {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  border-radius: 8px;
  border: none;
  outline: none;
  background: #111;
  color: white;
}
form input[type="file"] {
  margin-top: 10px;
  color: #FFD700;
}
button {
  margin-top: 25px;
  background: #FFD700;
  border: none;
  color: #000;
  padding: 12px 30px;
  border-radius: 8px;
  cursor: pointer;
  font-weight: 700;
  transition: 0.3s;
}
button:hover {
  background: #e5c400;
}
.logo-preview {
  text-align: center;
  margin-top: 15px;
}
.logo-preview img {
  width: 120px;
  height: 120px;
  object-fit: cover;
  border-radius: 15px;
  border: 3px solid #FFD700;
}
a.kembali {
  display: inline-block;
  margin-top: 20px;
  color: #FFD700;
  text-decoration: none;
  font-weight: 600;
}
a.kembali:hover {
  text-decoration: underline;
}
</style>
</head>
<body>

<div class="container">
  <h2>Edit Profil Perusahaan</h2>
  <form method="POST" enctype="multipart/form-data">
    <label>Nama Perusahaan:</label>
    <input type="text" name="nama_perusahaan" value="<?= htmlspecialchars($profil['nama_perusahaan'] ?? '') ?>" required>

    <label>Contact Person:</label>
    <input type="text" name="contact_person" value="<?= htmlspecialchars($profil['contact_person'] ?? '') ?>">

    <label>Alamat:</label>
    <textarea name="alamat" rows="3"><?= htmlspecialchars($profil['alamat'] ?? '') ?></textarea>

    <label>Telepon:</label>
    <input type="text" name="telepon" value="<?= htmlspecialchars($profil['telepon'] ?? '') ?>">

    <label>Email:</label>
    <input type="email" name="email" value="<?= htmlspecialchars($profil['email'] ?? '') ?>">

    <label>Maps (Google Maps Embed URL):</label>
    <textarea name="maps" rows="2"><?= htmlspecialchars($profil['maps'] ?? '') ?></textarea>

    <label>Logo Perusahaan:</label>
    <input type="file" name="logo" accept="image/*">
    <div class="logo-preview">
      <?php if (!empty($profil['logo'])): ?>
        <img src="uploads/<?= htmlspecialchars($profil['logo']); ?>" alt="Logo Saat Ini">
      <?php else: ?>
        <p style="color:#888;">Belum ada logo diunggah.</p>
      <?php endif; ?>
    </div>

    <center>
      <button type="submit" name="simpan">Simpan Perubahan</button>
    </center>
  </form>

  <center><a href="dashboard_admin.php" class="kembali">← Kembali ke Dashboard</a></center>
</div>

</body>
</html>
