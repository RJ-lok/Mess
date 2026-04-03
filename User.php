<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

$conn = new mysqli("localhost", "root", "", "mess");
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

$msg = "";
$msg_color = "";
$printData = null;
$doPrint = false;

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $cid = (int)($_POST['cid'] ?? 0);
    $mob_last4 = trim($_POST['mob_last4'] ?? '');

    if ($cid <= 0 || strlen($mob_last4) !== 4) {
        $msg = "Invalid Customer";
        $msg_color = "red";
    } else {

        $stmt = $conn->prepare("
            SELECT cid, name, paid, panding, `plus`, Days
            FROM customer
            WHERE cid = ? AND RIGHT(mob,4) = ?
        ");
        $stmt->bind_param("is", $cid, $mob_last4);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows !== 1) {
            $msg = "Invalid Customer";
            $msg_color = "red";
        } else {

            date_default_timezone_set("Asia/Kolkata");
            $today = date("Y-m-d");
            $hour  = (int)date("H");
            $isDay = ($hour >= 0 && $hour < 17);

            try {

                $conn->begin_transaction();

                /* ===== Lock entry row ===== */
                $chk = $conn->prepare("
                    SELECT day, night
                    FROM entry
                    WHERE cid = ? AND date = ?
                    FOR UPDATE
                ");
                $chk->bind_param("is", $cid, $today);
                $chk->execute();
                $entry = $chk->get_result()->fetch_assoc();

                $alreadyPrinted = false;
                if ($entry) {
                    if ($isDay && $entry['day'] === 'Y') $alreadyPrinted = true;
                    if (!$isDay && $entry['night'] === 'Y') $alreadyPrinted = true;
                }

                if ($alreadyPrinted) {

                    $conn->rollback();
                    $msg = "Already Printed";
                    $msg_color = "orange";

                } else {

                    $dayAlreadyCounted = false;
                    if ($entry) {
                        if ($entry['day'] === 'Y' || $entry['night'] === 'Y') {
                            $dayAlreadyCounted = true;
                        }
                    }

                    /* ===== Insert / Update entry ===== */
                    if ($entry) {
                        $column = $isDay ? 'day' : 'night';
                        $u = $conn->prepare("
                            UPDATE entry
                            SET $column = 'Y'
                            WHERE cid = ? AND date = ?
                        ");
                        $u->bind_param("is", $cid, $today);
                        $u->execute();
                    } else {
                        $dayVal   = $isDay ? 'Y' : NULL;
                        $nightVal = !$isDay ? 'Y' : NULL;

                        $i = $conn->prepare("
                            INSERT INTO entry (date, day, night, cid)
                            VALUES (?, ?, ?, ?)
                        ");
                        $i->bind_param("sssi", $today, $dayVal, $nightVal, $cid);
                        $i->execute();
                    }

                    /* ===== Update Days ===== */
                    if (!$dayAlreadyCounted) {
                        $updateDays = $conn->prepare("
                            UPDATE customer
                            SET Days = Days + 1
                            WHERE cid = ?
                        ");
                        $updateDays->bind_param("i", $cid);
                        $updateDays->execute();
                    }

                    /* ===== Update Plus ===== */
                    $updatePlus = $conn->prepare("
                        UPDATE customer
                        SET `plus` = `plus` + 1
                        WHERE cid = ?
                    ");
                    $updatePlus->bind_param("i", $cid);
                    $updatePlus->execute();

                    /* ===== Fetch Fresh Updated Data ===== */
                    $fresh = $conn->prepare("
                        SELECT cid, name, paid, panding, `plus`, Days
                        FROM customer
                        WHERE cid = ?
                    ");
                    $fresh->bind_param("i", $cid);
                    $fresh->execute();
                    $printData = $fresh->get_result()->fetch_assoc();

                    $conn->commit();

                    $msg = "Success";
                    $msg_color = "green";
                    $doPrint = true;
                }

            } catch (Exception $e) {

                $conn->rollback();
                $msg = "System Error";
                $msg_color = "red";
            }
        }
    }
}

$conn->close();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Dnyanvardhini Mess</title>

<style>
*{margin:0;padding:0;box-sizing:border-box;}
body{
    font-family:Arial;
    background: linear-gradient(135deg, #a70bf0, #e00e0e);
    min-height:100vh;
}
.center-container{
    display:flex;
    justify-content:center;
    margin-top:15px;
}
.box{
    background:#fff;
    padding:30px;
    width:400px;
    height: 440px;
    border-radius:15px;
    box-shadow:0 10px 25px rgba(0,0,0,0.3);
}
.box h2{text-align:center;margin-bottom:20px;}
input{
    width:100%;
    padding:14px;
    margin:15px 0;
    border-radius:10px;
    border:2px solid #999;
    font-size:16px;
}

.button-group {
    display: grid;
    grid-template-columns: 1fr 1fr; /* 2 buttons per row */
    gap: 15px;
    margin-top: 25px;
}

.btn {
    display: flex;
    justify-content: center;
    align-items: center;
    height: 50px;              /* Fixed Height */
    width: 100%;               /* Equal Width */
    font-size: 16px;
    border-radius: 8px;
    border: none;
    background: linear-gradient(to right, #007bff, #ff69b4);
    color: #fff;
    cursor: pointer;
    text-decoration: none;
    transition: 0.3s ease;
}

.btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.2);
}

#qrModal {
    display: none;
    position: fixed;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    top: 0;
    left: 0;
    justify-content: center;
    align-items: center;
        z-index: 2000;

}

#qrBox {
    background: white;
    padding: 20px;
    border-radius: 10px;
}

#qrBox img {
    width: 320px;
    height: 300px;
}

.msg{
    text-align:center;
    font-size:20px;
    margin-top:15px;
    font-weight:bold;
}
.center-container img {
    width: 320px;
    height: 350px;
    border-radius: 15px;           /* same as .box */
    box-shadow: 0 10px 25px rgba(0,0,0,0.3); /* same shadow */
    margin: 0 15px;                 /* horizontal gap between image and card */
    object-fit: cover;              /* ensures image fits nicely */
}

#printArea{ display:none; }

@page{ margin:0; }

@media print{
    body *{ visibility:hidden; }
    #printArea, #printArea *{ visibility:visible; }

    #printArea{
        display:block;
        position:absolute;
        left:0;
        top:0;
        padding:2mm;
        width:80mm;
        font-family:monospace;
        font-size:16px;
        line-height:1.5;
    }
}
</style>
</head>

<body>
    

<div id="qrModal">
    <div id="qrBox">
<center>        <h2 id="messTitle">DHANVARDHINI Mess</h2>
</center><br>
        <img id="qrImage" src="">
<br><br>

    </div>
</div>
<div class="center-container">
   

    <div class="box">
        <h2>Customer Login</h2>

        <form method="post" autocomplete="off">
            <input type="text" name="cid" placeholder="Customer ID" required>
            <input type="text" name="mob_last4" placeholder="Last 4 Digits of Mobile" maxlength="4" required>
        <div class="button-group">
    <button type="submit" class="btn">Submit</button>
    <a href="leave.php" class="btn">Mark Leave</a>
    <button type="button" id="qrBtn" class="btn">Generate QR</button>
    <a href="index.html" class="btn">Home</a>
</div>
            
        </form>

        <?php if ($msg): ?>
            <div class="msg" style="color:<?= $msg_color ?>"><?= $msg ?></div>
        <?php endif; ?>
    </div>

</div>

<?php if ($printData && $doPrint): ?>
<div id="printArea">
<center>DNYANVARDHINI MESS</center>
--------------------------------<br>
Name     : <?= $printData['cid']?>
        <?= $printData['name'] ?><br>
MEAL    : <?= $printData['plus'] ?>&nbsp&nbsp&nbsp
DAYS    : <?= $printData['Days'] ?><br>
PAID    : <?= $printData['paid'] ?><br>
PENDING : <?= $printData['panding'] ?><br>
DATE     : <?= date("d-m-Y") ?><br>
--------------------------------<br>
<br>.
</div>
<?php endif; ?>

<script>
 // QR Button Click
document.getElementById("qrBtn").addEventListener("click", function () {

    let messName = "DHANVARDHINI MESS";
    let upiId = "paytm.s1dwgnx@pty";

    let qrData = `upi://pay?pa=${upiId}&pn=${messName}&cu=INR`;

    let qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" 
                + encodeURIComponent(qrData);

    document.getElementById("qrImage").src = qrUrl;

    let modal = document.getElementById("qrModal");
    modal.style.display = "flex";

    // Auto close after 10 seconds
    setTimeout(function () {

        modal.style.display = "none";

        // ✅ Focus CID input automatically after QR closes
        let cidInput = document.querySelector("input[name='cid']");
        if (cidInput) {
            cidInput.focus();
            cidInput.select();
        }

    }, 10000);
});


// Window Load Events
window.onload = function () {

    const slip = document.getElementById("printArea");

    // ✅ Auto Print if slip exists
    if (slip) {
        window.print();
    }

    // ✅ Focus CID on load
    setTimeout(() => {
        const cidInput = document.querySelector("input[name='cid']");
        if (cidInput) {
            cidInput.focus();
            cidInput.select();
        }
    }, 300);

    // ✅ Allow only numbers in all inputs
    document.querySelectorAll("input").forEach(input => {
        input.addEventListener("input", () => {
            input.value = input.value.replace(/\D/g, '');
        });
    });

};

</script>

</body>
</html>
