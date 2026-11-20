<?php
$mode = (string)($_POST['mode'] ?? '');
$orderkind = (string)($_POST['orderkind'] ?? '');
if (session_status() === PHP_SESSION_NONE) @session_start();
$hasConfirmedCsrf = !empty($_SESSION['confirmed_edit_csrf']) && hash_equals((string)$_SESSION['confirmed_edit_csrf'], (string)($_POST['csrf'] ?? ''));

if ($mode === 'address') {
    require __DIR__ . '/save_address.php';
} elseif ($orderkind === 'confirmed' && $hasConfirmedCsrf) {
    require __DIR__ . '/update_confirmed_order.php';
    exit;
} elseif ($mode === 'edit') {
    require __DIR__ . '/update_edit_order.php';
} elseif ($mode === 'add') {
    require __DIR__ . '/insert_order.php';
} else {
    header('Content-Type: application/json');
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Invalid order mode.']);
}

