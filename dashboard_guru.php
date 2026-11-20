<?php 
include 'koneksi.php'; 

// Proteksi Halaman (Hanya untuk Guru) - TIDAK DIUBAH
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Guru') {
    header("Location: login.php");
    exit;
}

// Ambil ID Guru dari Session
$guru_id = $_SESSION['user_id'] ?? $_SESSION['id'];

// === Ambil Statistik dari Database ===
$hari_ini = $hari[date('w')];

// 1. Jadwal Hari Ini (DIPERBAIKI: Menggunakan GROUP BY agar tidak duplikat)
$q_jadwal_hari_ini = mysqli_query($koneksi, "SELECT j.*, k.nama_kelas, r.nama_ruangan, r.keterangan, m.nama_mapel 
    FROM jadwal j 
    JOIN kelas k ON j.kelas_id = k.id 
    LEFT JOIN ruangan r ON k.ruangan_id = r.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id 
    WHERE j.guru_id = $guru_id AND j.hari = '$hari_ini' 
    GROUP BY j.kelas_id, j.mapel_id
    ORDER BY j.jam_mulai ASC");
$total_hari_ini = mysqli_num_rows($q_jadwal_hari_ini);

// 2. Total Mata Pelajaran yang Diampu
$q_total_mapel = mysqli_query($koneksi, "SELECT COUNT(DISTINCT mapel_id) as c FROM jadwal WHERE guru_id = $guru_id");
$total_mapel = mysqli_fetch_assoc($q_total_mapel)['c'];

// 3. Jadwal yang Sudah Selesai (Sepanjang Semester/Periode)
$q_selesai = mysqli_query($koneksi, "SELECT COUNT(*) as c FROM jadwal WHERE guru_id = $guru_id AND status = 'Selesai'");
$total_selesai = mysqli_fetch_assoc($q_selesai)['c'];

// Format Tanggal Indonesia
$tgl_sekarang = $hari[date('w')] . ", " . date('j') . " " . $bulan[date('n')] . " " . date('Y');
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Guru - TQA Darussalam</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="flex h-screen overflow-hidden bg-[#f8fafc] font-sans">

    <?php include 'sidebar.php'; ?>
    
    <div class="flex-1 flex flex-col ml-64 h-screen overflow-y-auto">
        <?php include 'header.php'; ?>
        
        <main class="p-6 md:p-8">
            
            <!-- Judul Halaman -->
            <div class="flex items-center gap-3 mb-6">
                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center transition-transform duration-300 hover:scale-110 shadow-sm">
                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M3 13h8V3H3v10zm0 8h8v-6H3v6zm10 0h8V11h-8v10zm0-18v6h8V3h-8z"/></svg>
                </div>
                <div>
                    <h1 class="text-[22px] font-bold text-gray-900 leading-none mb-1 tracking-tight">Dashboard Guru</h1>
                    <p class="text-[13px] font-medium text-gray-600">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>...</p>
                </div>
            </div>

            <!-- 3 Kartu Statistik -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-5 mb-8">
                
                <!-- Jadwal Hari ini -->
                <div class="group bg-white p-5 rounded-2xl shadow-[0_2px_10px_-3px_rgba(11,107,83,0.15)] border border-[#e6f4f1] flex items-center gap-4 transition-all duration-300 hover:shadow-[0_8px_25px_-5px_rgba(11,107,83,0.25)] hover:-translate-y-1 cursor-default">
                    <div class="w-[48px] h-[48px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0 transition-colors duration-300 group-hover:bg-[#d1ebe4]">
                        <svg class="w-6 h-6 text-[#0b6b53] transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[14px] font-bold text-black leading-tight transition-colors duration-300 group-hover:text-[#0b6b53]">Jadwal Hari ini</p>
                        <p class="text-[24px] font-extrabold text-black leading-none mt-1"><?= $total_hari_ini ?></p>
                    </div>
                </div>

                <!-- Total Mata Pelajaran -->
                <div class="group bg-white p-5 rounded-2xl shadow-[0_2px_10px_-3px_rgba(11,107,83,0.15)] border border-[#e6f4f1] flex items-center gap-4 transition-all duration-300 hover:shadow-[0_8px_25px_-5px_rgba(11,107,83,0.25)] hover:-translate-y-1 cursor-default">
                    <div class="w-[48px] h-[48px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0 transition-colors duration-300 group-hover:bg-[#d1ebe4]">
                        <svg class="w-6 h-6 text-[#0b6b53] transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[14px] font-bold text-black leading-tight transition-colors duration-300 group-hover:text-[#0b6b53]">Total Mata Pelajaran</p>
                        <p class="text-[24px] font-extrabold text-black leading-none mt-1"><?= $total_mapel ?></p>
                    </div>
                </div>

                <!-- Jadwal yang sudah selesai -->
                <div class="group bg-white p-5 rounded-2xl shadow-[0_2px_10px_-3px_rgba(11,107,83,0.15)] border border-[#e6f4f1] flex items-center gap-4 transition-all duration-300 hover:shadow-[0_8px_25px_-5px_rgba(11,107,83,0.25)] hover:-translate-y-1 cursor-default">
                    <div class="w-[48px] h-[48px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0 transition-colors duration-300 group-hover:bg-[#d1ebe4]">
                        <svg class="w-6 h-6 text-[#0b6b53] transition-transform duration-300 group-hover:scale-110" fill="currentColor" viewBox="0 0 24 24"><path d="M20 3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-1 11h-6v-2h6v2zm0-4h-6V8h6v2z"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[14px] font-bold text-black leading-tight transition-colors duration-300 group-hover:text-[#0b6b53]">Jadwal yang sudah<br>selesai</p>
                        <p class="text-[24px] font-extrabold text-black leading-none mt-1"><?= $total_selesai ?></p>
                    </div>
                </div>
            </div>

            <!-- Tabel Jadwal Hari Ini -->
            <div class="bg-white rounded-2xl shadow-[0_2px_15px_-3px_rgba(11,107,83,0.1)] border border-[#e6f4f1] overflow-hidden transition-shadow duration-300 hover:shadow-[0_8px_30px_-5px_rgba(11,107,83,0.15)]">
                
                <!-- Header Tabel -->
                <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center bg-white">
                    <h3 class="text-[16px] font-bold text-black tracking-tight">Jadwal Hari ini</h3>
                    <p class="text-[13px] font-bold text-gray-800"><?= $tgl_sekarang ?></p>
                </div>
                
                <!-- Tabel -->
                <table class="w-full text-left border-collapse">
                    <thead class="bg-[#f0f7f4] text-[#0b6b53]">
                        <tr>
                            <th class="px-4 py-2.5 font-bold text-[13px] text-center w-16">No</th>
                            <th class="px-4 py-2.5 font-bold text-[13px] text-center">Waktu</th>
                            <th class="px-4 py-2.5 font-bold text-[13px] text-center">Kelas</th>
                            <th class="px-4 py-2.5 font-bold text-[13px] text-center">Ruangan</th>
                            <th class="px-4 py-2.5 font-bold text-[13px] text-center">Mata Pelajaran</th>
                            <th class="px-4 py-2.5 font-bold text-[13px] text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 text-[13px] font-medium text-gray-800">
                        <?php if($total_hari_ini > 0): ?>
                            <?php $no = 1; mysqli_data_seek($q_jadwal_hari_ini, 0); while($row = mysqli_fetch_assoc($q_jadwal_hari_ini)): 
                                // Menyesuaikan warna badge dengan desain
                                $status = $row['status'];
                                if ($status == 'Sedang Berlangsung') {
                                    $badge_class = 'bg-[#e6f7ec] text-[#0b6b53]'; // Hijau
                                    $status_text = 'Berlangsung';
                                } elseif ($status == 'Belum Dimulai') {
                                    $badge_class = 'bg-[#fde2e2] text-[#e53e3e]'; // Merah
                                    $status_text = 'Belum';
                                } else {
                                    $badge_class = 'bg-gray-200 text-gray-500'; // Abu-abu
                                    $status_text = 'Selesai';
                                }
                            ?>
                            <tr class="hover:bg-gray-50 transition-all duration-200 ease-in-out hover:shadow-[0_2px_10px_-4px_rgba(0,0,0,0.05)]">
                                <td class="px-4 py-3 text-center"><?= $no++ ?></td>
                                <td class="px-4 py-3 text-center"><?= date('H.i', strtotime($row['jam_mulai'])) ?> - <?= date('H.i', strtotime($row['jam_selesai'])) ?></td>
                                <td class="px-4 py-3 text-center uppercase"><?= htmlspecialchars($row['nama_kelas']) ?></td>
                                <td class="px-4 py-3 text-center">
                                    <div class="leading-tight">
                                        <span class="block text-black"><?= htmlspecialchars($row['nama_ruangan'] ?? '-') ?></span>
                                        <span class="block text-[11px] font-semibold text-gray-500 italic">(<?= htmlspecialchars($row['keterangan'] ?? '-') ?>)</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center"><?= htmlspecialchars($row['nama_mapel']) ?></td>
                                <td class="px-4 py-2 text-center">
                                    <span class="px-3 py-1.5 rounded-lg text-[11px] font-bold <?= $badge_class ?> w-full block transition-all duration-200 hover:scale-[1.03] shadow-sm active:scale-95">
                                        <?= $status_text ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" class="p-8 text-center text-gray-500 font-normal text-[13px]">Tidak ada jadwal mengajar untuk hari ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>