<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('user');
require_once __DIR__ . '/../includes/header.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-6">
            <div class="glass-card p-5 text-center shadow-lg border-0 rounded-5 bg-white">
                <div class="mb-4">
                    <div class="display-1 text-success animate__animated animate__bounceIn">✅</div>
                </div>
                <h2 class="fw-bold mb-3">Terima Kasih!</h2>
                <p class="text-secondary fs-5 mb-4">
                    Kontribusi Anda sangat berharga. Jawaban kuesioner Anda telah berhasil kami simpan dalam sistem.
                </p>
                <hr class="my-4 opacity-25">
                <div class="d-grid">
                    <a href="<?php echo url('index.php'); ?>" class="btn btn-primary btn-lg rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-box-arrow-right"></i> Keluar & Kembali ke Beranda
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>