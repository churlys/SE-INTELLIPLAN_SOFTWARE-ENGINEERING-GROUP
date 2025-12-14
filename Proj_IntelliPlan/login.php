
<?php
session_start();

if (file_exists(__DIR__ . '/lib/auth.php')) {
  require_once __DIR__ . '/lib/auth.php';
} else {
  function verify_csrf_token($t) { return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $t); }
  function csrf_token() { $_SESSION['csrf_token'] = $_SESSION['csrf_token'] ?? bin2hex(random_bytes(24)); return $_SESSION['csrf_token']; }
  function get_user_by_email($email) { return null; }
  function login_user($id) { $_SESSION['user_id'] = $id; }
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $errors[] = 'Invalid request. Please refresh and try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';
        if ($password === '') $errors[] = 'Please enter your password.';

        if (empty($errors)) {
            $user = get_user_by_email($email);
            if ($user && isset($user['password_hash']) && password_verify($password, $user['password_hash'])) {
                if ($remember) {
                  ini_set('session.cookie_lifetime', 60*60*24*30);
                }
                login_user((int)$user['id']);
                header('Location: dashboard.php');
                exit;
            } else {
                $errors[] = 'Email or password incorrect.';
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
  <title>Log in — IntelliPlan</title>
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body class="auth-page">

  <main class="auth-hero" role="main" aria-labelledby="login-title">
    <div class="auth-inner container">

      <div class="auth-card auth-card-scroll" role="region" aria-labelledby="login-title">
        <div class="auth-grid">
          <div class="auth-form-col">
            <a class="back-link" href="index.php" aria-label="Back to home">←</a>

            <h1 id="login-title" class="auth-heading">Welcome back!</h1>
            <p class="auth-sub">Enter your Credentials to access your account</p>

            <?php if (!empty($errors)): ?>
              <div class="errors" role="alert" aria-live="assertive">
                <?php foreach ($errors as $e): ?>
                  <div class="error"><?php echo htmlspecialchars($e); ?></div>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>

            <form method="post" action="login.php" class="auth-form" novalidate>
              <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($csrf); ?>">

              <label class="field">
                <span class="label-text">Email address</span>
                <input name="email" type="email" value="<?php echo htmlspecialchars($email); ?>" placeholder="name@domain.com" required>
              </label>

              <label class="field">
                <span class="label-text">Password
                  <a class="forgot" href="#" title="Forgot password" style="float:right; font-size:13px;">forgot password</a>
                </span>
                <div class="pw-field">
                  <input name="password" id="login-password" type="password" placeholder="●●●●●●●●" required>
                  <button type="button" class="pw-toggle" aria-label="Show password" onclick="togglePassword('login-password', this)">👁️</button>
                </div>
              </label>

              <label class="checkbox">
                <input type="checkbox" name="remember" value="1" <?php echo isset($_POST['remember']) ? 'checked' : ''; ?>>
                <span>Remember for 30 days</span>
              </label>

              <div class="form-actions">
                <button class="btn btn-login" type="submit">Login</button>
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

              <p class="have-account">Don't have an account? <a href="signup.php">Sign Up</a></p>
            </form>
          </div>

          <aside class="auth-visual-col" aria-hidden="false">
            <div class="logo-wrap">
              <img src="assets/logo.jpg" alt="IntelliPlan logo" class="logo-large logo-xlarge">
            </div>
          </aside>
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
        btn.textContent = '👁️';
        btn.setAttribute('aria-pressed','true');
      } else {
        input.type = 'password';
        btn.textContent = '👁️‍🗨️';
        btn.setAttribute('aria-pressed','false');
      }
    }

    document.documentElement.style.overflowY = 'auto';
    document.body.style.overflowY = 'auto';
  </script>
</body>
</html>