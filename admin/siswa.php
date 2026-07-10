<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

$user = $_SESSION['user'];
$errors = [];
$success = '';

// Handle CRUD Siswa (Tetap sama seperti kode asli Anda)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_siswa') {
        $nis = sanitize($_POST['nis'] ?? '');
        $nama = sanitize($_POST['nama'] ?? '');
        $kelas = sanitize($_POST['kelas'] ?? '');
        $usia = (int)($_POST['usia'] ?? 0);
        $jenisKelamin = sanitize($_POST['jenis_kelamin'] ?? '');
        
        if ($nis && $nama && $kelas && $usia > 0 && in_array($jenisKelamin, ['L', 'P'])) {
            $check = $conn->prepare('SELECT nis FROM siswa WHERE nis = ?');
            $check->bind_param('s', $nis);
            $check->execute();
            if ($check->get_result()->num_rows > 0) {
                $errors[] = 'NIS sudah terdaftar.';
            } else {
                $stmt = $conn->prepare('INSERT INTO siswa (nis, nama, kelas, usia, jenis_kelamin) VALUES (?, ?, ?, ?, ?)');
                $stmt->bind_param('sssis', $nis, $nama, $kelas, $usia, $jenisKelamin);
                if ($stmt->execute()) {
                    $success = 'Data siswa berhasil ditambahkan.';
                } else {
                    $errors[] = 'Gagal menambahkan data siswa.';
                }
            }
        } else {
            $errors[] = 'Semua field wajib diisi dengan benar.';
        }
    }
    
    if ($action === 'delete_siswa') {
        $nis = sanitize($_POST['nis'] ?? '');
        if ($nis) {
            $stmt = $conn->prepare('DELETE FROM siswa WHERE nis = ?');
            $stmt->bind_param('s', $nis);
            if ($stmt->execute()) {
                $success = 'Data siswa berhasil dihapus.';
            }
        }
    }
}

// Fetch data siswa
$siswaList = $conn->query('SELECT * FROM siswa ORDER BY nama ASC')->fetch_all(MYSQLI_ASSOC);

require_once __DIR__ . '/layout_header.php';
?>

<?php if ($errors): ?>
    <div class="alert alert-danger">
        <ul class="mb-0">
            <?php foreach ($errors as $err): ?>
                <li><?php echo $err; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if ($success): ?>
    <div class="alert alert-success"><?php echo $success; ?></div>
<?php endif; ?>

<div class="data-card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <div>
            <h5 class="card-title mb-0">Daftar Data Siswa</h5>
            <span class="badge bg-primary mt-1"><?php echo count($siswaList); ?> siswa terdaftar</span>
        </div>
        <div style="width: 300px;">
            <div class="input-group">
                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search"></i></span>
                <input type="text" id="searchInputSiswa" class="form-control border-start-0" placeholder="Cari NIS atau Nama..." onkeyup="filterTableSiswa()">
            </div>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-modern" id="tableDataSiswa">
            <thead>
                <tr>
                    <th>No</th>
                    <th>NIS</th>
                    <th>Nama</th>
                    <th>Kelas</th>
                    <th>Usia</th>
                    <th>Jenis Kelamin</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($siswaList as $siswa): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><strong><?php echo sanitize($siswa['nis']); ?></strong></td>
                    <td><?php echo sanitize($siswa['nama']); ?></td>
                    <td><?php echo sanitize($siswa['kelas']); ?></td>
                    <td><?php echo (int)$siswa['usia']; ?></td>
                    <td><?php echo sanitize($siswa['jenis_kelamin']); ?></td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Hapus data siswa ini?');" class="d-inline">
                            <input type="hidden" name="action" value="delete_siswa">
                            <input type="hidden" name="nis" value="<?php echo sanitize($siswa['nis']); ?>">
                            <button type="submit" class="btn btn-sm btn-danger-modern">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <tr id="noDataRow" style="display: none;">
                    <td colspan="7" class="text-center py-4 text-muted">Data siswa tidak ditemukan.</td>
                </tr>
            </tbody>
        </table>
    </div>
</div>

<script>
/**
 * Fungsi Pencarian Data Siswa
 * Mencari berdasarkan NIS (Kolom 1) atau Nama (Kolom 2)
 */
function filterTableSiswa() {
    const input = document.getElementById('searchInputSiswa');
    const filter = input.value.toUpperCase();
    const table = document.getElementById('tableDataSiswa');
    const tr = table.getElementsByTagName('tr');
    const noDataRow = document.getElementById('noDataRow');
    let dataFound = false;

    // Mulai dari i=1 untuk melewati header tabel
    for (let i = 1; i < tr.length; i++) {
        // Skip baris "no data" agar tidak ikut terfilter
        if (tr[i].id === 'noDataRow') continue;

        const tdNIS = tr[i].getElementsByTagName('td')[1];
        const tdNama = tr[i].getElementsByTagName('td')[2];
        
        if (tdNIS || tdNama) {
            const txtNIS = tdNIS.textContent || tdNIS.innerText;
            const txtNama = tdNama.textContent || tdNama.innerText;
            
            if (txtNIS.toUpperCase().indexOf(filter) > -1 || txtNama.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = "";
                dataFound = true;
            } else {
                tr[i].style.display = "none";
            }
        }
    }
    
    // Tampilkan pesan jika tidak ada data yang cocok
    noDataRow.style.display = dataFound ? "none" : "";
}
</script>

<?php require_once __DIR__ . '/layout_footer.php'; ?>