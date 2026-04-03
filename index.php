<?php
session_start();

// ===== DATABASE CONNECTION =====
$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$msg = "";

// ===== LOGIN FORM SUBMISSION =====
if (isset($_POST['password'])) {
    $pass = $_POST['password'];

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT 1 FROM `mess` WHERE `pass` = ?");
    if (!$stmt) {
        die("Prepare failed: (" . $conn->errno . ") " . $conn->error);
    }

    $stmt->bind_param("s", $pass);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows === 1) {
        $_SESSION['admin'] = true;
        header("Location: dashboard.php");
        exit;
    } else {
        $msg = "❌ Wrong Password";
    }

    $stmt->close();
}

$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Login</title>
<style>
/* ===== BODY ===== */
body{
    margin:0;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    font-family:"Segoe UI", Tahoma, sans-serif;
    background: linear-gradient(135deg, #0b7285, #0a5263);
}

/* ===== LOGIN CARD ===== */
.login-card{
    background:#fff;
    width:100%;
    max-width:380px;
    padding:40px 35px;
    border-radius:10px;
    box-shadow:0 15px 35px rgba(0,0,0,0.25);
    text-align:center;
}

/* ===== HEADING ===== */
.login-card h2{
    margin-bottom:25px;
    color:#333;
    font-weight:600;
}

/* ===== INPUT ===== */
.login-card input[type=password]{
    width:100%;
    padding:14px;
    font-size:16px;
    border-radius:6px;
    border:1px solid #ccc;
    outline:none;
    margin-bottom:20px;
    transition:0.25s;
}
.login-card input[type=password]:focus{
    border-color:#0b7285;
}

/* ===== BUTTON ===== */
.login-card button{
    width:100%;
    padding:14px;
    font-size:16px;
    font-weight:600;
    color:#fff;
    border:none;
    border-radius:6px;
    cursor:pointer;
    background:#0b7285;
    transition:0.25s;
}
.login-card button:hover{
    background:#095c6b;
}

/* ===== MESSAGE ===== */
.msg{
    margin-top:15px;
    font-weight:600;
    color:#dc3545;
}

/* ===== BUTTON STYLE FOR LINKS INSIDE LOGIN CARD ===== */
.login-card a.button, .login-card a {
    display: inline-block;
    width: 93%;
    padding: 14px;
    font-size: 16px;
    font-weight: 600;
    color: #fff;
    text-decoration: none; /* remove underline */
    border: none;
    border-radius: 6px;
    cursor: pointer;
    background: #0b7285;
    transition: 0.25s;
    text-align: center;
}

.login-card a.button:hover, .login-card a:hover {
    background: #095c6b;
    color: #fff;
    text-decoration: none;
}

</style>
</head>
<body>

<div class="login-card">
    <h2>Admin Login</h2>
    <form method="post">
        <input type="password" name="password" placeholder="Enter Password" required>
        <button type="submit">LOGIN</button>
    </form><br>
  <a href="index.html" class="button">🏠 Home</a>


    <?php if($msg): ?>
        <div class="msg"><?= htmlspecialchars($msg) ?></div>
    <?php endif; ?>
</div>

</body>
</html>
