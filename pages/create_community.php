<?php
session_start();
include '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $desc = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    // Simpan komunitas
    $stmt = $conn->prepare("INSERT INTO communities (name, description) VALUES (?, ?)");
    $stmt->bind_param("ss", $name, $desc);
    $stmt->execute();

    $new_id = $conn->insert_id;

    // Otomatis jadi anggota komunitas
    $conn->query("INSERT INTO community_members (user_id, community_id) VALUES ($user_id, $new_id)");

    header("Location: dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Buat Komunitas | UnilaVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gradient-to-br from-blue-50 to-blue-100 min-h-screen font-sans text-gray-800">
    <div class="max-w-xl mx-auto mt-20 bg-white p-8 rounded-2xl shadow-xl">
        <h2 class="text-2xl font-bold text-blue-700 mb-6 text-center">Buat Komunitas Baru</h2>

        <form method="POST" class="space-y-5">
            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Nama Komunitas</label>
                <input type="text" name="name" required placeholder="Contoh: Pecinta Koding Unila"
                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400">
            </div>

            <div>
                <label class="block mb-2 text-sm font-medium text-gray-700">Deskripsi Komunitas</label>
                <textarea name="description" required rows="4" placeholder="Jelaskan tentang komunitas ini..."
                    class="w-full p-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-400"></textarea>
            </div>

            <button type="submit"
                class="w-full py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition duration-200 shadow">
                + Buat Komunitas
            </button>
        </form>

        <div class="text-center mt-6">
            <a href="dashboard.php" class="text-sm text-blue-500 hover:underline">← Kembali ke Dashboard</a>
        </div>
    </div>
</body>
</html>
