<?php session_start(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Lupa Password - TQA</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>@import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap'); body { font-family: 'Poppins', sans-serif; }</style>
</head>
<body class="bg-gray-50 h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-2xl shadow-lg w-[400px]">
        <h2 class="text-2xl font-bold text-[#0b6b53] mb-2 text-center">Lupa Password</h2>
        <p class="text-sm text-gray-500 text-center mb-6">Masukkan email Anda untuk menerima tautan reset password.</p>
        
        <?php if(isset($_GET['msg'])): ?>
            <div class="bg-blue-100 text-blue-700 p-3 rounded mb-4 text-xs text-center"><?= htmlspecialchars($_GET['msg']) ?></div>
        <?php endif; ?>

        <form action="proses_lupa_password.php" method="POST">
            <div class="mb-4">
                <label class="block text-sm font-semibold text-gray-700 mb-1">Email Terdaftar</label>
                <input type="email" name="email" class="w-full px-4 py-2 border rounded-lg focus:ring-2 focus:ring-[#0b6b53] outline-none" required>
            </div>
            <button type="submit" class="w-full bg-[#0b6b53] text-white font-bold py-2.5 rounded-lg hover:bg-green-800 transition">Kirim Link Reset</button>
            <div class="text-center mt-4">
                <a href="login.php" class="text-sm text-gray-500 hover:text-[#0b6b53]">Kembali ke Login</a>
            </div>
        </form>
    </div>
</body>
</html>