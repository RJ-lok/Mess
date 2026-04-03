<?php

ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) die("DB Connection Failed");
$cid = "";
$payments = [];

$error = ""; 
if(isset($_GET['delete'])){
    $id = (int)$_GET['delete'];

    $stmt = $conn->prepare("DELETE FROM old_pay WHERE id=?");
    $stmt->bind_param("i",$id);
    $stmt->execute();

    header("Location: ".$_SERVER['PHP_SELF']);
    exit;
}
if(isset($_POST['fetch'])){
    $cid = (int)$_POST['cid'];

    $stmt = $conn->prepare("
        SELECT id,cid,old_paid,old_panding,new_paid,new_panding,changed_at
        FROM old_pay
        WHERE cid=?
        ORDER BY id DESC
    ");
    $stmt->bind_param("i",$cid);
}
else{

    $stmt = $conn->prepare("
        SELECT id,cid,old_paid,old_panding,new_paid,new_panding,changed_at
        FROM old_pay
        ORDER BY id DESC
    ");
}

$stmt->execute();
$res = $stmt->get_result();
$payments = $res->fetch_all(MYSQLI_ASSOC);
$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<title> History</title>
<style>
body{
    font-family:'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0b7285, #0a5263);
    min-height:100vh;
    display:flex;
    flex-direction:column;
    align-items:center;
    justify-content:flex-start;
    margin:0;
}

.container{
    background:#fff;
    width:90%;
    border-radius:15px;
    padding:30px;
    box-shadow:0 15px 40px rgba(0,0,0,0.25);
}

h2{
    text-align:center;
    color:#333;
    margin-bottom:20px;
}

form{
    text-align:center;
    margin-bottom:30px;
}

input[type="number"]{
    padding:12px 15px;
    font-size:20px;
    border-radius:8px;
    border:1px solid #ccc;
    width:200px;
    outline:none;
}

input[type="number"]:focus{
    border-color:#8e44ad;
    box-shadow:0 0 5px rgba(142,68,173,0.3);
}

table{
    width:100%;
    border-collapse:collapse;
    margin-top:20px;
}

th, td{
    padding:12px;
    border:1px solid #ddd;
    text-align:center;
    font-size:20px;
}

th{
    background:#8e44ad;
    color:#fff;
}

tr:nth-child(even){
    background:#f2f2f2;
}

p.error{
    text-align:center;
    color:red;
    font-weight:800;
}
</style>
</head>
<body>

<div class="container">
<h2>Payment History</h2>

<form method="post">
    <input type="number" name="cid" placeholder="Enter CID" value="<?= htmlspecialchars($cid) ?>" min="1" required>
    <button name="fetch" style="display:none;">Fetch</button>
</form>

<?php if($error): ?>
<p class="error"><?= $error ?></p>
<?php endif; ?>

<?php if(!empty($payments)): ?>
<table>
<tr>
    <th>CID</th>
    <th>ID</th>
    <th>Old Paid</th>
    <th>Old Pending</th>
    <th>New Paid</th>
    <th>New Pending</th>
    <th>Date</th>
    <th>Delete</th>
</tr>
<?php foreach($payments as $p): ?>
<tr>
    <td><?= $p['cid'] ?></td>
        <td><?= $p['id'] ?></td>
    <td><?= $p['old_paid'] ?></td>
    <td><?= $p['old_panding'] ?></td>
    <td><?= $p['new_paid'] ?></td>
    <td><?= $p['new_panding'] ?></td>
<td><?= date("d-m-Y", strtotime($p['changed_at'])) ?></td>
    <td>
<a href="?delete=<?= $p['id'] ?>"
onclick="return confirm('Delete this record?')"
style="color:red;font-weight:bold;">
Delete
</a>
</td>
</tr>
<?php endforeach; ?>
</table>
<?php endif; ?>

</div>
</body>
</html>
