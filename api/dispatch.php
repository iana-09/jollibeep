<?php
$routes = [
    'admin_logout.php', 'check_username.php', 'index.php', 'login.php', 'logout.php',
    'page1_registration.php', 'page2_registration_result.php', 'page4_login_result.php',
    'page5_jollibee_orderform.php', 'page6_order_result.php', 'page7_stores.php',
    'page8_profile.php', 'page9_home.php', 'page10_about.php', 'page11_manager_orders.php',
    'page12_admin_login.php', 'page13_controller_setup.php',
];
$route = $_GET['__jolli_route'] ?? '';
unset($_GET['__jolli_route']);

if (!is_string($route) || !in_array($route, $routes, true)) {
    http_response_code(404);
    exit('Not found');
}

$sessionRoutes = [
    'admin_logout.php', 'index.php', 'login.php', 'logout.php', 'page10_about.php',
    'page11_manager_orders.php', 'page12_admin_login.php', 'page13_controller_setup.php',
    'page2_registration_result.php', 'page4_login_result.php', 'page5_jollibee_orderform.php',
    'page6_order_result.php', 'page7_stores.php', 'page8_profile.php', 'page9_home.php',
];

$projectRoot = dirname(__DIR__);
chdir($projectRoot);
if (in_array($route, $sessionRoutes, true)) {
    require_once $projectRoot . DIRECTORY_SEPARATOR . 'vercel_session_bootstrap.php';
}
require $projectRoot . DIRECTORY_SEPARATOR . $route;