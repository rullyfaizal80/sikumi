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

        // ==============================================================================
        // 📡 4. EKSEKUSI API MENGGUNAKAN cURL (Dengan URL Dinamis)
        // ==============================================================================
        $ch = curl_init($apiUrl); // URL sekarang ditarik dari variabel dinamis $apiUrl
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers); 
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        
        // Matikan verifikasi SSL sementara jika menggunakan localhost
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); 

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // ==============================================================================
        // 📨 5. TANGANI BALASAN DARI AI
        // ==============================================================================
        if ($curlError) {
            return $this->response->setJSON([
                'status' => 'error',
                'reply' => 'Kesalahan Koneksi Jaringan: ' . $curlError
            ]);
        }

        $responseData = json_decode($response, true);

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
}