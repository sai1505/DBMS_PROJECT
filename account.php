<?php
    session_start();
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

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

    $sql = "SELECT * FROM userLogin WHERE username = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $userData = $result->fetch_assoc();
    } else {
        echo "Error: User not found in the database.";
        exit();
    }

    $cardSql = "SELECT * FROM card WHERE username = ?";
    $cardStmt = $conn->prepare($cardSql);
    $cardStmt->bind_param("s", $username);
    $cardStmt->execute();
    $cardResult = $cardStmt->get_result();

    $cards = [];
    while ($cardData = $cardResult->fetch_assoc()) {
        $cards[] = $cardData;
    }
    
    $ac_sql = "SELECT * FROM account WHERE username = ?";
    $ac_stmt = $conn->prepare($ac_sql);
    $ac_stmt->bind_param("s", $username);
    $ac_stmt->execute();
    $ac_result = $ac_stmt->get_result();

    if ($ac_result->num_rows > 0) {
        $accountData = $ac_result->fetch_assoc();
    } else {
        $accountData = array(
            'accountnumber' => 'N/A',
            'balance' => 'N/A'
        );
    }

    $ac_stmt->close();
    $stmt->close();
    $cardStmt->close();
    $conn->close();

    $email = $userData['email'];
    $surname = $userData['surname'];
    $name = $userData['name'];
    $address = $userData['address'];
    $phone = $userData['phone'];
    $gender = $userData['gender'];
    $dob = $userData['dateofbirth'];
    $accountOpenedDate = $userData['accountopened'];

    if ($cards) {
        $cardtype = $cards[0]['cardtype'];
        $cardcategory = $cards[0]['cardcategory'];
        $cardnumber = $cards[0]['cardnumber'];
        $issuedate = $cards[0]['issuedate'];
        $expirydate = $cards[0]['expirydate'];
        $cvv = $cards[0]['cvv'];
    } else {
        $cardtype = '';
        $cardcategory = '';
        $cardnumber = '';
        $issuedate = '';
        $expirydate = '';
    }

    // Function to format date from 'yyyy-mm-dd' to 'mm/yy'
    function format_date($date) {
        $dateParts = explode('-', $date);
        $month = $dateParts[1];
        $year = substr($dateParts[0], 2, 2); // Get the last 2 characters of the year
        return $month . '/' . $year;
    }
    
    // Convert name and surname to uppercase
    $name = strtoupper($userData['name']);
    $surname = strtoupper($userData['surname']);

    // If you want to update the $userData array as well
    $userData['name'] = $name;
    $userData['surname'] = $surname;

    foreach ($cards as &$card) {
        $cardNumber = $card['cardnumber'];
        $formattedCardNumber = substr($cardNumber, 0, 4) . ' ' . substr($cardNumber, 4, 4) . ' ' . substr($cardNumber, 8, 4) . ' ' . substr($cardNumber, 12, 4);
        $card['cardnumber'] = $formattedCardNumber;
        if (isset($card['name'])) {
            $card['name'] = strtoupper($card['name']);
        }
        if (isset($card['surname'])) {
            $card['surname'] = strtoupper($card['surname']);
        }
        $card['expirydate'] = format_date($card['expirydate']);
        $card['issuedate'] = format_date($card['issuedate']);
    }

    $cardData = !empty($cards) ? $cards[0] : null;
    $cardDataJson = json_encode($cardData);
    $cardsJson = json_encode($cards);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - UserAccount</title>
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

        .card-dates {
            display: flex;
            justify-content: space-between;
            margin-top: 10px;
        }

        .card-date {
            display: flex;
            flex-direction: column;
        }

        .date-label {
            font-size: 10px;
            opacity: 0.8;
        }

        .date-value {
            font-size: 14px;
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
            flex-direction: column;
            gap: 20px;
            max-height: 600px;
            overflow-y: auto;
            padding: 20px;
            background-color: #f8f8f8;
        }

        #cards-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
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
            margin-bottom: 20px;
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

        @keyframes cardChange {
            0% { transform: scale(0.9) rotateY(0deg); opacity: 0.7; }
            50% { transform: scale(1.05) rotateY(90deg); opacity: 0.9; }
            100% { transform: scale(1) rotateY(0deg); opacity: 1; }
        }

        .card {
            width: 350px;
            height: 200px;
            perspective: 1000px;
            margin-bottom: 20px;
        }
        
        .card-logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        .card-inner {
            width: 100%;
            height: 100%;
            transition: transform 0.6s;
            transform-style: preserve-3d;
            cursor: pointer;
            position: relative;
        }

        .card-front, .card-back {
            position: absolute;
            width: 100%;
            height: 100%;
            -webkit-backface-visibility: hidden;
            backface-visibility: hidden;
            border-radius: 15px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-front {
            background: linear-gradient(135deg, #1e3c72, #2a5298 );
            color: white;
        }

        .card-back {
            background: linear-gradient(135deg, #2a5298, #1e3c72);
            color: white;
            transform: rotateY(180deg);
        }

        .card-logo, .card-number, .card-name, .card-expiry, .card-cvv, .card-type, .card- issued {
            font-size: 14px;
            margin-bottom: 5px;
        }

        .card-number {
            font-size: 18px;
            letter-spacing: 2px;
        }

        .card-name {
            font-size: 16px;
        }

        .right-panel {
            flex: 1;
            padding-left: 40px;
            display: flex;
            flex-direction: column;
            gap: 20px;
        }

        .user-profile {
            display: flex;
            flex-direction: row;
            gap: 20px;
            align-items: center;
        }

        .user-avatar-large {
            width: 80px;
            height: 80px;
            border-radius: 50%;
            background-color: #1e3c72;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 36px;
            font-weight: bold;
            color: white;
        }

        .user-info-details {
            flex-grow: 1;
        }

        .user-name {
            font-size: 23px;
            font-weight: bold;
            color: #1e3c72;
        }

        .user-email {
            color: #666;
        }

        .account-summary {
            background-color: #f8f9ff;
            border-radius: 15px;
            padding: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }

        .account-summary h2 {
            color: #1e3c72;
            margin-bottom: 20px;
        }

        .account-details {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
        }

        .detail-item {
            background-color: white;
            padding: 15px;
            border-radius: 10px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.05);
        }

        .detail-label {
            font-size: 12px;
            color: #666;
            margin-bottom: 5px;
        }

        .detail-value {
            font-weight: bold;
            color: #333;
        }

        .no-cards {
            text-align: center;
            color: #777;
            font-style: italic;
        }

        .card-front {
            /* ... existing styles ... */
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .card-logo {
            align-self: flex-start;
        }

        .card-number {
            font-size: 18px;
            letter-spacing: 2px;
            margin: 20px 0;
        }

        .card-name {
            font-size: 16px;
            margin-bottom: 10px;
        }

        .card-dates {
            display: flex;
            justify-content: space-between;
            font-size: 12px;
            margin-top: 10px;
        }

        .card-dates span {
            flex: 1;
            font-size: 12px;
            white-space: nowrap;
        }

        .balance-box {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 20px;
            box-shadow: 0 10px 20px rgba(30, 60, 114, 0.2);
            position: relative;
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .balance-box:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(30, 60, 114, 0.3);
        }

        .balance-box::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, rgba(255,255,255,0) 70%);
            transform: rotate(30deg);
        }

        .balance-content {
            position: relative;
            z-index: 1;
        }

        .balance-label {
            font-size: 14px;
            opacity: 0.9;
            margin-bottom: 5px;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-weight: 300;
        }

        .balance-amount {
            font-size: 28px;
            font-weight: bold;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.1);
        }

        .balance-currency {
            font-size: 18px;
            vertical-align: super;
            margin-right: 3px;
            font-weight: 300;
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
                <a href="account.php" class="active">Accounts</a>
                <a href="transaction.php">Transactions</a>
                <a href="loan.php">Loans</a>
                <a href="feedback.php">Feedback</a>
                <a href="profile.php">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
            <div class="user-info">
                <div class="user-avatar" id="header-user-avatar">sfsdf</div>
                <span id="header-username"></span>
            </div>
        </header>
        <main class="main-content">
            <div class="left-panel">
                <div id="cards-container">
                    <!-- Cards will be dynamically added here -->
                </div>
            </div>
            <div class="right-panel">
                <div class="user-profile">
                    <div class="user-avatar-large" id="profile-user-avatar"></div>
                    <div class="user-info-details">
                        <div class="user-name" id="profile-user-name"></div>
                        <div class="user-email" id="profile-user-email"></div>
                    </div>
                </div>
                <div class="account-summary">
                    <h2>Account Summary</h2>
                    <div class="balance-box">
                        <div class="balance-content">
                            <div class="balance-label">Current Balance</div>
                            <div class="balance-amount">
                                <span class="balance-currency"></span>
                                <span id="current-balance"></span>
                            </div>
                        </div>
                    </div>
                    <div class="account-details" id="account-details">
                        <!-- Account details will be dynamically added here -->
                    </div>
                </div>
            </div>
        </main>
    </div>
    <script>
        const userData = {
            username: '<?php echo $username; ?>',
            email: '<?php echo $email; ?>',
            surname: '<?php echo $surname; ?>',
            name: '<?php echo $name; ?>',
            address: '<?php echo $address; ?>',
            phone: '<?php echo $phone; ?>',
            gender: '<?php echo $gender; ?>',
            dob: '<?php echo $dob; ?>',
            accountOpenedDate: '<?php echo $accountOpenedDate; ?>',
            accountnumber: '<?php echo $accountData['accountnumber']; ?>',
            balance: '<?php echo $accountData['balance']; ?>'
        };

        const cards = <?php echo $cardsJson; ?>;

        function populateUserProfile() {
            document.getElementById("header-username").textContent = userData.username;
            document.getElementById("header-user-avatar").textContent = userData.name.charAt(0).toUpperCase();
            document.getElementById("profile-user-avatar").textContent = userData.name.charAt(0).toUpperCase();
            document.getElementById("profile-user-name").textContent = `${userData.surname.toUpperCase()} ${userData.name.toUpperCase()}`;
            document.getElementById("profile-user-email").textContent = userData.email;
            document.getElementById("current-balance").textContent = `₹ ${parseFloat(userData.balance).toFixed(2)}`;
        }

        function populateAccountDetails() {
            const container = document.getElementById("account-details");
            const details = [
                { label: "Account Number", value: userData.accountnumber || "N/A" },
                { label: "Account Status", value: "Active" },
                { label: "Date Opened", value: userData.accountOpenedDate },
                { label: "Phone", value: userData.phone },
                { label: "Date of Birth", value: userData.dob },
                { label: "Address", value: userData.address },
                { label: "Gender", value: userData.gender.charAt(0).toUpperCase() + userData.gender.slice(1).toLowerCase() }
            ];

            container.innerHTML = details.map(detail => `
                <div class="detail-item">
                    <div class="detail-label">${detail.label}</div>
                    <div class="detail-value">${detail.value}</div>
                </div>
            `).join('');
        }

        const cardData = <?php echo $cardDataJson; ?>;

            console.log("User Data:", userData);
            console.log("Card Data:", cardData);

        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM fully loaded and parsed');
            populateUserProfile();
            populateAccountDetails();
            populateCardDetails();
        });

        document.addEventListener('DOMContentLoaded', () => {
            console.log('DOM loaded. Starting population...');
            
            populateUserProfile();
            populateAccountDetails();
            populateCards();
        });

        function populateCards() {
            const cardsContainer = document.getElementById("cards-container");
            if (!cardsContainer) {
                console.error('Cards container element not found!');
                return;
            }

            cardsContainer.innerHTML = '';

            if (cards.length === 0) {
                cardsContainer.innerHTML = '<p class="no-cards">No cards available.</p>';
                return;
            }

            cards.forEach((card, index) => {
                const cardElement = document.createElement('div');
                cardElement.className = 'card';

                // Determine the gradient based on card type and category
                let gradient;
                if (card.cardtype.toLowerCase() === 'visa') {
                    if (card.cardcategory.toLowerCase() === 'credit') {
                        gradient = 'linear-gradient(135deg, #1a237e, #42a5f5)';
                    } else {
                        gradient = 'linear-gradient(135deg, #004d40, #26a69a)';
                    }
                } else if (card.cardtype.toLowerCase() === 'mc') {
                    if (card.cardcategory.toLowerCase() === 'credit') {
                        gradient = 'linear-gradient(135deg, #b71c1c, #ff8a80)';
                    } else {
                        gradient = 'linear-gradient(135deg, #e65100, #ffd54f)';
                    }
                } else {
                    // Default gradient if card type is not recognized
                    gradient = 'linear-gradient(135deg, #1e3c72, #2a5298)';
                }

                cardElement.innerHTML = `
                    <div class="card-inner">
                        <div class="card-front" style="background: ${gradient};">
                            <div class="card-logo" style="font-size: 24px; font-weight: bold; margin-bottom: -5px;">${card.cardtype || 'CARD'}</div>
                            <div class="card-number" style="margin-bottom: 19px;">${card.cardnumber || 'XXXX XXXX XXXX XXXX'}</div>
                            <div class="card-name" style="margin-bottom: 15px;">${userData.surname || 'SURNAME'} ${userData.name || 'NAME'}</div>
                            <div class="card-dates" style="margin-bottom: 15px;">
                                <span>ISSUE: ${card.issuedate || 'MM/YY'}</span>
                                <span>EXPIRY: ${card.expirydate || 'MM/YY'}</span>
                            </div>
                        </div>
                        <div class="card-back" style="background: ${gradient}">
                            <div class="card-cvv">${card.cvv || 'N/A'}</div>
                            <div class="card-type" style="font-size: 20px; font-weight: bold;">${card.cardcategory || 'N/A'}</div>
                        </div>
                    </div>
                `;
                cardsContainer.appendChild(cardElement);
            });
        }
    </script>
</body>
</html>