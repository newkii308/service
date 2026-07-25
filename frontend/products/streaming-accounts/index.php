<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$productType = 'streaming';
$productHeading = 'บัญชี Streaming';
$productLead = 'แพ็กเกจพรีเมียม ส่งข้อมูลบัญชีผ่านระบบอัตโนมัติ';
$pageTitle = $productHeading;
$pageDescription = $productLead;
require dirname(__DIR__, 2) . '/includes/product-list.php';
