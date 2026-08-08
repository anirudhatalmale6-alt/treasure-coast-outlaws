<?php
require_once __DIR__ . '/includes/functions.php';

$news   = getPosts('news', 12);
$photos = getPosts('photo', 24);
$videos = getPosts('video', 12);

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
      <a class="btn btn-ghost" href="#videos">Watch Highlights</a>
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

<!-- ── Photos ───────────────────────────────────────────────────────────── -->
<section class="block" id="photos">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">On the Field</span>
        <h2>Photo Gallery</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$photos): ?>
      <div class="empty">No photos posted yet. Check back soon.</div>
    <?php else: ?>
      <div class="gallery">
        <?php foreach ($photos as $p): if (empty($p['image_file'])) continue; ?>
          <a href="#" data-full="<?= UPLOAD_URL . '/' . e($p['image_file']) ?>" onclick="return openLightbox(this)">
            <img src="<?= UPLOAD_URL . '/' . e($p['image_file']) ?>" alt="<?= e($p['title'] ?: 'Outlaws photo') ?>" loading="lazy">
            <?php if (($p['title'] ?: $p['body']) !== ''): ?>
              <span class="cap"><?= e($p['title'] ?: $p['body']) ?></span>
            <?php endif; ?>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<!-- ── Videos ───────────────────────────────────────────────────────────── -->
<section class="block" id="videos">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">Highlights</span>
        <h2>Videos</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$videos): ?>
      <div class="empty">No videos posted yet. Check back soon.</div>
    <?php else: ?>
      <div class="grid videos">
        <?php foreach ($videos as $p):
            $embed = !empty($p['video_url']) ? videoEmbedUrl($p['video_url']) : null;
            $isFile = !empty($p['video_file']);
        ?>
          <div class="video-card">
            <div class="video-frame">
              <?php if ($isFile): ?>
                <video controls preload="metadata" src="<?= UPLOAD_URL . '/' . e($p['video_file']) ?>"></video>
              <?php elseif ($embed): ?>
                <iframe src="<?= e($embed) ?>" title="<?= e($p['title']) ?>" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
              <?php endif; ?>
            </div>
            <div class="body">
              <h3><?= e($p['title'] ?: 'Highlight') ?></h3>
              <?php if ($p['body'] !== ''): ?><p><?= e($p['body']) ?></p><?php endif; ?>
            </div>
          </div>
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
