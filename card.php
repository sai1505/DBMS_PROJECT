<?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        header("Location: login.php");
        exit();
    }

    $username = htmlspecialchars($_SESSION['username']);
    $firstLetter = strtoupper(substr($username, 0, 1));

    $servername = "localhost";
    $db_username = "sai2005";
    $db_password = "sai@2005";
    $dbname = "Bank";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    function generateCardNumber() {
        return mt_rand(1000000000000000, 9999999999999999);
    }

    function generateCVV() {
        return mt_rand(100, 999);
    }

    $cardNumber = generateCardNumber();
    $cvv = generateCVV();
    $lastFourDigits = $cardNumber % 10000;
    $surname = '';
    $name = '';

    $stmt = $conn->prepare("SELECT surname, name FROM userLogin WHERE username=?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $stmt->bind_result($surname, $name);

    if ($stmt->fetch()) {
        // $surname and $name now contain the retrieved values
    } else {
        // Handle the case where no data was found
        echo "No user data found for username: $username";
    }

    $stmt->close();

    $fullName = strtoupper($surname . ' ' . $name);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['applycard'])) {
        $response = array();
        
        $username = $_SESSION['username'];
        $cardType = $_POST['cardType'];
        $cardCategory = $_POST['cardCategory'];
    
        // First, check if the user already has a card of this type and category
        $checkSql = "SELECT * FROM card WHERE username = ? AND cardtype = ? AND cardcategory = ?";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bind_param("sss", $username, $cardType, $cardCategory);
        $checkStmt->execute();
        $result = $checkStmt->get_result();
    
        if ($result->num_rows > 0) {
            // User already has this type of card
            echo "<script>alert('You have already applied for a " . $cardType . " " . $cardCategory . " card.');</script>";
        } else {
            // User doesn't have this type of card, proceed with application
            $cardNumber = generateCardNumber();
            $cvv = generateCVV();
            $issueDate = date('Y-m-d');
            $expiryDate = date('Y-m-d', strtotime('+' . ($cardCategory === 'Credit' ? '5 years' : '4 years')));
    
            $sql = "INSERT INTO card (username, cardnumber, cvv, cardtype, cardcategory, issuedate, expirydate) VALUES (?, ?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("siissss", $username, $cardNumber, $cvv, $cardType, $cardCategory, $issueDate, $expiryDate);
            
            if ($stmt->execute()) {
                echo "<script>alert('Card application successful!');</script>";
            } else {
                echo "<script>alert('Error applying for card: " . $stmt->error . "');</script>";
            }
            $stmt->close();
        }
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - UserCard</title>
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
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            perspective: 1000px;
        }
        
        .card {
            width: 350px;
            height: 200px;
            position: relative;
            transform-style: preserve-3d;
            transition: transform 0.6s;
        }
        
        .card:hover {
            transform: rotateY(180deg);
        }
        
        .card-front, .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            backface-visibility: hidden;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }
        
        .card-front {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
        }

        .card-back {
            background: linear-gradient(135deg, #2a5298, #1e3c72);
            color: white;
            transform: rotateY(180deg);
        }
        
        .card-logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .card-number {
            font-size: 18px;
            letter-spacing: 2px;
        }
        
        .card-name {
            font-size: 16px;
        }
        
        .card-expiry {
            font-size: 14px;
        }
        
        .card-cvv {
            font-size: 14px;
        }
        
        .right-panel {
            flex: 1;
            padding-left: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        
        .card-details {
            background-color: #f8f9ff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .card-details h2 {
            margin-bottom: 20px;
            color: #1e3c72;
        }
        
        .card-selector {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
        }

        .card-dates {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-top: 10px;
        }

        .card-issue, .card-expiry {
            flex: 1;
            font-size: 12px;
            white-space: nowrap;
        }

        .card-issue {
            margin-right: 10px;
        }

        .card-option:hover, .card-option.active {
            transform: translateY(-5px);
            opacity: 1;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .card-option {
            width: 120px;
            height: 70px;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.3s ease;
            opacity: 0.5;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            color: white;
            font-size: 14px;
        }
        
        .card-option span {
            font-size: 12px;
            margin-top: 5px;
        }
        
        #visa-credit-option {
            background: linear-gradient(135deg, #1a237e, #42a5f5);
        }

        #visa-debit-option {
            background: linear-gradient(135deg, #004d40, #26a69a);
        }

        #mastercard-credit-option {
            background: linear-gradient(135deg, #b71c1c, #ff8a80);
        }

        #mastercard-debit-option {
            background: linear-gradient(135deg, #e65100, #ffd54f);
        }
        
        .button {
            background-color: #1e3c72;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-weight: bold;
            margin-top: 20px;
            align-self: flex-start;
        }
        
        .button:hover {
            background-color: #2a5298;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }

    </style>
</head>
<body>
    <div class="dashboard">
    <header class="header">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="dashboard.php">Home</a>
                <a href="card.php"  class="active">Cards</a>
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
                <div class="card" id="card-display">
                    <div class="card-front">
                        <div class="card-logo" id="card-logo">VISA</div>
                        <div class="card-number">**** **** **** <?php echo $lastFourDigits; ?></div>
                        <div class="card-name"><?php echo htmlspecialchars($fullName); ?></div>
                        <div class="card-dates">
                            <div class="card-issue" id="card-issue"><?php echo $issueDate; ?></div>
                            <div class="card-expiry" id="card-expiry"><?php echo $expiryDate; ?></div>
                        </div>
                    </div>
                    <div class="card-back">
                        <div class="card-cvv">***</div>
                    </div>
                </div>
            </div>
            <div class="right-panel">
                <div class="card-details">
                    <h2>Choose Your Card</h2>
                    <div class="card-selector">
                        <div class="card-option" id="visa-credit-option" onclick="selectCard('Visa', 'Credit')">
                            VISA
                            <span>Credit</span>
                        </div>
                        <div class="card-option" id="visa-debit-option" onclick="selectCard('Visa', 'Debit')">
                            VISA
                            <span>Debit</span>
                        </div>
                        <div class="card-option" id="mastercard-credit-option" onclick="selectCard('Mastercard', 'Credit')">
                            MC
                            <span>Credit</span>
                        </div>
                        <div class="card-option" id="mastercard-debit-option" onclick="selectCard('Mastercard', 'Debit')">
                            MC
                            <span>Debit</span>
                        </div>
                    </div>
                    <p id="card-description"></p>
                    <button class="button" onclick="applyForCard()" name="applycard" >Apply for Card</button>
                </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        function selectCard(cardType, cardCategory) {
            const cardOptions = document.querySelectorAll('.card-option');
            cardOptions.forEach(option => option.classList.remove('active'));
            document.getElementById(`${cardType.toLowerCase().replace(' ', '-')}-${cardCategory.toLowerCase()}-option`).classList.add('active');
            updateCard(cardType, cardCategory);
        }

        function updateCard(cardType, cardCategory) {
            const cardDisplay = document.getElementById('card-display');
            const cardLogo = document.getElementById('card-logo');
            const cardIssue = document.getElementById('card-issue');
            const cardExpiry = document.getElementById('card-expiry');
            const cardDescription = document.getElementById('card-description');

            let cardColor = '';
            let description = '';

            // Get today's date for issue date
            const today = new Date();
            const issueDate = formatDate(today);

            // Calculate expiry date based on card type
            const expiryDate = new Date(today);
            if (cardCategory === 'Credit') {
                expiryDate.setFullYear(expiryDate.getFullYear() + 5);
            } else {
                expiryDate.setFullYear(expiryDate.getFullYear() + 4);
            }

            switch(cardType) {
                case 'Visa':
                    if (cardCategory === 'Credit') {
                        cardColor = 'linear-gradient(135deg, #1a237e, #42a5f5)';
                        cardLogo.innerHTML = 'VISA';
                        description = 'Visa Credit: Accepted worldwide, perfect for international travel.';
                    } else {
                        cardColor = 'linear-gradient(135deg, #004d40, #26a69a)';
                        cardLogo.innerHTML = 'VISA';
                        description = 'Visa Debit: Wide acceptance, great for everyday purchases.';
                    }
                    break;
                case 'Mastercard':
                    if (cardCategory === 'Credit') {
                        cardColor = 'linear-gradient(135deg, #b71c1c, #ff8a80)';
                        cardLogo.innerHTML = 'MC';
                        description = 'Mastercard Credit: Premium benefits, ideal for frequent travelers.';
                    } else {
                        cardColor = 'linear-gradient(135deg, #e65100, #ffd54f)';
                        cardLogo.innerHTML = 'MC';
                        description = 'Mastercard Debit: Wide acceptance, great for everyday purchases.';
                    }
                    break;
            }

            cardDisplay.querySelector('.card-front').style.background = cardColor;
            cardDisplay.querySelector('.card-back').style.background = cardColor;
            cardIssue.textContent = `ISSUE: ${issueDate}`;
            cardExpiry.textContent = `EXPIRY: ${formatDate(expiryDate)}`;
            cardDescription.textContent = description;

            // Add animation
            cardDisplay.style.animation = 'none';
            cardDisplay.offsetHeight; // Trigger reflow
            cardDisplay.style.animation = 'cardChange 0.5s ease-in-out';
        }

        function formatDate(date) {
            const month = String(date.getMonth() + 1).padStart(2, '0');
            const year = String(date.getFullYear()).slice(-2);
            return `${month}/${year}`;
        }

        function applyForCard() {
            const selectedCard = document.querySelector('.card-option.active');
            if (selectedCard) {
                const cardType = selectedCard.textContent.trim().split('\n')[0];
                const cardCategory = selectedCard.querySelector('span').textContent;
                
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = ''; // Submit to the same page
                form.style.display = 'none';

                const cardTypeInput = document.createElement('input');
                cardTypeInput.name = 'cardType';
                cardTypeInput.value = cardType;
                form.appendChild(cardTypeInput);

                const cardCategoryInput = document.createElement('input');
                cardCategoryInput.name = 'cardCategory';
                cardCategoryInput.value = cardCategory;
                form.appendChild(cardCategoryInput);

                const applyCardInput = document.createElement('input');
                applyCardInput.name = 'applycard';
                applyCardInput.value = '1';
                form.appendChild(applyCardInput);

                document.body.appendChild(form);
                form.submit();
            } else {
                alert('Please select a card type before applying.');
            }
        }

        // Add this to your existing styles
        document.head.insertAdjacentHTML('beforeend', `
            <style>
                @keyframes cardChange {
                    0% { transform: scale(0.9) rotateY(0deg); opacity: 0.7; }
                    50% { transform: scale(1.05) rotateY(90deg); opacity: 0.9; }
                    100% { transform: scale(1) rotateY(0deg); opacity: 1; }
                }
            </style>
        `);

        // Initialize with Visa credit card selected
        selectCard('Visa', 'Credit');
        document.addEventListener('DOMContentLoaded', () => {
    });
    </script>
</body>
</html>