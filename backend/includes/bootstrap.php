<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/frontend/includes/bootstrap.php';

function backend_product_config(string $type): array
{
    return [
        'code' => ['folder' => 'game-codes', 'title' => 'โค้ดและไอดีเกม'],
        'refill' => ['folder' => 'game-refills', 'title' => 'เติมเกม'],
        'streaming' => ['folder' => 'streaming-accounts', 'title' => 'Streaming'],
        'social' => ['folder' => 'social-boost', 'title' => 'Social Boost'],
        'otp' => ['folder' => 'app-otp', 'title' => 'OTP แอป'],
    ][$type] ?? ['folder' => 'game-codes', 'title' => 'สินค้า'];
}
