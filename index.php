<?php
include "koneksi.php";
session_start();

function generateMemberID() {
    return strtoupper(substr(md5(uniqid()), 0, 6));
}

// ===================== REGISTER PEMBELI =====================
if (isset($_POST['register'])) {
    $nama = $_POST['nama_lengkap'];
    $username = $_POST['username'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $member_id = generateMemberID();

    $sql = "INSERT INTO users (username, password, role, member_id, nama_lengkap)
            VALUES ('$username', '$password', 'pembeli', '$member_id', '$nama')";
    mysqli_query($conn, $sql);
    echo "<script>alert('Registrasi berhasil! Silakan login.');</script>";
}

// ===================== LOGIN =====================
if (isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // === LOGIN ADMIN ===
    if ($username === 'admin' && $password === 'adminperfume789') {
        $_SESSION['role'] = 'admin';
        $_SESSION['username'] = 'Admin';
        header("Location: dashboard_admin.php");
        exit;
    }

    // === LOGIN PEMBELI ===
    $result = mysqli_query($conn, "SELECT * FROM users WHERE username='$username'");
    $row = mysqli_fetch_assoc($result);

    if ($row) {
        if (password_verify($password, $row['password'])) {
            $_SESSION['role'] = 'pembeli';
            $_SESSION['username'] = $row['username'];
            $_SESSION['member_id'] = $row['member_id'];
            $_SESSION['nama_lengkap'] = $row['nama_lengkap'];
            $_SESSION['login'] = true;
            header("Location: dashboard_pembeli.php");
            exit;
        } else {
            echo "<script>alert('Password salah!');</script>";
        }
    } else {
        echo "<script>alert('Username tidak ditemukan!');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LuxPerfume - Premium Fragrances</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Roboto', sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #000 0%, #333 100%);
            display: flex; justify-content: center; align-items: center;
            min-height: 100vh; position: relative; overflow: hidden;
        }
        body::before {
            content: '';
            position: absolute;
            top: 0; left: 0; width: 100%; height: 100%;
            background: url('https://source.unsplash.com/1920x1080/?perfume,luxury,dark') no-repeat center/cover;
            z-index: -1; opacity: 0.3;
        }
        h1,h2 { font-family: 'Playfair Display', serif; }

        .container {
            background: rgba(255, 255, 255, 0.95);
            width: 400px; max-width: 90%;
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.3);
            overflow: hidden;
            animation: fadeInUp 1s ease-out;
            backdrop-filter: blur(10px);
        }
        @keyframes fadeInUp {
            from { opacity: 0; transform: translateY(50px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .hero { text-align: center; padding: 30px 20px; background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color:#000; }
        .hero h1 { font-size: 2.2rem; margin-bottom: 10px; font-weight: 700; }
        .hero p { font-size: 1rem; color:#333; font-weight: 300; }

        .tab { display: flex; background: #f8f9fa; }
        .tab button {
            flex: 1; padding: 15px; border: none; cursor: pointer;
            background: transparent; font-weight: 500; font-size: 16px; color:#666;
            transition: all 0.3s ease; position: relative;
        }
        .tab button::after {
            content: ''; position: absolute; bottom:0; left:0; width:0; height:3px; background:#ffd700; transition: width 0.3s ease;
        }
        .tab button.active { color:#ffd700; font-weight:bold; }
        .tab button.active::after { width:100%; }
        .tab button:hover { color:#ffd700; background: rgba(255,215,0,0.1); }

        .form-section { display:none; padding:30px 25px; animation: fadeIn 0.5s ease; }
        .form-section.active { display:block; }
        @keyframes fadeIn { from {opacity:0;} to {opacity:1;} }

        input {
            width:100%; padding:15px; margin:12px 0; border:2px solid #e0e0e0; border-radius:10px;
            font-size:16px; transition: all 0.3s ease; background:#fff;
        }
        input:focus { outline:none; border-color:#ffd700; box-shadow:0 0 0 3px rgba(255,215,0,0.1); }
        input::placeholder { color:#999; }

        .password-wrapper { position:relative; margin:12px 0; }
        .password-wrapper input { padding-right:45px; }
        .toggle-password { position:absolute; right:15px; top:50%; transform:translateY(-50%); cursor:pointer; user-select:none; font-size:18px; color:#999; transition: color 0.3s ease; }
        .toggle-password:hover { color:#ffd700; }

        button[type="submit"] {
            width:100%; padding:15px; margin-top:20px;
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color:#000; font-weight:bold; font-size:16px;
            border:none; border-radius:10px; cursor:pointer; transition: all 0.3s ease;
        }
        button[type="submit"]:hover { transform: translateY(-2px); box-shadow:0 10px 20px rgba(255,215,0,0.3); background:linear-gradient(135deg, #ffed4e 0%, #ffd700 100%); }
        button[type="submit"]:active { transform: translateY(0); }

        @media (max-width:480px) {
            .container { margin:20px; border-radius:15px; }
            .hero h1 { font-size:1.8rem; }
            .hero p { font-size:0.9rem; }
            .tab button { padding:12px; font-size:14px; }
            input, button[type="submit"] { padding:12px; font-size:14px; }
            .form-section { padding:25px 20px; }
        }

        /* ====== Tambahan Fungsi Responsif ====== */
        @media (max-width:768px) {
            body { flex-direction: column; padding: 20px; }
            .container { width: 90%; }
            .hero h1 { font-size: 2rem; }
        }

        @media (max-width:360px) {
            .hero h1 { font-size:1.5rem; }
            .hero p { font-size:0.8rem; }
            input, button[type="submit"] { font-size:13px; }
            .tab button { font-size:13px; }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="hero">
            <h1>LuxPerfume</h1>
            <p>Discover premium fragrances. Login or register for an exquisite shopping experience.</p>
        </div>
        
        <div class="tab" role="tablist">
            <button id="loginTab" class="active" onclick="showForm('login')" role="tab" aria-selected="true">Login</button>
            <button id="registerTab" onclick="showForm('register')" role="tab" aria-selected="false">Register</button>
        </div>

        <!-- LOGIN FORM -->
        <div id="loginForm" class="form-section active" role="tabpanel">
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required autocomplete="username">
                <div class="password-wrapper">
                    <input type="password" id="loginPassword" name="password" placeholder="Password" required autocomplete="current-password">
                    <span class="toggle-password" onclick="togglePassword('loginPassword', this)">👁</span>
                </div>
                <button type="submit" name="login">Sign In</button>
            </form>
        </div>

        <!-- REGISTER FORM -->
        <div id="registerForm" class="form-section" role="tabpanel">
            <form method="POST">
                <input type="text" name="nama_lengkap" placeholder="Full Name" required autocomplete="name">
                <input type="text" name="username" placeholder="Username" required autocomplete="username">
                <div class="password-wrapper">
                    <input type="password" id="registerPassword" name="password" placeholder="Password" required autocomplete="new-password">
                    <span class="toggle-password" onclick="togglePassword('registerPassword', this)">👁</span>
                </div>
                <button type="submit" name="register">Create Account</button>
            </form>
        </div>
    </div>

    <script>
        function showForm(form) {
            document.getElementById('loginForm').classList.remove('active');
            document.getElementById('registerForm').classList.remove('active');
            document.getElementById('loginTab').classList.remove('active');
            document.getElementById('registerTab').classList.remove('active');

            if (form === 'login') {
                document.getElementById('loginForm').classList.add('active');
                document.getElementById('loginTab').classList.add('active');
            } else {
                document.getElementById('registerForm').classList.add('active');
                document.getElementById('registerTab').classList.add('active');
            }
        }

        function togglePassword(inputId, icon) {
            let input = document.getElementById(inputId);
            if (input.type === "password") {
                input.type = "text"; icon.textContent = "🙈";
            } else {
                input.type = "password"; icon.textContent = "👁";
            }
        }

        // ====== Tambahan Responsif Dinamis (orientasi dan tinggi layar) ======
        window.addEventListener("resize", function() {
            document.body.style.minHeight = window.innerHeight + "px";
        });
    </script>
</body>
</html>
