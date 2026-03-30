<?php
session_start();

$correct_user = "admin";
$correct_pass = "1234";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $user = $_POST['username'];
    $pass = $_POST['password'];

    if ($user === $correct_user && $pass === $correct_pass) {
        $_SESSION['admin'] = $user;
        header("Location: admin_dashboard.php");
        exit();
    } else {
        $error = "Invalid login credentials!";
    }
}
?>

<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Login — ADBU Student Events</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">

<style>
:root {
  --bg:           #edeae3;
  --card:         #ffffff;
  --nav:          #ffffff;
  --border:       #e2e0db;
  --text-head:    #1a1a2e;
  --text-body:    #4a4a5a;
  --text-muted:   #8e8e9e;
  --accent:       #1a2744;
  --accent-h:     #243460;
  --gold:         #c9a84c;
  --input-bg:     #f5f4f0;
  --input-border: #dddbd5;
  --input-focus:  #1a2744;
  --error-bg:     #fff5f5;
  --error-border: #fca5a5;
  --error-text:   #dc2626;
  --shadow:       rgba(26,39,68,0.10);
}

[data-theme="dark"] {
  --bg:           #12111a;
  --card:         #1c1b27;
  --nav:          #1c1b27;
  --border:       #2a2938;
  --text-head:    #f0f0f5;
  --text-body:    #b0b0c0;
  --text-muted:   #6a6a7a;
  --accent:       #4a6fa5;
  --accent-h:     #5a80b8;
  --gold:         #c9a84c;
  --input-bg:     #252434;
  --input-border: #32314a;
  --input-focus:  #4a6fa5;
  --error-bg:     #2a1a1a;
  --error-border: #7f1d1d;
  --error-text:   #f87171;
  --shadow:       rgba(0,0,0,0.35);
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text-body);
  min-height: 100vh;
  transition: background 0.3s, color 0.3s;
}

/* ── NAV ── */
nav {
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 200;
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 28px;
  height: 64px;
  background: var(--nav);
  border-bottom: 1px solid var(--border);
  box-shadow: 0 1px 8px var(--shadow);
  transition: background 0.3s, border-color 0.3s;
}

.nav-left {
  display: flex;
  align-items: center;
  gap: 12px;
}

.nav-logo {
  height: 38px;
  width: auto;
  object-fit: contain;
}

.nav-brand { line-height: 1.25; }

.nav-brand-name {
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  font-size: 0.92rem;
  color: var(--text-head);
}

.nav-brand-sub {
  font-size: 0.62rem;
  font-weight: 500;
  letter-spacing: 0.10em;
  text-transform: uppercase;
  color: var(--text-muted);
}

.nav-right {
  display: flex;
  align-items: center;
  gap: 8px;
}

/* Toggle */
.toggle-wrap {
  display: flex;
  align-items: center;
  gap: 6px;
  margin-right: 4px;
}

.toggle-label { font-size: 0.75rem; color: var(--text-muted); }

.toggle-switch {
  position: relative;
  width: 46px; height: 24px;
  cursor: pointer;
  flex-shrink: 0;
}

.toggle-switch input { display: none; }

.toggle-track {
  position: absolute;
  inset: 0;
  border-radius: 100px;
  background: #d1d5db;
  border: 1px solid #c0c4cc;
  transition: background 0.3s;
}

.toggle-switch input:checked + .toggle-track { background: var(--accent); border-color: var(--accent); }

.toggle-thumb {
  position: absolute;
  top: 3px; left: 3px;
  width: 16px; height: 16px;
  border-radius: 50%;
  background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.2);
  transition: transform 0.3s;
  font-size: 9px;
  display: flex; align-items: center; justify-content: center;
}

.toggle-switch input:checked + .toggle-track + .toggle-thumb { transform: translateX(22px); }

/* Nav buttons */
.nav-btn {
  padding: 7px 16px;
  border-radius: 8px;
  font-size: 0.82rem;
  font-weight: 500;
  cursor: pointer;
  transition: all 0.2s;
  text-decoration: none;
  display: inline-flex;
  align-items: center;
  gap: 5px;
  font-family: 'Inter', sans-serif;
}

.nav-btn-ghost {
  background: transparent;
  border: 1px solid var(--border);
  color: var(--text-body);
}

.nav-btn-ghost:hover { background: var(--input-bg); border-color: var(--input-focus); color: var(--text-head); }

.nav-btn-solid {
  background: var(--accent);
  border: 1px solid var(--accent);
  color: #fff;
}

.nav-btn-solid:hover { background: var(--accent-h); }

/* ── PAGE ── */
.page {
  min-height: 100vh;
  padding-top: 64px;
  display: flex;
  flex-direction: column;
  align-items: center;
  justify-content: center;
  padding-bottom: 24px;
}

/* ── CARD ── */
.card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 16px;
  width: 100%;
  max-width: 460px;
  margin: 24px 16px 0;
  box-shadow: 0 4px 32px var(--shadow);
  overflow: hidden;
  animation: cardRise 0.55s cubic-bezier(.22,1,.36,1) both;
}

@keyframes cardRise {
  from { transform: translateY(20px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

.card-bar {
  height: 4px;
  background: linear-gradient(90deg, var(--accent) 0%, var(--gold) 100%);
}

.card-inner { padding: 34px 38px 36px; }

/* Card brand row */
.card-brand {
  display: flex;
  align-items: center;
  gap: 13px;
  padding-bottom: 22px;
  margin-bottom: 24px;
  border-bottom: 1px solid var(--border);
}

.card-logo { height: 50px; width: auto; object-fit: contain; flex-shrink: 0; }

.card-brand-name {
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  font-size: 0.97rem;
  color: var(--text-head);
  line-height: 1.3;
}

.card-brand-sub {
  font-size: 0.65rem;
  font-weight: 500;
  letter-spacing: 0.10em;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-top: 2px;
}

.card-title {
  font-family: 'Playfair Display', serif;
  font-weight: 700;
  font-size: 1.75rem;
  color: var(--text-head);
  letter-spacing: -0.02em;
  margin-bottom: 6px;
}

.card-subtitle {
  font-size: 0.84rem;
  color: var(--text-muted);
  margin-bottom: 26px;
}

/* ── FORM ── */
.field { margin-bottom: 17px; }

.field label {
  display: block;
  font-size: 0.78rem;
  font-weight: 600;
  color: var(--text-body);
  margin-bottom: 7px;
}

.field-wrap { position: relative; }

.field-icon {
  position: absolute;
  left: 13px; top: 50%;
  transform: translateY(-50%);
  font-size: 0.88rem;
  pointer-events: none;
  opacity: 0.5;
}

.field input {
  width: 100%;
  padding: 11px 40px 11px 40px;
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  border-radius: 10px;
  font-family: 'Inter', sans-serif;
  font-size: 0.9rem;
  color: var(--text-head);
  outline: none;
  transition: border-color 0.2s, box-shadow 0.2s, background 0.25s;
}

.field input::placeholder { color: var(--text-muted); font-weight: 300; }

.field input:focus {
  border-color: var(--input-focus);
  background: var(--card);
  box-shadow: 0 0 0 3px rgba(26,39,68,0.07);
}

[data-theme="dark"] .field input:focus { box-shadow: 0 0 0 3px rgba(74,111,165,0.15); }

.eye-btn {
  position: absolute;
  right: 11px; top: 50%;
  transform: translateY(-50%);
  background: none; border: none;
  cursor: pointer; font-size: 0.85rem;
  color: var(--text-muted); opacity: 0.55;
  padding: 4px;
  transition: opacity 0.2s;
}

.eye-btn:hover { opacity: 1; }

/* Error */
.error-msg {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--error-bg);
  border: 1px solid var(--error-border);
  border-radius: 8px;
  padding: 9px 13px;
  font-size: 0.81rem;
  color: var(--error-text);
  margin-bottom: 14px;
  animation: shake 0.35s ease;
}

@keyframes shake {
  0%,100% { transform: translateX(0); }
  25%      { transform: translateX(-5px); }
  75%      { transform: translateX(5px); }
}

/* Submit */
.btn-submit {
  width: 100%;
  padding: 13px;
  margin-top: 4px;
  background: var(--accent);
  color: #fff;
  border: none;
  border-radius: 10px;
  font-family: 'Inter', sans-serif;
  font-weight: 600;
  font-size: 0.92rem;
  cursor: pointer;
  letter-spacing: 0.02em;
  transition: background 0.2s, transform 0.15s, box-shadow 0.2s;
  box-shadow: 0 3px 14px var(--shadow);
}

.btn-submit:hover {
  background: var(--accent-h);
  transform: translateY(-1px);
  box-shadow: 0 6px 20px var(--shadow);
}

.btn-submit:active { transform: translateY(0) scale(0.99); }

/* Divider */
.divider {
  display: flex;
  align-items: center;
  gap: 12px;
  margin: 20px 0;
  color: var(--text-muted);
  font-size: 0.74rem;
  letter-spacing: 0.06em;
}

.divider::before, .divider::after {
  content: '';
  flex: 1;
  height: 1px;
  background: var(--border);
}

/* Links */
.card-link-row {
  text-align: center;
  font-size: 0.83rem;
  color: var(--text-muted);
  margin-bottom: 8px;
}

.card-link-row a {
  color: var(--accent);
  text-decoration: none;
  font-weight: 600;
  transition: color 0.2s;
}

.card-link-row a:hover { text-decoration: underline; }

.card-link-row.gold-link a { color: var(--gold); }

/* ── FOOTER ── */
footer {
  width: 100%;
  max-width: 460px;
  margin: 16px 16px 0;
  text-align: center;
  font-size: 0.74rem;
  color: var(--text-muted);
  line-height: 2;
  padding: 14px 0 8px;
  border-top: 1px solid var(--border);
}

footer .dev { font-size: 0.71rem; }
footer .dev span { color: var(--gold); font-weight: 600; }
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <div class="nav-left">
    <img src="adbu.jpeg" alt="ADBU Logo" class="nav-logo">
    <div class="nav-brand">
      <div class="nav-brand-name">Assam Don Bosco University</div>
      <div class="nav-brand-sub">Azara Campus · Student Events</div>
    </div>
  </div>

  <div class="nav-right">
    <div class="toggle-wrap">
      <label class="toggle-switch" title="Toggle dark mode">
        <input type="checkbox" id="themeCheck" onchange="toggleTheme()">
        <div class="toggle-track"></div>
        <div class="toggle-thumb">☀️</div>
      </label>
    </div>
    <a href="index.php"    class="nav-btn nav-btn-ghost">🏠 Home</a>
    <a href="login.php"    class="nav-btn nav-btn-ghost">Login</a>
    <a href="register.php" class="nav-btn nav-btn-ghost">Register</a>
    <a href="admin.php"    class="nav-btn nav-btn-solid">Admin ⚙</a>
  </div>
</nav>

<!-- PAGE -->
<div class="page">

  <!-- CARD -->
  <div class="card">
    <div class="card-bar"></div>
    <div class="card-inner">

      <!-- Card branding -->
      <div class="card-brand">
        <img src="adbu.jpeg" alt="ADBU" class="card-logo">
        <div>
          <div class="card-brand-name">Assam Don Bosco University</div>
          <div class="card-brand-sub">Azara Campus · Student Events</div>
        </div>
      </div>

      <h1 class="card-title">Welcome Back</h1>
      <p class="card-subtitle">Sign in to access the admin dashboard.</p>

      <form method="POST" autocomplete="off">

        <div class="field">
          <label for="username">Username / ID</label>
          <div class="field-wrap">
            <span class="field-icon">👤</span>
            <input type="text" id="username" name="username" placeholder="Enter admin username" required>
          </div>
        </div>

        <div class="field">
          <label for="password">Password</label>
          <div class="field-wrap">
            <span class="field-icon">🔒</span>
            <input type="password" id="password" name="password" placeholder="••••••••" required>
            <button type="button" class="eye-btn" onclick="togglePass()" title="Show / Hide">👁</button>
          </div>
        </div>

        <?php if (isset($error)) { ?>
          <div class="error-msg">
            <span>⚠️</span>
            <span><?php echo htmlspecialchars($error); ?></span>
          </div>
        <?php } ?>

        <button type="submit" class="btn-submit">Sign In →</button>

      </form>

      <div class="divider">OR</div>

      <div class="card-link-row">
        Don't have an account? <a href="register.php">Register here</a>
      </div>
      <div class="card-link-row gold-link">
        ⚙ Faculty / Staff? <a href="admin.php">Access Admin Portal</a>
      </div>

    </div><!-- /card-inner -->
  </div><!-- /card -->

  <!-- FOOTER -->
  <footer>
    <div>© 2026 ADBU Student Events System. All Rights Reserved.</div>
    <div class="dev">Developed by <span>Krishna Das</span></div>
  </footer>

</div><!-- /page -->

<script>
function toggleTheme() {
  const isDark = document.getElementById('themeCheck').checked;
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
  document.querySelector('.toggle-thumb').textContent = isDark ? '🌙' : '☀️';
}

function togglePass() {
  const inp = document.getElementById('password');
  inp.type = inp.type === 'password' ? 'text' : 'password';
}
</script>

</body>
</html>