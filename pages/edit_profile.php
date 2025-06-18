<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();
$profilePic = $user['profile_picture'] ?: 'uploads/profile/default.png';
$user = $conn->query("SELECT * FROM users WHERE id = $user_id")->fetch_assoc();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $biodata = $_POST['biodata'];
    $birthdate = $_POST['birthdate'];
    $gender = $_POST['gender'];
    $location = $_POST['location'];

    // Upload Foto
    if (!empty($_FILES['profile_picture']['name'])) {
        $ext = pathinfo($_FILES['profile_picture']['name'], PATHINFO_EXTENSION);
        $filename = "uploads/profile/user_" . $user_id . "." . $ext;
        move_uploaded_file($_FILES['profile_picture']['tmp_name'], "../" . $filename);
        $conn->query("UPDATE users SET profile_picture = '$filename' WHERE id = $user_id");
    }

    // Update semua data
    $stmt = $conn->prepare("UPDATE users SET username=?, email=?, biodata=?, birthdate=?, gender=?, location=? WHERE id=?");
    $stmt->bind_param("ssssssi", $username, $email, $biodata, $birthdate, $gender, $location, $user_id);
    $stmt->execute();
    $stmt->close();

    header("Location: profile.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Edit Profil</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 text-gray-800">

<nav class="bg-white shadow p-4 flex justify-between items-center">
    <a href="../pages/dashboard.php">
        <img src="../assets/logo.png" alt="UnilaVerse Logo" class="h-16 w-auto object-contain hover:scale-150 transition-transform duration-200" />
    </a>

    <div class="flex items-center space-x-6">
        <a href="../pages/dashboard.php" class="text-blue-600 font-medium hover:underline">Komunitas</a>
        <a href="../pages/following_posts.php" class="text-blue-600 font-medium hover:underline">Follow Post</a>

        <div class="relative">
            <button id="dropdownToggle" class="focus:outline-none flex items-center space-x-2">
                <img src="../<?= htmlspecialchars($profilePic) ?>" alt="Profil"
                     class="w-10 h-10 rounded-full object-cover border border-gray-300 hover:ring-2 ring-blue-400 transition" />
                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                </svg>
            </button>
            <div id="dropdownMenu" class="absolute right-0 mt-2 w-44 bg-white border rounded shadow-lg hidden z-10">
                <a href="../pages/profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">👤 Lihat Profil</a>
                <a href="../pages/logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-100">🔓 Logout</a>
            </div>
        </div>
    </div>
</nav>

    <main class="max-w-xl mx-auto mt-10 bg-white p-6 rounded-xl shadow-md">
        <h2 class="text-xl font-bold text-blue-700 mb-4">Edit Profil</h2>
        <form method="POST" enctype="multipart/form-data">
            <label class="block mb-2 font-semibold text-sm text-gray-700">Username</label>
            <input type="text" name="username" value="<?= htmlspecialchars($user['username']) ?>" required 
                class="w-full p-2 border border-gray-300 rounded-lg mb-4">

            <label class="block mb-2 font-semibold text-sm text-gray-700">Email</label>
            <input type="email" name="email" value="<?= htmlspecialchars($user['email']) ?>" required 
                class="w-full p-2 border border-gray-300 rounded-lg mb-4">

            <label class="block mb-2 font-semibold text-sm text-gray-700">Biodata</label>
            <textarea name="biodata" rows="3" class="w-full p-2 border border-gray-300 rounded-lg mb-4"><?= htmlspecialchars($user['biodata'] ?? '') ?></textarea>

            <label class="block mb-2 font-semibold text-sm text-gray-700">Tanggal Lahir</label>
            <input type="date" name="birthdate" value="<?= htmlspecialchars($user['birthdate'] ?? '') ?>"
                class="w-full p-2 border border-gray-300 rounded-lg mb-4">

            <label class="block mb-2 font-semibold text-sm text-gray-700">Jenis Kelamin</label>
            <select name="gender" class="w-full p-2 border border-gray-300 rounded-lg mb-4">
                <option value="">Pilih</option>
                <option value="Laki-laki" <?= $user['gender'] === 'Laki-laki' ? 'selected' : '' ?>>Laki-laki</option>
                <option value="Perempuan" <?= $user['gender'] === 'Perempuan' ? 'selected' : '' ?>>Perempuan</option>
            </select>

            <label class="block mb-2 font-semibold text-sm text-gray-700">Domisili</label>
            <input type="text" name="location" value="<?= htmlspecialchars($user['location'] ?? '') ?>" 
                class="w-full p-2 border border-gray-300 rounded-lg mb-4">

            <label class="block mb-2 font-semibold text-sm text-gray-700">Foto Profil</label>
            <input type="file" name="profile_picture" accept="image/*" class="mb-4">

            <button type="submit" 
                class="w-full bg-blue-600 text-white py-2 rounded-lg hover:bg-blue-700 transition">Simpan Perubahan</button>
        </form>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", () => {
        // Dropdown navbar toggle
    const dropdownBtn = document.getElementById("dropdownToggle");
    const dropdownMenu = document.getElementById("dropdownMenu");

    if (dropdownBtn && dropdownMenu) {
        dropdownBtn.addEventListener("click", (e) => {
            e.stopPropagation();
            dropdownMenu.classList.toggle("hidden");
        });

        document.addEventListener("click", () => {
            if (!dropdownMenu.classList.contains("hidden")) {
                dropdownMenu.classList.add("hidden");
            }
        });

        dropdownMenu.addEventListener("click", e => e.stopPropagation());
    }
});
</script>
</body>

</html>

