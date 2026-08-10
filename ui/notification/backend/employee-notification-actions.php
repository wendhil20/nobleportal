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

$redirectTo = BASE_URL . "/notification";

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
        $redirectTo = $row['link'];
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

header("Location: " . $redirectTo);
exit;