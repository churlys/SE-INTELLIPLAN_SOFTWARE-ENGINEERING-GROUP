<?php
// signup.php
// Sign-up page styled to match the provided Figma screenshot.
// Place this file in your web root alongside styles.css and the assets/ folder.
// This version re-uses lib/auth.php for CSRF and registration helpers (register_user(), login_user(), csrf_token()).
// If you didn't add lib/auth.php yet, either add it or remove the auth includes and adapt the handler.

session_start();
if (file_exists(__DIR__ . '/lib/auth.php')) {
  require_once __DIR__ . '/lib/auth.php';
} else {
  // If lib/auth.php is not present, define stubs so the page still renders.
  function csrf_token() { $_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(24)); return $_SESSION['csrf_token']; }
  function verify_csrf_token($t) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t); }
  function register_user($name,$email,$password){ return null; }
  function login_user($id){}
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // simple CSRF check
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';
        $accept = isset($_POST['accept']);

        if ($name === '') $errors[] = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $password2) $errors[] = 'Passwords do not match.';
        if (!$accept) $errors[] = 'You must agree to the terms & privacy policy.';

        if (empty($errors)) {
            // register user (lib/auth.php should return inserted user id or null on failure)
            $user_id = register_user($name, $email, $password);
            if ($user_id) {
                // log the user in and redirect to dashboard
                login_user($user_id);
                header('Location: dashboard.php');
                exit;
            } else {
                // registration failed (email exists or DB error)
                $errors[] = 'Registration failed. An account with that email may already exist.';
            }
        }
    }
}

$csrf = csrf_token();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Sign up — IntelliPlan</title>

  <!-- Inter used as a close match; replace with exact font if supplied -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">

  <!-- Full-bleed gradient background image (replace assets/gradient-auth.png with your Figma-exported gradient) -->
  <main class="auth-hero" role="main" aria-labelledby="signup-title">
    <div class="auth-inner container">
      <!-- centered white card -->
      <div class="auth-card auth-card-scroll" role="region" aria-labelledby="signup-title">
        <div class="auth-grid">
          <!-- LEFT: Form column -->
          <div class="auth-form-col">
            <!-- small back arrow (optional) -->
            <a class="back-link" href="index.php" aria-label="Back to home">←</a>

            <h1 id="signup-title" class="auth-heading">Get Started Now!</h1>

            <?php if (!empty($errors)): ?>
              <div class="errors" role="alert" aria-live="assertive">
                <?php foreach ($errors as $e): ?>
                  <div class="error"><?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="post" action="signup.php" class="auth-form" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

              <label class="field">
                <span class="label-text">Name</span>
                <input name="name" type="text" value="<?php echo htmlspecialchars($name); ?>" placeholder="Enter your name" required>
              </label>

              <label class="field">
                <span class="label-text">Email address</span>
                <input name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="Enter your email" required>
              </label>

              <label class="field">
                <span class="label-text">Password</span>
                <div class="pw-field">
                  <input name="password" id="password" type="password" placeholder="Enter your password" required>
                  <button type="button" class="pw-toggle" aria-label="Show password" onclick="togglePassword('password', this)">👁️</button>
                </div>
              </label>

              <label class="field">
                <span class="label-text">Confirm Password</span>
                <div class="pw-field">
                  <input name="password2" id="password2" type="password" placeholder="Enter your password" required>
                  <button type="button" class="pw-toggle" aria-label="Show password" onclick="togglePassword('password2', this)">👁️</button>
                </div>
              </label>

              <label class="checkbox">
                <input type="checkbox" name="accept" value="1" <?php echo isset($_POST['accept']) ? 'checked' : ''; ?>>
                <span>I agree to the <a href="#" target="_blank" rel="noopener">terms & policy</a></span>
              </label>

              <div class="form-actions">
                <!-- primary signup CTA color approximates Figma (green). Replace color in CSS if needed. -->
                <button class="btn-login" type="submit">Signup</button>
              </div>

              <div class="divider"><span>Or</span></div>

              <div class="social-row">
                <a class="social social-google" href="#" aria-label="Sign in with Google">
                  <!-- replace with svg/icon per design if desired -->
                  <span class="social-icon">G</span> Sign in with Google
                </a>
                <a class="social social-apple" href="#" aria-label="Sign in with Apple">
                  <span class="social-icon"></span> Sign in with Apple
                </a>
              </div>

              <p class="have-account">Have an account? <a href="login.php">Log In</a></p>
            </form>
          </div>

          <!-- RIGHT: Logo / visual column -->
          <div class="auth-visual-col" aria-hidden="false">
            <div class="logo-wrap">
              <!-- REPLACE: assets/logo-large.png with the big logo exported from Figma (SVG/PNG).
                   The markup intentionally uses an <img> so you can swap in the exact art file. -->
              <img src="assets/logo.jpg" alt="IntelliPlan logo" class="logo-xlarge">
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    // Toggle password visibility (closed eye = password hidden)
    function togglePassword(id, btn){
      const input = document.getElementById(id);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '👁️'; // Open eye when password visible
        btn.setAttribute('aria-pressed','true');
      } else {
        input.type = 'password';
        btn.textContent = '👁️‍🗨️'; // Closed eye when password hidden
        btn.setAttribute('aria-pressed','false');
      }
    }
  </script>
</body>
</html>