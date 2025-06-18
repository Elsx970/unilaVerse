<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT username, profile_picture FROM users WHERE id = $user_id")->fetch_assoc();
$profilePic = $user['profile_picture'] ?: 'uploads/profile/default.png';

// Proses pencarian
$search = isset($_GET['q']) ? trim($_GET['q']) : '';
$searchSql = $search ? "AND (p.content LIKE '%" . $conn->real_escape_string($search) . "%')" : "";

// Ambil postingan dari komunitas yang diikuti (kecuali milik sendiri)
$posts = $conn->query("
    SELECT p.*, u.id as user_id, u.username, u.profile_picture,
        c.name AS community_name,
        (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
        EXISTS (SELECT 1 FROM post_likes WHERE user_id = $user_id AND post_id = p.id) AS liked
    FROM community_members cm
    JOIN posts p ON p.community_id = cm.community_id
    JOIN users u ON p.user_id = u.id
    JOIN communities c ON p.community_id = c.id
    WHERE cm.user_id = $user_id AND p.user_id != $user_id $searchSql
    ORDER BY p.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Followed Posts - UnilaVerse</title>
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


    <!-- Heading -->
    <section class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded shadow">
        <h2 class="text-2xl font-semibold text-blue-700 mb-4">Postingan dari Komunitas yang Diikuti</h2>

        <!-- Form Pencarian -->
<form method="GET" class="mb-4 flex gap-2">
    <input type="text" name="q" placeholder="Cari di postingan komunitas..." value="<?= htmlspecialchars($search) ?>" class="border px-2 py-1 rounded-md text-sm w-full">
    <button type="submit" class="text-white bg-blue-600 px-3 py-1 rounded">Cari</button>
</form>

<?php if (isset($_GET['q'])): ?>
    <?php if ($posts->num_rows === 0): ?>
        <div class="text-center text-gray-500 bg-white py-10 rounded shadow">
            <p class="text-lg">Tidak ada postingan yang cocok.</p>
        </div>
    <?php endif; ?>
<?php endif; ?>


    <!-- Posts -->
    <section class="max-w-4xl mx-auto mt-6 space-y-6">
        <?php while ($post = $posts->fetch_assoc()): ?>
            <div class="bg-white p-4 rounded shadow">
                <!-- Header -->
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <a href="../pages/user_profile.php?id=<?= $post['user_id'] ?>">
                            <img src="../<?= htmlspecialchars($post['profile_picture'] ?: 'uploads/profile/default.png') ?>" onerror="this.onerror=null;this.src='../assets/profile.jpg';" class="w-10 h-10 rounded-full border object-cover" alt="Profil">
                        </a>
                        <div>
                            <a href="user_profile.php?id=<?= $post['user_id'] ?>" class="font-semibold text-blue-700 hover:underline">@<?= htmlspecialchars($post['username']) ?></a>
                            <p class="text-sm text-gray-500">di <span class="font-semibold"><?= htmlspecialchars($post['community_name']) ?></span></p>
                        </div>
                    </div>
                    <span class="text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($post['created_at'])) ?></span>
                </div>

                <!-- Konten -->
                <a href="post_detail.php?id=<?= $post['id'] ?>">
                    <p class="mb-2 line-clamp-4 hover:underline"><?= nl2br(htmlspecialchars($post['content'])) ?></p>
                    <?php if ($post['image_path']): ?>
                        <img src="../<?= htmlspecialchars($post['image_path']) ?>" class="rounded my-2 max-h-64 w-full object-contain">
                    <?php endif; ?>
                    <?php if ($post['video_path']): ?>
                        <video controls class="w-full rounded my-2">
                            <source src="../<?= htmlspecialchars($post['video_path']) ?>">
                            Browser tidak mendukung video.
                        </video>
                    <?php endif; ?>
                </a>

                <!-- Like dan Detail -->
                <div class="flex justify-between items-center mt-4">
                    <label>
                        <input class="peer hidden" type="checkbox" <?= $post['liked'] ? 'checked' : '' ?> />
                        <div class="like-btn group flex w-fit cursor-pointer items-center gap-2 border rounded-full border-blue-700 fill-none p-2 px-3 text-blue-500 font-extrabold peer-checked:fill-blue-500 peer-checked:hover:text-white" data-post-id="<?= $post['id'] ?>" data-liked="<?= $post['liked'] ? '1' : '0' ?>">
                            <div class="like-text group-hover:translate-x-4 transition z-10"><?= $post['like_count'] ?> suka</div>
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
                    $preview = $conn->query("
                        SELECT c.comment, c.created_at, u.id AS user_id, u.username, u.profile_picture
                        FROM comments c
                        JOIN users u ON c.user_id = u.id
                        WHERE c.post_id = {$post['id']}
                        ORDER BY c.created_at ASC
                        LIMIT 2
                    ");
                    while ($comment = $preview->fetch_assoc()): ?>
                        <div class="border border-gray-200 rounded-md p-3 flex items-start gap-2 bg-gray-50">
                            <a href="../pages/user_profile.php?id=<?= $comment['user_id'] ?>">
                                <img src="../<?= htmlspecialchars($comment['profile_picture'] ?: 'uploads/profile/default.png') ?>" onerror="this.onerror=null;this.src='../assets/profile.jpg';" class="w-10 h-10 rounded-full border object-cover">
                            </a>
                            <div class="flex-1 min-w-0">
                                <a href="../pages/user_profile.php?id=<?= $comment['user_id'] ?>" class="font-medium text-sm text-blue-600 hover:underline">@<?= htmlspecialchars($comment['username']) ?></a>
                                <p class="text-xs text-gray-500 mt-1"><?= date('d M Y H:i', strtotime($comment['created_at'])) ?></p>
                                <p class="text-sm text-gray-800 whitespace-pre-line break-words leading-snug mt-0.5"><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                            </div>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
        <?php endwhile; ?>
    </section>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        // Like
        document.querySelectorAll(".like-btn").forEach(btn => {
            btn.addEventListener("click", async () => {
                const postId = btn.dataset.postId;
                const res = await fetch("like_post.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "post_id=" + postId
                });
                const data = await res.json();
                if (data.success) {
                    btn.querySelector(".like-text").innerText = data.like_count + " suka";
                    btn.dataset.liked = data.liked ? "1" : "0";
                    btn.previousElementSibling.checked = data.liked;
                }
            });
        });

        // Dropdown
        const toggle = document.getElementById("dropdownToggle");
        const menu = document.getElementById("dropdownMenu");
        toggle.addEventListener("click", e => {
            e.stopPropagation();
            menu.classList.toggle("hidden");
        });
        document.addEventListener("click", () => menu.classList.add("hidden"));

        // Live search
        const input = document.getElementById("searchInput");
        const results = document.getElementById("searchResults");
        input.addEventListener("input", async () => {
            const q = input.value.trim();
            if (!q) return results.classList.add("hidden");
            const users = await fetch("search_user_api.php?q=" + encodeURIComponent(q)).then(r => r.json());
            results.innerHTML = users.length
                ? users.map(u => `
                    <a href="user_profile.php?id=${u.id}" class="flex items-center px-4 py-2 hover:bg-gray-100 space-x-3">
                        <img src="../${u.profile_picture || 'uploads/profile/default.png'}" onerror="this.onerror=null; this.src='../assets/profile.jpg';" class="w-8 h-8 rounded-full border" />
                        <span class="text-sm text-gray-700">@${u.username}</span>
                    </a>
                `).join('')
                : '<div class="px-4 py-2 text-sm text-gray-500">Tidak ditemukan.</div>';
            results.classList.remove("hidden");
        });
        document.addEventListener("click", e => {
            if (!results.contains(e.target) && e.target !== input) results.classList.add("hidden");
        });
    });
    </script>
</body>
</html>
