<?php
require_once 'db.php';
$debtorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
$debtor = null;
$error = null;
if ($debtorId === false || $debtorId === null) {
    $error = "ID Debitur tidak valid.";
} else {
    try {
        $stmt = $pdo->prepare("SELECT `Nama Debitur`, `sudah_proses` FROM `debtors` WHERE `id` = :id");
        $stmt->execute([':id' => $debtorId]);
        $debtor = $stmt->fetch();
        if (!$debtor) {
            $error = "Debitur tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log History Debitur</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0">Log History Debitur</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
                    <?php elseif ($debtor): ?>
                        <p><strong>Nama Debitur:</strong> <?= htmlspecialchars($debtor['Nama Debitur']) ?></p>
                        <p><strong>Status Proses:</strong>
                            <span id="status-badge"
                                class="badge <?php
                                    if ($debtor['sudah_proses'] == 1) echo 'bg-success';
                                    elseif ($debtor['sudah_proses'] == 2) echo 'bg-warning text-dark';
                                    else echo 'bg-secondary';
                                ?>">
                                <?php
                                    if ($debtor['sudah_proses'] == 1) echo 'Sudah Proses';
                                    elseif ($debtor['sudah_proses'] == 2) echo 'Sedang Diproses';
                                    else echo 'Belum Proses';
                                ?>
                            </span>
                        </p>
                        <?php if ($debtor['sudah_proses'] != 1): ?>
                        <button type="button" class="btn btn-success" id="btn-konfirmasi" data-id="<?= $debtorId ?>">Konfirmasi Sudah Diproses</button>
                        <div id="notif-konfirmasi" class="mt-3"></div>
                        <button type="button" class="btn btn-primary" id="btn-regenerate" data-id="<?= $debtorId ?>">Regenerate PDF</button>
                        <div id="notif-regen" class="mt-3"></div>
                        <?php endif; ?>
                        <hr>
                        <p>Log: PDF surat sudah pernah digenerate untuk debitur ini.</p>
                    <?php endif; ?>
                    <div class="mt-4">
                        <a href="daftar_debitur.php" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Kembali ke Daftar Debitur
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</body>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).on('click', '#btn-regenerate', function(e) {
    e.preventDefault();
    var btn = $(this);
    var id = btn.data('id');
    btn.prop('disabled', true).text('Mengenerate ulang...');
    // Default aksi: SP1, SP2, atau Penyitaan (bisa diubah sesuai kebutuhan)
    var aksi = 'Hijau'; // Default, bisa diambil dari database jika ingin lebih dinamis
    <?php
    // Tentukan aksi terakhir dari status proses, misal dari GET atau database
    // Untuk demo, pakai default 'Hijau'.
    ?>
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
            $('#notif-regen').html('<div class="alert alert-success mt-2">PDF berhasil digenerate ulang dan didownload.</div>');
            btn.prop('disabled', false).text('Regenerate PDF');
        },
        error: function() {
            $('#notif-regen').html('<div class="alert alert-danger mt-2">Gagal generate ulang PDF.</div>');
            btn.prop('disabled', false).text('Regenerate PDF');
        }
    });
});

$(document).on('click', '#btn-konfirmasi', function(e) {
    e.preventDefault();
    var btn = $(this);
    var id = btn.data('id');
    btn.prop('disabled', true).text('Mengonfirmasi...');
    $.ajax({
        url: 'log_history.php',
        method: 'POST',
        data: { id: id, konfirmasi: 1 },
        success: function(res) {
            $('#status-badge').removeClass('bg-warning text-dark').addClass('bg-success').text('Sudah Proses');
            btn.remove();
            $('#notif-konfirmasi').html('<div class="alert alert-success mt-2">Status berhasil dikonfirmasi sebagai Sudah Proses.</div>');
        },
        error: function() {
            $('#notif-konfirmasi').html('<div class="alert alert-danger mt-2">Gagal konfirmasi status.</div>');
            btn.prop('disabled', false).text('Konfirmasi Sudah Diproses');
        }
    });
});
</script>
<?php
// Tambahkan handler konfirmasi status jika ada POST konfirmasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['konfirmasi']) && isset($_POST['id'])) {
    $id = (int)$_POST['id'];
    $stmt = $pdo->prepare("UPDATE debtors SET sudah_proses = 1 WHERE id = :id");
    $stmt->execute([':id' => $id]);
    exit;
}
?>
</html> 