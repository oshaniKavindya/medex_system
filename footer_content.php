<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Footer Content - Medical Excuse Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .content-container {
            max-width: 800px;
            margin: 50px auto;
            background: white;
            border-radius: 10px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .content-header {
            background: linear-gradient(135deg, #0056b3, #004494);
            color: white;
            padding: 30px;
            text-align: center;
        }

        .content-header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: bold;
        }

        .content-header .icon {
            font-size: 3rem;
            margin-bottom: 15px;
        }

        .content-body {
            padding: 40px;
        }

        .section {
            display: none;
        }

        .section.active {
            display: block;
        }

        .back-btn {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1rem;
            margin-bottom: 20px;
            text-decoration: none;
            display: inline-block;
        }

        .back-btn:hover {
            background-color: #545b62;
            color: white;
            text-decoration: none;
        }

        .info-card {
            background: #f8f9fa;
            border-left: 4px solid #0056b3;
            padding: 20px;
            margin: 15px 0;
            border-radius: 0 5px 5px 0;
        }

        .info-card h5 {
            color: #0056b3;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .faq-item {
            background: #f8f9fa;
            margin: 10px 0;
            border-radius: 8px;
            overflow: hidden;
        }

        .faq-question {
            background: #0056b3;
            color: white;
            padding: 15px 20px;
            cursor: pointer;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .faq-question:hover {
            background: #004494;
        }

        .faq-answer {
            padding: 15px 20px;
            display: none;
            background: white;
            border-left: 4px solid #0056b3;
        }

        .faq-answer.show {
            display: block;
        }

        .contact-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .policy-section {
            margin: 20px 0;
        }

        .policy-section h4 {
            color: #0056b3;
            border-bottom: 2px solid #0056b3;
            padding-bottom: 5px;
            margin-bottom: 15px;
        }

        .highlight {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            padding: 15px;
            border-radius: 5px;
            margin: 15px 0;
        }
    </style>
</head>
<body>
    <div class="content-container">
        <!-- Contact Info Section -->
        <div id="contact-section" class="section active">
            <div class="content-header">
                <div class="icon"><i class="fas fa-phone"></i></div>
                <h1>Contact Information</h1>
            </div>
            <div class="content-body">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>
                
                <div class="contact-grid">
                    <div class="info-card">
                        <h5><i class="fas fa-university"></i> Faculty Office</h5>
                        <p><strong>Faculty of Geomatics</strong><br>
                        Sabaragamuwa University of Sri Lanka<br>
                        P.O. Box 02, Belihuloya 70140<br>
                        Sri Lanka</p>
                    </div>
                    
                    <div class="info-card">
                        <h5><i class="fas fa-envelope"></i> Email Contacts</h5>
                        <p><strong>General:</strong> geomatics@sab.ac.lk<br>
                        <strong>Medical Officer:</strong> medical@sab.ac.lk<br>
                        <strong>IT Support:</strong> support@sab.ac.lk</p>
                    </div>
                    
                    <div class="info-card">
                        <h5><i class="fas fa-phone"></i> Phone Numbers</h5>
                        <p><strong>Faculty Office:</strong> +94 45 228 7001<br>
                        <strong>Medical Unit:</strong> +94 45 228 7015<br>
                        <strong>Emergency:</strong> +94 45 228 7000</p>
                    </div>
                    
                    <div class="info-card">
                        <h5><i class="fas fa-clock"></i> Office Hours</h5>
                        <p><strong>Monday - Friday:</strong> 8:00 AM - 4:30 PM<br>
                        <strong>Saturday:</strong> 8:00 AM - 12:30 PM<br>
                        <strong>Sunday:</strong> Closed</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- FAQ Section -->
        <div id="faq-section" class="section">
            <div class="content-header">
                <div class="icon"><i class="fas fa-question-circle"></i></div>
                <h1>Frequently Asked Questions</h1>
            </div>
            <div class="content-body">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>What documents do I need to submit?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        You need to submit three documents:
                        <ul>
                            <li><strong>Medical Certificate:</strong> From a government hospital or certified private medical center</li>
                            <li><strong>Excuse Letter:</strong> Personal letter explaining your absence</li>
                            <li><strong>Medical Application:</strong> Official form with course details and absence dates</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>How long does the approval process take?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        The typical process takes 3-5 working days:
                        <ul>
                            <li>Admin review: 1-2 days</li>
                            <li>HOD approval: 2-3 days</li>
                        </ul>
                        You'll receive system notifications and can track progress through your dashboard.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Can I submit documents after the 14-day deadline?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Medical certificates must be submitted within 14 days of issue for validity. Late submissions may be rejected unless there are exceptional circumstances. Contact the medical officer for special cases.
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>What if my application is rejected?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        If rejected, you will receive a system notification with the reason. You can:
                        <ul>
                            <li>Resubmit with corrected documents</li>
                            <li>Contact the medical officer for clarification</li>
                            <li>Appeal the decision through proper channels</li>
                        </ul>
                    </div>
                </div>

                <div class="faq-item">
                    <div class="faq-question" onclick="toggleFaq(this)">
                        <span>Can I track my application status?</span>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer">
                        Yes! Log into your account and go to "View My Requests" to see real-time status updates. You'll also receive system notifications for each status change in your dashboard.
                    </div>
                </div>
            </div>
        </div>

        <!-- Privacy Policy Section -->
        <div id="privacy-section" class="section">
            <div class="content-header">
                <div class="icon"><i class="fas fa-shield-alt"></i></div>
                <h1>Privacy Policy</h1>
            </div>
            <div class="content-body">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>

                <div class="highlight">
                    <strong>Last Updated:</strong> September 2025<br>
                    <strong>Effective Date:</strong> September 2025
                </div>

                <div class="policy-section">
                    <h4>Information We Collect</h4>
                    <p>We collect the following information to process your medical excuse applications:</p>
                    <ul>
                        <li>Personal details (name, student ID, contact information)</li>
                        <li>Academic information (course codes, year, department)</li>
                        <li>Medical documents and certificates</li>
                        <li>Application submission data and timestamps</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h4>How We Use Your Information</h4>
                    <ul>
                        <li>Processing and reviewing medical excuse applications</li>
                        <li>Communicating application status and decisions</li>
                        <li>Maintaining academic records and attendance</li>
                        <li>Improving system functionality and user experience</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h4>Data Security</h4>
                    <p>We implement strong security measures to protect your information:</p>
                    <ul>
                        <li>Encrypted data transmission and storage</li>
                        <li>Access controls and user authentication</li>
                        <li>Regular security audits and updates</li>
                        <li>Restricted access to authorized personnel only</li>
                    </ul>
                </div>

                <div class="policy-section">
                    <h4>Data Sharing</h4>
                    <p>Your information is shared only with:</p>
                    <ul>
                        <li>Medical officers for application review</li>
                        <li>HODs for approval decisions</li>
                        <li>Relevant lecturers for attendance updates</li>
                        <li>University administration as required</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h5>Contact for Privacy Concerns</h5>
                    <p>If you have questions about this privacy policy, contact us at:<br>
                    <strong>Email:</strong> privacy@sab.ac.lk<br>
                    <strong>Phone:</strong> +94 45 228 7001</p>
                </div>
            </div>
        </div>

        <!-- About Us Section -->
        <div id="about-section" class="section">
            <div class="content-header">
                <div class="icon"><i class="fas fa-info-circle"></i></div>
                <h1>About Us</h1>
            </div>
            <div class="content-body">
                <a href="index.php" class="back-btn">
                    <i class="fas fa-arrow-left"></i> Back to Home
                </a>

                <div class="info-card">
                    <h5>About Us</h5>
                    <p>Welcome to the <strong>Medical Excuse Management System</strong> of the Faculty of Geomatics. This system was designed to make it easier for students, staff, and faculty members to submit, track, and manage their medical excuses in a secure and organized way. Our goal is to reduce unnecessary paperwork and make the process more transparent and reliable.</p>
                </div>

                <div class="info-card">
                    <h5>Our Key Features</h5>
                    <ul>
                        <li>Make the process simple and fast</li>
                        <li>Keep your requests safe and organized</li>
                        <li>Provide regular updates on your request status</li>
                        <li>Minimize errors and delays caused by manual paperwork</li>
                        <li>Enable staff to review and approve requests more efficiently</li>
                    </ul>
                </div>

                <div class="info-card">
                    <h5>Why This System?</h5>
                    <p>Traditional paper-based methods often lead to delays, misplaced documents, and extra work for both students and faculty. By moving to a digital platform, we ensure that every request is stored safely, easily accessible, and processed on time. This not only saves time but also helps maintain a transparent and fair process.</p>
                </div>

                <div class="info-card">
                    <h5>Support & Assistance</h5>
                    <p>If you have any questions or run into problems while using the system, please check the <strong>FAQ</strong> sections. Our support team is also available to guide you through the process and resolve any technical issues.</p>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Get the section to display from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const section = urlParams.get('section');
        
        // Hide all sections and show the requested one
        document.querySelectorAll('.section').forEach(s => s.classList.remove('active'));
        
        if (section) {
            const targetSection = document.getElementById(section + '-section');
            if (targetSection) {
                targetSection.classList.add('active');
            }
        } else {
            // Default to contact section
            document.getElementById('contact-section').classList.add('active');
        }

        // FAQ toggle functionality
        function toggleFaq(element) {
            const answer = element.nextElementSibling;
            const icon = element.querySelector('i');
            
            if (answer.classList.contains('show')) {
                answer.classList.remove('show');
                icon.classList.remove('fa-chevron-up');
                icon.classList.add('fa-chevron-down');
            } else {
                // Close all other FAQs
                document.querySelectorAll('.faq-answer').forEach(a => a.classList.remove('show'));
                document.querySelectorAll('.faq-question i').forEach(i => {
                    i.classList.remove('fa-chevron-up');
                    i.classList.add('fa-chevron-down');
                });
                
                // Open this FAQ
                answer.classList.add('show');
                icon.classList.remove('fa-chevron-down');
                icon.classList.add('fa-chevron-up');
            }
        }
    </script>
</body>
</html>