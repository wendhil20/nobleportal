<?php
// resignation-actions.php — Admin actions for the resignation workflow
// Included by resignrequest.php — expects $conn to already be connected.
include ROOT_PATH . "/network/connect.php";

$adminId = $_SESSION['admin_id'] ?? 0;
/**
 * Notify the employee about a status change on their resignation request.
 */
function notifyEmployeeResignationUpdate($conn, $resignationId, $title, $message)
{
    $stmt = $conn->prepare("SELECT user_id FROM nobleuser_resignation WHERE id = ?");
    $stmt->bind_param("i", $resignationId);
    $stmt->execute();
    $userId = $stmt->get_result()->fetch_assoc()['user_id'] ?? null;
    $stmt->close();

    if (!$userId) return;

    $link = BASE_URL . '/page-4';
    $notifStmt = $conn->prepare("INSERT INTO nobleportalnotification
        (for_user_id, recipient_type, title, message, link, is_read, created_at)
        VALUES (?, 'user', ?, ?, ?, 0, NOW())");
    $notifStmt->bind_param("isss", $userId, $title, $message, $link);
    $notifStmt->execute();
    $notifStmt->close();
}

// ---- Approve request ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_id'])) {
    $id = (int) $_POST['approve_id'];

    $stmt = $conn->prepare("UPDATE nobleuser_resignation
        SET status = 'APPROVED', reviewed_by = ?, reviewed_at = NOW()
        WHERE id = ? AND status = 'PENDING'");
    $stmt->bind_param("ii", $adminId, $id);
    $stmt->execute();
    $stmt->close();

    notifyEmployeeResignationUpdate($conn, $id, 'Resignation Request Approved',
        'Your resignation request has been approved. HR will prepare your resignation document next.');

    header('Location: ' . BASE_URL . '/admin-resignation');
    exit;
}

// ---- Reject request ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_id'])) {
    $id = (int) $_POST['reject_id'];
    $reason = trim($_POST['rejection_reason'] ?? '');

    $stmt = $conn->prepare("UPDATE nobleuser_resignation
        SET status = 'REJECTED', rejection_reason = ?, reviewed_by = ?, reviewed_at = NOW()
        WHERE id = ? AND status = 'PENDING'");
    $stmt->bind_param("sii", $reason, $adminId, $id);
    $stmt->execute();
    $stmt->close();

    notifyEmployeeResignationUpdate($conn, $id, 'Resignation Request Declined',
        'Your resignation request was not approved. Please check with HR for details.');

    header('Location: ' . BASE_URL . '/admin-resignation');
    exit;
}

// ---- Step 1: Upload resignation document + Step 2: start the rendering countdown ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['start_rendering_id'])) {
    $id = (int) $_POST['start_rendering_id'];
    $renderingDays = max(1, (int) ($_POST['rendering_days'] ?? 30));

    $documentPath = null;
    $documentOriginalName = null;

    if (!empty($_FILES['resignation_document']['name'])) {
        $ext = strtolower(pathinfo($_FILES['resignation_document']['name'], PATHINFO_EXTENSION));

        if ($ext === 'pdf' && $_FILES['resignation_document']['error'] === UPLOAD_ERR_OK) {
            $documentOriginalName = basename($_FILES['resignation_document']['name']);
            $documentPath = uniqid('resign_') . '.pdf';

            $destDir = ROOT_PATH . '/uploads/resignation/documents/';
            if (!is_dir($destDir)) {
                mkdir($destDir, 0755, true);
            }
            move_uploaded_file($_FILES['resignation_document']['tmp_name'], $destDir . $documentPath);
        }
    }

    if ($documentPath) {
        $startDate = date('Y-m-d');
        $endDate = date('Y-m-d', strtotime("+{$renderingDays} days"));

        $stmt = $conn->prepare("UPDATE nobleuser_resignation
            SET document_path = ?, document_original_name = ?, rendering_days = ?,
                rendering_start_date = ?, rendering_end_date = ?, status = 'RENDERING',
                notified_at = NULL
            WHERE id = ? AND status = 'APPROVED'");
        $stmt->bind_param("ssissi", $documentPath, $documentOriginalName, $renderingDays, $startDate, $endDate, $id);
        $stmt->execute();
        $stmt->close();

        notifyEmployeeResignationUpdate($conn, $id, 'Resignation Document Ready',
            "Your resignation document is ready and your {$renderingDays}-day rendering period has started.");
    }

    header('Location: ' . BASE_URL . '/admin-resignation');
    exit;
}

// ---- Step 3: Admin confirms — finalize as Resigned ----
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_resigned_id'])) {
    $id = (int) $_POST['mark_resigned_id'];

    $stmt = $conn->prepare("UPDATE nobleuser_resignation
        SET status = 'RESIGNED', resigned_at = NOW()
        WHERE id = ? AND status IN ('RENDERING', 'READY_FOR_COMPLETION')");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    notifyEmployeeResignationUpdate($conn, $id, 'Resignation Completed',
        'Your resignation has been finalized. Thank you for your service.');

    header('Location: ' . BASE_URL . '/admin-resignation');
    exit;
}

// ---- Auto-check on every page load: flag rendering periods that have
//      ended and fire a one-time admin notification for each ----
$autoCheck = $conn->prepare("SELECT id FROM nobleuser_resignation
    WHERE status = 'RENDERING' AND rendering_end_date <= CURDATE() AND notified_at IS NULL");
$autoCheck->execute();
$dueRows = $autoCheck->get_result()->fetch_all(MYSQLI_ASSOC);
$autoCheck->close();

foreach ($dueRows as $row) {
    $upd = $conn->prepare("UPDATE nobleuser_resignation
        SET status = 'READY_FOR_COMPLETION', notified_at = NOW() WHERE id = ?");
    $upd->bind_param("i", $row['id']);
    $upd->execute();
    $upd->close();

    $link = BASE_URL . '/admin-resignation';
    $notifStmt = $conn->prepare("INSERT INTO nobleportalnotification
        (for_role, for_position, recipient_type, title, message, link, is_read, created_at)
        VALUES ('hr', 'head', 'admin', 'Rendering Period Completed',
                'An employee has completed their rendering period and is ready to be marked as resigned.',
                ?, 0, NOW())");
    $notifStmt->bind_param("s", $link);
    $notifStmt->execute();
    $notifStmt->close();
}