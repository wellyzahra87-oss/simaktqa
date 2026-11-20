<?php
include 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = mysqli_real_escape_string($koneksi, $_POST['token']);
    $password_baru = md5($_POST['password']); // Menggunakan MD5 sesuai sistem Anda

    // Cek apakah token valid
    $cek = mysqli_query($koneksi, "SELECT * FROM password_resets WHERE token = '$token'");
    
    if (mysqli_num_rows($cek) > 0) {
        $data = mysqli_fetch_assoc($cek);
        $email = $data['email'];
        
        // Update password di tabel users
        mysqli_query($koneksi, "UPDATE users SET password = '$password_baru' WHERE email = '$email'");
        
        // Hapus token agar tidak bisa dipakai lagi
        mysqli_query($koneksi, "DELETE FROM password_resets WHERE token = '$token'");
        
        header("Location: login.php?reset=success");
    } else {
        echo "Token tidak valid atau sudah kedaluwarsa.";
    }
}
?>