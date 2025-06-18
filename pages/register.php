<?php
include '../includes/db.php';

$error = '';
// Variabel untuk menyimpan nilai isian agar tidak hilang saat terjadi error
$username_val = '';
$email_val = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Ambil data dari form dan hapus spasi berlebih
    $username = trim($_POST['username']);
    $email    = trim($_POST['email']);
    $password = $_POST['password'];

    // Simpan nilai untuk nanti ditampilkan kembali di form jika terjadi error
    $username_val = $username;
    $email_val = $email;

    // Validasi password minimal 8 karakter dan mengandung huruf serta angka
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $error = 'Password harus minimal 8 karakter dan mengandung huruf serta angka.';
    } else {
        // Cek apakah username atau email sudah ada
        $stmt = $conn->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
        $stmt->bind_param("ss", $username, $email);
        $stmt->execute();
        $stmt->store_result();
        
        if ($stmt->num_rows > 0) {
            $error = 'Username atau Email sudah terdaftar.';
        } else {
            // Jika belum ada, proses registrasi
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            $insert = $conn->prepare("INSERT INTO users (username, email, password) VALUES (?, ?, ?)");
            $insert->bind_param("sss", $username, $email, $hashedPassword);
            $insert->execute();

            header("Location: login.php");
            exit();
        }
        $stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Register - UnilaVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-green-100 to-green-300 min-h-screen flex items-center justify-center">
    <div class="bg-white p-8 rounded-lg shadow-lg w-full max-w-md">
        <h2 class="text-2xl font-bold mb-6 text-center text-green-800">
            Daftar ke <span class="text-green-600">UnilaVerse</span>
        </h2>

        <?php if (!empty($error)): ?>
            <div class="mb-4 text-red-600 text-sm bg-red-100 p-3 rounded">
                <?= htmlspecialchars($error) ?>
            </div>
        <?php endif; ?>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Username</label>
                <input name="username" type="text" required
                       value="<?= htmlspecialchars($username_val) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input name="email" type="email" required
                       value="<?= htmlspecialchars($email_val) ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input name="password" type="password" required minlength="8"
                       pattern="^(?=.*[A-Za-z])(?=.*\d)[A-Za-z\d]{8,}$"
                       title="Password harus minimal 8 karakter dan mengandung huruf serta angka"
                       class="w-full px-4 py-2 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-green-400">
            </div>

            <button type="submit"
                    class="w-full bg-green-600 text-white py-2 rounded hover:bg-green-700 transition">
                Daftar
            </button>
        </form>

        <p class="mt-4 text-center text-sm text-gray-600">
            Sudah punya akun? <a href="login.php" class="text-green-600 hover:underline">Login di sini</a>
        </p>
    </div>
</body>
</html>
