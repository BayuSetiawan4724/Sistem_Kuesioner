        </main>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Sidebar Toggle untuk Mobile
        const sidebar = document.getElementById('adminSidebar');
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebarOverlay = document.getElementById('sidebarOverlay');
        
        function toggleSidebar() {
            sidebar.classList.toggle('active');
            sidebarOverlay.classList.toggle('active');
            document.body.classList.toggle('sidebar-open');
        }
        
        sidebarToggle?.addEventListener('click', toggleSidebar);
        sidebarOverlay?.addEventListener('click', toggleSidebar);
        
        // Close sidebar saat klik nav item di mobile
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    sidebar.classList.remove('active');
                    sidebarOverlay.classList.remove('active');
                    document.body.classList.remove('sidebar-open');
                }
            });
        });
        
        // Update page title berdasarkan halaman aktif
        const pageTitles = {
            'dashboard': 'Dashboard',
            'siswa': 'Data Siswa',
            'kuesioner': 'Data Kuesioner',
            'jawaban': 'Data Jawaban',
            'clustering': 'Clustering K-Means'
        };
        const currentPage = '<?php echo $currentPage; ?>';
        if (pageTitles[currentPage]) {
            document.getElementById('pageTitle').textContent = pageTitles[currentPage];
        }
    </script>
</body>
</html>
