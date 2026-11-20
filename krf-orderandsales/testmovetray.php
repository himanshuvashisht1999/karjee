<?php
require('../connection.php');

$createdby="Auto";
$locationid='LOC6905e0fd5ab05';
$items = [
    [
        'stockorder_no' => 'PSO-KRF200220266466',
        'sowingdate_internal' => '2026-05-08'
    ]
];
$sss = transferSowingToPolyhouse($krfconn, $locationid, $createdby, $items);
print_r($sss);

function transferSowingToPolyhouse($krfconn, $locationid, $createdby, array $items = []){
    mysqli_begin_transaction($krfconn);
    try {

        $insertedCount = 0;
        $skippedCount  = 0;
        $lastChosenPolyhouseid = null;

        if (empty($items)) {
            mysqli_commit($krfconn);
            return [
                'status'   => 'success',
                'inserted' => 0,
                'skipped'  => 0,
                'polyhouseid' => null,
                'message'  => '0 records transferred successfully.'
            ];
        }

        foreach ($items as $item) {
            $so = isset($item['stockorder_no']) ? $item['stockorder_no'] : '';
            $sowingdate = isset($item['sowingdate_internal']) ? $item['sowingdate_internal'] : '';

            if (empty($so)) {
                continue;
            }

            $sowingDateCond = "";
            if ($sowingdate !== '') {
                $sowingDateCond = " AND sowingdate_internal = '".mysqli_real_escape_string($krfconn,$sowingdate)."'";
            }

            $sql = "SELECT * FROM krf_polyhouse_sowingplant_details WHERE locationid = '".mysqli_real_escape_string($krfconn,$locationid)."' AND status in ('Germination','Sowing') AND sowingdate_internal<>'' AND stockorder_no = '".mysqli_real_escape_string($krfconn,$so)."' " . $sowingDateCond . " ORDER BY sowingplantid ASC";

            $result = mysqli_query($krfconn, $sql);

            if (!$result) {
                throw new Exception(mysqli_error($krfconn));
            }

            $rows = [];
            while ($row = mysqli_fetch_assoc($result)) {
                $rows[] = $row;
            }

            if (empty($rows)) {
                continue;
            }

            // Count how many trays of each trayid are needed to move
            $neededTrays = [];
            foreach ($rows as $row) {
                $trayid = $row['trayid'];
                if (!isset($neededTrays[$trayid])) {
                    $neededTrays[$trayid] = 0;
                }
                $neededTrays[$trayid]++;
            }

            // Check polyhouses in the specified location for capacity
            $chosenPolyhouseid = null;
            $polyhousesSql = "SELECT polyhouseid FROM krf_polyhouse_master WHERE locationid = '".mysqli_real_escape_string($krfconn,$locationid)."' ORDER BY polyhouseid ASC";
            $polyhousesResult = mysqli_query($krfconn, $polyhousesSql);
            if (!$polyhousesResult) {
                throw new Exception(mysqli_error($krfconn));
            }
            while ($pRow = mysqli_fetch_assoc($polyhousesResult)) {
                $candidate = $pRow['polyhouseid'];
                if (checkPolyhouseCapacity($krfconn, $candidate, $neededTrays)) {
                    $chosenPolyhouseid = $candidate;
                    break;
                }
            }

            if (!$chosenPolyhouseid) {
                throw new Exception("selected location all polyhouse full");
            }

            $lastChosenPolyhouseid = $chosenPolyhouseid;

            foreach ($rows as $row) {

                /* ======================================
                   DUPLICATE CHECK
                ====================================== */

                $duplicateSql = "
                    SELECT polyhouseplantid
                    FROM krf_polyhouse_plant_details
                    WHERE sowingplantid = '".mysqli_real_escape_string($krfconn,$row['sowingplantid'])."'
                    LIMIT 1
                ";

                $duplicateResult = mysqli_query($krfconn, $duplicateSql);

                if (mysqli_num_rows($duplicateResult) > 0) {
                    $skippedCount++;
                    continue;
                }

                /* ======================================
                   FIND AVAILABLE BED
                ====================================== */

                $trayid = $row['trayid'];
                $bedInfo = getAvailableBed($krfconn, $chosenPolyhouseid, $trayid);

                if (!$bedInfo['status']) {
                    throw new Exception(
                        "No bed available for Tray ID ".$trayid.
                        " in Polyhouse ".$chosenPolyhouseid
                    );
                }

                /* ======================================
                   INSERT INTO POLYHOUSE TABLE
                ====================================== */

                $insertSql = "
                    INSERT INTO krf_polyhouse_plant_details
                    (
                        planted_date,
                        sowingplantid,
                        sowing_date,
                        sowingdate_internal,
                        sowinglotno_internal,
                        sowingbatchno,
                        psono,
                        stockorder_no,
                        roottype,
                        labourid,
                        polyhouseid,
                        locationid,
                        projectid,
                        lotno,
                        barcode,
                        productid,
                        productname,
                        tray_id,
                        qtypertray,
                        tray_position,
                        bed_no,
                        row_no,
                        column_no,
                        rack_no,
                        floar_no,
                        growth_stage,
                        health_status,
                        status,
                        trade_warehousedate,
                        remarks,
                        created_by,
                        dispatchdate_internal
                    )
                    VALUES
                    (
                        CURDATE(),
                        '".mysqli_real_escape_string($krfconn,$row['sowingplantid'])."',
                        ".($row['sowing_date'] ? "'".$row['sowing_date']."'" : "NULL").",
                        ".($row['sowingdate_internal'] ? "'".$row['sowingdate_internal']."'" : "NULL").",
                        '".mysqli_real_escape_string($krfconn,$row['sowinglotno_internal'])."',
                        '".mysqli_real_escape_string($krfconn,$row['sowingbatchno'])."',
                        '".mysqli_real_escape_string($krfconn,$row['psono'])."',
                        '".mysqli_real_escape_string($krfconn,$row['stockorder_no'])."',
                        '".mysqli_real_escape_string($krfconn,$row['roottype'])."',
                        '".mysqli_real_escape_string($krfconn,$row['labourid'])."',
                        '".mysqli_real_escape_string($krfconn,$chosenPolyhouseid)."',
                        '".mysqli_real_escape_string($krfconn,$locationid)."',
                        '".mysqli_real_escape_string($krfconn,$row['projectid'])."',
                        '".mysqli_real_escape_string($krfconn,$row['lotno'])."',
                        '".mysqli_real_escape_string($krfconn,$row['barcode'])."',
                        '".mysqli_real_escape_string($krfconn,$row['productid'])."',
                        '".mysqli_real_escape_string($krfconn,$row['productname'])."',
                        '".mysqli_real_escape_string($krfconn,$row['trayid'])."',
                        '".mysqli_real_escape_string($krfconn,$row['traycavity'])."',
                        '".mysqli_real_escape_string($krfconn,$row['tray_position'])."',
                        '".mysqli_real_escape_string($krfconn,$bedInfo['bed_no'])."',
                        ".($row['row_no'] ? "'".$row['row_no']."'" : "NULL").",
                        ".($row['column_no'] ? "'".$row['column_no']."'" : "NULL").",
                        '".mysqli_real_escape_string($krfconn,$row['rack_no'])."',
                        '".mysqli_real_escape_string($krfconn,$row['floar_no'])."',
                        '".mysqli_real_escape_string($krfconn,$row['growth_stage'])."',
                        '".mysqli_real_escape_string($krfconn,$row['health_status'])."',
                        'Polyhouse',
                        ".($row['trade_warehousedate'] ? "'".$row['trade_warehousedate']."'" : "NULL").",
                        '".mysqli_real_escape_string($krfconn,$row['remarks'])."',
                        '".mysqli_real_escape_string($krfconn,$createdby)."',
                        ".($row['dispatchdate_internal'] ? "'".$row['dispatchdate_internal']."'" : "NULL")."
                    )
                ";

                if (!mysqli_query($krfconn, $insertSql)) {
                    throw new Exception(mysqli_error($krfconn));
                }

                $updateStatusSql = "
                    UPDATE krf_polyhouse_sowingplant_details
                    SET status = 'Polyhouse'
                    WHERE sowingplantid = '".mysqli_real_escape_string($krfconn, $row['sowingplantid'])."'
                ";
                if (!mysqli_query($krfconn, $updateStatusSql)) {
                    throw new Exception(mysqli_error($krfconn));
                }

                $insertedCount++;
            }
        }

        mysqli_commit($krfconn);

        return [
            'status'   => 'success',
            'inserted' => $insertedCount,
            'skipped'  => $skippedCount,
            'polyhouseid' => $lastChosenPolyhouseid,
            'message'  => $insertedCount.' records transferred successfully.'
        ];

    } catch (Exception $e) {

        mysqli_rollback($krfconn);

        return [
            'status'  => 'error',
            'message' => $e->getMessage()
        ];
    }
}

function checkPolyhouseCapacity($krfconn, $polyhouseid, $neededTrays)
{
    $sql = "
        SELECT beddetails
        FROM krf_polyhouse_master
        WHERE polyhouseid = '".mysqli_real_escape_string($krfconn, $polyhouseid)."'
        LIMIT 1
    ";
    $result = mysqli_query($krfconn, $sql);
    if (!$result || mysqli_num_rows($result) == 0) {
        return false;
    }
    $row = mysqli_fetch_assoc($result);
    $beds = json_decode($row['beddetails'], true);
    if (!is_array($beds) || empty($beds)) {
        return false;
    }

    $bedAvailable = [];
    foreach ($beds as $bed) {
        if (!isset($bed['bedname'])) {
            continue;
        }
        $bedname = trim($bed['bedname']);

        foreach ($neededTrays as $trayid => $neededCount) {
            if (!isset($bed['traycapacity'][$trayid])) {
                continue;
            }
            $capacity = (int)$bed['traycapacity'][$trayid]['capacity'];

            $countSql = "
                SELECT COUNT(*) AS total
                FROM krf_polyhouse_plant_details
                WHERE polyhouseid = '".mysqli_real_escape_string($krfconn, $polyhouseid)."'
                AND bed_no = '".mysqli_real_escape_string($krfconn, $bedname)."'
                AND tray_id = '".mysqli_real_escape_string($krfconn, $trayid)."'
                AND health_status <> 'Dead'
                AND status NOT IN ('Removed','Final Dispatch')
            ";
            $countResult = mysqli_query($krfconn, $countSql);
            if (!$countResult) {
                return false;
            }
            $countRow = mysqli_fetch_assoc($countResult);
            $used = (int)$countRow['total'];
            $available = $capacity - $used;
            if ($available < 0) {
                $available = 0;
            }

            $bedAvailable[$bedname][$trayid] = $available;
        }
    }

    foreach ($neededTrays as $trayid => $neededCount) {
        for ($i = 0; $i < $neededCount; $i++) {
            $placed = false;
            foreach ($bedAvailable as $bedname => &$trayAvail) {
                if (isset($trayAvail[$trayid]) && $trayAvail[$trayid] > 0) {
                    $trayAvail[$trayid]--;
                    $placed = true;
                    break;
                }
            }
            unset($trayAvail);
            if (!$placed) {
                return false;
            }
        }
    }

    return true;
}

function getAvailableBed($krfconn, $polyhouseid, $trayid)
{
    try {

        $sql = "
            SELECT beddetails
            FROM krf_polyhouse_master
            WHERE polyhouseid = '".mysqli_real_escape_string($krfconn, $polyhouseid)."'
            LIMIT 1
        ";

        $result = mysqli_query($krfconn, $sql);

        if (!$result || mysqli_num_rows($result) == 0) {
            return [
                'status'  => false,
                'message' => 'Polyhouse configuration not found.'
            ];
        }

        $row = mysqli_fetch_assoc($result);

        $beds = json_decode($row['beddetails'], true);

        if (!is_array($beds) || empty($beds)) {
            return [
                'status'  => false,
                'message' => 'Invalid bed configuration.'
            ];
        }

        foreach ($beds as $bed) {

            if (
                !isset($bed['bedname']) ||
                !isset($bed['traycapacity'][$trayid])
            ) {
                continue;
            }

            $bedname  = trim($bed['bedname']);
            $capacity = (int)$bed['traycapacity'][$trayid]['capacity'];

            // Current occupancy of this bed for same tray type
            $countSql = "
                SELECT COUNT(*) AS total
                FROM krf_polyhouse_plant_details
                WHERE polyhouseid = '".mysqli_real_escape_string($krfconn, $polyhouseid)."'
                AND bed_no = '".mysqli_real_escape_string($krfconn, $bedname)."'
                AND tray_id = '".mysqli_real_escape_string($krfconn, $trayid)."'
                AND health_status <> 'Dead'
                AND status NOT IN ('Removed','Final Dispatch')
            ";

            $countResult = mysqli_query($krfconn, $countSql);

            if (!$countResult) {
                throw new Exception(mysqli_error($krfconn));
            }

            $countRow = mysqli_fetch_assoc($countResult);

            $usedCapacity = (int)$countRow['total'];

            // Bed has space available
            if ($usedCapacity < $capacity) {

                return [
                    'status'         => true,
                    'bed_no'         => $bedname,
                    'capacity'       => $capacity,
                    'used_capacity'  => $usedCapacity,
                    'available'      => ($capacity - $usedCapacity)
                ];
            }
        }

        return [
            'status'  => false,
            'message' => 'All beds are full for Tray ID '.$trayid
        ];

    } catch (Exception $e) {

        return [
            'status'  => false,
            'message' => $e->getMessage()
        ];
    }
}
?>