<?php
session_start();
include '../includes/db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: ../pages/login.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['community_id'])) {
    $user_id = $_SESSION['user_id'];
    $community_id = intval($_POST['community_id']);

    // Cek apakah sudah tergabung
    $check = $conn->query("SELECT * FROM community_members WHERE user_id = $user_id AND community_id = $community_id");
    if ($check->num_rows === 0) {
        $conn->query("INSERT INTO community_members (user_id, community_id) VALUES ($user_id, $community_id)");
    }

    header("Location: dashboard.php");
    exit();
}
?>
