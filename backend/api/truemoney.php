<?php
/**
 * truemoney.php — บริการแลกรับซองอั่งเปา TrueMoney
 *
 * แนวคิด: เงินจากซองจะถูกโอนเข้า "เบอร์วอลเล็ตของเจ้าของเว็บ"
 * (ตั้งค่าในหลังบ้าน settings.truemoney_phone) จากนั้นระบบจะบวกยอด
 * ให้สมาชิกในเว็บไซต์อัตโนมัติ
 *
 * ใช้ file_get_contents (stream context) เพื่อความเข้ากันได้กับ shared hosting
 */

/**
 * ดึงรหัส voucher hash จากลิงก์ซองที่ลูกค้าวาง
 * รองรับทั้งลิงก์เต็มและรหัสล้วน
 */
function tmExtractHash(string $input): ?string {
    $input = trim($input);
    if ($input === '') return null;

    // รูปแบบ ?v=XXXXXXXX
    if (preg_match('/[?&]v=([0-9A-Za-z]+)/', $input, $m)) {
        return $m[1];
    }
    // รูปแบบ /campaign/?v= หรือ path ต่อท้าย
    if (preg_match('#gift\.truemoney\.com/campaign/\?v=([0-9A-Za-z]+)#', $input, $m)) {
        return $m[1];
    }
    // รหัสล้วน (ตัวอักษร/ตัวเลขล้วน ยาวพอสมควร)
    if (preg_match('/^[0-9A-Za-z]{16,}$/', $input)) {
        return $input;
    }
    // เผื่อกรณีมีลิงก์ปนข้อความ — คว้ากลุ่มยาวสุด
    if (preg_match('/([0-9A-Za-z]{16,})/', $input, $m)) {
        return $m[1];
    }
    return null;
}

/**
 * แลกซอง — คืน array มาตรฐาน:
 *   ['ok'=>bool, 'amount'=>float, 'hash'=>string|null, 'message'=>string]
 */
function tmRedeem(string $voucherInput, string $ownerPhone): array {
    $hash = tmExtractHash($voucherInput);
    if ($hash === null) {
        return ['ok' => false, 'amount' => 0, 'hash' => null,
                'message' => 'ลิงก์ซองอั่งเปาไม่ถูกต้อง กรุณาตรวจสอบอีกครั้ง'];
    }
    $ownerPhone = preg_replace('/\D/', '', $ownerPhone);
    if (strlen($ownerPhone) < 9) {
        return ['ok' => false, 'amount' => 0, 'hash' => $hash,
                'message' => 'ระบบยังไม่ได้ตั้งค่าเบอร์วอลเล็ตผู้รับ กรุณาติดต่อผู้ดูแล'];
    }

    $url = "https://gift.truemoney.com/campaign/vouchers/{$hash}/redeem";
    $payload = json_encode([
        'mobile'       => $ownerPhone,
        'voucher_hash' => $hash,
    ], JSON_UNESCAPED_UNICODE);

    $context = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n" .
                               "User-Agent: Mozilla/5.0\r\n",
            'content'       => $payload,
            'timeout'       => 20,
            'ignore_errors' => true, // อ่าน body ได้แม้ status ไม่ใช่ 200
        ],
        'ssl' => [
            'verify_peer'      => true,
            'verify_peer_name' => true,
        ],
    ]);

    $raw = @file_get_contents($url, false, $context);
    if ($raw === false) {
        return ['ok' => false, 'amount' => 0, 'hash' => $hash,
                'message' => 'เชื่อมต่อระบบทรูมันนี่ไม่สำเร็จ กรุณาลองใหม่อีกครั้ง'];
    }

    $res = json_decode($raw, true);
    if (!is_array($res) || !isset($res['status']['code'])) {
        return ['ok' => false, 'amount' => 0, 'hash' => $hash,
                'message' => 'ระบบทรูมันนี่ตอบกลับผิดปกติ กรุณาลองใหม่'];
    }

    $code = $res['status']['code'];
    if ($code === 'SUCCESS') {
        $amount =
            $res['data']['my_ticket']['amount_baht']
            ?? $res['data']['voucher']['redeemed_amount_baht']
            ?? $res['data']['voucher']['amount_baht']
            ?? 0;
        $amount = (float)$amount;
        if ($amount <= 0) {
            return ['ok' => false, 'amount' => 0, 'hash' => $hash,
                    'message' => 'ไม่พบยอดเงินในซอง'];
        }
        return ['ok' => true, 'amount' => $amount, 'hash' => $hash,
                'message' => 'เติมเงินสำเร็จ'];
    }

    return ['ok' => false, 'amount' => 0, 'hash' => $hash,
            'message' => tmErrorMessage($code)];
}

/** แปลงรหัสข้อผิดพลาดของทรูมันนี่เป็นข้อความภาษาไทยที่เข้าใจง่าย */
function tmErrorMessage(string $code): string {
    $map = [
        'VOUCHER_NOT_FOUND'      => 'ไม่พบซองอั่งเปานี้ อาจถูกใช้ไปแล้วหรือลิงก์ผิด',
        'VOUCHER_OUT_OF_STOCK'   => 'ซองนี้ถูกรับครบแล้ว',
        'VOUCHER_EXPIRED'        => 'ซองอั่งเปาหมดอายุแล้ว',
        'VOUCHER_USED'           => 'ซองอั่งเปานี้ถูกใช้ไปแล้ว',
        'TARGET_USER_REDEEMED'   => 'บัญชีนี้รับซองนี้ไปแล้ว',
        'CANNOT_GET_OWN_VOUCHER' => 'ไม่สามารถรับซองของตัวเองได้',
        'TARGET_USER_NOT_FOUND'  => 'ไม่พบบัญชีผู้รับ กรุณาตรวจสอบเบอร์วอลเล็ต',
        'INTERNAL_ERROR'         => 'ระบบทรูมันนี่ขัดข้องชั่วคราว กรุณาลองใหม่',
    ];
    return $map[$code] ?? ('รับซองไม่สำเร็จ (' . $code . ')');
}
