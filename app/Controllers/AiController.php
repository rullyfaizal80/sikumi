<?php

namespace App\Controllers;

use App\Controllers\BaseController;

class AiController extends BaseController
{
    public function index()
    {
        // 1. Deteksi Role
        $session = session();
        $userRole = strtolower($session->get('role') ?? $session->get('level') ?? 'guru'); 
        
        $isGuru = ($userRole === 'guru' || $userRole === 'teacher');
        $displayRole = $isGuru ? 'GURU' : 'ADMIN/WAKA';

        $data = [
            'title' => 'SiKuMi AI Assistant',
            'displayRole' => $displayRole,
            'isGuru' => $isGuru
        ];

        return view('admin/ai/chat', $data);
    }

   public function sendMessage()
    {
        $pesanUser = $this->request->getPost('message');

        if (empty($pesanUser)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.', 'reply' => 'Pesan tidak boleh kosong.']);
        }

        // ==============================================================================
        // 📥 1. AMBIL KUNCI API (Prioritas: Akun Guru -> Default Server)
        // ==============================================================================
        $db = \Config\Database::connect();
        $session = session();
        
        $userId = $session->get('id') ?? $session->get('user_id') ?? user_id();
        $apiKey = '';

        // [LANGKAH A] Coba ambil API Key mandiri milik guru dari tabel 'users'
        if ($userId) {
            $userRow = $db->table('users')->select('api_key_ai')->where('id', $userId)->get()->getRowArray();
            if ($userRow && !empty(trim($userRow['api_key_ai']))) {
                $apiKey = trim($userRow['api_key_ai']);
            }
        }

        // [LANGKAH B] Bila guru belum punya, ambil kunci default dari tabel 'settings'
        if (empty($apiKey)) {
            $apiKeySetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray() : null;
            $apiKey = $apiKeySetting ? trim($apiKeySetting['value']) : '';
        }

        // Validasi: Jika tidak ada kunci sama sekali
        if (empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'Anda belum memasukkan Groq API Key di Pengaturan Akun.',
                'reply' => 'Fitur AI ditangguhkan. Anda belum memasukkan token Groq API Key di halaman Pengaturan Akun Anda.'
            ]);
        }

        // ==============================================================================
        // 🔗 2. TETAPKAN URL GROQ SECARA PERMANEN (MENCEGAH ERROR 'MALFORMED')
        // ==============================================================================
        // URL ini langsung dikunci di dalam sistem agar tidak terjadi salah alamat.
        $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

        // ==============================================================================
        // 🧠 3. SUSUN DATA UNTUK API
        // ==============================================================================
        $systemInstruction = "Anda adalah SiKuMi, Asisten AI Pintar terintegrasi di sistem SmartKurikulum MIMHa (MTs Miftahul Huda Bandung). "
                           . "Tugas utama Anda adalah membantu guru merancang perangkat ajar, membedah Capaian Pembelajaran (CP) menjadi Tujuan Pembelajaran (TP) dan Alur Tujuan Pembelajaran (ATP), serta menyusun Modul Ajar berstandar Kurikulum Merdeka. "
                           . "Anda menguasai pendekatan Understanding by Design (UbD), Teaching at the Right Level (TaRL), dan Experiential Learning (EL). "
                           . "Anda sangat mahir dan menguasai SEMUA mata pelajaran umum tingkat MTs. Berikan jawaban yang terstruktur, rapi, praktis, dan langsung pada intinya.";

        $data = [
            'model' => 'llama-3.3-70b-versatile', 
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $pesanUser]
            ],
            'temperature' => 0.7,
            'max_tokens' => 2048
        ];

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];

        // ==============================================================================
        // 🚀 4. EKSEKUSI cURL
        // ==============================================================================
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HEADER, true); 
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 
        
        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch); 
        
        // JIKA KONEKSI INTERNET SERVER GAGAL
        if ($responseRaw === false) {
            curl_close($ch);
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal menghubungi server Groq. Detail cURL: ' . $curlError,
                'reply' => 'Maaf, gagal menghubungi server AI. Pastikan server aplikasi Anda memiliki koneksi internet aktif. Detail: ' . $curlError
            ]);
        }

        // Pisahkan Header dan Body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE); 
        $headerStr = substr($responseRaw, 0, $headerSize); 
        $body = substr($responseRaw, $headerSize); 
        curl_close($ch);

        // ==============================================================================
        // 📥 5. TANGANI BALASAN DARI GROQ
        // ==============================================================================
        $responseData = json_decode($body, true);

        // Jika respons bukan JSON (Server down/Error 500)
        if (!$responseData && $httpCode >= 400) {
             return $this->response->setJSON([
                'status' => 'error',
                'message' => "Sistem AI Menolak (Code $httpCode): Respons server gagal dibaca.",
                'reply' => "Sistem AI Menolak (Code $httpCode): Respons server cacat atau gagal terbaca."
            ]);
        }

        // Jika kena limit (HTTP 429)
        if ($httpCode == 429) {
            preg_match('/x-ratelimit-reset-requests:\s*([0-9a-zA-Z]+)/i', $headerStr, $matches);
            $resetWaktu = $matches[1] ?? 'segera';
            
            return $this->response->setJSON([
                'status' => 'error',
                'message' => "Kuota AI API habis. Reset otomatis dalam {$resetWaktu}.",
                'reply' => "Kuota Limit Token Anda habis. Sistem akan mereset otomatis dalam <b>{$resetWaktu}</b>. Mohon bersabar!"
            ]);
        }

        // Jika BERHASIL (HTTP Status 200 OK)
        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($responseData['choices'][0]['message']['content'])) {
                $balasanSiKuMi = $responseData['choices'][0]['message']['content'];
                
                // Merapikan format Markdown menjadi HTML
                $balasanSiKuMi = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $balasanSiKuMi);
                $balasanSiKuMi = str_replace(['```html', '```'], '', $balasanSiKuMi);
                
                return $this->response->setJSON([
                    'status' => 'success',
                    'data' => trim($balasanSiKuMi),
                    'reply' => trim($balasanSiKuMi)
                ]);
            }
        }

        // Jika Terjadi Kesalahan Lain (Kunci Salah, Model Tidak Ada, dsb)
        $errorMessage = $responseData['error']['message'] ?? 'Kesalahan identitas atau kredensial API.';
        
        return $this->response->setJSON([
            'status' => 'error',
            'message' => "Groq Error (Code $httpCode): " . $errorMessage,
            'reply' => "Groq Server Menolak (Code $httpCode): " . $errorMessage
        ]);
    }

    public function analyzeCp()
    {
        $pesanUser = $this->request->getPost('message');

        if (empty($pesanUser)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data CP tidak boleh kosong.', 'reply' => 'Data CP tidak boleh kosong.']);
        }

        // ==============================================================================
        // 📥 1. AMBIL KUNCI API (Prioritas: Akun Guru -> Default Server)
        // ==============================================================================
        $db = \Config\Database::connect();
        $session = session();
        
        $userId = $session->get('id') ?? $session->get('user_id') ?? user_id();
        $apiKey = '';

        // [LANGKAH A] Coba ambil API Key mandiri milik guru dari tabel 'users'
        if ($userId) {
            $userRow = $db->table('users')->select('api_key_ai')->where('id', $userId)->get()->getRowArray();
            if ($userRow && !empty(trim($userRow['api_key_ai']))) {
                $apiKey = trim($userRow['api_key_ai']);
            }
        }

        // [LANGKAH B] Bila guru belum punya, ambil kunci default dari tabel 'settings'
        if (empty($apiKey)) {
            $apiKeySetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray() : null;
            $apiKey = $apiKeySetting ? trim($apiKeySetting['value']) : '';
        }

        // Validasi: Jika tidak ada kunci sama sekali
        if (empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'Anda belum memasukkan Groq API Key di Pengaturan Akun.',
                'reply' => 'Fitur AI ditangguhkan. Anda belum memasukkan token Groq API Key di halaman Pengaturan Akun Anda.'
            ]);
        }

        // ==============================================================================
        // 🔗 2. TETAPKAN URL GROQ SECARA PERMANEN (MENCEGAH ERROR 'MALFORMED')
        // ==============================================================================
        $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

        // SYSTEM PROMPT KHUSUS DARI PAK RULLY
        $systemInstruction = "Anda adalah Kurikulum AI Expert yang ahli dalam pengembangan Kurikulum Merdeka dan pendekatan Understanding by Design (UbD). Tugas Anda adalah membantu guru menganalisis Capaian Pembelajaran (CP) untuk menghasilkan dokumen perencanaan yang siap pakai.\n"
                           . "Instruksi Analisis:\n"
                           . "1. Analisis Komponen: Pecah CP menjadi Kompetensi (KKO) dan Lingkup Materi.\n"
                           . "2. Perumusan TP: Buat Tujuan Pembelajaran (TP) yang SMART. Untuk mata pelajaran praktik, pastikan TP memiliki aspek keterampilan yang jelas.\n"
                           . "3. Penetapan KKTP: Berikan indikator keberhasilan yang terukur (dapat berupa deskripsi kriteria atau rubrik).\n"
                           . "4. Pemetaan Waktu: Berikan estimasi beban Jam Pelajaran (JP) untuk setiap TP berdasarkan tingkat kompleksitas, dengan total alokasi yang disesuaikan dengan input semester pengguna.\n"
                           . "5. Aktivitas Pembelajaran: Sarankan 1-2 kegiatan belajar yang berbasis Experiential Learning atau Problem-Based Learning yang relevan dengan materi.\n"
                           . "Aturan Format Output:\n"
                           . "Sajikan dalam bentuk Tabel HTML dengan class 'table table-bordered' yang terdiri dari: [Elemen | Tujuan Pembelajaran (TP) | Lingkup Materi | KKTP | Estimasi JP | Aktivitas Pembelajaran]. Jangan gunakan Markdown (```html), langsung output tag HTML-nya saja.\n"
                           . "Setelah tabel, berikan ringkasan singkat mengenai saran urutan materi (scaffolding) untuk satu semester tersebut agar pembelajaran lebih efektif.\n"
                           . "Karakteristik Respons:\n"
                           . "- Gunakan bahasa yang suportif dan pedagogis.\n"
                           . "- Jika pengguna memasukkan CP yang sangat luas, bantu guru memecahnya menjadi unit-unit yang masuk akal untuk durasi 1 semester (asumsi 18 minggu).\n"
                           . "- Selalu prioritaskan keberhasilan murid di kelas (Student-Centered).";

        $data = [
            'model' => 'llama-3.3-70b-versatile', 
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $pesanUser]
            ],
            'temperature' => 0.6, // Dibuat 0.6 agar lebih presisi dalam tabel
            'max_tokens' => 3000
        ];

        $headers = [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ];

        // ==============================================================================
        // 🚀 3. EKSEKUSI cURL (Dengan Proteksi Header & Koneksi)
        // ==============================================================================
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HEADER, true); // Aktifkan pembacaan header untuk rate-limit
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);

        // JIKA KONEKSI INTERNET SERVER GAGAL
        if ($responseRaw === false) {
            curl_close($ch);
            return $this->response->setJSON([
                'status' => 'error',
                'message' => 'Gagal menghubungi server Groq. Detail cURL: ' . $curlError,
                'reply' => 'Maaf, analisis CP gagal dikirim. Terjadi masalah koneksi internet pada server aplikasi Anda. Detail: ' . $curlError
            ]);
        }

        // Pisahkan Header dan Body
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE); 
        $headerStr = substr($responseRaw, 0, $headerSize); 
        $body = substr($responseRaw, $headerSize); 
        curl_close($ch);

        // ==============================================================================
        // 📥 4. TANGANI BALASAN DARI GROQ
        // ==============================================================================
        $responseData = json_decode($body, true);

        // Jika respons bukan JSON (Server down/Error 500)
        if (!$responseData && $httpCode >= 400) {
             return $this->response->setJSON([
                'status' => 'error',
                'message' => "Sistem AI Menolak (Code $httpCode): Respons server gagal dibaca.",
                'reply' => "Sistem AI Menolak (Code $httpCode): Respons server dari Groq cacat atau gagal terbaca."
            ]);
        }

        // Jika kena rate limit (HTTP 429 Too Many Requests)
        if ($httpCode == 429) {
            preg_match('/x-ratelimit-reset-requests:\s*([0-9a-zA-Z]+)/i', $headerStr, $matches);
            $resetWaktu = $matches[1] ?? 'segera';
            
            return $this->response->setJSON([
                'status' => 'error',
                'message' => "Kuota AI API habis. Reset otomatis dalam {$resetWaktu}.",
                'reply' => "Beban analisis CP sedang penuh (Rate Limit). Sistem akan mereset otomatis dalam <b>{$resetWaktu}</b>. Mohon dicoba beberapa saat lagi ya!"
            ]);
        }

        // Jika BERHASIL (HTTP Status 200 OK)
        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($responseData['choices'][0]['message']['content'])) {
                $balasanSiKuMi = $responseData['choices'][0]['message']['content'];
                
                // Membersihkan sisa markdown code block jika model AI tidak sengaja menuliskannya
                $balasanSiKuMi = str_replace(['```html', '```'], '', $balasanSiKuMi);
                
                return $this->response->setJSON([
                    'status' => 'success',
                    'data' => trim($balasanSiKuMi),
                    'reply' => trim($balasanSiKuMi)
                ]);
            }
        }

        // Jika Terjadi Kesalahan Lain (Kunci Salah, Model Tidak Ada, dsb)
        $errorMessage = $responseData['error']['message'] ?? 'Kesalahan identitas atau kredensial API.';
        
        return $this->response->setJSON([
            'status' => 'error',
            'message' => "Groq Error (Code $httpCode): " . $errorMessage,
            'reply' => "Sistem AI Menolak Analisis (Code $httpCode): " . $errorMessage
        ]);
    }

    public function generateKktp()
    {
        $pesanUser = $this->request->getPost('message');

        if (empty($pesanUser)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Prompt tidak boleh kosong.']);
        }

        // ==============================================================================
        // 📥 1. AMBIL KUNCI API (Prioritas: Akun Guru -> Default Server)
        // ==============================================================================
        $db = \Config\Database::connect();
        $session = session();
        
        $userId = $session->get('id') ?? $session->get('user_id') ?? user_id();
        $apiKey = '';

        if ($userId) {
            $userRow = $db->table('users')->select('api_key_ai')->where('id', $userId)->get()->getRowArray();
            if ($userRow && !empty(trim($userRow['api_key_ai']))) {
                $apiKey = trim($userRow['api_key_ai']);
            }
        }

        if (empty($apiKey)) {
            $apiKeySetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray() : null;
            $apiKey = $apiKeySetting ? trim($apiKeySetting['value']) : '';
        }

        if (empty($apiKey)) {
            return $this->response->setJSON(['status' => 'error', 'reply' => '{"error": "Kunci API AI belum dipasang di sistem."}']);
        }

        // ==============================================================================
        // 🔗 2. TETAPKAN URL & INSTURKSI KHUSUS JSON MODE
        // ==============================================================================
        $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';

        // System prompt di-setting steril agar AI patuh 100% menghasilkan JSON tanpa basa-basi
        $systemInstruction = "Anda adalah Mesin Generator JSON otomatis khusus Kurikulum Merdeka. "
                           . "Tugas Anda adalah mengubah target acuan pembelajaran menjadi komponen rubrik penilaian. "
                           . "DILARANG keras memberikan kalimat pengantar, penutup, atau tanda kurung markdown seperti ```json. "
                           . "Langsung berikan output teks berupa format JSON objek murni yang valid.";

        $data = [
            'model' => 'llama-3.3-70b-versatile', 
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $pesanUser]
            ],
            'temperature' => 0.2, // Di-set rendah agar AI sangat patuh pada format struktur JSON
            'max_tokens' => 1000
        ];

        $headers = [ 'Authorization: Bearer ' . $apiKey, 'Content-Type: application/json' ];

        // ==============================================================================
        // 🚀 3. EKSEKUSI cURL
        // ==============================================================================
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $responseData = json_decode($responseRaw, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($responseData['choices'][0]['message']['content'])) {
                $balasanSiKuMi = $responseData['choices'][0]['message']['content'];
                return $this->response->setJSON(['status' => 'success', 'reply' => trim($balasanSiKuMi)]);
            }
        }

        return $this->response->setJSON(['status' => 'error', 'reply' => '{"error": "Server AI Mengalami Kendala."}']);
    }

    // ==============================================================================
    // 1. FITUR MASSAL / BULK (ANTI ERROR 429 & FIX MATCHING API KEY PROFIL)
    // ==============================================================================
    public function generateKktpBulk()
    {
        // 1. Ambil payload array data dari Javascript
        $rowsJson = $this->request->getPost('rows');
        $instruksiTambahan = $this->request->getPost('instruksi_tambahan') ?? '';

        if (empty($rowsJson)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Tidak ada data baris yang dikirim.']);
        }

        $rows = json_decode($rowsJson, true);
        if (!is_array($rows) || empty($rows)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Format payload data tidak valid.']);
        }

        // ==============================================================================
        // 📥 2. AMBIL KUNCI API (DISAMAKAN 100% DENGAN MODUL SATUAN YANG NORMAL)
        // ==============================================================================
        $db = \Config\Database::connect();
        $session = session();
        
        $userId = $session->get('id') ?? $session->get('user_id') ?? user_id(); // <-- FIX PERBAIKAN DI SINI
        $apiKey = '';

        // Coba ambil API Key mandiri milik guru dari tabel 'users'
        if ($userId) {
            $userRow = $db->table('users')->select('api_key_ai')->where('id', $userId)->get()->getRowArray();
            if ($userRow && !empty(trim($userRow['api_key_ai']))) {
                $apiKey = trim($userRow['api_key_ai']);
            }
        }

        // Bila guru belum punya di profil, ambil kunci default dari tabel 'settings'
        if (empty($apiKey)) {
            $apiKeySetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray() : null;
            $apiKey = $apiKeySetting ? trim($apiKeySetting['value']) : '';
        }

        // Proteksi Akhir jika benar-benar tidak ada kunci sama sekali
        if (empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'API Key AI Groq belum dikonfigurasi di profil Anda maupun Pengaturan sistem.'
            ]);
        }

        // 3. AMBIL URL ENDPOINT / PROVIDER
        $providerSetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_provider')->get()->getRowArray() : null;
        $apiUrl = $providerSetting ? trim($providerSetting['value']) : '';
        
        if (empty($apiUrl) || !filter_var($apiUrl, FILTER_VALIDATE_URL)) {
            $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
        }

        // 4. Susun seluruh materi baris menjadi satu kesatuan teks untuk prompt tunggal
        $daftarMateriPrompt = "";
        foreach ($rows as $index => $row) {
            $nomor = $index + 1;
            $daftarMateriPrompt .= "Data ke-{$nomor}:\n";
            $daftarMateriPrompt .= "- ID: {$row['id']}\n";
            $daftarMateriPrompt .= "- TP: \"{$row['tp']}\"\n";
            $daftarMateriPrompt .= "- Acuan Target: \"{$row['acuan']}\"\n\n";
        }

        // 5. Buat System Instruction ketat agar AI mengembalikan format Array JSON murni
        $systemInstruction = "Anda adalah pakar Kurikulum Merdeka nasional sekaligus sistem otomasi rubrik penilaian.\n"
            . "Tugas Anda adalah memproses SELURUH data materi yang diberikan secara massal dan menjabarkannya menjadi tingkat pencapaian KKTP.\n"
            . "Output WAJIB berupa raw JSON array murni tanpa format markdown (TANPA ```json ... ```), tanpa kalimat pembuka/sapaan, dan tanpa penutup.\n"
            . "Format balasan harus berupa susunan Array Of Objects persis seperti ini:\n"
            . "[\n"
            . "  {\"id\":\"MASUKKAN_ID_DATA_DI_SINI\", \"indikator\":\"...\", \"sb\":\"...\", \"b\":\"...\", \"c\":\"...\", \"pb\":\"...\"}\n"
            . "]\n"
            . "Aturan penulisan teks rubrik:\n"
            . "- Kolom 'id' wajib diisi persis sama dengan ID data masukan yang bersangkutan (JANGAN diganti/diacak).\n"
            . "- Kolom 'indikator' diisi ringkasan target acuan utama.\n"
            . "- Kolom 'sb' (Sangat Baik / Melampaui acuan).\n"
            . "- Kolom 'b' (Baik / Mencapai acuan persis).\n"
            . "- Kolom 'c' (Cukup / Hampir mencapai acuan).\n"
            . "- Kolom 'pb' (Perlu Bimbingan / Belum mencapai acuan).\n"
            . "- DILARANG menggunakan baris baru (enter) atau tanda petik dua (\") di dalam teks nilai rubrik. Gunakan petik tunggal (') jika terpaksa.";

        $pesanUser = "Berikut adalah daftar materi yang harus Anda rancang rubriknya sekaligus dalam 1 output array JSON:\n\n" . $daftarMateriPrompt;
        if (!empty($instruksiTambahan)) {
            $pesanUser .= "\nINSTRUKSI TAMBAHAN WAJIB DARI GURU: \"{$instruksiTambahan}\"";
        }

        // 6. Pack Data Kiriman ke Groq
        $payloadGroq = [
            'model' => 'llama-3.3-70b-versatile', 
            'messages' => [
                ['role' => 'system', 'content' => $systemInstruction],
                ['role' => 'user', 'content' => $pesanUser]
            ],
            'temperature' => 0.3, 
            'max_tokens' => 4000
        ];

        // 7. Eksekusi cURL Tunggal ke Server Groq
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json'
        ]); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payloadGroq));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $responseRaw = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 8. Handling Khusus Error Rate Limit 429
        if ($httpCode === 429) {
            return $this->response->setJSON([
                'status' => 'error', 
                'message' => 'Server AI Groq mendeteksi over-traffic (Rate Limit 429). Silakan tunggu sekitar 1 menit lalu klik tombol mulai lagi.'
            ]);
        }

        $responseData = json_decode($responseRaw, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($responseData['choices'][0]['message']['content'])) {
                $balasanAi = $responseData['choices'][0]['message']['content'];
                return $this->response->setJSON([
                    'status' => 'success', 
                    'reply' => $balasanAi
                ]);
            }
        }

        return $this->response->setJSON([
            'status' => 'error', 
            'message' => 'Groq gagal merespon dengan benar. Kode Status HTTP: ' . $httpCode
        ]);
    }
}