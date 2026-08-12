<?php
//accountupdate_process.php
// ==== BACKEND HANDLER (separated from the view) ====
// Called by managementaccount.php via AJAX/fetch (JSON response)

include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

header('Content-Type: application/json');

$response = [
    'success' => false,
    'message' => ''
];

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $response['message'] = "Invalid request method.";
    echo json_encode($response);
    exit;
}

$id              = trim($_POST['id'] ?? '');
$firstName       = trim($_POST['first_name'] ?? '');
$lastName        = trim($_POST['last_name'] ?? '');
$password        = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($id === '' || !ctype_digit($id)) {
    $response['message'] = "Invalid account.";
    echo json_encode($response);
    exit;
}

if ($firstName === '' || $lastName === '') {
    $response['message'] = "Please fill in both first and last name.";
    echo json_encode($response);
    exit;
}

// Password is optional — only validate/update it if the user actually typed something
$changePassword = ($password !== '' || $confirmPassword !== '');

if ($changePassword) {
    if (strlen($password) < 8) {
        $response['message'] = "Password must be at least 8 characters.";
        echo json_encode($response);
        exit;
    }
    if ($password !== $confirmPassword) {
        $response['message'] = "Passwords do not match.";
        echo json_encode($response);
        exit;
    }
}

$id = (int) $id;

// Confirm the account exists before attempting the update
$checkStmt = $conn->prepare("SELECT id FROM nobleuserlist WHERE id = ?");
$checkStmt->bind_param("i", $id);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows === 0) {
    $response['message'] = "Account not found.";
    $checkStmt->close();
    echo json_encode($response);
    exit;
}
$checkStmt->close();

if ($changePassword) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $updateStmt = $conn->prepare("UPDATE nobleuserlist SET first_name = ?, last_name = ?, password = ? WHERE id = ?");
    $updateStmt->bind_param("sssi", $firstName, $lastName, $hashedPassword, $id);
} else {
    $updateStmt = $conn->prepare("UPDATE nobleuserlist SET first_name = ?, last_name = ? WHERE id = ?");
    $updateStmt->bind_param("ssi", $firstName, $lastName, $id);
}

if ($updateStmt->execute()) {
    $response['success'] = true;
    $response['message'] = $changePassword
        ? "Successfully updated $firstName $lastName and their password."
        : "Successfully updated $firstName $lastName.";
} else {
    $response['message'] = "Error updating record: " . $conn->error;
}

$updateStmt->close();

echo json_encode($response);
exit;