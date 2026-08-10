<?php
//department_crud.php — AJAX CRUD for departments
header('Content-Type: application/json');

include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

function respond($success, $message = '', $extra = []) {
    echo json_encode(array_merge(['success' => $success, 'message' => $message], $extra));
    exit;
}

// ---------- LIST (GET) ----------
if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'list') {
    $departments = $conn->query("SELECT id, name FROM departments ORDER BY name")->fetch_all(MYSQLI_ASSOC);
    respond(true, '', ['departments' => $departments]);
}

// ---------- ADD / EDIT / DELETE (POST, JSON body) ----------
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(false, 'Invalid request method.');
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$action = $input['action'] ?? '';

switch ($action) {
    case 'add': {
        $name = trim($input['name'] ?? '');
        if ($name === '') {
            respond(false, 'Department name is required.');
        }

        $check = $conn->prepare("SELECT id FROM departments WHERE name = ?");
        $check->bind_param("s", $name);
        $check->execute();
        if ($check->get_result()->fetch_assoc()) {
            respond(false, 'A department with that name already exists.');
        }
        $check->close();

        $stmt = $conn->prepare("INSERT INTO departments (name, created_at) VALUES (?, NOW())");
        $stmt->bind_param("s", $name);
        if (!$stmt->execute()) {
            respond(false, 'Failed to add department.');
        }
        $stmt->close();
        respond(true, 'Department added.');
        break;
    }

    case 'edit': {
        $id = (int) ($input['id'] ?? 0);
        $name = trim($input['name'] ?? '');
        if ($id <= 0 || $name === '') {
            respond(false, 'Department and name are required.');
        }

        $stmt = $conn->prepare("UPDATE departments SET name = ? WHERE id = ?");
        $stmt->bind_param("si", $name, $id);
        if (!$stmt->execute()) {
            respond(false, 'Failed to update department.');
        }
        $stmt->close();
        respond(true, 'Department updated.');
        break;
    }

    case 'delete': {
        $id = (int) ($input['id'] ?? 0);
        if ($id <= 0) {
            respond(false, 'Invalid department.');
        }

        // Prevent deleting a department that's still assigned to employees
        $inUse = $conn->prepare("SELECT COUNT(*) AS cnt FROM nobleuser_employment_details WHERE department_id = ?");
        $inUse->bind_param("i", $id);
        $inUse->execute();
        $count = (int) $inUse->get_result()->fetch_assoc()['cnt'];
        $inUse->close();

        if ($count > 0) {
            respond(false, "Can't delete — this department is still assigned to $count employee(s).");
        }

        $stmt = $conn->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->bind_param("i", $id);
        if (!$stmt->execute()) {
            respond(false, 'Failed to delete department.');
        }
        $stmt->close();
        respond(true, 'Department deleted.');
        break;
    }

    default:
        respond(false, 'Unknown action.');
}