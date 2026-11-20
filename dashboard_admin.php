<?php 
include 'koneksi.php'; 
if(!isset($_SESSION['role']) || $_SESSION['role'] != 'Admin') {
    header("Location: login.php");
    exit;
}

// === Ambil Statistik dari Database ===
$total_santri = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM santri WHERE status='Aktif'"))['c'];
$total_guru   = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM users WHERE role='Guru' AND status='Aktif'"))['c'];
$total_kelas  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM kelas"))['c'];
$total_mapel  = mysqli_fetch_assoc(mysqli_query($koneksi, "SELECT COUNT(*) AS c FROM mata_pelajaran WHERE status='Aktif'"))['c'];

// === Jadwal Hari Ini ===
$hari_ini = $hari[date('w')];

$query_jadwal = mysqli_query($koneksi, "
    SELECT j.*, k.nama_kelas, m.nama_mapel, u.nama AS nama_guru
    FROM jadwal j
    JOIN kelas k ON j.kelas_id = k.id
    JOIN mata_pelajaran m ON j.mapel_id = m.id
    JOIN users u ON j.guru_id = u.id
    WHERE j.hari = '$hari_ini'
    GROUP BY j.kelas_id, j.mapel_id
    ORDER BY j.jam_mulai ASC
");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - TQA Darussalam</title>
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
        
        <main class="flex-1 flex flex-col px-6 py-4 overflow-hidden">
            
            <!-- JUDUL DASHBOARD -->
            <div class="flex items-center gap-3 mb-3 shrink-0">
                <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0 shadow-sm">
                    <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M3 3h8v8H3V3zm0 10h8v8H3v-8zm10 0h8v8h-8v-8zm0-10h8v8h-8V3z"/></svg>
                </div>
                <div>
                    <h1 class="text-[22px] font-bold text-black leading-none mb-1 tracking-tight">Dashboard Admin</h1>
                    <p class="text-[13px] font-semibold text-gray-700">Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>...</p>
                </div>
            </div>

            <!-- 4 KARTU STATISTIK -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-4 shrink-0">
                
                <!-- Total Santri -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-3 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer active:scale-95">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M16 11c1.66 0 2.99-1.34 2.99-3S17.66 5 16 5c-1.66 0-3 1.34-3 3s1.34 3 3 3zm-8 0c1.66 0 2.99-1.34 2.99-3S9.66 5 8 5C6.34 5 5 6.34 5 8s1.34 3 3 3zm0 2c-2.33 0-7 1.17-7 3.5V19h14v-2.5c0-2.33-4.67-3.5-7-3.5zm8 0c-.29 0-.62.02-.97.05 1.16.84 1.97 1.97 1.97 3.45V19h6v-2.5c0-2.33-4.67-3.5-7-3.5z"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[13px] font-bold text-black leading-tight">Total Santri</p>
                        <p class="text-[20px] font-extrabold text-black leading-none mt-1"><?= $total_santri ?></p>
                    </div>
                </div>
                
                <!-- Total Guru -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-3 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer active:scale-95">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M20 17.5c0-2.33-4.67-3.5-7-3.5s-7 1.17-7 3.5V19h14v-1.5zM12 11c1.66 0 2.99-1.34 2.99-3S13.66 5 12 5c-1.66 0-3 1.34-3 3s1.34 3 3 3z"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[13px] font-bold text-black leading-tight">Total Guru</p>
                        <p class="text-[20px] font-extrabold text-black leading-none mt-1"><?= $total_guru ?></p>
                    </div>
                </div>
                
                <!-- Total Kelas -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-3 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer active:scale-95">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v16H6V4zm7 16V4h5v16h-5z"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[13px] font-bold text-black leading-tight">Total Kelas</p>
                        <p class="text-[20px] font-extrabold text-black leading-none mt-1"><?= $total_kelas ?></p>
                    </div>
                </div>
                
                <!-- Mata Pelajaran -->
                <div class="bg-white p-3 rounded-2xl shadow-sm border border-gray-100 flex items-center gap-3 transition-all duration-300 hover:shadow-lg hover:-translate-y-1 cursor-pointer active:scale-95">
                    <div class="w-[40px] h-[40px] bg-[#e6f4f1] rounded-full flex items-center justify-center shrink-0">
                        <svg class="w-5 h-5 text-[#0b6b53]" fill="currentColor" viewBox="0 0 24 24"><path d="M4 13h16v2H4zM4 9h16v2H4zM4 5h16v2H4z"/></svg>
                    </div>
                    <div class="flex flex-col">
                        <p class="text-[13px] font-bold text-black leading-tight">Mata<br>pelajaran</p>
                        <p class="text-[20px] font-extrabold text-black leading-none mt-1"><?= $total_mapel ?></p>
                    </div>
                </div>
            </div>

            <!-- TABEL JADWAL -->
            <div class="bg-white rounded-2xl shadow-lg border border-[#e6f4f1] flex-1 min-h-0 flex flex-col overflow-hidden transition-shadow duration-300">
                
                <!-- Header Tabel -->
                <div class="px-5 py-3 flex justify-between items-center bg-white shrink-0 border-b border-gray-100">
                    <h3 class="text-[18px] font-bold text-black">Jadwal Hari ini</h3>
                    <a href="kelola_jadwal.php" class="font-bold text-[13px] text-black flex items-center gap-1 hover:text-[#0b6b53] transition-all duration-200 hover:gap-2 group">
                        Lihat Semua 
                        <svg class="w-4 h-4 text-black group-hover:text-[#0b6b53] transition-transform duration-300 group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 9l-7 7-7-7"></path></svg>
                    </a>
                </div>
                
                <!-- Tabel -->
                <div class="flex-1 overflow-auto">
                    <table class="w-full text-left border-collapse">
                        <thead class="bg-[#f0f7f4] sticky top-0 z-10 shadow-sm">
                            <tr>
                                <th class="px-4 py-3 font-bold text-[13px] text-[#0b6b53]">Waktu</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-[#0b6b53]">Kelas</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-[#0b6b53]">Mata Pelajaran</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-[#0b6b53]">Guru</th>
                                <th class="px-4 py-3 font-bold text-[13px] text-[#0b6b53] text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if(mysqli_num_rows($query_jadwal) > 0): ?>
                                <?php while($row = mysqli_fetch_assoc($query_jadwal)): 
                                    $status = $row['status'];
                                    if ($status == 'Sedang Berlangsung') {
                                        $badge_class = 'bg-[#d1f4e0] text-[#0b6b53]'; 
                                        $status_text = 'Berlangsung';
                                    } elseif ($status == 'Belum Dimulai') {
                                        $badge_class = 'bg-[#fce7c8] text-[#d97706]'; 
                                        $status_text = 'Akan Datang';
                                    } else {
                                        $badge_class = 'bg-[#e5e7eb] text-[#6b7280]'; 
                                        $status_text = 'Selesai';
                                    }
                                ?>
                                <tr class="hover:bg-gray-50 hover:shadow-sm transition-all duration-200 cursor-default h-[45px]">
                                    <td class="px-4 py-2 text-[13px] font-semibold text-black">
                                        <?= date('H.i', strtotime($row['jam_mulai'])) ?> – <?= date('H.i', strtotime($row['jam_selesai'])) ?>
                                    </td>
                                    <td class="px-4 py-2 text-[13px] font-semibold text-black uppercase">
                                        <?= htmlspecialchars($row['nama_kelas']) ?>
                                    </td>
                                    <td class="px-4 py-2 text-[13px] font-semibold text-black">
                                        <?= htmlspecialchars($row['nama_mapel']) ?>
                                    </td>
                                    <td class="px-4 py-2 text-[13px] font-semibold text-black">
                                        <?= htmlspecialchars($row['nama_guru']) ?>
                                    </td>
                                    <td class="px-4 py-2 text-center">
                                        <span class="block w-full py-1.5 rounded-md text-[12px] font-bold <?= $badge_class ?> shadow-sm">
                                            <?= $status_text ?>
                                        </span>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="5" class="p-8 text-center text-gray-500 font-medium text-[13px]">
                                        Tidak ada jadwal untuk hari ini.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>