<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "db_tqa_darussalam";

$koneksi = mysqli_connect($host, $user, $pass, $db);

if (!$koneksi) {
    die("Koneksi database gagal: " . mysqli_connect_error());
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Fungsi Warna Status Badge (diperbaiki: handle 'Belum Dimulai' & 'Sedang Berlangsung')
function get_status_badge($status) {
    $s = strtolower(trim($status));
    if (in_array($s, ['aktif', 'hadir', 'lulus', 'berlangsung', 'sedang berlangsung', 'tersedia'])) {
        return 'bg-[#e6f7ec] text-[#0b6b53]';
    } elseif (in_array($s, ['nonaktif', 'tidak hadir', 'tidak lulus', 'tidak tersedia', 'alpha'])) {
        return 'bg-[#fde2e2] text-[#e53e3e]';
    } elseif (in_array($s, ['akan datang', 'izin', 'belum', 'belum dimulai'])) {
        return 'bg-[#fef3c7] text-[#d97706]';
    } elseif (in_array($s, ['selesai'])) {
        return 'bg-[#e5e7eb] text-[#4b5563]';
    } elseif (in_array($s, ['sakit'])) {
        return 'bg-[#fce7f3] text-[#db2777]';
    }
    return 'bg-gray-100 text-gray-800';
}

$hari = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$bulan = ['', 'Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$tgl_sekarang = $hari[date('w')] . ", " . date('j') . " " . $bulan[date('n')] . " " . date('Y');
?>