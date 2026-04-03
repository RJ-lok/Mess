<?php
require_once "auth.php";
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost","root","","mess");
if ($conn->connect_error) die("DB Connection Failed");

$customer = null;
$error = "";

/* ========== FETCH CUSTOMER ========== */
if (isset($_POST['fetch'])) {

    $cid = (int)$_POST['cid'];

    $stmt = $conn->prepare(
        "SELECT cid,name,moblie,mob,paid,panding,m_type FROM customer WHERE cid=?"
    );
    $stmt->bind_param("i",$cid);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 1) {
        $customer = $res->fetch_assoc();
    } else {
        $error = "Customer Not Found!";
    }
    $stmt->close();
}

/* ========== UPDATE CUSTOMER + PAYMENT ========== */
if (isset($_POST['update'])) {

    $cid         = (int)$_POST['update_cid'];
    $name        = trim($_POST['update_name']);
    $mobile      = trim($_POST['update_mobile']);
    $paid        = (int)$_POST['update_paid'];
    $panding     = (int)$_POST['update_panding'];
    $m_type      = trim($_POST['update_mtype']);

    // 🔐 VALIDATION
    if (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $error = "Mobile must be 10 digits!";
    }
    elseif ($paid < 0 || $panding < 0) {
        $error = "Amount cannot be negative!";
    }
    else {

        $conn->begin_transaction();

        try {

            // 🔹 Fetch old payment
            $stmt = $conn->prepare("SELECT paid, panding FROM customer WHERE cid=?");
            $stmt->bind_param("i",$cid);
            $stmt->execute();
            $res = $stmt->get_result();
            $old = $res->fetch_assoc();
            $stmt->close();

            if(!$old){
                throw new Exception("Customer not found.");
            }

            $old_paid    = $old['paid'];
            $old_panding = $old['panding'];

          // 🔹 Insert only if payment changed
if ($old_paid != $paid || $old_panding != $panding) {

    $stmt = $conn->prepare("
        INSERT INTO old_pay (cid, old_paid, old_panding, new_paid, new_panding)
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param("iiiii", $cid, $old_paid, $old_panding, $paid, $panding);
    $stmt->execute();
    $stmt->close();
}

            // 🔹 Auto last 4 digit
            $mob_last4 = substr($mobile, -4);

            // 🔹 Update main table
            $stmt = $conn->prepare("
                UPDATE customer 
                SET name=?, moblie=?, mob=?, paid=?, panding=?, m_type=? 
                WHERE cid=?
            ");
            $stmt->bind_param(
                "sssissi",
                $name,
                $mobile,
                $mob_last4,
                $paid,
                $panding,
                $m_type,
                $cid
            );
            $stmt->execute();
            $stmt->close();

            $conn->commit();

            header("Location: payment.php?success=1");
            exit;

        } catch (Exception $e) {
            $conn->rollback();
            $error = "Transaction Failed!";
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment Update</title>

<style>
body{
    margin:0;
    font-family:'Segoe UI', sans-serif;
    background:linear-gradient(135deg,#8e44ad,#f39c12);
    min-height:100vh;
    display:flex;
    align-items:center;
    justify-content:center;
}

.box{
    background:#ffffff;
    width:750px;
    max-width:95%;
    padding:45px;
    border-radius:18px;
    box-shadow:0 25px 60px rgba(0,0,0,0.25);
    position:relative;
}

h2{
    text-align:center;
    margin-bottom:30px;
    font-weight:800;
    color:#333;
    
}

/* FORM LAYOUT */
.form-grid{
    display:grid;
    grid-template-columns:1fr 1fr;
    gap:20px 30px;
}

.form-group{
    display:flex;
    flex-direction:column;
}

label{
    font-weight:800;
    margin-bottom:6px;
    font-size:24px;
}

input, select{
    padding:12px 14px;
    border-radius:8px;
    border:1px solid #ccc;
    font-size:24px;
    background:#f2f2f2;
    outline:none;
    transition:0.3s;
}

input:focus, select:focus{
    border-color:#8e44ad;
    background:#fff;
    box-shadow:0 0 5px rgba(142,68,173,0.3);
}

/* Bottom Row */
.bottom-row{
    margin-top:25px;
    display:flex;
    justify-content:space-between;
    align-items:flex-end;
}

.bottom-row .form-group{
    width:45%;
}

button{
    padding:12px 30px;
    border:none;
    border-radius:8px;
    font-size:24px;
    font-weight:800;
    cursor:pointer;
    background:linear-gradient(to right,#8e44ad,#e84393);
    color:#fff;
    transition:0.3s;
}

button:hover{
    transform:translateY(-2px);
    box-shadow:0 10px 20px rgba(0,0,0,0.2);
}

/* Home Button */
.home-btn{
    position:absolute;
    top:25px;
    right:30px;
    background:#0d6efd;
    padding:8px 18px;
    border-radius:8px;
    color:#fff;
    text-decoration:none;
    font-size:24px;
    font-weight:800;
    transition:0.3s;
}

.home-btn:hover{
    background:#0b5ed7;
}

/* Table */
table{
    width:100%;
    margin-bottom:25px;
    border-collapse:collapse;
    background:#fff;
    border-radius:10px;
    overflow:hidden;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

th{
    background:#8e44ad;
    color:#fff;
    padding:12px;
    font-size:24px;
}

td{
    padding:12px;
    border-bottom:1px solid #eee;
    text-align:center;
    font-size:24px;
}

tr:last-child td{
    border-bottom:none;
}

/* Responsive */
@media(max-width:650px){
    .form-grid{
        grid-template-columns:1fr;
    }
    .bottom-row{
        flex-direction:column;
        gap:15px;
    }
    .bottom-row .form-group{
        width:100%;
    }
}
</style>
</head>

<body>
<div class="box">
<a href="dashboard.php" class="home-btn">Home</a>

<?php if(!$customer): ?>

<h2>Enter CID</h2>
<form method="post" style="display:flex; flex-direction:column; align-items:center;">
    <input type="number" name="cid" placeholder="Customer ID" style="width:300px;" required autofocus>
    <br>
    <button name="fetch">Fetch</button>
    <?php if($error): ?>
<p style="color:red; text-align:center; font-weight:800; font-size:24px;">
    <?= $error ?>
</p>
<?php endif; ?>

</form>

<?php else: ?>

<h2>Update Customer</h2>

<?php if($error): ?>
<p style="color:red"><?= $error ?></p>
<?php endif; ?>

<table>
<tr><th>Name</th><th>Paid</th><th>Pending</th></tr>
<tr>
<td><?= htmlspecialchars($customer['name']) ?></td>
<td><?= $customer['paid'] ?></td>
<td><?= $customer['panding'] ?></td>
</tr>
</table>

<form method="post">
<input type="hidden" name="update_cid" value="<?= $customer['cid'] ?>">

<div class="form-grid">

    <div class="form-group">
        <label>Name</label>
        <input type="text" name="update_name"
        value="<?= htmlspecialchars($customer['name']) ?>" required>
    </div>

    <div class="form-group">
        <label>Mobile</label>
        <input type="text" name="update_mobile"
pattern="[0-9]{10}" maxlength="10"
        value="<?= htmlspecialchars($customer['moblie']) ?>" required>
    </div>

    <div class="form-group">
        <label>Paid</label>
        <input type="number" name="update_paid"
        value="<?= $customer['paid'] ?>" required>
    </div>

    <div class="form-group">
        <label>Pending</label>
        <input type="number" name="update_panding"
        value="<?= $customer['panding'] ?>" required>
    </div>

</div>

<div class="bottom-row">

    <div class="form-group">
        <label>Mess Type</label>
        <select name="update_mtype" required>
            <option value="D" <?= ($customer['m_type']=='D')?'selected':'' ?>>D</option>
            <option value="L" <?= ($customer['m_type']=='L')?'selected':'' ?>>L</option>
            <option value="D&L" <?= ($customer['m_type']=='D&L')?'selected':'' ?>>D & L</option>
        </select>
    </div>

    <div>
        <button name="update">Update</button>
    </div>

</div>

</form>

<?php endif; ?>

</div>
</body>
</html>
