<?php
declare(strict_types=1);
require dirname(__DIR__, 3) . '/frontend/includes/api-action.php';
$productId = max(0, (int)($_POST['product_id'] ?? 0));
run_api_form_action('admin/codes/add', 'backend/products/game-codes/stock.php?id=' . $productId);
