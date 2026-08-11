<?php
//notification-count.php — lightweight JSON endpoint para sa polling ng unread

include ROOT_PATH . "/network/connect.php";

header("Content-Type: application/json");
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM nobleportalnotification WHERE for_user_id = ? AND recipient_type = 'user' AND is_read = 0");
$stmt->bind_param("i", $userId);
$stmt->execute();
$row = $stmt->get_result()->fetch_assoc();
$stmt->close();

echo json_encode([
    'count' => (int) ($row['cnt'] ?? 0),
]);
exit;