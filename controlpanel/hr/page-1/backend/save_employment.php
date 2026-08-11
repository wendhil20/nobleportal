<?php
//save_employment.php — AJAX handler for Employment Details form
header('Content-Type: application/json');

include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$userId = (int) ($_POST['user_id'] ?? 0);
$departmentId = !empty($_POST['department_id']) ? (int) $_POST['department_id'] : null;
$employmentType = trim($_POST['employment_type'] ?? '');

$salary = ($_POST['salary'] ?? '') !== '' ? (float) $_POST['salary'] : null;
$dailyRate = ($_POST['daily_rate'] ?? '') !== '' ? (float) $_POST['daily_rate'] : null;

// ---------- Allowance breakdown ----------
$allowanceLoad = ($_POST['allowance_load'] ?? '') !== '' ? (float) $_POST['allowance_load'] : null;
$allowanceTransportation = ($_POST['allowance_transportation'] ?? '') !== '' ? (float) $_POST['allowance_transportation'] : null;
$allowanceMeal = ($_POST['allowance_meal'] ?? '') !== '' ? (float) $_POST['allowance_meal'] : null;
$allowanceOthersLabel = trim($_POST['allowance_others_label'] ?? '');
$allowanceOthersAmount = ($_POST['allowance_others_amount'] ?? '') !== '' ? (float) $_POST['allowance_others_amount'] : null;

// Total allowance = sum of all parts (kept in `allowance` column for backward compat)
$allowance = ($allowanceLoad ?? 0) + ($allowanceTransportation ?? 0) + ($allowanceMeal ?? 0) + ($allowanceOthersAmount ?? 0);

$email = trim($_POST['email'] ?? '');
$contactNumber = trim($_POST['contact_number'] ?? '');

$emergencyName = trim($_POST['emergency_contact_name'] ?? '');
$emergencyNumber = trim($_POST['emergency_contact_number'] ?? '');
$presentAddress = trim($_POST['present_address'] ?? '');

$sssNumber = trim($_POST['sss_number'] ?? '');
$philhealthNumber = trim($_POST['philhealth_number'] ?? '');
$pagibigNumber = trim($_POST['pagibig_number'] ?? '');
$tinNumber = trim($_POST['tin_number'] ?? '');

$allowedTypes = ['trainee', 'probationary', 'regular', 'project_based'];

if ($userId <= 0) {
    respond(false, 'Missing employee.');
}
if (!in_array($employmentType, $allowedTypes, true)) {
    respond(false, 'Please select a valid employment type.');
}
if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(false, 'Please enter a valid email address.');
}
if ($allowanceOthersAmount !== null && $allowanceOthersAmount > 0 && $allowanceOthersLabel === '') {
    respond(false, 'Please specify the "Others" allowance.');
}

// Confirm employee exists
$stmt = $conn->prepare("SELECT id FROM nobleuserlist WHERE id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
if (!$stmt->get_result()->fetch_assoc()) {
    respond(false, 'Employee not found.');
}
$stmt->close();

// Check kung meron nang existing record BEFORE upsert (para malaman kung "added" or "updated")
$stmt = $conn->prepare("SELECT contract_file, picture FROM nobleuser_employment_details WHERE user_id = ?");
$stmt->bind_param("i", $userId);
$stmt->execute();
$existing = $stmt->get_result()->fetch_assoc();
$stmt->close();

$isNewRecord = !$existing;
$contractPath = $existing['contract_file'] ?? null;
$picturePath = $existing['picture'] ?? null;

$uploadRoot = ROOT_PATH . '/uploads/employment';
$contractDir = $uploadRoot . '/contracts';
$pictureDir = $uploadRoot . '/pictures';

if (!is_dir($contractDir)) {
    @mkdir($contractDir, 0755, true);
}
if (!is_dir($pictureDir)) {
    @mkdir($pictureDir, 0755, true);
}

// ---------- Contract upload (PDF only) ----------
if (!empty($_FILES['contract_file']['name']) && $_FILES['contract_file']['error'] === UPLOAD_ERR_OK) {
    $tmpPath = $_FILES['contract_file']['tmp_name'];
    $mime = mime_content_type($tmpPath);

    if ($mime !== 'application/pdf') {
        respond(false, 'Contract file must be a PDF.');
    }
    if ($_FILES['contract_file']['size'] > 10 * 1024 * 1024) { // 10MB cap
        respond(false, 'Contract PDF must be under 10MB.');
    }

    $filename = 'contract_' . $userId . '_' . time() . '.pdf';
    $destPath = $contractDir . '/' . $filename;

    if (!move_uploaded_file($tmpPath, $destPath)) {
        respond(false, 'Failed to upload contract file.');
    }

    $contractPath = 'uploads/employment/contracts/' . $filename;
}

// ---------- Picture upload (converted to WebP) ----------
if (!empty($_FILES['picture']['name']) && $_FILES['picture']['error'] === UPLOAD_ERR_OK) {
    $tmpPath = $_FILES['picture']['tmp_name'];

    if ($_FILES['picture']['size'] > 8 * 1024 * 1024) { // 8MB cap
        respond(false, 'Photo must be under 8MB.');
    }

    $imageInfo = @getimagesize($tmpPath);
    if ($imageInfo === false) {
        respond(false, 'Uploaded photo is not a valid image.');
    }

    $mime = $imageInfo['mime'];
    $sourceImage = null;

    switch ($mime) {
        case 'image/jpeg':
            $sourceImage = imagecreatefromjpeg($tmpPath);
            break;
        case 'image/png':
            $sourceImage = imagecreatefrompng($tmpPath);
            imagepalettetotruecolor($sourceImage);
            imagealphablending($sourceImage, true);
            imagesavealpha($sourceImage, true);
            break;
        case 'image/webp':
            $sourceImage = imagecreatefromwebp($tmpPath);
            break;
        default:
            respond(false, 'Photo must be a JPG, PNG, or WebP file.');
    }

    if (!$sourceImage) {
        respond(false, 'Could not process the uploaded photo.');
    }

    $filename = 'photo_' . $userId . '_' . time() . '.webp';
    $destPath = $pictureDir . '/' . $filename;

    if (!imagewebp($sourceImage, $destPath, 85)) {
        imagedestroy($sourceImage);
        respond(false, 'Failed to convert photo to WebP.');
    }
    imagedestroy($sourceImage);

    $picturePath = 'uploads/employment/pictures/' . $filename;
}

// ---------- Upsert ----------
$stmt = $conn->prepare(
    "INSERT INTO nobleuser_employment_details
        (user_id, department_id, employment_type, salary, daily_rate, allowance,
         allowance_load, allowance_transportation, allowance_meal, allowance_others_label, allowance_others_amount,
         email, contact_number, emergency_contact_name, emergency_contact_number, present_address,
         sss_number, philhealth_number, pagibig_number, tin_number,
         contract_file, picture, updated_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
     ON DUPLICATE KEY UPDATE
        department_id = VALUES(department_id),
        employment_type = VALUES(employment_type),
        salary = VALUES(salary),
        daily_rate = VALUES(daily_rate),
        allowance = VALUES(allowance),
        allowance_load = VALUES(allowance_load),
        allowance_transportation = VALUES(allowance_transportation),
        allowance_meal = VALUES(allowance_meal),
        allowance_others_label = VALUES(allowance_others_label),
        allowance_others_amount = VALUES(allowance_others_amount),
        email = VALUES(email),
        contact_number = VALUES(contact_number),
        emergency_contact_name = VALUES(emergency_contact_name),
        emergency_contact_number = VALUES(emergency_contact_number),
        present_address = VALUES(present_address),
        sss_number = VALUES(sss_number),
        philhealth_number = VALUES(philhealth_number),
        pagibig_number = VALUES(pagibig_number),
        tin_number = VALUES(tin_number),
        contract_file = VALUES(contract_file),
        picture = VALUES(picture),
        updated_at = NOW()"
);
$stmt->bind_param(
    "iisddddddsdsssssssssss",
    $userId, $departmentId, $employmentType, $salary, $dailyRate, $allowance,
    $allowanceLoad, $allowanceTransportation, $allowanceMeal, $allowanceOthersLabel, $allowanceOthersAmount,
    $email, $contactNumber, $emergencyName, $emergencyNumber, $presentAddress,
    $sssNumber, $philhealthNumber, $pagibigNumber, $tinNumber,
    $contractPath, $picturePath
);

if (!$stmt->execute()) {
    respond(false, 'Database error while saving employment details.');
}
$stmt->close();

// ---------- Notify the employee (reuses existing nobleportalnotification table) ----------
$notifTitle = $isNewRecord ? 'Employment Details Added' : 'Employment Details Updated';
$notifMessage = $isNewRecord
    ? 'Your employment 201 file has been set up by HR. Click to view your details.'
    : 'Your employment 201 file has been updated by HR. Click to view your details.';
$notifLink = BASE_URL . '/page-2';

$stmt = $conn->prepare(
    "INSERT INTO nobleportalnotification (for_role, for_position, for_user_id, recipient_type, title, message, link, is_read, created_at)
     VALUES (NULL, NULL, ?, 'user', ?, ?, ?, 0, NOW())"
);
$stmt->bind_param("isss", $userId, $notifTitle, $notifMessage, $notifLink);
$stmt->execute();
$stmt->close();

respond(true, 'Employment details saved.');