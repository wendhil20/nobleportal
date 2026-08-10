<?php
//notification-actions.php — Backend handler lang para sa mark-as-read actions.

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$adminId       = $_SESSION['admin_id'] ?? 0;
$adminRole     = $_SESSION['admin_role'] ?? '';
$adminPosition = $_SESSION['admin_position'] ?? null;

if (isset($_POST['mark_read_id'])) {
    $notifId = (int) $_POST['mark_read_id'];
    // recipient_type = 'admin' (o role/position-based) lang ang pwedeng i-mark
    // dito, para hindi ma-touch ng admin side yung notifications na para
    // talaga sa employee/user (recipient_type = 'user').
    $stmt = $conn->prepare("UPDATE nobleportalnotification
        SET is_read = 1
        WHERE id = ?
          AND (
                (for_user_id = ? AND recipient_type = 'admin')
                OR for_user_id IS NULL
              )");
    $stmt->bind_param("ii", $notifId, $adminId);
    $stmt->execute();
    $stmt->close();
}

if (isset($_POST['mark_all_read'])) {

    $stmt = $conn->prepare("UPDATE nobleportalnotification
        SET is_read = 1
        WHERE (for_user_id = ? AND recipient_type = 'admin')
           OR (
                (for_role IS NOT NULL OR for_position IS NOT NULL)
                AND (for_role IS NULL OR for_role = ?)
                AND (for_position IS NULL OR for_position = ?)
              )");
    $stmt->bind_param("iss", $adminId, $adminRole, $adminPosition);
    $stmt->execute();
    $stmt->close();
}

header("Location: " . BASE_URL . "/admin-notification");
exit;