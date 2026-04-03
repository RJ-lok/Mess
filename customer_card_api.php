<?php
header("Content-Type: application/json");
error_reporting(0);

$conn = new mysqli("localhost", "root", "", "mess");

$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 1;

$cstmt = $conn->prepare("SELECT * FROM customer WHERE cid=?");
$cstmt->bind_param("i", $cid);
$cstmt->execute();
$customer = $cstmt->get_result()->fetch_assoc();

$entries = [];
$res = $conn->query("SELECT date, day, night FROM entry WHERE cid=$cid");

while ($row = $res->fetch_assoc()) {
    $entries[] = $row;
}

echo json_encode([
    "status" => "success",
    "customer" => $customer,
    "entries" => $entries
]);
?>