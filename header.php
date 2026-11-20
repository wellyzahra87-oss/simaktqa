<?php
// Pastikan session aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Inisialisasi array notifikasi jika belum ada
if (!isset($_SESSION['notifikasi'])) {
    $_SESSION['notifikasi'] = [];
}

// Cek jika ada parameter 'success' atau 'msg' di URL
if (isset($_GET['success']) || isset($_GET['msg'])) {
    $pesan_notif = '';
    $status_type = 'success'; // 'success' atau 'error'
    
    // Logika Pesan Spesifik
    $success_param = $_GET['success'] ?? '';
    $msg_param = $_GET['msg'] ?? '';

    // 1. Menangkap pesan custom (jika ada)
    if (!empty($msg_param)) {
        $pesan_notif = htmlspecialchars($msg_param);
        $status_type = isset($_GET['type']) && $_GET['type'] == 'error' ? 'error' : 'success';
    } 
    // 2. Menangkap kode success standar
    else {
        switch ($success_param) {
            case 'absen':         $pesan_notif = 'Absensi santri berhasil disimpan ke database!'; break;
            case 'materi':        $pesan_notif = 'Sub Materi pembelajaran berhasil diperbarui!'; break;
            case 'generated':     $pesan_notif = 'Jadwal baru berhasil di-generate secara otomatis!'; break;
            case 'tambah_user':   $pesan_notif = 'Data berhasil ditambahkan!'; break;
            case 'edit_user':     $pesan_notif = 'Data berhasil diubah!'; break;
            case 'hapus_user':    $pesan_notif = 'Data berhasil dihapus!'; break;
            case 'tambah_kelas':  $pesan_notif = 'Kelas baru berhasil ditambahkan!'; break;
            case 'edit_kelas':    $pesan_notif = 'Data kelas berhasil diperbarui!'; break;
            case 'tambah_mapel':  $pesan_notif = 'Mata pelajaran baru berhasil ditambahkan!'; break;
            case 'simpan_nilai':  $pesan_notif = 'Nilai santri berhasil disimpan!'; break;
            case '1':             $pesan_notif = 'Aksi berhasil dilakukan!'; break;
            default:              $pesan_notif = 'Aktivitas berhasil disimpan!';
        }
    }

    // Simpan ke dalam session (maksimal 5 notifikasi terakhir)
    array_unshift($_SESSION['notifikasi'], [
        'pesan'   => $pesan_notif,
        'tipe'    => $status_type, 
        'waktu'   => date('H:i'),
        'tanggal' => date('d M Y')
    ]);
    
    if (count($_SESSION['notifikasi']) > 5) {
        array_pop($_SESSION['notifikasi']);
    }
}
?>

<!-- ============================================================
     RESPONSIVE OVERRIDE GLOBAL — Aktif di semua file yang include header.php
     Hanya berlaku di Mobile (< 768px). Tidak mengubah tampilan desktop.
     ============================================================ -->
<style>
@media (max-width: 767px) {
    /* 1. Viewport height akurat di browser mobile (address bar) */
    body > div.flex-1 { height: 100dvh !important; }

    /* 2. Padding main dikecilkan */
    main.px-6 { padding-left: 0.75rem !important; padding-right: 0.75rem !important; }

    /* 3. Grid form 2-kolom → 1 kolom */
    .grid.grid-cols-2 { grid-template-columns: 1fr !important; }
    .grid.grid-cols-2 > [class*="col-span-2"] { grid-column: span 1 !important; }

    /* 4. Grid stat card 3-4 kolom → 2 kolom */
    .grid.md\:grid-cols-4,
    .grid.md\:grid-cols-3 { grid-template-columns: repeat(2, 1fr) !important; }
    .grid.md\:grid-cols-4 > div,
    .grid.md\:grid-cols-3 > div { padding: 0.75rem !important; }

    /* 5. Tabel: font & padding lebih kecil */
    table { font-size: 12px !important; }
    table th,
    table td { padding: 6px 8px !important; }

    /* 6. Judul halaman dikecilkan */
    h1.text-\[22px\],
    h1.text-\[24px\],
    h1.text-\[28px\] { font-size: 18px !important; }
    h2.text-\[20px\],
    h2.text-\[22px\] { font-size: 16px !important; }

    /* 7. Info card flex-row → wrap (jadwal_mengajar & absensi_guru) */
    .flex.items-center.gap-6 { flex-wrap: wrap !important; gap: 0.5rem !important; }
    .flex.items-start.gap-4  { flex-wrap: wrap !important; gap: 0.5rem !important; }

    /* 8. Semua modal fixed-width → responsive */
    .fixed.inset-0 > div.relative {
        width: 92% !important;
        max-width: 420px !important;
    }

    /* 9. Notif dropdown: full-width di bawah header (mencegah overflow) */
    #notifDropdown {
        position: fixed !important;
        top: 68px !important;
        left: 0.5rem !important;
        right: 0.5rem !important;
        width: auto !important;
        margin-top: 0 !important;
    }

    /* 10. Toast: full-width di HP */
    #toast-container { right: 0.5rem !important; left: 0.5rem !important; }
    #toast-container > div { width: 100% !important; }

    /* 11. Form search + filter bisa wrap */
    form.flex { flex-wrap: wrap; }
    form.flex > select,
    form.flex > button { flex: 1 1 auto; min-width: 0; }
}

/* iPad portrait: 4 card → 2 kolom (karena layar belum lebar) */
@media (min-width: 768px) and (max-width: 1023px) {
    .grid.md\:grid-cols-4 { grid-template-columns: repeat(2, 1fr) !important; }
}

/* iOS Safari: cegah auto-zoom saat tap input */
@supports (-webkit-touch-callout: none) {
    @media (max-width: 767px) {
        input, select, textarea { font-size: 16px !important; }
    }
}
</style>

<!-- Header dengan Shadow halus saat scroll -->
<header class="bg-[#f8fafc] h-[64px] shrink-0 flex items-center justify-between pl-16 md:pl-6 pr-3 md:pr-6 border-b border-gray-300 sticky top-0 z-40 shadow-sm transition-shadow duration-300">
    <!-- Bagian Kiri: Tanggal (beri ruang untuk tombol hamburger di mobile) -->
    <div class="flex items-center min-w-0">
        <h2 class="text-[11px] sm:text-[13px] md:text-[16px] font-bold text-black tracking-tight truncate max-w-[140px] sm:max-w-[220px] md:max-w-none">
            <?= $tgl_sekarang ?>
        </h2>
    </div>
    
    <!-- Bagian Kanan: Notifikasi & Profil -->
    <div class="flex items-center gap-2 md:gap-4">
        
        <!-- 1. NOTIFIKASI LONCENG -->
        <div class="relative">
            <button onclick="toggleNotif()" class="relative text-gray-700 hover:text-[#0b6b53] transition-all duration-200 p-2 rounded-full hover:bg-gray-200 active:scale-90">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                <?php if (count($_SESSION['notifikasi']) > 0): ?>
                    <span class="absolute top-1.5 right-1.5 w-[9px] h-[9px] bg-red-500 rounded-full border-2 border-[#f8fafc] animate-pulse"></span>
                <?php endif; ?>
            </button>
            
            <!-- Dropdown Notifikasi -->
            <div id="notifDropdown" class="hidden absolute right-0 mt-3 w-80 bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-gray-100 py-2 z-50 transform scale-95 opacity-0 transition-all duration-300 origin-top-right">
                <div class="px-4 py-3 border-b border-gray-100 flex justify-between items-center bg-gray-50 rounded-t-2xl">
                    <p class="font-bold text-[13px] text-gray-800">Notifikasi Aktivitas</p>
                    <span class="text-[11px] font-bold bg-[#e6f4f1] text-[#0b6b53] px-2.5 py-1 rounded-full shadow-sm"><?= count($_SESSION['notifikasi']) ?> Terbaru</span>
                </div>
                <div class="max-h-64 overflow-y-auto custom-scrollbar">
                    <?php if (count($_SESSION['notifikasi']) > 0): ?>
                        <?php foreach ($_SESSION['notifikasi'] as $notif): 
                            $tipe = $notif['tipe'] ?? 'success';
                            $icon_color = $tipe == 'success' ? 'text-green-600 bg-green-100' : 'text-red-600 bg-red-100';
                        ?>
                            <div class="px-4 py-3.5 hover:bg-gray-100 transition-all duration-200 active:bg-gray-200 border-b border-gray-50 last:border-0 flex gap-3 cursor-pointer">
                                <div class="shrink-0 w-8 h-8 rounded-full flex items-center justify-center <?= $icon_color ?> mt-0.5 shadow-sm">
                                    <?php if($tipe == 'success'): ?>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    <?php else: ?>
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="text-[12.5px] font-semibold text-gray-800 leading-snug"><?= htmlspecialchars($notif['pesan']) ?></p>
                                    <p class="text-[10px] font-medium text-gray-500 mt-1.5 flex items-center gap-1">
                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <?= $notif['tanggal'] ?> - <?= $notif['waktu'] ?>
                                    </p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="px-4 py-8 text-center text-gray-500 text-[12px] flex flex-col items-center">
                            <svg class="w-10 h-10 text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                            Belum ada aktivitas terbaru.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Garis Pemisah Vertikal (sembunyi di HP) -->
        <div class="h-8 w-px bg-gray-300 hidden sm:block"></div>

        <!-- 2. PROFIL ADMIN/GURU -->
        <div class="relative flex items-center gap-2 md:gap-2.5 cursor-pointer group hover:bg-gray-200 p-1.5 rounded-xl transition-all duration-200 active:scale-95" onclick="toggleProfile()">
            
            <div class="w-[32px] h-[32px] md:w-[36px] md:h-[36px] bg-[#0b6b53] rounded-full flex items-center justify-center text-white shrink-0 shadow-md group-hover:shadow-lg group-hover:scale-105 transition-all duration-300">
                <svg class="w-4 h-4 md:w-5 md:h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
            </div>
            
            <div class="hidden md:block text-left pr-1">
                <p class="font-bold text-[13px] text-black leading-tight group-hover:text-[#0b6b53] transition-colors"><?= htmlspecialchars($_SESSION['role'] ?? 'Admin') ?></p>
                <p class="text-[11px] text-gray-800 font-medium leading-tight mt-0.5"><?= htmlspecialchars($_SESSION['email'] ?? 'wellyzahra@gmail.com') ?></p>
            </div>
            
            <svg class="w-3.5 h-3.5 md:w-4 md:h-4 text-black ml-0.5 md:ml-1 shrink-0 group-hover:translate-y-0.5 group-hover:text-[#0b6b53] transition-all duration-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
            
            <!-- Dropdown Profil -->
            <div id="profileDropdown" class="hidden absolute top-14 right-0 w-44 bg-white rounded-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.15)] border border-gray-100 py-2 z-50 transform scale-95 opacity-0 transition-all duration-300 origin-top-right">
                <button onclick="openLogoutModal()" class="w-full flex items-center gap-2.5 px-3 py-2.5 text-[13px] font-bold text-red-600 hover:bg-red-50 transition-all duration-200 active:scale-95 active:bg-red-100 rounded-lg mx-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                    Logout
                </button>
            </div>
        </div>
    </div>
</header>

<!-- Container untuk Toast Notification -->
<div id="toast-container" class="fixed top-24 right-6 z-[100] flex flex-col gap-3 pointer-events-none"></div>

<!-- MODAL KONFIRMASI LOGOUT -->
<div id="logoutModal" class="fixed inset-0 z-[110] flex items-center justify-center hidden">
    <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeLogoutModal()"></div>
    <div id="logoutModalContent" class="relative bg-white rounded-2xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.3)] p-6 w-[90%] max-w-[400px] text-center transform scale-95 opacity-0 transition-all duration-300">
        <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
            <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
        </div>
        <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Logout</h3>
        <p class="text-gray-500 font-medium mb-6 text-[14px]">Apakah Anda yakin ingin keluar dari sistem?</p>
        <div class="flex gap-3">
            <button onclick="closeLogoutModal()" class="flex-1 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-all active:scale-95 shadow-sm">Batal</button>
            <a href="logout.php" class="flex-1 bg-red-500 text-white font-bold py-2.5 rounded-lg hover:bg-red-600 transition-all active:scale-95 shadow-md">Ya, Logout</a>
        </div>
    </div>
</div>

<script>
    // ============================================================
    // FUNGSI DROPDOWN NOTIFIKASI & PROFIL (TIDAK DIUBAH)
    // ============================================================
    function toggleNotif() {
        const notif = document.getElementById('notifDropdown');
        const profile = document.getElementById('profileDropdown');
        
        profile.classList.add('hidden', 'scale-95', 'opacity-0');
        
        if (notif.classList.contains('hidden')) {
            notif.classList.remove('hidden');
            setTimeout(() => {
                notif.classList.remove('scale-95', 'opacity-0');
                notif.classList.add('scale-100', 'opacity-100');
            }, 10);
        } else {
            notif.classList.remove('scale-100', 'opacity-100');
            notif.classList.add('scale-95', 'opacity-0');
            setTimeout(() => notif.classList.add('hidden'), 300);
        }
    }

    function toggleProfile() {
        const notif = document.getElementById('notifDropdown');
        const profile = document.getElementById('profileDropdown');
        
        notif.classList.add('hidden', 'scale-95', 'opacity-0');
        
        if (profile.classList.contains('hidden')) {
            profile.classList.remove('hidden');
            setTimeout(() => {
                profile.classList.remove('scale-95', 'opacity-0');
                profile.classList.add('scale-100', 'opacity-100');
            }, 10);
        } else {
            profile.classList.remove('scale-100', 'opacity-100');
            profile.classList.add('scale-95', 'opacity-0');
            setTimeout(() => profile.classList.add('hidden'), 300);
        }
    }

    // Menutup dropdown jika user klik di luar area dropdown
    window.addEventListener('click', function(e) {
        if (!e.target.closest('.relative')) {
            const notif = document.getElementById('notifDropdown');
            const profile = document.getElementById('profileDropdown');
            
            if (notif && !notif.classList.contains('hidden')) {
                notif.classList.remove('scale-100', 'opacity-100');
                notif.classList.add('scale-95', 'opacity-0');
                setTimeout(() => notif.classList.add('hidden'), 300);
            }
            if (profile && !profile.classList.contains('hidden')) {
                profile.classList.remove('scale-100', 'opacity-100');
                profile.classList.add('scale-95', 'opacity-0');
                setTimeout(() => profile.classList.add('hidden'), 300);
            }
        }
    });

    // =======================================================
    // MODAL LOGOUT KUSTOM
    // =======================================================
    function openLogoutModal() {
        const modal = document.getElementById('logoutModal');
        const content = document.getElementById('logoutModalContent');
        
        document.getElementById('profileDropdown').classList.add('hidden', 'scale-95', 'opacity-0');
        
        modal.classList.remove('hidden');
        setTimeout(() => {
            content.classList.remove('scale-95', 'opacity-0');
            content.classList.add('scale-100', 'opacity-100');
        }, 10);
    }

    function closeLogoutModal() {
        const modal = document.getElementById('logoutModal');
        const content = document.getElementById('logoutModalContent');
        
        content.classList.remove('scale-100', 'opacity-100');
        content.classList.add('scale-95', 'opacity-0');
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 300);
    }

    // =======================================================
    // TOAST NOTIFIKASI MENGAMBANG
    // =======================================================
    function showToast(message, type = 'success') {
        const container = document.getElementById('toast-container');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `flex items-center w-full sm:w-80 p-4 mb-2 text-gray-500 bg-white rounded-xl shadow-[0_10px_40px_-10px_rgba(0,0,0,0.2)] transform translate-x-full transition-all duration-500 ease-in-out pointer-events-auto border-l-4 ${
            type === 'success' ? 'border-green-500' : 'border-red-500'
        }`;
        
        toast.innerHTML = `
            <div class="inline-flex items-center justify-center flex-shrink-0 w-8 h-8 rounded-lg ${
                type === 'success' ? 'bg-green-100 text-green-600' : 'bg-red-100 text-red-600'
            } shadow-sm">
                ${type === 'success' 
                    ? `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>` 
                    : `<svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/></svg>`
                }
            </div>
            <div class="ml-3 text-[13px] font-semibold text-gray-800">${message}</div>
            <button onclick="this.parentElement.remove()" class="ml-auto -mx-1.5 -my-1.5 bg-white text-gray-400 hover:text-gray-900 rounded-lg p-1.5 hover:bg-gray-100 active:scale-90 inline-flex h-8 w-8 transition-all duration-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            </button>
        `;
        
        container.appendChild(toast);
        
        setTimeout(() => {
            toast.classList.remove('translate-x-full');
        }, 10);

        setTimeout(() => {
            toast.classList.add('translate-x-full', 'opacity-0');
            setTimeout(() => toast.remove(), 500);
        }, 4000);
    }

    document.addEventListener("DOMContentLoaded", function() {
        const urlParams = new URLSearchParams(window.location.search);
        
        if (urlParams.has('success') || urlParams.has('msg')) {
            let msg = 'Aktivitas berhasil disimpan!';
            let type = 'success';
            
            const val = urlParams.get('success');
            const customMsg = urlParams.get('msg');
            const typeParam = urlParams.get('type');

            if (customMsg) {
                msg = customMsg;
                type = typeParam === 'error' ? 'error' : 'success';
            } else if (val === 'absen') {
                msg = 'Absensi santri berhasil disimpan!';
            } else if (val === 'materi') {
                msg = 'Sub Materi pembelajaran berhasil diperbarui!';
            } else if (val === 'generated') {
                msg = 'Jadwal baru berhasil di-generate secara otomatis!';
            } else if (val === 'tambah_user' || val === 'tambah_kelas' || val === 'tambah_mapel') {
                msg = 'Data baru berhasil ditambahkan ke sistem!';
            } else if (val === 'edit_user' || val === 'edit_kelas') {
                msg = 'Perubahan data berhasil disimpan!';
            } else if (val === 'hapus_user') {
                msg = 'Data berhasil dihapus dari sistem!';
            } else if (val === 'simpan_nilai') {
                msg = 'Nilai santri berhasil disimpan!';
            }
            
            showToast(msg, type);
        }
    });
</script>