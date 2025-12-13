<?php
$images = [
    'second_full' => 'assets/getstarted.png',
    'logo' => 'assets/logo.jpg',
    'hero_bg' => 'assets/gradient.png',
    'hero_illustration' => 'assets/illustration.png',
    'feature_card' => 'assets/wow.png',
    'feature_card_alt' => 'assets/features-left.png',
    'testimonial1' => 'assets/manny.jpg',
    'testimonial2' => 'assets/aang.jpg',
    'testimonial3' => 'assets/taw.png',
    'testimonial4' => 'assets/cute.png',
    'laptop' => 'assets/computer.png',
];

if (file_exists(__DIR__ . '/lib/auth.php')) {
  require_once __DIR__ . '/lib/auth.php';
}
?>
    <!doctype html>
    <html lang="en">
    <head>
      <meta charset="utf-8" />
      <meta name="viewport" content="width=device-width,initial-scale=1" />
      <title>Student Productivity — Landing</title>
      <link rel="stylesheet" href="assets/styles.css">
    </head>
    <body>

      <header class="site-header">
        <div class="container header-inner">
          <div class="logo">
            <img src="assets/logo.jpg" alt="Product logo">
          </div>

          <nav class="nav">
            <a class="nav-link" href="#home">Home</a>
            <a class="nav-link" href="#features">About</a>
            <a class="nav-link" href="#section3">Study Tips</a>
          </nav>

          <div class="actions">
            <a class="btn btn-ghost" href="login.php">Log in</a>
            <a class="btn btn-primary" href="signup.php">Sign up</a>
          </div>
        </div>
      </header>

      <main>
        <section class="hero" id="home">
          <div class="hero-overlay"></div>
          <img class="hero-illustration" src="assets/illustration.png" alt="Illustration of a person tapping a calendar widget">

          <div class="container hero-inner">
            <div class="hero-left">
              <h1 class="hero-title">The Ultimate Student Productivity Tool</h1>
              <p class="hero-sub">Most of students experience academic stress, missing deadlines, and procrastination. The goal of IntelliPlan is to alter that.Designed for the real demands of student life, it’s the wiser approach to plan, focus, and succeed.</p>

              <div class="hero-ctas">
                <a class="btn btn-primary large" href="signup.php">Get Started</a>
              </div>

              <ul class="feature-list">
                <li><strong>Calendar & Planner: </strong><span>Plan assignments and exams</span></li>
                <li><strong>Notes & Docs: </strong><span>Store notes and resources</span></li>
                <li><strong>Progress Tracking: </strong><span>Visualize your improvement</span></li>
              </ul>

              <div class="platforms" aria-hidden="true">
                <img src="assets/windows.png" alt="Windows" class="platform-badge">
                <img src="assets/pple.png" alt="macOS" class="platform-badge">
              </div>
            </div>

            <div class="hero-right"></div>
          </div>
        </section>

        <section class="feature-visual container" id="features">
          <div class="visual-left">
            <h2>Plan & Book</h2>
            <p>Schedule study sessions, track milestones and connect tasks with deadlines — visualized in an easy calendar view.</p>
          </div>
          <div class="visual-right">
            <img src="assets/wow.png" alt="Calendar interface with avatars and appointment details">
          </div>
        </section>

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

        <section class="section-4 container" id="section4">
          <div class="s4-inner">
            <div class="s4-copy">
              <h2>Manage classes, deadlines and study sessions</h2>
              <p>Bring everything together — from your personal timetable to group tasks and revision plans.</p>

              <div class="s4-ctas">
                <a class="btn btn-outline" href="#">Learn more</a>
                <a class="btn btn-primary" href="signup.php">Get started</a>
              </div>
            </div>

            <div class="s4-media">
              <img src="assets/computer.png" alt="Feature illustration">
            </div>
          </div>
        </section>

        <section class="two-panel container" id="cta">
          <div class="panel panel-left">
            <h2>Ready to get started?</h2>
            <p>Take control of your school life with the ultimate student planner.</p>

            <div class="store-buttons">
              <a class="store-btn" href="#" aria-label="App Store">
                <span class="store-icon"></span>
                <span>App Store</span>
              </a>
              <a class="store-btn" href="#" aria-label="Play Store">
                <span class="store-icon">▸</span>
                <span>Play Store</span>
              </a>
            </div>
          </div>

          <div class="panel panel-right">
            <ul class="audience-list" aria-hidden="false">
              <li>Parents 🔋</li>
              <li>Teachers 💡</li>
              <li>Counsellors 🖊️</li>
              <li>Students 📝</li>
              <li>Homeschoolers ⚡</li>
              <li>Tutors 👨‍🏫</li>
              <li>Helpers 🧑‍💼</li>
            </ul>
          </div>
        </section>

        <section class="content container">
          <h3>How it works</h3>
          <p>Replace/extend these sections with the additional content/screenshots you provided — I'll match the spacing and typography exactly when you add the assets and any extra screenshots.</p>
        </section>

        <footer class="site-footer">
          <div class="container footer-inner">
            <div>&copy; <?php echo date('Y'); ?> Your Company</div>
            <div class="footer-links"><a href="#">Terms</a> <a href="#">Privacy</a></div>
          </div>
        </footer>

      <script>
        document.querySelectorAll('a[href^="#"]').forEach(a=>{
          a.addEventListener('click', (e)=>{
            const href = a.getAttribute('href');
            if (href.length > 1) {
              e.preventDefault();
              document.querySelector(href)?.scrollIntoView({behavior:'smooth', block:'start'});
            }
          });
        });
      </script>
    </body>
    </html>
        <div>&copy; <?php echo date('Y'); ?> Your Company</div>
        <div class="footer-links"><a href="#">Terms</a> <a href="#">Privacy</a></div>
      </div>
    </footer>

  </main>

  <script>
 
    document.querySelectorAll('a[href^="#"]').forEach(a=>{
      a.addEventListener('click', (e)=>{
        const href = a.getAttribute('href');
        if (href.length > 1) {
          e.preventDefault();
          document.querySelector(href)?.scrollIntoView({behavior:'smooth', block:'start'});
        }
      });
    });
  </script>
</body>
</html>