<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Cell\DataType;

class UserSiswaController extends BaseController
{
    public function index()
    {
        $db = \Config\Database::connect();

        // 1. Ambil Parameter Pencarian & Halaman Aktif dari URL
        $keyword = $this->request->getGet('search') ?? '';
        $page    = (int) ($this->request->getGet('page_siswa') ?? 1);
        if ($page < 1) $page = 1;

        // 2. Batasi 10 baris per halaman agar jendela padat
        $limit = 10; 
        $offset = ($page - 1) * $limit;

        // 3. Query khusus mencari user grup 'siswa' + Join Profile Statis
        $builder = $db->table('users u')
                      ->select('u.id, u.username, u.active, u.status, u.created_at, ai.secret as email, 
                                sp.id as student_profile_id, sp.nisn, sp.nis, sp.gender, sp.phone_ortu, sp.birth_place, sp.birth_date')
                      ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                      ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner')
                      ->join('student_profiles sp', 'sp.user_id = u.id', 'left') // LEFT JOIN profil statis
                      ->where('u.deleted_at', null)
                      ->where('agu.group', 'siswa');

        if (!empty($keyword)) {
            $builder->groupStart()
                    ->like('u.username', $keyword)
                    ->orLike('ai.secret', $keyword)
                    ->orLike('sp.nisn', $keyword)
                    ->orLike('sp.nis', $keyword)
                    ->groupEnd();
        }

        // Hitung total data sebelum limitasi halaman
        $totalBuilder = clone $builder;
        $totalData = $totalBuilder->countAllResults(false);

        // Ambil potongan data untuk halaman aktif
        $daftarSiswa = $builder->orderBy('u.username', 'ASC')
                               ->limit($limit, $offset)
                               ->get()
                               ->getResultArray();

        // 4. Tempelkan Riwayat Rombel Otomatis ke masing-masing Siswa (Dari tabel baru)
        foreach ($daftarSiswa as &$siswa) {
            $siswa['history'] = $db->table('class_rombel_students crs')
                ->select('cr.rombel_name, mc.class_name as tingkat, ay.academic_year, ay.semester')
                ->join('class_rombel cr', 'cr.id = crs.rombel_id')
                ->join('master_classes mc', 'mc.id = cr.master_class_id')
                ->join('academic_years ay', 'ay.id = cr.academic_year_id')
                ->where('crs.student_id', $siswa['id'])
                ->orderBy('ay.id', 'DESC')
                ->get()->getResultArray();
        }
        
        unset($siswa); // Putuskan referensi agar perulangan di View tidak bentrok

        $totalHalaman = ceil($totalData / $limit);
        if ($totalHalaman < 1) $totalHalaman = 1;

        // 5. Ambil opsi data tahun akademik untuk kebutuhan form/view
        $tahunAkademik = $db->table('academic_years')->orderBy('academic_year', 'DESC')->orderBy('semester', 'DESC')->get()->getResultArray();

        // ===================================================================
        // KUNCI PERBAIKAN: BLOK NOMOR 5 (PENCARIAN student_academic_history)
        // YANG SEBELUMNYA MEMBUAT ERROR 1146 SEKARANG SUDAH DIHAPUS TOTAL.
        // ===================================================================

        // Send data ke view dengan key variabel asli milik view
        return view('admin/user_siswa_view', [
            'daftarSiswa'    => $daftarSiswa,
            'keyword'        => $keyword,
            'page'           => $page,
            'limit'          => $limit,
            'totalHalaman'   => $totalHalaman,
            'totalData'      => $totalData,
            'tahun_akademik' => $tahunAkademik
        ]);
    }

    /**
     * FUNGSI SAKLAR: Mengubah status login Siswa (Banned / Izinkan)
     */
    public function toggleStatus($id)
    {
        $db = \Config\Database::connect();
        
        $user = $db->table('users')->where('id', $id)->get()->getRow();
        
        if ($user) {
            $statusBaru = (strtolower($user->status ?? '') === 'banned') ? null : 'banned';
            
            $db->table('users')
               ->where('id', $id)
               ->update([
                   'status'     => $statusBaru,
                   'updated_at' => date('Y-m-d H:i:s')
               ]);
               
            $pesan = ($statusBaru === 'banned') ? '🔒 Akses login siswa berhasil dibekukan!' : '✅ Akses login siswa telah diizinkan kembali.';
            return redirect()->back()->with('sukses', $pesan);
        }

        return redirect()->back()->with('error', '❌ Siswa tidak ditemukan.');
    }

    public function storeSiswa()
    {
        $db = \Config\Database::connect();
        
        // 1. Ambil input dari form satu kesatuan (HANYA AKUN & BIODATA DASAR)
        $username    = $this->request->getPost('username');
        $email       = $this->request->getPost('email');
        $password    = $this->request->getPost('password'); // password login
        $nisn        = $this->request->getPost('nisn');
        $nis         = $this->request->getPost('nis');         // BARU
        $gender      = $this->request->getPost('gender');
        $phone_ortu  = $this->request->getPost('phone_ortu');  // BARU
        $birth_place = $this->request->getPost('birth_place'); // BARU
        $birth_date  = $this->request->getPost('birth_date');  // BARU
        
        // Variabel input Tahun Pelajaran dan Kelas SUDAH DIHAPUS dari sini

        // 2. MULAI PROSES TRANSAKSI SATU KESATUAN
        $db->transStart();

        // A. Masukkan ke tabel 'users' (Shield Auth)
        $db->table('users')->insert([
            'username'   => $username,
            'active'     => 1,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);
        $newUserId = $db->insertID(); // Ambil ID User yang baru saja tercipta

        // B. Masukkan Kredensial Email & Password ke 'auth_identities'
        $db->table('auth_identities')->insert([
            'user_id'    => $newUserId,
            'type'       => 'email_password',
            'secret'     => $email,
            'secret2'    => password_hash($password, PASSWORD_DEFAULT), // Enkripsi aman
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // C. Ikat hak akses sebagai 'siswa' di 'auth_groups_users'
        $db->table('auth_groups_users')->insert([
            'user_id'    => $newUserId,
            'group'      => 'siswa',
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // D. Masukkan Properti Induk Statis ke 'student_profiles'
        $db->table('student_profiles')->insert([
            'user_id'     => $newUserId,
            'nisn'        => !empty($nisn) ? $nisn : null,
            'nis'         => !empty($nis) ? $nis : null,
            'gender'      => $gender,
            'birth_place' => !empty($birth_place) ? $birth_place : null,
            'birth_date'  => !empty($birth_date) ? $birth_date : null,
            'phone_ortu'  => !empty($phone_ortu) ? $phone_ortu : null,
            'created_at'  => date('Y-m-d H:i:s'),
            'updated_at'  => date('Y-m-d H:i:s')
        ]);
        
        // BAGIAN E (student_academic_history) SEPENUHNYA DIHAPUS

        // 3. SELESAIKAN TRANSAKSI
        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Gagal menambahkan akun siswa terjadi kesalahan sistem.');
        }

        // Pesan sukses disesuaikan
        return redirect()->back()->with('sukses', '✅ Akun siswa berhasil dibuat! Siswa berstatus "Siswa Bebas" (Belum masuk kelas).');
    }

    /**
     * Memproses Perbaruan Data Profil & Akun Akun Siswa
     */
    public function updateSiswa($id)
    {
        $db = \Config\Database::connect();

        // 1. Ambil input data dari form modal edit
        $username   = $this->request->getPost('username');
        $email      = $this->request->getPost('email');
        $password   = $this->request->getPost('password');
        $nisn       = $this->request->getPost('nisn');
        $nis        = $this->request->getPost('nis');
        $gender     = $this->request->getPost('gender');
        $phone_ortu = $this->request->getPost('phone_ortu');
        $birth_place= $this->request->getPost('birth_place');
        $birth_date = $this->request->getPost('birth_date');

        $db->transStart();

        // 2. Update tabel dasar 'users'
        $db->table('users')->where('id', $id)->update([
            'username'   => $username,
            'updated_at' => date('Y-m-d H:i:s')
        ]);

        // 3. Update tabel 'auth_identities' (untuk email)
        $db->table('auth_identities')
           ->where('user_id', $id)
           ->where('type', 'email_password')
           ->update([
               'secret' => $email
           ]);

        // 4. Jika kolom password diisi, lakukan hashing dan perbarui password
        if (!empty($password) && strlen($password) >= 8) {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $db->table('auth_identities')
               ->where('user_id', $id)
               ->where('type', 'email_password')
               ->update([
                   'secret2' => $hashedPassword
               ]);
        }

        // 5. Cek apakah profil statis siswa sudah ada di database atau belum
        $profileExists = $db->table('student_profiles')->where('user_id', $id)->get()->getRow();

        $profileData = [
            'nisn'        => !empty($nisn) ? $nisn : null,
            'nis'         => !empty($nis) ? $nis : null,
            'gender'      => $gender,
            'phone_ortu'  => !empty($phone_ortu) ? $phone_ortu : null,
            'birth_place' => !empty($birth_place) ? $birth_place : null,
            'birth_date'  => !empty($birth_date) ? $birth_date : null,
            'updated_at'  => date('Y-m-d H:i:s')
        ];

        if ($profileExists) {
            // Jika sudah ada recordnya, lakukan update
            $db->table('student_profiles')->where('user_id', $id)->update($profileData);
        } else {
            // Antisipasi jika data profile belum terbuat sebelumnya, lakukan insert
            $profileData['user_id']    = $id;
            $profileData['created_at'] = date('Y-m-d H:i:s');
            $db->table('student_profiles')->insert($profileData);
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Gagal memperbarui data profil siswa.');
        }

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '✔️ Profil dan akun siswa berhasil diperbarui.');
    }

    /**
     * Hapus Akun Siswa (Smart Delete: Hard vs Soft)
     */
    public function deleteSiswa($id)
    {
        $db = \Config\Database::connect();
        
        // 1. Deteksi keberadaan Riwayat Akademik (Plotting Kelas Baru)
        // Cek langsung ke tabel class_rombel_students menggunakan student_id
        $historyCount = $db->table('class_rombel_students')
                           ->where('student_id', $id)
                           ->countAllResults();
                           
        $hasHistory = ($historyCount > 0);

        $db->transStart();

        if ($hasHistory) {
            // EKSEKUSI SOFT DELETE (Karena siswa sudah punya rekam jejak kelas)
            $db->table('users')->where('id', $id)->update([
                'deleted_at' => date('Y-m-d H:i:s'),
                'active'     => 0
            ]);
            $pesan = '🗑️ Akun dipindahkan ke Arsip (Soft Delete) karena siswa sudah memiliki rekam jejak rombel.';
        } else {
            // EKSEKUSI HARD DELETE (Bersihkan total karena statusnya Siswa Bebas/salah input)
            
            // Hapus profil statis (jika ada)
            $db->table('student_profiles')->where('user_id', $id)->delete();
            
            // Hapus data autentikasi dan role
            $db->table('auth_identities')->where('user_id', $id)->delete();
            $db->table('auth_groups_users')->where('user_id', $id)->delete();
            
            // Hapus akun utama
            $db->table('users')->where('id', $id)->delete();
            
            $pesan = '💥 Akun dihapus permanen (Hard Delete) dari sistem karena masih berstatus Siswa Bebas.';
        }

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->back()->with('error', '❌ Terjadi kesalahan saat memproses penghapusan akun.');
        }

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', $pesan);
    }

    /**
     * Halaman Gudang Arsip Siswa Terhapus (Trash)
     */
    public function trashSiswa()
    {
        $db = \Config\Database::connect();

        // Mengambil data siswa yang memiliki deleted_at (tidak null)
        $daftarTrash = $db->table('users u')
                          ->select('u.id, u.username, u.deleted_at, ai.secret as email, sp.nisn, sp.nis')
                          ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner')
                          ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
                          ->join('student_profiles sp', 'sp.user_id = u.id', 'left') // Join profile untuk NISN
                          ->where('agu.group', 'siswa')
                          ->where('u.deleted_at !=', null)
                          ->orderBy('u.deleted_at', 'DESC')
                          ->get()
                          ->getResultArray();

        return view('admin/user_siswa_trash_view', ['daftarTrash' => $daftarTrash]);
    }

    /**
     * Mengembalikan akun siswa dari masa Soft Delete (Restore)
     */
    public function restoreSiswa($id)
    {
        $db = \Config\Database::connect();
        
        $db->transStart();

        // Mengembalikan deleted_at menjadi NULL dan mengaktifkan kembali akun ('active' => 1)
        $db->table('users')->where('id', $id)->update([
            'deleted_at' => null,
            'active'     => 1
        ]);

        $db->transComplete();

        if ($db->transStatus() === FALSE) {
            return redirect()->to(base_url('admin/users/siswa-trash'))->with('error', '❌ Gagal memulihkan akun siswa.');
        }

        return redirect()->to(base_url('admin/users/siswa-tes'))->with('sukses', '✔️ Akun siswa berhasil dipulihkan! Akun tersebut kini aktif kembali di daftar utama.');
    }

    // ==========================================================
    // 1. FUNGSI IMPORT PINTAR (UPDATE BIKA ADA, INSERT JIKA BARU)
    // ==========================================================
    public function importSmart()
    {
        $file = $this->request->getFile('file_excel');
        
        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'Gagal upload file Excel.');
        }

        $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getTempName());
        $sheetData   = $spreadsheet->getActiveSheet()->toArray(null, true, true, true);
        
        $db = \Config\Database::connect();
        
        $jumlahInsert = 0;
        $jumlahUpdate = 0;
        $dataGagal    = []; 

        foreach ($sheetData as $rowIndex => $row) {
            if ($rowIndex == 1) continue; 

            $nama = trim($row['C'] ?? '');
            if (empty($nama)) continue; 

            $nis         = trim($row['A'] ?? '');
            $nisn        = trim($row['B'] ?? '');
            $email       = trim($row['D'] ?? '');
            $tempatLahir = trim($row['E'] ?? '');
            $tglLahir    = trim($row['F'] ?? '');
            $gender      = trim($row['G'] ?? '');
            $noHp        = trim($row['H'] ?? '');
            $password    = trim($row['I'] ?? '');

            // Ubah 0000-00-00 menjadi NULL agar aman
            if (empty($tglLahir) || $tglLahir == '0000-00-00' || $tglLahir == '-') {
                $tglLahir = null;
            }

            $userExist = $db->table('users')->where('username', $nama)->get()->getRow();
            $pesanErrorAsli = ''; // Variabel untuk menangkap error
            $isUpdate = false;

            // Mulai Transaksi
            $db->transStart();

            if ($userExist) {
                // ==========================
                // SKENARIO A: UPDATE DATA
                // ==========================
                $isUpdate = true;
                $userId   = $userExist->id;

                if (!$db->table('users')->where('id', $userId)->update(['updated_at' => date('Y-m-d H:i:s')])) {
                    $pesanErrorAsli = $db->error()['message'];
                }

                $dataProfile = [];
                if (!empty($nis)) $dataProfile['nis'] = $nis;
                if (!empty($nisn)) $dataProfile['nisn'] = $nisn;
                if (!empty($tempatLahir)) $dataProfile['birth_place'] = $tempatLahir;
                if ($tglLahir !== null) $dataProfile['birth_date'] = $tglLahir;
                if (!empty($gender)) $dataProfile['gender'] = $gender;
                if (!empty($noHp)) $dataProfile['phone_ortu'] = $noHp;

                if (!empty($dataProfile)) {
                    if (!$db->table('student_profiles')->where('user_id', $userId)->update($dataProfile)) {
                        $pesanErrorAsli = $db->error()['message'];
                    }
                }

                $dataIdentity = ['updated_at' => date('Y-m-d H:i:s')];
                if (!empty($email)) $dataIdentity['secret'] = $email;
                if (!empty($password)) $dataIdentity['secret2'] = password_hash($password, PASSWORD_BCRYPT);
                
                if (count($dataIdentity) > 1) {
                    if (!$db->table('auth_identities')->where('user_id', $userId)->where('type', 'email_password')->update($dataIdentity)) {
                        $pesanErrorAsli = $db->error()['message'];
                    }
                }

            } else {
                // ==========================
                // SKENARIO B: TAMBAH DATA BARU
                // ==========================
                if (empty($password)) $password = !empty($nis) ? $nis : '123456'; 
                if (empty($email)) $email = strtolower(str_replace(' ', '', $nama)) . rand(10,99) . '@sekolah.id';

                if (!$db->table('users')->insert([
                    'username'   => $nama,
                    'active'     => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ])) {
                    $pesanErrorAsli = "Tabel Users: " . $db->error()['message'];
                }
                
                $userId = $db->insertID();

                if (empty($pesanErrorAsli) && !$db->table('auth_identities')->insert([
                    'user_id'    => $userId,
                    'type'       => 'email_password',
                    'secret'     => $email,
                    'secret2'    => password_hash($password, PASSWORD_BCRYPT),
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ])) {
                    $pesanErrorAsli = "Tabel Auth: " . $db->error()['message'];
                }

                if (empty($pesanErrorAsli) && !$db->table('student_profiles')->insert([
                    'user_id'     => $userId,
                    'nis'         => $nis,
                    'nisn'        => $nisn,
                    'birth_place' => $tempatLahir,
                    'birth_date'  => $tglLahir,
                    'gender'      => $gender,
                    'phone_ortu'  => $noHp,
                ])) {
                    $pesanErrorAsli = "Tabel Profil: " . $db->error()['message'];
                }

                if (empty($pesanErrorAsli) && !$db->table('auth_groups_users')->insert([
                    'user_id'    => $userId,
                    'group'      => 'siswa',
                    'created_at' => date('Y-m-d H:i:s')
                ])) {
                    $pesanErrorAsli = "Tabel Group: " . $db->error()['message'];
                }
            }

            // Selesaikan transaksi
            $db->transComplete();

            // CEK HASIL AKHIR
            if ($db->transStatus() === FALSE || !empty($pesanErrorAsli)) {
                $alasan = !empty($pesanErrorAsli) ? $pesanErrorAsli : "Ditolak oleh sistem database";
                $dataGagal[] = $nama . " (Penyebab: " . $alasan . ")";
            } else {
                if ($isUpdate) {
                    $jumlahUpdate++;
                } else {
                    $jumlahInsert++;
                }
            }
        }
        
        $pesanGagal = "";
        if (!empty($dataGagal)) {
            $pesanGagal = "<br><br><b>" . count($dataGagal) . " siswa gagal diproses:</b><br>" . implode("<br>", $dataGagal);
        }
        
        return redirect()->back()->with('sukses', "Sistem Pintar Selesai! $jumlahInsert baru, $jumlahUpdate diperbarui." . $pesanGagal);
    }

    // ==========================================================
    // 2. FUNGSI DOWNLOAD EXCEL SISWA AKTIF
    // ==========================================================
    public function downloadExcelAktif()
    {
        $db = \Config\Database::connect();
        
        $siswaAktif = $db->table('users u')
            ->select('sp.nis, sp.nisn, u.username as nama, ai.secret as email, sp.birth_place, sp.birth_date, sp.gender, sp.phone_ortu')
            ->join('auth_groups_users agu', 'agu.user_id = u.id', 'inner')
            ->join('student_profiles sp', 'sp.user_id = u.id', 'left')
            ->join('auth_identities ai', 'ai.user_id = u.id AND ai.type = "email_password"', 'left')
            ->where('agu.group', 'siswa')
            ->where('u.active', 1)
            ->where('u.deleted_at', null)
            ->orderBy('u.username', 'ASC')
            ->get()->getResultArray();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $headers = ['A1'=>'NIS', 'B1'=>'NISN', 'C1'=>'Nama Siswa', 'D1'=>'Email', 'E1'=>'Tempat Lahir', 'F1'=>'Tanggal Lahir', 'G1'=>'Gender', 'H1'=>'No HP Ortu', 'I1'=>'Password Baru'];
        foreach ($headers as $cell => $val) {
            $sheet->setCellValue($cell, $val);
            $sheet->getStyle($cell)->getFont()->setBold(true);
        }

        $row = 2;
        foreach ($siswaAktif as $s) {
            // PAKSA MENJADI STRING AGAR TIDAK DIBULATKAN OLEH EXCEL
            $sheet->setCellValueExplicit('A' . $row, $s['nis'], DataType::TYPE_STRING);
            $sheet->setCellValueExplicit('B' . $row, $s['nisn'], DataType::TYPE_STRING);
            
            $sheet->setCellValue('C' . $row, $s['nama']);
            $sheet->setCellValue('D' . $row, $s['email']);
            $sheet->setCellValue('E' . $row, $s['birth_place']);
            $sheet->setCellValue('F' . $row, $s['birth_date']);
            $sheet->setCellValue('G' . $row, $s['gender']);
            
            // No HP juga dipaksa jadi string agar angka 0 di depan tidak hilang
            $sheet->setCellValueExplicit('H' . $row, $s['phone_ortu'], DataType::TYPE_STRING);
            
            $row++;
        }

        $writer = new Xlsx($spreadsheet);
        $fileName = 'Data_Siswa_Aktif_' . date('Y-m-d') . '.xlsx';

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="'. urlencode($fileName).'"');
        header('Cache-Control: max-age=0');
        
        $writer->save('php://output');
        exit;
    }

}