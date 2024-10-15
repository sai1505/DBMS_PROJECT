<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        header("Location: login.php"); // Redirect to login page if not logged in
        exit();
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

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

    // Fetch balance
    $balance_sql = "SELECT balance FROM account WHERE username = ?";
    $balance_stmt = $conn->prepare($balance_sql);
    $balance_stmt->bind_param("s", $username);
    $balance_stmt->execute();
    $balance_result = $balance_stmt->get_result();

    if ($balance_row = $balance_result->fetch_assoc()) {
        $balance = number_format($balance_row['balance'], 2);
    } else {
        $balance = "0.00";
    }

    $balance_stmt->close();

    // Fetch recent transactions
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
        LIMIT 5
    ");
    $stmt->bind_param("iii", $user_account, $user_account, $user_account);
    $stmt->execute();
    $result = $stmt->get_result();
    $recent_transactions = $result->fetch_all(MYSQLI_ASSOC);

    $card_count_sql = "SELECT COUNT(*) as card_count FROM card WHERE username = ?";
    $card_count_stmt = $conn->prepare($card_count_sql);
    $card_count_stmt->bind_param("s", $username);
    $card_count_stmt->execute();
    $card_count_result = $card_count_stmt->get_result();
    $card_count = $card_count_result->fetch_assoc()['card_count'];
    $card_count_stmt->close();

    // Fetch total credits and debits
    $totals_sql = "SELECT 
        SUM(CASE WHEN toaccountnumber = ? THEN amount ELSE 0 END) as total_credit,
        SUM(CASE WHEN fromaccountnumber = ? THEN amount ELSE 0 END) as total_debit
    FROM transaction
    WHERE ? IN (fromaccountnumber, toaccountnumber)";
    $totals_stmt = $conn->prepare($totals_sql);
    $totals_stmt->bind_param("iii", $user_account, $user_account, $user_account);
    $totals_stmt->execute();
    $totals_result = $totals_stmt->get_result();
    $totals = $totals_result->fetch_assoc();
    $total_credit = $totals['total_credit'];
    $total_debit = $totals['total_debit'];
    $totals_stmt->close();

    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - UserDashboard</title>
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
    background-color: #f0f4f8;
    border-radius: 20px 20px 0 0;
}

.left-panel {
    flex: 3;
    padding-right: 40px;
}

.right-panel {
    flex: 1;
}

.balance {
    font-size: 48px;
    font-weight: bold;
    margin-bottom: 30px;
    color: #1e3c72;
}

.summary-section {
    display: flex;
    justify-content: space-between;
    gap: 20px;
    margin-bottom: 30px;
}

.summary-card {
    flex: 1;
    background: linear-gradient(135deg, #ffffff, #ffffff, #396afc);
    border-radius: 15px;
    padding: 20px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    color: linear-gradient(135deg, #396afc, #2948ff);
    position: relative;
}

.summary-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
}

.summary-card .card-name {
    position: absolute;
    top: 10px;
    left: 20px;
    font-size: 18px;
    font-weight: bold;
    color: white;
}

.transaction-history {
    padding: 20px;
    background-color: #f7f7f7;
    border-radius: 15px;
    box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
}

.transaction-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px;
    border-bottom: 1px solid #e5e5e5;
}

.transaction-item:last-child {
    border-bottom: none;
}

.transaction-info {
    display: flex;
    gap: 10px;
    align-items: center;
}

.transaction-user {
    font-weight: bold;
    color: #1e3c72;
}

.transaction-date {
    color: #666;
}

.transaction-amount {
    font-weight: bold;
    font-size: 18px;
}

.credit {
    color: #2ecc71;
}

.debit {
    color: #e74c3c;
}

.card {
    background: linear-gradient(135deg, #1e3c72, #2a5298);
    color: white;
    border-radius: 15px;
    padding: 25px;
    margin-bottom: 20px;
    transition: all 0.3s ease;
}

.card:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
}

.card .card-name {
    font-size: 18px;
    font-weight: bold;
    margin-bottom: 10px;
}

.card .card-balance {
    font-size: 24px;
    font-weight: bold;
    margin-bottom: 20px;
}

.button {
    background-color: white;
    color: #1e3c72;
    border: none;
    padding: 10px 20px;
    border-radius: 25px;
    cursor: pointer;
    transition: all 0.3s ease;
    font-weight: bold;
    margin-top: 15px;
}

.button:hover {
    background-color: #f0f4f8;
    transform: translateY(-2px);
    box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
}

.fade-in-up {
    animation: fade-in-up 0.5s ease;
}

@keyframes fade-in-up {
    0% {
        opacity: 0;
        transform: translateY(20px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}
    </style>
</head>
<body>
    <div class="dashboard">
        <header class="header">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="dashboard.php" class="active">Home</a>
                <a href="card.php">Cards</a>
                <a href="account.php">Accounts</a>
                <a href="transaction.php">Transactions</a>
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
        <div class="left-panel">
            <h2>Welcome back, <?php echo $username; ?>!</h2>
            <div class="balance fade-in-up">₹ <?php echo $balance; ?></div>
            <div class="summary-section fade-in-up">
                <div class="summary-card">
                    <h3>Cards</h3>
                    <p class="summary-value"><?php echo $card_count; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Credit</h3>
                    <p class="summary-value credit">₹<?php echo $total_credit; ?></p>
                </div>
                <div class="summary-card">
                    <h3>Total Debit</h3>
                    <p class="summary-value debit">₹<?php echo $total_debit; ?></p>
                </div>
            </div>
            <div class="transaction-history fade-in-up">
                <h3>Recent Transactions</h3>
                <?php foreach ($recent_transactions as $transaction): ?>
                    <?php
                    $is_credit = $transaction['transaction_type'] == 'credit';
                    $other_user = $transaction['other_username'];
                    $amount = number_format($transaction['amount'], 2);
                    $date = date('M d, Y', strtotime($transaction['transactiondate']));
                    ?>
                    <div class="transaction-item <?php echo $is_credit ? 'credit' : 'debit'; ?>">
                        <div class="transaction-info">
                            <span class="transaction-user"><?php echo htmlspecialchars($other_user); ?></span>
                        </div>
                        <span class="transaction-amount <?php echo $is_credit ? 'credit' : 'debit'; ?>">
                            <?php echo $is_credit ? '+' : '-'; ?>₹<?php echo $amount; ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

            <div class="right-panel">
                <div class="card fade-in-up">
                    <h3>Cards</h3>
                    <p>Apply for our premium cards with rewards.</p>
                    <button class="button" data-href="card.php">Apply Now</button>
                </div>
                <div class="card fade-in-up">
                    <h3>Loans</h3>
                    <p>Get a loan with competitive interest rates.</p>
                    <button class="button" data-href="loan.php">Check Eligibility</button>
                </div>
                <div class="card fade-in-up">
                    <h3>Feedback</h3>
                    <p>Give a feedback to improvise our banking system.</p>
                    <button class="button" data-href="feedback.php">Give Feedback</button>
                </div>
            </div>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
        // Add click event listeners to all links
        document.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.getAttribute('href').charAt(0) !== '#') {
                        document.body.classList.add('fade-out');
                        setTimeout(() => {
                            window.location = this.href;
                        }, 500);
                    }
                });
            });
        });
        document.addEventListener('DOMContentLoaded', () => {
            // Add click event listeners to all links
            document.querySelectorAll('a').forEach(link => {
                link.addEventListener('click', function(e) {
                    if (this.getAttribute('href').charAt(0) !== '#') {
                        document.body.classList.add('fade-out');
                        setTimeout(() => {
                            window.location = this.href;
                        }, 500);
                    }
                });
            });
            
            // Add click event listeners to buttons with data-href attribute
            document.querySelectorAll('button[data-href]').forEach(button => {
                button.addEventListener('click', function(e) {
                    const href = this.getAttribute('data-href');
                    document.body.classList.add('fade-out');
                    setTimeout(() => {
                        window.location = href;
                    }, 500);
                });
            });
        });

        // Add click event listeners to buttons with data-href attribute
        document.querySelectorAll('button[data-href]').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                const href = this.getAttribute('data-href');
                document.body.classList.add('fade-out');
                setTimeout(() => {
                    window.location = href;
                }, 500);
            });
        });
    </script>
</body>
</html>