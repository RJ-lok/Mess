<?php

$conn = new mysqli("localhost","root","","mess");

$res = $conn->query("SELECT * FROM sms_log ORDER BY cid ASC");

while($row = $res->fetch_assoc()){
    echo "<pre>";
    echo "Mobile: ".$row['mobile']."\n";
    echo $row['message'];
    echo "</pre><hr>";
}

?>