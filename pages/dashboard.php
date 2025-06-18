<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$search_escaped = $conn->real_escape_string($search);

// Komunitas yang user sudah gabung
$my_communities_query = "
    SELECT c.* FROM communities c
    JOIN community_members m ON c.id = m.community_id
    WHERE m.user_id = $user_id";
if (!empty($search)) {
    $my_communities_query .= " AND c.name LIKE '%$search_escaped%'";
}
$my_communities = $conn->query($my_communities_query);

// Komunitas yang belum user gabung
$other_communities_query = "
    SELECT * FROM communities
    WHERE id NOT IN (SELECT community_id FROM community_members WHERE user_id = $user_id)";
if (!empty($search)) {
    $other_communities_query .= " AND name LIKE '%$search_escaped%'";
}
$other_communities = $conn->query($other_communities_query);

// Info user login
$user = $conn->query("SELECT username, profile_picture FROM users WHERE id = $user_id")->fetch_assoc();
$profilePic = $user['profile_picture'] ?: 'uploads/profile/default.png';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Dashboard - UnilaVerse</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

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
            <input type="text" id="searchInput" name="q" placeholder="Cari pengguna..." class="border px-2 py-1 rounded-md text-sm w-64">
            <div id="searchResults" class="absolute z-50 bg-white border rounded-md shadow w-64 mt-1 hidden max-h-64 overflow-y-auto"></div>
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

<!-- Konten -->
<main class="pt-24 max-w-5xl mx-auto px-4">

    <!-- Form Pencarian -->
    <form method="GET" class="mb-6 flex max-w-md mx-auto gap-2">
        <input type="text" name="search" placeholder="Cari komunitas..." value="<?= htmlspecialchars($search) ?>" class="border px-4 py-2 rounded-md text-sm w-full" />
        <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded-md text-sm hover:bg-blue-700">Cari</button>
    </form>

    <!-- Komunitas User -->
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-3xl font-bold text-blue-700">Komunitas Kamu</h2>
        <a href="create_community.php" class="bg-blue-600 text-white px-4 py-2 rounded-lg shadow hover:bg-blue-700 transition duration-300">
            + Buat Komunitas
        </a>
    </div>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12">
        <?php if ($my_communities->num_rows > 0): ?>
            <?php while($c = $my_communities->fetch_assoc()): ?>
                <a href="community_detail.php?id=<?= $c['id'] ?>" class="bg-white p-5 rounded-lg shadow hover:ring-2 hover:ring-blue-400 transition block">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="bg-blue-100 text-blue-700 p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 8h10M7 12h4m1 8a9 9 0 100-18 9 9 0 000 18z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-blue-800"><?= htmlspecialchars($c['name']) ?></h3>
                    </div>
                    <p class="text-sm text-gray-600"><?= htmlspecialchars($c['description']) ?></p>
                </a>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-500 col-span-3 text-center">Kamu belum bergabung dengan komunitas manapun.</p>
        <?php endif; ?>
    </div>

    <!-- Komunitas Lainnya -->
    <h2 class="text-2xl font-bold text-gray-700 mb-4">Komunitas Lainnya</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
        <?php if ($other_communities->num_rows > 0): ?>
            <?php while($c = $other_communities->fetch_assoc()): ?>
                <div class="bg-white p-5 rounded-lg shadow hover:ring-2 hover:ring-green-400 transition block">
                    <div class="flex items-center gap-3 mb-2">
                        <div class="bg-green-100 text-green-700 p-2 rounded-full">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                 viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                      d="M7 8h10M7 12h4m1 8a9 9 0 100-18 9 9 0 000 18z"/>
                            </svg>
                        </div>
                        <h3 class="text-lg font-semibold text-green-800"><?= htmlspecialchars($c['name']) ?></h3>
                    </div>
                    <p class="text-sm text-gray-600 mb-3"><?= htmlspecialchars($c['description']) ?></p>
                    <form action="join_community.php" method="POST">
                        <input type="hidden" name="community_id" value="<?= $c['id'] ?>">
                        <button type="submit"
                                class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 transition w-full">
                            Gabung Komunitas
                        </button>
                    </form>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <p class="text-gray-500 col-span-3 text-center">Tidak ada komunitas yang cocok dengan pencarian kamu.</p>
        <?php endif; ?>
    </div>

</main>

<!-- Script -->
<script>
document.addEventListener("DOMContentLoaded", () => {
    // Dropdown
    const dp = document.getElementById("dropdownToggle");
    const dm = document.getElementById("dropdownMenu");
    dp.addEventListener("click", e => {
        e.stopPropagation();
        dm.classList.toggle("hidden");
    });
    document.addEventListener("click", () => dm.classList.add("hidden"));

    // Live user search
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
