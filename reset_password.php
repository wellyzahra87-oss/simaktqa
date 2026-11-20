<?php 
include 'koneksi.php';
$token = $_GET['token'] ?? '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Reset Password - TQA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap'); body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-[400px]">
        <h2 class="text-2xl font-bold text-[#0b6b53] mb-6 text-center">Buat Password Baru</h2>
        
        <form action="proses_reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token) ?>">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Password Baru</label>
                <input type="password" name="password" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none" required minlength="6">
            </div>
            <button type="submit" class="w-full bg-[#0b6b53] text-white font-bold py-2.5 rounded-lg hover:bg-green-800 transition">Simpan Password Baru</button>
        </form>
    </div>
</body>
</html>