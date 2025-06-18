<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$post_id = $_GET['id'] ?? null;
if (!$post_id) {
    echo "ID postingan tidak ditemukan.";
    exit();
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    SELECT p.*, u.id as user_id, u.username, u.profile_picture,
           (SELECT COUNT(*) FROM post_likes WHERE post_id = p.id) AS like_count,
           EXISTS(SELECT 1 FROM post_likes WHERE user_id = ? AND post_id = p.id) AS liked,
           c.name AS community_name
    FROM posts p 
    JOIN users u ON p.user_id = u.id
    LEFT JOIN communities c ON p.community_id = c.id
    WHERE p.id = ?
");
$stmt->bind_param("ii", $user_id, $post_id);
$stmt->execute();
$post = $stmt->get_result()->fetch_assoc();

if (!$post) {
    echo "Postingan tidak ditemukan.";
    exit();
}

$user = $conn->query("SELECT username, profile_picture FROM users WHERE id = $user_id")->fetch_assoc();
$profilePic = $user['profile_picture'] ?: 'uploads/profile/default.png';

$comments = $conn->query("
    SELECT c.*, u.username, u.profile_picture 
    FROM comments c 
    JOIN users u ON c.user_id = u.id 
    WHERE c.post_id = $post_id 
    ORDER BY c.created_at ASC
");

$commentTree = [];
while ($row = $comments->fetch_assoc()) {
    $parent = $row['parent_id'] ?? 0;
    $commentTree[$parent][] = $row;
}

function renderComments($comments, $commentTree, $parent_id = 0, $level = 0) {
    if (!isset($commentTree[$parent_id])) return;

    foreach ($commentTree[$parent_id] as $comment) {
        $isReply = $parent_id != 0;

        echo '<div class="mb-4 ' . ($isReply 
            ? 'ml-10 pl-4 border-l-4 border-blue-400 bg-blue-50 rounded-md' 
            : 'bg-white border border-gray-200 p-3 rounded-md') . '">';

        echo '<div class="flex items-start space-x-3">';
        echo '<a href="../pages/user_profile.php?id=' . $comment['user_id'] . '">
                <img src="../' . (!empty($comment['profile_picture']) ? $comment['profile_picture'] : 'uploads/profile/default.png') . '"
                     alt="Profil"
                     onerror="this.onerror=null; this.src=\'../assets/profile.jpg\';"
                     class="w-10 h-10 rounded-full object-cover border border-gray-300" />
              </a>';

        echo '<div class="flex-1 min-w-0">';
        echo '<a href="../pages/user_profile.php?id=' . $comment['user_id'] . '" class="font-medium text-sm text-blue-600 hover:underline">
                @' . htmlspecialchars($comment['username']) . '
              </a>';
        echo '<p class="text-xs text-gray-500 mt-1">' . date('d M Y H:i', strtotime($comment['created_at'])) . '</p>';

        if ($isReply) {
            echo '<span class="text-xs text-gray-600 italic">Balasan</span>';
        }

        echo '<p class="text-sm text-gray-800 whitespace-pre-line break-words leading-snug mt-1">' . nl2br(htmlspecialchars($comment['comment'])) . '</p>';

        echo '<button class="text-sm text-blue-500 hover:underline mt-1 reply-btn" data-comment-id="' . $comment['id'] . '">Balas</button>';

        echo '<div class="reply-form hidden mt-2">
                <form action="add_comment.php" method="POST">
                    <input type="hidden" name="post_id" value="' . $comment['post_id'] . '"/>
                    <input type="hidden" name="parent_id" value="' . $comment['id'] . '"/>
                    <textarea name="comment" rows="2" required class="w-full p-2 border rounded mb-2 text-sm" placeholder="Tulis balasan..."></textarea>
                    <button type="submit" class="bg-blue-600 text-white px-3 py-1 rounded text-sm hover:bg-blue-700">Kirim</button>
                </form>
              </div>';

        echo '</div></div></div>';

        renderComments($comments, $commentTree, $comment['id'], $level + 1);
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?= htmlspecialchars($post['community_name'] ?? 'Postingan') ?> - UnilaVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

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

<section class="max-w-4xl mx-auto mt-8 bg-white p-6 rounded shadow">
    <!-- Post Header -->
    <div class="flex items-start gap-3 mb-4">
        <a href="../pages/user_profile.php?id=<?= $post['user_id'] ?>">
            <img src="../<?= htmlspecialchars($post['profile_picture'] ?: 'uploads/profile/default.png') ?>"
                 onerror="this.onerror=null; this.src='../assets/profile.jpg';"
                 class="w-10 h-10 rounded-full border border-gray-300" />
        </a>
        <div>
            <a href="../pages/user_profile.php?id=<?= $post['user_id'] ?>"
               class="text-blue-700 font-semibold text-lg hover:underline">
               @<?= htmlspecialchars($post['username']) ?>
            </a>
            <div class="text-sm text-gray-500"><?= date('d M Y, H:i', strtotime($post['created_at'])) ?></div>
        </div>
    </div>

    <!-- Post Content -->
    <p class="mb-4 text-gray-800 leading-relaxed"><?= nl2br(htmlspecialchars($post['content'])) ?></p>

    <?php if (!empty($post['image_path'])): ?>
        <div class="my-4 flex justify-center">
            <img src="../<?= htmlspecialchars($post['image_path']) ?>" class="rounded max-h-64 object-contain border shadow" />
        </div>
    <?php endif; ?>

    <?php if (!empty($post['video_path'])): ?>
        <div class="my-4">
            <video controls class="w-full max-h-64 rounded border shadow">
                <source src="../<?= htmlspecialchars($post['video_path']) ?>" />
            </video>
        </div>
    <?php endif; ?>

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

    <!-- Komentar -->
    <div class="mt-6">
        <h3 class="text-xl font-semibold mb-4">Komentar</h3>

        <?php renderComments($comments, $commentTree); ?>
    </div>

    <!-- Form Komentar Utama -->
    <form action="add_comment.php" method="POST" class="mt-6">
        <input type="hidden" name="post_id" value="<?= $post_id ?>" />
        <input type="hidden" name="parent_id" value="0" />
        <textarea name="comment" required placeholder="Tulis komentar..."
                  class="w-full p-3 border rounded focus:ring-2 focus:ring-blue-400 mb-2 resize-none"></textarea>
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Kirim Komentar</button>
    </form>

    <!-- Tempat Form Balas Komentar (JS akan tempel di sini) -->
    <div id="reply-form-container" class="mt-6 hidden">
        <form id="reply-form" action="add_comment.php" method="POST">
            <input type="hidden" name="post_id" value="<?= $post_id ?>" />
            <input type="hidden" name="parent_id" id="reply-parent-id" value="0" />
            <textarea name="comment" required placeholder="Tulis balasan..."
                      class="w-full p-3 border rounded focus:ring-2 focus:ring-blue-400 mb-2 resize-none"></textarea>
            <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">Kirim Balasan</button>
            <button type="button" onclick="hideReplyForm()" class="ml-2 text-gray-600 hover:underline">Batal</button>
        </form>
    </div>
</section>

<script>
// JS: Tampilkan form reply di bawah komentar tertentu
function showReplyForm(parentId, element) {
    const container = document.getElementById("reply-form-container");
    const input = document.getElementById("reply-parent-id");

    input.value = parentId;
    element.parentNode.appendChild(container);
    container.classList.remove("hidden");
}

// Sembunyikan form reply
function hideReplyForm() {
    document.getElementById("reply-form-container").classList.add("hidden");
}
</script>


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

    document.querySelectorAll('.reply-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const form = btn.parentElement.querySelector('.reply-form');
            form.classList.toggle('hidden');
        });
    });
</script>

</body>
</html>
