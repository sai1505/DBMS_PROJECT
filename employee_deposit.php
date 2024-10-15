<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['employeename'])) {
    header("Location: login.php");
    exit();
}

$employeename = htmlspecialchars($_SESSION['employeename']);
$firstLetter = strtoupper(substr($employeename, 0, 1));

// Database connection
$servername = "localhost";
$db_username = "sai2005";
$db_password = "sai@2005";
$dbname = "Bank";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$alert_message = '';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = filter_var($_POST['username'], FILTER_VALIDATE_INT);
    $amount = floatval($_POST['amount']);

    if ($amount <= 0) {
        $alert_message = "Invalid amount";
    } else {
        // Start transaction
        $conn->begin_transaction();

        try {
            // Check if user exists and get current balance
            $stmt = $conn->prepare("SELECT balance FROM account WHERE accountnumber = ?");
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows === 0) {
                throw new Exception("Account not found");
            }

            $row = $result->fetch_assoc();
            $currentBalance = $row['balance'];

            // Update balance
            $newBalance = $currentBalance + $amount;
            $stmt = $conn->prepare("UPDATE account SET balance = ? WHERE accountnumber = ?");
            $stmt->bind_param("ds", $newBalance, $username);
            $stmt->execute();

            // Commit transaction
            $conn->commit();

            $alert_message = "Deposited Successfully!!";
        } catch (Exception $e) {
            // Rollback transaction on error
            $conn->rollback();
            $alert_message = "An error occurred: " . $e->getMessage();
        }

        $stmt->close();
    }
}

$conn->close();

// Display alert if there's a message
if (!empty($alert_message)) {
    echo "<script>alert('$alert_message');</script>";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        :root {
            --primary-color: #4158D0;
            --secondary-color: #C850C0;
            --tertiary-color: #FFCC70;
            --background-color: #f0f2f5;
            --text-color: #333;
            --sidebar-width: 280px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--background-color);
            color: var(--text-color);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .dashboard-container {
            display: flex;
            width: 100%;
            position: relative;
            overflow: hidden;
            padding: 20px;
            gap: 20px;
        }

        .sidebar {
            width: var(--sidebar-width);
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            padding: 2rem;
            display: flex;
            flex-direction: column;
            position: relative;
            transition: all 0.3s ease;
            border-radius: 30px 0 0 30px;
            margin-left: 20px;
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin-bottom: 2rem;
            position: relative;
            z-index: 2;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            position: relative;
            z-index: 2;
        }

        .nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 0.75rem 1rem;
            border-radius: 10px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .nav a:hover::before {
            transform: scaleX(1);
            transform-origin: left;
        }

        .nav a:hover {
            transform: translateX(5px);
        }

        .nav a.active {
            background: rgba(255,255,255,0.2);
        }

        .main-content {
            flex-grow: 1;
            padding: 2rem;
            display: flex;
            flex-direction: column;
            gap: 2rem;
            position: relative;
            overflow: visible;
            background-color: white;
            border-radius: 0 30px 30px 0;
            box-shadow: -10px 0 20px rgba(0, 0, 0, 0.1);
        }

        .main-content::before {
            content: '';
            position: absolute;
            top: 0;
            left: -20px;
            width: 20px;
            height: 100%;
            background: linear-gradient(to right, transparent, white);
        }

        .deposit-form {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
        }

        .form-group label {
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: var(--primary-color);
        }

        .form-group input {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .deposit-btn {
            grid-column: span 2;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 15px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .deposit-btn:hover {
            background-color: var(--secondary-color);
        }

        .error {
            border-color: red !important;
        }

        .error-message {
            color: red;
            font-size: 0.8rem;
            margin-top: 0.25rem;
        }

        #notification {
            position: fixed;
            top: 20px;
            right: 20px;
            padding: 1rem;
            border-radius: 5px;
            color: white;
            font-weight: bold;
            display: none;
        }

        .success {
            background-color: #4CAF50;
        }

        .failure {
            background-color: #F44336;
        }

        @media (max-width: 768px) {
            .dashboard-container {
                flex-direction: column;
                padding: 10px;
            }

            .sidebar {
                width: 100%;
                border-radius: 30px 30px 0 0;
                margin-left: 0;
            }

            .main-content {
                border-radius: 0 0 30px 30px;
            }

            .deposit-form {
                grid-template-columns: 1fr;
            }

            .deposit-btn {
                grid-column: span 1;
            }
        }

        .user-info {
            display: flex;
            align-items: center;
            margin-top: auto;
            padding-top: 2rem;
            color: white;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #fff;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: #de6f47;
            margin-left: 10px;
            margin-right: 15px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .username {
            font-weight: 600;
        }

        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .welcome-text h1 {
            font-size: 2.5rem;
            margin-bottom: 0.5rem;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="employee_dashboard.php">Dashboard</a>
                <a href="employee_shift.php">Shifts</a>
                <a href="employee_userdata.php">User Data</a>
                <a href="employee_deposit.php"  class="active">Deposits</a>
                <a href="employee_profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo $firstLetter; ?>
                </div>
                <span><?php echo $employeename; ?></span>
            </div>
        </aside>
        <main class="main-content">
            <div class="welcome-card fade-in-up">
                <div class="welcome-text">
                    <h1>Deposit Management</h1>
                    <p>Process customer deposits below</p>
                </div>
            </div>
            <form id="deposit-form" class="deposit-form" method="post">
                <div class="form-group">
                    <label for="username">Account Number</label>
                    <input type="text" id="username" name="username" required>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="amount">Amount to Deposit</label>
                    <input type="number" id="amount" name="amount" step="0.01" min="0" required>
                    <span class="error-message"></span>
                </div>
                <button type="submit" class="deposit-btn" name="deposit">Process Deposit</button>
            </form>
        </main>
    </div>
    <div id="notification"></div>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const form = document.getElementById('deposit-form');
        const notification = document.getElementById('notification');

        form.onsubmit = function() {
            return validateForm();
        };

        function validateForm() {
            let isValid = true;
            const inputs = form.querySelectorAll('input');
            
            inputs.forEach(input => {
                if (input.value.trim() === '') {
                    showError(input, 'This field is required');
                    isValid = false;
                } else {
                    clearError(input);
                    
                    if (input.type === 'number' && parseFloat(input.value) <= 0) {
                        showError(input, 'Please enter a valid amount');
                        isValid = false;
                    }
                }
            });

            return isValid;
        }

        function showError(input, message) {
            input.classList.add('error');
            const errorElement = input.nextElementSibling;
            errorElement.textContent = message;
        }

        function clearError(input) {
            input.classList.remove('error');
            const errorElement = input.nextElementSibling;
            errorElement.textContent = '';
        }
    });
</script>
</body>
</html>