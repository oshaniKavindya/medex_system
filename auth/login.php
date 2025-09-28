<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Medical Excuse Management System - Login</title>
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
        overflow: hidden;
    }

    body {
        display: flex;
        justify-content: center;
        align-items: center;
        background: url('../assets/images/back.jpg') no-repeat center center fixed;
        background-size: cover;
        background-position: center;
    }

    section {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 100vw;
        height: 100vh;
        background: url('../assets/images/back.jpg') no-repeat center center;
        background-size: cover;
        background-position: center;
    }

    .login-box {
        position: relative;
        width: 400px;
        height: auto;
        max-height: 90vh;
        background: transparent;
        border: 2px solid rgba(255, 255, 255, 0.2);
        border-radius: 20px;
        backdrop-filter: blur(35px);
        display: flex;
        justify-content: center;
        align-items: center;
        padding: 2.5rem;
        box-shadow: 
            0 25px 45px rgba(0, 0, 0, 0.1),
            0 0 0 1px rgba(255, 255, 255, 0.1) inset;
        transition: all 0.3s ease;
    }

    .login-box:hover {
        transform: translateY(-5px);
        box-shadow: 
            0 35px 55px rgba(0, 0, 0, 0.15),
            0 0 0 1px rgba(255, 255, 255, 0.15) inset;
    }

    .login-form {
        width: 100%;
    }

    h2 {
        font-size: 2em;
        color: white;
        text-align: center;
        margin-bottom: 2rem;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
        font-weight: 600;
    }

    .input-box {
        position: relative;
        width: 100%;
        margin: 30px 0;
        border-bottom: 2px solid rgba(255, 255, 255, 0.3);
        transition: border-color 0.3s ease;
    }

    .input-box:hover {
        border-bottom-color: rgba(255, 255, 255, 0.5);
    }

    .input-box label {
        position: absolute;
        top: 50%;
        left: 5px;
        transform: translateY(-50%);
        font-size: 1em;
        color: rgba(255, 255, 255, 0.8);
        pointer-events: none;
        transition: 0.5s ease;
        font-weight: 400;
    }

    .input-box input:focus ~ label,
    .input-box input:valid ~ label {
        top: -5px;
        font-size: 0.9em;
        color: white;
        font-weight: 500;
    }

    .input-box input {
        width: 100%;
        height: 50px;
        background: transparent;
        border: none;
        outline: none;
        font-size: 1em;
        color: white;
        padding: 0 35px 0 5px;
        font-weight: 400;
    }

    .input-box input::placeholder {
        color: transparent;
    }

    .input-box .icon {
        position: absolute;
        right: 8px;
        top: 50%;
        transform: translateY(-50%);
        color: rgba(255, 255, 255, 0.7);
        font-size: 1.2em;
        transition: color 0.3s ease;
    }

    .input-box:hover .icon {
        color: white;
    }

    .remember-forgot {
        margin: -15px 0 15px;
        font-size: 0.9em;
        color: rgba(255, 255, 255, 0.9);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
    }

    .remember-forgot label {
        display: flex;
        align-items: center;
        cursor: pointer;
        font-weight: 400;
    }

    .remember-forgot label input {
        margin-right: 8px;
        accent-color: white;
        width: auto;
        height: auto;
    }

    .remember-forgot a {
        color: rgba(255, 255, 255, 0.9);
        text-decoration: none;
        font-weight: 400;
        transition: all 0.3s ease;
    }

    .remember-forgot a:hover {
        text-decoration: underline;
        color: white;
        text-shadow: 0 0 5px rgba(255, 255, 255, 0.5);
    }

    button {
        width: 100%;
        height: 40px;
        background: rgba(255, 255, 255, 0.9);
        border: none;
        outline: none;
        border-radius: 40px;
        cursor: pointer;
        font-size: 1em;
        color: #333;
        font-weight: 600;
        margin: 1rem 0;
        transition: all 0.3s ease;
        backdrop-filter: blur(10px);
        position: relative;
        overflow: hidden;
    }

    button::before {
        content: '';
        position: absolute;
        top: 0;
        left: -100%;
        width: 100%;
        height: 100%;
        background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
        transition: left 0.5s ease;
    }

    button:hover {
        background: white;
        transform: translateY(-2px);
        box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
    }

    button:hover::before {
        left: 100%;
    }

    button:active {
        transform: translateY(0px);
    }

    .register-link {
        font-size: 0.9em;
        color: rgba(255, 255, 255, 0.9);
        text-align: center;
        margin: 25px 0 10px;
        font-weight: 400;
    }

    .register-link p a {
        color: white;
        text-decoration: none;
        font-weight: 600;
        transition: all 0.3s ease;
    }

    .register-link p a:hover {
        text-decoration: underline;
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
        padding: 0.8rem;
        border-radius: 8px;
        margin-bottom: 1rem;
        font-size: 0.9em;
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

    /* Icons using SVG */
    .user-icon {
        width: 20px;
        height: 20px;
    }

    .lock-icon {
        width: 20px;
        height: 20px;
    }

    /* Responsive design */
    @media (max-width: 480px) {
        .login-box {
            width: 90vw;
            height: auto;
            padding: 2rem 1.5rem;
            margin: 1rem;
        }

        h2 {
            font-size: 1.8em;
            margin-bottom: 1.5rem;
        }

        .input-box {
            margin: 25px 0;
        }

        .remember-forgot {
            flex-direction: column;
            align-items: flex-start;
            gap: 0.5rem;
        }

        button {
            height: 45px;
            font-size: 1.1em;
        }
    }

    @media (max-height: 600px) {
        .login-box {
            height: auto;
            max-height: 95vh;
            padding: 1.5rem;
        }

        h2 {
            font-size: 1.6em;
            margin-bottom: 1rem;
        }

        .input-box {
            margin: 20px 0;
        }
    }

    /* Subtle animations */
    .login-box {
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

    .input-box {
        animation: fadeInUp 0.6s ease-out forwards;
        opacity: 0;
    }

    .input-box:nth-child(2) { animation-delay: 0.1s; }
    .input-box:nth-child(3) { animation-delay: 0.2s; }
    .remember-forgot { animation: fadeInUp 0.6s ease-out 0.3s forwards; opacity: 0; }
    button { animation: fadeInUp 0.6s ease-out 0.4s forwards; opacity: 0; }
    .register-link { animation: fadeInUp 0.6s ease-out 0.5s forwards; opacity: 0; }

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
    <div class="login-box">
      <div class="login-form">
        <h2>Login</h2>

        <div class="message error-message" id="errorMessage">
          Invalid username or password. Please try again.
        </div>

        <div class="message success-message" id="successMessage">
          Login successful! Redirecting...
        </div>

        <form action="" method="POST" id="loginForm">
          <div class="input-box">
            <span class="icon">
              <svg class="user-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
              </svg>
            </span>
            <input type="text" required id="username" name="username">
            <label>Username</label>
          </div>

          <div class="input-box">
            <span class="icon">
              <svg class="lock-icon" viewBox="0 0 24 24" fill="currentColor">
                <path d="M18 8h-1V6c0-2.76-2.24-5-5-5S7 3.24 7 6v2H6c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V10c0-1.1-.9-2-2-2zM9 6c0-1.66 1.34-3 3-3s3 1.34 3 3v2H9V6z"/>
              </svg>
            </span>
            <input type="password" required id="password" name="password">
            <label>Password</label>
          </div>

          <div class="remember-forgot">
            <label><input type="checkbox" id="remember" name="remember"> Remember me</label>
            <!-- <a href="#" onclick="handleForgotPassword()">Forgot Password?</a> -->
          </div>

          <button type="submit">
            Login
            <span class="loading" id="loadingSpinner"></span>
          </button>

          <div class="register-link">
            <p>Don't have an account? <a href="#" onclick="handleRegister()">Register</a></p>
          </div>
        </form>
      </div>
    </div>
  </section>

  <script>
    // Form handling
    document.getElementById('loginForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const username = document.getElementById('username').value;
      const password = document.getElementById('password').value;
      const remember = document.getElementById('remember').checked;
      const loadingSpinner = document.getElementById('loadingSpinner');
      const errorMessage = document.getElementById('errorMessage');
      const successMessage = document.getElementById('successMessage');

      // Hide messages
      errorMessage.style.display = 'none';
      successMessage.style.display = 'none';

      // Show loading
      loadingSpinner.style.display = 'inline-block';

      // Simulate login process (replace with actual authentication)
      setTimeout(() => {
        loadingSpinner.style.display = 'none';

        // Basic validation (replace with actual authentication)
        if (username.length > 0 && password.length > 0) {
          // Success - submit the actual form
          successMessage.style.display = 'block';
          
          // Store remember me preference
          if (remember) {
            sessionStorage.setItem('rememberMe', 'true');
            sessionStorage.setItem('username', username);
          }

          // Submit the form after showing success message
          setTimeout(() => {
            // Create a traditional form submission
            const form = document.createElement('form');
            form.method = 'POST';
            form.action = 'process_auth.php';
            
            const usernameInput = document.createElement('input');
            usernameInput.type = 'hidden';
            usernameInput.name = 'username';
            usernameInput.value = username;
            
            const passwordInput = document.createElement('input');
            passwordInput.type = 'hidden';
            passwordInput.name = 'password';
            passwordInput.value = password;
            
            const actionInput = document.createElement('input');
            actionInput.type = 'hidden';
            actionInput.name = 'action';
            actionInput.value = 'login';
            
            form.appendChild(usernameInput);
            form.appendChild(passwordInput);
            form.appendChild(actionInput);
            document.body.appendChild(form);
            form.submit();
          }, 1000);
        } else {
          // Error
          errorMessage.style.display = 'block';
        }
      }, 800);
    });

    // Check for remembered user on page load
    window.addEventListener('load', function() {
      if (sessionStorage.getItem('rememberMe') === 'true') {
        document.getElementById('username').value = sessionStorage.getItem('username') || '';
        document.getElementById('remember').checked = true;
      }
    });

    // Handle forgot password
    function handleForgotPassword() {
      alert('Forgot password functionality is not implemented yet. Please contact the administrator.');
    }

    // Handle registration
    function handleRegister() {
      window.location.href = 'register.php';
    }

    // Add subtle interactive effects
    document.querySelectorAll('.input-box input').forEach(input => {
      input.addEventListener('focus', function() {
        this.parentElement.style.borderBottomColor = 'rgba(255, 255, 255, 0.8)';
      });

      input.addEventListener('blur', function() {
        this.parentElement.style.borderBottomColor = 'rgba(255, 255, 255, 0.3)';
      });
    });

    // Prevent right-click context menu (optional security feature)
    document.addEventListener('contextmenu', function(e) {
      // e.preventDefault(); // Uncomment to disable right-click
    });

    // Add keyboard shortcuts
    document.addEventListener('keydown', function(e) {
      if (e.key === 'Enter' && (e.target.type !== 'submit')) {
        document.getElementById('loginForm').dispatchEvent(new Event('submit'));
      }
    });
  </script>
</body>
</html>