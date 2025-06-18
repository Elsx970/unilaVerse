<?php
session_start();
include '../includes/db.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit();
}

// Ambil data form
$content = $_POST['content'];
$community_id = $_POST['community_id'];
$user_id = $_SESSION['user_id'];

$image_path = '';
$video_path = '';

// Buat folder upload jika belum ada
if (!is_dir('../uploads/images')) {
    mkdir('../uploads/images', 0777, true);
}
if (!is_dir('../uploads/videos')) {
    mkdir('../uploads/videos', 0777, true);
}

// Upload Gambar
if (isset($_FILES['image']) && $_FILES['image']['size'] > 0) {
    $img_name = time() . '_' . basename($_FILES['image']['name']);
    $image_path = 'uploads/images/' . $img_name;
    $target_img_path = '../' . $image_path;

    if (!move_uploaded_file($_FILES['image']['tmp_name'], $target_img_path)) {
        die("Upload gambar gagal.");
    }
}

// Upload Video
if (isset($_FILES['video']) && $_FILES['video']['size'] > 0) {
    $vid_name = time() . '_' . basename($_FILES['video']['name']);
    $video_path = 'uploads/videos/' . $vid_name;
    $target_vid_path = '../' . $video_path;

    if (!move_uploaded_file($_FILES['video']['tmp_name'], $target_vid_path)) {
        die("Upload video gagal.");
    }
}

// Simpan ke database
$stmt = $conn->prepare("INSERT INTO posts (community_id, user_id, content, image_path, video_path) VALUES (?, ?, ?, ?, ?)");
$stmt->bind_param("iisss", $community_id, $user_id, $content, $image_path, $video_path);

if ($stmt->execute()) {
    header("Location: community_detail.php?id=$community_id");
    exit();
} else {
    die("Gagal menyimpan postingan.");
}
?>
