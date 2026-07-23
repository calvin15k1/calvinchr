<?php
// Admin panel — protected by session auth
require_once __DIR__ . '/../php/auth.php';
requireLogin('login.php');   // redirects to login.php if not authenticated

$siteUrl     = 'http://localhost/calvin_portfolio';
$apiBase     = $siteUrl . '/php/';
$uploadsBase = $siteUrl . '/uploads/';
$adminUser   = htmlspecialchars($_SESSION['admin_user'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin — Calvin Christian Portfolio</title>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500&family=Space+Mono&display=swap" rel="stylesheet" />
  <style>
    :root {
      --ink:#0d0d0d;--ivory:#f0ede6;--gold:#c9a84c;
      --teal:#1a3d3a;--ash:#666;--red:#e74c3c;
      --green:#27ae60;--border:rgba(240,237,230,0.1);
    }
    *,*::before,*::after{box-sizing:border-box;margin:0;padding:0}
    body{background:#111;color:var(--ivory);font-family:'Inter',sans-serif;font-size:14px;min-height:100vh}
    a{color:var(--gold);text-decoration:none}
    button{cursor:pointer;font:inherit}
    input,textarea,select{
      background:rgba(255,255,255,.05);border:1px solid var(--border);
      color:var(--ivory);font:inherit;padding:.6rem .9rem;
      border-radius:4px;outline:none;width:100%;transition:border-color .2s
    }
    input:focus,textarea:focus,select:focus{border-color:var(--gold)}
    select option{background:#222}

    /* LAYOUT */
    .admin-layout{display:grid;grid-template-columns:220px 1fr;min-height:100vh}
    .sidebar{
      background:#0d0d0d;border-right:1px solid var(--border);
      padding:2rem 1.25rem;display:flex;flex-direction:column;gap:.5rem;
      position:sticky;top:0;height:100vh;overflow-y:auto
    }
    .sidebar-logo{
      font-family:'Space Mono',monospace;font-size:1rem;
      color:var(--gold);letter-spacing:.2em;margin-bottom:1.5rem;
      padding-bottom:1.25rem;border-bottom:1px solid var(--border)
    }
    .sidebar-logo small{display:block;font-size:.65rem;color:var(--ash);letter-spacing:.1em;margin-top:.25rem}
    .nav-item{
      padding:.6rem .9rem;border-radius:4px;
      color:var(--ash);cursor:pointer;transition:all .2s;font-size:.85rem
    }
    .nav-item:hover,.nav-item.active{background:rgba(201,168,76,.1);color:var(--gold)}
    .sidebar-bottom{margin-top:auto;padding-top:1rem;border-top:1px solid var(--border);display:flex;flex-direction:column;gap:.6rem}
    .logged-in-user{
      display:flex;align-items:center;gap:.6rem;
      padding:.5rem .9rem;
      background:rgba(201,168,76,.07);
      border:1px solid rgba(201,168,76,.15);
      border-radius:4px;
    }
    .user-icon{color:var(--gold);font-size:.8rem}
    .user-name{font-family:'Space Mono',monospace;font-size:.72rem;letter-spacing:.08em;color:var(--ivory)}
    .back-link{font-size:.75rem;color:var(--ash);padding:.35rem .5rem}
    .back-link:hover{color:var(--ivory)}
    .logout-btn{
      display:block;padding:.55rem .9rem;
      font-family:'Space Mono',monospace;font-size:.72rem;letter-spacing:.1em;text-transform:uppercase;
      color:var(--red);border:1px solid rgba(224,82,82,.25);border-radius:4px;
      background:rgba(224,82,82,.06);transition:all .2s;cursor:pointer;text-decoration:none;text-align:center
    }
    .logout-btn:hover{background:rgba(224,82,82,.18);border-color:rgba(224,82,82,.5);color:#ff7070}

    /* MAIN */
    .main{padding:2rem;overflow-y:auto}
    .page{display:none}
    .page.active{display:block}
    h1{font-size:1.5rem;font-weight:400;margin-bottom:1.5rem;color:var(--ivory)}
    h2{font-size:1.1rem;font-weight:500;margin-bottom:1rem;color:var(--ivory)}

    /* CARDS */
    .card{background:rgba(255,255,255,.03);border:1px solid var(--border);border-radius:8px;padding:1.5rem;margin-bottom:1.5rem}
    .stats-row{display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem}
    .stat-card{background:rgba(201,168,76,.05);border:1px solid rgba(201,168,76,.2);border-radius:6px;padding:1.25rem}
    .stat-card .num{font-size:2rem;font-weight:300;color:var(--gold);line-height:1}
    .stat-card .label{font-size:.7rem;letter-spacing:.12em;text-transform:uppercase;color:var(--ash);margin-top:.4rem}

    /* FORMS */
    .form-grid{display:grid;grid-template-columns:1fr 1fr;gap:1rem}
    .form-group{display:flex;flex-direction:column;gap:.35rem}
    .form-group.full{grid-column:1/-1}
    .form-group label{font-size:.75rem;color:var(--ash);letter-spacing:.08em;text-transform:uppercase}
    .btn{
      padding:.6rem 1.5rem;border-radius:4px;border:none;
      font-size:.8rem;letter-spacing:.1em;text-transform:uppercase;
      transition:all .2s;font-family:'Space Mono',monospace;cursor:pointer
    }
    .btn-primary{background:var(--gold);color:var(--ink)}
    .btn-primary:hover{background:#b8922f}
    .btn-danger{background:rgba(231,76,60,.15);color:var(--red);border:1px solid rgba(231,76,60,.3)}
    .btn-danger:hover{background:rgba(231,76,60,.3)}
    .btn-sm{padding:.35rem .9rem;font-size:.7rem}
    .btn-group{display:flex;gap:.5rem;align-items:center;margin-top:1rem;flex-wrap:wrap}

    /* UPLOAD ZONE */
    .upload-zone{
      border:2px dashed rgba(201,168,76,.35);border-radius:8px;
      padding:2.5rem 2rem;text-align:center;cursor:pointer;
      transition:border-color .2s,background .2s;margin-top:1.25rem
    }
    .upload-zone:hover{border-color:var(--gold);background:rgba(201,168,76,.04)}
    .upload-zone.dragover{border-color:var(--gold);background:rgba(201,168,76,.08)}
    .upload-zone input[type=file]{display:none}
    .upload-zone .icon{font-size:2.5rem;margin-bottom:.75rem;color:var(--gold)}
    .upload-zone p{color:var(--ash);font-size:.9rem;line-height:1.6}
    .upload-zone small{color:#555;font-size:.78rem}
    #fileChosen{margin-top:.75rem;color:var(--gold);font-size:.82rem;word-break:break-all}

    /* PROGRESS */
    .progress-wrap{margin-top:.75rem;display:none}
    .progress-bar{height:5px;background:rgba(255,255,255,.1);border-radius:3px;overflow:hidden}
    .progress-fill{height:100%;background:var(--gold);width:0;transition:width .2s}
    .progress-label{font-size:.75rem;color:var(--ash);margin-top:.35rem;text-align:right}

    /* TABLE */
    .table-wrap{overflow-x:auto}
    table{width:100%;border-collapse:collapse;font-size:.85rem}
    th{text-align:left;padding:.6rem .75rem;font-size:.7rem;letter-spacing:.1em;text-transform:uppercase;color:var(--ash);border-bottom:1px solid var(--border)}
    td{padding:.75rem;border-bottom:1px solid rgba(240,237,230,.05);vertical-align:middle}
    tr:hover td{background:rgba(255,255,255,.02)}
    .thumb{width:64px;height:44px;object-fit:cover;border-radius:3px;background:#1a1a1a;display:block}
    .video-thumb{
      width:64px;height:44px;object-fit:cover;border-radius:3px;background:#1a1a1a;
      display:flex;align-items:center;justify-content:center;
      color:var(--gold);font-size:1.2rem;
    }
    .badge{
      display:inline-block;padding:.2rem .6rem;border-radius:3px;font-size:.65rem;
      letter-spacing:.1em;text-transform:uppercase;font-family:'Space Mono',monospace
    }
    .badge-photo{background:rgba(26,61,58,.5);color:#6fcfc7}
    .badge-video{background:rgba(201,168,76,.15);color:var(--gold)}
    .badge-feat{background:rgba(39,174,96,.15);color:var(--green)}

    /* MESSAGES */
    .msg-item{border:1px solid var(--border);border-radius:6px;padding:1rem 1.25rem;margin-bottom:.75rem;transition:border-color .2s}
    .msg-item:hover{border-color:rgba(201,168,76,.3)}
    .msg-item.unread{border-left:3px solid var(--gold)}
    .msg-meta{display:flex;gap:1rem;align-items:center;margin-bottom:.5rem;flex-wrap:wrap}
    .msg-name{font-weight:500}
    .msg-email{color:var(--ash);font-size:.8rem}
    .msg-date{color:var(--ash);font-size:.75rem;margin-left:auto}
    .msg-subject{font-size:.8rem;color:var(--gold);margin-bottom:.4rem}
    .msg-body{font-size:.85rem;color:rgba(240,237,230,.7);line-height:1.6;white-space:pre-wrap}

    /* ALERT */
    .alert{padding:.75rem 1rem;border-radius:4px;margin-bottom:1rem;font-size:.85rem;line-height:1.5}
    .alert code{background:rgba(255,255,255,.1);padding:.1rem .4rem;border-radius:3px;font-family:'Space Mono',monospace;font-size:.8rem}
    .alert-success{background:rgba(39,174,96,.12);border:1px solid rgba(39,174,96,.3);color:var(--green)}
    .alert-error{background:rgba(231,76,60,.12);border:1px solid rgba(231,76,60,.3);color:var(--red)}
    .alert-info{background:rgba(201,168,76,.08);border:1px solid rgba(201,168,76,.25);color:var(--gold)}

    /* PATH DEBUG */
    .path-debug{
      background:rgba(255,255,255,.03);border:1px solid rgba(255,255,255,.07);
      border-radius:6px;padding:1rem 1.25rem;margin-top:1rem;font-size:.8rem
    }
    .path-debug dt{color:var(--ash);font-size:.7rem;letter-spacing:.08em;text-transform:uppercase;margin-top:.5rem}
    .path-debug dd{color:var(--ivory);font-family:'Space Mono',monospace;font-size:.75rem;margin-top:.2rem;word-break:break-all}

    @media(max-width:768px){
      .admin-layout{grid-template-columns:1fr}
      .sidebar{position:static;height:auto;flex-direction:row;flex-wrap:wrap;padding:1rem}
      .stats-row{grid-template-columns:1fr 1fr}
      .form-grid{grid-template-columns:1fr}
    }
  </style>
</head>
<body>
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="sidebar">
    <div>
      <div class="sidebar-logo">CC<small>Portfolio Admin</small></div>
      <div class="nav-item active"  onclick="showPage('dashboard',this)">📊 Dashboard</div>
      <div class="nav-item"         onclick="showPage('analytics',this)">📈 Analytics</div>
      <div class="nav-item"         onclick="showPage('bookings',this)">📅 Bookings</div>
      <div class="nav-item"         onclick="showPage('upload',this)">⬆ Upload Media</div>
      <div class="nav-item"         onclick="showPage('projects',this)">🎬 Projects</div>
      <div class="nav-item"         onclick="showPage('media',this)">🖼 All Media</div>
      <div class="nav-item"         onclick="showPage('messages',this)">✉ Messages</div>
    </div>
    <div class="sidebar-bottom">
      <div class="logged-in-user">
        <span class="user-icon">◉</span>
        <span class="user-name"><?= $adminUser ?></span>
      </div>
      <a href="../index.html" class="back-link">← Back to Portfolio</a>
      <a href="../php/api_logout.php" class="logout-btn" onclick="return confirm('Log out?')">⏻ Log Out</a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="main">

    <!-- ═══ BOOKINGS ═══ -->
    <div class="page" id="page-bookings">
      <h1>Bookings</h1>
      <div id="bookingAlert"></div>
      <div class="stats-row" style="grid-template-columns:repeat(4,1fr);margin-bottom:1.5rem">
        <div class="stat-card"><div class="num" id="bkTotal">—</div><div class="label">Total</div></div>
        <div class="stat-card"><div class="num" id="bkPending">—</div><div class="label">Pending</div></div>
        <div class="stat-card"><div class="num" id="bkConfirmed">—</div><div class="label">Confirmed</div></div>
        <div class="stat-card"><div class="num" id="bkToday">—</div><div class="label">Today</div></div>
      </div>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr>
              <th>ID</th><th>Service</th><th>Client</th><th>Date / Time</th>
              <th>Delivery</th><th>Payment</th><th>Status</th><th>Calendar</th><th>Actions</th>
            </tr></thead>
            <tbody id="bookingsTable"><tr><td colspan="9" style="color:var(--ash)">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ BOOKINGS ═══ -->
    <div class="page" id="page-bookings">
      <h1>Bookings</h1>
      <p style="color:var(--ash);font-size:.85rem;margin-bottom:1.5rem">All client bookings. Confirmed bookings block those time slots from new reservations.</p>

      <div style="display:grid;grid-template-columns:repeat(4,1fr);gap:1rem;margin-bottom:1.5rem">
        <div class="stat-card"><div class="num" id="bkTotal">—</div><div class="label">Total Bookings</div></div>
        <div class="stat-card"><div class="num" id="bkPending">—</div><div class="label">Pending</div></div>
        <div class="stat-card"><div class="num" id="bkConfirmed">—</div><div class="label">Confirmed</div></div>
        <div class="stat-card"><div class="num" id="bkUpcoming">—</div><div class="label">Upcoming</div></div>
      </div>

      <div class="card">
        <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1rem;flex-wrap:wrap;gap:.75rem">
          <h2 style="margin:0">All Bookings</h2>
          <div style="display:flex;gap:.5rem;flex-wrap:wrap">
            <button class="btn btn-sm" style="border:1px solid var(--border);color:var(--ash)" onclick="filterBookings('all')" id="bkf-all">All</button>
            <button class="btn btn-sm" style="border:1px solid var(--border);color:var(--ash)" onclick="filterBookings('photography')" id="bkf-photography">📷</button>
            <button class="btn btn-sm" style="border:1px solid var(--border);color:var(--ash)" onclick="filterBookings('videography')" id="bkf-videography">🎥</button>
            <button class="btn btn-sm" style="border:1px solid var(--border);color:var(--ash)" onclick="filterBookings('editing')" id="bkf-editing">✂️</button>
            <button class="btn btn-sm" style="border:1px solid var(--border);color:var(--ash)" onclick="filterBookings('pending')" id="bkf-pending">Pending</button>
          </div>
        </div>
        <div class="table-wrap">
          <table>
            <thead><tr>
              <th>#</th><th>Client</th><th>Service</th>
              <th>Date / Time</th><th>Price</th><th>Payment</th>
              <th>Status</th><th>Calendar</th><th>Actions</th>
            </tr></thead>
            <tbody id="bookingsTable"><tr><td colspan="9" style="color:var(--ash)">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ ANALYTICS ═══ -->
    <div class="page" id="page-analytics">
      <h1>Analytics</h1>
      <p style="color:var(--ash);font-size:.85rem;margin-bottom:1.5rem">Live visitor data tracked from your portfolio and booking pages.</p>

      <!-- Stat cards -->
      <div class="stats-row" id="analyticsStats">
        <div class="stat-card"><div class="num" id="aToday">—</div><div class="label">Visits Today</div></div>
        <div class="stat-card"><div class="num" id="aWeek">—</div><div class="label">This Week</div></div>
        <div class="stat-card"><div class="num" id="aMonth">—</div><div class="label">Last 30 Days</div></div>
        <div class="stat-card"><div class="num" id="aTotal">—</div><div class="label">All Time</div></div>
      </div>

      <!-- Secondary stats -->
      <div style="display:grid;grid-template-columns:repeat(3,1fr);gap:1rem;margin-bottom:1.5rem" id="analyticsSecondary">
        <div class="stat-card"><div class="num" id="aUniqueToday">—</div><div class="label">Unique Today</div></div>
        <div class="stat-card"><div class="num" id="aUniqueWeek">—</div><div class="label">Unique This Week</div></div>
        <div class="stat-card"><div class="num" id="aBooking">—</div><div class="label">Booking Page Visits</div></div>
      </div>

      <div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;align-items:start">

        <!-- Chart -->
        <div class="card">
          <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:1.25rem">
            <h2 style="margin:0">Daily Visits — Last 30 Days</h2>
            <span id="aPeakHour" style="font-size:.72rem;color:var(--ash);font-family:'Space Mono',monospace"></span>
          </div>
          <div style="position:relative;height:200px;width:100%" id="chartWrap">
            <canvas id="visitsChart" style="width:100%;height:100%"></canvas>
            <div id="chartEmpty" style="display:none;text-align:center;padding-top:4rem;color:var(--ash);font-size:.85rem">No data yet — visits will appear here once people visit your site.</div>
          </div>
        </div>

        <!-- Top pages -->
        <div class="card">
          <h2>Top Pages</h2>
          <div id="topPages" style="display:flex;flex-direction:column;gap:.5rem">
            <div style="color:var(--ash);font-size:.82rem">Loading…</div>
          </div>
        </div>

      </div>

      <!-- Today hourly -->
      <div class="card" style="margin-top:1.5rem">
        <h2>Today by Hour</h2>
        <div style="position:relative;height:120px" id="hourlyWrap">
          <canvas id="hourlyChart" style="width:100%;height:100%"></canvas>
          <div id="hourlyEmpty" style="display:none;text-align:center;padding-top:2.5rem;color:var(--ash);font-size:.82rem">No visits recorded today yet.</div>
        </div>
      </div>

      <p style="margin-top:1rem;font-size:.72rem;color:#444;font-family:'Space Mono',monospace">
        ℹ IPs are hashed daily for visitor privacy. No personal data is stored.
      </p>
    </div>

    <!-- ═══ DASHBOARD ═══ -->
    <div class="page active" id="page-dashboard">
      <h1>Dashboard</h1>
      <div class="stats-row">
        <div class="stat-card"><div class="num" id="statPhotos">—</div><div class="label">Photos</div></div>
        <div class="stat-card"><div class="num" id="statVideos">—</div><div class="label">Videos</div></div>
        <div class="stat-card"><div class="num" id="statProjects">—</div><div class="label">Projects</div></div>
        <div class="stat-card"><div class="num" id="statMessages">—</div><div class="label">Messages</div></div>
      </div>
      <div class="card">
        <h2>Database &amp; Server Status</h2>
        <div id="dbStatus" style="color:var(--ash);font-size:.9rem">Checking…</div>
        <div class="path-debug" id="pathDebug" style="display:none">
          <dl>
            <dt>Project URL</dt><dd id="debugSiteUrl"></dd>
            <dt>API Base</dt><dd id="debugApiBase"></dd>
            <dt>Uploads URL</dt><dd id="debugUploadsUrl"></dd>
          </dl>
        </div>
      </div>
      <div class="card">
        <h2>Quick Actions</h2>
        <div class="btn-group">
          <button class="btn btn-primary" onclick="showPage('upload',document.querySelectorAll('.nav-item')[1])">Upload Media</button>
          <button class="btn btn-primary" onclick="showPage('projects',document.querySelectorAll('.nav-item')[2])">Add Project</button>
          <button class="btn btn-primary" onclick="showPage('messages',document.querySelectorAll('.nav-item')[4])">View Messages</button>
        </div>
      </div>
    </div>

    <!-- ═══ UPLOAD MEDIA ═══ -->
    <div class="page" id="page-upload">
      <h1>Upload Media</h1>
      <div id="uploadAlert"></div>

      <div class="card">
        <div class="form-grid">
          <div class="form-group">
            <label>Title</label>
            <input type="text" id="uploadTitle" placeholder="e.g. Golden Hour at Tanah Lot" />
          </div>
          <div class="form-group">
            <label>Category</label>
            <input type="text" id="uploadCategory" placeholder="landscape / wedding / commercial…" />
          </div>
          <div class="form-group full">
            <label>Description (optional)</label>
            <textarea id="uploadDesc" rows="2" placeholder="Short description of this media…"></textarea>
          </div>
          <div class="form-group">
            <label>Media Type</label>
            <select id="uploadType">
              <option value="photo">📷 Photo</option>
              <option value="video">🎥 Video</option>
            </select>
          </div>
          <div class="form-group">
            <label>Show in Carousel?</label>
            <select id="uploadFeatured">
              <option value="0">No</option>
              <option value="1">Yes — Featured</option>
            </select>
          </div>
        </div>

        <!-- Drop zone -->
        <div class="upload-zone" id="uploadZone">
          <input type="file" id="fileInput" accept="image/jpeg,image/png,image/webp,image/gif,video/mp4,video/quicktime,video/webm,video/avi" />
          <div class="icon">☁</div>
          <p>Click to choose a file, or drag &amp; drop here</p>
          <small>Photos: JPG, PNG, WebP, GIF — max 20 MB<br/>Videos: MP4, MOV, WebM — max 500 MB</small>
          <p id="fileChosen"></p>
        </div>

        <!-- Progress -->
        <div class="progress-wrap" id="progressWrap">
          <div class="progress-bar"><div class="progress-fill" id="progressFill"></div></div>
          <div class="progress-label" id="progressLabel">0%</div>
        </div>

        <div class="btn-group">
          <button class="btn btn-primary" id="uploadBtn" onclick="doUpload()">Upload File</button>
          <span id="uploadStatus" style="font-size:.8rem;color:var(--ash)"></span>
        </div>
      </div>
    </div>

    <!-- ═══ PROJECTS ═══ -->
    <div class="page" id="page-projects">
      <h1>Projects</h1>
      <div id="projectAlert"></div>

      <div class="card">
        <h2>Add New Project</h2>
        <div class="form-grid">
          <div class="form-group">
            <label>Title *</label>
            <input type="text" id="projTitle" placeholder="Bali Cinematic Series" />
          </div>
          <div class="form-group">
            <label>Subtitle</label>
            <input type="text" id="projSubtitle" placeholder="Travel &amp; Culture" />
          </div>
          <div class="form-group full">
            <label>Description</label>
            <textarea id="projDesc" rows="3" placeholder="Describe the project…"></textarea>
          </div>
          <div class="form-group">
            <label>Category *</label>
            <select id="projCategory">
              <option value="photography">Photography</option>
              <option value="videography">Videography</option>
              <option value="editing">Editing</option>
            </select>
          </div>
          <div class="form-group">
            <label>Client</label>
            <input type="text" id="projClient" placeholder="Client name or Personal Project" />
          </div>
          <div class="form-group">
            <label>Year</label>
            <input type="number" id="projYear" value="2026" min="2000" max="2099" />
          </div>
          <div class="form-group">
            <label>Cover Image Path</label>
            <input type="text" id="projCover" placeholder="uploads/photos/yourfile.jpg" />
            <small style="color:var(--ash);font-size:.72rem">Relative path from project root. Upload the photo first, then paste the path shown after upload.</small>
          </div>
          <div class="form-group">
            <label>Video Path / URL (optional)</label>
            <input type="text" id="projVideo" placeholder="uploads/videos/yourfilm.mp4" />
          </div>
          <div class="form-group">
            <label>Featured in Carousel?</label>
            <select id="projFeatured">
              <option value="0">No</option>
              <option value="1">Yes</option>
            </select>
          </div>
        </div>
        <div class="btn-group">
          <button class="btn btn-primary" onclick="saveProject()">Save Project</button>
        </div>
      </div>

      <div class="card">
        <h2>All Projects</h2>
        <div class="table-wrap">
          <table>
            <thead><tr><th>Title</th><th>Category</th><th>Client</th><th>Year</th><th>Featured</th><th>Action</th></tr></thead>
            <tbody id="projectsTable"><tr><td colspan="6" style="color:var(--ash)">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ ALL MEDIA ═══ -->
    <div class="page" id="page-media">
      <h1>All Media</h1>
      <div class="card">
        <div class="table-wrap">
          <table>
            <thead><tr><th>Preview</th><th>Title</th><th>Type</th><th>Category</th><th>Path</th><th>Featured</th><th>Action</th></tr></thead>
            <tbody id="mediaTable"><tr><td colspan="7" style="color:var(--ash)">Loading…</td></tr></tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- ═══ MESSAGES ═══ -->
    <div class="page" id="page-messages">
      <h1>Contact Messages</h1>
      <div id="messagesList" style="margin-top:1rem">Loading…</div>
    </div>

  </main>
</div>

<script>
// ── Injected by PHP so paths are always correct ──────────
const SITE_URL    = '<?= $siteUrl ?>';
const API         = '<?= $apiBase ?>';      // http://localhost/calvin_portfolio/php/
const UPLOADS_URL = '<?= $uploadsBase ?>';  // http://localhost/calvin_portfolio/uploads/

// Debug
document.getElementById('debugSiteUrl').textContent   = SITE_URL;
document.getElementById('debugApiBase').textContent   = API;
document.getElementById('debugUploadsUrl').textContent = UPLOADS_URL;

/* ════════════════════
   PAGE NAVIGATION
════════════════════ */
function showPage(name, el) {
  document.querySelectorAll('.page').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.nav-item').forEach(n => n.classList.remove('active'));
  const page = document.getElementById('page-' + name);
  if (page) page.classList.add('active');
  if (el)   el.classList.add('active');

  if (name === 'dashboard') loadDashboard();
  if (name === 'analytics') loadAnalytics();
  if (name === 'bookings')  loadBookings();
  if (name === 'media')     loadMedia();
  if (name === 'projects')  loadProjects();
  if (name === 'messages')  loadMessages();
}

/* ════════════════════
   FILE INPUT & DRAG
════════════════════ */
const fileInput  = document.getElementById('fileInput');
const uploadZone = document.getElementById('uploadZone');

uploadZone.addEventListener('click', () => fileInput.click());

fileInput.addEventListener('change', () => updateFileChosen(fileInput.files[0]));

uploadZone.addEventListener('dragover', e => { e.preventDefault(); uploadZone.classList.add('dragover'); });
uploadZone.addEventListener('dragleave', () => uploadZone.classList.remove('dragover'));
uploadZone.addEventListener('drop', e => {
  e.preventDefault();
  uploadZone.classList.remove('dragover');
  const dt = e.dataTransfer;
  if (dt.files.length) {
    // Create a DataTransfer to assign to the input
    try {
      const transfer = new DataTransfer();
      transfer.items.add(dt.files[0]);
      fileInput.files = transfer.files;
    } catch(e) {}
    updateFileChosen(dt.files[0]);
  }
});

function updateFileChosen(file) {
  if (!file) return;
  const mb = (file.size / 1024 / 1024).toFixed(2);
  document.getElementById('fileChosen').textContent = `✓ ${file.name}  (${mb} MB)`;
  // Auto-detect type
  if (file.type.startsWith('video/')) {
    document.getElementById('uploadType').value = 'video';
  } else {
    document.getElementById('uploadType').value = 'photo';
  }
}

/* ════════════════════
   UPLOAD
════════════════════ */
async function doUpload() {
  const file     = fileInput.files[0];
  const alertEl  = document.getElementById('uploadAlert');
  const btn      = document.getElementById('uploadBtn');
  const status   = document.getElementById('uploadStatus');
  const progress = document.getElementById('progressWrap');
  const fill     = document.getElementById('progressFill');
  const label    = document.getElementById('progressLabel');

  alertEl.innerHTML = '';

  if (!file) {
    alertEl.innerHTML = `<div class="alert alert-error">⚠ Please choose a file first.</div>`;
    return;
  }

  const fd = new FormData();
  fd.append('file',        file);
  fd.append('type',        document.getElementById('uploadType').value);
  fd.append('title',       document.getElementById('uploadTitle').value.trim() || file.name.replace(/\.[^.]+$/, ''));
  fd.append('description', document.getElementById('uploadDesc').value.trim());
  fd.append('category',    document.getElementById('uploadCategory').value.trim() || 'general');
  fd.append('featured',    document.getElementById('uploadFeatured').value);

  btn.disabled     = true;
  btn.textContent  = 'Uploading…';
  progress.style.display = 'block';
  status.textContent = '';

  try {
    const result = await new Promise((resolve, reject) => {
      const xhr = new XMLHttpRequest();

      xhr.upload.addEventListener('progress', e => {
        if (e.lengthComputable) {
          const pct = Math.round(e.loaded / e.total * 100);
          fill.style.width  = pct + '%';
          label.textContent = pct + '%';
        }
      });

      xhr.addEventListener('load', () => {
        try {
          resolve(JSON.parse(xhr.responseText));
        } catch(parseErr) {
          reject(new Error('Server returned invalid JSON. Response: ' + xhr.responseText.substring(0, 300)));
        }
      });
      xhr.addEventListener('error', () => reject(new Error('Network error — is XAMPP running?')));
      xhr.addEventListener('abort', () => reject(new Error('Upload aborted')));

      xhr.open('POST', API + 'api_upload.php');
      xhr.send(fd);
    });

    if (result.success) {
      alertEl.innerHTML = `
        <div class="alert alert-success">
          ✅ <strong>Upload successful!</strong><br/>
          File saved to: <code>${result.path}</code><br/>
          Use this path when creating a Project cover image: <code>${result.path}</code>
        </div>`;
      // Reset form
      fileInput.value = '';
      document.getElementById('fileChosen').textContent = '';
      document.getElementById('uploadTitle').value    = '';
      document.getElementById('uploadDesc').value     = '';
      document.getElementById('uploadCategory').value = '';
      fill.style.width = '100%';
      // Auto-fill cover field in projects page
      document.getElementById('projCover').value = result.path;
    } else {
      throw new Error(result.error || 'Unknown upload error');
    }

  } catch (err) {
    alertEl.innerHTML = `<div class="alert alert-error">❌ ${esc(err.message)}</div>`;
  } finally {
    btn.disabled    = false;
    btn.textContent = 'Upload File';
    setTimeout(() => { progress.style.display = 'none'; fill.style.width = '0'; label.textContent = '0%'; }, 2000);
  }
}

/* ════════════════════
   DASHBOARD
════════════════════ */
async function loadDashboard() {
  const statusEl = document.getElementById('dbStatus');
  statusEl.textContent = 'Checking connection…';
  try {
    const [mRes, pRes, msgRes] = await Promise.all([
      fetch(API + 'api_media.php?limit=1000'),
      fetch(API + 'api_projects.php?limit=1000'),
      fetch(API + 'api_messages.php'),
    ]);

    if (!mRes.ok) throw new Error('API returned ' + mRes.status + '. Is XAMPP running?');

    const m   = await mRes.json();
    const p   = await pRes.json();
    const msg = await msgRes.json();

    const photos = (m.data || []).filter(x => x.type === 'photo').length;
    const videos = (m.data || []).filter(x => x.type === 'video').length;

    document.getElementById('statPhotos').textContent   = photos;
    document.getElementById('statVideos').textContent   = videos;
    document.getElementById('statProjects').textContent = p.count || 0;
    document.getElementById('statMessages').textContent = msg.count || 0;

    statusEl.innerHTML = `<span style="color:var(--green)">✓ Connected to <strong>calvin_portfolio</strong> database.</span><br/>
      <span style="color:var(--ash);font-size:.8rem">${photos} photos · ${videos} videos · ${p.count||0} projects · ${msg.count||0} messages</span>`;
    document.getElementById('pathDebug').style.display = 'block';

  } catch(err) {
    statusEl.innerHTML = `
      <span style="color:var(--red)">✗ Cannot reach database.</span><br/>
      <span style="color:var(--ash);font-size:.82rem">
        Error: ${esc(err.message)}<br/><br/>
        Make sure:<br/>
        1. XAMPP Apache &amp; MySQL are both running<br/>
        2. You imported <code>database.sql</code> via phpMyAdmin<br/>
        3. Database name is <code>calvin_portfolio</code><br/>
        4. <code>php/config.php</code> has the right DB_USER / DB_PASS
      </span>`;
    document.getElementById('pathDebug').style.display = 'block';
  }
}

/* ════════════════════
   MEDIA TABLE
════════════════════ */
async function loadMedia() {
  const tbody = document.getElementById('mediaTable');
  tbody.innerHTML = '<tr><td colspan="7" style="color:var(--ash)">Loading…</td></tr>';
  try {
    const res  = await fetch(API + 'api_media.php?limit=200');
    const json = await res.json();
    const items = json.data || [];

    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="7" style="color:var(--ash);padding:1.5rem">No media uploaded yet. Use "Upload Media" to add files.</td></tr>';
      return;
    }

    tbody.innerHTML = items.map(item => {
      // Use the full URL returned by API (file_url), not a relative path
      const previewSrc = item.file_url || (UPLOADS_URL + item.file_path.replace('uploads/', ''));
      const preview = item.type === 'photo'
        ? `<img class="thumb" src="${previewSrc}" alt="${esc(item.title)}" loading="lazy" onerror="this.style.opacity='.3'" />`
        : `<div class="video-thumb">▶</div>`;
      return `
        <tr>
          <td>${preview}</td>
          <td>${esc(item.title)}</td>
          <td><span class="badge badge-${item.type}">${item.type}</span></td>
          <td style="color:var(--ash)">${esc(item.category)}</td>
          <td><code style="font-size:.7rem;color:var(--ash)">${esc(item.file_path)}</code></td>
          <td>${item.featured == 1 ? '<span class="badge badge-feat">Yes</span>' : '—'}</td>
          <td style="display:flex;gap:.4rem;flex-wrap:wrap">
            <button class="btn btn-sm" style="background:rgba(201,168,76,.15);color:var(--gold);border:1px solid rgba(201,168,76,.3)" onclick="openEditMedia(${item.id},this)">Edit</button>
            <button class="btn btn-danger btn-sm" onclick="deleteMedia(${item.id}, this)">Delete</button>
          </td>
        </tr>`;
    }).join('');

  } catch(err) {
    tbody.innerHTML = `<tr><td colspan="7" style="color:var(--red)">Error: ${esc(err.message)}</td></tr>`;
  }
}

async function deleteMedia(id, btn) {
  if (!confirm('Permanently delete this media file?')) return;
  btn.disabled = true;
  btn.textContent = '…';
  try {
    const res  = await fetch(API + 'api_delete.php?type=media&id=' + id, { method: 'DELETE' });
    const json = await res.json();
    if (json.success) {
      loadMedia();
    } else {
      alert('Delete failed: ' + (json.error || 'unknown error'));
      btn.disabled = false; btn.textContent = 'Delete';
    }
  } catch(err) {
    alert('Network error: ' + err.message);
    btn.disabled = false; btn.textContent = 'Delete';
  }
}

/* ════════════════════
   PROJECTS
════════════════════ */
async function loadProjects() {
  const tbody = document.getElementById('projectsTable');
  tbody.innerHTML = '<tr><td colspan="6" style="color:var(--ash)">Loading…</td></tr>';
  try {
    const res  = await fetch(API + 'api_projects.php?limit=100');
    const json = await res.json();
    const items = json.data || [];

    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="6" style="color:var(--ash);padding:1.5rem">No projects yet.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(p => `
      <tr>
        <td>${esc(p.title)}</td>
        <td><span class="badge badge-${p.category === 'photography' ? 'photo' : 'video'}">${esc(p.category)}</span></td>
        <td style="color:var(--ash)">${esc(p.client || '—')}</td>
        <td style="color:var(--ash)">${esc(p.year || '—')}</td>
        <td>${p.featured == 1 ? '<span class="badge badge-feat">Yes</span>' : '—'}</td>
        <td style="display:flex;gap:.4rem;flex-wrap:wrap">
          <button class="btn btn-sm" style="background:rgba(201,168,76,.15);color:var(--gold);border:1px solid rgba(201,168,76,.3)" onclick="openEditProject(${p.id},this)">Edit</button>
          <button class="btn btn-danger btn-sm" onclick="deleteProject(${p.id}, this)">Delete</button>
        </td>
      </tr>`).join('');
  } catch(err) {
    tbody.innerHTML = `<tr><td colspan="6" style="color:var(--red)">Error: ${esc(err.message)}</td></tr>`;
  }
}

async function saveProject() {
  const alertEl = document.getElementById('projectAlert');
  alertEl.innerHTML = '';
  const body = {
    title:       document.getElementById('projTitle').value.trim(),
    subtitle:    document.getElementById('projSubtitle').value.trim(),
    description: document.getElementById('projDesc').value.trim(),
    category:    document.getElementById('projCategory').value,
    client:      document.getElementById('projClient').value.trim(),
    year:        document.getElementById('projYear').value,
    cover_image: document.getElementById('projCover').value.trim(),
    video_url:   document.getElementById('projVideo').value.trim(),
    featured:    document.getElementById('projFeatured').value,
  };
  if (!body.title) {
    alertEl.innerHTML = `<div class="alert alert-error">Title is required.</div>`;
    return;
  }
  try {
    const res  = await fetch(API + 'api_save_project.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (json.success) {
      alertEl.innerHTML = `<div class="alert alert-success">✅ Project saved! ID: ${json.id}</div>`;
      // Clear form
      ['projTitle','projSubtitle','projDesc','projClient','projCover','projVideo'].forEach(id => {
        document.getElementById(id).value = '';
      });
      loadProjects();
    } else {
      throw new Error(json.error || 'Save failed');
    }
  } catch(err) {
    alertEl.innerHTML = `<div class="alert alert-error">❌ ${esc(err.message)}</div>`;
  }
}

async function deleteProject(id, btn) {
  if (!confirm('Delete this project?')) return;
  btn.disabled = true; btn.textContent = '…';
  try {
    const res  = await fetch(API + 'api_delete.php?type=project&id=' + id, { method: 'DELETE' });
    const json = await res.json();
    if (json.success) loadProjects();
    else { alert('Delete failed: ' + (json.error || 'unknown')); btn.disabled = false; btn.textContent = 'Delete'; }
  } catch(err) { alert(err.message); btn.disabled = false; btn.textContent = 'Delete'; }
}

/* ════════════════════
   MESSAGES
════════════════════ */
async function loadMessages() {
  const list = document.getElementById('messagesList');
  list.innerHTML = 'Loading…';
  try {
    const res  = await fetch(API + 'api_messages.php');
    const json = await res.json();
    const msgs = json.data || [];
    if (!msgs.length) { list.innerHTML = '<p style="color:var(--ash)">No messages yet.</p>'; return; }

    list.innerHTML = msgs.map(m => `
      <div class="msg-item ${m.is_read == 0 ? 'unread' : ''}">
        <div class="msg-meta">
          <span class="msg-name">${esc(m.name)}</span>
          <span class="msg-email">${esc(m.email)}</span>
          <span class="msg-date">${new Date(m.created_at).toLocaleDateString('en-GB',{day:'numeric',month:'short',year:'numeric'})}</span>
        </div>
        ${m.subject ? `<div class="msg-subject">${esc(m.subject)}</div>` : ''}
        <div class="msg-body">${esc(m.message)}</div>
        <div style="margin-top:.75rem">
          <a href="mailto:${esc(m.email)}?subject=Re: ${esc(m.subject||'Your enquiry')}" class="btn btn-primary btn-sm" style="display:inline-block">Reply via Email</a>
        </div>
      </div>`).join('');
  } catch(err) {
    list.innerHTML = `<p style="color:var(--red)">Error: ${esc(err.message)}</p>`;
  }
}

/* ════════════════════
   UTILITY
════════════════════ */
function esc(s) {
  if (!s) return '';
  return String(s)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ════════════════════
   ANALYTICS
════════════════════ */
async function loadAnalytics() {
  try {
    const res  = await fetch(API + 'api_analytics.php');
    const json = await res.json();
    if (!json.success) throw new Error(json.error || 'Failed');

    // Stat cards
    document.getElementById('aToday').textContent       = json.today;
    document.getElementById('aWeek').textContent        = json.week;
    document.getElementById('aMonth').textContent       = json.month;
    document.getElementById('aTotal').textContent       = json.total;
    document.getElementById('aUniqueToday').textContent = json.unique_today;
    document.getElementById('aUniqueWeek').textContent  = json.unique_week;
    document.getElementById('aBooking').textContent     = json.booking_visits;

    if (json.peak_hour !== null) {
      const h = json.peak_hour;
      const label = h === 0 ? '12 AM' : h < 12 ? h + ' AM' : h === 12 ? '12 PM' : (h-12) + ' PM';
      document.getElementById('aPeakHour').textContent = '⏰ Peak: ' + label;
    }

    // Daily chart
    drawBarChart('visitsChart', 'chartEmpty', json.daily, 'day', 'visits', '#c9a84c', 'Visits');

    // Hourly chart
    drawBarChart('hourlyChart', 'hourlyEmpty', json.hourly, 'hour', 'visits', 'rgba(201,168,76,0.6)', null, h => {
      return h === 0 ? '12a' : h < 12 ? h+'a' : h === 12 ? '12p' : (h-12)+'p';
    });

    // Top pages
    const tp = document.getElementById('topPages');
    if (!json.top_pages || !json.top_pages.length) {
      tp.innerHTML = '<div style="color:var(--ash);font-size:.82rem">No page data yet.</div>';
    } else {
      const max = json.top_pages[0].visits;
      tp.innerHTML = json.top_pages.map(p => {
        const pct = Math.round(p.visits / max * 100);
        const label = p.page.replace('/calvin_portfolio','').replace('/index.html','') || '/';
        return `
          <div style="display:flex;align-items:center;gap:.6rem">
            <div style="flex:1;min-width:0">
              <div style="font-size:.72rem;color:var(--ivory);font-family:'Space Mono',monospace;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">${esc(label)}</div>
              <div style="height:3px;background:rgba(255,255,255,.06);border-radius:2px;margin-top:.3rem;overflow:hidden">
                <div style="height:100%;width:${pct}%;background:var(--gold);border-radius:2px;transition:width .6s"></div>
              </div>
            </div>
            <div style="font-family:'Space Mono',monospace;font-size:.72rem;color:var(--gold);white-space:nowrap">${p.visits}</div>
          </div>`;
      }).join('');
    }

  } catch(err) {
    document.getElementById('aToday').textContent = '—';
    document.getElementById('chartEmpty').style.display = 'block';
    document.getElementById('chartEmpty').textContent = 'Could not load analytics: ' + err.message;
  }
}

function drawBarChart(canvasId, emptyId, data, labelKey, valueKey, color, tooltip, labelFn) {
  const canvas  = document.getElementById(canvasId);
  const emptyEl = document.getElementById(emptyId);
  if (!canvas) return;

  if (!data || !data.length) {
    canvas.style.display = 'none';
    if (emptyEl) emptyEl.style.display = 'block';
    return;
  }

  canvas.style.display = 'block';
  if (emptyEl) emptyEl.style.display = 'none';

  const dpr    = window.devicePixelRatio || 1;
  const parent = canvas.parentElement;
  const W      = parent.clientWidth  || 600;
  const H      = parent.clientHeight || 200;

  canvas.width  = W * dpr;
  canvas.height = H * dpr;
  canvas.style.width  = W + 'px';
  canvas.style.height = H + 'px';

  const ctx = canvas.getContext('2d');
  ctx.scale(dpr, dpr);

  const maxVal  = Math.max(...data.map(d => d[valueKey]), 1);
  const padL    = 30, padR = 10, padT = 16, padB = 28;
  const chartW  = W - padL - padR;
  const chartH  = H - padT - padB;
  const barW    = chartW / data.length;
  const gap     = Math.max(2, barW * 0.18);

  // Grid lines
  ctx.strokeStyle = 'rgba(255,255,255,0.05)';
  ctx.lineWidth   = 1;
  [0, 0.25, 0.5, 0.75, 1].forEach(t => {
    const y = padT + chartH * (1 - t);
    ctx.beginPath(); ctx.moveTo(padL, y); ctx.lineTo(W - padR, y); ctx.stroke();
    if (t > 0) {
      ctx.fillStyle = 'rgba(255,255,255,0.25)';
      ctx.font      = '9px Space Mono, monospace';
      ctx.textAlign = 'right';
      ctx.fillText(Math.round(maxVal * t), padL - 4, y + 3);
    }
  });

  // Bars
  data.forEach((d, i) => {
    const val    = d[valueKey];
    const barH   = (val / maxVal) * chartH;
    const x      = padL + i * barW + gap / 2;
    const y      = padT + chartH - barH;
    const bw     = barW - gap;

    // Bar gradient
    const grad = ctx.createLinearGradient(0, y, 0, y + barH);
    grad.addColorStop(0, color);
    grad.addColorStop(1, color.replace('0.6','0.2').includes('rgba') ? color.replace('0.6','0.2') : color + '55');
    ctx.fillStyle   = grad;
    ctx.beginPath();
    const r = Math.min(3, bw / 2);
    ctx.roundRect(x, y, bw, barH, [r, r, 0, 0]);
    ctx.fill();

    // X labels (every N bars)
    const every = data.length > 20 ? 5 : data.length > 10 ? 3 : 1;
    if (i % every === 0) {
      ctx.fillStyle = 'rgba(255,255,255,0.3)';
      ctx.font      = '8px Space Mono, monospace';
      ctx.textAlign = 'center';
      const rawLabel = d[labelKey];
      const label = labelFn ? labelFn(rawLabel) :
                    typeof rawLabel === 'string' ? rawLabel.slice(5) : rawLabel;
      ctx.fillText(label, x + bw / 2, H - padB + 14);
    }
  });
}

/* ════════════════════
   BOOKINGS
════════════════════ */
async function loadBookings() {
  const tbody = document.getElementById('bookingsTable');
  tbody.innerHTML = '<tr><td colspan="9" style="color:var(--ash)">Loading…</td></tr>';
  try {
    const res  = await fetch(API + 'api_booking_list.php');
    const json = await res.json();
    const items = json.data || [];

    document.getElementById('bkTotal').textContent     = items.length;
    document.getElementById('bkPending').textContent   = items.filter(b=>b.status==='pending').length;
    document.getElementById('bkConfirmed').textContent = items.filter(b=>b.status==='confirmed').length;
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('bkToday').textContent     = items.filter(b=>b.booked_date===today).length;

    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="9" style="color:var(--ash);padding:1.5rem">No bookings yet.</td></tr>';
      return;
    }

    const statusColor = { pending:'var(--gold)', confirmed:'#5cb85c', completed:'#888', cancelled:'var(--red)' };

    tbody.innerHTML = items.map(b => {
      const timeStr = b.booked_hour !== null
        ? formatAdminHour(b.booked_hour) + ' – ' + formatAdminHour(b.booked_end_hour)
        : '—';
      const delivStr = b.delivery_date ? b.delivery_date + (b.urgent=='1' ? ' ⚡' : '') : '—';
      const calBtn = b.calendar_link
        ? `<a href="${esc(b.calendar_link)}" target="_blank" style="color:var(--gold);font-size:.75rem;font-family:'Space Mono',monospace;white-space:nowrap">📅 Add</a>`
        : '—';
      return `<tr>
        <td style="color:var(--ash);font-family:'Space Mono',monospace;font-size:.72rem">#${b.id}</td>
        <td><span class="badge badge-${b.service==='photography'?'photo':'video'}">${esc(b.service)}</span>
            ${b.theme ? `<br/><span style="font-size:.7rem;color:var(--ash)">${esc(b.theme)}</span>` : ''}
            ${b.package ? `<br/><span style="font-size:.7rem;color:var(--ash)">${esc(b.package)}</span>` : ''}
        </td>
        <td>
          <strong>${esc(b.client_name)}</strong><br/>
          <a href="mailto:${esc(b.client_email)}" style="color:var(--gold);font-size:.75rem">${esc(b.client_email)}</a>
          ${b.client_phone ? `<br/><span style="font-size:.72rem;color:var(--ash)">${esc(b.client_phone)}</span>` : ''}
        </td>
        <td style="font-family:'Space Mono',monospace;font-size:.78rem">
          ${esc(b.booked_date)}<br/><span style="color:var(--ash)">${timeStr}</span>
        </td>
        <td style="font-family:'Space Mono',monospace;font-size:.78rem;color:${b.urgent=='1'?'#e07850':'var(--ash)'}">${delivStr}</td>
        <td style="font-size:.75rem;color:var(--ash)">${b.payment_method==='bank'?'🏦 BCA':'🌍 PayPal'}</td>
        <td>
          <select onchange="updateBookingStatus(${b.id},this.value)" style="background:#222;border:1px solid #333;color:${statusColor[b.status]||'var(--ivory)'};font-size:.72rem;padding:.3rem .5rem;border-radius:6px;font-family:'Space Mono',monospace">
            ${['pending','confirmed','completed','cancelled'].map(s=>`<option value="${s}"${b.status===s?' selected':''}>${s}</option>`).join('')}
          </select>
        </td>
        <td>${calBtn}</td>
        <td>
          <button class="btn btn-danger btn-sm" onclick="deleteBooking(${b.id},this)">Delete</button>
        </td>
      </tr>`;
    }).join('');
  } catch(err) {
    tbody.innerHTML = `<tr><td colspan="9" style="color:var(--red)">Error: ${esc(err.message)}</td></tr>`;
  }
}

function formatAdminHour(h) {
  if (h === null || h === undefined) return '—';
  h = parseInt(h);
  const ampm = h < 12 ? 'AM' : 'PM';
  return (h%12||12) + ':00 ' + ampm;
}

async function updateBookingStatus(id, status) {
  try {
    const res  = await fetch(API + 'api_booking_list.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'update_status', id, status }),
    });
    const json = await res.json();
    if (!json.success) alert('Failed: ' + (json.error||'unknown'));
  } catch(e) { alert(e.message); }
}

async function deleteBooking(id, btn) {
  if (!confirm('Delete this booking permanently?')) return;
  btn.disabled = true; btn.textContent = '…';
  try {
    const res  = await fetch(API + 'api_booking_list.php', {
      method:'POST',
      headers:{'Content-Type':'application/json'},
      body: JSON.stringify({ action:'delete', id }),
    });
    const json = await res.json();
    if (json.success) loadBookings();
    else { alert('Failed: '+(json.error||'unknown')); btn.disabled=false; btn.textContent='Delete'; }
  } catch(e) { alert(e.message); btn.disabled=false; btn.textContent='Delete'; }
}

/* ════════════════════
   BOOKINGS
════════════════════ */
let allBookings = [];
let currentFilter = 'all';

async function loadBookings() {
  const tbody = document.getElementById('bookingsTable');
  if (!tbody) return;
  tbody.innerHTML = '<tr><td colspan="9" style="color:var(--ash)">Loading…</td></tr>';

  try {
    const res  = await fetch(API + 'api_get_bookings.php');
    const json = await res.json();
    allBookings = json.data || [];

    // Stats
    const today = new Date().toISOString().slice(0,10);
    document.getElementById('bkTotal').textContent     = allBookings.length;
    document.getElementById('bkPending').textContent   = allBookings.filter(b=>b.status==='pending').length;
    document.getElementById('bkConfirmed').textContent = allBookings.filter(b=>b.status==='confirmed').length;
    document.getElementById('bkUpcoming').textContent  = allBookings.filter(b=>b.booked_date>=today && b.status!=='cancelled').length;

    renderBookings(currentFilter);
  } catch(err) {
    tbody.innerHTML = `<tr><td colspan="9" style="color:var(--red)">Error: ${esc(err.message)}</td></tr>`;
  }
}

function filterBookings(f) {
  currentFilter = f;
  document.querySelectorAll('[id^="bkf-"]').forEach(b => b.style.color = 'var(--ash)');
  const active = document.getElementById('bkf-' + f);
  if (active) active.style.color = 'var(--gold)';
  renderBookings(f);
}

function renderBookings(filter) {
  const tbody = document.getElementById('bookingsTable');
  let list = allBookings;
  if (filter !== 'all' && ['photography','videography','editing'].includes(filter)) {
    list = list.filter(b => b.service === filter);
  } else if (filter === 'pending') {
    list = list.filter(b => b.status === 'pending');
  }

  if (!list.length) {
    tbody.innerHTML = `<tr><td colspan="9" style="color:var(--ash);padding:1.5rem">No bookings found.</td></tr>`;
    return;
  }

  const svcIcon = { photography:'📷', videography:'🎥', editing:'✂️' };
  const statusColor = { pending:'rgba(201,168,76,.15)', confirmed:'rgba(39,174,96,.15)', cancelled:'rgba(231,76,60,.15)' };
  const statusText  = { pending:'var(--gold)', confirmed:'var(--green)', cancelled:'var(--red)' };

  tbody.innerHTML = list.map(b => {
    const timeStr = b.start_hour != null
      ? `${(parseInt(b.start_hour)%12||12)}:00 ${parseInt(b.start_hour)<12?'AM':'PM'}` + (b.duration>1 ? ` · ${b.duration}h` : '')
      : b.delivery_date ? `Delivery: ${b.delivery_date}` : '—';

    const calLink = buildCalLink(b);
    const calBtn = calLink
      ? `<a href="${esc(calLink)}" target="_blank" title="Add to Google Calendar" style="color:#7ab4f8;font-size:.78rem;text-decoration:none">📅 Add</a>`
      : '—';

    return `<tr>
      <td style="color:var(--ash);font-size:.75rem;font-family:'Space Mono',monospace">#${b.id}</td>
      <td>
        <div style="font-weight:500">${esc(b.client_name)}</div>
        <div style="font-size:.75rem;color:var(--ash)">${esc(b.client_email)}</div>
        ${b.client_phone?`<div style="font-size:.72rem;color:var(--ash)">${esc(b.client_phone)}</div>`:''}
      </td>
      <td>
        <span style="font-size:1.1rem">${svcIcon[b.service]||''}</span>
        <div style="font-size:.75rem;color:var(--ash);font-family:'Space Mono',monospace;text-transform:uppercase;letter-spacing:.06em">${esc(b.theme||b.package||b.service)}</div>
        ${b.urgent=='1'?'<span style="font-size:.68rem;color:#e07850;font-family:\'Space Mono\',monospace">⚡ Rush</span>':''}
      </td>
      <td>
        <div style="font-family:'Space Mono',monospace;font-size:.78rem">${esc(b.booked_date)}</div>
        <div style="font-size:.75rem;color:var(--ash)">${esc(timeStr)}</div>
      </td>
      <td style="font-size:.78rem;color:var(--gold);font-family:'Space Mono',monospace">${esc(b.total_price||'—')}</td>
      <td>
        <span class="badge ${b.payment_method==='bank'?'badge-photo':'badge-video'}">${b.payment_method==='bank'?'BCA':'PayPal'}</span>
      </td>
      <td>
        <span style="padding:.25rem .65rem;border-radius:100px;font-size:.65rem;font-family:'Space Mono',monospace;
          background:${statusColor[b.status]||'rgba(255,255,255,.05)'};
          color:${statusText[b.status]||'var(--ash)'}">
          ${esc(b.status)}
        </span>
      </td>
      <td>${calBtn}</td>
      <td>
        <div style="display:flex;gap:.4rem;flex-wrap:wrap">
          ${b.status!=='confirmed'?`<button class="btn btn-sm" style="background:rgba(39,174,96,.15);color:var(--green);border:1px solid rgba(39,174,96,.3)" onclick="updateBooking(${b.id},'confirmed',this)">✓</button>`:''}
          ${b.status!=='cancelled'?`<button class="btn btn-danger btn-sm" onclick="updateBooking(${b.id},'cancelled',this)">✕</button>`:''}
        </div>
      </td>
    </tr>`;
  }).join('');
}

async function updateBooking(id, status, btn) {
  btn.disabled = true;
  try {
    const res  = await fetch(API + 'api_get_bookings.php', {
      method: 'POST',
      headers: {'Content-Type':'application/json'},
      body: JSON.stringify({ id, status }),
    });
    const json = await res.json();
    if (json.success) loadBookings();
    else alert('Update failed: ' + (json.error || 'unknown'));
  } catch(e) {
    alert(e.message);
  } finally {
    btn.disabled = false;
  }
}

function buildCalLink(b) {
  if (!b.booked_date) return null;
  if ((b.service === 'photography' || b.service === 'videography') && b.start_hour != null) {
    const y=(int=>int)(b.booked_date.slice(0,4));
    const mo=(int=>int)(b.booked_date.slice(5,7));
    const d=(int=>int)(b.booked_date.slice(8,10));
    const sh = parseInt(b.start_hour);
    const eh = sh + Math.max(1, parseInt(b.duration||1));
    const pad = n => String(n).padStart(2,'0');
    const dtS = `${b.booked_date.replace(/-/g,'')}T${pad(sh)}0000`;
    const dtE = `${b.booked_date.replace(/-/g,'')}T${pad(eh)}0000`;
    const title = encodeURIComponent(`${b.service==='photography'?'📷':'🎥'} ${b.client_name}${b.theme?' · '+b.theme:''}`);
    const detail = encodeURIComponent(`#${b.id} · ${b.client_email} · ${b.total_price||''}`);
    return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dtS}/${dtE}&details=${detail}&location=Bali%2C+Indonesia`;
  }
  if (b.service === 'editing' && b.delivery_date) {
    const dtD = b.delivery_date.replace(/-/g,'');
    const dtN = b.delivery_date.replace(/-/g,''); // same day event
    const title = encodeURIComponent(`📦 Delivery: ${b.client_name}`);
    const detail = encodeURIComponent(`#${b.id} · ${b.package||'editing'} · ${b.urgent=='1'?'⚡ Rush':'Standard'}`);
    return `https://calendar.google.com/calendar/render?action=TEMPLATE&text=${title}&dates=${dtD}/${dtN}&details=${detail}`;
  }
  return null;
}

// Boot
loadDashboard();

/* ════════════════════
   EDIT MEDIA MODAL
════════════════════ */
let editMediaId = null;

function openEditMedia(id, btn) {
  editMediaId = id;
  // Fetch current data
  fetch(API + 'api_media.php?limit=1000').then(r=>r.json()).then(json=>{
    const item = (json.data||[]).find(x=>x.id==id);
    if (!item) { alert('Media not found'); return; }
    document.getElementById('em-title').value    = item.title || '';
    document.getElementById('em-category').value = item.category || '';
    document.getElementById('em-desc').value     = item.description || '';
    document.getElementById('em-featured').value = item.featured ? '1' : '0';
    document.getElementById('em-path').value     = item.file_path || '';
    document.getElementById('editMediaModal').style.display = 'flex';
  });
}

function closeEditMedia() {
  document.getElementById('editMediaModal').style.display = 'none';
  editMediaId = null;
}

async function saveEditMedia() {
  if (!editMediaId) return;
  const body = {
    id:       editMediaId,
    title:    document.getElementById('em-title').value.trim(),
    category: document.getElementById('em-category').value.trim(),
    description: document.getElementById('em-desc').value.trim(),
    featured: document.getElementById('em-featured').value,
  };
  try {
    const res  = await fetch(API + 'api_edit_media.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (json.success) {
      closeEditMedia();
      loadMedia();
      document.getElementById('editFeedback').textContent = '';
    } else { throw new Error(json.error||'Save failed'); }
  } catch(e) {
    document.getElementById('editFeedback').textContent = '✕ ' + e.message;
  }
}

/* ════════════════════
   EDIT PROJECT MODAL
════════════════════ */
let editProjectId = null;

function openEditProject(id, btn) {
  editProjectId = id;
  fetch(API + 'api_projects.php?limit=1000').then(r=>r.json()).then(json=>{
    const p = (json.data||[]).find(x=>x.id==id);
    if (!p) { alert('Project not found'); return; }
    document.getElementById('ep-title').value    = p.title || '';
    document.getElementById('ep-subtitle').value = p.subtitle || '';
    document.getElementById('ep-desc').value     = p.description || '';
    document.getElementById('ep-category').value = p.category || 'photography';
    document.getElementById('ep-client').value   = p.client || '';
    document.getElementById('ep-year').value     = p.year || '';
    document.getElementById('ep-cover').value    = p.cover_image || '';
    document.getElementById('ep-video').value    = p.video_url || '';
    document.getElementById('ep-featured').value = p.featured ? '1' : '0';
    document.getElementById('editProjectModal').style.display = 'flex';
  });
}

function closeEditProject() {
  document.getElementById('editProjectModal').style.display = 'none';
  editProjectId = null;
}

async function saveEditProject() {
  if (!editProjectId) return;
  const body = {
    id:          editProjectId,
    title:       document.getElementById('ep-title').value.trim(),
    subtitle:    document.getElementById('ep-subtitle').value.trim(),
    description: document.getElementById('ep-desc').value.trim(),
    category:    document.getElementById('ep-category').value,
    client:      document.getElementById('ep-client').value.trim(),
    year:        document.getElementById('ep-year').value,
    cover_image: document.getElementById('ep-cover').value.trim(),
    video_url:   document.getElementById('ep-video').value.trim(),
    featured:    document.getElementById('ep-featured').value,
  };
  try {
    const res  = await fetch(API + 'api_edit_project.php', {
      method:'POST', headers:{'Content-Type':'application/json'},
      body: JSON.stringify(body),
    });
    const json = await res.json();
    if (json.success) {
      closeEditProject();
      loadProjects();
      document.getElementById('editProjFeedback').textContent = '';
    } else { throw new Error(json.error||'Save failed'); }
  } catch(e) {
    document.getElementById('editProjFeedback').textContent = '✕ ' + e.message;
  }
}
</script>

<!-- ═══ EDIT MEDIA MODAL ═══ -->
<div id="editMediaModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.75);align-items:center;justify-content:center;padding:1rem">
  <div style="background:#1a1a1a;border:1px solid rgba(240,237,230,.1);border-radius:8px;padding:2rem;width:100%;max-width:500px;max-height:90vh;overflow-y:auto;position:relative">
    <button onclick="closeEditMedia()" style="position:absolute;top:1rem;right:1rem;color:var(--ash);font-size:1.1rem;background:none;border:none;cursor:pointer">✕</button>
    <h2 style="font-size:1.1rem;font-weight:500;margin-bottom:1.5rem;color:var(--ivory)">✏ Edit Media</h2>
    <div style="display:flex;flex-direction:column;gap:1rem">
      <div class="form-group"><label>Title</label><input type="text" id="em-title"/></div>
      <div class="form-group"><label>Category</label><input type="text" id="em-category" placeholder="landscape / wedding…"/></div>
      <div class="form-group"><label>Description</label><textarea id="em-desc" rows="3"></textarea></div>
      <div class="form-group"><label>Featured in Carousel?</label>
        <select id="em-featured"><option value="0">No</option><option value="1">Yes</option></select></div>
      <div class="form-group"><label>File Path (read-only)</label>
        <input type="text" id="em-path" readonly style="opacity:.4;cursor:not-allowed"/></div>
    </div>
    <div style="margin-top:1.5rem;display:flex;gap:.75rem;align-items:center">
      <button class="btn btn-primary" onclick="saveEditMedia()">Save Changes</button>
      <button class="btn" style="border:1px solid rgba(255,255,255,.1);color:var(--ash)" onclick="closeEditMedia()">Cancel</button>
    </div>
    <div id="editFeedback" style="color:var(--red);font-size:.8rem;margin-top:.6rem"></div>
  </div>
</div>

<!-- ═══ EDIT PROJECT MODAL ═══ -->
<div id="editProjectModal" style="display:none;position:fixed;inset:0;z-index:1000;background:rgba(0,0,0,.75);align-items:center;justify-content:center;padding:1rem">
  <div style="background:#1a1a1a;border:1px solid rgba(240,237,230,.1);border-radius:8px;padding:2rem;width:100%;max-width:560px;max-height:90vh;overflow-y:auto;position:relative">
    <button onclick="closeEditProject()" style="position:absolute;top:1rem;right:1rem;color:var(--ash);font-size:1.1rem;background:none;border:none;cursor:pointer">✕</button>
    <h2 style="font-size:1.1rem;font-weight:500;margin-bottom:1.5rem;color:var(--ivory)">✏ Edit Project</h2>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:1rem">
      <div class="form-group"><label>Title *</label><input type="text" id="ep-title"/></div>
      <div class="form-group"><label>Subtitle</label><input type="text" id="ep-subtitle"/></div>
      <div class="form-group" style="grid-column:1/-1"><label>Description</label><textarea id="ep-desc" rows="3"></textarea></div>
      <div class="form-group">
        <label>Category</label>
        <select id="ep-category">
          <option value="photography">Photography</option>
          <option value="videography">Videography</option>
          <option value="editing">Editing</option>
        </select>
      </div>
      <div class="form-group"><label>Client</label><input type="text" id="ep-client"/></div>
      <div class="form-group"><label>Year</label><input type="number" id="ep-year"/></div>
      <div class="form-group"><label>Featured?</label>
        <select id="ep-featured"><option value="0">No</option><option value="1">Yes</option></select></div>
      <div class="form-group" style="grid-column:1/-1"><label>Cover Image Path</label><input type="text" id="ep-cover" placeholder="uploads/photos/…"/></div>
      <div class="form-group" style="grid-column:1/-1"><label>Video URL / Path</label><input type="text" id="ep-video" placeholder="uploads/videos/… or YouTube"/></div>
    </div>
    <div style="margin-top:1.5rem;display:flex;gap:.75rem;align-items:center">
      <button class="btn btn-primary" onclick="saveEditProject()">Save Changes</button>
      <button class="btn" style="border:1px solid rgba(255,255,255,.1);color:var(--ash)" onclick="closeEditProject()">Cancel</button>
    </div>
    <div id="editProjFeedback" style="color:var(--red);font-size:.8rem;margin-top:.6rem"></div>
  </div>
</div>

</body>
</html>
