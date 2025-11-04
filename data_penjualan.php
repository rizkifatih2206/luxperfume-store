<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

// === Reset data transaksi_detail tiap Senin ===
$hari_ini = date('N'); // 1 = Senin
if ($hari_ini == 1) {
    $cek_reset = $conn->query("SELECT * FROM reset_log WHERE YEARWEEK(tanggal_reset, 1) = YEARWEEK(CURDATE(), 1)");
    if ($cek_reset->num_rows == 0) {
        $conn->query("INSERT INTO reset_log (tanggal_reset) VALUES (NOW())");
        $conn->query("DELETE FROM laporan_mingguan");
    }
}

// === Hitung penjualan & untung rugi 7 hari terakhir ===
function hitungUntungRugi($conn, $hari = 7) {
    $data = [];
    $total_untung = 0;
    $total_rugi = 0;

    $check_table = $conn->query("SHOW TABLES LIKE 'transaksi_detail'");
    if ($check_table->num_rows == 0) {
        for ($i = $hari - 1; $i >= 0; $i--) {
            $tanggal = date('Y-m-d', strtotime("-$i days"));
            $data[$tanggal] = ['penjualan' => 0, 'untung_rugi' => 0];
        }
        return ['data' => $data, 'total_untung' => 0, 'total_rugi' => 0, 'total_net' => 0];
    }

    for ($i = $hari - 1; $i >= 0; $i--) {
        $tanggal = date('Y-m-d', strtotime("-$i days"));
        // Ambil langsung dari transaksi_detail, tidak join parfum
        $query = "
            SELECT 
                SUM(subtotal) AS total_penjualan,
                SUM(harga * qty * 0.8) AS total_modal
            FROM transaksi_detail
            WHERE DATE(tanggal) = '$tanggal'
        ";

        $result = $conn->query($query);
        $row = $result ? $result->fetch_assoc() : ['total_penjualan' => 0, 'total_modal' => 0];

        $penjualan = (float)$row['total_penjualan'];
        $modal = (float)$row['total_modal'];
        $untung_rugi = $penjualan - $modal;

        $data[$tanggal] = [
            'penjualan' => $penjualan,
            'untung_rugi' => $untung_rugi
        ];

        if ($untung_rugi >= 0) $total_untung += $untung_rugi;
        else $total_rugi += abs($untung_rugi);
    }

    return [
        'data' => $data,
        'total_untung' => $total_untung,
        'total_rugi' => $total_rugi,
        'total_net' => $total_untung - $total_rugi
    ];
}

$laporan = hitungUntungRugi($conn);
$labels = array_keys($laporan['data']);
$penjualan_data = array_column($laporan['data'], 'penjualan');
$untung_rugi_data = array_column($laporan['data'], 'untung_rugi');

// === Ambil data detail transaksi terbaru ===
$transaksi_detail = $conn->query("
    SELECT *
    FROM transaksi_detail
    ORDER BY tanggal DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Data Penjualan - LuxPerfume Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<style>
body { font-family: 'Roboto', sans-serif; background: linear-gradient(135deg,#f9f9f9,#e0e0e0); color:#333; }
.container { max-width:1300px; margin:20px auto; padding:20px; }
header { background:linear-gradient(135deg,#000,rgba(255,215,0,0.8)); color:white; padding:25px; text-align:center; border-radius:0 0 20px 20px; }
header h1{font-family:'Playfair Display',serif; font-size:2.2rem;}
.summary-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:20px;margin:30px 0;}
.summary-card{background:white;padding:25px;border-radius:15px;text-align:center;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
.summary-card h4{color:#555;margin-bottom:8px;}
.summary-card.profit{border-top:5px solid #28a745;}
.summary-card.loss{border-top:5px solid #dc3545;}
.summary-card.net{border-top:5px solid #ffd700;}
.summary-card .value{font-size:1.8rem;font-weight:bold;}
.chart-container{background:white;padding:20px;border-radius:15px;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
.laporan-table{width:100%;border-collapse:collapse;margin-top:25px;background:white;border-radius:10px;overflow:hidden;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
.laporan-table th,.laporan-table td{padding:14px;border-bottom:1px solid #eee;text-align:left;}
.laporan-table th{background:#ffd700;color:#000;}
.laporan-table .profit{color:#28a745;font-weight:bold;}
.laporan-table .loss{color:#dc3545;font-weight:bold;}
button{margin-top:20px;padding:10px 25px;background:linear-gradient(135deg,#28a745,#20c997);color:white;border:none;border-radius:8px;cursor:pointer;font-weight:bold;}
button:hover{opacity:0.9;}
.no-data{text-align:center;padding:40px;color:#777;}
.detail-section{margin-top:40px;}
.detail-section h2{background:#ffd700;color:#000;padding:12px 20px;border-radius:10px 10px 0 0;font-family:'Playfair Display',serif;}
.detail-table{width:100%;border-collapse:collapse;background:white;box-shadow:0 5px 20px rgba(0,0,0,0.1);}
.detail-table th,.detail-table td{padding:12px;border-bottom:1px solid #eee;text-align:left;}
.detail-table th{background:#000;color:#fff;}
.detail-table tr:hover{background:#f9f9f9;}

/* === TAMBAHAN: RESPONSIVE DESIGN === */
@media (max-width: 992px) {
    header h1 { font-size: 1.8rem; }
    .container { padding: 15px; }
    .summary-grid { grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); }
    .laporan-table th, .laporan-table td,
    .detail-table th, .detail-table td { font-size: 14px; padding: 10px; }
}

@media (max-width: 768px) {
    header { padding: 20px 10px; }
    header h1 { font-size: 1.6rem; }
    header p { font-size: 14px; }
    .chart-container, .summary-card { padding: 15px; }
    .laporan-table, .detail-table { display: block; overflow-x: auto; white-space: nowrap; }
    button { width: 100%; }
}

@media (max-width: 480px) {
    header h1 { font-size: 1.4rem; }
    .summary-card .value { font-size: 1.4rem; }
    .container { margin: 10px; padding: 10px; }
    .detail-section h2 { font-size: 1rem; text-align:center; }
}
</style>
</head>
<body>
<header>
    <h1>📊 Data Penjualan (7 Hari Terakhir)</h1>
    <p>Memantau total penjualan & untung/rugi dari transaksi online maupun offline</p>
    <a href="dashboard_admin.php" style="color:white;text-decoration:none;">⬅ Kembali ke Dashboard</a>
</header>

<div class="container">
    <div class="summary-grid">
        <div class="summary-card profit">
            <h4>Total Untung</h4>
            <div class="value">Rp <?= number_format($laporan['total_untung'],0,',','.'); ?></div>
        </div>
        <div class="summary-card loss">
            <h4>Total Rugi</h4>
            <div class="value">Rp <?= number_format($laporan['total_rugi'],0,',','.'); ?></div>
        </div>
        <div class="summary-card net">
            <h4>Net Mingguan</h4>
            <div class="value"><?= $laporan['total_net']>=0?'+':'-' ?>Rp <?= number_format(abs($laporan['total_net']),0,',','.'); ?></div>
        </div>
    </div>

    <div class="chart-container">
        <?php if (array_sum($penjualan_data) == 0): ?>
            <div class="no-data">Belum ada data penjualan minggu ini.</div>
        <?php else: ?>
            <canvas id="chart"></canvas>
        <?php endif; ?>

        <button onclick="exportToCSV()">📥 Export ke Excel</button>

        <table class="laporan-table">
            <thead>
                <tr><th>Tanggal</th><th>Total Penjualan (Rp)</th><th>Untung/Rugi (Rp)</th></tr>
            </thead>
            <tbody>
            <?php foreach ($laporan['data'] as $tanggal => $row): ?>
                <tr>
                    <td><?= $tanggal; ?></td>
                    <td>Rp <?= number_format($row['penjualan'],0,',','.'); ?></td>
                    <td class="<?= $row['untung_rugi']>=0?'profit':'loss'; ?>">
                        <?= $row['untung_rugi']>=0?'+':'-'; ?>Rp <?= number_format(abs($row['untung_rugi']),0,',','.'); ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="detail-section">
        <h2>📋 Detail Transaksi</h2>
        <?php if ($transaksi_detail->num_rows > 0): ?>
        <table class="detail-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nama Pelanggan</th>
                    <th>No HP</th>
                    <th>Alamat</th>
                    <th>Nama Parfum</th>
                    <th>Harga</th>
                    <th>Qty</th>
                    <th>Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php while($t = $transaksi_detail->fetch_assoc()): ?>
                <tr>
                    <td><?= date('d-m-Y H:i', strtotime($t['tanggal'])); ?></td>
                    <td><?= htmlspecialchars($t['nama_pelanggan']); ?></td>
                    <td><?= htmlspecialchars($t['no_hp']); ?></td>
                    <td><?= htmlspecialchars($t['alamat']); ?></td>
                    <td><?= htmlspecialchars($t['nama_parfum']); ?></td>
                    <td>Rp <?= number_format($t['harga'],0,',','.'); ?></td>
                    <td><?= $t['qty']; ?></td>
                    <td>Rp <?= number_format($t['subtotal'],0,',','.'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php else: ?>
            <div class="no-data">Belum ada transaksi yang tercatat.</div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const ctx = document.getElementById('chart');
if (ctx) {
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: <?= json_encode($labels); ?>,
            datasets: [
                { label:'Penjualan', data: <?= json_encode($penjualan_data); ?>, borderColor:'#ffd700', backgroundColor:'rgba(255,215,0,0.2)', fill:true, tension:0.4 },
                { label:'Untung/Rugi', data: <?= json_encode($untung_rugi_data); ?>, borderColor:'#28a745', backgroundColor:'rgba(40,167,69,0.1)', fill:true, tension:0.4 }
            ]
        },
        options: { responsive:true, scales:{ y:{ beginAtZero:true } } }
    });
}

function exportToCSV() {
    let csv = 'Tanggal,Total Penjualan,Untung/Rugi\n';
    const labels = <?= json_encode($labels); ?>;
    const penjualan = <?= json_encode($penjualan_data); ?>;
    const untungRugi = <?= json_encode($untung_rugi_data); ?>;
    for (let i=0;i<labels.length;i++) {
        csv += `${labels[i]},${penjualan[i]},${untungRugi[i]}\n`;
    }
    const blob = new Blob([csv],{type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'laporan_penjualan_mingguan.csv';
    a.click();
}
</script>
</body>
</html>
<?php $conn->close(); ?>
