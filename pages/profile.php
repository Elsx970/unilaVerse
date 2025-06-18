<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = intval($_SESSION['user_id']);



// Ambil data user saat ini
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
if (!$user) {
    die("Pengguna tidak ditemukan.");
}

$profilePic = $user['profile_picture'] ?: 'uploads/profile/default.png';

// Hitung follower & following
$follower_count = $conn->query("SELECT COUNT(*) AS total FROM followers WHERE followed_id = $user_id")->fetch_assoc()['total'];
$following_count = $conn->query("SELECT COUNT(*) AS total FROM followers WHERE follower_id = $user_id")->fetch_assoc()['total'];

// Ambil postingan user sendiri dengan data tambahan
$posts = $conn->query("
    SELECT p.*, u.username, u.profile_picture, c.name AS community_name,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id AND user_id = $user_id) AS liked
    FROM posts p
    JOIN users u ON p.user_id = u.id
    JOIN communities c ON p.community_id = c.id
    WHERE p.user_id = $user_id
    ORDER BY p.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Profil Saya - UnilaVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<!-- Navbar -->
<nav class="bg-white shadow p-4 flex justify-between items-center">
    <a href="home.php">
        <img src="../assets/logo.png" alt="UnilaVerse Logo" class="h-16 w-auto object-contain hover:scale-150 transition-transform duration-200" />
    </a>
    <div class="flex items-center space-x-6">
        <a href="home.php" class="text-blue-600 font-medium hover:underline">Beranda</a>
        <a href="dashboard.php" class="text-blue-600 font-medium hover:underline">Komunitas</a>
        <a href="following_posts.php" class="text-blue-600 font-medium hover:underline">Follow Post</a>

        <div class="relative">
            <input type="text" id="searchInput" name="q"
                   placeholder="Cari pengguna..."
                   class="border px-2 py-1 rounded-md text-sm w-64">
            <div id="searchResults"
                 class="absolute z-50 bg-white border rounded-md shadow w-64 mt-1 hidden max-h-64 overflow-y-auto"></div>
        </div>

        <div class="relative">
            <button id="dropdownToggle" class="focus:outline-none flex items-center space-x-2">
                <img src="../<?= htmlspecialchars($profilePic) ?>" alt="Profil"
                     onerror="this.onerror=null;this.src='../assets/profile.jpg';"
                     class="w-10 h-10 rounded-full object-cover border border-gray-300 hover:ring-2 ring-blue-400 transition" />
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="dropdownMenu" class="absolute right-0 mt-2 w-44 bg-white border rounded shadow-lg hidden z-10">
                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">👤 Lihat Profil</a>
                <a href="logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-100">🔓 Logout</a>
            </div>
        </div>
    </div>
</nav>

<!-- Konten Utama -->
<main class="max-w-3xl mx-auto mt-10 bg-white p-6 rounded-xl shadow-md">
    <div class="flex flex-col items-center mb-6">
        <img src="../<?= htmlspecialchars($profilePic) ?>" 
             alt="Foto Profil" 
             onerror="this.onerror=null; this.src='../assets/profile.jpg';"
             class="w-28 h-28 rounded-full object-cover border-4 border-blue-200 shadow-md">
        <h2 class="text-2xl font-bold text-blue-700 mt-4"><?= htmlspecialchars($user['username']) ?></h2>
        <p class="text-gray-600"><?= htmlspecialchars($user['email']) ?></p>

        <div class="flex space-x-6 mt-4 text-center">
            <div>
                <p class="text-lg font-semibold text-blue-600"><?= $follower_count ?></p>
                <p class="text-sm text-gray-500">Follower</p>
            </div>
            <div>
                <p class="text-lg font-semibold text-blue-600"><?= $following_count ?></p>
                <p class="text-sm text-gray-500">Mengikuti</p>
            </div>
        </div>

        <!-- Biodata Lengkap -->
        <div class="mt-6 text-sm text-gray-700 w-full text-left space-y-1 bg-gray-50 p-4 rounded">
            <?php if (!empty($user['biodata'])): ?>
                <p><span class="font-semibold">Bio:</span> <?= nl2br(htmlspecialchars($user['biodata'])) ?></p>
            <?php endif; ?>
            <?php if (!empty($user['birthdate'])): ?>
                <p><span class="font-semibold">Tanggal Lahir:</span> <?= htmlspecialchars(date("d M Y", strtotime($user['birthdate']))) ?></p>
            <?php endif; ?>
            <?php if (!empty($user['gender'])): ?>
                <p><span class="font-semibold">Jenis Kelamin:</span> <?= htmlspecialchars($user['gender']) ?></p>
            <?php endif; ?>
            <?php if (!empty($user['location'])): ?>
                <p><span class="font-semibold">Domisili:</span> <?= htmlspecialchars($user['location']) ?></p>
            <?php endif; ?>
        </div>

        <a href="edit_profile.php" class="block w-full text-center mt-6 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Edit Profil</a>
    </div>

    <!-- Postingan -->
    <section class="max-w-4xl mx-auto mt-6 space-y-6">
    <?php while ($post = $posts->fetch_assoc()): ?>
    <div class="bg-white p-4 rounded shadow">
        <!-- Header -->
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center space-x-3">
            <a href="../pages/user_profile.php?id=<?= $post['user_id'] ?>">
                <img src="../<?= htmlspecialchars(!empty($post['profile_picture']) ? $post['profile_picture'] : 'uploads/profile/default.png') ?>"
                 alt="Profil"
                 onerror="this.onerror=null; this.src='../assets/profile.jpg';"
                 class="w-10 h-10 rounded-full object-cover border border-gray-300 hover:ring-2 ring-blue-400 transition" />
               </a>
                <a href="../pages/user_profile.php?id=<?= $post['user_id'] ?>" class="font-semibold text-blue-700 hover:underline">
                    @<?= htmlspecialchars($post['username']) ?>
                </a>
            </div>
            <span class="text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($post['created_at'])) ?></span>
        </div>

        <!-- Content -->
        <a href="post_detail.php?id=<?= $post['id'] ?>">
            <p class="mb-2 line-clamp-4 hover:underline"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
            <?php if (!empty($post['image_path'])): ?>
                <img src="../<?= $post['image_path'] ?>" class="rounded my-2 max-h-64 object-contain" />
            <?php endif; ?>
            <?php if (!empty($post['video_path'])): ?>
                <video controls class="w-full rounded my-2">
                    <source src="../<?= $post['video_path'] ?>" />
                    Browser tidak mendukung video.
                </video>
            <?php endif; ?>
        </a>

        <!-- Like Button -->
        <div class="flex justify-between items-center mt-4">
            <label>
                <input class="peer hidden" type="checkbox" <?= $post['liked'] ? 'checked' : '' ?> />
                <div class="like-btn group flex w-fit cursor-pointer items-center gap-2 overflow-hidden border rounded-full border-blue-700 fill-none p-2 px-3 font-extrabold text-blue-500 transition-all active:scale-90 peer-checked:fill-blue-500 peer-checked:hover:text-white"
                     data-post-id="<?= $post['id'] ?>" data-liked="<?= $post['liked'] ? '1' : '0' ?>">
                    <div class="z-10 transition group-hover:translate-x-4 like-text"><?= $post['like_count'] ?> suka</div>
                    <svg class="size-6 transition duration-500 group-hover:scale-[1100%]" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                        <path d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z" stroke-linejoin="round" stroke-linecap="round"></path>
                    </svg>
                </div>
            </label>

            <a href="post_detail.php?id=<?= $post['id'] ?>" class="text-sm text-blue-500 hover:underline">Lihat Detail & Komentar</a>
        </div>

        <!-- Komentar Preview -->
<div class="mt-4 space-y-3">
    <?php
    $preview_comments = $conn->query("
        SELECT c.comment, c.created_at, u.id AS user_id, u.username, u.profile_picture 
        FROM comments c 
        JOIN users u ON c.user_id = u.id 
        WHERE post_id = " . intval($post['id']) . " 
        ORDER BY c.created_at ASC LIMIT 2
    ");
    while ($comment = $preview_comments->fetch_assoc()): ?>
        <div class="border border-gray-200 rounded-md p-3 flex items-start gap-2 bg-gray-50">
            <!-- Foto Profil -->
            <a href="../pages/user_profile.php?id=<?= $comment['user_id'] ?>">
            <img src="../<?= htmlspecialchars(!empty($comment['profile_picture']) ? $comment['profile_picture'] : 'uploads/profile/default.png') ?>"
         alt="Profil"
         onerror="this.onerror=null; this.src='../assets/profile.jpg';"
         class="w-10 h-10 rounded-full object-cover border border-gray-300" />
          </a>

            <!-- Konten Komentar -->
            <div class="flex-1 min-w-0">
                <!-- Username -->
                <a href="../pages/user_profile.php?id=<?= $comment['user_id'] ?>"
                   class="font-medium text-sm text-blue-600 hover:underline">
                    @<?= htmlspecialchars($comment['username']) ?>
                </a>

                <!-- Tanggal -->
                <p class="text-xs text-gray-500 mt-1">
                    <?= date('d M Y H:i', strtotime($comment['created_at'])) ?>
                </p>
                <!-- Isi Komentar -->
                <p class="text-sm text-gray-800 whitespace-pre-line break-words leading-snug mt-0.1 overflow-hidden">
                    <?= nl2br(htmlspecialchars($comment['comment'])) ?>
                </p>
            </div>
        </div>

                <?php endwhile; ?>
            </div>
        </div>
    <?php endwhile; ?>
</section>

</main>

<!-- Script Toggle -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Like button AJAX
    document.querySelectorAll('.like-btn').forEach(btn => {
        btn.addEventListener("click", async () => {
            const postId = btn.dataset.postId;
            btn.classList.add('scale-95', 'transition', 'duration-100');
            setTimeout(() => btn.classList.remove('scale-95'), 150);
            const res = await fetch('like_post.php', {
                method: 'POST',
                headers: {'Content-Type':'application/x-www-form-urlencoded'},
                body: `post_id=${postId}`
            });
            const data = await res.json();
            if (data.success) {
                btn.querySelector('.like-text').innerText = `${data.like_count} suka`;
                btn.dataset.liked = data.liked ? '1' : '0';
                btn.previousElementSibling.checked = data.liked;
            }
        });
    });

    // Dropdown profil
    const dp = document.getElementById("dropdownToggle");
    const dm = document.getElementById("dropdownMenu");
    dp.addEventListener("click", e => {
        e.stopPropagation();
        dm.classList.toggle("hidden");
    });
    document.addEventListener("click", () => dm.classList.add("hidden"));

    // Live user search popup (sama)
    const input = document.getElementById("searchInput");
    const results = document.getElementById("searchResults");
    input.addEventListener("input", async () => {
        const q = input.value.trim();
        if (!q) return results.classList.add("hidden");
        const users = await fetch(`search_user_api.php?q=${encodeURIComponent(q)}`).then(r=>r.json());
        if (users.length === 0) {
            results.innerHTML = '<div class="px-4 py-2 text-gray-500 text-sm">Tidak ditemukan.</div>';
        } else {
            results.innerHTML = users.map(u => `
                <a href="user_profile.php?id=${u.id}" class="flex items-center px-4 py-2 hover:bg-gray-100 space-x-3">
                <img src="../${u.profile_picture || 'uploads/profile/default.png'}" 
                  onerror="this.onerror=null; this.src='../assets/profile.jpg';" 
                   class="w-8 h-8 rounded-full border"/>
                  <span class="text-sm text-gray-700">@${u.username}</span>
                 </a>
            `).join('');
        }
        results.classList.remove("hidden");
    });
    document.addEventListener("click", e => {
        if (!results.contains(e.target) && e.target !== input) results.classList.add("hidden");
    });
});
</script>

</body>
</html>
