<?php
$host = 'mail.scappshop.com';
$port = 993;
$email = 'mail1973f1c@scappshop.com';
$password = 'TLnvhZvWqDo9%AFm';

$mailbox = '{' . $host . ':' . $port . '/imap/ssl/novalidate-cert}INBOX';
$start = microtime(true);
$imap = @imap_open($mailbox, $email, $password, 0, 1);
$time = round(microtime(true) - $start, 2);

if ($imap === false) {
    echo "เชื่อมต่อไม่สำเร็จ (ใช้เวลา {$time}s): " . imap_last_error();
} else {
    echo "เชื่อมต่อสำเร็จ! (ใช้เวลา {$time}s) จำนวนอีเมล: " . imap_num_msg($imap);
    imap_close($imap);
}
