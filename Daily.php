<?php
require_once "auth.php";
ini_set('display_errors',1);
error_reporting(E_ALL);

$conn = new mysqli("localhost","root","","mess");
if ($conn->connect_error) die("DB Error");

$today = date('Y-m-d');
$last30 = date('Y-m-d', strtotime('-30 days'));

$msg = "";

/* ===== DELETE SPEND ===== */
if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    $stmt = $conn->prepare("DELETE FROM daily_spend WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    header("Location: ".$_SERVER['PHP_SELF']);
    exit();
}

/* ===== ADD SPEND ===== */
if(isset($_POST['add'])){
    $date   = $_POST['spend_date'];
    $amount = $_POST['amount'];
    $note   = $_POST['purpose'];
    $type   = $_POST['spend_type'];

    $stmt = $conn->prepare(
        "INSERT INTO daily_spend (spend_date, amount, purpose, spend_type)
         VALUES (?,?,?,?)"
    );
    $stmt->bind_param("sdss", $date, $amount, $note, $type);
    $stmt->execute();

    header("Location: " . $_SERVER['PHP_SELF']);
    exit();
}

/* ===== FILTER ===== */
$from = $_POST['from'] ?? $last30;
$to   = $_POST['to'] ?? $today;
$typeFilter = $_POST['type_filter'] ?? 'Both';

$sql = "SELECT * FROM daily_spend WHERE spend_date BETWEEN ? AND ?";
$params = [$from, $to];
$types  = "ss";

if($typeFilter !== 'Both'){
    $sql .= " AND spend_type = ?";
    $params[] = $typeFilter;
    $types .= "s";
}

$sql .= " ORDER BY id DESC";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$data = $stmt->get_result();

$total = 0;
$rows = [];
while($r = $data->fetch_assoc()){
    $total += $r['amount'];
    $rows[] = $r;
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Daily Spend</title>
<style>
/* Your existing CSS remains unchanged */
/* ===== BODY ===== */
body {
    font-family: 'Segoe UI', sans-serif;
    background: linear-gradient(135deg, #0b7285, #0a5263);
    min-height: 100vh;
    display: flex;
    justify-content: center; /* horizontal center */
    align-items: center;     /* vertical center */
    margin: 0;
    transition: background 0.5s ease;
}

/* ===== MAIN BOX ===== */
.box {
    background: #fff;
    width: 1100px;
    height: 800px;
    padding: 15px 20px; /* reduced padding */
    border-radius: 16px;
    box-shadow: 0 15px 35px rgba(0,0,0,.25);
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
        background: #f214d8;

}

.box:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0,0,0,.25);
}

/* ===== INPUT FIELDS ===== */
input, textarea, select, button {
    padding: 8px 10px;
    font-size: 18px;
    border-radius: 8px;
    border: 1px solid #ccc;
    transition: border 0.3s ease, box-shadow 0.3s ease;
}

input:focus, textarea:focus, select:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 6px rgba(102,126,234,0.5);
}

/* ===== BUTTON ===== */
button {
    background: linear-gradient(to right, #28a745, #2ecc71);
    color: #fff;
    border: none;
    cursor: pointer;
    font-weight: bold;
    transition: background 0.3s ease, transform 0.2s ease;
}

button:hover {
    background: linear-gradient(to right, #2ecc71, #28a745);
    transform: translateY(-2px);
}

/* ===== MESSAGE ===== */
.msg {
    text-align: center;
    font-weight: bold;
    color: green;
    margin-bottom: 10px;
    font-size: 20px;
}

/* ===== TOP SECTIONS ===== */
.top-sections {
    display: flex;
    gap: 15px;
    margin-bottom: 15px;
}

.add-box, .filter-box {
    width: 50%;
    background: #fafafa;
    padding: 12px 15px; /* compact padding */
    border-radius: 12px;
    box-shadow: 0 5px 12px rgba(0,0,0,.1);
}

/* ===== FORMS ===== */
.add-box form,
.filter-box form {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 10px; /* smaller gap */
}

.add-box form input,
.add-box form textarea,
.add-box form button,
.filter-box form input,
.filter-box form select,
.filter-box form button {
    width: 70%;
    padding: 6px 8px; /* smaller height */
    font-size: 18px;
}

/* ===== RADIO BUTTONS ===== */
.radio {
    display: flex;
    justify-content: center;
    gap: 30px;
    font-weight: bold;
    font-size: 18px;
}

/* ===== TOTAL ===== */
.total {
    text-align: right;
    font-size: 24px;
    font-weight: bold;
    margin: 8px 0;
}

/* ===== TABLE CARD ===== */
.table-card {
    background: #fff;
    border-radius: 14px;
    padding: 8px; /* compact padding */
    box-shadow: 0 8px 20px rgba(0,0,0,.15);
    margin-top: 5px;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
}

.table-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 15px 30px rgba(0,0,0,.2);
}

/* ===== TABLE SCROLL ===== */
.table-wrapper {
    max-height: 450px; /* smaller table height */
    overflow-y: auto;
    border-radius: 10px;
    border: 1px solid #ddd;
}

/* ===== TABLE STYLES ===== */
table {
    width: 100%;
    border-collapse: collapse;
        background:linear-gradient(135deg,#74ebd5,#ACB6E5);

}

th, td {
    padding: 6px; /* smaller row height */
    text-align: center;
    border-bottom: 1px solid #ddd;
    font-size: 24px;
    transition: background 0.2s ease;
}

tr:hover td {
    background: rgba(102,126,234,0.05);
}

thead th {
    position: sticky;
    top: 0;
    background: #f21414;
    z-index: 2;
    font-size: 24px;
}

/* ===== HOME BUTTON ===== */
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

.top-right-btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,.3);
}

</style>
</head>
<body>
<div class="box">
<a href="Abc.php" class="top-right-btn">Home</a>

<div class="top-sections">
<!-- ADD -->
<div class="add-box">
<form method="post">
<input type="date" name="spend_date" value="<?= $today ?>" required>
<input type="number" step="0.01" name="amount" placeholder="Amount" required>
<textarea name="purpose" placeholder="Where money spent?" required></textarea>

<div class="radio">
<label><input type="radio" name="spend_type" value="Personal" required> Personal</label>
<label><input type="radio" name="spend_type" value="Mess"> Mess</label>
</div>

<button name="add">Add Spend</button>
</form>
</div>

<!-- FILTER -->
<div class="filter-box">
<form method="post">
<input type="date" name="from" value="<?= $from ?>">
<input type="date" name="to" value="<?= $to ?>">

<select name="type_filter">
<option value="Both" <?= $typeFilter=='Both'?'selected':'' ?>>Both</option>
<option value="Personal" <?= $typeFilter=='Personal'?'selected':'' ?>>Personal</option>
<option value="Mess" <?= $typeFilter=='Mess'?'selected':'' ?>>Mess</option>
</select>

<button>Apply</button>
</form><br><br><br>
<div class="total">Total : ₹<?= number_format($total,2) ?></div>
</div>
</div>

<!-- TABLE CARD -->
<div class="table-card">
<div class="table-wrapper">
<table>
<thead>
<tr><th>Date</th><th>Amount</th><th>Purpose</th><th>Type</th><th>Action</th></tr>
</thead>
<tbody>
<?php if($rows): foreach($rows as $r): ?>
<tr>
    <td><?= $r['spend_date'] ?></td>
    <td>₹<?= number_format($r['amount'],2) ?></td>
    <td><?= htmlspecialchars($r['purpose']) ?></td>
    <td><?= $r['spend_type'] ?></td>
    <td>
        <a href="?delete=<?= $r['id'] ?>" class="delete-btn" 
           onclick="return confirm('Are you sure you want to delete this entry?')">Delete</a>
    </td>
</tr>
<?php endforeach; else: ?>
<tr><td colspan="5">No Data</td></tr>
<?php endif; ?>
</tbody>
</table>
</div>
</div>
</div>
</body>
</html>
