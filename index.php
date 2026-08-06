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

    //hr
    'hrpage-1',
    'hrpage-2',
    'registerprocess',
    'hr-employees',
    'hr-viewdocument',
    'employee_search'
];

if (in_array($request, $adminRoutes)) {
    session_name('noblecontrolpanel');
} else {
    session_name('nobleui');
}

session_start();

// ─── Routes ───────────────────────────────────────────────────────────────────
$routes = [
    //admin
    'admin-login'                               => 'controlpanel/auth/auth-1/login.php',
    'admin-handler'                             => 'controlpanel/auth/auth-1/backend/handler.php',
    'admin-register'                            => 'controlpanel/auth/auth-2/registerform.php',
    'handler'                                   => 'controlpanel/auth/auth-2/backend/settings_handler.php',
    'admin-logout'                              => 'controlpanel/auth/auth-1/logout.php',

    //hr
    'hrpage-1'                                  => 'controlpanel/hr/page-1/main.php',
    'hrpage-2'                                  => 'controlpanel/hr/page-2/registeraccount.php',
    'registerprocess'                           => 'controlpanel/hr/page-2/backend/register_process.php',
    'hr-employees'                              => 'controlpanel/hr/page-1/mainview.php',
    'hr-viewdocument'                           => 'controlpanel/hr/page-1/viewdocument.php',
    'employee_search'                      => 'controlpanel/hr/page-1/backend/ajax_employee_search.php',

    //user
    'login'                                     => 'ui/auth/login/index.php',
    'login-process'                             => 'ui/auth/login/backend/loginprocess.php',
    'logout'                                    => 'ui/auth/logout/logout.php',

    //page
    'page-1'                                    => 'ui/page/page-1/main.php',
    'page-1-personalhandler'                    => 'ui/page/page-1/backend/personalhandler.php',

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