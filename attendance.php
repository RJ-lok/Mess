<?php
require_once "auth.php";

/* ======= DB CONNECTION ======= */
$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";
$msgClass = "msg";

/* ======= FORM SUBMISSION ======= */
if (isset($_POST['submit'])) {

    $cids  = trim($_POST['cids']);
    $date  = $_POST['date'];
    $shift = $_POST['daynight'];

    /* ===== SHIFT VALIDATION (SECURITY FIX) ===== */
    if (!in_array($shift, ['day', 'night'])) {
        die("Invalid shift selected.");
    }

    $cidArray = preg_split('/\s+/', $cids);

    foreach ($cidArray as $cid) {

        $cid = intval($cid);
        if ($cid <= 0) continue;

        /* ===== VALIDATE CID ===== */
        $checkCustomer = $conn->prepare("SELECT cid FROM customer WHERE cid=? LIMIT 1");
        $checkCustomer->bind_param("i", $cid);
        $checkCustomer->execute();
        $resCustomer = $checkCustomer->get_result();

        if ($resCustomer->num_rows === 0) {
            $msg .= "⚠ CID $cid not found!<br>";
            continue;
        }

        /* ===== CHECK IF ENTRY EXISTS ===== */
        $check = $conn->prepare("SELECT day, night FROM entry WHERE cid=? AND date=? LIMIT 1");
        $check->bind_param("is", $cid, $date);
        $check->execute();
        $res = $check->get_result();

        $alreadyMarked = false;
        $isNewDay = false;   // For Days count

        if ($res->num_rows > 0) {

            $row = $res->fetch_assoc();

            // If both shifts were NULL before → new day attendance
            if (empty($row['day']) && empty($row['night'])) {
                $isNewDay = true;
            }

            // If same shift already marked
            if ($row[$shift] === 'Y') {
                $alreadyMarked = true;
            } else {
                // Update shift
                $upd = $conn->prepare("UPDATE entry SET $shift='Y' WHERE cid=? AND date=?");
                $upd->bind_param("is", $cid, $date);
                $upd->execute();
            }

        } else {

            // No entry exists → completely new day
            $isNewDay = true;

            $dayVal   = $shift === 'day'   ? 'Y' : NULL;
            $nightVal = $shift === 'night' ? 'Y' : NULL;

            $ins = $conn->prepare(
                "INSERT INTO entry (date, day, night, cid) VALUES (?, ?, ?, ?)"
            );
            $ins->bind_param("sssi", $date, $dayVal, $nightVal, $cid);
            $ins->execute();
        }

        /* ===== UPDATE PLUS (ONLY IF NEW SHIFT MARKED) ===== */
        if (!$alreadyMarked) {
            $conn->query("UPDATE customer SET plus = plus + 1 WHERE cid=$cid");
        }

        /* ===== UPDATE DAYS (ONLY FIRST ATTENDANCE OF DATE) ===== */
        if ($isNewDay) {
            $conn->query("UPDATE customer SET Days = Days + 1 WHERE cid=$cid");
        }
        $msg .= "✅ Attendance updated for CID $cid!<br>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Attendance Entry</title>

<style>
body{
    font-family:'Segoe UI',Tahoma;
    background:linear-gradient(135deg,#74ebd5,#ACB6E5);
    min-height:100vh;
    margin:0;
    display:flex;
    justify-content:center;
    align-items:center;
}
.form-box{
    background:rgba(255,255,255,0.18);
    backdrop-filter:blur(16px);
    padding:45px 40px;
    border-radius:22px;
    width:100%;
    max-width:520px;
    box-shadow:0 25px 45px rgba(0,0,0,.25);
    position:relative;
}
.home-btn{
    position:absolute;
    top:18px;
    right:20px;
    padding:8px 18px;
    background:linear-gradient(135deg,#28a745,#20c997);
    color:#fff;
    border-radius:20px;
    font-weight:700;
    text-decoration:none;
    font-size:14px;
}
h2{
    text-align:center;
    margin-bottom:35px;
    font-size:32px;
}
input,select{
    width:100%;
    padding:16px;
    margin-bottom:22px;
    font-size:20px;
    border-radius:14px;
    border:none;
}
input[type=submit]{
    background:linear-gradient(135deg,#007bff,#0056b3);
    color:#fff;
    font-weight:700;
    cursor:pointer;
}
.msg{
    margin-top:20px;
    padding:16px;
    text-align:center;
    font-weight:700;
    border-radius:14px;
    background:linear-gradient(135deg,#28a745,#218838);
    color:white;
}
</style>
</head>

<body>
<div class="form-box">
    <a href="dashboard.php" class="home-btn">🏠 Home</a>

    <form method="post">
        <h2>Enter Attendance</h2>

        <input type="text" name="cids" placeholder="Enter CIDs (e.g. 1 2 3 4)" required>
        <input type="date" name="date" value="<?= date('Y-m-d') ?>" required>

        <select name="daynight" required>
            <option value="day">Day</option>
            <option value="night">Night</option>
        </select>

        <input type="submit" name="submit" value="Submit">

        <?php if($msg): ?>
            <div class="<?= $msgClass ?>"><?= $msg ?></div>
        <?php endif; ?>
    </form>
</div>
</body>
</html>
