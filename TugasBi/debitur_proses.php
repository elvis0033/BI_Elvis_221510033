<?php
require_once 'db.php';

// Ambil semua debitur yang sudah diproses (sudah_proses = 1 atau 2)
$stmt = $pdo->query("SELECT id, `Nama Debitur`, sudah_proses FROM debtors WHERE sudah_proses IN (1,2) ORDER BY id DESC");
$debiturList = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Debitur yang Telah Diproses</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="mb-0">Debitur yang Telah Diproses</h2>
        <div>
            <a href="dashboard.php" class="btn btn-outline-primary me-2"><i class="bi bi-house"></i> Dashboard</a>
            <a href="daftar_debitur.php" class="btn btn-outline-secondary"><i class="bi bi-list"></i> Daftar Debitur</a>
        </div>
    </div>
    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No.</th>
                            <th>Nama Debitur</th>
                            <th>Status Proses</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($debiturList)): ?>
                            <tr><td colspan="4" class="text-center p-4"><strong>Tidak ada debitur yang sudah diproses.</strong></td></tr>
                        <?php else: ?>
                            <?php foreach ($debiturList as $i => $d): ?>
                                <tr>
                                    <td><?= $i+1 ?></td>
                                    <td><?= htmlspecialchars($d['Nama Debitur']) ?></td>
                                    <td>
                                        <?php if ($d['sudah_proses'] == 2): ?>
                                            <span class="badge bg-warning text-dark">Sedang Diproses</span>
                                        <?php elseif ($d['sudah_proses'] == 1): ?>
                                            <span class="badge bg-success">Sudah Proses</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="log_history.php?id=<?= $d['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-clock-history"></i> Log History</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
</body>
</html> 