<?php
header("Content-Type: application/json");

$lat = $_GET['lat'] ?? '';
$lng = $_GET['lng'] ?? '';

if (!$lat || !$lng) {
    echo json_encode(["status"=>"error","address"=>""]);
    exit;
}

$url = "https://nominatim.openstreetmap.org/reverse?format=json&lat=$lat&lon=$lng&zoom=18&addressdetails=1";

$opts = [
  "http" => [
    "header" => "User-Agent: PayrollSystem/1.0\r\n"
  ]
];

$context = stream_context_create($opts);
$response = file_get_contents($url, false, $context);

$data = json_decode($response, true);

echo json_encode([
  "status" => "success",
  "address" => $data['display_name'] ?? ''
]);
