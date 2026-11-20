<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap');
        
        body { font-family: 'Poppins', sans-serif; }
        
        .bg-tqa { background-color: #0b6b53; }
        .text-tqa { color: #0b6b53; }
        .border-tqa { border-color: #0b6b53; }

        /* Penambahan Gradasi Gelap di atas gambar agar teks putih terbaca */
        .bg-masjid {
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.1) 40%, rgba(0,0,0,0) 100%), url('images/simaktqa.png');
            background-size: cover;
            background-position: center bottom;
        }

        /* Ukuran disesuaikan untuk layar laptop */
        .text-title { font-size: 36px; line-height: 1.1; letter-spacing: -0.5px; }
        .text-subtitle { font-size: 16px; line-height: 1.4; font-weight: 600; }
        .text-label { font-size: 15px; font-weight: 500; }
        .input-field { font-size: 14px; padding-top: 10px; padding-bottom: 10px; border: 1.5px solid #9ca3af; transition: all 0.2s; }
        .input-field:focus { border-color: #0b6b53; outline: none; box-shadow: 0 0 0 1px #0b6b53; }
        .btn-text { font-size: 16px; font-weight: 500; }
    </style>
</head>
<body class="bg-white h-screen flex overflow-hidden">

    <!-- ================= BAGIAN KIRI (GAMBAR) ================= -->
    <!-- Disamakan persis dengan ukuran di login.php -->
    <div class="hidden lg:flex w-[50%] relative bg-masjid h-full flex-col items-center justify-start pt-16 shadow-2xl transition-all duration-300 active:scale-[0.98] cursor-pointer">
        <div class="z-10 flex flex-col items-center text-center">
            <div class="mb-2">
                <svg width="70" height="70" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <!-- Warna SVG diubah menjadi putih -->
                    <path d="M50 15 C 50 15, 32 28, 32 48 L 68 48 C 68 28, 50 15, 50 15 Z" fill="#ffffff"/>
                    <path d="M50 8 L 50 15 M 46 13 L 54 13" stroke="#ffffff" stroke-width="2.5"/>
                    <circle cx="50" cy="10" r="2.5" fill="#ffffff"/>
                    <rect x="28" y="48" width="44" height="28" fill="#ffffff"/>
                    <path d="M45 76 L 45 62 Q 50 57 55 62 L 55 76 Z" fill="#0b6b53"/>
                    <path d="M 15 76 Q 30 71 50 86 Q 70 71 85 76 L 80 96 Q 65 91 50 101 Q 35 91 20 96 Z" fill="#ffffff"/>
                </svg>
            </div>
            <!-- Teks disamakan persis dengan login.php -->
            <h1 class="text-3xl font-extrabold text-white leading-none mb-1 drop-shadow-lg">TQA</h1>
            <h2 class="text-xl font-bold text-white tracking-wide mb-2 drop-shadow-lg">Darussalam</h2>
            <p class="text-sm font-semibold text-white drop-shadow-lg">Ilmu &bull; Akhlak &bull; Prestasi</p>
        </div>
    </div>

    <!-- ================= BAGIAN KANAN (FORM) ================= -->
    <!-- Dikembalikan ke struktur semula (tanpa kotak putih tambahan) -->
    <div class="w-full lg:w-[50%] flex items-center justify-center p-6 lg:px-[8%]">
        <div class="w-full max-w-[400px]">
            
            <h2 class="text-title font-bold text-tqa mb-2 tracking-tight">Selamat Datang</h2>
            <p class="text-subtitle text-tqa mb-5 leading-snug">
                Silahkan Daftar untuk mengakses<br>
                Sistem Informasi Akademik TQA Darussalam
            </p>

            <!-- PESAN ERROR / SUKSES -->
            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 text-red-700 p-2 rounded-lg mb-3 text-sm font-semibold text-center">
                    <?= htmlspecialchars($_GET['error']) ?>
                </div>
            <?php endif; ?>

            <form action="proses_register.php" method="POST">
                <!-- Nama Lengkap -->
                <div class="mb-3">
                    <label class="block text-label text-tqa mb-1">Nama Lengkap</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" /></svg>
                        </div>
                        <input type="text" name="name" class="input-field w-full pl-11 pr-4 rounded-lg text-gray-800 placeholder-gray-500" placeholder="Masukkan Nama Lengkap..." required>
                    </div>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="block text-label text-tqa mb-1">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-700" fill="currentColor" viewBox="0 0 20 20"><path d="M2.003 5.884L10 9.882l7.997-3.998A2 2 0 0016 4H4a2 2 0 00-1.997 1.884z" /><path d="M18 8.118l-8 4-8-4V14a2 2 0 002 2h12a2 2 0 002-2V8.118z" /></svg>
                        </div>
                        <input type="email" name="email" class="input-field w-full pl-11 pr-4 rounded-lg text-gray-800 placeholder-gray-500" placeholder="Masukkan Email..." required>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label class="block text-label text-tqa mb-1">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" /></svg>
                        </div>
                        <input type="password" id="passwordInput" name="password" class="input-field w-full pl-11 pr-11 rounded-lg text-gray-800 placeholder-gray-500" placeholder="Masukkan Password..." required>
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center cursor-pointer" onclick="togglePassword('passwordInput', 'eyeIcon1')">
                            <svg id="eyeIcon1" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Konfirmasi Password -->
                <div class="mb-3">
                    <label class="block text-label text-tqa mb-1">Konfirmasi Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-5 h-5 text-gray-700" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 8a6 6 0 01-7.743 5.743L10 14l-1 1-1 1H6v2H2v-4l4.257-4.257A6 6 0 1118 8zm-6-4a1 1 0 100 2 2 2 0 012 2 1 1 0 102 0 4 4 0 00-4-4z" clip-rule="evenodd" /></svg>
                        </div>
                        <input type="password" id="passwordConfirmInput" name="password_confirmation" class="input-field w-full pl-11 pr-11 rounded-lg text-gray-800 placeholder-gray-500" placeholder="Masukkan Password..." required>
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center cursor-pointer" onclick="togglePassword('passwordConfirmInput', 'eyeIcon2')">
                            <svg id="eyeIcon2" class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Checkbox -->
                <div class="flex items-center mb-4">
                    <input type="checkbox" name="setuju" id="terms" class="w-[16px] h-[16px] border-gray-400 rounded text-tqa focus:ring-tqa mr-2 cursor-pointer" required>
                    <label for="terms" class="text-[12px] text-gray-700 cursor-pointer">
                        Saya setuju dengan <a href="#" class="text-blue-600 font-semibold hover:underline">Syarat & Ketentuan</a> dan <a href="#" class="text-blue-600 font-semibold hover:underline">Kebijakan Privasi</a>
                    </label>
                </div>

                <!-- Tombol Daftar -->
                <button type="submit" class="w-full bg-tqa text-white btn-text py-3 rounded-lg hover:opacity-90 transition duration-300">
                    Daftar
                </button>

                <!-- Link Login -->
                <p class="text-center mt-4 text-gray-800 font-medium text-[14px]">
                    Sudah punya Akun? <a href="login.php" class="text-blue-600 font-semibold hover:underline">Login disini</a>
                </p>
            </form>
        </div>
    </div>

    <!-- SCRIPT UNTUK TOGGLE PASSWORD -->
    <script>
        function togglePassword(inputId, iconId) {
            const p = document.getElementById(inputId);
            const e = document.getElementById(iconId);
            if (p.type === 'password') {
                p.type = 'text';
                e.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>';
            } else {
                p.type = 'password';
                e.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path>';
            }
        }
    </script>
</body>
</html>