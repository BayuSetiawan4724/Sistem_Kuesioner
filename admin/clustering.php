<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

// --- LOGIKA JUDUL DINAMIS ---
$currentAlgo = isset($_GET['algo']) ? $_GET['algo'] : 'kmeans';
$algoTitles = [
    'kmeans' => 'K-Means',
    'kmedoids' => 'K-Medoids',
    'dbscan' => 'DBSCAN',
    'hierarchical' => 'Hierarchical'
];
$activeAlgoName = isset($algoTitles[$currentAlgo]) ? $algoTitles[$currentAlgo] : 'K-Means';

$success = '';
$show_process = false;
$process_logs = [];
$wcss_data = [];
$scatter_data = ['Sehat' => [], 'Berisiko' => [], 'Toksik' => []];

// --- HANDLE RESET DATA ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_data') {
    $conn->query("TRUNCATE TABLE hasil_clustering");
    $conn->query("TRUNCATE TABLE hasil_skor");
    $success = "Semua data clustering dan skor variabel telah dibersihkan.";
}

// --- HANDLE PROSES CLUSTERING ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'proses_clustering') {
    $show_process = true;

    // 1. PREPROCESSING & TRANSFORMASI
    $query = "SELECT s.nis, s.nama, GROUP_CONCAT(j.nilai ORDER BY j.id_pernyataan ASC) as skor 
              FROM siswa s JOIN jawaban j ON s.nis = j.nis GROUP BY s.nis";
    $res = $conn->query($query);

    $clean_dataset = [];
    $conn->query("TRUNCATE TABLE hasil_skor");
    $totals_v = ['v1' => 0, 'v2' => 0, 'v3' => 0, 'v4' => 0, 'v5' => 0];
    $count_responden = 0;

    while ($row = $res->fetch_assoc()) {
        $s = explode(',', $row['skor']);
        if (count($s) < 15) continue;
        $count_responden++;

        $v1 = round(array_sum(array_slice($s, 0, 3)) / 3, 2);
        $v2 = round(array_sum(array_slice($s, 3, 3)) / 3, 2);
        $v3 = round(array_sum(array_slice($s, 6, 3)) / 3, 2);
        $v4 = round(array_sum(array_slice($s, 9, 3)) / 3, 2);
        $v5 = round(array_sum(array_slice($s, 12, 3)) / 3, 2);
        $total_avg = round(array_sum($s) / 15, 2);

        $totals_v['v1'] += $v1;
        $totals_v['v2'] += $v2;
        $totals_v['v3'] += $v3;
        $totals_v['v4'] += $v4;
        $totals_v['v5'] += $v5;

        $stmt = $conn->prepare("INSERT INTO hasil_skor (nis, skor_keaktifan_komunikasi, skor_teman_ngobrol, skor_cara_berkomunikasi, skor_isi_obrolan, skor_perasaan_saat_berkomunikasi, skor_total) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param('sdddddd', $row['nis'], $v1, $v2, $v3, $v4, $v5, $total_avg);
        $stmt->execute();

        $clean_dataset[$row['nis']] = [$v1, $v2, $v3, $v4, $v5];
    }

    $process_logs['totals_v'] = $totals_v;
    $process_logs['count_n'] = $count_responden;
    $process_logs['step1_clean'] = $clean_dataset;

    // 2. LOGIKA ALGORITMA (SWITCH CASE)
    $assignments = [];
    $k = 3;

    switch ($currentAlgo) {
        case 'kmeans':
            $centroids = [[1.33, 3.0, 1.67, 1.67, 1.33], [2.33, 3.0, 2.67, 2.0, 2.33], [4.67, 4.33, 5.0, 5.0, 4.0]];
            for ($iter = 1; $iter <= 50; $iter++) {
                $new_assignments = [];
                foreach ($clean_dataset as $nis => $scores) {
                    $dists = [];
                    foreach ($centroids as $c) {
                        $sum_sq = 0;
                        for ($i = 0; $i < 5; $i++) {
                            $sum_sq += pow($scores[$i] - $c[$i], 2);
                        }
                        $dists[] = sqrt($sum_sq);
                    }
                    $new_assignments[$nis] = array_keys($dists, min($dists))[0];
                }
                $process_logs['history'][$iter] = ['centroids' => $centroids, 'counts' => array_count_values($new_assignments)];
                if ($iter > 1 && $new_assignments === $assignments) break;
                $assignments = $new_assignments;
                foreach ($centroids as $c_idx => $c_val) {
                    $members = array_keys($assignments, $c_idx);
                    if (count($members) > 0) {
                        for ($i = 0; $i < 5; $i++) {
                            $sum = 0;
                            foreach ($members as $m) {
                                $sum += $clean_dataset[$m][$i];
                            }
                            $centroids[$c_idx][$i] = round($sum / count($members), 2);
                        }
                    }
                }
            }
            break;

        case 'kmedoids':
            $medoid_keys = array_rand($clean_dataset, $k);
            $centroids = [];
            foreach ($medoid_keys as $key) {
                $centroids[] = $clean_dataset[$key];
            }
            for ($iter = 1; $iter <= 20; $iter++) {
                $new_assignments = [];
                foreach ($clean_dataset as $nis => $scores) {
                    $dists = [];
                    foreach ($centroids as $c) {
                        $sum_abs = 0;
                        for ($i = 0; $i < 5; $i++) {
                            $sum_abs += abs($scores[$i] - $c[$i]);
                        }
                        $dists[] = $sum_abs;
                    }
                    $new_assignments[$nis] = array_keys($dists, min($dists))[0];
                }
                $process_logs['history'][$iter] = ['centroids' => $centroids, 'counts' => array_count_values($new_assignments)];
                if ($iter > 1 && $new_assignments === $assignments) break;
                $assignments = $new_assignments;
                foreach ($centroids as $c_idx => $curr) {
                    $members = array_keys($assignments, $c_idx);
                    $best_m = $curr;
                    $min_cost = PHP_INT_MAX;
                    foreach ($members as $cand) {
                        $cost = 0;
                        foreach ($members as $other) {
                            for ($i = 0; $i < 5; $i++) {
                                $cost += abs($clean_dataset[$cand][$i] - $clean_dataset[$other][$i]);
                            }
                        }
                        if ($cost < $min_cost) {
                            $min_cost = $cost;
                            $best_m = $clean_dataset[$cand];
                        }
                    }
                    $centroids[$c_idx] = $best_m;
                }
            }
            break;

        case 'dbscan':
            $eps = 0.75;
            $minPts = 4;
            $visited = [];
            $cluster_id = 0;
            foreach ($clean_dataset as $nis => $scores) {
                if (isset($visited[$nis])) continue;
                $visited[$nis] = true;
                $neighbors = [];
                foreach ($clean_dataset as $t_nis => $t_s) {
                    $d = 0;
                    for ($i = 0; $i < 5; $i++) {
                        $d += pow($scores[$i] - $t_s[$i], 2);
                    }
                    if (sqrt($d) <= $eps) $neighbors[] = $t_nis;
                }
                if (count($neighbors) >= $minPts) {
                    $cluster_id++;
                    $assignments[$nis] = $cluster_id % 3;
                    $queue = $neighbors;
                    while (!empty($queue)) {
                        $p = array_shift($queue);
                        if (!isset($visited[$p])) {
                            $visited[$p] = true;
                            $p_n = [];
                            foreach ($clean_dataset as $tn => $ts) {
                                $pd = 0;
                                for ($i = 0; $i < 5; $i++) {
                                    $pd += pow($clean_dataset[$p][$i] - $ts[$i], 2);
                                }
                                if (sqrt($pd) <= $eps) $p_n[] = $tn;
                            }
                            if (count($p_n) >= $minPts) $queue = array_merge($queue, $p_n);
                        }
                        if (!isset($assignments[$p])) $assignments[$p] = $cluster_id % 3;
                    }
                } else {
                    $assignments[$nis] = 0;
                }
            }
            $iter = 1;
            break;

        case 'hierarchical':
            $clusters = [];
            foreach ($clean_dataset as $nis => $s) {
                $clusters[] = ['ids' => [$nis], 'c' => $s];
            }
            while (count($clusters) > $k) {
                $min_d = PHP_INT_MAX;
                $merge = [0, 1];
                for ($i = 0; $i < count($clusters); $i++) {
                    for ($j = $i + 1; $j < count($clusters); $j++) {
                        $d = 0;
                        for ($v = 0; $v < 5; $v++) {
                            $d += pow($clusters[$i]['c'][$v] - $clusters[$j]['c'][$v], 2);
                        }
                        if ($d < $min_d) {
                            $min_d = $d;
                            $merge = [$i, $j];
                        }
                    }
                }
                $new_ids = array_merge($clusters[$merge[0]]['ids'], $clusters[$merge[1]]['ids']);
                $new_c = [];
                for ($v = 0; $v < 5; $v++) {
                    $sum = 0;
                    foreach ($new_ids as $id) {
                        $sum += $clean_dataset[$id][$v];
                    }
                    $new_c[] = round($sum / count($new_ids), 2);
                }
                array_splice($clusters, $merge[1], 1);
                array_splice($clusters, $merge[0], 1);
                $clusters[] = ['ids' => $new_ids, 'c' => $new_c];
            }
            foreach ($clusters as $idx => $c) {
                foreach ($c['ids'] as $id) {
                    $assignments[$id] = $idx;
                }
            }
            $iter = 1;
            break;
    }
    // --- 3. HITUNG SILHOUETTE SCORE (Revisi Poin 5b - Versi Terurut) ---
    $temp_silhouette = [[], [], []]; // Array penampung untuk 3 klaster
    $total_s_i = 0;

    foreach ($clean_dataset as $nis => $scores) {
        $ci = $assignments[$nis];

        // a(i): Rata-rata jarak ke anggota dalam klaster yang sama
        $a_i = 0;
        $same_members = array_keys($assignments, $ci);
        if (count($same_members) > 1) {
            foreach ($same_members as $m_nis) {
                if ($nis === $m_nis) continue;
                $d = 0;
                for ($v = 0; $v < 5; $v++) {
                    $d += pow($scores[$v] - $clean_dataset[$m_nis][$v], 2);
                }
                $a_i += sqrt($d);
            }
            $a_i /= (count($same_members) - 1);
        }

        // b(i): Rata-rata jarak ke klaster tetangga terdekat
        $b_i = PHP_INT_MAX;
        for ($c_idx = 0; $c_idx < $k; $c_idx++) {
            if ($c_idx === $ci) continue;
            $others = array_keys($assignments, $c_idx);
            if (empty($others)) continue;
            $d_other = 0;
            foreach ($others as $o_nis) {
                $d = 0;
                for ($v = 0; $v < 5; $v++) {
                    $d += pow($scores[$v] - $clean_dataset[$o_nis][$v], 2);
                }
                $d_other += sqrt($d);
            }
            $avg_d = $d_other / count($others);
            if ($avg_d < $b_i) $b_i = $avg_d;
        }

        // Hitung s(i)
        $s_i = ($a_i == 0 && $b_i == 0) ? 0 : ($b_i - $a_i) / max($a_i, $b_i);
        $total_s_i += $s_i;

        // Simpan sementara berdasarkan klaster untuk pengurutan visual
        $temp_silhouette[$ci][] = [
            'score' => round($s_i, 3),
            'color' => ($ci == 0 ? '#198754' : ($ci == 1 ? '#ffc107' : '#dc3545'))
        ];
    }

    // Reset data utama untuk dikirim ke Javascript
    $silhouette_data = ['scores' => [], 'colors' => [], 'labels' => []];

    // Proses pengurutan per klaster agar grafik membentuk pola "sirip" yang selaras
    for ($i = 0; $i < 3; $i++) {
        // Urutkan skor dari yang terbesar ke terkecil di tiap klaster
        usort($temp_silhouette[$i], function ($a, $b) {
            return $b['score'] <=> $a['score'];
        });

        foreach ($temp_silhouette[$i] as $item) {
            $silhouette_data['scores'][] = $item['score'];
            $silhouette_data['colors'][] = $item['color'];
            $silhouette_data['labels'][] = ''; // Label dikosongkan agar grafik rapat dan rapi
        }
    }

    $process_logs['avg_silhouette'] = round($total_s_i / count($clean_dataset), 3);

    // --- 4. POST-PROCESSING & DB UPDATE ---
    $conn->query("TRUNCATE TABLE hasil_clustering");
    $labels = ["Komunikasi Sehat", "Komunikasi Berisiko", "Komunikasi Toksik"];

    // Reset scatter data agar bersih sebelum diisi ulang
    $scatter_data = ['Sehat' => [], 'Berisiko' => [], 'Toksik' => []];

    foreach ($assignments as $nis => $c_idx) {
        // Penanganan index agar tetap aman (terutama untuk DBSCAN/Noise)
        $safe_idx = ($c_idx < 0 || $c_idx > 2) ? 0 : $c_idx;
        $lbl = $labels[$safe_idx];

        $stmt = $conn->prepare("INSERT INTO hasil_clustering (nis, cluster_label, created_at) VALUES (?, ?, NOW())");
        $stmt->bind_param('ss', $nis, $lbl);
        $stmt->execute();

        // Pemetaan key untuk Scatter Plot
        $key = (strpos($lbl, 'Sehat') !== false) ? 'Sehat' : ((strpos($lbl, 'Berisiko') !== false) ? 'Berisiko' : 'Toksik');

        // Mengisi data koordinat scatter plot
        $scatter_data[$key][] = [
            'x' => $clean_dataset[$nis][0],
            'y' => round(array_sum($clean_dataset[$nis]) / 5, 2)
        ];
    }

    // --- PENTING: Hitung ulang WCSS agar grafik Elbow Method TIDAK HILANG ---
    for ($k_elbow = 1; $k_elbow <= 10; $k_elbow++) {
        $temp_centroids = array_slice(array_values($clean_dataset), 0, $k_elbow);
        $sse = 0;
        foreach ($clean_dataset as $data) {
            $dists = [];
            foreach ($temp_centroids as $c) {
                $sum_sq = 0;
                for ($i = 0; $i < 5; $i++) {
                    $sum_sq += pow($data[$i] - $c[$i], 2);
                }
                $dists[] = $sum_sq;
            }
            $sse += min($dists);
        }
        $wcss_data[$k_elbow] = round($sse, 2);
    }

    $process_logs['iterations_count'] = $iter;
    $success = "Analisis " . $activeAlgoName . " Berhasil Selesai!";
}

// Ambil hasil akhir untuk tabel
$final_results = $conn->query("SELECT hc.*, s.nama FROM hasil_clustering hc JOIN siswa s ON hc.nis = s.nis ORDER BY hc.cluster_label ASC")->fetch_all(MYSQLI_ASSOC);

// Hitung statistik untuk metric cards
$stats_q = $conn->query("SELECT cluster_label, COUNT(*) as total FROM hasil_clustering GROUP BY cluster_label")->fetch_all(MYSQLI_ASSOC);
$stats = ['Sehat' => 0, 'Berisiko' => 0, 'Toksik' => 0];

foreach ($stats_q as $s) {
    if (strpos($s['cluster_label'], 'Sehat') !== false) $stats['Sehat'] = $s['total'];
    if (strpos($s['cluster_label'], 'Berisiko') !== false) $stats['Berisiko'] = $s['total'];
    if (strpos($s['cluster_label'], 'Toksik') !== false) $stats['Toksik'] = $s['total'];
}

require_once __DIR__ . '/layout_header.php';
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<div class="container-fluid py-4 bg-light">
    <?php if ($success): ?>
        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4 d-flex align-items-center">
            <i class="bi bi-check-circle-fill me-2 fs-5"></i> <?= $success ?>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body p-4 d-flex flex-wrap justify-content-between align-items-center">
            <div>
                <h5 class="fw-bold mb-1">
                    <i class="bi bi-gear-wide-connected text-primary me-2"></i>
                    Clustering <?= $activeAlgoName ?>
                </h5>
                <p class="text-muted small mb-0">Total Responden: <strong><?= count($final_results) ?> Siswa</strong></p>
            </div>
            <div class="d-flex gap-2">
                <form method="POST">
                    <input type="hidden" name="action" value="proses_clustering">
                    <button type="submit" class="btn btn-primary rounded-pill px-4 fw-bold shadow-sm">
                        Jalankan Algoritma
                    </button>
                </form>

                <a href="export_excel.php" class="btn btn-success rounded-pill px-4 fw-bold shadow-sm">
                    <i class="bi bi-file-earmark-excel me-1"></i> Export
                </a>

                <form method="POST">
                    <input type="hidden" name="action" value="reset_data">
                    <button type="submit" class="btn btn-outline-danger rounded-pill px-4 fw-bold shadow-sm">
                        Reset
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="row g-3 mb-4 text-center">
        <?php foreach (['success' => 'Sehat', 'warning' => 'Berisiko', 'danger' => 'Toksik'] as $col => $key): ?>
            <div class="col-md-4">
                <div class="card border-0 shadow-sm rounded-4 border-top border-<?= $col ?> border-5">
                    <div class="card-body py-4">
                        <h1 class="fw-bold text-<?= $col ?> mb-0"><?= $stats[$key] ?></h1>
                        <div class="text-muted fw-bold small text-uppercase">Cluster <?= $key ?></div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <?php if ($show_process): ?>
        <div class="row mb-4">
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white fw-bold border-0 pt-3">Visualisasi Elbow Method</div>
                    <div class="card-body"><canvas id="elbowChart"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white fw-bold border-0 pt-3 text-center">Scatter Plot Sebaran Cluster</div>
                    <div class="card-body"><canvas id="scatterChart"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white fw-bold border-0 pt-3 d-flex justify-content-between">
                        <span>Silhouette Score Plot</span>
                        <span class="badge bg-primary"><?= $process_logs['avg_silhouette'] ?></span>
                    </div>
                    <div class="card-body"><canvas id="silhouetteChart"></canvas></div>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-header bg-white fw-bold border-0 pt-3">Log Tahapan Iterasi</div>
                    <div class="card-body" style="max-height: 350px; overflow-y: auto;">
                        <?php if (isset($process_logs['history'])): foreach ($process_logs['history'] as $i => $log): ?>
                                <div class="mb-3 p-3 border rounded-3 small">
                                    <div class="fw-bold text-primary">Iterasi ke-<?= $i ?></div>
                                    <div class="text-muted">Centroid: [<?= implode('], [', array_map(function ($c) {
                                                                            return implode(', ', $c);
                                                                        }, $log['centroids'])) ?>]</div>
                                    <div class="fw-bold mt-1">Anggota: C0: <?= $log['counts'][0] ?? 0 ?> | C1: <?= $log['counts'][1] ?? 0 ?> | C2: <?= $log['counts'][2] ?? 0 ?></div>
                                </div>
                        <?php endforeach;
                        endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm rounded-4 mb-4 overflow-hidden">
            <div class="card-header bg-white py-3 border-0">
                <h6 class="fw-bold mb-0 text-dark">Dataset Preprocessing (Identitas Dihapus)</h6>
            </div>
            <div class="table-responsive" style="max-height: 500px;">
                <table class="table table-sm table-hover align-middle mb-0 small text-center">
                    <thead class="table-light sticky-top">
                        <tr>
                            <th>NIS</th>
                            <th>Variabel 1</th>
                            <th>Variabel 2</th>
                            <th>Variabel 3</th>
                            <th>Variabel 4</th>
                            <th>Variabel 5</th>
                            <th>Mean Row</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($process_logs['step1_clean'] as $nis => $scores): ?>
                            <tr>
                                <td class="text-muted"><?= $nis ?></td>
                                <td><?= number_format($scores[0], 2) ?></td>
                                <td><?= number_format($scores[1], 2) ?></td>
                                <td><?= number_format($scores[2], 2) ?></td>
                                <td><?= number_format($scores[3], 2) ?></td>
                                <td><?= number_format($scores[4], 2) ?></td>
                                <td class="fw-bold"><?= number_format(array_sum($scores) / 5, 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-primary sticky-bottom fw-bold">
                        <tr>
                            <td>TOTAL (SUM)</td>
                            <td><?= number_format($process_logs['totals_v']['v1'], 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v2'], 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v3'], 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v4'], 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v5'], 2) ?></td>
                            <td>-</td>
                        </tr>
                        <tr class="table-warning">
                            <td>MEAN ($\mu$) / <?= $process_logs['count_n'] ?? 155 ?></td>
                            <td><?= number_format($process_logs['totals_v']['v1'] / ($process_logs['count_n'] ?? 155), 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v2'] / ($process_logs['count_n'] ?? 155), 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v3'] / ($process_logs['count_n'] ?? 155), 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v4'] / ($process_logs['count_n'] ?? 155), 2) ?></td>
                            <td><?= number_format($process_logs['totals_v']['v5'] / ($process_logs['count_n'] ?? 155), 2) ?></td>
                            <td class="text-muted">Global Mean</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="card-header bg-white py-3 border-0 d-flex justify-content-between align-items-center">
            <h6 class="fw-bold mb-0 text-primary"><i class="bi bi-table me-2"></i>Hasil Pengelompokan Perilaku Final</h6>
            <span class="badge bg-light text-primary border rounded-pill px-3">Iterasi Optimal: <?= $process_logs['iterations_count'] ?? 0 ?></span>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light small text-uppercase">
                    <tr>
                        <th class="ps-4">No</th>
                        <th>NIS</th>
                        <th>Nama Siswa</th>
                        <th class="text-center">Label Perilaku</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($final_results)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-5">Silakan jalankan algoritma.</td>
                        </tr>
                        <?php else: $no = 1;
                        foreach ($final_results as $res): ?>
                            <tr>
                                <td class="ps-4 text-muted"><?= $no++ ?></td>
                                <td class="small text-muted"><?= $res['nis'] ?></td>
                                <td class="fw-bold text-dark"><?= htmlspecialchars($res['nama']) ?></td>
                                <td class="text-center">
                                    <?php $lbl = $res['cluster_label'];
                                    $badge = strpos($lbl, 'Toksik') !== false ? 'danger' : (strpos($lbl, 'Berisiko') !== false ? 'warning text-dark' : 'success'); ?>
                                    <span class="badge bg-<?= $badge ?> rounded-pill px-3 py-2 shadow-sm" style="font-size: 0.75rem; min-width: 150px;"><?= strtoupper($lbl) ?></span>
                                </td>
                            </tr>
                    <?php endforeach;
                    endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
    <?php if (!empty($wcss_data)): ?>
        // 1. Grafik Elbow Method
        const ctxElbow = document.getElementById('elbowChart').getContext('2d');
        new Chart(ctxElbow, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($wcss_data)) ?>,
                datasets: [{
                    label: 'Nilai WCSS',
                    data: <?= json_encode(array_values($wcss_data)) ?>,
                    borderColor: '#0d6efd',
                    backgroundColor: 'rgba(13, 110, 253, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'Elbow Method (Penentuan K Optimal)'
                    }
                }
            }
        });

        // 2. Grafik Scatter Plot Sebaran Cluster
        const ctxScatter = document.getElementById('scatterChart').getContext('2d');
        new Chart(ctxScatter, {
            type: 'scatter',
            data: {
                datasets: [{
                        label: 'Sehat',
                        data: <?= json_encode($scatter_data['Sehat']) ?>,
                        backgroundColor: '#198754'
                    },
                    {
                        label: 'Berisiko',
                        data: <?= json_encode($scatter_data['Berisiko']) ?>,
                        backgroundColor: '#ffc107'
                    },
                    {
                        label: 'Toksik',
                        data: <?= json_encode($scatter_data['Toksik']) ?>,
                        backgroundColor: '#dc3545'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Keaktifan (V1)'
                        },
                        min: 1,
                        max: 5
                    },
                    y: {
                        title: {
                            display: true,
                            text: 'Rata-rata Skor'
                        },
                        min: 1,
                        max: 5
                    }
                }
            }
        });

        // 3. Grafik Silhouette Score dengan Garis Metrik Rata-rata
        const ctxSilhouette = document.getElementById('silhouetteChart').getContext('2d');
        const avgScore = <?= $process_logs['avg_silhouette'] ?? 0 ?>;

        new Chart(ctxSilhouette, {
            type: 'bar',
            data: {
                labels: new Array(<?= count($silhouette_data['scores']) ?>).fill(''),
                datasets: [{
                    data: <?= json_encode($silhouette_data['scores']) ?>,
                    backgroundColor: <?= json_encode($silhouette_data['colors']) ?>,
                    barPercentage: 1.0,
                    categoryPercentage: 1.0,
                    borderWidth: 0
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Silhouette Plot',
                        color: '#0d6efd',
                        font: {
                            size: 14,
                            weight: 'bold'
                        }
                    }
                },
                scales: {
                    x: {
                        min: -1,
                        max: 1,
                        title: {
                            display: true,
                            text: 'Silhouette Coefficient Values'
                        },
                        grid: {
                            color: (c) => c.tick.value === 0 ? '#000' : '#e5e5e5'
                        }
                    },
                    y: {
                        display: false
                    }
                }
            },
            plugins: [{
                id: 'avgLine',
                afterDraw: (chart) => {
                    const {
                        ctx,
                        scales: {
                            x,
                            y
                        }
                    } = chart;
                    const xPos = x.getPixelForValue(avgScore);
                    ctx.save();
                    ctx.beginPath();
                    ctx.strokeStyle = 'red';
                    ctx.setLineDash([5, 5]);
                    ctx.lineWidth = 2;
                    ctx.moveTo(xPos, y.top);
                    ctx.lineTo(xPos, y.bottom);
                    ctx.stroke();
                    ctx.fillStyle = 'red';
                    ctx.font = 'bold 12px Arial';
                    ctx.fillText('Rata-rata: ' + avgScore, xPos + 5, y.top + 15);
                    ctx.restore();
                }
            }]
        });
    <?php endif; ?>
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>