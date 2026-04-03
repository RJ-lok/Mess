<?php
require_once "auth.php";

ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ================= DB CONNECTION ================= */
$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";
$msg_type = "";

/* ================= BULK UPDATE ================= */
if (isset($_POST['bulk_update'])) {

    if (!empty($_POST['update_row']) && !empty($_POST['cid'])) {

        $conn->begin_transaction();

        try {

            $cids    = $_POST['cid'];
            $paid    = $_POST['paid'];
            $pending = $_POST['panding'];
            $plus    = $_POST['plus'];
             $absent   = $_POST['absent'];
            $days    = $_POST['days'];
            $s_date  = $_POST['s_date'];
            $checked = $_POST['update_row'];

           $stmt = $conn->prepare("
    UPDATE customer 
    SET 
        paid=?,
        panding=?,
        plus=?,
        absent=?,
        Days=?,
        old_s_date = s_date,
        s_date=?
    WHERE cid=?
");

            if (!$stmt) {
                throw new Exception("Prepare failed");
            }

            $updatedCount = 0;

            for ($i = 0; $i < count($cids); $i++) {

                if (in_array($cids[$i], $checked)) {

                    $stmt->bind_param(
                        "iiiiisi",
                        $paid[$i],
                        $pending[$i],
                        $plus[$i],
                        $absent[$i],
                        $days[$i],
                        $s_date[$i],
                        $cids[$i]
                    );

                    $stmt->execute();
                    $updatedCount++;
                }
            }

            $stmt->close();
            $conn->commit();

            $msg = "✅ Successfully updated $updatedCount record(s).";
            $msg_type = "success";

        } catch (Exception $e) {
            $conn->rollback();
            $msg = "❌ Update Failed!";
            $msg_type = "error";
        }

    } else {
        $msg = "⚠ Please select at least one row.";
        $msg_type = "warning";
    }
}
/* ================= FILTER ================= */

$where = [];
$selectedTypes = $_POST['mess_type'] ?? [];
$filterCID = $_POST['filter_cid'] ?? '';

if (!empty($filterCID)) {
    $safeCID = (int)$filterCID;
    $where[] = "cid = $safeCID";
}

if (!empty($selectedTypes)) {

    $conditions = [];

    foreach ($selectedTypes as $type) {

        if ($type === 'LD') {
            // Two time mess → Days should match total days of s_date month
            $conditions[] = "
            (m_type LIKE '%L%D%' OR m_type LIKE '%D%L%')
            AND Days >= DAY(LAST_DAY(s_date))
            ";
        } 
        elseif ($type === 'L' || $type === 'D') {
            // One time mess → plus count should match month days
            $safeType = $conn->real_escape_string($type);
            $conditions[] = "
            m_type = '$safeType'
            AND plus >= DAY(LAST_DAY(s_date))
            ";
        }
    }

    if (!empty($conditions)) {
        $where[] = "(" . implode(" OR ", $conditions) . ")";
    }
}
$filter_month = $_POST['filter_month'] ?? '';

if (!empty($filter_month)) {

    $year  = date('Y', strtotime($filter_month));
    $month = date('m', strtotime($filter_month));

    $where[] = "YEAR(s_date) = '$year' AND MONTH(s_date) = '$month'";
}
/* ================= QUERY ================= */
$query = "SELECT cid, paid, panding, plus, absent, Days, m_type, s_date 
FROM customer";

if (!empty($where)) {
    $query .= " WHERE " . implode(" AND ", $where);
}

$query .= " ORDER BY cid";

$result = $conn->query($query);
$customers = [];

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $customers[] = $row;
    }
}

$conn->close();
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Mess Admin Panel</title>

<style>
/* ================= GLOBAL ================= */
*{
    margin:0;
    padding:0;
    box-sizing:border-box;
}

body{
    background-size: 400% 400%;
}
/* ================= CONTAINER ================= */

/* ================= CARD ================= */
.card{
    background: rgba(255,255,255,0.95);
    backdrop-filter: blur(10px);
    padding:35px;
    border-radius:20px;
    box-shadow: 0 15px 40px rgba(0,0,0,0.2);
    margin-bottom:40px;
    transition:0.3s;
}



/* ================= FILTER ================= */
.filter{
    display:flex;
    align-items:center;
    gap:40px;
    flex-wrap:wrap;
}

.filter label{
    font-size:22px;
    font-weight:700;
    cursor:pointer;
}

.filter input[type=checkbox]{
    transform:scale(1.7);
    margin-right:10px;
    cursor:pointer;
}

/* ================= TABLE ================= */
table{
    width:100%;
    border-collapse:collapse;
    border-radius:15px;
    overflow:hidden;
}

th{
    background: linear-gradient(45deg,#141e30,#243b55);
    color:white;
    padding:18px;
    font-size:22px;
    text-transform:uppercase;
    letter-spacing:1px;
}

td{
    padding:16px;
    border-bottom:1px solid #e0e0e0;
    text-align:center;
    font-size:20px;
}

tr{
    transition:0.2s;
}



/* ================= INPUT FIELDS ================= */
input[type=number],
input[type=date]{
    width:140px;
    padding:10px;
    border-radius:10px;
    border:1px solid #ccc;
    text-align:center;
    font-size:18px;
    transition:0.3s;
}

input[type=number]:focus,
input[type=date]:focus{
    border:1px solid #007bff;
    box-shadow:0 0 8px rgba(0,123,255,0.4);
    outline:none;
}

/* ================= CHECKBOX ================= */
.rowCheck,
#selectAll{
    transform:scale(1.7);
    cursor:pointer;
}

/* ================= BUTTONS ================= */
.btn{
    padding:14px 40px;
    border:none;
    border-radius:50px;
    font-weight:bold;
    cursor:pointer;
    font-size:20px;
    transition:0.3s;
    letter-spacing:1px;
}

.btn-primary{
    background:linear-gradient(45deg,#007bff,#00c6ff);
    color:white;
}

.btn-success{
    background:linear-gradient(45deg,#11998e,#38ef7d);
    color:white;
}

.btn:hover{
    transform:translateY(-3px);
    box-shadow:0 10px 20px rgba(0,0,0,0.2);
}

/* ================= MESSAGE ALERT ================= */
.alert{
    padding:20px;
    border-radius:12px;
    margin-bottom:25px;
    font-weight:bold;
    font-size:20px;
    box-shadow:0 8px 20px rgba(0,0,0,0.15);
}

.success{
    background:linear-gradient(45deg,#56ab2f,#a8e063);
    color:#fff;
}

.error{
    background:linear-gradient(45deg,#ff416c,#ff4b2b);
    color:#fff;
}

.warning{
    background:linear-gradient(45deg,#f7971e,#ffd200);
    color:#fff;
}

/* ================= NO DATA ================= */
.no-data{
    text-align:center;
    font-size:32px;
    font-weight:700;
    color:#fff;
    padding:80px 20px;
    background:linear-gradient(45deg,#ff512f,#dd2476);
    border-radius:20px;
    letter-spacing:2px;
}

/* ================= RESPONSIVE ================= */
@media(max-width:1200px){
    table{
        font-size:16px;
    }
    input[type=number],
    input[type=date]{
        width:100px;
    }
}

@media(max-width:768px){
    .filter{
        flex-direction:column;
        align-items:flex-start;
    }
}

</style>
</head>

<body>

<div class="container">

<?php if($msg): ?>
<div class="alert <?= $msg_type ?>">
<?= $msg ?>
</div>
<?php endif; ?>

<!-- FILTER -->
<div class="card">
<form method="post" class="filter">
<label>
CID:
<input type="number" name="filter_cid" 
value="<?= $_POST['filter_cid'] ?? '' ?>" 
style="width:120px; padding:8px; border-radius:8px;">
</label>
<label>
<input type="checkbox" name="mess_type[]" value="L"
<?= in_array('L',$selectedTypes)?'checked':'' ?>> Lunch
</label>

<label>
<input type="checkbox" name="mess_type[]" value="D"
<?= in_array('D',$selectedTypes)?'checked':'' ?>> Dinner
</label>

<label>
<input type="checkbox" name="mess_type[]" value="LD"
<?= in_array('LD',$selectedTypes)?'checked':'' ?>> L & D
</label>
<label>
Month:
<input type="month" name="filter_month"
value="<?= $_POST['filter_month'] ?? '' ?>"
style="padding:8px;border-radius:8px;">
</label>
<button class="btn btn-primary">Apply</button>
<button type="button" class="btn btn-primary" 
onclick="window.location.href='dashboard.php'">
 Home
</button>
</form>
</div>

<!-- TABLE -->
<?php if(!empty($customers)): ?>
<div class="card">

<form method="post">

<?php
foreach ($selectedTypes as $type) {
    echo '<input type="hidden" name="mess_type[]" value="'.$type.'">';
}
?>

<table>
<tr>
<th><input type="checkbox" id="selectAll"></th>
<th>CID</th>
<th>Paid</th>
<th>Pending</th>
<th>Plus</th>
<th>Absent</th>
<th>Days</th>
<th>Mess</th>
<th>S_Date</th>
</tr>

<?php foreach($customers as $c): ?>
<tr>

<td>
<input type="checkbox" name="update_row[]" 
value="<?= $c['cid'] ?>" class="rowCheck">
</td>

<td>
<?= $c['cid'] ?>
<input type="hidden" name="cid[]" value="<?= $c['cid'] ?>">
</td>

<td><input type="number"  name="paid[]" value="<?= $c['paid'] ?>"></td>
<td><input type="number"  name="panding[]" value="<?= $c['panding'] ?>"></td>
<td><input type="number" name="plus[]" value="<?= $c['plus'] ?>"></td>
<td><input type="number" name="absent[]" value="<?= $c['absent'] ?>"></td>
<td><input type="number" name="days[]" value="<?= $c['Days'] ?>"></td>
<td><?= $c['m_type'] ?></td>
<td><input type="date" name="s_date[]" value="<?= $c['s_date'] ?>"></td>

</tr>
<?php endforeach; ?>

</table>

<br>
<center>
<button type="submit" name="bulk_update" class="btn btn-success">
Update Selected
</button>
</center>

</form>
</div>
<?php else: ?>
<div class="card no-data">
    No Customers Found
</div>
<?php endif; ?>


</div>

<script>
document.getElementById("selectAll").addEventListener("change", function() {
    let checkboxes = document.querySelectorAll(".rowCheck");
    for(let cb of checkboxes){
        cb.checked = this.checked;
    }
});
</script>

</body>
</html>
