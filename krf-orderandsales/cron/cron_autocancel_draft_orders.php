<?php
/**
 * Cron Job: Auto-Cancel Draft Provisional Orders Older Than 48 Hours
 * 
 * Usage:
 * - CLI: C:\xampp\php\php.exe c:\xampp\htdocs\krf_amanaccounting\krf-orderandsales\cron\cron_autocancel_draft_orders.php
 * - Web/Curl: http://localhost/krf_amanaccounting/krf-orderandsales/cron/cron_autocancel_draft_orders.php
 */

require_once(__DIR__ . '/../../connection.php');
require_once(__DIR__ . '/../functions/orders_functions.php');

$result = autoCancelExpiredDraftOrders($krfconn);

$msg = "[" . date('Y-m-d H:i:s') . "] Auto-Cancel Cron Completed. Total Draft Orders Cancelled: " . $result['cancelled_count'];
if (!empty($result['psono_list'])) {
    $msg .= " | PSONOs: " . implode(', ', array_slice($result['psono_list'], 0, 10));
    if (count($result['psono_list']) > 10) {
        $msg .= " and " . (count($result['psono_list']) - 10) . " more...";
    }
}

if (php_sapi_name() === 'cli') {
    echo $msg . PHP_EOL;
} else {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => $msg,
        'cancelled_count' => $result['cancelled_count'],
        'cancelled_psos' => $result['psono_list']
    ]);
}
