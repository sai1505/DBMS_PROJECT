<?php
    session_start();
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

    if (isset($_POST['update'])) {
        $username = $_SESSION['username'];
        $surname=$_POST['surname'];
        $name = $_POST['name'];
        $address = $_POST['address'];
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        $dob = $_POST['dob'];
        $email = $_POST['email'];
        // Add more fields as needed

        $dob_mysql = date('Y-m-d', strtotime($dob));

        $sql = "UPDATE userLogin SET surname=?, name=?, address=?, phone=?, gender=?, dateofbirth=?, email=? WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssssssss", $surname, $name, $address, $phone, $gender, $dob_mysql, $email, $username);

        if ($stmt->execute()) {
            echo "<script>alert('Profile Updated Successfully!!');</script>";
            header("Location: dashboard.php"); // Redirect back to the profile page
            exit();
        } else {
            echo "<script>alert('Invalid Profile Update. Profile didn't update. ');</script>";
        }

        $stmt->close();
        $conn->close();
    }
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - UserProfile</title>
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
            padding: 40px;
            background-color: white;
            border-radius: 20px 20px 0 0;
        }
        
        .profile-form {
            max-width: 600px;
            margin: 0 auto;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 600;
            color: #1e3c72;
        }
        
        .form-group input,
        .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 16px;
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
                <a href="dashboard.php" >Home</a>
                <a href="card.php">Cards</a>
                <a href="account.php">Accounts</a>
                <a href="transaction.php">Transactions</a>
                <a href="loan.php">Loans</a>
                <a href="feedback.php">Feedback</a>
                <a href="profile.php" class="active">Profile</a>
                <a href="logout.php">Logout</a>
            </nav>
            <div class="user-info">
                <div class="user-avatar"><?php echo $firstLetter; ?></div>
                <span><?php echo $username; ?></span>
            </div>
        </header>
        <main class="main-content">
            <h2 class="fade-in-up">Profile Information</h2>
            <form class="profile-form fade-in-up" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value=<?php echo $username; ?> disabled>
                </div>
                <div class="form-group">
                    <label for="surname">Surname:</label>
                    <input type="text" id="surname" name="surname" required>
                </div>
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                </div>
                <div class="form-group">
                    <label for="address">Address:</label>
                    <input type="text" id="address" name="address" required>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="text" id="phone" name="phone" required>
                </div>
                <div class="form-group">
                    <label for="gender">Gender:</label>
                    <select id="gender" name="gender" required>
                        <option value="">Select gender</option>
                        <option value="Male">Male</option>
                        <option value="Female">Female</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                </div>
                <button type="submit" class="button" name="update" >Update Profile</button>
            </form>
        </main>
    </div>
</body>
</html>