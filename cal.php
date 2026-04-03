<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

/* DB CONNECT */
$conn = new mysqli("localhost","root","","mess");

if($conn->connect_error){
    die("Connection Failed: " . $conn->connect_error);
}

/* 🔥 ONLY PENDING CUSTOMERS */
$res = $conn->query("SELECT * FROM customer WHERE panding > 0");

if(!$res){
    die("Query Error");
}

while($row = $res->fetch_assoc()){

    $cid = $row['cid'];
    $name = $row['name'];
    $mobile = $row['moblie'];
    $type = $row['m_type'];
    $plus = $row['plus'];
    $days = $row['Days'];
    $panding = $row['panding'];

    /* BASE */
    if($type == 'L' || $type == 'D'){
        $base = 1700;
    } else {
        $base = 3200;
    }

    /* FIXED RATE */
    if($type == 'L' || $type == 'D'){
        $rate = 60;
        $total = $plus * $rate;
        $use = $plus . " Plate";
    } else {
        $rate = 110;
        $total = $days * $rate;
        $use = $days . " Days";
    }

    /* FINAL */
    $final = $total + $panding - $base;

    /* STATUS */
    if($final > 0){
        $status = "PAY";
    }
    elseif($final < 0){
        $status = "RETURN";
    }
    else{
        $status = "CLEAR";
    }

    /* MESSAGE FORMAT */
    $message  = "CID: $cid $name\n";
    $message .= "Type: $type\n";
    $message .= "Use: $use\n";
    $message .= "Rate: $rate\n";
    $message .= "Total: $total\n";
    $message .= "Base: $base\n";
    $message .= "Pending: $panding\n";
    $message .= "Final: $final ($status)";

    /* INSERT INTO sms_log */
    $stmt = $conn->prepare("INSERT INTO sms_log (cid, mobile, message) VALUES (?, ?, ?)");
    
    if($stmt){
        $stmt->bind_param("iss", $cid, $mobile, $message);
        $stmt->execute();
        $stmt->close();
    }
}

/* SUCCESS MESSAGE */
echo "✅ All Pending Customers Messages Stored Successfully!";

$conn->close();

?>