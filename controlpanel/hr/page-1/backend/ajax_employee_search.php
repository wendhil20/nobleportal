<?php
// ajax_employee_search.php — Realtime search endpoint for Employee 201 Files
include ROOT_PATH . "/network/connect.php";
include ROOT_PATH . "/controlpanel/auth/role/auth_guard.php";

requireAccess('hr', 'head');

header('Content-Type: application/json');

$search = trim($_GET['q'] ?? '');

if ($search !== '') {
    $like = '%' . $search . '%';
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, username, created_at
         FROM nobleuserlist
         WHERE first_name LIKE ? OR last_name LIKE ? OR username LIKE ?
         ORDER BY last_name, first_name"
    );
    $stmt->bind_param("sss", $like, $like, $like);
} else {
    $stmt = $conn->prepare(
        "SELECT id, first_name, last_name, username, created_at
         FROM nobleuserlist
         ORDER BY last_name, first_name"
    );
}
$stmt->execute();
$employees = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$hasInfo = [];
$res = $conn->query("SELECT DISTINCT user_id FROM nobleuser_employee_information");
while ($row = $res->fetch_assoc()) {
    $hasInfo[(int) $row['user_id']] = true;
}

$data = [];
foreach ($employees as $emp) {
    $data[] = [
        'id'         => (int) $emp['id'],
        'name'       => $emp['first_name'] . ' ' . $emp['last_name'],
        'username'   => $emp['username'],
        'on_file'    => !empty($hasInfo[(int) $emp['id']]),
        'view_url'   => BASE_URL . '/hr-employees?id=' . (int) $emp['id'],
    ];
}

echo json_encode(['employees' => $data]);