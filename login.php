<?php

    // Configuration
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

    // Function to sanitize input
    function sanitize_input($data) {
        $data = trim($data);
        $data = stripslashes($data);
        $data = htmlspecialchars($data);
        return $data;
    }

    // Admin Login
    if (isset($_POST['adminlogin'])) {
        $adminUsername = sanitize_input($_POST['adminusername']);
        $adminPassword = sanitize_input($_POST['adminpassword']);
    
        $stmt = $conn->prepare("SELECT * FROM adminLogin WHERE adminname=? AND password=?");
        $stmt->bind_param("ss", $adminUsername, $adminPassword);
        $stmt->execute();
        $result = $stmt->get_result();
    
        if ($result->num_rows > 0) {
            $admin = $result->fetch_assoc();
            session_start();
            $_SESSION['adminname'] = $admin['adminname'];
            
            // Check for null values in all attributes
            foreach ($admin as $attribute => $value) {
                if (empty($value)) {
                    // Redirect to admin_profile.php if any attribute is null
                    header('Location: admin_profile.php');
                    exit;
                }
            }
            
            // If all attributes have values, redirect to admin_dashboard.php
            header('Location: admin_dashboard.php');
            exit;
        } else {
            echo "<script>alert('Invalid Admin Login');</script>";
        }
    }

    // Employee Login
    if (isset($_POST['employeelogin'])) {
        $employeeName = sanitize_input($_POST['employeename']);
        $employeePassword = sanitize_input($_POST['employeepassword']);

        $stmt = $conn->prepare("SELECT * FROM employeeLogin WHERE employeename=? AND password=?");
        $stmt->bind_param("ss", $employeeName, $employeePassword);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $employee = $result->fetch_assoc();
            session_start();
            $_SESSION['employeename'] = $employee['employeename'];
            
            foreach ($employee as $attribute => $value) {
                if (empty($value)) {  // Assuming 'id' can be empty
                    header('Location: employee_profile.php');
                    exit;
                }
            }
            
            // If all attributes have values, redirect to employee_dashboard.php
            header('Location: employee_dashboard.php');
            exit;

        } else {
            echo "<script>alert('Invalid Employee Login');</script>";
        }
    }

    // User Login
    if (isset($_POST['userlogin'])) {
        $userEmail = sanitize_input($_POST['useremail']);
        $userPassword = sanitize_input($_POST['userpassword']);

        $stmt = $conn->prepare("SELECT * FROM userLogin WHERE email=? AND password=?");
        $stmt->bind_param("ss", $userEmail, $userPassword);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            session_start();
            $_SESSION['username'] = $user['username'];
            
            // Check for null values in all attributes
            foreach ($user as $attribute => $value) {
                if (empty($value)) {
                    // Redirect to profile.php if any attribute is null
                    header('Location: profile.php');
                    exit;
                }
            }
            
            // If all attributes have values, redirect to dashboard.php
            header('Location: dashboard.php');
            exit;
        } else {
            echo "<script>alert('Invalid User Login');</script>";
        }
    }

    // Forgot Password
    if (isset($_POST['resetPassword'])) {
        $email = sanitize_input($_POST['resetEmail']);
        $newPassword = sanitize_input($_POST['newPassword']);

        // First, check if the email exists in the database
        $stmt = $conn->prepare("SELECT id FROM userLogin WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            // Email exists, proceed with password update
            // In a real-world scenario, you should hash the password before storing it

            $updateStmt = $conn->prepare("UPDATE userLogin SET password = ? WHERE email = ?");
            $updateStmt->bind_param("ss", $newPassword, $email);
            
            if ($updateStmt->execute()) {
                echo "<script>alert('Password has been reset successfully.');</script>";
            } else {
                echo "<script>alert('Error resetting password. Please try again.');</script>";
            }
            $updateStmt->close();
        } else {
            echo "<script>alert('Email not found. Please check your email address.');</script>";
        }
        $stmt->close();
    }
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SecureBank Login</title>
    <style>
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        @keyframes float {
            0% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(5deg); }
            100% { transform: translateY(0px) rotate(0deg); }
        }
        body, html {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            height: 100%;
            overflow: hidden;
            background: #1a2a3a;
        }
        .container {
            display: flex;
            height: 100%;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        .login-panel {
            background-color: rgba(255, 255, 255, 0.9);
            border-radius: 10px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.2);
            overflow: hidden;
            width: 800px;
            height: 500px;
            display: flex;
            animation: fadeIn 1s ease-out;
            position: relative;
            z-index: 1;
        }
        .login-options {
            background: #0d4a6f;
            padding: 40px;
            width: 200px;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .login-option {
            background: none;
            border: 2px solid #ffd700;
            color: #ffd700;
            padding: 10px 20px;
            margin: 10px 0;
            border-radius: 25px;
            cursor: pointer;
            transition: all 0.3s ease;
            font-size: 16px;
        }
        .login-option.active, .login-option:hover {
            background-color: #ffd700;
            color: #0d4a6f;
        }
        .login-forms {
            flex-grow: 1;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            position: relative;
        }
        .login-form {
            display: none;
            animation: fadeIn 0.5s ease-out;
        }
        .login-form.active {
            display: block;
        }
        h2 {
            color: #0d4a6f;
            margin-bottom: 20px;
            animation: fadeIn 0.5s ease-out 0.2s both;
        }
        .input-group {
            margin-bottom: 15px;
            animation: fadeIn 0.5s ease-out 0.4s both;
        }
        .input-group label {
            display: block;
            margin-bottom: 5px;
            color: #0d4a6f;
        }
        input[type="text"], input[type="password"] {
            width: 100%;
            padding: 12px;
            border: 1px solid #0d4a6f;
            border-radius: 5px;
            font-size: 16px;
            transition: border-color 0.3s ease;
        }
        input[type="text"]:focus, input[type="password"]:focus {
            border-color: #ffd700;
            outline: none;
        }
        button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #0d4a6f;
            color: #ffd700;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            animation: fadeIn 0.5s ease-out 0.6s both;
        }
        button[type="submit"]:hover {
            background: #ffd700;
            color: #0d4a6f;
        }
        .icon {
            font-size: 24px;
            margin-right: 10px;
        }
        .background {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
            animation: fadeIn 0.5s ease-out 0.8s both;
            color: #0d4a6f;
            text-decoration: none;
            font-weight: bold;
        }
        .signup-link a {
            color: #0d4a6f;
            text-decoration: none;
            font-weight: bold;
        }
        .signup-link a:hover {
            color: #ff5800;
        }

        .forgot-password {
            text-align: center;
            margin-top: 10px;
            color: #0d4a6f;
            font-weight: bold;
            animation: fadeIn 0.5s ease-out 0.8s both;
        }
        .forgot-password a {
            color: #0d4a6f;
            text-decoration: none;
            font-size: 14px;
        }
        .forgot-password a:hover {
            text-decoration: underline;
        }

        .popup {
            display: none; /* Changed back to 'none' */
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            justify-content: center;
            align-items: center;
        }

        .popup-content {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 10px;
            width: 300px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.3);
            position: relative;
            animation: fadeIn 0.5s ease-out;
        }

        .popup-content h2 {
            color: #0d4a6f;
            margin-bottom: 30px;
            text-align: center;
            font-size: 24px;
        }

        .popup-content .input-group {
            margin-bottom: 20px;
        }

        .popup-content label {
            display: block;
            margin-bottom: 8px;
            color: #0d4a6f;
            font-weight: bold;
        }

        .popup-content input[type="email"],
        .popup-content input[type="password"] {
            width: calc(100% - 20px);
            padding: 10px;
            border: 1px solid #0d4a6f;
            border-radius: 5px;
            font-size: 16px;
        }

        .popup-content button[type="submit"] {
            width: 100%;
            padding: 12px;
            background: #0d4a6f;
            color: #ffd700;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            margin-top: 10px;
        }

        .close {
            position: absolute;
            top: 10px;
            right: 15px;
            color: #0d4a6f;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s ease;
        }
        .close:hover {
            color: #ffd700;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
</head>
<body>
    <div class="container">
        <canvas id="background" class="background"></canvas>
        <div class="login-panel">
            <div class="login-options">
                <button id="adminBtn" class="login-option active"><i class="fas fa-user-shield icon"></i> Admin</button>
                <button id="employeeBtn" class="login-option"><i class="fas fa-user-tie icon"></i> Employee</button>
                <button id="userBtn" class="login-option"><i class="fas fa-user icon"></i> User</button>
            </div>
            <div class="login-forms">
                <form id="adminForm" class="login-form active" method="post">
                    <h2><i class="fas fa-user-shield"></i> Admin Login</h2>
                    <div class="input-group">
                        <label for="adminUsername">Admin Username:</label>
                        <input type="text" id="adminUsername" name="adminusername" placeholder="Enter admin username" required>
                    </div>
                    <div class="input-group">
                        <label for="adminPassword">Password:</label>
                        <input type="password" id="adminPassword" name="adminpassword" placeholder="Enter admin password" required>
                    </div>
                    <button type="submit" name="adminlogin">Login as Admin</button>
                </form>
                <form id="employeeForm" class="login-form" method="post">
                    <h2><i class="fas fa-user-tie"></i> Employee Login</h2>
                    <div class="input-group">
                        <label for="employeeId">Employee Username:</label>
                        <input type="text" id="employeeName" name="employeename" placeholder="Enter employee userame" required>
                    </div>
                    <div class="input-group">
                        <label for="employeePassword">Password:</label>
                        <input type="password" id="employeePassword" name="employeepassword" placeholder="Enter employee password" required>
                    </div>
                    <button type="submit" name="employeelogin">Login as Employee</button>
                </form>
                <form id="userForm" class="login-form" method="post">
                    <h2><i class="fas fa-user"></i> User Login</h2>
                    <div class="input-group">
                        <label for="userEmail">Email:</label>
                        <input type="text" id="userEmail" name="useremail" placeholder="Enter user email" required>
                    </div>
                    <div class="input-group">
                        <label for="userPassword">Password:</label>
                        <input type="password" id="userPassword" name="userpassword" placeholder="Enter user password" required>
                    </div>
                    <button type="submit" name="userlogin" >Login as User</button>
                    <div class="signup-link">
                        New user? <a href="register.php">Sign up here</a>
                    </div>
                    <div class="forgot-password">
                        <a href="#" id="forgotPasswordLink">Forgot Password?</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div id="forgotPasswordPopup" class="popup">
        <div class="popup-content">
            <span class="close">&times;</span>
            <h2><i class="fas fa-key"></i> Reset Password</h2>
            <form id="resetPasswordForm" method="post">
                <div class="input-group">
                    <label for="resetEmail">Email:</label>
                    <input type="email" id="resetEmail" name="resetEmail" placeholder="Enter your email" required>
                </div>
                <div class="input-group">
                    <label for="newPassword">New Password:</label>
                    <input type="password" id="newPassword" name="newPassword" placeholder="Enter new password" required>
                </div>
                <button type="submit" name="resetPassword">Reset Password</button>
            </form>
        </div>
    </div>

    <script>
document.addEventListener('DOMContentLoaded', function() {
    const adminBtn = document.getElementById('adminBtn');
    const employeeBtn = document.getElementById('employeeBtn');
    const userBtn = document.getElementById('userBtn');
    const adminForm = document.getElementById('adminForm');
    const employeeForm = document.getElementById('employeeForm');
    const userForm = document.getElementById('userForm');
    const signupLink = document.querySelector('.signup-link a');
    const forgotPasswordLink = document.getElementById('forgotPasswordLink');
    const forgotPasswordPopup = document.getElementById('forgotPasswordPopup');
    const closePopup = forgotPasswordPopup.querySelector('.close');
    const resetPasswordForm = document.getElementById('resetPasswordForm');

    function setActive(button, form) {
        [adminBtn, employeeBtn, userBtn].forEach(btn => btn.classList.remove('active'));
        [adminForm, employeeForm, userForm].forEach(f => f.classList.remove('active'));
        button.classList.add('active');
        form.classList.add('active');
    }

    adminBtn.addEventListener('click', () => setActive(adminBtn, adminForm));
    employeeBtn.addEventListener('click', () => setActive(employeeBtn, employeeForm));
    userBtn.addEventListener('click', () => setActive(userBtn, userForm));

    signupLink.addEventListener('click', (e) => {
        e.preventDefault();
        alert('Sign up functionality would be implemented here.');
    });

    forgotPasswordLink.addEventListener('click', (e) => {
         e.preventDefault();
         forgotPasswordPopup.style.display = 'flex';
    });

    closePopup.addEventListener('click', () => {
        forgotPasswordPopup.style.display = 'none';
    });

    window.addEventListener('click', (e) => {
        if (e.target === forgotPasswordPopup) {
            forgotPasswordPopup.style.display = 'none';
        }
    });

    resetPasswordForm.addEventListener('submit', (e) => {
        const email = document.getElementById('resetEmail').value;
        const newPassword = document.getElementById('newPassword').value;
        // Here you would typically send an AJAX request to your server to handle the password reset
        forgotPasswordPopup.style.display = 'none';
    });

    // Background animation code with currency symbols
    const canvas = document.getElementById('background');
    const ctx = canvas.getContext('2d');

    canvas.width = window.innerWidth;
    canvas.height = window.innerHeight;

    const symbols = ['$', '€', '£', '¥', '₹']; // Add or remove currency symbols as needed
    const particles = [];
    const particleCount = 50;

    class Particle {
        constructor() {
            this.x = Math.random() * canvas.width;
            this.y = Math.random() * canvas.height;
            this.size = Math.random() * 20 + 10;
            this.speedX = Math.random() * 3 - 1.5;
            this.speedY = Math.random() * 3 - 1.5;
            this.symbol = symbols[Math.floor(Math.random() * symbols.length)];
        }

        update() {
            this.x += this.speedX;
            this.y += this.speedY;

            if (this.x < 0 || this.x > canvas.width) this.speedX *= -1;
            if (this.y < 0 || this.y > canvas.height) this.speedY *= -1;
        }

        draw() {
            ctx.fillStyle = 'rgba(255, 215, 0, 0.5)'; // Golden color
            ctx.font = `${this.size}px Arial`;
            ctx.fillText(this.symbol, this.x, this.y);
        }
    }

    function init() {
        for (let i = 0; i < particleCount; i++) {
            particles.push(new Particle());
        }
    }

    function animate() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        for (let i = 0; i < particles.length; i++) {
            particles[i].update();
            particles[i].draw();
        }
        requestAnimationFrame(animate);
    }

    init();
    animate();

    window.addEventListener('resize', function() {
        canvas.width = window.innerWidth;
        canvas.height = window.innerHeight;
        init();
    });
});
</script>
</body>
</html>