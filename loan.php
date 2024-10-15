<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}

$username = htmlspecialchars($_SESSION['username']);
$firstLetter = strtoupper(substr($username, 0, 1));

// Database connection
$servername = "localhost";
$db_username = "sai2005";
$db_password = "sai@2005";
$dbname = "Bank";

$conn = new mysqli($servername, $db_username, $db_password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get account number
$acc_sql = "SELECT accountnumber FROM account WHERE username = ?";
$acc_stmt = $conn->prepare($acc_sql);
$acc_stmt->bind_param("s", $username);
$acc_stmt->execute();
$acc_result = $acc_stmt->get_result();

if ($acc_row = $acc_result->fetch_assoc()) {
    $accountnumber = $acc_row['accountnumber'];
} else {
    die("Error: Account not found for this user.");
}

$acc_stmt->close();

// Fetch user's loans
$loans_sql = "SELECT * FROM loan WHERE username = ?";
$loans_stmt = $conn->prepare($loans_sql);
$loans_stmt->bind_param("s", $username);
$loans_stmt->execute();
$loans_result = $loans_stmt->get_result();

$loans = [];
while ($loan = $loans_result->fetch_assoc()) {
    $loans[] = $loan;
}
$loans_stmt->close();

// Calculate loan summary
$total_loans = count($loans);
$total_amount = array_sum(array_column($loans, 'loanamount'));
$total_amount_paid = array_sum(array_column($loans, 'loanamountpaid'));
$total_remaining = $total_amount - $total_amount_paid; // Assuming no payments made yet

// Calculate progress percentage
$progress_percentage = 0;
if ($total_amount > 0) {
    $progress_percentage = (($total_amount - $total_remaining) / $total_amount) * 100;
}

if (isset($_POST['process'])) {
    // Get form data
    $loantype = $_POST['loantype'];
    $loanamount = floatval($_POST['loanamount']);

    // Check if loan amount is at least 50,000
    if ($loanamount < 50000) {
        echo "<script>alert('Loan amount must be at least 50,000');</script>";
    } else {
        // Set interest rate and loan duration based on loan type
        switch ($loantype) {
            case 'Personal Loan':
                $interestrate = 11.0;
                $duration = 2; // 2 years
                $enddate = date("Y-m-d", strtotime("+2 years"));
                break;
            case 'Home Loan':
                $interestrate = 9.5;
                $duration = 10; // 3 years
                $enddate = date("Y-m-d", strtotime("+3 years"));
                break;
            case 'Business Loan':
                $interestrate = 16.0;
                $duration = 4; // 4 years
                $enddate = date("Y-m-d", strtotime("+4 years"));
                break;
            default:
                $interestrate = 5.99;
                $duration = 1; // 1 year
                $enddate = date("Y-m-d", strtotime("+1 year"));
        }

        // Calculate total loan amount including interest
        $totalInterest = $loanamount * ($interestrate / 100) * $duration;
        $totalLoanAmount = $loanamount + $totalInterest;

        // Check if user already has a loan with the same parameters
        $check_sql = "SELECT * FROM loan WHERE username = ? AND loantype = ? AND loanamount = ? AND interestrate = ?";
        $check_stmt = $conn->prepare($check_sql);
        $check_stmt->bind_param("ssdd", $username, $loantype, $loanamount, $interestrate);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();

        if ($check_result->num_rows > 0) {
            echo "<script>alert('You already have a loan with the same parameters.');</script>";
        } else {
            $loannumber = sprintf("%09d", mt_rand(100000000, 999999999));
            $startdate = date("Y-m-d");

            // Prepare SQL statement
            $sql = "INSERT INTO loan (username, accountnumber, loantype, loanamount, interestrate, loannumber, loanstartdate, loanenddate) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

            $stmt = $conn->prepare($sql);
            if ($stmt === false) {
                die("Error preparing statement: " . $conn->error);
            }

            $stmt->bind_param("sssddsss", $username, $accountnumber, $loantype, $totalLoanAmount, $interestrate, $loannumber, $startdate, $enddate);
            $stmt->execute();

            if ($stmt->affected_rows > 0) {
                echo "<script>alert('Loan successfully applied. ');</script>";
            } else {
                echo "<script>alert('Error applying loan');</script>";
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['payment_amount']) && isset($_POST['loan_number'])) {
    $payment_amount = floatval($_POST['payment_amount']);
    $loan_number = $_POST['loan_number'];

    function getAccountBalance($conn, $username) {
        $balance_sql = "SELECT balance FROM account WHERE username = ?";
        $balance_stmt = $conn->prepare($balance_sql);
        $balance_stmt->bind_param("s", $username);
        $balance_stmt->execute();
        $balance_result = $balance_stmt->get_result();
        if ($balance_row = $balance_result->fetch_assoc()) {
            return $balance_row['balance'];
        }
        return 0;
    }

    // Get account balance
    $account_balance = getAccountBalance($conn, $username);

    // Check if the account has sufficient balance
    if ($payment_amount > $account_balance) {
        echo json_encode(['success' => false, 'message' => 'Insufficient account balance']);
        exit();
    }

    // Get current loan details
    $get_loan_sql = "SELECT loannumber, loanamount, loanamountpaid FROM loan WHERE username = ? AND loannumber = ?";
    $get_loan_stmt = $conn->prepare($get_loan_sql);
    $get_loan_stmt->bind_param("ss", $username, $loan_number);
    $get_loan_stmt->execute();
    $loan_result = $get_loan_stmt->get_result();

    if ($loan_row = $loan_result->fetch_assoc()) {
        $current_loan_amount = $loan_row['loanamount'];
        $current_paid_amount = $loan_row['loanamountpaid'];
        $remaining_balance = $current_loan_amount - $current_paid_amount;

        if ($payment_amount <= $remaining_balance && $payment_amount > 0) {
            $new_paid_amount = $current_paid_amount + $payment_amount;
            $new_account_balance = $account_balance - $payment_amount;

            // Start transaction
            $conn->begin_transaction();

            try {
                // Update loan
                $update_loan_sql = "UPDATE loan SET loanamountpaid = ? WHERE username = ? AND loannumber = ?";
                $update_loan_stmt = $conn->prepare($update_loan_sql);
                $update_loan_stmt->bind_param("dss", $new_paid_amount, $username, $loan_number);
                $update_loan_stmt->execute();

                // Update account balance
                $update_balance_sql = "UPDATE account SET balance = ? WHERE username = ?";
                $update_balance_stmt = $conn->prepare($update_balance_sql);
                $update_balance_stmt->bind_param("ds", $new_account_balance, $username);
                $update_balance_stmt->execute();

                // Commit transaction
                $conn->commit();

                echo json_encode(['success' => true, 'message' => 'Payment processed successfully']);
            } catch (Exception $e) {
                // Rollback transaction on error
                $conn->rollback();
                echo json_encode(['success' => false, 'message' => 'Error processing payment: ' . $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Invalid payment amount']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'No active loan found']);
    }

    exit();
}

$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - UserLoan</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background-color: #f0f4f8;
            color: #333;
        }
        
        .dashboard {
            max-width: 1200px;
            margin: 20px auto;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 40px;
            background-color: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
        }
        
        .nav {
            display: flex;
            gap: 20px;
        }
        
        .nav a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
            position: relative;
            padding: 5px 10px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }
        
        .nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.1);
            transform: skew(-10deg);
            transition: all 0.3s ease;
            z-index: -1;
            opacity: 0;
        }
        
        .nav a:hover::before {
            opacity: 1;
            transform: skew(-10deg) translateY(-3px);
        }
        
        .nav a:hover {
            transform: translateY(-3px);
            text-shadow: 3px 3px 6px rgba(0,0,0,0.4);
        }
        
        .nav a.active {
            background: rgba(255,255,255,0.2);
            border-radius: 5px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            gap: 10px;
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
            color: #1e3c72;
        }
        
        .main-content {
            display: flex;
            padding: 40px;
            background-color: white;
            border-radius: 20px 20px 0 0;
        }
        
        .left-panel {
            flex: 2;
            padding-right: 40px;
        }
        
        .right-panel {
            flex: 1;
            background-color: #f8f9ff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: -5px 0 15px rgba(0, 0, 0, 0.05);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .right-panel h3 {
            margin-bottom: 20px; /* Add this line to create space below the heading */
        }
        
        .loan-form {
            background-color: #f8f9ff;
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .loan-form h3 {
            margin-bottom: 15px;
            color: #1e3c72;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group input:disabled {
            background-color: #e9ecef;
        }
        
        .button {
            background-color: #1e3c72;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            margin-top: 15px;
        }
        
        .button:hover {
            background-color: #2a5298;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .loan-cards {
            display: flex;
            flex-direction: column;
            gap: 30px;
            perspective: 1000px;
            margin-top: 20px; /* Add this line to create space above the loan cards */
        }
        
        .loan-card {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border-radius: 15px;
            padding: 20px;
            position: relative;
            overflow: hidden;
            transition: all 0.3s ease;
            transform-style: preserve-3d;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }
        
        .loan-card:hover {
            transform: translateY(-10px) rotateX(5deg) rotateY(5deg);
            box-shadow: 0 20px 30px rgba(0, 0, 0, 0.2);
        }
        
        .loan-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                135deg,
                rgba(255, 255, 255, 0.3) 0%,
                rgba(255, 255, 255, 0) 60%
            );
            z-index: 1;
        }
        
        .loan-card h4 {
            margin-bottom: 10px;
            font-size: 1.2em;
            position: relative;
            z-index: 2;
        }
        
        .loan-card p {
            margin-bottom: 5px;
            font-size: 0.9em;
            position: relative;
            z-index: 2;
        }
        
        .loan-card .pay-button {
            position: absolute;
            bottom: 10px;
            right: 10px;
            background-color: white;
            color: #1e3c72;
            border: none;
            padding: 5px 15px;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            z-index: 2;
        }
        
        .loan-card .pay-button:hover {
            background-color: #f0f4f8;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .loan-summary {
            background-color: #e6e9f0;
            border-radius: 15px;
            padding: 20px;
            margin-top: 30px;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.05);
            transform: translateZ(20px);
        }
        
        .loan-summary h4 {
            color: #1e3c72;
            margin-bottom: 10px;
        }
        
        .loan-summary p {
            margin-bottom: 5px;
            font-size: 0.9em;
        }
        
        .loan-progress {
            margin-top: 10px;
            height: 10px;
            background-color: #d1d9e6;
            border-radius: 5px;
            overflow: hidden;
            position: relative;
        }
        
        .loan-progress-bar {
            height: 100%;
            background-color: #4caf50;
            width: 0;
            transition: width 0.5s ease-in-out;
            position: relative;
        }
        
        .loan-progress-bar::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(
                90deg,
                rgba(255, 255, 255, 0.1) 0%,
                rgba(255, 255, 255, 0.3) 50%,
                rgba(255, 255, 255, 0.1) 100%
            );
            animation: shimmer 2s infinite;
        }
        
        @keyframes shimmer {
            0% {
                transform: translateX(-100%);
            }
            100% {
                transform: translateX(100%);
            }
        }

        .modal {
        display: none; /* Hidden by default */
        position: fixed;
        z-index: 1000;
        left: 0;
        top: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0,0,0,0.6);
    }

    .modal.show {
        display: flex;
        justify-content: center;
        align-items: center;
    }

    .modal-content {
        background: linear-gradient(135deg, #1e3c72, #2a5298);
        color: white;
        padding: 40px;
        border-radius: 20px;
        width: 500px;
        max-width: 90%;
        box-shadow: 0 10px 30px rgba(0,0,0,0.3);
        position: relative;
    }

    .modal-content h2 {
        margin-bottom: 30px;
        font-size: 28px;
        text-align: center;
        color: #ffffff;
        text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
    }

    .modal-content p {
        margin-bottom: 25px;
        font-size: 20px;
        text-align: center;
        color: #e0e0e0;
    }

    .modal-content input[type="number"] {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 8px;
        font-size: 18px;
        background-color: rgba(255, 255, 255, 0.15);
        color: white;
        margin-bottom: 25px;
        transition: background-color 0.3s ease;
    }

    .modal-content input[type="number"]:focus {
        background-color: rgba(255, 255, 255, 0.25);
        outline: none;
    }

    .modal-content button {
        width: 100%;
        padding: 15px;
        border: none;
        border-radius: 30px;
        font-size: 18px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
        background-color: #4CAF50;
        color: white;
        text-transform: uppercase;
        letter-spacing: 1px;
    }

    .modal-content button:hover {
        background-color: #45a049;
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.2);
    }

    .close {
        position: absolute;
        top: 15px;
        right: 20px;
        color: #ffffff;
        font-size: 32px;
        font-weight: bold;
        cursor: pointer;
        transition: all 0.3s ease;
    }

    .close:hover,
    .close:focus {
        color: #f0f0f0;
        text-decoration: none;
        transform: scale(1.1);
    }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="dashboard.php">Home</a>
                <a href="card.php">Cards</a>
                <a href="account.php">Accounts</a>
                <a href="transaction.php">Transactions</a>
                <a href="loan.php" class="active">Loans</a>
                <a href="feedback.php">Feedback</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
            <div class="user-info">
                <div class="user-avatar"><?php echo $firstLetter; ?></div>
                <span><?php echo $username; ?></span>
            </div>
        </header>
        <main class="main-content">
            <div class="left-panel">
                <h2>Apply for a Loan</h2>
                <div class="loan-form">
                    <h3>Loan Application</h3>
                    <form method="post">
                        <div class="form-group">
                            <label for="loan-type">Loan Type</label>
                            <select id="loan-type" name="loantype" onchange="updateInterestRate()">
                                <option value="Personal Loan">Personal Loan</option>
                                <option value="Home Loan">Home Loan</option>
                                <option value="Business Loan">Business Loan</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="loan-amount">Loan Amount</label>
                            <input type="number" id="loan-amount" name="loanamount" min="10" step="1" required>
                        </div>
                        <div class="form-group">
                            <label for="interest-rate">Interest Rate</label>
                            <input type="text" id="interest-rate" name="interestrate" readonly value="">
                        </div>
                        <button type="submit" class="button" name="process" >Process Loan</button>
                    </form>
                </div>
            </div>
            <div class="right-panel">
                <h3>Current Loans</h3>
                <div class="loan-cards">
                    <?php 
                        $activeLoanCount = 0;
                        if (!empty($loans)): 
                            foreach ($loans as $loan): 
                                $remainingBalance = $loan['loanamount'] - $loan['loanamountpaid'];
                                if ($remainingBalance > 0):
                                    $activeLoanCount++;
                    ?>
                    <div class="loan-card" data-loan-number="<?php echo $loan['loannumber']; ?>">
                        <h3><?php echo $loan['loantype']; ?></h3>
                        <p>Loan Amount: ₹ <?php echo $loan['loanamount']; ?></p>
                        <p>Interest Rate: <?php echo $loan['interestrate']; ?>%</p>
                        <p>Remaining Balance: ₹ <?php echo $loan['loanamount'] - $loan['loanamountpaid']; ?></p>
                        <button class="pay-button">Pay</button>
                    </div>
                    <?php 
                        endif;
                        endforeach; 
                        endif; 
                        if ($activeLoanCount == 0):
                    ?>
                    <p>You currently have no active loans.</p>
                    <?php endif; ?>
                </div>
                <div id="myModal" class="modal">
                    <div class="modal-content">
                        <span class="close">&times;</span>
                        <h2>Make a Payment</h2>
                        <p>Remaining Balance: <span id="remainingBalance"></span></p>
                        <input type="hidden" id="currentLoanId" value="">
                        <input type="number" id="paymentAmount" min="1">
                        <button id="confirmPayment">Confirm Payment</button>
                    </div>
                </div>
                <div class="loan-summary">
                    <h4>Loan Summary</h4>
                    <p>Total Loans: <?php echo $total_loans; ?></p>
                    <p>Total Amount: ₹ <?php echo number_format($total_amount, 2); ?></p>
                    <p>Total Remaining: ₹ <?php echo number_format($total_remaining, 2); ?></p>
                    <div class="loan-progress">
                        <div class="loan-progress-bar" style="width: <?php echo $progress_percentage; ?>%;"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
    function updateInterestRate() {
        const loanType = document.getElementById('loan-type').value;
        const interestRateInput = document.getElementById('interest-rate');
        
        let interestRate;
        let loanTerm;
        switch(loanType) {
            case 'Personal Loan':
                interestRate = '11%';
                loanTerm = '2 years';
                break;
            case 'Home Loan':
                interestRate = '9.5%';
                loanTerm = '10 years';
                break;
            case 'Business Loan':
                interestRate = '16%';
                loanTerm = '4 years';
                break;
            default:
                interestRate = '5.99%';
                loanTerm = '1 year';
        }
        
        interestRateInput.value = interestRate + ' for ' + loanTerm;
    }

    // Call updateInterestRate when the page loads
    document.addEventListener('DOMContentLoaded', function() {
        updateInterestRate();
    });

    var modal = document.getElementById("myModal");
        var payButtons = document.querySelectorAll(".pay-button");
        var span = document.getElementsByClassName("close")[0];
        var paymentAmountInput = document.getElementById("paymentAmount");
        var confirmPaymentButton = document.getElementById("confirmPayment");
        var remainingBalanceElement = document.getElementById("remainingBalance");
        var currentLoanIdInput = document.getElementById("currentLoanId");
    
        function closeModal() {
            modal.classList.remove('show');
        }
        Array.from(payButtons).forEach(function(button) {
        button.onclick = function() {
            var loanCard = this.closest('.loan-card');
            var remainingBalance = loanCard.querySelector('p:last-of-type').textContent.split('₹')[1].trim();
            var loanNumber = loanCard.dataset.loanNumber;
            remainingBalanceElement.textContent = '₹ ' + remainingBalance;
            currentLoanIdInput.value = loanNumber;
            modal.classList.add('show');
        }
        });
        // Close the modal when clicking on <span> (x)
        span.onclick = closeModal;

        // Close the modal when clicking outside of it
        window.onclick = function(event) {
            if (event.target == modal) {
                closeModal();
            }
        }
        confirmPaymentButton.addEventListener("click", function() {
            var paymentAmount = parseFloat(paymentAmountInput.value);
            var remainingBalance = parseFloat(remainingBalanceElement.textContent.split('₹')[1].trim());
            var loanNumber = currentLoanIdInput.value;

            if (paymentAmount <= remainingBalance && paymentAmount > 0) {
                fetch('loan.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `payment_amount=${paymentAmount}&loan_number=${loanNumber}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Payment successful!');
                        location.reload(); // Reload the page to reflect the updated loan amounts
                    } else {
                        alert('Payment failed: ' + data.message);
                    }
                })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('An error occurred while processing the payment.');
                });
            } else {
                alert("Invalid payment amount!");
            }
        });
    </script>
</body>
</html>