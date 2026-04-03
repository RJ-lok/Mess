<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ===== DB CONNECT ===== */
$conn = new mysqli("localhost","root","","mess");
if ($conn->connect_error) die("DB Connection Failed");
// ===== Auto delete leave if any entry exists in leave range =====
$delLeaveStmt = $conn->prepare("
    DELETE FROM customer_leave
    WHERE EXISTS (
        SELECT 1
        FROM entry e
        WHERE e.cid = customer_leave.cid
          AND e.date BETWEEN customer_leave.l_start AND customer_leave.l_end
    )
");
$delLeaveStmt->execute();
$delLeaveStmt->close();
/* ===== INPUT ===== */
$date  = $_POST['date'] ?? '';
$shift = $_POST['shift'] ?? '';

$eat = [];
$noteat = [];

/* ===== CHECK SUNDAY ===== */
$isSunday = false;
if (!empty($date) && date('l', strtotime($date)) == 'Sunday') {
    $isSunday = true;
}

/* =========================================================
   UPDATE ABSENT
========================================================= */
if (isset($_POST['update_absent']) && !empty($_POST['absent_cid'])) {

    foreach ($_POST['absent_cid'] as $cid) {

        $checkStmt = $conn->prepare("
            SELECT day, night 
            FROM absent 
            WHERE date = ? AND cid = ?
        ");
        $checkStmt->bind_param("si", $date, $cid);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
        $existing = $result->fetch_assoc();
        $checkStmt->close();

        $increase = false;

        if ($shift === 'day') {

            if (!$existing) {
                $stmt = $conn->prepare("
                    INSERT INTO absent (date, cid, day)
                    VALUES (?, ?, 'Y')
                ");
                $stmt->bind_param("si", $date, $cid);
                $stmt->execute();
                $stmt->close();
                $increase = true;

            } elseif ($existing['day'] !== 'Y') {

                $stmt = $conn->prepare("
                    UPDATE absent SET day='Y'
                    WHERE date=? AND cid=?
                ");
                $stmt->bind_param("si", $date, $cid);
                $stmt->execute();
                $stmt->close();
                $increase = true;
            }

        } else {

            if (!$existing) {
                $stmt = $conn->prepare("
                    INSERT INTO absent (date, cid, night)
                    VALUES (?, ?, 'Y')
                ");
                $stmt->bind_param("si", $date, $cid);
                $stmt->execute();
                $stmt->close();
                $increase = true;

            } elseif ($existing['night'] !== 'Y') {

                $stmt = $conn->prepare("
                    UPDATE absent SET night='Y'
                    WHERE date=? AND cid=?
                ");
                $stmt->bind_param("si", $date, $cid);
                $stmt->execute();
                $stmt->close();
                $increase = true;
            }
        }

        if ($increase) {
            $updateCustomer = $conn->prepare("
                UPDATE customer SET absent = absent + 1
                WHERE cid = ?
            ");
            $updateCustomer->bind_param("i", $cid);
            $updateCustomer->execute();
            $updateCustomer->close();
        }
    }

    echo "<script>alert('Absent Updated Successfully');</script>";
}

/* =========================================================
   FETCH DATA
========================================================= */
if (!empty($date) && !empty($shift)) {

    /* ===== EAT ===== */
    $eat_sql = "
        SELECT DISTINCT c.cid, c.name, c.m_type
        FROM entry e
        JOIN customer c ON c.cid = e.cid
        WHERE e.date = ?
        AND ".($shift=='day'?"e.day='Y'":"e.night='Y'")."
        ORDER BY c.cid ASC
    ";
    $stmt = $conn->prepare($eat_sql);
    $stmt->bind_param("s",$date);
    $stmt->execute();
    $res = $stmt->get_result();
    while($r = $res->fetch_assoc()) $eat[] = $r;
    $stmt->close();

    /* ===== NOT EAT (INCLUDING LEAVE IN YELLOW) ===== */
 if ($shift == 'day') {

    if ($isSunday) {
        $messCondition = "c.m_type IN ('L','D','D&L')";
    } else {
        $messCondition = "c.m_type IN ('L','D&L')";
    }

} else {
    $messCondition = "c.m_type IN ('D','D&L')";
}

        $noteat_sql = "
            SELECT 
                c.cid, 
                c.name, 
                c.m_type,
                CASE 
                    WHEN cl.cid IS NOT NULL THEN 1 
                    ELSE 0 
                END AS is_leave
            FROM customer c
            LEFT JOIN entry e 
                ON c.cid = e.cid 
                AND e.date = ?
                AND ".($shift=='day'?"e.day='Y'":"e.night='Y'")."
            LEFT JOIN customer_leave cl 
                ON c.cid = cl.cid 
                AND ? BETWEEN cl.l_start AND cl.l_end
            WHERE e.cid IS NULL
            AND $messCondition
         ORDER BY is_leave DESC, c.cid ASC
        ";

        $stmt = $conn->prepare($noteat_sql);
        $stmt->bind_param("ss",$date,$date);
        $stmt->execute();
        $res = $stmt->get_result();
        while($r = $res->fetch_assoc()) $noteat[] = $r;
        $stmt->close();
    }

?>

<!DOCTYPE html>
<html>
<head>
<title>Mess Attendance</title>

<style>
*{ box-sizing:border-box; }

body{
    margin:0;
    font-family:'Segoe UI',sans-serif;
    background:linear-gradient(135deg,#667eea,#764ba2);
    min-height:100vh;
    display:flex;
    justify-content:center;
    padding:20px 0;
}

.container{
    width:1500px;
    background:#fff;
    border-radius:18px;
    box-shadow:0 18px 45px rgba(0,0,0,.25);
    overflow:hidden;
}

.topbar{
    padding:20px;
    border-bottom:1px solid #ddd;
    display:flex;
    justify-content:space-between;
    align-items:center;
    flex-wrap:wrap;
}

.topbar form{
    display:flex;
    gap:12px;
    align-items:center;
}

input,select{
    padding:12px;
    font-size:17px;
    border-radius:10px;
    border:1px solid #bbb;
}

button{
    padding:12px 26px;
    border:none;
    border-radius:10px;
    background:#28a745;
    color:#fff;
    font-size:17px;
    font-weight:600;
    cursor:pointer;
}
button:hover{ background:#218838; }

.grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:25px;
    padding:25px;
}

.card{
    border-radius:16px;
    padding:10px;
    background:#fafafa;
    box-shadow:0 10px 22px rgba(0,0,0,.12);
}

.count{
    text-align:center;
    font-weight:800;
    margin-bottom:8px;
    font-size:26px;
}

.table-wrap{
    max-height:500px;
    overflow-y:auto;
    border-radius:12px;
    border:1px solid #e0e0e0;
}

table{
    width:100%;
    border-collapse:collapse;
}

th,td{
    padding:10px;
    border-bottom:1px solid #eee;
    text-align:center;
    font-size:18px;
}

th{
    background:#f1f1f1;
    position:sticky;
    top:0;
}

.badge{
    padding:6px 14px;
    border-radius:22px;
    color:#fff;
    font-weight:600;
}

.L{background:#28a745}
.D{background:#007bff}
.DL{background:#6f42c1}

.leave-row{
    background:#fff3cd !important;
    font-weight:600;
}

input[type="checkbox"]{
    width:22px;
    height:22px;
}

.nodata{
    padding:20px;
    color:#999;
    font-size:18px;
}
.nav-buttons{
    display:flex;
    gap:12px;
}

.nav-btn{
    padding:10px 18px;
    border-radius:10px;
    font-size:16px;
    font-weight:600;
    text-decoration:none;
    color:#fff;
    transition:0.3s;
}

.nav-btn.home{
    background:linear-gradient(to right,#ff416c,#ff4b2b);
}

.nav-btn.leave{
    background:linear-gradient(to right,#ffc107,#ff9800);
}

.nav-btn:hover{
    transform:scale(1.08);
}
</style>
</head>
<body>

<div class="container">
<div class="topbar">

    <form method="post">
        <input type="date" name="date" value="<?= htmlspecialchars($date) ?>" required>
        <select name="shift" required>
            <option value="">Select Shift</option>
            <option value="day" <?= $shift=='day'?'selected':'' ?>>Day</option>
            <option value="night" <?= $shift=='night'?'selected':'' ?>>Night</option>
        </select>
        <button name="check">Check</button>
    </form>

    <div class="nav-buttons">
        <a href="index.html" class="nav-btn home">🏠 Home</a>
    </div>

</div>
<?php if(isset($_POST['check']) || isset($_POST['update_absent'])): ?>
<div class="grid">

<!-- EAT -->
<div class="card">
<div class="count">✅ Total: <?= count($eat) ?></div>
<div class="table-wrap">
<table>
<tr><th>CID</th><th>Name</th><th>Mess Type</th></tr>
<?php foreach($eat as $r): ?>
<tr>
<td><?= $r['cid'] ?></td>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><span class="badge <?= $r['m_type']=='D&L'?'DL':$r['m_type'] ?>">
<?= $r['m_type'] ?></span></td>
</tr>
<?php endforeach; ?>
<?php if(count($eat)==0): ?>
<tr><td colspan="3" class="nodata">No Data</td></tr>
<?php endif; ?>
</table>
</div>
</div>

<!-- NOT EAT -->
<form method="post">
<input type="hidden" name="date" value="<?= htmlspecialchars($date) ?>">
<input type="hidden" name="shift" value="<?= htmlspecialchars($shift) ?>">

<div class="card">

<?php 
$realNotEat = array_filter($noteat,function($r){
    return $r['is_leave']==0;
});
?>

<div class="count">❌ Total: <?= count($realNotEat) ?></div>

<div class="table-wrap">
<table>
<tr><th>Select</th><th>CID</th><th>Name</th><th>Mess Type</th></tr>

<?php foreach($noteat as $r): ?>
<tr class="row-click <?= $r['is_leave']?'leave-row':'' ?>">
<td>
<?php if(!$r['is_leave']): ?>
<input type="checkbox" name="absent_cid[]" value="<?= $r['cid'] ?>">
<?php else: ?>
🟡
<?php endif; ?>
</td>
<td><?= $r['cid'] ?></td>
<td><?= htmlspecialchars($r['name']) ?></td>
<td><span class="badge <?= $r['m_type']=='D&L'?'DL':$r['m_type'] ?>">
<?= $r['m_type'] ?></span></td>
</tr>
<?php endforeach; ?>

<?php if(count($noteat)==0): ?>
<tr><td colspan="4" class="nodata">No Data</td></tr>
<?php endif; ?>

</table>
</div>

<div style="text-align:center;margin-top:10px;">
<button type="submit" name="update_absent">➕ Mark Absent (+1)</button>
</div>

</div>
</form>

</div>
<?php endif; ?>

</div>

<script>
document.addEventListener("DOMContentLoaded", function(){
    document.querySelectorAll(".row-click").forEach(row=>{
        row.addEventListener("click", function(e){
            if(e.target.type !== "checkbox"){
                let cb = this.querySelector("input[type='checkbox']");
                if(cb){
                    cb.checked = !cb.checked;
                }
            }
        });
    });
});
</script>

</body>
</html>