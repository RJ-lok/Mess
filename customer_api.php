<?php
header("Content-Type: application/json");

// === DB CONNECTION ===
$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) {
    echo json_encode(["status" => "error", "message" => "DB connection failed"]);
    exit;
}

// === GET cid and mobile from POST or GET ===
$cid = $_POST['cid'] ?? $_GET['cid'] ?? '';
$mobile = $_POST['mobile'] ?? $_GET['mobile'] ?? '';

if (!$cid || !$mobile) {
    echo json_encode(["status" => "error", "message" => "Missing CID or Mobile"]);
    exit;
}

// === MAKE SURE COLUMN NAME MATCHES DB ===
// Use proper escaping to prevent SQL injection for this quick version
$cid = (int)$cid;
$mobile = $conn->real_escape_string($mobile);

// === QUERY ===
$sql = "SELECT cid, name FROM customer WHERE cid=$cid AND mob='$mobile'";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode([
        "status" => "success",
        "data" => $row
    ]);
} else {
    echo json_encode([
        "status" => "error",
        "message" => "No matching record"
    ]);
}

// === CLOSE CONNECTION ===
$conn->close();
?>