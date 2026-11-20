<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$current_page = basename($_SERVER['PHP_SELF'], ".php");
$role         = $_SESSION['role']  ?? 'Admin';
$nama_user    = $_SESSION['nama']  ?? 'User';
$email_user   = $_SESSION['email'] ?? '-';

$menu_admin = [
    ['file' => 'dashboard_admin.php', 'page' => 'dashboard_admin', 'label' => 'Dashboard',      'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    ['file' => 'kelola_user.php',     'page' => 'kelola_user',     'label' => 'Kelola User',    'icon' => 'M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z'],
    ['file' => 'mata_pelajaran.php',  'page' => 'mata_pelajaran',  'label' => 'Mata Pelajaran', 'icon' => 'M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z'],
    ['file' => 'kelola_kelas.php',    'page' => 'kelola_kelas',    'label' => 'Kelola Kelas',   'icon' => 'M12 3L1 9l4 2.18v6L12 21l7-3.82v-6l2-1.09V17h2V9L12 3zm6.82 6L12 12.72 5.18 9 12 5.28 18.82 9zM17 15.99l-5 2.73-5-2.73v-3.72l5 2.73 5-2.73v3.72z'],
    ['file' => 'kelola_jadwal.php',   'page' => 'kelola_jadwal',   'label' => 'Kelola Jadwal',  'icon' => 'M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z'],
    ['file' => 'laporan.php',         'page' => 'laporan',         'label' => 'Laporan',        'icon' => 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z'],
];

$menu_guru = [
    ['file' => 'dashboard_guru.php',  'page' => 'dashboard_guru',  'label' => 'Dashboard',        'icon' => 'M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z'],
    ['file' => 'absensi_guru.php',    'page' => 'absensi_guru',    'label' => 'Absensi Guru',     'icon' => 'M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z'],
    ['file' => 'jadwal_mengajar.php', 'page' => 'jadwal_mengajar', 'label' => 'Jadwal Mengajar',  'icon' => 'M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z'],
    ['file' => 'kelola_nilai.php',    'page' => 'kelola_nilai',    'label' => 'Kelola Nilai',     'icon' => 'M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z'],
];

$menus = ($role === 'Admin') ? $menu_admin : $menu_guru;
?>

<!-- ===== CSS TAMBAHAN UNTUK RESPONSIVE MOBILE ===== -->
<style>
    .sidebar-closed {
        transform: translateX(-100%) !important;
    }

    @media (max-width: 767px) {
        /* Paksa konten utama full width di HP */
        body > div.flex-1 {
            margin-left: 0 !important;
        }
        /* Beri ruang untuk tombol hamburger di header */
        header {
            padding-left: 4rem !important;
        }
        /* Beri jarak atas konten utama agar tidak tertutup tombol */
        main {
            padding-top: 1rem !important;
        }
    }
</style>

<!-- ===== SIDEBAR ===== -->
<aside id="sidebar" class="w-64 flex flex-col h-screen fixed left-0 top-0 overflow-hidden z-50 shadow-xl transition-transform duration-300 -translate-x-full md:translate-x-0" style="background-color: #0b6b53; color: white;">
    <div class="px-5 pt-5 pb-0 flex items-center">
        <button onclick="toggleSidebar()" class="text-white hover:text-gray-300 transition" aria-label="Toggle Sidebar">
            <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
        </button>
    </div>

    <div class="p-5 pt-3 pb-4 flex flex-col items-center text-center border-b border-white border-opacity-20 shrink-0">
        <!-- Logo Masjid (Disamakan dengan Login) -->
        <div class="w-14 h-14 bg-white rounded-full flex items-center justify-center mb-2 shadow-md">
            <svg class="w-8 h-8" viewBox="0 0 100 100" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M50 15 C 50 15, 32 28, 32 48 L 68 48 C 68 28, 50 15, 50 15 Z" fill="#0b6b53"/>
                <path d="M50 8 L 50 15 M 46 13 L 54 13" stroke="#0b6b53" stroke-width="2.5"/>
                <circle cx="50" cy="10" r="2.5" fill="#0b6b53"/>
                <rect x="28" y="48" width="44" height="28" fill="#0b6b53"/>
                <path d="M45 76 L 45 62 Q 50 57 55 62 L 55 76 Z" fill="#ffffff"/>
                <path d="M 15 76 Q 30 71 50 86 Q 70 71 85 76 L 80 96 Q 65 91 50 101 Q 35 91 20 96 Z" fill="#0b6b53"/>
            </svg>
        </div>
        <h1 class="text-lg font-bold text-white mb-1">TQA Darussalam</h1>
        
        <!-- Ikon Profil User Disamakan dengan Gambar -->
        <div class="mt-2 flex items-center gap-3 w-full text-left">
            <div class="w-10 h-10 bg-white rounded-full flex items-center justify-center shrink-0 shadow-sm border border-gray-300">
                <svg class="w-6 h-6 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                </svg>
            </div>
            <div class="overflow-hidden">
                <p class="font-bold text-[14px] text-white truncate leading-tight"><?= htmlspecialchars($nama_user) ?></p>
                <p class="text-[11px] text-gray-200 truncate"><?= htmlspecialchars($email_user) ?></p>
            </div>
        </div>
    </div>

    <nav class="flex-1 px-3 py-3 space-y-1 overflow-y-auto">
        <?php foreach ($menus as $m): ?>
            <?php $aktif = ($current_page === $m['page']) ? 'bg-[#2dd4bf] text-white shadow-sm' : 'text-white hover:bg-white hover:bg-opacity-10'; ?>
            <a href="<?= $m['file'] ?>" class="menu-link flex items-center gap-3 px-3 py-2.5 rounded-lg transition font-semibold text-[14px] <?= $aktif ?>">
                <svg class="w-5 h-5 shrink-0" fill="currentColor" viewBox="0 0 24 24"><path d="<?= $m['icon'] ?>"/></svg>
                <span><?= $m['label'] ?></span>
            </a>
        <?php endforeach; ?>

        <div class="pt-3 mt-3 border-t border-white border-opacity-20 shrink-0">
            <button onclick="confirmLogout()" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-lg transition font-semibold text-white hover:bg-white hover:bg-opacity-10 active:bg-[#2dd4bf] text-[14px]">
                <!-- Ikon Logout Disamakan dengan Gambar -->
                <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span>Logout</span>
            </button>
        </div>
    </nav>
</aside>

<!-- ===== OVERLAY GELAP ===== -->
<div id="sidebarOverlay" onclick="toggleSidebar()" class="hidden fixed inset-0 bg-black bg-opacity-50 z-40 md:hidden"></div>

<!-- ===== TOMBOL HAMBURGER ===== -->
<button id="openSidebarBtn" onclick="toggleSidebar()" class="hidden fixed top-4 left-4 z-50 bg-[#0b6b53] text-white p-2.5 rounded-lg shadow-lg hover:bg-[#085341] transition" aria-label="Buka Menu">
    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 6h16M4 12h16M4 18h16"></path></svg>
</button>

<!-- ===== MODAL LOGOUT ===== -->
<div id="logoutModal" class="fixed inset-0 z-[60] flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-40 backdrop-blur-sm"></div>
    <div class="relative bg-white p-6 md:p-8 rounded-2xl w-[90%] max-w-[420px] text-center border-2 border-[#0b6b53] shadow-2xl">
        <h3 class="text-lg md:text-xl font-bold text-gray-800 mb-6 md:mb-8">Apakah Anda Yakin ingin Keluar ?</h3>
        <div class="flex justify-center gap-3 md:gap-6">
            <button onclick="closeLogout()" class="px-6 md:px-10 py-2.5 bg-[#facc15] text-gray-900 font-bold rounded-lg hover:bg-yellow-500 transition shadow-sm text-[15px] md:text-[17px]">Tidak</button>
            <a href="logout.php" class="px-6 md:px-10 py-2.5 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition shadow-sm text-[15px] md:text-[17px]">Ya</a>
        </div>
    </div>
</div>

<script>
    function isMobile() {
        return window.innerWidth < 768;
    }

    function toggleSidebar() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const openBtn = document.getElementById('openSidebarBtn');
        // Ambil konten utama secara langsung
        const mainContent = document.querySelector('body > div.flex-1');

        if (isMobile()) {
            const isHidden = sidebar.classList.contains('-translate-x-full');
            if (isHidden) {
                // BUKA
                sidebar.classList.remove('-translate-x-full');
                overlay.classList.remove('hidden');
                openBtn.classList.add('hidden');
            } else {
                // TUTUP
                sidebar.classList.add('-translate-x-full');
                overlay.classList.add('hidden');
                openBtn.classList.remove('hidden');
            }
            // 🔥 PAKSA margin 0 di mobile agar konten tidak terdorong
            if (mainContent) mainContent.style.marginLeft = '0';
        } else {
            // MODE DESKTOP
            const isCollapsed = sidebar.classList.contains('sidebar-closed');
            if (isCollapsed) {
                sidebar.classList.remove('sidebar-closed');
                openBtn.classList.add('hidden');
                if (mainContent) mainContent.style.marginLeft = '';
            } else {
                sidebar.classList.add('sidebar-closed');
                openBtn.classList.remove('hidden');
                if (mainContent) mainContent.style.marginLeft = '0';
            }
        }
    }

    // Auto-close sidebar saat menu diklik (Mobile)
    document.querySelectorAll('.menu-link').forEach(function(link) {
        link.addEventListener('click', function() {
            if (isMobile()) {
                document.getElementById('sidebar').classList.add('-translate-x-full');
                document.getElementById('sidebarOverlay').classList.add('hidden');
                document.getElementById('openSidebarBtn').classList.remove('hidden');
            }
        });
    });

    // Handle Resize (Reset state saat layar diputar / diubah ukurannya)
    let lastIsMobile = window.innerWidth < 768;
    window.addEventListener('resize', function() {
        const nowIsMobile = window.innerWidth < 768;
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        const openBtn = document.getElementById('openSidebarBtn');
        const mainContent = document.querySelector('body > div.flex-1');

        sidebar.classList.remove('-translate-x-full', 'sidebar-closed');
        overlay.classList.add('hidden');

        if (nowIsMobile) {
            sidebar.classList.add('-translate-x-full');
            openBtn.classList.remove('hidden');
            if (mainContent) mainContent.style.marginLeft = '0';
        } else {
            openBtn.classList.add('hidden');
            if (mainContent) mainContent.style.marginLeft = '';
        }
        lastIsMobile = nowIsMobile;
    });

    // Pastikan saat halaman dimuat pertama kali di HP, margin langsung 0
    document.addEventListener('DOMContentLoaded', function() {
        const openBtn = document.getElementById('openSidebarBtn');
        const mainContent = document.querySelector('body > div.flex-1');
        
        if (isMobile()) {
            openBtn.classList.remove('hidden');
            if (mainContent) mainContent.style.marginLeft = '0';
        } else {
            if (mainContent) mainContent.style.marginLeft = '';
        }
    });

    function confirmLogout() { document.getElementById('logoutModal').classList.remove('hidden'); }
    function closeLogout()   { document.getElementById('logoutModal').classList.add('hidden'); }
</script>