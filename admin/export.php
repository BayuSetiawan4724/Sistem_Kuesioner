<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

$type = $_GET['type'] ?? 'excel';

// Ambil pertanyaan aktif untuk header
$questionsQuery = $conn->query("SELECT id_pernyataan FROM kuesioner WHERE status='aktif' ORDER BY id_pernyataan ASC");
$questions = $questionsQuery->fetch_all(MYSQLI_ASSOC);
$totalQ = count($questions);

// Query Utama: Mengambil Nilai dan Komentar
$queryData = "
    SELECT 
        s.nis, 
        s.nama,
        GROUP_CONCAT(CONCAT(j.id_pernyataan, '|', j.nilai, '|', IFNULL(j.komentar, '')) ORDER BY j.id_pernyataan ASC SEPARATOR '###') as jawaban_data
    FROM siswa s 
    JOIN jawaban j ON s.nis = j.nis 
    GROUP BY s.nis, s.nama
    ORDER BY s.nama ASC
";
$result = $conn->query($queryData);

// --- LOGIKA UNTUK PDF (Mode Cetak) ---
if ($type === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="id">
    <head>
        <title>Laporan Hasil Kuesioner & Pengalaman Responden</title>
        <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
        <style>
            @media print { .btn-print { display: none; } @page { size: landscape; } }
            body { font-size: 10px; padding: 20px; }
            .komentar-text { font-style: italic; color: #666; font-size: 9px; }
        </style>
    </head>
    <body onload="window.print()">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h4 class="mb-0">Laporan Jawaban & Pengalaman Responden</h4>
                <p class="text-muted mb-0">Penelitian Perilaku Pemain Roblox - SMK Mutiara Ilmu</p>
            </div>
            <button onclick="window.history.back()" class="btn btn-secondary btn-print">Kembali</button>
        </div>
        <table class="table table-bordered border-dark table-sm">
            <thead class="table-light text-center">
                <tr>
                    <th>No</th><th>NIS</th><th>Nama Responden</th>
                    <?php for($i = 1; $i <= $totalQ; $i++) echo "<th>P$i</th>"; ?>
                </tr>
            </thead>
            <tbody>
                <?php 
                $no = 1; 
                while ($row = $result->fetch_assoc()): 
                    $jawabanMap = [];
                    if ($row['jawaban_data']) {
                        $parts = explode('###', $row['jawaban_data']);
                        foreach ($parts as $part) {
                            $d = explode('|', $part);
                            if(count($d) >= 3) $jawabanMap[(int)$d[0]] = ['v' => $d[1], 'c' => $d[2]];
                        }
                    }
                ?>
                <tr>
                    <td class="text-center"><?= $no++ ?></td>
                    <td><?= $row['nis'] ?></td>
                    <td><strong><?= $row['nama'] ?></strong></td>
                    <?php foreach ($questions as $q): 
                        $id = (int)$q['id_pernyataan'];
                        $n = $jawabanMap[$id]['v'] ?? '-';
                        $c = $jawabanMap[$id]['c'] ?? '';
                    ?>
                        <td class="text-center">
                            <?= $n ?>
                            <?php if($c): ?><br><span class="komentar-text">[Obs: <?= $c ?>]</span><?php endif; ?>
                        </td>
                    <?php endforeach; ?>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
    exit();
}

// --- LOGIKA UNTUK EXCEL (CSV) ---
if (ob_get_level()) ob_end_clean();

$filename = "Data_Lengkap_Kuesioner_" . date('Y-m-d_H-i') . ".csv";
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');

$output = fopen('php://output', 'w');

// Header Excel (Menambahkan kolom komentar di samping setiap P)
$header = ['No', 'NIS', 'Nama'];
for ($i = 1; $i <= $totalQ; $i++) { 
    $header[] = 'Nilai P' . $i; 
    $header[] = 'Komentar P' . $i; 
}
fputcsv($output, $header);

// Isi Data
$no = 1;
while ($row = $result->fetch_assoc()) {
    $line = [$no++, $row['nis'], $row['nama']];
    
    $jawabanMap = [];
    if ($row['jawaban_data']) {
        $parts = explode('###', $row['jawaban_data']);
        foreach ($parts as $part) {
            $d = explode('|', $part);
            if(count($d) >= 3) $jawabanMap[(int)$d[0]] = ['v' => $d[1], 'c' => $d[2]];
        }
    }

    foreach ($questions as $q) {
        $id = (int)$q['id_pernyataan'];
        $line[] = $jawabanMap[$id]['v'] ?? '-';
        $line[] = $jawabanMap[$id]['c'] ?? '';
    }
    fputcsv($output, $line);
}

fclose($output);
exit();