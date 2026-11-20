<?php
require('../connection.php');

function addColumnIfNotExists($conn, $table, $column, $definition, $afterColumn)
{
    $checkSql = "SHOW COLUMNS FROM `$table` LIKE '$column'";
    $result = $conn->query($checkSql);
    if ($result->num_rows == 0) {
        $alterSql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition AFTER `$afterColumn`";
        if ($conn->query($alterSql)) {
            echo "Column '$column' added successfully.\n";
            return true;
        } else {
            echo "Error adding column '$column': " . $conn->error . "\n";
            return false;
        }
    } else {
        echo "Column '$column' already exists.\n";
        return true;
    }
}

// Add SecondaryHardeningdays
addColumnIfNotExists($krfconn, 'krf_growthstages', 'SecondaryHardeningdays', 'VARCHAR(50) DEFAULT NULL', 'ClipRemovalRemark');

// Add SecondaryHardeningRemark
addColumnIfNotExists($krfconn, 'krf_growthstages', 'SecondaryHardeningRemark', 'TEXT DEFAULT NULL', 'SecondaryHardeningdays');

echo "Migration Check Completed.\n";
?>