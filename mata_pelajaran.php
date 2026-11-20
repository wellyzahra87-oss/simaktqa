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
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$search = isset($_GET['search']) ? mysqli_real_escape_string($koneksi, $_GET['search']) : '';
$error_msg = '';

// ==========================================
// 1. LOGIKA HAPUS DATA
// ==========================================
if ($action == 'delete' && $id > 0) {
    $delete = mysqli_query($koneksi, "DELETE FROM mata_pelajaran WHERE id = $id");
    if ($delete) {
        // PERBAIKAN: Menggunakan parameter 'msg' spesifik agar tidak muncul notif default
        header("Location: mata_pelajaran.php?msg=" . urlencode('Mata Pelajaran berhasil dihapus!'));
    } else {
        header("Location: mata_pelajaran.php?msg=" . urlencode("Gagal menghapus data karena terhubung dengan tabel lain (Jadwal/Nilai).") . "&type=error");
    }
    exit;
}

// ==========================================
// 2. LOGIKA SIMPAN DATA (TAMBAH / EDIT)
// ==========================================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $nama_mapel = mysqli_real_escape_string($koneksi, $_POST['nama_mapel']);
    $deskripsi  = mysqli_real_escape_string($koneksi, $_POST['deskripsi']);
    $status     = $_POST['status'];

    if ($id > 0) {
        // Update Data
        $query = "UPDATE mata_pelajaran SET nama_mapel='$nama_mapel', deskripsi='$deskripsi', status='$status' WHERE id=$id";
        $pesan_sukses = 'Mata Pelajaran berhasil diubah!';
    } else {
        // Insert Data
        $query = "INSERT INTO mata_pelajaran (nama_mapel, deskripsi, status) VALUES ('$nama_mapel', '$deskripsi', '$status')";
        $pesan_sukses = 'Mata Pelajaran berhasil ditambahkan!';
    }

    if (mysqli_query($koneksi, $query)) {
        // PERBAIKAN: Menggunakan parameter 'msg' spesifik
        header("Location: mata_pelajaran.php?msg=" . urlencode($pesan_sukses));
        exit;
    } else {
        $error_msg = "Gagal menyimpan data: " . mysqli_error($koneksi);
    }
}

// ==========================================
// 3. AMBIL DATA UNTUK EDIT
// ==========================================
$data_edit = [];
if ($action == 'edit' && $id > 0) {
    $data_edit = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM mata_pelajaran WHERE id = $id"));
}

// ==========================================
// 4. AMBIL DATA UNTUK TABEL (LIST)
// ==========================================
$where = "WHERE 1=1";
if ($search) {
    $where .= " AND (nama_mapel LIKE '%$search%' OR deskripsi LIKE '%$search%')";
}
$query_list = mysqli_query($koneksi, "SELECT * FROM mata_pelajaran $where ORDER BY id ASC");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mata Pelajaran - TQA Darussalam</title>
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

    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <?php include 'header.php'; ?>
        
        <!-- Padding Utama Dikurangi agar Tabel Lebih Panjang -->
        <main class="flex-1 flex flex-col px-6 py-4 overflow-hidden">
            
            <!-- Notifikasi Error POST (Lokal) -->
            <div class="shrink-0">
                <?php if(!empty($error_msg)): ?>
                    <div id="notif-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-2 rounded-lg mb-2 font-semibold text-sm shadow-md transition-opacity duration-500">
                        <?= $error_msg ?>
                    </div>
                <?php endif; ?>
            </div>

            <!-- ======================================================= -->
            <!-- TAMPILAN 1: TABEL MATA PELAJARAN                        -->
            <!-- ======================================================= -->
            <?php if ($action == 'list'): ?>
                <!-- Header Judul -->
                <div class="flex items-center gap-3 mb-3 shrink-0">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shadow-sm">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M4 6H2v14c0 1.1.9 2 2 2h14v-2H4V6zm16-4H8c-1.1 0-2 .9-2 2v12c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zm-1 9h-4v4h-2v-4H9V9h4V5h2v4h4v2z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Kelola Mata Pelajaran</h1>
                        <p class="text-[13px] font-medium text-gray-600">Kelola data mata pelajaran TQA Darussalam.</p>
                    </div>
                </div>

                <!-- Search & Tombol Tambah -->
                <div class="flex justify-between items-center mb-3 shrink-0 gap-4">
                    <!-- FORM PENCARIAN DENGAN TOMBOL CARI -->
                    <form method="GET" action="mata_pelajaran.php" class="flex gap-2 flex-1 max-w-md">
                        <div class="relative flex-1">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            </div>
                            <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all" placeholder="Cari Mata Pelajaran...">
                        </div>
                        <button type="submit" class="bg-[#0b6b53] text-white px-5 py-2 rounded-lg text-[13px] font-semibold hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 whitespace-nowrap">
                            Cari
                        </button>
                    </form>

                    <a href="mata_pelajaran.php?action=add" class="bg-[#0b6b53] text-white px-4 py-2 rounded-lg font-bold text-[13px] hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95 whitespace-nowrap">
                        + Tambah Mata Pelajaran
                    </a>
                </div>

                <!-- Tabel -->
                <div class="bg-white rounded-2xl shadow-lg border border-[#e6f4f1] flex-1 min-h-0 flex flex-col transition-shadow duration-300">
                    <div class="flex-1 overflow-auto">
                        <table class="w-full text-left border-collapse min-w-max">
                            <thead class="bg-[#f0f7f4] text-[#0b6b53] sticky top-0 z-10 shadow-sm">
                                <tr>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-16">NO</th>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-1/4">Nama<br>Mata Pelajaran</th>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center">Deskripsi</th>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-32">Status</th>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-32">Aksi</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                <?php 
                                $no = 1;
                                $rows_rendered = 0;
                                if(mysqli_num_rows($query_list) > 0): 
                                    while($row = mysqli_fetch_assoc($query_list)): 
                                        $rows_rendered++;
                                ?>
                                <tr class="hover:bg-gray-50 hover:shadow-sm transition-all duration-200 h-[45px]">
                                    <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                                    <td class="px-4 py-2 text-center font-semibold"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                    <td class="px-4 py-2 text-center text-gray-600"><?= htmlspecialchars($row['deskripsi'] ?? '-') ?></td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="px-2.5 py-1 rounded-md text-[11px] font-bold <?= get_status_badge($row['status']) ?> shadow-sm">
                                            <?= $row['status'] ?>
                                        </span>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <div class="flex justify-center gap-2">
                                            <a href="mata_pelajaran.php?action=edit&id=<?= $row['id'] ?>" class="w-7 h-7 bg-[#fef3c7] rounded flex items-center justify-center text-[#d97706] hover:bg-yellow-200 transition-all duration-200 active:scale-90 shadow-sm" title="Edit">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                            </a>
                                            <!-- TOMBOL HAPUS MENGARAH KE MODAL JS -->
                                            <button onclick="confirmDelete('mata_pelajaran.php?action=delete&id=<?= $row['id'] ?>')" class="w-7 h-7 bg-[#fde2e2] rounded flex items-center justify-center text-[#e53e3e] hover:bg-red-200 transition-all duration-200 active:scale-90 shadow-sm" title="Hapus">
                                                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M6 19c0 1.1.9 2 2 2h8c1.1 0 2-.9 2-2V7H6v12zM19 4h-3.5l-1-1h-5l-1 1H5v2h14V4z"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php 
                                    endwhile; 
                                else: 
                                ?>
                                    <tr><td colspan="5" class="p-4 text-center text-gray-500 text-sm h-[45px]">Data mata pelajaran tidak ditemukan.</td></tr>
                                <?php 
                                endif; 

                                if ($rows_rendered < 10 && $rows_rendered > 0) {
                                    for ($i = $rows_rendered; $i < 10; $i++) {
                                        echo '<tr class="h-[45px]">';
                                        for ($j = 0; $j < 5; $j++) {
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

            <!-- ======================================================= -->
            <!-- TAMPILAN 2: FORM TAMBAH / EDIT                          -->
            <!-- ======================================================= -->
            <?php else: ?>
            <div class="flex-1 overflow-y-auto w-full">
                <div class="max-w-3xl mx-auto w-full pb-8">
                    <!-- Header Form -->
                    <div class="flex items-center gap-3 mb-4 shrink-0">
                        <a href="mata_pelajaran.php" class="w-9 h-9 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm active:scale-95">
                            <svg class="w-4 h-4 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        </a>
                        <div>
                            <h1 class="text-[22px] font-bold text-gray-900 leading-tight">
                                <?= $action == 'edit' ? 'Edit Mata Pelajaran' : 'Tambah Mata Pelajaran' ?>
                            </h1>
                            <p class="text-[13px] font-medium text-gray-600">Lengkapi form di bawah ini</p>
                        </div>
                    </div>

                    <!-- Card Form -->
                    <div class="bg-white p-6 rounded-2xl shadow-xl border border-[#e6f4f1] transition-shadow duration-300">
                        <form method="POST" action="mata_pelajaran.php?action=<?= $action ?>&id=<?= $id ?>">
                            
                            <div class="mb-4">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Nama Mata Pelajaran</label>
                                <input type="text" name="nama_mapel" value="<?= $action == 'edit' ? htmlspecialchars($data_edit['nama_mapel']) : '' ?>" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" placeholder="Contoh: Tahsin, Tafsir, Tahfiz..." required>
                            </div>

                            <div class="mb-4">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Deskripsi</label>
                                <textarea name="deskripsi" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none text-[13px] shadow-sm transition-all" placeholder="Jelaskan secara singkat mengenai mata pelajaran ini..." required><?= $action == 'edit' ? htmlspecialchars($data_edit['deskripsi']) : '' ?></textarea>
                            </div>

                            <div class="mb-6">
                                <label class="block font-semibold text-[13px] text-gray-700 mb-1.5">Status</label>
                                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px] shadow-sm transition-all cursor-pointer" required>
                                    <option value="Aktif" <?= $action == 'edit' && $data_edit['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                    <option value="Nonaktif" <?= $action == 'edit' && $data_edit['status'] == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                </select>
                            </div>

                            <div class="flex gap-3 pt-4 border-t border-gray-100">
                                <button type="submit" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[13px] py-2.5 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">
                                    Simpan Data
                                </button>
                                <a href="mata_pelajaran.php" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold text-[13px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95">
                                    Batal
                                </a>
                            </div>
                        </form>
                    </div>
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