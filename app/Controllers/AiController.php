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
}