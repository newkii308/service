<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$productType = 'code';
$productHeading = 'โค้ดและไอดีเกม';
$productLead = 'เลือกซื้อสินค้าในคลัง ส่งมอบอัตโนมัติทันทีหลังชำระ';
$pageTitle = $productHeading;
$pageDescription = $productLead;
require dirname(__DIR__, 2) . '/includes/product-list.php';
