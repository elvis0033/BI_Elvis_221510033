<?php
// ======================================================================
// FILE DAFTAR_DEBITUR.PHP (Versi 7: Dengan Sorting di Header Tabel)
// ======================================================================

declare(strict_types=1);
require_once 'db.php';

// Fungsi untuk menghitung status yang benar
function calculateStatus(int $overdue_days): array
{
    if ($overdue_days > 30) {
        return ['text' => 'Merah', 'class' => 'bg-danger', 'action' => 'Proses Sita', 'action_class' => 'btn-danger'];
    } elseif ($overdue_days >= 15) {
        return ['text' => 'Kuning', 'class' => 'bg-warning text-dark', 'action' => 'Proses SP2', 'action_class' => 'btn-warning'];
    } elseif ($overdue_days >= 1) {
        return ['text' => 'Hijau', 'class' => 'bg-success', 'action' => 'Proses SP1', 'action_class' => 'btn-success'];
    }
    return ['text' => 'Lancar', 'class' => 'bg-secondary', 'action' => null, 'action_class' => ''];
}

// --- 1. MENGAMBIL NILAI FILTER DAN SORTING DARI URL ---
$searchName = $_GET['nama'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$sortOrder = $_GET['sort'] ?? 'terbaru'; // Default sorting

$allDebtors = [];
$error = null;
$pageTitle = "Daftar Debitur";

try {
    // --- 2. MEMBANGUN QUERY SQL SECARA DINAMIS ---
    $params = [];
    $sql = "SELECT `id`, `Nama Debitur`, `Perhitungan Telat`, `Tenor`, `sudah_proses` FROM `debtors` WHERE 1=1";

    if (!empty($searchName)) {
        $sql .= " AND `Nama Debitur` LIKE :nama";
        $params[':nama'] = '%' . $searchName . '%';
    }

    // --- 3. MENENTUKAN KLAUSA ORDER BY DENGAN AMAN ---
    $orderByClause = "";
    switch ($sortOrder) {
        case 'terlambat_desc':
            $orderByClause = "ORDER BY `Perhitungan Telat` DESC";
            break;
        case 'terlambat_asc':
            $orderByClause = "ORDER BY `Perhitungan Telat` ASC";
            break;
        default: // Default sorting adalah debitur terbaru
            $orderByClause = "ORDER BY `id` DESC";
            break;
    }

    $sql .= " " . $orderByClause;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $allDebtors = $stmt->fetchAll();

    // Filter berdasarkan STATUS yang baru dihitung (dilakukan di PHP)
    if (!empty($filterStatus)) {
        $pageTitle = "Daftar Debitur Status " . htmlspecialchars($filterStatus);
        $filteredData = [];
        foreach ($allDebtors as $debtor) {
            $correctStatusInfo = calculateStatus((int)$debtor['Perhitungan Telat']);
            if ($correctStatusInfo['text'] == $filterStatus) {
                $filteredData[] = $debtor;
            }
        }
        $allDebtors = $filteredData;
    }

} catch (PDOException $e) {
    $error = "Database Error: " . $e->getMessage();
}

// --- 4. PERSIAPAN UNTUK LINK SORTING DINAMIS ---
// Menentukan arah sorting berikutnya untuk kolom Keterlambatan
$nextSort_Keterlambatan = ($sortOrder == 'terlambat_desc') ? 'terlambat_asc' : 'terlambat_desc';
// Membangun query string yang ada untuk dipertahankan saat sorting
$existing_params = http_build_query(array_filter(['nama' => $searchName, 'status' => $filterStatus]));

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        /* CSS untuk membuat header tabel bisa diklik */
        .sortable-header a {
            text-decoration: none;
            color: inherit;
        }
        .sortable-header a:hover {
            color: #fff;
        }
    </style>
</head>
<body>

<div class="container-fluid p-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h1 class="h3 mb-0"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-muted">Klik judul kolom "Keterlambatan" untuk mengurutkan.</p>
        </div>
        <div>
            <a href="dashboard.php" class="btn btn-outline-primary me-2">&larr; Kembali ke Dashboard</a>
            <a href="debitur_proses.php" class="btn btn-outline-success"><i class="bi bi-check2-square"></i> Debitur Diproses</a>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><strong>Filter Pencarian</strong></div>
        <div class="card-body">
            <form action="daftar_debitur.php" method="GET" class="row g-3 align-items-end">
                <div class="col-md-5"><label for="nama" class="form-label">Nama Debitur</label><input type="text" class="form-control" id="nama" name="nama" placeholder="Ketik nama..." value="<?= htmlspecialchars($searchName) ?>"></div>
                <div class="col-md-5"><label for="status" class="form-label">Filter Status</label><select id="status" name="status" class="form-select"><option value="">Semua Status</option><option value="Hijau" <?= $filterStatus == 'Hijau' ? 'selected' : '' ?>>Hijau</option><option value="Kuning" <?= $filterStatus == 'Kuning' ? 'selected' : '' ?>>Kuning</option><option value="Merah" <?= $filterStatus == 'Merah' ? 'selected' : '' ?>>Merah</option><option value="Lancar" <?= $filterStatus == 'Lancar' ? 'selected' : '' ?>>Lancar</option></select></div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                    <a href="daftar_debitur.php" class="btn btn-secondary w-100 mt-2">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="alert alert-danger"><strong>Terjadi Kesalahan:</strong> <?= htmlspecialchars($error) ?></div>
    <?php else: ?>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead class="table-dark">
                            <tr>
                                <th>No.</th>
                                <th>Nama Debitur</th>
                                <th>Status</th>
                                <th class="sortable-header">
                                    <a href="?<?= $existing_params ?>&sort=<?= $nextSort_Keterlambatan ?>">
                                        Keterlambatan
                                        <?php if ($sortOrder == 'terlambat_desc'): ?>
                                            <i class="bi bi-sort-down"></i>
                                        <?php elseif ($sortOrder == 'terlambat_asc'): ?>
                                            <i class="bi bi-sort-up"></i>
                                        <?php endif; ?>
                                    </a>
                                </th>
                                <th>Tenor</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($allDebtors)): ?>
                                <tr><td colspan="6" class="text-center p-4"><strong>Data tidak ditemukan.</strong></td></tr>
                            <?php else: ?>
                                <?php foreach ($allDebtors as $index => $debtor): ?>
                                    <?php
                                        $correctStatus = calculateStatus((int)$debtor['Perhitungan Telat']);
                                    ?>
                                    <tr>
                                        <td><?= $index + 1 ?></td>
                                        <td><?= htmlspecialchars($debtor['Nama Debitur']) ?></td>
                                        <td><span class="badge <?= $correctStatus['class'] ?>"><?= $correctStatus['text'] ?></span></td>
                                        <td><?= $debtor['Perhitungan Telat'] ?> hari</td>
                                        <td><?= $debtor['Tenor'] ?> bulan</td>
                                        <td>
                                            <?php if (isset($debtor['sudah_proses']) && $debtor['sudah_proses']): ?>
                                                <a href="log_history.php?id=<?= $debtor['id'] ?>" class="btn btn-sm btn-info"><i class="bi bi-clock-history"></i> Log History</a>
                                            <?php elseif ($correctStatus['action']): ?>
                                                <button type="button" class="btn btn-sm <?= $correctStatus['action_class'] ?> btn-proses" data-id="<?= $debtor['id'] ?>" data-aksi="<?= $correctStatus['text'] ?>">
                                                    <i class="bi bi-gear-fill"></i> <?= $correctStatus['action'] ?>
                                                </button>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div>

</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on('click', '.btn-proses', function(e) {
    e.preventDefault();
    var btn = $(this);
    var id = btn.data('id');
    var aksi = btn.data('aksi');
    btn.prop('disabled', true).text('Memproses...');
    $.ajax({
        url: 'proses_tindakan.php',
        method: 'POST',
        data: { id: id, aksi: aksi, ajax: 1 },
        xhrFields: { responseType: 'blob' },
        success: function(data, status, xhr) {
            var filename = xhr.getResponseHeader('Content-Disposition');
            if (filename) {
                var match = filename.match(/filename="?([^";]+)"?/);
                filename = match ? match[1] : 'Surat.pdf';
            } else {
                filename = 'Surat.pdf';
            }
            var blob = new Blob([data], { type: 'application/pdf' });
            var link = document.createElement('a');
            link.href = window.URL.createObjectURL(blob);
            link.download = filename;
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);
            btn.replaceWith(
                '<a href="log_history.php?id=' + id + '" class="btn btn-sm btn-info"><i class="bi bi-clock-history"></i> Log History</a>'
            );
        },
        error: function() {
            alert('Gagal memproses!');
            btn.prop('disabled', false).text('Proses');
        }
    });
});
</script>
</html>