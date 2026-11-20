<?php
session_start();
include 'koneksi.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    $password = md5($_POST['password']);

    // 1. Cek Email apakah terdaftar
    $query_email = "SELECT * FROM users WHERE email = '$email'";
    $result_email = mysqli_query($koneksi, $query_email);

    if (mysqli_num_rows($result_email) == 0) {
        // Email tidak ditemukan
        header("Location: login.php?error=email");
        exit;
    }

    $user = mysqli_fetch_assoc($result_email);

    // 2. Cek Password
    if ($user['password'] !== $password) {
        // Password salah
        header("Location: login.php?error=password");
        exit;
    }

    // 3. Cek Status Akun
    if ($user['status'] == 'Nonaktif') {
        // Akun dinonaktifkan
        header("Location: login.php?error=nonaktif");
        exit;
    }

    // 4. Jika semua benar, buat Session
    // (Ditambahkan $_SESSION['id'] agar seragam dengan file lain yang memanggil session id)
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['id']      = $user['id']; 
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role']; 
    $_SESSION['nama']    = $user['nama'];
    
    // 5. Arahkan ke halaman yang sesuai
    if ($user['role'] == 'Admin') {
        header("Location: dashboard_admin.php");
    } elseif ($user['role'] == 'Guru') {
        header("Location: dashboard_guru.php");
    } else {
        header("Location: index.php");
    }
    exit;
}
?>