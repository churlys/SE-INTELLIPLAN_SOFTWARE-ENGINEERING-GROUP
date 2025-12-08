<?php
// dashboard.php - protected dashboard
session_start();
require_once __DIR__ . '/lib/db.php';
require_once __DIR__ . '/lib/auth.php';
require_auth(); // redirects to login if not authenticated

$user = current_user();
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width,initial-scale=1" />
  <title>Dashboard — Student Productivity</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="assets/styles.css">
</head>
<body>
  <header class="site-header">
    <div class="container header-inner">
      <div class="logo"><img src="assets/logo.png" alt="Logo" /></div>
      <nav class="nav">
        <a class="nav-link" href="#">Dashboard</a>
        <a class="nav-link" href="#">Tasks</a>
        <a class="nav-link" href="#">Calendar</a>
      </nav>
      <div class="actions">
        <span class="welcome">Hi, <?php echo htmlspecialchars($user['name']); ?></span>
        <a class="btn btn-ghost" href="logout.php">Log out</a>
      </div>
    </div>
  </header>

  <main>
    <section class="dashboard-hero">
      <div class="container">
        <h1>Welcome back, <?php echo htmlspecialchars($user['name']); ?>.</h1>
        <p>Here’s an overview of your tasks and study plan — this area mirrors the Figma dashboard frame so you can plug in the live widgets later.</p>
      </div>
      <!-- background/illustration can be re-used from the landing hero -->
      <img class="hero-illustration small" src="assets/illustration.png" alt="illustration">
    </section>

    <section class="container dashboard-grid">
      <div class="card">
        <h3>Today</h3>
        <p>No tasks for today — add some!</p>
      </div>
      <div class="card">
        <h3>Upcoming</h3>
        <p>Exam review — 3 days</p>
      </div>
      <div class="card wide">
        <h3>Notes</h3>
        <p>Quick links and study notes</p>
      </div>
    </section>
  </main>

  <footer class="site-footer">
    <div class="container footer-inner">
      <div>&copy; <?php echo date('Y'); ?> Your Company</div>
      <div class="footer-links"><a href="#">Terms</a><a href="#">Privacy</a></div>
    </div>
  </footer>
</body>
</html>