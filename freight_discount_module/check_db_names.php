<?php
require_once __DIR__ . '/../connection.php';

echo "1. Checking krf_accounting database:\n";
$resAccounting = $conn->query("SHOW TABLES LIKE 'krf_freight_discount_plan'");
echo "  Table exists in krf_accounting: " . ($resAccounting->num_rows > 0 ? "YES" : "NO") . "\n";
if ($resAccounting->num_rows > 0) {
    $r = $conn->query("SELECT COUNT(*) as c FROM krf_freight_discount_plan")->fetch_assoc();
    echo "  Rows in krf_accounting: " . $r['c'] . "\n";
}

echo "\n2. Checking karjeefarm database:\n";
$resKrf = $krfconn->query("SHOW TABLES LIKE 'krf_freight_discount_plan'");
echo "  Table exists in karjeefarm: " . ($resKrf->num_rows > 0 ? "YES" : "NO") . "\n";
if ($resKrf->num_rows > 0) {
    $rKrf = $krfconn->query("SELECT COUNT(*) as c FROM krf_freight_discount_plan")->fetch_assoc();
    echo "  Rows in karjeefarm: " . $rKrf['c'] . "\n";
}
