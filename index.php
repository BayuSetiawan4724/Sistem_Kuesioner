<?php require_once __DIR__ . '/includes/header.php';
// Query untuk mengambil jumlah siswa terdaftar
$totalSiswa = $conn->query('SELECT COUNT(*) AS total FROM siswa')->fetch_assoc()['total'] ?? 0;

// Query untuk mengambil jumlah pernyataan yang berstatus aktif
$totalKuesionerAktif = $conn->query("SELECT COUNT(*) AS total FROM kuesioner WHERE status = 'aktif'")->fetch_assoc()['total'] ?? 0;
?>

<section class="hero py-5">
    <div class="container py-4">
        <div class="row align-items-center">
            <div class="col-lg-6">
                <span class="badge bg-primary-soft text-primary mb-3 px-3 py-2">Penelitian Perilaku Digital & Psikologi Remaja</span>
                
                <h1 class="fw-bold mb-3" style="font-size: 2.2rem; line-height: 1.2;">
                    ANALISIS PERILAKU PEMAIN ROBLOX UNTUK MENGIDENTIFIKASI POLA KOMUNIKASI PADA REMAJA
                </h1>
                
                <p class="text-secondary mb-4 fs-5">
                    Selamat datang di platform pengumpulan data kuesioner digital. Penelitian ini bertujuan untuk memetakan bagaimana aktivitas di platform Roblox memengaruhi kecenderungan pola komunikasi sosial. 
                </p>
                
                <p class="text-muted small mb-4 italic">
                    *Data dikumpulkan dari responden terpilih di lingkungan SMK Mutiara Ilmu sebagai subjek penelitian ilmiah.
                </p>

                <div class="d-flex flex-wrap gap-2">
                    <a class="btn btn-primary btn-lg px-4 shadow-sm" href="<?php echo url('login.php'); ?>">Mulai Kuesioner</a>
                    <a class="btn btn-outline-primary btn-lg px-4" href="<?php echo url('register.php'); ?>">Registrasi Responden</a>
                </div>
            </div>
            
            <div class="col-lg-6 mt-4 mt-lg-0">
                <div class="glass-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-semibold mb-0">Progress Pengumpulan Data</h5>
                        <i class="bi bi-graph-up-arrow text-primary"></i>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-4 shadow-sm h-100 border-start border-4 border-primary">
                                <div class="text-secondary small">Total Responden</div>
                                <div class="fs-4 fw-bold text-primary"><?php echo $totalSiswa; ?></div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-white rounded-4 shadow-sm h-100 border-start border-4 border-success">
                                <div class="text-secondary small">Instrumen Aktif</div>
                                <div class="fs-4 fw-bold text-success"><?php echo $totalKuesionerAktif; ?></div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4 p-3 bg-light rounded-3">
                        <small class="text-secondary d-block">
                            <strong>Mengapa data ini penting?</strong><br>
                            Hasil analisis menggunakan algoritma K-Means akan membantu memahami dampak interaksi virtual terhadap kesehatan mental dan kecakapan komunikasi di dunia nyata.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>


<?php require_once __DIR__ . '/includes/footer.php'; ?>