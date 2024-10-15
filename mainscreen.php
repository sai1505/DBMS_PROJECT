<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BankingCo - Your Trusted Financial Partner</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            color: #333;
            overflow-x: hidden;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }

        header {
            background-color: #ffffff;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            z-index: 1000;
        }

        nav {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
        }

        .logo {
            font-size: 24px;
            font-weight: 700;
            color: #0066cc;
        }

        .nav-links {
            display: flex;
            list-style: none;
        }

        .nav-links li {
            margin-left: 30px;
        }

        .nav-links a {
            text-decoration: none;
            color: #333;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .nav-links a:hover {
            color: #0066cc;
        }

        .slideshow {
            height: 100vh;
            position: relative;
            overflow: hidden;
        }

        .slide {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            opacity: 0;
            transition: opacity 0.5s ease-in-out;
            background-size: cover;
            background-position: center;
        }

        .slide.active {
            opacity: 1;
        }

        .slide-content {
            position: relative;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            text-align: center;
            color: #ffffff;
        }

        .slide-content h2 {
            font-size: 48px;
            margin-bottom: 20px;
        }

        .slide-content p {
            font-size: 24px;
            margin-bottom: 30px;
        }

        .btn {
            display: inline-block;
            background-color: #0066cc;
            color: #ffffff;
            padding: 12px 30px;
            border-radius: 30px;
            text-decoration: none;
            font-weight: 600;
            transition: background-color 0.3s ease;
        }

        .btn:hover {
            background-color: #0052a3;
        }

        .services {
            padding: 100px 0;
            background-color: #f9f9f9;
        }

        .services h2 {
            text-align: center;
            font-size: 36px;
            margin-bottom: 50px;
        }

        .service-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 30px;
        }

        .service-card {
            background-color: #ffffff;
            border-radius: 10px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .service-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
        }

        .service-icon {
            font-size: 48px;
            color: #0066cc;
            margin-bottom: 20px;
        }

        .service-card h3 {
            font-size: 24px;
            margin-bottom: 15px;
        }

        footer {
            background-color: #0066cc;
            color: #ffffff;
            padding: 50px 0;
        }

        .footer-content {
            display: flex;
            justify-content: space-between;
        }

        .footer-section {
            flex: 1;
            margin-right: 30px;
        }
        
        .footer-section h3 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .footer-section ul {
            list-style: none;
        }

        .footer-section ul li {
            margin-bottom: 10px;
        }

        .footer-section a {
            color: #ffffff;
            text-decoration: none;
            transition: opacity 0.3s ease;
        }

        .footer-section a:hover {
            opacity: 0.8;
        }

        .copyright {
            text-align: center;
            margin-top: 50px;
            padding-top: 20px;
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <nav>
                <div class="logo">BankingCo</div>
                <ul class="nav-links">
                    <li><a href="#slideshow">Home</a></li>
                    <li><a href="#services">Services</a></li>
                    <li><a href="register.php">Sign Up</a></li>
                    <li><a href="login.php">Login</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="slideshow" id="slideshow">
        <div class="slide active" style="background-image: url('https://images.pexels.com/photos/2988232/pexels-photo-2988232.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2')">
            <div class="slide-content">
                <h2>Welcome to BankingCo</h2>
                <p>Your trusted financial partner</p>
                <a href="#services" class="btn">Learn More</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('https://images.pexels.com/photos/7316957/pexels-photo-7316957.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2')">
            <div class="slide-content">
                <h2>Expert Financial Advice</h2>
                <p>Our team of experts is here to help you achieve your financial goals</p>
                <a href="#services" class="btn">Learn More</a>
            </div>
        </div>
        <div class="slide" style="background-image: url('https://images.pexels.com/photos/7168609/pexels-photo-7168609.jpeg?auto=compress&cs=tinysrgb&w=1260&h=750&dpr=2')">
            <div class="slide-content">
                <h2>Secure Online Banking</h2>
                <p>Manage your accounts and pay bills online with our secure online banking system</p>
                <a href="#services" class="btn">Learn More</a>
            </div>
        </div>
    </div>

    <div class="services" id="services">
        <div class="container">
            <h2>Our Services</h2>
            <div class="service-grid">
                <div class="service-card">
                    <div class="service-icon">🏦</div>
                    <h3>Online Banking</h3>
                    <p>Manage your accounts and pay bills online with our secure online banking system</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">📈</div>
                    <h3>Investment Services</h3>
                    <p>Our team of experts can help you achieve your investment goals</p>
                </div>
                <div class="service-card">
                    <div class="service-icon">📊</div>
                    <h3>Financial Planning</h3>
                    <p>We can help you create a personalized financial plan to achieve your goals</p>
                </div>
            </div>
        </div>
    </div>

    <footer>
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h3>About Us</h3>
                    <p>We are a team of financial experts dedicated to helping you achieve your financial goals and financial analysis.</p>
                </div>
                <div class="footer-section">
                    <h3>Quick Links</h3>
                    <ul>
                        <li><a href="#slideshow">Home</a></li>
                        <li><a href="#services">Services</a></li>
                        <li><a href="register.php">Sign Up</a></li>
                        <li><a href="login.php">Login</a></li>
                    </ul>
                </div>
                <div class="footer-section">
                    <h3>Contact Us</h3>
                    <p>Phone: +91 6531858382</p>
                    <p>Email: [info@bankingco.com](mailto:info@bankingco.com)</p>
                </div>
            </div>
            <div class="copyright">
                <p>&copy; 2023 BankingCo. All rights reserved.</p>
            </div>
        </div>
    </footer>
    <script>
        // Slideshow script
        const slides = document.querySelectorAll('.slide');
        let currentSlide = 0;

        function showSlide() {
            slides[currentSlide].classList.add('active');
            setTimeout(() => {
                slides[currentSlide].classList.remove('active');
                currentSlide = (currentSlide + 1) % slides.length;
                showSlide();
            }, 5000);
        }
        showSlide();
    </script>
</body>
</html>