<?php
// index.php
// Landing page template — update image filenames in $images or place images into assets/

$images = [
    'second_full' => 'assets/getstarted.png',
    'logo' => 'assets/logo.jpg',
    'hero_bg' => 'assets/gradient.png', // background that should cover the whole viewport
    'hero_illustration' => 'assets/illustration.png',
    'feature_card' => 'assets/wow.png',            // illustration used in features left card
    'feature_card_alt' => 'assets/features-left.png', // (optional) larger/cleaner art
    'testimonial1' => 'assets/manny.jpg',
    'testimonial2' => 'assets/aang.jpg',
    'testimonial3' => 'assets/keinth.jpg',
    'testimonial4' => 'assets/person4.jpg',
    'laptop' => 'assets/computer.png',
];

// Form handling remains the same
$success = false;
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cta_submit'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = trim($_POST['role'] ?? '');

    if ($name === '') $errors[] = 'Please enter your name.';
    if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';

    if (empty($errors)) {
        if (!is_dir(__DIR__ . '/data')) mkdir(__DIR__ . '/data', 0755, true);
        $file = __DIR__ . '/data/submissions.csv';
        $line = [
            date('c'),
            str_replace(["\r", "\n", '"'], ['','','\''], $name),
            str_replace(["\r", "\n", '"'], ['','','\''], $email),
            str_replace(["\r", "\n", '"'], ['','','\''], $role),
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        $fp = fopen($file, 'a');
        if ($fp) {
            fputcsv($fp, $line);
            fclose($fp);
            $success = true;
            $name = $email = $role = '';
        } else {
            $errors[] = 'Could not save your submission. Check directory permissions.';
        }
    }
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width,initial-scale=1" />
    <title>The Ultimate Student Productivity Tool</title>
    <link rel="stylesheet" href="assets/styles.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700;800&family=Nunito:wght@600;700&display=swap" rel="stylesheet">
</head>
<body>
<header class="site-header" aria-hidden="false">
    <div class="header-inner">
        <div class="brand">
            <img src="<?=htmlspecialchars($images['logo'])?>" alt="Logo" class="logo">
            <nav class="main-nav" aria-label="Main">
                <a href="#">Home</a>
                <a href="#">About</a>
                <a href="#">Study Tips</a>
            </nav>
        </div>
        <div class="header-actions">
            <button class="btn" aria-label="Sign up">Sign up</button>
            <button class="btn" aria-label="Log in">Log in</button>
        </div>
    </div>
</header>

<!-- HERO: full screen background -->
<section class="hero" style="background-image: linear-gradient(135deg, rgba(138,179,255,0.12), rgba(232,198,255,0.06)), url('<?=htmlspecialchars($images['hero_bg'])?>'); background-size: cover; background-position: center;">
    <div class="hero-inner">
        <div class="hero-copy">
            <h1>The Ultimate Student<br/>Productivity Tool</h1>
            <p class="lead">Most of students experience academic stress, missing deadlines, and procrastination. The goal of IntelliPlan is to alter that. Designed for the real demands of student life, it's the wiser approach to plan, focus, and succeed.</p>

            <div class="hero-ctas">
                <div class="cta-row">
                  <button class="btn large get-started" aria-label="Get Started">Get Started</button>
                </div>
                <div class="platforms" aria-hidden="true">
                    <img src="assets/windows.png" alt="Windows" class="platform-badge">
                    <img src="assets/pple.png" alt="macOS" class="platform-badge">
                   
                </div>
            </div>

            <div style="margin-top:28px;font-size:12px;color:rgba(2,6,23,0.6);">
                Backed by the media, loved by students.
                <div style="display:flex;gap:20px;margin-top:8px;align-items:center">
                  <img src="assets/lock.png" alt="ccpa" style="height:28px;opacity:0.8">
                  <img src="assets/gdpr.png" alt="gdpr" style="height:28px;opacity:0.8">
                  <img src="assets/aicpa.png" alt="aicpa" style="height:28px;opacity:0.8">
                </div>
            </div>
        </div>

        <div class="hero-art" aria-hidden="true">
            <div class="bg-responsive" style="background-image: url('<?=htmlspecialchars($images['hero_illustration'])?>'); height:360px; background-size:contain; background-position:center right;"></div>
        </div>
    </div>
</section>

<!-- FEATURES / METHODS panel (copy layout from screenshot) -->
<section class="features-panel">
  <div class="panel">
    <div class="panel-title">The IntelliPlan's<br/>Productivity Methods</div>

    <div class="feature-layout">
      <div class="feature-left">
        <div class="art-card">
          <div class="bg-responsive" style="background-image: url('<?=htmlspecialchars($images['feature_card'])?>');"></div>
        </div>
      </div>

      <div class="feature-right">
        <div class="timeline">
          <div class="timeline-item">
            <div class="dot" aria-hidden="true"></div>
            <div class="item-body">
              <h4>GET ORGANIZE</h4>
              <p>The MyStudyLife calendar, designed especially for academic life, helps you organize your time. With clever work management and intelligent reminders, nothing slips between the cracks.</p>
            </div>
          </div>

          <div class="timeline-item">
            <div class="dot" aria-hidden="true"></div>
            <div class="item-body">
              <h4>STAY AHEAD</h4>
              <p>With everything in one place – across web and even widgets – you can manage your time, workload, and priorities with clarity and confidence.</p>
            </div>
          </div>

          <div class="timeline-item">
            <div class="dot" aria-hidden="true"></div>
            <div class="item-body">
              <h4>WORK PRODUCTIVELY</h4>
              <p>Use simple, distraction-free tools to help you stay focused and fully engaged when it counts. This will let you study more effectively, lower your stress, and boost your performance.</p>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- TESTIMONIALS panel (centered headline + horizontal cards) -->
<section class="testimonials-panel">
  <div class="panel" style="background:transparent;box-shadow:none;padding:30px 40px;">
    <div style="text-align:center;margin-bottom:8px;"><span style="display:inline-block;background:#e9f7ff;color:#1a6fb3;padding:6px 12px;border-radius:9999px;font-weight:700;font-size:12px;">Real Students – Real Results</span></div>
    <div class="panel-title" style="margin-top:10px;">Join a global community of 24 million students</div>
    <div class="panel-sub">Join millions of students who have transformed the way they manage school. From beating procrastination to acing exams, see how IntelliPlan is helping learners stay organized, focused, and in control — every day.</div>

    <div class="testimonials-row" aria-label="Student testimonials">
      <div class="testimonial-card">
        <div class="avatar-wrap"><div class="avatar" style="background-image:url('<?=htmlspecialchars($images['testimonial1'])?>');"></div></div>
        <div class="author">Emily W</div>
        <div class="meta">Computer Science Student, UCLA</div>
        <div class="quote">"IntelliPlan's calendar has been a real help for me to manage my workload. It keeps all my deadlines in one spot, which has definitely cut down on my stress."</div>
      </div>

      <div class="testimonial-card">
        <div class="avatar-wrap"><div class="avatar" style="background-image:url('<?=htmlspecialchars($images['testimonial2'])?>');"></div></div>
        <div class="author">Luis G</div>
        <div class="meta">Grade 12 Student, Spain</div>
        <div class="quote">"I've always struggled with procrastination, but IntelliPlan has been a lifesaver. Its task list and tracking features help me break projects into manageable steps."</div>
      </div>

      <div class="testimonial-card">
        <div class="avatar-wrap"><div class="avatar" style="background-image:url('<?=htmlspecialchars($images['testimonial3'])?>');"></div></div>
        <div class="author">Ava R</div>
        <div class="meta">Teacher, High School</div>
        <div class="quote">"I can't believe how quickly students adapt to IntelliPlan. Assignments and schedules are clearer — less back-and-forth and more learning time."</div>
      </div>

      <div class="testimonial-card">
        <div class="avatar-wrap"><div class="avatar" style="background-image:url('<?=htmlspecialchars($images['testimonial4'])?>');"></div></div>
        <div class="author">Mason P</div>
        <div class="meta">Parent</div>
        <div class="quote">"Great for families — keeps everyone synced and reduces last-minute surprises."</div>
      </div>
    </div>
  </div>
</section>



<!-- COMPARISON panel (two-column problem vs solution lists) -->
<section class="compare-panel">
  <div class="panel">
    <div style="text-align:center">
      <div class="badge-pill">Everything in one place for School</div>
      <div class="panel-title">Goodbye to disorder, Hello to stability.</div>
      <div class="panel-sub">Quit juggling calendars, reminders, timers, and task apps. IntelliPlan puts everything you need to manage school in one organized, synced space made specifically for students.</div>
    </div>

    <div class="compare-lists" role="list">
      <div class="compare-col left" aria-label="The problem">
        <ul>
          <li>"My schedule is all over the place."</li>
          <li>"I forget deadlines and due dates constantly."</li>
          <li>"I'm using five different apps to stay organized."</li>
          <li>"I lose track when switching devices."</li>
          <li>"Studying feels overwhelming and chaotic."</li>
          <li>"I want it to feel more like me."</li>
        </ul>
      </div>

      <div class="compare-col right" aria-label="How IntelliPlan works">
        <ul>
          <li>Smart calendar for rotating, block, & custom schedules</li>
          <li>Smart reminders for tasks, exams, and study sessions</li>
          <li>Everything you need in one dashboard — no switching tabs</li>
          <li>Instant sync across your phone, tablet, and laptop</li>
          <li>Focus tools like Pomodoro timers and subtasks</li>
          <li>Personalization with custom colours, images, and views</li>
        </ul>
      </div>
    </div>
  </div>
</section>

<!-- rest of page (product-laptop replacement done above) -->
<section class="product-laptop container">
<section class="product-laptop" style="background-image: url('<?=htmlspecialchars($images['product_bg'] ?? 'assets/gradient.png')?>');">
    <div class="lp-copy">
      <h3>IntelliPlan, Built for Schools.</h3>
      <p>Designed for classrooms and districts: teacher dashboards, student accounts, family access, and privacy-first data controls that scale.</p>
      <ul>
        <li>Single sign-on (SAML, Google Workspace)</li>
        <li>Group rollout and rostering</li>
        <li>Read-only teacher reports</li>
      </ul>
    </div>

    <div class="lp-art" aria-hidden="true">
      <img src="<?=htmlspecialchars($images['laptop'])?>" alt="Laptop preview">
    </div>
  </div>


</section>
<!-- full-bleed whole image block (paste immediately after product-laptop closing </section>) -->
<section class="page-bottom-fullimg" aria-hidden="true">
  <img src="<?=htmlspecialchars($images['second_full'] ?? '/assets/getstarted.png')?>" alt="" class="page-bottom-img" />
</section>

<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <div>© <?=date('Y')?> IntelliPlan. All rights reserved.</div>
            <div class="footer-links">
                <a href="#">Privacy</a>
                <a href="#">Terms</a>
                <a href="#">Contact</a>
            </div>
        </div>
    </div>
</footer>
</body>
</html>