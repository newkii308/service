<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$productType = 'refill';
$productHeading = 'บริการเติมเกม';
$productLead = 'เลือกแพ็กเกจ กรอก UID และเซิร์ฟเวอร์ให้ถูกต้อง';
$pageTitle = $productHeading;
$pageDescription = $productLead;
require dirname(__DIR__, 2) . '/includes/product-list.php';
