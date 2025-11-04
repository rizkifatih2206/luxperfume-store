<?php
include "koneksi.php";

$hasil = null;
if (isset($_GET['cari'])) {
    $id_member = $_GET['id_member'];
    $q = mysqli_query($conn, "SELECT * FROM users WHERE member_id='$id_member'");
    $hasil = mysqli_fetch_assoc($q);
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Cari Member</title>
</head>
<body>
    <h2>Cari Member Berdasarkan ID</h2>
    <form method="GET">
        <input type="text" name="id_member" placeholder="Masukkan ID Member" required>
        <button type="submit" name="cari">Cari</button>
    </form>

    <?php if ($hasil) { ?>
        <h3>Nama Member: <?php echo $hasil['nama_lengkap']; ?></h3>
    <?php } elseif (isset($_GET['cari'])) { ?>
        <p>Member tidak ditemukan.</p>
    <?php } ?>
    <br>
    <a href="dashboard_admin.php">⬅ Kembali</a>
</body>
</html>
