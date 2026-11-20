<?php 
include 'koneksi.php'; 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

// ==========================================
// ROUTING & PARAMETER
// ==========================================
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$kelas_id = isset($_GET['kelas_id']) ? (int)$_GET['kelas_id'] : 0;
$id_jadwal = isset($_GET['id_jadwal']) ? (int)$_GET['id_jadwal'] : 0;
$mapel_id = isset($_GET['mapel_id']) ? (int)$_GET['mapel_id'] : 0; 
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'pengaturan'; 
$error_msg = '';

// Auto-migrate tanggal column (Jika belum ada)
$cek_kolom_tgl = mysqli_query($koneksi, "SHOW COLUMNS FROM jadwal LIKE 'tanggal'");
if (mysqli_num_rows($cek_kolom_tgl) == 0) {
    mysqli_query($koneksi, "ALTER TABLE jadwal ADD COLUMN tanggal DATE NULL");
}

// Ambil ID Guru Default dari Kelas
$default_guru_id = 0;
if ($kelas_id > 0) {
    $q_kg = mysqli_query($koneksi, "SELECT guru_id FROM kelas WHERE id = $kelas_id");
    $d_kg = mysqli_fetch_assoc($q_kg);
    $default_guru_id = $d_kg['guru_id'] ?? 0;
}

// FIX: Jika guru_id kosong/0, ambil guru pertama yang aktif agar proses insert tidak gagal
if ($default_guru_id == 0) {
    $q_fallback = mysqli_query($koneksi, "SELECT id FROM users WHERE role='Guru' LIMIT 1");
    if($q_fallback && mysqli_num_rows($q_fallback) > 0) {
        $default_guru_id = mysqli_fetch_assoc($q_fallback)['id'];
    }
}

// ==========================================
// PROSES SIMPAN JADWAL BARU (DARI MENU TAMBAH JADWAL)
// ==========================================
if (isset($_POST['simpan_jadwal_baru'])) {
    $kelas_id_post = (int)$_POST['kelas_id'];
    $mapel_id_post = (int)$_POST['mapel_id'];
    $guru_id_post = (int)$_POST['guru_id'];
    
    // CEK DUPLIKAT: Apakah kelas dan mapel ini sudah ada jadwalnya?
    $cek_duplikat = mysqli_query($koneksi, "SELECT id FROM jadwal WHERE kelas_id = $kelas_id_post AND mapel_id = $mapel_id_post LIMIT 1");
    
    if (mysqli_num_rows($cek_duplikat) > 0) {
        $error_msg = "Gagal! Jadwal untuk kelas dan mata pelajaran ini sudah ada. Silakan atur jadwal di menu Atur Jadwal.";
    } else {
        // Cari pertemuan ke berapa
        $q_max = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT MAX(pertemuan_ke) as max_p FROM jadwal WHERE kelas_id = $kelas_id_post AND mapel_id = $mapel_id_post"));
        $next_pertemuan = ($q_max['max_p'] ?? 0) + 1;

        // Set default Hari, Jam Mulai, Jam Selesai
        $query = "INSERT INTO jadwal (kelas_id, mapel_id, guru_id, hari, jam_mulai, jam_selesai, pertemuan_ke, status) VALUES ($kelas_id_post, $mapel_id_post, $guru_id_post, 'Senin', '00:00:00', '00:00:00', $next_pertemuan, 'Belum Dimulai')";
        
        if (mysqli_query($koneksi, $query)) {
            header("Location: kelola_jadwal.php?msg=" . urlencode("Jadwal berhasil ditambahkan! Silakan atur waktu di menu Atur Jadwal."));
            exit;
        } else {
            $error_msg = "Gagal menyimpan jadwal: " . mysqli_error($koneksi);
        }
    }
}

// ==========================================
// PROSES SIMPAN JADWAL MANUAL (DARI TAB PENGATURAN)
// ==========================================
if (isset($_POST['simpan_jadwal_manual'])) {
    $kelas_id_post = (int)$_POST['kelas_id'];
    $mapel_id_post = (int)$_POST['mapel_id'];
    $guru_id_post = (int)$_POST['guru_id'];
    $tanggal = mysqli_real_escape_string($koneksi, $_POST['tanggal']);
    
    // Ambil nama hari otomatis dari tanggal input
    $timestamp = strtotime($tanggal);
    $hari_map = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
    $hari = $hari_map[date('l', $timestamp)];
    
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $pertemuan_ke = (int)$_POST['pertemuan_ke'];
    
    // 1. Cek jika pertemuan sudah ada (Berdasarkan Kelas dan MAPEL)
    $cek_pert = mysqli_query($koneksi, "SELECT id FROM jadwal WHERE kelas_id = $kelas_id_post AND mapel_id = $mapel_id_post AND pertemuan_ke = $pertemuan_ke");
    
    // 2. Cek jika tanggal sudah ada (Berdasarkan Kelas dan MAPEL)
    $cek_tgl = mysqli_query($koneksi, "SELECT id FROM jadwal WHERE kelas_id = $kelas_id_post AND mapel_id = $mapel_id_post AND tanggal = '$tanggal'");
    
    if (mysqli_num_rows($cek_pert) > 0) {
        $error_msg = "Gagal! Pertemuan ke-$pertemuan_ke sudah ada di jadwal. Silakan pilih nomor pertemuan lain.";
    } elseif (mysqli_num_rows($cek_tgl) > 0) {
        $error_msg = "Gagal! Jadwal di tanggal ini sudah ada untuk mata pelajaran ini.";
    } else {
        // 3. Simpan Jadwal (Bebas Bentrok Jam, Sesuai Permintaan)
        mysqli_query($koneksi, "INSERT INTO jadwal (kelas_id, mapel_id, guru_id, hari, tanggal, jam_mulai, jam_selesai, pertemuan_ke, status) VALUES ($kelas_id_post, $mapel_id_post, $guru_id_post, '$hari', '$tanggal', '$jam_mulai', '$jam_selesai', $pertemuan_ke, 'Belum Dimulai')");
        header("Location: kelola_jadwal.php?action=atur&kelas_id=$kelas_id_post&mapel_id=$mapel_id_post&tab=pengaturan&msg=" . urlencode("Jadwal manual berhasil disimpan!"));
        exit;
    }
}

// ==========================================
// PROSES HAPUS JADWAL (BERDASARKAN KELAS & MAPEL)
// ==========================================
if ($action == 'hapus_semua_jadwal' && $kelas_id > 0) {
    if ($mapel_id > 0) {
        // Hapus jadwal untuk mapel tertentu
        mysqli_query($koneksi, "DELETE FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id");
    } else {
        // Hapus semua jadwal untuk kelas ini (TANPA menghapus data kelas / santri)
        mysqli_query($koneksi, "DELETE FROM jadwal WHERE kelas_id = $kelas_id");
    }
    header("Location: kelola_jadwal.php?msg=" . urlencode("Jadwal berhasil dihapus!"));
    exit;
}

// ==========================================
// 1. PROSES SIMPAN PENGATURAN
// ==========================================
if (isset($_POST['simpan_pengaturan'])) {
    $session_key = $kelas_id . '_' . $mapel_id;
    $_SESSION['periode'][$session_key]['tanggal_mulai'] = $_POST['tanggal_mulai'];
    $_SESSION['periode'][$session_key]['jumlah_pertemuan'] = (int)$_POST['jumlah_pertemuan'];
    $_SESSION['periode'][$session_key]['jam_mulai'] = $_POST['jam_mulai'];
    $_SESSION['periode'][$session_key]['jam_selesai'] = $_POST['jam_selesai'];
    
    header("Location: kelola_jadwal.php?action=atur&kelas_id=$kelas_id&mapel_id=$mapel_id&tab=pengaturan&msg=" . urlencode("Pengaturan jadwal berhasil disimpan!"));
    exit;
}

// ==========================================
// 2. PROSES GENERATE JADWAL
// ==========================================
if (isset($_POST['generate_jadwal'])) {
    $session_key = $kelas_id . '_' . $mapel_id;
    $_SESSION['periode'][$session_key]['tanggal_mulai'] = $_POST['tanggal_mulai'];
    $_SESSION['periode'][$session_key]['jumlah_pertemuan'] = (int)$_POST['jumlah_pertemuan'];
    $_SESSION['periode'][$session_key]['jam_mulai'] = $_POST['jam_mulai'];
    $_SESSION['periode'][$session_key]['jam_selesai'] = $_POST['jam_selesai'];
    
    // --- OTOMATIS MENGISI JADWAL ---
    $jml = (int)$_POST['jumlah_pertemuan'];
    $tgl_mulai = $_POST['tanggal_mulai'];
    
    // Ambil input jam dan tambahkan detik untuk format database
    $jam_mulai_post = $_POST['jam_mulai'] . ':00';
    $jam_selesai_post = $_POST['jam_selesai'] . ':00';
    
    $hari_mulai = date('l', strtotime($tgl_mulai));
    $hari_indo = ['Sunday'=>'Minggu', 'Monday'=>'Senin', 'Tuesday'=>'Selasa', 'Wednesday'=>'Rabu', 'Thursday'=>'Kamis', 'Friday'=>'Jumat', 'Saturday'=>'Sabtu'];
    $hari_awal = $hari_indo[$hari_mulai];
    
    // Gunakan mapel_id yang aktif
    $default_mapel = $mapel_id > 0 ? $mapel_id : (mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT id FROM mata_pelajaran LIMIT 1"))['id'] ?? 0);
    
    if ($default_guru_id > 0 && $default_mapel > 0) {
        // HAPUS JADWAL LAMA HANYA UNTUK MAPEL INI (Agar mapel lain tidak terhapus)
        mysqli_query($koneksi, "DELETE FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $default_mapel");

        for ($i = 1; $i <= $jml; $i++) {
            $tgl_pert = date('Y-m-d', strtotime($tgl_mulai . ' + ' . ($i - 1) . ' week'));
            mysqli_query($koneksi, "INSERT INTO jadwal (kelas_id, mapel_id, guru_id, hari, tanggal, jam_mulai, jam_selesai, pertemuan_ke, status) VALUES ($kelas_id, $default_mapel, $default_guru_id, '$hari_awal', '$tgl_pert', '$jam_mulai_post', '$jam_selesai_post', $i, 'Belum Dimulai')");
        }
    }
    
    header("Location: kelola_jadwal.php?action=atur&kelas_id=$kelas_id&mapel_id=$mapel_id&tab=pengaturan&msg=" . urlencode("Jadwal berhasil di-generate ulang!"));
    exit;
}

// ==========================================
// 3. PROSES SIMPAN JADWAL (EDIT DI DALAM ATUR JADWAL)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['simpan_jadwal'])) {
    $mapel_id_post = (int)$_POST['mapel_id'];
    $guru_id_post = (int)$_POST['guru_id'];
    $hari = mysqli_real_escape_string($koneksi, $_POST['hari']);
    $jam_mulai = $_POST['jam_mulai'];
    $jam_selesai = $_POST['jam_selesai'];
    $pertemuan_ke = (int)$_POST['pertemuan_ke'];
    $status_jadwal = isset($_POST['status_jadwal']) ? $_POST['status_jadwal'] : 'Belum Dimulai';

    if ($id_jadwal > 0) {
        $query = "UPDATE jadwal SET mapel_id=$mapel_id_post, guru_id=$guru_id_post, hari='$hari', jam_mulai='$jam_mulai', jam_selesai='$jam_selesai', pertemuan_ke=$pertemuan_ke, status='$status_jadwal' WHERE id=$id_jadwal";
    } else {
        $query = "INSERT INTO jadwal (kelas_id, mapel_id, guru_id, hari, jam_mulai, jam_selesai, pertemuan_ke, status) VALUES ($kelas_id, $mapel_id_post, $guru_id_post, '$hari', '$jam_mulai', '$jam_selesai', $pertemuan_ke, 'Belum Dimulai')";
    }

    if (mysqli_query($koneksi, $query)) {
        $redirect_tab = isset($_POST['current_tab']) ? $_POST['current_tab'] : 'pengaturan';
        header("Location: kelola_jadwal.php?action=atur&kelas_id=$kelas_id&mapel_id=$mapel_id_post&tab=$redirect_tab&msg=" . urlencode("Jadwal berhasil disimpan!"));
        exit;
    } else {
        $error_msg = "Gagal menyimpan jadwal: " . mysqli_error($koneksi);
    }
}

// ==========================================
// 4. PROSES HAPUS JADWAL (SATU PERTEMUAN)
// ==========================================
if ($action == 'hapus' && $id_jadwal > 0) {
    // Ambil data jadwal yang akan dihapus untuk keperluan re-number
    $q_cek = mysqli_query($koneksi, "SELECT kelas_id, mapel_id, pertemuan_ke FROM jadwal WHERE id = $id_jadwal");
    $d_cek = mysqli_fetch_assoc($q_cek);
    
    if ($d_cek) {
        $del_kelas = $d_cek['kelas_id'];
        $del_mapel = $d_cek['mapel_id'];
        $del_pertemuan = $d_cek['pertemuan_ke'];

        if (mysqli_query($koneksi, "DELETE FROM jadwal WHERE id = $id_jadwal")) {
            // Re-number pertemuan setelahnya agar tidak ada baris kosong dan urutan tetap rapi
            mysqli_query($koneksi, "UPDATE jadwal SET pertemuan_ke = pertemuan_ke - 1 WHERE kelas_id = $del_kelas AND mapel_id = $del_mapel AND pertemuan_ke > $del_pertemuan");
            
            header("Location: kelola_jadwal.php?action=atur&kelas_id=$kelas_id&mapel_id=$mapel_id&tab=$tab&msg=" . urlencode("Jadwal berhasil dihapus!"));
        } else {
            header("Location: kelola_jadwal.php?action=atur&kelas_id=$kelas_id&mapel_id=$mapel_id&tab=$tab&msg=" . urlencode("Gagal menghapus jadwal.") . "&type=error");
        }
    } else {
        header("Location: kelola_jadwal.php?action=atur&kelas_id=$kelas_id&mapel_id=$mapel_id&tab=$tab&msg=" . urlencode("Jadwal tidak ditemukan.") . "&type=error");
    }
    exit;
}

// ==========================================
// AMBIL DATA MASTER UNTUK FORM
// ==========================================
$q_mapel = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE status='Aktif' ORDER BY nama_mapel ASC");
$q_guru = mysqli_query($koneksi, "SELECT * FROM users WHERE role='Guru' AND status='Aktif' ORDER BY nama ASC");

// QUERY UNTUK LIST DI HALAMAN UTAMA (Hanya menampilkan yang sudah punya jadwal)
$q_kelas_master = mysqli_query($koneksi, "
    SELECT 
        k.id as id_kelas, k.nama_kelas, k.kapasitas, r.nama_ruangan, r.keterangan,
        m.id as id_mapel, m.nama_mapel,
        COALESCE(u.nama, ug.nama) as nama_guru,
        (SELECT COUNT(*) FROM santri s WHERE s.kelas_id = k.id) as jumlah_santri,
        (SELECT COUNT(*) FROM jadwal j2 WHERE j2.kelas_id = k.id AND j2.mapel_id = m.id) as total_pertemuan,
        (SELECT MIN(id) FROM jadwal j3 WHERE j3.kelas_id = k.id AND j3.mapel_id = m.id) as id_jadwal_edit
    FROM kelas k
    LEFT JOIN ruangan r ON k.ruangan_id = r.id
    JOIN jadwal j ON k.id = j.kelas_id
    LEFT JOIN mata_pelajaran m ON j.mapel_id = m.id
    LEFT JOIN users u ON j.guru_id = u.id
    LEFT JOIN users ug ON k.guru_id = ug.id
    GROUP BY k.id, m.id
    ORDER BY k.nama_kelas ASC, m.nama_mapel ASC
");

// QUERY UNTUK DROPDOWN DI FORM TAMBAH JADWAL BARU (Menampilkan semua kelas)
$q_all_kelas = mysqli_query($koneksi, "SELECT k.id as id_kelas, k.nama_kelas, r.nama_ruangan, r.keterangan FROM kelas k LEFT JOIN ruangan r ON k.ruangan_id = r.id ORDER BY k.nama_kelas ASC");

// Data Edit Jadwal
$data_edit = [];
if ($action == 'edit_jadwal' && $id_jadwal > 0) {
    $data_edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM jadwal WHERE id = $id_jadwal"));
    // Pastikan mapel_id sinkron jika diakses dari halaman luar
    if ($mapel_id == 0 && !empty($data_edit['mapel_id'])) {
        $mapel_id = $data_edit['mapel_id'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Jadwal - TQA Darussalam</title>
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
    $current_page = 'kelola_jadwal'; 
    include 'sidebar.php'; 
    ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <?php include 'header.php'; ?>
        
        <!-- Halaman utama sekarang bisa di-scroll (overflow-y-auto) -->
        <main class="flex-1 flex flex-col px-6 py-4 overflow-y-auto">
            
            <!-- Notifikasi Error POST (Lokal) -->
            <div class="shrink-0">
                <?php if(!empty($error_msg)): ?>
                    <div id="notif-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg mb-2 font-semibold text-sm shadow-md transition-opacity duration-500"><?= $error_msg ?></div>
                <?php endif; ?>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 1: DAFTAR KELAS                                    -->
            <!-- ======================================================= -->
            <?php if ($action == 'list'): ?>
            <div class="flex-1 flex flex-col min-h-0">
                <!-- Judul (Dirapatkan) -->
                <div class="flex items-center gap-3 mb-3 shrink-0">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Kelola Jadwal</h1>
                        <p class="text-[13px] font-medium text-gray-600">Kelola data Jadwal TQA Darussalam</p>
                    </div>
                </div>

                <div class="flex justify-between items-center mb-2 shrink-0">
                    <h2 class="text-[16px] font-bold text-gray-900">Daftar Kelas</h2>
                </div>
                
                <!-- Pencarian & Filter & Tombol Tambah -->
                <div class="flex gap-2 mb-3 shrink-0 max-w-4xl items-center">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" id="searchKelas" onkeyup="filterKelas()" class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" placeholder="Cari Kelas...">
                    </div>
                    <select id="filterMapel" onchange="filterKelas()" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white w-44 text-[13px] font-medium cursor-pointer shadow-sm transition-all">
                        <option value="">Semua Mapel</option>
                        <?php mysqli_data_seek($q_mapel, 0); while($m = mysqli_fetch_assoc($q_mapel)): ?>
                            <option value="<?= strtolower(htmlspecialchars($m['nama_mapel'])) ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
                        <?php endwhile; ?>
                    </select>
                    <button type="button" onclick="filterKelas()" class="bg-[#0b6b53] text-white px-5 py-2 rounded-lg text-[13px] font-semibold hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 whitespace-nowrap">
                        Cari
                    </button>
                    <a href="kelola_jadwal.php?action=form_tambah_jadwal" class="bg-[#0b6b53] text-white px-4 py-2 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 flex items-center gap-1.5 whitespace-nowrap">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Jadwal Baru
                    </a>
                </div>

                <!-- Scroll Container untuk List Kelas -->
                <div class="flex-1 pr-2" id="listKelasContainer">
                    <div class="grid grid-cols-1 gap-3 pb-4">
                        <?php 
                        if(mysqli_num_rows($q_kelas_master) > 0):
                        while($k = mysqli_fetch_assoc($q_kelas_master)): 
                            $is_atur = $k['total_pertemuan'] > 0;
                            $ada_guru = !empty($k['nama_guru']);
                            $mapel_name = $k['nama_mapel'] ?? 'Belum ada Mapel';
                            $id_kelas = $k['id_kelas'];
                            $id_mapel = $k['id_mapel'];
                        ?>
                        <div class="kelas-card bg-white rounded-xl shadow-md border border-[#e6f4f1] p-4 flex items-center justify-between hover:shadow-lg transition-shadow duration-300" data-mapel="<?= strtolower(htmlspecialchars($mapel_name)) ?>">
                            <div class="flex items-center gap-4">
                                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-lg flex items-center justify-center shrink-0">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                                </div>
                                <div>
                                    <h3 class="text-[15px] font-extrabold text-gray-900 uppercase"><?= htmlspecialchars($k['nama_kelas']) ?> <?= $id_mapel ? '- ' . htmlspecialchars($mapel_name) : '' ?></h3>
                                    <div class="flex items-center gap-4 text-[12px] text-gray-700 font-semibold mt-1">
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M13 6a3 3 0 11-6 0 3 3 0 016 0zM18 8a2 2 0 11-4 0 2 2 0 014 0zM14 15a4 4 0 00-8 0v3h8v-3zM6 8a2 2 0 11-4 0 2 2 0 014 0zM16 18v-3a5.972 5.972 0 00-.75-2.906A3.005 3.005 0 0119 15v3h-3zM4.75 12.094A5.973 5.973 0 004 15v3H1v-3a3 3 0 013.75-2.906z"/></svg>
                                            Kapasitas : <?= $k['kapasitas'] ?>
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                            Jumlah Santri : <?= $k['jumlah_santri'] ?>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-1 text-[12px] text-gray-700 font-semibold mt-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                        <?= htmlspecialchars($k['nama_ruangan'] ?? '-') ?> (<?= htmlspecialchars($k['keterangan'] ?? '-') ?>)
                                    </div>
                                    <div class="flex items-center gap-1 text-[12px] text-gray-700 font-semibold mt-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg>
                                        Mapel : <?= htmlspecialchars($mapel_name) ?>
                                    </div>
                                    <div class="flex items-center gap-1 text-[12px] text-gray-700 font-semibold mt-1">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg>
                                        Guru : <?= htmlspecialchars($k['nama_guru'] ?? 'Belum diatur') ?>
                                    </div>
                                </div>
                            </div>

                            <div class="flex flex-col items-end gap-2">
                                <?php if($is_atur): ?>
                                    <span class="px-3 py-1 bg-[#e6f7ec] text-[#0b6b53] rounded-full text-[11px] font-bold flex items-center gap-1 shadow-sm">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <?= $k['total_pertemuan'] ?> Pertemuan
                                    </span>
                                <?php else: ?>
                                    <span class="px-3 py-1 bg-gray-200 text-gray-600 rounded-full text-[11px] font-bold flex items-center gap-1 shadow-sm">
                                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                        Belum di Atur
                                    </span>
                                <?php endif; ?>
                                
                                <div class="flex gap-2">
                                    <?php if($is_atur): ?>
                                        <button type="button" onclick="showConfirmModal('Hapus Jadwal', 'Apakah Anda yakin ingin menghapus jadwal ini?', function(){ window.location.href='kelola_jadwal.php?action=hapus_semua_jadwal&kelas_id=<?= $id_kelas ?>&mapel_id=<?= $id_mapel ?>'; }, true)" class="px-3 py-1.5 bg-[#fde2e2] border-2 border-[#fde2e2] text-[#e53e3e] font-bold text-[12px] rounded-lg hover:bg-red-200 transition-all duration-200 shadow-sm active:scale-95 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                            Hapus
                                        </button>
                                    <?php else: ?>
                                        <button type="button" disabled class="px-3 py-1.5 bg-gray-100 border-2 border-gray-200 text-gray-400 font-bold text-[12px] rounded-lg cursor-not-allowed flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                            Hapus
                                        </button>
                                    <?php endif; ?>

                                    <?php if(!empty($k['id_jadwal_edit'])): ?>
                                        <a href="kelola_jadwal.php?action=edit_jadwal&kelas_id=<?= $id_kelas ?>&mapel_id=<?= $id_mapel ?>&id_jadwal=<?= $k['id_jadwal_edit'] ?>" class="px-3 py-1.5 bg-[#fef3c7] border-2 border-[#fef3c7] text-[#d97706] font-bold text-[12px] rounded-lg hover:bg-yellow-200 transition-all duration-200 shadow-sm active:scale-95 flex items-center gap-1">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            Edit
                                        </a>
                                    <?php endif; ?>
                                    <?php if($ada_guru): ?>
                                        <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $id_kelas ?>&mapel_id=<?= $id_mapel ?>" class="px-4 py-1.5 border-2 border-[#0b6b53] text-[#0b6b53] font-bold text-[12px] rounded-lg hover:bg-[#0b6b53] hover:text-white transition-all duration-200 shadow-sm active:scale-95 flex items-center gap-1.5">
                                            Atur Jadwal 
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </a>
                                    <?php else: ?>
                                        <button type="button" onclick="showConfirmModal('Peringatan', 'Kelas ini belum memiliki Guru Pengajar. Silakan atur Guru di menu Kelola Kelas terlebih dahulu!', null, false, true)" class="px-4 py-1.5 bg-gray-100 border-2 border-gray-300 text-gray-400 font-bold text-[12px] rounded-lg cursor-not-allowed flex items-center gap-1.5">
                                            Atur Jadwal 
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"></path></svg>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endwhile; else: ?>
                            <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-8 text-center flex flex-col items-center justify-center mt-4">
                                <svg class="w-16 h-16 text-gray-300 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                <h3 class="text-[16px] font-bold text-gray-800 mb-1">Belum Ada Jadwal</h3>
                                <p class="text-[13px] text-gray-500 mb-4">Silakan tambahkan jadwal baru untuk kelas yang sudah dibuat di menu Kelola Kelas.</p>
                                <a href="kelola_jadwal.php?action=form_tambah_jadwal" class="bg-[#0b6b53] text-white px-5 py-2 rounded-lg text-[13px] font-bold hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">Tambah Jadwal Baru</a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 2: ATUR JADWAL                                     -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'atur'): 
                $kelas_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT k.*, r.nama_ruangan, r.keterangan, (SELECT COUNT(*) FROM santri s WHERE s.kelas_id = k.id) as jumlah_santri FROM kelas k LEFT JOIN ruangan r ON k.ruangan_id = r.id WHERE k.id = $kelas_id"));
                
                // Ambil Info Mapel & Guru Spesifik
                $mapel_info = '-';
                $list_guru_info = '-';
                if ($mapel_id > 0) {
                    $q_info = mysqli_query($koneksi, "SELECT m.nama_mapel, (SELECT GROUP_CONCAT(DISTINCT u.nama SEPARATOR ', ') FROM jadwal j JOIN users u ON j.guru_id = u.id WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id) as list_guru FROM mata_pelajaran m WHERE m.id = $mapel_id");
                    if ($d_info = mysqli_fetch_assoc($q_info)) {
                        $mapel_info = $d_info['nama_mapel'];
                        $list_guru_info = $d_info['list_guru'] ?? 'Belum diatur';
                    }
                }
                
                $session_key = $kelas_id . '_' . $mapel_id;
                $tgl_mulai_session = $_SESSION['periode'][$session_key]['tanggal_mulai'] ?? '';
                $jml_pertemuan_session = $_SESSION['periode'][$session_key]['jumlah_pertemuan'] ?? 16;
                $jam_mulai_session = $_SESSION['periode'][$session_key]['jam_mulai'] ?? '14:00';
                $jam_selesai_session = $_SESSION['periode'][$session_key]['jam_selesai'] ?? '16:00';
                
                $total_jadwal_db = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id"))['c'];
                
                $q_max_db = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT MAX(pertemuan_ke) as max_p FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id"));
                $max_db = $q_max_db['max_p'] ?? 0;
                $batas_loop = max($jml_pertemuan_session, $max_db);
            ?>
            <div class="w-full pb-10">
                <!-- Header Atur Jadwal -->
                <div class="flex items-center justify-between mb-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <a href="kelola_jadwal.php" class="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm active:scale-95">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                        <div>
                            <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Jadwal Kelas <?= htmlspecialchars($kelas_info['nama_kelas']) ?></h1>
                            <p class="text-[12px] font-medium text-gray-600">Kelola data Jadwal TQA Darussalam</p>
                        </div>
                    </div>
                    
                    <!-- Tabs Menu -->
                    <div class="flex gap-1 bg-white p-1 rounded-lg shadow-md border border-gray-100">
                        <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=pengaturan" class="px-4 py-1.5 rounded-md font-bold text-[12px] transition-all duration-200 active:scale-95 <?= $tab == 'pengaturan' ? 'bg-[#0b6b53] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Pengaturan Jadwal</a>
                        <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=daftar" class="px-4 py-1.5 rounded-md font-bold text-[12px] transition-all duration-200 active:scale-95 <?= $tab == 'daftar' ? 'bg-[#0b6b53] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Daftar Pertemuan (<?= $total_jadwal_db ?>)</a>
                    </div>
                </div>

                <!-- Info Box Kelas -->
                <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-3 flex items-center justify-between mb-3 shrink-0">
                    <div class="flex items-center gap-3">
                        <div class="w-9 h-9 bg-[#e6f4f1] rounded-full flex items-center justify-center">
                            <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                        </div>
                        <div>
                            <h3 class="text-[14px] font-extrabold text-gray-900 uppercase"><?= htmlspecialchars($kelas_info['nama_kelas']) ?></h3>
                            <p class="text-[12px] font-semibold text-gray-600"><?= htmlspecialchars($kelas_info['nama_ruangan']) ?> (<?= htmlspecialchars($kelas_info['keterangan']) ?>)</p>
                            <p class="text-[12px] font-semibold text-gray-600">Mapel : <?= htmlspecialchars($mapel_info) ?></p>
                            <p class="text-[12px] font-semibold text-gray-600">Guru : <?= htmlspecialchars($list_guru_info) ?></p>
                        </div>
                    </div>
                    <div class="flex items-center gap-6 pr-4 border-l border-gray-200 pl-6">
                        <div class="text-center">
                            <p class="text-[11px] font-bold text-gray-500 mb-0.5">Kapasitas</p>
                            <p class="text-[15px] font-extrabold text-gray-900"><?= $kelas_info['kapasitas'] ?></p>
                        </div>
                        <div class="text-center">
                            <p class="text-[11px] font-bold text-gray-500 mb-0.5">Jumlah Santri</p>
                            <p class="text-[15px] font-extrabold text-gray-900"><?= $kelas_info['jumlah_santri'] ?></p>
                        </div>
                    </div>
                </div>

                <!-- ======================================================= -->
                <!-- KONTEN TAB: PENGATURAN JADWAL                           -->
                <!-- ======================================================= -->
                <?php if ($tab == 'pengaturan'): ?>
                    
                    <!-- Form Generate & Simpan -->
                    <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-4 mb-3 shrink-0">
                        <form method="POST" id="formGenerate" action="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=pengaturan">
                            <input type="hidden" name="generate_jadwal" value="1">
                            
                            <h3 class="font-bold text-[13px] text-gray-800 mb-2">Periode Pembelajaran</h3>
                            <div class="flex flex-wrap gap-3 items-end">
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-[11px] font-bold text-gray-600 mb-1">*Tanggal Mulai</label>
                                    <input type="date" name="tanggal_mulai" value="<?= $tgl_mulai_session ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] focus:ring-2 focus:ring-[#0b6b53] outline-none shadow-sm transition-all" required>
                                </div>
                                <div class="flex-1 min-w-[150px]">
                                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Tanggal Selesai (Otomatis)</label>
                                    <input type="text" class="w-full px-3 py-1.5 border border-gray-200 bg-gray-50 rounded-lg text-[13px] text-gray-500 outline-none" value="<?= $tgl_mulai_session ? date('d M Y', strtotime($tgl_mulai_session . ' + ' . ($jml_pertemuan_session - 1) . ' week')) : '' ?>" readonly placeholder="Terisi Otomatis">
                                </div>
                                <div class="flex-1 min-w-[110px]">
                                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Jam Mulai</label>
                                    <input type="time" name="jam_mulai" value="<?= $jam_mulai_session ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] focus:ring-2 focus:ring-[#0b6b53] outline-none shadow-sm transition-all" required>
                                </div>
                                <div class="flex-1 min-w-[110px]">
                                    <label class="block text-[11px] font-bold text-gray-600 mb-1">Jam Selesai</label>
                                    <input type="time" name="jam_selesai" value="<?= $jam_selesai_session ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] focus:ring-2 focus:ring-[#0b6b53] outline-none shadow-sm transition-all" required>
                                </div>
                                <div class="flex-1 min-w-[280px] flex items-end gap-2 shrink-0">
                                    <div class="flex-1 min-w-[80px]">
                                        <label class="block text-[11px] font-bold text-gray-600 mb-1">Jumlah Pertemuan</label>
                                        <input type="number" name="jumlah_pertemuan" value="<?= $jml_pertemuan_session ?>" class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-[13px] text-center focus:ring-2 focus:ring-[#0b6b53] outline-none shadow-sm transition-all" min="1" required>
                                    </div>
                                    <button type="submit" name="simpan_pengaturan" value="1" class="bg-gray-100 text-gray-700 border border-gray-300 px-3 py-1.5 rounded-lg text-[12px] font-bold hover:bg-gray-200 transition-all duration-200 active:scale-95 shadow-sm h-[34px] flex items-center gap-1 whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                        Simpan
                                    </button>
                                    <button type="button" onclick="showConfirmModal('Generate Ulang', 'Generate ulang akan menimpa semua jadwal mapel ini yang ada di kelas ini. Lanjutkan?', function(){ document.getElementById('formGenerate').submit(); }, false)" class="bg-[#0b6b53] text-white px-3 py-1.5 rounded-lg text-[12px] font-bold hover:bg-[#08533f] transition-all duration-200 active:scale-95 shadow-md h-[34px] flex items-center gap-1 whitespace-nowrap">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path></svg>
                                        Generate
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Tabel Pengaturan Jadwal -->
                    <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] mb-4">
                        <div class="p-3 border-b border-gray-100 flex justify-between items-center shrink-0">
                            <h3 class="font-bold text-[14px] text-gray-900">Jadwal Pembelajaran</h3>
                            <button type="button" onclick="document.getElementById('manualFormContainer').classList.toggle('hidden')" class="bg-[#0b6b53] text-white px-3 py-1.5 rounded-lg text-[12px] font-bold hover:bg-[#08533f] transition-all duration-200 active:scale-95 shadow-sm flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Jadwal Manual
                            </button>
                        </div>

                        <!-- Form Tambah Jadwal Manual (Mata Pelajaran Dikunci, Hari Otomatis) -->
                        <div id="manualFormContainer" class="hidden p-5 border-b border-gray-100 bg-gray-50">
                            <h4 class="font-bold text-[14px] text-gray-900 mb-3">Form Tambah Jadwal Manual</h4>
                            <form method="POST" class="space-y-4">
                                <input type="hidden" name="simpan_jadwal_manual" value="1">
                                <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block font-semibold text-[13px] text-gray-700 mb-1">Pertemuan Ke-</label>
                                        <input type="number" name="pertemuan_ke" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" required min="1" value="<?= max(1, $total_jadwal_db + 1) ?>">
                                    </div>
                                    
                                    <!-- TANGGAL (Hari otomatis mengikuti tanggal) -->
                                    <div>
                                        <label class="block font-semibold text-[13px] text-gray-700 mb-1">Tanggal</label>
                                        <input type="date" name="tanggal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" required value="<?= date('Y-m-d') ?>">
                                    </div>

                                    <!-- FIELD MATA PELAJARAN (DIKUNCI) -->
                                    <div>
                                        <label class="block font-semibold text-[13px] text-gray-700 mb-1">Mata Pelajaran</label>
                                        <input type="text" class="w-full px-3 py-2 border border-gray-200 bg-gray-100 rounded-lg text-[13px] text-gray-500 outline-none cursor-not-allowed" readonly value="<?= htmlspecialchars($mapel_info) ?>">
                                        <input type="hidden" name="mapel_id" value="<?= $mapel_id ?>">
                                    </div>

                                    <div>
                                        <label class="block font-semibold text-[13px] text-gray-700 mb-1">Guru Pengajar</label>
                                        <select name="guru_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all cursor-pointer" required>
                                            <option value="">Pilih Guru</option>
                                            <?php mysqli_data_seek($q_guru, 0); while($g = mysqli_fetch_assoc($q_guru)): ?>
                                                <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
                                            <?php endwhile; ?>
                                        </select>
                                    </div>
                                    
                                    <div>
                                        <label class="block font-semibold text-[13px] text-gray-700 mb-1">Jam Mulai</label>
                                        <input type="time" name="jam_mulai" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" required value="14:00">
                                    </div>
                                    <div>
                                        <label class="block font-semibold text-[13px] text-gray-700 mb-1">Jam Selesai</label>
                                        <input type="time" name="jam_selesai" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" required value="16:00">
                                    </div>
                                </div>
                                <div class="flex gap-3 pt-4 mt-5 border-t border-gray-100">
                                    <button type="submit" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[13px] py-2 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">
                                        Simpan Jadwal
                                    </button>
                                    <button type="button" onclick="document.getElementById('manualFormContainer').classList.add('hidden')" class="px-6 py-2 bg-gray-100 text-gray-700 font-semibold text-[13px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95">
                                        Batal
                                    </button>
                                </div>
                            </form>
                        </div>

                        <div class="w-full">
                            <table class="w-full text-left border-collapse min-w-max">
                                <thead class="bg-[#f0f7f4] text-[#0b6b53]">
                                    <tr>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Pertemuan</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Hari</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Tanggal</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Waktu</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Mata Pelajaran</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center">Guru Pengajar</th>
                                        <th class="px-3 py-2.5 font-bold text-[13px] text-center w-24">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                    <?php 
                                    for ($i = 1; $i <= $batas_loop; $i++): 
                                        // Filter berdasarkan mapel_id
                                        $q_cek = mysqli_query($koneksi, "SELECT j.*, m.nama_mapel, u.nama as nama_guru FROM jadwal j JOIN mata_pelajaran m ON j.mapel_id = m.id JOIN users u ON j.guru_id = u.id WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.pertemuan_ke = $i");
                                        $jadwal_db = mysqli_fetch_assoc($q_cek);
                                        
                                        // Ambil tanggal dari DB jika ada, jika tidak pakai kalkulasi
                                        $tgl_pertemuan = !empty($jadwal_db['tanggal']) ? date('d M Y', strtotime($jadwal_db['tanggal'])) : ($tgl_mulai_session ? date('d M Y', strtotime($tgl_mulai_session . ' + ' . ($i - 1) . ' week')) : '-');
                                        
                                        // TAMPILKAN BARIS HANYA JIKA DATA JADWAL ADA (MENGHILANGKAN BARIS KOSONG "-")
                                        if ($jadwal_db): 
                                    ?>
                                    <tr class="hover:bg-gray-50 hover:shadow-sm transition-all duration-200 h-[42px]">
                                        <td class="px-3 py-2 text-center font-bold text-[#0b6b53]">Ke-<?= $i ?></td>
                                        <td class="px-3 py-2 text-center">
                                            <span class="px-2 py-0.5 bg-pink-100 text-pink-700 rounded-full font-bold text-[11px] uppercase"><?= $jadwal_db['hari'] ?></span>
                                        </td>
                                        <td class="px-3 py-2 text-center text-gray-600"><?= $tgl_pertemuan ?></td>
                                        <td class="px-3 py-2 text-center"><?= date('H.i', strtotime($jadwal_db['jam_mulai'])) ?> - <?= date('H.i', strtotime($jadwal_db['jam_selesai'])) ?></td>
                                        <td class="px-3 py-2 text-center"><?= htmlspecialchars($jadwal_db['nama_mapel']) ?></td>
                                        <td class="px-3 py-2 text-center"><?= htmlspecialchars($jadwal_db['nama_guru']) ?></td>
                                        <td class="px-3 py-2 text-center flex justify-center gap-1.5">
                                            <a href="kelola_jadwal.php?action=edit_jadwal&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&id_jadwal=<?= $jadwal_db['id'] ?>&tab=pengaturan" class="w-6 h-6 bg-[#fef3c7] rounded flex items-center justify-center text-[#d97706] hover:bg-yellow-200 transition-all duration-200 active:scale-90 shadow-sm"><svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg></a>
                                            <button type="button" onclick="showConfirmModal('Hapus Jadwal', 'Apakah Anda yakin ingin menghapus jadwal ini?', function(){ window.location.href='kelola_jadwal.php?action=hapus&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&id_jadwal=<?= $jadwal_db['id'] ?>&tab=pengaturan'; }, true)" class="w-6 h-6 bg-[#fde2e2] rounded flex items-center justify-center text-[#e53e3e] hover:bg-red-200 transition-all duration-200 active:scale-90 shadow-sm"><svg class="w-3 h-3" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg></button>
                                        </td>
                                    </tr>
                                    <?php endif; endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <!-- TOMBOL SIMPAN BAWAH TABEL (TETAP DI TAB PENGATURAN) -->
                        <div class="p-3 border-t border-gray-100 flex justify-end bg-white shrink-0 rounded-b-xl">
                            <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=pengaturan&msg=<?= urlencode('Jadwal berhasil disimpan!') ?>" class="bg-[#0b6b53] text-white px-4 py-2 rounded-lg text-[12px] font-bold hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                                Simpan Jadwal
                            </a>
                        </div>
                    </div>

                <!-- ======================================================= -->
                <!-- KONTEN TAB: DAFTAR PERTEMUAN (READ-ONLY)                -->
                <!-- ======================================================= -->
                <?php else: ?>
                    <?php 
                    // PERBAIKAN: Tabel tetap muncul jika ada data jadwal di database, meskipun form periode belum diisi
                    if (empty($tgl_mulai_session) && $total_jadwal_db == 0): 
                    ?>
                        <div class="bg-yellow-50 border border-yellow-200 text-yellow-700 p-5 rounded-xl text-center shadow-md shrink-0">
                            <h3 class="font-bold text-[14px] mb-1">Periode Belum Diatur</h3>
                            <p class="text-[12px]">Silakan kembali ke tab <b>Pengaturan Jadwal</b> dan isi Tanggal Mulai serta Jumlah Pertemuan untuk melakukan Generate.</p>
                        </div>
                    <?php else: ?>
                        <!-- Info Box Daftar -->
                        <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] p-3 flex items-center justify-between mb-3 shrink-0">
                            <div class="flex items-center gap-3">
                                <div class="w-9 h-9 bg-[#e6f4f1] rounded-full flex items-center justify-center">
                                    <svg class="w-5 h-5 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                </div>
                                <div>
                                    <p class="text-[11px] font-bold text-gray-500 mb-0.5">Periode</p>
                                    <p class="font-bold text-[13px] text-gray-900">
                                        <?= !empty($tgl_mulai_session) ? date('d M Y', strtotime($tgl_mulai_session)) : '-' ?> - 
                                        <?= !empty($tgl_mulai_session) ? date('d M Y', strtotime($tgl_mulai_session . ' + ' . ($jml_pertemuan_session - 1) . ' week')) : '-' ?>
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-6 pr-4 border-l border-gray-200 pl-6">
                                <div class="text-center">
                                    <p class="text-[11px] font-bold text-gray-500 mb-0.5">Jumlah Pertemuan</p>
                                    <p class="text-[15px] font-extrabold text-gray-900"><?= $jml_pertemuan_session ?></p>
                                </div>
                                <div class="text-center">
                                    <p class="text-[11px] font-bold text-gray-500 mb-0.5">Sisa Pertemuan</p>
                                    <p class="text-[15px] font-extrabold text-gray-900"><?= max(0, $jml_pertemuan_session - $total_jadwal_db) ?></p>
                                </div>
                            </div>
                        </div>

                        <!-- Tabel Daftar Pertemuan -->
                        <div class="bg-white rounded-xl shadow-md border border-[#e6f4f1] mb-4">
                            <div class="p-3 border-b border-gray-100 shrink-0">
                                <h3 class="font-bold text-[14px] text-gray-900">Semua Jadwal</h3>
                            </div>
                            <div class="w-full">
                                <table class="w-full text-left border-collapse min-w-max">
                                    <thead class="bg-[#f0f7f4] text-[#0b6b53]">
                                        <tr>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center w-12">No</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center">Tanggal</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center">Hari</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center">Waktu</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center">Mata Pelajaran</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center">Guru Pengajar</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center">Sub Materi</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center">Status</th>
                                            <th class="px-3 py-2.5 font-bold text-[13px] text-center w-24">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                        <?php 
                                        for ($i = 1; $i <= $batas_loop; $i++): 
                                            // PERBAIKAN: Cek jika tgl_mulai_session kosong
                                            $current_date = !empty($tgl_mulai_session) ? date('Y-m-d', strtotime($tgl_mulai_session . ' + ' . ($i - 1) . ' week')) : '';
                                            $date_formatted = !empty($tgl_mulai_session) ? date('d M Y', strtotime($current_date)) : '-';
                                            
                                            // Filter berdasarkan mapel_id
                                            $q_daftar = mysqli_query($koneksi, "SELECT j.*, m.nama_mapel, u.nama as nama_guru FROM jadwal j JOIN mata_pelajaran m ON j.mapel_id = m.id JOIN users u ON j.guru_id = u.id WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.pertemuan_ke = $i");
                                            $d = mysqli_fetch_assoc($q_daftar);
                                            
                                            if ($d && !empty($d['tanggal'])) {
                                                $date_formatted = date('d M Y', strtotime($d['tanggal']));
                                            }
                                            
                                            // Hanya tampilkan baris jika data jadwal ada (Baris kosong dihilangkan)
                                            if($d): 
                                        ?>
                                        <tr class="hover:bg-gray-50 hover:shadow-sm transition-all duration-200 h-[42px]">
                                            <td class="px-3 py-2 text-center"><?= $i ?></td>
                                            <td class="px-3 py-2 text-center"><?= $date_formatted ?></td>
                                            <td class="px-3 py-2 text-center text-gray-700"><?= $d['hari'] ?></td>
                                            <td class="px-3 py-2 text-center"><?= date('H.i', strtotime($d['jam_mulai'])) ?> - <?= date('H.i', strtotime($d['jam_selesai'])) ?></td>
                                            <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['nama_mapel']) ?></td>
                                            <td class="px-3 py-2 text-center"><?= htmlspecialchars($d['nama_guru']) ?></td>
                                            <td class="px-3 py-2 text-center text-[11px]"><?= !empty($d['sub_materi']) ? htmlspecialchars($d['sub_materi']) : '-' ?></td>
                                            <td class="px-3 py-2 text-center">
                                                <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= get_status_badge($d['status']) ?> shadow-sm">
                                                    <?= $d['status'] ?>
                                                </span>
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <a href="kelola_jadwal.php?action=detail_jadwal&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&id_jadwal=<?= $d['id'] ?>&tab=daftar" class="bg-[#0b6b53] text-white px-3 py-1 rounded font-bold text-[11px] hover:bg-[#08533f] transition-all duration-200 shadow-sm active:scale-95">Detail</a>
                                            </td>
                                        </tr>
                                        <?php endif; endfor; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 3: FORM TAMBAH / EDIT JADWAL                       -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'form_jadwal' || $action == 'edit_jadwal'): 
                $pertemuan_url = isset($_GET['pertemuan']) ? (int)$_GET['pertemuan'] : 1;
                $is_readonly = ($tab == 'daftar' && $action == 'edit_jadwal'); // Kunci form jika dari tab Daftar
                
                $prev_mapel = '';
                $prev_guru = '';
                $prev_jam_mulai = '14:00';
                $prev_jam_selesai = '16:00';
                
                if ($action == 'form_jadwal' && $pertemuan_url > 1) {
                    $prev_pertemuan = $pertemuan_url - 1;
                    $q_prev = mysqli_query($koneksi, "SELECT * FROM jadwal WHERE kelas_id = $kelas_id AND mapel_id = $mapel_id AND pertemuan_ke = $prev_pertemuan");
                    if (mysqli_num_rows($q_prev) > 0) {
                        $d_prev = mysqli_fetch_assoc($q_prev);
                        $prev_mapel = $d_prev['mapel_id'];
                        $prev_guru = $d_prev['guru_id'];
                        $prev_jam_mulai = $d_prev['jam_mulai'];
                        $prev_jam_selesai = $d_prev['jam_selesai'];
                    }
                } else if ($action == 'form_jadwal' && $pertemuan_url == 1) {
                    $prev_guru = $default_guru_id;
                }

                // Ambil Info Kelas untuk ditampilkan di form
                $kelas_info = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT k.*, r.nama_ruangan, r.keterangan FROM kelas k LEFT JOIN ruangan r ON k.ruangan_id = r.id WHERE k.id = $kelas_id"));
            ?>
            <div class="max-w-3xl mx-auto w-full pb-10">
                <!-- Header Form -->
                <div class="flex items-center gap-3 mb-4 shrink-0">
                    <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=pengaturan" class="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight"><?= $action == 'edit_jadwal' ? 'Edit Jadwal' : 'Tambah Jadwal' ?></h1>
                        <p class="text-[12px] font-medium text-gray-600">Lengkapi form jadwal di bawah ini</p>
                    </div>
                </div>

                <!-- Card Form -->
                <div class="bg-white p-5 rounded-xl shadow-xl border border-[#e6f4f1] transition-shadow duration-300">
                    <form method="POST" action="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=<?= $tab ?>&id_jadwal=<?= $id_jadwal ?>">
                        <input type="hidden" name="simpan_jadwal" value="1">
                        <input type="hidden" name="current_tab" value="<?= $tab ?>">
                        
                        <!-- HIDDEN INPUTS (Hanya untuk mode Tambah) -->
                        <?php if ($action == 'form_jadwal'): ?>
                            <input type="hidden" name="hari" value="Senin">
                            <input type="hidden" name="pertemuan_ke" value="<?= $pertemuan_url ?>">
                            <input type="hidden" name="jam_mulai" value="<?= $prev_jam_mulai ?>">
                            <input type="hidden" name="jam_selesai" value="<?= $prev_jam_selesai ?>">
                        <?php endif; ?>

                        <div class="grid grid-cols-2 gap-4">
                            <!-- Field Kelas & Ruangan (Readonly) -->
                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Nama Kelas</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-[13px] text-gray-500 outline-none" readonly value="<?= htmlspecialchars($kelas_info['nama_kelas']) ?>">
                            </div>
                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Ruangan (Otomatis)</label>
                                <input type="text" class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-[13px] text-gray-500 outline-none" readonly value="<?= htmlspecialchars($kelas_info['nama_ruangan']) ?> (<?= htmlspecialchars($kelas_info['keterangan']) ?>)">
                            </div>

                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Mata Pelajaran</label>
                                <?php if ($mapel_id > 0): 
                                    $nama_mapel_lock = '-';
                                    $q_mp_lock = mysqli_query($koneksi, "SELECT nama_mapel FROM mata_pelajaran WHERE id = $mapel_id");
                                    if($d_mp_lock = mysqli_fetch_assoc($q_mp_lock)) $nama_mapel_lock = $d_mp_lock['nama_mapel'];
                                ?>
                                    <input type="text" class="w-full px-3 py-2 border border-gray-200 bg-gray-100 rounded-lg text-[13px] text-gray-500 outline-none cursor-not-allowed" readonly value="<?= htmlspecialchars($nama_mapel_lock) ?>">
                                    <input type="hidden" name="mapel_id" value="<?= $mapel_id ?>">
                                <?php else: ?>
                                    <select name="mapel_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all <?= $is_readonly ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' ?>" <?= $is_readonly ? 'disabled' : '' ?> required>
                                        <option value="">Pilih Mapel</option>
                                        <?php 
                                        mysqli_data_seek($q_mapel, 0);
                                        while($m = mysqli_fetch_assoc($q_mapel)): 
                                            $selected = '';
                                            if ($action == 'edit_jadwal' && $data_edit['mapel_id'] == $m['id']) $selected = 'selected';
                                            if ($action == 'form_jadwal' && $prev_mapel == $m['id']) $selected = 'selected';
                                        ?>
                                            <option value="<?= $m['id'] ?>" <?= $selected ?>><?= htmlspecialchars($m['nama_mapel']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                <?php endif; ?>
                            </div>

                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Guru Pengajar</label>
                                <select name="guru_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all <?= $is_readonly ? 'bg-gray-100 text-gray-500 cursor-not-allowed' : '' ?>" <?= $is_readonly ? 'disabled' : '' ?> required>
                                    <option value="">Pilih Guru</option>
                                    <?php 
                                    mysqli_data_seek($q_guru, 0);
                                    while($g = mysqli_fetch_assoc($q_guru)): 
                                        $selected = '';
                                        if ($action == 'edit_jadwal' && $data_edit['guru_id'] == $g['id']) $selected = 'selected';
                                        if ($action == 'form_jadwal' && $prev_guru == $g['id']) $selected = 'selected';
                                    ?>
                                        <option value="<?= $g['id'] ?>" <?= $selected ?>><?= htmlspecialchars($g['nama']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Tambahan Field yang bisa diedit saat mode edit -->
                            <?php if ($action == 'edit_jadwal'): ?>
                                <div>
                                    <label class="block font-semibold text-[13px] text-gray-700 mb-1">Pertemuan Ke-</label>
                                    <input type="number" name="pertemuan_ke" value="<?= htmlspecialchars($data_edit['pertemuan_ke']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" required>
                                </div>
                                <div>
                                    <label class="block font-semibold text-[13px] text-gray-700 mb-1">Hari</label>
                                    <select name="hari" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" required>
                                        <?php foreach(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu', 'Minggu'] as $h): ?>
                                            <option value="<?= $h ?>" <?= $data_edit['hari'] == $h ? 'selected' : '' ?>><?= $h ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div>
                                    <label class="block font-semibold text-[13px] text-gray-700 mb-1">Jam Mulai</label>
                                    <input type="time" name="jam_mulai" value="<?= htmlspecialchars($data_edit['jam_mulai']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" required>
                                </div>
                                <div>
                                    <label class="block font-semibold text-[13px] text-gray-700 mb-1">Jam Selesai</label>
                                    <input type="time" name="jam_selesai" value="<?= htmlspecialchars($data_edit['jam_selesai']) ?>" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" required>
                                </div>
                                <div class="col-span-2">
                                    <label class="block font-semibold text-[13px] text-gray-700 mb-1">Status Pelaksanaan</label>
                                    <select name="status_jadwal" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all">
                                        <option value="Belum Dimulai" <?= $data_edit['status'] == 'Belum Dimulai' ? 'selected' : '' ?>>Belum Dimulai</option>
                                        <option value="Sedang Berlangsung" <?= $data_edit['status'] == 'Sedang Berlangsung' ? 'selected' : '' ?>>Sedang Berlangsung</option>
                                        <option value="Selesai" <?= $data_edit['status'] == 'Selesai' ? 'selected' : '' ?>>Selesai</option>
                                    </select>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="flex gap-3 pt-4 mt-5 border-t border-gray-100">
                            <?php if (!$is_readonly): ?>
                                <button type="submit" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[13px] py-2 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">
                                    Simpan Jadwal
                                </button>
                            <?php endif; ?>
                            <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=pengaturan" class="px-6 py-2 bg-gray-100 text-gray-700 font-semibold text-[13px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95 text-center <?= $is_readonly ? 'flex-1' : '' ?>">
                                <?= $is_readonly ? 'Tutup Detail' : 'Batal' ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- ======================================================= -->
            <!-- VIEW 4: DETAIL JADWAL (BARU)                            -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'detail_jadwal'): 
                $detail = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT j.*, m.nama_mapel, u.nama as nama_guru, k.nama_kelas FROM jadwal j JOIN mata_pelajaran m ON j.mapel_id = m.id JOIN users u ON j.guru_id = u.id JOIN kelas k ON j.kelas_id = k.id WHERE j.id = $id_jadwal"));
            ?>
            <div class="max-w-3xl mx-auto w-full pb-10">
                <!-- Header Form -->
                <div class="flex items-center gap-3 mb-4 shrink-0">
                    <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=daftar" class="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Detail Jadwal Pertemuan</h1>
                        <p class="text-[12px] font-medium text-gray-600">Informasi lengkap jadwal pertemuan</p>
                    </div>
                </div>

                <!-- Card Detail -->
                <div class="bg-white p-6 rounded-xl shadow-lg border border-[#e6f4f1]">
                    <div class="grid grid-cols-2 gap-y-4 gap-x-8 text-[14px]">
                        <div>
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Kelas</p>
                            <p class="font-bold text-gray-900"><?= htmlspecialchars($detail['nama_kelas']) ?></p>
                        </div>
                        <div>
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Pertemuan Ke-</p>
                            <p class="font-bold text-gray-900"><?= $detail['pertemuan_ke'] ?></p>
                        </div>
                        <div>
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Mata Pelajaran</p>
                            <p class="font-bold text-gray-900"><?= htmlspecialchars($detail['nama_mapel']) ?></p>
                        </div>
                        <div>
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Guru Pengajar</p>
                            <p class="font-bold text-gray-900"><?= htmlspecialchars($detail['nama_guru']) ?></p>
                        </div>
                        <div>
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Hari</p>
                            <p class="font-bold text-gray-900"><?= htmlspecialchars($detail['hari']) ?></p>
                        </div>
                        <div>
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Waktu</p>
                            <p class="font-bold text-gray-900"><?= date('H.i', strtotime($detail['jam_mulai'])) ?> - <?= date('H.i', strtotime($detail['jam_selesai'])) ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Sub Materi</p>
                            <p class="font-semibold text-gray-900"><?= !empty($detail['sub_materi']) ? htmlspecialchars($detail['sub_materi']) : '-' ?></p>
                        </div>
                        <div class="col-span-2">
                            <p class="text-[12px] font-bold text-gray-500 uppercase tracking-wider mb-1">Status Pelaksanaan</p>
                            <span class="px-3 py-1 rounded-md text-[12px] font-bold <?= get_status_badge($detail['status']) ?> shadow-sm">
                                <?= $detail['status'] ?>
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-end pt-6 mt-6 border-t border-gray-100">
                        <a href="kelola_jadwal.php?action=atur&kelas_id=<?= $kelas_id ?>&mapel_id=<?= $mapel_id ?>&tab=daftar" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold text-[13px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95 shadow-sm">
                            Kembali
                        </a>
                    </div>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 5: FORM TAMBAH JADWAL BARU (DARI LIST KELAS)       -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'form_tambah_jadwal'): ?>
            <div class="max-w-3xl mx-auto w-full pb-10">
                <!-- Header Form -->
                <div class="flex items-center gap-3 mb-4 shrink-0">
                    <a href="kelola_jadwal.php" class="w-8 h-8 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[20px] font-bold text-gray-900 leading-tight">Tambah Jadwal Baru</h1>
                        <p class="text-[12px] font-medium text-gray-600">Lengkapi form jadwal di bawah ini</p>
                    </div>
                </div>

                <!-- Card Form -->
                <div class="bg-white p-5 rounded-xl shadow-xl border border-[#e6f4f1] transition-shadow duration-300">
                    <form method="POST" action="kelola_jadwal.php">
                        <input type="hidden" name="simpan_jadwal_baru" value="1">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <!-- Nama Kelas (Dropdown) -->
                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Nama Kelas</label>
                                <select name="kelas_id" id="selectKelas" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" required>
                                    <option value="">Pilih Kelas</option>
                                    <?php 
                                    // Gunakan $q_all_kelas agar kelas yang baru dibuat di Kelola Kelas tetap muncul di dropdown ini
                                    mysqli_data_seek($q_all_kelas, 0); 
                                    $listed_kelas = [];
                                    while($k = mysqli_fetch_assoc($q_all_kelas)): 
                                        if(!in_array($k['id_kelas'], $listed_kelas)):
                                            $listed_kelas[] = $k['id_kelas'];
                                    ?>
                                        <option value="<?= $k['id_kelas'] ?>" data-ruangan="<?= htmlspecialchars($k['nama_ruangan']) ?> (<?= htmlspecialchars($k['keterangan']) ?>)" <?= $kelas_id == $k['id_kelas'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($k['nama_kelas']) ?>
                                        </option>
                                    <?php endif; endwhile; ?>
                                </select>
                            </div>

                            <!-- Ruangan (Readonly, mengikuti kelas) -->
                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Ruangan (Otomatis)</label>
                                <input type="text" id="inputRuangan" class="w-full px-3 py-2 border border-gray-200 bg-gray-50 rounded-lg text-[13px] text-gray-500 outline-none" readonly placeholder="Pilih kelas untuk melihat ruangan">
                            </div>

                            <!-- Mata Pelajaran -->
                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Mata Pelajaran</label>
                                <select name="mapel_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" required>
                                    <option value="">Pilih Mapel</option>
                                    <?php mysqli_data_seek($q_mapel, 0); while($m = mysqli_fetch_assoc($q_mapel)): ?>
                                        <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['nama_mapel']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <!-- Guru Pengajar -->
                            <div class="col-span-2">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1">Guru Pengajar</label>
                                <select name="guru_id" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" required>
                                    <option value="">Pilih Guru</option>
                                    <?php mysqli_data_seek($q_guru, 0); while($g = mysqli_fetch_assoc($q_guru)): ?>
                                        <option value="<?= $g['id'] ?>"><?= htmlspecialchars($g['nama']) ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                        </div>

                        <div class="flex gap-3 pt-4 mt-5 border-t border-gray-100">
                            <button type="submit" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[13px] py-2 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">
                                Simpan Jadwal
                            </button>
                            <a href="kelola_jadwal.php" class="px-6 py-2 bg-gray-100 text-gray-700 font-semibold text-[13px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            
            <?php endif; ?>

        </main>
    </div>

    <!-- MODAL KONFIRMASI KUSTOM -->
    <div id="confirmModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeConfirmModal()"></div>
        <div class="relative bg-white rounded-xl shadow-2xl p-6 w-[400px] text-center transform scale-100 transition-transform border border-gray-100">
            <div id="confirmIconBg" class="w-16 h-16 bg-[#e6f4f1] rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                <svg id="confirmIcon" class="w-8 h-8 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 id="confirmTitle" class="text-xl font-bold text-gray-900 mb-2">Konfirmasi</h3>
            <p id="confirmMessage" class="text-gray-500 font-medium mb-6 text-[14px]">Apakah Anda yakin?</p>
            <div class="flex gap-3">
                <button id="btnCancel" onclick="closeConfirmModal()" class="flex-1 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-all active:scale-95 shadow-sm">Batal</button>
                <!-- PERBAIKAN: Menambahkan fungsi closeConfirmModal jika confirmCallback kosong (untuk alert) -->
                <button id="btnConfirmAction" onclick="if(typeof confirmCallback === 'function') { confirmCallback(); } else { closeConfirmModal(); }" class="flex-1 bg-[#0b6b53] text-white font-bold py-2.5 rounded-lg hover:bg-[#08533f] transition-all active:scale-95 shadow-md">Ya, Lanjutkan</button>
            </div>
        </div>
    </div>

    <script>
        function filterKelas() {
            let searchText = document.getElementById('searchKelas').value.toLowerCase();
            let filterMapel = document.getElementById('filterMapel').value.toLowerCase();
            let cards = document.getElementsByClassName('kelas-card');
            
            for (let i = 0; i < cards.length; i++) {
                let title = cards[i].querySelector('h3').innerText.toLowerCase();
                let mapelList = cards[i].getAttribute('data-mapel') || '';
                
                let matchSearch = title.includes(searchText);
                let matchMapel = filterMapel === '' || mapelList.includes(filterMapel);
                
                if (matchSearch && matchMapel) {
                    cards[i].style.display = "";
                } else {
                    cards[i].style.display = "none";
                }
            }
        }

        // JAVASCRIPT UNTUK MENGHILANGKAN NOTIFIKASI ERROR LOKAL (2 DETIK)
        document.addEventListener("DOMContentLoaded", function() {
            const notifError = document.getElementById('notif-error');
            if (notifError) {
                // Jika error berasal dari simpan jadwal manual, otomatis buka form manual
                if(notifError.innerText.includes('Pertemuan') || notifError.innerText.includes('tanggal ini')) {
                    document.getElementById('manualFormContainer').classList.remove('hidden');
                }

                setTimeout(() => {
                    notifError.style.opacity = '0';
                    setTimeout(() => notifError.remove(), 500);
                }, 2000); // 2000ms = 2 Detik
            }

            // JS untuk auto-fill ruangan pada form tambah jadwal
            const selectKelas = document.getElementById('selectKelas');
            const inputRuangan = document.getElementById('inputRuangan');
            if(selectKelas) {
                selectKelas.addEventListener('change', function() {
                    const selectedOption = this.options[this.selectedIndex];
                    const ruangan = selectedOption.getAttribute('data-ruangan');
                    inputRuangan.value = ruangan || '';
                });
                // Trigger on load if already selected
                if(selectKelas.value) {
                    const selectedOption = selectKelas.options[selectKelas.selectedIndex];
                    inputRuangan.value = selectedOption.getAttribute('data-ruangan') || '';
                }
            }
        });

        // =======================================================
        // FUNGSI MODAL KONFIRMASI KUSTOM
        // =======================================================
        let confirmCallback = null;

        function showConfirmModal(title, message, callback, isDanger = true, isAlert = false) {
            document.getElementById('confirmTitle').innerText = title;
            document.getElementById('confirmMessage').innerText = message;
            confirmCallback = callback;

            const btn = document.getElementById('btnConfirmAction');
            const btnCancel = document.getElementById('btnCancel');
            const iconBg = document.getElementById('confirmIconBg');
            const icon = document.getElementById('confirmIcon');
            
            if (isAlert) {
                btnCancel.classList.add('hidden');
                btn.innerText = "OK";
                btn.className = "w-full bg-[#0b6b53] text-white font-bold py-2.5 rounded-lg hover:bg-[#08533f] transition-all active:scale-95 shadow-md";
                iconBg.className = "w-16 h-16 bg-[#e6f4f1] rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm";
                icon.className = "w-8 h-8 text-[#0b6b53]";
                icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
            } else {
                btnCancel.classList.remove('hidden');
                btn.innerText = "Ya, Lanjutkan";
                
                if (isDanger) {
                    btn.className = "flex-1 bg-red-500 text-white font-bold py-2.5 rounded-lg hover:bg-red-600 transition-all active:scale-95 shadow-md";
                    iconBg.className = "w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm";
                    icon.className = "w-8 h-8 text-red-600";
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>';
                } else {
                    btn.className = "flex-1 bg-[#0b6b53] text-white font-bold py-2.5 rounded-lg hover:bg-[#08533f] transition-all active:scale-95 shadow-md";
                    iconBg.className = "w-16 h-16 bg-[#e6f4f1] rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm";
                    icon.className = "w-8 h-8 text-[#0b6b53]";
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>';
                }
            }

            document.getElementById('confirmModal').classList.remove('hidden');
        }

        function closeConfirmModal() {
            document.getElementById('confirmModal').classList.add('hidden');
            confirmCallback = null;
        }
    </script>
</body>
</html>