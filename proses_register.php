<?php
session_start();
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nama = mysqli_real_escape_string($koneksi, $_POST['name']);
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = $_POST['password'];
    $confirm = $_POST['password_confirmation'];

    // 1. Cek apakah password cocok
    if ($password !== $confirm) {
        header("Location: register.php?error=Password+dan+Konfirmasi+tidak+cocok!");
        exit;
    }

    // 2. Cek apakah email sudah terdaftar
    $cek_email = mysqli_query($koneksi, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($cek_email) > 0) {
        header("Location: register.php?error=Email+sudah+terdaftar!");
        exit;
    }

    // 3. Simpan ke database (Role default: Admin, password di-hash MD5)
    $password_hash = md5($password);
    $query = "INSERT INTO users (nama, email, password, role, status) VALUES ('$nama', '$email', '$password_hash', 'Admin', 'Aktif')";
    
    if (mysqli_query($koneksi, $query)) {
        // Jika berhasil, arahkan ke login
        header("Location: login.php?success=1");
        exit;
    } else {
        header("Location: register.php?error=Gagal+mendaftar,+coba+lagi!");
        exit;
    }
}
?>