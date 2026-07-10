<?php
require_once __DIR__ . '/includes/init.php';

$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = sanitize($_POST['nis'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $nama = sanitize($_POST['nama'] ?? '');
    $kelas = sanitize($_POST['kelas'] ?? '');
    $usia = (int)($_POST['usia'] ?? 0);
    $jenisKelamin = sanitize($_POST['jenis_kelamin'] ?? '');

    if ($nis === '' || $password === '' || $confirm === '' || $nama === '' || $kelas === '' || $usia <= 0 || !in_array($jenisKelamin, ['L', 'P'], true)) {
        $errors[] = 'Semua field wajib diisi dengan benar.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Password dan konfirmasi tidak sama.';
    }

    if (!$errors) {
        // Pastikan NIS unik di tabel users.
        $check = $conn->prepare('SELECT nis FROM users WHERE nis = ? LIMIT 1');
        $check->bind_param('s', $nis);
        $check->execute();
        $check->store_result();
        if ($check->num_rows > 0) {
            $errors[] = 'NIS sudah terdaftar, gunakan NIS lain.';
        } else {
            $conn->begin_transaction();
            try {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $role = 'user';
                $insertUser = $conn->prepare('INSERT INTO users (nis, password, role, created_at) VALUES (?, ?, ?, NOW())');
                $insertUser->bind_param('sss', $nis, $hashed, $role);
                $insertUser->execute();

                $insertSiswa = $conn->prepare('INSERT INTO siswa (nis, nama, kelas, usia, jenis_kelamin) VALUES (?, ?, ?, ?, ?)');
                $insertSiswa->bind_param('sssis', $nis, $nama, $kelas, $usia, $jenisKelamin);
                $insertSiswa->execute();

                $conn->commit();
                header('Location: ' . url('login.php?status=registered'));
                exit;
            } catch (Exception $e) {
                $conn->rollback();
                $errors[] = 'Terjadi kesalahan saat menyimpan data: ' . $e->getMessage();
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="glass-card p-4">
                <h3 class="fw-bold mb-3 text-center">Registrasi Akun</h3>
                <p class="text-secondary text-center mb-4">Pastikan NIS unik untuk menghindari duplikasi data.</p>

                <?php if ($errors): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $err): ?>
                                <li><?php echo $err; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php elseif ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="POST" action="<?php echo url('register.php'); ?>" class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">NIS</label>
                        <input type="text" name="nis" class="form-control" placeholder="Nomor Induk Siswa" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control" placeholder="Nama siswa" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Kelas</label>
                        <input type="text" name="kelas" class="form-control" placeholder="XI RPL 1" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Usia</label>
                        <input type="number" name="usia" class="form-control" min="10" max="25" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-select" required>
                            <option value="">Pilih</option>
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Konfirmasi Password</label>
                        <input type="password" name="confirm_password" class="form-control" required>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-success w-100">Buat Akun</button>
                    </div>
                    <div class="text-center">
                        <span class="text-secondary">Sudah punya akun?</span>
                        <a href="<?php echo url('login.php'); ?>">Login</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

