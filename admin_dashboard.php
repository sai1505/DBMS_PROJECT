<?php
    session_start();
    // Check if user is logged in
    if (!isset($_SESSION['adminname'])) {
        header("Location: login.php"); // Redirect to login page if not logged in
        exit();
    }
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    // Database connection
    $servername = "localhost";
    $username = "sai2005";
    $password = "sai@2005";
    $dbname = "Bank";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $adminname = htmlspecialchars($_SESSION['adminname']);
    $firstLetter = strtoupper(substr($adminname, 0, 1));

    // Retrieve admin details from the database
    $stmt = $conn->prepare("SELECT * FROM adminLogin WHERE adminname = ?");
    $stmt->bind_param("s", $adminname);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $adminDetails = $result->fetch_assoc();
    }

    $stmt->close();

    // Retrieve employee counts from the database
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM employeeLogin WHERE branchname = ?");
    
    $stmt->bind_param("s", $location);
    
    $location = 'Visakhapatnam';
    $stmt->execute();
    $result = $stmt->get_result();
    $employeeCountVizag = $result->fetch_assoc()['count'];

    $location = 'Vizianagaram';
    $stmt->execute();
    $result = $stmt->get_result();
    $employeeCountVizianagaram = $result->fetch_assoc()['count'];

    $stmt->close();
    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curved Panel Dynamic Dashboard</title>
    <style>
        /* ... (previous styles remain the same) ... */
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

        .dashboard-container {
            display: flex;
            flex-grow: 1;
            position: relative;
        }

        .sidebar {
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

        .sidebar.collapsed {
            width: var(--sidebar-collapsed-width);
        }

        .logo {
            font-size: 24px;
            font-weight: bold;
            color: white;
            margin-bottom: 2rem;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .logo {
            opacity: 0;
        }

        .nav {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .nav a {
            color: white;
            text-decoration: none;
            padding: 0.75rem 1rem;
            border-radius: 15px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            overflow: hidden;
        }

        .nav a i {
            margin-right: 15px;
            font-size: 1.2rem;
            width: 20px;
            text-align: center;
        }

        .nav a span {
            transition: opacity 0.3s ease, transform 0.3s ease;
        }

        .sidebar.collapsed .nav a span {
            opacity: 0;
            transform: translateX(20px);
        }

        .nav a:hover, .nav a.active {
            background: rgba(255,255,255,0.2);
            transform: translateX(5px);
        }

        .nav a::after {
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

        .nav a:hover::after, .nav a.active::after {
            transform: scaleX(1);
            transform-origin: left;
        }

        .user-info {
            margin-top: auto;
            display: flex;
            align-items: center;
            color: white;
            transition: opacity 0.3s ease;
        }

        .sidebar.collapsed .user-info {
            opacity: 0;
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

        .toggle-btn {
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

        .sidebar.collapsed .toggle-btn {
            transform: rotate(180deg);
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

        .sidebar.collapsed + .main-content {
            margin-left: calc(var(--sidebar-collapsed-width) + 40px);
        }

        .welcome-card {
            grid-column: 1 / -1;
            background: var(--gradient-1);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            margin-bottom: 1.5rem; /* Added margin-bottom */
        }

        .welcome-text h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .balance {
            font-size: 2.5rem;
            font-weight: bold;
        }

        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px) rotate(2deg);
            box-shadow: 0 15px 40px rgba(0,0,0,0.2);
        }

        .stat-card h3 {
            font-size: 1.2rem;
            color: #666;
            margin-bottom: 1rem;
        }

        .stat-card p {
            font-size: 1.8rem;
            font-weight: bold;
            color: #333;
        }

        .graph-card {
            grid-column: 1 / -1;
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .graph-placeholder {
            width: 100%;
            height: 300px;
            background: var(--gradient-3);
            border-radius: 10px;
            display: flex;
            justify-content: center;
            align-items: center;
            color: white;
            font-size: 1.2rem;
        }

        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (max-width: 768px) {
            .sidebar {
                top: 10px;
                left: 10px;
                width: calc(100% - 20px);
                height: auto;
                flex-direction: row;
                justify-content: space-between;
                align-items: center;
                padding: 1rem;
            }

            .sidebar.collapsed {
                width: calc(100% - 20px);
                height: 60px;
            }

            .logo {
                margin-bottom: 0;
            }

            .nav {
                flex-direction: row;
                justify-content: center;
                display: none;
            }

            .sidebar:not(.collapsed) .nav {
                display: flex;
                flex-direction: column;
            }

            .user-info {
                margin-top: 0;
            }

            .toggle-btn {
                top: auto;
                bottom: -15px;
                right: 20px;
            }

            .main-content {
                margin-left: 0;
                margin-top: 100px;
                grid-template-columns: 1fr;
            }

            .sidebar.collapsed + .main-content {
                margin-left: 0;
                margin-top: 80px;
            }
        }

        .admin-details {
            grid-column: 1 / -1;
            background: linear-gradient(to bottom right, #2F4F4F, #3CB371);
            border-radius: 20px;
            padding: 1.5rem;
            color: white;
            margin-bottom: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .admin-details h2 {
            margin-bottom: 1rem;
            text-align: center;
            font-size: 1.5rem;
        }

        .admin-details-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1rem;
        }

        .admin-detail-item {
            background: rgba(255,255,255,0.1);
            padding: 1rem;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
        }

        .admin-detail-item:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .admin-detail-item h3 {
            font-size: 0.9rem;
            margin-bottom: 0.3rem;
            color: rgba(255,255,255,0.8);
        }

        .admin-detail-item p {
            font-size: 1.1rem;
            font-weight: bold;
        }

        .admin-detail-item i {
            font-size: 1.5rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .employee-count-cards {
            grid-column: 1 / -1;
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .employee-count-card {
            background: linear-gradient(135deg, #A7D129, #616F39);
            border-radius: 20px;
            padding: 1.25rem;
            color: white;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 130px;
        }

        .employee-count-card h3 {
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .employee-count-card p {
            font-size: 2rem;
            font-weight: bold;
        }

        @media (max-width: 768px) {
            .employee-count-cards {
                grid-template-columns: 1fr;
            }
        }
    </style>
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="dashboard-container">
        <aside class="sidebar">
            <div class="logo">BankingCo</div>
            <nav class="nav">
                <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="admin_employee_registration.php"><i class="fas fa-user-plus"></i><span>Employee Registration</span></a>
                <a href="admin_shift.php"><i class="fas fa-calendar-alt"></i><span>Employee Shifts</span></a>
                <a href="admin_employeedata.php"><i class="fas fa-users"></i><span>Employee Data</span></a>
                <a href="admin_profile.php"><i class="fas fa-user-circle"></i><span>Profile</span></a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i><span>Logout</span></a>
            </nav>
            <div class="user-info">
                <div class="user-avatar">
                    <?php echo $firstLetter; ?>
                </div>
                <span><?php echo $adminname; ?></span>
            </div>
            <button class="toggle-btn">
                <i class="fas fa-chevron-left"></i>
            </button>
        </aside>
        <main class="main-content">
            <div class="welcome-card">
                <div class="welcome-text">
                    <h1>Welcome back, <?php echo $adminname; ?></h1>
                    <p>Your financial ecosystem dashboard</p>
                </div>
            </div>

            <div class="admin-details">
                <h2>Admin Profile</h2>
                <div class="admin-details-grid">
                    <div class="admin-detail-item">
                        <h3>Admin Username</h3>
                        <p><?php echo $adminDetails['adminname']; ?></p>
                    </div>
                    <div class="admin-detail-item">
                        <h3>Email</h3>
                        <p><?php echo $adminDetails['email']; ?></p>
                    </div>
                    <div class="admin-detail-item">
                        <h3>Surname</h3>
                        <p><?php echo $adminDetails['surname']; ?></p>
                    </div>
                    <div class="admin-detail-item">
                        <h3>Name</h3>
                        <p><?php echo $adminDetails['name']; ?></p>
                    </div>
                    <div class="admin-detail-item">
                        <h3>Phone</h3>
                        <p><?php echo $adminDetails['phone']; ?></p>
                    </div>
                    <div class="admin-detail-item">
                        <h3>Gender</h3>
                        <p><?php echo $adminDetails['gender']; ?></p>
                    </div>
                    <div class="admin-detail-item">
                        <h3>Date of Birth</h3>
                        <p><?php echo $adminDetails['dateofbirth']; ?></p>
                    </div>
                </div>
            </div>

            <div class="employee-count-cards">
                <div class="employee-count-card">
                    <h3>Employees in Vizag</h3>
                    <p><?php echo $employeeCountVizag; ?></p>
                </div>
                <div class="employee-count-card">
                    <h3>Employees in Vizianagaram</h3>
                    <p><?php echo $employeeCountVizianagaram; ?></p>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            const navLinks = document.querySelectorAll('.nav a');

            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });

            navLinks.forEach(link => {
                link.addEventListener('click', (e) => {
                    navLinks.forEach(l => l.classList.remove('active'));
                    link.classList.add('active');
                });
            });
        });
    </script>
</body>
</html>