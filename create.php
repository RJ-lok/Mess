<?php
$host = "localhost";
$user = "root";
$pass = "";
$db   = "mess";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$tables = [];
$result = $conn->query("SHOW TABLES");

while ($row = $result->fetch_array()) {
    $tables[] = $row[0];
}
$exportMessages = [];

/* ================= CSV EXPORT ================= */

foreach ($tables as $table) {

    $filename = "C:\Users\Jagdish\OneDrive\Desktop\Mess_Info\ " . $table . "_data.csv";

    // delete old file if exists
    if (file_exists($filename)) {
        unlink($filename);
    }

    $fp = fopen($filename, 'w');
    if (!$fp) {
        $exportMessages[] = "❌ Cannot open file <strong>$filename</strong>";
        continue;
    }

    $result = $conn->query("SELECT * FROM `$table`");

    if ($result) {

        // column headers
        $columns = [];
        while ($field = $result->fetch_field()) {
            $columns[] = $field->name;
        }
        fputcsv($fp, $columns);

        // data rows
        while ($row = $result->fetch_assoc()) {
            fputcsv($fp, $row);
        }

        $exportMessages[] = "✅ CSV Exported: <strong>$table</strong>";
    } else {
        $exportMessages[] = "❌ Failed: <strong>$table</strong> - " . $conn->error;
    }

    fclose($fp);
}

/* ================= FULL SQL BACKUP ================= */

$backupFile = "C:\Users\Jagdish\OneDrive\Desktop\Mess_Info\mess_full_backup.sql";
$mysqldump = "C:\\xampp\\mysql\\bin\\mysqldump.exe";

// delete old backup
if (file_exists($backupFile)) {
    unlink($backupFile);
}

$command = "\"$mysqldump\" -u root $db > \"$backupFile\"";
exec($command, $output, $result);

if ($result === 0) {
    $exportMessages[] = "✅ Full Database SQL Backup Created!";
} else {
    $exportMessages[] = "❌ SQL Backup Failed!";
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>CSV Exporter</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: linear-gradient(135deg, #667eea, #764ba2);
    min-height: 100vh;
    display: flex;
    justify-content: center;
    align-items: center;
    margin: 0;
    color: #fff;
}
.container {
    background: rgba(255,255,255,0.05);
    padding: 40px 50px;
    border-radius: 20px;
    box-shadow: 0 20px 40px rgba(0,0,0,0.3);
    text-align: center;
    width: 450px;
    backdrop-filter: blur(10px);
}
h1 {
    font-size: 28px;
    margin-bottom: 30px;
    text-shadow: 1px 1px 5px rgba(0,0,0,0.3);
}
.messages {
    margin-top: 30px;
    text-align: left;
    font-size: 14px;
    line-height: 1.6;
}
.messages strong { color: #ffd700; }
.messages em { color: #00ffea; }
</style>
</head>
<body>
<div class="container">
    <h1>📤 Exporting Tables...</h1>

    <div class="messages">
        <?php foreach ($exportMessages as $msg) echo $msg . "<br>"; ?>
    </div>
</div>

<script>
// Redirect to home page after 3 seconds
setTimeout(function() {
    window.location.href = "index.html";
}, 3000);
</script>
</body>
</html>
