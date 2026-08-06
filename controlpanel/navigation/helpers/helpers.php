<?php
// controlpanel/auth/role/helpers.php

function hasAccess($role, $position = null) {
    if (!isset($_SESSION['admin_role'])) {
        return false;
    }

    if ($_SESSION['admin_role'] !== $role) {
        return false;
    }

    if ($position !== null && (!isset($_SESSION['admin_position']) || $_SESSION['admin_position'] !== $position)) {
        return false;
    }

    return true;
}

function hasAnyAccess(array $combinations) {
    foreach ($combinations as $combo) {
        [$role, $position] = $combo + [null, null];
        if (hasAccess($role, $position)) {
            return true;
        }
    }
    return false;
}