<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        header("Location: login.php"); // Redirect to login page if not logged in
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

    // Check if the form is submitted
    if (isset($_POST['submit'])) {
        // Get the feedback from the form
        $feedback = $_POST['feedback'];

        // Get current date and time
        $currentDate = date('Y-m-d H:i:s');
        
        // Prepare and bind
        $stmt = $conn->prepare("INSERT INTO feedback (username, feedbackinfo, feedbackdate) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $username, $feedback, $currentDate);
        
        // Execute the statement
        if ($stmt->execute()) {
            echo "<script>alert('Feedback Submitted Successfully!!');</script>";
        } else {
            echo "<script>alert('There is an error right now, please try after some time.');</script>";
        }
        
        // Close statement
        $stmt->close();
    }

    // Close connection
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - UserFeedback</title>
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
        }
        
        .feedback-form {
            background-color: white;
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }
        
        .form-group textarea {
            height: 150px;
            resize: vertical;
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
            font-size: 16px;
        }
        
        .button:hover {
            background-color: #2a5298;
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0, 0, 0, 0.1);
        }
        
        .image-container {
            margin-top: 20px;
            border-radius: 15px;
            overflow: hidden;
        }
        
        .image-container img {
            width: 100%;
            height: auto;
            display: block;
        }
        
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        .fade-in-up {
            animation: fadeInUp 0.5s ease-out forwards;
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
                <a href="account.php">Accounts</a>
                <a href="transaction.php">Transactions</a>
                <a href="loan.php">Loans</a>
                <a href="feedback.php" class="active">Feedback</a>
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
                <h2>We Value Your Feedback</h2>
                <p>Your opinion matters to us. Please take a moment to share your thoughts and help us improve our services.</p>
                <div class="feedback-form fade-in-up">
                    <form id="feedbackForm" method="post">
                        <div class="form-group">
                            <label for="message">Your Feedback:</label>
                            <textarea id="message" name="feedback" required></textarea>
                        </div>
                        <button type="submit" class="button" name="submit">Submit Feedback</button>
                    </form>
                </div>
            </div>
            <div class="right-panel">
                <h3>Why Your Feedback Matters</h3>
                <p>At VirtualBank, we're committed to providing the best possible experience for our customers. Your feedback helps us:</p>
                <ul>
                    <li>Improve our services</li>
                    <li>Develop new features</li>
                    <li>Enhance customer support</li>
                    <li>Make informed decisions</li>
                </ul>
                <div class="image-container fade-in-up">
                    <img src="https://t3.ftcdn.net/jpg/06/77/37/32/240_F_677373260_lLpYceGua0EeAl0fcOSA1oxyinG3vGOM.jpg" alt="Customer Feedback">
                </div>
            </div>
        </main>
    </div>
    <script>
    </script>
</body>
</html>