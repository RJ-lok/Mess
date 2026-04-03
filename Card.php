<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ================= DB CONNECTION ================= */
$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) die("DB Connection Failed");

/* ================= GET CID ================= */
$cid = isset($_GET['cid']) ? (int)$_GET['cid'] : 0;
if ($cid === 0) {
    $r = $conn->query("SELECT cid FROM customer ORDER BY cid ASC LIMIT 1");
    if ($r && $r->num_rows) {
        $cid = $r->fetch_assoc()['cid'];
    }
}

/* ================= FETCH CUSTOMER ================= */
$cstmt = $conn->prepare("SELECT * FROM customer WHERE cid=?");
$cstmt->bind_param("i", $cid);
$cstmt->execute();
$customer = $cstmt->get_result()->fetch_assoc();
if (!$customer) die("Customer not found");

/* ================= START DATE ================= */
$startDate = !empty($customer['s_date'])
    ? new DateTime($customer['s_date'])
    : new DateTime();

/* ================= FETCH ENTRY DATA ================= */
$entries = [];
$estmt = $conn->prepare("SELECT date, day, night FROM entry WHERE cid=?");
$estmt->bind_param("i", $cid);
$estmt->execute();
$res = $estmt->get_result();
while ($row = $res->fetch_assoc()) {
    $entries[$row['date']] = $row;
}

/* ================= FETCH ABSENT DATA ================= */
$absentRecords = [];
$astmt = $conn->prepare("SELECT date, day, night FROM absent WHERE cid=?");
$astmt->bind_param("i", $cid);
$astmt->execute();
$ares = $astmt->get_result();
while ($row = $ares->fetch_assoc()) {
    $absentRecords[$row['date']] = $row;
}

/* ================= LAST ENTRY DATE ================= */
if (!empty($entries)) {
    $lastEntryDate = max(array_keys($entries));
    $lastDateObj = new DateTime($lastEntryDate);
} else {
    $lastDateObj = new DateTime();
}
if ($startDate > $lastDateObj) {
    $lastDateObj = clone $startDate;
}

/* ================= PREV / NEXT ================= */
$prev = $conn->query("SELECT cid FROM customer WHERE cid<$cid ORDER BY cid DESC LIMIT 1")->fetch_assoc();
$next = $conn->query("SELECT cid FROM customer WHERE cid>$cid ORDER BY cid ASC LIMIT 1")->fetch_assoc();

/* ================= DATE RANGE FILTER ================= */
$fromDate = $_GET['from_date'] ?? $startDate->format("Y-m-d");
$toDate   = $_GET['to_date']   ?? $lastDateObj->format("Y-m-d");

$fromObj = new DateTime($fromDate);
$toObj   = new DateTime($toDate);
if ($fromObj > $toObj) [$fromObj, $toObj] = [$toObj, $fromObj];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Customer Card</title>

<style>
body{
    font-family:"Segoe UI",Arial,sans-serif;
    background: linear-gradient(135deg, #0b7285, #0a5263);
    padding:20px;
}
.container{
    max-width:1100px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:14px;
    box-shadow:0 10px 25px rgba(0,0,0,.15);
}
.header{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:20px;
    gap:15px;
}
.header h2{font-size:32px;font-weight:800;}
.home-btn{
    padding:10px 20px;
    background:linear-gradient(135deg,#28a745,#218838);
    color:#fff;
    text-decoration:none;
    border-radius:8px;
    font-weight:700;
}
.cid-form{
    display:flex;
    align-items:center;
    gap:8px;
    background:#f5f7fa;
    padding:8px 12px;
    border-radius:10px;
}
.cid-input{
    width:90px;
    padding:8px;
    border-radius:8px;
    border:1px solid #ccc;
    font-weight:600;
}
.cid-btn{
    padding:8px 16px;
    border:none;
    border-radius:8px;
    background:linear-gradient(135deg,#007bff,#0056b3);
    color:#fff;
    font-weight:700;
    cursor:pointer;
}
.customer-info{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(180px,1fr));
    gap:12px;
    margin-bottom:20px;
}
.customer-info div{
    background:#f5f7fa;
    border-left:5px solid #007bff;
    padding:10px 14px;
    border-radius:8px;
    font-weight:600;
}
.nav{
    display:flex;
    justify-content:space-between;
    align-items:center;
    margin-bottom:15px;
}
.nav a{
    padding:10px 24px;
    background:linear-gradient(135deg,#007bff,#0056b3);
    color:#fff;
    text-decoration:none;
    border-radius:8px;
    font-weight:700;
}
.nav a.disabled{
    background:#aaa;
    pointer-events:none;
}
.range-form{
    display:flex;
    gap:8px;
    align-items:center;
    background:#f5f7fa;
    padding:8px 14px;
    border-radius:10px;
}
.range-form input{
    padding:6px;
    border-radius:6px;
    border:1px solid #ccc;
}
.range-form button{
    padding:6px 14px;
    border:none;
    border-radius:6px;
    background:#28a745;
    color:#fff;
    font-weight:700;
    cursor:pointer;
}
table{
    width:100%;
    border-collapse:collapse;
}
th{
    background:#007bff;
    color:#fff;
}
th,td{
    border:1px solid #ddd;
    padding:10px;
    text-align:center;
}
tr:nth-child(even){background:#f9f9f9;}
@media (max-width:768px){
    .header{
        flex-direction:column;
        align-items:flex-start;
        gap:10px;
    }
}
</style>
</head>

<body>
<div class="container">

<div class="header">
    <h2>Customer Card</h2>
    <a href="index.html" class="home-btn">🏠 Home </a>

    <form method="get" class="cid-form">
        <label>CID:</label>
        <input type="number" name="cid" value="<?= $customer['cid'] ?>" class="cid-input">
        <button class="cid-btn">Go</button>
    </form>
</div>

<div class="customer-info">
    <div><b>CID:</b> <?= $customer['cid'] ?></div>
    <div><b>Name:</b> <?= htmlspecialchars($customer['name']) ?></div>
    <div><b>Paid:</b> <?= $customer['paid'] ?></div>
    <div><b>Pending:</b> <?= $customer['panding'] ?></div>
    <div><b>Meal Type:</b> <?= $customer['m_type'] ?></div>
    <div><b>No. of Days:</b> <?= $customer['Days'] ?></div>
    <div><b>Total Plates:</b> <?= $customer['plus'] ?></div>
    <div><b>Start Date:</b> <?= $customer['s_date'] ?></div>
</div>

<div class="nav">
    <a class="<?= !$prev ? 'disabled' : '' ?>" href="?cid=<?= $prev['cid'] ?? '' ?>">← Previous</a>

    <form method="get" class="range-form">
        <input type="hidden" name="cid" value="<?= $customer['cid'] ?>">
        <input type="date" name="from_date" value="<?= $fromObj->format('Y-m-d') ?>">
        <input type="date" name="to_date" value="<?= $toObj->format('Y-m-d') ?>">
        <button>Apply</button>
    </form>

    <a class="<?= !$next ? 'disabled' : '' ?>" href="?cid=<?= $next['cid'] ?? '' ?>">Next →</a>
</div>
<?php
$plateCount = 0;
$totalDays = 0;
$counter = 0;
$maxDays = 365;

for ($d = clone $fromObj; $d <= $toObj && $counter < $maxDays; $d->modify("+1 day")) {

    $counter++;
    $date = $d->format("Y-m-d");

    $dayEntry   = $entries[$date]['day'] ?? '';
    $nightEntry = $entries[$date]['night'] ?? '';

    $dayAbsent   = $absentRecords[$date]['day'] ?? '';
    $nightAbsent = $absentRecords[$date]['night'] ?? '';

    $dayFlag = false;
    $nightFlag = false;

    if($dayEntry == 'Y' && $dayAbsent != 'Y'){
        $plateCount++;
        $dayFlag = true;
    }

    if($nightEntry == 'Y' && $nightAbsent != 'Y'){
        $plateCount++;
        $nightFlag = true;
    }

    if($dayFlag || $nightFlag){
        $totalDays++;
    }
}
?>
<div style="
margin-bottom:15px;
font-size:18px;
font-weight:bold;
background:#f1f8ff;
padding:10px;
border-left:5px solid #007bff;
border-radius:6px;
">
 Total Days: <?= $totalDays ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;   Total Plates: <?= $plateCount ?>
</div>
<table>
<tr>
    <th>Date</th>
    <th>Day</th>
    <th>Night</th>
</tr>
<?php
$counter = 0;

for ($d = clone $fromObj; $d <= $toObj && $counter < $maxDays; $d->modify("+1 day")) {

    $counter++;
    $date = $d->format("Y-m-d");

    $dayEntry   = $entries[$date]['day'] ?? '';
    $nightEntry = $entries[$date]['night'] ?? '';

    $dayAbsent   = $absentRecords[$date]['day'] ?? '';
    $nightAbsent = $absentRecords[$date]['night'] ?? '';

    echo "<tr>
        <td>$date</td>
        <td>";

        if($dayAbsent == 'Y'){
            echo "<span style='color:red;font-weight:bold;'>❌</span>";
        } elseif($dayEntry == 'Y'){
            echo "<span style='color:green;font-weight:bold;'>✔</span>";
        } else {
            echo "<span style='color:orange;font-weight:bold;'>-</span>";
        }

    echo "</td>
        <td>";

        if($nightAbsent == 'Y'){
            echo "<span style='color:red;font-weight:bold;'>❌</span>";
        } elseif($nightEntry == 'Y'){
            echo "<span style='color:green;font-weight:bold;'>✔</span>";
        } else {
            echo "<span style='color:orange;font-weight:bold;'>-</span>";
        }

    echo "</td>
    </tr>";
}
?>

</table>
</div>

<script>
document.addEventListener("keydown",e=>{
    if(e.key==="ArrowRight"){
        let n=document.querySelector(".nav a:last-child");
        if(!n.classList.contains("disabled")) location=n.href;
    }
    if(e.key==="ArrowLeft"){
        let p=document.querySelector(".nav a:first-child");
        if(!p.classList.contains("disabled")) location=p.href;
    }
});
</script>

</body>
</html>
