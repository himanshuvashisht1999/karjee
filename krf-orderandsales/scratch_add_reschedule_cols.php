<?php
require('c:/xampp/htdocs/krf_amanaccounting/connection.php');

function checkAndAddColumn($conn, $table, $column, $definition) {
    $res = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
    if ($res && $res->num_rows > 0) {
        echo "Column `$table`.`$column` already exists.\n";
    } else {
        $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
        if ($conn->query($sql)) {
            echo "Successfully added column `$table`.`$column`.\n";
        } else {
            echo "Error adding `$table`.`$column`: " . $conn->error . "\n";
        }
    }
}

checkAndAddColumn($krfconn, 'krf_sales_order_items', 'confirmdeliverydate', 'DATE NULL AFTER `deliverydate`');
checkAndAddColumn($krfconn, 'krf_sales_order_items', 'confirmdeliverydateremark', 'VARCHAR(405) NULL AFTER `confirmdeliverydate`');

checkAndAddColumn($krfconn, 'krf_production_stockorder', 'confirmdeliverydate', 'DATE NULL AFTER `expected_delivery_date`');
checkAndAddColumn($krfconn, 'krf_production_stockorder', 'confirmdeliverydateremark', 'VARCHAR(405) NULL AFTER `confirmdeliverydate`');
