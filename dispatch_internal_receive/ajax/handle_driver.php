<?php
include('../../connection.php');

$action = $_POST['action'] ?? '';
echo "ajay";
// if ($action == 'add_driver') {
//     $drivername = trim($_POST['drivername'] ?? '');
//     $drivermobileno = trim($_POST['drivermobileno'] ?? '');
//     $projectid = intval($_POST['projectid'] ?? 0);

//     if (empty($drivername)) {
//         echo json_encode(['status' => 'error', 'message' => 'Driver name is required.']);
//         exit;
//     }

//     $drivername_esc = $krfconn->real_escape_string($drivername);
//     $drivermobileno_esc = $krfconn->real_escape_string($drivermobileno);

//     // Insert driver details
//     echo $query = "INSERT INTO krf_vehicle_driver_master (projectid, drivername, drivermobileno, status, created_at, updated_at) 
//               VALUES ('$projectid', '$drivername_esc', '$drivermobileno_esc', 'Active', NOW(), NOW())";

//     if ($krfconn->query($query)) {
//         $driverid = $krfconn->insert_id;
//         echo json_encode([
//             'status' => 'success',
//             'driver' => [
//                 'driverid' => $driverid,
//                 'drivername' => $drivername,
//                 'drivermobileno' => $drivermobileno
//             ]
//         ]);
//     } else {
//         echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $krfconn->error]);
//     }
//     exit;
// }

// if ($action == 'get_active_drivers') {
//     $d_res = $krfconn->query("SELECT * FROM krf_vehicle_driver_master WHERE status = 'Active' ORDER BY drivername ASC");
//     $drivers = [];
//     if ($d_res) {
//         while ($d = $d_res->fetch_assoc()) {
//             $drivers[] = [
//                 'driverid' => $d['driverid'],
//                 'drivername' => $d['drivername'],
//                 'drivermobileno' => $d['drivermobileno']
//             ];
//         }
//     }
//     echo json_encode(['status' => 'success', 'drivers' => $drivers]);
//     exit;
// }

// echo json_encode(['status' => 'error', 'message' => 'Invalid action.']);
// exit;
?>
