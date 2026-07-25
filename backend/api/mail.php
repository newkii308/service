<?php
/**
 * mail.php — โมดูล "เช่าอีเมล / กล่องเมล" (Temp/Rent Mail)
 * รวมเข้ากับระบบร้านหลักแล้ว: ใช้ session/wallet/CSRF/tenant เดียวกับ core.php ทั้งหมด
 * รองรับหลายโฮสเมล (Multi-host): DirectAdmin และ cPanel ต่อกล่องเมลได้พร้อมกันหลายเซิร์ฟเวอร์/โดเมน
 */

/* =============================================================
 *  Crypto — เข้ารหัสรหัสผ่านกล่องเมล / รหัสผ่าน API ของโฮส
 * ============================================================= */
function mail_crypto_key(): string {
    return hash('sha256', MAIL_ENCRYPT_KEY, true);
}
function mail_encrypt(string $plain): string {
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plain, 'aes-256-cbc', mail_crypto_key(), OPENSSL_RAW_DATA, $iv);
    return base64_encode($iv . $cipher);
}
function mail_decrypt(string $encoded): string {
    $raw = base64_decode($encoded);
    if ($raw === false || strlen($raw) < 17) return '';
    $iv = substr($raw, 0, 16);
    $cipher = substr($raw, 16);
    $plain = openssl_decrypt($cipher, 'aes-256-cbc', mail_crypto_key(), OPENSSL_RAW_DATA, $iv);
    return $plain === false ? '' : $plain;
}
function mail_random_password(int $length = 16): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#%';
    $out = '';
    for ($i = 0; $i < $length; $i++) $out .= $chars[random_int(0, strlen($chars) - 1)];
    return $out;
}

/* =============================================================
 *  Multi-host client — สร้าง/ลบกล่องเมลจริงบนโฮสของแต่ละ mail_hosts row
 *  รองรับ driver: 'directadmin' และ 'cpanel'
 * ============================================================= */
function mail_host_curl(string $url, array $headers, ?array $postFields = null): array {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    if ($postFields !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
    }
    $raw = curl_exec($ch);
    if ($raw === false) {
        $err = curl_error($ch);
        curl_close($ch);
        throw new RuntimeException('เชื่อมต่อโฮสเมลไม่สำเร็จ: ' . $err);
    }
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $httpCode, 'body' => $raw];
}

/** สร้างกล่องอีเมลจริงบนโฮสที่กำหนด (ตาม driver ของ mail_hosts row) */
function mail_host_create_email(array $host, string $localPart, string $domain, string $password, int $quotaMb): void {
    $secret = mail_decrypt($host['api_secret']);

    if ($host['driver'] === 'cpanel') {
        // cPanel UAPI: Email::add_pop
        $url = rtrim($host['api_url'], '/') . '/execute/Email/add_pop';
        $headers = [
            'Authorization: cpanel ' . $host['api_username'] . ':' . $secret,
        ];
        $res = mail_host_curl($url, $headers, [
            'email'          => $localPart,
            'domain'         => $domain,
            'password'       => $password,
            'quota'          => $quotaMb,
        ]);
        $json = json_decode((string)$res['body'], true);
        $ok = $res['code'] < 400 && (($json['result']['status'] ?? $json['status'] ?? 0) == 1 || ($json['status'] ?? 0) == 1);
        if (!$ok) {
            $msg = $json['result']['errors'][0] ?? $json['errors'][0] ?? $res['body'];
            throw new RuntimeException('cPanel สร้างอีเมลไม่สำเร็จ: ' . $msg);
        }
        return;
    }

    // ค่าเริ่มต้น: DirectAdmin (CMD_API_POP)
    $url = rtrim($host['api_url'], '/') . '/CMD_API_POP';
    $auth = base64_encode($host['api_username'] . ':' . $secret);
    $headers = ['Authorization: Basic ' . $auth];
    $res = mail_host_curl($url, $headers, [
        'action'  => 'create',
        'domain'  => $domain,
        'user'    => $localPart,
        'passwd'  => $password,
        'passwd2' => $password,
        'quota'   => $quotaMb,
        'limit'   => 100,
        'json'    => 'yes',
    ]);

    $body = trim((string)$res['body']);
    $result = [];
    $json = json_decode($body, true);
    if (is_array($json)) {
        // DirectAdmin เวอร์ชันใหม่ (Evolution) ตอบกลับเป็น JSON
        $result = [
            'error'   => isset($json['error']) ? $json['error'] : (isset($json['success']) ? 0 : null),
            'text'    => $json['text'] ?? $json['message'] ?? $json['result'] ?? null,
            'details' => $json['details'] ?? null,
        ];
    } else {
        // DirectAdmin เวอร์ชันเดิม ตอบกลับเป็น query string (error=1&text=...)
        parse_str($body, $parsed);
        $result = [
            'error'   => $parsed['error'] ?? null,
            'text'    => isset($parsed['text']) ? urldecode($parsed['text']) : null,
            'details' => isset($parsed['details']) ? urldecode($parsed['details']) : null,
        ];
    }

    $isError = $res['code'] >= 400 || (isset($result['error']) && (int)$result['error'] === 1);
    if ($isError) {
        $msg = $result['text'] ?? $result['details'] ?? null;
        // แนบข้อความดิบจาก DirectAdmin ไปด้วยเสมอ เพื่อให้เห็นสาเหตุจริงตอน debug
        $full = trim(($msg ? $msg . ' — ' : '') . 'HTTP ' . $res['code'] . ': ' . mb_substr($body, 0, 300));
        throw new RuntimeException('DirectAdmin สร้างอีเมลไม่สำเร็จ: ' . $full);
    }
}

/** ลบกล่องอีเมลจริงบนโฮส (ไม่ถือเป็น error ร้ายแรงถ้าลบไม่สำเร็จ แค่ log ไว้) */
function mail_host_delete_email(array $host, string $localPart, string $domain): void {
    try {
        $secret = mail_decrypt($host['api_secret']);
        if ($host['driver'] === 'cpanel') {
            $url = rtrim($host['api_url'], '/') . '/execute/Email/delete_pop';
            $headers = ['Authorization: cpanel ' . $host['api_username'] . ':' . $secret];
            mail_host_curl($url, $headers, ['email' => $localPart . '@' . $domain]);
        } else {
            $url = rtrim($host['api_url'], '/') . '/CMD_API_POP';
            $auth = base64_encode($host['api_username'] . ':' . $secret);
            $headers = ['Authorization: Basic ' . $auth];
            mail_host_curl($url, $headers, [
                'action' => 'delete',
                'domain' => $domain,
                'user'   => $localPart . '@' . $domain,
            ]);
        }
    } catch (Throwable $e) {
        error_log('[Mail] ลบอีเมลบนโฮส (' . ($host['name'] ?? '') . ') ไม่สำเร็จ (' . $localPart . '@' . $domain . '): ' . $e->getMessage());
    }
}

/** ทดสอบการเชื่อมต่อโฮส — ใช้ตอนแอดมินกดปุ่ม "ทดสอบการเชื่อมต่อ" ในหลังบ้าน */
function mail_host_test(array $host): array {
    $secret = mail_decrypt($host['api_secret']);
    try {
        if ($host['driver'] === 'cpanel') {
            $url = rtrim($host['api_url'], '/') . '/execute/Email/list_pops';
            $headers = ['Authorization: cpanel ' . $host['api_username'] . ':' . $secret];
            $res = mail_host_curl($url, $headers);
        } else {
            $url = rtrim($host['api_url'], '/') . '/CMD_API_SHOW_DOMAINS';
            $auth = base64_encode($host['api_username'] . ':' . $secret);
            $res = mail_host_curl($url, ['Authorization: Basic ' . $auth]);
        }
        if ($res['code'] >= 400) return ['ok' => false, 'message' => 'HTTP ' . $res['code']];
        return ['ok' => true, 'message' => 'เชื่อมต่อโฮสสำเร็จ'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

/* =============================================================
 *  IMAP — ดึงอีเมลจาก mail server ของโฮสที่กล่องเมลนั้นสังกัดอยู่
 * ============================================================= */
function mail_imap_connect(array $host, string $email, string $password) {
    $mailbox = '{' . $host['imap_host'] . ':' . $host['imap_port'] . $host['imap_flags'] . '}INBOX';
    $imap = @imap_open($mailbox, $email, $password, 0, 1);
    if ($imap === false) {
        throw new RuntimeException('เชื่อมต่อกล่องเมลไม่สำเร็จ: ' . imap_last_error());
    }
    return $imap;
}

function mail_decode_mime_str(string $str): string {
    $out = @imap_mime_header_decode($str);
    if (!$out) return $str;
    $result = '';
    foreach ($out as $part) $result .= $part->text;
    return $result;
}

function mail_parse_from(string $fromRaw): array {
    $decoded = mail_decode_mime_str($fromRaw);
    if (preg_match('/(.*)<(.+)>/', $decoded, $m)) return [trim($m[1], " \t\""), trim($m[2])];
    return ['', trim($decoded)];
}

function mail_decode_body(string $body, int $encoding): string {
    switch ($encoding) {
        case 3: return base64_decode($body);
        case 4: return quoted_printable_decode($body);
        default: return $body;
    }
}

function mail_walk_parts($imap, int $msgno, array $parts, string $prefix, string &$html, string &$text): void {
    foreach ($parts as $i => $part) {
        $partNum = $prefix === '' ? (string)($i + 1) : $prefix . '.' . ($i + 1);
        if (!empty($part->parts) && (int)$part->type === 1) {
            mail_walk_parts($imap, $msgno, $part->parts, $partNum, $html, $text);
            continue;
        }
        $subtype = strtoupper($part->subtype ?? '');
        if ($subtype === 'HTML' || $subtype === 'PLAIN') {
            $raw = imap_fetchbody($imap, $msgno, $partNum);
            $decoded = mail_decode_body($raw, $part->encoding ?? 0);
            if ($subtype === 'HTML') $html .= $decoded; else $text .= $decoded;
        }
    }
}

function mail_get_body($imap, int $msgno): array {
    $structure = @imap_fetchstructure($imap, $msgno);
    $html = ''; $text = '';
    if (!$structure) { $text = imap_body($imap, $msgno); return [$html, $text]; }
    if (empty($structure->parts)) {
        $body = mail_decode_body(imap_body($imap, $msgno), $structure->encoding ?? 0);
        if (($structure->subtype ?? '') === 'HTML') $html = $body; else $text = $body;
    } else {
        mail_walk_parts($imap, $msgno, $structure->parts, '', $html, $text);
    }
    return [$html, $text];
}

/** รูปแบบข้อความที่ถือว่าเป็นรหัส OTP (เรียงจากเจาะจงมากไปน้อย) */
function mail_extract_otp(string $content): ?string {
    $patterns = [
        '/(?:OTP|otp|รหัส|verification code|passcode)[^0-9]{0,15}(\d{4,8})/u',
        '/\b(\d{6})\b/',
        '/\b(\d{4})\b/',
    ];
    foreach ($patterns as $pattern) {
        if (preg_match($pattern, $content, $m)) return $m[1];
    }
    return null;
}

/** ดึงอีเมลใหม่จาก server แล้วบันทึกเข้า mail_messages (ข้ามฉบับที่เคยมีแล้วด้วย UID) */
function mail_sync_inbox(array $mailbox, array $host): int {
    $password = mail_decrypt($mailbox['secret']);
    $imap = mail_imap_connect($host, $mailbox['email'], $password);
    try {
        $total = imap_num_msg($imap);
        if ($total === 0) return 0;
        $overview = imap_fetch_overview($imap, "1:$total", 0);
        $newCount = 0;
        $pdo = db();
        $checkStmt = $pdo->prepare('SELECT id FROM mail_messages WHERE mailbox_id = ? AND uid = ? LIMIT 1');
        $insertStmt = $pdo->prepare(
            'INSERT INTO mail_messages (tenant_id, mailbox_id, uid, from_addr, from_name, subject, body_html, body_text, otp_code, received_at)
             VALUES (?,?,?,?,?,?,?,?,?,?)'
        );
        foreach ($overview as $item) {
            $uid = (string)($item->uid ?? $item->msgno);
            $checkStmt->execute([$mailbox['id'], $uid]);
            if ($checkStmt->fetch()) continue;

            [$fromName, $fromAddr] = mail_parse_from($item->from ?? '');
            $subject = mail_decode_mime_str($item->subject ?? '(ไม่มีหัวข้อ)');
            [$html, $text] = mail_get_body($imap, (int)($item->msgno ?? 0));
            $otp = mail_extract_otp($subject . ' ' . $text);
            $receivedAt = isset($item->udate) ? date('Y-m-d H:i:s', $item->udate) : date('Y-m-d H:i:s');

            $insertStmt->execute([
                tenantId(), $mailbox['id'], $uid, $fromAddr, $fromName, $subject, $html, $text, $otp, $receivedAt,
            ]);
            $newCount++;
        }
        $pdo->prepare('UPDATE mail_boxes SET last_synced_at = NOW() WHERE id = ?')->execute([$mailbox['id']]);
        return $newCount;
    } finally {
        imap_close($imap);
    }
}

function mail_delete_message_on_server(array $mailbox, array $host, string $uid): void {
    $password = mail_decrypt($mailbox['secret']);
    $imap = mail_imap_connect($host, $mailbox['email'], $password);
    try {
        $msgno = imap_msgno($imap, (int)$uid);
        if ($msgno > 0) { imap_delete($imap, (string)$msgno); imap_expunge($imap); }
    } finally {
        imap_close($imap);
    }
}

/** ดึง host row ของกล่องเมล (ตรวจสิทธิ์ tenant ไปในตัว) */
function mail_get_host_for_mailbox(array $mailbox): array {
    $stmt = db()->prepare('SELECT * FROM mail_hosts WHERE id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$mailbox['host_id'], tenantId()]);
    $host = $stmt->fetch();
    if (!$host) throw new RuntimeException('ไม่พบข้อมูลโฮสเมลของกล่องนี้ (อาจถูกลบไปแล้ว)');
    return $host;
}
