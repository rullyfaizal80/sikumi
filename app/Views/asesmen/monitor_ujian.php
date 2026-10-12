<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <style>
        /* 1. Responsif Font Token (HP Friendly) */
        .token-display { 
            font-size: 5rem; 
            letter-spacing: 15px; 
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3); 
        }
        @media (max-width: 768px) {
            .token-display { 
                font-size: 3rem; /* Mengecil di HP */
                letter-spacing: 8px; 
            }
        }

        .badge-custom-success { background-color: #28a745 !important; color: #ffffff !important; border: 1px solid #1e7e34; }
        .badge-custom-danger { background-color: #dc3545 !important; color: #ffffff !important; border: 1px solid #bd2130; }
        .badge-custom-secondary { background-color: #6c757d !important; color: #ffffff !important; border: 1px solid #545b62; }
    </style>
</head>
<body class="bg-dark">
    <!-- Padding responsif: p-2 di HP, p-4 di Laptop -->
    <div class="container-fluid p-2 p-md-4">
        
        <!-- Header Responsif -->
        <div class="d-flex justify-content-between align-items-center mb-3 mb-md-4">
            <h4 class="text-white mb-0" style="font-size: 1.2rem;"><i class="fas fa-desktop mr-2"></i> Monitor Pengawas</h4>
            <!-- Tombol kembali disembunyikan di HP karena Menu Sidebar sudah ada -->
            <a href="<?= base_url('/') ?>" class="btn btn-outline-light btn-sm d-none d-md-inline-block">
                <i class="fas fa-arrow-left mr-1"></i> Dashboard
            </a>
        </div>

        <!-- Panel Token Realtime -->
        <div class="card bg-info text-white text-center shadow-lg mb-3 mb-md-4" style="border-radius: 15px;">
            <div class="card-body py-4 py-md-5">
                <h6 class="text-uppercase mb-2 font-weight-bold"><i class="fas fa-key mr-1"></i> Token Ujian Sesi Ini Berganti Setiap 5 Menit</h6>
                <h1 class="font-weight-bold token-display mb-0" id="liveToken">------</h1>
                <p class="mt-2 mb-0 text-light small"><i class="fas fa-sync-alt fa-spin mr-1"></i> Refresh tiap 10 detik</p>
            </div>
        </div>

       <!-- Tabel Laporan Ujian -->
        <div class="card shadow-sm">
            <!-- Header dengan Input Tanggal & 3 Lencana Status -->
<div class="card-header bg-white d-flex flex-column flex-md-row justify-content-between align-items-center py-3 w-100">
    
    <!-- Bagian Kiri: Ditambahkan flex-grow-1 agar mengambil ruang sisa di kiri dan mendorong bagian kanan -->
    <div class="d-flex align-items-center mb-3 mb-md-0 flex-grow-1">
        <h5 class="mb-0 text-dark font-weight-bold" style="font-size: 1.1rem; white-space: nowrap;">
            <i class="fas fa-list-alt mr-2"></i> Log Ujian
        </h5>
        <!-- INPUT TANGGAL: Menggunakan inline style margin-left agar pasti tembus tanpa terpengaruh versi Bootstrap -->
        <input type="date" id="filterDate" class="form-control form-control-sm shadow-sm" value="<?= date('Y-m-d') ?>" style="max-width: 150px; cursor: pointer; margin-left: 20px !important;">
    </div>

    <!-- Bagian Kanan: Ditambahkan text-right dan ml-md-auto agar mepet ke kanan -->
    <div class="d-flex align-items-center flex-nowrap ml-md-auto text-right">
        <span class="badge badge-custom-success px-2 py-1 shadow-sm mr-2" id="countBerlangsung">0 Berlangsung</span>
        <span class="badge badge-custom-danger px-2 py-1 shadow-sm mr-2" id="countTerkunci">0 Terkunci</span>
        <span class="badge badge-custom-secondary px-2 py-1 shadow-sm" id="countSelesai">0 Selesai</span>
    </div>

</div>

            
            <div class="card-body p-0">
                <div class="table-responsive" style="max-height: 60vh; overflow-y: auto;">
                    <table class="table table-striped table-hover mb-0 text-dark text-nowrap">
                        <thead class="bg-light shadow-sm" style="position: sticky; top: 0; z-index: 1;">
                            <tr>
                                <th class="text-center" width="50">No</th>
                                <th>Nama Siswa</th>
                                <th>Kelas</th>
                                <th>Mata Pelajaran</th>
                                <th class="text-center">Waktu Masuk</th>
                                <th class="text-center">Pelanggaran</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    <i class="fas fa-spinner fa-spin mr-2"></i> Memuat data...
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>

    <script>
        function fetchMonitorData() {
            // Ambil nilai tanggal dari input kalender
            const selectedDate = document.getElementById('filterDate').value;
            // Sisipkan tanggal ke dalam URL API
            const url = "<?= base_url('asesmen/admin/api-monitor') ?>?date=" + selectedDate;

            fetch(url)
                .then(response => response.json())
                .then(data => {
                    
                    // Update Token Global
                    document.getElementById('liveToken').innerText = data.token;

                    const tbody = document.getElementById('studentTableBody');
                    let html = '';
                    
                    // Variabel penghitung status harian
                    let cBerlangsung = 0;
                    let cTerkunci = 0;
                    let cSelesai = 0;

                    if(data.students.length === 0) {
                        html = `<tr><td colspan="7" class="text-center py-4 text-muted">Belum ada log aktivitas ujian pada tanggal tersebut.</td></tr>`;
                    } else {
                        data.students.forEach((std, index) => {
                            let statusBadge = '';
                            
                            // Logika penambahan jumlah dan desain badge
                            if(std.status === 'berlangsung') {
                                statusBadge = `<span class="badge badge-custom-success px-3 py-2 shadow-sm"><i class="fas fa-spinner fa-spin mr-1"></i> Berlangsung</span>`;
                                cBerlangsung++;
                            } else if(std.status === 'terkunci') {
                                statusBadge = `<span class="badge badge-custom-danger px-3 py-2 shadow-sm"><i class="fas fa-lock mr-1"></i> Terkunci</span>`;
                                cTerkunci++;
                           } else if(std.status === 'selesai') {
    statusBadge = `<span class="badge badge-custom-secondary px-3 py-2 shadow-sm"><i class="fas fa-check mr-1"></i> Selesai</span>`;
    cSelesai++;
}

                            let blokirClass = std.blokir_count > 0 ? 'text-danger font-weight-bold' : 'text-success';

                            html += `
                                <tr>
                                    <td class="text-center align-middle">${index + 1}</td>
                                    <td class="font-weight-bold align-middle">${std.username ?? 'N/A'}</td>
                                    <td class="align-middle">${std.rombel_name ?? '-'}</td>
                                    <td class="align-middle">${std.subject_name ?? '-'}</td>
                                    <td class="text-center align-middle">${std.waktu_masuk}</td>
                                    <td class="text-center align-middle ${blokirClass}">${std.blokir_count} kali</td>
                                    <td class="text-center align-middle">${statusBadge}</td>
                                </tr>
                            `;
                        });
                    }
                    
                    // Render tabel
                    tbody.innerHTML = html;
                    
                    // Update angka di 3 Lencana Header
                    document.getElementById('countBerlangsung').innerText = cBerlangsung + ' Berlangsung';
                    document.getElementById('countTerkunci').innerText = cTerkunci + ' Terkunci';
                    document.getElementById('countSelesai').innerText = cSelesai + ' Selesai';
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        // Panggil saat halaman pertama dimuat
        fetchMonitorData();

        // JIKA TANGGAL DIUBAH PADA KALENDER, LANGSUNG REFRESH DATA TANPA MENUNGGU 10 DETIK
        document.getElementById('filterDate').addEventListener('change', fetchMonitorData);

        // Refresh rutin tiap 10 detik
        setInterval(fetchMonitorData, 10000);
    </script>
</body>
</html>
