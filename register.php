<?php
    // Enable error reporting
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Database connection settings
    $servername = "localhost";
    $username = "sai2005";
    $password = "sai@2005";
    $dbname = "Bank";

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    if (isset($_POST['register'])) {
        // Check if the form fields are set
        if (isset($_POST['username']) && isset($_POST['email']) && isset($_POST['password']) && isset($_POST['confirmpassword'])) {
            $username = $_POST['username'];
            $email = $_POST['email'];
            $password = $_POST['password'];
            $confirmpassword = $_POST['confirmpassword'];
            $accountOpenedDate = date('Y-m-d'); // Current date in YYYY-MM-DD format

            // Compare the password and confirm password
            if ($password == $confirmpassword) {
                // Check if the username already exists
                $sql = "SELECT * FROM userLogin WHERE username = ?";
                $stmt = $conn->prepare($sql);
                $stmt->bind_param("s", $username);
                $stmt->execute();
                $result = $stmt->get_result();
                if ($result->num_rows > 0) {
                    echo "<script>alert('Username already exists');</script>";
                } else {
                    // Check if the email already exists
                    $sql = "SELECT * FROM userLogin WHERE email = ?";
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param("s", $email);
                    $stmt->execute();
                    $result = $stmt->get_result();
                    if ($result->num_rows > 0) {
                        echo "<script>alert('Email already exists');</script>";
                    } else {
                        // Prepare the SQL query
                        // Prepare the SQL query for userLogin table
                        $sql_userLogin = "INSERT INTO userLogin (username, email, password, accountopened) VALUES (?, ?, ?, ?)";

                        // Prepare the statement for userLogin
                        $stmt_userLogin = $conn->prepare($sql_userLogin);

                        // Bind the parameters for userLogin
                        $stmt_userLogin->bind_param("ssss", $username, $email, $password, $accountOpenedDate);

                        // Execute the query for userLogin
                        if (!$stmt_userLogin->execute()) {
                            echo "Error inserting into userLogin: " . $stmt_userLogin->error;
                        } else {
                            // Generate account number (you may want to implement a more sophisticated method)
                            $accountNumber = mt_rand(10000000, 99999999);
                            
                            // Prepare the SQL query for account table
                            $sql_account = "INSERT INTO account (accountnumber, username, balance, status, credit, debit) VALUES (?, ?, ?, ?, ?, ?)";
                            
                            // Prepare the statement for account
                            $stmt_account = $conn->prepare($sql_account);
                            
                            // Set initial balance and status
                            $initialBalance = 10000;
                            $status = 'active';
                            $credit=0;
                            $debit=0;
                            
                            // Bind the parameters for account
                            $stmt_account->bind_param("ssisii", $accountNumber, $username, $initialBalance, $status, $credit, $debit);
                            
                            // Execute the query for account
                            if (!$stmt_account->execute()) {
                                echo "Error inserting into account: " . $stmt_account->error;
                            } else {
                                echo "<script>alert('Registered successfully');</script>";
                                header("Location: login.php");
                                exit;
                            }
                            
                            // Close the account statement
                            $stmt_account->close();
                        }

                        // Close the userLogin statement
                        $stmt_userLogin->close();
                    }
                }
            } else {
                echo "<script>alert('Passwords do not match');</script>";
            }
        } else {
            echo "<script>alert('Please fill in all fields');</script>";
        }
    }
    // Close the connection
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureBank - Registration</title>
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;600;700&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.9.1/gsap.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: linear-gradient(135deg, #00E5FF, #00B0FF);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            overflow: hidden;
        }

        .background-design {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
        }

        .shape {
            position: absolute;
            opacity: 0.1;
        }

        .circle {
            border-radius: 50%;
        }

        .square {
            border-radius: 10px;
        }

        .container {
            background-color: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            padding: 40px;
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
            width: 450px;
            max-width: 90%;
            position: relative;
            overflow: hidden;
        }

        h1 {
            color: #FF4081;
            text-align: center;
            margin-bottom: 30px;
            font-size: 32px;
            position: relative;
        }

        h1::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 3px;
            background-color: #FF4081;
        }

        form {
            display: flex;
            flex-direction: column;
        }

        .input-group {
            margin-bottom: 20px;
            position: relative;
        }

        input {
            width: 100%;
            padding: 12px;
            border: 2px solid #00B0FF;
            border-radius: 8px;
            font-size: 16px;
            transition: all 0.3s ease;
            background-color: rgba(255, 255, 255, 0.8);
        }

        input:focus {
            border-color: #FF4081;
            outline: none;
            box-shadow: 0 0 0 3px rgba(255, 64, 129, 0.2);
        }

        label {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: #00B0FF;
            transition: all 0.3s ease;
            pointer-events: none;
        }

        input:focus + label,
        input:not(:placeholder-shown) + label {
            top: 0;
            font-size: 12px;
            background-color: white;
            padding: 0 4px;
        }

        button {
            background-color: #FF4081;
            color: white;
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-size: 18px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: all 0.5s ease;
        }

        button:hover {
            background-color: #FF69B4;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        button:hover::before {
            left: 100%;
        }

        .login-link {
            text-align: center;
            margin-top: 20px;
            font-size: 16px;
            color: #00B0FF;
        }

        .login-link a {
            color: #00B0FF;
            text-decoration: none;
        }

        .login-link a:hover {
            color: #FF4081;
        }
    </style>
</head>
<body>
    <div class="background-design">
        <div class="shape circle" style="width: 200px; height: 200px; background-color: #FF69B4; top: 10%; left: 20%;"></div>
        <div class="shape square" style="width: 300px; height: 300px; background-color: #00E5FF; top: 30%; left: 50%;"></div>
        <div class="shape circle" style="width: 250px; height: 250px; background-color: #FF4081; top: 50%; left: 30%;"></div>
        <div class="shape square" style="width: 350px; height: 350px; background-color: #00B0FF; top: 70%; left: 60%;"></div>
    </div>
    <div class="container">
        <h1>BankingCo</h1>
        <form method="post">
            <div class="input-group">
                <input type="text" id="name" name="username" required>
                <label for="name">UserName</label>
            </div>
            <div class="input-group">
                <input type="email" id="email" name="email" required>
                <label for="email">Email</label>
            </div>
            <div class="input-group">
                <input type="password" id="password" name="password" required>
                <label for="password">Password</label>
            </div>
            <div class="input-group">
                <input type="password" id="confirm-password" name="confirmpassword" required>
                <label for="confirm-password">Confirm Password</label>
            </div>
            <button type="submit" name="register">Register</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>

    <script>
        // GSAP animation
        gsap.registerPlugin(ScrollTrigger);

        // Form animation
        gsap.from('.input-group', {
            opacity: 0,
            y: 50,
            stagger: 0.2,
            duration: 0.8,
            delay: 0.5
        });

        // Button animation
        gsap.from('button', {
            opacity: 0,
            y: 50,
            duration: 0.8,
            delay: 1.2
        });

        // Terms animation
        gsap.from('.terms', {
            opacity: 0,
            y: 50,
            duration: 0.8,
            delay: 1.5
        });

        // Login link animation
        gsap.from('.login-link', {
            opacity: 0,
            y: 50,
            duration: 0.8,
            delay: 1.8
        });
    </script>
</body>
</html>