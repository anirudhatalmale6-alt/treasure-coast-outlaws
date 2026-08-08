<?php
require_once __DIR__ . '/includes/functions.php';

$news       = getPosts('news', 12);
$highlights = getHighlights(60);   // photos + videos combined, newest first

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

<!-- ── News ─────────────────────────────────────────────────────────────── -->
<section class="block" id="news">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">From the Dugout</span>
        <h2>Team News</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$news): ?>
      <div class="empty">No news posted yet. Check back soon.</div>
    <?php else: ?>
      <div class="grid news">
        <?php foreach ($news as $p): ?>
          <article class="card">
            <?php if (!empty($p['image_file'])): ?>
              <div class="thumb" style="background-image:url('<?= UPLOAD_URL . '/' . e($p['image_file']) ?>')"></div>
            <?php else: ?>
              <div class="thumb placeholder">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M4 5h16v14H4z"/><path d="M4 9h16M8 5v14"/></svg>
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
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
