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

// Parameter Pencarian Kelas
$search_kelas = isset($_GET['search_kelas']) ? mysqli_real_escape_string($koneksi, $_GET['search_kelas']) : '';

// ==========================================
// PROSES SIMPAN NILAI (SATUAN / SEMUA)
// ==========================================
if (isset($_POST['simpan_nilai']) || isset($_POST['simpan_semua'])) {
    $nilai_data = $_POST['nilai']; // Array [santri_id => nilai_angka]
    $mapel_target = (int)$_POST['mapel_id'];
    $kelas_target = (int)$_POST['kelas_id'];
    
    foreach ($nilai_data as $santri_id => $nilai_angka) {
        $santri_id = (int)$santri_id;
        $nilai_angka = (int)$nilai_angka;
        
        // Tentukan Status Kelulusan (Misal: >= 75 Lulus)
        $status_lulus = ($nilai_angka >= 75) ? 'Lulus' : 'Tidak Lulus';
        
        // Cek apakah data nilai sudah ada
        $cek = mysqli_query($koneksi, "SELECT id FROM nilai WHERE santri_id = $santri_id AND mapel_id = $mapel_target");
        
        if (mysqli_num_rows($cek) > 0) {
            // Update
            $row = mysqli_fetch_assoc($cek);
            $id_nilai = $row['id'];
            mysqli_query($koneksi, "UPDATE nilai SET nilai_angka = $nilai_angka, status_lulus = '$status_lulus' WHERE id = $id_nilai");
        } else {
            // Insert
            mysqli_query($koneksi, "INSERT INTO nilai (santri_id, mapel_id, nilai_angka, status_lulus) VALUES ($santri_id, $mapel_target, $nilai_angka, '$status_lulus')");
        }
    }
    
    // Redirect agar form tidak tersubmit ulang saat di-refresh
    header("Location: kelola_nilai.php?action=detail&kelas_id=$kelas_target&mapel_id=$mapel_target&success=1");
    exit;
}

// ==========================================
// QUERY DATA UNTUK LIST KELAS
// ==========================================
$where_kelas = "";
if (!empty($search_kelas)) {
    $where_kelas = " AND k.nama_kelas LIKE '%$search_kelas%'";
}

$q_kelas = mysqli_query($koneksi, "
    SELECT j.kelas_id, j.mapel_id, k.nama_kelas, m.nama_mapel, j.hari, j.jam_mulai, j.jam_selesai, r.nama_ruangan, r.keterangan
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id
    LEFT JOIN ruangan r ON k.ruangan_id = r.id
    WHERE j.guru_id = $guru_id $where_kelas
    GROUP BY j.kelas_id, j.mapel_id
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Nilai - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc]">

    <?php 
    $current_page = 'kelola_nilai'; 
    include 'sidebar.php'; 
    ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-y-auto">
        <?php include 'header.php'; ?>
        
        <main class="p-6 md:p-8">
            
            <!-- ======================================================= -->
            <!-- VIEW 1: DAFTAR KELAS YANG DIAMPU                        -->
            <!-- ======================================================= -->
            <?php if ($action == 'list'): ?>
            <div class="max-w-5xl mx-auto w-full">
                <!-- Judul (Ukuran Disesuaikan) -->
                <div class="flex items-center gap-3 mb-4 shrink-0">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M19 3h-4.18C14.4 1.84 13.3 1 12 1c-1.3 0-2.4.84-2.82 2H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-7 0c.55 0 1 .45 1 1s-.45 1-1 1-1-.45-1-1 .45-1 1-1zm-2 14l-4-4 1.41-1.41L10 14.17l6.59-6.59L18 9l-8 8z"/></svg>
                    </div>
                    <div>
                        <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Kelola Nilai</h1>
                        <p class="text-[13px] font-semibold text-gray-700">Input Nilai Santri disini...</p>
                    </div>
                </div>

                <h2 class="text-[16px] font-bold text-gray-900 mb-3">Kelas yang diampu</h2>
                
                <!-- Search Bar & Tombol Cari -->
                <form method="GET" action="kelola_nilai.php" class="mb-4 shrink-0 max-w-md flex gap-2">
                    <div class="relative flex-1">
                        <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </div>
                        <input type="text" name="search_kelas" value="<?= htmlspecialchars($search_kelas) ?>" class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px]" placeholder="Cari Kelas...">
                    </div>
                    <!-- Tombol Cari dengan Gradasi & Animasi -->
                    <button type="submit" class="bg-gradient-to-r from-[#0b6b53] to-[#12826a] text-white px-4 py-2 rounded-lg font-bold text-[13px] hover:from-[#08533f] hover:to-[#0b6b53] transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg whitespace-nowrap">Cari</button>
                </form>

                <div class="space-y-3">
                    <?php if(mysqli_num_rows($q_kelas) > 0): while($j = mysqli_fetch_assoc($q_kelas)): ?>
                    <!-- Card dengan Gradasi, Animasi Tekan, dan Layout Mapel di Kanan -->
                    <div class="bg-gradient-to-r from-white to-[#f4fbf7] rounded-xl shadow-sm border border-[#e6f4f1] p-4 flex items-center justify-between hover:shadow-lg hover:-translate-y-1 transition-all duration-300 active:scale-[0.98]">
                        <div class="flex items-center gap-4">
                            <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-lg flex items-center justify-center shrink-0 shadow-sm">
                                <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M10 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V8c0-1.1-.9-2-2-2h-8l-2-2z"/></svg>
                            </div>
                            <div>
                                <!-- Nama Kelas & Mapel Sejajar (Kanan-Kiri) -->
                                <div class="flex items-center justify-between gap-4 mb-1">
                                    <h3 class="text-[15px] font-extrabold text-gray-900 uppercase"><?= htmlspecialchars($j['nama_kelas']) ?></h3>
                                    <span class="px-2.5 py-1 bg-[#0b6b53] text-white rounded-md text-[11px] font-bold uppercase tracking-wider shadow-sm"><?= htmlspecialchars($j['nama_mapel']) ?></span>
                                </div>
                                
                                <div class="flex flex-col gap-1 mt-1.5 text-[12px] font-semibold text-gray-700">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z"/></svg>
                                        Hari: <?= htmlspecialchars($j['hari'] ?? '-') ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg>
                                        <?= date('H.i', strtotime($j['jam_mulai'])) ?> - <?= date('H.i', strtotime($j['jam_selesai'])) ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M4 4a2 2 0 012-2h8a2 2 0 012 2v12a1 1 0 01-1 1H5a1 1 0 01-1-1V4z"/></svg>
                                        <?= htmlspecialchars($j['nama_ruangan'] ?? '-') ?> (<?= htmlspecialchars($j['keterangan'] ?? '-') ?>)
                                    </div>
                                </div>
                            </div>
                        </div>

                        <a href="kelola_nilai.php?action=detail&kelas_id=<?= $j['kelas_id'] ?>&mapel_id=<?= $j['mapel_id'] ?>" class="w-8 h-8 flex items-center justify-center hover:bg-gray-100 rounded-full transition shrink-0 active:scale-90">
                            <svg class="w-5 h-5 text-black" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 5l7 7-7 7"></path></svg>
                        </a>
                    </div>
                    <?php endwhile; else: ?>
                        <div class="p-5 text-center text-gray-500 bg-white rounded-xl border border-[#e6f4f1] font-semibold text-[13px]">
                            <?= !empty($search_kelas) ? 'Kelas tidak ditemukan.' : 'Belum ada kelas yang diampu.' ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- ======================================================= -->
            <!-- VIEW 2: INPUT NILAI SANTRI                              -->
            <!-- ======================================================= -->
            <?php elseif ($action == 'detail'): 
                // Info Kelas & Mapel
                $q_info = mysqli_query($koneksi, "SELECT j.*, k.nama_kelas, m.nama_mapel 
                    FROM jadwal j 
                    JOIN kelas k ON j.kelas_id = k.id 
                    JOIN mata_pelajaran m ON j.mapel_id = m.id 
                    WHERE j.kelas_id = $kelas_id AND j.mapel_id = $mapel_id AND j.guru_id = $guru_id LIMIT 1");
                $info = mysqli_fetch_assoc($q_info);
                
                // Data Santri
                $q_santri = mysqli_query($koneksi, "SELECT * FROM santri WHERE kelas_id = $kelas_id AND status = 'Aktif' ORDER BY nama_santri ASC");
            ?>
            <div class="max-w-4xl mx-auto w-full">
                
                <!-- FORM DIMULAI DI SINI -->
                <form method="POST" action="kelola_nilai.php">
                    <input type="hidden" name="kelas_id" value="<?= $kelas_id ?>">
                    <input type="hidden" name="mapel_id" value="<?= $mapel_id ?>">

                    <!-- Header Info -->
                    <div class="bg-gradient-to-r from-white to-[#f4fbf7] rounded-xl shadow-sm border border-[#e6f4f1] p-4 flex items-center mb-4 shrink-0">
                        <div class="flex items-center gap-3">
                            <a href="kelola_nilai.php" class="w-9 h-9 bg-[#e6f4f1] rounded-full flex items-center justify-center hover:bg-[#d1e7e2] transition shrink-0 active:scale-90">
                                <svg class="w-4 h-4 text-[#0b6b53]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            </a>
                            <div>
                                <h2 class="text-[18px] font-bold text-gray-900 mb-1 leading-tight"><?= htmlspecialchars($info['nama_kelas']) ?></h2>
                                <div class="flex flex-col gap-0.5 text-[12px] font-semibold text-gray-700">
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9 4.804A7.968 7.968 0 005.5 4c-1.255 0-2.443.29-3.5.804v10A7.969 7.969 0 015.5 14c1.669 0 3.218.51 4.5 1.385A7.962 7.962 0 0114.5 14c1.255 0 2.443.29 3.5.804v-10A7.968 7.968 0 0014.5 4c-1.255 0-2.443.29-3.5.804V12a1 1 0 11-2 0V4.804z"/></svg> 
                                        <?= htmlspecialchars($info['nama_mapel']) ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm1-12a1 1 0 10-2 0v4a1 1 0 00.293.707l2.828 2.829a1 1 0 101.415-1.415L11 9.586V6z" clip-rule="evenodd"/></svg> 
                                        <?= date('H.i', strtotime($info['jam_mulai'])) ?> - <?= date('H.i', strtotime($info['jam_selesai'])) ?>
                                    </div>
                                    <div class="flex items-center gap-1.5">
                                        <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd"/></svg> 
                                        <?= htmlspecialchars($_SESSION['nama']) ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Toolbar Pencarian & Tombol Simpan -->
                    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3 mb-4">
                        <div class="flex gap-2 w-full sm:max-w-xs">
                            <input type="text" id="searchSantriDetail" onkeyup="filterTable()" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none bg-white text-[13px]" placeholder="Cari Santri...">
                            <button type="button" onclick="filterTable()" class="bg-gradient-to-r from-[#0b6b53] to-[#12826a] text-white px-4 py-2 rounded-lg font-bold text-[13px] hover:from-[#08533f] hover:to-[#0b6b53] transition-all duration-200 active:scale-95 shadow-md whitespace-nowrap">Cari</button>
                        </div>
                        <?php if(mysqli_num_rows($q_santri) > 0): ?>
                        <!-- Tombol Simpan dengan Gradasi & Animasi -->
                        <button type="submit" name="simpan_semua" class="bg-gradient-to-r from-[#0b6b53] to-[#12826a] text-white px-6 py-2.5 rounded-lg font-bold text-[14px] hover:from-[#08533f] hover:to-[#0b6b53] transition-all duration-200 active:scale-95 shadow-md hover:shadow-lg flex items-center gap-2">
                            <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M17 3H5c-1.11 0-2 .9-2 2v14c0 1.1.89 2 2 2h14c1.1 0 2-.9 2-2V7l-4-4zm-5 16c-1.66 0-3-1.34-3-3s1.34-3 3-3 3 1.34 3 3-1.34 3-3 3zm3-10H5V5h10v4z"/></svg>
                            Simpan Nilai
                        </button>
                        <?php endif; ?>
                    </div>

                    <!-- Tabel Nilai -->
                    <div class="bg-white rounded-xl shadow-sm border border-[#e6f4f1] overflow-hidden mb-4">
                        <table class="w-full text-left border-collapse" id="tabelNilai">
                            <!-- Header Tabel dengan Gradasi -->
                            <thead class="bg-gradient-to-r from-[#e6f4f1] to-[#f0f7f4] text-[#0b6b53]">
                                <tr>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-16">NO</th>
                                    <th class="px-4 py-3 font-bold text-[13px]">Nama Santri</th>
                                    <th class="px-4 py-3 font-bold text-[13px] text-center w-48">Nilai</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                                <?php 
                                $no = 1;
                                if(mysqli_num_rows($q_santri) > 0):
                                    while($s = mysqli_fetch_assoc($q_santri)): 
                                        // Ambil nilai yang sudah ada di database
                                        $q_nilai = mysqli_query($koneksi, "SELECT nilai_angka FROM nilai WHERE santri_id = {$s['id']} AND mapel_id = $mapel_id");
                                        $nilai_db = mysqli_fetch_assoc($q_nilai);
                                        $nilai_sekarang = $nilai_db ? $nilai_db['nilai_angka'] : '';
                                ?>
                                <tr class="hover:bg-gray-50 transition h-[50px]">
                                    <td class="px-4 py-2 text-center"><?= $no++ ?></td>
                                    <td class="px-4 py-2 font-semibold"><?= htmlspecialchars($s['nama_santri']) ?></td>
                                    <td class="px-4 py-2 text-center">
                                        <!-- Input nilai dengan type number -->
                                        <input type="number" name="nilai[<?= $s['id'] ?>]" value="<?= $nilai_sekarang ?>" class="w-24 px-3 py-1.5 border border-gray-300 rounded-lg text-center font-bold text-[14px] focus:ring-2 focus:ring-[#0b6b53] outline-none transition-shadow active:scale-95" min="0" max="100" placeholder="0" required>
                                    </td>
                                </tr>
                                <?php endwhile; else: ?>
                                    <tr><td colspan="3" class="p-8 text-center text-gray-500 font-normal">Tidak ada data santri di kelas ini.</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                </form>
            </div>
            <?php endif; ?>

        </main>
    </div>

    <!-- SCRIPT UNTUK FILTER PENCARIAN DI TABEL DETAIL -->
    <script>
        function filterTable() {
            // Ambil nilai input
            var input = document.getElementById("searchSantriDetail");
            var filter = input.value.toLowerCase();
            // Ambil tabel dan baris
            var table = document.getElementById("tabelNilai");
            var tr = table.getElementsByTagName("tr");

            // Loop melalui semua baris tabel, sembunyikan yang tidak cocok dengan pencarian
            for (var i = 1; i < tr.length; i++) { // Mulai dari 1 karena index 0 adalah header
                var td = tr[i].getElementsByTagName("td")[1]; // Index 1 adalah kolom Nama Santri
                if (td) {
                    var txtValue = td.textContent || td.innerText;
                    if (txtValue.toLowerCase().indexOf(filter) > -1) {
                        tr[i].style.display = "";
                    } else {
                        tr[i].style.display = "none";
                    }
                }
            }
        }
    </script>
</body>
</html>