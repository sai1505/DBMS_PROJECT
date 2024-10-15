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

// Fetch all rows
$sql = "SELECT * FROM userLogin";
$result = $conn->query($sql);

if ($result) {
    $all_rows = $result->fetch_all(MYSQLI_ASSOC);
    $json_data = json_encode($all_rows);
    if ($json_data === false) {
        error_log("JSON encoding failed: " . json_last_error_msg());
        $json_data = '[]';
    }
} else {
    error_log("Query failed: " . $conn->error);
    $json_data = '[]';
}

$conn->close();
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
            flex-shrink: 0;
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
            min-width: 0;
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
            margin-right: 15px;
        }

        .user-details {
            display: flex;
            flex-direction: column;
        }

        .username {
            font-weight: 600;
        }

        .table-container {
            overflow-x: auto;
            margin-top: 2rem;
        }

        .shift-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
        }

        .shift-table th,
        .shift-table td {
            padding: 15px;
            text-align: left;
            background: white;
            white-space: nowrap;
        }

        .shift-table th {
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .shift-table tr {
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .shift-table tr:hover {
            transform: scale(1.02);
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

        .search-container {
            margin-top: 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .search-bar {
            width: 100%;
            max-width: 500x;
            display: flex;
            background: white;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }

        .search-bar input {
            flex-grow: 1;
            padding: 15px 20px;
            border: none;
            outline: none;
            font-size: 16px;
        }

        .search-bar button {
            padding: 15px 20px;
            background: linear-gradient(45deg, var(--primary-color), var(--secondary-color));
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .search-bar button:hover {
            opacity: 0.8;
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
                <a href="employee_shift.php">Shifts</a>
                <a href="employee_userdata.php"  class="active">User Data</a>
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
                    <h1>User Data</h1>
                    <p>Here are the users data from the bank</p>
                </div>
            </div>
            <div class="search-container fade-in-up">
                <form class="search-bar" action="" method="GET">
                    <input type="text" name="search" id="search-input" placeholder="Search for users..." value="">
                    <button type="submit">Search</button>
                </form>
            </div>
            <div class="fade-in-up table-container">
                <table class="shift-table" id="user-table">
                    <thead>
                        <tr>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Surname</th>
                            <th>Name</th>
                            <th>Address</th>
                            <th>Phone</th>
                            <th>Gender</th>
                            <th>Date of Birth</th>
                            <th>Account Opened</th>
                        </tr>
                    </thead>
                    <tbody id="table-body">
                        <!-- Table rows will be dynamically inserted here -->
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    <script>
        // Parse the JSON data
const userData = <?php echo $json_data; ?>;
console.log("User data:", userData);

function createTableRow(row) {
    return `
        <tr>
            <td>${escapeHtml(row.username)}</td>
            <td>${escapeHtml(row.email)}</td>
            <td>${escapeHtml(row.surname)}</td>
            <td>${escapeHtml(row.name)}</td>
            <td>${escapeHtml(row.address)}</td>
            <td>${escapeHtml(row.phone)}</td>
            <td>${escapeHtml(row.gender)}</td>
            <td>${escapeHtml(row.dateofbirth)}</td>
            <td>${escapeHtml(row.accountopened)}</td>
        </tr>
    `;
}

function populateTable(data) {
    const tableBody = document.getElementById('table-body');
    if (!tableBody) return;

    const tableContent = data.map(createTableRow).join('');
    tableBody.innerHTML = tableContent;
}

function escapeHtml(unsafe) {
    if (unsafe == null) return '';
    return unsafe
        .toString()
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function setupSearch() {
    const searchInput = document.getElementById('search-input');
    if (!searchInput) return;

    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const filteredData = userData.filter(row => 
            Object.values(row).some(value => 
                value && value.toString().toLowerCase().includes(searchTerm)
            )
        );
        populateTable(filteredData);
    });
}

// Wait for the DOM to be fully loaded
document.addEventListener('DOMContentLoaded', () => {
    populateTable(userData);
    setupSearch();
});
    </script>
</body>
</html>