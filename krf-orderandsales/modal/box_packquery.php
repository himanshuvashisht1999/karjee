<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

include('../../connection.php');
$action = $_GET['action'] ?? '';
 switch ($action) {
    case 'getboxpackingfreightmaster':

        $projectid = intval($_POST['projectid'] ?? 0);

        if ($projectid <= 0) {

            echo json_encode([
                'status'  => 'error',
                'message' => 'Project ID is missing.'
            ]);

            exit;
        }


        $sql = "
            SELECT
                id,
                projectid,

                stateid,
                statename,

                districtid,
                districtname,

                location,

                plants_per_box,

                boxpacking_amount,
                airport_charges,
                bus_charges,
                railway_charges

            FROM krf_boxpacking_freight_master

            WHERE projectid = ?

            ORDER BY
                statename,
                districtname,
                location
        ";


        $stmt = $krfconn->prepare($sql);

        $stmt->bind_param(
            "i",
            $projectid
        );

        $stmt->execute();


        $result = $stmt->get_result();

        $data = [];


        while ($row = $result->fetch_assoc()) {

            $data[] = $row;

        }


        echo json_encode([
            'status' => 'success',
            'data'   => $data
        ]);


        exit;
    }    

 