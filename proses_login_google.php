<?php
session_start();
include 'koneksi.php';

// Menerima token JWT dari Google
$jwt = $_POST['credential'] ?? '';

if (empty($jwt)) {
    header("Location: login.php?error=1");
    exit;
}

// Memecah JWT untuk mendapatkan email
$parts = explode('.', $jwt);
$payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);

$email = $payload['email'];
$nama = $payload['name'];

// Cek apakah email terdaftar di database
$cek = mysqli_query($koneksi, "SELECT * FROM users WHERE email = '$email'");

if (mysqli_num_rows($cek) > 0) {
    // Email ditemukan, login!
    $user = mysqli_fetch_assoc($cek);
    
    if ($user['status'] == 'Nonaktif') {
        header("Location: login.php?error=nonaktif");
        exit;
    }

    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email']   = $user['email'];
    $_SESSION['role']    = $user['role']; 
    $_SESSION['nama']    = $user['nama'];

    if ($user['role'] == 'Admin') {
        header("Location: dashboard_admin.php");
    } else {
        header("Location: dashboard_guru.php");
    }
} else {
    // Email belum ada di database.
    // Anda bisa mengarahkan ke halaman register otomatis, atau tolak.
    echo "Email Google Anda belum terdaftar di sistem. Silakan daftar melalui form.";
}
?>