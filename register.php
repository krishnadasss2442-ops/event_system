<?php
session_start();
require 'config/db.php';

$error = "";
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $name  = trim($_POST['name']);
  $email = trim($_POST['email']);
  $pass  = $_POST['password'];
  $confirm = $_POST['confirm_password'];

  // FIX: server-side confirm password validation (was missing)
  if ($pass !== $confirm) {
    $error = "Passwords do not match.";
  } elseif (strlen($pass) < 6) {
    $error = "Password must be at least 6 characters.";
  } else {
    $hashed = password_hash($pass, PASSWORD_BCRYPT);
    $stmt = $conn->prepare("INSERT INTO users (name, email, password) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $name, $email, $hashed);

    try {
      $stmt->execute();
      header("Location: login.php");
      exit;
    } catch (mysqli_sql_exception $e) {
      if ($e->getCode() == 1062) {
        $error = "Email already registered.";
      } else {
        $error = "A database error occurred. Please try again later.";
      }
    }
  }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register – ADBU Azara Student Events</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --bg: #f4f1eb; --bg2: #ede9e0; --surface: #ffffff;
      --border: rgba(0,0,0,0.08); --text: #1a1612; --text2: #5a5248; --text3: #8a8278;
      --accent: #1a3a5c; --accent2: #c8a84b; --accent3: #2e6da4;
      --card-shadow: 0 8px 48px rgba(0,0,0,0.1);
      --nav-bg: rgba(255,255,255,0.92); --nav-border: rgba(0,0,0,0.07);
      --btn-primary-bg: #1a3a5c; --btn-primary-text: #fff;
      --btn-secondary-border: #1a3a5c; --btn-secondary-text: #1a3a5c;
      --badge-bg: #e8f0f9; --badge-text: #1a3a5c;
      --footer-bg: #12243a; --footer-text: #a0b4c8; --footer-text2: #cdd8e3;
      --toggle-track: #d0cac0; --toggle-thumb: #fff;
      --input-bg: #f4f1eb; --divider: rgba(0,0,0,0.07);
    }
    [data-theme="dark"] {
      --bg: #0d1520; --bg2: #111e2e; --surface: #162232;
      --border: rgba(255,255,255,0.07); --text: #eae6df; --text2: #9ab0c4; --text3: #607a90;
      --accent: #4a9fd4; --accent2: #d4aa50; --accent3: #5ab3e8;
      --card-shadow: 0 8px 48px rgba(0,0,0,0.45);
      --nav-bg: rgba(13,21,32,0.95); --nav-border: rgba(255,255,255,0.06);
      --btn-primary-bg: #4a9fd4; --btn-primary-text: #0d1520;
      --btn-secondary-border: #4a9fd4; --btn-secondary-text: #4a9fd4;
      --badge-bg: #1a3048; --badge-text: #5ab3e8;
      --footer-bg: #080f18; --footer-text: #4a6a82; --footer-text2: #7a9ab2;
      --toggle-track: #2a3a4e; --toggle-thumb: #4a9fd4;
      --input-bg: #111e2e; --divider: rgba(255,255,255,0.06);
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); transition: background 0.4s, color 0.4s; min-height: 100vh; display: flex; flex-direction: column; }

    nav { position: fixed; top: 0; left: 0; right: 0; z-index: 1000; background: var(--nav-bg); backdrop-filter: blur(16px); border-bottom: 1px solid var(--nav-border); height: 68px; display: flex; align-items: center; padding: 0 2rem; transition: background 0.4s, border-color 0.4s; }
    .nav-inner { max-width: 1200px; width: 100%; margin: 0 auto; display: flex; align-items: center; justify-content: space-between; gap: 1rem; }
    .nav-logo { display: flex; align-items: center; gap: 0.75rem; text-decoration: none; }
    .nav-logo img { height: 44px; width: auto; object-fit: contain; }
    .nav-logo-text { display: flex; flex-direction: column; line-height: 1.15; }
    .nav-logo-text .uni-name { font-family: 'Cormorant Garamond', serif; font-size: 1rem; font-weight: 700; color: var(--accent); letter-spacing: 0.01em; transition: color 0.4s; }
    .nav-logo-text .campus-tag { font-size: 0.66rem; font-weight: 500; color: var(--text3); letter-spacing: 0.08em; text-transform: uppercase; }
    .nav-actions { display: flex; align-items: center; gap: 0.6rem; }

    .theme-toggle { position: relative; width: 46px; height: 24px; cursor: pointer; flex-shrink: 0; }
    .theme-toggle input { display: none; }
    .theme-track { position: absolute; inset: 0; background: var(--toggle-track); border-radius: 99px; transition: background 0.3s; display: flex; align-items: center; justify-content: space-between; padding: 0 5px; font-size: 12px; }
    .theme-thumb { position: absolute; top: 3px; left: 3px; width: 18px; height: 18px; background: var(--toggle-thumb); border-radius: 50%; transition: transform 0.3s, background 0.3s; box-shadow: 0 1px 4px rgba(0,0,0,0.2); }
    [data-theme="dark"] .theme-thumb { transform: translateX(22px); }

    .btn { padding: 0.45rem 1.1rem; border-radius: 6px; font-family: 'DM Sans', sans-serif; font-size: 0.82rem; font-weight: 500; cursor: pointer; border: 1.5px solid transparent; transition: all 0.2s; white-space: nowrap; letter-spacing: 0.02em; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; }
    .btn-primary { background: var(--btn-primary-bg); color: var(--btn-primary-text); border-color: var(--btn-primary-bg); }
    .btn-primary:hover { opacity: 0.88; transform: translateY(-1px); }
    .btn-outline { background: transparent; color: var(--btn-secondary-text); border-color: var(--btn-secondary-border); }
    .btn-outline:hover { background: var(--accent); color: #fff; border-color: var(--accent); transform: translateY(-1px); }
    .btn-ghost { background: var(--badge-bg); color: var(--badge-text); border-color: transparent; }
    .btn-ghost:hover { opacity: 0.8; transform: translateY(-1px); }

    main { flex: 1; display: flex; align-items: center; justify-content: center; padding: 100px 1.5rem 3rem; position: relative; overflow: hidden; }
    main::before { content: ''; position: absolute; inset: 0; background-image: radial-gradient(circle at 20% 30%, rgba(26,58,92,0.06) 0%, transparent 55%), radial-gradient(circle at 80% 70%, rgba(200,168,75,0.07) 0%, transparent 50%); pointer-events: none; transition: opacity 0.4s; }
    [data-theme="dark"] main::before { background-image: radial-gradient(circle at 20% 30%, rgba(74,159,212,0.08) 0%, transparent 55%), radial-gradient(circle at 80% 70%, rgba(212,170,80,0.07) 0%, transparent 50%); }

    .login-wrapper { width: 100%; max-width: 440px; animation: fadeUp 0.7s ease both; }
    @keyframes fadeUp { from { opacity: 0; transform: translateY(28px); } to { opacity: 1; transform: translateY(0); } }
    .login-card { background: var(--surface); border: 1px solid var(--border); border-radius: 20px; padding: 2.8rem 2.4rem 2.4rem; box-shadow: var(--card-shadow); transition: background 0.4s, border-color 0.4s; }
    .login-card::before { content: ''; display: block; height: 3px; background: linear-gradient(90deg, var(--accent), var(--accent3), var(--accent)); border-radius: 99px; margin-bottom: 2rem; }

    .card-logo { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1.2rem; }
    .card-logo img { height: 40px; width: auto; object-fit: contain; }
    .card-logo-text { display: flex; flex-direction: column; line-height: 1.2; }
    .card-logo-text .uni { font-family: 'Cormorant Garamond', serif; font-size: 0.95rem; font-weight: 700; color: var(--accent); }
    .card-logo-text .campus { font-size: 0.62rem; color: var(--text3); letter-spacing: 0.07em; text-transform: uppercase; }
    .card-divider { height: 1px; background: var(--border); margin: 0 0 1.5rem; }
    .card-heading { font-family: 'Cormorant Garamond', serif; font-size: 1.7rem; font-weight: 700; color: var(--text); margin-bottom: 0.3rem; }
    .card-sub { font-size: 0.82rem; color: var(--text2); margin-bottom: 1.8rem; line-height: 1.5; }

    .error-msg { background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c; font-size: 0.82rem; padding: 0.65rem 0.9rem; border-radius: 8px; margin-bottom: 1.2rem; }
    [data-theme="dark"] .error-msg { background: #2d1a1a; border-color: #7f1d1d; color: #fca5a5; }

    .form-group { margin-bottom: 1.1rem; }
    .form-label { display: block; font-size: 0.78rem; font-weight: 600; color: var(--text2); margin-bottom: 0.4rem; letter-spacing: 0.03em; }
    .input-wrap { position: relative; }
    .input-icon { position: absolute; left: 0.8rem; top: 50%; transform: translateY(-50%); font-size: 0.9rem; pointer-events: none; }
    .form-input { width: 100%; padding: 0.7rem 0.9rem 0.7rem 2.5rem; background: var(--input-bg); border: 1.5px solid var(--border); border-radius: 9px; font-family: 'DM Sans', sans-serif; font-size: 0.88rem; color: var(--text); outline: none; transition: border-color 0.25s, background 0.4s, color 0.4s, box-shadow 0.25s; }
    .form-input:focus { border-color: var(--accent); box-shadow: 0 0 0 3px rgba(26,58,92,0.1); }
    [data-theme="dark"] .form-input:focus { box-shadow: 0 0 0 3px rgba(74,159,212,0.15); }
    .form-input::placeholder { color: var(--text3); }
    .pw-toggle { position: absolute; right: 0.85rem; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; font-size: 1rem; color: var(--text3); padding: 0; line-height: 1; transition: color 0.2s; }
    .pw-toggle:hover { color: var(--accent); }

    .submit-btn { width: 100%; padding: 0.82rem 1rem; font-size: 0.92rem; border-radius: 9px; font-weight: 600; background: var(--btn-primary-bg); color: var(--btn-primary-text); border: none; cursor: pointer; font-family: 'DM Sans', sans-serif; transition: all 0.25s; letter-spacing: 0.03em; position: relative; overflow: hidden; }
    .submit-btn::after { content: ''; position: absolute; inset: 0; background: linear-gradient(135deg, transparent 0%, rgba(255,255,255,0.1) 100%); pointer-events: none; }
    .submit-btn:hover { opacity: 0.9; transform: translateY(-1px); box-shadow: 0 6px 20px rgba(26,58,92,0.25); }

    .or-divider { display: flex; align-items: center; gap: 0.75rem; margin: 1.4rem 0; font-size: 0.72rem; color: var(--text3); text-transform: uppercase; letter-spacing: 0.1em; font-weight: 500; }
    .or-divider::before, .or-divider::after { content: ''; flex: 1; height: 1px; background: var(--divider); }

    .switch-text { text-align: center; font-size: 0.82rem; color: var(--text2); margin-top: 1.2rem; }
    .switch-text a { color: var(--accent); text-decoration: none; font-weight: 600; }
    .switch-text a:hover { opacity: 0.75; text-decoration: underline; }

    footer { background: var(--footer-bg); color: var(--footer-text); transition: background 0.4s, color 0.4s; }
    .footer-bottom { max-width: 1200px; margin: 0 auto; padding: 1.2rem 2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .footer-copy { font-size: 0.76rem; }
    .footer-copy strong { color: var(--accent2); }
    .footer-dev { font-size: 0.72rem; letter-spacing: 0.04em; }
    .footer-dev span { color: var(--footer-text2); font-weight: 600; }

    @media(max-width: 480px) { nav { padding: 0 1rem; } .nav-logo-text .campus-tag { display: none; } .nav-logo-text .uni-name { font-size: 0.85rem; } .btn { padding: 0.4rem 0.8rem; font-size: 0.78rem; } .login-card { padding: 2rem 1.4rem 1.8rem; } main { padding: 90px 1rem 2rem; } }
  </style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">
      <!-- FIX: was /mnt/user-data/uploads/adbu.jpeg — corrected to relative path -->
      <img src="adbu.jpeg" alt="ADBU Logo" onerror="this.style.display='none'"/>
      <div class="nav-logo-text">
        <span class="uni-name">Assam Don Bosco University</span>
        <span class="campus-tag">Azara Campus · Student Events</span>
      </div>
    </a>
    <div class="nav-actions">
      <label class="theme-toggle" title="Toggle theme">
        <input type="checkbox" id="themeToggle" onchange="toggleTheme(this)"/>
        <div class="theme-track"><span>☀️</span><span>🌙</span></div>
        <div class="theme-thumb"></div>
      </label>
      <a href="index.php" class="btn btn-ghost">Home</a>
      <a href="login.php" class="btn btn-ghost">Login</a>
      <a href="register.php" class="btn btn-outline">Register</a>
      <a href="admin.php" class="btn btn-primary">Admin ⚙</a>
    </div>
  </div>
</nav>

<main>
  <div class="login-wrapper">
    <form action="register.php" method="POST">
      <div class="login-card">
        <div class="card-logo">
          <img src="adbu.jpeg" alt="ADBU Logo" onerror="this.style.display='none'"/>
          <div class="card-logo-text">
            <span class="uni">Assam Don Bosco University</span>
            <span class="campus">Azara Campus · Student Events</span>
          </div>
        </div>
        <div class="card-divider"></div>
        <div class="card-heading">Create Account</div>
        <div class="card-sub">Sign up to join and participate in student events.</div>

        <?php if ($error): ?>
          <div class="error-msg">⚠ <?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="form-group">
          <label class="form-label">Full Name</label>
          <div class="input-wrap">
            <span class="input-icon">👤</span>
            <input class="form-input" type="text" name="name" placeholder="e.g. John Doe" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Student Email / ID</label>
          <div class="input-wrap">
            <span class="input-icon">✉️</span>
            <input class="form-input" type="email" name="email" placeholder="student@adbu.in" required>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input class="form-input" type="password" id="passwordInput" name="password" required>
            <button class="pw-toggle" type="button" onclick="togglePw('passwordInput')">👁</button>
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Confirm Password</label>
          <div class="input-wrap">
            <span class="input-icon">🔒</span>
            <input class="form-input" type="password" id="confirmPasswordInput" name="confirm_password" required>
            <button class="pw-toggle" type="button" onclick="togglePw('confirmPasswordInput')">👁</button>
          </div>
        </div>

        <button class="submit-btn" type="submit">Sign Up →</button>
        <div class="or-divider">or</div>
        <div class="switch-text">Already have an account? <a href="login.php">Login here</a></div>
      </div>
    </form>
  </div>
</main>

<footer>
  <div class="footer-bottom">
    <div class="footer-copy">&copy; 2025 <strong>Assam Don Bosco University</strong>. All rights reserved.</div>
    <div class="footer-dev">Developed by <span>Krishna Das</span></div>
  </div>
</footer>

<script>
  function toggleTheme(el) { document.documentElement.setAttribute('data-theme', el.checked ? 'dark' : 'light'); }
  function togglePw(id) { const inp = document.getElementById(id); inp.type = inp.type === 'password' ? 'text' : 'password'; }
</script>
</body>
</html>