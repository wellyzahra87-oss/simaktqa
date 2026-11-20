<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Script Google Login -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap');
        body { font-family: 'Poppins', sans-serif; }
        .bg-tqa { background-color: #0b6b53; }
        .text-tqa { color: #0b6b53; }
        
        /* Penambahan Gradasi Gelap di atas gambar agar teks putih terbaca */
        .bg-masjid {
            background-image: linear-gradient(to bottom, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.1) 40%, rgba(0,0,0,0) 100%), url('images/simaktqa.png'); 
            background-size: cover;
            background-position: center bottom;
        }

        .input-field { border: 1.5px solid #d1d5db; transition: all 0.2s; font-size: 14px; }
        .input-field:focus { border-color: #0b6b53; outline: none; box-shadow: 0 0 0 1px #0b6b53; }
    </style>
</head>
<body class="bg-white h-screen flex overflow-hidden">

    <!-- BAGIAN KIRI (GAMBAR MASJID) -->
    <!-- Penambahan class shadow-2xl, transition, dan active:scale -->
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
            <!-- Teks diubah menjadi putih dan diberi drop-shadow -->
            <h1 class="text-3xl font-extrabold text-white leading-none mb-1 drop-shadow-lg">TQA</h1>
            <h2 class="text-xl font-bold text-white tracking-wide mb-2 drop-shadow-lg">Darussalam</h2>
            <p class="text-sm font-semibold text-white drop-shadow-lg">Ilmu &bull; Akhlak &bull; Prestasi</p>
        </div>
    </div>

    <!-- BAGIAN KANAN (FORM LOGIN) -->
    <div class="w-full lg:w-[50%] flex items-center justify-center p-6 lg:px-12 relative">
        <div class="w-full max-w-[380px]">
            
            <h2 class="text-3xl font-extrabold text-tqa mb-2 tracking-tight">Selamat Datang</h2>
            <p class="text-[13px] font-semibold text-tqa mb-6 leading-snug">
                Silahkan Login untuk mengakses<br>
                Sistem Informasi Akademik TQA Darussalam
            </p>

            <!-- Notifikasi Error -->
            <?php if(isset($_GET['error'])): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-3 py-2 rounded-lg mb-4 text-xs font-semibold text-center">
                    Email atau Password salah, atau Akun tidak aktif!
                </div>
            <?php endif; ?>
            <?php if(isset($_GET['reset']) && $_GET['reset'] == 'success'): ?>
                <div class="bg-green-100 border border-green-400 text-green-700 px-3 py-2 rounded-lg mb-4 text-xs font-semibold text-center">
                    Password berhasil diubah! Silakan login kembali.
                </div>
            <?php endif; ?>

            <form action="proses_login.php" method="POST">
                <!-- Email -->
                <div class="mb-4">
                    <label class="block text-[13px] font-bold text-tqa mb-1.5">Email</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        </div>
                        <input type="email" name="email" class="input-field w-full pl-10 pr-3 py-2.5 rounded-lg text-gray-800 placeholder-gray-400" placeholder="Masukkan Email..." required>
                    </div>
                </div>

                <!-- Password -->
                <div class="mb-2">
                    <label class="block text-[13px] font-bold text-tqa mb-1.5">Password</label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"></path></svg>
                        </div>
                        <input type="password" id="passwordInput" name="password" class="input-field w-full pl-10 pr-10 py-2.5 rounded-lg text-gray-800 placeholder-gray-400" placeholder="Masukkan Password..." required>
                        <div class="absolute inset-y-0 right-0 pr-3.5 flex items-center cursor-pointer" onclick="togglePassword()">
                            <svg id="eyeIcon" class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"></path></svg>
                        </div>
                    </div>
                </div>

                <!-- Lupa Password -->
                <div class="flex justify-end mb-5">
                    <a href="lupa_password.php" class="text-[13px] font-bold text-blue-600 hover:underline">Lupa Password?</a>
                </div>

                <button type="submit" class="w-full bg-tqa text-white text-[15px] font-bold py-2.5 rounded-lg hover:opacity-90 transition shadow-md">
                    Login
                </button>

                <div class="flex items-center my-5">
                    <div class="flex-grow border-t border-gray-200"></div>
                    <span class="px-3 text-gray-400 text-xs">atau</span>
                    <div class="flex-grow border-t border-gray-200"></div>
                </div>

                <!-- Tombol Google (Native HTML) -->
                <div id="g_id_onload"
                     data-client_id="MASUKKAN_GOOGLE_CLIENT_ID_ANDA_DISINI.apps.googleusercontent.com"
                     data-context="signin"
                     data-ux_mode="popup"
                     data-login_uri="http://localhost/nama_folder_anda/proses_google_login.php"
                     data-auto_prompt="false">
                </div>
                <div class="g_id_signin w-full"
                     data-type="standard"
                     data-shape="rectangular"
                     data-theme="outline"
                     data-text="signin_with"
                     data-size="large"
                     data-logo_alignment="left">
                </div>

                <p class="text-center mt-6 text-gray-800 font-medium text-[13px]">
                    Belum punya Akun? <a href="register.php" class="text-blue-600 font-bold hover:underline">Daftar sekarang</a>
                </p>

                <div class="flex items-center justify-center mt-8 text-gray-400 text-[12px] font-medium">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    <span>Akun Anda aman bersama Kami</span>
                </div>
            </form>
        </div>
    </div>

    <script>
        function togglePassword() {
            const p = document.getElementById('passwordInput');
            const e = document.getElementById('eyeIcon');
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