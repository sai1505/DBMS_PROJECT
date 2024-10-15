<?php
session_start();
// Check if user is logged in
if (!isset($_SESSION['adminname'])) {
    header("Location: login.php"); // Redirect to login page if not logged in
    exit();
}
error_reporting(E_ALL);
ini_set('display_errors', 1);

$adminname = htmlspecialchars($_SESSION['adminname']);
$firstLetter = strtoupper(substr($adminname, 0, 1));

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

if (isset($_POST['shift'])) {
    // Check if the form fields are set
    if (isset($_POST['username']) && isset($_POST['shift_start_date']) && isset($_POST['shift_time'])) {
        $employeename = $_POST['username'];
        $shiftstartdate = $_POST['shift_start_date'];
        $timing = $_POST['shift_time'];

        // Calculate shift end date (7 days after start date)
        $shiftenddate = date('Y-m-d', strtotime($shiftstartdate . ' + 7 days'));

        // Check if the employee exists
        $sql_check = "SELECT * FROM employeeLogin WHERE employeename = ?";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("s", $employeename);
        $stmt_check->execute();
        $result = $stmt_check->get_result();

        if ($result->num_rows > 0) {
            // Employee exists, proceed with shift assignment
            $sql_shift = "INSERT INTO employeeshift (employeename, shiftstartdate, shiftenddate, timing) VALUES (?, ?, ?, ?)";
            $stmt_shift = $conn->prepare($sql_shift);
            $stmt_shift->bind_param("ssss", $employeename, $shiftstartdate, $shiftenddate, $timing);

            if ($stmt_shift->execute()) {
                echo "<script>alert('Shift assigned successfully');</script>";
            } else {
                echo "<script>alert('Error assigning shift: " . $stmt_shift->error . "');</script>";
            }

            $stmt_shift->close();
        } else {
            echo "<script>alert('Employee does not exist');</script>";
        }

        $stmt_check->close();
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
    <title>Employee Registration</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap');

        :root {
            --gradient-1: linear-gradient(135deg, #2B7A0B, #5BB318);
            --gradient-2: linear-gradient(135deg, #A4907C, #8D7B68);
            --gradient-3: linear-gradient(135deg, #285430, #5F8D4E);
            --text-color: #333;
            --bg-color: #F0F0F0;
            --sidebar-width: 280px;
            --sidebar-collapsed-width: 120px;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-color);
            min-height: 100vh;
            display: flex;
            overflow-x: hidden;
        }

        .employee-dashboard-container {
            display: flex;
            flex-grow: 1;
            position: relative;
        }

        .employee-sidebar {
            position: fixed;
            top: 20px;
            left: 20px;
            width: var(--sidebar-width);
            height: calc(100vh - 40px);
            background: var(--gradient-2);
            display: flex;
            flex-direction: column;
            padding: 2rem;
            transition: all 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
            border-radius: 30px;
            overflow: hidden;
            z-index: 1000;
        }

        .employee-sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .employee-logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin-bottom: 2rem;
            transition: opacity 0.3s ease;
        }

        .employee-sidebar.collapsed .employee-logo {
            opacity: 0;
        }

        .employee-nav {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .employee-nav a {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .employee-nav a i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .employee-nav a span {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .employee-sidebar.collapsed .employee-nav a span {
            opacity: 0;
            transform: translateX(20px);
        }

        .employee-nav a:hover, .employee-nav a.active {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }

        .employee-nav a::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 3px;
            background: white;
            transform: scaleX(0);
            transform-origin: right;
            transition: transform 0.3s ease;
        }

        .employee-nav a:hover::after, .employee-nav a.active::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .employee-user-info {
            margin-top: auto;
            display: flex;
            align-items: center;
            color: white;
            transition: opacity 0.3s ease;
        }

        .employee-sidebar.collapsed .employee-user-info {
            opacity: 0;
        }

        .employee-user-avatar {
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
            margin-right: 15px;
        }
        
        .employee-toggle-btn {
            position: absolute;
            top: 33px;
            right: 40px;
            width: 30px;
            height: 30px;
            background: white;
            border: none;
            border-radius: 50%;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .employee-sidebar.collapsed .employee-toggle-btn {
            transform: rotate(180deg);
        }

        .employee-main-content {
            margin-left: calc(var(--sidebar-width) + 40px);
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: auto auto 1fr;
            gap: 2rem;
            flex-grow: 1;
            transition: margin-left 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        .employee-sidebar.collapsed + .employee-main-content {
            margin-left: calc(var(--sidebar-collapsed-width) + 40px);
        }

        .employee-welcome-card {
            grid-column: 1 / -1;
            background: var(--gradient-1);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .employee-welcome-text h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .employee-shift-form {
            grid-column: 1 / -1;
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .employee-form-group {
            margin-bottom: 1rem;
        }

        .employee-form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .employee-form-group input,
        .employee-form-group select {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 10px;
            font-size: 1rem;
        }

        .employee-submit-btn {
            background: var(--gradient-1);
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 10px;
            font-size: 1rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .employee-submit-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.2);
        }

        @media (max-width: 1200px) {
            .employee-main-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .employee-sidebar {
                top: 10px;
                left: 10px;
                width: calc(100% - 20px);
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
            }

            .employee-sidebar.collapsed {
                width: calc(100% - 20px);
                height: 60px;
            }

            .employee-logo {
                margin-bottom: 0;
            }

            .employee-nav {
                flex-direction: row;
                justify-content: center;
                display: none;
            }

            .employee-sidebar:not(.collapsed) .employee-nav {
                display: flex;
                flex-direction: column;
            }

            .employee-user-info {
                margin-top: 0;
            }

            .employee-toggle-btn {
                top: auto;
                bottom: -15px;
                right: 20px;
            }

            .employee-main-content {
                margin-left: 0;
                margin-top: 100px;
                grid-template-columns: 1fr;
            }

            .employee-sidebar.collapsed + .employee-main-content {
                margin-left: 0;
                margin-top: 80px;
            }
        }

        .main-content {
            margin-left: calc(var(--sidebar-width) + 40px);
            padding: 2rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            grid-template-rows: auto auto auto;
            gap: 1.5rem;
            flex-grow: 1;
            transition: margin-left 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }
        
    </style>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="employee-dashboard-container">
        <aside class="employee-sidebar">
            <div class="employee-logo">BankingCo</div>
            <nav class="employee-nav">
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="admin_employee_registration.php"><i class="fas fa-user-plus"></i><span>Employee Registration</span></a>
                <a href="admin_shift.php"  class="active"><i class="fas fa-calendar-alt"></i><span>Employee Shifts</span></a>
                <a href="admin_employeedata.php"><i class="fas fa-users"></i><span>Employee Data</span></a>
                <a href="admin_profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </nav>
            <div class="employee-user-info">
                <div class="employee-user-avatar">
                    <?php echo $firstLetter; ?>
                </div>
                <span><?php echo $adminname; ?></span>
            </div>
            <button class="employee-toggle-btn">
                <i class="fas fa-chevron-left"></i>
            </button>
        </aside>
        <main class="employee-main-content">
            <div class="employee-welcome-card">
                <div class="employee-welcome-text">
                    <h1>Employee Shifts</h1>
                    <p>Assign a shift to a employee in the system</p>
                </div>
            </div>
            <div class="employee-shift-form">
                <form method="post">
                    <div class="employee-form-group">
                        <label for="username">Employee Username:</label>
                        <input type="text" id="username" name="username" required>
                    </div>
                    <div class="employee-form-group">
                        <label for="shift_start_date">Shift Start Date:</label>
                        <input type="date" id="shift_start_date" name="shift_start_date" required>
                    </div>
                    <div class="employee-form-group">
                        <label for="shift_time">Shift Time:</label>
                        <select id="shift_time" name="shift_time" required>
                            <option value="1pm to 10pm">1pm to 10pm</option>
                            <option value="6am to 3pm">6am to 3pm</option>
                            <option value="10pm to 6am">10pm to 6am</option>
                        </select>
                    </div>
                    <button type="submit" class="employee-submit-btn" name="shift">Assign Shift</button>
                </form>
            </div>
        </main>
    </div>


    <script>
    document.addEventListener('DOMContentLoaded', () => {
        const sidebar = document.querySelector('.employee-sidebar');
        const toggleBtn = document.querySelector('.employee-toggle-btn');
        const navLinks = document.querySelectorAll('.employee-nav a');
        const mainContent = document.querySelector('.employee-main-content');
        const logo = document.querySelector('.employee-logo');
        const userInfo = document.querySelector('.employee-user-info');

        toggleBtn.onclick = () => {
            sidebar.classList.toggle('collapsed');
            
            if (sidebar.classList.contains('collapsed')) {
                mainContent.style.marginLeft = `calc(var(--sidebar-collapsed-width) + 40px)`;
                setTimeout(() => {
                    logo.style.opacity = '0';
                    userInfo.style.opacity = '0';
                }, 100);
            } else {
                mainContent.style.marginLeft = `calc(var(--sidebar-width) + 40px)`;
                setTimeout(() => {
                    logo.style.opacity = '1';
                    userInfo.style.opacity = '1';
                }, 100);
            }

            navLinks.forEach(link => {
                const span = link.querySelector('span');
                if (sidebar.classList.contains('collapsed')) {
                    span.style.opacity = '0';
                    span.style.transform = 'translateX(20px)';
                } else {
                    setTimeout(() => {
                        span.style.opacity = '1';
                        span.style.transform = 'translateX(0)';
                    }, 100);
                }
            });
        };

        navLinks.forEach((link) => {
            link.addEventListener('click', (e) => {
                navLinks.forEach((otherLink) => {
                    otherLink.classList.remove('active');
                });
                link.classList.add('active');
            });
        });
    });
</script>
</body>
</html>