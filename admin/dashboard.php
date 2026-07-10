<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

$user = $_SESSION['user'];

// Fetch statistics
$totalSiswa = $conn->query('SELECT COUNT(*) AS total FROM siswa')->fetch_assoc()['total'] ?? 0;
$totalKuesionerAktif = $conn->query("SELECT COUNT(*) AS total FROM kuesioner WHERE status = 'aktif'")->fetch_assoc()['total'] ?? 0;
$totalJawaban = $conn->query('SELECT COUNT(*) AS total FROM jawaban')->fetch_assoc()['total'] ?? 0;
$totalCluster = $conn->query('SELECT COUNT(DISTINCT nis) AS total FROM hasil_clustering')->fetch_assoc()['total'] ?? 0;

// Calculate dataset (total jawaban)
$jumlahDataset = $totalJawaban;

require_once __DIR__ . '/layout_header.php';
?>

<div class="row g-4 mb-4">
    <div class="col-lg-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">Total Responden</div>
            <div class="metric-value text-primary"><?php echo $totalSiswa; ?></div>
            <div class="metric-change positive">
                <i class="bi bi-arrow-up"></i>
                <span>Active users</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">Total Pertanyaan</div>
            <div class="metric-value text-success"><?php echo $totalKuesionerAktif; ?></div>
            <div class="metric-change positive">
                <i class="bi bi-check-circle"></i>
                <span>Aktif</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">Jumlah Dataset</div>
            <div class="metric-value text-info"><?php echo $jumlahDataset; ?></div>
            <div class="metric-change positive">
                <i class="bi bi-database"></i>
                <span>Total data</span>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="metric-card">
            <div class="metric-label">Jumlah Cluster</div>
            <div class="metric-value text-warning"><?php echo $totalCluster; ?></div>
            <div class="metric-change">
                <i class="bi bi-diagram-3"></i>
                <span>K-Means</span>
            </div>
        </div>
    </div>
</div>

<div class="data-card">
    <div class="card-header">
        <h5 class="card-title mb-0">Ringkasan Sistem</h5>
    </div>
    <div class="row g-4">
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                <div class="avatar bg-primary text-white">
                    <i class="bi bi-people"></i>
                </div>
                <div>
                    <div class="fw-bold">Data Siswa</div>
                    <small class="text-muted">Kelola informasi siswa</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                <div class="avatar bg-success text-white">
                    <i class="bi bi-file-text"></i>
                </div>
                <div>
                    <div class="fw-bold">Data Kuesioner</div>
                    <small class="text-muted">Kelola pernyataan kuesioner</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                <div class="avatar bg-info text-white">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div>
                    <div class="fw-bold">Data Jawaban</div>
                    <small class="text-muted">Lihat jawaban responden</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                <div class="avatar bg-info text-white">
                    <i class="bi bi-clipboard-check"></i>
                </div>
                <div>
                    <div class="fw-bold">Uji Statistik</div>
                    <small class="text-muted">Uji Validitas dan Reliabilitas</small>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="d-flex align-items-center gap-3 p-3 bg-light rounded">
                <div class="avatar bg-warning text-white">
                    <i class="bi bi-diagram-3"></i>
                </div>
                <div>
                    <div class="fw-bold">Clustering</div>
                    <small class="text-muted">Proses Clustering</small>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
