<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/bootstrap.php';
$productType = 'otp';
$productHeading = 'OTP สำหรับแอป';
$productLead = 'บริการ OTP สำหรับ Netflix, Disney+, YouKu และแอปที่รองรับ';
$pageTitle = $productHeading;
$pageDescription = $productLead;
require dirname(__DIR__, 2) . '/includes/product-list.php';
