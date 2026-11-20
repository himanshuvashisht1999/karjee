<?php
$_REQUEST['action'] = 'get_orders';
$_REQUEST['status'] = 'readytoload';

ob_start();
require_once __DIR__ . '/model/readytoload_model.php';
$output = ob_get_clean();

$data = json_decode($output, true);
foreach ($data['data'] as $ord) {
    echo "PSO: " . $ord['psono'] . " | " . $ord['clientname'] . " | Inv: " . $ord['invoiceno'] . " | Stock: " . $ord['stockorder_no'] . PHP_EOL;
}
