# IntelliPlan — Landing + Auth PHP Prototype

A small PHP prototype of the IntelliPlan landing site and auth pages (signup, login, dashboard).  
This project reproduces the long-scrolling landing page you provided, plus working sign-up / login flows wired for MySQL (PDO). The design uses a full-bleed gradient background for auth pages and image/illustration placeholders exported from your Figma file.

This README explains:
- project structure
- required assets and filenames
- how to configure the database
- how to run locally
- where to replace Figma assets and copy
- a short Git push cheat-sheet

---

## Project structure

Top-level files you should have:
- `index.php` — Landing page (sections 1–5)
- `signup.php` — Sign-up page + server-side handler
- `login.php` — Login page + server-side handler
- `dashboard.php` — Protected dashboard (requires login)
- `logout.php` — Session destroy / redirect
- `styles.css` — Main site CSS (landing + components)
- `styles-login.css` or `auth-override.css` — Auth-specific rules (login/signup)
- `auth-fixes.css` (optional) — small auth fixes (scrolling / large logo)
- `schema.sql` — MySQL schema to create `users` table
- `README.md` — You are reading it
- `assets/` — images and icons referenced by pages
- `lib/`
  - `lib/db.php` — PDO MySQL connection (update credentials here)
  - `lib/auth.php` — auth helpers (register_user, get_user_by_email, login_user, csrf_token, require_auth)

Notes:
- The PHP files include comments pointing where to swap in your exported Figma images and exact copy.
- The prototype uses `password_hash()` / `password_verify()` and a simple CSRF token stored in session.

---

## Required software

- PHP 8.0+ (recommended)
- MySQL (or MariaDB) server
- Git (optional, for version control)
- A modern browser for testing

---

## Database setup

1. Open `lib/db.php` and set your DB constants:
   - `DB_HOST`, `DB_PORT`, `DB_NAME`, `DB_USER`, `DB_PASS`

2. Create the database & table:
   - From terminal:
     ```
     mysql -u root -p < schema.sql
     ```
   - Or run the SQL inside `schema.sql` from your preferred DB UI.

`schema.sql` (provided) creates a `student_prod` database and `users` table:
- `id` INT AUTO_INCREMENT PRIMARY KEY
- `name`, `email` (UNIQUE), `password_hash`, `created_at`

---

## Assets — filenames and where to put them

Place all exported assets in the `assets/` folder. The code references these example filenames — either use these names or update `src`/CSS URLs to match your filenames.

Suggested default files:
- `assets/logo.jpg` or `assets/logo.png` — small logo used in header
- `assets/logo-large.png` — larger logo used on auth pages
- `assets/gradient.png` — hero/background gradient used for landing (section backgrounds)
- `assets/gradient-auth.png` — full-bleed auth gradient (login/signup background)
- `assets/hero-illustration.png` — hero illustration placed behind hero text
- `assets/wow.png` or `assets/calendar-illustration.png` — feature visual
- `assets/section3-image.png` — section 3 image placeholder
- `assets/section4-media.png` — section 4 media placeholder
- `assets/section5-gradient.png` — gradient used for section 5 (if different)
- `assets/avatars/avatar-1.png`, `avatar-2.png`, `avatar-3.png`, `avatar-4.png` — testimonial avatars
- other icons/badges referenced (windows.png, pple.png, etc.)

There are inline comments in `index.php`, `signup.php`, and `login.php` showing the exact replace points.

---

## Quick local run (dev)

From your project root:

1. Ensure PHP CLI is available:
   ```
   php -v
   ```

2. Start the built-in web server:
   ```
   php -S localhost:8000
   ```

3. Open in the browser:
   - Landing: http://localhost:8000/index.php
   - Sign up: http://localhost:8000/signup.php
   - Log in: http://localhost:8000/login.php

Notes:
- If you run under Apache/Nginx, place files in your web root and configure virtual host as usual.
- Hard-refresh (Ctrl/Cmd+Shift+R) to avoid cached CSS or images after changes.

---

## Authentication & security notes

- Passwords are stored using `password_hash()` and verified with `password_verify()`.
- Sessions are used for login state. `login_user()` regenerates session id.
- A simple CSRF token is implemented via `csrf_token()` / `verify_csrf_token()`.
- For production:
  - Use HTTPS
  - Store DB credentials in environment variables or an .env file (not checked into Git)
  - Add rate-limiting and stronger session handling
  - Proper error logging (don’t echo PDO errors in production)

---

## Making the pages match your Figma exactly

- I added placeholders where text and images should be replaced with the exact Figma exports.
- To get pixel-perfect:
  - Export the exact images (SVG/PNG) from Figma and place in `assets/`.
  - Provide exact font files or use the exact Google font if available.
  - If you want me to extract exact font sizes / spacing from the public Figma link, say “extract from Figma” and I’ll pull the spec and update the CSS (you previously made the file viewable).

---

## Git / GitHub — push everything to a single repo

If you want to push this project to GitHub, here’s a minimal sequence (from project root):

1. Initialize repo (if not already):
   ```
   git init
   git add .
   git commit -m "Initial commit — IntelliPlan prototype"
   ```

2. Add remote & push (replace with your repo):
   - using HTTPS:
     ```
     git remote add origin https://github.com/<your-username>/<repo-name>.git
     git branch -M main
     git push -u origin main
     ```
   - using SSH:
     ```
     git remote add origin git@github.com:<your-username>/<repo-name>.git
     git branch -M main
     git push -u origin main
     ```

Remember to add a sensible `.gitignore` (exclude `.env`, vendor/, node_modules/, IDE files, database dumps with secrets).

---

## Troubleshooting

- Blank page or PHP errors: enable display errors in dev (or check logs). Example:
  ```php
  ini_set('display_errors', 1);
  ini_set('display_startup_errors', 1);
  error_reporting(E_ALL);
  ```
- Database connection failed: check `lib/db.php` credentials and ensure MySQL user has privileges.
- Images not loading: confirm files are inside `assets/` and referenced filenames match.

---

## What I can help with next

- Export exact images and update the CSS to be pixel-perfect against your Figma frames.
- Add MySQL seed/demo user, or switch to email verification + password reset flows.
- Merge auth CSS into your main stylesheet or produce a single combined stylesheet.
- Create a zip with all files ready to upload to your server.
- Walk you through the GitHub push step-by-step (I can provide exact commands once you give repo name and whether you want SSH or HTTPS).

---

Thanks — drop the `assets/` images and any exact Section 4 copy you want in place and I’ll fine-tune spacing, fonts, and radii for a pixel-perfect match.