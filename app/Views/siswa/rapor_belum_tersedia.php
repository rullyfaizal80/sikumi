<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Belum Tersedia</title>
    <!-- Menggunakan font eksternal yang sama dengan rapor -->
    <link href="https://fonts.googleapis.com/css2?family=Merriweather:wght@400;700&family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f4f7f6;
            margin: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh; /* Membuat konten selalu di tengah layar vertikal */
        }
        .empty-state-card {
            background: #fff;
            padding: 40px 30px;
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            text-align: center;
            max-width: 450px;
            width: 90%;
            border-top: 6px solid #2c3e50;
            animation: fadeIn 0.5s ease-in-out;
        }
        .icon-wrapper {
            font-size: 60px;
            margin-bottom: 20px;
            line-height: 1;
        }
        h2 {
            font-family: 'Merriweather', serif;
            color: #2c3e50;
            font-size: 22px;
            margin: 0 0 15px 0;
            line-height: 1.4;
        }
        p {
            color: #6c757d;
            font-size: 15px;
            line-height: 1.6;
            margin: 0 0 30px 0;
        }
        .highlight {
            font-weight: 600;
            color: #d35400; /* Warna oranye gelap untuk penekanan tanggal */
        }
        .btn-back {
            display: inline-block;
            background-color: #2c3e50;
            color: #ffffff;
            text-decoration: none;
            padding: 12px 24px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-back:hover {
            background-color: #1a252f;
            box-shadow: 0 4px 10px rgba(44, 62, 80, 0.2);
            transform: translateY(-2px);
        }
        
        /* Animasi muncul yang halus */
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>

    <div class="empty-state-card">
        <div class="icon-wrapper">
            🗓️ <!-- Anda bisa menggantinya dengan SVG/Gambar ilustrasi jika punya -->
        </div>
        <h2>Laporan Belum Terbit</h2>
        <p>
            Saat ini belum ada data laporan bulanan yang tersedia. Laporan pertama untuk semester ini baru dapat diakses setelah <span class="highlight">tanggal 6 pada bulan berikutnya</span>.
        </p>
        
        <a href="<?= base_url('/') ?>" class="btn-back">
            Kembali ke Beranda
        </a>
    </div>

</body>
</html>