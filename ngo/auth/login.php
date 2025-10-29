<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>NGO Login - CharityBridge</title>
    <link
      href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../../css/style.css" />
  </head>
  <body class="bg-light-color text-text-dark">
    <!-- Header -->
    <header>
      <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <a href="../../index.html" class="text-3xl font-bold text-primary-color">CharityBridge</a>
        <nav class="hidden md:flex">
          <ul>
            <li><a href="../../index.html">Home</a></li>
            <li><a href="login.php">Login</a></li>
            <li><a href="../../register.html" class="btn btn-primary">Register</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-primary-color via-secondary-color to-accent-color text-white py-16">
        <div class="absolute inset-0 bg-black opacity-20"></div>
        <div class="container relative z-10">
            <div class="text-center max-w-3xl mx-auto">
                <h1 class="text-4xl md:text-5xl font-extrabold mb-4">
                    Welcome Back,<br>
                    <span class="text-yellow-300">NGO Partner</span>
                </h1>
                <p class="text-lg md:text-xl opacity-95">
                    Access your organization dashboard and continue your mission
                </p>
            </div>
        </div>
    </section>

    <!-- Login Form -->
    <section class="py-20 bg-light-color">
      <div class="container">
        <div class="max-w-md mx-auto form-container">
          <div class="text-center mb-8">
            <div class="w-20 h-20 bg-gradient-to-br from-secondary-color to-green-600 rounded-full flex items-center justify-center mx-auto mb-4">
              <i class="fas fa-hands-helping text-white text-3xl"></i>
            </div>
            <h2 class="text-2xl font-bold text-primary-color">NGO Portal</h2>
            <p class="text-gray-600 mt-2">Manage your organization and impact</p>
          </div>

          <!-- ✅ Connects to backend -->
          <?php
          // Initialize error message
          $error_message = "";
          
          // Process form submission
          if ($_SERVER["REQUEST_METHOD"] == "POST") {
              // Include the existing backend logic
              ob_start(); // Start output buffering to capture any output
              include __DIR__ . '/../backend/ngo_login.php';
              $output = ob_get_clean(); // Get any output from the backend
              
              // Check if there was any output (error messages)
              if (!empty($output)) {
                  $error_message = $output;
              }
          }
          ?>
          
          <?php if (!empty($error_message)): ?>
          <div class="alert alert-error mb-6">
            <i class="fas fa-exclamation-triangle mr-2"></i>
            <?php echo htmlspecialchars($error_message); ?>
          </div>
          <?php endif; ?>
          
          <form action="login.php" method="POST">
            <!-- Email -->
            <div class="form-group">
              <label for="email">
                <i class="fas fa-envelope mr-2 text-primary-color"></i>
                Organization Email
              </label>
              <input
                type="email"
                id="email"
                name="email"
                placeholder="ngo@yourorganization.org"
                value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                required
              />
            </div>

            <!-- Password -->
            <div class="form-group">
              <label for="password">
                <i class="fas fa-lock mr-2 text-primary-color"></i>
                Password
              </label>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="Enter your password"
                required
              />
            </div>

            <div class="flex items-center justify-between mb-6">
              <label class="flex items-center">
                <input type="checkbox" id="remember_me" name="remember_me" class="mr-2">
                <span class="text-sm text-gray-600">Remember me</span>
              </label>
              <a href="#" class="text-sm text-primary-color hover:text-blue-800 font-medium">Forgot Password?</a>
            </div>

            <!-- Submit -->
            <div class="form-group">
              <button type="submit" class="btn btn-primary w-full text-lg py-4" style="color: white !important;">
                <i class="fas fa-sign-in-alt mr-2"></i>
                Access NGO Dashboard
              </button>
            </div>
          </form>
          
          <div class="text-center mt-8 pt-6 border-t border-gray-200">
            <p class="text-gray-600 mb-4">New NGO Partner?</p>
            <a href="ngo_register.html" class="btn bg-gray-100 text-gray-700 hover:bg-gray-200 w-full">
              <i class="fas fa-building mr-2"></i>
              Register Your Organization
            </a>
          </div>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer>
      <div class="container">
        <p>&copy; 2025 CharityBridge. All rights reservedddd.</p>
      </div>
    </footer>
    
    <script>
      // Scroll to error message if it exists
      document.addEventListener('DOMContentLoaded', function() {
        const errorAlert = document.querySelector('.alert-error');
        if (errorAlert) {
          errorAlert.scrollIntoView({ behavior: 'smooth', block: 'center' });
          // Add a subtle animation to draw attention
          errorAlert.style.animation = 'pulse 2s infinite';
        }

        // Remember me functionality
        const emailInput = document.getElementById('email');
        const rememberMeCheckbox = document.getElementById('remember_me');
        const loginForm = document.querySelector('form');

        // Load saved email if remember me was checked
        const savedEmail = localStorage.getItem('ngo_email');
        if (savedEmail) {
          emailInput.value = savedEmail;
          rememberMeCheckbox.checked = true;
        }

        // Save email on form submit if remember me is checked
        loginForm.addEventListener('submit', function() {
          if (rememberMeCheckbox.checked) {
            localStorage.setItem('ngo_email', emailInput.value.trim());
          } else {
            localStorage.removeItem('ngo_email');
          }
        });
      });
    </script>
    
    <style>
      @keyframes pulse {
        0% { transform: scale(1); }
        50% { transform: scale(1.02); }
        100% { transform: scale(1); }
      }
    </style>
  </body>
</html>
