<?php
session_start();
require 'config/db.php';

if (!isset($_SESSION['admin'])) {
    header("Location: admin.php");
    exit();
}
$admin_name = ucfirst(htmlspecialchars($_SESSION['admin'] ?? 'Admin'));

$action = $_POST['action'] ?? $_GET['action'] ?? '';

/* ── CREATE EVENT ── */
if ($action === 'create_event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $capacity    = intval($_POST['capacity'] ?? 0);
    $location    = trim($_POST['location'] ?? '');
    if ($title && $event_date) {
        // Convert datetime-local "2026-04-05T10:00" → "2026-04-05 10:00:00"
        $event_date_sql = str_replace('T', ' ', $event_date) . ':00';
        $stmt = $conn->prepare("INSERT INTO events (title, description, event_date, capacity, location) VALUES (?,?,?,?,?)");
        $stmt->bind_param("sssis", $title, $description, $event_date_sql, $capacity, $location);
        if ($stmt->execute()) {
            $_SESSION['flash'] = ['type'=>'success','msg'=>"Event \"$title\" created successfully!"];
        } else {
            $_SESSION['flash'] = ['type'=>'error','msg'=>'DB Error: '.$conn->error];
        }
        $stmt->close();
    } else {
        $_SESSION['flash'] = ['type'=>'error','msg'=>'Title and Date are required.'];
    }
    header("Location: admin_dashboard.php"); exit();
}

/* ── DELETE EVENT ── */
if ($action === 'delete_event' && isset($_POST['event_id'])) {
    $eid = intval($_POST['event_id']);
    $conn->query("DELETE FROM registrations WHERE event_id = $eid");
    $conn->query("DELETE FROM events WHERE id = $eid");
    $_SESSION['flash'] = ['type'=>'success','msg'=>'Event deleted successfully.'];
    header("Location: admin_dashboard.php"); exit();
}

/* ── EDIT EVENT ── */
/* ── EDIT EVENT ── */
if ($action === 'edit_event' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $eid         = intval($_POST['event_id']);
    $title       = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $event_date  = trim($_POST['event_date'] ?? '');
    $capacity    = intval($_POST['capacity'] ?? 0);
    $location    = trim($_POST['location'] ?? '');
    
    // Convert datetime-local format to SQL format
    $event_date_sql = str_replace('T', ' ', $event_date) . ':00';
    
    $stmt = $conn->prepare("UPDATE events SET title=?, description=?, event_date=?, capacity=?, location=? WHERE id=?");
    $stmt->bind_param("sssisi", $title, $description, $event_date_sql, $capacity, $location, $eid);
    $stmt->execute();
    $stmt->close();
    
    $_SESSION['flash'] = ['type'=>'success','msg'=>"\"$title\" updated."];
    header("Location: admin_dashboard.php"); 
    exit();
}

/* ── FETCH STATS ── */
$total_events = (int)($conn->query("SELECT COUNT(*) as c FROM events")->fetch_assoc()['c'] ?? 0);
$total_regs   = (int)($conn->query("SELECT COUNT(*) as c FROM registrations")->fetch_assoc()['c'] ?? 0);
$now_str      = date('Y-m-d H:i:s');
$upcoming     = (int)($conn->query("SELECT COUNT(*) as c FROM events WHERE event_date > '$now_str'")->fetch_assoc()['c'] ?? 0);
$past         = max(0, $total_events - $upcoming);

/* ── FETCH EVENTS ── */
$all_events_res = $conn->query("SELECT * FROM events ORDER BY event_date DESC");
$all_events = [];
while ($r = $all_events_res->fetch_assoc()) $all_events[] = $r;
$recent_events = array_slice($all_events, 0, 5);

/* ── FETCH REGISTRATIONS ── */
// FIXED QUERY HERE: Swapped `created_at` out and ordered by `r.id` instead.
$regs_res = $conn->query("
    SELECT r.id, u.name as uname, u.email, e.title as ev_title
    FROM registrations r
    LEFT JOIN users u  ON u.id = r.user_id
    LEFT JOIN events e ON e.id = r.event_id
    ORDER BY r.id DESC LIMIT 60
");
$regs = $regs_res ? $regs_res->fetch_all(MYSQLI_ASSOC) : [];

$flash = $_SESSION['flash'] ?? null;
unset($_SESSION['flash']);

/* ── Helper: build safe edit data for JS ── */
function ev_data(array $ev): string {
    return htmlspecialchars(json_encode([
        'id'          => (int)$ev['id'],
        'title'       => $ev['title'],
        'description' => $ev['description'] ?? '',
        'event_date'  => date('Y-m-d\TH:i', strtotime($ev['event_date'])),
        'capacity'    => (int)($ev['capacity'] ?? 0),
        'location'    => $ev['location'] ?? '',
    ], JSON_HEX_QUOT | JSON_HEX_APOS), ENT_QUOTES);
}

function ev_status(string $event_date): array {
    $ts  = strtotime($event_date);
    $now = time();
    if ($ts > $now + 3600)     return ['upcoming', '● Upcoming'];
    elseif ($ts > $now - 3600) return ['live',     '● Live'];
    else                       return ['closed',   '● Closed'];
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Dashboard — ADBU Student Events</title>
<link href="https://fonts.googleapis.com/css2?family=Playfair+Display:wght@600;700&family=Inter:wght@300;400;500;600&display=swap" rel="stylesheet">
<style>
:root {
  --bg:#edeae3;--card:#ffffff;--nav:#ffffff;--border:#e2e0db;
  --text-head:#1a1a2e;--text-body:#4a4a5a;--text-muted:#8e8e9e;
  --accent:#1a2744;--accent-h:#243460;--gold:#c9a84c;
  --input-bg:#f5f4f0;--input-border:#dddbd5;--shadow:rgba(26,39,68,0.10);
  --green:#16a34a;--green-bg:#f0fdf4;--green-border:#bbf7d0;
  --blue:#1d4ed8;--blue-bg:#eff6ff;--blue-border:#bfdbfe;
  --amber:#b45309;--amber-bg:#fffbeb;--amber-border:#fde68a;
  --red:#dc2626;--red-bg:#fff5f5;--red-border:#fca5a5;
  --overlay:rgba(0,0,0,0.45);
}
[data-theme="dark"] {
  --bg:#12111a;--card:#1c1b27;--nav:#1c1b27;--border:#2a2938;
  --text-head:#f0f0f5;--text-body:#b0b0c0;--text-muted:#6a6a7a;
  --accent:#4a6fa5;--accent-h:#5a80b8;--gold:#c9a84c;
  --input-bg:#252434;--input-border:#32314a;--shadow:rgba(0,0,0,0.40);
  --green:#4ade80;--green-bg:#052e16;--green-border:#14532d;
  --blue:#60a5fa;--blue-bg:#1e3a5f;--blue-border:#1e40af;
  --amber:#fbbf24;--amber-bg:#2d1f00;--amber-border:#78350f;
  --red:#f87171;--red-bg:#2a1a1a;--red-border:#7f1d1d;
  --overlay:rgba(0,0,0,0.72);
}

*,*::before,*::after{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text-body);min-height:100vh;display:flex;flex-direction:column;transition:background .3s,color .3s;}

/* ── NAV ── */
nav{position:fixed;top:0;left:0;right:0;z-index:300;display:flex;align-items:center;justify-content:space-between;padding:0 28px;height:64px;background:var(--nav);border-bottom:1px solid var(--border);box-shadow:0 1px 8px var(--shadow);transition:background .3s,border-color .3s;}
.nav-left{display:flex;align-items:center;gap:12px;}
.nav-logo{height:38px;width:auto;object-fit:contain;display:block;}
.nav-brand-name{font-family:'Playfair Display',serif;font-weight:700;font-size:.92rem;color:var(--text-head);transition:color .3s;}
.nav-brand-sub{font-size:.62rem;font-weight:500;letter-spacing:.10em;text-transform:uppercase;color:var(--text-muted);}
.nav-right{display:flex;align-items:center;gap:10px;}
.admin-badge{display:flex;align-items:center;gap:8px;padding:6px 14px;background:var(--input-bg);border:1px solid var(--border);border-radius:100px;font-size:.80rem;color:var(--text-body);}
.admin-badge .dot{width:8px;height:8px;border-radius:50%;background:var(--green);animation:blink 2s infinite;}
@keyframes blink{0%,100%{opacity:1}50%{opacity:.3}}

/* ── TOGGLE SWITCH ── */
.toggle-wrap{display:flex;align-items:center;gap:6px;cursor:pointer;user-select:none;}
.toggle-wrap .t-icon{font-size:14px;}
.toggle-track{width:44px;height:24px;border-radius:100px;background:#d1d5db;border:1px solid #c0c4cc;position:relative;transition:background .3s,border-color .3s;flex-shrink:0;}
.toggle-track.on{background:var(--accent);border-color:var(--accent);}
.toggle-knob{position:absolute;top:3px;left:3px;width:16px;height:16px;border-radius:50%;background:#fff;box-shadow:0 1px 4px rgba(0,0,0,.25);transition:transform .3s;}
.toggle-track.on .toggle-knob{transform:translateX(20px);}

.btn-logout{padding:7px 16px;border-radius:8px;font-size:.82rem;font-weight:500;cursor:pointer;text-decoration:none;display:inline-flex;align-items:center;gap:5px;font-family:'Inter',sans-serif;background:var(--red-bg);border:1px solid var(--red-border);color:var(--red);transition:all .2s;}
.btn-logout:hover{background:var(--red);color:#fff;border-color:var(--red);}

/* ── LAYOUT ── */
.page{padding-top:64px;flex:1;display:flex;flex-direction:column;}
.main{flex:1;padding:32px 28px 40px;max-width:1100px;margin:0 auto;width:100%;}

/* ── FLASH ── */
.flash{padding:12px 18px;border-radius:10px;margin-bottom:20px;font-size:.85rem;font-weight:500;display:flex;align-items:center;gap:10px;animation:riseUp .4s ease both;}
.flash.success{background:var(--green-bg);border:1px solid var(--green-border);color:var(--green);}
.flash.error  {background:var(--red-bg);  border:1px solid var(--red-border);  color:var(--red);}

/* ── WELCOME ── */
.welcome-banner{background:var(--card);border:1px solid var(--border);border-radius:16px;padding:28px 32px;margin-bottom:28px;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 12px var(--shadow);overflow:hidden;position:relative;animation:riseUp .5s cubic-bezier(.22,1,.36,1) both;}
.welcome-banner::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:linear-gradient(90deg,var(--accent) 0%,var(--gold) 100%);}
.welcome-text h1{font-family:'Playfair Display',serif;font-weight:700;font-size:1.6rem;color:var(--text-head);letter-spacing:-.02em;margin-bottom:4px;}
.welcome-text p{font-size:.85rem;color:var(--text-muted);}
.welcome-text p span{color:var(--gold);font-weight:600;}
.welcome-date{text-align:right;font-size:.78rem;color:var(--text-muted);line-height:1.9;}
.welcome-date .wtime{font-size:1.25rem;font-weight:700;color:var(--text-head);font-variant-numeric:tabular-nums;display:block;letter-spacing:-.5px;}
.welcome-date .wdate{font-size:.80rem;color:var(--text-muted);}
@keyframes riseUp{from{transform:translateY(16px);opacity:0}to{transform:translateY(0);opacity:1}}

/* ── STATS ── */
.stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:28px;}
.stat-card{background:var(--card);border:1px solid var(--border);border-radius:14px;padding:22px 24px;box-shadow:0 2px 10px var(--shadow);display:flex;flex-direction:column;gap:10px;transition:transform .2s,box-shadow .2s;animation:riseUp .5s cubic-bezier(.22,1,.36,1) both;}
.stat-card:nth-child(1){animation-delay:.05s}.stat-card:nth-child(2){animation-delay:.10s}.stat-card:nth-child(3){animation-delay:.15s}.stat-card:nth-child(4){animation-delay:.20s}
.stat-card:hover{transform:translateY(-2px);box-shadow:0 6px 20px var(--shadow);}
.stat-icon{width:38px;height:38px;border-radius:10px;display:flex;align-items:center;justify-content:center;font-size:1.1rem;}
.stat-icon.green{background:var(--green-bg)}.stat-icon.blue{background:var(--blue-bg)}.stat-icon.amber{background:var(--amber-bg)}.stat-icon.red{background:var(--red-bg)}
.stat-label{font-size:.70rem;font-weight:600;letter-spacing:.07em;text-transform:uppercase;color:var(--text-muted);}
.stat-value{font-size:1.8rem;font-weight:700;color:var(--text-head);line-height:1;}
.stat-sub{font-size:.75rem;color:var(--text-muted);}

/* ── GRID ── */
.bottom-grid{display:grid;grid-template-columns:1fr 1.7fr;gap:20px;}
@media(max-width:800px){.bottom-grid{grid-template-columns:1fr;}}
.section-card{background:var(--card);border:1px solid var(--border);border-radius:14px;box-shadow:0 2px 10px var(--shadow);overflow:hidden;animation:riseUp .5s .25s cubic-bezier(.22,1,.36,1) both;}
.section-head{display:flex;align-items:center;justify-content:space-between;padding:16px 20px;border-bottom:1px solid var(--border);}
.section-head h2{font-size:.92rem;font-weight:600;color:var(--text-head);}
.badge{font-size:.68rem;font-weight:600;padding:3px 10px;border-radius:100px;}
.badge-green{background:var(--green-bg);color:var(--green);border:1px solid var(--green-border);}
.badge-blue {background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-border);}

/* ── ACTION BUTTONS ── */
.actions-list{padding:14px 16px;display:flex;flex-direction:column;gap:8px;}
.action-btn{display:flex;align-items:center;gap:12px;padding:12px 16px;border-radius:10px;background:var(--input-bg);border:1px solid var(--input-border);color:var(--text-head);font-size:.85rem;font-weight:500;transition:all .2s;cursor:pointer;width:100%;font-family:'Inter',sans-serif;text-align:left;text-decoration:none;}
.action-btn:hover{background:var(--accent);border-color:var(--accent);color:#fff;transform:translateX(4px);}
.action-btn .icon{width:32px;height:32px;border-radius:8px;background:var(--card);border:1px solid var(--border);display:flex;align-items:center;justify-content:center;font-size:.95rem;flex-shrink:0;transition:background .2s,border-color .2s;}
.action-btn:hover .icon{background:rgba(255,255,255,.15);border-color:transparent;}
.action-btn .arrow{margin-left:auto;opacity:.4;transition:opacity .2s,transform .2s;}
.action-btn:hover .arrow{opacity:1;transform:translateX(3px);}

/* ── EVENTS TABLE ── */
.events-table{width:100%;border-collapse:collapse;}
.events-table th{font-size:.70rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);padding:10px 16px;text-align:left;border-bottom:1px solid var(--border);background:var(--input-bg);}
.events-table td{font-size:.83rem;color:var(--text-body);padding:11px 16px;border-bottom:1px solid var(--border);vertical-align:middle;}
.events-table tr:last-child td{border-bottom:none;}
.events-table tr:hover td{background:var(--input-bg);}
.ev-name{font-weight:600;color:var(--text-head);}
.ev-status{display:inline-flex;align-items:center;gap:4px;font-size:.70rem;font-weight:600;padding:3px 8px;border-radius:100px;}
.ev-status.live    {background:var(--green-bg);color:var(--green);border:1px solid var(--green-border);}
.ev-status.upcoming{background:var(--blue-bg); color:var(--blue); border:1px solid var(--blue-border);}
.ev-status.closed  {background:var(--input-bg);color:var(--text-muted);border:1px solid var(--border);}
.ev-actions{display:flex;gap:5px;}
.btn-sm{padding:4px 9px;border-radius:6px;font-size:.72rem;font-weight:500;cursor:pointer;font-family:'Inter',sans-serif;transition:all .2s;border:1px solid transparent;}
.btn-edit{background:var(--blue-bg);color:var(--blue);border-color:var(--blue-border);}
.btn-edit:hover{background:var(--blue);color:#fff;}
.btn-del {background:var(--red-bg); color:var(--red); border-color:var(--red-border);}
.btn-del:hover {background:var(--red);color:#fff;}

/* ── MODAL ── */
.modal-overlay{position:fixed;inset:0;background:var(--overlay);z-index:500;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;transition:opacity .25s;}
.modal-overlay.open{opacity:1;pointer-events:all;}
.modal{background:var(--card);border:1px solid var(--border);border-radius:16px;width:min(580px,94vw);max-height:90vh;overflow-y:auto;transform:translateY(30px) scale(.97);transition:transform .25s cubic-bezier(.22,1,.36,1);}
.modal-overlay.open .modal{transform:none;}
.modal-header{padding:18px 24px 14px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;position:sticky;top:0;background:var(--card);z-index:2;}
.modal-header h3{font-family:'Playfair Display',serif;font-size:1.12rem;color:var(--text-head);}
.modal-close{background:none;border:none;font-size:1.25rem;cursor:pointer;color:var(--text-muted);line-height:1;padding:2px 6px;border-radius:6px;transition:color .2s,background .2s;}
.modal-close:hover{color:var(--red);background:var(--red-bg);}
.modal-body{padding:20px 24px 24px;}

/* ── FORM ── */
.form-group{margin-bottom:15px;}
.form-group label{display:block;font-size:.75rem;font-weight:600;letter-spacing:.05em;text-transform:uppercase;color:var(--text-muted);margin-bottom:5px;}
.form-control{width:100%;padding:10px 13px;background:var(--input-bg);border:1px solid var(--input-border);border-radius:9px;font-size:.88rem;font-family:'Inter',sans-serif;color:var(--text-head);outline:none;transition:border-color .2s,box-shadow .2s;}
.form-control:focus{border-color:var(--accent);box-shadow:0 0 0 3px rgba(26,39,68,.10);}
textarea.form-control{resize:vertical;min-height:88px;}
.form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
.modal-actions{display:flex;justify-content:flex-end;gap:10px;padding-top:6px;}
.btn-primary{padding:10px 22px;background:var(--accent);color:#fff;border:none;border-radius:9px;font-size:.88rem;font-weight:600;font-family:'Inter',sans-serif;cursor:pointer;transition:background .2s,transform .15s;}
.btn-primary:hover{background:var(--accent-h);transform:translateY(-1px);}
.btn-secondary{padding:10px 18px;background:var(--input-bg);color:var(--text-body);border:1px solid var(--input-border);border-radius:9px;font-size:.88rem;font-weight:500;font-family:'Inter',sans-serif;cursor:pointer;transition:background .2s;}
.btn-secondary:hover{background:var(--border);}

/* ── MANAGE TABLE ── */
.manage-wrap{overflow-x:auto;}
.manage-table{width:100%;border-collapse:collapse;}
.manage-table th{font-size:.68rem;font-weight:600;letter-spacing:.06em;text-transform:uppercase;color:var(--text-muted);padding:9px 12px;text-align:left;border-bottom:1px solid var(--border);background:var(--input-bg);}
.manage-table td{font-size:.81rem;color:var(--text-body);padding:10px 12px;border-bottom:1px solid var(--border);vertical-align:middle;}
.manage-table tr:last-child td{border-bottom:none;}
.manage-table tr:hover td{background:var(--input-bg);}

/* ── REGISTRATIONS ── */
.reg-list{padding:8px 20px;max-height:360px;overflow-y:auto;}
.reg-item{display:flex;align-items:center;justify-content:space-between;padding:11px 0;border-bottom:1px solid var(--border);}
.reg-item:last-child{border-bottom:none;}
.reg-name{font-weight:600;color:var(--text-head);font-size:.84rem;}
.reg-email{font-size:.74rem;color:var(--text-muted);margin-top:2px;}
.reg-event{font-size:.75rem;color:var(--blue);font-weight:500;}
.reg-date{font-size:.70rem;color:var(--text-muted);margin-top:2px;}

/* ── FOOTER ── */
footer{text-align:center;font-size:.74rem;color:var(--text-muted);padding:18px 16px;border-top:1px solid var(--border);background:var(--card);line-height:1.9;transition:background .3s,border-color .3s;}
footer .dev span{color:var(--gold);font-weight:600;}

/* ── EMPTY STATE ── */
.empty{text-align:center;padding:28px;color:var(--text-muted);font-size:.85rem;}
</style>
</head>
<body>

<nav>
  <div class="nav-left">
    <img src="data:image/png;base64,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" alt="ADBU Logo" class="nav-logo">
    <div class="nav-brand">
      <div class="nav-brand-name">Assam Don Bosco University</div>
      <div class="nav-brand-sub">Azara Campus · Student Events · Admin</div>
    </div>
  </div>
  <div class="nav-right">
    <div class="admin-badge">
      <div class="dot"></div>
      👤 <?= $admin_name ?>
    </div>
    <div class="toggle-wrap" id="themeToggle" title="Toggle dark / light mode" role="switch" aria-checked="false" tabindex="0">
      <span class="t-icon">☀️</span>
      <div class="toggle-track" id="toggleTrack">
        <div class="toggle-knob"></div>
      </div>
      <span class="t-icon">🌙</span>
    </div>
    <a href="logout.php" class="btn-logout">🚪 Logout</a>
  </div>
</nav>

<div class="page">
<main class="main">

<?php if ($flash): ?>
<div class="flash <?= htmlspecialchars($flash['type']) ?>">
  <?= $flash['type'] === 'success' ? '✅' : '❌' ?> <?= htmlspecialchars($flash['msg']) ?>
</div>
<?php endif; ?>

  <div class="welcome-banner">
    <div class="welcome-text">
      <h1>Welcome Back, <?= $admin_name ?> 👋</h1>
      <p>You're managing the <span>ADBU Student Events</span> portal. Here's today's overview.</p>
    </div>
    <div class="welcome-date">
      <span class="wtime" id="clockTime">--:--:-- --</span>
      <span class="wdate" id="clockDate">Loading…</span>
    </div>
  </div>

  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-icon green">🎉</div>
      <div class="stat-label">Total Events</div>
      <div class="stat-value"><?= $total_events ?></div>
      <div class="stat-sub">All time</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon blue">👥</div>
      <div class="stat-label">Registrations</div>
      <div class="stat-value"><?= $total_regs ?></div>
      <div class="stat-sub">Total sign-ups</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon amber">📅</div>
      <div class="stat-label">Upcoming</div>
      <div class="stat-value"><?= $upcoming ?></div>
      <div class="stat-sub">Future events</div>
    </div>
    <div class="stat-card">
      <div class="stat-icon red">📋</div>
      <div class="stat-label">Past Events</div>
      <div class="stat-value"><?= $past ?></div>
      <div class="stat-sub">Completed</div>
    </div>
  </div>

  <div class="bottom-grid">

    <div class="section-card">
      <div class="section-head">
        <h2>Quick Actions</h2>
        <span class="badge badge-blue">Admin</span>
      </div>
      <div class="actions-list">
        <button class="action-btn" onclick="openModal('modalCreate')">
          <div class="icon">➕</div><span>Create New Event</span><span class="arrow">→</span>
        </button>
        <button class="action-btn" onclick="openModal('modalManage')">
          <div class="icon">📋</div><span>Manage Events</span><span class="arrow">→</span>
        </button>
        <button class="action-btn" onclick="openModal('modalRegs')">
          <div class="icon">📝</div><span>View Registrations</span><span class="arrow">→</span>
        </button>
        <button class="action-btn" onclick="openModal('modalReports')">
          <div class="icon">📊</div><span>Download Reports</span><span class="arrow">→</span>
        </button>
      </div>
    </div>

    <div class="section-card">
      <div class="section-head">
        <h2>Recent Events</h2>
        <span class="badge badge-green">Live Data</span>
      </div>
      <table class="events-table">
        <thead>
          <tr><th>Event</th><th>Date</th><th>Status</th><th>Actions</th></tr>
        </thead>
        <tbody>
          <?php if (empty($recent_events)): ?>
          <tr><td colspan="4" class="empty">No events yet.</td></tr>
          <?php else: foreach ($recent_events as $ev):
            [$st, $sl] = ev_status($ev['event_date']);
            $data = ev_data($ev);
          ?>
          <tr>
            <td><div class="ev-name"><?= htmlspecialchars($ev['title']) ?></div></td>
            <td><?= date('M j', strtotime($ev['event_date'])) ?></td>
            <td><span class="ev-status <?= $st ?>"><?= $sl ?></span></td>
            <td>
              <div class="ev-actions">
                <button class="btn-sm btn-edit" onclick="openEdit('<?= $data ?>')">✏️ Edit</button>
                <form method="POST" style="display:inline"
                      onsubmit="return confirm('Delete this event and its registrations?')">
                  <input type="hidden" name="action"   value="delete_event">
                  <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
                  <button type="submit" class="btn-sm btn-del">🗑️ Del</button>
                </form>
              </div>
            </td>
          </tr>
          <?php endforeach; endif; ?>
        </tbody>
      </table>
    </div>

  </div></main>

<footer>
  <div>© <?= date('Y') ?> ADBU Student Events System. All Rights Reserved.</div>
  <div class="dev">Developed by <span>Krishna Das</span></div>
</footer>
</div>

<div class="modal-overlay" id="modalCreate">
  <div class="modal">
    <div class="modal-header">
      <h3>➕ Create New Event</h3>
      <button class="modal-close" onclick="closeModal('modalCreate')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="admin_dashboard.php">
        <input type="hidden" name="action" value="create_event">
        <div class="form-group">
          <label>Event Title *</label>
          <input type="text" name="title" class="form-control" placeholder="e.g. TechFest 2026" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" class="form-control" placeholder="Brief description…"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Date &amp; Time *</label>
            <input type="datetime-local" name="event_date" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Capacity (0 = unlimited)</label>
            <input type="number" name="capacity" class="form-control" placeholder="0" min="0" value="0">
          </div>
        </div>
        <div class="form-group">
          <label>Location / Venue</label>
          <input type="text" name="location" class="form-control" placeholder="e.g. Main Auditorium">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeModal('modalCreate')">Cancel</button>
          <button type="submit" class="btn-primary">✅ Create Event</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalEdit">
  <div class="modal">
    <div class="modal-header">
      <h3>✏️ Edit Event</h3>
      <button class="modal-close" onclick="closeModal('modalEdit')">✕</button>
    </div>
    <div class="modal-body">
      <form method="POST" action="admin_dashboard.php">
        <input type="hidden" name="action"   value="edit_event">
        <input type="hidden" name="event_id" id="eId">
        <div class="form-group">
          <label>Event Title *</label>
          <input type="text" name="title" id="eTitle" class="form-control" required>
        </div>
        <div class="form-group">
          <label>Description</label>
          <textarea name="description" id="eDesc" class="form-control"></textarea>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label>Date &amp; Time *</label>
            <input type="datetime-local" name="event_date" id="eDate" class="form-control" required>
          </div>
          <div class="form-group">
            <label>Capacity</label>
            <input type="number" name="capacity" id="eCap" class="form-control" min="0">
          </div>
        </div>
        <div class="form-group">
          <label>Location</label>
          <input type="text" name="location" id="eLoc" class="form-control">
        </div>
        <div class="modal-actions">
          <button type="button" class="btn-secondary" onclick="closeModal('modalEdit')">Cancel</button>
          <button type="submit" class="btn-primary">💾 Save Changes</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalManage">
  <div class="modal" style="width:min(820px,96vw)">
    <div class="modal-header">
      <h3>📋 Manage All Events</h3>
      <button class="modal-close" onclick="closeModal('modalManage')">✕</button>
    </div>
    <div class="modal-body" style="padding:0">
      <div class="manage-wrap">
        <table class="manage-table">
          <thead>
            <tr><th>#</th><th>Title</th><th>Date</th><th>Location</th><th>Cap.</th><th>Status</th><th>Actions</th></tr>
          </thead>
          <tbody>
            <?php if (empty($all_events)): ?>
            <tr><td colspan="7" class="empty">No events.</td></tr>
            <?php else: foreach ($all_events as $i => $ev):
              [$st, $sl] = ev_status($ev['event_date']);
              $data = ev_data($ev);
            ?>
            <tr>
              <td style="color:var(--text-muted)"><?= $i+1 ?></td>
              <td style="font-weight:600;color:var(--text-head)"><?= htmlspecialchars($ev['title']) ?></td>
              <td><?= date('d M Y, H:i', strtotime($ev['event_date'])) ?></td>
              <td><?= htmlspecialchars($ev['location'] ?? '—') ?></td>
              <td><?= $ev['capacity'] ? (int)$ev['capacity'] : '∞' ?></td>
              <td><span class="ev-status <?= $st ?>"><?= $sl ?></span></td>
              <td>
                <div class="ev-actions">
                  <button class="btn-sm btn-edit" onclick="openEdit('<?= $data ?>')">✏️ Edit</button>
                  <form method="POST" action="admin_dashboard.php" style="display:inline"
                        onsubmit="return confirm('Delete this event?')">
                    <input type="hidden" name="action"   value="delete_event">
                    <input type="hidden" name="event_id" value="<?= (int)$ev['id'] ?>">
                    <button type="submit" class="btn-sm btn-del">🗑️</button>
                  </form>
                </div>
              </td>
            </tr>
            <?php endforeach; endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalRegs">
  <div class="modal" style="width:min(680px,96vw)">
    <div class="modal-header">
      <h3>📝 All Registrations</h3>
      <button class="modal-close" onclick="closeModal('modalRegs')">✕</button>
    </div>
    <div class="modal-body" style="padding:0">
      <div class="reg-list">
        <?php if (empty($regs)): ?>
          <p class="empty">No registrations yet.</p>
        <?php else: foreach ($regs as $r): ?>
        <div class="reg-item">
          <div>
            <div class="reg-name"><?= htmlspecialchars($r['uname'] ?? 'Unknown') ?></div>
            <div class="reg-email"><?= htmlspecialchars($r['email'] ?? '') ?></div>
          </div>
          <div style="text-align:right">
            <div class="reg-event"><?= htmlspecialchars($r['ev_title'] ?? '—') ?></div>
            <div class="reg-date"><?= !empty($r['created_at']) ? date('d M Y, H:i', strtotime($r['created_at'])) : 'Registered' ?></div>
          </div>
        </div>
        <?php endforeach; endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="modal-overlay" id="modalReports">
  <div class="modal">
    <div class="modal-header">
      <h3>📊 Download Reports</h3>
      <button class="modal-close" onclick="closeModal('modalReports')">✕</button>
    </div>
    <div class="modal-body">
      <p style="font-size:.85rem;color:var(--text-muted);margin-bottom:18px;">Export data as CSV files.</p>
      <div style="display:flex;flex-direction:column;gap:10px;">
        <a href="export.php?type=events"        class="action-btn"><div class="icon">🎉</div><span>Export All Events (CSV)</span><span class="arrow">→</span></a>
        <a href="export.php?type=registrations" class="action-btn"><div class="icon">📋</div><span>Export Registrations (CSV)</span><span class="arrow">→</span></a>
        <a href="export.php?type=users"         class="action-btn"><div class="icon">👥</div><span>Export Users List (CSV)</span><span class="arrow">→</span></a>
      </div>
      <div class="modal-actions" style="margin-top:18px">
        <button class="btn-secondary" onclick="closeModal('modalReports')">Close</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ══════════════════════════════════════
   1.  LIVE CLOCK  — runs every second
   ══════════════════════════════════════ */
function tickClock() {
  var now  = new Date();
  var h    = now.getHours(), m = now.getMinutes(), s = now.getSeconds();
  var ampm = h >= 12 ? 'pm' : 'am';
  h = h % 12 || 12;
  var pad  = function(n){ return String(n).padStart(2,'0'); };
  document.getElementById('clockTime').textContent = pad(h)+':'+pad(m)+':'+pad(s)+' '+ampm;

  var days   = ['Sunday','Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'];
  var months = ['January','February','March','April','May','June','July','August','September','October','November','December'];
  document.getElementById('clockDate').textContent =
    days[now.getDay()]+', '+now.getDate()+' '+months[now.getMonth()]+' '+now.getFullYear();
}
tickClock();
setInterval(tickClock, 1000);

/* ══════════════════════════════════════
   2.  DARK / LIGHT TOGGLE
   ══════════════════════════════════════ */
var html    = document.documentElement;
var track   = document.getElementById('toggleTrack');
var toggleEl= document.getElementById('themeToggle');

function applyTheme(theme) {
  html.setAttribute('data-theme', theme);
  if (theme === 'dark') {
    track.classList.add('on');
    toggleEl.setAttribute('aria-checked','true');
  } else {
    track.classList.remove('on');
    toggleEl.setAttribute('aria-checked','false');
  }
  localStorage.setItem('adbu-admin-theme', theme);
}

// Restore saved theme immediately (before paint)
applyTheme(localStorage.getItem('adbu-admin-theme') || 'light');

toggleEl.addEventListener('click', function(){
  applyTheme(html.getAttribute('data-theme') === 'dark' ? 'light' : 'dark');
});
toggleEl.addEventListener('keydown', function(e){
  if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); toggleEl.click(); }
});

/* ══════════════════════════════════════
   3.  MODAL HELPERS
   ══════════════════════════════════════ */
function openModal(id) {
  document.getElementById(id).classList.add('open');
  document.body.style.overflow = 'hidden';
}
function closeModal(id) {
  document.getElementById(id).classList.remove('open');
  document.body.style.overflow = '';
}

// Backdrop click closes
document.querySelectorAll('.modal-overlay').forEach(function(ov){
  ov.addEventListener('click', function(e){
    if (e.target === ov) closeModal(ov.id);
  });
});

// Escape key closes
document.addEventListener('keydown', function(e){
  if (e.key === 'Escape') {
    document.querySelectorAll('.modal-overlay.open').forEach(function(m){ closeModal(m.id); });
  }
});

/* ══════════════════════════════════════
   4.  OPEN EDIT MODAL — safely decode
   ══════════════════════════════════════ */
function openEdit(encoded) {
  // encoded is an HTML-entity-escaped JSON string passed via onclick attribute
  var txt  = document.createElement('textarea');
  txt.innerHTML = encoded;           // browser decodes HTML entities
  var data = JSON.parse(txt.value);  // now parse clean JSON

  document.getElementById('eId').value    = data.id;
  document.getElementById('eTitle').value = data.title;
  document.getElementById('eDesc').value  = data.description || '';
  document.getElementById('eDate').value  = data.event_date  || '';
  document.getElementById('eCap').value   = data.capacity    || 0;
  document.getElementById('eLoc').value   = data.location    || '';

  // Close any open modal first, then open edit
  document.querySelectorAll('.modal-overlay.open').forEach(function(m){ closeModal(m.id); });
  openModal('modalEdit');
}
</script>
</body>
</html>