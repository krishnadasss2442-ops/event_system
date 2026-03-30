<?php
session_start();
require 'config/db.php';

$events = $conn->query("SELECT * FROM events WHERE event_date >= NOW() ORDER BY event_date ASC");
?>


<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>ADBU Azara – Student Event Registration</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@400;600;700&family=DM+Sans:wght@300;400;500;600&display=swap" rel="stylesheet"/>
  <style>
    :root {
      --bg: #f4f1eb; --bg2: #ede9e0; --surface: #ffffff; --surface2: #f9f7f3;
      --border: rgba(0,0,0,0.08); --text: #1a1612; --text2: #5a5248; --text3: #8a8278;
      --accent: #1a3a5c; --accent2: #c8a84b; --accent3: #2e6da4;
      --hero-overlay: rgba(10,20,35,0.52); --card-shadow: 0 4px 32px rgba(0,0,0,0.08);
      --nav-bg: rgba(255,255,255,0.92); --nav-border: rgba(0,0,0,0.07);
      --btn-primary-bg: #1a3a5c; --btn-primary-text: #fff;
      --btn-secondary-bg: transparent; --btn-secondary-border: #1a3a5c; --btn-secondary-text: #1a3a5c;
      --badge-bg: #e8f0f9; --badge-text: #1a3a5c;
      --footer-bg: #12243a; --footer-text: #a0b4c8; --footer-text2: #cdd8e3;
      --toggle-track: #d0cac0; --toggle-thumb: #fff;
    }
    [data-theme="dark"] {
      --bg: #0d1520; --bg2: #111e2e; --surface: #162232; --surface2: #1a2a3e;
      --border: rgba(255,255,255,0.07); --text: #eae6df; --text2: #9ab0c4; --text3: #607a90;
      --accent: #4a9fd4; --accent2: #d4aa50; --accent3: #5ab3e8;
      --hero-overlay: rgba(5,12,22,0.65); --card-shadow: 0 4px 32px rgba(0,0,0,0.4);
      --nav-bg: rgba(13,21,32,0.95); --nav-border: rgba(255,255,255,0.06);
      --btn-primary-bg: #4a9fd4; --btn-primary-text: #0d1520;
      --btn-secondary-bg: transparent; --btn-secondary-border: #4a9fd4; --btn-secondary-text: #4a9fd4;
      --badge-bg: #1a3048; --badge-text: #5ab3e8;
      --footer-bg: #080f18; --footer-text: #4a6a82; --footer-text2: #7a9ab2;
      --toggle-track: #2a3a4e; --toggle-thumb: #4a9fd4;
    }
    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html { scroll-behavior: smooth; }
    body { font-family: 'DM Sans', sans-serif; background: var(--bg); color: var(--text); transition: background 0.4s, color 0.4s; min-height: 100vh; }

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
    .btn-outline { background: var(--btn-secondary-bg); color: var(--btn-secondary-text); border-color: var(--btn-secondary-border); }
    .btn-outline:hover { background: var(--accent); color: #fff; border-color: var(--accent); transform: translateY(-1px); }
    .btn-ghost { background: var(--badge-bg); color: var(--badge-text); border-color: transparent; }
    .btn-ghost:hover { opacity: 0.8; transform: translateY(-1px); }
    .btn-lg { padding: 0.75rem 2rem; font-size: 0.95rem; border-radius: 8px; }

    .hero { position: relative; min-height: 100vh; display: flex; align-items: center; justify-content: center; overflow: hidden; }
    .hero-bg { position: absolute; inset: 0; background-image: url('images.webp'); background-size: cover; background-position: center top; background-repeat: no-repeat; filter: saturate(0.85); transition: filter 0.4s; }
    [data-theme="dark"] .hero-bg { filter: saturate(0.6) brightness(0.7); }
    .hero-overlay { position: absolute; inset: 0; background: var(--hero-overlay); transition: background 0.4s; }
    .hero-content { position: relative; z-index: 2; text-align: center; color: #fff; max-width: 700px; padding: 2rem; }
    .hero-badge { display: inline-block; background: rgba(200,168,75,0.25); border: 1px solid rgba(200,168,75,0.45); color: #e8d48a; font-size: 0.75rem; font-weight: 500; letter-spacing: 0.12em; text-transform: uppercase; padding: 0.4rem 1rem; border-radius: 99px; margin-bottom: 1.5rem; }
    .hero-title { font-family: 'Cormorant Garamond', serif; font-size: clamp(2.5rem, 6vw, 4.2rem); font-weight: 700; line-height: 1.08; margin-bottom: 1.2rem; }
    .hero-title span { color: var(--accent2); }
    .hero-sub { font-size: 1.05rem; font-weight: 300; opacity: 0.88; line-height: 1.65; margin-bottom: 2.2rem; max-width: 520px; margin-left: auto; margin-right: auto; }
    .hero-cta { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; }
    .hero-scroll-hint { position: absolute; bottom: 2rem; left: 50%; transform: translateX(-50%); color: rgba(255,255,255,0.55); font-size: 0.72rem; letter-spacing: 0.15em; text-transform: uppercase; z-index: 2; }

    .stats-strip { background: var(--surface); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); padding: 2rem; transition: background 0.4s; }
    .stats-inner { max-width: 900px; margin: 0 auto; display: flex; justify-content: space-around; flex-wrap: wrap; gap: 1.5rem; text-align: center; }
    .stat-num { font-family: 'Cormorant Garamond', serif; font-size: 2.2rem; font-weight: 700; color: var(--accent); }
    .stat-label { font-size: 0.78rem; color: var(--text3); text-transform: uppercase; letter-spacing: 0.08em; margin-top: 0.2rem; }

    section { padding: 5rem 2rem; }
    .section-inner { max-width: 1200px; margin: 0 auto; }
    .section-tag { font-size: 0.72rem; font-weight: 600; letter-spacing: 0.14em; text-transform: uppercase; color: var(--accent2); margin-bottom: 0.6rem; }
    .section-title { font-family: 'Cormorant Garamond', serif; font-size: clamp(1.8rem, 3.5vw, 2.6rem); font-weight: 700; color: var(--text); margin-bottom: 0.6rem; }
    .section-sub { color: var(--text2); font-size: 0.95rem; max-width: 520px; line-height: 1.6; }

    .events-section { background: var(--bg2); transition: background 0.4s; }
    .events-header { display: flex; justify-content: space-between; align-items: flex-end; flex-wrap: wrap; gap: 1rem; margin-bottom: 2.5rem; }
    .events-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(290px, 1fr)); gap: 1.5rem; }
    .event-card { background: var(--surface); border: 1px solid var(--border); border-radius: 16px; overflow: hidden; box-shadow: var(--card-shadow); transition: transform 0.25s, box-shadow 0.25s, background 0.4s; }
    .event-card:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.12); }
    .event-card-thumb { height: 140px; display: flex; align-items: center; justify-content: center; font-size: 3rem; background: linear-gradient(135deg,#e8f0f9,#d0e4f5); position: relative; }
    .event-card-thumb.c2 { background: linear-gradient(135deg,#fdf3e0,#f5e0b0); }
    .event-card-thumb.c3 { background: linear-gradient(135deg,#e6f9ee,#b8eccc); }
    .event-cat { position: absolute; top: 0.7rem; right: 0.7rem; background: var(--badge-bg); color: var(--badge-text); font-size: 0.68rem; font-weight: 600; padding: 0.25rem 0.6rem; border-radius: 99px; letter-spacing: 0.06em; }
    .event-card-body { padding: 1.3rem; }
    .event-date { font-size: 0.73rem; color: var(--accent2); font-weight: 600; letter-spacing: 0.06em; text-transform: uppercase; margin-bottom: 0.4rem; }
    .event-name { font-family: 'Cormorant Garamond', serif; font-size: 1.25rem; font-weight: 700; color: var(--text); margin-bottom: 0.5rem; }
    .event-desc { font-size: 0.82rem; color: var(--text2); line-height: 1.55; margin-bottom: 1rem; }
    .event-footer { display: flex; justify-content: space-between; align-items: center; }
    .event-seats { font-size: 0.76rem; color: var(--text3); }
    .event-seats span { color: var(--accent); font-weight: 600; }

    .features-section { background: var(--bg); transition: background 0.4s; }
    .features-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1.5rem; margin-top: 2.5rem; }
    .feature-card { background: var(--surface2); border: 1px solid var(--border); border-radius: 14px; padding: 1.8rem 1.5rem; transition: background 0.4s; }
    .feature-icon { font-size: 2rem; margin-bottom: 0.8rem; }
    .feature-title { font-family: 'Cormorant Garamond', serif; font-size: 1.15rem; font-weight: 700; margin-bottom: 0.4rem; }
    .feature-desc { font-size: 0.82rem; color: var(--text2); line-height: 1.55; }

    .cta-section { background: var(--surface); border-top: 1px solid var(--border); border-bottom: 1px solid var(--border); text-align: center; transition: background 0.4s; }
    .cta-inner { max-width: 600px; margin: 0 auto; }
    .cta-btns { display: flex; gap: 1rem; justify-content: center; flex-wrap: wrap; margin-top: 1.8rem; }

    footer { background: var(--footer-bg); color: var(--footer-text); padding: 3rem 2rem 1.5rem; transition: background 0.4s; }
    .footer-inner { max-width: 1200px; margin: 0 auto; }
    .footer-grid { display: grid; grid-template-columns: 2fr 1fr 1fr; gap: 2rem; margin-bottom: 2rem; }
    .footer-logo { display: flex; align-items: center; gap: 0.75rem; margin-bottom: 1rem; }
    .footer-logo img { height: 40px; }
    .footer-logo-text { font-family: 'Cormorant Garamond', serif; font-size: 1rem; font-weight: 700; color: var(--footer-text2); }
    .footer-about { font-size: 0.8rem; line-height: 1.65; color: var(--footer-text); }
    .footer-heading { font-size: 0.72rem; font-weight: 600; letter-spacing: 0.1em; text-transform: uppercase; color: var(--footer-text2); margin-bottom: 0.9rem; }
    .footer-links { list-style: none; }
    .footer-links li { margin-bottom: 0.5rem; }
    .footer-links a { color: var(--footer-text); font-size: 0.82rem; text-decoration: none; transition: color 0.2s; }
    .footer-links a:hover { color: var(--footer-text2); }
    .footer-bottom { border-top: 1px solid rgba(255,255,255,0.06); padding-top: 1.2rem; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem; }
    .footer-copy { font-size: 0.76rem; }
    .footer-copy strong { color: var(--accent2); }
    .footer-dev { font-size: 0.72rem; letter-spacing: 0.04em; }
    .footer-dev span { color: var(--footer-text2); font-weight: 600; }

    @media(max-width: 768px) { .footer-grid { grid-template-columns: 1fr; } }
    @media(max-width: 600px) { nav { padding: 0 1rem; } .nav-logo-text .uni-name { font-size: 0.85rem; } .btn { padding: 0.4rem 0.8rem; font-size: 0.78rem; } section { padding: 3.5rem 1.2rem; } }
    @media(max-width: 480px) { .nav-logo-text .campus-tag { display: none; } .stats-inner { gap: 1.5rem; } }
  </style>
</head>
<body>

<nav>
  <div class="nav-inner">
    <a href="index.php" class="nav-logo">
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
      <!-- FIX: was admin.html — corrected to admin.php -->
      <a href="admin.php" class="btn btn-primary">Admin ⚙</a>
    </div>
  </div>
</nav>

<section class="hero" id="home">
  <div class="hero-bg"></div>
  <div class="hero-overlay"></div>
  <div class="hero-content">
    <div class="hero-badge">✦ Student Event Registration System</div>
    <h1 class="hero-title">Where <span>Campus Life</span><br/>Comes Alive</h1>
    <p class="hero-sub">Discover, register and participate in the vibrant events at Assam Don Bosco University, Azara Campus — all in one place.</p>
    <div class="hero-cta">
      <a href="register.php" class="btn btn-primary btn-lg">Register Now →</a>
      <a href="login.php" class="btn btn-outline btn-lg" style="color:#fff;border-color:rgba(255,255,255,0.5);">Sign In</a>
    </div>
  </div>
  <div class="hero-scroll-hint">Scroll</div>
</section>

<div class="stats-strip">
  <div class="stats-inner">
    <div class="stat-item"><div class="stat-num">40+</div><div class="stat-label">Events This Year</div></div>
    <div class="stat-item"><div class="stat-num">6,200+</div><div class="stat-label">Registered Students</div></div>
    <div class="stat-item"><div class="stat-num">18</div><div class="stat-label">Departments</div></div>
    <div class="stat-item"><div class="stat-num">100%</div><div class="stat-label">Online Registration</div></div>
  </div>
</div>

<section class="events-section" id="events">
  <div class="section-inner">
    <div class="events-header">
      <div>
        <div class="section-tag">Upcoming</div>
        <h2 class="section-title">Featured Events</h2>
        <p class="section-sub">Find events that spark your passion — from tech fests to cultural nights.</p>
      </div>
    </div>
    <div class="events-grid">
      <?php if ($events && $events->num_rows > 0): ?>
        <?php while ($ev = $events->fetch_assoc()): ?>
        <div class="event-card">
          <div class="event-card-thumb">
            <span>🎓</span>
            <div class="event-cat">Event</div>
          </div>
          <div class="event-card-body">
            <div class="event-date"><?= htmlspecialchars(date('F j, Y · g:i A', strtotime($ev['event_date']))) ?></div>
            <div class="event-name"><?= htmlspecialchars($ev['title']) ?></div>
            <div class="event-desc"><?= htmlspecialchars($ev['description']) ?></div>
            <div class="event-footer">
              <div class="event-seats">Seats: <span><?= (int)$ev['available_seats'] ?> left</span></div>
            </div>
          </div>
        </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p style="color:var(--text2);grid-column:1/-1;">No upcoming events found. Check back soon!</p>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="features-section" id="features">
  <div class="section-inner">
    <div class="section-tag">Why Use</div>
    <h2 class="section-title">Everything in One Platform</h2>
    <div class="features-grid">
      <div class="feature-card"><div class="feature-icon">📋</div><div class="feature-title">Easy Registration</div><div class="feature-desc">Register for multiple events in seconds with your student ID and a single account.</div></div>
      <div class="feature-card"><div class="feature-icon">🔔</div><div class="feature-title">Live Updates</div><div class="feature-desc">Get instant notifications about event changes, reminders and last-minute announcements.</div></div>
      <div class="feature-card"><div class="feature-icon">🎫</div><div class="feature-title">Digital Passes</div><div class="feature-desc">Receive QR-coded digital entry passes directly to your account after registration.</div></div>
      <div class="feature-card"><div class="feature-icon">📊</div><div class="feature-title">Admin Dashboard</div><div class="feature-desc">Faculty coordinators can manage events, track attendance and view analytics in real-time.</div></div>
    </div>
  </div>
</section>

<section class="cta-section">
  <div class="cta-inner">
    <div class="section-tag" style="color:var(--accent2)">Get Started</div>
    <h2 class="section-title">Ready to Join the Action?</h2>
    <p class="section-sub">Create your account today and never miss an event at ADBU Azara again.</p>
    <div class="cta-btns">
      <a href="register.php" class="btn btn-primary btn-lg">Create Account</a>
      <a href="login.php" class="btn btn-outline btn-lg">Sign In</a>
    </div>
  </div>
</section>

<footer>
  <div class="footer-inner">
    <div class="footer-grid">
      <div>
        <div class="footer-logo">
          <img src="adbu.jpeg" alt="ADBU" onerror="this.style.display='none'"/>
          <div class="footer-logo-text">Assam Don Bosco University<br/><span style="font-size:0.75rem;font-weight:400;color:var(--footer-text)">Azara Campus</span></div>
        </div>
        <p class="footer-about">The Student Event Registration System is an official platform of ADBU Azara Campus, designed to streamline event discovery and participation for all students and faculty.</p>
      </div>
      <div>
        <div class="footer-heading">Quick Links</div>
        <ul class="footer-links">
          <li><a href="index.php">Home</a></li>
          <li><a href="register.php">Register</a></li>
          <li><a href="login.php">Login</a></li>
        </ul>
      </div>
      <div>
        <div class="footer-heading">Contact</div>
        <ul class="footer-links">
          <li><a href="#">Azara, Guwahati – 781017</a></li>
          <li><a href="#">Assam, India</a></li>
          <li><a href="#">events@adbu.in</a></li>
          <li><a href="#">www.adbu.in</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <div class="footer-copy">&copy; 2025 <strong>Assam Don Bosco University</strong>. All rights reserved.</div>
      <div class="footer-dev">Developed by <span>Krishna Das</span></div>
    </div>
  </div>
</footer>

<script>
  function toggleTheme(el) {
    document.documentElement.setAttribute('data-theme', el.checked ? 'dark' : 'light');
  }
</script>
</body>
</html>