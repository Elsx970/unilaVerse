<?php
session_start();
include '../includes/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Login required']);
    exit;
}

$user_id = $_SESSION['user_id'];
$post_id = intval($_POST['post_id']);

// Cek like sebelumnya
$check = $conn->query("SELECT * FROM post_likes WHERE user_id = $user_id AND post_id = $post_id");

if ($check->num_rows == 0) {
    $conn->query("INSERT INTO post_likes (user_id, post_id) VALUES ($user_id, $post_id)");
    $liked = true;
} else {
    $conn->query("DELETE FROM post_likes WHERE user_id = $user_id AND post_id = $post_id");
    $liked = false;
}

// Hitung like terbaru
$result = $conn->query("SELECT COUNT(*) AS like_count FROM post_likes WHERE post_id = $post_id");
$like_count = $result->fetch_assoc()['like_count'];

echo json_encode([
    'success' => true,
    'liked' => $liked,
    'like_count' => $like_count
]);


