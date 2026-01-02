<?php
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['student_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

include '../../includes/db.php';

// Count notifications from last 24 hours
// You might want to add a 'seen' table later for true unread status
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM notifications WHERE created_at > NOW() - INTERVAL 24 HOUR");
$stmt->execute();
$result = $stmt->get_result()->fetch_assoc();

echo json_encode(['status' => 'success', 'count' => $result['count']]);
?>