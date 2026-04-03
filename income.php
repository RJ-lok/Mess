<?php
require_once "auth.php";
ini_set('display_errors',1);
error_reporting(E_ALL);

$conn = new mysqli("localhost","root","","mess");
if ($conn->connect_error) die("DB Error");

$today = date('Y-m-d');
$last30 = date('Y-m-d', strtotime('-30 days'));

/* ================= DELETE ENTRY ================= */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);

    $stmt = $conn->prepare("DELETE FROM daily_income WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    header("Location: income.php");
    exit();
}

/* ================= ADD INCOME ================= */
if(isset($_POST['add'])){
    $date   = $_POST['income_date'];
    $amount = $_POST['amount'];

    $stmt = $conn->prepare(
        "INSERT INTO daily_income (income_date, amount)
         VALUES (?,?)"
    );
    $stmt->bind_param("sd", $date, $amount);
    $stmt->execute();

    header("Location: income.php");
    exit();
}

/* ================= FILTER ================= */
$from = $_POST['from'] ?? $last30;
$to   = $_POST['to'] ?? $today;

$stmt = $conn->prepare(
    "SELECT id, income_date, amount 
     FROM daily_income
     WHERE income_date BETWEEN ? AND ?
     ORDER BY income_date ASC"
);

$stmt->bind_param("ss", $from, $to);
$stmt->execute();
$result = $stmt->get_result();

/* GROUP DATA */
$dateTotals = [];
while($row = $result->fetch_assoc()){
    $dateTotals[$row['income_date']][] = $row;
}

$totalAll = 0;
?>

<!DOCTYPE html>
<html>
<head>
<title>Daily Income</title>

<style>
body{
    font-family: Arial, sans-serif;
    background:#f4f6f9;
    margin:0;
    padding:30px;
}

.container{
    max-width:1000px;
    margin:auto;
    background:#fff;
    padding:25px;
    border-radius:8px;
    box-shadow:0 5px 15px rgba(0,0,0,0.1);
}

h2{
    text-align:center;
    margin-top:0;
    color:#333;
}

/* FORMS */
.forms{
    display:flex;
    gap:25px;
    margin-bottom:25px;
}

form{
    flex:1;
}

input, button{
    padding:8px;
    margin:5px 0;
    width:80%;
    font-size:24px;
    box-sizing:border-box;
}

button{
    background:#007bff;
    color:#fff;
    border:none;
    cursor:pointer;
}

button:hover{
    background:#0056b3;
}

/* TABLE */
.table-wrapper{
    overflow-x:auto;
}

table{
    width:100%;
    border-collapse:collapse;
}

th{
    background:#007bff;
    color:#fff;
    padding:10px;
    text-align:center;
    font-size:18px;
}

td{
    font-size:18px;
    padding:10px;
    text-align:center;
    border-bottom:1px solid #ddd;
}

tr:nth-child(even){
    background:#f9f9f9;
}

tr:hover{
    background:#eef3ff;
}

.delete-btn{
    background:#dc3545;
    color:#fff;
    padding:5px 10px;
    border-radius:4px;
    text-decoration:none;
    font-size:14px;
}

.delete-btn:hover{
    background:#b52a37;
}

.total{
    margin-top:20px;
    text-align:right;
    font-size:18px;
    font-weight:bold;
}
.top-right-btn {
    position: absolute;
    top: 5%;
    right:25%;
    background: linear-gradient(to right, #ff416c, #ff4b2b);
    color: #fff;
    padding: 8px 18px;
    border-radius: 25px;
    text-decoration: none;
    font-weight: bold;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

</style>
</head>

<body>

<div class="container">
<a href="Abc.php" class="top-right-btn">Home</a>

<h2>Daily Income</h2>

<div class="forms">

<!-- ADD FORM -->
<form method="post">
<h4>Add Income</h4>
<input type="date" name="income_date" value="<?= $today ?>" required>
<input type="number" step="0.01" name="amount" placeholder="Amount" required>
<button name="add">Add Income</button>
</form>

<!-- FILTER FORM -->
<form method="post">
<h4>Filter By Date</h4>
<label>From:</label>
<input type="date" name="from" value="<?= $from ?>"><br>
<label> To : </label>
<input type="date" name="to" value="<?= $to ?>">
<button>Apply Filter</button>
</form>

</div>

<div class="table-wrapper">
<table>
<tr>
<th>Date</th>
<th>Amount</th>
<th>Total</th>
<th>Delete</th>
</tr>

<?php
foreach($dateTotals as $date => $entries){

    $dateTotal = array_sum(array_column($entries,'amount'));
    $totalAll += $dateTotal;
    $first = true;

    foreach($entries as $entry){
        echo "<tr>";
        echo "<td>".$date."</td>";
        echo "<td>₹".number_format($entry['amount'],2)."</td>";

        if($first){
            echo "<td>₹".number_format($dateTotal,2)."</td>";
            $first = false;
        } else {
            echo "<td></td>";
        }

        echo "<td>
        <a href='?delete=".$entry['id']."' 
           class='delete-btn'
           onclick=\"return confirm('Are you sure you want to delete this entry?')\">
           Delete
        </a>
        </td>";

        echo "</tr>";
    }
}
?>

</table>
</div>

<div class="total">
Grand Total: ₹<?= number_format($totalAll,2) ?>
</div>

</div>

</body>
</html>