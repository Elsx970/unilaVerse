<?php
include '../includes/db.php';

$keyword = $_GET['q'] ?? '';

if (strlen(trim($keyword)) < 1) {
    echo json_encode([]);
    exit;
}

$stmt = $conn->prepare("SELECT id, username, profile_picture FROM users WHERE username LIKE ? LIMIT 10");
$search = '%' . $keyword . '%';
$stmt->bind_param("s", $search);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

header('Content-Type: application/json');
echo json_encode($users);
