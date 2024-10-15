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

    // Database connection
    $servername = "localhost";
    $username = "sai2005";
    $password = "sai@2005";
    $dbname = "Bank";

    $conn = new mysqli($servername, $username, $password, $dbname);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Fetch employee data
    $sql = "SELECT * FROM employeeLogin";
    $result = $conn->query($sql);

    $employees = [];
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $employees[] = $row;
        }
    }

    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $username = $_POST['username'];
        $newPassword = $_POST['newPassword'];
    
        // Prepare and execute the update query
        $stmt = $conn->prepare("UPDATE employeeLogin SET password = ? WHERE employeename = ?");
        $stmt->bind_param("ss", $newPassword, $username);
        
        if ($stmt->execute()) {
            if ($stmt->affected_rows > 0) {
                echo "Password updated successfully";
            } else {
                echo "No matching employee found or password unchanged";
            }
        } else {
            echo "Error updating password: " . $conn->error;
        }
    
        $stmt->close();
    }

    $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Curved Panel Dynamic Dashboard</title>
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
            margin-right: 15px;
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
            grid-template-columns: 1fr;
            gap: 2rem;
            flex-grow: 1;
            transition: margin-left 0.5s cubic-bezier(0.68, -0.55, 0.27, 1.55);
        }

        .sidebar.collapsed + .main-content {
            margin-left: calc(var(--sidebar-collapsed-width) + 40px);
        }

        .welcome-card {
            background: var(--gradient-1);
            border-radius: 20px;
            padding: 2rem;
            color: white;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }

        .welcome-text h1 {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .employee-table {
            background: white;
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: auto;
        }

        .employee-table table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 15px;
        }

        .employee-table th, .employee-table td {
            padding: 15px;
            text-align: left;
            vertical-align: middle;
        }

        .employee-table th {
            background: var(--gradient-1);
            color: white;
            text-transform: uppercase;
            font-size: 14px;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .employee-table tr {
            background: white;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
            transition: transform 0.3s ease;
        }

        .employee-table tr:hover {
            transform: scale(1.02) translateY(-5px);
        }

        .employee-table td small {
            color: #666;
            font-size: 0.8em;
        }

        .forgot-password-btn {
            background: var(--gradient-3);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .forgot-password-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 10px rgba(0,0,0,0.2);
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            align-items: center;
            justify-content: center;
        }

        .modal-content {
            background: white;
            padding: 2rem;
            border-radius: 20px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
            width: 90%;
            max-width: 500px;
        }

        .modal h2 {
            margin-bottom: 1rem;
            color: var(--text-color);
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        .form-group input {
            width: 100%;
            padding: 0.5rem;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            margin-top: 1rem;
        }

        .btn-confirm, .btn-cancel {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-confirm {
            background: var(--gradient-1);
            color: white;
        }

        .btn-cancel {
            background: #ccc;
            color: var(--text-color);
        }

        .btn-confirm:hover, .btn-cancel:hover {
            transform: translateY(-2px);
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        /* New styles for search bar */
        .search-container {
            margin-bottom: 1rem;
        }

        .search-bar {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ccc;
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
        }

        .search-bar:focus {
            outline: none;
            border-color: #5BB318;
            box-shadow: 0 0 0 2px rgba(91, 179, 24, 0.2);
        }

        @media (max-width: 1200px) {
            .main-content {
                grid-template-columns: 1fr;
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
            }

            .sidebar.collapsed + .main-content {
                margin-left: 0;
                margin-top: 80px;
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
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i><span>Dashboard</span></a>
                <a href="admin_employee_registration.php"><i class="fas fa-user-plus"></i><span>Employee Registration</span></a>
                <a href="admin_shift.php"><i class="fas fa-calendar-alt"></i><span>Employee Shifts</span></a>
                <a href="admin_employeedata.php"  class="active"><i class="fas fa-users"></i><span>Employee Data</span></a>
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
                    <h1>Employee Data</h1>
                    <p>Employee Management Dashboard</p>
                </div>
            </div>
            <div class="employee-table">
                <div class="search-container">
                    <input type="text" id="searchBar" class="search-bar" placeholder="Search for employees...">
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>Employee Username</th>
                            <th>Employee Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Job Title</th>
                            <th>Branch</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody">
                    <?php foreach ($employees as $employee): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($employee['employeename']); ?></td>
                            <td>
                                <?php echo htmlspecialchars($employee['surname'] . ' ' . $employee['name']); ?>
                                <br>
                                <small><?php echo htmlspecialchars($employee['gender']); ?> | 
                                    <?php echo htmlspecialchars($employee['dateofbirth']); ?></small>
                            </td>
                            <td><?php echo htmlspecialchars($employee['email']); ?></td>
                            <td><?php echo htmlspecialchars($employee['phone']); ?></td>
                            <td><?php echo htmlspecialchars($employee['jobtitle']); ?></td>
                            <td><?php echo htmlspecialchars($employee['branchname']); ?></td>
                            <td>
                                <button class="forgot-password-btn" onclick="resetPassword('<?php echo htmlspecialchars($employee['email']); ?>')">Reset Password</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <div id="resetPasswordModal" class="modal">
                <div class="modal-content">
                    <h2>Reset Password</h2>
                    <form id="resetPasswordForm">
                        <input type="hidden" id="resetEmail" name="email">
                        <div class="form-group">
                            <label for="username">Username:</label>
                            <input type="text" id="username" name="username" required>
                        </div>
                        <div class="form-group">
                            <label for="newPassword">New Password:</label>
                            <input type="password" id="newPassword" name="newPassword" required>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn-confirm">Confirm</button>
                            <button type="button" class="btn-cancel" onclick="closeModal()">Cancel</button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.querySelector('.sidebar');
            const toggleBtn = document.querySelector('.toggle-btn');
            const navLinks = document.querySelectorAll('.nav a');
            const modal = document.getElementById('resetPasswordModal');
            const resetForm = document.getElementById('resetPasswordForm');
            const searchBar = document.getElementById('searchBar');
            const employeeTableBody = document.getElementById('employeeTableBody');

            toggleBtn.addEventListener('click', () => {
                sidebar.classList.toggle('collapsed');
            });

            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    navLinks.forEach(l => l.classList.remove('active'));
                    this.classList.add('active');
                });
            });

            function resetPassword(email) {
                document.getElementById('resetEmail').value = email;
                modal.style.display = 'flex';
            }

            function closeModal() {
                modal.style.display = 'none';
                resetForm.reset();
            }

            window.onclick = function(event) {
                if (event.target == modal) {
                    closeModal();
                }
            }

            resetForm.onsubmit = function(e) {
                e.preventDefault();
                const email = document.getElementById('resetEmail').value;
                const username = document.getElementById('username').value;
                const newPassword = document.getElementById('newPassword').value;

                const formData = new FormData();
                formData.append('email', email);
                formData.append('username', username);
                formData.append('newPassword', newPassword);

                fetch('rough.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.text())
                .then(result => {
                    alert("Password updated successfully");
                    closeModal();
                })
                .catch(error => {
                    console.error('Error:', error);
                    alert('An error occurred while resetting the password');
                });
            };

            // Search functionality
            searchBar.addEventListener('input', function() {
                const searchTerm = this.value.toLowerCase();
                const rows = employeeTableBody.getElementsByTagName('tr');

                for (let row of rows) {
                    const text = row.textContent.toLowerCase();
                    if (text.includes(searchTerm)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });

            // Make resetPassword and closeModal functions global
            window.resetPassword = resetPassword;
            window.closeModal = closeModal;
        });
    </script>
</body>
</html>