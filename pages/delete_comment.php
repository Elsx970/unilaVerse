<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$comment_id = $_GET['id'] ?? null;

if ($comment_id) {
    // Pastikan komentar milik user yang login
    $check = $conn->prepare("SELECT user_id, post_id FROM comments WHERE id = ?");
    $check->bind_param("i", $comment_id);
    $check->execute();
    $result = $check->get_result();
    if ($row = $result->fetch_assoc()) {
        if ($row['user_id'] == $user_id) {
            // Hapus komentar
            $delete = $conn->prepare("DELETE FROM comments WHERE id = ?");
            $delete->bind_param("i", $comment_id);
            $delete->execute();
            // Redirect ke halaman komunitas postingan
            header("Location: community_detail.php?id=" . $_GET['community_id']);
            exit();
        } else {
            echo "Kamu tidak berhak menghapus komentar ini.";
        }
    } else {
        echo "Komentar tidak ditemukan.";
    }
} else {
    echo "ID komentar tidak valid.";
}
?>
