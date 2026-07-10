<?php
require_once __DIR__ . '/init.php';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kuesioner SMK Mutiara Ilmu</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <?php if (strpos($_SERVER['PHP_SELF'], 'admin/') !== false): ?>
        <link rel="stylesheet" href="<?php echo url('assets/css/admin.css'); ?>">
    <?php else: ?>
        <link rel="stylesheet" href="<?php echo url('assets/css/styles.css'); ?>">
    <?php endif; ?>
</head>
<body class="bg-light" style="font-family: 'Inter', sans-serif;">

<?php 
// Hanya tampilkan Navbar Utama jika BUKAN di folder admin
if (strpos($_SERVER['PHP_SELF'], 'admin/') === false): 
?>
<nav class="navbar navbar-expand-lg bg-white shadow-sm sticky-top">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="<?php echo url(); ?>">MutiaraSurvey</a>
        
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto align-items-lg-center">
                <li class="nav-item"><a class="nav-link" href="<?php echo url(); ?>">Beranda</a></li>
                <li class="nav-item"><a class="nav-link" href="<?php echo url('login.php'); ?>">Login</a></li>
            </ul>
        </div>
    </div>
</nav>
<?php endif; ?>
<main>