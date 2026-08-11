<?php
// employee-resignation-actions.php
include ROOT_PATH . "/network/connect.php";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_resignation_request'])) {
    $userId = $_SESSION['user_id'] ?? 0;
    $reason = trim($_POST['reason'] ?? '');

    if ($userId > 0) {
        // Prevent duplicate active requests — a user can only have one
        // request "in flight" at a time. A REJECTED or fully RESIGNED
        // record doesn't block a fresh request.
        $check = $conn->prepare("SELECT id FROM nobleuser_resignation
            WHERE user_id = ? AND status NOT IN ('REJECTED', 'RESIGNED')
            LIMIT 1");
        $check->bind_param("i", $userId);
        $check->execute();
        $existing = $check->get_result()->fetch_assoc();
        $check->close();

        if (!$existing) {
            $stmt = $conn->prepare("INSERT INTO nobleuser_resignation (user_id, reason, status)
                VALUES (?, ?, 'PENDING')");
            $stmt->bind_param("is", $userId, $reason);
            $stmt->execute();
            $stmt->close();

            // Notify HR heads of the new request
            $link = BASE_URL . '/admin-resignation';
            $notifStmt = $conn->prepare("INSERT INTO nobleportalnotification
                (for_role, for_position, recipient_type, title, message, link, is_read, created_at)
                VALUES ('hr', 'head', 'admin', 'New Resignation Request',
                        'An employee has submitted a resignation request awaiting review.',
                        ?, 0, NOW())");
            $notifStmt->bind_param("s", $link);
            $notifStmt->execute();
            $notifStmt->close();
        }
    }

    header('Location: ' . BASE_URL . '/page-4');
    exit;
}