<?php
// index.php
// Simple PHP page that outputs the static landing page markup and styles.
// Replace SVG placeholders or <img> sources with your real images to match the original exactly.
?>
<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>IntelliPlan — Student Productivity</title>

  <!-- Google Fonts -->
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;600;700&family=Poppins:wght@500;700&display=swap" rel="stylesheet">

  <style>
    :root{
      --bg:#eef7ff;
      --card:#ffffff;
      --muted:#6b7280;
      --primary:#2b9bff;
      --accent:#8b5cf6;
      --soft-blue:#dff3ff;
      --max-width:980px;
      --radius:16px;
      --shadow: 0 8px 30px rgba(20,30,60,0.08);
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      font-family: "Inter", system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial;
      background:linear-gradient(180deg,#f6fbff 0%, #f1f7ff 30%);
      color:#0f172a;
      -webkit-font-smoothing:antialiased;
      -moz-osx-font-smoothing:grayscale;
    }
    .page{
      max-width:1100px;
      margin:36px auto;
      padding:0 20px 80px;
    }
    header{
      display:flex;
      align-items:center;
      justify-content:space-between;
      gap:20px;
      margin-bottom:28px;
    }
    .logo{
      display:flex;
      align-items:center;
      gap:12px;
      text-decoration:none;
      color:inherit;
    }
    .logo .mark{
      width:44px;
      height:44px;
      border-radius:10px;
      background:linear-gradient(135deg,var(--primary), #7cc3ff);
      display:flex;
      align-items:center;
      justify-content:center;
      box-shadow:0 6px 18px rgba(43,155,255,0.18);
      color:#fff;
      font-weight:700;
      font-family:"Poppins",sans-serif;
    }
    nav{display:flex;gap:18px;align-items:center}
    nav a{
      color:var(--muted);
      text-decoration:none;
      font-weight:600;
      font-size:14px;
      padding:8px 12px;
      border-radius:10px;
    }
    nav a.cta{
      background:linear-gradient(90deg,var(--primary),#5ac6ff);
      color:#fff;
      box-shadow:0 6px 18px rgba(43,155,255,0.18);
      font-family:"Poppins",sans-serif;
    }
    .hero{
      background:linear-gradient(180deg,#e8f6ff, #eef3ff);
      border-radius:20px;
      padding:46px;
      display:flex;
      gap:36px;
      align-items:center;
      box-shadow:var(--shadow);
    }
    .hero-left{
      flex:1;
      min-width:320px;
    }
    .hero h1{
      font-family:"Poppins",sans-serif;
      font-size:34px;
      margin:0 0 12px;
      line-height:1.05;
      color:#05264d;
    }
    .hero p.lead{
      margin:0 0 20px;
      color:var(--muted);
      font-size:16px;
    }
    .cta-row{display:flex; gap:12px; align-items:center}
    .btn{
      padding:12px 18px;
      border-radius:12px;
      border:0;
      cursor:pointer;
      font-weight:700;
      font-family:"Poppins",sans-serif;
      color:#fff;
      background:var(--primary);
      box-shadow:0 8px 18px rgba(43,155,255,0.18);
    }
    .btn.ghost{
      background:transparent;
      color:var(--primary);
      border:1px solid rgba(43,155,255,0.14);
      box-shadow:none;
      font-weight:600;
    }
    .hero-stats{
      display:flex;
      gap:18px;
      margin-top:18px;
      align-items:center;
    }
    .stat{
      display:flex;
      gap:10px;
      align-items:center;
      color:var(--muted);
      font-weight:600;
    }

    .hero-right{
      width:420px;
      display:flex;
      align-items:center;
      justify-content:center;
    }
    .illustration{
      width:100%;
      max-width:420px;
      border-radius:16px;
      background:linear-gradient(180deg,#ffffff, #f7fbff);
      padding:20px;
      box-shadow:0 10px 30px rgba(18,36,86,0.08);
      display:flex;
      justify-content:center;
      align-items:center;
    }

    /* Features card */
    .features{
      margin-top:30px;
      display:flex;
      gap:20px;
      align-items:flex-start;
      flex-wrap:wrap;
      justify-content:space-between;
    }
    .card{
      background:var(--card);
      border-radius:14px;
      padding:18px;
      box-shadow:var(--shadow);
      flex:1 1 300px;
      min-width:260px;
    }
    .card h3{margin:0 0 10px}
    .card p{margin:0;color:var(--muted);font-size:14px}

    /* Big white content area */
    .content-block{
      margin-top:34px;
      background:var(--card);
      border-radius:14px;
      padding:36px;
      box-shadow:var(--shadow);
    }
    .content-row{
      display:flex;
      gap:28px;
      align-items:center;
      flex-wrap:wrap;
    }
    .content-illustration{
      flex:0 0 420px;
      min-width:260px;
      background:linear-gradient(180deg,#f8fcff,#ffffff);
      border-radius:12px;
      padding:18px;
      box-shadow:0 8px 20px rgba(17,28,60,0.04);
    }
    .content-text{flex:1;min-width:260px}

    .centered{
      text-align:center;
    }

    /* Testimonials strip */
    .testimonials{
      margin-top:30px;
      display:flex;
      flex-direction:column;
      gap:18px;
      align-items:center;
    }
    .t-cards{
      display:flex;
      gap:18px;
      align-items:stretch;
      justify-content:center;
      flex-wrap:wrap;
    }
    .t-card{
      background:linear-gradient(180deg,#ffffff,#fbfdff);
      border-radius:12px;
      padding:18px;
      width:300px;
      box-shadow:0 8px 24px rgba(12,36,80,0.06);
    }
    .avatar{
      width:48px;height:48px;border-radius:50%; background:linear-gradient(90deg,#ffd49f,#ffb6c1);
      display:inline-block; vertical-align:middle;
    }
    .t-card h4{margin:8px 0 6px;font-size:16px}
    .t-card p{margin:0;color:var(--muted);font-size:14px}

    /* Two-column checklist */
    .two-col{
      display:grid;
      grid-template-columns:1fr 1fr;
      gap:28px;
    }
    .two-col ul{padding-left:18px; color:var(--muted)}
    .two-col li{margin-bottom:10px}
    .compare{
      margin-top:30px;
      background:linear-gradient(180deg,#ffffff,#f6fbff);
      padding:26px;border-radius:12px;box-shadow:var(--shadow);
    }

    /* CTA strip */
    .cta-strip{
      margin-top:28px;
      display:flex;
      gap:18px;
      align-items:center;
      justify-content:space-between;
      padding:24px;
      border-radius:12px;
      background:linear-gradient(90deg,#d6f1ff,#eef0ff);
      box-shadow:0 8px 24px rgba(10,30,80,0.04);
    }
    .footer-gradient{
      margin-top:40px;
      height:160px;
      border-radius:18px;
      background:linear-gradient(135deg,#e8e7ff,#e8f7ff);
    }

    /* Responsive */
    @media (max-width:900px){
      .hero{flex-direction:column; padding:28px}
      .hero-right{width:100%}
      .content-row{flex-direction:column}
      .two-col{grid-template-columns:1fr}
      .cta-strip{flex-direction:column;align-items:stretch}
    }
  </style>
</head>
<body>
  <main class="page" role="main">
    <header>
      <a href="#" class="logo" aria-label="IntelliPlan home">
        <div class="mark" style="font-size:18px">IP</div>
        <div>
          <div style="font-weight:700">IntelliPlan</div>
          <div style="font-size:12px;color:var(--muted);margin-top:2px">Built for Students</div>
        </div>
      </a>

      <nav aria-label="Main navigation">
        <a href="#">About</a>
        <a href="#">Pricing</a>
        <a href="#">Schools</a>
        <a class="cta" href="#">Get Started</a>
      </nav>
    </header>

    <!-- HERO -->
    <section class="hero" aria-label="Hero">
      <div class="hero-left">
        <h1>The Ultimate Student Productivity Tool</h1>
        <p class="lead">Plan, prioritize and study smarter with a productivity system built specifically for students — curriculum-aware timelines, focus sessions, and grade-friendly planning.</p>

        <div class="cta-row">
          <button class="btn">Get Started — It's Free</button>
          <button class="btn ghost">See a Demo</button>
        </div>

        <div class="hero-stats" style="margin-top:18px">
          <div class="stat">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="opacity:.9"><circle cx="12" cy="12" r="10" stroke="#2b9bff" stroke-width="1.6"/></svg>
            <div style="font-size:13px"><strong style="color:#05264d">24M+</strong> students</div>
          </div>
          <div class="stat">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" style="opacity:.9"><rect x="3" y="3" width="18" height="18" rx="4" stroke="#8b5cf6" stroke-width="1.6"/></svg>
            <div style="font-size:13px"><strong style="color:#05264d">For Schools</strong> & districts</div>
          </div>
        </div>
      </div>

      <div class="hero-right">
        <div class="illustration" role="img" aria-label="Illustration of student and calendar">
          <!-- Inline placeholder SVG similar to screenshot. Replace with exact image if available. -->
          <svg width="360" height="220" viewBox="0 0 360 220" xmlns="http://www.w3.org/2000/svg" role="img" aria-hidden="true">
            <defs>
              <linearGradient id="g1" x1="0" x2="1">
                <stop offset="0" stop-color="#dff6ff"/>
                <stop offset="1" stop-color="#e9e0ff"/>
              </linearGradient>
            </defs>
            <rect x="4" y="10" width="352" height="200" rx="12" fill="url(#g1)"/>
            <!-- calendar -->
            <rect x="28" y="22" width="200" height="132" rx="8" fill="#fff" stroke="#ddeefc" />
            <rect x="28" y="22" width="200" height="30" rx="8" fill="#eaf6ff" />
            <circle cx="60" cy="37" r="6" fill="#fff"/>
            <g transform="translate(40,68)" fill="#cdeeff" opacity="0.8">
              <rect x="0" y="0" width="40" height="20" rx="4"/>
              <rect x="46" y="0" width="40" height="20" rx="4"/>
              <rect x="92" y="0" width="40" height="20" rx="4"/>
            </g>
            <!-- person -->
            <g transform="translate(240,36)">
              <circle cx="48" cy="46" r="34" fill="#ffefef"/>
              <rect x="16" y="90" width="64" height="40" rx="12" fill="#fff"/>
              <rect x="10" y="126" width="76" height="8" rx="4" fill="#f2f6ff"/>
            </g>
            <!-- small UI -->
            <rect x="242" y="10" width="92" height="18" rx="8" fill="#fff" opacity="0.9"/>
          </svg>
        </div>
      </div>
    </section>

    <!-- Feature cards -->
    <div class="features" aria-label="Key features">
      <div class="card">
        <h3>The IntelliPlan's Productivity Methods</h3>
        <p>Design workflows that match study goals: timeboxing, spaced repetition, and exam-focused planning. Visualize weeks and milestones easily.</p>
      </div>
      <div class="card">
        <h3>Get Organised</h3>
        <p>Create subject plans, pin assignments, and get a clear weekly view to avoid last-minute stress.</p>
      </div>
      <div class="card">
        <h3>Study Insights</h3>
        <p>See focused metrics, track improvement and adapt study plans using personalized recommendations.</p>
      </div>
    </div>

    <!-- Big white explanation block with image -->
    <section class="content-block" aria-labelledby="methodsTitle">
      <div class="centered" style="margin-bottom:18px">
        <h2 id="methodsTitle" style="margin:0">The IntelliPlan's Productivity Methods</h2>
        <p style="color:var(--muted);margin-top:8px">A system built to help students plan work and study efficiently.</p>
      </div>

      <div class="content-row">
        <div class="content-illustration" aria-hidden="true">
          <!-- Replace with a screenshot / hero image -->
          <svg width="360" height="200" viewBox="0 0 360 200" xmlns="http://www.w3.org/2000/svg">
            <rect width="360" height="200" rx="12" fill="#ffffff"/>
            <rect x="12" y="18" width="336" height="28" rx="8" fill="#f4fbff"/>
            <rect x="20" y="62" width="120" height="24" rx="6" fill="#eef6ff"/>
            <rect x="148" y="62" width="180" height="24" rx="6" fill="#fff"/>
            <rect x="20" y="98" width="308" height="70" rx="8" fill="#f7faff"/>
          </svg>
        </div>

        <div class="content-text">
          <h3>Get organized. Stay on track.</h3>
          <p style="color:var(--muted)">Use subject timelines to split assignments into study sessions. Schedule tasks, attach resources and keep everything synced across devices.</p>

          <ul style="margin-top:18px;color:var(--muted)">
            <li><strong>Work productively:</strong> split tasks into clear study chunks</li>
            <li><strong>Focus work:</strong> built-in focus timers and priority labels</li>
            <li><strong>Track progress:</strong> visual completion and performance stats</li>
          </ul>
        </div>
      </div>
    </section>

    <!-- Testimonials -->
    <section class="testimonials" aria-label="Testimonials">
      <div style="font-weight:700;color:#05264d">Join a global community of 24 million students</div>

      <div class="t-cards" style="margin-top:12px">
        <div class="t-card">
          <div style="display:flex;gap:12px;align-items:center">
            <div class="avatar" aria-hidden="true"></div>
            <div>
              <div style="font-weight:700">Claire, Student</div>
              <div style="font-size:13px;color:var(--muted)">United Kingdom</div>
            </div>
          </div>
          <p style="margin-top:12px">"IntelliPlan helped me finally plan my revision. I improved my scores and feel less anxious."</p>
        </div>

        <div class="t-card">
          <div style="display:flex;gap:12px;align-items:center">
            <div class="avatar" style="background:linear-gradient(90deg,#a7f3d0,#60a5fa)"></div>
            <div>
              <div style="font-weight:700">Mr. Lopez, Teacher</div>
              <div style="font-size:13px;color:var(--muted)">USA</div>
            </div>
          </div>
          <p style="margin-top:12px">"We've rolled IntelliPlan out across our district — it's made curriculum planning simpler and more transparent."</p>
        </div>

        <div class="t-card">
          <div style="display:flex;gap:12px;align-items:center">
            <div class="avatar" style="background:linear-gradient(90deg,#ffd6a5,#ffb4c6)"></div>
            <div>
              <div style="font-weight:700">Amina, Parent</div>
              <div style="font-size:13px;color:var(--muted)">Canada</div>
            </div>
          </div>
          <p style="margin-top:12px">"I can see my child's assignments and progress — planning is collaborative now."</p>
        </div>
      </div>
    </section>

    <!-- Comparison / Goodbye to disorder -->
    <section class="compare" aria-label="Goodbye to disorder">
      <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:18px">
        <div style="flex:1;min-width:260px">
          <h3>Goodbye to disorder, Hello to stability.</h3>
          <p style="color:var(--muted)">Plan with clarity. Prioritize efficiently. Study with confidence.</p>
        </div>

        <div style="flex:1;min-width:260px">
          <div class="two-col">
            <div>
              <h4 style="margin-top:0">Why students choose IntelliPlan</h4>
              <ul>
                <li>Clear weekly and exam views</li>
                <li>Subject-specific timelines</li>
                <li>Focus-mode and reminders</li>
              </ul>
            </div>
            <div>
              <h4 style="margin-top:0">Schools & districts</h4>
              <ul>
                <li>Curriculum alignment tools</li>
                <li>Admin & roster sync</li>
                <li>Class planning & teacher dashboards</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </section>

    <!-- Product for schools -->
    <section class="content-block" style="margin-top:26px">
      <div style="display:flex;align-items:center;justify-content:space-between;gap:18px;flex-wrap:wrap">
        <div>
          <h3>IntelliPlan, Built for Schools.</h3>
          <p style="color:var(--muted)">Centralized planning, easy rollout, and analytics to support teachers and districts.</p>
        </div>
        <div style="min-width:320px">
          <!-- Small placeholder laptop screenshot -->
          <svg width="320" height="120" viewBox="0 0 320 120" xmlns="http://www.w3.org/2000/svg">
            <rect width="320" height="120" rx="10" fill="#fff"/>
            <rect x="12" y="16" width="296" height="88" rx="8" fill="#f6fbff"/>
          </svg>
        </div>
      </div>
    </section>

    <!-- CTA row -->
    <div class="cta-strip" role="region" aria-label="Call to action">
      <div>
        <h3 style="margin:0">Ready to get started?</h3>
        <p style="margin:8px 0 0;color:var(--muted)">For students, teachers and entire schools — sign up and get organised today.</p>
      </div>
      <div style="display:flex;gap:10px;align-items:center">
        <button class="btn">Create account</button>
        <button class="btn ghost">Contact Sales</button>
      </div>
    </div>

    <div class="footer-gradient" aria-hidden="true"></div>
  </main>
</body>
</html>