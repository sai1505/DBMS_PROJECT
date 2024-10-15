<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['employeename'])) {
        header("Location: login.php"); // Redirect to login page if not logged in
        exit();
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $employeename = htmlspecialchars($_SESSION['employeename']);
    $firstLetter = strtoupper(substr($employeename, 0, 1));

    $servername = "localhost";
    $db_username = "sai2005";
    $db_password = "sai@2005";
    $dbname = "Bank";

    $conn = new mysqli($servername, $db_username, $db_password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch existing employee data
    $stmt = $conn->prepare("SELECT * FROM employeeLogin WHERE employeename = ?");
    $stmt->bind_param("s", $employeename);
    $stmt->execute();
    $result = $stmt->get_result();
    $employeeData = $result->fetch_assoc();

    if (isset($_POST['update'])) {
        $email = $_POST['email'];
        $phone = $_POST['phone'];
        $gender = $_POST['gender'];
        $surname = $_POST['surname'];
        $name = $_POST['name'];
        $dob = $_POST['dob'];
        $job_title = $_POST['job-title'];
        $branch_name = $_POST['branch-name'];

        $sql = "UPDATE employeeLogin SET email=?, phone=?, gender=?, surname=?, name=?, 
                dateofbirth=?, jobtitle=?, branchname=? 
                WHERE employeename = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssss", $email, $phone, $gender, $surname, $name, $dob, $job_title, $branch_name, $employeename);

        if ($stmt->execute()) {
            echo "<script>alert('Profile Updated Successfully!!');</script>";
        } else {
            echo "<script>alert('Invalid Profile Update. Profile didn't update.');</script>";
        }
    }

    $conn->close();
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

        .employee-form {
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

        .form-group input,
        .form-group select {
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 1rem;
        }

        .form-group select {
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' fill='%234158D0' viewBox='0 0 16 16'%3E%3Cpath d='M7.247 11.14 2.451 5.658C1.885 5.013 2.345 4 3.204 4h9.592a1 1 0 0 1 .753 1.659l-4.796 5.48a1 1 0 0 1-1.506 0z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 12px;
            padding-right: 2rem;
        }

        .submit-btn {
            grid-column: span 2;
            background-color: var(--primary-color);
            color: white;
            border: none;
            padding: 0.75rem;
            border-radius: 5px;
            font-size: 1rem;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .submit-btn:hover {
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

            .employee-form {
                grid-template-columns: 1fr;
            }

            .submit-btn {
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
            margin-right: 15px; /* This adds space to the right of the avatar */
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
                <a href="employee_deposit.php">Deposits</a>
                <a href="employee_profile.php"  class="active">Profile</a>
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
                    <h1>Profile Information</h1>
                    <p>Update your profile information below</p>
                </div>
            </div>
            <form id="employee-form" class="employee-form" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" name="username" value=<?php echo $employeename; ?> disabled>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" id="email" name="email" required>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="phone">Phone:</label>
                    <input type="tel" id="phone" name="phone" required>
                    <span class="error-message"></span>
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
                    <label for="surname">Surname:</label>
                    <input type="text" id="surname" name="surname" required>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="name">Name:</label>
                    <input type="text" id="name" name="name" required>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="dob">Date of Birth:</label>
                    <input type="date" id="dob" name="dob" required>
                </div>
                <div class="form-group">
                    <label for="job-title">Job Title:</label>
                    <select id="job-title" name="job-title" required>
                        <option value="">Select a job title</option>
                        <option value="Bank-Clerk">Bank Clerk</option>
                        <option value="Bank-Manager">Bank Manager</option>
                        <option value="Bank-Recovery-Agent">Bank Recovery Agent</option>
                    </select>
                    <span class="error-message"></span>
                </div>
                <div class="form-group">
                    <label for="branch-name">Branch Name:</label>
                    <select id="branch-name" name="branch-name" required>
                        <option value="">Select a branch</option>
                        <option value="Visakhapatnam">Visakhapatnam</option>
                        <option value="Vizianagaram">Vizianagaram</option>
                    </select>
                </div>
                <button type="submit" class="submit-btn" name="update" >Update Profile</button>
            </form>
        </main>
    </div>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('employee-form');
            const notification = document.getElementById('notification');

            form.addEventListener('submit', function(e) {
                if (validateForm()) {
                    // Simulate form submission
                    setTimeout(() => {
                        showNotification('Employee information submitted successfully!', 'success');
                        form.reset();
                    }, 1000);
                }
            });

            function validateForm() {
                let isValid = true;
                const inputs = form.querySelectorAll('input, select');
                
                inputs.forEach(input => {
                    if (input.value.trim() === '') {
                        showError(input, 'This field is required');
                        isValid = false;
                    } else {
                        clearError(input);
                        
                        if (input.type === 'email' && !isValidEmail(input.value)) {
                            showError(input, 'Please enter a valid email address');
                            isValid = false;
                        }
                        
                        if (input.type === 'tel' && !isValidPhone(input.value)) {
                            showError(input, 'Please enter a valid phone number');
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

            function isValidEmail(email) {
                const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                return re.test(email);
            }

            function isValidPhone(phone) {
                const re = /^\+?[\d\s-]{10,}$/;
                return re.test(phone);
            }

            function showNotification(message, type) {
                notification.textContent = message;
                notification.className = type;
                notification.style.display = 'block';
                
                setTimeout(() => {
                    notification.style.display = 'none';
                }, 3000);
            }
        });
    </script>
</body>
</html>