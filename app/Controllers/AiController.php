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
            return $this->response->setJSON(['status' => 'error', 'message' => 'Pesan tidak boleh kosong.']);
        }

        // ==============================================================================
        // 📥 1. AMBIL PENGATURAN API DARI DATABASE
        // ==============================================================================
        $db = \Config\Database::connect();
        
        // Ambil API Key
        $apiKeySetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray() : null;
        $apiKey = $apiKeySetting ? trim($apiKeySetting['value']) : '';

        // Ambil URL Endpoint dari kolom ai_provider
        $apiProviderSetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_provider')->get()->getRowArray() : null;
        // Jika di database kosong, gunakan URL Groq sebagai fallback (cadangan) default
        $apiUrl = (!empty($apiProviderSetting['value'])) ? trim($apiProviderSetting['value']) : 'https://api.groq.com/openai/v1/chat/completions';

        if (empty($apiKey)) {
            return $this->response->setJSON([
                'status' => 'success', 
                'reply' => 'Maaf, kunci akses API belum dipasang di database pengaturan.'
            ]);
        }

        // ==============================================================================
        // 🧠 2. SYSTEM INSTRUCTION (Membentuk Karakter AI)
        // ==============================================================================
        $systemInstruction = "Anda adalah SiKuMi, Asisten AI Pintar terintegrasi di sistem SmartKurikulum MIMHa (MTs Miftahul Huda Bandung). "
                           . "Tugas utama Anda adalah membantu guru merancang perangkat ajar, membedah Capaian Pembelajaran (CP) menjadi Tujuan Pembelajaran (TP) dan Alur Tujuan Pembelajaran (ATP), serta menyusun Modul Ajar berstandar Kurikulum Merdeka. "
                           . "Anda menguasai pendekatan Understanding by Design (UbD), Teaching at the Right Level (TaRL), dan Experiential Learning (EL). "
                           . "Anda sangat mahir dan menguasai SEMUA mata pelajaran umum tingkat MTs (seperti PAI, Matematika, IPA, IPS, Bahasa Indonesia, Bahasa Inggris, Informatika, PKn, dll). "
                           . "Berikan jawaban yang terstruktur, rapi, praktis, dan langsung pada intinya. JANGAN gunakan pengantar bertele-tele.";

        // ==============================================================================
        // 🚀 3. SUSUN DATA UNTUK API (Groq / OpenAI Compatible)
        // ==============================================================================
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

        // ... (di dalam fungsi sendMessage)

        // 4. EKSEKUSI API MENGGUNAKAN cURL (Simpan header ke variabel)
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HEADER, true); // <--- TAMBAHKAN INI untuk menangkap Header
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $responseRaw = curl_exec($ch);
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE); // <--- TAMBAHKAN INI
        $headerStr = substr($responseRaw, 0, $headerSize); // <--- TAMBAHKAN INI
        $body = substr($responseRaw, $headerSize); // <--- TAMBAHKAN INI
        
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        // 5. TANGANI BALASAN
        $responseData = json_decode($body, true);

        // Jika kena limit (HTTP 429 Too Many Requests)
        if ($httpCode == 429) {
            // Mencari info reset dari header "x-ratelimit-reset-requests"
            preg_match('/x-ratelimit-reset-requests:\s*([0-9a-zA-Z]+)/i', $headerStr, $matches);
            $resetWaktu = $matches[1] ?? 'segera';
            
            return $this->response->setJSON([
                'status' => 'error',
                'reply' => "Kuota SiKuMi habis. Sistem akan mereset otomatis dalam <b>{$resetWaktu}</b>. Mohon bersabar ya!"
            ]);
        }

        // Jika berhasil (HTTP Status 200 OK)
        if ($httpCode >= 200 && $httpCode < 300) {
            if (isset($responseData['choices'][0]['message']['content'])) {
                
                $balasanSiKuMi = $responseData['choices'][0]['message']['content'];
                
                // Merapikan format Markdown menjadi HTML
                $balasanSiKuMi = preg_replace('/\*\*(.*?)\*\*/s', '<b>$1</b>', $balasanSiKuMi);
                $balasanSiKuMi = str_replace('```html', '', $balasanSiKuMi);
                $balasanSiKuMi = str_replace('```', '', $balasanSiKuMi);
                
                return $this->response->setJSON([
                    'status' => 'success',
                    'reply' => trim($balasanSiKuMi)
                ]);
            }
        }

        // Tampilkan ERROR JIKA GAGAL
        $errorMessage = $responseData['error']['message'] ?? 'Kesalahan tidak dikenal dari server AI.';
        
        return $this->response->setJSON([
            'status' => 'error',
            'reply' => "Sistem AI Menolak: " . $errorMessage
        ]);
    }

    public function analyzeCp()
    {
        $pesanUser = $this->request->getPost('message');

        if (empty($pesanUser)) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'Data CP tidak boleh kosong.']);
        }

        $db = \Config\Database::connect();
        
        $apiKeySetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_api_key')->get()->getRowArray() : null;
        $apiKey = $apiKeySetting ? trim($apiKeySetting['value']) : '';

        $apiProviderSetting = $db->tableExists('settings') ? $db->table('settings')->where('key', 'ai_provider')->get()->getRowArray() : null;
        $apiUrl = (!empty($apiProviderSetting['value'])) ? trim($apiProviderSetting['value']) : 'https://api.groq.com/openai/v1/chat/completions';

        if (empty($apiKey)) {
            return $this->response->setJSON(['status' => 'error', 'reply' => 'Maaf, kunci akses API AI belum dipasang.']);
        }

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

        $headers = [ 'Authorization: Bearer ' . $apiKey, 'Content-Type: application/json' ];

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

        return $this->response->setJSON(['status' => 'error', 'reply' => "Sistem AI Menolak permintaan."]);
    }
}