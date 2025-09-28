<?php
// Start session and check for redirects BEFORE including header
session_start();
require_once 'config/database.php';
require_once 'includes/functions.php';

// Redirect logged-in users to their respective dashboards
if (isLoggedIn()) {
    $role = $_SESSION['role'];
    switch ($role) {
        case 'student':
            header('Location: /medex_system/student/dashboard.php');
            exit();
        case 'admin':
            header('Location: /medex_system/admin/dashboard.php');
            exit();
        case 'hod':
            header('Location: /medex_system/hod/dashboard.php');
            exit();
        case 'lecturer':
            header('Location: /medex_system/lecturer/dashboard.php');
            exit();
    }
}

// Now include header after redirect logic
$pageTitle = 'Home';
require_once 'includes/header.php';
?>

<style>
    body {
        background-color: #ebebeb;
        margin: 0;
        padding: 0;
    }

    /* Remove any Bootstrap container margins that might cause gaps */
    .container-fluid {
        padding: 0;
    }

    main {
        margin: 0;
        padding: 0;
    }

    /* Main Content */
    .hero {
        position: relative;
        height: 500px;
        overflow: hidden;
        background: linear-gradient(rgba(0, 0, 0, 0.5), rgba(0, 0, 0, 0.5)), url('assets/images/home.jpeg');
        background-size: cover;
        background-position: center;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: white;
        width: 100vw;
        margin: 0;
        margin-left: calc(-50vw + 50%);
    }

    .hero-image {
        display: none; /* Hide the img tag since we're using CSS background */
    }

    .hero-overlay {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        text-align: center;
        color: white;
        z-index: 1;
    }

    .hero-title {
        font-size: 3.5rem;
        margin-bottom: 60px;
        text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        font-weight: bold;
    }

    .action-buttons {
        display: flex;
        gap: 100px;
    }

    .action-btn {
        background-color: #0056b3;
        color: white;
        border: none;
        padding: 12px 25px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        font-weight: 500;
        transition: background-color 0.3s;
        text-decoration: none;
        display: inline-block;
    }

    .action-btn:hover {
        background-color: #004494;
        color: white;
        text-decoration: none;
    }

    /* Footer */
    .custom-footer {
        background-color: #333;
        color: white;
        padding: 30px 0;
        margin-top: 50px;
    }

    .footer-content {
        max-width: 1400px;
        margin: 0 auto;
        display: flex;
        justify-content: center;
        flex-wrap: wrap;
        gap: 30px;
    }

    .footer-btn {
        background-color: transparent;
        color: white;
        border: 1px solid #555;
        padding: 10px 20px;
        border-radius: 4px;
        cursor: pointer;
        font-size: 1rem;
        transition: all 0.3s;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .footer-btn:hover {
        background-color: #0056b3;
        border-color: #0056b3;
        color: white;
        text-decoration: none;
    }

    /* Content Section Styles */
    .content-section {
        background: white;
        padding: 60px 0;
        margin: 50px 0;
    }

    .section-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .section-title {
        text-align: center;
        font-size: 2.5rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 50px;
    }

    .features-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
        margin-bottom: 40px;
    }

    .feature-card {
        text-align: center;
        padding: 30px;
        background: #f8f9fa;
        border-radius: 10px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        transition: transform 0.3s ease, box-shadow 0.3s ease;
    }

    .feature-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    }

    .feature-icon {
        font-size: 3rem;
        color: #0056b3;
        margin-bottom: 20px;
    }

    .feature-title {
        font-size: 1.5rem;
        font-weight: bold;
        color: #333;
        margin-bottom: 15px;
    }

    .feature-description {
        color: #666;
        line-height: 1.6;
    }

    .steps-container {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        margin-top: 30px;
    }

    .step-card {
        background: #f8f9fa;
        padding: 25px;
        border-radius: 10px;
        border-left: 4px solid #0056b3;
        position: relative;
    }

    .step-number {
        background: #0056b3;
        color: white;
        width: 30px;
        height: 30px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: bold;
        margin-bottom: 15px;
    }

    .step-title {
        font-weight: bold;
        color: #333;
        margin-bottom: 10px;
    }

    .step-description {
        color: #666;
        font-size: 0.9rem;
        line-height: 1.5;
    }

    /* Mobile Responsiveness */
    @media (max-width: 768px) {
        .hero-title {
            font-size: 1.8rem;
        }

        .action-buttons {
            flex-direction: column;
            width: 80%;
            gap: 20px;
        }

        .footer-content {
            flex-direction: column;
            gap: 20px;
        }

        .footer-btn {
            width: 80%;
            justify-content: center;
            margin: 0 auto;
        }

        .content-section {
            padding: 40px 0;
            margin: 30px 0;
        }

        .section-title {
            font-size: 2rem;
            margin-bottom: 30px;
        }

        .features-grid {
            grid-template-columns: 1fr;
            gap: 20px;
        }

        .feature-card {
            padding: 20px;
        }

        .steps-container {
            grid-template-columns: 1fr;
            gap: 20px;
        }
    }
</style>

<main>
    <section class="hero">
        <div class="hero-overlay">
            <h1 class="hero-title">Medical Excuse Management System</h1>
            <div class="action-buttons">
                <a href="auth/login.php" class="action-btn" id="applyMedicalBtn">Apply for Medical</a>
                <a href="auth/login.php" class="action-btn secondary" id="viewRequestsBtn">View My Requests</a>
            </div>
        </div>
    </section>
</main>
<div class="custom-footer">
    <div class="footer-content">
        <a href="footer_content.php?section=contact" class="footer-btn" id="contactInfoBtn">
            <i class="fas fa-phone"></i> Contact Info
        </a>
        <a href="footer_content.php?section=faq" class="footer-btn" id="faqBtn">
            <i class="fas fa-question-circle"></i> FAQ
        </a>
        <a href="footer_content.php?section=privacy" class="footer-btn" id="privacyBtn">
            <i class="fas fa-shield-alt"></i> Privacy Policy
        </a>
        <a href="footer_content.php?section=about" class="footer-btn" id="aboutBtn">
            <i class="fas fa-info-circle"></i> About Us
        </a>
    </div>
</div>

<!-- Features Section -->
<div class="content-section">
    <div class="section-container">
        <h2 class="section-title">Why Choose Our System?</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-upload"></i>
                </div>
                <div class="feature-title">Easy Submission</div>
                <div class="feature-description">
                    Submit your medical excuses online with simple document uploads. No more paperwork hassles.
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="feature-title">Quick Processing</div>
                <div class="feature-description">
                    Fast review and approval process with real-time status tracking through your dashboard.
                </div>
            </div>
            <div class="feature-card">
                <div class="feature-icon">
                    <i class="fas fa-shield-alt"></i>
                </div>
                <div class="feature-title">Secure & Reliable</div>
                <div class="feature-description">
                    Your documents are safely stored with university-grade security and privacy protection.
                </div>
            </div>
        </div>
    </div>
</div>

<!-- How It Works Section -->
<div class="content-section" style="background: #f8f9fa;">
    <div class="section-container">
        <h2 class="section-title">How It Works</h2>
        <div class="steps-container">
            <div class="step-card">
                <div class="step-number">1</div>
                <div class="step-title">Create Account</div>
                <div class="step-description">
                    Register with your student details and get instant access to the system.
                </div>
            </div>
            <div class="step-card">
                <div class="step-number">2</div>
                <div class="step-title">Submit Application</div>
                <div class="step-description">
                    Upload your medical certificate, excuse letter, and application form.
                </div>
            </div>
            <div class="step-card">
                <div class="step-number">3</div>
                <div class="step-title">Admin Review</div>
                <div class="step-description">
                    Medical officer reviews your documents for completeness and authenticity.
                </div>
            </div>
            <div class="step-card">
                <div class="step-number">4</div>
                <div class="step-title">HOD Approval</div>
                <div class="step-description">
                    Head of Department gives final approval and notifies relevant lecturers.
                </div>
            </div>
        </div>
    </div>
</div>



<?php require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/footer.php'; ?>