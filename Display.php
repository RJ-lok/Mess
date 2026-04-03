<?php
require_once "auth.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ================= DB CONNECTION ================= */
$conn = mysqli_connect("localhost", "root", "", "mess");
if (!$conn) {
    die("Database connection failed: " . mysqli_connect_error());
}

/* ================= FILTER LOGIC ================= */
$where = [];

/* CID filter */
if (!empty($_POST['use_cid']) && $_POST['cid_value'] !== '') {
    $where[] = "c.cid = " . intval($_POST['cid_value']);
}

/* Paid = 0 filter */
if (!empty($_POST['paid_zero'])) {
    $where[] = "c.paid = 0";
}

/* Pending > 0 filter */
if (!empty($_POST['pending_gtzero'])) {
    $where[] = "c.panding >0";
}

/* Mess type filter */
if (!empty($_POST['use_mtype']) && !empty($_POST['m_type'])) {
    $m_type = mysqli_real_escape_string($conn, $_POST['m_type']);
    if ($m_type === 'LD') {
        $where[] = "(c.m_type LIKE '%L%D%' OR c.m_type LIKE '%D%L%')";
    } else {
        $where[] = "c.m_type = '$m_type'";
    }
}
if (!empty($_POST['use_date'])) {

    $from = mysqli_real_escape_string($conn,$_POST['date_from']);
    $to   = mysqli_real_escape_string($conn,$_POST['date_to']);

    if ($from && $to) {
        $where[] = "c.s_date BETWEEN '$from' AND '$to'";
    } elseif ($from) {
        $where[] = "c.s_date >= '$from'";
    } elseif ($to) {
        $where[] = "c.s_date <= '$to'";
    }
}
/* ================= SELECT QUERY ================= */
$query = "
SELECT 
    c.cid,
    c.name,
    c.moblie,
    c.mob,
    c.paid,
    c.panding,
    c.plus,
    c.Days,
    c.m_type,
    c.s_date,
    c.old_s_date
FROM customer c
";

/* Apply WHERE filters */
if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY c.cid";

/* ================= EXECUTE ================= */
$res = mysqli_query($conn, $query);
$customers = [];
 $total_customers=0;
$total_paid = 0;
$total_pending = 0;

if ($res && mysqli_num_rows($res) > 0) {
    while ($row = mysqli_fetch_assoc($res)) {
        $customers[] = $row;
        $total_paid += $row['paid'];
        $total_pending += $row['panding'];
         $total_customers++;
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Customer Days</title>
<style>
body{
    font-family: Arial;
    background: linear-gradient(to right,#6f2b82,#f8b500);
    padding:1px;
}
.container{
    max-width:1500px;
    margin:auto;
    background:#fff;
    padding:10px;
    border-radius:12px;
    box-shadow:0 10px 25px rgba(0,0,0,0.25);
    position: relative;
}
h2{text-align:center;margin-bottom:20px;}
table{width:100%;border-collapse:collapse;}
th,td{border:1px solid #aaa;padding:10px;text-align:center;}
th{background:#007bff;color:white;}
tr:nth-child(even){background:#f3f3f3;}

.filter-box{
    background:#f7f9fc;
    border:1px solid #d0d7e2;
    border-radius:12px;
    padding:20px;
    margin-bottom:25px;
}
.filter-box label{font-weight:600;}
.filter-box input[type="text"], .filter-box select{
    padding:6px 10px;
    border-radius:6px;
    border:1px solid #ccc;
}
.filter-box input[type="checkbox"]{
    transform:scale(1.1);
    margin-right:6px;
}
.filter-box button{
    background:linear-gradient(to right,#007bff,#00c6ff);
    color:white;
    border:none;
    padding:10px 30px;
    border-radius:25px;
    font-size:15px;
    cursor:pointer;
}

/* Home Button */
.top-right-btn{
    position:absolute;
    top:20px;
    right:20px;
    background:linear-gradient(to right,#ff416c,#ff4b2b);
    color:white;
    padding:10px 20px;
    border-radius:25px;
    text-decoration:none;
    font-weight:bold;
}

/* Date Input Styling */
.filter-box input[type="date"]{
    padding:8px 12px;
    border:1px solid #ccc;
    border-radius:8px;
    font-size:14px;
    margin:5px;
    background:#fff;
    transition:all 0.3s ease;
}

/* Hover Effect */
.filter-box input[type="date"]:hover{
    border-color:#007bff;
    box-shadow:0 0 5px rgba(0,123,255,0.3);
}

/* Focus Effect */
.filter-box input[type="date"]:focus{
    outline:none;
    border-color:#007bff;
    box-shadow:0 0 8px rgba(0,123,255,0.5);
}

/* Date Label spacing */
.filter-box label{
    margin-right:8px;
}

</style>
</head>
<body>

<div class="container">
<a href="dashboard.php" class="top-right-btn">Home</a>

<h2>Customer Days</h2>

<form method="post" class="filter-box">
<center>
<label><input type="checkbox" name="use_cid"> CID</label>
<input type="text" name="cid_value">

<label><input type="checkbox" name="use_mtype"> Mess Type&nbsp;</label>
<select name="m_type">
    <option value="">--Select--</option>
    <option value="LD"> L & D </option>
    <option value="L"> Lunch </option>
    <option value="D"> Dinner </option>
</select>

<label><input type="checkbox" name="paid_zero"> Paid = 0</label>
<label><input type="checkbox" name="pending_gtzero"> Pending = 0</label>
<label>
    
<input type="checkbox" name="use_date"> Date Range
</label>
From:
<input type="date" name="date_from" value="<?= date('Y-m-01') ?>">

To:
<input type="date" name="date_to" value="<?= date('Y-m-d') ?>">
</center>
<br>
<center><button type="submit">Apply</button></center>
</form>

<?php if (!empty($customers)): ?>
<table>
<tr style="font-weight:bold;background:#ffd700;">
    <td colspan="1">Total</td>
    <td><?= $total_customers ?></td>
    <td><?= $total_paid ?></td>
    <td><?= $total_pending ?></td>
    <td colspan="7"></td>
</tr>

<tr>
    <th>Name</th>
    <th>CID</th>
       <th>Paid</th>
    <th>Pending</th>
    <th>Plat</th>
    <th>Days</th>
     <th>Mess Type</th>
    <th>Mobile</th>
    <th>Last 4 Digits</th>
    <th>Start Date</th>
    <th>Old  Date</th>
</tr>

<?php foreach ($customers as $c): ?>
<tr>
    <td><?= $c['name'] ?></td>
    <td><?= $c['cid'] ?></td>
    <td><?= $c['paid'] ?></td>
    <td><?= $c['panding'] ?></td>
    <td><?= $c['plus'] ?></td>
    <td><?= $c['Days'] ?></td>
    <td><?= $c['m_type'] ?></td>
    <td><?= $c['moblie'] ?></td>
    <td><?= $c['mob'] ?></td>
    <td><?= $c['s_date'] ?></td>
    <td><?= $c['old_s_date'] ?></td>
</tr>
<?php endforeach; ?>
</table>
<?php else: ?>
<p style="text-align:center;font-weight:bold;">No customers found</p>
<?php endif; ?>

</div>
</body>
</html>
