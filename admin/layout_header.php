<?php
require_once __DIR__ . '/../includes/init.php';
requireRole('admin');
$user = $_SESSION['user'];
$currentPage = basename($_SERVER['PHP_SELF'], '.php');

// --- LOGIKA JUDUL DINAMIS UNTUK HEADER ---
$currentAlgo = $_GET['algo'] ?? $_POST['algorithm_type'] ?? 'kmeans';
$algoTitles = [
    'kmeans'       => 'K-Means',
    'kmedoids'     => 'K-Medoids',
    'dbscan'       => 'DBSCAN',
    'hierarchical' => 'Hierarchical'
];

// Logika penentuan judul halaman
if ($currentPage === 'clustering') {
    // Jika di halaman clustering, ambil nama dari array di atas
    $displayTitle = "Clustering " . ($algoTitles[$currentAlgo] ?? 'K-Means');
} else {
    // Jika di halaman lain (dashboard, dll), gunakan nama halaman standar
    $displayTitle = ucfirst($currentPage);
}
?>
<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $displayTitle; ?> Admin - Sistem Kuesioner</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="<?php echo url('assets/css/admin.css'); ?>">
</head>

<body class="admin-body">
    <aside class="admin-sidebar" id="adminSidebar">
        <div class="sidebar-header">
            <div class="d-flex align-items-center gap-2">
                <div class="logo-icon">
                    <i class="bi bi-clipboard-data"></i>
                </div>
                <span class="brand-text">MutiaraSurvey</span>
            </div>
        </div>

        <nav class="sidebar-nav">
            <div class="nav-section">
                <div class="nav-label">MENU</div>

                <a href="<?php echo url('admin/dashboard.php'); ?>" class="nav-item <?php echo $currentPage === 'dashboard' ? 'active' : ''; ?>">
                    <i class="bi bi-speedometer2"></i>
                    <span>Dashboard</span>
                </a>

                <a href="<?php echo url('admin/siswa.php'); ?>" class="nav-item <?php echo $currentPage === 'siswa' ? 'active' : ''; ?>">
                    <i class="bi bi-people"></i>
                    <span>Data Siswa</span>
                </a>

                <a href="<?php echo url('admin/kuesioner.php'); ?>" class="nav-item <?php echo $currentPage === 'kuesioner' ? 'active' : ''; ?>">
                    <i class="bi bi-file-text"></i>
                    <span>Data Kuesioner</span>
                </a>

                <a href="<?php echo url('admin/jawaban.php'); ?>" class="nav-item <?php echo $currentPage === 'jawaban' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Data Jawaban</span>
                </a>

                <a href="<?php echo url('admin/uji_statistik.php'); ?>" class="nav-item <?php echo $currentPage === 'uji_statistik' ? 'active' : ''; ?>">
                    <i class="bi bi-clipboard-check"></i>
                    <span>Uji Statistik</span>
                </a>

                <div class="nav-group">
                    <a href="#clusteringSubmenu" class="nav-item <?php echo $currentPage === 'clustering' ? 'active' : ''; ?>" data-bs-toggle="collapse" role="button" aria-expanded="<?php echo $currentPage === 'clustering' ? 'true' : 'false'; ?>">
                        <i class="bi bi-diagram-3"></i>
                        <span>Clustering</span>
                        <i class="bi bi-chevron-down ms-auto small"></i>
                    </a>
                    <div class="collapse <?php echo $currentPage === 'clustering' ? 'show' : ''; ?>" id="clusteringSubmenu">
                        <div class="nav-section ps-4 mt-1" style="gap: 2px;">
                            <?php
                            $activeAlgo = isset($_GET['algo']) ? $_GET['algo'] : '';
                            ?>
                            <a href="<?php echo url('admin/clustering.php?algo=kmeans'); ?>" class="nav-item py-2 <?php echo $activeAlgo === 'kmeans' ? 'active' : ''; ?>" style="font-size: 0.85rem;">
                                <i class="bi bi-circle-fill me-2" style="font-size: 0.5rem;"></i> K-Means
                            </a>
                            <a href="<?php echo url('admin/clustering.php?algo=kmedoids'); ?>" class="nav-item py-2 <?php echo $activeAlgo === 'kmedoids' ? 'active' : ''; ?>" style="font-size: 0.85rem;">
                                <i class="bi bi-circle-fill me-2" style="font-size: 0.5rem;"></i> K-Medoids
                            </a>
                            <a href="<?php echo url('admin/clustering.php?algo=dbscan'); ?>" class="nav-item py-2 <?php echo $activeAlgo === 'dbscan' ? 'active' : ''; ?>" style="font-size: 0.85rem;">
                                <i class="bi bi-circle-fill me-2" style="font-size: 0.5rem;"></i> DBSCAN
                            </a>
                            <a href="<?php echo url('admin/clustering.php?algo=hierarchical'); ?>" class="nav-item py-2 <?php echo $activeAlgo === 'hierarchical' ? 'active' : ''; ?>" style="font-size: 0.85rem;">
                                <i class="bi bi-circle-fill me-2" style="font-size: 0.5rem;"></i> Hierarchical
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="nav-section mt-auto">
                <a href="<?php echo url('logout.php'); ?>" class="nav-item nav-item-danger">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Logout</span>
                </a>
            </div>
        </nav>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <div class="d-flex align-items-center justify-content-between w-100">
                <div class="d-flex align-items-center gap-3">
                    <button class="sidebar-toggle" id="sidebarToggle" type="button">
                        <i class="bi bi-list"></i>
                    </button>
                    <div>
                        <h5 class="page-title mb-0" id="pageTitle">
                            <?php echo ($currentPage === 'clustering') ? 'Clustering' : ucfirst($currentPage); ?>
                        </h5>
                        <small class="text-muted">Sistem Kuesioner SMK Mutiara Ilmu</small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-3">
                    <div class="user-profile">
                        <div class="avatar">
                            <i class="bi bi-person-circle"></i>
                        </div>
                        <div class="user-info">
                            <div class="user-name"><?php echo sanitize($user['nis']); ?></div>
                            <div class="user-role">Admin</div>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Content Area -->
        <main class="admin-content">
            <!-- Overlay untuk mobile -->
            <div class="sidebar-overlay" id="sidebarOverlay"></div>