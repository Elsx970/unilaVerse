<!-- File: comment_upload.php -->
<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: ../pages/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$post_id = $_POST['post_id'];
$comment = $_POST['comment'];

if (!empty($comment)) {
    $stmt = $conn->prepare("INSERT INTO comments (post_id, user_id, comment) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $post_id, $user_id, $comment);
    $stmt->execute();
}

// Redirect back to the community detail page
$community_id_query = $conn->query("SELECT community_id FROM posts WHERE id = $post_id LIMIT 1");
$community_id = $community_id_query->fetch_assoc()['community_id'];

header("Location: community_detail.php?id=$community_id");
exit();
