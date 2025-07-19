<?php
// ======================================================================
// FILE: PROSES_TINDAKAN.PHP (Versi 3: Tanpa Bubble Logika Lanjutan)
// ======================================================================

declare(strict_types=1);
require_once 'db.php';

$isAjax = isset($_POST['ajax']) && $_POST['ajax'] == 1;
if ($isAjax) {
    $debtorId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
    $actionType = filter_input(INPUT_POST, 'aksi', FILTER_SANITIZE_SPECIAL_CHARS);
} else {
    $debtorId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    $actionType = filter_input(INPUT_GET, 'aksi', FILTER_SANITIZE_SPECIAL_CHARS);
}

$debtor = null;
$error = null;

if ($debtorId === false || $actionType === null) {
    $error = "ID Debitur atau jenis aksi tidak valid.";
} else {
    try {
        // 2. Ambil data debitur dari database, TERMASUK PROFIL RISIKO
        $stmt = $pdo->prepare("SELECT `Nama Debitur`, `Profil Risiko` FROM `debtors` WHERE `id` = :id");
        $stmt->execute([':id' => $debtorId]);
        $debtor = $stmt->fetch();

        if (!$debtor) {
            $error = "Debitur dengan ID " . htmlspecialchars($debtorId) . " tidak ditemukan.";
        } else {
            // --- Tambahkan logic generate PDF di sini ---
            require_once __DIR__ . '/fpdf182/fpdf.php';
            $pdf = new FPDF();
            $pdf->AddPage();
            $pdf->SetFont('Arial','B',16);

            // Judul surat sesuai aksi
            $judul = '';
            if ($actionType == 'Hijau') $judul = 'Surat Peringatan 1 (SP1)';
            elseif ($actionType == 'Kuning') $judul = 'Surat Peringatan 2 (SP2)';
            elseif ($actionType == 'Merah') $judul = 'Surat Penyitaan';
            else $judul = 'Surat';

            $pdf->Cell(0,10,$judul,0,1,'C');
            $pdf->SetFont('Arial','',12);
            $pdf->Ln(10);
            $pdf->Cell(0,10,'Nama Debitur: '.$debtor['Nama Debitur'],0,1);
            $pdf->Cell(0,10,'Profil Risiko: '.$debtor['Profil Risiko'],0,1);
            $pdf->Ln(10);
            $isiSurat = '';
            if ($actionType == 'Hijau') {
                $isiSurat = "Dengan ini kami memberikan peringatan pertama (SP1) kepada saudara/i atas keterlambatan pembayaran angsuran. Mohon segera melakukan pelunasan.";
            } elseif ($actionType == 'Kuning') {
                $isiSurat = "Ini adalah Surat Peringatan Kedua (SP2). Kolektor akan melakukan kunjungan ke alamat Anda untuk klarifikasi dan penyelesaian tunggakan.";
            } elseif ($actionType == 'Merah') {
                $isiSurat = "Proses penyitaan kendaraan akan segera dilakukan sesuai ketentuan yang berlaku karena tidak ada penyelesaian atas tunggakan.";
            } else {
                $isiSurat = "Surat tindakan sesuai status debitur.";
            }
            $pdf->MultiCell(0,10,$isiSurat);
            $pdf->Ln(10);
            $pdf->Cell(0,10,'Tanggal: '.date('d-m-Y'),0,1);

            // Output PDF langsung download
            $filename = $judul.' - '.$debtor['Nama Debitur'].'.pdf';
            // Update status sudah_proses ke 2 (Sedang Diproses) sebelum generate PDF
            $update = $pdo->prepare("UPDATE debtors SET sudah_proses = 2 WHERE id = :id");
            $update->execute([':id' => $debtorId]);
            if ($isAjax) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: attachment; filename="'.str_replace('"','',$filename).'"');
                $pdf->Output('F', 'php://output');
                exit;
            } else {
                $pdf->Output('D', $filename);
                exit;
            }
        }
    } catch (PDOException $e) {
        $error = "Database Error: " . $e->getMessage();
    }
}

// Menentukan judul dan deskripsi berdasarkan jenis aksi
$pageTitle = "Konfirmasi Tindakan";
$actionDescription = "";
switch ($actionType) {
    case 'Hijau':
        $actionDescription = "Anda akan memproses **Surat Peringatan 1 (SP1)**.";
        break;
    case 'Kuning':
        $actionDescription = "Anda akan memproses **Surat Peringatan 2 (SP2)** dan membuat **tugas untuk kolektor**.";
        break;
    case 'Merah':
        $actionDescription = "Anda akan memulai **proses penyitaan kendaraan**.";
        break;
    default:
        $actionDescription = "Aksi tidak dikenali.";
        break;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container mt-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h4 class="mb-0"><?= htmlspecialchars($pageTitle) ?></h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger">
                            <?= htmlspecialchars($error) ?>
                        </div>
                    <?php elseif ($debtor): ?>
                        <div class="alert alert-info">
                            <h5 class="alert-heading">Detail Aksi</h5>
                            <p><strong>Nama Debitur:</strong> <?= htmlspecialchars($debtor['Nama Debitur']) ?></p>
                            <p><strong>Profil Risiko:</strong> <?= htmlspecialchars($debtor['Profil Risiko']) ?></p>
                            <hr>
                            <p class="mb-0"><strong>Tindakan:</strong> <?= $actionDescription ?></p>
                        </div>
                        
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
</html>