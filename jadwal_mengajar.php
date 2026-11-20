<?php 
include 'koneksi.php'; 

// Proteksi Halaman Guru
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Guru') {
    header("Location: login.php");
    exit;
}

$guru_id = $_SESSION['user_id'] ?? $_SESSION['id'];
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0;

$jadwal_id = 0;
if (isset($_GET['jadwal_id'])) {
    $jadwal_id = (int)$_GET['jadwal_id'];
} elseif (isset($_GET['id_jadwal'])) {
    $jadwal_id = (int)$_GET['id_jadwal'];
}

$tanggal_hari_ini = date('Y-m-d');
$hari_ini = $hari[date('w')]; // Mendapatkan nama hari ini (Senin, Selasa, dst)
$error_msg = '';

// AUTO-MIGRATE: Tambah kolom sub_materi
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal LIKE 'sub_materi'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE jadwal ADD COLUMN sub_materi TEXT NULL");
}

// PERBAIKAN: Menambahkan kolom baru khusus untuk kehadiran guru agar tidak bentrok dengan status jadwal
$cek_kolom_hadir = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal LIKE 'status_kehadiran'");
if (mysqli_num_rows($cek_kolom_hadir) == 0) {
    mysqli_query($koneksi, "ALTER TABLE jadwal ADD COLUMN status_kehadiran VARCHAR(20) NULL DEFAULT NULL");
}

// PROSES SIMPAN ABSENSI MANUAL / SCAN
if (isset($_POST['simpan_absensi'])) {
    $jadwal_id = (int)$_POST['jadwal_id'];
    $tanggal = $_POST['tanggal'];
    
    if (isset($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $santri_id => $status) {
            $santri_id = (int)$santri_id;
            $status = mysqli_real_escape_string($koneksi, $status);
            
            $cek = mysqli_query($koneksi, "SELECT id FROM absensi WHERE jadwal_id=$jadwal_id AND santri_id=$santri_id AND tanggal='$tanggal'");
            if (mysqli_num_rows($cek) > 0) {
                mysqli_query($koneksi, "UPDATE absensi SET status='$status' WHERE jadwal_id=$jadwal_id AND santri_id=$santri_id AND tanggal='$tanggal'");
            } else {
                mysqli_query($koneksi, "INSERT INTO absensi (jadwal_id, santri_id, tanggal, status) VALUES ($jadwal_id, $santri_id, '$tanggal', '$status')");
            }
        }
    }
    
    $redirect_action = (isset($_POST['is_scan']) && $_POST['is_scan'] == '1') ? 'absensi_scan' : 'absensi_manual';
    header("Location: jadwal_mengajar.php?action=$redirect_action&kelas_id=$kelas_id&mapel_id=$mapel_id&jadwal_id=$jadwal_id&success=absen");
    exit;
}

// PROSES SIMPAN SUB MATERI (EDIT STATUS MANUAL) -> INI YANG MENGUBAH STATUS DI DATABASE
if (isset($_POST['simpan_materi'])) {
    $id_jadwal_update = (int)$_POST['id_jadwal'];
    $sub_materi = mysqli_real_escape_string($koneksi, $_POST['sub_materi']);
    $status_jadwal = mysqli_real_escape_string($koneksi, $_POST['status_jadwal']);
    
    mysqli_query($koneksi, "UPDATE jadwal SET sub_materi='$sub_materi', status='$status_jadwal' WHERE id=$id_jadwal_update");
    header("Location: jadwal_mengajar.php?action=detail&kelas_id=$kelas_id&mapel_id=$mapel_id&success=materi");
    exit;
}

// AMBIL DATA KELAS & JADWAL GURU (Mencegah Duplikat dengan GROUP BY)
// Cek apakah kolom tanggal ada
$cek_tgl = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal LIKE 'tanggal'");
$has_tgl = mysqli_num_rows($cek_tgl) > 0;
$tgl_select = $has_tgl ? ", MIN(j.tanggal) as tanggal_awal" : ", NULL as tanggal_awal";

$q_jadwal = mysqli_query($koneksi, "
    SELECT j.kelas_id, j.mapel_id, k.nama_kelas, m.nama_mapel, j.jam_mulai, j.jam_selesai, j.hari, j.status, r.nama_ruangan, r.keterangan $tgl_select
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id
    LEFT JOIN ruangan r ON k.ruangan_id = r.id
    WHERE j.guru_id = $guru_id
    GROUP BY j.kelas_id, j.mapel_id
");

// AMBIL INFO JADWAL UNTUK HEADER (DIPERBAIKI: Prioritaskan hari ini)
$info = null;
if ($action == 'detail' || $action == 'riwayat_absensi' || $action == 'metode_absensi' || $action == 'absensi_manual' || $action == 'absensi_scan') {
    $q_info = mysqli_query($koneksi, "SELECT j.*, k.nama_kelas, m.nama_mapel, r.nama_ruangan, r.keterangan, u.nama as nama_guru 
        FROM jadwal j JOIN kelas k ON j.kelas_id = k.id JOIN mata_pelajaran m ON j.mapel_id = m.id 
        LEFT JOIN ruangan r ON k.ruangan_id = r.id JOIN users u ON j.guru_id = u.id
        WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.guru_id = $guru_id 
        ORDER BY CASE WHEN j.tanggal = '$tanggal_hari_ini' THEN 1 ELSE 2 END ASC, j.pertemuan_ke ASC LIMIT 1");
    $info = mysqli_fetch_assoc($q_info);
}

// FUNGSI BANTU UNTUK MENGHITUNG TANGGAL DINAMIS
function hitung_tanggal($hari_target, $pertemuan_ke) {
    $hari_map = ['Senin'=>'Monday', 'Selasa'=>'Tuesday', 'Rabu'=>'Wednesday', 'Kamis'=>'Thursday', 'Jumat'=>'Friday', 'Sabtu'=>'Saturday', 'Minggu'=>'Sunday'];
    $hari_eng = $hari_map[$hari_target] ?? 'Monday';
    $base_date = strtotime("this week $hari_eng");
    return date('d M Y', strtotime("+" . ($pertemuan_ke - 1) . " weeks", $base_date));
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Jadwal Mengajar - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body { height: 100%; overflow: hidden; }
        ::-webkit-scrollbar { height: 8px; width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
        
        @media print {
            @page { margin: 1cm; size: A4 portrait; }
            aside, header, .no-print, .sidebar { display: none !important; }
            /* PERBAIKAN: Memastikan elemen melayang/notifikasi tidak ikut tercetak */
            .fixed, .sticky, .toast, #toast-notif { display: none !important; }
            body { background-color: white !important; overflow: visible !important; margin: 0 !important; padding: 0 !important; }
            .flex-1 { margin-left: 0 !important; width: 100% !important; height: auto !important; overflow: visible !important; }
            main { padding: 0 !important; overflow: visible !important; }
            .print-area { display: block !important; width: 100% !important; }
            .print-table { width: 100% !important; border-collapse: collapse !important; margin-top: 20px; }
            .print-table th, .print-table td { border: 1px solid #000 !important; padding: 6px !important; text-align: center; font-size: 12px !important; color: #000 !important; }
            .print-table th { background-color: #f1f5f4 !important; font-weight: bold; }
            .print-table td.nama { text-align: left !important; padding-left: 10px !important; }
        }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc] font-sans">

    <?php 
    $current_page = 'jadwal_mengajar'; 
    include 'sidebar.php'; 
    ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <?php include 'header.php'; ?>
        
        <main class="flex-1 flex flex-col px-6 py-4 overflow-y-auto">
            
            <!-- NOTIFIKASI TELAH DIHAPUS SESUAI PERMINTAAN -->

            <!-- ======================================================= -->
            <!-- VIEW 1: DAFTAR JADWAL MENGAJAR                          -->
            <!-- ======================================================= -->
            <?php if ($action == 'list'): ?>
            <div class="w-full">
                <!-- Judul -->
                <div class="flex items-center gap-3 mb-4 shrink-0">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0 shadow-sm transition-transform duration-300 hover:scale-110">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Jadwal Mengajar</h1>
                        <p class="text-[13px] font-semibold text-gray-700">Kelola Jadwal mengajar anda di TQA Darussalam....</p>
                    </div>
                </div>

                <h2 class="text-[16px] font-bold text-gray-900 mb-3">Kelas dan Jadwal Mengajar</h2>
                
                <!-- Pencarian & Tombol Cari -->
                <form class="flex gap-2 mb-4 max-w-md shrink-0" onsubmit="event.preventDefault(); filterJadwal();">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="searchKelas" onkeyup="filterJadwal()" class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all duration-200" placeholder="Cari Kelas...">
                    </div>
                    <button type="button" onclick="filterJadwal()" class="bg-[#0b6b53] text-white px-5 py-2 rounded-lg text-[13px] font-semibold hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 whitespace-nowrap">
                        Cari
                    </button>
                </form>

                <div class="space-y-3" id="listJadwalContainer">
                    <?php if(mysqli_num_rows($q_jadwal) > 0): while($j = mysqli_fetch_assoc($q_jadwal)): 
                        // PERBAIKAN: STATUS MURNI DARI DATABASE (Diatur via tombol Edit)
                        $display_status = $j['status'];
                        $badge_class = 'bg-[#fde2e2] text-[#e53e3e]'; // Default: Belum Dimulai (Merah)

                        if ($display_status == 'Sedang Berlangsung') {
                            $badge_class = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                        } elseif ($display_status == 'Selesai') {
                            $badge_class = 'bg-gray-200 text-gray-600'; // Abu-abu
                        }
                    ?>
                    <!-- PERBAIKAN: Menambahkan class 'jadwal-card' untuk fitur pencarian -->
                    <div class="jadwal-card bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(11,107,83,0.15)] border border-[#e6f4f1] p-4 flex items-center justify-between hover:shadow-[0_8px_25px_-5px_rgba(11,107,83,0.25)] hover:-translate-y-1 transition-all duration-300">
                        <div class="flex items-center gap-4">
                            <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-lg flex items-center justify-center shrink-0 transition-colors duration-300 group-hover:bg-[#d1ebe4]">
                                <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-extrabold text-gray-900 uppercase transition-colors duration-300 group-hover:text-[#0b6b53]"><?= htmlspecialchars($j['nama_kelas']) ?></h3>
                                <div class="flex flex-col gap-0.5 mt-1 text-[12px] font-semibold text-gray-700">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                                        <?= $j['hari'] ?>, <?= hitung_tanggal($j['hari'], 1) ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                                        <?= htmlspecialchars($j['nama_mapel']) ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                        <?= htmlspecialchars($j['nama_ruangan'] ?? '-') ?> (<?= htmlspecialchars($j['keterangan'] ?? '-') ?>)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Bagian Kanan (Jam & Status) -->
                        <div class="flex items-center gap-4 ml-auto">
                            <div class="flex flex-col items-end gap-1.5 pr-2">
                                <p class="font-bold text-[14px] text-black"><?= date('H.i', strtotime($j['jam_mulai'])) ?> - <?= date('H.i', strtotime($j['jam_selesai'])) ?></p>
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold flex items-center gap-1.5 <?= $badge_class ?> shadow-sm transition-transform duration-200 group-hover:scale-105">
                                    <span class="w-2 h-2 rounded-full bg-current"></span> <?= $display_status ?>
                                </span>
                            </div>
                            <a href="jadwal_mengajar.php?action=detail&kelas_id=<?= $j['kelas_id'] ?>&mapel_id=<?= $j['mapel_id'] ?>" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-full transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md shrink-0">
                                <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                        <div class="p-5 text-center text-gray-500 bg-white rounded-xl border border-[#e6f4f1] font-semibold text-[13px] shadow-sm">Belum ada jadwal mengajar.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 2: DETAIL JADWAL & DAFTAR PERTEMUAN                -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'detail'): 
                $total_pertemuan = mysqli_num_rows(mysqli_query($koneksi, "SELECT id FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id AND guru_id = $guru_id"));
                $total_santri = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM santri WHERE kelas_id = $kelas_id AND status='Aktif'"))['c'];
                
                // PERBAIKAN: STATUS HEADER MURNI DARI DATABASE
                $display_status_info = $info['status'];
                $badge_info = 'bg-[#fde2e2] text-[#e53e3e]'; // Default: Belum Dimulai (Merah)

                if ($display_status_info == 'Sedang Berlangsung') {
                    $badge_info = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                } elseif ($display_status_info == 'Selesai') {
                    $badge_info = 'bg-gray-200 text-gray-600'; // Abu-abu
                }

                // Cari pertemuan hari ini ATAU pertemuan berikutnya yang belum selesai
                $q_today_meeting = mysqli_query($koneksi, "SELECT pertemuan_ke FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id AND guru_id = $guru_id AND (tanggal = '$tanggal_hari_ini' OR (tanggal IS NULL AND hari = '$hari_ini')) LIMIT 1");
                $d_today_meeting = mysqli_fetch_assoc($q_today_meeting);
                
                if ($d_today_meeting) {
                    $no_pertemuan_hari_ini = $d_today_meeting['pertemuan_ke'];
                } else {
                    $q_next_meeting = mysqli_query($koneksi, "SELECT pertemuan_ke FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id AND guru_id = $guru_id AND status != 'Selesai' ORDER BY pertemuan_ke ASC LIMIT 1");
                    $d_next_meeting = mysqli_fetch_assoc($q_next_meeting);
                    $no_pertemuan_hari_ini = $d_next_meeting ? $d_next_meeting['pertemuan_ke'] : 1;
                }
            ?>
            <div class="w-full">
                <!-- Header Info Card -->
                <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-5 flex items-start justify-between mb-4 relative transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <div class="flex items-center gap-4">
                        <a href="jadwal_mengajar.php" class="w-9 h-9 bg-[#e6f4f1] rounded-full flex items-center justify-center hover:bg-[#d1e7e2] transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md shrink-0">
                            <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                        <div>
                            <h2 class="text-[20px] font-extrabold text-gray-900 mb-1"><?= htmlspecialchars($info['nama_kelas']) ?></h2>
                            <div class="flex flex-col gap-0.5 text-[12.5px] font-semibold text-gray-700">
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg> <?= htmlspecialchars($info['nama_mapel']) ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg> <?= date('H.i', strtotime($info['jam_mulai'])) ?> - <?= date('H.i', strtotime($info['jam_selesai'])) ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg> <?= htmlspecialchars($info['nama_guru']) ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg> Pertemuan <?= $no_pertemuan_hari_ini ?>/<?= $total_pertemuan ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg> Total Santri : <?= $total_santri ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg> <?= htmlspecialchars($info['nama_ruangan']) ?> (<?= htmlspecialchars($info['keterangan']) ?>)</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- BAGIAN KANAN: STATUS & TOMBOL ABSENSI -->
                    <div class="flex flex-col items-end gap-3">
                        <span class="px-3 py-1 <?= $badge_info ?> rounded-full text-[11px] font-bold flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-current"></span> <?= $display_status_info ?>
                        </span>
                        
                        <a href="jadwal_mengajar.php?action=metode_absensi&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&jadwal_id=<?= $info['id'] ?>" class="border-[2px] border-[#0b6b53] text-[#0b6b53] bg-[#e6f4f1] px-6 py-2.5 rounded-lg font-bold text-[14px] hover:bg-[#d1e7e2] transition-all duration-200 active:scale-95 flex items-center gap-2 shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Lakukan Absensi
                        </a>
                    </div>
                </div>

                <!-- Tabel Daftar Pertemuan -->
                <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] overflow-hidden mb-4 transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#f0f7f4] text-[#0b6b53]">
                            <tr>
                                <th class="px-4 py-3 font-bold text-[13px] text-center">Pertemuan</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-center">Tanggal</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-center">Sub Materi</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-center">Status Absensi</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                            <?php 
                            $q_pertemuan = mysqli_query($koneksi, "SELECT * FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id AND guru_id = $guru_id ORDER BY pertemuan_ke ASC");
                            $no = 1;
                            while($p = mysqli_fetch_assoc($q_pertemuan)): 
                                // PERBAIKAN: STATUS TABEL MURNI DARI DATABASE JADWAL (BUKAN status_kehadiran)
                                $display_status = $p['status'];
                                $b_class = 'bg-[#fde2e2] text-[#e53e3e]'; // Default: Belum Dimulai (Merah)

                                if ($display_status == 'Sedang Berlangsung') {
                                    $b_class = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                                } elseif ($display_status == 'Selesai') {
                                    $b_class = 'bg-gray-200 text-gray-600'; // Abu-abu
                                }
                                
                                $is_belum_mulai = ($display_status == 'Belum Dimulai');
                                $sub_materi = (!$is_belum_mulai && !empty($p['sub_materi'])) ? $p['sub_materi'] : '-';

                                // CEK APAKAH JADWAL INI HARI INI
                                $is_hari_ini = false;
                                if (!empty($p['tanggal'])) {
                                    $is_hari_ini = ($p['tanggal'] == $tanggal_hari_ini);
                                } else {
                                    $is_hari_ini = ($p['hari'] == $hari_ini);
                                }

                                // LOGIKA TOMBOL EDIT
                                $bisa_diedit = ($display_status == 'Sedang Berlangsung' || $display_status == 'Selesai' || $is_hari_ini);
                            ?>
                            <tr class="hover:bg-gray-50 transition-all duration-200 ease-in-out hover:shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] h-[50px]">
                                <td class="px-4 py-2 text-center font-bold text-[#0b6b53]">Ke-<?= $p['pertemuan_ke'] ?></td>
                                
                                <td class="px-4 py-2 text-center"><?= !empty($p['tanggal']) ? date('d M Y', strtotime($p['tanggal'])) : hitung_tanggal($p['hari'], $p['pertemuan_ke']) ?></td>
                                
                                <td class="px-4 py-2 text-center text-gray-600"><?= htmlspecialchars($sub_materi) ?></td>
                                <td class="px-4 py-2 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= $b_class ?> shadow-sm"><?= $display_status ?></span>
                                </td>
                                <td class="px-4 py-2 text-center flex justify-center gap-1.5 items-center">
                                    <?php if ($is_hari_ini || $display_status == 'Selesai'): ?>
                                        <a href="jadwal_mengajar.php?action=riwayat_absensi&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&jadwal_id=<?= $p['id'] ?>" class="w-7 h-7 bg-black text-white rounded-full flex items-center justify-center hover:bg-gray-800 transition-all duration-200 hover:scale-110 active:scale-90 shadow-sm hover:shadow-md" title="Lihat Detail & Riwayat">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($bisa_diedit): ?>
                                        <a href="jadwal_mengajar.php?action=edit_materi&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&id_jadwal=<?= $p['id'] ?>" class="w-7 h-7 bg-[#fef3c7] rounded flex items-center justify-center text-[#d97706] hover:bg-yellow-200 transition-all duration-200 hover:scale-110 active:scale-90 shadow-sm hover:shadow-md" title="Edit Sub Materi">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-gray-400 font-bold w-7 text-center">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php $no++; endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 3: FORM EDIT SUB MATERI                            -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'edit_materi'): 
                $q_edit = mysqli_query($koneksi, "SELECT * FROM jadwal WHERE id = $jadwal_id");
                $d_edit = mysqli_fetch_assoc($q_edit);
            ?>
            <div class="max-w-2xl mx-auto w-full">
                <div class="flex items-center gap-3 mb-5 shrink-0">
                    <a href="jadwal_mengajar.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-tight">Edit Sub Materi</h1>
                        <p class="text-[13px] font-medium text-gray-600">Pertemuan Ke-<?= $d_edit['pertemuan_ke'] ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] transition-all duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <form method="POST" action="jadwal_mengajar.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>">
                        <input type="hidden" name="id_jadwal" value="<?= $jadwal_id ?>">
                        
                        <div class="mb-4">
                            <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Sub Materi Pembelajaran</label>
                            <textarea name="sub_materi" rows="4" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[14px] shadow-sm transition-all" placeholder="Tuliskan materi yang diajarkan hari ini..." required><?= htmlspecialchars($d_edit['sub_materi'] ?? '') ?></textarea>
                        </div>

                        <div class="mb-6">
                            <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Status Pelaksanaan</label>
                            <select name="status_jadwal" class="w-full px-3.5 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[14px] shadow-sm transition-all cursor-pointer">
                                <option value="Belum Dimulai" <?= $d_edit['status'] == 'Belum Dimulai' ? 'selected' : '' ?>>Belum Dimulai</option>
                                <option value="Sedang Berlangsung" <?= $d_edit['status'] == 'Sedang Berlangsung' ? 'selected' : '' ?>>Sedang Berlangsung</option>
                                <option value="Selesai" <?= $d_edit['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                            </select>
                        </div>

                        <div class="flex gap-3 pt-5 border-t border-gray-100">
                            <button type="submit" name="simpan_materi" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[14px] py-2.5 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                                Simpan Sub Materi
                            </button>
                            <a href="jadwal_mengajar.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold text-[14px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95 shadow-sm hover:shadow-md">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 4: RIWAYAT ABSENSI (DETAIL PERTEMUAN)              -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'riwayat_absensi'): 
                // Ambil data santri dan status absensinya untuk jadwal_id ini
                $q_riwayat = mysqli_query($koneksi, "SELECT s.id as santri_id, s.nama_santri, a.status 
                    FROM santri s 
                    LEFT JOIN absensi a ON s.id = a.santri_id AND a.jadwal_id = $jadwal_id
                    WHERE s.kelas_id = $kelas_id AND s.status = 'Aktif' ORDER BY s.nama_santri ASC");
                
                $jadwal_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM jadwal WHERE id = $jadwal_id"));
            ?>
            <div class="w-full">
                <!-- Header Info Card Detail Pertemuan -->
                <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-5 mb-4 transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <div class="flex justify-between items-center mb-4">
                        <div class="flex items-center gap-3">
                            <a href="jadwal_mengajar.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-9 h-9 bg-[#e6f4f1] rounded-full flex items-center justify-center hover:bg-[#d1e7e2] transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md shrink-0">
                                <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </a>
                            <div>
                                <h2 class="text-[22px] font-extrabold text-gray-900 leading-tight">Pertemuan Ke-<?= $jadwal_info['pertemuan_ke'] ?></h2>
                                <p class="text-[13px] font-semibold text-gray-600"><?= htmlspecialchars($info['nama_kelas']) ?> - <?= htmlspecialchars($info['nama_mapel']) ?></p>
                            </div>
                        </div>
                        <!-- Tombol Cek / Lakukan Absensi (Mengarah ke Metode) -->
                        <a href="jadwal_mengajar.php?action=metode_absensi&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&jadwal_id=<?= $jadwal_id ?>" class="border-[2px] border-[#0b6b53] text-[#0b6b53] bg-[#e6f4f1] px-5 py-2 rounded-lg font-bold text-[14px] hover:bg-[#d1e7e2] transition-all duration-200 active:scale-95 flex items-center gap-2 shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"></path></svg>
                            Cek / Lakukan Absensi
                        </a>
                    </div>
                    
                    <!-- Grid Detail (Tanggal, Jam, Sub Materi, Status) -->
                    <div class="grid grid-cols-4 gap-4 bg-[#f8fafc] p-4 rounded-xl border border-gray-200">
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Tanggal</p>
                            <p class="font-bold text-[14px] text-black"><?= date('d M Y') ?></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Jam</p>
                            <p class="font-bold text-[14px] text-black"><?= date('H.i', strtotime($jadwal_info['jam_mulai'])) ?> - <?= date('H.i', strtotime($jadwal_info['jam_selesai'])) ?></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Sub Materi</p>
                            <p class="font-bold text-[14px] text-black"><?= !empty($jadwal_info['sub_materi']) ? htmlspecialchars($jadwal_info['sub_materi']) : '-' ?></p>
                        </div>
                        <div>
                            <p class="text-[11px] font-bold text-gray-500 mb-1 uppercase tracking-wider">Status</p>
                            <?php 
                                $b_class = 'bg-[#fde2e2] text-[#e53e3e]'; // Default Merah
                                if ($jadwal_info['status'] == 'Sedang Berlangsung') $b_class = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                                if ($jadwal_info['status'] == 'Selesai') $b_class = 'bg-gray-200 text-gray-600'; // Abu
                            ?>
                            <span class="px-2.5 py-1 rounded-md text-[12px] font-bold <?= $b_class ?> shadow-sm">
                                <?= $jadwal_info['status'] ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Tabel Riwayat Absensi -->
                <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] overflow-hidden transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#f0f7f4] text-[#0b6b53]">
                            <tr>
                                <th class="px-4 py-3 font-bold text-[13px] text-center w-12">No</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-center">Nama Santri</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-center">Status Kehadiran</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                            <?php 
                            $no = 1;
                            if (mysqli_num_rows($q_riwayat) > 0) {
                                while($r = mysqli_fetch_assoc($q_riwayat)): 
                                    // FIX: Handle empty string from DB
                                    $status = (!empty($r['status'])) ? $r['status'] : 'Belum Absen';
                                    
                                    $badge_class = 'bg-gray-100 text-gray-600';
                                    if ($status == 'Hadir') $badge_class = 'bg-[#e6f7ec] text-[#0b6b53]';
                                    if ($status == 'Izin') $badge_class = 'bg-yellow-100 text-yellow-700';
                                    if ($status == 'Sakit') $badge_class = 'bg-pink-100 text-pink-700';
                                    if ($status == 'Alpha') $badge_class = 'bg-red-100 text-red-700';
                            ?>
                            <tr class="hover:bg-gray-50 transition-all duration-200 ease-in-out hover:shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] h-[45px]">
                                <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                                <td class="px-4 py-2 text-center font-semibold"><?= htmlspecialchars($r['nama_santri']) ?></td>
                                <td class="px-4 py-2 text-center">
                                    <span class="px-3 py-1.5 rounded-md text-[12px] font-bold <?= $badge_class ?> shadow-sm">
                                        <?= $status ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; } else { ?>
                                <tr><td colspan="3" class="p-5 text-center text-gray-500 font-semibold text-[13px]">Tidak ada data santri.</td></tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 5: PILIH METODE ABSENSI                            -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'metode_absensi'): ?>
            <div class="w-full">
                <!-- Header Info Card (Sesuai Design) -->
                <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-4 flex items-center justify-between mb-6 transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <div class="flex items-center gap-6">
                        <a href="jadwal_mengajar.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-10 h-10 bg-[#e6f4f1] rounded-full flex items-center justify-center hover:bg-[#d1e7e2] transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                        <div class="flex items-center gap-3 border-r-2 border-gray-200 pr-6">
                            <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.328.996.002 1.069c0 .392.23.755.588.93l4 1.953a1 1 0 00.868 0l4-1.953a1.05 1.05 0 00.588-.93V8.25l-2.25.964v4.867a1 1 0 01-.53.882l-4 2.222a1 1 0 01-.94 0l-4-2.222a1 1 0 01-.53-.882V6.79l-2.25-.964a1 1 0 010-1.84l7-3z"/></svg></div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-500">Kelas</p>
                                <p class="font-bold text-[14px] text-black"><?= htmlspecialchars($info['nama_kelas']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 border-r-2 border-gray-200 pr-6">
                            <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg></div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-500">Mata Pelajaran</p>
                                <p class="font-bold text-[14px] text-black"><?= htmlspecialchars($info['nama_mapel']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg></div>
                            <div>
                                <p class="text-[11px] font-bold text-gray-500">Tanggal</p>
                                <p class="font-bold text-[14px] text-black"><?= date('d M Y') ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Pilihan Card -->
                <div class="grid grid-cols-2 gap-6">
                    <!-- Card QR -->
                    <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-8 flex flex-col items-center text-center transition-all duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)] hover:-translate-y-1">
                        <div class="w-48 h-48 bg-[#e6f4f1] rounded-full flex items-center justify-center mb-5 shadow-inner">
                            <svg class="w-24 h-24 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h6v6H3V3zm2 2v2h2V5H5zm8-2h6v6h-6V3zm2 2v2h2V5h-2zM3 15h6v6H3v-6zm2 2v2h2v-2H5zm10-2h4v2h-4v-2zm-2 4h2v2h-2v-2zm4 0h2v2h-2v-2z"/></svg>
                        </div>
                        <h3 class="text-[18px] font-extrabold text-black mb-2">Absensi QR CODE</h3>
                        <p class="text-[13px] font-semibold text-gray-600 mb-6">scan QR Code Santri<br>untuk melakukan Absensi</p>
                        <a href="jadwal_mengajar.php?action=absensi_scan&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&jadwal_id=<?= $jadwal_id ?>" class="bg-[#0b6b53] text-white px-6 py-2.5 rounded-lg font-bold text-[14px] hover:bg-[#08533f] w-full transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg">
                            Mulai Scan
                        </a>
                    </div>

                    <!-- Card Manual -->
                    <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-8 flex flex-col items-center text-center transition-all duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)] hover:-translate-y-1">
                        <div class="w-48 h-48 bg-[#e6f4f1] rounded-full flex items-center justify-center mb-5 shadow-inner">
                            <svg class="w-24 h-24 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                        </div>
                        <h3 class="text-[18px] font-extrabold text-black mb-2">Absensi Manual</h3>
                        <p class="text-[13px] font-semibold text-gray-600 mb-6">Input daftar hadir santri<br>secara manual</p>
                        <a href="jadwal_mengajar.php?action=absensi_manual&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&jadwal_id=<?= $jadwal_id ?>" class="bg-[#0b6b53] text-white px-6 py-2.5 rounded-lg font-bold text-[14px] hover:bg-[#08533f] w-full transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg">
                            Input Manual
                        </a>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 6: ABSENSI MANUAL                                  -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'absensi_manual'): 
                $q_santri = mysqli_query($koneksi, "SELECT * FROM santri WHERE kelas_id = $kelas_id AND status = 'Aktif' ORDER BY nama_santri ASC");
                
                // Ambil data absensi dari DB untuk keperluan cetak (jika sudah disimpan)
                $absensi_manual_data = [];
                $q_absen_print_manual = mysqli_query($koneksi, "SELECT santri_id, status FROM absensi WHERE jadwal_id = $jadwal_id AND tanggal = '$tanggal_hari_ini'");
                while($a = mysqli_fetch_assoc($q_absen_print_manual)) {
                    $absensi_manual_data[$a['santri_id']] = $a['status'];
                }
                
                // Ambil info jadwal spesifik untuk print
                $jadwal_manual_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM jadwal WHERE id = $jadwal_id"));
            ?>
            <div class="w-full print-area">
                <!-- TAMPILAN LAYAR (WEB) -->
                <div class="no-print">
                    <!-- Header Web (Sesuai Design) -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <a href="jadwal_mengajar.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-10 h-10 bg-[#e6f4f1] rounded-xl flex items-center justify-center hover:bg-[#d1e7e2] transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </a>
                            <div>
                                <h1 class="text-[22px] font-bold text-black leading-tight">Absensi Manual</h1>
                                <p class="text-[13px] font-semibold text-gray-700">Kelola Absensi Santri TQA Darussalam</p>
                            </div>
                        </div>
                        <button onclick="window.print()" class="border-2 border-[#0b6b53] text-[#0b6b53] px-5 py-2.5 rounded-lg font-bold text-[14px] hover:bg-[#e6f4f1] transition-all duration-200 active:scale-95 flex items-center gap-2 shadow-sm hover:shadow-md">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                            Export
                        </button>
                    </div>

                    <!-- Header Info Card (Sesuai Design) -->
                    <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-4 flex items-center justify-between mb-4 transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                        <div class="flex items-center gap-6">
                            <div class="w-10 h-10 bg-[#e6f4f1] rounded-full flex items-center justify-center shadow-sm"><svg class="w-5 h-5 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg></div>
                            <div class="flex items-center gap-3 border-r-2 border-gray-200 pr-6">
                                <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.328.996.002 1.069c0 .392.23.755.588.93l4 1.953a1 1 0 00.868 0l4-1.953a1.05 1.05 0 00.588-.93V8.25l-2.25.964v4.867a1 1 0 01-.53.882l-4 2.222a1 1 0 01-.94 0l-4-2.222a1 1 0 01-.53-.882V6.79l-2.25-.964a1 1 0 010-1.84l7-3z"/></svg></div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-500">Kelas</p>
                                    <p class="font-bold text-[14px] text-black"><?= htmlspecialchars($info['nama_kelas']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 border-r-2 border-gray-200 pr-6">
                                <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg></div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-500">Mata Pelajaran</p>
                                    <p class="font-bold text-[14px] text-black"><?= htmlspecialchars($info['nama_mapel']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg></div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-500">Tanggal</p>
                                    <p class="font-bold text-[14px] text-black"><?= date('d M Y') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Absensi -->
                    <form method="POST">
                        <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                        <input type="hidden" name="tanggal" value="<?= $tanggal_hari_ini ?>">

                        <div class="flex justify-between items-center mb-4">
                            <div class="relative w-1/2">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <!-- PERBAIKAN: Menambahkan id dan onkeyup untuk fitur pencarian -->
                                <input type="text" id="searchSantri" onkeyup="filterSantri()" class="w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" placeholder="Cari Nama Santri...">
                            </div>
                            <button type="submit" name="simpan_absensi" class="bg-[#0b6b53] text-white px-5 py-2.5 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 active:scale-95 flex items-center gap-2 shadow-md hover:shadow-lg">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                Simpan Absensi
                            </button>
                        </div>

                        <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] overflow-hidden transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                            <div class="p-4 border-b border-gray-100 flex justify-between items-center">
                                <h3 class="font-bold text-black text-[16px]">Daftar Absensi Santri</h3>
                                <label class="flex items-center gap-2 cursor-pointer font-semibold text-[13px] text-gray-700 group">
                                    <input type="checkbox" id="checkAll" class="w-5 h-5 rounded border-gray-300 text-[#0b6b53] focus:ring-[#0b6b53] transition-all cursor-pointer">
                                    Tandai Hadir Semua
                                </label>
                            </div>
                            <table class="w-full text-left border-collapse">
                                <thead class="bg-[#f0f7f4] text-[#0b6b53]">
                                    <tr>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center w-12">No</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Nama Santri</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Hadir</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Izin</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Sakit</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Alpha</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    <?php 
                                    $no = 1;
                                    while($s = mysqli_fetch_assoc($q_santri)): 
                                    ?>
                                    <!-- PERBAIKAN: Menambahkan class 'santri-row' untuk target pencarian -->
                                    <tr class="santri-row hover:bg-gray-50 font-medium text-[13.5px] text-black h-[45px] transition-colors duration-200">
                                        <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                                        <td class="px-4 py-2 text-center font-bold"><?= htmlspecialchars($s['nama_santri']) ?></td>
                                        
                                        <!-- Radio Buttons -->
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" name="status[<?= $s['id'] ?>]" value="Hadir" class="w-5 h-5 text-[#0b6b53] focus:ring-[#0b6b53] border-gray-300 cursor-pointer transition-transform hover:scale-110" checked>
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" name="status[<?= $s['id'] ?>]" value="Izin" class="w-5 h-5 text-yellow-500 focus:ring-yellow-500 border-gray-300 cursor-pointer transition-transform hover:scale-110">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" name="status[<?= $s['id'] ?>]" value="Sakit" class="w-5 h-5 text-pink-500 focus:ring-pink-500 border-gray-300 cursor-pointer transition-transform hover:scale-110">
                                        </td>
                                        <td class="px-4 py-2 text-center">
                                            <input type="radio" name="status[<?= $s['id'] ?>]" value="Alpha" class="w-5 h-5 text-red-600 focus:ring-red-600 border-gray-300 cursor-pointer transition-transform hover:scale-110">
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </form>
                </div> <!-- End of no-print -->

                <!-- ========================================== -->
                <!-- TAMPILAN PRINT ABSENSI MANUAL (BARU)       -->
                <!-- ========================================== -->
                <div class="hidden print:block w-full print-area">
                    <!-- Kop Surat / Header -->
                    <div class="text-center mb-6 border-b-4 border-double border-gray-800 pb-4">
                        <h1 class="text-[24px] font-extrabold text-black uppercase tracking-wider">TQA Darussalam</h1>
                        <p class="text-[14px] font-semibold text-gray-700">Ilmu • Akhlak • Prestasi</p>
                        <p class="text-[16px] font-bold text-black mt-2">LAPORAN ABSENSI SANTRI</p>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-6 text-[13px]">
                        <div class="space-y-1">
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Kelas</span><span class="font-bold text-black">: <?= htmlspecialchars($info['nama_kelas']) ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Mata Pelajaran</span><span class="font-bold text-black">: <?= htmlspecialchars($info['nama_mapel']) ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Guru Pengajar</span><span class="font-bold text-black">: <?= htmlspecialchars($info['nama_guru']) ?></span></div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Hari, Tanggal</span><span class="font-bold text-black">: <?= $info['hari'] ?>, <?= date('d M Y') ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Jam Pelajaran</span><span class="font-bold text-black">: <?= date('H.i', strtotime($jadwal_manual_info['jam_mulai'])) ?> - <?= date('H.i', strtotime($jadwal_manual_info['jam_selesai'])) ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Pertemuan Ke-</span><span class="font-bold text-black">: <?= $jadwal_manual_info['pertemuan_ke'] ?></span></div>
                        </div>
                    </div>

                    <!-- Tabel Print -->
                    <table class="print-table w-full border-collapse border border-gray-800">
                        <thead>
                            <tr>
                                <th class="border border-gray-800 p-2 text-center" width="5%">No</th>
                                <th class="border border-gray-800 p-2 text-center" width="15%">NIS</th>
                                <th class="border border-gray-800 p-2 text-center" width="35%">Nama Santri</th>
                                <th class="border border-gray-800 p-2 text-center" width="10%">L/P</th>
                                <th class="border border-gray-800 p-2 text-center" width="8%">Hadir</th>
                                <th class="border border-gray-800 p-2 text-center" width="8%">Izin</th>
                                <th class="border border-gray-800 p-2 text-center" width="8%">Sakit</th>
                                <th class="border border-gray-800 p-2 text-center" width="11%">Alpha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($q_santri, 0);
                            $no = 1;
                            while($s = mysqli_fetch_assoc($q_santri)): 
                                // Gunakan data dari DB, jika belum ada (belum disimpan) default ke Alpha
                                $status_print = isset($absensi_manual_data[$s['id']]) ? $absensi_manual_data[$s['id']] : 'Alpha'; 
                            ?>
                            <tr class="border-b border-gray-800">
                                <td class="border border-gray-800 p-2 text-center text-black"><?= $no++ ?></td>
                                <td class="border border-gray-800 p-2 text-center text-black"><?= htmlspecialchars($s['nis']) ?></td>
                                <td class="border border-gray-800 p-2 text-left text-black nama"><?= htmlspecialchars($s['nama_santri']) ?></td>
                                <td class="border border-gray-800 p-2 text-center text-black"><?= $s['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Hadir' ? '✓' : '' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Izin' ? '✓' : '' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Sakit' ? '✓' : '' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Alpha' ? '✓' : '' ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Tanda Tangan -->
                    <div class="flex justify-between mt-16 text-[13px]">
                        <div class="text-center w-48">
                            <p class="mb-16">Mengetahui,<br>Kepala TQA Darussalam</p>
                            <p class="font-bold underline text-black">( ............................................ )</p>
                        </div>
                        <div class="text-center w-48">
                            <p class="mb-16"><?= date('d M Y') ?><br>Guru Mata Pelajaran</p>
                            <p class="font-bold underline text-black"><?= htmlspecialchars($info['nama_guru']) ?></p>
                        </div>
                    </div>
                </div> <!-- End of print-only -->

            </div>

            <!-- ======================================================= -->
            <!-- VIEW 7: ABSENSI SCAN QR                                 -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'absensi_scan'): 
                $q_santri = mysqli_query($koneksi, "SELECT * FROM santri WHERE kelas_id = $kelas_id AND status = 'Aktif' ORDER BY nama_santri ASC");
                
                // Ambil data absensi dari DB untuk keperluan cetak (jika sudah disimpan)
                $absensi_data = [];
                $q_absen_print = mysqli_query($koneksi, "SELECT santri_id, status FROM absensi WHERE jadwal_id = $jadwal_id AND tanggal = '$tanggal_hari_ini'");
                while($a = mysqli_fetch_assoc($q_absen_print)) {
                    $absensi_data[$a['santri_id']] = $a['status'];
                }

                // Cek apakah sudah ada yang berstatus 'Hadir'
                $ada_hadir = false;
                foreach ($absensi_data as $st) {
                    if ($st == 'Hadir') { $ada_hadir = true; break; }
                }

                // Ambil info jadwal spesifik (pertemuan & jam) untuk print
                $jadwal_print_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM jadwal WHERE id = $jadwal_id"));
            ?>
            <div class="w-full print-area">
                
                <!-- TAMPILAN LAYAR -->
                <div class="no-print">
                    <!-- Header Info Card (Sesuai Design) -->
                    <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-4 flex items-center justify-between mb-6 transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                        <div class="flex items-center gap-6">
                            <!-- TOMBOL BACK: Langsung ke halaman detail (Halaman awal Lakukan Absensi) -->
                            <a href="jadwal_mengajar.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-10 h-10 bg-[#e6f4f1] rounded-full flex items-center justify-center hover:bg-[#d1e7e2] transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md">
                                <svg class="w-5 h-5 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </a>
                            <div class="flex items-center gap-3 border-r-2 border-gray-200 pr-6">
                                <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.328.996.002 1.069c0 .392.23.755.588.93l4 1.953a1 1 0 00.868 0l4-1.953a1.05 1.05 0 00.588-.93V8.25l-2.25.964v4.867a1 1 0 01-.53.882l-4 2.222a1 1 0 01-.94 0l-4-2.222a1 1 0 01-.53-.882V6.79l-2.25-.964a1 1 0 010-1.84l7-3z"/></svg></div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-500">Kelas</p>
                                    <p class="font-bold text-[14px] text-black"><?= htmlspecialchars($info['nama_kelas']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3 border-r-2 border-gray-200 pr-6">
                                <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg></div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-500">Mata Pelajaran</p>
                                    <p class="font-bold text-[14px] text-black"><?= htmlspecialchars($info['nama_mapel']) ?></p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-[#e6f4f1] rounded-full flex items-center justify-center"><svg class="w-4 h-4 text-[#0b6b53]" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg></div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-500">Tanggal</p>
                                    <p class="font-bold text-[14px] text-black"><?= date('d M Y') ?></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Penampung Data Scan -->
                    <form method="POST" id="formAbsensiScan">
                        <input type="hidden" name="simpan_absensi" value="1">
                        <input type="hidden" name="is_scan" value="1">
                        <input type="hidden" name="jadwal_id" value="<?= $jadwal_id ?>">
                        <input type="hidden" name="tanggal" value="<?= $tanggal_hari_ini ?>">
                        
                        <!-- Layout Grid -->
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-5">
                            <!-- Kolom Scanner -->
                            <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] p-5 flex flex-col items-center text-center transition-all duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                                <div id="reader" class="w-full rounded-xl overflow-hidden mb-4 border-2 border-[#0b6b53] bg-gray-50 relative" style="min-height: 300px;">
                                    <svg class="w-32 h-32 mx-auto mt-10 text-[#0b6b53] opacity-50" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h6v6H3V3zm2 2v2h2V5H5zm8-2h6v6h-6V3zm2 2v2h2V5h-2zM3 15h6v6H3v-6zm2 2v2h2v-2H5zm10-2h4v2h-4v-2zm-2 4h2v2h-2v-2zm4 0h2v2h-2v-2z"/></svg>
                                    <p class="absolute bottom-4 w-full text-center text-gray-500 font-bold text-[12px]">Kamera belum aktif...</p>
                                </div>
                                <svg class="w-8 h-8 text-black mb-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                                <h3 class="font-bold text-black text-[15px]">Scan QR Code</h3>
                                <p class="text-[12px] font-semibold text-gray-500 mt-1">Arahkan kamera ke Barcode Santri<br>untuk melakukan Absensi</p>
                                
                                <button type="button" id="start-camera" class="mt-5 bg-[#0b6b53] text-white px-5 py-2.5 rounded-xl font-bold text-[14px] hover:bg-[#08533f] transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg w-full">Mulai Scan</button>
                            </div>

                            <!-- Kolom Teridentifikasi Hadir -->
                            <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] flex flex-col h-[500px] transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                                <div class="p-4 border-b border-gray-100">
                                    <h3 class="font-bold text-black text-[14px]">Teridentifikasi Hadir</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto p-2" id="list-hadir">
                                    <p class="text-center text-gray-500 font-semibold text-[13px] mt-4 <?= $ada_hadir ? 'hidden' : '' ?>" id="empty-hadir">Belum ada data discan.</p>
                                    <?php 
                                    mysqli_data_seek($q_santri, 0);
                                    while($s = mysqli_fetch_assoc($q_santri)): 
                                        $status_scan = isset($absensi_data[$s['id']]) ? $absensi_data[$s['id']] : 'Alpha';
                                        if ($status_scan == 'Hadir'):
                                    ?>
                                        <div class="flex justify-between items-center p-2 border-b border-gray-100">
                                            <div class="flex items-center gap-2">
                                                <span class="font-semibold text-[13px] text-black"><?= htmlspecialchars($s['nama_santri']) ?></span>
                                            </div>
                                            <span class="px-3 py-1 bg-[#e6f7ec] text-[#0b6b53] rounded-full text-[11px] font-bold flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Hadir</span>
                                            <input type="hidden" name="status[<?= $s['id'] ?>]" value="Hadir">
                                        </div>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </div>
                            </div>

                            <!-- Kolom Tidak Hadir -->
                            <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] flex flex-col h-[500px] transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                                <div class="p-4 border-b border-gray-100">
                                    <h3 class="font-bold text-black text-[14px]">Tidak Hadir Hari ini</h3>
                                </div>
                                <div class="flex-1 overflow-y-auto p-2" id="list-tidak-hadir">
                                    <?php 
                                    $no=1; 
                                    mysqli_data_seek($q_santri, 0);
                                    while($s = mysqli_fetch_assoc($q_santri)): 
                                        $status_scan = isset($absensi_data[$s['id']]) ? $absensi_data[$s['id']] : 'Alpha';
                                        if ($status_scan != 'Hadir'):
                                    ?>
                                    <div class="flex justify-between items-center p-2 border-b border-gray-100 santri-item hover:bg-gray-50 transition-colors duration-200" id="santri-<?= $s['id'] ?>" data-id="<?= $s['id'] ?>" data-nama="<?= htmlspecialchars($s['nama_santri']) ?>">
                                        <div class="flex items-center gap-3 w-2/3">
                                            <span class="text-[12px] font-bold text-gray-500 w-4"><?= $no++ ?></span>
                                            <span class="font-bold text-[13px] text-gray-800 truncate"><?= htmlspecialchars($s['nama_santri']) ?></span>
                                        </div>
                                        <select name="status[<?= $s['id'] ?>]" class="status-dropdown px-2 py-1 rounded-md border-none bg-transparent text-[12px] font-bold text-red-600 outline-none focus:ring-0 cursor-pointer">
                                            <option value="Alpha" class="text-red-600 bg-red-50" <?= $status_scan == 'Alpha' ? 'selected' : '' ?>>Alpha</option>
                                            <option value="Izin" class="text-yellow-600 bg-yellow-50" <?= $status_scan == 'Izin' ? 'selected' : '' ?>>Izin</option>
                                            <option value="Sakit" class="text-pink-600 bg-pink-50" <?= $status_scan == 'Sakit' ? 'selected' : '' ?>>Sakit</option>
                                            <option value="Hadir" class="text-[#0b6b53] bg-green-50">Hadir</option>
                                        </select>
                                    </div>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </div>
                            </div>
                        </div>

                        <!-- Tombol Bawah -->
                        <div class="flex justify-end gap-3 mt-5">
                            <button type="button" onclick="window.print()" class="border-2 border-[#0b6b53] text-[#0b6b53] px-5 py-2.5 rounded-xl font-bold text-[14px] hover:bg-[#e6f4f1] transition-all duration-200 active:scale-95 shadow-sm hover:shadow-md flex items-center gap-2">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                Export
                            </button>
                            <button type="submit" class="bg-[#0b6b53] text-white px-6 py-2.5 rounded-xl font-bold text-[14px] hover:bg-[#08533f] transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg flex items-center gap-2">
                                <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                Simpan Absensi
                            </button>
                        </div>
                    </form>
                </div> <!-- End of no-print -->

                <!-- ========================================== -->
                <!-- TAMPILAN PRINT (SAMA SEPERTI SEBELUMNYA)   -->
                <!-- ========================================== -->
                <div class="hidden print:block w-full print-area">
                    <!-- Kop Surat / Header -->
                    <div class="text-center mb-6 border-b-4 border-double border-gray-800 pb-4">
                        <h1 class="text-[24px] font-extrabold text-black uppercase tracking-wider">TQA Darussalam</h1>
                        <p class="text-[14px] font-semibold text-gray-700">Ilmu • Akhlak • Prestasi</p>
                        <p class="text-[16px] font-bold text-black mt-2">LAPORAN ABSENSI SANTRI</p>
                    </div>

                    <!-- Info Grid -->
                    <div class="grid grid-cols-2 gap-4 mb-6 text-[13px]">
                        <div class="space-y-1">
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Kelas</span><span class="font-bold text-black">: <?= htmlspecialchars($info['nama_kelas']) ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Mata Pelajaran</span><span class="font-bold text-black">: <?= htmlspecialchars($info['nama_mapel']) ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Guru Pengajar</span><span class="font-bold text-black">: <?= htmlspecialchars($info['nama_guru']) ?></span></div>
                        </div>
                        <div class="space-y-1">
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Hari, Tanggal</span><span class="font-bold text-black">: <?= $info['hari'] ?>, <?= date('d M Y') ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Jam Pelajaran</span><span class="font-bold text-black">: <?= date('H.i', strtotime($jadwal_print_info['jam_mulai'])) ?> - <?= date('H.i', strtotime($jadwal_print_info['jam_selesai'])) ?></span></div>
                            <div class="flex"><span class="w-32 font-semibold text-gray-700">Pertemuan Ke-</span><span class="font-bold text-black">: <?= $jadwal_print_info['pertemuan_ke'] ?></span></div>
                        </div>
                    </div>

                    <!-- Tabel Print -->
                    <table class="print-table w-full border-collapse border border-gray-800">
                        <thead>
                            <tr>
                                <th class="border border-gray-800 p-2 text-center" width="5%">No</th>
                                <th class="border border-gray-800 p-2 text-center" width="15%">NIS</th>
                                <th class="border border-gray-800 p-2 text-center" width="35%">Nama Santri</th>
                                <th class="border border-gray-800 p-2 text-center" width="10%">L/P</th>
                                <th class="border border-gray-800 p-2 text-center" width="8%">Hadir</th>
                                <th class="border border-gray-800 p-2 text-center" width="8%">Izin</th>
                                <th class="border border-gray-800 p-2 text-center" width="8%">Sakit</th>
                                <th class="border border-gray-800 p-2 text-center" width="11%">Alpha</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            mysqli_data_seek($q_santri, 0);
                            $no = 1;
                            while($s = mysqli_fetch_assoc($q_santri)): 
                                // Gunakan data dari DB, jika belum ada (belum disimpan) default ke Alpha
                                $status_print = isset($absensi_data[$s['id']]) ? $absensi_data[$s['id']] : 'Alpha'; 
                            ?>
                            <tr class="border-b border-gray-800">
                                <td class="border border-gray-800 p-2 text-center text-black"><?= $no++ ?></td>
                                <td class="border border-gray-800 p-2 text-center text-black"><?= htmlspecialchars($s['nis']) ?></td>
                                <td class="border border-gray-800 p-2 text-left text-black nama"><?= htmlspecialchars($s['nama_santri']) ?></td>
                                <td class="border border-gray-800 p-2 text-center text-black"><?= $s['jenis_kelamin'] == 'Laki-laki' ? 'L' : 'P' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Hadir' ? '✓' : '' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Izin' ? '✓' : '' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Sakit' ? '✓' : '' ?></td>
                                <td class="border border-gray-800 p-2 text-center font-bold text-black"><?= $status_print == 'Alpha' ? '✓' : '' ?></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>

                    <!-- Tanda Tangan -->
                    <div class="flex justify-between mt-16 text-[13px]">
                        <div class="text-center w-48">
                            <p class="mb-16">Mengetahui,<br>Kepala TQA Darussalam</p>
                            <p class="font-bold underline text-black">( ............................................ )</p>
                        </div>
                        <div class="text-center w-48">
                            <p class="mb-16"><?= date('d M Y') ?><br>Guru Mata Pelajaran</p>
                            <p class="font-bold underline text-black"><?= htmlspecialchars($info['nama_guru']) ?></p>
                        </div>
                    </div>
                </div> <!-- End of print-only -->

            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- SCRIPT UNTUK FITUR TAMBAHAN -->
    <script>
        // FUNGSI PENCARIAN JADWAL (VIEW 1)
        function filterJadwal() {
            let searchText = document.getElementById('searchKelas').value.toLowerCase();
            let cards = document.getElementsByClassName('jadwal-card');
            
            for (let i = 0; i < cards.length; i++) {
                let cardText = cards[i].innerText.toLowerCase();
                if (cardText.includes(searchText)) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }

        // FUNGSI PENCARIAN SANTRI (VIEW 6 - ABSENSI MANUAL)
        function filterSantri() {
            let searchText = document.getElementById('searchSantri').value.toLowerCase();
            let rows = document.getElementsByClassName('santri-row');
            
            for (let i = 0; i < rows.length; i++) {
                let rowText = rows[i].innerText.toLowerCase();
                if (rowText.includes(searchText)) {
                    rows[i].style.display = "";
                } else {
                    rows[i].style.display = "none";
                }
            }
        }

        // FIX: Memperbaiki script Check All agar berfungsi dengan benar (centang & hapus centang)
        const checkAll = document.getElementById('checkAll');
        if(checkAll) {
            checkAll.addEventListener('change', function() {
                const hadirRadios = document.querySelectorAll('input[type="radio"][value="Hadir"]');
                hadirRadios.forEach(radio => {
                    radio.checked = this.checked; 
                });
            });
        }

        const startCameraBtn = document.getElementById('start-camera');
        if(startCameraBtn) {
            let html5QrCode = new Html5Qrcode("reader");
            let isScanning = false;

            const onScanSuccess = (decodedText, decodedResult) => {
                let santriId = null;
                if (decodedText.startsWith('TQA-')) {
                    santriId = decodedText.replace('TQA-', '');
                } else {
                    santriId = decodedText;
                }

                if (santriId) {
                    const item = document.getElementById('santri-' + santriId);
                    if (item && item.parentElement.id === 'list-tidak-hadir') {
                        const audio = new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg');
                        audio.play().catch(e => {});

                        const nama = item.getAttribute('data-nama');
                        item.remove();
                        
                        const select = document.createElement('input');
                        select.type = 'hidden';
                        select.name = 'status[' + santriId + ']';
                        select.value = 'Hadir';
                        document.getElementById('formAbsensiScan').appendChild(select);

                        document.getElementById('empty-hadir').style.display = 'none';
                        const divHadir = document.createElement('div');
                        divHadir.className = 'flex justify-between items-center p-2 border-b border-gray-100';
                        divHadir.innerHTML = `
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-[13px] text-black">${nama}</span>
                            </div>
                            <span class="px-3 py-1 bg-[#e6f7ec] text-[#0b6b53] rounded-full text-[11px] font-bold flex items-center gap-1"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg> Hadir</span>
                        `;
                        document.getElementById('list-hadir').appendChild(divHadir);
                    }
                }
            };

            startCameraBtn.addEventListener('click', async () => {
                if (isScanning) return;
                
                const readerDiv = document.getElementById('reader');
                // Hapus placeholder SVG dan teks agar kamera bisa render sempurna
                readerDiv.innerHTML = '';
                readerDiv.style.width = '100%'; 

                startCameraBtn.innerText = "Memuat Kamera...";
                startCameraBtn.disabled = true;
                startCameraBtn.classList.replace('bg-[#0b6b53]', 'bg-gray-400');

                try {
                    // Coba kamera belakang
                    await html5QrCode.start(
                        { facingMode: "environment" },
                        { fps: 10, qrbox: { width: 250, height: 250 } },
                        onScanSuccess,
                        (errorMessage) => {}
                    );
                    isScanning = true;
                    startCameraBtn.innerText = "Kamera Aktif...";
                } catch (err) {
                    // Fallback ke kamera depan (jika menggunakan laptop)
                    try {
                        await html5QrCode.start(
                            { facingMode: "user" },
                            { fps: 10, qrbox: { width: 250, height: 250 } },
                            onScanSuccess,
                            (errorMessage) => {}
                        );
                        isScanning = true;
                        startCameraBtn.innerText = "Kamera Aktif...";
                    } catch (err2) {
                        // TAMPILAN ERROR YANG RAPI (Tanpa Alert Browser)
                        readerDiv.innerHTML = `
                            <div class="flex flex-col items-center justify-center h-full p-4 text-center">
                                <svg class="w-12 h-12 text-red-500 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                                <p class="text-[13px] font-bold text-red-600 mb-1">Akses Kamera Ditolak</p>
                                <p class="text-[11px] font-semibold text-gray-600">Pastikan Anda mengizinkan akses kamera di browser dan menggunakan <b>HTTPS</b> atau <b>localhost</b>.</p>
                            </div>
                        `;
                        startCameraBtn.innerText = "Coba Lagi";
                        startCameraBtn.disabled = false;
                        startCameraBtn.classList.replace('bg-gray-400', 'bg-[#0b6b53]');
                    }
                }
            });
        }
    </script>
</body>
</html>