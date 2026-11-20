<?php

// Connect to database
require('connection.php');

$employeeIds = [
    '21280',
    '21450',
    '21451',
    '21448',
    '21449',
    '20694',
    '20695',
    '21453',
    '21092',
    '21452'
];

$tables = [
    'hrm_LabourPaymentMaster'       => 'EmployeeID',
    'hrm_LabourOtherPayment'        => 'EmployeeID',
    'hrm_labourdocuments'           => 'EmployeeID',
    'hrm_LabourCalculatedSalary'    => 'EmployeeID',
    'hrm_labourcalculateattendance' => 'EmployeeID',
    'hrm_LabourMaster'              => 'EmployeeID',
    'hrm_labourattendance'          => 'labourid',
    'hrm_labour_references'         => 'employee_id',
];

$prefix     = 'KFB-';
$locationid = 'LOC691d69de86d38';

try {

    /*
     * Validate employee IDs
     */
    if (empty($employeeIds)) {
        throw new Exception('Employee ID list cannot be empty.');
    }

    /*
     * Start transaction
     */
    $hrmconn->begin_transaction();

    $updateReport = [];
    $totalUpdated = 0;

    /*
     * Create placeholders:
     * ?, ?, ?, ...
     */
    $placeholders = implode(
        ',',
        array_fill(0, count($employeeIds), '?')
    );

    foreach ($tables as $tableName => $columnName) {

        /*
         * -------------------------------------------------
         * Check if locationid column exists in current table
         * -------------------------------------------------
         */
        $checkSql = "
            SELECT COUNT(*) AS total
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME = 'locationid'
        ";

        $checkStmt = $hrmconn->prepare($checkSql);

        if (!$checkStmt) {
            throw new Exception(
                "Column check prepare failed for {$tableName}: " .
                $hrmconn->error
            );
        }

        $checkStmt->bind_param('s', $tableName);

        if (!$checkStmt->execute()) {
            throw new Exception(
                "Column check failed for {$tableName}: " .
                $checkStmt->error
            );
        }

        $checkStmt->bind_result($locationColumnCount);
        $checkStmt->fetch();
        $checkStmt->close();

        $hasLocationId = ((int)$locationColumnCount > 0);


        /*
         * -------------------------------------------------
         * Build SQL based on locationid availability
         * -------------------------------------------------
         */
        if ($hasLocationId) {

            $sql = "
                UPDATE `$tableName`
                SET
                    `locationid` = ?,
                    `$columnName` = CONCAT(?, `$columnName`)
                WHERE `$columnName` IN ($placeholders)
                  AND `$columnName` NOT LIKE ?
            ";

            /*
             * Parameters:
             * 1. location ID
             * 2. Prefix
             * 3. Employee IDs
             * 4. Prefix pattern
             */
            $params = array_merge(
                [$locationid, $prefix],
                $employeeIds,
                [$prefix . '%']
            );

        } else {

            $sql = "
                UPDATE `$tableName`
                SET
                    `$columnName` = CONCAT(?, `$columnName`)
                WHERE `$columnName` IN ($placeholders)
                  AND `$columnName` NOT LIKE ?
            ";

            /*
             * Parameters:
             * 1. Prefix
             * 2. Employee IDs
             * 3. Prefix pattern
             */
            $params = array_merge(
                [$prefix],
                $employeeIds,
                [$prefix . '%']
            );
        }


        /*
         * -------------------------------------------------
         * Prepare UPDATE query
         * -------------------------------------------------
         */
        $stmt = $hrmconn->prepare($sql);

        if (!$stmt) {
            throw new Exception(
                "Prepare failed for {$tableName}: " .
                $hrmconn->error
            );
        }


        /*
         * All parameters are strings
         */
        $types = str_repeat('s', count($params));

        $stmt->bind_param($types, ...$params);


        /*
         * Execute UPDATE
         */
        if (!$stmt->execute()) {
            throw new Exception(
                "Update failed for {$tableName}: " .
                $stmt->error
            );
        }


        /*
         * Store report
         */
        $updatedRows = $stmt->affected_rows;

        $updateReport[$tableName] = [
            'rows'           => $updatedRows,
            'locationid_set' => $hasLocationId
        ];

        $totalUpdated += $updatedRows;

        $stmt->close();
    }


    /*
     * Commit all updates
     */
    $hrmconn->commit();


    /*
     * -------------------------------------------------
     * Success Report
     * -------------------------------------------------
     */
    echo "<h3>Update Completed Successfully</h3>";

    echo "
        <table border='1' cellpadding='8' cellspacing='0'>
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Rows Updated</th>
                    <th>Location ID Updated</th>
                </tr>
            </thead>
            <tbody>
    ";

    foreach ($updateReport as $table => $report) {

        $locationStatus = $report['locationid_set']
            ? 'Yes'
            : 'No — column not found';

        echo "
            <tr>
                <td>" . htmlspecialchars($table) . "</td>
                <td><strong>{$report['rows']}</strong></td>
                <td>{$locationStatus}</td>
            </tr>
        ";
    }

    echo "
            </tbody>
        </table>
    ";

    echo "
        <br>
        <strong>Total rows updated: {$totalUpdated}</strong>
    ";


} catch (Throwable $e) {

    /*
     * Rollback all changes if any table fails
     */
    try {
        $hrmconn->rollback();
    } catch (Throwable $rollbackException) {
        // Transaction may not have started
    }

    echo "<h3>Update Failed — All changes rolled back.</h3>";

    echo "<strong>Error:</strong> " .
        htmlspecialchars($e->getMessage());
}

?>