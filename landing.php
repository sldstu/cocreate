<?php
require_once 'config/config.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is already logged in
if (isset($_SESSION['user_id'])) {
    header('Location: router.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Event Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Google+Sans:wght@300;400;500;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Theme CSS -->
    <link rel="stylesheet" href="public/assets/css/theme.css">
    <style>
        html {
            scroll-behavior: smooth;
        }

        body {
            font-family: 'Google Sans', 'Roboto', sans-serif;
            background-color: var(--color-background);
            color: var(--color-text-primary);
            transition: background-color 0.3s ease, color 0.3s ease;
        }

        /* Gradient text effect */
        .gradient-text {
            background: linear-gradient(to right, #4f46e5, #3b82f6, #8b5cf6);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        /* Glass card effect */
        .glass-card {
            background: rgba(255, 255, 255, 0.35);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.25);
            border-radius: 24px;
            transition: all 0.5s ease;
        }

        [data-theme="dark"] .glass-card {
            background: rgba(30, 30, 30, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .glass-card:hover {
            border: 4px solid transparent;
            background-clip: padding-box;
            border-image: linear-gradient(to top right, #6366f1, #8b5cf6) 1;
            transform: translateY(-5px);
            border-radius: 24px;
        }

        /* Animations */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .fade-in-up {
            animation: fadeInUp 0.9s ease-out forwards;
        }

        .fade-in-up.delay-100 {
            animation-delay: 0.2s;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .animate-fade-in {
            animation: fadeIn 1s ease-out forwards;
        }

        /* Floating animation */
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-10px); }
        }
        .animate-float {
            animation: float 4s ease-in-out infinite;
        }
        .animate-float.delay-200 {
            animation-delay: 2s;
        }
        .animate-float.delay-300 {
            animation-delay: 3s;
        }

        /* Gradient background animation */
        @keyframes gradientMove {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .animate-gradient {
            background-image: linear-gradient(-45deg, #c7d2fe, #e0f2fe, #ede9fe, #c4b5fd);
            background-size: 400% 400%;
            animation: gradientMove 15s ease infinite;
        }

        [data-theme="dark"] .animate-gradient {
            background-image: linear-gradient(-45deg, #1e293b, #0f172a, #1e1b4b, #312e81);
        }

        /* Nav link hover effect */
        .nav-link {
            position: relative;
            transition: color 0.3s ease;
            color: var(--color-text-secondary);
        }

        .nav-link::after {
            content: '';
            position: absolute;
            width: 0%;
            height: 2px;
            left: 0;
            bottom: -4px;
            background: linear-gradient(to right, #4f46e5, #3b82f6);
            transition: width 0.3s ease;
        }

        .nav-link:hover::after {
            width: 100%;
        }

        .hero-gradient {
            background: linear-gradient(to right, var(--color-primary), #7C3AED);
        }

        .app-name {
            color: var(--color-text-primary);
        }

        .feature-card {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border-light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevation-1);
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-elevation-3);
        }

        .btn-primary {
            background-color: var(--color-primary);
            color: white;
        }

        .btn-primary:hover {
            background-color: #3b78e7;
        }

        .btn-outline {
            border: 1px solid white;
            color: white;
        }

        .btn-outline:hover {
            background-color: white;
            color: var(--color-primary);
        }

        .testimonial-card, .announcement-card {
            background-color: var(--color-surface);
            border: 1px solid var(--color-border-light);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-elevation-1);
            transition: all 0.3s ease;
        }

        /* Dark mode specific overrides */
        [data-theme="dark"] .feature-card,
        [data-theme="dark"] .testimonial-card,
        [data-theme="dark"] .announcement-card {
            background-color: var(--color-surface);
            border-color: var(--color-border);
        }

        [data-theme="dark"] .bg-white {
            background-color: var(--color-surface);
        }

        [data-theme="dark"] .text-gray-600,
        [data-theme="dark"] .text-gray-700,
        [data-theme="dark"] .text-gray-800,
        [data-theme="dark"] .text-gray-900 {
            color: var(--color-text-secondary);
        }

        /* Theme toggle button */
        .theme-toggle {
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 50%;
            transition: background-color 0.2s;
        }

        .theme-toggle:hover {
            background-color: var(--color-hover);
        }

        /* Curved border gradient */
        .gradient-border {
            position: relative;
            border-radius: 24px;
            background-clip: padding-box;
            padding: 4px;
            background: linear-gradient(to top right, #6366f1, #8b5cf6);
        }

        .gradient-border-content {
            background-color: var(--color-surface);
            border-radius: 20px;
            height: 100%;
            width: 100%;
        }

        [data-theme="dark"] .gradient-border-content {
            background-color: var(--color-surface-dark, #1f2937);
        }

        /* Navbar background */
        .navbar-bg {
            background-color: var(--color-surface-transparent, rgba(255, 255, 255, 0.8));
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }

        [data-theme="dark"] .navbar-bg {
            background-color: rgba(30, 41, 59, 0.8);
        }
    </style>
</head>

<body class="relative min-h-screen overflow-x-hidden">
    <!-- Subtle Animated Gradient Background -->
    <div class="absolute inset-0 -z-10 animate-gradient bg-[length:400%_400%]"></div>

    <!-- Floating Icons Layer -->
    <div class="absolute top-0 left-0 w-full h-full overflow-hidden pointer-events-none -z-10">
        <div class="absolute top-20 left-10 w-10 h-10 bg-indigo-400 rounded-full opacity-20 animate-float"></div>
        <div class="absolute top-1/2 right-16 w-12 h-12 bg-pink-300 rounded-full opacity-20 animate-float delay-200"></div>
        <div class="absolute bottom-24 left-1/4 w-8 h-8 bg-blue-300 rounded-full opacity-20 animate-float delay-300"></div>
    </div>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 z-50 w-full navbar-bg shadow-lg border-b border-white/30 transition-all duration-300">
        <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center space-x-4">
                <img src="public/assets/img/brand/CoCreate-v2.png" alt="<?php echo APP_NAME; ?> Logo" class="h-10 w-10 drop-shadow-md rounded-xl">
                <span class="text-2xl font-extrabold app-name tracking-tight"><?php echo APP_NAME; ?></span>
            </div>
            <div class="hidden md:flex items-center space-x-10 font-medium">
                <a href="#features" class="nav-link">Features</a>
                <a href="#testimonials" class="nav-link">Testimonials</a>
                <a href="#announcements" class="nav-link">Announcements</a>
                <a href="#about" class="nav-link">About</a>
            </div>
            <div class="flex items-center space-x-4">
                <!-- Theme toggle button -->
                <button class="theme-toggle" onclick="toggleTheme()">
                    <i class="fas fa-moon dark:hidden" style="color: var(--color-text-secondary);"></i>
                    <i class="fas fa-sun hidden dark:inline" style="color: var(--color-text-secondary);"></i>
                </button>
                <a href="login.php" class="ml-4 bg-gradient-to-r from-indigo-600 to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold px-5 py-2 rounded-full shadow-lg hover:shadow-indigo-300 transition-all duration-300">
                    Login
                </a>
            </div>
            <button id="menuToggle" class="md:hidden focus:outline-none text-2xl" style="color: var(--color-text-primary);">
                <i class="fas fa-bars"></i>
            </button>
        </div>
        <div id="mobileMenu" class="md:hidden hidden px-6 pb-4 pt-2 flex flex-col space-y-4 border-t" style="background-color: var(--color-surface); border-color: var(--color-border-light);">
            <a href="#features" class="hover:text-blue-600" style="color: var(--color-text-secondary);">Features</a>
            <a href="#testimonials" class="hover:text-blue-600" style="color: var(--color-text-secondary);">Testimonials</a>
            <a href="#announcements" class="hover:text-blue-600" style="color: var(--color-text-secondary);">Announcements</a>
            <a href="#about" class="hover:text-blue-600" style="color: var(--color-text-secondary);">About</a>
            <a href="login.php" class="text-blue-600 font-semibold">Login</a>
        </div>
    </nav>

    <!-- Hero Section -->
    <main id="home" class="relative z-10 flex-grow flex items-center justify-center px-6 py-40 overflow-hidden">
        <!-- Glow and Floating Shapes -->
        <div class="absolute -top-20 -left-20 w-[500px] h-[500px] bg-gradient-to-tr from-indigo-300 via-blue-400 to-purple-400 rounded-full blur-3xl opacity-30 z-0"></div>
        <div class="absolute top-10 right-10 w-24 h-24 bg-pink-200 rounded-full opacity-20 animate-pulse"></div>
        <div class="absolute bottom-16 left-12 w-32 h-32 bg-blue-200 rounded-full opacity-20 animate-ping"></div>

        <!-- Hero Content with Gradient Border -->
        <div class="gradient-border w-full max-w-3xl fade-in-up">
            <div class="gradient-border-content p-12 text-center">
                <div class="flex justify-center mb-6">
                    <img src="public/assets/img/brand/CoCreate-v2.png" alt="<?php echo APP_NAME; ?> Logo" class="h-24 w-24 drop-shadow-md animate-float">
                </div>
                <h1 class="text-5xl md:text-6xl font-extrabold gradient-text mb-4 animate-fade-in">
                    Welcome to <?php echo APP_NAME; ?>
                </h1>
                <p class="text-lg md:text-xl leading-relaxed fade-in-up delay-100" style="color: var(--color-text-secondary);">
                    Streamline Your Event Management. From small meetings to large conferences, we've got you covered.
                </p>
                <a href="register.php" class="inline-block mt-8 bg-gradient-to-r from-indigo-500 to-blue-600 hover:from-blue-700 hover:to-indigo-700 text-white font-semibold px-7 py-3 rounded-full shadow-xl hover:shadow-indigo-400 transition-all transform hover:scale-105 duration-300">
                    Get Started
                </a>
                <div class="mt-6">
                    <a href="#features" class="inline-flex items-center text-blue-600 hover:text-indigo-700 font-semibold transition duration-300">
                        <i class="fas fa-arrow-down mr-2 animate-bounce"></i> Learn More
                    </a>
                </div>
            </div>
        </div>
    </main>

    <!-- Features Section -->
    <section id="features" class="py-20 mt-28 max-w-7xl mx-auto px-6 text-center">
        <h2 class="text-3xl md:text-4xl font-extrabold gradient-text mb-6">Powerful Features for Seamless Event Management</h2>
        <p class="mt-4 text-xl mb-10" style="color: var(--color-text-secondary);">Everything you need to create and manage successful events</p>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mt-10">
            <div class="glass-card p-6 shadow-xl hover:shadow-2xl transition duration-300">
                <div class="text-blue-600 text-4xl mb-4">
                    <i class="fas fa-calendar-alt"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" style="color: var(--color-text-primary);">Event Planning</h3>
                <p style="color: var(--color-text-secondary);">Create and manage events with comprehensive planning tools. Set dates, locations, and agendas with ease.</p>
            </div>
            <div class="glass-card p-6 shadow-xl hover:shadow-2xl transition duration-300">
                <div class="text-green-600 text-4xl mb-4">
                    <i class="fas fa-tasks"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" style="color: var(--color-text-primary);">Task Management</h3>
                <p style="color: var(--color-text-secondary);">Assign and track tasks to ensure everything gets done on time. Monitor progress and set deadlines.</p>
            </div>
            <div class="glass-card p-6 shadow-xl hover:shadow-2xl transition duration-300">
                <div class="text-purple-600 text-4xl mb-4">
                    <i class="fas fa-users"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" style="color: var(--color-text-primary);">Team Collaboration</h3>
                <p style="color: var(--color-text-secondary);">Work together seamlessly with role-based access control. Share information and coordinate efforts.</p>
            </div>
        </div>
        
        <div class="grid grid-cols-1 md:grid-cols-3 gap-10 mt-10">
            <div class="glass-card p-6 shadow-xl hover:shadow-2xl transition duration-300">
                <div class="text-blue-600 text-4xl mb-4">
                    <i class="fas fa-chart-bar"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" style="color: var(--color-text-primary);">Analytics & Reporting</h3>
                <p style="color: var(--color-text-secondary);">Gain insights with detailed reports and analytics. Track attendance, budget, and performance metrics.</p>
            </div>
            <div class="glass-card p-6 shadow-xl hover:shadow-2xl transition duration-300">
                <div class="text-green-600 text-4xl mb-4">
                    <i class="fas fa-bell"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" style="color: var(--color-text-primary);">Notifications & Reminders</h3>
                <p style="color: var(--color-text-secondary);">Stay on top of deadlines with automated notifications and reminders. Never miss an important update.</p>
            </div>
            <div class="glass-card p-6 shadow-xl hover:shadow-2xl transition duration-300">
                <div class="text-purple-600 text-4xl mb-4">
                    <i class="fas fa-mobile-alt"></i>
                </div>
                <h3 class="text-xl font-bold mb-2" style="color: var(--color-text-primary);">Mobile Friendly</h3>
                <p style="color: var(--color-text-secondary);">Access your event management dashboard from anywhere. Fully responsive design for all devices.</p>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section id="testimonials" class="py-20 mt-10" style="background-color: var(--color-background);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold sm:text-4xl gradient-text">
                    What Our Users Say
                </h2>
                <p class="mt-4 text-xl" style="color: var(--color-text-secondary);">
                    Trusted by organizations of all sizes
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Testimonial 1 -->
                <div class="glass-card p-8 fade-in-up">
                    <div class="flex items-center mb-6">
                        <div class="text-yellow-400 flex">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="mb-6" style="color: var(--color-text-secondary);">
                        "CoCreate has transformed how we manage our university events. The intuitive interface and powerful features have saved us countless hours of work."
                    </p>
                    <div class="flex items-center">
                        <img class="h-12 w-12 rounded-full object-cover" src="public/images/testimonial-1.jpg" alt="User">
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold" style="color: var(--color-text-primary);">Sarah Johnson</h4>
                            <p style="color: var(--color-text-tertiary);">Event Coordinator, State University</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 2 -->
                <div class="glass-card p-8 fade-in-up delay-100">
                    <div class="flex items-center mb-6">
                        <div class="text-yellow-400 flex">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                        </div>
                    </div>
                    <p class="mb-6" style="color: var(--color-text-secondary);">
                        "The task management and team collaboration features are outstanding. We've improved our efficiency by 40% since implementing CoCreate."
                    </p>
                    <div class="flex items-center">
                        <img class="h-12 w-12 rounded-full object-cover" src="public/images/testimonial-2.jpg" alt="User">
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold" style="color: var(--color-text-primary);">Michael Chen</h4>
                            <p style="color: var(--color-text-tertiary);">Department Head, Tech Solutions Inc.</p>
                        </div>
                    </div>
                </div>

                <!-- Testimonial 3 -->
                <div class="glass-card p-8 fade-in-up delay-200">
                    <div class="flex items-center mb-6">
                        <div class="text-yellow-400 flex">
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star"></i>
                            <i class="fas fa-star-half-alt"></i>
                        </div>
                    </div>
                    <p class="mb-6" style="color: var(--color-text-secondary);">
                        "As a student organization leader, CoCreate has been invaluable for planning our campus events. The interface is user-friendly and perfect for our needs."
                    </p>
                    <div class="flex items-center">
                        <img class="h-12 w-12 rounded-full object-cover" src="public/images/testimonial-3.jpg" alt="User">
                        <div class="ml-4">
                            <h4 class="text-lg font-semibold" style="color: var(--color-text-primary);">Jessica Martinez</h4>
                            <p style="color: var(--color-text-tertiary);">Student Council President</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Announcements Section -->
    <section id="announcements" class="py-20" style="background-color: var(--color-surface);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-extrabold sm:text-4xl gradient-text">
                    Latest Updates
                </h2>
                <p class="mt-4 text-xl" style="color: var(--color-text-secondary);">
                    Stay informed about new features and improvements
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Announcement 1 -->
                <div class="glass-card p-6 fade-in-up">
                    <div class="mb-4" style="color: var(--color-primary);">
                        <span class="text-sm font-semibold">NEW FEATURE</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3" style="color: var(--color-text-primary);">Mobile App Launch</h3>
                    <p class="mb-4" style="color: var(--color-text-secondary);">
                        We're excited to announce the launch of our mobile app for iOS and Android. Manage your events on the go!
                    </p>
                    <div class="text-sm" style="color: var(--color-text-tertiary);">
                        <i class="far fa-calendar-alt mr-2"></i> May 15, 2023
                    </div>
                </div>

                <!-- Announcement 2 -->
                <div class="glass-card p-6 fade-in-up delay-100">
                    <div class="mb-4" style="color: var(--color-secondary);">
                        <span class="text-sm font-semibold">UPDATE</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3" style="color: var(--color-text-primary);">Enhanced Reporting Tools</h3>
                    <p class="mb-4" style="color: var(--color-text-secondary);">
                        Our reporting dashboard has been completely revamped with new visualizations and export options.
                    </p>
                    <div class="text-sm" style="color: var(--color-text-tertiary);">
                        <i class="far fa-calendar-alt mr-2"></i> April 28, 2023
                    </div>
                </div>

                <!-- Announcement 3 -->
                <div class="glass-card p-6 fade-in-up delay-200">
                    <div class="mb-4" style="color: var(--color-accent);">
                        <span class="text-sm font-semibold">MAINTENANCE</span>
                    </div>
                    <h3 class="text-xl font-semibold mb-3" style="color: var(--color-text-primary);">System Upgrade</h3>
                    <p class="mb-4" style="color: var(--color-text-secondary);">
                        We'll be performing a system upgrade on June 5th from 2-4 AM EST. The system will be unavailable during this time.
                    </p>
                    <div class="text-sm" style="color: var(--color-text-tertiary);">
                        <i class="far fa-calendar-alt mr-2"></i> April 10, 2023
                    </div>
                </div>
            </div>

            <div class="text-center mt-12">
                <a href="#" style="color: var(--color-primary);" class="hover:underline font-medium">
                    View all announcements <i class="fas fa-arrow-right ml-2"></i>
                </a>
            </div>
        </div>
    </section>

    <!-- About Section -->
    <section id="about" class="py-20" style="background-color: var(--color-background);">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="lg:grid lg:grid-cols-2 lg:gap-8 items-center">
                <div class="fade-in-up">
                    <h2 class="text-3xl font-extrabold sm:text-4xl mb-6 gradient-text">
                        About <?php echo APP_NAME; ?>
                    </h2>
                    <p class="text-lg mb-6" style="color: var(--color-text-secondary);">
                        <?php echo APP_NAME; ?> was developed to simplify the complex process of event management for organizations of all sizes. Our mission is to provide a comprehensive, user-friendly platform that streamlines every aspect of event planning and execution.
                    </p>
                    <p class="text-lg mb-6" style="color: var(--color-text-secondary);">
                        Founded in 2022, our team consists of experienced event planners and software developers who understand the challenges faced by event organizers. We've combined our expertise to create a solution that addresses real-world needs.
                    </p>
                    <p class="text-lg" style="color: var(--color-text-secondary);">
                        Whether you're organizing a small departmental meeting or a large campus-wide event, <?php echo APP_NAME; ?> provides the tools you need to succeed.
                        </p>
                </div>
                <div class="mt-10 lg:mt-0 fade-in-up delay-100">
                    <img src="public/images/about-image.svg" alt="About <?php echo APP_NAME; ?>" class="w-full animate-float">
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="py-20 hero-gradient text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl font-extrabold text-white sm:text-4xl mb-6">
                Ready to streamline your event management?
            </h2>
            <p class="text-xl text-indigo-100 mb-8 max-w-3xl mx-auto">
                Join thousands of organizations that trust <?php echo APP_NAME; ?> for their event planning needs.
            </p>
            <div class="flex flex-col sm:flex-row justify-center space-y-4 sm:space-y-0 sm:space-x-4">
                <a href="register.php" class="bg-white hover:bg-gray-100 px-6 py-3 rounded-full text-lg font-medium shadow-md transform hover:scale-105 transition-all duration-300" style="color: var(--color-primary);">
                    Get Started for Free
                </a>
                <a href="login.php" class="border border-white text-white hover:bg-white hover:text-indigo-600 px-6 py-3 rounded-full text-lg font-medium transform hover:scale-105 transition-all duration-300">
                    Sign In
                </a>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="py-12" style="background-color: #202124; color: white;">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-xl font-bold mb-4"><?php echo APP_NAME; ?></h3>
                    <p style="color: #9AA0A6;">
                        Simplifying event management for organizations of all sizes.
                    </p>
                    <div class="flex space-x-4 mt-4">
                        <a href="#" style="color: #9AA0A6;" class="hover:text-white">
                            <i class="fab fa-facebook-f"></i>
                        </a>
                        <a href="#" style="color: #9AA0A6;" class="hover:text-white">
                            <i class="fab fa-twitter"></i>
                        </a>
                        <a href="#" style="color: #9AA0A6;" class="hover:text-white">
                            <i class="fab fa-instagram"></i>
                        </a>
                        <a href="#" style="color: #9AA0A6;" class="hover:text-white">
                            <i class="fab fa-linkedin-in"></i>
                        </a>
                    </div>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Quick Links</h4>
                    <ul class="space-y-2">
                        <li><a href="#features" style="color: #9AA0A6;" class="hover:text-white">Features</a></li>
                        <li><a href="#testimonials" style="color: #9AA0A6;" class="hover:text-white">Testimonials</a></li>
                        <li><a href="#announcements" style="color: #9AA0A6;" class="hover:text-white">Announcements</a></li>
                        <li><a href="#about" style="color: #9AA0A6;" class="hover:text-white">About</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Resources</h4>
                    <ul class="space-y-2">
                        <li><a href="#" style="color: #9AA0A6;" class="hover:text-white">Documentation</a></li>
                        <li><a href="#" style="color: #9AA0A6;" class="hover:text-white">Tutorials</a></li>
                        <li><a href="#" style="color: #9AA0A6;" class="hover:text-white">Blog</a></li>
                        <li><a href="#" style="color: #9AA0A6;" class="hover:text-white">Support</a></li>
                    </ul>
                </div>
                <div>
                    <h4 class="text-lg font-semibold mb-4">Contact</h4>
                    <ul class="space-y-2">
                        <li class="flex items-start">
                            <i class="fas fa-map-marker-alt mt-1 mr-3" style="color: #9AA0A6;"></i>
                            <span style="color: #9AA0A6;">123 University Ave, City, State 12345</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-envelope mt-1 mr-3" style="color: #9AA0A6;"></i>
                            <span style="color: #9AA0A6;">info@cocreate.com</span>
                        </li>
                        <li class="flex items-start">
                            <i class="fas fa-phone-alt mt-1 mr-3" style="color: #9AA0A6;"></i>
                            <span style="color: #9AA0A6;">(123) 456-7890</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="border-t mt-8 pt-8 text-center" style="border-color: #3C4043; color: #9AA0A6;">
                <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Theme JS -->
    <script src="public/assets/js/theme.js"></script>
    <script>
        // Mobile menu toggle
        document.getElementById('menuToggle').addEventListener('click', function() {
            document.getElementById('mobileMenu').classList.toggle('hidden');
        });

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });

        // Update theme toggle icon based on current theme
        function updateThemeIcon() {
            const currentTheme = document.documentElement.getAttribute('data-theme');
            const moonIcons = document.querySelectorAll('.fa-moon');
            const sunIcons = document.querySelectorAll('.fa-sun');

            if (currentTheme === 'dark') {
                moonIcons.forEach(icon => icon.classList.add('hidden'));
                sunIcons.forEach(icon => icon.classList.remove('hidden'));
            } else {
                moonIcons.forEach(icon => icon.classList.remove('hidden'));
                sunIcons.forEach(icon => icon.classList.add('hidden'));
            }
        }

        // Initial icon update and animations
        document.addEventListener('DOMContentLoaded', function() {
            updateThemeIcon();
            
            // Add animation classes with delay for staggered effect
            const animatedElements = document.querySelectorAll('.fade-in-up');
            animatedElements.forEach((el, index) => {
                if (!el.classList.contains('delay-100') && !el.classList.contains('delay-200')) {
                    el.style.animationDelay = (index * 0.1) + 's';
                }
            });
        });

        // Override the toggleTheme function to also update icons
        const originalToggleTheme = window.toggleTheme || function() {
            const currentTheme = document.documentElement.getAttribute('data-theme') || 'light';
            const newTheme = currentTheme === 'light' ? 'dark' : 'light';
            document.documentElement.setAttribute('data-theme', newTheme);
            localStorage.setItem('theme', newTheme);
        };
        
        window.toggleTheme = function() {
            originalToggleTheme();
            updateThemeIcon();
        };

        // Set initial theme based on user preference
        (function() {
            const savedTheme = localStorage.getItem('theme');
            if (savedTheme) {
                document.documentElement.setAttribute('data-theme', savedTheme);
                updateThemeIcon();
            } else if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-theme', 'dark');
                updateThemeIcon();
            }
        })();
    </script>
</body>

</html>
