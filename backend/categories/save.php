<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/frontend/includes/api-action.php';
run_api_form_action('admin/category/save', 'backend/categories/');
