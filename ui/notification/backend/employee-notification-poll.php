<?php
// employee-notification-poll.php
include ROOT_PATH . "/network/connect.php";

header('Content-Type: application/json');

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    http_response_code(401);
    echo json_encode(['error' => 'unauthorized']);
    exit;
}

function timeAgo($datetime) {
    if (!$datetime) return '';
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 604800) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}

$stmt = $conn->prepare("SELECT id, title, message, link, is_read, created_at
    FROM nobleportalnotification
    WHERE for_user_id = ? AND recipient_type = 'user'
    ORDER BY created_at DESC");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
$notifications = $res->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$unreadCount = 0;
$out = [];
foreach ($notifications as $n) {
    $isUnread = (int) $n['is_read'] === 0;
    if ($isUnread) $unreadCount++;
    $out[] = [
        'id'       => (int) $n['id'],
        'title'    => htmlspecialchars($n['title'] ?: 'Notification'),
        'message'  => htmlspecialchars($n['message'] ?? ''),
        'link'     => $n['link'] ? htmlspecialchars($n['link']) : null,
        'is_read'  => !$isUnread,
        'time_ago' => timeAgo($n['created_at']),
    ];
}

echo json_encode([
    'unread_count'  => $unreadCount,
    'notifications' => $out,
]);