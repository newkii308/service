<?php
declare(strict_types=1);
require dirname(__DIR__, 3) . '/frontend/includes/api-action.php';
run_api_form_action('admin/product/toggle', 'backend/products/streaming-accounts/');
