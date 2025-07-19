<?php
// ======================================================================
// FILE LENGKAP DASHBOARD.PHP (Versi Final dengan Perhitungan Real-time)
// ======================================================================

declare(strict_types=1);

require_once 'db.php';

// Fungsi untuk menghitung status yang benar
function calculateStatus(int $overdue_days): string
{
    if ($overdue_days > 30) {
        return 'Merah';
    } elseif ($overdue_days >= 15) {
        return 'Kuning';
    } elseif ($overdue_days >= 1) {
        return 'Hijau';
    }
    return 'Lancar';
}

// Variabel default
$hijauCount = 0;
$kuningCount = 0;
$merahCount = 0;
$lancarCount = 0; // Tambahan untuk menghitung yang lancar
$totalDebtors = 0;
$recentDebtors = [];
$error = null;

try {
    // --- LOGIKA BARU: HITUNG ULANG SEMUA STATUS ---
    // 1. Ambil semua data keterlambatan yang diperlukan
    $stmt = $pdo->query("SELECT `Perhitungan Telat` FROM `debtors`");
    $allLatenessData = $stmt->fetchAll(PDO::FETCH_COLUMN);
    $totalDebtors = count($allLatenessData);

    // 2. Hitung jumlah untuk setiap status di dalam PHP
    foreach ($allLatenessData as $days) {
        $status = calculateStatus((int)$days);
        switch ($status) {
            case 'Hijau':
                $hijauCount++;
                break;
            case 'Kuning':
                $kuningCount++;
                break;
            case 'Merah':
                $merahCount++;
                break;
            case 'Lancar':
                $lancarCount++;
                break;
        }
    }

    // 3. Ambil 5 debitur terbaru untuk ditampilkan di ringkasan dashboard
    // Tabel ringkasan juga perlu dihitung ulang statusnya
    $stmt = $pdo->query("SELECT `Nama Debitur`, `Perhitungan Telat` FROM `debtors` ORDER BY `id` DESC LIMIT 5");
    $recentDebtors = $stmt->fetchAll();

} catch (PDOException $e) {
    // Jika ada error database, tampilkan pesannya agar mudah diperbaiki
    $error = "Database Error: " . $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Ringkasan Debitur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        body { background-color: #f8f9fa; }
        .stats-card { border: none; border-radius: 0.375rem; }
        .stats-card-body { display: flex; justify-content: space-between; align-items: center; padding: 1.25rem; }
        .stats-card .display-6 { opacity: 0.75; }
        .stats-card-hijau { background-color: #d1e7dd; color: #0f5132; }
        .stats-card-kuning { background-color: #fff3cd; color: #664d03; }
        .stats-card-merah { background-color: #f8d7da; color: #842029; }
        .stats-card-total { background-color: #e2e3e5; color: #41464b; }
        a.stats-link { text-decoration: none; color: inherit; }
        a.stats-link .card { transition: transform 0.2s, box-shadow 0.2s; }
        a.stats-link:hover .card { transform: translateY(-5px); box-shadow: 0 8px 15px rgba(0,0,0,0.1); }
    </style>
</head>
<body>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0">Dashboard</h1>
            <p class="text-muted">Ringkasan data debitur (real-time) per tanggal <?= date('d F Y') ?>.</p>
        </div>
        <div>
            <a href="debitur_proses.php" class="btn btn-outline-success me-2"><i class="bi bi-check2-square"></i> Debitur Diproses</a>
            <a href="daftar_debitur.php" class="btn btn-outline-primary"><i class="bi bi-list"></i> Daftar Debitur</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger">
            <strong>Terjadi Kesalahan:</strong> <?= htmlspecialchars($error) ?>
        </div>
    <?php else: ?>
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <a href="daftar_debitur.php?status=Hijau" class="stats-link">
                    <div class="card stats-card stats-card-hijau">
                        <div class="stats-card-body">
                            <div><h6 class="card-title mb-1">STATUS HIJAU</h6><h3 class="mb-0"><?= $hijauCount ?></h3><small>SP-1 Diterbitkan</small></div>
                            <div class="text-end"><i class="bi bi-check-circle-fill display-6"></i></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="daftar_debitur.php?status=Kuning" class="stats-link">
                    <div class="card stats-card stats-card-kuning">
                        <div class="stats-card-body">
                            <div><h6 class="card-title mb-1">STATUS KUNING</h6><h3 class="mb-0"><?= $kuningCount ?></h3><small>Kunjungan Kolektor</small></div>
                            <div class="text-end"><i class="bi bi-exclamation-triangle-fill display-6"></i></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <a href="daftar_debitur.php?status=Merah" class="stats-link">
                    <div class="card stats-card stats-card-merah">
                        <div class="stats-card-body">
                            <div><h6 class="card-title mb-1">STATUS MERAH</h6><h3 class="mb-0"><?= $merahCount ?></h3><small>Proses Penyitaan</small></div>
                            <div class="text-end"><i class="bi bi-x-circle-fill display-6"></i></div>
                        </div>
                    </div>
                </a>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="card stats-card stats-card-total">
                    <div class="stats-card-body">
                        <div><h6 class="card-title mb-1">TOTAL DEBITUR</h6><h3 class="mb-0"><?= $totalDebtors ?></h3><small>Jumlah Seluruh Debitur</small></div>
                        <div class="text-end"><i class="bi bi-people-fill display-6"></i></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0"><i class="bi bi-people-fill me-2"></i>Debitur Terbaru</h5>
                <a href="daftar_debitur.php" class="btn btn-primary">Lihat Semua Debitur &rarr;</a>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama Debitur</th>
                                <th>Status (Real-time)</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentDebtors as $debtor): ?>
                                <?php
                                    // Hitung status yang benar untuk setiap baris di tabel ringkasan
                                    $correctStatus = calculateStatus((int)$debtor['Perhitungan Telat']);
                                ?>
                                <tr>
                                    <td><?= htmlspecialchars($debtor['Nama Debitur']) ?></td>
                                    <td>
                                        <span class="badge 
                                            <?php 
                                                switch($correctStatus) {
                                                    case 'Hijau': echo 'bg-success'; break;
                                                    case 'Kuning': echo 'bg-warning text-dark'; break;
                                                    case 'Merah': echo 'bg-danger'; break;
                                                    default: echo 'bg-secondary';
                                                }
                                            ?>">
                                            <?= $correctStatus ?>
                                        </span>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

</body>
</html>