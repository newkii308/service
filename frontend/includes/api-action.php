<?php
declare(strict_types=1);

require_once __DIR__ . '/bootstrap.php';

function run_api_form_action(string $route, string $redirectPath): void
{
    if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'POST') {
        http_response_code(405);
        exit('Method Not Allowed');
    }

    $submitted = (string)($_POST['_csrf'] ?? '');
    if ($submitted === '' || csrf_token() === '' || !hash_equals(csrf_token(), $submitted)) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'คำขอหมดอายุ กรุณาลองใหม่'];
        legacy_redirect($redirectPath);
    }

    $_SERVER['HTTP_X_CSRF_TOKEN'] = $submitted;
    $_GET['r'] = $route;
    $GLOBALS['LEGACY_FORM_REDIRECT'] = app_url($redirectPath);
    require dirname(__DIR__, 2) . '/backend/api/index.php';
    exit;
}
