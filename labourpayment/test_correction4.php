<?php

require('connection.php');

/*
 * Existing Employee IDs
 */
$employeeIds = [
    'KFM-11042',
    'KFM-11043',
    'KFM-11044'
];

/*
 * Replacement numeric IDs
 *
 * 1 => 111111
 * 2 => 222222
 */
$replaceIds = [
    'KFM-11076',
    'KFM-11077',
    'KFM-11078'
];

/*
 * Table => Employee ID column
 */
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

/*
 * Prefix to add before replacement EmployeeID
 */
$prefix = 'KFM-';

/*
 * Location ID
 */
$locationid = 'LOC6905e1117eb3c';


try {

    /*
     * -----------------------------------------
     * Validate arrays
     * -----------------------------------------
     */

    if (empty($employeeIds)) {
        throw new Exception('Employee ID list cannot be empty.');
    }

    if (empty($replaceIds)) {
        throw new Exception('Replacement ID list cannot be empty.');
    }

    if (count($employeeIds) !== count($replaceIds)) {
        throw new Exception(
            'Employee ID count and replacement ID count must be equal.'
        );
    }


    /*
     * Create mapping:
     *
     * 1 => 111111
     * 2 => 222222
     */
    $idMapping = array_combine($employeeIds, $replaceIds);

    if ($idMapping === false) {
        throw new Exception('Failed to create employee ID mapping.');
    }


    /*
     * Start transaction
     */
    $hrmconn->begin_transaction();

    $updateReport = [];
    $totalUpdated = 0;


    /*
     * Process each table
     */
    foreach ($tables as $tableName => $columnName) {

        /*
         * -----------------------------------------
         * Check available columns
         * -----------------------------------------
         */

        $checkSql = "
            SELECT COLUMN_NAME
            FROM INFORMATION_SCHEMA.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = ?
              AND COLUMN_NAME IN ('locationid', 'seriesid')
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

        $result = $checkStmt->get_result();

        $hasLocationId = false;
        $hasSeriesId   = false;

        while ($row = $result->fetch_assoc()) {

            $existingColumn = strtolower($row['COLUMN_NAME']);

            if ($existingColumn === 'locationid') {
                $hasLocationId = true;
            }

            if ($existingColumn === 'seriesid') {
                $hasSeriesId = true;
            }
        }

        $checkStmt->close();


        /*
         * Initialize report for current table
         */
        $updateReport[$tableName] = [
            'rows'           => 0,
            'locationid_set' => $hasLocationId,
            'seriesid_set'   => $hasSeriesId,
            'details'        => []
        ];


        /*
         * -----------------------------------------
         * Replace each Employee ID
         * -----------------------------------------
         */
        foreach ($idMapping as $oldId => $replaceId) {

            /*
             * New EmployeeID with prefix
             *
             * Example:
             * KRF-111111
             */
            $newEmployeeId = $prefix . $replaceId;


            /*
             * -----------------------------------------
             * Build SET dynamically
             * -----------------------------------------
             */

            $setParts = [];
            $params   = [];
            $types    = '';

            /*
             * Update main Employee ID column
             */
            $setParts[] = "`$columnName` = ?";
            $params[]   = $newEmployeeId;
            $types     .= 's';


            /*
             * Update locationid if column exists
             */
            if ($hasLocationId) {

                $setParts[] = "`locationid` = ?";
                $params[]   = $locationid;
                $types     .= 's';
            }


            /*
             * Update seriesid without prefix
             *
             * Example:
             * EmployeeID = KRF-111111
             * seriesid   = 111111
             */
            if ($hasSeriesId) {

                $setParts[] = "`seriesid` = ?";
                $params[]   = $replaceId;
                $types     .= 's';
            }


            /*
             * Old Employee ID for WHERE
             */
            $params[] = $oldId;
            $types   .= 's';


            /*
             * Final SQL
             */
            $sql = "
                UPDATE `$tableName`
                SET " . implode(', ', $setParts) . "
                WHERE `$columnName` = ?
            ";


            /*
             * Prepare
             */
            $stmt = $hrmconn->prepare($sql);

            if (!$stmt) {
                throw new Exception(
                    "Prepare failed for {$tableName}: " .
                    $hrmconn->error
                );
            }


            /*
             * Bind parameters
             */
            $stmt->bind_param($types, ...$params);


            /*
             * Execute
             */
            if (!$stmt->execute()) {

                throw new Exception(
                    "Update failed for {$tableName} " .
                    "({$oldId} → {$newEmployeeId}): " .
                    $stmt->error
                );
            }


            /*
             * Get updated rows
             */
            $updatedRows = $stmt->affected_rows;

            $updateReport[$tableName]['rows'] += $updatedRows;

            $updateReport[$tableName]['details'][] = [
                'old_id'      => $oldId,
                'new_id'      => $newEmployeeId,
                'seriesid'    => $hasSeriesId ? $replaceId : '-',
                'rows'        => $updatedRows
            ];

            $totalUpdated += $updatedRows;

            $stmt->close();
        }
    }


    /*
     * -----------------------------------------
     * Commit all changes
     * -----------------------------------------
     */
    $hrmconn->commit();


    /*
     * -----------------------------------------
     * Success Report
     * -----------------------------------------
     */

    echo "<h2>Update Completed Successfully</h2>";

    echo "
        <table
            border='1'
            cellpadding='8'
            cellspacing='0'
            style='border-collapse: collapse; width: 100%;'
        >
            <thead>
                <tr>
                    <th>Table Name</th>
                    <th>Old Employee ID</th>
                    <th>New Employee ID</th>
                    <th>Series ID</th>
                    <th>Rows Updated</th>
                    <th>Location ID</th>
                </tr>
            </thead>

            <tbody>
    ";


    foreach ($updateReport as $table => $report) {

        foreach ($report['details'] as $detail) {

            $locationStatus = $report['locationid_set']
                ? htmlspecialchars($locationid)
                : 'Column not found';

            echo "
                <tr>
                    <td>" . htmlspecialchars($table) . "</td>

                    <td>
                        " . htmlspecialchars($detail['old_id']) . "
                    </td>

                    <td>
                        <strong>
                            " . htmlspecialchars($detail['new_id']) . "
                        </strong>
                    </td>

                    <td>
                        " . htmlspecialchars($detail['seriesid']) . "
                    </td>

                    <td>
                        {$detail['rows']}
                    </td>

                    <td>
                        {$locationStatus}
                    </td>
                </tr>
            ";
        }
    }


    echo "
            </tbody>
        </table>

        <br>

        <strong>
            Total rows updated: {$totalUpdated}
        </strong>
    ";


} catch (Throwable $e) {

    /*
     * Rollback all changes
     */
    try {
        $hrmconn->rollback();
    } catch (Throwable $rollbackException) {
        // Ignore rollback failure
    }

    echo "<h3>Update Failed — All changes rolled back.</h3>";

    echo "<strong>Error:</strong> " .
        htmlspecialchars($e->getMessage());
}

?>