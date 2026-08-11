<?php
//personalhandler.php
include ROOT_PATH . "/network/connect.php";

$isAjax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

function jsonResponse($data, $httpCode = 200) {
    http_response_code($httpCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => 'Invalid request method.'], 405);
    }
    header("Location: " . BASE_URL . "/page-1");
    exit;
}

function redirectWithError($msg, $userId, $old = [], $isAjax = false) {
    if ($isAjax) {
        jsonResponse(['success' => false, 'message' => $msg], 422);
    }
    $_SESSION['form_error'] = $msg;
    $_SESSION['old_input']  = $old;
    header("Location: " . BASE_URL . "/page-1");
    exit;
}

// Bumubuo ng bagong reference number sa format na NHCC-HR{YEAR}-{4-digit sequence},
function generateReferenceNumber($conn) {
    $year = date('Y');
    $prefix = "NHCC-HR{$year}-";
    $likePattern = $prefix . '%';

    $stmt = $conn->prepare(
        "SELECT reference_number FROM nobleuser_employee_information
         WHERE reference_number LIKE ? ORDER BY reference_number DESC LIMIT 1 FOR UPDATE"
    );
    $stmt->bind_param("s", $likePattern);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    $nextSeq = 1;
    if (!empty($row['reference_number'])) {
        $lastSeq = (int) substr($row['reference_number'], strlen($prefix));
        $nextSeq = $lastSeq + 1;
    }

    return $prefix . str_pad((string) $nextSeq, 4, '0', STR_PAD_LEFT);
}

$userId = (int) ($_POST['user_id'] ?? 0);

$firstName     = strtoupper(trim($_POST['first_name'] ?? ''));
$middleName    = strtoupper(trim($_POST['middle_name'] ?? ''));
$lastName      = strtoupper(trim($_POST['last_name'] ?? ''));
$extensionName = strtoupper(trim($_POST['extension_name'] ?? ''));
$birthdate     = trim($_POST['birthdate'] ?? '');
$age           = (int) ($_POST['age'] ?? 0);
$gender        = trim($_POST['gender'] ?? '');
$birthplace    = trim($_POST['birthplace'] ?? '');
$maritalStatus = trim($_POST['marital_status'] ?? '');
$address       = trim($_POST['present_address'] ?? '');
$religion      = trim($_POST['religion'] ?? '');
$citizenship   = trim($_POST['citizenship'] ?? '');

$oldInput = $_POST; // para ma-restore kung mag-error (files hindi na-preserve, normal lang)

// ==== Validation: Step 1 & 2 ====
if ($userId <= 0) {
    redirectWithError("Missing employee reference.", $userId, $oldInput, $isAjax);
}
if ($firstName === '' || $lastName === '' || $birthdate === '' || $age <= 0 || $birthplace === '') {
    redirectWithError("Please complete all required fields in Step 1.", $userId, $oldInput, $isAjax);
}
if (!in_array($gender, ['MALE', 'FEMALE'], true)) {
    redirectWithError("Please select a gender.", $userId, $oldInput, $isAjax);
}
if (!in_array($maritalStatus, ['SINGLE', 'MARRIED', 'WIDOWED', 'SEPARATED', 'DIVORCED'], true)) {
    redirectWithError("Please select a valid marital status.", $userId, $oldInput, $isAjax);
}
if ($address === '' || $religion === '' || $citizenship === '') {
    redirectWithError("Please complete all required fields in Step 2.", $userId, $oldInput, $isAjax);
}

// ==== Confirm existing employee account ====
$stmt = $conn->prepare("SELECT id FROM nobleuserlist WHERE id = ? LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$exists = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$exists) {
    redirectWithError("Employee account not found.", $userId, $oldInput, $isAjax);
}

// ==== Block edits while PENDING review ====
$stmt = $conn->prepare("SELECT status FROM nobleuser_employee_information WHERE user_id = ? ORDER BY id DESC LIMIT 1");
$stmt->bind_param("i", $userId);
$stmt->execute();
$currentStatusRow = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Alamin kung bagong submission ba ito o resubmission (galing REJECTED) —
// gagamitin sa wording ng notification sa ibaba.
$isResubmission = !empty($currentStatusRow['status']) && $currentStatusRow['status'] === 'REJECTED';

if (!empty($currentStatusRow['status']) && $currentStatusRow['status'] === 'PENDING') {
    redirectWithError("Your 201 File is pending review and cannot be edited right now.", $userId, $oldInput, $isAjax);
}

// ==== Validation: Step 3 documents ====
$documentTypes = require ROOT_PATH . "/ui/page/page-1/backend/document_types.php";
$isMarried     = ($maritalStatus === 'MARRIED');

// Alamin kung ano na yung na-upload na noon (para hindi paulit-ulit i-require)
$existingDocCounts = [];
$stmt = $conn->prepare("SELECT document_type, COUNT(*) AS cnt FROM nobleuser_employee_documents WHERE user_id = ? GROUP BY document_type");
$stmt->bind_param("i", $userId);
$stmt->execute();
$res = $stmt->get_result();
while ($row = $res->fetch_assoc()) {
    $existingDocCounts[$row['document_type']] = (int) $row['cnt'];
}
$stmt->close();

$missingRequired = [];
foreach ($documentTypes as $key => $doc) {
    if ($key === 'marriage_certificate' && !$isMarried) continue;
    if (!$doc['required']) continue;
    if (($existingDocCounts[$key] ?? 0) > 0) continue;

    $hasNewFile = false;
    if (!empty($doc['multiple'])) {
        $names = $_FILES['documents']['name'][$key] ?? [];
        foreach ((array) $names as $n) {
            if ($n !== '') { $hasNewFile = true; break; }
        }
    } else {
        $hasNewFile = (($_FILES['documents']['name'][$key] ?? '') !== '');
    }

    if (!$hasNewFile) {
        $missingRequired[] = $doc['label'];
    }
}

if (!empty($missingRequired)) {
    redirectWithError("Missing required documents: " . implode(', ', $missingRequired), $userId, $oldInput, $isAjax);
}

// ==== Everything valid — proceed sa transaction ====
$conn->begin_transaction();

try {
    // -- Save/update Step 1 & 2 info --
    $stmt = $conn->prepare("SELECT id FROM nobleuser_employee_information WHERE user_id = ? LIMIT 1");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $existingInfoRow = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if ($existingInfoRow) {
        $stmt = $conn->prepare("UPDATE nobleuser_employee_information SET
            first_name = ?, middle_name = ?, last_name = ?, extension_name = ?,
            birthdate = ?, age = ?, gender = ?, birthplace = ?,
            marital_status = ?, present_address = ?, religion = ?, citizenship = ?,
            status = 'PENDING', review_notes = NULL, reviewed_by = NULL, reviewed_at = NULL
            WHERE user_id = ?");
        $stmt->bind_param(
            "sssssissssssi",
            $firstName, $middleName, $lastName, $extensionName,
            $birthdate, $age, $gender, $birthplace,
            $maritalStatus, $address, $religion, $citizenship,
            $userId
        );
        $stmt->execute();
        $stmt->close();
    } else {
        $referenceNumber = generateReferenceNumber($conn);

        $stmt = $conn->prepare("INSERT INTO nobleuser_employee_information
            (user_id, reference_number, first_name, middle_name, last_name, extension_name, birthdate, age, gender, birthplace, marital_status, present_address, religion, citizenship, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'PENDING')");
        $stmt->bind_param(
            "issssssissssss",
            $userId, $referenceNumber, $firstName, $middleName, $lastName, $extensionName,
            $birthdate, $age, $gender, $birthplace,
            $maritalStatus, $address, $religion, $citizenship
        );
        $stmt->execute();
        $stmt->close();
    }

    // -- Process document uploads --
    $maxFileSize   = 5 * 1024 * 1024; // 5MB
    $allowedMimes  = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
    $uploadBaseDir = ROOT_PATH . '/uploads/employee_documents/' . $userId . '/';

    if (!is_dir($uploadBaseDir)) {
        mkdir($uploadBaseDir, 0755, true);
    }

    foreach ($documentTypes as $key => $doc) {
        if ($key === 'marriage_certificate' && !$isMarried) continue;

        $isMultiple = !empty($doc['multiple']);
        $filesForThisType = [];

        if ($isMultiple) {
            $names = $_FILES['documents']['name'][$key] ?? [];
            foreach ((array) $names as $i => $name) {
                if ($name === '') continue;
                $filesForThisType[] = [
                    'tmp_name' => $_FILES['documents']['tmp_name'][$key][$i],
                    'name'     => $_FILES['documents']['name'][$key][$i],
                    'error'    => $_FILES['documents']['error'][$key][$i],
                    'size'     => $_FILES['documents']['size'][$key][$i],
                ];
            }
            $maxAllowed = $doc['max'] ?? 3;
            $existingCount = $existingDocCounts[$key] ?? 0;
            if (($existingCount + count($filesForThisType)) > $maxAllowed) {
                throw new Exception("Too many files for \"{$doc['label']}\" (max {$maxAllowed}).");
            }
        } else {
            $name = $_FILES['documents']['name'][$key] ?? '';
            if ($name !== '') {
                $filesForThisType[] = [
                    'tmp_name' => $_FILES['documents']['tmp_name'][$key],
                    'name'     => $_FILES['documents']['name'][$key],
                    'error'    => $_FILES['documents']['error'][$key],
                    'size'     => $_FILES['documents']['size'][$key],
                ];
            }
        }

        if (empty($filesForThisType)) continue;

        if (!$isMultiple && ($existingDocCounts[$key] ?? 0) > 0) {
            $oldStmt = $conn->prepare("SELECT file_path FROM nobleuser_employee_documents WHERE user_id = ? AND document_type = ?");
            $oldStmt->bind_param("is", $userId, $key);
            $oldStmt->execute();
            $oldRows = $oldStmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $oldStmt->close();

            foreach ($oldRows as $oldRow) {
                $fullOldPath = ROOT_PATH . $oldRow['file_path'];
                if (is_file($fullOldPath)) @unlink($fullOldPath);
            }

            $delStmt = $conn->prepare("DELETE FROM nobleuser_employee_documents WHERE user_id = ? AND document_type = ?");
            $delStmt->bind_param("is", $userId, $key);
            $delStmt->execute();
            $delStmt->close();
        }

        foreach ($filesForThisType as $file) {
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
                $userId, $key, $originalName, $storedName, $relativePath, $finalMime, $fileSize
            );
            $insertStmt->execute();
            $insertStmt->close();
        }
    }

    $notifTitle = $isResubmission
        ? "201 File resubmitted for review"
        : "New 201 File submitted for review";
    $notifMessage = trim("$firstName $lastName") . " has " . ($isResubmission ? "resubmitted" : "submitted") . " their 201 File and is waiting for HR review.";
    $notifLink = BASE_URL . "/hrpage-employees?id=" . $userId;

    $notifStmt = $conn->prepare("INSERT INTO nobleportalnotification
        (for_role, for_position, for_user_id, title, message, link, is_read, created_at)
        VALUES ('hr', 'head', NULL, ?, ?, ?, 0, NOW())");
    $notifStmt->bind_param("sss", $notifTitle, $notifMessage, $notifLink);
    $notifStmt->execute();
    $notifStmt->close();

    $conn->commit();

    $successMessage = $isResubmission
        ? '201 File resubmitted successfully. It is now pending HR review.'
        : '201 File submitted successfully. It is now pending HR review.';

    if ($isAjax) {
        jsonResponse(['success' => true, 'message' => $successMessage]);
    }

    header("Location: " . BASE_URL . "/page-1?saved=1");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    redirectWithError($e->getMessage(), $userId, $oldInput, $isAjax);
}