<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        body, html { margin: 0; padding: 0; height: 100%; overflow: hidden; background-color: #f4f6f9; }
        
        /* Overlay Layar Kunci Sebelum Mulai */
        #lockOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #fff; z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }

        /* Header Bar saat Ujian */
        #examHeader {
            height: 60px; background: #343a40; color: #fff; display: none;
            align-items: center; justify-content: space-between; padding: 0 20px;
        }

        /* iFrame Google Form */
        #examFrame {
            width: 100%; height: calc(100% - 60px); border: none; display: none;
        }
        
        .timer-box { background: #dc3545; padding: 5px 15px; border-radius: 20px; font-weight: bold; letter-spacing: 1px;}
    </style>
</head>
<!-- oncontextmenu="return false" memblokir klik kanan -->
<body oncontextmenu="return false">

    <!-- OVERLAY SEBELUM MULAI (Untuk Memicu Fullscreen) -->
    <div id="lockOverlay">
        <i class="fas fa-lock fa-4x text-info mb-3"></i>
        <h3 class="font-weight-bold">Ruang Ujian Terkunci</h3>
        <p class="text-muted text-center max-w-50">Anda akan memasuki mode layar penuh.<br>Segala bentuk kecurangan (membuka tab lain, split screen, keluar dari layar penuh) akan dicatat oleh sistem.</p>
        
        <button id="btnStart" class="btn btn-success btn-lg font-weight-bold px-5 mt-3 shadow">
            <i class="fas fa-expand mr-2"></i> Mulai Kerjakan
        </button>
    </div>

    <!-- HEADER UJIAN -->
    <div id="examHeader">
        <div>
            <h5 class="mb-0 font-weight-bold"><?= esc($jadwal['subject_name']) ?></h5>
            <small><?= esc($jadwal['nama_ujian'] ?? 'Ujian Aktif') ?></small>
        </div>
        <div class="d-flex align-items-center">
            <div class="timer-box mr-3">
                <i class="far fa-clock mr-1"></i> <span id="timeRemaining">Menghitung...</span>
            </div>
            <a href="<?= base_url('siswa/asesmen') ?>" class="btn btn-sm btn-outline-light" onclick="return confirm('Yakin ingin keluar? Pastikan Anda sudah Submit Google Form sebelum keluar.');">
                Selesai / Keluar
            </a>
        </div>
    </div>

    <!-- IFRAME GOOGLE FORM -->
    <iframe id="examFrame" src="<?= esc($jadwal['link_gform']) ?>" allowfullscreen></iframe>

    <!-- SCRIPT KEAMANAN & TIMER -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const btnStart = document.getElementById('btnStart');
        const lockOverlay = document.getElementById('lockOverlay');
        const examHeader = document.getElementById('examHeader');
        const examFrame = document.getElementById('examFrame');
        
        let warningCount = 0;
        const maxWarnings = 3;

        // 1. FUNGSI MASUK FULLSCREEN
        btnStart.addEventListener('click', function() {
            let elem = document.documentElement;
            if (elem.requestFullscreen) { elem.requestFullscreen(); } 
            else if (elem.webkitRequestFullscreen) { elem.webkitRequestFullscreen(); } 
            else if (elem.msRequestFullscreen) { elem.msRequestFullscreen(); }
            
            // Sembunyikan Overlay, Tampilkan Soal
            lockOverlay.style.display = 'none';
            examHeader.style.display = 'flex';
            examFrame.style.display = 'block';
        });

        // 2. DETEKSI KELUAR FULLSCREEN (ESC ditekan)
        document.addEventListener('fullscreenchange', exitHandler);
        document.addEventListener('webkitfullscreenchange', exitHandler);
        document.addEventListener('mozfullscreenchange', exitHandler);
        document.addEventListener('MSFullscreenChange', exitHandler);

        function exitHandler() {
            if (!document.fullscreenElement && !document.webkitIsFullScreen && !document.mozFullScreen && !document.msFullscreenElement) {
                if (examHeader.style.display === 'flex') { // Jika sedang ujian
                    pelanggaranTerdeteksi("Anda keluar dari mode Layar Penuh!");
                    // Paksa tutup soal, munculkan overlay lagi
                    lockOverlay.style.display = 'flex';
                    examHeader.style.display = 'none';
                    examFrame.style.display = 'none';
                }
            }
        }

        // 3. DETEKSI PINDAH TAB / BLUR (Visibility API)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden && examHeader.style.display === 'flex') {
                pelanggaranTerdeteksi("Anda terdeteksi membuka tab atau aplikasi lain!");
            }
        });

        window.addEventListener('blur', function() {
            if (examHeader.style.display === 'flex') {
                pelanggaranTerdeteksi("Layar kehilangan fokus! Dilarang split screen atau meminimize browser.");
            }
        });

        // 4. BLOKIR SHORTCUT KEYBOARD (F12, Ctrl+C, Ctrl+V, Alt+Tab dicegah sebisanya)
        document.addEventListener('keydown', function(e) {
            // Blokir F12 (Inspect)
            if (e.key === 'F12' || e.keyCode === 123) { e.preventDefault(); return false; }
            // Blokir Ctrl+Shift+I, Ctrl+U, dll
            if (e.ctrlKey && (e.key === 'I' || e.key === 'i' || e.key === 'U' || e.key === 'u' || e.key === 'C' || e.key === 'c')) {
                e.preventDefault(); return false;
            }
        });

        // FUNGSI ALERT PELANGGARAN
       let isWarningCooldown = false; // Tambahkan flag cooldown

        function pelanggaranTerdeteksi(pesan) {
            if (isWarningCooldown) return; // Hentikan jika sedang cooldown (mencegah double trigger)
            
            isWarningCooldown = true;
            warningCount++;
            
            if (warningCount >= maxWarnings) {
                Swal.fire({
                    icon: 'error',
                    title: 'UJIAN DIKUNCI!',
                    text: 'Anda telah melakukan pelanggaran maksimal. Ujian diblokir.',
                    allowOutsideClick: false,
                    confirmButtonText: 'Kembali ke Dashboard'
                }).then(() => {
                    window.location.href = "<?= base_url('siswa/asesmen') ?>";
                });
            } else {
                Swal.fire({
                    icon: 'warning',
                    title: 'PELANGGARAN DETEKSI!',
                    text: pesan + ' (Peringatan ' + warningCount + ' dari ' + maxWarnings + ')',
                    confirmButtonColor: '#d33'
                });
                
                // Buka kembali cooldown setelah 3 detik
                setTimeout(() => { isWarningCooldown = false; }, 3000); 
            }
        }

        // 5. COUNTDOWN TIMER MUNDUR
        const endTime = new Date("<?= date('M d, Y H:i:s', strtotime($jadwal['waktu_selesai'])) ?>").getTime();
        
        const timerInterval = setInterval(function() {
            const now = new Date().getTime();
            const distance = endTime - now;

            if (distance < 0) {
                clearInterval(timerInterval);
                document.getElementById("timeRemaining").innerHTML = "WAKTU HABIS!";
                Swal.fire({
                    icon: 'info',
                    title: 'Waktu Ujian Habis',
                    text: 'Ujian ini telah selesai sesuai dengan batas waktu.',
                    allowOutsideClick: false
                }).then(() => {
                    window.location.href = "<?= base_url('siswa/asesmen') ?>";
                });
            } else {
                const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
                const seconds = Math.floor((distance % (1000 * 60)) / 1000);
                
                document.getElementById("timeRemaining").innerHTML = 
                    (hours > 0 ? hours + "j " : "") + minutes + "m " + seconds + "d";
            }
        }, 1000);
    </script>
</body>
</html>