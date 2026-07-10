<?php
require_once __DIR__ . '/includes/init.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Normalisasi input dari form
    $nis = sanitize($_POST['nis'] ?? '');
    $password = $_POST['password'] ?? '';
    // Kecilkan huruf role dari form untuk mempermudah perbandingan
    $requestedRole = strtolower(trim($_POST['role'] ?? ''));

    if ($nis === '' || $password === '' || empty($requestedRole)) {
        $error = 'NIS, role, dan password wajib diisi.';
    } else {
        // Ambil data user berdasarkan NIS
        $stmt = $conn->prepare('
    SELECT u.id_user, u.nis, u.password, u.role, s.nama 
    FROM users u 
    LEFT JOIN siswa s ON u.nis = s.nis 
    WHERE u.nis = ? 
    LIMIT 1
');
        $stmt->bind_param('s', $nis);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if ($user) {
            $dbRole = strtolower(trim($user['role']));
            $validPass = (password_verify($password, $user['password']) || $password === $user['password']);

            if ($dbRole === $requestedRole && $validPass) {
                // 2. Sekarang $user['nama'] sudah memiliki data dari DB
                $_SESSION['user'] = [
                    'id_user' => $user['id_user'],
                    'nis' => $user['nis'],
                    'nama' => $user['nama'],
                    'role' => $user['role'],
                ];

                if ($dbRole === 'admin') {
                    header('Location: ' . url('admin/dashboard.php'));
                } else {
                    header('Location: ' . url('user/dashboard.php'));
                }
                exit;
            } else {
                $error = 'NIS, role, atau password salah.';
            }
        } else {
            $error = 'Akun tidak ditemukan.';
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-5">
            <div class="glass-card p-4">
                <h3 class="fw-bold mb-3 text-center">Login</h3>
                <p class="text-secondary text-center mb-4">Masuk sebagai admin atau siswa.</p>
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST" action="<?php echo url('login.php'); ?>">
                    <div class="mb-3">
                        <label class="form-label">NIS</label>
                        <input type="text" name="nis" class="form-control" placeholder="Masukkan NIS" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Password</label>
                        <input type="password" name="password" class="form-control" placeholder="Masukkan password" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Masuk Sebagai</label>
                        <select name="role" class="form-select" required>
                            <option value="">Pilih role</option>
                            <option value="admin">Admin</option>
                            <option value="user">User/Siswa</option>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary w-100">Masuk</button>
                    <div class="text-center mt-3">
                        <span class="text-secondary">Belum punya akun?</span>
                        <a href="<?php echo url('register.php'); ?>">Daftar</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>