<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] != "pembeli") {
    header("Location: index.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Data Member</title>
</head>
<body>
    <h1>Data Member</h1>
    <p>Nama Lengkap: <?php echo $_SESSION['nama_lengkap']; ?></p>
    <p>ID Member: <?php echo $_SESSION['member_id']; ?></p>
    <p>Username: <?php echo $_SESSION['username']; ?></p>
    <br>
    <a href="dashboard_pembeli.php">⬅ Kembali</a>
</body>
</html>
