<?php 
include 'koneksi.php'; 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

// --- MEMBUAT PATH ABSOLUT AGAR TIDAK 404 ---
$folder_path = dirname($_SERVER['PHP_SELF']);
$folder_path = str_replace('\\', '/', $folder_path);
$base_url = ($folder_path == '/' || $folder_path == '.') ? '' : $folder_path;
$link_form = $base_url . '/form_user.php';
$link_kelola = $base_url . '/kelola_user.php';

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'santri';
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$status_filter = isset($_GET['status']) ? mysqli_real_escape_string($koneksi, $_GET['status']) : '';
$kelas_filter = isset($_GET['kelas']) ? (int)$_GET['kelas'] : 0;

// --- LOGIKA HAPUS ---
if (isset($_GET['hapus'])) {
    $id_hapus = (int)$_GET['hapus'];
    $tipe_hapus = $_GET['tipe'];
    
    if ($tipe_hapus == 'santri') {
        $delete_query = "DELETE FROM santri WHERE id = $id_hapus";
        $pesan_sukses = 'Data Santri berhasil dihapus!';
    } else {
        $delete_query = "DELETE FROM users WHERE id = $id_hapus";
        $pesan_sukses = 'Data User berhasil dihapus!';
    }
    
    if (mysqli_query($koneksi, $delete_query)) {
        // PERBAIKAN: Mengirim pesan custom via URL agar HANYA 1 notifikasi yang muncul dari header.php
        header("Location: $link_kelola?tab=$tab&msg=" . urlencode($pesan_sukses));
    } else {
        header("Location: $link_kelola?tab=$tab&msg=" . urlencode("Gagal menghapus data") . "&type=error");
    }
    exit;
}

// --- PAGINATION SETUP ---
$limit = 10;
$page = isset($_GET['page']) && (int)$_GET['page'] > 0 ? (int)$_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// --- QUERY BUILDER ---
if ($tab == 'santri') {
    $where_base = "WHERE 1=1";
    if ($search) $where_base .= " AND (nama_santri LIKE '%$search%' OR nis LIKE '%$search%')";
    if ($status_filter) $where_base .= " AND status = '$status_filter'";
    if ($kelas_filter > 0) $where_base .= " AND kelas_id = $kelas_filter";
    
    $total_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM santri $where_base"))['c'];
    
    $where_query = "WHERE 1=1";
    if ($search) $where_query .= " AND (s.nama_santri LIKE '%$search%' OR s.nis LIKE '%$search%')";
    if ($status_filter) $where_query .= " AND s.status = '$status_filter'";
    if ($kelas_filter > 0) $where_query .= " AND s.kelas_id = $kelas_filter";
    
    $query = mysqli_query($koneksi, "
        SELECT s.*, k.nama_kelas 
        FROM santri s 
        LEFT JOIN kelas k ON s.kelas_id = k.id 
        $where_query ORDER BY s.id ASC LIMIT $limit OFFSET $offset
    ");
} else {
    $role_tab = ($tab == 'guru') ? 'Guru' : 'Admin';
    $where = "WHERE role = '$role_tab'";
    if ($search) $where .= " AND (nama LIKE '%$search%' OR email LIKE '%$search%')";
    if ($status_filter) $where .= " AND status = '$status_filter'";
    
    $total_data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) as c FROM users $where"))['c'];
    $query = mysqli_query($koneksi, "SELECT * FROM users $where ORDER BY id ASC LIMIT $limit OFFSET $offset");
}

$total_pages = ceil($total_data / $limit);
$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola User - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <style>
        html, body { height: 100%; overflow: hidden; }
        ::-webkit-scrollbar { height: 8px; width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc] font-sans">

    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <?php include 'header.php'; ?>
        
        <!-- Container untuk Toast Notification (Dikosongkan karena dihandle header.php) -->
        <div id="toast-container" class="fixed top-20 right-6 z-[100] flex flex-col gap-3 pointer-events-none"></div>

        <!-- Padding Utama Dikurangi agar Tabel Lebih Panjang -->
        <main class="flex-1 flex flex-col px-6 py-4 overflow-hidden">
            
            <!-- Judul -->
            <div class="flex items-center gap-3 mb-3 shrink-0">
                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shadow-sm">
                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                </div>
                <div>
                    <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Kelola User</h1>
                    <p class="text-[13px] font-medium text-gray-600">Kelola data santri, guru dan Admin...</p>
                </div>
            </div>

            <!-- Tabs & Tombol Tambah -->
            <div class="flex justify-between items-center mb-3 shrink-0">
                <div class="flex gap-2 bg-white p-1 rounded-xl shadow-md border border-gray-100">
                    <a href="<?= $link_kelola ?>?tab=santri" class="px-5 py-1.5 rounded-lg font-bold text-[13px] transition-all duration-200 active:scale-95 <?= $tab == 'santri' ? 'bg-[#0b6b53] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Santri</a>
                    <a href="<?= $link_kelola ?>?tab=guru" class="px-5 py-1.5 rounded-lg font-bold text-[13px] transition-all duration-200 active:scale-95 <?= $tab == 'guru' ? 'bg-[#0b6b53] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Guru</a>
                    <a href="<?= $link_kelola ?>?tab=admin" class="px-5 py-1.5 rounded-lg font-bold text-[13px] transition-all duration-200 active:scale-95 <?= $tab == 'admin' ? 'bg-[#0b6b53] text-white shadow-sm' : 'text-gray-600 hover:bg-gray-50' ?>">Admin</a>
                </div>
                
                <?php $btn_label = ($tab == 'santri') ? '+ Tambah Santri' : (($tab == 'guru') ? '+ Tambah Guru' : '+ Tambah Admin'); ?>
                <a href="<?= $link_form ?>?tab=<?= $tab ?>" class="bg-[#0b6b53] text-white px-4 py-2 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">
                    <?= $btn_label ?>
                </a>
            </div>

            <!-- FORM PENCARIAN & FILTER -->
            <form method="GET" action="<?= $link_kelola ?>" class="flex gap-3 mb-3 shrink-0">
                <input type="hidden" name="tab" value="<?= $tab ?>">
                
                <div class="flex flex-1 gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" placeholder="Cari <?= ucfirst($tab) ?>...">
                    </div>
                    <button type="submit" class="bg-[#0b6b53] text-white px-5 py-2 rounded-lg text-[13px] font-semibold hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 whitespace-nowrap">
                        Cari
                    </button>
                </div>
                
                <?php if ($tab == 'santri'): ?>
                <select name="kelas" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white w-44 text-[13px] font-medium cursor-pointer shadow-sm transition-all">
                    <option value="">Semua Kelas</option>
                    <?php mysqli_data_seek($q_kelas, 0); while($k = mysqli_fetch_assoc($q_kelas)): ?>
                        <option value="<?= $k['id'] ?>" <?= $kelas_filter == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                    <?php endwhile; ?>
                </select>
                <?php endif; ?>

                <select name="status" onchange="this.form.submit()" class="px-3 py-2 border border-gray-300 rounded-lg text-gray-600 focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white w-44 text-[13px] font-medium cursor-pointer shadow-sm transition-all">
                    <option value="">Semua Status</option>
                    <option value="Aktif" <?= $status_filter == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                    <option value="Nonaktif" <?= $status_filter == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                </select>
            </form>

            <!-- AREA TABEL -->
            <div class="bg-white rounded-2xl shadow-lg border border-[#e6f4f1] flex-1 min-h-0 flex flex-col transition-shadow duration-300">
                
                <div class="flex-1 overflow-auto">
                    <table class="w-full min-w-[1000px] text-left border-collapse">
                        <thead class="bg-[#f0f7f4] text-[#0b6b53] sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 font-bold text-[13px] w-16 text-center">NO</th>
                                <?php if ($tab == 'santri'): ?>
                                    <th class="px-4 py-3 font-bold text-[13px]">Nama Santri</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Kelas</th>
                                    <!-- KOLOM GENDER DITAMBAHKAN -->
                                    <th class="px-4 py-3 font-bold text-[13px]">Jenis Kelamin</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Status</th>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center">Kartu QR</th>
                                <?php elseif ($tab == 'guru'): ?>
                                    <th class="px-4 py-3 font-bold text-[13px]">Nama</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">NIP/NIK</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Jenis Kelamin</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Email</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Status</th>
                                <?php else: ?>
                                    <!-- KOLOM NIP/NIK DIHAPUS UNTUK ADMIN -->
                                    <th class="px-4 py-3 font-bold text-[13px]">Nama</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Jenis Kelamin</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Email</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Status</th>
                                <?php endif; ?>
                                <th class="px-4 py-3 font-bold text-[13px] text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                            <?php 
                            $no = $offset + 1;
                            $rows_rendered = 0;
                            if($query && mysqli_num_rows($query) > 0): 
                                while($row = mysqli_fetch_assoc($query)): 
                                    $rows_rendered++;
                            ?>
                            <tr class="hover:bg-gray-50 hover:shadow-sm transition-all duration-200">
                                <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                                
                                <?php if ($tab == 'santri'): ?>
                                    <td class="px-4 py-2 font-semibold"><?= htmlspecialchars($row['nama_santri']) ?></td>
                                    <td class="px-4 py-2 uppercase text-gray-600"><?= htmlspecialchars($row['nama_kelas'] ?? '-') ?></td>
                                    <!-- ISI DATA GENDER -->
                                    <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($row['jenis_kelamin'] ?? '-') ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= get_status_badge($row['status']) ?> shadow-sm">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <!-- BAGIAN QR CODE -->
                                    <td class="px-4 py-2 flex items-center justify-center gap-2">
                                        <button onclick="viewQRCard('TQA-<?= $row['id'] ?>', '<?= htmlspecialchars($row['nama_santri']) ?>', '<?= htmlspecialchars($row['nama_kelas'] ?? '-') ?>', '<?= htmlspecialchars($row['nis'] ?? '-') ?>')" class="text-gray-500 hover:text-[#0b6b53] transition-all duration-200 p-1.5 bg-gray-100 rounded-full hover:bg-gray-200 active:scale-90" title="Lihat Kartu Santri">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                        </button>
                                        
                                        <button onclick="printQRCode('TQA-<?= $row['id'] ?>', '<?= htmlspecialchars($row['nama_santri']) ?>', '<?= htmlspecialchars($row['nama_kelas'] ?? '-') ?>', '<?= htmlspecialchars($row['nis'] ?? '-') ?>')" class="text-gray-500 hover:text-[#0b6b53] transition-all duration-200 p-1.5 bg-gray-100 rounded-full hover:bg-gray-200 active:scale-90" title="Cetak Kartu Santri">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg>
                                        </button>
                                    </td>
                                <?php elseif ($tab == 'guru'): ?>
                                    <td class="px-4 py-2 font-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($row['nip_nik'] ?? '-') ?></td>
                                    <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($row['jenis_kelamin'] ?? '-') ?></td>
                                    <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= get_status_badge($row['status']) ?> shadow-sm">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                <?php else: ?>
                                    <!-- ISI TABEL ADMIN (NIP/NIK DIHAPUS) -->
                                    <td class="px-4 py-2 font-semibold"><?= htmlspecialchars($row['nama']) ?></td>
                                    <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($row['jenis_kelamin'] ?? '-') ?></td>
                                    <td class="px-4 py-2 text-gray-600"><?= htmlspecialchars($row['email']) ?></td>
                                    <td class="px-4 py-2">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= get_status_badge($row['status']) ?> shadow-sm">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                <?php endif; ?>

                                <!-- Tombol Aksi -->
                                <td class="px-4 py-2 text-center">
                                    <div class="flex items-center justify-center gap-2">
                                        <a href="<?= $link_form ?>?tab=<?= $tab ?>&id=<?= $row['id'] ?>" class="w-7 h-7 bg-[#fef3c7] rounded flex items-center justify-center text-[#d97706] hover:bg-yellow-200 transition-all duration-200 active:scale-90 shadow-sm" title="Edit">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </a>
                                        <!-- TOMBOL HAPUS MENGARAH KE FUNGSI JS -->
                                        <button onclick="confirmDelete('<?= $link_kelola ?>?tab=<?= $tab ?>&hapus=<?= $row['id'] ?>&tipe=<?= $tab == 'santri' ? 'santri' : 'users' ?>')" class="w-7 h-7 bg-[#fde2e2] rounded flex items-center justify-center text-[#e53e3e] hover:bg-red-200 transition-all duration-200 active:scale-90 shadow-sm" title="Hapus">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php 
                                endwhile; 
                            else: 
                            ?>
                                <tr><td colspan="<?= ($tab == 'santri') ? 7 : (($tab == 'guru') ? 7 : 6) ?>" class="p-4 text-center text-gray-500 text-sm">Data tidak ditemukan.</td></tr>
                            <?php 
                            endif; 

                            // LOGIKA 10 BARIS: Menambah baris kosong agar tepat 10 baris
                            if ($rows_rendered < 10 && $rows_rendered > 0) {
                                $col_count = ($tab == 'santri') ? 7 : (($tab == 'guru') ? 7 : 6);
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
            <div class="flex justify-center items-center gap-2 mt-3 shrink-0">
                <a href="<?= $link_kelola ?>?tab=<?= $tab ?>&search=<?= $search ?>&status=<?= $status_filter ?>&kelas=<?= $kelas_filter ?>&page=<?= max(1, $page - 1) ?>" class="w-7 h-7 border border-gray-300 rounded flex items-center justify-center text-gray-500 hover:bg-gray-100 text-[12px] <?= $page <= 1 ? 'pointer-events-none opacity-50' : '' ?> transition-all active:scale-95"><</a>
                <?php for($i = 1; $i <= $total_pages; $i++): ?>
                    <a href="<?= $link_kelola ?>?tab=<?= $tab ?>&search=<?= $search ?>&status=<?= $status_filter ?>&kelas=<?= $kelas_filter ?>&page=<?= $i ?>" class="w-7 h-7 border border-gray-300 rounded flex items-center justify-center font-bold text-[12px] <?= $page == $i ? 'bg-gray-200 text-gray-800 shadow-sm' : 'bg-white text-gray-800 hover:bg-gray-50' ?> transition-all active:scale-95"><?= $i ?></a>
                <?php endfor; ?>
                <a href="<?= $link_kelola ?>?tab=<?= $tab ?>&search=<?= $search ?>&status=<?= $status_filter ?>&kelas=<?= $kelas_filter ?>&page=<?= min($total_pages, $page + 1) ?>" class="w-7 h-7 border border-gray-300 rounded flex items-center justify-center text-gray-500 hover:bg-gray-100 text-[12px] <?= $page >= $total_pages ? 'pointer-events-none opacity-50' : '' ?> transition-all active:scale-95">></a>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- MODAL PREVIEW KARTU SANTRI -->
    <div id="qrModal" class="fixed inset-0 z-50 flex items-center justify-center hidden">
        <div class="absolute inset-0 bg-black bg-opacity-50 backdrop-blur-sm transition-opacity" onclick="closeQRModal()"></div>
        <div class="relative bg-white rounded-xl shadow-2xl p-6 w-[320px] text-center border-4 border-[#0b6b53] transform scale-100 transition-transform">
            <div class="border-b border-gray-200 pb-3 mb-4">
                <h2 class="text-xl font-extrabold text-[#0b6b53] tracking-tight">TQA Darussalam</h2>
                <p class="text-xs font-semibold text-gray-500">Kartu Identitas Santri</p>
            </div>
            <div id="modal-qrcode" class="flex justify-center mb-4 bg-white p-2 rounded-xl inline-block border border-gray-100 shadow-inner"></div>
            <div class="text-left mb-5 space-y-2">
                <div>
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Nama Santri</p>
                    <p id="modal-nama" class="text-sm font-bold text-gray-900">-</p>
                </div>
                <!-- KELAS DIPINDAH KE BAWAH NAMA -->
                <div class="text-left">
                    <p class="text-[11px] font-bold text-gray-400 uppercase tracking-wider">Kelas</p>
                    <p id="modal-kelas" class="text-sm font-bold text-gray-900 uppercase">-</p>
                </div>
            </div>
            <div class="flex gap-2">
                <button onclick="closeQRModal()" class="flex-1 bg-gray-100 text-gray-700 font-bold py-2 rounded-lg hover:bg-gray-200 transition-all active:scale-95 text-sm shadow-sm">Tutup</button>
                <button onclick="printQRCodeFromModal()" class="flex-1 bg-[#0b6b53] text-white font-bold py-2 rounded-lg hover:bg-[#08533f] transition-all active:scale-95 text-sm flex items-center justify-center gap-1 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"></path></svg> Cetak
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL KONFIRMASI HAPUS (BARU) -->
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

    <!-- SCRIPT UNTUK PREVIEW, PRINT QR CODE, HAPUS MODAL, DAN TOAST -->
    <script>
        let currentQRData = { code: '', nama: '', kelas: '', nis: '' };

        // =======================================================
        // FUNGSI MODAL HAPUS
        // =======================================================
        function confirmDelete(url) {
            document.getElementById('btnConfirmDelete').href = url;
            document.getElementById('deleteModal').classList.remove('hidden');
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').classList.add('hidden');
        }

        function viewQRCard(code, nama, kelas, nis) {
            currentQRData = { code, nama, kelas, nis };
            document.getElementById('modal-nama').innerText = nama;
            document.getElementById('modal-kelas').innerText = kelas;

            const modalQrDiv = document.getElementById('modal-qrcode');
            modalQrDiv.innerHTML = ''; 
            
            new QRCode(modalQrDiv, {
                text: code,
                width: 140,
                height: 140,
                colorDark : "#0b6b53",
                colorLight : "#ffffff",
                correctLevel : QRCode.CorrectLevel.H
            });

            document.getElementById('qrModal').classList.remove('hidden');
        }

        function closeQRModal() {
            document.getElementById('qrModal').classList.add('hidden');
        }

        function printQRCodeFromModal() {
            printQRCode(currentQRData.code, currentQRData.nama, currentQRData.kelas, currentQRData.nis);
        }

        function printQRCode(code, nama, kelas, nis) {
            const printWindow = window.open('', '_blank');
            printWindow.document.write(`
                <html>
                <head>
                    <title>Cetak Kartu - ${nama}</title>
                    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"><\/script>
                    <style>
                        body { display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; font-family: 'Poppins', Arial, sans-serif; background-color: #f8fafc; }
                        .card { border: 4px solid #0b6b53; padding: 30px; border-radius: 20px; text-align: center; background-color: #ffffff; width: 300px; box-shadow: 0 10px 25px rgba(0,0,0,0.1); }
                        .header { border-bottom: 2px solid #eee; padding-bottom: 15px; margin-bottom: 20px; }
                        h2 { margin: 0; color: #0b6b53; font-size: 22px; font-weight: 800; letter-spacing: -0.5px; }
                        .subtitle { margin: 5px 0 0 0; color: #666; font-size: 12px; font-weight: 600; }
                        #print-qrcode { display: flex; justify-content: center; margin-bottom: 20px; }
                        .data-row { display: flex; justify-content: space-between; text-align: left; margin-bottom: 10px; }
                        .label { font-size: 10px; color: #888; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; }
                        .value { font-size: 15px; color: #111; font-weight: 700; margin-top: 2px; }
                        .full-width { text-align: left; margin-bottom: 15px; }
                    </style>
                </head>
                <body>
                    <div class="card">
                        <div class="header">
                            <h2>TQA Darussalam</h2>
                            <p class="subtitle">Kartu Identitas Santri</p>
                        </div>
                        <div id="print-qrcode"></div>
                        <div class="full-width">
                            <div class="label">Nama Santri</div>
                            <div class="value">${nama}</div>
                        </div>
                        <!-- KELAS DIPINDAH KE BAWAH NAMA -->
                        <div class="full-width">
                            <div class="label">Kelas</div>
                            <div class="value">${kelas}</div>
                        </div>
                    </div>
                    <script>
                        new QRCode(document.getElementById("print-qrcode"), {
                            text: "${code}",
                            width: 160,
                            height: 160,
                            colorDark : "#0b6b53",
                            colorLight : "#ffffff",
                            correctLevel : QRCode.CorrectLevel.H
                        });
                        window.onload = function() { 
                            setTimeout(function() {
                                window.print(); 
                                window.close(); 
                            }, 500);
                        }
                    <\/script>
                </body>
                </html>
            `);
            printWindow.document.close();
        }
    </script>
</body>
</html>