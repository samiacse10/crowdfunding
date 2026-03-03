<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us - Crowdfunding Platform</title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Animate.css -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f6f9fc 0%, #e6f0fa 100%);
            min-height: 100vh;
            line-height: 1.6;
            color: #2c3e50;
        }

        /* Header/Navigation */
        .navbar {
            background: linear-gradient(135deg, #2A5C82, #1E3A5F);
            padding: 1rem 0;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 800;
            color: white !important;
            letter-spacing: -0.5px;
        }

        .navbar-brand i {
            color: #FFD166;
            margin-right: 10px;
            text-shadow: 0 2px 5px rgba(0,0,0,0.2);
        }

        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.9) !important;
            font-weight: 500;
            padding: 0.5rem 1rem !important;
            transition: all 0.3s ease;
            border-radius: 8px;
        }

        .navbar-nav .nav-link:hover {
            color: #FFD166 !important;
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }

        .navbar-nav .nav-link.active {
            color: #FFD166 !important;
            font-weight: 600;
            background: rgba(255,255,255,0.1);
        }

        /* Hero Section with Background Image */
        .hero-section {
            background: linear-gradient(rgba(0, 0, 0, 0.6), rgba(0, 0, 0, 0.6)), 
                        url('https://images.unsplash.com/photo-1532629345422-7515f3d16bb6?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 120px 0;
            margin-bottom: 60px;
            position: relative;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: radial-gradient(circle at center, transparent 0%, rgba(0,0,0,0.4) 100%);
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            margin: 0 auto;
            text-align: center;
        }

        .hero-title {
            font-size: 3.8rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            letter-spacing: -1px;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
            animation: fadeInDown 1s ease;
        }

        .hero-title span {
            color: #FFD166;
            display: block;
            font-size: 2.5rem;
            margin-top: 0.5rem;
        }

        .hero-subtitle {
            font-size: 1.4rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.3);
            animation: fadeInUp 1s ease;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 3rem;
            animation: fadeIn 1.5s ease;
        }

        .stat-item {
            text-align: center;
            background: rgba(255,255,255,0.1);
            padding: 1.5rem 2rem;
            border-radius: 15px;
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
            min-width: 150px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 800;
            color: #FFD166;
            display: block;
            line-height: 1.2;
        }

        .stat-label {
            font-size: 0.95rem;
            opacity: 0.9;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        /* Section Titles */
        .section-title {
            font-size: 2.8rem;
            font-weight: 800;
            color: #1E3A5F;
            margin-bottom: 2rem;
            position: relative;
            padding-bottom: 1rem;
            letter-spacing: -0.5px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #FF6B6B, #FFD166);
            border-radius: 2px;
        }

        .section-title.center {
            text-align: center;
        }

        .section-title.center::after {
            left: 50%;
            transform: translateX(-50%);
            width: 150px;
            background: linear-gradient(90deg, #FF6B6B, #FFD166, #4ECDC4);
        }

        /* Feature Cards */
        .feature-card {
            background: white;
            border-radius: 20px;
            padding: 2.5rem 2rem;
            box-shadow: 0 15px 35px rgba(30, 58, 95, 0.1);
            transition: all 0.4s ease;
            height: 100%;
            border: 1px solid rgba(255,107,107,0.1);
            position: relative;
            overflow: hidden;
        }

        .feature-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #FF6B6B, #FFD166);
            transition: all 0.4s ease;
        }

        .feature-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 45px rgba(30, 58, 95, 0.2);
        }

        .feature-card:hover::before {
            height: 8px;
        }

        .feature-icon {
            width: 90px;
            height: 90px;
            background: linear-gradient(135deg, #FF6B6B, #FFD166);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 2rem;
            box-shadow: 0 10px 20px rgba(255,107,107,0.3);
        }

        .feature-icon i {
            font-size: 2.8rem;
            color: white;
        }

        .feature-title {
            font-size: 1.6rem;
            font-weight: 700;
            margin-bottom: 1rem;
            color: #1E3A5F;
        }

        .feature-text {
            color: #5a6c7e;
            line-height: 1.8;
        }

        /* How It Works Steps */
        .steps-container {
            margin: 3rem 0;
        }

        .step-item {
            background: white;
            border-radius: 20px;
            padding: 2.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 15px 35px rgba(30, 58, 95, 0.1);
            position: relative;
            transition: all 0.4s ease;
            border-left: 5px solid transparent;
            border-image: linear-gradient(135deg, #FF6B6B, #FFD166);
            border-image-slice: 1;
        }

        .step-item:hover {
            transform: translateX(15px);
            box-shadow: 0 20px 45px rgba(30, 58, 95, 0.15);
        }

        .step-number {
            position: absolute;
            top: -20px;
            left: 40px;
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, #FF6B6B, #FFD166);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 1.8rem;
            box-shadow: 0 8px 20px rgba(255,107,107,0.4);
        }

        .step-content {
            margin-left: 50px;
        }

        .step-title {
            font-size: 2rem;
            font-weight: 700;
            color: #1E3A5F;
            margin-bottom: 1rem;
        }

        .step-description {
            color: #5a6c7e;
            font-size: 1.1rem;
            line-height: 1.8;
        }

        /* Team Cards */
        .team-grid {
            margin: 3rem 0;
        }

        .team-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 15px 35px rgba(30, 58, 95, 0.1);
            transition: all 0.4s ease;
            text-align: center;
            position: relative;
        }

        .team-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 45px rgba(30, 58, 95, 0.2);
        }

        .team-image {
            width: 100%;
            height: 280px;
            object-fit: cover;
            transition: all 0.6s ease;
        }

        .team-card:hover .team-image {
            transform: scale(1.1);
        }

        .team-info {
            padding: 2rem 1.5rem;
            background: linear-gradient(135deg, #1E3A5F, #2A5C82);
            color: white;
        }

        .team-name {
            font-size: 1.4rem;
            font-weight: 700;
            margin-bottom: 0.3rem;
            color: white;
        }

        .team-role {
            color: #FFD166;
            font-weight: 600;
            margin-bottom: 1rem;
            font-size: 0.95rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .team-social {
            display: flex;
            justify-content: center;
            gap: 1rem;
        }

        .team-social a {
            width: 38px;
            height: 38px;
            background: rgba(255,255,255,0.2);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            transition: all 0.3s ease;
            text-decoration: none;
            font-size: 1.1rem;
        }

        .team-social a:hover {
            background: #FFD166;
            color: #1E3A5F;
            transform: translateY(-5px);
        }

        /* Stats Section with Background Image */
        .stats-section {
            background: linear-gradient(rgba(0, 0, 0, 0.7), rgba(0, 0, 0, 0.7)), 
                        url('https://images.unsplash.com/photo-1559027615-cd4628902d4a?ixlib=rb-4.0.3&ixid=M3wxMjA3fDB8MHxwaG90by1wYWdlfHx8fGVufDB8fHx8fA%3D%3D&auto=format&fit=crop&w=2070&q=80');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: white;
            padding: 80px 0;
            margin: 60px 0;
            border-radius: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 2rem;
            text-align: center;
        }

        .stats-grid .stat-item {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(5px);
            border: 1px solid rgba(255,255,255,0.2);
        }

        .stats-grid .stat-number {
            font-size: 3.2rem;
            color: #FFD166;
        }

        .stats-grid .stat-label {
            font-size: 1.1rem;
            color: white;
        }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, #FF6B6B, #FFD166);
            color: white;
            padding: 100px 0;
            border-radius: 40px;
            margin: 60px 0;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .cta-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100" preserveAspectRatio="none"><path d="M0 0 L100 100 M100 0 L0 100" stroke="rgba(255,255,255,0.1)" stroke-width="1"/></svg>');
            opacity: 0.2;
        }

        .cta-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 1rem;
            position: relative;
            z-index: 2;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.2);
        }

        .cta-text {
            font-size: 1.3rem;
            opacity: 0.95;
            margin-bottom: 2rem;
            max-width: 600px;
            margin-left: auto;
            margin-right: auto;
            position: relative;
            z-index: 2;
        }

        .cta-button {
            display: inline-block;
            padding: 1.2rem 3.5rem;
            background: white;
            color: #1E3A5F;
            text-decoration: none;
            border-radius: 60px;
            font-weight: 700;
            font-size: 1.3rem;
            transition: all 0.4s ease;
            box-shadow: 0 15px 35px rgba(0,0,0,0.2);
            position: relative;
            z-index: 2;
            border: 2px solid transparent;
        }

        .cta-button:hover {
            transform: translateY(-5px) scale(1.05);
            box-shadow: 0 25px 45px rgba(0,0,0,0.3);
            background: transparent;
            color: white;
            border-color: white;
        }

        .cta-button i {
            transition: transform 0.3s ease;
        }

        .cta-button:hover i {
            transform: translateX(5px) rotate(10deg);
        }

        /* Footer */
        .footer {
            background: linear-gradient(135deg, #1E3A5F, #2A5C82);
            color: white;
            padding: 30px 0;
            margin-top: 60px;
        }

        .footer p {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }

        /* Animations */
        @keyframes float {
            0% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
            100% { transform: translateY(0px); }
        }

        .feature-icon {
            animation: float 3s ease-in-out infinite;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.8rem;
            }
            
            .hero-title span {
                font-size: 1.8rem;
            }
            
            .hero-subtitle {
                font-size: 1.2rem;
            }
            
            .hero-stats {
                flex-direction: column;
                gap: 1rem;
                align-items: center;
            }
            
            .stat-item {
                width: 100%;
                max-width: 300px;
            }
            
            .section-title {
                font-size: 2.2rem;
            }
            
            .step-title {
                font-size: 1.6rem;
            }
            
            .step-number {
                width: 50px;
                height: 50px;
                font-size: 1.5rem;
                left: 20px;
            }
            
            .step-content {
                margin-left: 30px;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
            
            .cta-title {
                font-size: 2.2rem;
            }
            
            .cta-text {
                font-size: 1.1rem;
            }
            
            .cta-button {
                padding: 1rem 2.5rem;
                font-size: 1.1rem;
            }
        }

        /* Funding-related decorative elements */
        .funding-badge {
            background: linear-gradient(135deg, #FF6B6B, #FFD166);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            display: inline-block;
            margin-bottom: 1rem;
        }

        .progress-indicator {
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
            margin: 1rem 0;
        }

        .progress-indicator .progress-fill {
            height: 100%;
            width: 75%;
            background: linear-gradient(90deg, #FF6B6B, #FFD166);
            border-radius: 4px;
            animation: progressPulse 2s ease-in-out infinite;
        }

        @keyframes progressPulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }
    </style>
</head>
<body>

    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container">
            <a class="navbar-brand" href="/crowdfunding/index.php">
                <i class="fa-solid fa-hand-holding-heart"></i> Crowdfund
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="/crowdfunding/index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="/crowdfunding/footer_links/about_us.php">About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#how-it-works">How It Works</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#contact">Contact</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section with Funding Image -->
    <section class="hero-section">
        <div class="container">
            <div class="hero-content animate__animated animate__fadeIn">
                <span class="funding-badge animate__animated animate__fadeInDown">🌟 Making Dreams Reality Since 2024</span>
                <h1 class="hero-title">Empowering Dreams,<br><span>One Campaign at a Time</span></h1>
                <p class="hero-subtitle">Join thousands of creators and backers who are making a difference in the world through crowdfunding. Every contribution counts, every dream matters.</p>
                <div class="progress-indicator">
                    <div class="progress-fill"></div>
                </div>
                <div class="hero-stats">
                    <div class="stat-item">
                        <span class="stat-number">100+</span>
                        <span class="stat-label">Campaigns</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">$50K+</span>
                        <span class="stat-label">Raised</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-number">1K+</span>
                        <span class="stat-label">Backers</span>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container">
        <!-- About Section -->
        <div class="row mb-5">
            <div class="col-12">
                <h2 class="section-title animate__animated animate__fadeInLeft">About Crowdfund</h2>
                <div class="feature-card">
                    <div class="progress-indicator">
                        <div class="progress-fill" style="width: 85%;"></div>
                    </div>
                    <p style="font-size: 1.2rem; line-height: 1.9; color: #2c3e50;">
                        <i class="fa-solid fa-quote-left" style="color: #FF6B6B; font-size: 2rem; margin-right: 10px; float: left;"></i>
                        Our platform is a trusted crowdfunding space where compassion meets action. We connect generous donors with individuals and communities who need support the most.

                        Whether it's medical emergencies, education support, disaster relief, or personal hardships, we provide a secure and transparent way to raise and donate funds.

                        Users can start their own fundraising campaigns, share their stories, and receive support from kind-hearted people. At the same time, donors can explore meaningful causes and contribute with confidence, knowing their generosity creates real impact.

                        We believe that even a small donation can change a life. Together, we turn kindness into hope and hope into real change. Join us in making a difference, one campaign at a time.
                    </p>
                </div>
            </div>
        </div>

        <!-- Features -->
        <div class="row g-4 mb-5">
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-rocket"></i>
                    </div>
                    <h3 class="feature-title">Easy to Start</h3>
                    <p class="feature-text">Create your campaign in minutes with our simple step-by-step process. No technical skills required. Start raising funds today!</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-shield-heart"></i>
                    </div>
                    <h3 class="feature-title">Secure Donations</h3>
                    <p class="feature-text">Multiple payment methods including bKash, Nagad, Islamic Bank cards, and more. Your money is safe with our encrypted platform.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fa-solid fa-users-between-lines"></i>
                    </div>
                    <h3 class="feature-title">Community Support</h3>
                    <p class="feature-text">Join a community of passionate backers ready to support your next big idea or cause. Together we achieve more.</p>
                </div>
            </div>
        </div>

        <!-- How It Works Section -->
        <div id="how-it-works">
            <h2 class="section-title center animate__animated animate__fadeIn">How It Works</h2>
            
            <div class="steps-container">
                <div class="step-item animate__animated animate__fadeInLeft">
                    <div class="step-number">1</div>
                    <div class="step-content">
                        <h3 class="step-title">Create an Account</h3>
                        <p class="step-description">
                           Start by signing up on the platform. Once registered, you can choose to donate to campaigns, start your own fundraising campaign, or do both. Set up your profile and get ready to make an impact.
                        </p>
                    </div>
                </div>

                <div class="step-item animate__animated animate__fadeInRight">
                    <div class="step-number">2</div>
                    <div class="step-content">
                        <h3 class="step-title">Start or Support a Campaign</h3>
                        <p class="step-description">
                           If you are an organizer, create a campaign by setting a goal, sharing your story, and adding images or videos. If you are a supporter, explore campaigns and donate to causes that matter to you.
                        </p>
                    </div>
                </div>

                <div class="step-item animate__animated animate__fadeInLeft">
                    <div class="step-number">3</div>
                    <div class="step-content">
                        <h3 class="step-title">Track Impact & Stay Connected</h3>
                        <p class="step-description">
                           Organizers can manage donations, share updates, and thank supporters. Donors can track the campaigns they support and see how their contributions are making a real difference.
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Section with Funding Image -->
        <div class="stats-section">
            <div class="stats-grid">
                <div class="stat-item">
                    <span class="stat-number">1+</span>
                    <span class="stat-label">Years of Service</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">5K+</span>
                    <span class="stat-label">Happy Fundraisers</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">10+</span>
                    <span class="stat-label">Countries</span>
                </div>
                <div class="stat-item">
                    <span class="stat-number">24/7</span>
                    <span class="stat-label">Support</span>
                </div>
            </div>
        </div>

        <!-- Team Section -->
        <h2 class="section-title center animate__animated animate__fadeIn">Meet Our Team</h2>
        
        <div class="row g-4 team-grid">
            <div class="col-md-4">
                <div class="team-card animate__animated animate__fadeInUp">
                    <img src="owner1.jpg" alt="Team Member" class="team-image">
                    <div class="team-info">
                        <h4 class="team-name">Humayra Nuha</h4>
                        <p class="team-role">Team Member</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s;">
                    <img src="owner2.jpg" alt="Team Member" class="team-image">
                    <div class="team-info">
                        <h4 class="team-name">Moumita Ghose</h4>
                        <p class="team-role">Team Member</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                            <a href="#"><i class="fab fa-facebook-f"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="team-card animate__animated animate__fadeInUp" style="animation-delay: 0.4s;">
                    <img src="owner4.jpeg" alt="Team Member" class="team-image">
                    <div class="team-info">
                        <h4 class="team-name">Ritu Akter Samia</h4>
                        <p class="team-role">Team Member</p>
                        <div class="team-social">
                            <a href="#"><i class="fab fa-linkedin-in"></i></a>
                            <a href="#"><i class="fab fa-github"></i></a>
                            <a href="#"><i class="fab fa-twitter"></i></a>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Contact Section -->
        <div id="contact">
            <h2 class="section-title center animate__animated animate__fadeIn">Contact Us</h2>
            <div class="row g-4 mb-5">
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fa-solid fa-location-dot"></i>
                        </div>
                        <h3 class="feature-title">Address</h3>
                        <p class="feature-text">University of Barishal<br>Barishal, Bangladesh</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fa-solid fa-phone"></i>
                        </div>
                        <h3 class="feature-title">Phone</h3>
                        <p class="feature-text">01533114856<br>Mon-Fri, 9am-6pm</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card text-center">
                        <div class="feature-icon mx-auto">
                            <i class="fa-solid fa-envelope"></i>
                        </div>
                        <h3 class="feature-title">Email</h3>
                        <p class="feature-text">support@crowdfund.com<br>info@crowdfund.com</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- CTA Section -->
        <div class="cta-section">
            <h2 class="cta-title animate__animated animate__fadeInDown">Ready to Start Your Journey?</h2>
            <p class="cta-text animate__animated animate__fadeInUp">Join thousands of successful fundraisers who have brought their dreams to life with Crowdfund. Your story could be next.</p>
            <a href="/crowdfunding/index.php" class="cta-button animate__animated animate__zoomIn">
                <i class="fa-solid fa-rocket me-2"></i>Start Your Campaign
            </a>
        </div>
    </div>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-md-12 text-center">
                    <p>&copy; 2026 Crowdfunding Platform. All Rights Reserved. | <i class="fa-solid fa-heart" style="color: #FF6B6B;"></i> Making a difference together</p>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth',
                        block: 'start'
                    });
                }
            });
        });
        
        // Animate elements on scroll
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate__fadeIn');
                }
            });
        }, {
            threshold: 0.1
        });
        
        document.querySelectorAll('.feature-card, .team-card, .step-item').forEach(el => {
            observer.observe(el);
        });

        // Active link highlighting
        const currentLocation = window.location.pathname;
        const navLinks = document.querySelectorAll('.nav-link');
        
        navLinks.forEach(link => {
            const linkPath = link.getAttribute('href');
            if (currentLocation.includes(linkPath) && linkPath !== '#' && !linkPath.startsWith('#')) {
                link.classList.add('active');
            }
        });

        // Counter animation for stats
        function animateCounter(element, start, end, duration) {
            let startTimestamp = null;
            const step = (timestamp) => {
                if (!startTimestamp) startTimestamp = timestamp;
                const progress = Math.min((timestamp - startTimestamp) / duration, 1);
                element.innerText = Math.floor(progress * (end - start) + start) + '+';
                if (progress < 1) {
                    window.requestAnimationFrame(step);
                }
            };
            window.requestAnimationFrame(step);
        }

        // Trigger counter animation when stats section is visible
        const statsObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const statNumbers = document.querySelectorAll('.stats-grid .stat-number');
                    statNumbers.forEach(stat => {
                        const value = parseInt(stat.innerText);
                        animateCounter(stat, 0, value, 2000);
                    });
                    statsObserver.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });

        const statsSection = document.querySelector('.stats-section');
        if (statsSection) {
            statsObserver.observe(statsSection);
        }
    </script>
</body>
</html>