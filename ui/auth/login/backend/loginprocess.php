<?php
//loginprocess.php
include ROOT_PATH . "/network/connect.php"; // gives us $conn (mysqli)

// ==== 1. Only allow POST requests ====
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: " . BASE_URL . "/login");
    exit;
}

// ==== 2. Basic input handling ====
$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

function redirectWithError($msg) {
    $_SESSION['login_error'] = $msg;
    header("Location: " . BASE_URL . "/login");
    exit;
}

if ($username === '' || $password === '') {
    redirectWithError("Please enter your Employee ID and password.");
}

// ==== 3. Look up the account (prepared statement via mysqli) ====
$stmt = $conn->prepare("SELECT id, first_name, last_name, username, password 
                         FROM nobleuserlist 
                         WHERE username = ? 
                         LIMIT 1");
$stmt->bind_param("s", $username);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// ==== 4. Verify credentials ====
// Same generic message for "no such user" and "wrong password" so we don't leak which one was wrong.
if (!$user || !password_verify($password, $user['password'])) {
    redirectWithError("Invalid Employee ID or password.");
}

// ==== 5. Success: start the session ====
session_regenerate_id(true); // prevent session fixation

$_SESSION['user_id']    = $user['id'];
$_SESSION['username']   = $user['username'];
$_SESSION['first_name'] = $user['first_name'];
$_SESSION['last_name']  = $user['last_name'];

unset($_SESSION['login_error']);

// ==== 6. Redirect to dashboard/control panel ====
header("Location: " . BASE_URL . "/page-1");
exit;