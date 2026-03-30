<?php
session_start();
require 'config/db.php';

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// ✅ GET event_id from POST (NEW)
if (isset($_POST['event_id'])) {
    $event_id = intval($_POST['event_id']);
} else {
    die("Invalid request");
}

// ❌ Prevent duplicate registration
$check = $conn->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
$check->bind_param("ii", $user_id, $event_id);
$check->execute();
$result = $check->get_result();

if ($result->num_rows > 0) {
    // already registered
    header("Location: dashboard.php");
    exit;
}
$check->close();

// ✅ Insert registration
$stmt = $conn->prepare("INSERT INTO registrations (user_id, event_id) VALUES (?, ?)");
$stmt->bind_param("ii", $user_id, $event_id);

if ($stmt->execute()) {
    header("Location: dashboard.php");
} else {
    echo "Error: " . $stmt->error;
}

$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Register — <?= htmlspecialchars($event['title']) ?> · ADBU</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
/* ── TOKENS (identical to dashboard) ── */
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
  --shadow:       rgba(26,39,68,0.10);
  --green:        #16a34a;
  --green-bg:     #f0fdf4;
  --green-border: #bbf7d0;
  --blue:         #1d4ed8;
  --blue-bg:      #eff6ff;
  --blue-border:  #bfdbfe;
  --amber:        #b45309;
  --amber-bg:     #fffbeb;
  --amber-border: #fde68a;
  --red:          #dc2626;
  --red-bg:       #fff5f5;
  --red-border:   #fca5a5;
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
  --shadow:       rgba(0,0,0,0.35);
  --green:        #4ade80;
  --green-bg:     #052e16;
  --green-border: #14532d;
  --blue:         #60a5fa;
  --blue-bg:      #1e3a5f;
  --blue-border:  #1e40af;
  --amber:        #fbbf24;
  --amber-bg:     #2d1f00;
  --amber-border: #78350f;
  --red:          #f87171;
  --red-bg:       #2a1a1a;
  --red-border:   #7f1d1d;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', sans-serif;
  background: var(--bg);
  color: var(--text-body);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
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

.nav-left { display: flex; align-items: center; gap: 12px; }
.nav-logo  { height: 38px; width: auto; object-fit: contain; }
.nav-brand { line-height: 1.25; }
.nav-brand-name {
  font-family: 'Playfair Display', serif;
  font-weight: 700; font-size: 0.92rem;
  color: var(--text-head);
}
.nav-brand-sub {
  font-size: 0.62rem; font-weight: 500;
  letter-spacing: 0.10em; text-transform: uppercase;
  color: var(--text-muted);
}
.nav-right { display: flex; align-items: center; gap: 10px; }

.user-badge {
  display: flex; align-items: center; gap: 8px;
  padding: 6px 14px;
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 100px;
  font-size: 0.80rem; color: var(--text-body);
}
.user-badge .dot {
  width: 8px; height: 8px;
  border-radius: 50%; background: var(--green);
  animation: blink 2s infinite;
}
@keyframes blink { 0%,100%{opacity:1} 50%{opacity:0.3} }

.toggle-switch { position: relative; width: 46px; height: 24px; cursor: pointer; }
.toggle-switch input { display: none; }
.toggle-track {
  position: absolute; inset: 0;
  border-radius: 100px; background: #d1d5db;
  border: 1px solid #c0c4cc; transition: background 0.3s;
}
.toggle-switch input:checked + .toggle-track { background: var(--accent); border-color: var(--accent); }
.toggle-thumb {
  position: absolute; top: 3px; left: 3px;
  width: 16px; height: 16px;
  border-radius: 50%; background: #fff;
  box-shadow: 0 1px 4px rgba(0,0,0,0.2);
  transition: transform 0.3s;
  font-size: 9px;
  display: flex; align-items: center; justify-content: center;
}
.toggle-switch input:checked + .toggle-track + .toggle-thumb { transform: translateX(22px); }

.btn-back {
  padding: 7px 16px;
  border-radius: 8px; font-size: 0.82rem; font-weight: 500;
  cursor: pointer; text-decoration: none;
  display: inline-flex; align-items: center; gap: 5px;
  font-family: 'Inter', sans-serif;
  background: var(--input-bg);
  border: 1px solid var(--border);
  color: var(--text-body);
  transition: all 0.2s;
}
.btn-back:hover { background: var(--border); }

.btn-logout {
  padding: 7px 16px; border-radius: 8px;
  font-size: 0.82rem; font-weight: 500;
  cursor: pointer; text-decoration: none;
  display: inline-flex; align-items: center; gap: 5px;
  font-family: 'Inter', sans-serif;
  background: var(--red-bg); border: 1px solid var(--red-border);
  color: var(--red); transition: all 0.2s;
}
.btn-logout:hover { background: var(--red); color: #fff; border-color: var(--red); }

/* ── LAYOUT ── */
.page { padding-top: 64px; flex: 1; display: flex; flex-direction: column; }
.main { flex: 1; padding: 40px 28px; max-width: 780px; margin: 0 auto; width: 100%; }

@keyframes riseUp {
  from { transform: translateY(16px); opacity: 0; }
  to   { transform: translateY(0);    opacity: 1; }
}

/* ── BREADCRUMB ── */
.breadcrumb {
  display: flex; align-items: center; gap: 6px;
  font-size: 0.78rem; color: var(--text-muted);
  margin-bottom: 20px;
  animation: riseUp 0.4s cubic-bezier(.22,1,.36,1) both;
}
.breadcrumb a { color: var(--text-muted); text-decoration: none; }
.breadcrumb a:hover { color: var(--accent); }
.breadcrumb .sep { color: var(--border); }
.breadcrumb .current { color: var(--text-body); }

/* ── EVENT PREVIEW CARD ── */
.event-preview {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 16px var(--shadow);
  margin-bottom: 22px;
  animation: riseUp 0.45s 0.05s cubic-bezier(.22,1,.36,1) both;
}

.event-preview-bar {
  height: 4px;
  background: linear-gradient(90deg, var(--accent) 0%, var(--gold) 100%);
}

.event-preview-body { padding: 26px 28px 20px; }

.event-preview-meta {
  display: flex; align-items: center; gap: 10px;
  flex-wrap: wrap;
  margin-bottom: 14px;
}

.chip {
  display: inline-flex; align-items: center; gap: 5px;
  font-size: 0.71rem; font-weight: 600;
  padding: 3px 11px; border-radius: 100px;
  letter-spacing: 0.03em;
}
.chip.date  { background: var(--blue-bg);  color: var(--blue);  border: 1px solid var(--blue-border); }
.chip.live  { background: var(--green-bg); color: var(--green); border: 1px solid var(--green-border); }
.chip.live .live-dot {
  width: 6px; height: 6px; border-radius: 50%;
  background: var(--green); animation: blink 1.5s infinite;
}

.event-preview-title {
  font-family: 'Playfair Display', serif;
  font-weight: 700; font-size: 1.45rem;
  color: var(--text-head); line-height: 1.3;
  margin-bottom: 10px;
}

.event-preview-desc {
  font-size: 0.87rem; color: var(--text-muted);
  line-height: 1.7; max-width: 580px;
}

.event-preview-divider {
  height: 1px; background: var(--border);
  margin: 18px 0 16px;
}

.event-info-row {
  display: flex; gap: 28px; flex-wrap: wrap;
}
.event-info-item { font-size: 0.80rem; color: var(--text-muted); }
.event-info-item strong { display: block; color: var(--text-head); font-weight: 600; font-size: 0.84rem; margin-bottom: 2px; }

/* ── REGISTER CARD ── */
.register-card {
  background: var(--card);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 16px var(--shadow);
  animation: riseUp 0.45s 0.10s cubic-bezier(.22,1,.36,1) both;
}

.register-card-header {
  padding: 22px 28px 18px;
  border-bottom: 1px solid var(--border);
  background: var(--input-bg);
}

.register-card-header h2 {
  font-family: 'Playfair Display', serif;
  font-weight: 700; font-size: 1.10rem;
  color: var(--text-head); margin-bottom: 3px;
}

.register-card-header p { font-size: 0.80rem; color: var(--text-muted); }

.register-card-body { padding: 26px 28px; }

/* ── FORM FIELDS ── */
.form-group { margin-bottom: 18px; }

.form-label {
  display: block; font-size: 0.78rem; font-weight: 600;
  color: var(--text-body); margin-bottom: 6px;
  letter-spacing: 0.02em;
}

.form-control {
  width: 100%; padding: 10px 14px;
  background: var(--input-bg);
  border: 1px solid var(--input-border);
  border-radius: 9px; font-size: 0.85rem;
  color: var(--text-body);
  font-family: 'Inter', sans-serif;
  transition: border-color 0.2s, box-shadow 0.2s;
  outline: none;
}
.form-control:focus {
  border-color: var(--accent);
  box-shadow: 0 0 0 3px rgba(26,39,68,0.10);
}
.form-control[readonly] {
  opacity: 0.75; cursor: not-allowed;
}

/* ── ALERT ── */
.alert {
  padding: 13px 18px; border-radius: 10px;
  font-size: 0.84rem; line-height: 1.55;
  margin-bottom: 20px;
  display: flex; align-items: flex-start; gap: 10px;
  animation: riseUp 0.3s cubic-bezier(.22,1,.36,1) both;
}
.alert-icon { font-size: 1.1rem; margin-top: 1px; flex-shrink: 0; }
.alert.success {
  background: var(--green-bg); color: var(--green);
  border: 1px solid var(--green-border);
}
.alert.error {
  background: var(--red-bg); color: var(--red);
  border: 1px solid var(--red-border);
}
.alert.warning {
  background: var(--amber-bg); color: var(--amber);
  border: 1px solid var(--amber-border);
}

/* ── CONFIRM BOX ── */
.confirm-box {
  background: var(--input-bg);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 16px 18px;
  margin-bottom: 22px;
  font-size: 0.83rem;
  color: var(--text-body);
  line-height: 1.7;
}
.confirm-box .confirm-title {
  font-weight: 600; color: var(--text-head);
  margin-bottom: 8px; font-size: 0.85rem;
}
.confirm-row { display: flex; justify-content: space-between; align-items: center; padding: 4px 0; border-bottom: 1px dashed var(--border); }
.confirm-row:last-child { border-bottom: none; }
.confirm-row .key { color: var(--text-muted); font-size: 0.78rem; }
.confirm-row .val { font-weight: 500; color: var(--text-head); font-size: 0.82rem; }

/* ── CHECKBOX ── */
.checkbox-group {
  display: flex; align-items: flex-start; gap: 10px;
  background: var(--amber-bg); border: 1px solid var(--amber-border);
  border-radius: 10px; padding: 13px 15px;
  margin-bottom: 22px; cursor: pointer;
}
.checkbox-group input[type="checkbox"] {
  margin-top: 2px; accent-color: var(--accent);
  width: 16px; height: 16px; flex-shrink: 0; cursor: pointer;
}
.checkbox-group label {
  font-size: 0.81rem; color: var(--amber);
  line-height: 1.55; cursor: pointer;
}

/* ── BUTTONS ── */
.btn-row { display: flex; gap: 12px; align-items: center; flex-wrap: wrap; }

.btn-submit {
  flex: 1; min-width: 180px;
  padding: 12px 24px;
  border-radius: 10px; font-size: 0.88rem; font-weight: 600;
  cursor: pointer; font-family: 'Inter', sans-serif;
  background: var(--accent); border: 1px solid var(--accent);
  color: #fff; transition: all 0.2s;
  display: flex; align-items: center; justify-content: center; gap: 7px;
}
.btn-submit:hover:not(:disabled) { background: var(--accent-h); transform: translateY(-1px); box-shadow: 0 4px 14px var(--shadow); }
.btn-submit:disabled { opacity: 0.5; cursor: not-allowed; transform: none; }

.btn-cancel {
  padding: 12px 20px;
  border-radius: 10px; font-size: 0.85rem; font-weight: 500;
  cursor: pointer; text-decoration: none;
  font-family: 'Inter', sans-serif;
  background: transparent; border: 1px solid var(--border);
  color: var(--text-body); transition: all 0.2s;
  display: inline-flex; align-items: center; gap: 6px;
}
.btn-cancel:hover { background: var(--input-bg); }

.btn-dashboard {
  display: inline-flex; align-items: center; gap: 7px;
  padding: 11px 22px; border-radius: 10px;
  font-size: 0.85rem; font-weight: 600;
  text-decoration: none; font-family: 'Inter', sans-serif;
  background: var(--accent); color: #fff;
  border: 1px solid var(--accent); transition: all 0.2s;
}
.btn-dashboard:hover { background: var(--accent-h); }

/* ── FOOTER ── */
footer {
  text-align: center; font-size: 0.74rem;
  color: var(--text-muted); padding: 18px 16px;
  border-top: 1px solid var(--border);
  background: var(--card); line-height: 1.9;
}
footer .dev span { color: var(--gold); font-weight: 600; }

@media (max-width: 640px) {
  .main { padding: 24px 16px 32px; }
  .event-preview-body, .register-card-header, .register-card-body { padding-left: 18px; padding-right: 18px; }
  .btn-row { flex-direction: column; }
  .btn-submit { min-width: 100%; }
}
</style>
</head>
<body>

<!-- NAV -->
<nav>
  <div class="nav-left">
    <img src="adbu.jpeg" alt="ADBU Logo" class="nav-logo" onerror="this.style.display='none'">
    <div class="nav-brand">
      <div class="nav-brand-name">Assam Don Bosco University</div>
      <div class="nav-brand-sub">Azara Campus · Student Events</div>
    </div>
  </div>
  <div class="nav-right">
    <div class="user-badge">
      <div class="dot"></div>
      👋 <?= $student_name ?>
    </div>
    <label class="toggle-switch" title="Toggle dark mode">
      <input type="checkbox" id="themeCheck" onchange="toggleTheme()">
      <div class="toggle-track"></div>
      <div class="toggle-thumb">☀️</div>
    </label>
    <a href="dashboard.php" class="btn-back">← Dashboard</a>
    <a href="logout.php" class="btn-logout">🚪 Logout</a>
  </div>
</nav>

<div class="page">
<main class="main">

  <!-- BREADCRUMB -->
  <div class="breadcrumb">
    <a href="dashboard.php">🏠 Dashboard</a>
    <span class="sep">›</span>
    <a href="dashboard.php">Events</a>
    <span class="sep">›</span>
    <span class="current"><?= htmlspecialchars($event['title']) ?></span>
  </div>

  <!-- EVENT PREVIEW -->
  <div class="event-preview">
    <div class="event-preview-bar"></div>
    <div class="event-preview-body">
      <div class="event-preview-meta">
        <span class="chip date">📅 <?= htmlspecialchars($event['event_date']) ?></span>
        <span class="chip live"><span class="live-dot"></span> Open for Registration</span>
      </div>
      <div class="event-preview-title"><?= htmlspecialchars($event['title']) ?></div>
      <div class="event-preview-desc"><?= nl2br(htmlspecialchars($event['description'])) ?></div>
      <div class="event-preview-divider"></div>
      <div class="event-info-row">
        <div class="event-info-item">
          <strong>Event ID</strong>
          #<?= $event['id'] ?>
        </div>
        <div class="event-info-item">
          <strong>Date</strong>
          <?= htmlspecialchars($event['event_date']) ?>
        </div>
        <div class="event-info-item">
          <strong>Status</strong>
          Open
        </div>
      </div>
    </div>
  </div>

  <!-- REGISTRATION CARD -->
  <div class="register-card">
    <div class="register-card-header">
      <h2>📝 Event Registration</h2>
      <p>Review your details below and confirm your registration.</p>
    </div>
    <div class="register-card-body">

      <?php if ($success): ?>
        <!-- SUCCESS STATE -->
        <div class="alert success">
          <span class="alert-icon">✅</span>
          <div><?= $success ?></div>
        </div>
        <div class="confirm-box">
          <div class="confirm-title">Registration Summary</div>
          <div class="confirm-row">
            <span class="key">Student</span>
            <span class="val"><?= $student_name ?></span>
          </div>
          <div class="confirm-row">
            <span class="key">Event</span>
            <span class="val"><?= htmlspecialchars($event['title']) ?></span>
          </div>
          <div class="confirm-row">
            <span class="key">Date</span>
            <span class="val"><?= htmlspecialchars($event['event_date']) ?></span>
          </div>
          <div class="confirm-row">
            <span class="key">Status</span>
            <span class="val" style="color:var(--green)">Confirmed ✓</span>
          </div>
        </div>
        <a href="dashboard.php" class="btn-dashboard">← Back to Dashboard</a>

      <?php elseif ($already): ?>
        <!-- ALREADY REGISTERED STATE -->
        <div class="alert warning">
          <span class="alert-icon">⚠️</span>
          <div>You have already registered for this event. Check the dashboard for your registrations.</div>
        </div>
        <a href="dashboard.php" class="btn-dashboard">← Back to Dashboard</a>

      <?php else: ?>
        <!-- REGISTER FORM -->
        <?php if ($error): ?>
          <div class="alert error">
            <span class="alert-icon">❌</span>
            <div><?= $error ?></div>
          </div>
        <?php endif; ?>

        <!-- Confirm details box -->
        <div class="confirm-box">
          <div class="confirm-title">Confirm Your Details</div>
          <div class="confirm-row">
            <span class="key">Student Name</span>
            <span class="val"><?= $student_name ?></span>
          </div>
          <div class="confirm-row">
            <span class="key">Event</span>
            <span class="val"><?= htmlspecialchars($event['title']) ?></span>
          </div>
          <div class="confirm-row">
            <span class="key">Event Date</span>
            <span class="val"><?= htmlspecialchars($event['event_date']) ?></span>
          </div>
        </div>

        <!-- Read-only fields -->
        <div class="form-group">
          <label class="form-label">Your Name</label>
          <input type="text" class="form-control" value="<?= $student_name ?>" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Event</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($event['title']) ?>" readonly>
        </div>
        <div class="form-group">
          <label class="form-label">Event Date</label>
          <input type="text" class="form-control" value="<?= htmlspecialchars($event['event_date']) ?>" readonly>
        </div>

        <!-- Consent checkbox -->
        <div class="checkbox-group">
          <input type="checkbox" id="consent" onchange="toggleSubmit()">
          <label for="consent">
            I confirm that I want to register for this event. I understand that my name will be recorded and I am responsible for attending.
          </label>
        </div>

        <!-- Submit -->
        <form method="POST" action="register_event.php?id=<?= $event_id ?>">
          <div class="btn-row">
            <button type="submit" class="btn-submit" id="submitBtn" disabled>
              ✅ Confirm Registration
            </button>
            <a href="dashboard.php" class="btn-cancel">✕ Cancel</a>
          </div>
        </form>

      <?php endif; ?>

    </div>
  </div>

</main>

<footer>
  <div>© 2026 ADBU Student Events System. All Rights Reserved.</div>
  <div class="dev">Developed by <span>Krishna Das</span></div>
</footer>
</div>

<script>
// Theme toggle (matches dashboard)
function toggleTheme() {
  const isDark = document.getElementById('themeCheck').checked;
  document.documentElement.setAttribute('data-theme', isDark ? 'dark' : 'light');
  document.querySelector('.toggle-thumb').textContent = isDark ? '🌙' : '☀️';
}

// Consent checkbox enables submit button
function toggleSubmit() {
  const btn = document.getElementById('submitBtn');
  if (btn) btn.disabled = !document.getElementById('consent').checked;
}
</script>

</body>
</html>