<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Master Page</title>
<style>
body {
    font-family: 'Segoe UI', Tahoma, sans-serif;
    display: flex;
    justify-content: center;
    align-items: center;
    min-height: 100vh;
    margin: 0;
    background: linear-gradient(135deg, #0b7285, #0a5263);
}

.container {
    background: linear-gradient(135deg, #00f0e0, #00f986);
    padding: 40px 30px;
    border-radius: 30px;
    box-shadow: 0 10px 25px rgba(0,0,0,0.2);
    text-align: center;
    width: 480px;
    height: 350px;
    max-width: 90%;
}

.container h2 {
    margin: 0 0 30px 0; /* Top margin 0, bottom 30px */
    font-size: 35px;
    font-weight: 800;
    color: #222;
}


/* Grid layout for buttons */
.button-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 40px;
}

/* Button style */
button {
    padding: 16px 0;
    font-size: 24px;
    font-weight: 700;
    border-radius: 15px;
    border: none;
    cursor: pointer;
    color: #fff;
    background: linear-gradient(90deg, #e41f26, #4f4ba2);
    box-shadow: 0 6px 18px rgba(0,0,0,0.2);
    transition: all 0.3s ease;
    width: 100%;
}

button:hover {
    transform: translateY(-4px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.25);
    opacity: 0.95;
}
</style>
</head>
<body>
<div class="container">
    <h2>Master Page</h2>
    <div class="button-grid">
        <form action="Daily.php" method="post">
            <button type="submit" name="btn1">Out</button>
        </form>
        <form action="income.php" method="post">
            <button type="submit" name="btn2">In</button>
        </form>
        <form action="dashboard.php" method="post">
            <button type="submit" name="btn3">Home</button>
        </form>
    
    </div>
</div>
</body>
</html>
