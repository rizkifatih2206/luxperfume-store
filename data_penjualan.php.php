<?php
session_start();
include "koneksi.php";

if (!isset($_SESSION['role']) || $_SESSION['role'] != "admin") {
    header("Location: index.php");
    exit;
}

// Fungsi hitung untung rugi (dari kode sebelumnya, dengan error handling)
function hitungUntungRugi($conn, $hari = 7) {
    $data = [];
    $total_untung = 0;
    $total_rugi = 0;

    // Cek tabel penjualan
    $check_table = $conn->query("SHOW TABLES LIKE 'penjualan'");
    if ($check_table->num_rows == 0) {
        for ($i = $hari - 1; $i >= 0; $i--) {
            $tanggal = date('Y-m-d', strtotime("-$i days"));
            $data[$tanggal] = ['penjualan' => 0, 'untung_rugi' => 0];
        }
        return ['data' => $data, 'total_untung' => 0, 'total_rugi' => 0, 'total_net' => 0];
    }

    // Cek kolom harga_beli
    $check_col = $conn->query("SHOW COLUMNS FROM parfum LIKE 'harga_beli'");
    $has_harga_beli = $check_col->num_rows > 0;

    for ($i = $hari - 1; $i >= 0; $i--) {
        $tanggal = date('Y-m-d', strtotime("-$i days"));
        if ($has_harga_beli) {
            $query = "SELECT SUM(p.total) as total_penjualan, SUM(p.jumlah * pr.harga_beli) as total_hpp 
                      FROM penjualan p JOIN parfum pr ON p.parfum_id = pr.id 
                      WHERE DATE(p.tanggal) = '$tanggal'";
        } else {
            $query = "SELECT SUM(total) as total_penjualan FROM penjualan WHERE DATE(tanggal) = '$tanggal'";
        }
        $result = $conn->query($query);
        $row = $result ? $result->fetch_assoc() : null;

        $penjualan = (float)($row['total_penjualan'] ?? $row['total_penjualan'] ?? 0);
        // If HPP available
        $hpp = $has_harga_beli ? (float)($row['total_hpp'] ?? 0) : 0;
        $untung_rugi = $penjualan - $hpp;

        $data[$tanggal] = [
            'penjualan' => $penjualan,
            'untung_rugi' => $untung_rugi
        ];

        if ($untung_rugi > 0) $total_untung += $untung_rugi;
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
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Data Penjualan - LuxPerfume Admin</title>
    <link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@400;700&family=Roboto:wght@300;400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Reset & Global Modern Styles */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Roboto', sans-serif; 
            line-height: 1.6; 
            color: #333; 
            background: linear-gradient(135deg, #f9f9f9 0%, #e0e0e0 100%); 
            position: relative; 
            overflow-x: hidden;
        }
        body::before { 
            content: ''; 
            position: fixed; 
            top: 0; left: 0; width: 100%; height: 100%; 
            background: url('https://source.unsplash.com/1920x1080/?perfume,analytics') no-repeat center/cover; 
            z-index: -1; opacity: 0.03; 
        }
        h1, h2, h3 { font-family: 'Playfair Display', serif; }
        .container { max-width: 1400px; margin: 0 auto; padding: 20px; }

        /* Header Modern */
        header { 
            background: linear-gradient(135deg, rgba(0,0,0,0.9) 0%, rgba(255,215,0,0.8) 100%); 
            color: white; 
            padding: 25px; 
            text-align: center; 
            position: sticky; top: 0; z-index: 100; 
            box-shadow: 0 8px 32px rgba(0,0,0,0.2); 
            border-radius: 0 0 20px 20px;
            backdrop-filter: blur(10px);
        }
        header h1 { font-size: 2.5rem; margin-bottom: 10px; display: flex; align-items: center; justify-content: center; gap: 10px; }
        header p { font-size: 1.1rem; opacity: 0.9; }
        .back-link { 
            display: inline-flex; align-items: center; gap: 8px; 
            background: rgba(255,255,255,0.2); color: white; padding: 12px 20px; 
            text-decoration: none; border-radius: 50px; margin-top: 10px; 
            transition: all 0.3s ease; backdrop-filter: blur(5px);
        }
        .back-link:hover { background: rgba(255,255,255,0.3); transform: translateY(-2px); box-shadow: 0 4px 15px rgba(255,215,0,0.3); }

        /* Summary Cards */
        .summary-grid { 
            display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); 
            gap: 20px; margin: 30px 0; 
        }
        .summary-card { 
            background: rgba(255,255,255,0.95); padding: 25px; border-radius: 20px; 
            text-align: center; box-shadow: 0 10px 40px rgba(0,0,0,0.1); 
            transition: all 0.3s ease; backdrop-filter: blur(10px);
        }
        .summary-card:hover { transform: translateY(-5px); box-shadow: 0 15px 50px rgba(0,0,0,0.15); }
        .summary-card.profit { border-top: 5px solid #28a745; }
        .summary-card.loss { border-top: 5px solid #dc3545; }
        .summary-card.net { border-top: 5px solid #ffd700; }
        .summary-card h4 { font-size: 1.2rem; color: #666; margin-bottom: 10px; }
        .summary-card .value { font-size: 2rem; font-weight: bold; margin-bottom: 5px; }
        .summary-card .profit .value { color: #28a745; }
        .summary-card .loss .value { color: #dc3545; }
        .summary-card .net .value { color: #ffd700; }

        /* Chart Container */
        .chart-section { background: rgba(255,255,255,0.95); padding: 30px; border-radius: 20px; margin: 30px 0; box-shadow: 0 10px 40px rgba(0,0,0,0.1); backdrop-filter: blur(10px); }
        .chart-container { position: relative; height: 400px; margin: 20px 0; }

        /* Tabel Laporan */
        .laporan-table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; border-radius: 15px; overflow: hidden; box-shadow: 0 5px 20px rgba(0,0,0,0.1); }
        .laporan-table th, .laporan-table td { padding: 15px; text-align: left; border-bottom: 1px solid #eee; }
        .laporan-table th { background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%); color: #000; font-weight: bold; }
        .laporan-table tr:hover { background: rgba(255,215,0,0.05); }
        .laporan-table .profit { color: #28a745; font-weight: bold; }
        .laporan-table .loss { color: #dc3545; font-weight: bold; }

        /* No Data Message */
        .no-data { text-align: center; padding: 60px 20px; color: #666; font-style: italic; }
        .no-data i { font-size: 4rem; color: #ffd700; margin-bottom: 20px; }

        /* Responsive */
        @media (max-width: 768px) { 
            .container { padding: 10px; }
            header h1 { font-size: 2rem; }
            .summary-grid { grid-template-columns: 1fr; }
            .chart-container { height: 300px; }
            .laporan-table { font-size: 0.9rem; }
            .laporan-table th, .laporan-table td { padding: 10px; }
        }
    </style>
</head>
<body>
    <header>
        <h1><i class="fas fa-chart-line"></i> Data Penjualan (7 Hari Terakhir)</h1>
        <p>Grafik dan laporan untung/rugi harian. Total mingguan di bawah.</p>
        <a href="dashboard_admin.php" class="back-link">
            <i class="fas fa-arrow-left"></i> Kembali ke Dashboard
        </a>
    </header>

    <div class="container">
        <!-- Summary Cards -->
        <div class="summary-grid">
            <div class="summary-card profit">
                <h4><i class="fas fa-arrow-up"></i> Total Untung</h4>
                <div class="value">Rp <?= number_format($laporan['total_untung'], 0, ',', '.'); ?></div>
            </div>
            <div class="summary-card loss">
                <h4><i class="fas fa-arrow-down"></i> Total Rugi</h4>
                <div class="value">Rp <?= number_format($laporan['total_rugi'], 0, ',', '.'); ?></div>
            </div>
            <div class="summary-card net">
                <h4><i class="fas fa-balance-scale"></i> Net Mingguan</h4>
                <div class="value <?= $laporan['total_net'] >= 0 ? 'profit' : 'loss'; ?>">Rp <?= number_format($laporan['total_net'], 0, ',', '.'); ?></div>
            </div>
        </div>

        <!-- Chart Section -->
        <div class="chart-section">
            <h3 style="text-align: center; margin-bottom: 20px; color: #000; border-bottom: 2px solid #ffd700; padding-bottom: 10px;">
                <i class="fas fa-chart-line"></i> Grafik Penjualan & Untung/Rugi
            </h3>
            <?php if (array_sum($penjualan_data) == 0): ?>
                <div class="no-data">
                    <i class="fas fa-chart-bar"></i>
                    <h3>Belum ada data penjualan.</h3>
                    <p>Lakukan transaksi di Etalase Kasir untuk melihat laporan.</p>
                </div>
            <?php else: ?>
                <div class="chart-container">
                    <canvas id="salesChart"></canvas>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <button onclick="exportToCSV()" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); color: white; padding: 12px 25px; border: none; border-radius: 15px; cursor: pointer; font-weight: bold; transition: all 0.3s ease; font-size: 1rem;">
                        <i class="fas fa-download"></i> Export ke CSV
                    </button>
                </div>
            <?php endif; ?>

            <!-- Tabel Laporan Harian -->
            <table class="laporan-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-calendar"></i> Tanggal</th>
                        <th><i class="fas fa-money-bill"></i> Total Penjualan (Rp)</th>
                        <th><i class="fas fa-balance-scale"></i> Untung/Rugi (Rp)</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($laporan['data'] as $tanggal => $item): ?>
                        <tr>
                            <td><?= $tanggal; ?></td>
                            <td>Rp <?= number_format($item['penjualan'], 0, ',', '.'); ?></td>
                            <td class="<?= $item['untung_rugi'] >= 0 ? 'profit' : 'loss'; ?>">
                                <?= $item['untung_rugi'] >= 0 ? '+' : ''; ?>Rp <?= number_format($item['untung_rugi'], 0, ',', '.'); ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <script>
        // Data dari PHP
        const labels = <?= json_encode($labels); ?>;
        const penjualanData = <?= json_encode($penjualan_data); ?>;
        const untungRugiData = <?= json_encode($untung_rugi_data); ?>;

        // Inisialisasi Chart.js jika ada data
        if (labels && labels.length && penjualanData.reduce((a,b)=>a+b,0) > 0) {
            const ctx = document.getElementById('salesChart').getContext('2d');
            const salesChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: labels,
                    datasets: [{
                        label: 'Penjualan (Rp)',
                        data: penjualanData,
                        borderColor: '#ffd700',
                        backgroundColor: 'rgba(255, 215, 0, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#ffd700',
                        pointBorderColor: '#fff',
                        pointRadius: 6
                    }, {
                        label: 'Untung/Rugi (Rp)',
                        data: untungRugiData,
                        borderColor: '#28a745',
                        backgroundColor: 'rgba(40, 167, 69, 0.1)',
                        tension: 0.4,
                        fill: true,
                        pointBackgroundColor: '#28a745',
                        pointBorderColor: '#fff',
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            labels: { font: { size: 14 }, padding: 20 }
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                },
                                font: { size: 12 }
                            },
                            grid: { color: 'rgba(0,0,0,0.1)' }
                        },
                        x: {
                            grid: { color: 'rgba(0,0,0,0.1)' }
                        }
                    },
                    interaction: {
                        intersect: false,
                        mode: 'index'
                    },
                    animation: {
                        duration: 1200,
                        easing: 'easeInOutQuart'
                    }
                }
            });
        }

        // Export to CSV
        function exportToCSV() {
            let csv = 'Tanggal,Penjualan,Untung/Rugi\n';
            for (let i = 0; i < labels.length; i++) {
                const tanggal = labels[i];
                const pen = penjualanData[i] ?? 0;
                const ur = untungRugiData[i] ?? 0;
                csv += `${tanggal},${pen},${ur}\n`;
            }
            const blob = new Blob([csv], { type: 'text/csv' });
            const url = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = url;
            a.download = 'laporan_penjualan_7_hari.csv';
            document.body.appendChild(a);
            a.click();
            document.body.removeChild(a);
            window.URL.revokeObjectURL(url);
        }
    </script>

</body>
</html>
<?php $conn->close(); ?>
