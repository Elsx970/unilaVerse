<?php
session_start();
include '../includes/db.php';

$logged_in_user_id = $_SESSION['user_id'] ?? 0;
if (!$logged_in_user_id) {
    header('Location: login.php');
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $followed_id = intval($_POST['followed_id'] ?? 0);
    if ($followed_id <= 0 || $followed_id === $logged_in_user_id) {
        // Gagal jika mencoba follow/unfollow diri sendiri atau id tidak valid
        header("Location: user_profile.php?id=$followed_id");
        exit();
    }

    if (isset($_POST['follow'])) {
        $stmt = $conn->prepare("INSERT IGNORE INTO followers (follower_id, followed_id) VALUES (?, ?)");
        $stmt->bind_param('ii', $logged_in_user_id, $followed_id);
        $stmt->execute();
        $stmt->close();
    } elseif (isset($_POST['unfollow'])) {
        $stmt = $conn->prepare("DELETE FROM followers WHERE follower_id = ? AND followed_id = ?");
        $stmt->bind_param('ii', $logged_in_user_id, $followed_id);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: user_profile.php?id=$followed_id");
    exit();
}

header('Location: index.php');
exit();

