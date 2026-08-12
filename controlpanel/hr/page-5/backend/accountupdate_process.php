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
$username        = trim($_POST['username'] ?? '');
$password        = (string) ($_POST['password'] ?? '');
$confirmPassword = (string) ($_POST['confirm_password'] ?? '');

if ($id === '' || !ctype_digit($id)) {
    $response['message'] = "Invalid account.";
    echo json_encode($response);
    exit;
}

if ($firstName === '' || $lastName === '' || $username === '') {
    $response['message'] = "Please fill in all fields.";
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

// NOTE: same reasoning as register_process.php — instead of doing a
// separate SELECT-then-UPDATE uniqueness check (which has a race
// condition), we rely on the DB's UNIQUE constraint on `username`
// and catch duplicate error code 1062. We exclude the current row
// (id != ?) so saving without changing the username doesn't
// falsely collide with itself.

if ($changePassword) {
    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    $updateStmt = $conn->prepare("UPDATE nobleuserlist SET first_name = ?, last_name = ?, username = ?, password = ? WHERE id = ?");
    $updateStmt->bind_param("ssssi", $firstName, $lastName, $username, $hashedPassword, $id);
} else {
    $updateStmt = $conn->prepare("UPDATE nobleuserlist SET first_name = ?, last_name = ?, username = ? WHERE id = ?");
    $updateStmt->bind_param("sssi", $firstName, $lastName, $username, $id);
}

if ($updateStmt->execute()) {
    $response['success'] = true;
    $response['message'] = $changePassword
        ? "Successfully updated $firstName $lastName and their password."
        : "Successfully updated $firstName $lastName.";
} elseif ($conn->errno === 1062) {
    // Duplicate entry caught here at the DB level — no race condition possible
    $response['message'] = "Username '$username' is already taken. Please choose another.";
} else {
    $response['message'] = "Error updating record: " . $conn->error;
}

$updateStmt->close();

echo json_encode($response);
exit;