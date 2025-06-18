<?php
session_start();
include '../includes/db.php';

if (!isset($_GET['q']) || empty(trim($_GET['q']))) {
    echo "Masukkan kata kunci pencarian.";
    exit;
}

$keyword = trim($_GET['q']);

// Cari username yang mirip
$stmt = $conn->prepare("SELECT id, username, profile_picture FROM users WHERE username LIKE ?");
$like = "%$keyword%";
$stmt->bind_param("s", $like);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Hasil Pencarian</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<div class="max-w-3xl mx-auto p-4">
    <h1 class="text-2xl font-bold mb-4">Hasil Pencarian untuk: "<?= htmlspecialchars($keyword) ?>"</h1>

    <?php if ($result->num_rows > 0): ?>
        <ul class="space-y-4">
            <?php while ($user = $result->fetch_assoc()): ?>
                <li class="bg-white p-4 rounded shadow flex items-center space-x-4">
                    <img src="../<?= $user['profile_picture'] ?: 'uploads/profile/default.png' ?>" 
                         alt="Profil" 
                         class="w-12 h-12 rounded-full object-cover border" />
                    <div>
                        <a href="user_profile.php?id=<?= $user['id'] ?>" class="text-blue-600 font-medium hover:underline">
                            @<?= htmlspecialchars($user['username']) ?>
                        </a>
                    </div>
                </li>
            <?php endwhile; ?>
        </ul>
    <?php else: ?>
        <p class="text-gray-500">Tidak ada pengguna ditemukan.</p>
    <?php endif; ?>
</div>

</body>
</html>
