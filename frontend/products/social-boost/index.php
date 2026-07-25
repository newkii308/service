<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$productType = 'social';
$productHeading = 'Social Boost';
$productLead = 'บริการผู้ติดตาม ไลก์ วิว และเอนเกจเมนต์ คิดราคาต่อ 1,000 หน่วย';
$pageTitle = $productHeading;
$pageDescription = $productLead;
require dirname(__DIR__, 2) . '/includes/product-list.php';
