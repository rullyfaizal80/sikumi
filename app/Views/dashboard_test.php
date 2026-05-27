<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SiKuMi - Dashboard</title>
    <!-- Pemanggilan Seluruh Aset CSS Secara Lokal (Instant Tanpa Loading) -->
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        /* Standarisasi Kesejukan Warna Ekosistem MIMHa Finance */
        body { background-color: #f4f6f9 !important; }
        .app-header { background-color: #ffffff !important; border-bottom: 1px solid #dee2e6 !important; }
        .app-sidebar { background-color: #f8f9fa !important; border-right: 1px solid #dee2e6 !important; }
        .sidebar-brand { border-bottom: 1px solid #dee2e6 !important; background-color: #ffffff !important; }
        .nav-link { color: #333333 !important; font-weight: 500; border-radius: 4px; margin-bottom: 2px; }
        .nav-link:hover { background-color: #e9ecef !important; color: #000000 !important; }
        .nav-link.active { background-color: #dee2e6 !important; color: #000000 !important; font-weight: 700; }
        .nav-header { color: #6c757d !important; font-weight: 700; font-size: 11px; padding-left: 10px; margin-top: 15px; margin-bottom: 5px; text-uppercase: true; }
        .card-stat { border-radius: 8px; border: 1px solid #dee2e6 !important; background-color: #ffffff; }
        /* Animasi rotasi panah menu sidebar */
        .menu-arrow {
            transition: transform 0.3s ease !important;
            display: inline-block; 
        }
        [aria-expanded="true"] .menu-arrow {
            transform: rotate(-180deg); 
        }
        
        /* Mempercantik garis sub-menu */
        .sub-menu-container {
            border-left: 2px dashed #ced4da; /* Garis dibuat putus-putus agar elegan */
            margin-left: 1.2rem;
            padding-left: 0.5rem;
        }

        /* Pembeda Visual Induk Menu */
        .main-menu-item {
            background-color: #e9ecef; /* Warna latar sedikit gelap */
            border-left: 4px solid #0d6efd; /* Garis biru di sebelah kiri */
            border-radius: 4px;
        }
    </style>
</head>
<body class="layout-fixed sidebar-expand-lg">
    
    <!-- pembungkus UTAMA (WRAPPER KUNCI SYSTEM) -->
    <div class="app-wrapper">
        
        <!-- ======================================================== -->
        <!-- 1. NAVBAR ATAS (HEADER - MEMBENTANG SEMPURNA DI ATAS) -->
        <!-- ======================================================== -->
        <nav class="app-header navbar navbar-expand navbar-light">
            <div class="container-fluid">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button" title="Buka/Tutup Menu">
    <span style="font-size: 1.5rem; line-height: 1; font-weight: bold; color: inherit;">☰</span>
</a>
                    </li>
                </ul>
                
                <!-- ======================================================== -->
<!-- REVISI SISI KIRI NAVBAR: MENGGUNAKAN LOGO FILE .PNG LOKAL -->
<!-- ======================================================== -->
<ul class="navbar-nav">
    <li class="nav-item d-flex align-items-center ps-2">
    <!-- GANTI TAG <i> LAMA DENGAN BARIS <img /> BARU INI: -->
    <img src="<?= base_url('assets/img/sikumi.png') ?>" alt="Logo MIMHa" class="me-2" style="height: 48px; width: auto; object-fit: contain;">
    
    <!-- Menggunakan d-flex flex-column agar teks developer berada di bawah judul -->
    <div class="d-flex flex-column justify-content-center">
        <span class="navbar-text font-weight-bold p-0" style="color: #212529; font-weight: 800; font-size: 19px; letter-spacing: 0.5px; line-height: 1.2;">
            Sistem Kurikulum <span style="color: #FF9F00;">MIMHa</span> <span class="text-muted font-weight-normal" style="font-size: 15px;">(SiKuMi)</span>
        </span>
        <!-- Kalimat baru di bawah judul -->
        <small class="text-muted" style="font-size: 11px; font-weight: 500; margin-top: 2px;">Developed by Rully Faizal</small>
    </div>
</li>
</ul>

                 <!-- SISI KANAN NAVBAR: INFORMASI USER, JAM REAL-TIME & LOGOUT -->
                <ul class="navbar-nav ms-auto align-items-center">
                    <!-- Detail Pengguna Aktif & Waktu Jam Server Ter-update Otomatis -->
                    <li class="nav-item text-end me-3 d-none d-sm-block">
                        <span class="d-block font-weight-bold text-dark small" style="font-weight: 600; font-size: 13px;">
                            <?= esc($username) ?> (<span class="text-uppercase text-muted" style="font-size: 9px; font-weight: 700;"><?= implode(', ', $myRoles) ?></span>)
                        </span>
                        <!-- Elemen ID untuk memicu Detak Jam JavaScript -->
                        <span id="jam-realtime" class="text-muted d-block small font-weight-bold" style="font-size: 11px;">
                            Loading waktu...
                        </span>
                    </li>
                    
                    <!-- Tombol Aksi Keluar Aplikasi Minimalis -->
                    <li class="nav-item pe-2">
                        <a href="<?= base_url('logout') ?>" class="btn btn-sm btn-outline-danger d-flex align-items-center shadow-sm" style="border-radius: 4px; padding: 5px 10px;">
                            <i class="bi bi-box-arrow-right me-1"></i> Keluar
                        </a>
                    </li>
                </ul>

            </div>
        </nav>

        <!-- ======================================================== -->
        <!-- 2. SIDEBAR SAMPING (MENU BERSIH DI BAWAH NAVBAR KIRI) -->
        <!-- ======================================================== -->
        <aside class="app-sidebar shadow-sm">
            
            <!-- Label Atas Khas Navigasi Keuangan -->
            <!-- Menggunakan d-flex dan min-height agar sejajar dengan tinggi navbar, serta padding vertikal yang disesuaikan -->
<div class="sidebar-brand d-flex align-items-center justify-content-center" style="height: 65px; padding: 0 1rem; border-bottom: 1px solid #dee2e6;">
    <span class="brand-text font-weight-bold text-dark" style="font-size: 13px; letter-spacing: 1px; font-weight: 700;">
        MENU UTAMA
    </span>
</div>
            
            <!-- Rendering Daftar Menu Hasil Database -->
            <div class="sidebar-wrapper p-2">
                <nav class="mt-1">
                    <?php $currentUri = service('uri')->getPath(); ?>
                    
                    <ul class="nav flex-column" role="menu">

                        <?php foreach ($sidebarMenu as $mId => $node): ?>
                            
                            <?php if (!empty($node['anak'])): ?>
                                
                                <li class="nav-item mb-2">
                                    <div data-bs-toggle="collapse" data-bs-target="#menuLipat<?= $mId ?>" role="button" aria-expanded="true" aria-controls="menuLipat<?= $mId ?>" class="nav-link font-weight-bold d-flex justify-content-between align-items-center main-menu-item" style="color: #212529 !important; cursor: pointer;">
                                        
                                        <div>
                                            <i class="nav-icon <?= esc($node['induk']['icon']) ?> text-primary me-2"></i>
                                            <span class="mb-0 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                                <?= esc($node['induk']['permission_description']) ?>
                                            </span>
                                        </div>
                                        
                                        <span class="menu-arrow text-secondary" style="font-size: 12px;">▼</span>
                                    </div>
                                    
                                    <div class="collapse show mt-1" id="menuLipat<?= $mId ?>">
                                        <ul class="nav flex-column sub-menu-container py-1">
                                            <?php foreach ($node['anak'] as $sub): ?>
                                                <?php $isActiveSub = (strpos($currentUri, $sub['menu_link']) !== false && $sub['menu_link'] !== '#'); ?>
                                                <li class="nav-item">
                                                    <a href="<?= $sub['is_active'] == 1 ? base_url($sub['menu_link']) : '#' ?>" 
                                                       class="nav-link py-2 mb-1 <?= $isActiveSub ? 'text-primary font-weight-bold bg-white shadow-sm' : '' ?> <?= $sub['is_active'] == 0 ? 'fitur-belum-siap opacity-50' : '' ?>"
                                                       style="font-size: 14px; color: <?= $isActiveSub ? '#0d6efd' : '#495057' ?> !important; border-radius: 4px;">
                                                        <i class="<?= esc($sub['icon'] ?? 'bi bi-dash') ?> me-2 <?= $isActiveSub ? 'text-primary' : 'text-muted' ?>" style="font-size: 12px;"></i>
                                                        <?= esc($sub['permission_description']) ?>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    </div>
                                </li>

                            <?php else: ?>
                                <?php $isActiveInduk = (strpos($currentUri, $node['induk']['menu_link']) !== false && $node['induk']['menu_link'] !== '#'); ?>
                                <li class="nav-item mb-2">
                                    <a href="<?= base_url($node['induk']['menu_link']) ?>" class="nav-link font-weight-bold main-menu-item <?= $isActiveInduk ? 'text-primary' : '' ?>" style="color: <?= $isActiveInduk ? '#0d6efd' : '#212529' ?> !important;">
                                        <i class="nav-icon <?= esc($node['induk']['icon']) ?> text-primary me-2"></i> 
                                        <span class="mb-0 text-uppercase" style="font-size: 0.85rem; letter-spacing: 0.5px;">
                                            <?= esc($node['induk']['permission_description']) ?>
                                        </span>
                                    </a>
                                </li>
                            <?php endif; ?>

                        <?php endforeach; ?>

                    </ul>
                </nav>
            </div>
        </aside>

        <!-- ======================================================== -->
        <!-- 3. CONTENT WRAPPER (AREA KONTEN KERJA UTAMA - SISI KANAN) -->
        <!-- ======================================================== -->
        <main class="app-main">
            <div class="app-content-header py-3">
                <div class="container-fluid ps-4">
                    <h3 class="text-dark mb-1" style="font-weight: 700; font-size: 26px; letter-spacing: -0.5px;">Dashboard</h3>
                </div>
            </div>

            <div class="app-content">
                <div class="container-fluid ps-4 pe-4">
                    
                    <!-- Kotak Putih Selamat Datang Minimalis -->
                    <div class="card card-stat border-0 shadow-sm p-4 mb-4">
                        <h5 class="text-dark mb-2" style="font-weight: 600; font-size: 18px;">Selamat Datang!</h5>
                        <p class="text-muted mb-0" style="font-size: 14px; line-height: 1.6;">Anda telah berhasil login ke dalam sistem kendali kurikulum terpadu MIMHa.</p>
                    </div>

                    <!-- Indikator 3 Kotak Statistik Kurikulum -->
                    <div class="row g-4">
                        <div class="col-md-4">
                            <div class="card card-stat p-4 shadow-sm text-center">
                                <h4 class="text-warning mb-1 font-weight-bold" style="font-weight: 700;">📅 Kaldik</h4>
                                <span class="text-muted small">Semester Ganjil Aktif</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat p-4 shadow-sm text-center">
                                <h4 class="text-success mb-1 font-weight-bold" style="font-weight: 700;">📝 0</h4>
                                <span class="text-muted small">Perangkat Ajar Terbuat</span>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card card-stat p-4 shadow-sm text-center">
                                <h4 class="text-info mb-1 font-weight-bold" style="font-weight: 700;">🤖 Ready</h4>
                                <span class="text-muted small">Asisten Inteligensi AI</span>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </main>

    </div> <!-- END WRAPPER -->

    <!-- ======================================================== -->
    <!-- 4. JENDELA POP-UP ALERT NOTIFIKASI MODAL BOOTSTRAP 5 -->
    <!-- ======================================================== -->
    <div class="modal fade" id="modalBelumSiap" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content shadow border-0" style="border-radius: 8px;">
                <div class="modal-header bg-warning text-dark font-weight-bold border-0">
                    <h5 class="modal-title" style="font-weight: 700;"><i class="bi bi-exclamation-triangle-fill me-2"></i> Informasi Aplikasi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center py-4">
                    <i class="bi bi-cone-striped text-warning" style="font-size: 55px;"></i>
                    <h5 class="mt-3" style="font-weight: 700; color: #333;">Mohon Maaf!</h5>
                    <p class="text-muted mb-0" style="font-size: 14px;">Fitur <strong id="nama-fitur-text" class="text-dark"></strong> saat ini masih dalam tahap proses pengembangan internal tim kurikulum MIMHa.</p>
                </div>
                <div class="modal-footer p-2 border-top bg-light justify-content-center border-0" style="border-radius: 8px;">
                    <button type="button" class="btn btn-sm btn-secondary px-4 font-weight-bold" data-bs-dismiss="modal" style="border-radius: 4px;">Dimengerti</button>
                </div>
            </div>
        </div>
    </div>

     <!-- Pemanggilan Seluruh Aset JavaScript Secara Lokal -->
    <script src="<?= base_url('assets/js/bootstrap.bundle.min.js') ?>"></script>
    <script src="<?= base_url('assets/js/adminlte.min.js') ?>"></script>

    <!-- LOGIKA JAVASCRIPT: POP-UP ALERT & SKRIP JAM REAL-TIME BERJALAN -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            // A. LOGIKA POP-UP MENU '0'
            const linksBelumSiap = document.querySelectorAll('.fitur-belum-siap');
            const modalBelumSiap = new bootstrap.Modal(document.getElementById('modalBelumSiap'));
            const textFitur = document.getElementById('nama-fitur-text');

            linksBelumSiap.forEach(link => {
                link.addEventListener('click', function(e) {
                    const targetUrl = this.getAttribute('href');
                    const currentDomain = window.location.origin;
                    
                    if (targetUrl === '#' || targetUrl === currentDomain + '/#') {
                        e.preventDefault(); 
                        const namaFitur = this.getAttribute('data-nama');
                        textFitur.textContent = namaFitur; 
                        modalBelumSiap.show(); 
                    }
                });
            });

            // B. LOGIKA DETAK JAM DETIK REAL-TIME (OTOMATIS)
            function perbaruiWaktu() {
                const sekarang = new Date();
                
                // Opsi Array Nama Hari dan Bulan bahasa Indonesia resmi
                const namaHari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
                const namaBulan = ["Jan", "Feb", "Mar", "Apr", "Mei", "Jun", "Jul", "Agu", "Sep", "Okt", "Nov", "Des"];
                
                const hari = namaHari[sekarang.getDay()];
                const tanggal = String(sekarang.getDate()).padStart(2, '0');
                const bulan = namaBulan[sekarang.getMonth()];
                const tahun = sekarang.getFullYear();
                
                // Format angka jam, menit, detik agar selalu dua digit (e.g., 09.05.12)
                const jam = String(sekarang.getHours()).padStart(2, '0');
                const menit = String(sekarang.getMinutes()).padStart(2, '0');
                const detik = String(sekarang.getSeconds()).padStart(2, '0');
                
                // Gabungkan string menyerupai format MIMHa Finance
                const stringWaktu = `${hari}, ${tanggal} ${bulan} ${tahun} ${jam}.${menit}.${detik} WIB`;
                
                document.getElementById('jam-realtime').textContent = stringWaktu;
            }

            // Jalankan fungsi jam pertama kali saat web dibuka
            perbaruiWaktu();
            // Perintahkan browser untuk memperbarui fungsi waktu setiap 1000 milidetik (1 detik)
            setInterval(perbaruiWaktu, 1000);
        });
    </script>
</body>
</html>
