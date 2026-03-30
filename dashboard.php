<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit; }
if ($_SESSION['user_role'] === 'admin') { header("Location: admin.php"); exit; }

$student_id   = $_SESSION['user_id'];
$student_name = htmlspecialchars($_SESSION['user_name'] ?? 'Student');

$events       = $conn->query("SELECT * FROM events ORDER BY event_date ASC");
$total_events = $events->num_rows;

$stmt = $conn->prepare("SELECT COUNT(*) as total FROM registrations WHERE user_id = ?");
$stmt->bind_param("i", $student_id);
$stmt->execute();
$my_regs = $stmt->get_result()->fetch_assoc()['total'] ?? 0;
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>ADBU Student Events – Dashboard</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=DM+Sans:ital,opsz,wght@0,9..40,400;0,9..40,500;0,9..40,600;0,9..40,700;1,9..40,400&family=DM+Serif+Display&display=swap" rel="stylesheet">
<style>
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

/* ── LIGHT THEME ── */
:root, [data-theme="light"] {
  --navy:        #1a2744;
  --gold:        #c9a84c;
  --bg:          #edeae3;
  --card:        #ffffff;
  --text:        #1a1a1a;
  --text2:       #555;
  --muted:       #888;
  --border:      #e5e2da;
  --pill-bg:     #f5f3ef;
  --pill-border: #e0ddd6;
  --badge-bg:    #eef2ff;
  --badge-border:#d0d8f5;
  --badge-color: #4a5fb5;
  --green:       #2e9e5b;
  --green-bg:    #e8f9f0;
  --green-border:#b7e8cf;
  --shadow:      0 2px 16px rgba(0,0,0,0.07);
  --shadow-hover:0 8px 28px rgba(0,0,0,0.11);
  --footer-bg:   #1a2744;
  --footer-text: #cbd5e1;
  --toggle-track:#e0ddd6;
  --toggle-knob: #fff;
  --toggle-icon: '🌙';
}

/* ── DARK THEME ── */
[data-theme="dark"] {
  --navy:        #5b7fe8;
  --gold:        #e8c96a;
  --bg:          #0f1117;
  --card:        #1a1d27;
  --text:        #e8eaf0;
  --text2:       #a0a8c0;
  --muted:       #6b7499;
  --border:      #2a2f42;
  --pill-bg:     #1e2235;
  --pill-border: #2a2f42;
  --badge-bg:    #1e2550;
  --badge-border:#2e3a70;
  --badge-color: #7e99e8;
  --green:       #4ade80;
  --green-bg:    #0d2e1a;
  --green-border:#1a5c34;
  --shadow:      0 2px 20px rgba(0,0,0,0.3);
  --shadow-hover:0 8px 32px rgba(0,0,0,0.45);
  --footer-bg:   #0a0c14;
  --footer-text: #6b7499;
  --toggle-track:#3a4060;
  --toggle-knob: #e8c96a;
  --toggle-icon: '☀️';
}

body {
  font-family: 'DM Sans', sans-serif;
  background: var(--bg);
  color: var(--text);
  min-height: 100vh;
  display: flex;
  flex-direction: column;
  transition: background .3s, color .3s;
}

/* ── NAVBAR ── */
.navbar {
  background: var(--card);
  display: flex;
  align-items: center;
  justify-content: space-between;
  padding: 0 32px;
  height: 62px;
  border-bottom: 1px solid var(--border);
  position: fixed;
  top: 0; left: 0; right: 0;
  z-index: 200;
  transition: background .3s, border-color .3s;
  box-shadow: 0 1px 8px rgba(0,0,0,0.06);
}
.navbar-brand { display: flex; align-items: center; gap: 10px; text-decoration: none; }
.navbar-logo {
  width: 40px; height: 40px;
  border-radius: 8px;
  overflow: hidden;
  display: flex; align-items: center; justify-content: center;
  flex-shrink: 0;
}
.navbar-logo img { width: 100%; height: 100%; object-fit: contain; }
.navbar-title h1 {
  font-size: 13.5px; font-weight: 700;
  color: var(--navy); line-height: 1.2;
  transition: color .3s;
}
.navbar-title p {
  font-size: 10px; font-weight: 500;
  letter-spacing: 0.12em; text-transform: uppercase;
  color: var(--muted);
}
.navbar-actions { display: flex; align-items: center; gap: 10px; }

.user-pill {
  display: flex; align-items: center; gap: 8px;
  background: var(--pill-bg); border: 1px solid var(--pill-border);
  border-radius: 999px; padding: 6px 14px 6px 10px;
  font-size: 13px; font-weight: 500; color: var(--text);
  transition: background .3s, border-color .3s;
}
.user-pill .dot {
  width: 8px; height: 8px; background: #f5a623;
  border-radius: 50%; display: inline-block;
}

/* Toggle switch */
.theme-toggle {
  position: relative; width: 48px; height: 26px; cursor: pointer;
  background: var(--toggle-track); border-radius: 999px; border: none;
  transition: background .3s; flex-shrink: 0;
}
.theme-toggle .knob {
  position: absolute; top: 3px; left: 3px;
  width: 20px; height: 20px; border-radius: 50%;
  background: var(--toggle-knob);
  transition: transform .3s, background .3s;
  display: flex; align-items: center; justify-content: center;
  font-size: 11px; line-height: 1;
}
[data-theme="dark"] .theme-toggle .knob { transform: translateX(22px); }
[data-theme="light"] .theme-toggle { background: #f5a623; }
[data-theme="dark"]  .theme-toggle { background: #3a4060; }

.logout-btn {
  display: flex; align-items: center; gap: 7px;
  background: var(--card); border: 1.5px solid var(--pill-border);
  border-radius: 10px; padding: 7px 16px;
  font-size: 13px; font-weight: 600; color: var(--text);
  cursor: pointer; text-decoration: none;
  transition: background .15s, border-color .15s, color .15s;
}
.logout-btn:hover { background: #fdf3f3; border-color: #e8b4b4; color: #c0392b; }
[data-theme="dark"] .logout-btn:hover { background: #2d1515; border-color: #7a2222; color: #f87171; }

/* ── MAIN ── */
main {
  flex: 1;
  max-width: 1200px;
  margin: 0 auto;
  padding: 96px 28px 48px;
  width: 100%;
}

/* ── WELCOME CARD ── */
.welcome-card {
  background: var(--card);
  border-radius: 18px;
  padding: 36px 44px;
  display: flex; align-items: center; justify-content: space-between;
  margin-bottom: 24px;
  box-shadow: var(--shadow);
  position: relative; overflow: hidden;
  transition: background .3s, box-shadow .3s;
}
.welcome-card::before {
  content: '';
  position: absolute; top: 0; left: 0; right: 0; height: 4px;
  background: linear-gradient(90deg, var(--navy) 0%, var(--gold) 100%);
}
.welcome-card h2 {
  font-family: 'DM Serif Display', serif;
  font-size: 30px; font-weight: 400; color: var(--text);
  margin-bottom: 6px; transition: color .3s;
}
.welcome-card .sub { font-size: 14px; color: var(--muted); }
.welcome-card .sub span { color: var(--gold); font-weight: 600; }
.welcome-time { text-align: right; }
.welcome-time .time {
  font-size: 22px; font-weight: 700; color: var(--text);
  font-variant-numeric: tabular-nums; letter-spacing: -0.5px;
  transition: color .3s;
}
.welcome-time .date { font-size: 13px; color: var(--muted); margin-top: 4px; }

/* ── STATS ── */
.stats-grid {
  display: grid; grid-template-columns: repeat(3,1fr);
  gap: 18px; margin-bottom: 32px;
}
.stat-card {
  background: var(--card); border-radius: 18px;
  padding: 26px 26px 22px; box-shadow: var(--shadow);
  transition: background .3s, box-shadow .3s;
}
.stat-icon {
  width: 44px; height: 44px; border-radius: 12px;
  display: flex; align-items: center; justify-content: center;
  font-size: 21px; margin-bottom: 16px;
}
.stat-icon.teal { background: #e6f9f5; }
.stat-icon.pink { background: #fdeef0; }
.stat-icon.yellow { background: #fdf5e6; }
[data-theme="dark"] .stat-icon.teal   { background: #0d2920; }
[data-theme="dark"] .stat-icon.pink   { background: #2a0f14; }
[data-theme="dark"] .stat-icon.yellow { background: #2a2010; }
.stat-label {
  font-size: 10.5px; font-weight: 600; letter-spacing: 0.1em;
  text-transform: uppercase; color: var(--muted); margin-bottom: 8px;
}
.stat-value { font-size: 36px; font-weight: 700; color: var(--text); line-height: 1; margin-bottom: 6px; }
.stat-dash  { font-size: 28px; font-weight: 700; color: var(--text); line-height: 1; margin-bottom: 6px; }
.stat-sub   { font-size: 13px; color: var(--muted); }
.stat-sub.green { color: var(--green); font-weight: 600; }

/* ── EVENTS ── */
.section-header { display: flex; align-items: center; gap: 12px; margin-bottom: 20px; }
.section-header h3 { font-size: 19px; font-weight: 700; color: var(--text); }
.live-badge {
  background: var(--green-bg); color: var(--green);
  border: 1px solid var(--green-border);
  border-radius: 999px; font-size: 12px; font-weight: 600; padding: 3px 12px;
}

.events-grid { display: grid; grid-template-columns: repeat(3,1fr); gap: 20px; }

.event-card {
  background: var(--card); border-radius: 18px; overflow: hidden;
  box-shadow: var(--shadow); display: flex; flex-direction: column;
  transition: transform .2s, box-shadow .2s, background .3s;
}
.event-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-hover); }
.event-card-stripe { height: 4px; background: linear-gradient(90deg, var(--navy) 0%, var(--gold) 100%); }
.event-card-body { padding: 22px 22px 18px; flex: 1; display: flex; flex-direction: column; }

.event-date-badge {
  display: inline-flex; align-items: center; gap: 6px;
  background: var(--badge-bg); border: 1px solid var(--badge-border);
  border-radius: 999px; padding: 4px 13px;
  font-size: 11.5px; font-weight: 500; color: var(--badge-color);
  margin-bottom: 14px; align-self: flex-start;
}
.event-card h4 { font-size: 17px; font-weight: 700; color: var(--text); margin-bottom: 9px; line-height: 1.3; }
.event-card .desc { font-size: 13px; color: var(--text2); line-height: 1.65; flex: 1; }

.event-card-footer {
  padding: 0 22px 20px;
  display: flex; align-items: center; justify-content: space-between;
}
.seats-label { font-size: 13px; color: var(--muted); }
.seats-open  { color: var(--green); font-weight: 600; }
.seats-full  { color: #e55353; font-weight: 600; }

.register-btn {
  background: var(--navy); color: #fff; border: none; border-radius: 10px;
  padding: 9px 20px; font-size: 13.5px; font-weight: 600;
  font-family: 'DM Sans', sans-serif; cursor: pointer;
  display: inline-flex; align-items: center; gap: 5px;
  text-decoration: none; transition: opacity .15s, transform .1s;
}
.register-btn:hover { opacity: 0.88; transform: translateY(-1px); }

.registered-btn {
  background: var(--green-bg); color: var(--green);
  border: 1.5px solid var(--green-border); border-radius: 10px;
  padding: 9px 20px; font-size: 13.5px; font-weight: 600;
  font-family: 'DM Sans', sans-serif; cursor: default;
}

.no-events {
  text-align: center; padding: 60px 0; color: var(--muted);
  font-size: 15px; grid-column: 1 / -1;
}

/* ── FOOTER ── */
footer {
  background: var(--footer-bg);
  color: var(--footer-text);
  text-align: center;
  padding: 18px 20px;
  font-size: 12.5px;
  transition: background .3s;
}
footer a { color: var(--gold); text-decoration: none; font-weight: 600; }
footer a:hover { text-decoration: underline; }
footer .footer-inner { display: flex; flex-wrap: wrap; justify-content: center; gap: 6px 20px; }
footer .sep { opacity: 0.35; }

/* ── RESPONSIVE ── */
@media (max-width: 900px) {
  .stats-grid, .events-grid { grid-template-columns: 1fr 1fr; }
  .welcome-card { flex-direction: column; gap: 18px; }
  .welcome-time { text-align: left; }
}
@media (max-width: 580px) {
  .stats-grid, .events-grid { grid-template-columns: 1fr; }
  main { padding: 80px 14px 40px; }
  .navbar { padding: 0 14px; }
  .welcome-card { padding: 24px 20px; }
}
</style>
</head>
<body>

<!-- FIXED NAVBAR -->
<nav class="navbar">
  <a class="navbar-brand" href="#">
    <div class="navbar-logo">
      <img src="data:image/png;base64,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" alt="ADBU Logo">
    </div>
    <div class="navbar-title">
      <h1>Assam Don Bosco University</h1>
      <p>Azara Campus &middot; Student Events</p>
    </div>
  </a>
  <div class="navbar-actions">
    <div class="user-pill">
      <span class="dot"></span>
      👤 <?= $student_name ?>
    </div>
    <button class="theme-toggle" id="themeToggle" aria-label="Toggle dark mode">
      <span class="knob" id="themeKnob">🌙</span>
    </button>
    <a href="logout.php" class="logout-btn">
      🚪 Logout
    </a>
  </div>
</nav>

<!-- MAIN CONTENT -->
<main>

  <!-- WELCOME -->
  <div class="welcome-card">
    <div>
      <h2>Welcome, <?= $student_name ?> 🎓</h2>
      <p class="sub">Browse and register for upcoming <span>ADBU Student Events</span> below.</p>
    </div>
    <div class="welcome-time">
      <div class="time" id="liveClock">--:--:-- --</div>
      <div class="date" id="liveDate"></div>
    </div>
  </div>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon teal">🎉</div>
      <div class="stat-label">Total Events</div>
      <div class="stat-value"><?= $total_events ?></div>
      <div class="stat-sub">Available to register</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon pink">📋</div>
      <div class="stat-label">My Registrations</div>
      <?php if ($my_regs > 0): ?>
        <div class="stat-value"><?= $my_regs ?></div>
        <div class="stat-sub"><?= $my_regs === 1 ? '1 event' : "$my_regs events" ?> joined</div>
      <?php else: ?>
        <div class="stat-dash">—</div>
        <div class="stat-sub">Check your profile</div>
      <?php endif; ?>
    </div>
    <div class="stat-card">
      <div class="stat-icon yellow">📅</div>
      <div class="stat-label">Upcoming</div>
      <div class="stat-dash">—</div>
      <div class="stat-sub green">Stay tuned!</div>
    </div>
  </div>

  <!-- EVENTS -->
  <div class="section-header">
    <h3>All Events</h3>
    <span class="live-badge">Live</span>
  </div>

  <div class="events-grid">
    <?php if ($total_events > 0): ?>
      <?php while ($row = $events->fetch_assoc()):
        $stmt = $conn->prepare("SELECT id FROM registrations WHERE user_id = ? AND event_id = ?");
        $stmt->bind_param("ii", $student_id, $row['id']);
        $stmt->execute();
        $is_registered = $stmt->get_result()->num_rows > 0;
        $stmt->close();

        $date_fmt  = date('Y-m-d H:i:s', strtotime($row['event_date']));
        $desc      = htmlspecialchars($row['description']);
        $desc_s    = mb_strlen($desc) > 120 ? mb_substr($desc, 0, 120) . '...' : $desc;
        $seats_open = !isset($row['capacity']) || $row['capacity'] === null || $row['capacity'] > 0;
      ?>
      <div class="event-card">
        <div class="event-card-stripe"></div>
        <div class="event-card-body">
          <div class="event-date-badge">📅 <?= htmlspecialchars($date_fmt) ?></div>
          <h4><?= htmlspecialchars($row['title']) ?></h4>
          <p class="desc"><?= $desc_s ?></p>
        </div>
        <div class="event-card-footer">
          <div class="seats-label">
            Seats: <?= $seats_open
              ? '<span class="seats-open">Open</span>'
              : '<span class="seats-full">Full</span>' ?>
          </div>
          <?php if ($is_registered): ?>
            <span class="registered-btn">✓ Registered</span>
          <?php else: ?>
            <form action="register_event.php" method="POST" style="margin:0">
              <input type="hidden" name="event_id" value="<?= (int)$row['id'] ?>">
              <button type="submit" class="register-btn">Register →</button>
            </form>
          <?php endif; ?>
        </div>
      </div>
      <?php endwhile; ?>
    <?php else: ?>
      <div class="no-events">No events available at the moment.</div>
    <?php endif; ?>
  </div>

</main>

<!-- FOOTER -->
<footer>
  <div class="footer-inner">
    <span>© <?= date('Y') ?> Assam Don Bosco University, Azara Campus</span>
    <span class="sep">|</span>
    <span>All rights reserved</span>
    <span class="sep">|</span>
    <span>Developed by <a href="#">Krishna Das</a></span>
  </div>
</footer>

<script>
// Live clock
function tick() {
  const now = new Date();
  let h = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
  const ampm = h >= 12 ? 'pm' : 'am';
  h = h % 12 || 12;
  const p = n => String(n).padStart(2,'0');
  document.getElementById('liveClock').textContent = `${p(h)}:${p(m)}:${p(s)} ${ampm}`;
  const days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  const months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  document.getElementById('liveDate').textContent =
    `${days[now.getDay()]}, ${now.getDate()} ${months[now.getMonth()]} ${now.getFullYear()}`;
}
tick(); setInterval(tick, 1000);

// Dark / Light mode toggle
const root   = document.documentElement;
const toggle = document.getElementById('themeToggle');
const knob   = document.getElementById('themeKnob');

// Restore saved preference
const saved = localStorage.getItem('adbu-theme') || 'light';
root.setAttribute('data-theme', saved);
knob.textContent = saved === 'dark' ? '☀️' : '🌙';

toggle.addEventListener('click', () => {
  const current = root.getAttribute('data-theme');
  const next    = current === 'dark' ? 'light' : 'dark';
  root.setAttribute('data-theme', next);
  knob.textContent = next === 'dark' ? '☀️' : '🌙';
  localStorage.setItem('adbu-theme', next);
});
</script>
</body>
</html>