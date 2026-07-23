/* ═══════════════════════════════════════════════════════
   Calvin Christian Portfolio — main.js
   ═══════════════════════════════════════════════════════ */

const API_BASE = 'php/';

/* ════════════════════════════════════
   1. CUSTOM CURSOR
════════════════════════════════════ */
(function initCursor() {
  const cursor   = document.getElementById('cursor');
  const follower = document.getElementById('cursorFollower');
  let mx = -100, my = -100, fx = -100, fy = -100;

  document.addEventListener('mousemove', e => { mx = e.clientX; my = e.clientY; });

  function animCursor() {
    if (cursor)   { cursor.style.left = mx + 'px'; cursor.style.top = my + 'px'; }
    if (follower) {
      fx += (mx - fx) * 0.1;
      fy += (my - fy) * 0.1;
      follower.style.left = fx + 'px';
      follower.style.top  = fy + 'px';
    }
    requestAnimationFrame(animCursor);
  }
  animCursor();
})();

/* ════════════════════════════════════
   2. NAV SCROLL BEHAVIOUR
════════════════════════════════════ */
(function initNav() {
  const nav = document.getElementById('nav');
  if (!nav) return;
  window.addEventListener('scroll', () => {
    nav.classList.toggle('scrolled', window.scrollY > 60);
  }, { passive: true });
})();

/* ════════════════════════════════════
   3. PARALLAX ON SCROLL
════════════════════════════════════ */
(function initParallax() {
  const heroBg      = document.getElementById('heroBg');
  const heroContent = document.querySelector('.hero-content');
  const parallaxEls = document.querySelectorAll('[data-parallax]');

  function onScroll() {
    const sy = window.scrollY;

    if (heroBg)      heroBg.style.transform      = `translateY(${sy * 0.4}px)`;
    if (heroContent) {
      const rate = parseFloat(heroContent.dataset.parallax) || 0.3;
      heroContent.style.transform = `translateY(${sy * rate}px)`;
      heroContent.style.opacity   = Math.max(0, 1 - sy / 500);
    }

    parallaxEls.forEach(el => {
      if (el === heroContent) return;
      const rect = el.getBoundingClientRect();
      const rate = parseFloat(el.dataset.parallax) || 0.1;
      const mid  = window.innerHeight / 2 - rect.top - rect.height / 2;
      el.style.transform = `translateY(${mid * rate * -1}px)`;
    });
  }

  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();
})();

/* ════════════════════════════════════
   4. SCROLL-TRIGGERED FADE-UPS
════════════════════════════════════ */
(function initFadeUps() {
  const targets = [
    '.section-header', '.about-image-col', '.about-text-col',
    '.process-step', '.contact-left', '.contact-right',
    '.reel-strip', '.gallery-filter'
  ];
  targets.forEach(sel => {
    document.querySelectorAll(sel).forEach((el, i) => {
      el.classList.add('fade-up');
      el.style.transitionDelay = (i * 0.08) + 's';
    });
  });

  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (e.isIntersecting) { e.target.classList.add('visible'); io.unobserve(e.target); }
    });
  }, { threshold: 0.1 });

  document.querySelectorAll('.fade-up').forEach(el => io.observe(el));
})();

/* ════════════════════════════════════
   5. HERO VIDEO FALLBACK
════════════════════════════════════ */
(function initHeroVideo() {
  const video    = document.getElementById('heroVideo');
  const fallback = document.getElementById('heroFallback');
  if (!video) return;

  function showFallback() {
    video.style.display = 'none';
    if (fallback) fallback.classList.add('active');
  }

  video.addEventListener('error', showFallback);
  const source = video.querySelector('source');
  if (source) source.addEventListener('error', showFallback);

  setTimeout(() => { if (video.readyState === 0) showFallback(); }, 15000);
  video.play().catch(() => {});
})();

/* ════════════════════════════════════
   6. COUNTER ANIMATION
════════════════════════════════════ */
(function initCounters() {
  const counters = document.querySelectorAll('.stat-num[data-count]');
  if (!counters.length) return;

  const io = new IntersectionObserver(entries => {
    entries.forEach(e => {
      if (!e.isIntersecting) return;
      const el     = e.target;
      const target = parseInt(el.dataset.count);
      const start  = performance.now();
      io.unobserve(el);
      function tick(now) {
        const t     = Math.min(1, (now - start) / 1400);
        const eased = 1 - Math.pow(1 - t, 3);
        el.textContent = Math.round(eased * target);
        if (t < 1) requestAnimationFrame(tick);
      }
      requestAnimationFrame(tick);
    });
  }, { threshold: 0.5 });

  counters.forEach(c => io.observe(c));
})();

/* ════════════════════════════════════
   7. FEATURED CAROUSEL
   KEY FIX: use carousel.scrollLeft instead of
   slide.scrollIntoView() — scrollIntoView causes
   the whole PAGE to jump to the carousel section.
════════════════════════════════════ */
async function loadFeaturedCarousel() {
  const carousel = document.getElementById('featuredCarousel');
  const dotsWrap = document.getElementById('carouselDots');
  if (!carousel) return;

  let slides  = [];
  let current = 0;

  // ── Scroll the carousel TRACK only, never the page ──────────
  function scrollToSlide(idx) {
    if (!slides.length) return;
    current = Math.max(0, Math.min(idx, slides.length - 1));
    const slide     = slides[current];
    // scrollLeft centres the slide inside the scrollable track
    const trackLeft = carousel.getBoundingClientRect().left;
    const slideLeft = slide.getBoundingClientRect().left;
    const offset    = slideLeft - trackLeft - (carousel.clientWidth - slide.offsetWidth) / 2;
    carousel.scrollBy({ left: offset, behavior: 'smooth' });
    updateDots();
  }

  function updateDots() {
    if (!dotsWrap) return;
    dotsWrap.querySelectorAll('.carousel-dot').forEach((d, i) => {
      d.classList.toggle('active', i === current);
    });
  }

  try {
    const res      = await fetch(API_BASE + 'api_projects.php?featured=1&limit=8');
    const json     = await res.json();
    const projects = json.data || [];

    if (!projects.length) {
      carousel.innerHTML = `
        <div class="carousel-slide">
          <div style="width:100%;height:100%;background:linear-gradient(135deg,#1a3d3a,#0d1f1e);display:flex;align-items:center;justify-content:center;">
            <p style="color:rgba(240,237,230,0.3);font-family:'Space Mono',monospace;font-size:.75rem;letter-spacing:.15em;text-align:center;padding:2rem;">
              ADD YOUR FIRST PROJECT<br/>via the Admin Panel
            </p>
          </div>
          <div class="carousel-info">
            <div class="slide-category">Videography</div>
            <div class="slide-title">Your First Project</div>
          </div>
        </div>`;
      return;
    }

    carousel.innerHTML = '';
    if (dotsWrap) dotsWrap.innerHTML = '';

    projects.forEach((proj, i) => {
      const slide = document.createElement('div');
      slide.className    = 'carousel-slide';
      slide.dataset.index = i;

      const mediaEl = proj.cover_url
        ? `<img src="${proj.cover_url}" alt="${escHtml(proj.title)}" loading="lazy" />`
        : `<div style="width:100%;height:100%;background:linear-gradient(135deg,#1a3d3a,#0d0d0d)"></div>`;

      slide.innerHTML = `
        ${mediaEl}
        ${proj.video_url ? '<div class="slide-play">▶</div>' : ''}
        <div class="carousel-info">
          <div class="slide-category">${escHtml(proj.category)}</div>
          <div class="slide-title">${escHtml(proj.title)}</div>
        </div>`;

      if (proj.video_url) {
        slide.addEventListener('click', () => openLightbox('video', proj.video_url, proj.title));
      } else if (proj.cover_url) {
        slide.addEventListener('click', () => openLightbox('image', proj.cover_url, proj.title));
      }

      carousel.appendChild(slide);
      slides.push(slide);

      if (dotsWrap) {
        const dot = document.createElement('button');
        dot.className = 'carousel-dot' + (i === 0 ? ' active' : '');
        dot.setAttribute('aria-label', `Slide ${i + 1}`);
        dot.addEventListener('click', () => scrollToSlide(i));
        dotsWrap.appendChild(dot);
      }
    });

    // Prev / Next buttons
    document.getElementById('carouselPrev')?.addEventListener('click', () => scrollToSlide(current - 1));
    document.getElementById('carouselNext')?.addEventListener('click', () => scrollToSlide(current + 1));

    // Touch / swipe support
    let startX = 0;
    carousel.addEventListener('touchstart', e => { startX = e.touches[0].clientX; }, { passive: true });
    carousel.addEventListener('touchend',   e => {
      const diff = startX - e.changedTouches[0].clientX;
      if (Math.abs(diff) > 50) scrollToSlide(diff > 0 ? current + 1 : current - 1);
    });

    // Update current index as the user scrolls the carousel manually
    carousel.addEventListener('scroll', () => {
      const centre = carousel.scrollLeft + carousel.clientWidth / 2;
      let closest = 0, minDist = Infinity;
      slides.forEach((s, i) => {
        const dist = Math.abs(s.offsetLeft + s.offsetWidth / 2 - centre);
        if (dist < minDist) { minDist = dist; closest = i; }
      });
      if (closest !== current) { current = closest; updateDots(); }
    }, { passive: true });

  } catch (err) {
    console.warn('Carousel fetch failed:', err);
    carousel.innerHTML = `<div style="padding:3rem;color:var(--ash);font-family:'Space Mono',monospace;font-size:.75rem;">
      Could not load projects. Make sure XAMPP &amp; the database are running.</div>`;
  }
}

/* ════════════════════════════════════
   8. GALLERY GRID
════════════════════════════════════ */
async function loadGallery() {
  const grid = document.getElementById('galleryGrid');
  if (!grid) return;

  try {
    const res   = await fetch(API_BASE + 'api_media.php?limit=40');
    const json  = await res.json();
    const items = json.data || [];

    if (!items.length) {
      grid.innerHTML = `<p style="color:var(--ash);font-family:'Space Mono',monospace;font-size:.75rem;letter-spacing:.1em;padding:2rem 0;">
        No media yet — upload photos &amp; videos via the Admin Panel.</p>`;
      return;
    }

    grid.innerHTML = '';
    items.forEach(item => {
      const el = document.createElement('div');
      el.className      = 'gallery-item';
      el.dataset.type   = item.type;

      if (item.type === 'photo') {
        el.innerHTML = `
          <img src="${item.file_url}" alt="${escHtml(item.title)}" loading="lazy" />
          <div class="gallery-item-overlay">
            <span>${escHtml(item.category)}</span>
            <span>${escHtml(item.title)}</span>
          </div>`;
        el.addEventListener('click', () => openLightbox('image', item.file_url, item.title));
      } else {
        el.innerHTML = `
          <video src="${item.file_url}" muted preload="metadata" poster="${item.thumbnail_url || ''}"></video>
          <div class="gallery-item-overlay">
            <div class="play-icon">▶</div>
            <span>${escHtml(item.title)}</span>
          </div>`;
        el.addEventListener('click', () => openLightbox('video', item.file_url, item.title));
      }
      grid.appendChild(el);
    });

    // Video hover preview
    grid.querySelectorAll('.gallery-item video').forEach(vid => {
      vid.parentElement.addEventListener('mouseenter', () => vid.play().catch(() => {}));
      vid.parentElement.addEventListener('mouseleave', () => { vid.pause(); vid.currentTime = 0; });
    });

    // Filter buttons
    document.querySelectorAll('.filter-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');
        const f = btn.dataset.filter;
        document.querySelectorAll('.gallery-item').forEach(item => {
          item.classList.toggle('hidden', f !== 'all' && item.dataset.type !== f);
        });
      });
    });

    // Show See More button if enough items
    const moreWrap = document.getElementById('galleryMoreWrap');
    if (moreWrap && items.length > 6) moreWrap.style.display = 'flex';

  } catch (err) {
    console.warn('Gallery fetch failed:', err);
    grid.innerHTML = `<p style="color:var(--ash);font-family:'Space Mono',monospace;font-size:.75rem;padding:2rem 0;">
      Could not load gallery. Ensure XAMPP is running.</p>`;
  }
}

/* Gallery See More / See Less toggle */
let _galleryExpanded = false;
function toggleGallery() {
  const grid = document.getElementById('galleryGrid');
  const btn  = document.getElementById('galleryMoreBtn');
  if (!grid || !btn) return;
  _galleryExpanded = !_galleryExpanded;
  const text = btn.querySelector('.gmb-text');
  if (_galleryExpanded) {
    grid.classList.remove('gallery-collapsed');
    grid.classList.add('gallery-expanded');
    text.textContent = 'See Less';
    btn.classList.add('expanded');
  } else {
    grid.classList.add('gallery-collapsed');
    grid.classList.remove('gallery-expanded');
    text.textContent = 'See More';
    btn.classList.remove('expanded');
    document.getElementById('gallery')?.scrollIntoView({ behavior:'smooth', block:'start' });
  }
}


/* ════════════════════════════════════
   9. LIGHTBOX
════════════════════════════════════ */
function openLightbox(type, src, caption) {
  const lb      = document.getElementById('lightbox');
  const content = document.getElementById('lightboxContent');
  const cap     = document.getElementById('lightboxCaption');
  if (!lb || !content) return;

  if (type === 'video') {
    // Encode URL safely — handles spaces and special chars in filenames
    // Only encode if it's a local path (not already a full encoded URL)
    const safeSrc = src.includes('%') ? src : src.split('/').map((seg, i) => i === 0 ? seg : encodeURIComponent(seg)).join('/');

    // Detect if it's an external embed (YouTube/Vimeo)
    if (/youtu\.?be|vimeo\.com/i.test(src)) {
      // Convert to embed URL
      let embedSrc = src;
      if (/youtu\.?be/i.test(src)) {
        const id = src.match(/(?:v=|youtu\.be\/)([^&?/]+)/)?.[1];
        if (id) embedSrc = `https://www.youtube.com/embed/${id}?autoplay=1`;
      } else if (/vimeo/i.test(src)) {
        const id = src.match(/vimeo\.com\/(\d+)/)?.[1];
        if (id) embedSrc = `https://player.vimeo.com/video/${id}?autoplay=1`;
      }
      content.innerHTML = `<iframe src="${escHtml(embedSrc)}" style="width:90vw;max-width:1100px;height:56.25vw;max-height:80vh;" frameborder="0" allow="autoplay;fullscreen" allowfullscreen></iframe>`;
    } else {
      // Determine MIME type from extension
      const ext  = src.split('.').pop().toLowerCase().split('?')[0];
      const mime = { mp4:'video/mp4', mov:'video/mp4', webm:'video/webm', avi:'video/mp4', mkv:'video/webm' }[ext] || 'video/mp4';
      content.innerHTML = `
        <video controls autoplay playsinline
          style="max-width:90vw;max-height:80vh;width:auto;height:auto;background:#000;display:block;"
          onerror="document.getElementById('lbErr').style.display='block'">
          <source src="${escHtml(safeSrc)}" type="${mime}" />
          Your browser does not support this video format.
        </video>
        <div id="lbErr" style="display:none;color:#e05252;font-size:.85rem;margin-top:.75rem;text-align:center;font-family:'Space Mono',monospace">
          ⚠ Could not load video. Check that the file exists at: <code style="word-break:break-all">${escHtml(src)}</code>
        </div>`;
    }
  } else {
    content.innerHTML = `<img src="${escHtml(src)}" alt="${escHtml(caption || '')}" style="max-width:90vw;max-height:80vh;object-fit:contain;" />`;
  }

  if (cap) cap.textContent = caption || '';
  lb.classList.add('active');
  document.body.style.overflow = 'hidden';
}

function closeLightbox() {
  const lb      = document.getElementById('lightbox');
  const content = document.getElementById('lightboxContent');
  if (!lb) return;
  lb.classList.remove('active');
  if (content) content.innerHTML = '';
  document.body.style.overflow = '';
}

document.getElementById('lightboxClose')?.addEventListener('click', closeLightbox);
document.getElementById('lightbox')?.addEventListener('click', e => {
  if (e.target === document.getElementById('lightbox')) closeLightbox();
});
document.addEventListener('keydown', e => { if (e.key === 'Escape') closeLightbox(); });

/* ════════════════════════════════════
   10. CONTACT FORM
════════════════════════════════════ */
(function initContactForm() {
  const form = document.getElementById('contactForm');
  const btn  = document.getElementById('formSubmit');
  const fb   = document.getElementById('formFeedback');
  if (!form) return;

  form.addEventListener('submit', async e => {
    e.preventDefault();
    btn.classList.add('loading');
    fb.className   = 'form-feedback';
    fb.textContent = '';

    const body = {
      name:    form.name.value.trim(),
      email:   form.email.value.trim(),
      subject: form.subject?.value.trim() || '',
      message: form.message.value.trim(),
    };

    try {
      const res  = await fetch(API_BASE + 'api_contact.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify(body),
      });
      const json = await res.json();
      if (json.success) {
        fb.className   = 'form-feedback success';
        fb.textContent = json.message || 'Message sent!';
        form.reset();
      } else {
        throw new Error((json.errors || [json.error]).join(', '));
      }
    } catch (err) {
      fb.className   = 'form-feedback error';
      fb.textContent = err.message || 'Failed to send. Please email directly.';
    } finally {
      btn.classList.remove('loading');
    }
  });
})();

/* ════════════════════════════════════
   11. MOBILE NAV TOGGLE
════════════════════════════════════ */
(function initMobileNav() {
  const toggle = document.getElementById('navToggle');
  const links  = document.querySelector('.nav-links');
  if (!toggle || !links) return;

  toggle.addEventListener('click', () => {
    const open = links.style.display === 'flex';
    links.style.cssText = open ? '' :
      'display:flex;flex-direction:column;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(13,13,13,.97);align-items:center;justify-content:center;gap:2.5rem;z-index:999;';
    links.querySelectorAll('.nav-link').forEach(a => {
      a.style.cssText = open ? '' : 'font-size:1.8rem;letter-spacing:.1em;';
    });
  });
  links.querySelectorAll('.nav-link').forEach(a => {
    a.addEventListener('click', () => { links.style.cssText = ''; });
  });
})();

/* ════════════════════════════════════
   12. SMOOTH ANCHOR SCROLLING
   Intercept hash links so only the window scrolls,
   never triggered by the carousel internals.
════════════════════════════════════ */
document.querySelectorAll('a[href^="#"]').forEach(a => {
  a.addEventListener('click', e => {
    const target = document.querySelector(a.getAttribute('href'));
    if (target) { e.preventDefault(); target.scrollIntoView({ behavior: 'smooth' }); }
  });
});

/* ════════════════════════════════════
   UTILITY
════════════════════════════════════ */
function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g,  '&lt;')
    .replace(/>/g,  '&gt;')
    .replace(/"/g,  '&quot;');
}

/* ════════════════════════════════════
   13. Q&A ACCORDION
════════════════════════════════════ */
(function initQA() {
  document.querySelectorAll('.qa-question').forEach(btn => {
    btn.addEventListener('click', () => {
      const answer   = btn.nextElementSibling;
      const expanded = btn.getAttribute('aria-expanded') === 'true';

      // Close all others
      document.querySelectorAll('.qa-question').forEach(b => {
        b.setAttribute('aria-expanded', 'false');
        b.nextElementSibling.classList.remove('open');
      });

      // Toggle clicked
      if (!expanded) {
        btn.setAttribute('aria-expanded', 'true');
        answer.classList.add('open');
      }
    });
  });
})();

/* ════════════════════════════════════
   14. BOOKING MODAL
════════════════════════════════════ */
(function initBooking() {

  /* ── Data ─────────────────────────────── */
  const SERVICES = {
    photography: {
      label: 'Photography',
      themes: ['Property', 'Wedding', 'Lifestyle', 'Portrait', 'Commercial'],
      stepTitle: 'Choose your<br/><em>theme</em>',
      price: (theme) => ({
        amount: 'Rp 1.000.000 / hour',
        note: 'Starting price — final quote based on hours & location.'
      }),
    },
    videography: {
      label: 'Videography',
      themes: ['Property', 'Wedding', 'Lifestyle', 'Commercial', 'Documenter', 'Event'],
      stepTitle: 'Choose your<br/><em>theme</em>',
      price: (theme) => {
        const map = {
          'Wedding':    { amount: 'Rp 10.000.000', note: 'Starting price for full wedding coverage.' },
          'Commercial': { amount: 'Rp 5.000.000',  note: 'Starting price for commercial productions.' },
          'Property':   { amount: 'Rp 3.000.000',  note: 'Starting price for property tours.' },
          'Lifestyle':  { amount: 'Rp 1.000.000',  note: 'Starting price for lifestyle shoots.' },
          'Documenter': { amount: 'Rp 3.000.000',  note: 'Starting price for documentary projects.' },
          'Event':      { amount: 'Rp 2.000.000',  note: 'Starting price for event coverage.' },
        };
        return map[theme] || { amount: 'Custom Quote', note: 'Get in touch for pricing details.' };
      },
    },
    editing: {
      label: 'Video Editing',
      themes: [],  // handled separately
      stepTitle: 'Choose your<br/><em>package</em>',
      editingPackages: [
        { id: 'full',  label: 'Full Package', sub: 'Assembling & Cutting · Colour Grading · Typography · Motion Graphics · Sound Mixing & Audio Enhancement' },
        { id: 'basic', label: 'Basic Cut',    sub: 'Assembling & Cutting only' },
      ],
      price: (pkg) => {
        if (pkg === 'full')  return { amount: 'Rp 2.000.000 – 5.000.000', note: 'Short-form from Rp 2.000.000 · Long-form from Rp 5.000.000' };
        if (pkg === 'basic') return { amount: 'Custom Quote', note: 'Pricing based on footage length.' };
        return { amount: 'Custom Quote', note: '' };
      },
    },
  };

  /* ── State ───────────────────────────── */
  let state = { service: null, theme: null, editingPkg: null, contentType: '', payment: null };

  /* ── Elements ────────────────────────── */
  const overlay    = document.getElementById('bookingOverlay');
  const modal      = document.getElementById('bookingModal');
  const bpFill     = document.getElementById('bpFill');
  const bpLabel    = document.getElementById('bpLabel');
  const step2Title = document.getElementById('step2Title');
  const themeGrid  = document.getElementById('themeGrid');
  const editExtras = document.getElementById('editingExtras');
  const pkgGrid    = document.getElementById('editingPackageGrid');
  const step2Next  = document.getElementById('step2Next');
  const priceSumEl = document.getElementById('priceSummary');
  const feedback   = document.getElementById('bookingFeedback');

  /* ── Open / Close ─────────────────────── */
  function openBooking() {
    overlay.classList.add('active');
    document.body.style.overflow = 'hidden';
    goStep(1);
  }
  window.closeBooking = function () {
    overlay.classList.remove('active');
    document.body.style.overflow = '';
    // reset state
    state = { service: null, theme: null, editingPkg: null, contentType: '', payment: null };
    document.querySelectorAll('.service-card, .theme-btn, .payment-opt').forEach(el => el.classList.remove('selected'));
  };

  document.getElementById('openBooking')?.addEventListener('click', openBooking);
  document.getElementById('bookingClose')?.addEventListener('click', closeBooking);
  overlay?.addEventListener('click', e => { if (e.target === overlay) closeBooking(); });
  document.addEventListener('keydown', e => { if (e.key === 'Escape' && overlay.classList.contains('active')) closeBooking(); });

  /* ── Step navigation ──────────────────── */
  window.goStep = function (n) {
    document.querySelectorAll('.booking-step').forEach(s => s.classList.remove('active'));
    document.getElementById('step' + n)?.classList.add('active');
    const pct = { 1: '25%', 2: '50%', 3: '75%', 4: '100%' };
    bpFill.style.width = pct[n] || '25%';
    bpLabel.textContent = `Step ${n} of 4`;
    modal.scrollTop = 0;
  };

  /* ── Step 1: service selection ─────────── */
  document.querySelectorAll('.service-card').forEach(card => {
    card.addEventListener('click', () => {
      document.querySelectorAll('.service-card').forEach(c => c.classList.remove('selected'));
      card.classList.add('selected');
      state.service = card.dataset.service;
      state.theme   = null;
      state.editingPkg = null;
      buildStep2();
      goStep(2);
    });
  });

  /* ── Build step 2 dynamically ─────────── */
  function buildStep2() {
    const svc = SERVICES[state.service];
    if (!svc) return;

    step2Title.innerHTML = svc.stepTitle;
    themeGrid.innerHTML  = '';
    pkgGrid.innerHTML    = '';
    editExtras.style.display = 'none';
    step2Next.disabled   = true;

    if (state.service === 'editing') {
      // Show editing package buttons + content type textarea
      editExtras.style.display = 'block';
      svc.editingPackages.forEach(pkg => {
        const btn = document.createElement('button');
        btn.className  = 'theme-btn';
        btn.innerHTML  = `<strong>${pkg.label}</strong>`;
        btn.title      = pkg.sub;
        btn.addEventListener('click', () => {
          pkgGrid.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
          state.editingPkg = pkg.id;
          state.theme      = pkg.label;
          checkStep2Valid();
          updatePriceSummary();
        });
        pkgGrid.appendChild(btn);
      });

      // Show "Package includes" tooltip on hover
      const hint = document.createElement('p');
      hint.style.cssText = 'font-size:.78rem;color:var(--ash);margin-bottom:.75rem;line-height:1.6;';
      hint.textContent   = 'Hover a package to see what\'s included.';
      pkgGrid.before(hint);

    } else {
      // Show theme buttons
      svc.themes.forEach(theme => {
        const btn = document.createElement('button');
        btn.className   = 'theme-btn';
        btn.textContent = theme;
        btn.addEventListener('click', () => {
          themeGrid.querySelectorAll('.theme-btn').forEach(b => b.classList.remove('selected'));
          btn.classList.add('selected');
          state.theme = theme;
          checkStep2Valid();
          updatePriceSummary();
        });
        themeGrid.appendChild(btn);
      });
    }
  }

  function checkStep2Valid() {
    if (state.service === 'editing') {
      step2Next.disabled = !state.editingPkg;
    } else {
      step2Next.disabled = !state.theme;
    }
  }

  /* ── Price summary ────────────────────── */
  function updatePriceSummary() {
    const svc = SERVICES[state.service];
    if (!svc) return;

    let priceData;
    if (state.service === 'editing') {
      priceData = svc.price(state.editingPkg);
    } else {
      priceData = svc.price(state.theme);
    }
    if (!priceData) return;

    priceSumEl.classList.add('visible');
    priceSumEl.innerHTML = `
      <div class="ps-service">${svc.label} · ${state.theme || ''}</div>
      <div class="ps-price">${priceData.amount}</div>
      ${priceData.note ? `<div class="ps-note">${priceData.note}</div>` : ''}
    `;
  }

  /* ── Payment selection ────────────────── */
  document.querySelectorAll('.payment-opt').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('.payment-opt').forEach(b => b.classList.remove('selected'));
      btn.classList.add('selected');
      state.payment = btn.dataset.pay;
    });
  });

  /* ── Submit booking ───────────────────── */
  window.submitBooking = async function () {
    feedback.className   = 'booking-feedback';
    feedback.textContent = '';

    const name  = document.getElementById('bName').value.trim();
    const email = document.getElementById('bEmail').value.trim();
    const phone = document.getElementById('bPhone').value.trim();
    const date  = document.getElementById('bDate').value;
    const notes = document.getElementById('bNotes').value.trim();
    const contentType = document.getElementById('contentTypeInput')?.value.trim() || '';

    if (!name || !email) {
      feedback.className   = 'booking-feedback error';
      feedback.textContent = 'Please fill in your name and email.';
      return;
    }
    if (!state.payment) {
      feedback.className   = 'booking-feedback error';
      feedback.textContent = 'Please choose a payment method.';
      return;
    }

    const svc = SERVICES[state.service];
    const subject = `Booking: ${svc?.label} · ${state.theme || ''}`;
    const msgParts = [
      `Service: ${svc?.label}`,
      `Theme / Package: ${state.theme || '—'}`,
      state.service === 'editing' && contentType ? `Content Type: ${contentType}` : '',
      `Date: ${date || 'TBD'}`,
      `Phone: ${phone || '—'}`,
      `Payment: ${state.payment === 'bank' ? 'Bank Transfer (Blu by BCA)' : 'PayPal'}`,
      notes ? `Notes: ${notes}` : '',
    ].filter(Boolean).join('\n');

    const btn = document.getElementById('step3Next');
    btn.disabled     = true;
    btn.textContent  = 'Sending…';

    try {
      const res  = await fetch(API_BASE + 'api_contact.php', {
        method:  'POST',
        headers: { 'Content-Type': 'application/json' },
        body:    JSON.stringify({ name, email, subject, message: msgParts }),
      });
      const json = await res.json();
      if (!json.success) throw new Error(json.error || 'Send failed');

      // Build confirmation screen
      const payInfo = state.payment === 'bank'
        ? `<h4>Payment Instructions — Bank Transfer</h4>
           <p>Bank: <strong>Blu by BCA</strong><br/>
           Account No: <code>007002991116</code><br/>
           Name: <strong>Calvin Christian</strong><br/><br/>
           Please transfer a <strong>50% deposit</strong> to confirm your booking, then send your proof of payment to <a href="mailto:youredit2026@gmail.com" style="color:var(--gold)">youredit2026@gmail.com</a>.</p>`
        : `<h4>Payment Instructions — PayPal</h4>
           <p>Send a <strong>50% deposit</strong> via PayPal to:<br/>
           <code>calvinchristian15k1@gmail.com</code><br/><br/>
           Include your name and booking details in the PayPal note, then email your confirmation to <a href="mailto:youredit2026@gmail.com" style="color:var(--gold)">youredit2026@gmail.com</a>.</p>`;

      document.getElementById('confirmMsg').textContent =
        `Thanks ${name}! Your ${svc?.label} booking request has been received. I'll reach out to ${email} within 24 hours to confirm details.`;
      document.getElementById('confirmPayment').innerHTML = payInfo;

      goStep(4);
    } catch (err) {
      feedback.className   = 'booking-feedback error';
      feedback.textContent = err.message || 'Something went wrong. Please email directly.';
    } finally {
      btn.disabled    = false;
      btn.textContent = 'Confirm Booking →';
    }
  };

})();

/* ════════════════════════════════════
   INIT
════════════════════════════════════ */
document.addEventListener('DOMContentLoaded', () => {
  loadFeaturedCarousel();
  loadGallery();
});
