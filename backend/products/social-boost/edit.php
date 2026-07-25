<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
require_admin_page();
$productType = 'social';
require dirname(__DIR__, 2) . '/includes/product-edit.php';
