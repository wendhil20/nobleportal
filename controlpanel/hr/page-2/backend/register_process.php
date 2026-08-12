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

// NOTE: `username` column must have a UNIQUE index/key in the DB
// (confirmed present on nobleuserlist.username).
// We removed the separate SELECT-then-INSERT check because it has a
// race condition: two near-simultaneous requests (double click, double
// event binding, retried request, etc.) can both pass the SELECT check
// before either INSERT completes, resulting in duplicate rows.
// Relying on the DB's UNIQUE constraint + catching the duplicate error
// code (1062) makes the "is this username taken" check atomic.

$hashedPassword = password_hash($password, PASSWORD_DEFAULT);

$insertStmt = $conn->prepare("INSERT INTO nobleuserlist (first_name, last_name, username, password) VALUES (?, ?, ?, ?)");
$insertStmt->bind_param("ssss", $firstName, $lastName, $username, $hashedPassword);

if ($insertStmt->execute()) {
    $response['success'] = true;
    $response['message'] = "Successfully registered $firstName $lastName. Username: $username";
} elseif ($conn->errno === 1062) {
    // Duplicate entry caught here at the DB level — no race condition possible
    $response['message'] = "Username '$username' is already taken. Please choose another.";
} else {
    $response['message'] = "Error saving record: " . $conn->error;
}

$insertStmt->close();

echo json_encode($response);
exit;