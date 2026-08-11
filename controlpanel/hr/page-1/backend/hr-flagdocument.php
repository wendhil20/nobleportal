<?php
//hr-flagdocument.php — HR flags a specific uploaded document as needing re-upload.

include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/hrpage-1");
    exit;
}

function redirectBack($userId, $error = null) {
    if ($error) {
        $_SESSION['review_error'] = $error;
    }
    header("Location: " . BASE_URL . "/hr-employees?id=" . (int) $userId);
    exit;
}

$userId       = (int) ($_POST['user_id'] ?? 0);
$documentType = trim($_POST['document_type'] ?? '');
$reason       = trim($_POST['reason'] ?? '');

if ($userId <= 0) {
    redirectBack($userId, "Missing employee reference.");
}

$documentTypes = require ROOT_PATH . "/ui/page/page-1/backend/document_types.php";

if (!isset($documentTypes[$documentType])) {
    redirectBack($userId, "Invalid document type.");
}

if ($reason === '') {
    redirectBack($userId, "Please provide a reason for the re-upload request.");
}

$docLabel = $documentTypes[$documentType]['label'];

// Confirm employee exists
$stmt = $conn->prepare("SELECT id, first_name, last_name FROM nobleuserlist WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$emp = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$emp) {
    redirectBack($userId, "Employee not found.");
}

// Kailangan may naka-save na 201 File info muna bago ma-flag ang isang document dito
$stmt = $conn->prepare("SELECT id FROM nobleuser_employee_information WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$infoRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$infoRow) {
    redirectBack($userId, "This employee has no 201 File information on record yet.");
}

$conn->begin_transaction();

try {
    // -- Alisin ang mga na-upload na file para sa specific document type --
    $stmt = $conn->prepare("SELECT file_path FROM nobleuser_employee_documents WHERE user_id = ? AND document_type = ?");
    $stmt->bind_param("is", $userId, $documentType);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    if (empty($rows)) {
        throw new Exception("This employee has not uploaded \"{$docLabel}\" yet.");
    }

    foreach ($rows as $row) {
        $fullPath = ROOT_PATH . $row['file_path'];
        if (is_file($fullPath)) @unlink($fullPath);
    }

    $delStmt = $conn->prepare("DELETE FROM nobleuser_employee_documents WHERE user_id = ? AND document_type = ?");
    $delStmt->bind_param("is", $userId, $documentType);
    $delStmt->execute();
    $delStmt->close();

    // -- I-close muna ang dating open flag (kung meron) para hindi mag-duplicate --
    $closeStmt = $conn->prepare("UPDATE nobleuser_document_flags SET resolved_at = NOW() WHERE user_id = ? AND document_type = ? AND resolved_at IS NULL");
    $closeStmt->bind_param("is", $userId, $documentType);
    $closeStmt->execute();
    $closeStmt->close();

    // -- Gumawa ng bagong open flag para sa specific document type --
    $reviewerId = $_SESSION['user_id'] ?? null;
    $flagStmt = $conn->prepare("INSERT INTO nobleuser_document_flags
        (user_id, document_type, reason, flagged_by, flagged_at)
        VALUES (?, ?, ?, ?, NOW())");
    $flagStmt->bind_param("issi", $userId, $documentType, $reason, $reviewerId);
    $flagStmt->execute();
    $flagStmt->close();

    // -- Ibalik ang buong 201 File sa PENDING habang hinihintay ang re-upload ng employee.
    //    Kahit APPROVED o REJECTED na ito dati, kapag may bagong flag, dapat mag-abang
    //    ulit ang file sa aksyon (sa kasong ito, ang employee ang kailangang kumilos —
    //    mag-re-upload — pero ginagamit pa rin natin ang PENDING slot para ma-lock ang
    //    buong personal info form at hindi na kailangang i-refill ito).
    $statusStmt = $conn->prepare("UPDATE nobleuser_employee_information
        SET status = 'PENDING', review_notes = NULL, reviewed_by = NULL, reviewed_at = NULL
        WHERE user_id = ?");
    $statusStmt->bind_param("i", $userId);
    $statusStmt->execute();
    $statusStmt->close();

    // -- Notify the employee --
    $notifTitle   = "Document re-upload needed";
    $notifMessage = "HR flagged your \"{$docLabel}\" for re-upload. Reason: {$reason}";
    $notifLink    = BASE_URL . "/page-1";

    $notifStmt = $conn->prepare("INSERT INTO nobleportalnotification
        (for_role, for_position, for_user_id, recipient_type, title, message, link, is_read, created_at)
        VALUES (NULL, NULL, ?, 'user', ?, ?, ?, 0, NOW())");
    $notifStmt->bind_param("isss", $userId, $notifTitle, $notifMessage, $notifLink);
    $notifStmt->execute();
    $notifStmt->close();

    $conn->commit();
    header("Location: " . BASE_URL . "/hr-employees?id=" . $userId . "&reviewed=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    redirectBack($userId, $e->getMessage());
}