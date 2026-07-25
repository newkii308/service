<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/includes/api-action.php';
run_api_form_action('wallet/topup', 'frontend/account/topup/');
