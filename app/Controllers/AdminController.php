<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Shield\Models\UserModel;
use App\Models\CustomRoleModel;

class AdminController extends BaseController
{

public function index()
{
    $userModel = new UserModel();
    $roleModel = new CustomRoleModel();

    // 1. GANTI findAll() MENJADI paginate() UNTUK MEMBATASI DATA (Misal: 10 data per halaman)
    // Fitur ini otomatis membaca parameter '?page=' dari URL Anda
    $daftarUser = $userModel->paginate(10, 'default');

    // 2. Ambil objek pager untuk menggerakkan komponen navigasi tombol di halaman view
    $pager = $userModel->pager;

    $daftarRole = $roleModel->findAll();

    // KODE BARU Anda tetap dipertahankan dengan sempurna
    $db = \Config\Database::connect();
    $builder = $db->table('auth_groups_users agu');
    $builder->select('agu.user_id, cr.role_title');
    $builder->join('custom_roles cr', 'cr.role_name = agu.group');
    $userGrupRaw = $builder->get()->getResultArray();

    // Kelompokkan Nama Resmi Peran berdasarkan ID Pengguna
    $peranUser = [];
    foreach ($userGrupRaw as $row) {
        $peranUser[$row['user_id']][] = $row['role_title'];
    }

    // 3. Masukkan variabel 'pager' ke dalam array data kiriman ke view
    $data = [
        'users'      => $daftarUser,
        'roles'      => $daftarRole, 
        'peranUser'  => $peranUser,
        'pager'      => $pager // <-- Wajib dikirimkan untuk mencetak tombol halaman
    ];

    return view('admin/user_management', $data);
}

    // Tambahkan fungsi ini di dalam file app/Controllers/AdminController.php
public function storeRole()
{
    // 1. Tangkap data input dari form visual
    $roleName  = $this->request->getPost('role_name');
    $roleTitle = $this->request->getPost('role_title');

    // 2. Masukkan data ke dalam tabel custom_roles menggunakan model kustom kita
    $roleModel = new \App\Models\CustomRoleModel();
    $roleModel->insert([
        'role_name'  => $roleName,
        'role_title' => $roleTitle,
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s')
    ]);

    // 3. Kembalikan ke halaman admin sambil membawa pesan sukses
    return redirect()->to('admin/users')->with('sukses', 'Peran jabatan baru "' . $roleTitle . '" berhasil disimpan ke database kustom!');
}

// Tambahkan fungsi pengelola multi-jabatan ini ke dalam app/Controllers/AdminController.php
public function updateUserRoles()
{
    $userId = $this->request->getPost('user_id');
    $roles  = $this->request->getPost('roles'); // Menangkap array banyak centangan jabatan

    $db = \Config\Database::connect();
    
    // 1. Bersihkan semua jabatan lama guru tersebut di tabel jembatan Shield
    $db->table('auth_groups_users')->where('user_id', $userId)->delete();

    // 2. Jika ada jabatan baru yang dicentang, masukkan semuanya secara massal (Batch)
    if (!empty($roles)) {
        $dataInsert = [];
        foreach ($roles as $role) {
            $dataInsert[] = [
                'user_id'    => $userId,
                'group'      => $role,
                'created_at' => date('Y-m-d H:i:s')
            ];
        }
        $db->table('auth_groups_users')->insertBatch($dataInsert);
    }

    // 3. Kembalikan ke halaman admin dengan membawa pesan sukses
    return redirect()->to('admin/users')->with('sukses', 'Daftar jabatan aktif guru berhasil diperbarui secara dinamis!');
}

// Fungsi untuk menampilkan halaman matriks centang menu
public function permissionMatrix()
{
    $db = \Config\Database::connect();
    
    // Ambil semua role kustom
    $roles = $db->table('custom_roles')->get()->getResultArray();
    
    // Ambil semua menu utama (parent_id adalah null) beserta sub-menunya
    $menusRaw = $db->table('custom_permissions')->get()->getResultArray();
    
    // Kelompokkan menu berdasarkan induknya (parent_id)
    $menus = [];
    foreach ($menusRaw as $m) {
        if ($m['parent_id'] === null) {
            $menus[$m['id']]['induk'] = $m;
        } else {
            $menus[$m['parent_id']]['anak'][] = $m;
        }
    }

    // Ambil semua data centangan yang sudah ada saat ini di tabel jembatan
    $matrixRaw = $db->table('custom_roles_permissions')->get()->getResultArray();
    $matrixActive = [];
    foreach ($matrixRaw as $row) {
        $matrixActive[$row['role_id']][] = $row['permission_id'];
    }

    $data = [
        'roles'        => $roles,
        'menus'        => $menus,
        'matrixActive' => $matrixActive
    ];

    return view('admin/permission_matrix', $data);
}

// Fungsi untuk mengeksekusi penyimpanan hasil centangan baru
public function saveMatrix()
{
    $db = \Config\Database::connect();
    $matrixInput = $this->request->getPost('matrix'); // Mengambil data array [role_id][permission_id]

    // 1. Kosongkan dulu semua hak akses lama demi menghindari data ganda
    $db->table('custom_roles_permissions')->truncate();

    // 2. Jika ada yang dicentang, masukkan secara massal
    if (!empty($matrixInput)) {
        $dataInsert = [];
        foreach ($matrixInput as $roleId => $permissionIds) {
            foreach ($permissionIds as $permId) {
                $dataInsert[] = [
                    'role_id'       => $roleId,
                    'permission_id' => $permId
                ];
            }
        }
        $db->table('custom_roles_permissions')->insertBatch($dataInsert);
    }

    return redirect()->to('admin/permission-matrix')->with('sukses', 'Matriks hak akses menu sekolah berhasil diperbarui secara dinamis!');
}
/*
// Fungsi untuk menampilkan daftar Tahun Pelajaran & Semester
public function academicSetting()
{
    $db = \Config\Database::connect();
    
    // Ambil seluruh daftar tahun akademik
    $daftarAkademik = $db->table('academic_years')->orderBy('academic_year', 'ASC')->get()->getResultArray();

    $data = [
        'academic' => $daftarAkademik
    ];

    return view('admin/academic_setting', $data);
}

// Fungsi untuk mengubah status semester aktif secara massal
public function activateAcademic($id)
{
    $db = \Config\Database::connect();

    // 1. Matikan seluruh status aktif tahun pelajaran lama (set is_active = 0)
    $db->table('academic_years')->update(['is_active' => 0]);

    // 2. Aktifkan ID tahun pelajaran yang dipilih oleh pengguna (set is_active = 1)
    $db->table('academic_years')->where('id', $id)->update(['is_active' => 1]);

    return redirect()->to('admin/academic')->with('sukses', 'Tahun Pelajaran & Semester aktif berhasil diperbarui!');
}
*/
public function storeUser()
{
    // 1. Tangkap data input dari form admin
    $username = $this->request->getPost('username');
    $email    = $this->request->getPost('email');
    $password = $this->request->getPost('password'); // Password acak awal dari admin

    // 2. Gunakan entitas User bawaan CodeIgniter Shield
    $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
    $user      = new \CodeIgniter\Shield\Entities\User([
        'username' => $username,
        'email'    => $email,
        'password' => $password,
    ]);

    // 3. Simpan ke database tabel users & auth_identities
    $userModel->save($user);

    // 4. Tarik ID user yang baru saja lahir
    $newUserId = $userModel->getInsertID();

    // 5. Otomatis berikan peran sebagai 'guru_pengajar' secara default
    $db = \Config\Database::connect();
    $db->table('auth_groups_users')->insert([
        'user_id'    => $newUserId,
        'group'      => 'guru_pengajar',
        'created_at' => date('Y-m-d H:i:s')
    ]);

    return redirect()->to('admin/users')->with('sukses', 'Akun Guru baru bernama "' . $username . '" berhasil didaftarkan oleh sistem!');
}

public function resetPassword($id)
{
    // 1. Panggil model User bawaan Shield Auth
    $userModel = model(\CodeIgniter\Shield\Models\UserModel::class);
    $user      = $userModel->find($id);

    if (!$user) {
        return redirect()->to('admin/users')->with('error', 'Akun pengguna tidak ditemukan!');
    }

    // 2. Setel ulang sandi ke format default lembaga: Mimha@2026
    $user->password = 'Mimha@2026';
    $userModel->save($user);

    return redirect()->to('admin/users')->with('sukses', 'Kata sandi untuk akun "' . $user->username . '" berhasil di-reset ke default: Mimha@2026');
}

/**
 * 3. PENYESUAIAN: Mengubah Status Semester Aktif (Fungsi bawaan Anda yang disinkronkan)
 */
public function activateAcademic($id)
{
    $db = \Config\Database::connect();

    // 1. Matikan seluruh status aktif tahun pelajaran lama
    $db->table('academic_years')->update(['is_active' => 0]);

    // 2. Aktifkan ID tahun pelajaran yang diklik oleh Waka Kurikulum
    $db->table('academic_years')->where('id', $id)->update(['is_active' => 1]);

    // MODIFIKASI: Alihkan lurus kembali ke halaman pengaturan satu pintu (Tab Akademik)
    return redirect()->to('admin/settings?tab=akademik')->with('sukses', 'Tahun Pelajaran & Semester aktif berhasil diperbarui!');
}

    public function appSettings()
    {
        $db = \Config\Database::connect();

        // 1. Ambil data teks pengaturan dari tabel settings dan konversi menjadi key-value array
        $settingsRaw = $db->table('settings')->get()->getResultArray();
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['key']] = $row['value'];
        }

        // 2. Ambil seluruh data tahun akademik untuk TAB 3 dan TAB 4
        $academic = $db->table('academic_years')
                       ->orderBy('academic_year', 'ASC')
                       ->orderBy('semester', 'ASC')
                       ->get()
                       ->getResultArray();

        // 3. Lempar data ke view
        $data = [
            'title'    => 'Pusat Pengaturan Terpadu SiKuMi',
            'settings' => $settings,
            'academic' => $academic
        ];

        return view('admin/app_settings', $data);
    }

    /**
     * PROSES UTAMA: Menyimpan Data Tab 1, Tab 2, dan Tab 5 (Metode Overwrite Logo)
     */
    public function saveSettings()
    {
        $db = \Config\Database::connect();
        
        // Kumpulkan input data teks biasa
        $textSettings = [
            'kaldik_lembaga_nama' => $this->request->getPost('kaldik_lembaga_nama'),
            'kaldik_kepala_nama'  => $this->request->getPost('kaldik_kepala_nama'),
            'kaldik_kepala_npk'   => $this->request->getPost('kaldik_kepala_npk'),
            'kaldik_titi_mangsa'  => $this->request->getPost('kaldik_titi_mangsa'),
            'kaldik_hari_kerja'   => $this->request->getPost('kaldik_hari_kerja'),
            'durasi_menit_jp'     => $this->request->getPost('durasi_menit_jp'),
            'ai_provider'         => $this->request->getPost('ai_provider'),
            'ai_api_key'          => $this->request->getPost('ai_api_key'),
        ];

        // Simpan atau Update data teks ke database
        foreach ($textSettings as $key => $value) {
            if ($value !== null) {
                $exist = $db->table('settings')->where('key', $key)->get()->getRow();
                if ($exist) {
                    $db->table('settings')->where('key', $key)->update(['value' => $value]);
                } else {
                    $db->table('settings')->insert(['key' => $key, 'value' => $value]);
                }
            }
        }

        // PROSES UPLOAD LOGO KIRI
        $logoKiri = $this->request->getFile('logo_kaldik1');
        if ($logoKiri && $logoKiri->isValid() && !$logoKiri->hasMoved()) {
            $targetPathKiri = FCPATH . 'assets/img/logo_kaldik1.png';
            if (file_exists($targetPathKiri)) {
                @unlink($targetPathKiri);
            }
            $logoKiri->move(FCPATH . 'assets/img', 'logo_kaldik1.png');
            $db->table('settings')->where('key', 'logo_kaldik1')->update(['value' => 'logo_kaldik1.png']);
        }

        // PROSES UPLOAD LOGO KANAN
        $logoKanan = $this->request->getFile('logo_kaldik2');
        if ($logoKanan && $logoKanan->isValid() && !$logoKanan->hasMoved()) {
            $targetPathXanan = FCPATH . 'assets/img/logo_kaldik2.png';
            if (file_exists($targetPathXanan)) {
                @unlink($targetPathXanan);
            }
            $logoKanan->move(FCPATH . 'assets/img', 'logo_kaldik2.png');
            $db->table('settings')->where('key', 'logo_kaldik2')->update(['value' => 'logo_kaldik2.png']);
        }

        return redirect()->to(base_url('admin/settings?tab=profil'))->with('sukses', 'Konfigurasi pusat berhasil disimpan!');
    }

    /**
     * PROSES PENDAMPING: Menambahkan Tahun Angkatan Baru (Otomatis Ganjil & Genap)
     */
    public function addAngkatan() 
    {
        $db = \Config\Database::connect();
        
        $rawInput = $this->request->getPost('academic_year');
        $tahunBaru = substr(trim($rawInput), 0, 9);

        if (empty($tahunBaru)) {
            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('error', 'Tahun angkatan tidak boleh kosong!');
        }

        if (strlen($tahunBaru) < 9) {
            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('error', 'Format salah! Wajib menggunakan format 9 karakter. Contoh: 2026/2027');
        }

        $cekTahun = $db->table('academic_years')
                       ->where('academic_year', $tahunBaru)
                       ->get()
                       ->getRow();

        if ($cekTahun) {
            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('error', "Peringatan! Tahun Angkatan/Pelajaran {$tahunBaru} sudah terdaftar di sistem.");
        }

        $currentDateTime = date('Y-m-d H:i:s');

        try {
            $db->table('academic_years')->insert([
                'academic_year' => $tahunBaru,
                'semester'      => 'Ganjil',
                'is_active'     => 0,
                'created_at'    => $currentDateTime,
                'updated_at'    => $currentDateTime
            ]);

            $db->table('academic_years')->insert([
                'academic_year' => $tahunBaru,
                'semester'      => 'Genap',
                'is_active'     => 0,
                'created_at'    => $currentDateTime,
                'updated_at'    => $currentDateTime
            ]);

            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('sukses', "Tahun Angkatan {$tahunBaru} untuk Semester Ganjil & Genap berhasil ditambahkan!");

        } catch (\Exception $e) {
            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('error', 'Kesalahan Sistem Database: ' . $e->getMessage());
        }
    }

    /**
     * FITUR AMAN: Menghapus Tahun Angkatan dengan Proteksi Relasi Tabel academic_calendars
     */
    public function deleteAngkatan()
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil string tahun yang dilempar dari form (Contoh: 2026/2027)
        $tahunHapus = $this->request->getPost('academic_year');

        if (empty($tahunHapus)) {
            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('error', 'Parameter tahun tidak valid!');
        }

        // 2. Proteksi Kunci 1: Jangan izinkan hapus jika statusnya sedang AKTIF digunakan salah satu semesternya
        $cekAktif = $db->table('academic_years')
                       ->where('academic_year', $tahunHapus)
                       ->where('is_active', 1)
                       ->get()
                       ->getRow();

        if ($cekAktif) {
            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('error', "⚠️ Gagal! Tahun {$tahunHapus} tidak boleh dihapus karena salah satu semesternya sedang disetel sebagai Semester Aktif.");
        }

        // 3. Ambil semua ID yang berkaitan dengan tahun tersebut (ID Semester Ganjil dan ID Semester Genap)
        $listAcademic = $db->table('academic_years')
                           ->where('academic_year', $tahunHapus)
                           ->get()
                           ->getResultArray();

        $allIds = [];
        foreach ($listAcademic as $row) {
            $allIds[] = $row['id'];
        }

        // 4. Proteksi Kunci 2: Cek apakah salah satu ID semester tersebut sudah memiliki data di tabel kalender akademik
        if (!empty($allIds)) {
            $cekKaldik = $db->table('academic_calendars') // Sesuai dengan struktur .sql asli Anda
                            ->whereIn('academic_year_id', $allIds) 
                            ->get()
                            ->getRow();

            // Jika di semester ganjil atau genap saja sudah ada data terisi, maka gagalkan penghapusan tahun pelajaran tersebut
            if ($cekKaldik) {
                return redirect()->to(base_url('admin/settings?tab=angkatan'))
                                 ->with('error', "❌ Tidak bisa dihapus! Tahun Angkatan {$tahunHapus} sudah terikat dengan data agenda di Kalender Akademik.");
            }
        }

        // 5. Eksekusi Hapus jika lolos semua sensor proteksi di atas
        try {
            $db->table('academic_years')->where('academic_year', $tahunHapus)->delete();

            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('sukses', "🗑️ Berhasil! Data Tahun Angkatan {$tahunHapus} yang masih kosong telah dibersihkan dari sistem.");
        } catch (\Exception $e) {
            return redirect()->to(base_url('admin/settings?tab=angkatan'))
                             ->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }
}
