<?php
session_start();
session_destroy();

// Kita hapus header("Location: index.php") agar bisa menampilkan animasi dulu.
// Pengalihan ke index.php akan dihandle oleh JavaScript di bawah.
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Logging Out - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        body { 
            font-family: 'Poppins', sans-serif; 
            background-color: #f8fafc;
        }

        /* Animasi Loading Spinner */
        .spinner {
            border: 4px solid rgba(11, 107, 83, 0.1);
            border-left-color: #0b6b53;
            border-radius: 50%;
            width: 44px;
            height: 44px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Animasi Fade In */
        .fade-in {
            animation: fadeIn 0.5s ease-out forwards;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body class="h-screen flex items-center justify-center">

    <!-- Card Container dengan Shadow dan Animasi Hover -->
    <div class="fade-in bg-white p-8 rounded-3xl shadow-2xl border border-gray-100 flex flex-col items-center max-w-sm w-full mx-4 transition-all duration-300 hover:scale-105">
        
        <!-- Icon & Spinner -->
        <div class="w-20 h-20 bg-[#e6f4f1] rounded-full flex items-center justify-center mb-6 shadow-inner">
            <div class="spinner"></div>
        </div>
        
        <!-- Teks -->
        <h2 class="text-2xl font-bold text-gray-900 mb-2 tracking-tight">Berhasil Logout</h2>
        <p class="text-gray-500 text-center text-[14px] leading-relaxed">
            Terima kasih telah menggunakan sistem.<br>Mengalihkan ke halaman login...
        </p>
        
        <!-- Indikator Progress Bar -->
        <div class="w-full bg-gray-100 rounded-full h-1.5 mt-6 overflow-hidden">
            <div class="bg-[#0b6b53] h-1.5 rounded-full animate-[width_1.5s_ease-in-out_forwards]" style="width: 0%;" id="progress-bar"></div>
        </div>

    </div>

    <!-- JavaScript untuk Redirect & Animasi Bar -->
    <script>
        // Animasi Progress Bar
        let progressBar = document.getElementById('progress-bar');
        setTimeout(() => {
            progressBar.style.transition = 'width 1.5s ease-in-out';
            progressBar.style.width = '100%';
        }, 100);

        // Alihkan ke halaman login setelah 1.5 detik
        setTimeout(function() {
            window.location.href = 'index.php';
        }, 1500);
    </script>
</body>
</html>