<?php
include ROOT_PATH . '/network/connect.php';
header('Content-Type: application/json');

$action = $_POST['action'] ?? $_GET['action'] ?? '';

function respond($success, $data = [], $message = '') {
    echo json_encode(['success' => $success, 'message' => $message, 'data' => $data]);
    exit;
}

switch ($action) {

    // ---------- ROLES ----------
    case 'list_roles':
        $res = $conn->query("SELECT * FROM noble_roles ORDER BY role_name ASC");
        respond(true, $res->fetch_all(MYSQLI_ASSOC));

    case 'add_role':
        $name = trim($_POST['role_name'] ?? '');
        if ($name === '') respond(false, [], 'Role name is required.');
        $stmt = $conn->prepare("INSERT INTO noble_roles (role_name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if (!$stmt->execute()) respond(false, [], 'Role already exists.');
        respond(true, [], 'Role added.');

    case 'edit_role':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['role_name'] ?? '');
        if ($id <= 0 || $name === '') respond(false, [], 'Invalid data.');
        $stmt = $conn->prepare("UPDATE noble_roles SET role_name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if (!$stmt->execute()) respond(false, [], 'Update failed. Name may already exist.');
        respond(true, [], 'Role updated.');

    case 'delete_role':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) respond(false, [], 'Invalid ID.');
        $stmt = $conn->prepare("DELETE FROM noble_roles WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        respond(true, [], 'Role deleted.');

    // ---------- POSITIONS ----------
    case 'list_positions':
        $res = $conn->query("SELECT * FROM noble_positions ORDER BY position_name ASC");
        respond(true, $res->fetch_all(MYSQLI_ASSOC));

    case 'add_position':
        $name = trim($_POST['position_name'] ?? '');
        if ($name === '') respond(false, [], 'Position name is required.');
        $stmt = $conn->prepare("INSERT INTO noble_positions (position_name) VALUES (?)");
        $stmt->bind_param("s", $name);
        if (!$stmt->execute()) respond(false, [], 'Position already exists.');
        respond(true, [], 'Position added.');

    case 'edit_position':
        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['position_name'] ?? '');
        if ($id <= 0 || $name === '') respond(false, [], 'Invalid data.');
        $stmt = $conn->prepare("UPDATE noble_positions SET position_name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if (!$stmt->execute()) respond(false, [], 'Update failed. Name may already exist.');
        respond(true, [], 'Position updated.');

    case 'delete_position':
        $id = (int)($_POST['id'] ?? 0);
        if ($id <= 0) respond(false, [], 'Invalid ID.');
        $stmt = $conn->prepare("DELETE FROM noble_positions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        respond(true, [], 'Position deleted.');

    default:
        respond(false, [], 'Unknown action.');
}