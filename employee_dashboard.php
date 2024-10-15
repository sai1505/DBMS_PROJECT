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
$username = "sai2005";
$password = "sai@2005";
$dbname = "Bank";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Retrieve employee details from the database
$stmt = $conn->prepare("SELECT * FROM employeeLogin WHERE employeename = ?");
$stmt->bind_param("s", $employeename);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employeeDetails = $result->fetch_assoc();
}
$stmt->close();

// Retrieve user count from the database
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM userLogin");
$stmt->execute(); // Execute before getting result
$result = $stmt->get_result();
$countUsers = $result->fetch_assoc()['count'];
$stmt->close();

// Retrieve total balance from the database
$stmt = $conn->prepare("SELECT SUM(balance) as totalBalance FROM account");
$stmt->execute(); // Execute before getting result
$result = $stmt->get_result();
$totalBalance = $result->fetch_assoc()['totalBalance'];
$stmt->close();

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

        .employee-details {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1rem;
            margin-top: 1rem;
        }

        .employee-detail {
            background: #f0f2f5;
            padding: 1rem;
            border-radius: 10px;
        }

        .employee-detail h3 {
            color: var(--secondary-color);
            margin-bottom: 0.5rem;
        }

        .stat-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .stat-card h3 {
            font-size: 1.2rem;
            margin-bottom: 0.5rem;
            color: var(--secondary-color);
        }

        .stat-card p {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
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
            animation: fadeInUp 0.5s ease forwards;
        }

        .menu-toggle {
            display: none;
            position: fixed;
            top: 20px;
            left: 20px;
            z-index: 1000;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            width: 50px;
            height: 50px;
            font-size: 24px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .menu-toggle:hover {
            background: var(--secondary-color);
            transform: rotate(90deg);
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
            margin-right: 15px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .username {
            font-weight: 600;
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
                transform: translateY(-100%);
                transition: transform 0.3s ease;
            }

            .sidebar.active {
                transform: translateY(0);
            }

            .main-content {
                border-radius: 0 0 30px 30px;
            }

            .main-content::before {
                display: none;
            }

            .menu-toggle {
                display: block;
            }

            .user-info {
                flex-direction: row;
                justify-content: flex-start;
                padding-bottom: 1rem;
            }

            .employee-details {
                grid-template-columns: 1fr;
            }
        }

        .section-title {
            font-size: 2rem;
            font-weight: 600;
            color: var(--primary-color);
            margin: 1rem 0 1rem;
            padding-left: 1rem;
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            background-clip: text;
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            position: relative;
        }

    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="employee_dashboard.php" class="active">Dashboard</a>
                <a href="employee_shift.php">Shifts</a>
                <a href="employee_userdata.php">User Data</a>
                <a href="employee_deposit.php">Deposits</a>
                <a href="employee_profile.php">Profile</a>
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
                    <h1>Welcome back, <?php echo $employeename; ?></h1>
                    <p>Here's your employee dashboard</p>
                </div>
            </div>
            <div class="fade-in-up">
                <h2 class="section-title">Employee Information</h2>
                <div class="employee-details fade-in-up">
                    <div class="employee-detail">
                        <h3>Employee Username</h3>
                        <p><?php echo $employeeDetails['employeename']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>Email</h3>
                        <p><?php echo $employeeDetails['email']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>Surname</h3>
                        <p><?php echo $employeeDetails['surname']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>Name</h3>
                        <p><?php echo $employeeDetails['name']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>Phone</h3>
                        <p><?php echo $employeeDetails['phone']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>date of Birth</h3>
                        <p><?php echo $employeeDetails['dateofbirth']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>Gender</h3>
                        <p><?php echo $employeeDetails['gender']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>Job Title</h3>
                        <p><?php echo $employeeDetails['jobtitle']; ?></p>
                    </div>
                    <div class="employee-detail">
                        <h3>Branch</h3>
                        <p><?php echo $employeeDetails['branchname']; ?></p>
                    </div>
                </div>
            </div>
            <div class="stat-cards">
                <div class="stat-card fade-in-up">
                    <h3>Total Users</h3>
                    <p><?php echo number_format($countUsers); ?></p>
                </div>
                <div class="stat-card fade-in-up">
                    <h3>Total Balance of all Users</h3>
                    <p>₹ <?php echo number_format($totalBalance, 2); ?></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const menuToggle = document.querySelector('.menu-toggle');
            const sidebar = document.querySelector('.sidebar');

            menuToggle.addEventListener('click', () => {
                sidebar.classList.toggle('active');
                menuToggle.classList.toggle('active');
                if (sidebar.classList.contains('active')) {
                    menuToggle.innerHTML = '✕';
                } else {
                    menuToggle.innerHTML = '☰';
                }
            });
        });
    </script>
</body>
</html>