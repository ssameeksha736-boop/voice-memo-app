<?php

declare(strict_types=1);

// Let PHP's built-in dev server (`php -S`) serve real static files directly
// instead of routing every asset request through the front controller.
if (PHP_SAPI === 'cli-server') {
    $requestPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $assetPath = __DIR__ . $requestPath;
    if (!str_ends_with($requestPath, '.php') && is_file($assetPath)) {
        return false;
    }
}

date_default_timezone_set('Asia/Kolkata');
ini_set('display_errors', '1');
error_reporting(E_ALL);

// Per-project session cookie name. Cookies are NOT scoped by port, so every
// project in this suite left on the default `PHPSESSID` shares one cookie on
// localhost -- logging into another project on :8015 would silently overwrite
// this app's session (and vice versa). A distinct name keeps each project's
// login independent when several are open in the same browser at once.
session_name('reel_voicememo_session');
session_start([
    'cookie_httponly' => true,
    'cookie_samesite' => 'Lax',
]);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/Database.php';
require_once __DIR__ . '/../core/Auth.php';
require_once __DIR__ . '/../core/Csrf.php';
require_once __DIR__ . '/../core/Validator.php';
require_once __DIR__ . '/../core/Upload.php';
require_once __DIR__ . '/../core/Summarizer.php';
require_once __DIR__ . '/../core/Router.php';

require_once __DIR__ . '/../app/models/User.php';
require_once __DIR__ . '/../app/models/Folder.php';
require_once __DIR__ . '/../app/models/Tag.php';
require_once __DIR__ . '/../app/models/Memo.php';

require_once __DIR__ . '/../app/controllers/AuthController.php';
require_once __DIR__ . '/../app/controllers/UserController.php';
require_once __DIR__ . '/../app/controllers/AdminController.php';

$router = new Router();

// ---- Auth ----
$router->get('login', fn() => (new AuthController())->showLogin());
$router->post('login', fn() => (new AuthController())->login());
$router->post('logout', fn() => (new AuthController())->logout());

// ---- User ----
$router->get('user/dashboard', fn() => (new UserController())->dashboard());
$router->get('user/memos', fn() => (new UserController())->memos());
$router->get('user/memos/new', fn() => (new UserController())->newMemo());
$router->post('user/memos/store', fn() => (new UserController())->store());
$router->get('user/memos/view', fn() => (new UserController())->viewMemo());
$router->get('user/memos/audio', fn() => (new UserController())->audio());
$router->get('user/memos/edit', fn() => (new UserController())->editMemo());
$router->post('user/memos/update', fn() => (new UserController())->update());
$router->post('user/memos/delete', fn() => (new UserController())->delete());
$router->post('user/memos/star', fn() => (new UserController())->star());
$router->post('user/memos/summarize-preview', fn() => (new UserController())->summarizePreview());
$router->get('user/folders', fn() => (new UserController())->folders());
$router->post('user/folders/store', fn() => (new UserController())->folderStore());
$router->post('user/folders/delete', fn() => (new UserController())->folderDelete());

// ---- Super Admin ----
$router->get('admin/dashboard', fn() => (new AdminController())->dashboard());
$router->get('admin/users', fn() => (new AdminController())->users());
$router->post('admin/users/status', fn() => (new AdminController())->userStatus());
$router->get('admin/users/overview', fn() => (new AdminController())->userOverview());

$route = $_GET['route'] ?? '';

if ($route === '') {
    if (Auth::check()) {
        redirect(Auth::dashboardRoute(Auth::role()));
    }
    redirect('login');
}

$router->dispatch($_SERVER['REQUEST_METHOD'], $route);
