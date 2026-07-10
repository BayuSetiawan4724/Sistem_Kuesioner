<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

// 1. Ambil semua pertanyaan aktif
$questions = $conn->query("SELECT id_pernyataan FROM kuesioner WHERE status = 'aktif' ORDER BY id_pernyataan ASC")->fetch_all(MYSQLI_ASSOC);
$numItems = count($questions);

// 2. Ambil data jawaban per siswa (Matrix Data)
$query = "SELECT s.nis, GROUP_CONCAT(j.nilai ORDER BY j.id_pernyataan ASC) as skor_array 
          FROM siswa s JOIN jawaban j ON s.nis = j.nis GROUP BY s.nis";
$result = $conn->query($query);
$data = [];
$totalSkorPerSiswa = [];

while ($row = $result->fetch_assoc()) {
    $scores = explode(',', $row['skor_array']);
    if (count($scores) == $numItems) {
        $data[] = $scores;
        $totalSkorPerSiswa[] = array_sum($scores);
    }
}

$n = count($data); // Jumlah Responden

// Fungsi Korelasi Pearson (Uji Validitas)
function hitungPearson($itemScores, $totalScores, $n) {
    $sumX = array_sum($itemScores);
    $sumY = array_sum($totalScores);
    $sumX2 = 0; $sumY2 = 0; $sumXY = 0;
    
    for ($i = 0; $i < $n; $i++) {
        $sumX2 += pow($itemScores[$i], 2);
        $sumY2 += pow($totalScores[$i], 2);
        $sumXY += ($itemScores[$i] * $totalScores[$i]);
    }
    
    $numerator = ($n * $sumXY) - ($sumX * $sumY);
    $denominator = sqrt(($n * $sumX2 - pow($sumX, 2)) * ($n * $sumY2 - pow($sumY, 2)));
    
    return ($denominator == 0) ? 0 : $numerator / $denominator;
}

// Fungsi Cronbach's Alpha (Uji Reliabilitas)
function hitungCronbach($data, $totalSkorPerSiswa, $n, $k) {
    $varianceItems = 0;
    for ($j = 0; $j < $k; $j++) {
        $column = array_column($data, $j);
        $varianceItems += hitungVarians($column, $n);
    }
    $varianceTotal = hitungVarians($totalSkorPerSiswa, $n);
    
    if ($varianceTotal == 0) return 0;
    return ($k / ($k - 1)) * (1 - ($varianceItems / $varianceTotal));
}

function hitungVarians($arr, $n) {
    $mean = array_sum($arr) / $n;
    $sumSqDiff = 0;
    foreach ($arr as $val) $sumSqDiff += pow($val - $mean, 2);
    return $sumSqDiff / ($n - 1);
}

// --- LOGIKA OTOMATIS R-TABEL ---
function getRTabel($n) {
    // Array referensi r-tabel (Signifikansi 5% / 0.05)
    // Indeks adalah jumlah responden (N)
    $r_list = [
        3 => 0.997, 4 => 0.950, 5 => 0.878, 10 => 0.632, 15 => 0.514, 
        20 => 0.444, 25 => 0.396, 30 => 0.361, 35 => 0.334, 40 => 0.312, 
        45 => 0.294, 50 => 0.279, 60 => 0.254, 70 => 0.235, 80 => 0.220, 
        90 => 0.207, 100 => 0.195, 125 => 0.176, 150 => 0.159, 200 => 0.138, 
        300 => 0.113, 400 => 0.098, 500 => 0.088
    ];

    if (isset($r_list[$n])) return $r_list[$n];

    // Jika N tidak ada di daftar, cari nilai terdekat di bawahnya
    krsort($r_list);
    foreach ($r_list as $key => $val) {
        if ($n >= $key) return $val;
    }
    return 0.361; // Default fallback
}

// Panggil fungsi berdasarkan jumlah responden (N) saat ini
$r_tabel = getRTabel($n);

require_once __DIR__ . '/layout_header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 bg-light">
                <div class="card-body p-4">
                    <h4 class="fw-bold text-dark"><i class="bi bi-cpu-fill me-2"></i>Modul Analisis Validitas & Reliabilitas</h4>
                    <p class="text-secondary mb-0">
                        Halaman ini melakukan kalkulasi otomatis terhadap instrumen penelitian menggunakan metode <strong>Pearson Product Moment</strong> untuk Validitas dan <strong>Cronbach's Alpha</strong> untuk Reliabilitas. Proses ini setara dengan pengujian yang dilakukan pada perangkat lunak statistik profesional (SPSS).
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-xl-8">
            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-uppercase small text-primary mb-3">1. Landasan Teoritis Uji Validitas</h6>
                    <p class="small text-muted">
                        Uji validitas digunakan untuk mengukur sah atau valid tidaknya suatu kuesioner. Sebuah item dinyatakan valid jika memiliki korelasi yang signifikan dengan skor total. Kalkulasi ini menggunakan rumus korelasi Pearson:
                    </p>
                    <div class="bg-light p-3 rounded-3 text-center mb-3">
                        <code class="text-dark fs-5">r = [n(ΣXY) - (ΣX)(ΣY)] / √[(nΣX² - (ΣX)²)(nΣY² - (ΣY)²)]</code>
                    </div>
                    <ul class="small text-muted">
                        <li><strong>r-Hitung:</strong> Koefisien korelasi yang didapatkan dari data responden.</li>
                        <li><strong>r-Tabel:</strong> Nilai kritis berdasarkan Distribusi Nilai r Tabel Signifikansi 5% dengan derajat bebas (df) = n-2.</li>
                    </ul>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-white py-3 border-0">
                    <h5 class="fw-bold mb-0">Output Tabel Validitas Item</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Item Pernyataan</th>
                                <th>Koefisien (r-Hitung)</th>
                                <th>Nilai Kritis (r-Tabel)</th>
                                <th>Interpretasi</th>
                                <th class="pe-4">Kesimpulan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            for ($j = 0; $j < $numItems; $j++): 
                                $itemScores = array_column($data, $j);
                                $r_hitung = hitungPearson($itemScores, $totalSkorPerSiswa, $n);
                                $isValid = ($r_hitung > $r_tabel);
                            ?>
                            <tr>
                                <td class="ps-4 fw-semibold text-secondary">P<?php echo ($j + 1); ?></td>
                                <td class="fw-bold"><?php echo number_format($r_hitung, 3); ?></td>
                                <td><?php echo $r_tabel; ?></td>
                                <td>
                                    <?php 
                                    if ($r_hitung > 0.8) echo "Sangat Kuat";
                                    elseif ($r_hitung > 0.6) echo "Kuat";
                                    elseif ($r_hitung > 0.4) echo "Cukup";
                                    else echo "Lemah";
                                    ?>
                                </td>
                                <td class="pe-4">
                                    <?php if ($isValid): ?>
                                        <span class="badge bg-success-soft text-success px-3 rounded-pill"><i class="bi bi-check-circle-fill me-1"></i> VALID</span>
                                    <?php else: ?>
                                        <span class="badge bg-danger-soft text-danger px-3 rounded-pill"><i class="bi bi-x-circle-fill me-1"></i> TIDAK VALID</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endfor; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-xl-4">
            <div class="card border-0 shadow-sm rounded-4 bg-primary text-white mb-4">
                <div class="card-body p-4 text-center">
                    <h6 class="text-uppercase opacity-75 small fw-bold">Statistik Reliabilitas</h6>
                    <h2 class="mb-1">Cronbach's Alpha</h2>
                    <?php 
                        $alpha = hitungCronbach($data, $totalSkorPerSiswa, $n, $numItems);
                        $isReliable = ($alpha > 0.6);
                    ?>
                    <div class="display-3 fw-bold my-3"><?php echo number_format($alpha, 3); ?></div>
                    <div class="badge bg-white text-primary px-4 py-2 fs-6 rounded-pill">
                        <?php echo $isReliable ? 'KONSISTEN (RELIABEL)' : 'TIDAK KONSISTEN'; ?>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4 mb-4">
                <div class="card-body">
                    <h6 class="fw-bold text-uppercase small text-dark mb-3">2. Landasan Uji Reliabilitas</h6>
                    <p class="small text-muted">
                        Reliabilitas menunjukkan sejauh mana alat ukur dapat dipercaya. Pengujian menggunakan <strong>Alpha Cronbach</strong>:
                    </p>
                    <div class="bg-light p-2 rounded text-center small mb-3">
                        <code>α = [k / (k-1)] * [1 - (Σσᵢ² / σₜ²)]</code>
                    </div>
                    <p class="small text-muted">
                        <strong>Kriteria:</strong> Menurut <em>Nunnally (1994)</em>, suatu instrumen dikatakan reliabel jika memiliki nilai Cronbach's Alpha > <strong>0.600</strong>. Nilai Anda adalah <strong><?php echo number_format($alpha, 3); ?></strong>, yang berarti data ini <?php echo $isReliable ? 'layak' : 'tidak layak'; ?> untuk dianalisis lebih lanjut menggunakan K-Means.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-4">
                <div class="card-header bg-white py-3 border-0">
                    <h6 class="fw-bold mb-0">Informasi Sampel (N)</h6>
                </div>
                <div class="card-body pt-0">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Total Responden:</span>
                        <span class="fw-bold"><?php echo $n; ?> Siswa</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-secondary small">Total Butir Soal:</span>
                        <span class="fw-bold"><?php echo $numItems; ?> Pernyataan</span>
                    </div>
                    <hr class="opacity-25">
                    <div class="p-3 bg-light rounded-3 small text-secondary">
                        <i class="bi bi-info-circle-fill me-1 text-primary"></i>
                        Data ini diambil secara <em>real-time</em> dari database responden pemain Roblox di SMK Mutiara Ilmu.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Tambahan style agar tampilan lebih modern */
    .bg-success-soft { background-color: rgba(25, 135, 84, 0.1); }
    .bg-danger-soft { background-color: rgba(220, 53, 69, 0.1); }
    .bg-primary-soft { background-color: rgba(13, 110, 253, 0.1); }
    .table-modern thead th { border-top: none; text-transform: uppercase; font-size: 0.75rem; letter-spacing: 0.5px; }
</style>

<?php require_once __DIR__ . '/layout_footer.php'; ?>