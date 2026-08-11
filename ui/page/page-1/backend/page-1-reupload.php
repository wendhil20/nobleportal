<?php
//page-1-reupload.php — tumatanggap ng re-upload ng employee para sa isang naka-flag na document

include ROOT_PATH . "/network/connect.php";

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function jsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

$userId = $_SESSION['user_id'] ?? 0;
if ($userId <= 0) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Your session has expired. Please log in again.'], 401);
    }
    header("Location: " . BASE_URL . "/login");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
    header("Location: " . BASE_URL . "/page-1");
    exit;
}

function redirectWithError($msg, $userId, $isAjax = false) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $msg], 422);
    }
    $_SESSION['form_error'] = $msg;
    header("Location: " . BASE_URL . "/page-1");
    exit;
}

$documentType = trim($_POST['document_type'] ?? '');

$documentTypes = require ROOT_PATH . "/ui/page/page-1/backend/document_types.php";

if (!isset($documentTypes[$documentType])) {
    redirectWithError("Invalid document type.", $userId, $isAjax);
}

$doc = $documentTypes[$documentType];

// ==== Kailangan may open flag muna bago tanggapin ang re-upload ====
$stmt = $conn->prepare("SELECT id FROM nobleuser_document_flags WHERE user_id = ? AND document_type = ? AND resolved_at IS NULL ORDER BY id DESC LIMIT 1");
$stmt->bind_param("is", $userId, $documentType);
$stmt->execute();
$flag = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$flag) {
    redirectWithError("This document is not currently marked for re-upload.", $userId, $isAjax);
}

// ==== Kunin ang mga bagong file ====
$isMultiple = !empty($doc['multiple']);
$filesToProcess = [];

if ($isMultiple) {
    $names = $_FILES['document']['name'] ?? [];
    foreach ((array) $names as $i => $name) {
        if ($name === '') continue;
        $filesToProcess[] = [
            'tmp_name' => $_FILES['document']['tmp_name'][$i],
            'name'     => $_FILES['document']['name'][$i],
            'error'    => $_FILES['document']['error'][$i],
            'size'     => $_FILES['document']['size'][$i],
        ];
    }

    $maxAllowed = $doc['max'] ?? 3;
    $stmt = $conn->prepare("SELECT COUNT(*) AS cnt FROM nobleuser_employee_documents WHERE user_id = ? AND document_type = ?");
    $stmt->bind_param("is", $userId, $documentType);
    $stmt->execute();
    $existingCount = (int) ($stmt->get_result()->fetch_assoc()['cnt'] ?? 0);
    $stmt->close();

    if (($existingCount + count($filesToProcess)) > $maxAllowed) {
        redirectWithError("Too many files for \"{$doc['label']}\" (max {$maxAllowed}).", $userId, $isAjax);
    }
} else {
    $name = $_FILES['document']['name'] ?? '';
    if ($name !== '') {
        $filesToProcess[] = [
            'tmp_name' => $_FILES['document']['tmp_name'],
            'name'     => $_FILES['document']['name'],
            'error'    => $_FILES['document']['error'],
            'size'     => $_FILES['document']['size'],
        ];
    }
}

if (empty($filesToProcess)) {
    redirectWithError("Please select a file to upload for \"{$doc['label']}\".", $userId, $isAjax);
}

$conn->begin_transaction();

try {
    $maxFileSize  = 5 * 1024 * 1024; // 5MB
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    $uploadBaseDir = ROOT_PATH . '/uploads/employee_documents/' . $userId . '/';

    if (!is_dir($uploadBaseDir)) {
        mkdir($uploadBaseDir, 0755, true);
    }

    if (!$isMultiple) {
        $oldStmt = $conn->prepare("SELECT file_path FROM nobleuser_employee_documents WHERE user_id = ? AND document_type = ?");
        $oldStmt->bind_param("is", $userId, $documentType);
        $oldStmt->execute();
        $oldRows = $oldStmt->get_result()->fetch_all(MYSQLI_ASSOC);
        $oldStmt->close();

        foreach ($oldRows as $oldRow) {
            $fullOldPath = ROOT_PATH . $oldRow['file_path'];
            if (is_file($fullOldPath)) @unlink($fullOldPath);
        }

        $delStmt = $conn->prepare("DELETE FROM nobleuser_employee_documents WHERE user_id = ? AND document_type = ?");
        $delStmt->bind_param("is", $userId, $documentType);
        $delStmt->execute();
        $delStmt->close();
    }

    foreach ($filesToProcess as $file) {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Upload failed for \"{$doc['label']}\".");
        }
        if ($file['size'] <= 0 || $file['size'] > $maxFileSize) {
            throw new Exception("\"{$doc['label']}\" exceeds the 5MB limit.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime  = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $allowedMimes, true)) {
            throw new Exception("Invalid file type for \"{$doc['label']}\". Only JPG, PNG, WEBP, or PDF allowed.");
        }

        $baseName = bin2hex(random_bytes(8)) . '_' . time();

        if ($mime === 'application/pdf') {
            $storedName = $baseName . '.pdf';
            if (!move_uploaded_file($file['tmp_name'], $uploadBaseDir . $storedName)) {
                throw new Exception("Failed to save \"{$doc['label']}\".");
            }
            $finalMime = 'application/pdf';
        } else {
            $storedName = $baseName . '.webp';
            $destPath   = $uploadBaseDir . $storedName;

            switch ($mime) {
                case 'image/jpeg':
                    $img = @imagecreatefromjpeg($file['tmp_name']);
                    break;
                case 'image/png':
                    $img = @imagecreatefrompng($file['tmp_name']);
                    if ($img) {
                        imagepalettetotruecolor($img);
                        imagealphablending($img, true);
                        imagesavealpha($img, true);
                    }
                    break;
                case 'image/webp':
                    $img = @imagecreatefromwebp($file['tmp_name']);
                    break;
                default:
                    $img = null;
            }

            if (!$img || !imagewebp($img, $destPath, 82)) {
                if ($img) imagedestroy($img);
                throw new Exception("Failed to process image for \"{$doc['label']}\".");
            }
            imagedestroy($img);
            $finalMime = 'image/webp';
        }

        $relativePath = '/uploads/employee_documents/' . $userId . '/' . $storedName;

        $insertStmt = $conn->prepare("INSERT INTO nobleuser_employee_documents
            (user_id, document_type, original_filename, stored_filename, file_path, mime_type, file_size)
            VALUES (?, ?, ?, ?, ?, ?, ?)");
        $originalName = $file['name'];
        $fileSize     = $file['size'];
        $insertStmt->bind_param(
            "isssssi",
            $userId, $documentType, $originalName, $storedName, $relativePath, $finalMime, $fileSize
        );
        $insertStmt->execute();
        $insertStmt->close();
    }

    $resolveStmt = $conn->prepare("UPDATE nobleuser_document_flags SET resolved_at = NOW() WHERE user_id = ? AND document_type = ? AND resolved_at IS NULL");
    $resolveStmt->bind_param("is", $userId, $documentType);
    $resolveStmt->execute();
    $resolveStmt->close();

    $stmt = $conn->prepare("SELECT first_name, last_name FROM nobleuserlist WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $emp = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $empName = trim(($emp['first_name'] ?? '') . ' ' . ($emp['last_name'] ?? ''));
    $notifTitle   = "Document re-uploaded";
    $notifMessage = "{$empName} has re-uploaded \"{$doc['label']}\". Please review.";
    $notifLink    = BASE_URL . "/hr-employees?id=" . $userId;

    $notifStmt = $conn->prepare("INSERT INTO nobleportalnotification
        (for_role, for_position, for_user_id, title, message, link, is_read, created_at)
        VALUES ('hr', 'head', NULL, ?, ?, ?, 0, NOW())");
    $notifStmt->bind_param("sss", $notifTitle, $notifMessage, $notifLink);
    $notifStmt->execute();
    $notifStmt->close();

    $conn->commit();

    if ($isAjax) {
        jsonResponse(['success' => true, 'message' => 'Document re-uploaded successfully.']);
    }

    header("Location: " . BASE_URL . "/page-1?reuploaded=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    redirectWithError($e->getMessage(), $userId, $isAjax);
}