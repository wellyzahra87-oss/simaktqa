<?php 
include 'koneksi.php'; 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

// ==========================================
// AUTO-MIGRATE: Menambahkan kolom guru_id jika belum ada
// ==========================================
$cek_kolom = mysqli_query($koneksi, "SHOW COLUMNS FROM kelas LIKE 'guru_id'");
if (mysqli_num_rows($cek_kolom) == 0) {
    mysqli_query($koneksi, "ALTER TABLE kelas ADD COLUMN guru_id INT(11) NULL");
}

// ==========================================
// ROUTING & PARAMETER
// ==========================================
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'kelas'; 
$action = isset($_GET['action']) ? $_GET['action'] : 'list';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$error_msg = '';

// ==========================================
// 1. LOGIKA HAPUS DATA
// ==========================================
if ($action == 'delete' && $id > 0) {
    if ($tab == 'kelas') {
        $delete = mysqli_query($koneksi, "DELETE FROM kelas WHERE id = $id");
    } else {
        $delete = mysqli_query($koneksi, "DELETE FROM ruangan WHERE id = $id");
    }
    
    if ($delete) {
        // PERBAIKAN: Menggunakan parameter 'msg' agar notif spesifik dan tidak dobel
        header("Location: kelola_kelas.php?tab=$tab&msg=" . urlencode('Data berhasil dihapus!'));
    } else {
        header("Location: kelola_kelas.php?tab=$tab&msg=" . urlencode("Gagal menghapus data karena terhubung dengan data lain.") . "&type=error");
    }
    exit;
}

// ==========================================
// 2. AMBIL DATA UNTUK EDIT
// ==========================================
$data_edit = [];
if ($action == 'edit' && $id > 0) {
    if ($tab == 'kelas') {
        $data_edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM kelas WHERE id = $id"));
    } else {
        $data_edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM ruangan WHERE id = $id"));
    }
}

// ==========================================
// 3. LOGIKA SIMPAN DATA (TAMBAH / EDIT)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($tab == 'kelas') {
        $nama_kelas = mysqli_real_escape_string($koneksi, $_POST['nama_kelas']);
        $ruangan_id = (int)$_POST['ruangan_id'];
        $kapasitas  = (int)$_POST['kapasitas'];
        $status     = $_POST['status'];

        // Validasi 1: Cek apakah ruangan yang dipilih tersedia
        $q_cek_ruang = mysqli_query($koneksi, "SELECT status FROM ruangan WHERE id = $ruangan_id");
        $d_cek_ruang = mysqli_fetch_assoc($q_cek_ruang);
        if ($d_cek_ruang && $d_cek_ruang['status'] == 'Tidak Tersedia') {
            if (!$action == 'edit' || ($action == 'edit' && $data_edit['ruangan_id'] != $ruangan_id)) {
                $error_msg = "Gagal! Ruangan tersebut berstatus 'Tidak Tersedia'.";
            }
        }

        // Validasi 2: Cek kapasitas jika edit
        if ($id > 0 && empty($error_msg)) {
            $jml_santri = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM santri WHERE kelas_id = $id"))['c'];
            if ($kapasitas < $jml_santri) {
                $error_msg = "Gagal! Kapasitas ($kapasitas) tidak boleh kurang dari jumlah santri saat ini ($jml_santri).";
            }
        }

        if (empty($error_msg)) {
            if ($id > 0) {
                $query = "UPDATE kelas SET nama_kelas='$nama_kelas', ruangan_id=$ruangan_id, kapasitas=$kapasitas, status='$status' WHERE id=$id";
                $pesan_sukses = 'Data berhasil diubah!';
            } else {
                $query = "INSERT INTO kelas (nama_kelas, ruangan_id, kapasitas, status) VALUES ('$nama_kelas', $ruangan_id, $kapasitas, '$status')";
                $pesan_sukses = 'Data berhasil ditambahkan!';
            }
        }
    } else {
        $nama_ruangan = mysqli_real_escape_string($koneksi, $_POST['nama_ruangan']);
        $keterangan   = mysqli_real_escape_string($koneksi, $_POST['keterangan']);
        $status       = $_POST['status'];

        if ($id > 0) {
            $query = "UPDATE ruangan SET nama_ruangan='$nama_ruangan', keterangan='$keterangan', status='$status' WHERE id=$id";
            $pesan_sukses = 'Data berhasil diubah!';
        } else {
            $query = "INSERT INTO ruangan (nama_ruangan, keterangan, status) VALUES ('$nama_ruangan', '$keterangan', '$status')";
            $pesan_sukses = 'Data berhasil ditambahkan!';
        }
    }

    if (empty($error_msg)) {
        if (mysqli_query($koneksi, $query)) {
            // PERBAIKAN: Menggunakan parameter 'msg' agar notif spesifik dan tidak dobel
            header("Location: kelola_kelas.php?tab=$tab&msg=" . urlencode($pesan_sukses));
            exit;
        } else {
            $error_msg = "Gagal menyimpan data: " . mysqli_error($koneksi);
        }
    }
}

// Ambil data ruangan dan guru untuk dropdown
$current_ruangan_id = ($action == 'edit' && $tab == 'kelas' && isset($data_edit['ruangan_id'])) ? (int)$data_edit['ruangan_id'] : 0;
$q_ruangan = mysqli_query($koneksi, "SELECT * FROM ruangan WHERE status = 'Tersedia' OR id = $current_ruangan_id ORDER BY nama_ruangan ASC");

$q_all_guru = mysqli_query($koneksi, "SELECT id, nama FROM users WHERE role='Guru' ORDER BY nama ASC");

// ==========================================
// 4. SETUP PAGINATION & AMBIL DATA
// ==========================================
$limit = 10;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

$data_tabel = [];
$total_pages = 0;

if ($action == 'list') {
    if ($tab == 'kelas') {
        $where_count = "WHERE 1=1";
        if ($search) $where_count .= " AND nama_kelas LIKE '%$search%'";
        if (!empty($status_filter)) $where_count .= " AND status = '$status_filter'";
        
        $total_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM kelas $where_count"))['c'];
        
        $where = "WHERE 1=1";
        if ($search) $where .= " AND k.nama_kelas LIKE '%$search%'";
        if (!empty($status_filter)) $where .= " AND k.status = '$status_filter'";
        
        $query = mysqli_query($koneksi, "
            SELECT k.*, r.nama_ruangan, r.keterangan,
            (SELECT COUNT(*) FROM santri s WHERE s.kelas_id = k.id) as jumlah_santri
            FROM kelas k
            LEFT JOIN ruangan r ON k.ruangan_id = r.id
            $where ORDER BY k.id ASC LIMIT $limit OFFSET $offset
        ");
    } else {
        $where_count = "WHERE 1=1";
        if ($search) $where_count .= " AND (nama_ruangan LIKE '%$search%' OR keterangan LIKE '%$search%')";
        if (!empty($status_filter)) $where_count .= " AND status = '$status_filter'";
        $total_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM ruangan $where_count"))['c'];
        
        $where = "WHERE 1=1";
        if ($search) $where .= " AND (nama_ruangan LIKE '%$search%' OR keterangan LIKE '%$search%')";
        if (!empty($status_filter)) $where .= " AND status = '$status_filter'";
        
        $query = mysqli_query($koneksi, "SELECT * FROM ruangan $where ORDER BY id ASC LIMIT $limit OFFSET $offset");
    }
    
    $total_pages = ceil($total_data / $limit);
    while($row = mysqli_fetch_assoc($query)) $data_tabel[] = $row;
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas - TQA Darussalam</title>
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
    $current_page = 'kelola_kelas'; 
    include 'sidebar.php'; 
    ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <?php include 'header.php'; ?>
        
        <!-- Padding Utama Dikurangi agar Tabel Lebih Panjang -->
        <main class="flex-1 flex flex-col px-6 py-4 <?= ($action == 'list') ? 'overflow-hidden' : 'overflow-y-auto' ?>">
            
            <!-- Notifikasi Error POST (Lokal) -->
            <div class="shrink-0">
                <?php if(!empty($error_msg)): ?>
                    <div id="notif-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg mb-2 font-semibold text-sm shadow-md transition-opacity duration-500"><?= $error_msg ?></div>
                <?php endif; ?>
            </div>

            <!-- ======================================================= -->
            <!-- TAMPILAN 1: TABEL KELAS / RUANGAN                       -->
            <!-- ======================================================= -->
            <?php if ($action == 'list'): ?>
                <!-- Header Judul -->
                <div class="flex items-center gap-3 mb-3 shrink-0">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-1V1h-2v2H8V1H6v2H5c-1.11 0-1.99.9-1.99 2L3 19c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm0 16H5V8h14v11zM7 10h5v5H7z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Kelola Kelas</h1>
                        <p class="text-[13px] font-medium text-gray-600">Kelola kelas pelajaran TQA Darussalam.</p>
                    </div>
                </div>

                <!-- Tabs -->
                <div class="flex gap-2 mb-3 shrink-0 bg-white p-1 rounded-xl shadow-md border border-gray-100 w-fit">
                    <a href="kelola_kelas.php?tab=kelas" class="px-5 py-1.5 rounded-lg font-bold text-[13px] transition-all duration-200 active:scale-95 <?= $tab == 'kelas' ? 'bg-[#0b6b53] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Kelas</a>
                    <a href="kelola_kelas.php?tab=ruangan" class="px-5 py-1.5 rounded-lg font-bold text-[13px] transition-all duration-200 active:scale-95 <?= $tab == 'ruangan' ? 'bg-[#0b6b53] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Ruangan</a>
                </div>

                <!-- Search, Filter Status & Tombol Tambah -->
                <div class="flex justify-between items-center mb-3 shrink-0 gap-4">
                    <form method="GET" action="kelola_kelas.php" class="flex flex-wrap items-center gap-3 flex-1 max-w-[800px]">
                        <input type="hidden" name="tab" value="<?= $tab ?>">
                        
                        <div class="flex flex-1 gap-2 min-w-[200px]">
                            <div class="relative flex-1">
                                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                    <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                                </div>
                                <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" placeholder="Cari <?= ucfirst($tab) ?>...">
                            </div>
                            <!-- TOMBOL CARI DITAMBAHKAN -->
                            <button type="submit" class="bg-[#0b6b53] text-white px-5 py-2 rounded-lg text-[13px] font-semibold hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 whitespace-nowrap">
                                Cari
                            </button>
                        </div>

                        <select name="status" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white w-44 text-[13px] font-medium cursor-pointer shadow-sm transition-all">
                            <option value="">Semua Status</option>
                            <option value="Tersedia" <?= $status_filter == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                            <option value="Tidak Tersedia" <?= $status_filter == 'Tidak Tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
                        </select>
                    </form>

                    <a href="kelola_kelas.php?tab=<?= $tab ?>&action=add" class="bg-[#0b6b53] text-white px-4 py-2 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 whitespace-nowrap">
                        + Tambah <?= $tab ?>
                    </a>
                </div>

                <!-- Tabel -->
                <div class="bg-white rounded-2xl shadow-lg border border-[#e6f4f1] flex-1 min-h-0 flex flex-col transition-shadow duration-300">
                    <div class="flex-1 overflow-auto">
                        <table class="w-full min-w-max text-left border-collapse">
                            <thead class="bg-[#f0f7f4] text-[#0b6b53] sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-16">NO</th>
                                    
                                    <?php if ($tab == 'kelas'): ?>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Nama kelas</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Kapasitas</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Jumlah Santri</th>
                                    <?php else: ?>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Nama Ruangan</th>
                                        <th class="px-4 py-3 font-bold text-[13px] text-center">Keterangan</th>
                                    <?php endif; ?>

                                    <th class="px-4 py-3 font-bold text-[13px] text-center">Status</th>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                <?php 
                                $no = $offset + 1; 
                                $rows_rendered = 0;
                                if(!empty($data_tabel)):
                                    foreach($data_tabel as $row): 
                                        $rows_rendered++;
                                ?>
                                <tr class="hover:bg-gray-50 hover:shadow-sm transition-all duration-200 h-[45px]">
                                    <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                                    
                                    <?php if ($tab == 'kelas'): ?>
                                        <td class="px-4 py-2 text-center font-semibold"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                        <td class="px-4 py-2 text-center"><?= htmlspecialchars($row['kapasitas']) ?></td>
                                        <td class="px-4 py-2 text-center"><?= htmlspecialchars($row['jumlah_santri']) ?></td>
                                    <?php else: ?>
                                        <td class="px-4 py-2 text-center font-semibold"><?= htmlspecialchars($row['nama_ruangan']) ?></td>
                                        <td class="px-4 py-2 text-center text-gray-600"><?= htmlspecialchars($row['keterangan'] ?? '-') ?></td>
                                    <?php endif; ?>

                                    <td class="px-4 py-2 text-center">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= get_status_badge($row['status']) ?> w-full block shadow-sm">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <div class="flex justify-center gap-2">
                                            <a href="kelola_kelas.php?tab=<?= $tab ?>&action=edit&id=<?= $row['id'] ?>" class="w-7 h-7 bg-[#fef3c7] rounded flex items-center justify-center text-[#d97706] hover:bg-yellow-200 transition-all duration-200 active:scale-90 shadow-sm" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </a>
                                            <!-- TOMBOL HAPUS MENGARAH KE MODAL JS -->
                                            <button onclick="confirmDelete('kelola_kelas.php?tab=<?= $tab ?>&action=delete&id=<?= $row['id'] ?>')" class="w-7 h-7 bg-[#fde2e2] rounded flex items-center justify-center text-[#e53e3e] hover:bg-red-200 transition-all duration-200 active:scale-90 shadow-sm" title="Hapus">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; else: ?>
                                    <tr><td colspan="6" class="p-4 text-center text-gray-500 text-sm h-[45px]">Data tidak ditemukan.</td></tr>
                                <?php endif; 

                                if ($rows_rendered < 10 && $rows_rendered > 0) {
                                    $col_count = 6; 
                                    for ($i = $rows_rendered; $i < 10; $i++) {
                                        echo '<tr class="h-[45px]">';
                                        for ($j = 0; $j < $col_count; $j++) {
                                            echo '<td class="px-4 py-2 text-center text-gray-400"></td>';
                                        }
                                        echo '</tr>';
                                    }
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="flex justify-center items-center gap-1.5 mt-3 shrink-0">
                    <a href="kelola_kelas.php?tab=<?= $tab ?>&search=<?= $search ?>&status=<?= $status_filter ?>&page=<?= max(1, $page - 1) ?>" class="w-7 h-7 border border-gray-300 rounded flex items-center justify-center text-gray-500 hover:bg-gray-100 text-[12px] <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?> transition-all active:scale-95"><</a>
                    <?php for($i = 1; $i <= $total_pages; $i++): ?>
                        <a href="kelola_kelas.php?tab=<?= $tab ?>&search=<?= $search ?>&status=<?= $status_filter ?>&page=<?= $i ?>" class="w-7 h-7 border border-gray-300 rounded flex items-center justify-center font-bold text-[12px] <?= $page == $i ? 'bg-gray-200 text-gray-800 shadow-sm' : 'bg-white text-gray-800 hover:bg-gray-50' ?> transition-all active:scale-95"><?= $i ?></a>
                    <?php endfor; ?>
                    <a href="kelola_kelas.php?tab=<?= $tab ?>&search=<?= $search ?>&status=<?= $status_filter ?>&page=<?= min($total_pages, $page + 1) ?>" class="w-7 h-7 border border-gray-300 rounded flex items-center justify-center text-gray-500 hover:bg-gray-100 text-[12px] <?= $page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?> transition-all active:scale-95">></a>
                </div>
                <?php endif; ?>

            <!-- ======================================================= -->
            <!-- TAMPILAN 2: FORM TAMBAH / EDIT                          -->
            <!-- ======================================================= -->
            <?php else: ?>
            <div class="max-w-3xl mx-auto w-full pb-10">
                <!-- Header Form -->
                <div class="flex items-center gap-3 mb-4 shrink-0">
                    <a href="kelola_kelas.php?tab=<?= $tab ?>" class="w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm active:scale-95">
                        <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-tight">
                            <?= $action == 'edit' ? 'Edit' : 'Tambah' ?> <?= ucfirst($tab) ?>
                        </h1>
                        <p class="text-[13px] font-medium text-gray-600">Lengkapi form di bawah ini</p>
                    </div>
                </div>

                <!-- Card Form -->
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-[#e6f4f1] transition-shadow duration-300">
                    <form method="POST" action="kelola_kelas.php?tab=<?= $tab ?>&action=<?= $action ?>&id=<?= $id ?>">
                        
                        <?php if ($tab == 'kelas'): ?>
                            <div class="mb-4">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Nama Kelas</label>
                                <input type="text" name="nama_kelas" value="<?= $action == 'edit' ? htmlspecialchars($data_edit['nama_kelas']) : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" placeholder="Contoh: MUTAWASSIT A" required>
                            </div>
                            
                            <div class="mb-4">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Ruangan</label>
                                <select name="ruangan_id" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all cursor-pointer" required>
                                    <option value="">Pilih Ruangan</option>
                                    <?php mysqli_data_seek($q_ruangan, 0); while($r = mysqli_fetch_assoc($q_ruangan)): ?>
                                        <option value="<?= $r['id'] ?>" <?= $action == 'edit' && $data_edit['ruangan_id'] == $r['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($r['nama_ruangan']) ?> - <?= htmlspecialchars($r['keterangan']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-4">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Kapasitas Santri</label>
                                <input type="number" name="kapasitas" value="<?= $action == 'edit' ? htmlspecialchars($data_edit['kapasitas']) : '20' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" placeholder="Contoh: 23" required>
                            </div>
                        <?php else: ?>
                            <div class="mb-4">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Nama Ruangan</label>
                                <input type="text" name="nama_ruangan" value="<?= $action == 'edit' ? htmlspecialchars($data_edit['nama_ruangan']) : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" placeholder="Contoh: R1" required>
                            </div>
                            <div class="mb-4">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Keterangan</label>
                                <input type="text" name="keterangan" value="<?= $action == 'edit' ? htmlspecialchars($data_edit['keterangan'] ?? '') : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" placeholder="Contoh: Dalam Masjid" required>
                            </div>
                        <?php endif; ?>

                        <div class="mb-6">
                            <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Status</label>
                            <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all cursor-pointer" required>
                                <option value="Tersedia" <?= $action == 'edit' && $data_edit['status'] == 'Tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                <option value="Tidak Tersedia" <?= $action == 'edit' && $data_edit['status'] == 'Tidak Tersedia' ? 'selected' : '' ?>>Tidak Tersedia</option>
                            </select>
                        </div>

                        <div class="flex gap-3 pt-4 border-t border-gray-100">
                            <button type="submit" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[13px] py-2.5 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">
                                Simpan Data
                            </button>
                            <a href="kelola_kelas.php?tab=<?= $tab ?>" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold text-[13px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- MODAL KONFIRMASI HAPUS -->
    <div id="deleteModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeDeleteModal()"></div>
        <div class="relative bg-white rounded-xl shadow-2xl p-6 w-[400px] text-center transform scale-100 transition-transform border border-gray-100">
            <div class="w-16 h-16 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4 shadow-sm">
                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            </div>
            <h3 class="text-xl font-bold text-gray-900 mb-2">Konfirmasi Hapus</h3>
            <p class="text-gray-500 font-medium mb-6 text-[14px]">Apakah Anda yakin ingin menghapus data ini secara permanen? Tindakan ini tidak dapat dibatalkan.</p>
            <div class="flex gap-3">
                <button onclick="closeDeleteModal()" class="flex-1 bg-gray-100 text-gray-700 font-bold py-2.5 rounded-lg hover:bg-gray-200 transition-all active:scale-95 shadow-sm">Batal</button>
                <a id="btnConfirmDelete" href="#" class="flex-1 bg-red-500 text-white font-bold py-2.5 rounded-lg hover:bg-red-600 transition-all active:scale-95 shadow-md">Ya, Hapus</a>
            </div>
        </div>
    </div>

    <!-- SCRIPT UNTUK MODAL HAPUS DAN NOTIFIKASI ERROR POST -->
    <script>
        // Fungsi untuk membuka Modal Hapus
        function confirmDelete(url) {
            document.getElementById('btnConfirmDelete').href = url;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        // Fungsi untuk menutup Modal Hapus
        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        // Notifikasi Error lokal (jika simpan gagal) hilang dalam 2 detik
        document.addEventListener("DOMContentLoaded", function() {
            const notifError = document.getElementById('notif-error');
            if (notifError) {
                setTimeout(() => {
                    notifError.style.opacity = '0';
                    setTimeout(() => notifError.remove(), 500);
                }, 2000); 
            }
        });
    </script>
</body>
</html>