<?php
// products.php – returns live active list of products from database as JSON
header('Content-Type: application/json');
require_once "db_connect.php";

$sql = "SELECT ProductID AS id, ProductName AS name FROM product_master WHERE Status = 'Active' ORDER BY ProductName";
$result = $krfconn->query($sql);
$products = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
echo json_encode($products);
?>
