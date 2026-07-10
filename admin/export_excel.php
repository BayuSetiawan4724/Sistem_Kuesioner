<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

// Header agar browser mendownload file sebagai Excel
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=Dataset_Preprocessing_KMeans.xls");

// Ambil data hasil preprocessing (skor variabel desimal)
$query = "SELECT hs.*, s.nama FROM hasil_skor hs JOIN siswa s ON hs.nis = s.nis ORDER BY hs.id_skor ASC";
$res = $conn->query($query);
?>

<table border="1">
    <thead>
        <tr>
            <th style="background-color: #f2f2f2;">No</th>
            <th style="background-color: #f2f2f2;">NIS</th>
            <th style="background-color: #f2f2f2;">Nama Siswa</th>
            <th style="background-color: #f2f2f2;">V1 (Keaktifan)</th>
            <th style="background-color: #f2f2f2;">V2 (Teman)</th>
            <th style="background-color: #f2f2f2;">V3 (Cara)</th>
            <th style="background-color: #f2f2f2;">V4 (Isi)</th>
            <th style="background-color: #f2f2f2;">V5 (Emosi)</th>
            <th style="background-color: #f2f2f2;">Rata-Rata Total</th>
        </tr>
    </thead>
    <tbody>
        <?php $no=1; while($row = $res->fetch_assoc()): ?>
        <tr>
            <td><?= $no++ ?></td>
            <td>'<?= $row['nis'] ?></td> <td><?= $row['nama'] ?></td>
            <td><?= number_format($row['skor_keaktifan_komunikasi'], 2) ?></td>
            <td><?= number_format($row['skor_teman_ngobrol'], 2) ?></td>
            <td><?= number_format($row['skor_cara_berkomunikasi'], 2) ?></td>
            <td><?= number_format($row['skor_isi_obrolan'], 2) ?></td>
            <td><?= number_format($row['skor_perasaan_saat_berkomunikasi'], 2) ?></td>
            <td style="font-weight: bold;"><?= number_format($row['skor_total'], 2) ?></td>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>