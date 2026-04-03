<?php
require_once "auth.php";
ini_set('display_errors',1);
error_reporting(E_ALL);

$conn = new mysqli("localhost","root","","mess");
if ($conn->connect_error) die("DB Error");

$customer = null;
$confirm  = false;

/* ================= DELETE ================= */
if(isset($_POST['delete'])){
    $cid = (int)$_POST['cid'];
    $stmt = $conn->prepare("DELETE FROM customer WHERE cid=?");
    $stmt->bind_param("i",$cid);
    $stmt->execute();
    header("Location: delete.php?msg=deleted");
    exit;
}

/* ================= FETCH (POST → REDIRECT) ================= */
if(isset($_POST['fetch'])){
    $cid = (int)$_POST['cid'];
    header("Location: delete.php?cid=".$cid);
    exit;
}

/* ================= CONFIRM (POST → REDIRECT) ================= */
if(isset($_POST['confirm'])){
    $cid = (int)$_POST['cid'];
    header("Location: delete.php?cid=".$cid."&confirm=1");
    exit;
}

/* ================= LOAD DATA USING GET ================= */
if(isset($_GET['cid'])){
    $cid = (int)$_GET['cid'];
    $stmt = $conn->prepare("SELECT cid,name,paid,panding FROM customer WHERE cid=?");
    $stmt->bind_param("i",$cid);
    $stmt->execute();
    $res = $stmt->get_result();
    if($res->num_rows==1){
        $customer = $res->fetch_assoc();
    }
}

if(isset($_GET['confirm'])){
    $confirm = true;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Delete Customer</title>
<style>
body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:Segoe UI,sans-serif;
     background: #0b7285;
}
.card{
    background:#fff;
    width:420px;
    padding:30px;
    border-radius:18px;
    box-shadow:0 20px 40px rgba(0,0,0,.3);
}
h2{text-align:center}
input,button{
    width:100%;
    padding:12px;
    margin:10px 0;
    border-radius:10px;
    border:1px solid #ccc;
    font-size:16px;
}
button{
    border:none;
    background:#dc3545;
    color:#fff;
    font-weight:600;
    cursor:pointer;
}
a{
    text-decoration:none;
}
.home-btn{
    display:block;
    text-align:center;
    background:#007bff;
    color:#fff;
    padding:12px;
    border-radius:10px;
    margin-top:10px;
}
.warn{
    background:#fff3cd;
    padding:10px;
    border-radius:10px;
    text-align:center;
}
.success{
    background:#d4edda;
    padding:10px;
    border-radius:10px;
    text-align:center;
    margin-bottom:10px;
}
table{width:100%;border-collapse:collapse}
td,th{padding:8px;border-bottom:1px solid #ddd}
</style>
</head>
<body>

<div class="card">
<h2>DELETE CUSTOMER</h2>

<?php if(isset($_GET['msg']) && $_GET['msg']=="deleted"): ?>
<div class="success">Customer Deleted Successfully</div>
<?php endif; ?>

<?php if(!$customer && !$confirm): ?>

<form method="post">
<input type="number" name="cid" placeholder="Enter CID" required>
<button name="fetch">Fetch</button>
</form>
<a href="dashboard.php" class="home-btn">Go to Home Page</a>

<?php elseif($customer && !$confirm): ?>

<table>
<tr><th>Name</th><th>Paid</th><th>Pending</th></tr>
<tr>
<td><?= htmlspecialchars($customer['name']) ?></td>
<td><?= htmlspecialchars($customer['paid']) ?></td>
<td><?= htmlspecialchars($customer['panding']) ?></td>
</tr>
</table>

<form method="post">
<input type="hidden" name="cid" value="<?= $customer['cid'] ?>">
<button name="confirm">Delete</button>
</form>

<a href="dashboard.php" class="home-btn">Go to Home Page</a>

<?php elseif($customer && $confirm): ?>

<div class="warn">⚠ Confirm permanent delete</div>

<form method="post">
<input type="hidden" name="cid" value="<?= $customer['cid'] ?>">
<button name="delete">YES DELETE</button>
</form>

<a href="delete.php?cid=<?= $customer['cid'] ?>" class="home-btn" style="background:#6c757d;">
Cancel
</a>

<?php else: ?>

<div class="warn">Customer Not Found</div>
<a href="dashboard.php" class="home-btn">Go to Home Page</a>

<?php endif; ?>

</div>
</body>
</html>
