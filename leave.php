<?php
session_start();
ini_set('display_errors',1);
error_reporting(E_ALL);

$conn = new mysqli("localhost","root","","mess");
if($conn->connect_error) die("DB Connection Failed: " . $conn->connect_error);


/* ================= ADD LEAVE ================= */
if(isset($_POST['add_leave'])){

    $cid = filter_input(INPUT_POST,'cid',FILTER_VALIDATE_INT);
    $start_type = $_POST['start_type'] ?? '';
    $total_days = filter_input(INPUT_POST,'total_days',FILTER_VALIDATE_INT);

    if($cid===false || $cid<=0 || empty($start_type) || 
       $total_days===false || $total_days<1 || $total_days>30){

        $_SESSION['msg']="All fields required! (1-30 days only)";
        $_SESSION['type']="error";
        header("Location: leave.php");
        exit;
    }

    /* ===== CHECK CUSTOMER EXIST ===== */
    $checkCustomer = $conn->prepare("SELECT cid FROM customer WHERE cid=?");
    $checkCustomer->bind_param("i",$cid);
    $checkCustomer->execute();
    $checkCustomer->store_result();

    if($checkCustomer->num_rows==0){
        $_SESSION['msg']="Customer ID Not Found!";
        $_SESSION['type']="error";
        header("Location: leave.php");
        exit;
    }
    $checkCustomer->close();

    /* ===== START DATE ===== */
    $l_start = ($start_type=="today") 
        ? date("Y-m-d") 
        : date("Y-m-d",strtotime("+1 day"));

    /* ===== END DATE ===== */
    $l_end = date("Y-m-d",strtotime($l_start." +".($total_days-1)." days"));

    /* ===== OVERLAP CHECK ===== */
    $check = $conn->prepare("
        SELECT 1 FROM customer_leave
        WHERE cid=? AND NOT (l_end < ? OR l_start > ?)
    ");
    $check->bind_param("iss",$cid,$l_start,$l_end);
    $check->execute();
    $check->store_result();

    if($check->num_rows>0){
        $_SESSION['msg']="Leave overlaps with existing leave!";
        $_SESSION['type']="error";
        header("Location: leave.php");
        exit;
    }
    $check->close();

    /* ===== ENTRY CHECK (Block if entry exists in range) ===== */
    $entryCheck = $conn->prepare("
        SELECT 1 FROM entry
        WHERE cid=? AND date BETWEEN ? AND ?
        LIMIT 1
    ");
    $entryCheck->bind_param("iss",$cid,$l_start,$l_end);
    $entryCheck->execute();
    $entryCheck->store_result();

    if($entryCheck->num_rows>0){
        $_SESSION['msg']="Cannot add leave! Entry exists in selected date range.";
        $_SESSION['type']="error";
        header("Location: leave.php");
        exit;
    }
    $entryCheck->close();

    /* ===== INSERT LEAVE ===== */
    $stmt = $conn->prepare("
        INSERT INTO customer_leave (cid,l_start,l_end)
        VALUES (?,?,?)
    ");
    $stmt->bind_param("iss",$cid,$l_start,$l_end);
    $stmt->execute();
    $stmt->close();

    $_SESSION['msg']="Leave Added Successfully!";
    $_SESSION['type']="success";
    header("Location: leave.php");
    exit;
}


/* ================= AUTO DELETE LEAVE IF ENTRY FOUND ================= */
$conn->query("
DELETE cl FROM customer_leave cl
JOIN entry e 
ON cl.cid = e.cid
AND e.date BETWEEN cl.l_start AND cl.l_end
");


/* ================= SEARCH FILTER ================= */
$where="";
$params=[];
$types="";

if(isset($_GET['search']) && !empty($_GET['search_cid'])){

    $search_cid = filter_input(INPUT_GET,'search_cid',FILTER_VALIDATE_INT);

    if($search_cid!==false && $search_cid>0){
        $where="WHERE cl.cid=?";
        $params[]=$search_cid;
        $types.="i";
    }
}


/* ================= FETCH LEAVES ================= */
$sql="
SELECT cl.sno, cl.cid, c.name, cl.l_start, cl.l_end,
DATEDIFF(cl.l_end,cl.l_start)+1 AS total_days,
CASE 
    WHEN cl.l_end >= CURDATE() THEN 'Active'
    ELSE 'Expired'
END AS status
FROM customer_leave cl
JOIN customer c ON c.cid=cl.cid
$where
ORDER BY cl.l_start DESC
";

$stmt=$conn->prepare($sql);

if(!empty($where)){
    $stmt->bind_param($types,...$params);
}

$stmt->execute();
$result=$stmt->get_result();

$leaveList=[];
while($row=$result->fetch_assoc()){
    $leaveList[]=$row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Leave Management</title>
<style>

/* ===== BODY ===== */
body{
    font-family: 'Segoe UI', Arial, sans-serif;
    background: linear-gradient(135deg,#eef2f7,#dce3f0);
    margin:0;
    padding:40px 20px;
}

/* ===== CONTAINER ===== */
.container{
    max-width:1100px;
    margin:auto;
    background:#ffffff;
    padding:35px;
    border-radius:18px;
    box-shadow:0 15px 40px rgba(0,0,0,0.12);
    animation:fadeIn 0.5s ease-in-out;
}
.top-right-btn {
    position: absolute;
    top: 7px;
    right: 7px;
    background: linear-gradient(to right, #ff416c, #ff4b2b);
    color: #fff;
    padding: 8px 18px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

/* ===== HEADINGS ===== */
h2{
    color:#333;
    border-left:5px solid #4e73df;
    padding-left:10px;
    margin-bottom:20px;
}

/* ===== FORMS ===== */
form{
    display:flex;
    gap:15px;
    flex-wrap:wrap;
    margin-bottom:25px;
}

/* ===== INPUT & SELECT ===== */
input,select{
    padding:12px 14px;
    font-size:16px;
    border-radius:8px;
    border:1px solid #ccc;
    min-width:180px;
    transition:all 0.3s ease;
}

input:focus,select:focus{
    border-color:#4e73df;
    box-shadow:0 0 8px rgba(78,115,223,0.3);
    outline:none;
}

/* ===== BUTTON ===== */
button{
    padding:12px 25px;
    border:none;
    border-radius:8px;
    font-size:15px;
    font-weight:600;
    background:linear-gradient(to right,#28a745,#20c997);
    color:#fff;
    cursor:pointer;
    transition:all 0.3s ease;
}

button:hover{
    transform:translateY(-2px);
    box-shadow:0 8px 20px rgba(0,0,0,0.2);
}

.reset-btn{
    background:linear-gradient(to right,#6c757d,#495057);
}

/* ===== TABLE ===== */
table{
    width:100%;
    border-collapse:separate;
    border-spacing:0;
    margin-top:25px;
    background:#fff;
    border-radius:15px;
    overflow:hidden;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

th{
    background:#4e73df;
    color:#fff;
    padding:14px;
    font-weight:600;
    font-size:15px;
    letter-spacing:0.5px;
}

td{
    padding:14px;
    text-align:center;
    font-size:14px;
    color:#333;
}

tbody tr{
    transition:all 0.2s ease;
}

tbody tr:nth-child(even){
    background:#f8f9fc;
}

tbody tr:hover{
    background:#e2e6f5;
    transform:scale(1.01);
}

/* ===== STATUS BADGE ===== */
.badge{
    padding:6px 14px;
    border-radius:25px;
    font-size:13px;
    font-weight:600;
    letter-spacing:0.3px;
}

.active{
    background:#28a745;
}

.expired{
    background:#6c757d;
}

/* ===== MESSAGE BOX ===== */
.msg{
    padding:12px;
    border-radius:10px;
    margin-bottom:20px;
    font-weight:500;
    animation:fadeIn 0.4s ease-in-out;
}

.success{
    background:#28a745;
    color:#fff;
}

.error{
    background:#dc3545;
    color:#fff;
}

/* ===== RESPONSIVE ===== */
@media(max-width:768px){
    form{
        flex-direction:column;
    }

    input,select,button{
        width:100%;
    }

    table{
        font-size:13px;
    }
}

/* ===== ANIMATION ===== */
@keyframes fadeIn{
    from{opacity:0; transform:translateY(10px);}
    to{opacity:1; transform:translateY(0);}
}

</style>
</head>
<body>
<a href="User.php" class="top-right-btn">Home</a>

<div class="container">

<h2>➕ Add Leave</h2>

<?php if(isset($_SESSION['msg'])): ?>
<div class="msg <?= $_SESSION['type']; ?>">
<?= $_SESSION['msg']; ?>
</div>
<?php unset($_SESSION['msg'],$_SESSION['type']); endif; ?>

<form method="post">
<input type="number" name="cid" placeholder="Customer ID" required>

<select name="start_type" required>
<option value="">Start</option>
<option value="today">Today</option>
<option value="tomorrow">Tomorrow</option>
</select>

<select name="total_days" required>
<option value="">Days (1-30)</option>
<?php for($i=1;$i<=30;$i++): ?>
<option value="<?= $i ?>"><?= $i ?> Days</option>
<?php endfor; ?>
</select>

<button name="add_leave">Add Leave</button>
</form>


<h2>🔍 Search Leave By CID</h2>

<form method="get">
<input type="number" name="search_cid" placeholder="Enter CID">
<button type="submit" name="search">Search</button>
<a href="leave.php">
<button type="button" class="reset-btn">Reset</button>
</a>
</form>


<h2>📋 Leave List</h2>

<table>
<tr>
<th>Name</th>
<th>Start</th>
<th>End</th>
<th>Total Days</th>
<th>Status</th>
</tr>

<?php if(count($leaveList)>0): ?>
<?php foreach($leaveList as $row): ?>
<tr>
<td><?= htmlspecialchars($row['name']) ?></td>
<td><?= $row['l_start'] ?></td>
<td><?= $row['l_end'] ?></td>
<td><?= $row['total_days'] ?> Days</td>
<td>
<span class="badge <?= strtolower($row['status']) ?>">
<?= $row['status'] ?>
</span>
</td>
</tr>
<?php endforeach; ?>
<?php else: ?>
<tr>
<td colspan="5">No Leaves Found</td>
</tr>
<?php endif; ?>
</table>

</div>

</body>
</html>