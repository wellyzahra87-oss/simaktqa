<?php
session_start();
include 'koneksi.php';

// Panggil Library PHPMailer
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/Exception.php';
require 'PHPMailer/PHPMailer.php';
require 'PHPMailer/SMTP.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email = mysqli_real_escape_string($koneksi, $_POST['email']);
    
    // Cek apakah email terdaftar
    $cek = mysqli_query($koneksi, "SELECT * FROM users WHERE email = '$email'");
    if (mysqli_num_rows($cek) > 0) {
        // Buat token unik
        $token = bin2hex(random_bytes(50));
        
        // Hapus token lama jika ada, simpan token baru
        mysqli_query($koneksi, "DELETE FROM password_resets WHERE email = '$email'");
        mysqli_query($koneksi, "INSERT INTO password_resets (email, token) VALUES ('$email', '$token')");
        
        // >>> PENTING: GANTI 'simaktqa' DENGAN NAMA FOLDER ASLI PROYEK ANDA DI HTDOCS <<<
        // Jika Anda membuka di browser "http://localhost/simaktqa/login.php", maka tulis "simaktqa"
        $reset_link = "http://localhost/simaktqa/reset_password.php?token=" . $token;
        
        // ==========================================
        // KONFIGURASI PENGIRIMAN EMAIL (MAILTRAP)
        // ==========================================
        $mail = new PHPMailer(true);

        try {
            $mail->isSMTP();
            $mail->Host       = 'sandbox.smtp.mailtrap.io'; 
            $mail->SMTPAuth   = true;
            
            // Kredensial Mailtrap Anda
            $mail->Username   = 'f876129c987f86'; 
            $mail->Password   = '119ad2e7324b67'; 
            
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port       = 2525; 

            // Pengirim & Penerima
            $mail->setFrom('admin@tqa.com', 'Admin TQA Darussalam');
            $mail->addAddress($email);

            // Konten Email
            $mail->isHTML(true);
            $mail->Subject = 'Reset Password Akun TQA Darussalam';
            $mail->Body    = "
                <h3>Halo,</h3>
                <p>Kami menerima permintaan untuk mereset password akun Anda di Sistem Informasi Akademik TQA Darussalam.</p>
                <p>Silakan klik tombol di bawah ini untuk membuat password baru:</p>
                <p><a href='$reset_link' style='background-color:#0b6b53; color:white; padding:10px 20px; text-decoration:none; border-radius:5px; display:inline-block;'>Reset Password</a></p>
                <p>Atau copy-paste link berikut ke browser Anda:<br>$reset_link</p>
                <br>
                <p>Jika Anda tidak merasa melakukan permintaan ini, silakan abaikan email ini.</p>
            ";

            $mail->send();
            
            // Jika berhasil
            header("Location: lupa_password.php?msg=Link+reset+telah+dikirim+ke+email+Anda!");
            
        } catch (Exception $e) {
            // Jika gagal, tampilkan error dari PHPMailer
            echo "<div style='font-family:sans-serif; padding:20px; text-align:center;'>";
            echo "<h3 style='color:red;'>Gagal mengirim email.</h3>";
            echo "<p>Error: {$mail->ErrorInfo}</p>";
            echo "<a href='lupa_password.php'>Kembali</a>";
            echo "</div>";
        }
        
    } else {
        header("Location: lupa_password.php?msg=Email+tidak+terdaftar!");
    }
}
?>