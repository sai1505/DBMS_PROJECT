<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
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

    $stmt = $conn->prepare("SELECT balance FROM account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $fromBalance= $row['balance'];

    // Process form submission
    if (isset($_POST['transfer'])) {
        $fromAccount = $_POST['fromaccount'];
        $toAccount = $_POST['toaccount'];
        $amount = floatval($_POST['amount']);

        // Validate input
        if (empty($fromAccount) || empty($toAccount) || $amount <= 0) {
            echo "<script>alert('Invalid input. Please check all fields.');</script>";
            exit();
        }

        // Check if accounts exist and belong to the logged-in user
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM account WHERE accountnumber = ? AND username = ?");
        $stmt->bind_param("is", $fromAccount, $username);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['count'] == 0) {
            echo "<script>alert('This account does not exist or does not belong to you.');</script>";
            exit();
        }

        // Check if to_account exists (it can belong to any user)
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM account WHERE accountnumber = ?");
        $stmt->bind_param("i", $toAccount);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        if ($row['count'] == 0) {
            echo "<script>alert('Receiver account does not exist.');</script>";
            exit();
        }

        if ($fromBalance === false || $fromBalance < $amount) {
            echo "<script>alert('Insufficient funds.');</script>";
            exit();
        }

        // Perform the transfer
        $conn->begin_transaction();
        $transactiondate = date('Y-m-d');

        try {
            // Deduct from 'from' account
            $stmt = $conn->prepare("UPDATE account SET balance = balance - ?, debit = debit + ? WHERE accountnumber = ?");
            $stmt->bind_param("iii", $amount, $amount, $fromAccount);
            $stmt->execute();

            // Add to 'to' account
            $stmt = $conn->prepare("UPDATE account SET balance = balance + ?, credit = credit + ? WHERE accountnumber = ?");
            $stmt->bind_param("iii", $amount, $amount, $toAccount);
            $stmt->execute();

            // Record the transaction
            $stmt = $conn->prepare("INSERT INTO transaction (username, fromaccountnumber, toaccountnumber, amount, transactiondate) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("siiis", $username, $fromAccount, $toAccount, $amount, $transactiondate);
            $stmt->execute();

            $conn->commit();
            echo "<script>alert('Transfer Successful!!');</script>";
        } catch (Exception $e) {
            echo "<script>alert('An error ocurred. Transfer Failed!!');</script>";
        }
    }

    $stmt = $conn->prepare("SELECT accountnumber FROM account WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_account = $result->fetch_assoc()['accountnumber'];

    // Fetch recent transactions
    $stmt = $conn->prepare("
        SELECT 
            t.id,
            t.amount, 
            t.transactiondate, 
            t.fromaccountnumber, 
            t.toaccountnumber, 
            CASE 
                WHEN t.fromaccountnumber = ? THEN a2.username 
                ELSE a1.username 
            END as other_username,
            CASE
                WHEN t.toaccountnumber = ? THEN 'credit'
                ELSE 'debit'
            END as transaction_type
        FROM transaction t
        LEFT JOIN account a1 ON t.fromaccountnumber = a1.accountnumber
        LEFT JOIN account a2 ON t.toaccountnumber = a2.accountnumber
        WHERE ? IN (t.fromaccountnumber, t.toaccountnumber)
        ORDER BY t.transactiondate DESC, t.id DESC
        LIMIT 3
    ");
    $stmt->bind_param("iii", $user_account, $user_account, $user_account);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_transactions = $result->fetch_all(MYSQLI_ASSOC);

    // Fetch sum of credits and debits
    $stmt = $conn->prepare("SELECT SUM(credit) as total_credit, SUM(debit) as total_debit FROM account WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();
    $total_credit = $row['total_credit'];
    $total_debit = $row['total_debit'];
    
    $stmt = $conn->prepare("SELECT accountnumber FROM account WHERE username = ? LIMIT 1");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();
    $user_account = $result->fetch_assoc()['accountnumber'];

    // Fetch all transactions
    $stmt = $conn->prepare("
        SELECT 
            t.id,
            t.amount, 
            t.transactiondate, 
            t.fromaccountnumber, 
            t.toaccountnumber, 
            CASE 
                WHEN t.fromaccountnumber = ? THEN a2.username 
                ELSE a1.username 
            END as other_username,
            CASE
                WHEN t.toaccountnumber = ? THEN 'credit'
                ELSE 'debit'
            END as transaction_type
        FROM transaction t
        LEFT JOIN account a1 ON t.fromaccountnumber = a1.accountnumber
        LEFT JOIN account a2 ON t.toaccountnumber = a2.accountnumber
        WHERE ? IN (t.fromaccountnumber, t.toaccountnumber)
        ORDER BY t.transactiondate DESC, t.id DESC
    ");
    $stmt->bind_param("iii", $user_account, $user_account, $user_account);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_transactions = $result->fetch_all(MYSQLI_ASSOC);
    $conn->close();

    if(isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        header('Content-Type: application/json');
        echo json_encode($all_transactions);
        exit;
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - UserTransaction</title>
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
        
        .balance-section {
            flex: 1;
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            border-radius: 20px;
            padding: 30px;
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 400px;
        }
        
        .balance-header {
            text-align: center;
        }
        
        .balance-title {
            font-size: 24px;
            margin-bottom: 10px;
            opacity: 0;
            transform: translateY(-20px);
            transition: opacity 0.5s, transform 0.5s;
        }
        
        .balance-amount {
            font-size: 48px;
            font-weight: bold;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            margin-bottom: 20px;
            opacity: 0;
            transform: scale(0.9);
            transition: opacity 0.5s, transform 0.5s;
        }
        
        .balance-chart {
            flex-grow: 1;
            display: flex;
            justify-content: space-around;
            align-items: flex-end;
            margin-top: 30px;
        }
        
        .chart-bar {
            width: 60px;
            background-color: rgba(255, 255, 255, 0.2);
            border-radius: 5px 5px 0 0;
            position: relative;
            transition: height 1s ease-out;
        }
        
        .chart-bar::before {
            content: attr(data-amount);
            position: absolute;
            top: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
            font-weight: bold;
        }
        
        .chart-bar.credit {
            background-color: rgba(76, 217, 100, 0.7);
        }
        
        .chart-bar.debit {
            background-color: rgba(255, 59, 48, 0.7);
        }
        
        .chart-label {
            position: absolute;
            bottom: -25px;
            left: 50%;
            transform: translateX(-50%);
            font-size: 14px;
        }
        
        .transaction-section {
            flex: 2;
            margin-left: 40px;
        }
        
        .transaction-form {
            background-color: #f8f9ff;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .transaction-form h3 {
            margin-bottom: 20px;
            color: #1e3c72;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }
        
        .submit-btn {
            background-color: #1e3c72;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        
        .submit-btn:hover {
            background-color: #2a5298;
        }
        
        .recent-transactions {
            background-color: #f8f9ff;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .recent-transactions h3 {
            margin-bottom: 20px;
            color: #1e3c72;
        }
        
        .transaction-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-left: 5px solid;
            margin-bottom: 10px;
            background-color: white;
            border-radius: 0 10px 10px 0;
            transition: all 0.3s ease;
        }
        
        .transaction-item:hover {
            transform: translateX(5px);
        }
        
        .transaction-item.credit {
            border-left-color: #4cd964;
        }
        
        .transaction-item.debit {
            border-left-color: #ff3b30;
        }
        
        .transaction-amount {
            font-weight: bold;
        }
        
        .transaction-amount.credit {
            color: #4cd964;
        }
        
        .transaction-amount.debit {
            color: #ff3b30;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.4);
            overflow: hidden;
        }

        .modal-content {
            background-color: #fefefe;
            padding: 20px;
            border: 1px solid #888;
            width: 80%;
            max-width: 600px;
            border-radius: 10px;
            max-height: 80vh;
            overflow-y: auto;
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }

        .modal-header h2 {
            margin: 0;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover,
        .close:focus {
            color: black;
            text-decoration: none;
            cursor: pointer;
        }

                .view-all-btn {
            display: inline-block;
            background-color: #1e3c72;
            color: white;
            text-decoration: none;
            padding: 10px 20px;
            border-radius: 5px;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .view-all-btn:hover {
            background-color: #2a5298;
            transform: translateY(-2px);
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="dashboard.php">Home</a>
                <a href="card.php" >Cards</a>
                <a href="account.php" >Accounts</a>
                <a href="transaction.php" class="active">Transactions</a>
                <a href="loan.php">Loans</a>
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
            <div class="balance-section">
                <div class="balance-header">
                    <h2 class="balance-title">Current Balance</h2>
                    <div class="balance-amount">₹ <?php echo $fromBalance; ?></div>
                </div>
                <div class="balance-chart">
                    <div class="chart-bar credit" data-amount="<?php echo number_format($total_credit, 2); ?>">
                        <div class="chart-label">Credit</div>
                    </div>
                    <div class="chart-bar debit" data-amount="<?php echo number_format($total_debit, 2); ?>">
                        <div class="chart-label">Debit</div>
                    </div>
                </div>
            </div>
            <div class="transaction-section">
                <div class="transaction-form">
                    <h3>Perform Transaction</h3>
                    <form method="post">
                        <div class="form-group">
                            <label for="from-account">From Account Number</label>
                            <input type="text" id="from-account" name="fromaccount" required>
                        </div>
                        <div class="form-group">
                            <label for="to-account">To Account Number</label>
                            <input type="text" id="to-account" name="toaccount" required>
                        </div>
                        <div class="form-group">
                            <label for="amount">Amount</label>
                            <input type="number" id="amount" name="amount" required>
                        </div>
                        <button type="submit" class="submit-btn" name="transfer" >Transfer</button>
                    </form>
                </div>
                <div class="recent-transactions">
                    <h3>Recent Transactions</h3>
                    <?php foreach ($recent_transactions as $transaction): ?>
                        <?php
                        $is_credit = $transaction['transaction_type'] == 'credit';
                        $other_user = $transaction['other_username'];
                        $amount = number_format($transaction['amount'], 2);
                        $date = date('Y-m-d H:i:s', strtotime($transaction['transactiondate']));
                        ?>
                        <div class="transaction-item <?php echo $is_credit ? 'credit' : 'debit'; ?>">
                            <span><?php echo htmlspecialchars($other_user); ?></span>
                            <span class="transaction-amount <?php echo $is_credit ? 'credit' : 'debit'; ?>">
                                <?php echo $is_credit ? '+' : '-'; ?>₹<?php echo $amount; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                    <button id="viewAllTransactionsBtn" class="view-all-btn">View All Transactions</button>
                </div>
                <div id="transactionModal" class="modal">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h2>All Transactions</h2>
                            <span class="close">&times;</span>
                        </div>
                        <div id="allTransactions"></div>
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Animate balance title and amount
            setTimeout(() => {
                document.querySelector('.balance-title').style.opacity = '1';
                document.querySelector('.balance-title').style.transform = 'translateY(0)';
            }, 300);

            setTimeout(() => {
                document.querySelector('.balance-amount').style.opacity = '1';
                document.querySelector('.balance-amount').style.transform = 'scale(1)';
            }, 600);

            // Animate chart bars
            setTimeout(() => {
            const creditBar = document.querySelector('.chart-bar.credit');
            const debitBar = document.querySelector('.chart-bar.debit');

            const creditAmount = parseFloat(creditBar.getAttribute('data-amount').replace(',', ''));
            const debitAmount = parseFloat(debitBar.getAttribute('data-amount').replace(',', ''));
            const maxAmount = Math.max(creditAmount, debitAmount);

            const creditHeight = (creditAmount / maxAmount) * 100;
            const debitHeight = (debitAmount / maxAmount) * 100;

            // Set initial height to 0
            creditBar.style.height = '0%';
            debitBar.style.height = '0%';

            // Trigger reflow
            creditBar.offsetHeight;
            debitBar.offsetHeight;

            // Set final height with transition
            creditBar.style.transition = 'height 1s ease-out';
            debitBar.style.transition = 'height 1s ease-out';
            creditBar.style.height = `${creditHeight}%`;
            debitBar.style.height = `${debitHeight}%`;

            // Update the data-amount attribute to show the amount with currency symbol
            creditBar.setAttribute('data-amount', '₹' + creditBar.getAttribute('data-amount'));
            debitBar.setAttribute('data-amount', '₹' + debitBar.getAttribute('data-amount'));
        }, 100);
        });

        var modal = document.getElementById("transactionModal");
        var btn = document.getElementById("viewAllTransactionsBtn");
        var span = document.getElementsByClassName("close")[0];

        btn.onclick = function() {
            modal.style.display = "block";
            fetchAllTransactions();
        }

        span.onclick = function() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = "none";
            }
        }

        function fetchAllTransactions() {
            fetch(window.location.href, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                let transactionsHtml = '';
                data.forEach(transaction => {
                    const isCredit = transaction.transaction_type === 'credit';
                    transactionsHtml += `
                        <div class="transaction-item ${isCredit ? 'credit' : 'debit'}">
                            <span>${transaction.other_username}</span>
                            <span class="transaction-amount ${isCredit ? 'credit' : 'debit'}">
                                ${isCredit ? '+' : '-'}₹${parseFloat(transaction.amount).toFixed(2)}
                            </span>
                            <span>${transaction.transactiondate}</span>
                        </div>
                    `;
                });
                document.getElementById('allTransactions').innerHTML = transactionsHtml;
            })
            .catch(error => console.error('Error:', error));
        }
    </script>
</body>
</html>