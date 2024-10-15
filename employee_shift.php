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

    // Fetch shift data
    $stmt = $conn->prepare("SELECT * FROM employeeshift WHERE employeename = ?");
    $stmt->bind_param("s", $employeename);
    $stmt->execute();
    $result = $stmt->get_result();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fluid Curve Dashboard</title>
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

        .balance {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .quick-stats {
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
        }

        .shift-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
            margin-top: 2rem;
        }

        .shift-table th,
        .shift-table td {
            padding: 15px;
            text-align: left;
            background: white;
        }

        .shift-table th {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
        }

        .shift-table tr {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .shift-table tr:hover {
            transform: scale(1.02) translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .shift-table td:first-child,
        .shift-table th:first-child {
            border-radius: 10px 0 0 10px;
        }

        .shift-table td:last-child,
        .shift-table th:last-child {
            border-radius: 0 10px 10px 0;
        }

        @media (max-width: 768px) {
            .shift-table {
                font-size: 14px;
            }

            .shift-table th,
            .shift-table td {
                padding: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="employee_dashboard.php">Dashboard</a>
                <a href="employee_shift.php"  class="active">Shifts</a>
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
                    <h1>Employee Shifts</h1>
                    <p>Here are your shifts dates and timings</p>
                </div>
            </div>
            <div class="fade-in-up">
                <table class="shift-table">
                    <thead>
                        <tr>
                            <th>Employee Username</th>
                            <th>Start Time</th>
                            <th>End Time</th>
                            <th>Timing</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if ($result->num_rows > 0) {
                            while($row = $result->fetch_assoc ()) {
                                echo "<tr>";
                                echo "<td>" . $row["employeename"] . "</td>";
                                echo "<td>" . $row["shiftstartdate"] . "</td>";
                                echo "<td>" . $row["shiftenddate"] . "</td>";
                                echo "<td>" . $row["timing"] . "</td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='4'>No shifts found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
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