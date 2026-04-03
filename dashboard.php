<?php
require_once "auth.php"; // 🔒 Login protection
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Admin Dashboard</title>
<style>
/* ===== BODY & BACKGROUND ===== */
body {
    margin: 0;
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    font-family: "Segoe UI", Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #1c92d2, #f2fcfe);
}

/* ===== CARD ===== */
.dashboard-card {
    background: #ffffff;
    width: 100%;
    max-width: 600px;
    padding: 40px 30px;
    border-radius: 16px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.15);
    text-align: center;
    transition: all 0.3s ease;
}

.dashboard-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 25px 50px rgba(0,0,0,0.2);
}

/* ===== HEADING ===== */
.dashboard-card h2 {
    margin-bottom: 35px;
    color: #333;
    font-weight: 800;
    font-size: 34px;
}

/* ===== BUTTON GRID ===== */
.button-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 15px;
}

/* ===== BUTTONS & LINKS ===== */
.dashboard-card a,
.dashboard-card button {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px 0;
    border-radius: 10px;
    font-size: 20px;
    font-weight: 600;
    color: #fff;
    text-decoration: none;
    border: none;
    cursor: pointer;
    transition: all 0.3s ease;
}

.dashboard-card a:hover,
.dashboard-card button:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0,0,0,0.2);
    opacity: 0.95;
}

/* ===== BUTTON COLORS ===== */
.newmess { background: linear-gradient(135deg, #0b7285, #20c997); }
.payment { background: linear-gradient(135deg, #198754, #52b788); }
.attendance { background: linear-gradient(135deg, #0d6efd, #3b82f6); }
.display { background: linear-gradient(135deg, #6c757d, #adb5bd); }
.newcustomer { background: linear-gradient(135deg, #6610f2, #845ef7); }
.daily { background: linear-gradient(135deg, #fd7e14, #ff922b); }
.delete { background: linear-gradient(135deg, #dc3545, #ff6b6b); }
.logout { background: linear-gradient(135deg, #495057, #6c757d); }
.display { background: linear-gradient(135deg, #6c757d, #adb5bd); }
.addcustomer { background: linear-gradient(135deg, #6610f2, #845ef7); }


/* ===== FOOTER ===== */
.footer-text {
    margin-top: 25px;
    font-size: 14px;
    color: #666;
}
.dashboard-card a:hover,
.dashboard-card button:hover {
    transform: translateY(-5px);
    box-shadow: 0 15px 30px rgba(0,0,0,0.2);
}

/* ===== RESPONSIVE ===== */
@media (max-width: 480px) {
    .dashboard-card {
        padding: 30px 20px;
    }
    .dashboard-card h2 {
        font-size: 24px;
    }
    .button-grid {
        grid-template-columns: 1fr;
    }
    .dashboard-card a,
    .dashboard-card button {
        font-size: 15px;
        padding: 14px 0;
    }
}
</style>
</head>
<body>

<div class="dashboard-card">
    <h2>Admin Dashboard</h2>
<div class="button-grid">
    <a href="newmess.php" class="newmess">🆕 New Mess</a>
    <a href="payment.php" class="payment">💰 Payment</a>
    <a href="attendance.php" class="attendance">📅 Attendance</a>
    <a href="Abc.php" class="daily">📆 Daily</a>
    <a href="delete.php" class="delete">🗑 Delete</a>
    <a href="Display.php" class="display">📋 Display</a>
    <a href="New.php" class="addcustomer">➕ Add Customer</a>
    <a href="logout.php" class="logout">🚪 Logout</a>
</div>


    <div class="footer-text">
        Logged in as <b>Admin</b>
    </div>
</div>

</body>
</html>
