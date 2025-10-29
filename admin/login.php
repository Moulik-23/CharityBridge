<?php
session_start();

$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $conn = new mysqli('localhost', 'root', '', 'charitybridge');
  if ($conn->connect_error) {
    $errorMessage = 'Unable to connect to the database.';
  } else {
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($role !== 'admin') {
      $errorMessage = 'Invalid role.';
    } else {
      $stmt = $conn->prepare('SELECT email, password FROM admins WHERE email=?');
      $stmt->bind_param('s', $email);
      $stmt->execute();
      $result = $stmt->get_result();

      if ($result && $result->num_rows === 1) {
        $row = $result->fetch_assoc();

        // NOTE: This compares plaintext; switch to password_hash/password_verify when available
        if ($row['password'] === $password) {
          $_SESSION['admin_logged_in'] = true;
          $_SESSION['admin_email'] = $row['email'];
          header('Location: ../admin/dashboard.php');
          exit;
        } else {
          $errorMessage = 'Invalid email or password.';
        }
      } else {
        $errorMessage = 'Invalid email or password.';
      }

      $stmt && $stmt->close();
    }

    $conn->close();
  }
}
?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Admin Login - CharityBridge</title>
    <link
      href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css"
      rel="stylesheet"
    />
    <link rel="stylesheet" href="../css/style.css" >
  </head>
  <body class="bg-light-color text-text-dark">
    <!-- Header -->
    <header>
      <div class="container mx-auto px-6 py-4 flex justify-between items-center">
        <a href="../index.html" class="text-3xl font-bold text-primary-color">CharityBridge</a>
        <nav class="hidden md:flex">
          <ul>
            <li><a href="../index.html">Home</a></li>
            <li><a href="../../login.html">Login</a></li>
          </ul>
        </nav>
      </div>
    </header>

    <!-- Login Form -->
    <section class="py-16">
      <div class="container">
        <div class="max-w-md mx-auto">
          <h2 class="text-center mb-12">Admin Login</h2>

          <?php if (!empty($errorMessage)): ?>
          <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-6" role="alert">
            <span class="block sm:inline"><?php echo htmlspecialchars($errorMessage); ?></span>
          </div>
          <?php endif; ?>

          <!-- Form connected to backend -->
          <form action="<?php echo htmlspecialchars($_SERVER['PHP_SELF']); ?>" method="POST">
            <!-- Role -->
            <div class="form-group">
              <label for="role">Role</label>
              <select id="role" name="role" required>
                <option value="admin">Admin</option>
              </select>
            </div>

            <!-- Email -->
            <div class="form-group">
              <label for="email">Email Address</label>
              <input
                type="email"
                id="email"  
                name="email"
                placeholder="admin@example.com"
                required
                value="<?php echo isset($email) ? htmlspecialchars($email) : ''; ?>"
              />
            </div>

            <!-- Password -->
            <div class="form-group">
              <label for="password">Password</label>
              <input
                type="password"
                id="password"
                name="password"
                placeholder="••••••••"
                required
              />
            </div>

            <!-- Remember Me -->
            <div class="form-group">
              <label class="inline-flex items-center">
                <input type="checkbox" id="remember" name="remember_me" class="mr-2" />
                <span>Remember me</span>
              </label>
            </div>

            <!-- Submit -->
            <div class="form-group">
              <button type="submit" class="btn btn-primary w-full">
                Login
              </button>
            </div>
          </form>
        </div>
      </div>
    </section>

    <!-- Footer -->
    <footer>
      <div class="container">
        <p>&copy; 2025 CharityBridge. All rights reserved.</p>
      </div>
    </footer>
    <!-- <script src="../js/admin.js"></script> -->
  </body>
  </html>





