<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chat SiKuMi AI - SmartKurikulum MIMHa</title>
    <link rel="stylesheet" href="<?= base_url('assets/css/adminlte.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/css/bootstrap-icons.min.css') ?>">
    <style>
        :root {
            --mimha-primary: #FF9F00; 
            --mimha-accent: #FFC107;  
            --mimha-dark: #212529;    
            --mimha-bg: #F8F9FA;      
        }
        body { background-color: var(--mimha-bg); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; height: 100vh; display: flex; flex-direction: column; }
        
        .chat-container { flex-grow: 1; display: flex; flex-direction: column; overflow: hidden; max-width: 900px; margin: 0 auto; width: 100%; box-shadow: 0 0 20px rgba(0,0,0,0.05); background: #fff; }
        
        /* Area Pesan dengan Background Pola */
        .chat-messages { flex-grow: 1; padding: 20px; overflow-y: auto; background-image: url('data:image/svg+xml,%3Csvg width="20" height="20" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"%3E%3Cg fill="%23f0f0f0" fill-opacity="0.4" fill-rule="evenodd"%3E%3Ccircle cx="3" cy="3" r="3"/%3E%3Ccircle cx="13" cy="13" r="3"/%3E%3C/g%3E%3C/svg%3E'); }
        
        .message-row { display: flex; margin-bottom: 20px; width: 100%; }
        .message-row.user { justify-content: flex-end; }
        .message-row.ai { justify-content: flex-start; }
        
        .avatar { width: 40px; height: 40px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; color: white; flex-shrink: 0; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
        .avatar-ai { background: linear-gradient(135deg, var(--mimha-dark), var(--mimha-primary)); margin-right: 15px; }
        .avatar-user { background-color: #6c757d; margin-left: 15px; }
        
        .bubble { max-width: 75%; padding: 12px 18px; border-radius: 15px; font-size: 14.5px; line-height: 1.5; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .message-row.ai .bubble { background-color: #fff; border: 1px solid #e9ecef; border-top-left-radius: 0; color: #333; }
        .message-row.user .bubble { background-color: var(--mimha-primary); border-top-right-radius: 0; color: #fff; }
        
        /* Area Input Ketikan */
        .chat-input-area { padding: 15px 20px; background: #fff; border-top: 1px solid #dee2e6; display: flex; gap: 10px; align-items: flex-end; }
        .chat-input { flex-grow: 1; border: 1px solid #ced4da; border-radius: 20px; padding: 12px 20px; outline: none; resize: none; overflow-y: hidden; min-height: 48px; max-height: 120px; font-family: inherit; font-size: 14px; transition: border-color 0.2s; }
        .chat-input:focus { border-color: var(--mimha-primary); box-shadow: 0 0 0 3px rgba(255, 159, 0, 0.1); }
        .btn-send { background-color: var(--mimha-primary); color: white; border: none; border-radius: 50%; width: 48px; height: 48px; display: flex; align-items: center; justify-content: center; cursor: pointer; transition: transform 0.2s, background-color 0.2s; flex-shrink: 0; box-shadow: 0 2px 5px rgba(255,159,0,0.3); }
        .btn-send:hover { background-color: #e68f00; transform: scale(1.05); }
        .btn-send:disabled { background-color: #ccc; cursor: not-allowed; transform: none; box-shadow: none; }

        /* Animasi SiKuMi Mengetik */
        .typing-indicator { display: none; padding: 12px 18px; background-color: #fff; border: 1px solid #e9ecef; border-radius: 15px; border-top-left-radius: 0; width: fit-content; }
        .dot { width: 8px; height: 8px; background-color: #adb5bd; border-radius: 50%; display: inline-block; animation: bounce 1.4s infinite ease-in-out both; margin-right: 4px; }
        .dot:nth-child(1) { animation-delay: -0.32s; }
        .dot:nth-child(2) { animation-delay: -0.16s; }
        .dot:nth-child(3) { margin-right: 0; }
        @keyframes bounce { 0%, 80%, 100% { transform: scale(0); } 40% { transform: scale(1); } }
    </style>
</head>
<body>

    <nav class="navbar navbar-expand-lg bg-dark navbar-dark shadow-sm">
        <div class="container-fluid px-4">
            <a class="navbar-brand font-weight-bold" href="#">
                <span class="text-white fw-bold">SiKuMi </span> 
                <span class="fw-bold" style="color: var(--mimha-primary);">AI Assistant</span>
            </a>
            <div class="d-flex gap-2 align-items-center">
                <span class="badge bg-secondary me-2">Akses: <?= esc($displayRole) ?></span>
                <a href="<?= base_url('/') ?>" class="btn btn-sm btn-outline-light rounded-pill px-3">
                    🏠 Dashboard
                </a>
            </div>
        </div>
    </nav>

    <div class="chat-container">
        
        <div class="chat-messages" id="chatBox">
            <div class="message-row ai">
                <div class="avatar avatar-ai"><i class="bi bi-robot"></i></div>
                <div class="bubble">
                    <strong>Halo! 👋</strong><br>
                    Saya <b>SiKuMi</b>, Asisten AI Pintar Anda. Saya dirancang untuk membantu Bapak/Ibu menyusun perangkat ajar, membedah Capaian Pembelajaran (CP), hingga merancang skenario kelas yang interaktif.<br><br>
                    Untuk saat ini saya sedang uji coba sistem obrolan. Coba ketikkan sesuatu di bawah!
                </div>
            </div>
        </div>

        <div class="px-4 pb-2">
            <div class="message-row ai mb-0" id="typingIndicator" style="display: none;">
                <div class="avatar avatar-ai"><i class="bi bi-robot"></i></div>
                <div class="typing-indicator" style="display: block;">
                    <div class="dot"></div><div class="dot"></div><div class="dot"></div>
                </div>
            </div>
        </div>

        <div class="chat-input-area">
            <textarea class="chat-input" id="messageInput" placeholder="Ketik pesan Anda ke SiKuMi di sini... (Shift + Enter untuk baris baru)" rows="1"></textarea>
            <button class="btn-send" id="sendBtn" onclick="sendMessage()">
                <i class="bi bi-send-fill fs-5"></i>
            </button>
        </div>

    </div>

    <script>
        const chatBox = document.getElementById('chatBox');
        const messageInput = document.getElementById('messageInput');
        const sendBtn = document.getElementById('sendBtn');
        const typingIndicator = document.getElementById('typingIndicator');

        // Otomatis meninggikan kotak teks jika ketikan panjang
        messageInput.addEventListener('input', function() {
            this.style.height = '48px';
            this.style.height = (this.scrollHeight) + 'px';
        });

        // Kirim pakai tombol Enter di Keyboard
        messageInput.addEventListener('keypress', function (e) {
            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault();
                sendMessage();
            }
        });

        // Fungsi mencetak balon chat ke layar
        function appendMessage(sender, text) {
            const row = document.createElement('div');
            row.className = `message-row ${sender}`;
            
            let avatarHtml = sender === 'user' 
                ? `<div class="avatar avatar-user"><i class="bi bi-person-fill"></i></div>` 
                : `<div class="avatar avatar-ai"><i class="bi bi-robot"></i></div>`;
            
            let bubbleHtml = `<div class="bubble">${text.replace(/\n/g, '<br>')}</div>`;

            if(sender === 'user') {
                row.innerHTML = bubbleHtml + avatarHtml;
            } else {
                row.innerHTML = avatarHtml + bubbleHtml;
            }

            chatBox.appendChild(row);
            scrollToBottom();
        }

        // Fungsi scroll otomatis ke bawah
        function scrollToBottom() {
            chatBox.scrollTop = chatBox.scrollHeight;
        }

        // Fungsi mengirim pesan ke Controller
        function sendMessage() {
            const message = messageInput.value.trim();
            if (!message) return;

            // 1. Tampilkan pesan user ke layar
            appendMessage('user', message);
            
            // 2. Reset input & matikan tombol (Mencegah spam klik)
            messageInput.value = '';
            messageInput.style.height = '48px';
            sendBtn.disabled = true;
            
            // 3. Tampilkan animasi titik-titik (SiKuMi sedang mengetik)
            typingIndicator.style.display = 'flex';
            scrollToBottom();

            // 4. Siapkan Data
            const formData = new FormData();
            formData.append('message', message);
            
            // 5. Kirim pakai Fetch API (AJAX) ke Route yang sudah dibuat
            const sendUrl = '<?= base_url("sikumi-ai/send") ?>';

            fetch(sendUrl, {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                // Matikan animasi mengetik dan nyalakan tombol kembali
                typingIndicator.style.display = 'none';
                sendBtn.disabled = false;
                
                if(data.status === 'success') {
            appendMessage('ai', data.reply);
        } else {
            // PERBAIKAN: Tampilkan pesan error ASLI dari Google / Server
            appendMessage('ai', '⚠️ **INFO ERROR:** <br>' + (data.reply || data.message || 'Kesalahan sistem tidak diketahui.'));
        }
            })
            .catch(error => {
                typingIndicator.style.display = 'none';
                sendBtn.disabled = false;
                appendMessage('ai', 'Koneksi terputus. Sepertinya saya sedang offline dari server.');
                console.error('Error:', error);
            });
        }
    </script>
</body>
</html>