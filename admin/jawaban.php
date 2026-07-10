<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

$user = $_SESSION['user'];

// Query diperbarui untuk mengambil teks pernyataan agar muncul di detail komentar
$queryJawaban = "
    SELECT 
        s.nis, 
        s.nama,
        GROUP_CONCAT(CONCAT(j.id_pernyataan, '|', j.nilai, '|', IFNULL(j.komentar, ''), '|', k.teks_pernyataan) ORDER BY j.id_pernyataan ASC SEPARATOR '###') as jawaban_data
    FROM siswa s 
    LEFT JOIN jawaban j ON s.nis = j.nis 
    LEFT JOIN kuesioner k ON j.id_pernyataan = k.id_pernyataan
    GROUP BY s.nis, s.nama
    ORDER BY s.nama ASC
";

$jawabanList = $conn->query($queryJawaban)->fetch_all(MYSQLI_ASSOC);
$questions = $conn->query("SELECT id_pernyataan FROM kuesioner WHERE status = 'aktif' ORDER BY id_pernyataan ASC")->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/layout_header.php';
?>

<style>
    .row-detail { background-color: #f8f9fa; display: none; }
    .comment-box { border-left: 4px solid #dc3545; padding-left: 15px; margin-bottom: 10px; }
    .clickable-row { cursor: pointer; transition: background 0.2s; }
    .clickable-row:hover { background-color: rgba(13, 110, 253, 0.05) !important; }
</style>

<div class="data-card">
    <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
        <h5 class="card-title mb-0">Laporan Pengalaman Responden</h5>
        
        <div class="d-flex gap-2">
            <div class="input-group input-group-sm" style="width: 250px;">
                <span class="input-group-text bg-white"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInputJawaban" class="form-control" placeholder="Cari Nama atau NIS..." onkeyup="filterTableJawaban()">
            </div>
            <button class="btn btn-primary-modern btn-sm" onclick="exportData('excel')">
                <i class="bi bi-file-earmark-excel"></i> Export CSV
            </button>
        </div>
    </div>
    
    <div class="alert alert-warning border-0 shadow-sm small py-2 mt-3">
        <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Tips:</strong> Klik pada baris nama responden untuk melihat detail teks pernyataan dan komentar yang diisi.
    </div>

    <div class="table-responsive mt-3">
        <table class="table table-modern" id="tableJawaban">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Identitas</th>
                    <?php $p_count = 1; foreach ($questions as $q): ?>
                        <th class="text-center">P<?= $p_count++; ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody id="jawabanTbody">
                <?php $no = 1; foreach ($jawabanList as $row): 
                    $safeNis = preg_replace('/[^a-zA-Z0-9]/', '', $row['nis']);
                    $commentsFound = [];
                    $jawabanMap = [];
                    
                    if ($row['jawaban_data']) {
                        $parts = explode('###', $row['jawaban_data']);
                        foreach ($parts as $part) {
                            $d = explode('|', $part);
                            if (count($d) >= 4) {
                                $id = (int)$d[0];
                                $jawabanMap[$id] = $d[1];
                                if (!empty($d[2])) {
                                    $commentsFound[] = ['p' => $id, 'teks' => $d[3], 'isi' => $d[2]];
                                }
                            }
                        }
                    }
                ?>
                    <tr class="clickable-row main-row" data-nis="<?= $safeNis ?>" onclick="toggleDetail('<?= $safeNis ?>')">
                        <td><?= $no++; ?></td>
                        <td class="identity-cell">
                            <div class="fw-bold text-primary name-text"><?= sanitize($row['nama']); ?></div>
                            <small class="text-muted nis-text"><?= sanitize($row['nis']); ?></small>
                            <?php if (!empty($commentsFound)): ?>
                                <span class="badge bg-danger-soft text-danger ms-1" style="font-size: 0.65rem;">
                                    <?= count($commentsFound) ?> Komentar
                                </span>
                            <?php endif; ?>
                        </td>
                        <?php foreach ($questions as $q): 
                            $val = $jawabanMap[(int)$q['id_pernyataan']] ?? '-';
                        ?>
                            <td class="text-center"><?= $val ?></td>
                        <?php endforeach; ?>
                    </tr>

                    <tr id="detail-<?= $safeNis ?>" class="row-detail">
                        <td colspan="<?= count($questions) + 3; ?>" class="p-4">
                            <h6 class="fw-bold text-dark border-bottom pb-2 mb-3">
                                <i class="bi bi-chat-right-quote-fill me-2 text-danger"></i>
                                Detail Pengalaman: <?= sanitize($row['nama']); ?>
                            </h6>
                            <?php if (empty($commentsFound)): ?>
                                <p class="text-muted small">Responden ini tidak memberikan komentar tambahan.</p>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($commentsFound as $cf): ?>
                                        <div class="col-md-6">
                                            <div class="comment-box bg-white p-3 rounded shadow-sm">
                                                <div class="small fw-bold text-primary mb-1">Pernyataan:</div>
                                                <p class="small text-dark mb-2">"<?= sanitize($cf['teks']); ?>"</p>
                                                <div class="small fw-bold text-danger mb-1">Pengalaman Responden:</div>
                                                <p class="mb-0 italic text-secondary">"<?= sanitize($cf['isi']); ?>"</p>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr id="noDataMessage" style="display:none;">
                    <td colspan="<?= count($questions) + 3; ?>" class="text-center py-5 text-muted">
                        <i class="bi bi-search fs-2 d-block mb-2"></i>
                        Data responden tidak ditemukan.
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
    /**
     * Fungsi Filter Tabel Berdasarkan Input
     */
    function filterTableJawaban() {
        const input = document.getElementById('searchInputJawaban');
        const filter = input.value.toUpperCase();
        const tbody = document.getElementById('jawabanTbody');
        const rows = tbody.getElementsByClassName('main-row');
        const noData = document.getElementById('noDataMessage');
        let visibleCount = 0;

        for (let i = 0; i < rows.length; i++) {
            const nameText = rows[i].querySelector('.name-text').textContent || rows[i].querySelector('.name-text').innerText;
            const nisText = rows[i].querySelector('.nis-text').textContent || rows[i].querySelector('.nis-text').innerText;
            const nisId = rows[i].getAttribute('data-nis');
            const detailRow = document.getElementById('detail-' + nisId);

            if (nameText.toUpperCase().indexOf(filter) > -1 || nisText.toUpperCase().indexOf(filter) > -1) {
                rows[i].style.display = "";
                visibleCount++;
            } else {
                rows[i].style.display = "none";
                // Sembunyikan detail jika baris utamanya terfilter keluar
                if(detailRow) detailRow.style.display = "none";
            }
        }
        
        noData.style.display = visibleCount === 0 ? "" : "none";
    }

    function toggleDetail(nis) {
        const detailRow = document.getElementById('detail-' + nis);
        if (detailRow.style.display === 'table-row') {
            detailRow.style.display = 'none';
        } else {
            document.querySelectorAll('.row-detail').forEach(row => row.style.display = 'none');
            detailRow.style.display = 'table-row';
        }
    }

    function exportData(type) {
        if (confirm("Unduh data " + type.toUpperCase() + "?")) {
            window.location.href = 'export.php?type=' + type;
        }
    }
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>