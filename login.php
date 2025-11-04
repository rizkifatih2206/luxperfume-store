<?php
session_start();
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // 🔹 Cek jika password admin khusus
    if ($password === 'adminperfume789') {
        $_SESSION['username'] = 'Admin';
        $_SESSION['role'] = 'admin';
        header("Location: admin.php");
        exit;
    }

    // 🔹 Cek dari database pembeli
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username' LIMIT 1");
    $user = mysqli_fetch_assoc($result);

    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = 'pembeli';
        header("Location: stok.php");
        exit;
    } else {
        echo "<script>alert('Username atau password salah!'); window.location='login.php';</script>";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Login</title>
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
<h2>Login</h2>
<form method="POST" action="">
    <input type="text" name="username" placeholder="Username" required><br>
    <input type="password" name="password" placeholder="Password" required><br><br>
    <button type="submit">Login</button>
    <a href="register.php">Belum punya akun? Daftar di sini</a>
</form>
</body>
</html>
