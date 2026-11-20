<?php 
include 'koneksi.php'; 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

// ==========================================
// ROUTING & PARAMETER
// ==========================================
$view = isset($_GET['view']) ? $_GET['view'] : 'main'; 
$filter_kelas = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;
$filter_mapel = isset($_GET['mapel']) ? (int)$_GET['mapel'] : 0;
$filter_hari = isset($_GET['hari']) ? mysqli_real_escape_string($koneksi, $_GET['hari']) : '';
$filter_tahun = isset($_GET['tahun']) ? mysqli_real_escape_string($koneksi, $_GET['tahun']) : date('Y');
$filter_semester = isset($_GET['semester']) ? mysqli_real_escape_string($koneksi, $_GET['semester']) : 'Ganjil';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$id_guru = isset($_GET['id_guru']) ? (int)$_GET['id_guru'] : 0;

// Parameter Tambahan (Tanggal & ID Santri untuk Detail)
$tgl_mulai = isset($_GET['tgl_mulai']) ? $_GET['tgl_mulai'] : '';
$tgl_selesai = isset($_GET['tgl_selesai']) ? $_GET['tgl_selesai'] : '';
$id_santri = isset($_GET['id_santri']) ? (int)$_GET['id_santri'] : 0;

// PARAMETER BARU UNTUK FILTER DETAIL
$det_kelas = isset($_GET['det_kelas']) ? (int)$_GET['det_kelas'] : 0;
$det_mapel = isset($_GET['det_mapel']) ? (int)$_GET['det_mapel'] : 0;
$det_hari = isset($_GET['det_hari']) ? mysqli_real_escape_string($koneksi, $_GET['det_hari']) : '';
$det_jam_mulai = isset($_GET['det_jam_mulai']) ? mysqli_real_escape_string($koneksi, $_GET['det_jam_mulai']) : '';
$det_jam_selesai = isset($_GET['det_jam_selesai']) ? mysqli_real_escape_string($koneksi, $_GET['det_jam_selesai']) : '';
$jadwal_id = isset($_GET['jadwal_id']) ? (int)$_GET['jadwal_id'] : 0;

// ==========================================
// PROSES UPDATE STATUS NILAI (ADMIN)
// ==========================================
if (isset($_POST['simpan_status_nilai'])) {
    if (isset($_POST['status']) && is_array($_POST['status'])) {
        foreach ($_POST['status'] as $santri_id_update => $status_new) {
            $santri_id_update = (int)$santri_id_update;
            $status_new = mysqli_real_escape_string($koneksi, $status_new);
            
            $where_mapel = $det_mapel > 0 ? " AND mapel_id = $det_mapel" : "";
            
            mysqli_query($koneksi, "UPDATE nilai SET status_lulus = '$status_new' WHERE santri_id = $santri_id_update $where_mapel");
        }
    }
    header("Location: laporan.php?view=nilai_kelas&det_kelas=$det_kelas&tahun=$filter_tahun&semester=$filter_semester&msg=" . urlencode("Status kelulusan berhasil diperbarui."));
    exit;
}

// Ambil data untuk dropdown filter
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$q_mapel = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran ORDER BY nama_mapel ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Laporan - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* CSS Khusus untuk Print */
        @media print {
            body { background-color: white !important; overflow: visible !important; height: auto !important; }
            .no-print { display: none !important; }
            .print-only { display: block !important; }
            main { padding: 0 !important; margin: 0 !important; overflow: visible !important; height: auto !important; }
            .ml-64 { margin-left: 0 !important; }
            .shadow-sm, .shadow-md, .shadow-lg { box-shadow: none !important; border: 1px solid #ddd !important; }
            .print-area { width: 100%; position: absolute; left: 0; top: 0; }
            table { width: 100% !important; border-collapse: collapse !important; }
            th, td { border: 1px solid #ccc !important; padding: 6px !important; font-size: 11px !important; }
            .bg-\[\#f0f7f4\] { background-color: #f8fafc !important; }
            .overflow-auto, .overflow-y-auto { overflow: visible !important; max-height: none !important; }
        }
        .print-only { display: none; }
        html, body { height: 100%; overflow: hidden; }
        ::-webkit-scrollbar { height: 8px; width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc] font-sans">

    <!-- ========================================== -->
    <!-- TOAST NOTIFICATION (2 DETIK)               -->
    <!-- ========================================== -->
    <div id="toast-notif" class="no-print fixed top-24 right-8 z-[999] transform translate-x-[150%] transition-transform duration-300 ease-in-out">
        <div class="bg-[#0b6b53] text-white px-6 py-3 rounded-xl shadow-lg flex items-center gap-3 border border-[#08533f]">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-semibold text-sm">Laporan berhasil dimuat!</span>
        </div>
    </div>

    <div class="no-print">
        <?php 
        $current_page = 'laporan'; 
        include 'sidebar.php'; 
        ?>
    </div>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <div class="no-print">
            <?php include 'header.php'; ?>
        </div>
        
        <!-- Main Area -->
        <main class="flex-1 flex flex-col px-6 py-4 overflow-hidden">
            
            <!-- ======================================================= -->
            <!-- VIEW 1: MENU UTAMA LAPORAN                              -->
            <!-- ======================================================= -->
            <?php if ($view == 'main'): ?>
            <div class="flex-1 overflow-y-auto w-full flex justify-center">
                <div class="max-w-4xl w-full pb-8">
                    <div class="flex items-center gap-3 mb-4 shrink-0">
                        <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zM9 17H7v-7h2v7zm4 0h-2V7h2v10zm4 0h-2v-4h2v4z"/></svg>
                        </div>
                        <div>
                            <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Kelola Laporan</h1>
                            <p class="text-[13px] font-medium text-gray-600">Kelola Semua laporan TQA Darussalam</p>
                        </div>
                    </div>

                    <div class="space-y-3">
                        <a href="laporan.php?view=absensi_santri" class="bg-white rounded-xl shadow-md hover:shadow-lg hover:-translate-y-1 transition-all duration-300 border border-[#e6f4f1] p-4 flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-lg flex items-center justify-center group-hover:bg-[#d1e7e2] transition shrink-0">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-extrabold text-gray-900">ABSENSI SANTRI</h3>
                                    <p class="text-[12px] font-semibold text-gray-600 mt-0.5">Kelola dan Lihat Data Kehadiran Santri</p>
                                </div>
                            </div>
                            <div class="w-8 h-8 bg-[#0b6b53] rounded-full flex items-center justify-center text-white shrink-0 group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </a>

                        <a href="laporan.php?view=absensi_guru" class="bg-white rounded-xl shadow-md hover:shadow-lg hover:-translate-y-1 transition-all duration-300 border border-[#e6f4f1] p-4 flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-lg flex items-center justify-center group-hover:bg-[#d1e7e2] transition shrink-0">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M21 3H3c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h18c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 16H4V7h16v12zM6 9h2v2H6zm0 4h2v2H6zm4-4h8v2h-8zm0 4h8v2h-8z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-extrabold text-gray-900">ABSENSI GURU</h3>
                                    <p class="text-[12px] font-semibold text-gray-600 mt-0.5">Kelola dan Lihat Data Kehadiran Guru</p>
                                </div>
                            </div>
                            <div class="w-8 h-8 bg-[#0b6b53] rounded-full flex items-center justify-center text-white shrink-0 group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </a>

                        <a href="laporan.php?view=nilai" class="bg-white rounded-xl shadow-md hover:shadow-lg hover:-translate-y-1 transition-all duration-300 border border-[#e6f4f1] p-4 flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-lg flex items-center justify-center group-hover:bg-[#d1e7e2] transition shrink-0">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-extrabold text-gray-900">NILAI</h3>
                                    <p class="text-[12px] font-semibold text-gray-600 mt-0.5">Kelola dan Lihat Data Nilai</p>
                                </div>
                            </div>
                            <div class="w-8 h-8 bg-[#0b6b53] rounded-full flex items-center justify-center text-white shrink-0 group-hover:scale-110 transition-transform">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 2: ABSENSI SANTRI (LIST KELAS/MAPEL/GURU)          -->
            <!-- ======================================================= -->
            <?php elseif ($view == 'absensi_santri'): ?>
            <div class="max-w-6xl mx-auto w-full print-area flex-1 flex flex-col min-h-0">
                
                <!-- Header Web -->
                <div class="flex items-center gap-3 mb-3 no-print shrink-0">
                    <a href="laporan.php" class="w-8 h-8 bg-[#e6f4f1] rounded-lg flex items-center justify-center hover:bg-[#d1e7e2] transition shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Absensi Santri</h1>
                        <p class="text-[12px] font-medium text-gray-600">Pilih Kelas untuk melihat detail absensi</p>
                    </div>
                </div>

                <!-- Filter Box & Search -->
                <form method="GET" action="laporan.php" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-[#e6f4f1] p-3 mb-3 no-print flex flex-wrap gap-3 items-end shrink-0">
                    <input type="hidden" name="view" value="absensi_santri">
                    
                    <div class="flex-1 min-w-[120px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas</label>
                        <select name="kelas" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white transition-all duration-200">
                            <option value="0">Semua Kelas</option>
                            <?php mysqli_data_seek($q_kelas, 0); while($k = mysqli_fetch_assoc($q_kelas)): ?>
                                <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[120px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Mata Pelajaran</label>
                        <select name="mapel" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white transition-all duration-200">
                            <option value="0">Semua Mapel</option>
                            <?php mysqli_data_seek($q_mapel, 0); while($m = mysqli_fetch_assoc($q_mapel)): ?>
                                <option value="<?= $m['id'] ?>" <?= $filter_mapel == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mapel']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex-[1.5] min-w-[150px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Pencarian</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] transition-all duration-200" placeholder="Cari Kelas / Mapel / Guru...">
                        </div>
                    </div>
                    <button type="submit" class="bg-[#0b6b53] text-white px-4 py-1.5 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 flex items-center gap-1.5 h-[34px] whitespace-nowrap">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                        Tampilkan
                    </button>
                </form>

                <!-- List Card (Kelas, Mapel, Guru) -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid grid-cols-1 gap-3 pb-4">
                        <?php 
                        $where_kelas_mapel = "WHERE 1=1";
                        if ($filter_kelas > 0) $where_kelas_mapel .= " AND k.id = $filter_kelas";
                        if ($filter_mapel > 0) $where_kelas_mapel .= " AND m.id = $filter_mapel";
                        if (!empty($search)) $where_kelas_mapel .= " AND (k.nama_kelas LIKE '%$search%' OR m.nama_mapel LIKE '%$search%' OR u.nama LIKE '%$search%')";

                        $q_kelas_mapel_santri = mysqli_query($koneksi, "
                            SELECT k.id as id_kelas, k.nama_kelas, m.id as id_mapel, m.nama_mapel, u.nama as nama_guru, MIN(j.hari) as hari, MIN(j.jam_mulai) as jam_mulai, MIN(j.jam_selesai) as jam_selesai
                            FROM jadwal j
                            JOIN kelas k ON j.kelas_id = k.id
                            JOIN mata_pelajaran m ON j.mapel_id = m.id
                            JOIN users u ON j.guru_id = u.id
                            $where_kelas_mapel
                            GROUP BY k.id, m.id, u.id
                            ORDER BY k.nama_kelas ASC, m.nama_mapel ASC
                        ");
                        
                        if(mysqli_num_rows($q_kelas_mapel_santri) > 0):
                            while($km = mysqli_fetch_assoc($q_kelas_mapel_santri)): 
                        ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-[#e6f4f1] p-4 flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                </div>
                                <div>
                                    <!-- PERBAIKAN: Menampilkan Nama Kelas dan Nama Mapel -->
                                    <h3 class="text-[15px] font-extrabold text-gray-900 uppercase group-hover:text-[#0b6b53] transition-colors">
                                        <?= htmlspecialchars($km['nama_kelas']) ?> - <?= htmlspecialchars($km['nama_mapel']) ?>
                                    </h3>
                                    <div class="flex items-center gap-1.5 mt-1 text-[12px] font-semibold text-gray-700">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        Guru Pengajar : <?= htmlspecialchars($km['nama_guru']) ?>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1 text-[12px] font-semibold text-gray-700">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                        <?= $km['hari'] ?> | <?= date('H.i', strtotime($km['jam_mulai'])) ?> - <?= date('H.i', strtotime($km['jam_selesai'])) ?>
                                    </div>
                                </div>
                            </div>
                            <a href="laporan.php?view=absensi_santri_kelas&det_kelas=<?= $km['id_kelas'] ?>&det_mapel=<?= $km['id_mapel'] ?>" class="w-8 h-8 bg-[#e6f4f1] text-[#0b6b53] rounded-full flex items-center justify-center hover:bg-[#0b6b53] hover:text-white transition-all duration-200 active:scale-95 shrink-0 shadow-sm group-hover:scale-110">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                        <?php endwhile; else: ?>
                            <div class="p-5 text-center text-gray-500 bg-white rounded-xl border border-[#e6f4f1] font-semibold text-[13px] shadow-sm">
                                <?= !empty($search) ? 'Data pencarian tidak ditemukan.' : 'Belum ada data jadwal.' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 2.1: DETAIL ABSENSI SANTRI (REKAP HADIR/IZIN)      -->
            <!-- ======================================================= -->
            <?php elseif ($view == 'absensi_santri_kelas'): 
                $kelas_name = $det_kelas > 0 ? mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id=$det_kelas"))['nama_kelas'] : 'Semua Kelas';
                $mapel_name = $det_mapel > 0 ? mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_mapel FROM mata_pelajaran WHERE id=$det_mapel"))['nama_mapel'] : 'Semua Mapel';
                $guru_name = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT u.nama FROM jadwal j JOIN users u ON j.guru_id = u.id WHERE j.kelas_id = $det_kelas AND j.mapel_id = $det_mapel LIMIT 1"))['nama'] ?? '-';
            ?>
            <div class="max-w-6xl mx-auto w-full print-area flex-1 flex flex-col min-h-0">
                
                <!-- HEADER CETAK -->
                <div class="print-only text-center mb-4">
                    <h1 class="text-lg font-bold uppercase">Laporan Absensi Santri</h1>
                    <p class="text-xs mt-1">
                        Kelas: <?= htmlspecialchars($kelas_name) ?> | 
                        Mata Pelajaran: <?= htmlspecialchars($mapel_name) ?> | 
                        Semester: <?= $filter_semester ?> <?= $filter_tahun ?>
                    </p>
                    <hr class="border-gray-400 mt-2 mb-4">
                </div>

                <!-- Header Web -->
                <div class="flex items-center gap-3 mb-3 no-print shrink-0">
                    <a href="laporan.php?view=absensi_santri&kelas=<?= $filter_kelas ?>&mapel=<?= $filter_mapel ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 bg-[#e6f4f1] rounded-lg flex items-center justify-center hover:bg-[#d1e7e2] transition shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight"><?= htmlspecialchars($kelas_name) ?> - <?= htmlspecialchars($mapel_name) ?></h1>
                        <p class="text-[12px] font-medium text-gray-600">Rekap Kehadiran Santri</p>
                    </div>
                </div>

                <!-- Info Card Detail -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-4 flex justify-between items-start mb-3 relative overflow-hidden shrink-0 no-print">
                    <div class="flex gap-3 z-10">
                        <div class="w-12 h-12 bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-[16px] font-extrabold text-gray-900 mb-0.5"><?= htmlspecialchars($kelas_name) ?> - <?= htmlspecialchars($mapel_name) ?></h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-[12px] font-semibold text-gray-700">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    Guru: <?= htmlspecialchars($guru_name) ?>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                    Periode: <?= $filter_semester ?> <?= $filter_tahun ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button onclick="window.print()" class="border-2 border-[#0b6b53] text-[#0b6b53] px-3 py-1.5 rounded-lg font-bold text-[12px] hover:bg-[#e6f4f1] transition shadow-sm hover:shadow-md active:scale-95 flex items-center gap-1.5 z-10">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                        Cetak
                    </button>
                </div>

                <!-- Tabel Data -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] flex-1 min-h-0 flex flex-col">
                    <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-[#f8fafc] no-print shrink-0 rounded-t-xl">
                        <h3 class="font-bold text-gray-900 text-[14px]">Rekap Kehadiran Santri</h3>
                    </div>
                    <div class="flex-1 overflow-auto">
                        <?php 
                        $where_kelas = "WHERE s.status = 'Aktif' AND s.kelas_id = $det_kelas";
                        if (!empty($search)) $where_kelas .= " AND s.nama_santri LIKE '%$search%'";

                        $join_absensi = "LEFT JOIN absensi a ON s.id = a.santri_id AND a.jadwal_id IN (SELECT id FROM jadwal WHERE mapel_id = $det_mapel)";
                        if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
                            $join_absensi .= " AND a.tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
                        } else {
                            if ($filter_semester == 'Ganjil') {
                                $join_absensi .= " AND MONTH(a.tanggal) IN (7,8,9,10,11,12)";
                            } else {
                                $join_absensi .= " AND MONTH(a.tanggal) IN (1,2,3,4,5,6)";
                            }
                            if (!empty($filter_tahun)) {
                                $join_absensi .= " AND YEAR(a.tanggal) = '$filter_tahun'";
                            }
                        }

                        $query_absensi_kelas = mysqli_query($koneksi, "
                            SELECT s.id as id_santri, s.nama_santri, k.nama_kelas,
                            SUM(CASE WHEN a.status = 'Hadir' THEN 1 ELSE 0 END) as hadir,
                            SUM(CASE WHEN a.status = 'Izin' THEN 1 ELSE 0 END) as izin,
                            SUM(CASE WHEN a.status = 'Sakit' THEN 1 ELSE 0 END) as sakit,
                            SUM(CASE WHEN a.status = 'Alpha' THEN 1 ELSE 0 END) as alpha
                            FROM santri s
                            LEFT JOIN kelas k ON s.kelas_id = k.id
                            $join_absensi
                            $where_kelas
                            GROUP BY s.id
                            ORDER BY s.nama_santri ASC
                        ");
                        ?>
                        
                        <?php if(mysqli_num_rows($query_absensi_kelas) > 0): ?>
                            <table class="w-full text-left border-collapse min-w-max">
                                <thead class="bg-[#f0f7f4] text-[#0b6b53] sticky top-0 z-10 shadow-sm">
                                    <tr>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center w-12">No</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px]">Nama Santri</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Hadir</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Izin</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Sakit</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Alpha</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center w-24 no-print">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                    <?php 
                                    $no = 1;
                                    while($row = mysqli_fetch_assoc($query_absensi_kelas)): 
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200 h-[42px]">
                                        <td class="px-3 py-2 text-center"><?= $no++ ?></td>
                                        <td class="px-3 py-2 font-semibold"><?= htmlspecialchars($row['nama_santri']) ?></td>
                                        <td class="px-3 py-2 text-center"><?= $row['hadir'] ?></td>
                                        <td class="px-3 py-2 text-center"><?= $row['izin'] ?></td>
                                        <td class="px-3 py-2 text-center"><?= $row['sakit'] ?></td>
                                        <td class="px-3 py-2 text-center"><?= $row['alpha'] ?></td>
                                        <td class="px-3 py-2 text-center no-print">
                                            <a href="laporan.php?view=absensi_santri_detail&id_santri=<?= $row['id_santri'] ?>&kelas=<?= $det_kelas ?>&mapel=<?= $det_mapel ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&tahun=<?= $filter_tahun ?>&semester=<?= $filter_semester ?>" class="bg-[#0b6b53] text-white px-2.5 py-1 rounded font-bold text-[11px] hover:bg-[#08533f] transition shadow-sm hover:shadow-md active:scale-95 inline-block">Detail</a>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center h-full p-6 text-center text-gray-500 font-normal text-sm">
                                <?= (!empty($search)) ? 'Data yang dicari tidak ditemukan.' : 'Data absensi belum tersedia untuk kelas dan mapel ini.' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 2.2: DETAIL ABSENSI SANTRI (RIWAYAT PER SISWA)     -->
            <!-- ======================================================= -->
            <?php elseif ($view == 'absensi_santri_detail'): 
                $santri_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT s.*, k.nama_kelas FROM santri s LEFT JOIN kelas k ON s.kelas_id = k.id WHERE s.id = $id_santri"));
            ?>
            <div class="max-w-6xl mx-auto w-full print-area flex-1 flex flex-col min-h-0">
                
                <!-- HEADER CETAK -->
                <div class="print-only text-center mb-4">
                    <h1 class="text-lg font-bold uppercase">Detail Absensi Santri</h1>
                    <p class="text-xs mt-1">
                        Nama: <?= htmlspecialchars($santri_info['nama_santri']) ?> | 
                        Kelas: <?= htmlspecialchars($santri_info['nama_kelas']) ?> | 
                        Semester: <?= $filter_semester ?> <?= $filter_tahun ?>
                    </p>
                    <hr class="border-gray-400 mt-2 mb-4">
                </div>

                <!-- Header Web -->
                <div class="flex items-center gap-3 mb-3 no-print shrink-0">
                    <a href="laporan.php?view=absensi_santri_kelas&det_kelas=<?= $filter_kelas ?>&tgl_mulai=<?= $tgl_mulai ?>&tgl_selesai=<?= $tgl_selesai ?>&search=<?= urlencode($search) ?>&tahun=<?= $filter_tahun ?>&semester=<?= $filter_semester ?>" class="w-8 h-8 bg-[#e6f4f1] rounded-lg flex items-center justify-center hover:bg-[#d1e7e2] transition shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Detail Absensi Santri</h1>
                        <p class="text-[12px] font-medium text-gray-600">Riwayat kehadiran santri secara detail</p>
                    </div>
                </div>

                <!-- Profil Santri -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-4 flex justify-between items-start mb-3 relative overflow-hidden shrink-0 no-print">
                    <div class="flex gap-3 z-10">
                        <div class="w-12 h-12 bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-[16px] font-extrabold text-gray-900 mb-0.5"><?= htmlspecialchars($santri_info['nama_santri']) ?></h2>
                            <div class="grid grid-cols-1 gap-x-6 gap-y-0.5 text-[12px] font-semibold text-gray-700">
                                <!-- NIS Dihapus -->
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.328.996.002 1.069c0 .392.23.755.588.93l4 1.953a1 1 0 00.868 0l4-1.953a1.05 1.05 0 00.588-.93V8.25l-2.25.964v4.867a1 1 0 01-.53.882l-4 2.222a1 1 0 01-.94 0l-4-2.222a1 1 0 01-.53-.882V6.79l-2.25-.964a1 1 0 010-1.84l7-3z"/></svg>
                                    Kelas: <?= htmlspecialchars($santri_info['nama_kelas'] ?? '-') ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button onclick="window.print()" class="border-2 border-[#0b6b53] text-[#0b6b53] px-3 py-1.5 rounded-lg font-bold text-[12px] hover:bg-[#e6f4f1] transition shadow-sm hover:shadow-md active:scale-95 flex items-center gap-1.5 z-10">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                        Cetak
                    </button>
                </div>

                <!-- Tabel Riwayat -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] flex-1 min-h-0 flex flex-col">
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-left border-collapse min-w-max">
                            <thead class="bg-[#f0f7f4] text-[#0b6b53] sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-3 py-2.5 font-bold text-[13px] text-center w-12">No</th>
                                    <th class="px-3 py-2.5 font-bold text-[13px] text-center">Tanggal</th>
                                    <th class="px-3 py-2.5 font-bold text-[13px] text-center">Mata Pelajaran</th>
                                    <th class="px-3 py-2.5 font-bold text-[13px] text-center">Pertemuan</th>
                                    <th class="px-3 py-2.5 font-bold text-[13px] text-center">Jam</th>
                                    <th class="px-3 py-2.5 font-bold text-[13px] text-center">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                <?php 
                                $where_detail = "WHERE a.santri_id = $id_santri";
                                if (!empty($tgl_mulai) && !empty($tgl_selesai)) {
                                    $where_detail .= " AND a.tanggal BETWEEN '$tgl_mulai' AND '$tgl_selesai'";
                                } else {
                                    // Filter Semester (Ganjil: 7-12, Genap: 1-6)
                                    if ($filter_semester == 'Ganjil') {
                                        $where_detail .= " AND MONTH(a.tanggal) IN (7,8,9,10,11,12)";
                                    } else {
                                        $where_detail .= " AND MONTH(a.tanggal) IN (1,2,3,4,5,6)";
                                    }
                                    if (!empty($filter_tahun)) {
                                        $where_detail .= " AND YEAR(a.tanggal) = '$filter_tahun'";
                                    }
                                }

                                $q_detail = mysqli_query($koneksi, "
                                    SELECT a.tanggal, a.status, m.nama_mapel, j.jam_mulai, j.jam_selesai, j.pertemuan_ke
                                    FROM absensi a
                                    JOIN jadwal j ON a.jadwal_id = j.id
                                    JOIN mata_pelajaran m ON j.mapel_id = m.id
                                    $where_detail
                                    ORDER BY a.tanggal ASC
                                ");
                                $no = 1;
                                if(mysqli_num_rows($q_detail) > 0) {
                                    while($row = mysqli_fetch_assoc($q_detail)): 
                                ?>
                                <tr class="hover:bg-gray-50 transition-colors duration-200 h-[42px]">
                                    <td class="px-3 py-2 text-center"><?= $no++ ?></td>
                                    <td class="px-3 py-2 text-center"><?= date('d M Y', strtotime($row['tanggal'])) ?></td>
                                    <td class="px-3 py-2 text-center"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                    <td class="px-3 py-2 text-center">Ke-<?= $row['pertemuan_ke'] ?></td>
                                    <td class="px-3 py-2 text-center"><?= date('H.i', strtotime($row['jam_mulai'])) ?> - <?= date('H.i', strtotime($row['jam_selesai'])) ?></td>
                                    <td class="px-3 py-2 text-center">
                                        <?php 
                                        $badge = 'bg-gray-100 text-gray-700';
                                        if($row['status'] == 'Hadir') $badge = 'bg-green-100 text-green-700';
                                        if($row['status'] == 'Izin') $badge = 'bg-yellow-100 text-yellow-700';
                                        if($row['status'] == 'Sakit') $badge = 'bg-blue-100 text-blue-700';
                                        if($row['status'] == 'Alpha') $badge = 'bg-red-100 text-red-700';
                                        ?>
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= $badge ?>">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; } else { ?>
                                    <tr><td colspan="6" class="p-6 text-center text-gray-500 font-normal text-sm">Belum ada data absensi untuk periode ini.</td></tr>
                                <?php } ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 3: ABSENSI GURU (LIST)                             -->
            <!-- ======================================================= -->
            <?php elseif ($view == 'absensi_guru'): ?>
            <div class="max-w-6xl mx-auto w-full print-area flex-1 flex flex-col min-h-0">
                <div class="flex items-center gap-3 mb-3 no-print shrink-0">
                    <a href="laporan.php" class="w-8 h-8 bg-[#e6f4f1] rounded-lg flex items-center justify-center hover:bg-[#d1e7e2] transition shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Absensi Guru</h1>
                        <p class="text-[12px] font-medium text-gray-600">Kelola dan Lihat Data Kehadiran Guru</p>
                    </div>
                </div>

                <!-- Filter Box -->
                <form method="GET" action="laporan.php" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-[#e6f4f1] p-3 mb-3 no-print flex flex-wrap gap-3 items-end shrink-0">
                    <input type="hidden" name="view" value="absensi_guru">
                    
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas</label>
                        <select name="kelas" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white transition-all duration-200">
                            <option value="0">Semua Kelas</option>
                            <?php mysqli_data_seek($q_kelas, 0); while($k = mysqli_fetch_assoc($q_kelas)): ?>
                                <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Mata Pelajaran</label>
                        <select name="mapel" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white transition-all duration-200">
                            <option value="0">Semua Mapel</option>
                            <?php mysqli_data_seek($q_mapel, 0); while($m = mysqli_fetch_assoc($q_mapel)): ?>
                                <option value="<?= $m['id'] ?>" <?= $filter_mapel == $m['id'] ? 'selected' : '' ?>><?= htmlspecialchars($m['nama_mapel']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[100px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                        <select name="semester" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white transition-all duration-200">
                            <option value="Ganjil" <?= $filter_semester == 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                            <option value="Genap" <?= $filter_semester == 'Genap' ? 'selected' : '' ?>>Genap</option>
                        </select>
                    </div>
                    <div class="flex-[2] min-w-[180px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Pencarian</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] transition-all duration-200" placeholder="Cari Nama Guru...">
                        </div>
                    </div>
                    <button type="submit" class="bg-[#0b6b53] text-white px-4 py-1.5 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 flex items-center gap-1.5 h-[34px] whitespace-nowrap">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                        Tampilkan
                    </button>
                </form>

                <!-- List Jadwal Guru -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid grid-cols-1 gap-3 pb-4">
                        <?php 
                        $where_guru = "WHERE u.role = 'Guru'";
                        if ($filter_kelas > 0) $where_guru .= " AND j.kelas_id = $filter_kelas";
                        if ($filter_mapel > 0) $where_guru .= " AND j.mapel_id = $filter_mapel";
                        if (!empty($filter_hari)) $where_guru .= " AND j.hari = '$filter_hari'";
                        if (!empty($search)) $where_guru .= " AND u.nama LIKE '%$search%'";
                        
                        $q_absensi_guru = mysqli_query($koneksi, "
                            SELECT u.id as id_guru, u.nama, k.id as kelas_id, k.nama_kelas, m.id as mapel_id, m.nama_mapel, j.hari, j.jam_mulai, j.jam_selesai
                            FROM users u
                            JOIN jadwal j ON u.id = j.guru_id
                            JOIN kelas k ON j.kelas_id = k.id
                            JOIN mata_pelajaran m ON j.mapel_id = m.id
                            $where_guru
                            GROUP BY u.id, k.id, m.id, j.hari, j.jam_mulai, j.jam_selesai
                            ORDER BY u.nama ASC, j.hari ASC, j.jam_mulai ASC
                        ");
                        
                        if(mysqli_num_rows($q_absensi_guru) > 0):
                            while($g = mysqli_fetch_assoc($q_absensi_guru)): 
                        ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-[#e6f4f1] p-4 flex items-center justify-between">
                            <div class="flex items-center gap-4">
                                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-extrabold text-gray-900"><?= htmlspecialchars($g['nama']) ?></h3>
                                    <div class="grid grid-cols-2 gap-x-6 gap-y-0.5 mt-1 text-[12px] font-semibold text-gray-700">
                                        <!-- PERBAIKAN: Menambahkan Hari ke dalam Card -->
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                                            <?= $g['hari'] ?>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.328.996.002 1.069c0 .392.23.755.588.93l4 1.953a1 1 0 00.868 0l4-1.953a1.05 1.05 0 00.588-.93V8.25l-2.25.964v4.867a1 1 0 01-.53.882l-4 2.222a1 1 0 01-.94 0l-4-2.222a1 1 0 01-.53-.882V6.79l-2.25-.964a1 1 0 010-1.84l7-3z"/></svg>
                                            <?= htmlspecialchars($g['nama_kelas']) ?>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                                            <?= htmlspecialchars($g['nama_mapel']) ?>
                                        </div>
                                        <div class="flex items-center gap-1.5">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                            <?= date('H.i', strtotime($g['jam_mulai'])) ?> - <?= date('H.i', strtotime($g['jam_selesai'])) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <a href="laporan.php?view=absensi_guru_detail&id_guru=<?= $g['id_guru'] ?>&det_kelas=<?= $g['kelas_id'] ?>&det_mapel=<?= $g['mapel_id'] ?>&det_hari=<?= $g['hari'] ?>&det_jam_mulai=<?= $g['jam_mulai'] ?>&det_jam_selesai=<?= $g['jam_selesai'] ?>" class="w-7 h-7 flex items-center justify-center hover:bg-gray-100 rounded-full transition shrink-0 active:scale-95">
                                <svg class="w-5 h-5 text-gray-800" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                        <?php endwhile; else: ?>
                            <div class="p-5 text-center text-gray-500 bg-white rounded-xl border border-[#e6f4f1] font-semibold text-[13px]">
                                <?= !empty($search) ? 'Data pencarian tidak ditemukan.' : 'Tidak ada data jadwal guru yang ditemukan.' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 4: DETAIL ABSENSI GURU                             -->
            <!-- ======================================================= -->
            <?php elseif ($view == 'absensi_guru_detail'): 
                $guru_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = $id_guru"));
                
                $where_detail_guru = "WHERE j.guru_id = $id_guru";
                if ($det_kelas > 0) $where_detail_guru .= " AND j.kelas_id = $det_kelas";
                if ($det_mapel > 0) $where_detail_guru .= " AND j.mapel_id = $det_mapel";
                if (!empty($det_hari)) $where_detail_guru .= " AND j.hari = '$det_hari'";
                if (!empty($det_jam_mulai)) $where_detail_guru .= " AND j.jam_mulai = '$det_jam_mulai'";
                if (!empty($det_jam_selesai)) $where_detail_guru .= " AND j.jam_selesai = '$det_jam_selesai'";

                $q_kombinasi = mysqli_query($koneksi, "
                    SELECT j.kelas_id, j.mapel_id, k.nama_kelas, m.nama_mapel, j.hari, j.jam_mulai, j.jam_selesai
                    FROM jadwal j
                    JOIN kelas k ON j.kelas_id = k.id
                    JOIN mata_pelajaran m ON j.mapel_id = m.id
                    $where_detail_guru
                    GROUP BY j.kelas_id, j.mapel_id, j.hari, j.jam_mulai, j.jam_selesai
                    ORDER BY k.nama_kelas ASC, m.nama_mapel ASC, j.hari ASC
                ");
                
                $kombinasi = mysqli_fetch_assoc($q_kombinasi);
            ?>
            <div class="max-w-6xl mx-auto w-full print-area flex-1 flex flex-col min-h-0">
                
                <!-- HEADER CETAK -->
                <div class="print-only text-center mb-4">
                    <h1 class="text-lg font-bold uppercase">Detail Absensi Guru</h1>
                    <p class="text-xs mt-1">Nama: <?= htmlspecialchars($guru_info['nama']) ?></p>
                    <hr class="border-gray-400 mt-2 mb-4">
                </div>

                <div class="flex items-center gap-3 mb-3 no-print shrink-0">
                    <a href="laporan.php?view=absensi_guru" class="w-8 h-8 bg-[#e6f4f1] rounded-lg flex items-center justify-center hover:bg-[#d1e7e2] transition shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Absensi Guru</h1>
                        <p class="text-[12px] font-medium text-gray-600">Kelola Absensi Guru TQA Darussalam</p>
                    </div>
                </div>

                <!-- Profil Guru -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-4 flex justify-between items-start mb-3 relative overflow-hidden shrink-0 no-print">
                    <div class="flex gap-3 z-10">
                        <div class="w-12 h-12 bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-[16px] font-extrabold text-gray-900 mb-0.5"><?= htmlspecialchars($guru_info['nama']) ?></h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-[12px] font-semibold text-gray-700">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 2a1 1 0 00-1 1v1a1 1 0 002 0V3a1 1 0 00-1-1zM4 4h3a3 3 0 006 0h3a2 2 0 012 2v9a2 2 0 01-2 2H4a2 2 0 01-2-2V6a2 2 0 012-2zm0 2v9h12V6H4z" clip-rule="evenodd"/></svg>
                                    NIP: <?= htmlspecialchars($guru_info['nip_nik'] ?? '-') ?>
                                </div>
                                <?php if ($kombinasi): ?>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                                    Mapel: <?= htmlspecialchars($kombinasi['nama_mapel']) ?>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M10.394 2.08a1 1 0 00-.788 0l-7 3a1 1 0 000 1.84L5.25 8.051a.999.999 0 01.356-.257l4-1.714a1 1 0 11.788 1.838l-2.328.996.002 1.069c0 .392.23.755.588.93l4 1.953a1 1 0 00.868 0l4-1.953a1.05 1.05 0 00.588-.93V8.25l-2.25.964v4.867a1 1 0 01-.53.882l-4 2.222a1 1 0 01-.94 0l-4-2.222a1 1 0 01-.53-.882V6.79l-2.25-.964a1 1 0 010-1.84l7-3z"/></svg>
                                    Kelas: <?= htmlspecialchars($kombinasi['nama_kelas']) ?>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                                    Hari: <?= $kombinasi['hari'] ?> (<?= date('H.i', strtotime($kombinasi['jam_mulai'])) ?> - <?= date('H.i', strtotime($kombinasi['jam_selesai'])) ?>)
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                    Periode: <?= $filter_semester ?> <?= $filter_tahun ?>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <button onclick="window.print()" class="border-2 border-[#0b6b53] text-[#0b6b53] px-3 py-1.5 rounded-lg font-bold text-[12px] hover:bg-[#e6f4f1] transition shadow-sm hover:shadow-md active:scale-95 flex items-center gap-1.5 z-10">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                        Cetak
                    </button>
                </div>

                <!-- Tabel Riwayat -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] flex-1 min-h-0 flex flex-col">
                    <div class="flex-1 overflow-auto p-4">
                        <?php if($kombinasi): 
                            $kelas_id = $kombinasi['kelas_id'];
                            $mapel_id = $kombinasi['mapel_id'];
                            $hari = $kombinasi['hari'];
                            $jam_mulai = $kombinasi['jam_mulai'];
                            $jam_selesai = $kombinasi['jam_selesai'];
                        ?>
                        <div class="bg-white border border-gray-200 rounded-lg overflow-hidden shadow-sm">
                            <table class="w-full text-left border-collapse min-w-max">
                                <thead class="bg-[#f8fafc] text-[#0b6b53]">
                                    <tr>
                                        <th class="px-3 py-2 font-bold text-[13px] text-center">Pertemuan</th>
                                        <th class="px-3 py-2 font-bold text-[13px] text-center">Tanggal</th>
                                        <th class="px-3 py-2 font-bold text-[13px] text-center">Hari</th>
                                        <th class="px-3 py-2 font-bold text-[13px] text-center">Jam</th>
                                        <th class="px-3 py-2 font-bold text-[13px] text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                    <?php 
                                    $q_detail = mysqli_query($koneksi, "
                                        SELECT j.*, 
                                        (SELECT tanggal FROM jadwal j2 WHERE j2.kelas_id = j.kelas_id AND j2.mapel_id = j.mapel_id AND j2.pertemuan_ke = j.pertemuan_ke AND j2.hari = '$hari' AND j2.jam_mulai = '$jam_mulai' LIMIT 1) as tgl_pertemuan
                                        FROM jadwal j 
                                        WHERE j.guru_id = $id_guru AND j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.hari = '$hari' AND j.jam_mulai = '$jam_mulai' AND j.jam_selesai = '$jam_selesai'
                                        ORDER BY j.pertemuan_ke ASC
                                    ");
                                    
                                    $p = 1;
                                    if(mysqli_num_rows($q_detail) > 0) {
                                        while($d = mysqli_fetch_assoc($q_detail)): 
                                            $status_teks = ($d['status'] == 'Selesai') ? 'Hadir' : (($d['status'] == 'Belum Dimulai') ? 'Belum' : 'Berlangsung');
                                            $status_warna = ($d['status'] == 'Selesai') ? 'bg-green-100 text-green-700' : (($d['status'] == 'Belum Dimulai') ? 'bg-red-100 text-red-700' : 'bg-[#e6f7ec] text-[#0b6b53]');
                                            
                                            $tgl_tampil = !empty($d['tgl_pertemuan']) ? date('d M Y', strtotime($d['tgl_pertemuan'])) : hitung_tanggal($d['hari'], $d['pertemuan_ke']);
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200 h-[42px]">
                                        <td class="px-3 py-2 text-center"><?= $p++ ?></td>
                                        <td class="px-3 py-2 text-center"><?= $tgl_tampil ?></td>
                                        <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['hari']) ?></td>
                                        <td class="px-3 py-2 text-center"><?= date('H.i', strtotime($d['jam_mulai'])) ?> - <?= date('H.i', strtotime($d['jam_selesai'])) ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= $status_warna ?>">
                                                <?= $status_teks ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endwhile; } else { ?>
                                        <tr><td colspan="5" class="p-4 text-center text-gray-500 font-normal text-sm">Belum ada jadwal.</td></tr>
                                    <?php } ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                            <div class="text-center text-gray-500 font-normal text-sm py-4">Belum ada jadwal mengajar.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 5: NILAI SANTRI (LIST KELAS)                       -->
            <!-- ======================================================= -->
            <?php elseif ($view == 'nilai'): ?>
            <div class="max-w-6xl mx-auto w-full print-area flex-1 flex flex-col min-h-0">
                
                <div class="flex items-center gap-3 mb-3 no-print shrink-0">
                    <a href="laporan.php" class="w-8 h-8 bg-[#e6f4f1] rounded-lg flex items-center justify-center hover:bg-[#d1e7e2] transition shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Nilai Santri</h1>
                        <p class="text-[12px] font-medium text-gray-600">Pilih Kelas untuk melihat detail nilai</p>
                    </div>
                </div>

                <!-- Filter Box & Search -->
                <form method="GET" action="laporan.php" class="bg-white rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-[#e6f4f1] p-3 mb-3 no-print flex flex-wrap gap-3 items-end shrink-0">
                    <input type="hidden" name="view" value="nilai">
                    
                    <div class="flex-1 min-w-[130px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Kelas</label>
                        <select name="kelas" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white transition-all duration-200">
                            <option value="0">Semua Kelas</option>
                            <?php mysqli_data_seek($q_kelas, 0); while($k = mysqli_fetch_assoc($q_kelas)): ?>
                                <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="flex-1 min-w-[80px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Tahun</label>
                        <input type="number" name="tahun" value="<?= $filter_tahun ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none transition-all duration-200">
                    </div>
                    <div class="flex-1 min-w-[80px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Semester</label>
                        <select name="semester" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] font-semibold focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white transition-all duration-200">
                            <option value="Ganjil" <?= $filter_semester == 'Ganjil' ? 'selected' : '' ?>>Ganjil</option>
                            <option value="Genap" <?= $filter_semester == 'Genap' ? 'selected' : '' ?>>Genap</option>
                        </select>
                    </div>
                    <div class="flex-[1.5] min-w-[150px]">
                        <label class="block text-[11px] font-bold text-gray-700 mb-1">Pencarian</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-9 pr-3 py-1.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] transition-all duration-200" placeholder="Cari Kelas / Guru...">
                        </div>
                    </div>
                    <button type="submit" class="bg-[#0b6b53] text-white px-4 py-1.5 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 flex items-center gap-1.5 h-[34px] whitespace-nowrap">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M3 3a1 1 0 011-1h12a1 1 0 011 1v3a1 1 0 01-.293.707L12 11.414V15a1 1 0 01-.293.707l-2 2A1 1 0 018 17v-5.586L3.293 6.707A1 1 0 013 6V3z" clip-rule="evenodd"/></svg>
                        Tampilkan
                    </button>
                </form>

                <!-- List Card (Per Kelas) -->
                <div class="flex-1 overflow-y-auto pr-1">
                    <div class="grid grid-cols-1 gap-3 pb-4">
                        <?php 
                        $where_nilai_cards = "WHERE 1=1";
                        if ($filter_kelas > 0) $where_nilai_cards .= " AND k.id = $filter_kelas";
                        if (!empty($search)) $where_nilai_cards .= " AND (k.nama_kelas LIKE '%$search%' OR u.nama LIKE '%$search%')";

                        // PERBAIKAN: Ambil per kelas, dengan contoh guru, hari, jam
                        $q_nilai_cards = mysqli_query($koneksi, "
                            SELECT k.id as id_kelas, k.nama_kelas,
                            (SELECT u.nama FROM jadwal j JOIN users u ON j.guru_id = u.id WHERE j.kelas_id = k.id LIMIT 1) as nama_guru,
                            (SELECT j.hari FROM jadwal j WHERE j.kelas_id = k.id LIMIT 1) as hari,
                            (SELECT j.jam_mulai FROM jadwal j WHERE j.kelas_id = k.id LIMIT 1) as jam_mulai,
                            (SELECT j.jam_selesai FROM jadwal j WHERE j.kelas_id = k.id LIMIT 1) as jam_selesai
                            FROM kelas k
                            $where_nilai_cards
                            ORDER BY k.nama_kelas ASC
                        ");
                        
                        if(mysqli_num_rows($q_nilai_cards) > 0):
                            while($km = mysqli_fetch_assoc($q_nilai_cards)): 
                        ?>
                        <div class="bg-white rounded-xl shadow-md hover:shadow-lg transition-all duration-300 border border-[#e6f4f1] p-4 flex items-center justify-between group">
                            <div class="flex items-center gap-4">
                                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-extrabold text-gray-900 uppercase group-hover:text-[#0b6b53] transition-colors">
                                        <?= htmlspecialchars($km['nama_kelas']) ?>
                                    </h3>
                                    <!-- PERBAIKAN: Ganti Hari & Jam menjadi Tahun & Periode, hapus Guru -->
                                    <div class="flex items-center gap-1.5 mt-1 text-[12px] font-semibold text-gray-700">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd"/></svg>
                                        Tahun: <?= $filter_tahun ?>
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-1 text-[12px] font-semibold text-gray-700">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                        Periode: <?= $filter_semester ?>
                                    </div>
                                </div>
                            </div>
                            <a href="laporan.php?view=nilai_kelas&det_kelas=<?= $km['id_kelas'] ?>" class="w-8 h-8 bg-[#e6f4f1] text-[#0b6b53] rounded-full flex items-center justify-center hover:bg-[#0b6b53] hover:text-white transition-all duration-200 active:scale-95 shrink-0 shadow-sm group-hover:scale-110">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                            </a>
                        </div>
                        <?php endwhile; else: ?>
                            <div class="p-5 text-center text-gray-500 bg-white rounded-xl border border-[#e6f4f1] font-semibold text-[13px] shadow-sm">
                                <?= !empty($search) ? 'Data pencarian tidak ditemukan.' : 'Belum ada data kelas.' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 5.1: NILAI SANTRI PER KELAS (SEMUA MAPEL)          -->
            <!-- ======================================================= -->
            <?php elseif ($view == 'nilai_kelas'): 
                $kelas_name = $det_kelas > 0 ? mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT nama_kelas FROM kelas WHERE id=$det_kelas"))['nama_kelas'] : 'Semua Kelas';
                $guru_name = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT u.nama FROM jadwal j JOIN users u ON j.guru_id = u.id WHERE j.kelas_id = $det_kelas LIMIT 1"))['nama'] ?? '-';
                $jadwal_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT hari, jam_mulai, jam_selesai FROM jadwal WHERE kelas_id = $det_kelas LIMIT 1"));
            ?>
            <div class="max-w-6xl mx-auto w-full print-area flex-1 flex flex-col min-h-0">
                
                <!-- HEADER CETAK -->
                <div class="print-only text-center mb-4">
                    <h1 class="text-lg font-bold uppercase">Laporan Nilai Santri</h1>
                    <p class="text-xs mt-1">
                        Kelas: <?= htmlspecialchars($kelas_name) ?> | 
                        Semester: <?= $filter_semester ?> <?= $filter_tahun ?>
                    </p>
                    <hr class="border-gray-400 mt-2 mb-4">
                </div>

                <div class="flex items-center gap-3 mb-3 no-print shrink-0">
                    <a href="laporan.php?view=nilai&kelas=<?= $filter_kelas ?>&search=<?= urlencode($search) ?>" class="w-8 h-8 bg-[#e6f4f1] rounded-lg flex items-center justify-center hover:bg-[#d1e7e2] transition shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight"><?= htmlspecialchars($kelas_name) ?></h1>
                        <p class="text-[12px] font-medium text-gray-600">Daftar Nilai Seluruh Mata Pelajaran</p>
                    </div>
                </div>

                <!-- Info Card Detail -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-4 flex justify-between items-start mb-3 relative overflow-hidden shrink-0 no-print">
                    <div class="flex gap-3 z-10">
                        <div class="w-12 h-12 bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                            <svg class="w-6 h-6 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M14 2H6c-1.1 0-1.99.9-1.99 2L4 20c0 1.1.89 2 1.99 2H18c1.1 0 2-.9 2-2V8l-6-6zm2 16H8v-2h8v2zm0-4H8v-2h8v2zm-3-5V3.5L18.5 9H13z"/></svg>
                        </div>
                        <div>
                            <h2 class="text-[16px] font-extrabold text-gray-900 mb-0.5"><?= htmlspecialchars($kelas_name) ?></h2>
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-1 text-[12px] font-semibold text-gray-700">
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                    Guru: <?= htmlspecialchars($guru_name) ?>
                                </div>
                                <div class="flex items-center gap-1.5">
                                    <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                    Periode: <?= $filter_semester ?> <?= $filter_tahun ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <button onclick="window.print()" class="border-2 border-[#0b6b53] text-[#0b6b53] px-3 py-1.5 rounded-lg font-bold text-[12px] hover:bg-[#e6f4f1] transition shadow-sm hover:shadow-md active:scale-95 flex items-center gap-1.5 z-10">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24"><path d="M19 8H5c-1.66 0-3 1.34-3 3v6h4v4h12v-4h4v-6c0-1.66-1.34-3-3-3zm-3 11H8v-5h8v5zm3-7c-.55 0-1-.45-1-1s.45-1 1-1 1 .45 1 1-.45 1-1 1zm-1-9H6v4h12V3z"/></svg>
                        Cetak
                    </button>
                </div>

                <!-- Form untuk menyimpan status -->
                <form id="form_nilai" method="POST" action="laporan.php?view=nilai_kelas&det_kelas=<?= $det_kelas ?>&tahun=<?= $filter_tahun ?>&semester=<?= $filter_semester ?>" class="bg-white rounded-xl shadow-md border border-[#e6f4f1] flex-1 min-h-0 flex flex-col">
                    <div class="p-3 border-b border-gray-100 flex justify-between items-center bg-[#f8fafc] no-print shrink-0 rounded-t-xl">
                        <h3 class="font-bold text-gray-900 text-[14px]">Daftar Nilai Santri</h3>
                        <button type="submit" name="simpan_status_nilai" class="bg-[#0b6b53] text-white px-4 py-1.5 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md hover:shadow-lg active:scale-95 flex items-center gap-1.5 h-[34px] whitespace-nowrap">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4"></path></svg>
                            Simpan Status
                        </button>
                    </div>
                    <div class="flex-1 overflow-auto">
                        <?php 
                        // Ambil semua daftar mata pelajaran secara dinamis
                        $q_mapel_all = mysqli_query($koneksi, "SELECT id, nama_mapel FROM mata_pelajaran ORDER BY nama_mapel ASC");
                        $mapel_cols = [];
                        while($mc = mysqli_fetch_assoc($q_mapel_all)) {
                            $mapel_cols[] = $mc;
                        }

                        // Ambil semua nilai dalam satu query untuk optimasi, filter berdasarkan kelas dan semester
                        $q_all_nilai = mysqli_query($koneksi, "SELECT santri_id, mapel_id, nilai_angka FROM nilai WHERE santri_id IN (SELECT id FROM santri WHERE kelas_id = $det_kelas)");
                        $all_nilai = [];
                        while($an = mysqli_fetch_assoc($q_all_nilai)) {
                            $all_nilai[$an['santri_id']][$an['mapel_id']] = $an['nilai_angka'];
                        }

                        $where_nilai = "WHERE s.status = 'Aktif' AND s.kelas_id = $det_kelas";
                        if (!empty($search)) $where_nilai .= " AND s.nama_santri LIKE '%$search%'";

                        // Pastikan filter semester diterapkan
                        $where_nilai .= " AND EXISTS (SELECT 1 FROM nilai n2 WHERE n2.santri_id = s.id)";

                        $q_nilai = mysqli_query($koneksi, "
                            SELECT s.id, s.nama_santri, k.nama_kelas,
                            MAX(n.status_lulus) as status_lulus
                            FROM santri s
                            LEFT JOIN kelas k ON s.kelas_id = k.id
                            LEFT JOIN nilai n ON s.id = n.santri_id
                            $where_nilai
                            GROUP BY s.id
                            ORDER BY s.nama_santri ASC
                        ");
                        ?>
                        
                        <?php if(mysqli_num_rows($q_nilai) > 0): ?>
                            <table class="w-full text-left border-collapse min-w-max">
                                <thead class="bg-[#f0f7f4] text-[#0b6b53] sticky top-0 z-10 shadow-sm">
                                    <tr>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center w-12">No</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px]">Nama Santri</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Kelas</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Semester</th>
                                        <!-- PERBAIKAN: Kolom Mapel Dinamis -->
                                        <?php foreach($mapel_cols as $mc): ?>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center"><?= htmlspecialchars($mc['nama_mapel']) ?></th>
                                        <?php endforeach; ?>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Status Kelulusan</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                    <?php 
                                    $no = 1;
                                    while($row = mysqli_fetch_assoc($q_nilai)): 
                                        $is_lulus = ($row['status_lulus'] == 'Lulus');
                                        $santri_nilai = isset($all_nilai[$row['id']]) ? $all_nilai[$row['id']] : [];
                                    ?>
                                    <tr class="hover:bg-gray-50 transition-colors duration-200 h-[42px]">
                                        <td class="px-3 py-2 text-center"><?= $no++ ?></td>
                                        <td class="px-3 py-2 font-semibold"><?= htmlspecialchars($row['nama_santri']) ?></td>
                                        <td class="px-3 py-2 text-center text-gray-600"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                        <td class="px-3 py-2 text-center text-gray-600"><?= $filter_semester ?></td>
                                        
                                        <!-- PERBAIKAN: Nilai Mapel Dinamis -->
                                        <?php foreach($mapel_cols as $mc): 
                                            $val = isset($santri_nilai[$mc['id']]) ? $santri_nilai[$mc['id']] : '0';
                                        ?>
                                            <td class="px-3 py-2 text-center"><?= $val ?></td>
                                        <?php endforeach; ?>
                                        
                                        <td class="px-3 py-2 text-center">
                                            <select name="status[<?= $row['id'] ?>]" class="px-2 py-1 text-xs font-bold rounded-md border-gray-300 <?= $is_lulus ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' ?> outline-none cursor-pointer">
                                                <option value="Lulus" <?= $is_lulus ? 'selected' : '' ?>>Lulus</option>
                                                <option value="Tidak Lulus" <?= !$is_lulus ? 'selected' : '' ?>>Tidak Lulus</option>
                                            </select>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="flex flex-col items-center justify-center h-full p-6 text-center text-gray-500 font-normal text-sm">
                                <?= (!empty($search)) ? 'Data yang dicari tidak ditemukan.' : 'Data nilai belum tersedia untuk kelas ini.' ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- SCRIPT UNTUK TOAST NOTIFICATION -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const urlParams = new URLSearchParams(window.location.search);
            const view = urlParams.get('view');
            
            // Tampilkan notifikasi hanya jika berada di halaman laporan spesifik (bukan main)
            if (view && view !== 'main') {
                const toast = document.getElementById('toast-notif');
                if (toast) {
                    // Animasi masuk
                    toast.classList.remove('translate-x-[150%]');
                    toast.classList.add('translate-x-0');
                    
                    // Hilang setelah 2 detik
                    setTimeout(() => {
                        toast.classList.remove('translate-x-0');
                        toast.classList.add('translate-x-[150%]');
                    }, 2000);
                }
            }
        });
    </script>
</body>
</html>