<?php
require('../../connection.php');
$stockorder_no = 'KFP2862';
$sowingbatchno = '100147';
mysqli_begin_transaction($conn);
try {
    /*
     * Copy data from:
     * krf_polyhouse_sowingplant_details
     *
     * to:
     * krf_polyhouse_plant_details
     *
     * Duplicate check:
     * stockorder_no + sowingplantid + barcode
     */

    $sql = "
        INSERT INTO karjeefarm.krf_polyhouse_plant_details
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
            locationid,
            labourid,
            polyhouseid,
            projectid,
            lotno,
            barcode,
            productid,
            productname,

            tray_position,
            bed_no,
            row_no,
            column_no,
            rack_no,
            floar_no,

            growth_stage,
            health_status,
            last_inspection_date,
            last_inspection_remark,
            inspected_by,

            status,
            workingstatus,
            trade_warehousedate,
            remarks,

            created_at,
            created_by,
            updated_at,
            updated_by,

            dispatchdate_internal
        )

        SELECT
            CURDATE() AS planted_date,

            s.sowingplantid,
            s.sowing_date,
            s.sowingdate_internal,
            s.sowinglotno_internal,
            s.sowingbatchno,
            s.psono,
            s.stockorder_no,
            s.roottype,
            s.locationid,
            s.labourid,
            s.polyhouseid,
            s.projectid,
            s.lotno,
            s.barcode,
            s.productid,
            s.productname,

            s.tray_position,
            s.bed_no,
            s.row_no,
            s.column_no,
            s.rack_no,
            s.floar_no,

            s.growth_stage,
            s.health_status,
            s.last_inspection_date,
            s.last_inspection_remark,
            s.inspected_by,

            'Polyhouse' AS status,
            s.workingstatus,
            s.trade_warehousedate,
            s.remarks,

            NOW() AS created_at,
            s.created_by,
            NOW() AS updated_at,
            s.updated_by,

            s.dispatchdate_internal

        FROM karjeefarm.krf_polyhouse_sowingplant_details s

        WHERE s.stockorder_no = ?
          AND s.sowingbatchno = ?

          AND NOT EXISTS
          (
              SELECT 1
              FROM karjeefarm.krf_polyhouse_plant_details p
              WHERE p.stockorder_no = s.stockorder_no
                AND p.sowingplantid = s.sowingplantid
                AND p.barcode = s.barcode
          )
    ";

    $stmt = mysqli_prepare($conn, $sql);

    if (!$stmt) {
        throw new Exception("Prepare failed: " . mysqli_error($conn));
    }

    mysqli_stmt_bind_param(
        $stmt,
        "ss",
        $stockorder_no,
        $sowingbatchno
    );

    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception("Insert failed: " . mysqli_stmt_error($stmt));
    }

    $inserted = mysqli_stmt_affected_rows($stmt);

    mysqli_stmt_close($stmt);

    mysqli_commit($conn);

    echo json_encode([
        'status' => true,
        'message' => 'Data copied successfully.',
        'stockorder_no' => $stockorder_no,
        'sowingbatchno' => $sowingbatchno,
        'inserted_records' => $inserted
    ]);

} catch (Exception $e) {

    mysqli_rollback($conn);

    echo json_encode([
        'status' => false,
        'message' => $e->getMessage()
    ]);
}
?>