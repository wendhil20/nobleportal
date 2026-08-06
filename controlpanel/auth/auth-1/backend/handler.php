<?php
// handler.php
include ROOT_PATH . '/network/connect.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '/admin-login');
    exit;
}

$email = trim($_POST['email'] ?? '');
$password = $_POST['password'] ?? '';

if ($email === '' || $password === '') {
    header('Location: ' . BASE_URL . '/admin-login/?error=1');
    exit;
}

$stmt = $conn->prepare("SELECT id, name, email, role, position, password FROM nobleadminlist WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();
$stmt->close();

if ($admin && password_verify($password, $admin['password'])) {
    // Regenerate session ID to prevent session fixation
    session_regenerate_id(true);

    $_SESSION['admin_id']       = $admin['id'];
    $_SESSION['admin_name']     = $admin['name'];
    $_SESSION['admin_email']    = $admin['email'];
    $_SESSION['admin_role']     = $admin['role'];
    $_SESSION['admin_position'] = $admin['position'];

    // Update last_active timestamp
    $update = $conn->prepare("UPDATE nobleadminlist SET last_active = NOW() WHERE id = ?");
    $update->bind_param("i", $admin['id']);
    $update->execute();
    $update->close();

    // Determine redirect destination based on role (department) + position
    $role = $admin['role'];
    $position = $admin['position'];

    $destination = match ($role) {
        'hr' => match ($position) {
            'head'               => 'hrpage-1',
            'hrstaff'            => 'hrstaff',
            default              => 'hrpage-1',
        },

        // Fallback for roles/departments not yet mapped above
        default => 'admin-login',
    };

    header('Location: ' . BASE_URL . '/' . $destination);
    exit;
} else {
    header('Location: ' . BASE_URL . '/admin-login/?error=1');
    exit;
}