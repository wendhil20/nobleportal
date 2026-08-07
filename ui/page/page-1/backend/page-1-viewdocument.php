<?php
//page-1-viewdocument.php — serves a single uploaded employee document (owner only)
include ROOT_PATH . "/network/connect.php";

$userId = $_SESSION['user_id'] ?? 0;
if ($userId <= 0) {
    header("Location: " . BASE_URL . "/login");
    exit;
}

$docId = (int) ($_GET['id'] ?? 0);
if ($docId <= 0) {
    http_response_code(404);
    exit("Document not found.");
}

$stmt = $conn->prepare("SELECT id, user_id, document_type, original_filename, file_path, mime_type, file_size FROM nobleuser_employee_documents WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $docId);
$stmt->execute();
$doc = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$doc) {
    http_response_code(404);
    exit("Document not found.");
}

// ==== Ownership check: user can only view their own documents ====
if ((int) $doc['user_id'] !== (int) $userId) {
    http_response_code(403);
    exit("You do not have permission to view this document.");
}

// ==== Serve the file ====
$fullPath = ROOT_PATH . $doc['file_path'];

if (!is_file($fullPath)) {
    http_response_code(404);
    exit("File is missing from storage.");
}

$isDownload = isset($_GET['download']) && $_GET['download'] == '1';
$disposition = $isDownload ? 'attachment' : 'inline';

$safeName = preg_replace('/[^\w\-. ]/', '_', $doc['original_filename']);

header("Content-Type: " . $doc['mime_type']);
header("Content-Length: " . filesize($fullPath));
header("Content-Disposition: {$disposition}; filename=\"{$safeName}\"");
header("X-Content-Type-Options: nosniff");
header("Cache-Control: private, max-age=0, must-revalidate");

readfile($fullPath);
exit;