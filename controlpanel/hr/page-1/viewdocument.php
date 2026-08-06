<?php
//viewdocument.php — serves a single uploaded employee document (HR/head only)
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

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