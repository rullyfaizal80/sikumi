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
        
        /* Layar Kunci Sebelum Mulai */
        #lockOverlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: #fff; z-index: 9999;
            display: flex; flex-direction: column; align-items: center; justify-content: center;
        }

        /* Header & iFrame Ujian */
        #examHeader {
            height: 60px; background: #343a40; color: #fff; display: none;
            align-items: center; justify-content: space-between; padding: 0 20px;
        }
        #examFrame {
            width: 100%; height: calc(100% - 60px); border: none; display: none;
        }
        .timer-box { background: #dc3545; padding: 5px 15px; border-radius: 20px; font-weight: bold; letter-spacing: 1px;}
    </style>
</head>
<body oncontextmenu="return false">

    <!-- OVERLAY AWAL -->
    <div id="lockOverlay">
        <i class="fas fa-lock fa-4x text-info mb-3"></i>
        <h3 class="font-weight-bold">Ruang Ujian Terkunci</h3>
        <p class="text-muted text-center max-w-50">Segala bentuk kecurangan (membuka tab lain, minimize browser) akan diblokir seketika.</p>
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
            <button id="btnSelesai" class="btn btn-sm btn-outline-light">
    Selesai / Keluar
</button>
        </div>
    </div>

    <!-- IFRAME GOOGLE FORM -->
    <iframe id="examFrame" src="<?= esc($jadwal['link_gform']) ?>" allowfullscreen></iframe>

    <!-- SCRIPT KEAMANAN -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        const btnStart = document.getElementById("btnStart");
        const lockOverlay = document.getElementById("lockOverlay");
        const examHeader = document.getElementById("examHeader");
        const examFrame = document.getElementById("examFrame");
        
        let isBlocked = false;
        let isProctorMode = false;

        // 1. TOMBOL MULAI (DENGAN FALLBACK IPAD)
        btnStart.addEventListener("click", function() {
            let elem = document.documentElement;
            // Coba masuk fullscreen (iPad Safari kadang menolak ini, jadi kita gunakan try-catch agar tidak error)
            try {
                if (elem.requestFullscreen) { elem.requestFullscreen(); } 
                else if (elem.webkitRequestFullscreen) { elem.webkitRequestFullscreen(); }
            } catch (e) {
                console.log("Perangkat ini tidak mendukung pemaksaan Layar Penuh (Misal: iPad).");
            }
            
            // Apapun yang terjadi (bisa fullscreen atau tidak), ujian tetap berjalan
            lockOverlay.style.display = "none";
            examHeader.style.display = "flex";
            examFrame.style.display = "block";
        });

        // 2.5 TOMBOL SELESAI UJIAN (MENGGUNAKAN SWEETALERT)
        const btnSelesai = document.getElementById("btnSelesai");
        if (btnSelesai) {
            btnSelesai.addEventListener("click", function(e) {
                e.preventDefault();
                
                // Gunakan SweetAlert yang merender di dalam HTML (aman dari sensor blokir)
                Swal.fire({
                    title: 'Yakin ingin keluar?',
                    text: 'Pastikan Anda sudah menekan tombol KIRIM pada Google Form sebelum keluar!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, Selesai!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Jika yakin, arahkan ke route selesai_ujian
                        window.location.href = "<?= base_url('siswa/asesmen/selesai-ujian/' . $jadwal['id']) ?>";
                    }
                });
            });
        }

        // ====================================================================================
        // KITA MENGHAPUS SENSOR FULLSCREEN. 
        // Siswa tidak akan diganggu jika layar keluar fullscreen akibat Pop-up Google Form.
        // ====================================================================================

        // 2. DETEKSI PINDAH TAB / MINIMIZE BROWSER -> BLOKIR PERMANEN
        function triggerBlock() {
            if (isProctorMode) return; 

            if (examHeader.style.display === "flex" && !isBlocked) {
                isBlocked = true; 
                examHeader.style.display = "none";
                examFrame.style.display = "none";

                Swal.fire({
                    icon: "error",
                    title: "TERBLOKIR!",
                    text: "Anda terdeteksi pindah ke tab atau aplikasi lain.",
                    allowOutsideClick: false,
                    confirmButtonText: "Kembali ke Dashboard & Simpan"
                }).then(() => {
                    window.location.href = "<?= base_url('siswa/asesmen/eksekusi-blokir/' . $jadwal['id']) ?>";
                });
            }
        }

        document.addEventListener("visibilitychange", function() {
            // Ini didukung di Windows, Chromebook, dan iOS Safari
            if (document.hidden) triggerBlock();
        });

        // 3. BLOKIR SHORTCUT & MODE PENGAWAS (Ctrl + Shift + X)
        document.addEventListener("keydown", function(e) {
            if (e.ctrlKey && e.shiftKey && (e.key === "X" || e.key === "x")) {
                e.preventDefault();
                Swal.fire({
                    title: 'Mode Troubleshooting',
                    input: 'password',
                    inputPlaceholder: 'PIN Pengawas',
                    showCancelButton: true,
                    confirmButtonText: 'Buka Akses'
                }).then((result) => {
                    if (result.value === "12345") { 
                        isProctorMode = true;
                        Swal.fire("Sensor Mati!", "Silakan perbaiki perangkat. Refresh (F5) jika sudah selesai.", "success");
                    }
                });
                return false;
            }
            if (e.key === "F12" || e.keyCode === 123) { e.preventDefault(); return false; }
            if (e.ctrlKey && (e.key === "I" || e.key === "i" || e.key === "C" || e.key === "c" || e.key === "V" || e.key === "v")) { e.preventDefault(); return false; }
        });

        // 4. TIMER MUNDUR
        let sisaWaktuDetik = parseInt("<?= $sisa_waktu_detik ?>");
        const timerInterval = setInterval(function() {
            if (sisaWaktuDetik <= 0) {
                clearInterval(timerInterval);
                document.getElementById("timeRemaining").innerHTML = "WAKTU HABIS!";
                Swal.fire({
                    icon: "info",
                    title: "Waktu Ujian Habis",
                    text: "Durasi ujian Anda telah berakhir.",
                    allowOutsideClick: false,
                    confirmButtonText: "Kembali ke Dashboard"
                }).then(() => {
                    window.location.href = "<?= base_url('siswa/asesmen') ?>";
                });
            } else {
                const hours = Math.floor(sisaWaktuDetik / 3600);
                const minutes = Math.floor((sisaWaktuDetik % 3600) / 60);
                const seconds = sisaWaktuDetik % 60;
                document.getElementById("timeRemaining").innerHTML = (hours > 0 ? hours + "j " : "") + minutes + "m " + seconds + "d";
                sisaWaktuDetik--; 
            }
        }, 1000);
    </script>
</body>
</html>