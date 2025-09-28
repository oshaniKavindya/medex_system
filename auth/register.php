<?php
// Include only the necessary functions without the visual header
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/config/database.php';
require_once $_SERVER['DOCUMENT_ROOT'] . '/medex_system/includes/functions.php';

// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Redirect if already logged in
if (isLoggedIn()) {
    header('Location: ../index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Medical Excuse Management System - Registration</title>
    <style>
        @import url('https://fonts.googleapis.com/css?family=Poppins');

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Poppins', sans-serif;
        }

        html, body {
            height: 100%;
            width: 100%;
            overflow-x: hidden;
        }

        body {
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px 0;
        }

        section {
            display: flex;
            justify-content: center;
            align-items: center;
            width: 100%;
            min-height: 100vh;
            background: url('../assets/images/back.jpg') no-repeat center center;
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .wrapper {
            width: 100%;
            max-width: 500px;
            padding: 40px 35px;
            color: white;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.2);
            border-radius: 20px;
            backdrop-filter: blur(35px);
            box-shadow: 
                0 25px 45px rgba(0, 0, 0, 0.1),
                0 0 0 1px rgba(255, 255, 255, 0.1) inset;
            margin: 20px;
            max-height: 90vh;
            overflow-y: auto;
            animation: slideIn 0.6s ease-out;
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(30px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .wrapper:hover {
            transform: translateY(-5px);
            box-shadow: 
                0 35px 55px rgba(0, 0, 0, 0.15),
                0 0 0 1px rgba(255, 255, 255, 0.15) inset;
        }

        .wrapper h2 {
            font-size: 2.2em;
            text-align: center;
            margin-bottom: 30px;
            color: white;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            font-weight: 600;
        }

        .form-row {
            display: flex;
            gap: 15px;
            margin-bottom: 0;
        }

        .form-row .input-field {
            flex: 1;
        }

        .input-field {
            position: relative;
            margin-bottom: 20px;
            background: transparent;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            transition: all 0.3s ease;
            animation: fadeInUp 0.6s ease-out forwards;
            opacity: 0;
        }

        .input-field:nth-child(1) { animation-delay: 0.1s; }
        .input-field:nth-child(2) { animation-delay: 0.2s; }
        .input-field:nth-child(3) { animation-delay: 0.3s; }
        .input-field:nth-child(4) { animation-delay: 0.4s; }
        .input-field:nth-child(5) { animation-delay: 0.5s; }
        .input-field:nth-child(6) { animation-delay: 0.6s; }

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

        .input-field:hover {
            border-color: rgba(255, 255, 255, 0.5);
            transform: scale(1.02);
        }

        .input-field input,
        .input-field select {
            width: 100%;
            padding: 12px 45px 12px 15px;
            border: none;
            outline: none;
            border-radius: 18px;
            font-size: 16px;
            background: transparent;
            color: white;
            font-weight: 400;
        }

        .input-field input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        .input-field select {
            color: rgba(255, 255, 255, 0.7);
        }

        .input-field select option {
            background: rgba(44, 62, 80, 0.95);
            color: white;
            padding: 10px;
        }

        .input-field select:focus,
        .input-field select:valid {
            color: white;
        }

        .input-field i {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            color: rgba(255, 255, 255, 0.7);
            transition: color 0.3s ease;
        }

        .input-field:hover i {
            color: white;
        }

        /* Year field styling */
        #year_group {
            transition: all 0.3s ease;
        }

        /* Checkbox styling */
        .checkbox-label {
            font-size: 14px;
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
            color: rgba(255, 255, 255, 0.9);
            font-weight: 400;
            animation: fadeInUp 0.6s ease-out 0.7s forwards;
            opacity: 0;
            gap: 10px;
        }

        .checkbox-label input[type="checkbox"] {
            margin-right: 0;
            margin-top: 3px;
            accent-color: white;
            width: 18px;
            height: 18px;
            flex-shrink: 0;
        }

        .checkbox-label a {
            color: white;
            text-decoration: underline;
            transition: all 0.3s ease;
        }

        .checkbox-label a:hover {
            text-shadow: 0 0 5px rgba(255, 255, 255, 0.8);
        }

        /* Button styling */
        .btn {
            width: 100%;
            padding: 12px;
            background: rgba(255, 255, 255, 0.9);
            color: #333;
            font-weight: 600;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s ease;
            margin: 20px 0;
            position: relative;
            overflow: hidden;
            animation: fadeInUp 0.6s ease-out 0.8s forwards;
            opacity: 0;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
            transition: left 0.5s ease;
        }

        .btn:hover {
            background: white;
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
        }

        .btn:hover::before {
            left: 100%;
        }

        .btn:active {
            transform: translateY(0px);
        }

        /* Login link */
        .login-link {
            text-align: center;
            margin-top: 20px;
            animation: fadeInUp 0.6s ease-out 0.9s forwards;
            opacity: 0;
        }

        .login-link p {
            color: rgba(255, 255, 255, 0.9);
            font-size: 14px;
            margin-bottom: 10px;
        }

        .login-link a {
            color: white;
            text-decoration: none;
            font-weight: 600;
            padding: 8px 20px;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .login-link a:hover {
            background: rgba(255, 255, 255, 0.1);
            border-color: rgba(255, 255, 255, 0.6);
            text-shadow: 0 0 8px rgba(255, 255, 255, 0.8);
        }

        /* Home button */
        .home-button {
            position: fixed;
            top: 20px;
            left: 20px;
            background: rgba(255, 255, 255, 0.9);
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            justify-content: center;
            align-items: center;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            z-index: 1000;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .home-button:hover {
            background: white;
            transform: translateY(-3px) scale(1.1);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            border-color: rgba(255, 255, 255, 0.5);
        }

        .home-button svg {
            width: 24px;
            height: 24px;
            color: #333;
            transition: color 0.3s ease;
        }

        .home-button:hover svg {
            color: #000;
        }

        /* Error and success messages */
        .message {
            padding: 12px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            text-align: center;
            display: none;
            backdrop-filter: blur(10px);
        }

        .error-message {
            background: rgba(255, 82, 82, 0.2);
            border: 1px solid rgba(255, 82, 82, 0.3);
            color: #ffebee;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.2);
            border: 1px solid rgba(76, 175, 80, 0.3);
            color: #e8f5e8;
        }

        /* Loading spinner */
        .loading {
            display: none;
            width: 16px;
            height: 16px;
            border: 2px solid rgba(51, 51, 51, 0.3);
            border-top: 2px solid #333;
            border-radius: 50%;
            animation: spin 1s linear infinite;
            margin-left: 8px;
            vertical-align: middle;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Icons using text symbols */
        .icon-user::before { content: "👤"; }
        .icon-id::before { content: "🆔"; }
        .icon-email::before { content: "✉️"; }
        .icon-lock::before { content: "🔒"; }
        .icon-building::before { content: "🏢"; }
        .icon-calendar::before { content = "📅"; }

        /* Responsive design */
        @media (max-width: 768px) {
            .wrapper {
                width: 95%;
                padding: 25px 20px;
                margin: 10px;
            }

            .wrapper h2 {
                font-size: 1.8em;
                margin-bottom: 25px;
            }

            .form-row {
                flex-direction: column;
                gap: 0;
            }

            .input-field {
                margin-bottom: 18px;
            }

            .input-field input,
            .input-field select {
                padding: 10px 40px 10px 12px;
                font-size: 15px;
            }

            .checkbox-label {
                font-size: 13px;
            }
        }

        @media (max-width: 480px) {
            .wrapper {
                width: 90%;
                padding: 20px 15px;
            }

            .wrapper h2 {
                font-size: 1.6em;
            }
        }

        /* Custom scrollbar */
        .wrapper::-webkit-scrollbar {
            width: 6px;
        }

        .wrapper::-webkit-scrollbar-track {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 3px;
        }

        .wrapper::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.3);
            border-radius: 3px;
        }

        .wrapper::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.5);
        }
    </style>
</head>
<body>
    <!-- Home Button -->
    <a href="../index.php" class="home-button" title="Go to Home">
        <svg viewBox="0 0 24 24" fill="currentColor">
            <path d="M10 20v-6h4v6h5v-8h3L12 3 2 12h3v8z"/>
        </svg>
    </a>

    <section>
        <div class="wrapper">
            <h2>Registration</h2>

            <div class="message error-message" id="errorMessage">
                Please check your information and try again.
            </div>

            <div class="message success-message" id="successMessage">
                Registration successful! Please wait while we redirect you.
            </div>

            <form action="process_auth.php" method="POST" class="needs-validation" novalidate id="registrationForm">
                <input type="hidden" name="action" value="register">

                <div class="form-row">
                    <div class="input-field">
                        <input type="text" id="full_name" name="full_name" placeholder="Full Name" required>
                        <i class="icon-user"></i>
                    </div>
                    <div class="input-field">
                        <input type="text" id="username" name="username" placeholder="Username" required>
                        <i class="icon-user"></i>
                    </div>
                </div>

                <div class="input-field">
                    <input type="email" id="email" name="email" placeholder="Email Address" required>
                    <i class="icon-email"></i>
                </div>

                <div class="form-row">
                    <div class="input-field">
                        <select id="role" name="role" required>
                            <option value="">Select your role</option>
                            <option value="student">Student</option>
                            <option value="lecturer">Lecturer</option>
                        </select>
                        <i class="icon-user"></i>
                    </div>
                    <div class="input-field">
                        <select id="department" name="department" required>
                            <option value="">Select department</option>
                            <option value="survey_geodesy">Survey & Geodesy</option>
                            <option value="remote_sensing_gis">Remote Sensing & GIS</option>
                        </select>
                        <i class="icon-building"></i>
                    </div>
                </div>

                <div class="input-field" id="year_group" style="display: none;">
                    <select id="year" name="year">
                        <option value="">Select academic year</option>
                        <option value="1">1st Year</option>
                        <option value="2">2nd Year</option>
                        <option value="3">3rd Year</option>
                        <option value="4">4th Year</option>
                    </select>
                    <i class="icon-calendar"></i>
                </div>

                <div class="form-row">
                    <div class="input-field">
                        <input type="password" id="password" name="password" placeholder="Password" minlength="8" required>
                        <i class="icon-lock"></i>
                    </div>
                    <div class="input-field">
                        <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm Password" required>
                        <i class="icon-lock"></i>
                    </div>
                </div>

                <label class="checkbox-label">
                    <input type="checkbox" id="terms" name="terms" required>
                    <span>I agree to the <a href="#" data-bs-toggle="modal" data-bs-target="#termsModal">Terms and Conditions</a> and accept that the provided details are correct</span>
                </label>

                <button type="submit" class="btn">
                    Register
                    <span class="loading" id="loadingSpinner"></span>
                </button>

                <div class="login-link">
                    <p>Already have an account?</p>
                    <a href="login.php">Login Here</a>
                </div>
            </form>
        </div>
    </section>

    <!-- Terms and Conditions Modal -->
    <div class="modal fade" id="termsModal" tabindex="-1" style="display: none;">
        <div class="modal-dialog modal-lg">
            <div class="modal-content" style="background: rgba(44, 62, 80, 0.95); color: white; border: none; border-radius: 15px;">
                <div class="modal-header" style="border-bottom: 1px solid rgba(255,255,255,0.1);">
                    <h5 class="modal-title">Terms and Conditions</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" style="filter: invert(1);"></button>
                </div>
                <div class="modal-body">
                    <h6>Medical Excuse Management System - Terms of Use</h6>
                    <p>By registering for this system, you agree to:</p>
                    <ul>
                        <li>Provide accurate and truthful information in all submissions</li>
                        <li>Submit only genuine medical documents and certificates</li>
                        <li>Use the system only for legitimate medical excuse purposes</li>
                        <li>Respect the confidentiality of medical information</li>
                        <li>Follow university policies and procedures</li>
                        <li>Report any system issues or security concerns immediately</li>
                    </ul>
                    <p class="text-muted">
                        <small>Last updated: <?php echo date('F j, Y'); ?></small>
                    </p>
                </div>
                <div class="modal-footer" style="border-top: 1px solid rgba(255,255,255,0.1);">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" style="background: rgba(255,255,255,0.1); border: 1px solid rgba(255,255,255,0.3);">Close</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show/hide year field based on role
        document.getElementById('role').addEventListener('change', function() {
            const yearGroup = document.getElementById('year_group');
            const yearSelect = document.getElementById('year');
            
            if (this.value === 'student') {
                yearGroup.style.display = 'block';
                yearSelect.required = true;
            } else {
                yearGroup.style.display = 'none';
                yearSelect.required = false;
                yearSelect.value = '';
            }
        });

        // Password confirmation validation
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (password !== confirmPassword) {
                this.setCustomValidity('Passwords do not match');
                this.style.borderColor = 'rgba(255, 82, 82, 0.8)';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = 'rgba(255, 255, 255, 0.3)';
            }
        });

        // Username validation
        document.getElementById('username').addEventListener('input', function() {
            const username = this.value;
            const pattern = /^[a-zA-Z0-9_]+$/;
            
            if (username && !pattern.test(username)) {
                this.setCustomValidity('Username can only contain letters, numbers, and underscores');
                this.style.borderColor = 'rgba(255, 82, 82, 0.8)';
            } else {
                this.setCustomValidity('');
                this.style.borderColor = 'rgba(255, 255, 255, 0.3)';
            }
        });

        // Form submission handling
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const terms = document.getElementById('terms').checked;
            const loadingSpinner = document.getElementById('loadingSpinner');
            const errorMessage = document.getElementById('errorMessage');
            const successMessage = document.getElementById('successMessage');

            // Hide messages
            errorMessage.style.display = 'none';
            successMessage.style.display = 'none';

            // Client-side validation - only prevent submission if there are errors
            if (password !== confirmPassword) {
                e.preventDefault();
                errorMessage.textContent = 'Passwords do not match.';
                errorMessage.style.display = 'block';
                return;
            }

            if (!terms) {
                e.preventDefault();
                errorMessage.textContent = 'You must agree to the terms and conditions.';
                errorMessage.style.display = 'block';
                return;
            }

            if (!this.checkValidity()) {
                e.preventDefault();
                errorMessage.textContent = 'Please fill in all required fields correctly.';
                errorMessage.style.display = 'block';
                this.classList.add('was-validated');
                return;
            }

            // If we reach here, validation passed - show loading and let form submit
            loadingSpinner.style.display = 'inline-block';
            successMessage.style.display = 'block';
            
            // Don't call e.preventDefault() here - let the form submit to process_auth.php
        });

        // Add interactive effects to input fields
        document.querySelectorAll('.input-field input, .input-field select').forEach(element => {
            element.addEventListener('focus', function() {
                this.parentElement.style.borderColor = 'rgba(255, 255, 255, 0.8)';
                this.parentElement.style.transform = 'scale(1.02)';
            });

            element.addEventListener('blur', function() {
                this.parentElement.style.borderColor = 'rgba(255, 255, 255, 0.3)';
                this.parentElement.style.transform = 'scale(1)';
            });
        });

        // Modal functionality (basic implementation)
        document.querySelectorAll('[data-bs-toggle="modal"]').forEach(trigger => {
            trigger.addEventListener('click', function(e) {
                e.preventDefault();
                const targetModal = document.querySelector(this.getAttribute('data-bs-target'));
                if (targetModal) {
                    targetModal.style.display = 'flex';
                    targetModal.style.position = 'fixed';
                    targetModal.style.top = '0';
                    targetModal.style.left = '0';
                    targetModal.style.width = '100%';
                    targetModal.style.height = '100%';
                    targetModal.style.backgroundColor = 'rgba(0,0,0,0.5)';
                    targetModal.style.alignItems = 'center';
                    targetModal.style.justifyContent = 'center';
                    targetModal.style.zIndex = '9999';
                }
            });
        });

        document.querySelectorAll('[data-bs-dismiss="modal"]').forEach(trigger => {
            trigger.addEventListener('click', function() {
                const modal = this.closest('.modal');
                if (modal) {
                    modal.style.display = 'none';
                }
            });
        });

        // Close modal when clicking outside
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('modal')) {
                e.target.style.display = 'none';
            }
        });
    </script>

   
</body>
</html>