<?php
require_once __DIR__ . '/includes/functions.php';

$news       = getPosts('news', 12);
$highlights = getHighlights(60);   // photos + videos combined, newest first

// Instagram feed (official API, cached). Empty array until a token is set.
require_once __DIR__ . '/includes/instagram.php';
$igPosts = igGetPosts();

$pageTitle = null; // homepage uses the plain site name
include __DIR__ . '/includes/header.php';
?>

<a id="top"></a>
<!-- ── Hero ─────────────────────────────────────────────────────────────── -->
<header class="hero">
  <div class="wrap">
    <img class="hero-logo" src="assets/img/logo.png" alt="<?= e(SITE_NAME) ?>">
    <h1>Treasure&nbsp;Coast <span class="thin">Outlaws</span></h1>
    <p class="lead">Follow the team — the latest news, game-day photos and highlight videos, all in one place.</p>
    <div class="cta">
      <a class="btn btn-primary" href="#news">Latest News</a>
      <a class="btn btn-ghost" href="#highlights">Highlights</a>
    </div>
  </div>
</header>

<!-- ── News (Instagram feed + any manual posts) ─────────────────────────── -->
<section class="block" id="news">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">From the Dugout</span>
        <h2>Team News</h2>
        <div class="divider"></div>
      </div>
      <a class="ig-follow" href="<?= e(IG_URL) ?>" target="_blank" rel="noopener">
        <svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
          <rect x="2" y="2" width="20" height="20" rx="5"/><circle cx="12" cy="12" r="4"/><circle cx="17.5" cy="6.5" r="1" fill="currentColor" stroke="none"/>
        </svg>
        <span>Follow @<?= e(IG_HANDLE) ?></span>
      </a>
    </div>

    <?php if ($igPosts): ?>
      <div class="ig-scroll-wrap">
        <button type="button" class="ig-arrow prev" aria-label="Scroll left" onclick="igScroll(-1)">&#8249;</button>
        <div class="ig-scroller" id="igScroller">
          <?php foreach ($igPosts as $post):
              $img = igPostImage($post);
              if ($img === '') continue;
              $isVideo = ($post['media_type'] ?? '') === 'VIDEO';
          ?>
            <a class="card ig-post" href="<?= e($post['permalink'] ?? IG_URL) ?>" target="_blank" rel="noopener">
              <div class="thumb" style="background-image:url('<?= e($img) ?>')">
                <?php if ($isVideo): ?><span class="ig-play" aria-hidden="true">▶</span><?php endif; ?>
              </div>
              <div class="body">
                <span class="date"><?= e(niceDate($post['timestamp'] ?? '')) ?></span>
                <?php $cap = igExcerpt($post['caption'] ?? ''); ?>
                <?php if ($cap !== ''): ?><p><?= e($cap) ?></p><?php endif; ?>
                <span class="ig-viewlink">View on Instagram →</span>
              </div>
            </a>
          <?php endforeach; ?>
        </div>
        <button type="button" class="ig-arrow next" aria-label="Scroll right" onclick="igScroll(1)">&#8250;</button>
      </div>
    <?php endif; ?>

    <?php if ($news): ?>
      <div class="grid news"<?= $igPosts ? ' style="margin-top:26px"' : '' ?>>
        <?php foreach ($news as $p): ?>
          <article class="card">
            <?php if (!empty($p['image_file'])): ?>
              <div class="thumb" style="background-image:url('<?= UPLOAD_URL . '/' . e($p['image_file']) ?>')"></div>
            <?php else: ?>
              <div class="thumb placeholder">
                <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 5h16v14H4z"/><path d="M4 9h16M8 5v14"/></svg>
              </div>
            <?php endif; ?>
            <div class="body">
              <span class="date"><?= e(niceDate($p['created_at'])) ?></span>
              <h3><?= e($p['title']) ?></h3>
              <?php if ($p['body'] !== ''): ?><p><?= e($p['body']) ?></p><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php elseif (!$igPosts): ?>
      <div class="empty">Our latest updates will appear here — follow us on Instagram in the meantime.</div>
    <?php endif; ?>
  </div>
</section>

<!-- ── Highlights (photos + videos combined) ────────────────────────────── -->
<section class="block" id="highlights">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">Photos &amp; Videos</span>
        <h2>Highlights</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$highlights): ?>
      <div class="empty">No highlights posted yet. Check back soon.</div>
    <?php else: ?>
      <div class="highlights-grid">
        <?php foreach ($highlights as $p): ?>
          <?php if ($p['type'] === 'photo' && !empty($p['image_file'])): ?>
            <a class="hl-item" href="#" data-full="<?= UPLOAD_URL . '/' . e($p['image_file']) ?>" onclick="return openLightbox(this)">
              <div class="hl-media" style="background-image:url('<?= UPLOAD_URL . '/' . e($p['image_file']) ?>')">
                <span class="hl-badge">Photo</span>
              </div>
              <?php if (($p['title'] ?: $p['body']) !== ''): ?>
                <div class="hl-cap"><?= e($p['title'] ?: $p['body']) ?></div>
              <?php endif; ?>
            </a>
          <?php elseif ($p['type'] === 'video'):
              $embed  = !empty($p['video_url']) ? videoEmbedUrl($p['video_url']) : null;
              $isFile = !empty($p['video_file']);
          ?>
            <div class="hl-item">
              <div class="hl-media video-frame">
                <?php if ($isFile): ?>
                  <video controls preload="metadata" src="<?= UPLOAD_URL . '/' . e($p['video_file']) ?>"></video>
                <?php elseif ($embed): ?>
                  <iframe src="<?= e($embed) ?>" title="<?= e($p['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
                <?php endif; ?>
                <span class="hl-badge">Video</span>
              </div>
              <?php if (($p['title'] ?: $p['body']) !== ''): ?>
                <div class="hl-cap"><?= e($p['title'] ?: $p['body']) ?></div>
              <?php endif; ?>
            </div>
          <?php endif; ?>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- Lightbox for photos -->
<div class="lightbox" id="lightbox" onclick="this.classList.remove('open')">
  <span class="close">&times;</span>
  <img id="lightboxImg" src="" alt="">
</div>

<script>
function openLightbox(a){
  document.getElementById('lightboxImg').src = a.getAttribute('data-full');
  document.getElementById('lightbox').classList.add('open');
  return false;
}
function igScroll(dir){
  var s = document.getElementById('igScroller');
  if (!s) return;
  var card = s.querySelector('.ig-post');
  var step = card ? (card.getBoundingClientRect().width + 20) : (s.clientWidth * 0.8);
  s.scrollBy({ left: dir * step, behavior: 'smooth' });
}
function igSyncArrows(){
  var s = document.getElementById('igScroller');
  if (!s) return;
  var wrap = s.closest('.ig-scroll-wrap');
  if (!wrap) return;
  // Only offer arrows when there's actually more to scroll to.
  wrap.classList.toggle('has-overflow', (s.scrollWidth - s.clientWidth) > 8);
}
window.addEventListener('load', igSyncArrows);
window.addEventListener('resize', igSyncArrows);
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
