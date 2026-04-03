

<?php
require_once "auth.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

/* ================= DATABASE CONNECTION ================= */
$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$add_msg = "";
$add_color = "";

/* ================= ADD NEW CUSTOMER ================= */
if (isset($_POST['add_submit'])) {

    $cid        = trim($_POST['add_cid']);         // Manual ID
    $name       = trim($_POST['add_name']);
    $moblie     = trim($_POST['add_moblie']);
    $paid       = trim($_POST['add_paid']);
    $panding    = trim($_POST['add_panding']);
    $m_type     = trim($_POST['add_m_type']);
    $s_date     = trim($_POST['add_s_date']);
    $plus       = trim($_POST['add_plus']);
    $Days       = trim($_POST['add_last_days']);   // Changed here
    $absent     = 0;

    // last 4 digits of mobile
    $mob = substr($moblie, -4);

    /* ========= VALIDATION ========= */
    if (!ctype_digit($cid)) {
        $add_msg = "Customer ID must be numeric!";
        $add_color = "red";

    } elseif (!preg_match("/^[a-zA-Z ]+$/", $name)) {
        $add_msg = "Name must contain letters only!";
        $add_color = "red";

    } elseif (!preg_match("/^\d{10}$/", $moblie)) {
        $add_msg = "Mobile must be exactly 10 digits!";
        $add_color = "red";

    } elseif (!ctype_digit($paid) || !ctype_digit($panding) || !ctype_digit($plus) || !ctype_digit($Days)) {
        $add_msg = "Paid, Pending, Plus and  Days must be numbers!";
        $add_color = "red";

    } elseif (!in_array($m_type, ['D','L','D&L'])) {
        $add_msg = "Invalid Mess Type!";
        $add_color = "red";

    } elseif (empty($s_date)) {
        $add_msg = "Start date required!";
        $add_color = "red";

    } else {

        // cast numeric values
        $cid        = (int)$cid;
        $paid       = (int)$paid;
        $panding    = (int)$panding;
        $plus       = (int)$plus;
        $Days        = (int)$Days;
        $absent     = (int)$absent;

        /* ========= INSERT ========= */
        $stmt = $conn->prepare("
            INSERT INTO customer
            (cid, name, moblie, mob, paid, panding, plus,Days, m_type, s_date, absent, old_s_date)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        if ($stmt) {

            $stmt->bind_param(
                "isssiiiissis",
                $cid,
                $name,
                $moblie,
                $mob,
                $paid,
                $panding,
                $plus,
                $Days,
                $m_type,
                $s_date,
                $absent,
                $s_date
            );

            if ($stmt->execute()) {
                $add_msg = "Customer added successfully!";
                $add_color = "green";
            } else {
                $add_msg = "Insert failed: " . $stmt->error;
                $add_color = "red";
            }

            $stmt->close();
        } else {
            $add_msg = "Prepare failed: " . $conn->error;
            $add_color = "red";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Customer</title>

<style>
* { box-sizing: border-box; }
body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background:linear-gradient(135deg,#6f2b82,#f8b500);
    font-family:Poppins,sans-serif;
}
.container{
    background:#fff;
    padding:30px;
    width:600px;
    border-radius:16px;
    box-shadow:0 12px 30px rgba(0,0,0,0.25);
}
h2{ text-align:center; margin-bottom:20px; }
a.home-link{
    float:right;
    margin-top:-45px;
    background:#007bff;
    color:#fff;
    padding:6px 12px;
    border-radius:8px;
    text-decoration:none;
}
form{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:15px 20px;
}
input, select, button{
    padding:10px;
    font-size:15px;
    border-radius:8px;
    border:1px solid #ccc;
}
input[readonly]{ background:#eee; }
button{
    grid-column:span 2;
    background:linear-gradient(90deg,#007bff,#ff69b4);
    color:#fff;
    font-weight:bold;
    cursor:pointer;
}
.msg{
    grid-column:span 2;
    text-align:center;
    font-weight:bold;
    margin-top:10px;
}
</style>
</head>

<body>

<div class="container">
<h2>Add New Customer</h2>
<a href="dashboard.php" class="home-link">Home</a>

<form method="post" autocomplete="off">

    <input type="number" name="add_cid" placeholder="Customer ID" required>
    <input type="text" name="add_name" placeholder="Name" required>

    <input type="text" name="add_moblie" placeholder="Mobile (10 digits)" required>
    <input type="text" name="add_paid" placeholder="Paid Amount" required>

    <input type="text" name="add_panding" placeholder="Pending Amount" required>
    <input type="number" name="add_last_days" placeholder="Enter  Days" required>

    <select name="add_m_type" required>
        <option value="">Mess Type</option>
        <option value="D">D</option>
        <option value="L">L</option>
        <option value="D&L">D & L</option>
    </select>

    <input type="date" name="add_s_date" required>

    <input type="text" placeholder="Last 4 digits auto" readonly>
    <input type="number" name="add_plus" placeholder="Enter Plates" required>

    <button type="submit" name="add_submit">Add Customer</button>

    <?php if($add_msg): ?>
        <div class="msg" style="color:<?= $add_color ?>"><?= $add_msg ?></div>
    <?php endif; ?>

</form>
</div>

<script>
const last4 = document.querySelector('input[readonly]');

document.querySelectorAll('input').forEach(inp=>{
    inp.addEventListener('input',()=>{
        if(['add_paid','add_panding','add_plus','add_last_days','add_cid'].includes(inp.name)){
            inp.value = inp.value.replace(/\D/g,'');
        }
        if(inp.name === 'add_moblie'){
            inp.value = inp.value.replace(/\D/g,'').slice(0,10);
            last4.value = inp.value.slice(-4);
        }
        if(inp.name === 'add_name'){
            inp.value = inp.value.replace(/[^a-zA-Z ]/g,'');
        }
    });
});
</script>

</body>
</html>
