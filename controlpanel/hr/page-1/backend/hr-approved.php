<?php
// hr-approved.php

include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/hrpage-1");
    exit;
}

$userId = (int) ($_POST['user_id'] ?? 0);
$action = $_POST['action'] ?? '';
$notes  = trim($_POST['notes'] ?? '');

if ($userId <= 0 || !in_array($action, ['approve', 'reject'], true)) {
    header("Location: " . BASE_URL . "/hrpage-1");
    exit;
}

// Reject requires a note so the employee knows what to fix
if ($action === 'reject' && $notes === '') {
    $_SESSION['review_error'] = "Please provide notes when rejecting a submission.";
    header("Location: " . BASE_URL . "/hr-employees?id=" . $userId);
    exit;
}

$newStatus = $action === 'approve' ? 'APPROVED' : 'REJECTED';

// Only update if current status is still PENDING (avoid double-review race conditions)
$stmt = $conn->prepare(
    "UPDATE nobleuser_employee_information 
     SET status = ?, review_notes = ?, reviewed_by = ?, reviewed_at = NOW() 
     WHERE user_id = ? AND status = 'PENDING'"
);
$reviewerId = $_SESSION['id'] ?? null;
$stmt->bind_param("ssii", $newStatus, $notes, $reviewerId, $userId);
$stmt->execute();

if ($stmt->affected_rows === 0) {
    $_SESSION['review_error'] = "This submission was already reviewed or could not be found.";
    $stmt->close();
    header("Location: " . BASE_URL . "/hr-employees?id=" . $userId);
    exit;
}
$stmt->close();

header("Location: " . BASE_URL . "/hr-employees?id=" . $userId . "&reviewed=1");
exit;