<?php
require_once __DIR__ . '/../php/auth.php';

// Already logged in → go straight to dashboard
if (isLoggedIn()) {
    header('Location: index.php');
    exit;
}

// Grab any error flashed from a redirect (not used now but handy)
$flashError = $_GET['error'] ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Admin Login — Calvin Christian</title>
  <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:ital,wght@0,300;1,300&family=Space+Mono:wght@400;700&family=Inter:wght@300;400&display=swap" rel="stylesheet" />
  <style>
    :root {
      --ink:    #0d0d0d;
      --ivory:  #f0ede6;
      --gold:   #c9a84c;
      --golddim:#9e7d32;
      --teal:   #1a3d3a;
      --ash:    #777;
      --red:    #e05252;
      --border: rgba(240,237,230,0.12);
    }

    *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }
    html, body { height: 100%; }

    body {
      background: var(--ink);
      color: var(--ivory);
      font-family: 'Inter', sans-serif;
      font-weight: 300;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: 100vh;
      overflow: hidden;
    }

    /* ── Animated background ── */
    .bg {
      position: fixed; inset: 0; z-index: 0;
      background: radial-gradient(ellipse at 20% 50%, rgba(26,61,58,0.55) 0%, transparent 60%),
                  radial-gradient(ellipse at 80% 20%, rgba(201,168,76,0.06) 0%, transparent 50%);
    }
    .bg-grain {
      position: fixed; inset: 0; z-index: 0;
      background-image: url("data:image/svg+xml,%3Csvg viewBox='0 0 256 256' xmlns='http://www.w3.org/2000/svg'%3E%3Cfilter id='noise'%3E%3CfeTurbulence type='fractalNoise' baseFrequency='0.9' numOctaves='4' stitchTiles='stitch'/%3E%3C/filter%3E%3Crect width='100%25' height='100%25' filter='url(%23noise)' opacity='0.04'/%3E%3C/svg%3E");
      opacity: 0.6;
    }

    /* ── Card ── */
    .card {
      position: relative; z-index: 1;
      width: 100%; max-width: 420px;
      padding: 3rem 2.5rem;
      background: rgba(13,13,13,0.85);
      border: 1px solid var(--border);
      backdrop-filter: blur(18px);
      animation: fadeUp 0.5s cubic-bezier(0.22,1,0.36,1) both;
    }
    @keyframes fadeUp {
      from { opacity: 0; transform: translateY(24px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Header ── */
    .logo {
      font-family: 'Space Mono', monospace;
      font-size: 0.75rem;
      letter-spacing: 0.25em;
      text-transform: uppercase;
      color: var(--gold);
      margin-bottom: 0.5rem;
    }
    .title {
      font-family: 'Cormorant Garamond', Georgia, serif;
      font-size: 2.4rem;
      font-weight: 300;
      line-height: 1;
      margin-bottom: 0.3rem;
    }
    .title em { font-style: italic; color: var(--gold); }
    .subtitle {
      font-size: 0.78rem;
      color: var(--ash);
      letter-spacing: 0.05em;
      margin-bottom: 2.25rem;
    }

    /* ── Form ── */
    .form { display: flex; flex-direction: column; gap: 1.1rem; }

    .field { position: relative; }
    .field label {
      display: block;
      font-family: 'Space Mono', monospace;
      font-size: 0.62rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      color: var(--ash);
      margin-bottom: 0.4rem;
    }
    .field input {
      width: 100%;
      background: rgba(255,255,255,0.04);
      border: 1px solid var(--border);
      color: var(--ivory);
      font-family: 'Inter', sans-serif;
      font-size: 0.95rem;
      font-weight: 300;
      padding: 0.85rem 1rem 0.85rem 2.8rem;
      outline: none;
      border-radius: 0;
      transition: border-color 0.2s, background 0.2s;
      letter-spacing: 0.02em;
    }
    .field input:focus {
      border-color: var(--gold);
      background: rgba(201,168,76,0.04);
    }
    .field input.error { border-color: var(--red); }

    /* Input icons */
    .field-icon {
      position: absolute;
      left: 0.9rem;
      top: 50%;
      transform: translateY(50%);   /* offset for label height */
      color: var(--ash);
      font-size: 0.85rem;
      pointer-events: none;
      transition: color 0.2s;
    }
    .field:focus-within .field-icon { color: var(--gold); }

    /* Show/hide password toggle */
    .toggle-pw {
      position: absolute;
      right: 0.9rem;
      top: 50%; transform: translateY(50%);
      background: none; border: none;
      color: var(--ash); font-size: 0.8rem;
      cursor: pointer; padding: 0.2rem;
      transition: color 0.2s;
    }
    .toggle-pw:hover { color: var(--ivory); }

    /* ── Error alert ── */
    .alert-error {
      display: flex; align-items: center; gap: 0.6rem;
      padding: 0.75rem 1rem;
      background: rgba(224,82,82,0.1);
      border: 1px solid rgba(224,82,82,0.35);
      color: var(--red);
      font-size: 0.82rem;
      line-height: 1.4;
      animation: shake 0.35s ease;
    }
    @keyframes shake {
      0%,100%{ transform: translateX(0); }
      25%    { transform: translateX(-6px); }
      75%    { transform: translateX(6px); }
    }
    .alert-hidden { display: none; }

    /* ── Submit button ── */
    .btn-login {
      margin-top: 0.5rem;
      width: 100%;
      padding: 1rem;
      background: var(--gold);
      color: var(--ink);
      border: none;
      font-family: 'Space Mono', monospace;
      font-size: 0.8rem;
      letter-spacing: 0.18em;
      text-transform: uppercase;
      cursor: pointer;
      transition: background 0.2s, transform 0.15s;
      position: relative;
      overflow: hidden;
    }
    .btn-login:hover:not(:disabled) { background: var(--golddim); }
    .btn-login:active:not(:disabled) { transform: scale(0.98); }
    .btn-login:disabled { opacity: 0.6; cursor: not-allowed; }

    /* Spinner inside button */
    .btn-spinner {
      display: none;
      width: 14px; height: 14px;
      border: 2px solid rgba(13,13,13,0.3);
      border-top-color: var(--ink);
      border-radius: 50%;
      animation: spin 0.7s linear infinite;
      position: absolute; right: 1.25rem; top: 50%; transform: translateY(-50%);
    }
    @keyframes spin { to { transform: translateY(-50%) rotate(360deg); } }

    /* ── Footer ── */
    .card-footer {
      margin-top: 2rem;
      padding-top: 1.25rem;
      border-top: 1px solid var(--border);
      display: flex; justify-content: space-between; align-items: center;
    }
    .card-footer a {
      font-family: 'Space Mono', monospace;
      font-size: 0.65rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--ash);
      text-decoration: none;
      transition: color 0.2s;
    }
    .card-footer a:hover { color: var(--gold); }
    .card-footer span {
      font-size: 0.65rem;
      color: rgba(240,237,230,0.2);
      font-family: 'Space Mono', monospace;
    }

    /* ── Responsive ── */
    @media (max-width: 480px) {
      .card { padding: 2rem 1.5rem; margin: 1rem; }
    }
  </style>
</head>
<body>
  <div class="bg"></div>
  <div class="bg-grain"></div>

  <div class="card">
    <div class="logo">Calvin Christian · Portfolio</div>
    <h1 class="title">Admin <em>Login</em></h1>
    <p class="subtitle">Restricted area — authorised access only</p>

    <!-- Error alert -->
    <div class="alert-error alert-hidden" id="alertBox" role="alert">
      <span>⚠</span>
      <span id="alertMsg"></span>
    </div>

    <form class="form" id="loginForm" novalidate>
      <!-- Username -->
      <div class="field">
        <label for="username">Username</label>
        <span class="field-icon">◎</span>
        <input
          type="text"
          id="username"
          name="username"
          autocomplete="username"
          placeholder="admin"
          spellcheck="false"
          required
        />
      </div>

      <!-- Password -->
      <div class="field">
        <label for="password">Password</label>
        <span class="field-icon">◈</span>
        <input
          type="password"
          id="password"
          name="password"
          autocomplete="current-password"
          placeholder="••••••••"
          required
        />
        <button type="button" class="toggle-pw" id="togglePw" aria-label="Show password">👁</button>
      </div>

      <button type="submit" class="btn-login" id="loginBtn">
        Sign In
        <div class="btn-spinner" id="btnSpinner"></div>
      </button>
    </form>

    <div class="card-footer">
      <a href="../index.html">← Back to Portfolio</a>
      <span>CC © 2026</span>
    </div>
  </div>

  <script>
    const API_BASE = '../php/';

    /* ── Show/hide password ── */
    const pwInput  = document.getElementById('password');
    const togglePw = document.getElementById('togglePw');
    togglePw.addEventListener('click', () => {
      const isText = pwInput.type === 'text';
      pwInput.type       = isText ? 'password' : 'text';
      togglePw.textContent = isText ? '👁' : '🙈';
    });

    /* ── Alert helper ── */
    function showError(msg) {
      const box = document.getElementById('alertBox');
      document.getElementById('alertMsg').textContent = msg;
      box.classList.remove('alert-hidden');
      // Re-trigger animation
      box.style.animation = 'none';
      box.offsetHeight; // reflow
      box.style.animation = '';
    }
    function hideError() {
      document.getElementById('alertBox').classList.add('alert-hidden');
    }

    /* ── Form submit ── */
    document.getElementById('loginForm').addEventListener('submit', async (e) => {
      e.preventDefault();
      hideError();

      const username = document.getElementById('username').value.trim();
      const password = document.getElementById('password').value;
      const btn      = document.getElementById('loginBtn');
      const spinner  = document.getElementById('btnSpinner');

      if (!username || !password) {
        showError('Please enter your username and password.');
        return;
      }

      // Loading state
      btn.disabled           = true;
      btn.childNodes[0].textContent = 'Signing in…';
      spinner.style.display  = 'block';

      try {
        const res  = await fetch(API_BASE + 'api_login.php', {
          method:  'POST',
          headers: { 'Content-Type': 'application/json' },
          body:    JSON.stringify({ username, password }),
        });

        const json = await res.json();

        if (json.success) {
          // Success — redirect to admin dashboard
          btn.childNodes[0].textContent = 'Redirecting…';
          window.location.href = json.redirect || 'index.php';
        } else {
          showError(json.error || 'Invalid credentials.');
          document.getElementById('password').value = '';
          document.getElementById('password').focus();
        }

      } catch (err) {
        showError('Connection error. Make sure XAMPP is running.');
      } finally {
        btn.disabled           = false;
        spinner.style.display  = 'none';
        if (!btn.childNodes[0].textContent.includes('Redirect')) {
          btn.childNodes[0].textContent = 'Sign In';
        }
      }
    });

    /* ── Focus username on load ── */
    document.getElementById('username').focus();
  </script>
</body>
</html>
