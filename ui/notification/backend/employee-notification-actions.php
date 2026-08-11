<?php
//employee-notification-actions.php
include ROOT_PATH . "/network/connect.php";

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    return;
}

$userId = $_SESSION['user_id'] ?? 0;

if ($userId <= 0) {
    return;
}

$isAjax = (
    (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
    || (isset($_POST['ajax']) && $_POST['ajax'] === '1')
);

$redirectTo   = BASE_URL . "/notification";
$responseLink = null;

if (isset($_POST['mark_read_id'])) {
    $notifId = (int) $_POST['mark_read_id'];

    // recipient_type = 'user' lang ang pwedeng i-mark dito, at dapat
    // pag-aari niya talaga (for_user_id = kanyang session).
    $stmt = $conn->prepare("UPDATE nobleportalnotification
        SET is_read = 1
        WHERE id = ? AND for_user_id = ? AND recipient_type = 'user'");
    $stmt->bind_param("ii", $notifId, $userId);
    $stmt->execute();
    $stmt->close();

    // kunin yung link ng notification para dun i-redirect ang user
    $linkStmt = $conn->prepare("SELECT link FROM nobleportalnotification
        WHERE id = ? AND for_user_id = ? AND recipient_type = 'user'");
    $linkStmt->bind_param("ii", $notifId, $userId);
    $linkStmt->execute();
    $linkRes = $linkStmt->get_result();
    $row = $linkRes->fetch_assoc();
    $linkStmt->close();

    if (!empty($row['link'])) {
        $redirectTo   = $row['link'];
        $responseLink = $row['link'];
    }
}

if (isset($_POST['mark_all_read'])) {
    $stmt = $conn->prepare("UPDATE nobleportalnotification
        SET is_read = 1
        WHERE for_user_id = ? AND recipient_type = 'user'");
    $stmt->bind_param("i", $userId);
    $stmt->execute();
    $stmt->close();
}

if ($isAjax) {
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'link' => $responseLink]);
    exit;
}

header("Location: " . $redirectTo);
exit;