<?php
// network/auth_guard.php
/**
 * Restricts the current page to specific role(s) and/or position(s).
 * Call this at the top of any protected file, right after including this guard.
 *
 * @param string|array $allowedRoles     Role(s) allowed on this page. e.g. 'HR DEPARTMENT' or ['HR DEPARTMENT', 'ADMIN DEPARTMENT']
 * @param string|array|null $allowedPositions  Position(s) allowed. e.g. 'head' or ['head', 'hrstaff']. Null = any position within the allowed role(s).
 */
function requireAccess($allowedRoles, $allowedPositions = null)
{
    // 1. Hindi naka-login? Balik sa login page.
    if (!isset($_SESSION['admin_id'])) {
        header('Location: ' . BASE_URL . '/admin-login');
        exit;
    }

    $adminRole     = $_SESSION['admin_role'];
    $adminPosition = $_SESSION['admin_position'];

    // Normalize to arrays para pareho ang comparison kahit single value lang ipasa
    $allowedRoles = is_array($allowedRoles) ? $allowedRoles : [$allowedRoles];

    // 2. Check kung tugma ang role
    if (!in_array($adminRole, $allowedRoles, true)) {
        header('Location: ' . BASE_URL . '/admin-login');
        exit;
    }

    // 3. Kung may binigay na allowed positions, i-check din
    if ($allowedPositions !== null) {
        $allowedPositions = is_array($allowedPositions) ? $allowedPositions : [$allowedPositions];

        if (!in_array($adminPosition, $allowedPositions, true)) {
            header('Location: ' . BASE_URL . '/admin-login');
            exit;
        }
    }
}