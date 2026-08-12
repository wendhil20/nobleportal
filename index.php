<?php
// index.php
define('ROOT_PATH', __DIR__);

require_once ROOT_PATH . '/vendor/autoload.php';

// ─── Load .env ────────────────────────────────────────────────────────────────
$envFile = ROOT_PATH . '/.env';

if (!file_exists($envFile)) {
    die('.env file not found.');
}

foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || str_starts_with($line, '#'))
        continue;
    if (!str_contains($line, '='))
        continue;

    [$key, $value] = explode('=', $line, 2);
    $_ENV[trim($key)] = trim($value);
}

// ─── Constants ────────────────────────────────────────────────────────────────
define('GOOGLE_CLIENT_ID', $_ENV['GOOGLE_CLIENT_ID'] ?? '');
define('GOOGLE_CLIENT_SECRET', $_ENV['GOOGLE_CLIENT_SECRET'] ?? '');

// ─── Base URL ─────────────────────────────────────────────────────────────────
$isLocalhost = (
    strpos($_SERVER['HTTP_HOST'], 'localhost') !== false ||
    strpos($_SERVER['HTTP_HOST'], '127.0.0.1') !== false
);

define(
    'BASE_URL',
    $isLocalhost
    ? 'http://localhost/nobleportal'
    : $_ENV['APP_URL']
);

// ─── Routing ──────────────────────────────────────────────────────────────────
$request = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request = trim($request, '/');
$request = preg_replace('#^nobleportal/?#', '', $request);
$request = trim($request, '/');

if ($request === '' || $request === 'login') {
    $request = 'login';
}

// ─── Define Admin Routes ──────────────────────────────────────────────────────
$adminRoutes = [

    'admin-login',
    'admin-handler',
    'admin-register',
    'handler',
    'admin-logout',
    'admin-notification',

    //hr page 1
    'hrpage-1',
    'hr-employees',
    'hr-viewdocument',
    'employee_search',
    'hr-approved',
    'hr-employeedetails',
    'save-employment',
    'departmentcrud',
    'view-information',
    'hr-flagdocument',

    //hr page 2
    'hrpage-2',
    'registerprocess',

    //hr page 3
    'hr-orientation',

    //hr page 4
    'admin-resignation',
    'admin-resignation-handler',

    //hr page 5
    'management-account',
    'accountupdate-process',

];

if (in_array($request, $adminRoutes)) {
    session_name('noblecontrolpanel');
} else {
    session_name('nobleui');
}

session_start();

// ─── Routes ───────────────────────────────────────────────────────────────────
$routes = [

    //admin notification
    'admin-notification'                                                 => 'controlpanel/notification/admin-notification.php',
    'admin-notification-handler'                                         => 'controlpanel/notification/backend/admin-notification-handler.php',

    //admin
    'admin-login'                                                        => 'controlpanel/auth/auth-1/login.php',
    'admin-handler'                                                      => 'controlpanel/auth/auth-1/backend/handler.php',
    'admin-register'                                                     => 'controlpanel/auth/auth-2/registerform.php',
    'handler'                                                            => 'controlpanel/auth/auth-2/backend/settings_handler.php',
    'admin-logout'                                                       => 'controlpanel/auth/auth-1/logout.php',

    //hr page 1
    'hrpage-1'                                                           => 'controlpanel/hr/page-1/main.php',
    'hr-employees'                                                       => 'controlpanel/hr/page-1/mainview.php',
    'hr-viewdocument'                                                    => 'controlpanel/hr/page-1/viewdocument.php',
    'employee_search'                                                    => 'controlpanel/hr/page-1/backend/ajax_employee_search.php',
    'hr-approved'                                                        => 'controlpanel/hr/page-1/backend/hr-approved.php',
    'hr-employeedetails'                                                 => 'controlpanel/hr/page-1/hr-employeedetails.php',
    'save-employment'                                                    => 'controlpanel/hr/page-1/backend/save_employment.php',
    'departmentcrud'                                                     => 'controlpanel/hr/page-1/backend/department_crud.php',
    'view-information'                                                   => 'controlpanel/hr/page-1/viewinformation.php',
    'hr-flagdocument'                                                    => 'controlpanel/hr/page-1/backend/hr-flagdocument.php',


    //hr page 2
    'hrpage-2'                                                           => 'controlpanel/hr/page-2/registeraccount.php',
    'registerprocess'                                                    => 'controlpanel/hr/page-2/backend/register_process.php',

    //hr page 3
    'hr-orientation'                                                     => 'controlpanel/hr/page-3/hr-orientation.php',

    //hr page 4
    'admin-resignation'                                                  => 'controlpanel/hr/page-4/resignrequest.php',
    'admin-resignation-handler'                                          => 'controlpanel/hr/page-4/backend/resignation-actions.php',

    //hr page 5
    'management-account'                                                 => 'controlpanel/hr/page-5/managementaccount.php',
    'accountupdate-process'                                                => 'controlpanel/hr/page-5/backend/accountupdate_process.php',

    //user
    'login'                                                              => 'ui/auth/login/index.php',
    'login-process'                                                      => 'ui/auth/login/backend/loginprocess.php',
    'logout'                                                             => 'ui/auth/logout/logout.php',

    //regulation
    'termandpolicy'                                                      => 'ui/regulation/termandpolicy.php',

    //notification
    'notification'                                                       => 'ui/notification/notification.php',
    'notification-handler'                                               => 'ui/notification/backend/employee-notification-actions.php',
    'notification-count'                                                 => 'ui/navigation/backend/notification-count.php',
    'notification-poll'                                                  => 'ui/notification/backend/employee-notification-poll.php',

    //page 1
    'page-1'                                                             => 'ui/page/page-1/main.php',
    'page-1-personalhandler'                                             => 'ui/page/page-1/backend/personalhandler.php',
    'page-1-viewdocument'                                                => 'ui/page/page-1/backend/page-1-viewdocument.php',
    'page-1-reupload'                                                    => 'ui/page/page-1/backend/page-1-reupload.php',

    //page 2
    'page-2'                                                             => 'ui/page/page-2/employee-main.php',

    //page 3
    'page-3'                                                             => 'ui/page/page-3/orientation.php',

    //page 4
    'page-4'                                                             => 'ui/page/page-4/resign.php',
    'resignation-handler'                                                => 'ui/page/page-4/backend/employee-resignation-actions.php',




];

if (preg_match('#^login/(\d+)$#', $request, $m)) {
    $_GET['id'] = $m[1];
    $request = 'login';
}

$file = $routes[$request] ?? null;

if ($file === null) {
    header('Location: ' . BASE_URL . '/');
    exit;
}

$filepath = ROOT_PATH . '/' . $file;

if (file_exists($filepath)) {
    include $filepath;
} else {
    header('Location: ' . BASE_URL . '/');
    exit;
}