<?php
declare(strict_types=1);
require dirname(__DIR__, 3) . '/frontend/includes/api-action.php';
$_POST['type'] = 'code';
run_api_form_action('admin/product/save', 'backend/products/game-codes/');
