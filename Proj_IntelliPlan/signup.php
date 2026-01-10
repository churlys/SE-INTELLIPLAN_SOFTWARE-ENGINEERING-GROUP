<?php
if (file_exists(__DIR__ . '/lib/auth.php')) {
  require_once __DIR__ . '/lib/auth.php';
} else {
  function csrf_token() { $_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(24)); return $_SESSION['csrf_token']; }
  function verify_csrf_token($t) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t); }
  function register_user($name,$email,$password){ return null; }
  function login_user($id){}
}

$errors = [];
$name = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $password2 = $_POST['password2'] ?? '';

        if ($name === '') $errors[] = 'Please enter your name.';
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if (strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
        if ($password !== $password2) $errors[] = 'Passwords do not match.';

        if (empty($errors)) {
            $user_id = register_user($name, $email, $password);
            if ($user_id) {
                login_user($user_id);
                header('Location: dashboard.php');
                exit;
            } else {
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
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">

  <main class="auth-hero" role="main" aria-labelledby="signup-title">
    <div class="auth-inner container">
      
      <div class="auth-card auth-card-scroll" role="region" aria-labelledby="signup-title">
        <div class="auth-grid">
          
          <div class="auth-form-col">
            
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
                  <button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password', this)">
                    <svg class="pw-icon pw-icon-hidden" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                      <path d="M2 12C4.5 7 8.2 4.5 12 4.5S19.5 7 22 12c-2.5 5-6.2 7.5-10 7.5S4.5 17 2 12Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M4 20 20 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <svg class="pw-icon pw-icon-visible" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                      <path d="M2 12C4.5 7 8.2 4.5 12 4.5S19.5 7 22 12c-2.5 5-6.2 7.5-10 7.5S4.5 17 2 12Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2" />
                    </svg>
                  </button>
                </div>
              </label>

              <label class="field">
                <span class="label-text">Confirm Password</span>
                <div class="pw-field">
                  <input name="password2" id="password2" type="password" placeholder="Enter your password" required>
                  <button type="button" class="pw-toggle" aria-label="Show password" aria-pressed="false" onclick="togglePassword('password2', this)">
                    <svg class="pw-icon pw-icon-hidden" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                      <path d="M2 12C4.5 7 8.2 4.5 12 4.5S19.5 7 22 12c-2.5 5-6.2 7.5-10 7.5S4.5 17 2 12Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <path d="M4 20 20 4" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                    </svg>
                    <svg class="pw-icon pw-icon-visible" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                      <path d="M2 12C4.5 7 8.2 4.5 12 4.5S19.5 7 22 12c-2.5 5-6.2 7.5-10 7.5S4.5 17 2 12Z" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                      <circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="2" />
                    </svg>
                  </button>
                </div>
              </label>

              <div class="form-actions">
                <button class="btn-login" type="submit">Signup</button>
              </div>

              <div class="divider"><span>Or</span></div>

              <div class="social-row">
                <a class="social social-google" href="#" aria-label="Sign in with Google">
                  <span class="social-icon">G</span> Sign in with Google
                </a>
                <a class="social social-apple" href="#" aria-label="Sign in with Apple">
                  <span class="social-icon"></span> Sign in with Apple
                </a>
              </div>

              <p class="have-account">Have an account? <a href="login.php">Log In</a></p>
            </form>
          </div>

          <div class="auth-visual-col" aria-hidden="false">
            <div class="logo-wrap">
              <img src="assets/logo.jpg" alt="IntelliPlan logo" class="logo-xlarge">
            </div>
          </div>
        </div>
      </div>
    </div>
  </main>

  <script>
    function togglePassword(id, btn){
      const input = document.getElementById(id);
      if (!input) return;
      if (input.type === 'password') {
        input.type = 'text';
        btn.classList.add('is-visible');
        btn.setAttribute('aria-pressed','true');
        btn.setAttribute('aria-label','Hide password');
      } else {
        input.type = 'password';
        btn.classList.remove('is-visible');
        btn.setAttribute('aria-pressed','false');
        btn.setAttribute('aria-label','Show password');
      }
    }
  </script>
</body>
</html>