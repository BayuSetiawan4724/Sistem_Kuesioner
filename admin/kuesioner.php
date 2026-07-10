<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');

$user = $_SESSION['user'];
$errors = [];
$success = '';

// Handle CRUD Kuesioner
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_question') {
        $teks = sanitize($_POST['teks_pernyataan'] ?? '');
        $variabel = sanitize($_POST['variabel'] ?? '');
        $status = sanitize($_POST['status'] ?? 'aktif');
        
        if ($teks && in_array($variabel, ['Keaktifan Komunikasi', 'Teman Ngobrol', 'Cara Berkomunikasi', 'Isi Obrolan', 'Perasaan Saat Berkomunikasi'], true)) {
            $stmt = $conn->prepare('INSERT INTO kuesioner (teks_pernyataan, variabel, status) VALUES (?, ?, ?)');
            $stmt->bind_param('sss', $teks, $variabel, $status);
            if ($stmt->execute()) {
                $success = 'Pernyataan berhasil ditambahkan.';
            } else {
                $errors[] = 'Gagal menambahkan pernyataan.';
            }
        } else {
            $errors[] = 'Isi pernyataan dan pilih variabel yang valid.';
        }
    }
    
    if ($action === 'edit_question') {
        $id = (int)($_POST['id_pernyataan'] ?? 0);
        $teks = sanitize($_POST['teks_pernyataan'] ?? '');
        $variabel = sanitize($_POST['variabel'] ?? '');
        $status = sanitize($_POST['status'] ?? 'aktif');
        
        if ($id > 0 && $teks && in_array($variabel, ['Keaktifan Komunikasi', 'Teman Ngobrol', 'Cara Berkomunikasi', 'Isi Obrolan', 'Perasaan Saat Berkomunikasi'], true)) {
            $stmt = $conn->prepare('UPDATE kuesioner SET teks_pernyataan = ?, variabel = ?, status = ? WHERE id_pernyataan = ?');
            $stmt->bind_param('sssi', $teks, $variabel, $status, $id);
            if ($stmt->execute()) {
                $success = 'Pernyataan berhasil diperbarui.';
            } else {
                $errors[] = 'Gagal memperbarui pernyataan.';
            }
        }
    }
    
    if ($action === 'delete_question') {
        $id = (int)($_POST['id_pernyataan'] ?? 0);
        if ($id > 0) {
            $stmt = $conn->prepare('DELETE FROM kuesioner WHERE id_pernyataan = ?');
            $stmt->bind_param('i', $id);
            if ($stmt->execute()) {
                $success = 'Pernyataan berhasil dihapus.';
            }
        }
    }
    
    if ($action === 'toggle_status') {
        $id = (int)($_POST['id_pernyataan'] ?? 0);
        $newStatus = sanitize($_POST['status'] ?? 'aktif');
        if ($id > 0) {
            $stmt = $conn->prepare('UPDATE kuesioner SET status = ? WHERE id_pernyataan = ?');
            $stmt->bind_param('si', $newStatus, $id);
            $stmt->execute();
        }
    }
}

// Fetch data kuesioner
$kuesionerList = $conn->query('SELECT * FROM kuesioner ORDER BY id_pernyataan DESC')->fetch_all(MYSQLI_ASSOC);

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

<div class="data-card mb-4">
    <div class="card-header">
        <h5 class="card-title mb-0">Tambah Pernyataan Kuesioner</h5>
    </div>
    <form method="POST" class="row g-3">
        <input type="hidden" name="action" value="add_question">
        <div class="col-12">
            <label class="form-label">Teks Pernyataan</label>
            <textarea name="teks_pernyataan" class="form-control" rows="3" placeholder="Masukkan teks pernyataan..." required></textarea>
        </div>
        <div class="col-md-6">
            <label class="form-label">Variabel</label>
            <select name="variabel" class="form-select" required>
                <option value="">Pilih variabel</option>
                <option value="Keaktifan Komunikasi">Keaktifan Komunikasi</option>
                <option value="Teman Ngobrol">Teman Ngobrol</option>
                <option value="Cara Berkomunikasi">Cara Berkomunikasi</option>
                <option value="Isi Obrolan">Isi Obrolan</option>
                <option value="Perasaan Saat Berkomunikasi">Perasaan Saat Berkomunikasi</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
                <option value="aktif" selected>Aktif</option>
                <option value="nonaktif">Nonaktif</option>
            </select>
        </div>
        <div class="col-12">
            <button type="submit" class="btn btn-success-modern">
                <i class="bi bi-plus-circle"></i> Tambah Pernyataan
            </button>
        </div>
    </form>
</div>

<div class="data-card">
    <div class="card-header">
        <h5 class="card-title mb-0">Daftar Pernyataan Kuesioner</h5>
        <span class="badge bg-primary"><?php echo count($kuesionerList); ?> pernyataan</span>
    </div>
    <div class="table-responsive">
        <table class="table table-modern">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Pernyataan</th>
                    <th>Variabel</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php $no = 1; foreach ($kuesionerList as $k): ?>
                <tr>
                    <td><?php echo $no++; ?></td>
                    <td><?php echo sanitize($k['teks_pernyataan']); ?></td>
                    <td><span class="badge bg-info"><?php echo ucfirst(sanitize($k['variabel'])); ?></span></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="toggle_status">
                            <input type="hidden" name="id_pernyataan" value="<?php echo (int)$k['id_pernyataan']; ?>">
                            <input type="hidden" name="status" value="<?php echo $k['status'] === 'aktif' ? 'nonaktif' : 'aktif'; ?>">
                            <button type="submit" class="btn btn-sm <?php echo $k['status'] === 'aktif' ? 'btn-success-modern' : 'btn-secondary'; ?>">
                                <?php echo $k['status'] === 'aktif' ? 'Aktif' : 'Nonaktif'; ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <form method="POST" onsubmit="return confirm('Hapus pernyataan ini?');" class="d-inline">
                            <input type="hidden" name="action" value="delete_question">
                            <input type="hidden" name="id_pernyataan" value="<?php echo (int)$k['id_pernyataan']; ?>">
                            <button type="submit" class="btn btn-sm btn-danger-modern">
                                <i class="bi bi-trash"></i> Hapus
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/layout_footer.php'; ?>
