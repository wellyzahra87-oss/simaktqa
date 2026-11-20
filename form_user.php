<?php 
include 'koneksi.php'; 

// --- MEMBUAT PATH ABSOLUT AGAR TIDAK 404 ---
$folder_path = dirname($_SERVER['PHP_SELF']);
$folder_path = str_replace('\\', '/', $folder_path);
$base_url = ($folder_path == '/' || $folder_path == '.') ? '' : $folder_path;
$link_kelola = $base_url . '/kelola_user.php';
$link_self = $base_url . '/form_user.php';

if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: $base_url/login.php");
    exit;
}

$tab = isset($_GET['tab']) ? $_GET['tab'] : 'santri';
$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = $id > 0;
$error_msg = '';

// Ambil data jika mode Edit
$data = [];
if ($is_edit) {
    if ($tab == 'santri') {
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM santri WHERE id = $id"));
    } else {
        $data = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT * FROM users WHERE id = $id"));
    }
}

// Proses Simpan (Tambah / Edit)
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if ($tab == 'santri') {
        // PERBAIKAN: NIS diset kosong karena kolom inputnya sudah dihapus
        $nis = '';
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $jk = $_POST['jenis_kelamin'];
        $kelas_id = (int)$_POST['kelas_id'];
        $status = $_POST['status'];

        if ($is_edit) {
            // Update tanpa mengubah NIS
            $query = "UPDATE santri SET nama_santri='$nama', jenis_kelamin='$jk', kelas_id=$kelas_id, status='$status' WHERE id=$id";
        } else {
            // Insert (NIS akan tersimpan sebagai string kosong)
            $query = "INSERT INTO santri (nis, nama_santri, jenis_kelamin, kelas_id, status) VALUES ('$nis', '$nama', '$jk', $kelas_id, '$status')";
        }
    } else {
        $nama = mysqli_real_escape_string($koneksi, $_POST['nama']);
        $email = mysqli_real_escape_string($koneksi, $_POST['email']);
        $jk = $_POST['jenis_kelamin'];
        $status = $_POST['status'];
        $role = ($tab == 'guru') ? 'Guru' : 'Admin';
        
        // Logika NIP/NIK: Hanya diambil jika bukan Admin
        $nip = ($tab == 'admin') ? NULL : mysqli_real_escape_string($koneksi, $_POST['nip_nik']);

        if ($is_edit) {
            if (!empty($_POST['password'])) {
                $pass = md5($_POST['password']);
                $query = "UPDATE users SET nama='$nama', email='$email', nip_nik='$nip', jenis_kelamin='$jk', status='$status', password='$pass' WHERE id=$id";
            } else {
                $query = "UPDATE users SET nama='$nama', email='$email', nip_nik='$nip', jenis_kelamin='$jk', status='$status' WHERE id=$id";
            }
        } else {
            $pass = md5($_POST['password']); 
            $query = "INSERT INTO users (nama, email, password, role, nip_nik, jenis_kelamin, status) VALUES ('$nama', '$email', '$pass', '$role', '$nip', '$jk', '$status')";
        }
    }

    // PERBAIKAN: Menggunakan try-catch untuk menangani Error Duplikat di PHP 8+
    try {
        mysqli_query($koneksi, $query);
        
        // Tentukan kode success berdasarkan aksi (Tambah / Edit)
        $success_code = $is_edit ? 'edit_user' : 'tambah_user';
        header("Location: $link_kelola?tab=$tab&success=$success_code");
        exit;
    } catch (mysqli_sql_exception $e) {
        if ($e->getCode() == 1062) {
            $error_msg = ($tab == 'santri') ? "Gagal! NIS tersebut sudah terdaftar." : "Gagal! Email tersebut sudah terdaftar.";
        } else {
            $error_msg = "Gagal menyimpan data: " . $e->getMessage();
        }
    }
}

$q_kelas = mysqli_query($koneksi, "SELECT * FROM kelas ORDER BY nama_kelas ASC");
$judul = $is_edit ? 'Edit' : 'Tambah';
$judul .= ' ' . ucfirst($tab);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $judul ?> - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc] font-sans">

    <?php 
    $current_page = 'kelola_user'; 
    include 'sidebar.php'; 
    ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-hidden">
        <?php include 'header.php'; ?>
        
        <main class="flex-1 overflow-y-auto p-6 md:p-8">
            <div class="max-w-3xl mx-auto">
                
                <!-- Header Form -->
                <div class="flex items-center gap-3 mb-6">
                    <a href="<?= $link_kelola ?>?tab=<?= $tab ?>" class="w-10 h-10 bg-white border border-gray-200 rounded-full flex items-center justify-center hover:bg-gray-50 transition-all duration-200 shadow-sm active:scale-95">
                        <svg class="w-5 h-5 text-gray-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    </a>
                    <div>
                        <h1 class="text-[24px] md:text-[28px] font-bold text-gray-900 leading-tight"><?= $judul ?></h1>
                        <p class="text-[14px] font-medium text-gray-600">Lengkapi form di bawah ini</p>
                    </div>
                </div>

                <!-- Error Msg -->
                <?php if (!empty($error_msg)): ?>
                    <div id="notif-error" class="bg-red-100 border border-red-400 text-red-700 px-4 py-2.5 rounded-lg mb-5 font-semibold text-[14px] shadow-md transition-opacity duration-500">
                        <?= $error_msg ?>
                    </div>
                <?php endif; ?>

                <!-- Card Form -->
                <div class="bg-white p-6 rounded-2xl shadow-xl border border-[#e6f4f1] transition-shadow duration-300">
                    <form method="POST" action="<?= $link_self ?>?tab=<?= $tab ?>&id=<?= $id ?>">
                        <!-- Menggunakan Grid 2 Kolom -->
                        <div class="grid grid-cols-2 gap-4">
                            
                            <?php if ($tab == 'santri'): ?>
                                <!-- KOLOM NIS SUDAH DIHAPUS SESUAI PERMINTAAN -->
                                
                                <div class="col-span-2">
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Nama Santri</label>
                                    <input type="text" name="nama" value="<?= $is_edit ? htmlspecialchars($data['nama_santri']) : '' ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none text-[14px] shadow-sm transition-all" placeholder="Masukkan Nama Lengkap..." required>
                                </div>
                                
                                <div>
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none bg-white text-[14px] shadow-sm transition-all cursor-pointer" required>
                                        <option value="Laki-laki" <?= $is_edit && $data['jenis_kelamin'] == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="Perempuan" <?= $is_edit && $data['jenis_kelamin'] == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>
                                
                                <div>
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Kelas</label>
                                    <select name="kelas_id" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none bg-white text-[14px] shadow-sm transition-all cursor-pointer" required>
                                        <option value="">Pilih Kelas</option>
                                        <?php mysqli_data_seek($q_kelas, 0); while($k = mysqli_fetch_assoc($q_kelas)): ?>
                                            <option value="<?= $k['id'] ?>" <?= $is_edit && $data['kelas_id'] == $k['id'] ? 'selected' : '' ?>><?= htmlspecialchars($k['nama_kelas']) ?></option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>

                                <div class="col-span-2">
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Status</label>
                                    <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none bg-white text-[14px] shadow-sm transition-all cursor-pointer" required>
                                        <option value="Aktif" <?= $is_edit && $data['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="Nonaktif" <?= $is_edit && $data['status'] == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>

                            <?php else: ?>
                                <div class="col-span-2">
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Nama Lengkap</label>
                                    <input type="text" name="nama" value="<?= $is_edit ? htmlspecialchars($data['nama']) : '' ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none text-[14px] shadow-sm transition-all" placeholder="Masukkan Nama Lengkap..." required>
                                </div>

                                <div class="col-span-2">
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Email</label>
                                    <input type="email" name="email" value="<?= $is_edit ? htmlspecialchars($data['email']) : '' ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none text-[14px] shadow-sm transition-all" placeholder="Masukkan Email..." required>
                                </div>

                                <!-- FIELD NIP/NIK HANYA MUNCUL JIKA BUKAN ADMIN -->
                                <?php if ($tab != 'admin'): ?>
                                <div>
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">NIP / NIK <?= $tab == 'guru' ? '' : '(Opsional)' ?></label>
                                    <input type="text" name="nip_nik" value="<?= $is_edit ? htmlspecialchars($data['nip_nik'] ?? '') : '' ?>" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none text-[14px] shadow-sm transition-all" placeholder="Masukkan NIP/NIK..." <?= $tab == 'guru' ? 'required' : '' ?>>
                                </div>
                                <?php endif; ?>

                                <div class="<?= $tab == 'admin' ? 'col-span-2' : '' ?>">
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Jenis Kelamin</label>
                                    <select name="jenis_kelamin" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none bg-white text-[14px] shadow-sm transition-all cursor-pointer" required>
                                        <option value="Laki-laki" <?= $is_edit && ($data['jenis_kelamin'] ?? '') == 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                                        <option value="Perempuan" <?= $is_edit && ($data['jenis_kelamin'] ?? '') == 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
                                    </select>
                                </div>

                                <div>
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Password <?= $is_edit ? '(Opsional)' : '' ?></label>
                                    <input type="password" name="password" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none text-[14px] shadow-sm transition-all" placeholder="<?= $is_edit ? '••••••••' : 'Masukkan Password...' ?>" <?= $is_edit ? '' : 'required' ?>>
                                </div>

                                <div>
                                    <label class="block font-semibold text-[14px] text-gray-700 mb-1.5">Status</label>
                                    <select name="status" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] focus:border-[#0b6b53] outline-none bg-white text-[14px] shadow-sm transition-all cursor-pointer" required>
                                        <option value="Aktif" <?= $is_edit && $data['status'] == 'Aktif' ? 'selected' : '' ?>>Aktif</option>
                                        <option value="Nonaktif" <?= $is_edit && $data['status'] == 'Nonaktif' ? 'selected' : '' ?>>Nonaktif</option>
                                    </select>
                                </div>
                            <?php endif; ?>

                        </div>

                        <!-- Tombol Aksi -->
                        <div class="flex gap-3 mt-6 pt-5 border-t border-gray-100">
                            <button type="submit" class="flex-1 bg-[#0b6b53] text-white font-semibold text-[14px] py-2.5 rounded-lg hover:bg-[#08533f] transition-all duration-200 shadow-md active:scale-95">
                                Simpan Data
                            </button>
                            <a href="<?= $link_kelola ?>?tab=<?= $tab ?>" class="px-6 py-2.5 bg-gray-100 text-gray-700 font-semibold text-[14px] rounded-lg hover:bg-gray-200 transition-all duration-200 active:scale-95">
                                Batal
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- Script Notifikasi 2 Detik -->
    <script>
        document.addEventListener("DOMContentLoaded", function() {
            const notifError = document.getElementById('notif-error');
            if (notifError) {
                setTimeout(() => {
                    notifError.style.opacity = '0';
                    setTimeout(() => notifError.remove(), 500);
                }, 2000); // 2000ms = 2 Detik
            }
        });
    </script>
</body>
</html>