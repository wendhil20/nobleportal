<?php
//register_process.php
// ==== BACKEND HANDLER (separated from the view) ====
// Called by registeraccount.php via AJAX/fetch (JSON response)

include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr','head');

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

$firstName = trim($_POST['first_name'] ?? '');
$lastName  = trim($_POST['last_name'] ?? '');
$username  = trim($_POST['username'] ?? '');
$password  = trim($_POST['password'] ?? '');

if ($firstName === '' || $lastName === '' || $username === '' || $password === '') {
    $response['message'] = "Please fill in all fields.";
    echo json_encode($response);
    exit;
}

// NOTE: replace $conn / $pdo depending on what connect.php uses
$checkStmt = $conn->prepare("SELECT id FROM nobleuserlist WHERE username = ?");
$checkStmt->bind_param("s", $username);
$checkStmt->execute();
$checkStmt->store_result();

if ($checkStmt->num_rows > 0) {
    $response['message'] = "Username '$username' is already taken. Please choose another.";
    $checkStmt->close();
    echo json_encode($response);
    exit;
}
$checkStmt->close();

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertStmt = $conn->prepare("INSERT INTO nobleuserlist (first_name, last_name, username, password) VALUES (?, ?, ?, ?)");
$insertStmt->bind_param("ssss", $firstName, $lastName, $username, $hashedPassword);

if ($insertStmt->execute()) {
    $response['success'] = true;
    $response['message'] = "Successfully registered $firstName $lastName. Username: $username";
} else {
    $response['message'] = "Error saving record: " . $conn->error;
}

$insertStmt->close();

echo json_encode($response);
exit;