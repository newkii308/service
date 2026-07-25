<?php
// =============================================================
//  Reseller Supplier API Client Layer (gafiwshop.xyz)
// =============================================================

function supplier_request(string $endpoint, ?array $postData = null, ?string $apiKey = null): array {
    $ch = curl_init();
    $url = 'https://gafiwshop.xyz/api/' . $endpoint;
    
    $options = [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_SSL_VERIFYPEER => false,
    ];

    if ($postData !== null) {
        $options[CURLOPT_POST] = true;
        if ($apiKey !== null && !isset($postData['keyapi']) && !isset($postData['api_key'])) {
            if ($endpoint === 'netflix_otp') {
                $postData['api_key'] = $apiKey;
            } else {
                $postData['keyapi'] = $apiKey;
            }
        }
        $options[CURLOPT_POSTFIELDS] = http_build_query($postData);
    } else {
        if ($apiKey !== null) {
            $separator = strpos($url, '?') === false ? '?' : '&';
            $keyParam = ($endpoint === 'netflix_otp') ? 'api_key' : 'keyapi';
            $options[CURLOPT_URL] = $url . $separator . $keyParam . '=' . urlencode($apiKey);
        }
    }

    curl_setopt_array($ch, $options);
    $response = curl_exec($ch);
    // curl_close($ch); // Deprecated in PHP 8.0+ / E_DEPRECATED in PHP 8.5

    if ($response === false) {
        return ['ok' => false, 'status' => 'error', 'msg' => 'ไม่สามารถเชื่อมต่อ Supplier API ได้'];
    }

    $data = json_decode($response, true);
    if ($data === null) {
        return ['ok' => false, 'status' => 'error', 'msg' => 'ข้อมูลที่ได้รับจากคู่ค้าไม่ถูกต้อง (Invalid JSON): ' . $response];
    }

    return $data;
}

// 1. ดึงยอดคงเหลือ
function supplier_get_balance(string $apiKey): array {
    $res = supplier_request('api_money', [], $apiKey);
    $ok = (isset($res['ok']) && $res['ok'] === true) || (isset($res['status']) && $res['status'] === 'success');
    
    $balanceStr = $res['balance'] ?? $res['msg'] ?? '0.00';
    preg_match('/[0-9]+(\.[0-9]+)?/', $balanceStr, $matches);
    $balance = isset($matches[0]) ? (float)$matches[0] : 0.00;
    
    return [
        'ok' => $ok,
        'balance' => number_format($balance, 2, '.', ''),
        'owner' => $res['owner'] ?? 'ตัวแทนจำหน่าย'
    ];
}

// 2. ดึงรายการสินค้าทั้งหมด
function supplier_get_products(): array {
    return supplier_request('api_product');
}

// 3. สั่งซื้อสินค้า
function supplier_buy_product(string $apiKey, string $typeId, string $usernameBuy = ''): array {
    return supplier_request('api_buy', [
        'type_id' => $typeId,
        'username_buy' => $usernameBuy
    ], $apiKey);
}

// 4. ดึงประวัติสั่งซื้อ
function supplier_get_history(string $apiKey, string $usernameBuy = '', string $limit = '1000'): array {
    $params = [];
    if ($usernameBuy !== '') $params['username_buy'] = $usernameBuy;
    if ($limit !== '') $params['limit'] = $limit;
    return supplier_request('api_history?' . http_build_query($params), null, $apiKey);
}

// 5. แจ้งเคลม
function supplier_submit_claim(string $apiKey, string $orderId, string $reason): array {
    return supplier_request('api_claim', [
        'order_id' => $orderId,
        'reason' => $reason
    ], $apiKey);
}

// 6. ตรวจสอบสถานะเคลม
function supplier_check_claim(string $apiKey, string $orderId = ''): array {
    return supplier_request('check_claim_status', [
        'order_id' => $orderId
    ], $apiKey);
}

// 7. ดึงประวัติการอัปเดตสต็อกคู่ค้า
function supplier_get_stock_updates(int $limit = 50, int $offset = 0): array {
    return supplier_request('update_stock?limit=' . $limit . '&offset=' . $offset);
}

// 8. ดึงรหัส OTP Netflix
function supplier_get_netflix_otp(string $apiKey, string $orderId, string $type): array {
    return supplier_request('netflix_otp', [
        'order_id' => $orderId,
        'type' => $type
    ], $apiKey);
}

// 9. ดึง OTP YouKu
function supplier_get_youku_otp(string $email): array {
    return supplier_request('otp_youku?email=' . urlencode($email));
}

// 10. ขอ OTP Disney+
function supplier_get_disney_otp(string $apiKey, string $phone): array {
    return supplier_request('otp_disney?phone=' . urlencode($phone), null, $apiKey);
}

// =============================================================
//  SMS OTP Client Methods (otp.md)
// =============================================================

// 11. ดึงรายการสินค้า OTP ทั้งหมด
function supplier_get_sms_products(): array {
    return supplier_request('otp_product');
}

// 12. ซื้อเบอร์รับ OTP
function supplier_buy_sms_number(string $apiKey, string $product, string $location): array {
    return supplier_request('otp_buy', [
        'keyapi' => $apiKey,
        'product' => $product,
        'location' => $location
    ], $apiKey);
}

// 13. ตรวจสอบสถานะออเดอร์ SMS OTP
function supplier_check_sms_status(string $orderId): array {
    return supplier_request('otp_his', [
        'order' => $orderId
    ]);
}

// 14. ยกเลิกเบอร์
function supplier_cancel_sms_number(string $apiKey, string $orderId): array {
    return supplier_request('otp_cancel', [
        'keyapi' => $apiKey,
        'order' => $orderId
    ], $apiKey);
}

// 15. ซื้อเบอร์เดิม (ขอ OTP ใหม่)
function supplier_buyagain_sms_number(string $apiKey, string $orderId): array {
    return supplier_request('otp_buyagain', [
        'keyapi' => $apiKey,
        'order' => $orderId
    ], $apiKey);
}

// =============================================================
//  MeeLike SMM API Client Methods
// =============================================================

function meelike_request(array $params): array {
    $ch = curl_init('https://api.meelike-th.com/api/v2');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query($params),
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_SSL_VERIFYPEER => false,
    ]);
    $response = curl_exec($ch);
    if ($response === false) {
        return ['ok' => false, 'error' => 'ไม่สามารถเชื่อมต่อ MeeLike API ได้'];
    }
    $data = json_decode($response, true);
    if ($data === null) {
        return ['ok' => false, 'error' => 'ข้อมูลจาก MeeLike API ไม่ถูกต้อง: ' . $response];
    }
    return $data;
}

function meelike_get_balance(string $apiKey): array {
    return meelike_request(['key' => $apiKey, 'action' => 'balance']);
}

function meelike_get_services(string $apiKey): array {
    return meelike_request(['key' => $apiKey, 'action' => 'services']);
}

function meelike_add_order(string $apiKey, string $service, string $link, int $quantity): array {
    return meelike_request([
        'key' => $apiKey,
        'action' => 'add',
        'service' => $service,
        'link' => $link,
        'quantity' => (string)$quantity,
    ]);
}

function meelike_order_status(string $apiKey, string $orderId): array {
    return meelike_request([
        'key' => $apiKey,
        'action' => 'status',
        'order' => $orderId,
    ]);
}
