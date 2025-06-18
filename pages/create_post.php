<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}


$community_id = $_GET['community_id'] ?? null;

if (!$community_id) {
    echo "Community ID tidak ditemukan.";
    exit();
}

// Ambil informasi komunitas
$community = $conn->query("SELECT * FROM communities WHERE id = $community_id")->fetch_assoc();

if (!$community) {
    echo "Komunitas tidak ditemukan.";
    exit();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Buat Postingan - <?= htmlspecialchars($community['name']) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">


    <main class="max-w-3xl mx-auto mt-10 bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-bold text-blue-700 mb-4">Buat Postingan di <?= htmlspecialchars($community['name']) ?></h2>
        <form action="post_upload.php" method="POST" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="community_id" value="<?= $community_id ?>" />
            <label for="content" class="block font-medium mb-1">Isi Postingan</label>
            <textarea id="content" name="content" required placeholder="Apa yang ingin kamu sampaikan?" autofocus
                class="w-full p-3 border border-gray-300 rounded focus:outline-none focus:ring-2 focus:ring-blue-400" rows="5"></textarea>

            <div>
                <label for="image" class="block mb-1 font-medium">Gambar (opsional, max 5MB)</label>
                <input type="file" id="image" name="image" accept="image/png, image/jpeg, image/jpg, image/gif" class="w-full" />
            </div>
            <div>
                <label for="video" class="block mb-1 font-medium">Video (opsional, max 50MB)</label>
                <input type="file" id="video" name="video" accept="video/mp4, video/webm, video/ogg" class="w-full" />
            </div>

            <div class="flex justify-between items-center">
                <a href="community_detail.php?id=<?= $community_id ?>" class="text-gray-500 hover:underline">← Kembali</a>
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">Kirim</button>
            </div>
        </form>
    </main>
</body>
</html>

