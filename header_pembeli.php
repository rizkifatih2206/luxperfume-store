<?php
if (session_status() === PHP_SESSION_NONE) session_start();
?>
<nav>
  <div class="brand">
    <div class="logo">LP</div>
    <div>
      <h1>LuxPerfume</h1>
      <div style="font-size:12px;color:var(--muted); margin-top:2px;">Etalase Parfum Premium</div>
    </div>
  </div>

  <div class="nav-links">
    <a href="dashboard_pembeli.php" <?= basename($_SERVER['PHP_SELF'])=='dashboard_pembeli.php'?'style="color:#fff"':''; ?>>Home</a>
    <a href="stok.php" <?= basename($_SERVER['PHP_SELF'])=='stok.php'?'style="color:#fff"':''; ?>>Products</a>
    <a href="about.php" <?= basename($_SERVER['PHP_SELF'])=='about.php'?'style="color:#fff"':''; ?>>About</a>
    <a href="logout.php">Logout</a>
  </div>

  <div class="header-right">
    <div style="font-size:14px;color:var(--muted);">ID Member:
      <strong style="color:#fff; margin-left:6px;"><?= htmlspecialchars($_SESSION['member_id']); ?></strong>
    </div>
    <div id="cartBtn" style="background:var(--gold); color:#000; padding:8px 12px; border-radius:10px; font-weight:800; cursor:pointer;">
      🛒 (<?= isset($_SESSION['cart']) ? array_sum(array_column($_SESSION['cart'],'qty')) : 0; ?>)
    </div>
  </div>
</nav>
