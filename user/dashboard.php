<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('user');
$user = $_SESSION['user'];

$success = '';
$errors = [];

// Ambil pernyataan aktif dan urutkan berdasarkan variabel
$questions = $conn->query("SELECT id_pernyataan, teks_pernyataan, variabel FROM kuesioner WHERE status = 'aktif' ORDER BY variabel ASC, id_pernyataan ASC")->fetch_all(MYSQLI_ASSOC);

// Cek apakah siswa sudah pernah mengirimkan jawaban
$checkAns = $conn->prepare('SELECT id_jawaban FROM jawaban WHERE nis = ? LIMIT 1');
$checkAns->bind_param('s', $user['nis']);
$checkAns->execute();
if ($checkAns->get_result()->num_rows > 0) {
    header('Location: ' . url('user/thanks.php'));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $questions) {
    $conn->begin_transaction();
    try {
        // Pastikan kolom di VALUES ada 4 tanda tanya
        $insertStmt = $conn->prepare('INSERT INTO jawaban (nis, id_pernyataan, nilai, komentar) VALUES (?, ?, ?, ?)');
        
        foreach ($questions as $q) {
            $field = 'q_' . $q['id_pernyataan'];
            $commField = 'comment_' . $q['id_pernyataan']; // Nama field sesuai dengan textarea
            
            $val = isset($_POST[$field]) ? (int)$_POST[$field] : 0;
            // Ambil komentar jika ada, jika tidak ada set NULL
            $comment = (isset($_POST[$commField]) && !empty($_POST[$commField])) ? sanitize($_POST[$commField]) : null;
            
            if ($val < 1 || $val > 5) {
                throw new Exception('Mohon isi semua pernyataan (skala 1-5) yang tersedia.');
            }
            
            // PENTING: Harus ada 4 tipe data "siis" dan 4 variabel
            // s = nis (string), i = id (int), i = nilai (int), s = komentar (string/null)
            $insertStmt->bind_param('siis', $user['nis'], $q['id_pernyataan'], $val, $comment);
            $insertStmt->execute();
        }
        
        $conn->commit();
        header('Location: ' . url('user/thanks.php')); // Pastikan URL benar
        exit;
    } catch (Exception $e) {
        $conn->rollback();
        $errors[] = $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
    .category-divider {
        background: linear-gradient(90deg, #0d6efd 0%, #f8f9fa 100%);
        color: white;
        padding: 10px 20px;
        border-radius: 50px 0 0 50px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-top: 2rem;
        margin-bottom: 1.5rem;
        font-size: 0.9rem;
    }

    .option-circle {
        cursor: pointer;
        width: 45px;
        height: 45px;
        border: 2px solid #dee2e6;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.2s ease;
        font-weight: 600;
    }

    .form-check-input:checked+.option-circle {
        background-color: #0d6efd;
        border-color: #0d6efd;
        color: white;
        transform: scale(1.1);
        box-shadow: 0 4px 10px rgba(13, 110, 253, 0.3);
    }

    .form-check-input {
        display: none;
    }
</style>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-5">
               <div>
                    <h2 class="fw-bold text-dark">Kuesioner Responden</h2>
                    <p class="text-muted">Halo👋, <span class="text-primary fw-semibold"><?php echo sanitize($user['nama'] ?? 'Siswa'); ?></span>. Silahkan isi kuesioner di bawah ini dengan jujur sesuai pengalaman Anda bermain Roblox.Pendapat Anda sangat berarti bagi kami.Terimakasihh🤗</p>
                </div>
                <a class="btn btn-outline-danger btn-sm rounded-pill px-3" href="<?php echo url('logout.php'); ?>">Logout</a>
            </div>

            <div class="alert alert-primary border-0 shadow-sm rounded-4 p-4 mb-4 bg-white">
                <div class="d-flex align-items-center">
                    <div class="fs-2 me-3">💡</div>
                    <div>
                        <h6 class="fw-bold mb-1">Panduan Skala Penilaian:</h6>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <span class="badge rounded-pill bg-light text-dark border p-2 px-3">1: Sangat Tidak Setuju</span>
                            <span class="badge rounded-pill bg-light text-dark border p-2 px-3">2: Tidak Setuju</span>
                            <span class="badge rounded-pill bg-light text-dark border p-2 px-3">3: Netral</span>
                            <span class="badge rounded-pill bg-light text-dark border p-2 px-3">4: Setuju</span>
                            <span class="badge rounded-pill bg-light text-dark border p-2 px-3">5: Sangat Setuju</span>
                        </div>
                    </div>
                </div>
            </div>

            <form method="POST">
                <?php
                $currentCategory = '';
                foreach ($questions as $index => $q):
                    $nomorSekarang = $index + 1;
                    if ($currentCategory !== $q['variabel']):
                        $currentCategory = $q['variabel'];
                ?>
                        <div class="category-divider shadow-sm">
                            <i class="bi bi-bookmark-fill me-2"></i> Variabel: <?php echo sanitize($currentCategory); ?>
                        </div>
                    <?php endif; ?>

                    <div class="card border-0 shadow-sm rounded-4 mb-3 animate__animated animate__fadeInUp">
                        <div class="card-body p-4">
                            <div class="row align-items-center">
                                <div class="col-md-7">
                                    <p class="fs-5 mb-0 text-dark"><strong><?php echo ($index + 1); ?>.</strong> <?php echo sanitize($q['teks_pernyataan']); ?></p>
                                </div>
                                <div class="col-md-5 mt-3 mt-md-0">
                                    <div class="d-flex justify-content-md-end gap-3">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <div class="form-check p-0 m-0">
                                                <input class="form-check-input" type="radio" name="q_<?php echo $q['id_pernyataan']; ?>" id="q_<?php echo $q['id_pernyataan']; ?>_<?php echo $i; ?>" value="<?php echo $i; ?>" required>
                                                <label class="option-circle" for="q_<?php echo $q['id_pernyataan']; ?>_<?php echo $i; ?>">
                                                    <?php echo $i; ?>
                                                </label>
                                            </div>
                                        <?php endfor; ?>
                                    </div>
                                </div>
                            </div>

                             <?php
                            // Logika: Kolom komentar tidak muncul pada nomor 2, 4, 5, 7, 8, 13
                            $nomorDilarang = [1, 2, 3, 4, 5, 7, 8, 9, 13, 15];
                            $showComment = !in_array($nomorSekarang, $nomorDilarang);

                            if ($showComment):
                                $teks = strtolower($q['teks_pernyataan']);
                                // Placeholder default menjurus ke pengalaman buruk
                                $placeholder = "Contoh: Ada pemain yang terus memaksa minta ID sosial media saya meski sudah saya tolak...";

                                if (strpos($teks, 'luar') !== false || strpos($teks, 'sosmed') !== false || strpos($teks, 'discord') !== false || strpos($teks, 'platform luar') !== false) {
                                    $placeholder = "Contoh: Saya pernah diajak pindah ke Discord/WA lalu dikirimi pesan atau gambar yang melecehkan dan membuat saya tidak nyaman...";
                                } elseif (strpos($teks, 'ajak') !== false || strpos($teks, 'bertemu') !== false) {
                                    $placeholder = "Contoh: Seseorang mengajak saya bertemu langsung di dunia nyata dengan iming-iming hadiah, padahal saya tidak mengenalnya...";
                                } elseif (strpos($teks, 'pribadi') !== false || strpos($teks, 'kontak') !== false) {
                                    $placeholder = "Contoh: Ada pemain yang menanyakan alamat rumah atau sekolah saya dengan alasan ingin mengirim hadiah atau uang...";
                                } elseif (strpos($teks, 'marah') !== false || strpos($teks, 'kasar') !== false || strpos($teks, 'ejekan') !== false) {
                                    $placeholder = "Contoh: Seseorang terus menerus mengejek fisik saya di chat pribadi sampai saya merasa tertekan dan takut untuk bermain...";
                                }
                            ?>
                                <div class="mt-4 pt-3 border-top">
                                    <label class="form-label small fw-bold text-danger">
                                        <i class="bi bi-exclamation-octagon-fill me-1"></i> Ceritakan pengalaman detail Anda :
                                    </label>
                                    <textarea name="comment_<?php echo $q['id_pernyataan']; ?>"
                                        class="form-control rounded-3 border-danger-subtle shadow-sm"
                                        style="background-color: #fff8f8;"
                                        placeholder="<?php echo $placeholder; ?>"
                                        rows="3"></textarea>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>

                <div class="text-center mt-5 mb-5">
                    <button type="submit" class="btn btn-success btn-lg px-5 py-3 rounded-pill fw-bold shadow">
                        <i class="bi bi-send-fill me-2"></i> Kirim Jawaban Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>