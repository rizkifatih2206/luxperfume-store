<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // Cek apakah username sudah dipakai
    $cek = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    if (mysqli_num_rows($cek) > 0) {
        echo "<script>alert('Username sudah digunakan!'); window.location='register.php';</script>";
        exit;
    }

    // Simpan akun baru pembeli
    $query = "INSERT INTO users (username, password, role) VALUES ('$username', '$password', 'pembeli')";
    if (mysqli_query($conn, $query)) {
        echo "<script>alert('Registrasi berhasil! Silakan login.'); window.location='login.php';</script>";
    } else {
        echo "<script>alert('Gagal registrasi.');</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Register Pembeli</title>
    <style>
        body {
            font-family: Arial, sans-serif; 
            background-color: #f8f9fa; 
            text-align: center; 
            padding-top: 100px;
        }
        form {
            display: inline-block; 
            padding: 25px; 
            background: white; 
            border-radius: 10px; 
            box-shadow: 0 3px 10px rgba(0,0,0,0.2);
        }
        input {
            margin: 8px 0; 
            padding: 10px; 
            width: 220px; 
            border-radius: 5px; 
            border: 1px solid #ccc;
        }
        button {
            padding: 10px 15px; 
            background-color: #8e44ad; 
            color: white; 
            border: none; 
            border-radius: 5px; 
            cursor: pointer;
            font-weight: bold;
        }
        button:hover {
            background-color: #732d91;
        }
        a {
            text-decoration: none; 
            color: #8e44ad; 
            display: block; 
            margin-top: 10px;
        }
    </style>
</head>
<body>
<h2>Register Pembeli</h2>
<form method="POST" action="">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Daftar</button>
    <a href="login.php">Sudah punya akun? Login</a>
</form>
</body>
</html>
