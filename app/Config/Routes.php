<?php

use CodeIgniter\Router\RouteCollection;

/**
 * @var RouteCollection $routes
 */

// =========================================================================
// RUTE UTAMA & AUTENTIKASI
// =========================================================================
$routes->get('/', 'Home::index', ['as' => 'dashboard', 'filter' => 'session']);

$routes->get('login', '\App\Controllers\Auth\LoginController::loginView');
$routes->post('login', '\App\Controllers\Auth\LoginController::loginAction');

// Panggil rute otomatis bawaan CodeIgniter Shield secara resmi
service('auth')->routes($routes);

// Rute Halaman Diblokir (Tanpa membuat controller baru)
$routes->get('blocked', function() {
    return view('403_kustom'); 
});

// =========================================================================
// JALUR KHUSUS HALAMAN ADMIN (GRUP TERPUSAT)
// =========================================================================
$routes->group('admin', static function ($routes) {
    
    // --- MANAJEMEN PENGGUNA & HAK AKSES ---
    $routes->get('users', 'AdminController::index');
    $routes->post('users/store', 'AdminController::storeUser');
    $routes->post('users/update-roles', 'AdminController::updateUserRoles');
    $routes->post('users/reset-password/(:num)', 'AdminController::resetPassword/$1');

    $routes->post('roles/store', 'AdminController::storeRole');
    $routes->post('users/role-update/(:num)', 'AdminController::updateRole/$1');
    $routes->get('users/role-delete/(:num)', 'AdminController::deleteRole/$1'); // Note: Rentan CSRF, biarkan GET agar View tetap jalan

    $routes->get('permission-matrix', 'AdminController::permissionMatrix');
    $routes->post('permission-matrix/save', 'AdminController::saveMatrix');

    // --- PUSAT PENGATURAN TERPADU SIKUMI (SATU JENDELA) ---
    $routes->get('settings', 'AdminController::appSettings');
    $routes->post('settings/save', 'AdminController::saveSettings');
    $routes->post('settings/add-angkatan', 'AdminController::addAngkatan');
    $routes->post('settings/delete-angkatan', 'AdminController::deleteAngkatan'); 
    
    // SINKRONISASI TAB 4: Mengaktifkan Semester Berjalan
   // $routes->get('academic/activate/(:num)', 'AdminController::activateAcademic/$1');

    $routes->get('settings/academic-activate/(:num)', '\App\Controllers\AdminController::activateAcademic/$1');

    // --- KALENDER PENDIDIKAN (KALDIK) ---
    $routes->get('kaldik', 'KaldikController::index');
    $routes->post('kaldik/store', 'KaldikController::storeAgenda');
    $routes->post('kaldik/copy', 'KaldikController::copyKaldik');
    $routes->post('kaldik/update', 'KaldikController::updateAgenda');
    $routes->post('kaldik/delete/(:num)', 'KaldikController::deleteAgenda/$1');
    $routes->get('kaldik/print', 'KaldikController::printKaldik');

    // --- MANAJEMEN GURU ---
    $routes->get('users/guru-tes', 'UserGuruController::index');
    $routes->post('users/guru-store', 'UserGuruController::storeGuru');
    $routes->post('users/guru-update/(:num)', 'UserGuruController::updateGuru/$1');
    $routes->get('users/guru-toggle/(:num)', 'UserGuruController::toggleStatus/$1');
    
    $routes->get('users/guru-delete/(:num)', 'UserGuruController::deleteGuru/$1');
    $routes->get('users/guru-trash', 'UserGuruController::trashGuru');
    $routes->get('users/guru-restore/(:num)', 'UserGuruController::restoreGuru/$1');
    
    $routes->post('users/guru-update-history/(:num)', 'UserGuruController::updateHistory/$1');
    $routes->get('users/guru-delete-history/(:num)', 'UserGuruController::deleteHistory/$1');

    // --- MANAJEMEN SISWA ---
    $routes->get('users/siswa-tes', 'UserSiswaController::index');
    $routes->post('users/siswa-store', 'UserSiswaController::storeSiswa');
    $routes->post('users/siswa-update/(:num)', 'UserSiswaController::updateSiswa/$1');
    $routes->get('users/siswa-toggle/(:num)', 'UserSiswaController::toggleStatus/$1');

    $routes->get('users/siswa-delete/(:num)', 'UserSiswaController::deleteSiswa/$1');
    $routes->get('users/siswa-trash', 'UserSiswaController::trashSiswa');
    $routes->get('users/siswa-restore/(:num)', 'UserSiswaController::restoreSiswa/$1');

    // --- MASTER DATA ---
    $routes->get('master-data', 'MasterDataController::index');
    
    // Master Data Mata Pelajaran
    $routes->post('master-data/subject-store', 'MasterDataController::storeSubject');
    $routes->post('master-data/subject-update/(:num)', 'MasterDataController::updateSubject/$1');
    $routes->get('master-data/subject-delete/(:num)', 'MasterDataController::deleteSubject/$1');

    // Master Data Kelas
    $routes->post('master-data/class-store', 'MasterDataController::storeClass');
    $routes->post('master-data/class-update/(:num)', 'MasterDataController::updateClass/$1');
    $routes->get('master-data/class-delete/(:num)', 'MasterDataController::deleteClass/$1');

    $routes->get('rombel', 'RombelController::index');
    $routes->post('rombel/store', 'RombelController::store');
    $routes->post('rombel/update/(:num)', 'RombelController::update/$1');
    $routes->post('rombel/plot-store', 'RombelController::plotStore');
    $routes->get('rombel/plot-delete/(:num)', 'RombelController::plotDelete/$1');
    $routes->post('rombel/copy', 'RombelController::copySemester');
    $routes->get('rombel/delete/(:num)', 'RombelController::delete/$1');

    $routes->get('rombel/siswa/(:num)', 'RombelSiswaController::manage/$1');
    $routes->post('rombel/siswa/add', 'RombelSiswaController::add');
    $routes->post('rombel/siswa/remove', 'RombelSiswaController::remove');

    $routes->post('rombel/copy-plotting-to-other', '\App\Controllers\RombelController::copyPlottingToOtherClass');

   // --- MANAJEMEN JADWAL PELAJARAN ---
    $routes->get('schedule', '\App\Controllers\ScheduleController::index');
    $routes->post('schedule/create-version', '\App\Controllers\ScheduleController::createVersion');
    $routes->post('schedule/generate-time', '\App\Controllers\ScheduleController::generateTime');
    $routes->post('schedule/update-time', '\App\Controllers\ScheduleController::updateTime');
    $routes->get('schedule/delete-day-time/(:any)', '\App\Controllers\ScheduleController::deleteDayTime/$1');
    $routes->get('schedule/delete-slot-time/(:num)', '\App\Controllers\ScheduleController::deleteSlotTime/$1');
    $routes->get('schedule/reset-all-slots', '\App\Controllers\ScheduleController::resetAllSlots');
    $routes->post('schedule/save-plotting', '\App\Controllers\ScheduleController::savePlotting');

    $routes->post('schedule/save-combined', '\App\Controllers\ScheduleController::saveCombined');
    $routes->get('schedule/delete-combined/(:num)', '\App\Controllers\ScheduleController::deleteCombined/$1');
    $routes->post('schedule/update-combined', '\App\Controllers\ScheduleController::updateCombined');
    $routes->post('schedule/save-matrix', '\App\Controllers\ScheduleController::saveMatrix');

    $routes->post('schedule/save-activity', '\App\Controllers\ScheduleController::saveActivity');
    $routes->post('schedule/update-activity', '\App\Controllers\ScheduleController::updateActivity');
    $routes->get('schedule/delete-activity/(:num)', '\App\Controllers\ScheduleController::deleteActivity/$1');

    $routes->get('schedule/delete-version/(:num)', '\App\Controllers\ScheduleController::deleteVersion/$1');
    $routes->post('schedule/copy-version', '\App\Controllers\ScheduleController::copyVersion');

    $routes->post('schedule/auto-generate', '\App\Controllers\ScheduleController::autoGenerateMatrix');

    $routes->get('schedule/set-active/(:num)', '\App\Controllers\ScheduleController::setActiveVersion/$1');
    $routes->get('schedule/print/(:num)', '\App\Controllers\ScheduleController::printSchedule/$1');

    $routes->get('analisis-heb', '\App\Controllers\AnalysisController::index');
    
});

$routes->group('guru', static function ($routes) {
    $routes->get('kaldik', '\App\Controllers\KaldikController::guruIndex');
    $routes->get('kaldik/print', '\App\Controllers\KaldikController::guruPrint');

    $routes->get('schedule', '\App\Controllers\ScheduleController::guruView');

    $routes->get('analisis-heb', '\App\Controllers\AnalysisController::index');
});

// =====================================================================
// MENU KHUSUS: SIKUMI AI ASSISTANT (Berlaku untuk Admin & Guru)
// =====================================================================
$routes->get('sikumi-ai', 'AiController::index');
$routes->post('sikumi-ai/send', 'AiController::sendMessage');

// ====================================================================
// MODUL PERANGKAT AJAR & AI SIKUMI
// ====================================================================

$routes->get('guru/analisis-cp', 'PerangkatController::analisis_cp');
$routes->post('guru/analisis-cp/reset', 'PerangkatController::reset_analisis_cp');

$routes->post('perangkat/save_draft', 'PerangkatController::save_draft_elemen');
$routes->get('perangkat/delete_draft/(:num)', 'PerangkatController::delete_draft_elemen/$1');

$routes->post('ai/analyze_cp', 'AiController::analyzeCp');

$routes->post('perangkat/update_draft', 'PerangkatController::update_draft_elemen');

$routes->post('perangkat/save_analisis_manual', 'PerangkatController::save_analisis_manual');
$routes->post('perangkat/update_analisis_manual', 'PerangkatController::update_analisis_manual');
$routes->get('perangkat/delete_analisis_manual/(:num)', 'PerangkatController::delete_analisis_manual/$1');

$routes->post('perangkat/save_analisis_batch', 'PerangkatController::save_analisis_batch');

$routes->post('perangkat/copy_draft', 'PerangkatController::copy_draft_elemen');

$routes->post('admin/kaldik/category/store', 'KaldikController::storeCategory');
$routes->post('admin/kaldik/category/update', 'KaldikController::updateCategory');
$routes->get('admin/kaldik/category/delete/(:num)', 'KaldikController::deleteCategory/$1');
$routes->post('admin/kaldik/clear', 'KaldikController::clearKaldik');

$routes->get('guru/atp', 'AtpController::index');
$routes->post('guru/atp/simpan', 'AtpController::simpanAtp');
$routes->post('guru/atp/reset', 'AtpController::resetAtp');
$routes->post('guru/atp/copy', 'AtpController::copyAtp');

$routes->get('guru/modul-ajar', 'ModulAjarController::index');
$routes->get('guru/modul-ajar/create', 'ModulAjarController::create');

$routes->post('guru/modul-ajar/store', 'ModulAjarController::store');
$routes->post('guru/modul-ajar/reset', 'ModulAjarController::resetModul');

$routes->get('guru/kktp', 'KktpController::index');
$routes->post('guru/kktp/simpan', 'KktpController::simpan');
$routes->post('guru/kktp/reset', 'KktpController::reset');
$routes->post('guru/kktp/copy', 'KktpController::copyKktp');
$routes->get('guru/kktp/print', 'KktpController::printKktp');

$routes->post('guru/modul-ajar/generate-ai', 'ModulAjarController::generateAi');
$routes->get('guru/modul-ajar/print/(:num)', 'ModulAjarController::printModul/$1');
$routes->post('guru/modul-ajar/copy-all', 'ModulAjarController::copyAllModul');

$routes->get('profile', 'ProfileController::index');
$routes->post('profile/update', 'ProfileController::updateProfile');
$routes->post('profile/update-password', 'ProfileController::updatePassword');
$routes->get('profile/download-ttd', 'ProfileController::downloadTtd');

$routes->post('ai/generate_kktp_bulk', 'AiController::generateKktpBulk');
$routes->post('ai/generate_kktp', 'AiController::generateKktp');

$routes->get('jurnal-mengajar', 'JurnalGuruController::index');
$routes->post('jurnal-mengajar/simpan', 'JurnalGuruController::simpanJurnal');
$routes->get('jurnal-mengajar/print', 'JurnalGuruController::printJurnal');

//Version 2.0
// Jika Anda menggunakan method POST untuk form upload
$routes->post('/admin/users/importSmart', 'UserSiswaController::importSmart');
$routes->get('/admin/users/downloadExcelAktif', 'UserSiswaController::downloadExcelAktif');
$routes->post('admin/users/role-store', 'AdminController::storeRole');

$routes->group('admin/absensi', static function($routes) {
    // Halaman Index (Daftar Rombel)
    $routes->get('/', 'AbsensiController::index');
    // Halaman Input Absensi per Rombel
    $routes->get('input/(:num)', 'AbsensiController::input/$1');   
    // Proses Simpan Data
    $routes->post('store', 'AbsensiController::store');
    $routes->get('rekap', 'AbsensiController::rekap');
});

$routes->group('admin/kepatuhan', static function($routes) {
    $routes->get('/', 'KepatuhanController::index');
    // Rute di bawah ini akan kita buat di tahap selanjutnya
    $routes->get('input/(:num)', 'KepatuhanController::input/$1');
    $routes->post('save', 'KepatuhanController::save');
    $routes->get('rekap-kelas/(:num)', 'KepatuhanController::rekapKelas/$1');
    $routes->get('rekap-sekolah', 'KepatuhanController::rekapSekolah');
});

$routes->group('admin/rekap-sekolah', static function($routes) {
    // Arahkan ke Controller BARU yang sudah kita buat
    $routes->get('/', 'RekapSekolahController::index'); 
    
    // Arahkan rute hari efektif ke method di Controller BARU
    $routes->post('set-hari', 'RekapSekolahController::setHari');
    $routes->get('get-hari', 'RekapSekolahController::getHari');
});

$routes->group('siswa/yaumiyah', static function($routes) {
    $routes->get('/', 'YaumiyahController::index');
    $routes->post('save', 'YaumiyahController::save');
});

$routes->group('guru/yaumiyah', static function($routes) {
    $routes->get('/', 'RekapYaumiyahController::index');          // Daftar Kelas
    $routes->get('rekap/(:num)', 'RekapYaumiyahController::rekapKelas/$1'); // Laporan Kelas
    $routes->get('monitoring/(:num)', 'RekapYaumiyahController::monitoringBulanan/$1');
});

$routes->group('guru/quran', static function($routes) {
    $routes->get('/', 'PenilaianQuranController::index');
    // Rute di bawah ini disiapkan untuk tahap selanjutnya
    $routes->get('tahsin/(:num)', 'PenilaianQuranController::tahsin/$1');
    $routes->post('tahsin/save', 'PenilaianQuranController::saveTahsin');
    $routes->get('tahfidz/(:num)', 'PenilaianQuranController::tahfidz/$1');
    $routes->post('tahfidz/save', 'PenilaianQuranController::saveTahfidz');
    $routes->get('kitabah/(:num)', 'PenilaianQuranController::kitabah/$1');
    $routes->post('kitabah/save', 'PenilaianQuranController::saveKitabah');
    $routes->get('rekap/(:num)', 'PenilaianQuranController::rekap/$1');
    $routes->get('jurnal/(:num)', 'PenilaianQuranController::jurnal/$1');
    $routes->post('jurnal/save', 'PenilaianQuranController::saveJurnal');
    $routes->get('jurnal/delete/(:num)', 'PenilaianQuranController::deleteJurnal/$1');
});

$routes->group('admin/spiritual', static function($routes) {
    $routes->get('/', 'SpiritualController::index');
    $routes->get('input/(:num)', 'SpiritualController::input/$1');
    $routes->post('save', 'SpiritualController::save');
    $routes->get('rekap-kelas/(:num)', 'SpiritualController::rekapKelas/$1');
});

$routes->group('admin/aspek-sosial', static function($routes) {
    $routes->get('/', 'AspekSosialController::index');
    $routes->get('input/(:num)', 'AspekSosialController::input/$1');
    $routes->post('save', 'AspekSosialController::save');
    $routes->get('rekap-kelas/(:num)', 'AspekSosialController::rekap_kelas/$1');
});

// Route Group untuk Kelompok Al-Qur'an
$routes->group('guru/quran_kelompok', static function($routes) {
    // CRUD Dasar
    $routes->get('/', 'KelompokQuranController::index');              // Menampilkan daftar kelompok
    $routes->get('create', 'KelompokQuranController::create');        // Menampilkan form tambah kelompok
    $routes->post('store', 'KelompokQuranController::store');         // Memproses simpan data kelompok
    $routes->get('edit/(:num)', 'KelompokQuranController::edit/$1');  // Menampilkan form edit berdasarkan ID
    $routes->post('update/(:num)', 'KelompokQuranController::update/$1'); // Memproses update data
    $routes->get('delete/(:num)', 'KelompokQuranController::delete/$1');  // Menghapus data kelompok
    
    // Rencana Fitur Rekap
    $routes->get('rekap-kelompok', 'KelompokQuranController::rekapKelompok'); // Halaman rekap per pembimbing
    $routes->get('rekap-kelas', 'KelompokQuranController::rekapKelas');       // Halaman rekap per kelas reguler
    $routes->get('show/(:num)', 'KelompokQuranController::show/$1');
});

// Modul Jurnal Keputraan & Keputrian (Disatukan)
$routes->get('guru/jurnal-karakter', 'JurnalKarakterController::index');
$routes->post('guru/jurnal-karakter/save', 'JurnalKarakterController::saveJurnal');
$routes->get('guru/jurnal-karakter/delete/(:segment)/(:num)', 'JurnalKarakterController::deleteJurnal/$1/$2');

$routes->group('guru/ekstrakurikuler', static function($routes) {
    // Menampilkan daftar eskul
    $routes->get('/', 'EkstrakurikulerController::index');
    // Rute Manajemen Kelompok Eskul
    $routes->get('kelompok/create', 'EkstrakurikulerController::kelompokCreate');
    $routes->post('kelompok/store', 'EkstrakurikulerController::kelompokStore');
    $routes->get('kelompok/show/(:num)', 'EkstrakurikulerController::kelompokShow/$1');
    $routes->get('kelompok/edit/(:num)', 'EkstrakurikulerController::kelompokEdit/$1');
    $routes->post('kelompok/update/(:num)', 'EkstrakurikulerController::kelompokUpdate/$1');
    $routes->get('kelompok/delete/(:num)', 'EkstrakurikulerController::kelompokDelete/$1');
    // Rute untuk input nilai eskul
$routes->get('kelompok/input/(:num)', 'EkstrakurikulerController::kelompokInput/$1');
$routes->post('kelompok/save_nilai/(:num)', 'EkstrakurikulerController::kelompokSaveNilai/$1');
});

// Routes Pramuka
$routes->group('guru/pramuka', static function($routes) {
    // Asumsi halaman index menampilkan daftar kelas/rombel
    $routes->get('input/(:num)', 'PramukaController::input/$1');
    $routes->post('save_nilai/(:num)', 'PramukaController::saveNilai/$1');
});

// Routes Peminatan
$routes->group('guru/peminatan', static function($routes) {
    $routes->get('input/(:num)', 'PeminatanController::input/$1');
    $routes->post('save_nilai/(:num)', 'PeminatanController::saveNilai/$1');
});

// Modul Penilaian Sumatif
$routes->get('guru/nilai-sumatif', 'NilaiSumatifController::index');
$routes->post('guru/nilai-sumatif/simpan', 'NilaiSumatifController::simpanNilai');

$routes->get('admin/laporan-siswa', 'LaporanSiswaController::index');
$routes->get('admin/laporan-siswa/get-siswa-by-kelas', 'LaporanSiswaController::getSiswaByKelas');

$routes->get('catatansiswa', 'CatatanSiswaController::index');
$routes->post('catatansiswa/simpanAnekdot', 'CatatanSiswaController::simpanAnekdot');
$routes->post('catatansiswa/simpanPrestasi', 'CatatanSiswaController::simpanPrestasi');
$routes->get('catatansiswa/rekap', 'CatatanSiswaController::rekap');

$routes->get('catatansiswa/rekapAll', 'CatatanSiswaController::rekapAll');
$routes->post('catatansiswa/updateAnekdot', 'CatatanSiswaController::updateAnekdot');
$routes->post('catatansiswa/hapusAnekdot', 'CatatanSiswaController::hapusAnekdot');
$routes->post('catatansiswa/updatePrestasi', 'CatatanSiswaController::updatePrestasi');
$routes->post('catatansiswa/hapusPrestasi', 'CatatanSiswaController::hapusPrestasi');
