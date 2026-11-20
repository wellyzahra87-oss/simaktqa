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

// Menangkap parameter id_jadwal atau jadwal_id
$jadwal_id = 0;
if (isset($_GET['id_jadwal'])) {
    $jadwal_id = (int)$_GET['id_jadwal'];
} elseif (isset($_GET['jadwal_id'])) {
    $jadwal_id = (int)$_GET['jadwal_id'];
}

$success_msg = '';
$hari_ini = $hari[date('w')]; // Mendapatkan nama hari ini
$tanggal_hari_ini = date('Y-m-d');

// ==========================================
// FUNGSI HITUNG TANGGAL DINAMIS (SESUAI ADMIN)
// ==========================================
function hitung_tanggal($hari_target, $pertemuan_ke) {
    $hari_map = ['Senin'=>'Monday', 'Selasa'=>'Tuesday', 'Rabu'=>'Wednesday', 'Kamis'=>'Thursday', 'Jumat'=>'Friday', 'Sabtu'=>'Saturday', 'Minggu'=>'Sunday'];
    $hari_eng = $hari_map[$hari_target] ?? 'Monday';
    $base_date = strtotime("this week $hari_eng");
    return date('d M Y', strtotime("+" . ($pertemuan_ke - 1) . " weeks", $base_date));
}

// ==========================================
// AUTO-MIGRATE: Tambah kolom sub_materi (Jika belum ada)
// ==========================================
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal LIKE 'sub_materi'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE jadwal ADD COLUMN sub_materi TEXT NULL");
}

// ==========================================
// CEK KEBERADAAN KOLOM TANGGAL DI TABEL JADWAL
// ==========================================
$cek_tgl = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal LIKE 'tanggal'");
$has_tgl = mysqli_num_rows($cek_tgl) > 0;

// ==========================================
// PROSES UPDATE JADWAL (EDIT KEHADIRAN GURU)
// ==========================================
if (isset($_POST['update_pertemuan'])) {
    $id_update = (int)$_POST['id_jadwal'];
    $kehadiran_guru = mysqli_real_escape_string($koneksi, $_POST['kehadiran_guru']);
    $tanggal = $tanggal_hari_ini;

    // Cek apakah data absensi guru sudah ada di tanggal ini
    $cek_absen = mysqli_query($koneksi, "SELECT id FROM absensi_guru WHERE jadwal_id = $id_update AND tanggal = '$tanggal'");
    
    if (mysqli_num_rows($cek_absen) > 0) {
        // Jika ada, Update statusnya
        mysqli_query($koneksi, "UPDATE absensi_guru SET status = '$kehadiran_guru' WHERE jadwal_id = $id_update AND tanggal = '$tanggal'");
    } else {
        // Jika belum ada, Insert baru
        $q_j = mysqli_query($koneksi, "SELECT pertemuan_ke FROM jadwal WHERE id = $id_update");
        $d_j = mysqli_fetch_assoc($q_j);
        $pertemuan_ke = $d_j['pertemuan_ke'] ?? 1;
        
        mysqli_query($koneksi, "INSERT INTO absensi_guru (jadwal_id, pertemuan_ke, tanggal, jam_masuk, status) VALUES ($id_update, $pertemuan_ke, '$tanggal', '00:00:00', '$kehadiran_guru')");
    }
    
    header("Location: absensi_guru.php?action=detail&kelas_id=$kelas_id&mapel_id=$mapel_id&success=1");
    exit;
}

// ==========================================
// PROSES LAKUKAN ABSENSI (UBAH STATUS JADI HADIR)
// ==========================================
if (isset($_POST['lakukan_absensi'])) {
    $id_jadwal_update = (int)$_POST['jadwal_id'];
    $tanggal = $tanggal_hari_ini;
    $jam_masuk = date('H:i:s'); // Waktu saat tombol ditekan
    $status = 'Hadir';

    // Cek apakah data absensi guru sudah ada di tanggal ini
    $cek_absen = mysqli_query($koneksi, "SELECT id FROM absensi_guru WHERE jadwal_id = $id_jadwal_update AND tanggal = '$tanggal'");
    
    if (mysqli_num_rows($cek_absen) > 0) {
        // Update
        mysqli_query($koneksi, "UPDATE absensi_guru SET status = '$status', jam_masuk = '$jam_masuk' WHERE jadwal_id = $id_jadwal_update AND tanggal = '$tanggal'");
    } else {
        // Insert
        $q_j = mysqli_query($koneksi, "SELECT pertemuan_ke FROM jadwal WHERE id = $id_jadwal_update");
        $d_j = mysqli_fetch_assoc($q_j);
        $pertemuan_ke = $d_j['pertemuan_ke'] ?? 1;
        
        mysqli_query($koneksi, "INSERT INTO absensi_guru (jadwal_id, pertemuan_ke, tanggal, jam_masuk, status) VALUES ($id_jadwal_update, $pertemuan_ke, '$tanggal', '$jam_masuk', '$status')");
    }
    
    $success_msg = "Absensi berhasil dilakukan!";
    header("Location: absensi_guru.php?action=detail&kelas_id=$kelas_id&mapel_id=$mapel_id&success=1");
    exit;
}

// ==========================================
// DATA UNTUK VIEW 1 (LIST KELAS)
// ==========================================
$q_kelas = mysqli_query($koneksi, "
    SELECT 
        j.kelas_id, j.mapel_id, k.nama_kelas, m.nama_mapel, j.jam_mulai, j.jam_selesai, j.hari, j.status, r.nama_ruangan, r.keterangan,
        COALESCE(
            (SELECT tanggal FROM jadwal j2 WHERE j2.kelas_id = j.kelas_id AND j2.mapel_id = j.mapel_id AND j2.tanggal >= '$tanggal_hari_ini' ORDER BY j2.tanggal ASC LIMIT 1),
            (SELECT tanggal FROM jadwal j3 WHERE j3.kelas_id = j.kelas_id AND j3.mapel_id = j.mapel_id AND j3.tanggal IS NOT NULL AND j3.tanggal != '0000-00-00' ORDER BY j3.tanggal DESC LIMIT 1)
        ) as tanggal_mulai
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id
    LEFT JOIN ruangan r ON k.ruangan_id = r.id
    WHERE j.guru_id = $guru_id
    GROUP BY j.kelas_id, j.mapel_id
");

// ==========================================
// DATA UNTUK VIEW 2 (DETAIL PERTEMUAN)
// ==========================================
if ($action == 'detail') {
    $q_info = mysqli_query($koneksi, "SELECT j.*, k.nama_kelas, m.nama_mapel, r.nama_ruangan, r.keterangan 
        FROM jadwal j 
        JOIN kelas k ON j.kelas_id = k.id 
        JOIN mata_pelajaran m ON j.mapel_id = m.id 
        LEFT JOIN ruangan r ON k.ruangan_id = r.id
        WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.guru_id = $guru_id 
        ORDER BY CASE WHEN j.tanggal = '$tanggal_hari_ini' THEN 1 ELSE 2 END ASC, j.pertemuan_ke ASC LIMIT 1");
    $info = mysqli_fetch_assoc($q_info);

    $q_total = mysqli_query($koneksi, "SELECT COUNT(*) as total FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id AND guru_id = $guru_id");
    $total_pertemuan = mysqli_fetch_assoc($q_total)['total'];

    // PERBAIKAN: Mengambil data absensi guru dari tabel absensi_guru untuk tanggal hari ini
    $q_pertemuan = mysqli_query($koneksi, "SELECT j.*, a.status as status_guru 
        FROM jadwal j 
        LEFT JOIN absensi_guru a ON j.id = a.jadwal_id AND a.tanggal = '$tanggal_hari_ini' 
        WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.guru_id = $guru_id 
        ORDER BY j.pertemuan_ke ASC");
}

// ==========================================
// DATA UNTUK VIEW DETAIL (READ-ONLY)
// ==========================================
if ($action == 'detail_pertemuan') {
    $q_detail = mysqli_query($koneksi, "SELECT j.*, a.status as status_guru 
        FROM jadwal j 
        LEFT JOIN absensi_guru a ON j.id = a.jadwal_id AND a.tanggal = '$tanggal_hari_ini'
        WHERE j.id = $jadwal_id");
    $d_detail = mysqli_fetch_assoc($q_detail);

    $q_info_detail = mysqli_query($koneksi, "SELECT j.*, k.nama_kelas, m.nama_mapel
        FROM jadwal j 
        JOIN kelas k ON j.kelas_id = k.id 
        JOIN mata_pelajaran m ON j.mapel_id = m.id 
        WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.guru_id = $guru_id LIMIT 1");
    $info_detail = mysqli_fetch_assoc($q_info_detail);
}

// ==========================================
// DATA UNTUK VIEW EDIT PERTEMUAN
// ==========================================
if ($action == 'edit_pertemuan') {
    $q_edit = mysqli_query($koneksi, "SELECT j.*, a.status as status_guru 
        FROM jadwal j 
        LEFT JOIN absensi_guru a ON j.id = a.jadwal_id AND a.tanggal = '$tanggal_hari_ini'
        WHERE j.id = $jadwal_id");
    $d_edit = mysqli_fetch_assoc($q_edit);
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi Guru - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        html, body { height: 100%; overflow: hidden; }
        ::-webkit-scrollbar { height: 8px; width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc] font-sans">

    <?php 
    $current_page = 'absensi_guru'; 
    include 'sidebar.php'; 
    ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <?php include 'header.php'; ?>
        
        <!-- Padding Utama Disesuaikan dengan Dashboard Admin -->
        <main class="flex-1 flex flex-col px-6 py-4 overflow-y-auto">

            <!-- ======================================================= -->
            <!-- VIEW 1: DAFTAR KELAS YANG DIAMPU                        -->
            <!-- ======================================================= -->
            <?php if ($action == 'list'): ?>
            <div class="w-full">
                <!-- Judul -->
                <div class="flex items-center gap-3 mb-4 shrink-0">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0 transition-transform duration-300 hover:scale-110 shadow-sm">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Absensi Guru</h1>
                        <p class="text-[13px] font-semibold text-gray-700">Isi Absensi anda disini...</p>
                    </div>
                </div>

                <h2 class="text-[16px] font-bold text-gray-900 mb-3">Kelas yang diampu</h2>
                
                <!-- Search & Tombol Cari -->
                <div class="flex gap-2 mb-4 max-w-md shrink-0">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="searchKelas" onkeyup="filterKelas()" class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all duration-200" placeholder="Cari Kelas...">
                    </div>
                    <button type="button" onclick="filterKelas()" class="bg-[#0b6b53] text-white px-5 py-2 rounded-lg text-[13px] font-semibold hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 whitespace-nowrap">
                        Cari
                    </button>
                </div>

                <div class="space-y-3" id="listKelas">
                    <?php if(mysqli_num_rows($q_kelas) > 0): while($j = mysqli_fetch_assoc($q_kelas)): 
                        // STATUS MURNI DARI DATABASE JADWAL
                        $display_status = $j['status'];
                        $badge_class = 'bg-[#fde2e2] text-[#e53e3e]'; // Default: Belum Dimulai (Merah)

                        if ($display_status == 'Sedang Berlangsung') {
                            $badge_class = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                        } elseif ($display_status == 'Selesai') {
                            $badge_class = 'bg-gray-200 text-gray-600'; // Abu-abu
                        }
                        
                        // Tentukan Tanggal Tampil
                        $tampil_tgl = !empty($j['tanggal_mulai']) ? date('d M Y', strtotime($j['tanggal_mulai'])) : hitung_tanggal($j['hari'], 1);
                    ?>
                    <div class="kelas-card group bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(11,107,83,0.15)] border border-[#e6f4f1] p-4 flex items-center justify-between hover:shadow-[0_8px_25px_-5px_rgba(11,107,83,0.25)] hover:-translate-y-1 transition-all duration-300 cursor-default">
                        <div class="flex items-center gap-4">
                            <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-lg flex items-center justify-center shrink-0 transition-colors duration-300 group-hover:bg-[#d1ebe4]">
                                <svg class="w-5 h-5 text-[#0b6b53] transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                            </div>
                            <div>
                                <h3 class="text-[15px] font-extrabold text-gray-900 uppercase transition-colors duration-300 group-hover:text-[#0b6b53]"><?= htmlspecialchars($j['nama_kelas']) ?></h3>
                                <div class="flex flex-col gap-0.5 mt-1 text-[12px] font-semibold text-gray-700">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                                        <?= $j['hari'] ?>, <?= $tampil_tgl ?>
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
                                <p class="text-[14px] font-bold text-black"><?= date('H.i', strtotime($j['jam_mulai'])) ?> - <?= date('H.i', strtotime($j['jam_selesai'])) ?></p>
                                <span class="px-3 py-1 rounded-full text-[11px] font-bold flex items-center gap-1.5 <?= $badge_class ?> shadow-sm transition-transform duration-200 group-hover:scale-105">
                                    <span class="w-2 h-2 rounded-full bg-current"></span> <?= $display_status ?>
                                </span>
                            </div>
                            <a href="absensi_guru.php?action=detail&kelas_id=<?= $j['kelas_id'] ?>&mapel_id=<?= $j['mapel_id'] ?>" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-full transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md shrink-0">
                                <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                    </div>
                    <?php endwhile; else: ?>
                        <div class="p-5 text-center text-gray-500 bg-white rounded-xl border border-[#e6f4f1] font-semibold text-[13px] shadow-sm">Belum ada kelas yang diampu.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 2: DETAIL & DAFTAR PERTEMUAN (ABSENSI)             -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'detail'): 
                // STATUS HEADER MURNI DARI DATABASE JADWAL
                $display_status_info = $info['status'];
                $badge_info = 'bg-[#fde2e2] text-[#e53e3e]'; // Default: Belum Dimulai (Merah)

                if ($display_status_info == 'Sedang Berlangsung') {
                    $badge_info = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                } elseif ($display_status_info == 'Selesai') {
                    $badge_info = 'bg-gray-200 text-gray-600'; // Abu-abu
                }

                // Tombol bisa diklik jika status bukan Selesai
                $is_allowed_absensi = ($display_status_info == 'Sedang Berlangsung' || $display_status_info == 'Belum Dimulai');
            ?>
            <div class="w-full">
                <!-- Header Info -->
                <div class="bg-white rounded-xl shadow-[0_2px_10px_-3px_rgba(11,107,83,0.15)] border border-[#e6f4f1] p-4 flex items-center justify-between mb-3 relative overflow-hidden shrink-0 transition-shadow duration-300 hover:shadow-[0_8px_25px_-5px_rgba(11,107,83,0.25)]">
                    <div class="flex items-center gap-3 relative z-10">
                        <a href="absensi_guru.php" class="w-9 h-9 bg-[#e6f4f1] rounded-full flex items-center justify-center hover:bg-[#d1e7e2] transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md shrink-0">
                            <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                        </a>
                        <div>
                            <h2 class="text-[18px] font-bold text-gray-900 mb-1 leading-tight"><?= htmlspecialchars($info['nama_kelas']) ?></h2>
                            <div class="flex flex-col gap-0.5 text-[12px] font-semibold text-gray-700">
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg> <?= htmlspecialchars($info['nama_mapel']) ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg> <?= date('H.i', strtotime($info['jam_mulai'])) ?> - <?= date('H.i', strtotime($info['jam_selesai'])) ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg> <?= htmlspecialchars($_SESSION['nama']) ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg> Pertemuan <?= $info['pertemuan_ke'] ?>/<?= $total_pertemuan ?></div>
                                <div class="flex items-center gap-1.5"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg> <?= htmlspecialchars($info['nama_ruangan']) ?> (<?= htmlspecialchars($info['keterangan']) ?>)</div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col items-end gap-2 relative z-10">
                        <span class="px-2.5 py-1 <?= $badge_info ?> rounded-md text-[11px] font-bold flex items-center gap-1.5 shadow-sm">
                            <span class="w-2 h-2 rounded-full bg-current"></span> <?= $display_status_info ?>
                        </span>
                        
                        <!-- Form Lakukan Absensi -->
                        <?php if ($is_allowed_absensi): ?>
                            <form method="POST">
                                <input type="hidden" name="jadwal_id" value="<?= $info['id'] ?>">
                                <button type="submit" name="lakukan_absensi" class="border-[1.5px] border-[#0b6b53] text-[#0b6b53] bg-[#e6f4f1] px-4 py-1.5 rounded-lg font-bold text-[12px] hover:bg-[#d1e7e2] transition-all duration-200 active:scale-95 flex items-center gap-1.5 shadow-sm hover:shadow-md">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                    Lakukan Absensi
                                </button>
                            </form>
                        <?php else: ?>
                            <button type="button" disabled class="border-[1.5px] border-gray-300 text-gray-400 bg-gray-50 px-4 py-1.5 rounded-lg font-bold text-[12px] flex items-center gap-1.5 shadow-sm cursor-not-allowed">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                Lakukan Absensi
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Tabel Pertemuan -->
                <div class="bg-white rounded-xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] overflow-hidden transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#f0f7f4] text-[#0b6b53]">
                            <tr>
                                <th class="px-3 py-2.5 font-bold text-[13px] text-center">Pertemuan</th>
                                <th class="px-3 py-2.5 font-bold text-[13px] text-center">Tanggal</th>
                                <th class="px-3 py-2.5 font-bold text-[13px] text-center">Jam</th>
                                <th class="px-3 py-2.5 font-bold text-[13px] text-center">Status Absensi</th>
                                <th class="px-3 py-2.5 font-bold text-[13px] text-center w-24">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                            <?php 
                            $no = 1;
                            while($p = mysqli_fetch_assoc($q_pertemuan)): 
                                // PERBAIKAN: STATUS ABSENSI TABEL MENGGUNAKAN TABEL absensi_guru (status_guru)
                                if (!empty($p['status_guru']) && $p['status_guru'] == 'Hadir') {
                                    $display_status_row = 'Hadir';
                                    $badge_class_row = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                                } else {
                                    $display_status_row = 'Tidak Hadir';
                                    $badge_class_row = 'bg-[#fde2e2] text-[#e53e3e]'; // Merah
                                }

                                $tgl_row_valid = (!empty($p['tanggal']) && $p['tanggal'] != '0000-00-00');
                                $tgl_row = $tgl_row_valid ? date('Y-m-d', strtotime($p['tanggal'])) : date('Y-m-d', strtotime(hitung_tanggal($p['hari'], $p['pertemuan_ke'])));
                                $is_today = ($tgl_row == $tanggal_hari_ini);
                            ?>
                            <tr class="hover:bg-gray-50 transition-all duration-200 ease-in-out hover:shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)] h-[42px]">
                                <td class="px-3 py-2 text-center font-bold text-[#0b6b53]">Ke-<?= $p['pertemuan_ke'] ?></td>
                                <td class="px-3 py-2 text-center text-gray-600"><?= $tgl_row_valid ? date('d M Y', strtotime($p['tanggal'])) : hitung_tanggal($p['hari'], $p['pertemuan_ke']) ?></td>
                                <td class="px-3 py-2 text-center text-gray-600"><?= date('H.i', strtotime($p['jam_mulai'])) ?> - <?= date('H.i', strtotime($p['jam_selesai'])) ?></td>
                                
                                <!-- STATUS ABSENSI -->
                                <td class="px-3 py-2 text-center">
                                    <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= $badge_class_row ?> shadow-sm"><?= $display_status_row ?></span>
                                </td>
                                
                                <!-- AKSI -->
                                <td class="px-3 py-2 text-center flex justify-center gap-1.5 items-center">
                                    <?php if ($is_today || $display_status_row == 'Hadir'): ?>
                                        <!-- TOMBOL DETAIL (IKON MATA) -->
                                        <a href="absensi_guru.php?action=detail_pertemuan&id_jadwal=<?= $p['id'] ?>&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-7 h-7 rounded-full bg-black text-white flex items-center justify-center hover:bg-gray-800 transition-all duration-200 hover:scale-110 active:scale-95 shadow-sm hover:shadow-md" title="Lihat Detail">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 4.5C7 4.5 2.73 7.61 1 12c1.73 4.39 6 7.5 11 7.5s9.27-3.11 11-7.5c-1.73-4.39-6-7.5-11-7.5zM12 17c-2.76 0-5-2.24-5-5s2.24-5 5-5 5 2.24 5 5-2.24 5-5 5zm0-8c-1.66 0-3 1.34-3 3s1.34 3 3 3 3-1.34 3-3-1.34-3-3-3z"/></svg>
                                        </a>
                                    <?php endif; ?>

                                    <?php if ($is_today || $display_status_row == 'Hadir' || $p['status'] == 'Sedang Berlangsung'): ?>
                                        <!-- TOMBOL EDIT (IKON PENSIL) -->
                                        <a href="absensi_guru.php?action=edit_pertemuan&id_jadwal=<?= $p['id'] ?>&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-7 h-7 rounded bg-[#fef3c7] text-[#d97706] flex items-center justify-center hover:bg-yellow-200 transition-all duration-200 hover:scale-110 active:scale-95 shadow-sm hover:shadow-md" title="Edit">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </a>
                                    <?php else: ?>
                                        <!-- Jika Bukan Hari Ini dan Belum Dimulai, Tampilkan Strip -->
                                        <span class="text-gray-400 font-bold text-[14px]">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 3: DETAIL PERTEMUAN (READ-ONLY)                    -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'detail_pertemuan'): ?>
            <div class="max-w-2xl mx-auto w-full">
                <div class="flex items-center gap-3 mb-5 shrink-0">
                    <a href="absensi_guru.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-tight">Detail Kehadiran Guru</h1>
                        <p class="text-[13px] font-medium text-gray-600">Pertemuan Ke-<?= $d_detail['pertemuan_ke'] ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] space-y-4 transition-all duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <div class="flex items-center justify-between border-b pb-3">
                        <span class="text-[14px] font-semibold text-gray-600">Nama Guru</span>
                        <span class="text-[15px] font-bold text-gray-900"><?= htmlspecialchars($_SESSION['nama']) ?></span>
                    </div>
                    <div class="flex items-center justify-between border-b pb-3">
                        <span class="text-[14px] font-semibold text-gray-600">Status Kehadiran</span>
                        <?php
                            // PERBAIKAN: Menggunakan status_guru dari tabel absensi_guru
                            $status_db = (!empty($d_detail['status_guru'])) ? $d_detail['status_guru'] : 'Tidak Hadir';
                            $badge_detail = 'bg-[#fde2e2] text-[#e53e3e]'; // Merah
                            if ($status_db == 'Hadir') $badge_detail = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                        ?>
                        <span class="px-3 py-1 rounded-md text-[13px] font-bold <?= $badge_detail ?> shadow-sm"><?= $status_db ?></span>
                    </div>
                    <div class="flex items-center justify-between border-b pb-3">
                        <span class="text-[14px] font-semibold text-gray-600">Tanggal Hari Ini</span>
                        <span class="text-[15px] font-bold text-gray-900"><?= date('d F Y') ?></span>
                    </div>
                    <div class="flex items-center justify-between border-b pb-3">
                        <span class="text-[14px] font-semibold text-gray-600">Jadwal</span>
                        <span class="text-[15px] font-bold text-gray-900"><?= htmlspecialchars($info_detail['nama_kelas']) ?> - <?= htmlspecialchars($info_detail['nama_mapel']) ?></span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-[14px] font-semibold text-gray-600">Waktu</span>
                        <span class="text-[15px] font-bold text-gray-900"><?= date('H.i', strtotime($d_detail['jam_mulai'])) ?> - <?= date('H.i', strtotime($d_detail['jam_selesai'])) ?></span>
                    </div>
                </div>
                
                <div class="mt-5 flex justify-end">
                    <a href="absensi_guru.php?action=edit_pertemuan&id_jadwal=<?= $jadwal_id ?>&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="bg-[#0b6b53] text-white font-semibold text-[14px] px-6 py-2.5 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                        Edit Data
                    </a>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 4: FORM EDIT PERTEMUAN (EDIT KEHADIRAN GURU)       -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'edit_pertemuan'): ?>
            <div class="max-w-2xl mx-auto w-full">
                <div class="flex items-center gap-3 mb-5 shrink-0">
                    <a href="absensi_guru.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 active:scale-90 shadow-sm hover:shadow-md">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-tight">Edit Pertemuan</h1>
                        <p class="text-[13px] font-medium text-gray-600">Pertemuan Ke-<?= $d_edit['pertemuan_ke'] ?></p>
                    </div>
                </div>

                <div class="bg-white p-6 rounded-xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] transition-all duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                    <form method="POST" action="absensi_guru.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>">
                        <input type="hidden" name="id_jadwal" value="<?= $jadwal_id ?>">
                        
                        <!-- Info Tanggal & Jam (Readonly) -->
                        <div class="grid grid-cols-2 gap-4 mb-6">
                            <div>
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Tanggal</label>
                                <input type="text" value="<?= !empty($d_edit['tanggal']) ? date('d M Y', strtotime($d_edit['tanggal'])) : hitung_tanggal($d_edit['hari'], $d_edit['pertemuan_ke']) ?>" class="w-full px-3.5 py-2.5 border border-gray-200 bg-gray-50 rounded-lg text-[14px] text-gray-500 outline-none" readonly>
                            </div>
                            <div>
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Jam</label>
                                <input type="text" value="<?= date('H.i', strtotime($d_edit['jam_mulai'])) ?> - <?= date('H.i', strtotime($d_edit['jam_selesai'])) ?>" class="w-full px-3.5 py-2.5 border border-gray-200 bg-gray-50 rounded-lg text-[14px] text-gray-500 outline-none" readonly>
                            </div>
                        </div>

                        <!-- Edit Kehadiran Guru -->
                        <div class="mb-6">
                            <label class="block font-semibold text-[13px] text-gray-700 mb-2">Edit Kehadiran Guru</label>
                            <div class="flex gap-6">
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <!-- PERBAIKAN: Value menggunakan status_guru dari tabel absensi_guru -->
                                    <input type="radio" name="kehadiran_guru" value="Hadir" class="w-5 h-5 text-[#0b6b53] focus:ring-[#0b6b53] border-gray-300 transition-all duration-200 cursor-pointer" <?= (!empty($d_edit['status_guru']) && $d_edit['status_guru'] == 'Hadir') ? 'checked' : '' ?> required>
                                    <span class="text-[14px] font-semibold text-gray-800 group-hover:text-[#0b6b53] transition-colors duration-200">Hadir</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer group">
                                    <input type="radio" name="kehadiran_guru" value="Tidak Hadir" class="w-5 h-5 text-red-600 focus:ring-red-600 border-gray-300 transition-all duration-200 cursor-pointer" <?= (empty($d_edit['status_guru']) || $d_edit['status_guru'] != 'Hadir') ? 'checked' : '' ?> required>
                                    <span class="text-[14px] font-semibold text-gray-800 group-hover:text-red-600 transition-colors duration-200">Tidak Hadir</span>
                                </label>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-5 border-t border-gray-100">
                            <button type="submit" name="update_pertemuan" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[14px] py-2.5 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95">
                                Simpan Perubahan
                            </button>
                            <a href="absensi_guru.php?action=detail&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold text-[14px] rounded-lg hover:bg-gray-200 transition-all duration-200 shadow-sm hover:shadow-md active:scale-95">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- SCRIPT UNTUK FITUR PENCARIAN KELAS -->
    <script>
        function filterKelas() {
            // Ambil nilai input dan ubah ke huruf kecil
            let input = document.getElementById('searchKelas').value.toLowerCase();
            
            // Ambil semua elemen card kelas
            let cards = document.getElementsByClassName('kelas-card');

            // Loop melalui semua card
            for (let i = 0; i < cards.length; i++) {
                // Ambil teks di dalam card
                let text = cards[i].textContent || cards[i].innerText;
                
                // Cek apakah teks mengandung kata kunci pencarian
                if (text.toLowerCase().indexOf(input) > -1) {
                    cards[i].style.display = ""; // Tampilkan
                } else {
                    cards[i].style.display = "none"; // Sembunyikan
                }
            }
        }
    </script>
</body>
</html>