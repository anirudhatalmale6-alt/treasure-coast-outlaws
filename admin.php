<?php
/**
 * Admin — the only page the team manager needs.
 *  • Log in with the password from includes/config.php
 *  • Post News / Photos / Videos (they appear instantly on the home page)
 *  • Delete anything you no longer want
 */
require_once __DIR__ . '/includes/functions.php';

$flash = null;      // ['type'=>'ok|err', 'msg'=>'...']

/* ── Logout ──────────────────────────────────────────────────────────────── */
if (isset($_GET['logout'])) {
    $_SESSION = [];
    session_destroy();
    header('Location: admin.php');
    exit;
}

/* ── Login ───────────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (hash_equals(ADMIN_PASSWORD, (string)($_POST['password'] ?? ''))) {
        $_SESSION['is_admin'] = true;
        header('Location: admin.php');
        exit;
    }
    $flash = ['type' => 'err', 'msg' => 'Wrong password. Try again.'];
}

/* ── Create a post ───────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create' && isAdmin()) {
    try {
        $type  = in_array($_POST['type'] ?? '', ['news', 'photo', 'video'], true) ? $_POST['type'] : 'news';
        $title = trim($_POST['title'] ?? '');
        $body  = trim($_POST['body'] ?? '');
        $videoUrl = trim($_POST['video_url'] ?? '');

        $imageFile = null;
        $videoFile = null;

        if ($type === 'news' || $type === 'photo') {
            $imageFile = handleUpload('image', 'image');
        }
        if ($type === 'video') {
            $videoFile = handleUpload('video', 'video');
        }

        // Validation per type
        if ($type === 'news' && $title === '' && $body === '') {
            throw new RuntimeException('Please add a headline or some text for the news post.');
        }
        if ($type === 'photo' && !$imageFile) {
            throw new RuntimeException('Please choose a photo to upload.');
        }
        if ($type === 'video' && !$videoFile && $videoUrl === '') {
            throw new RuntimeException('Add a video link (YouTube/Vimeo) or upload a video file.');
        }

        $stmt = getDB()->prepare("INSERT INTO posts (type, title, body, image_file, video_file, video_url)
                                  VALUES (:t,:ti,:b,:img,:vf,:vu)");
        $stmt->execute([
            ':t'   => $type,
            ':ti'  => $title,
            ':b'   => $body,
            ':img' => $imageFile,
            ':vf'  => $videoFile,
            ':vu'  => $videoUrl !== '' ? $videoUrl : null,
        ]);
        $flash = ['type' => 'ok', 'msg' => ucfirst($type) . ' posted! It\'s now live on the home page.'];
    } catch (Throwable $ex) {
        $flash = ['type' => 'err', 'msg' => $ex->getMessage()];
    }
}

/* ── Delete a post ───────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete' && isAdmin()) {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = getDB()->prepare("SELECT * FROM posts WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if ($row = $stmt->fetch()) {
        foreach (['image_file', 'video_file'] as $col) {
            if (!empty($row[$col]) && is_file(UPLOAD_DIR . '/' . $row[$col])) {
                @unlink(UPLOAD_DIR . '/' . $row[$col]);
            }
        }
        getDB()->prepare("DELETE FROM posts WHERE id = :id")->execute([':id' => $id]);
        $flash = ['type' => 'ok', 'msg' => 'Post deleted.'];
    }
}

$pageTitle = 'Admin';

/* ════════════════════════════════════════════════════════════════════════════
   LOGIN SCREEN
   ═══════════════════════════════════════════════════════════════════════════ */
if (!isAdmin()):
    include __DIR__ . '/includes/header.php';
?>
<div class="admin-shell">
  <div class="wrap">
    <form class="login-card" method="post" autocomplete="off">
      <img src="assets/img/logo.png" alt="<?= e(SITE_NAME) ?>">
      <h2>Admin Login</h2>
      <p style="color:var(--steel);margin-bottom:22px;font-size:.92rem">Sign in to post news, photos and videos.</p>
      <?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= e($flash['msg']) ?></div><?php endif; ?>
      <input type="hidden" name="action" value="login">
      <div class="field">
        <label for="pw">Password</label>
        <input type="password" id="pw" name="password" required autofocus>
      </div>
      <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Sign In</button>
    </form>
  </div>
</div>
<?php
    include __DIR__ . '/includes/footer.php';
    exit;
endif;

/* ════════════════════════════════════════════════════════════════════════════
   DASHBOARD (logged in)
   ═══════════════════════════════════════════════════════════════════════════ */
$allPosts = getPosts(null, 200);
include __DIR__ . '/includes/header.php';
?>
<div class="admin-shell">
  <div class="wrap">
    <div class="admin-head">
      <div>
        <span class="section-label">Control Room</span>
        <h2 style="margin:0">Post Manager</h2>
      </div>
      <div style="display:flex;gap:10px">
        <a class="btn btn-ghost btn-sm" href="index.php" target="_blank">View Site &#8599;</a>
        <a class="btn btn-ghost btn-sm" href="admin.php?logout=1">Log Out</a>
      </div>
    </div>

    <?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= e($flash['msg']) ?></div><?php endif; ?>

    <div class="admin-cols">
      <!-- New post form -->
      <div class="panel">
        <h3>Create a Post</h3>
        <form method="post" enctype="multipart/form-data" id="postForm">
          <input type="hidden" name="action" value="create">
          <input type="hidden" name="type" id="typeInput" value="news">

          <div class="type-tabs">
            <button type="button" class="active" data-type="news" onclick="setType(this)">News</button>
            <button type="button" data-type="photo" onclick="setType(this)">Photo</button>
            <button type="button" data-type="video" onclick="setType(this)">Video</button>
          </div>

          <!-- Title (all types) -->
          <div class="field">
            <label for="title" id="titleLabel">Headline</label>
            <input type="text" id="title" name="title" placeholder="e.g. Outlaws win season opener 8–3">
          </div>

          <!-- Body / caption (all types) -->
          <div class="field">
            <label for="body" id="bodyLabel">Text</label>
            <textarea id="body" name="body" placeholder="Write the story, caption or description here..."></textarea>
          </div>

          <!-- Image upload (news + photo) -->
          <div class="field" id="imageField">
            <label for="image" id="imageLabel">Photo <span style="color:var(--muted);text-transform:none;letter-spacing:0">(optional for news)</span></label>
            <input type="file" id="image" name="image" accept="image/*">
            <div class="hint">JPG, PNG, GIF or WEBP.</div>
          </div>

          <!-- Video fields (video only) -->
          <div class="field hide" id="videoUrlField">
            <label for="video_url">Video Link</label>
            <input type="text" id="video_url" name="video_url" placeholder="Paste a YouTube or Vimeo link">
            <div class="hint">Easiest option — just paste the link from YouTube or Vimeo.</div>
          </div>
          <div class="field hide" id="videoFileField">
            <label for="video">…or upload a video file</label>
            <input type="file" id="video" name="video" accept="video/*">
            <div class="hint">MP4, WEBM or MOV. Large files depend on your host's limit.</div>
          </div>

          <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Publish</button>
        </form>
      </div>

      <!-- Existing posts -->
      <div class="panel">
        <h3>Posted Items <span style="color:var(--muted);font-weight:400">(<?= count($allPosts) ?>)</span></h3>
        <?php if (!$allPosts): ?>
          <div class="empty">Nothing posted yet. Create your first post on the left.</div>
        <?php else: ?>
          <?php foreach ($allPosts as $p): ?>
            <div class="post-row">
              <div class="pv" <?= !empty($p['image_file']) ? 'style="background-image:url(\'' . UPLOAD_URL . '/' . e($p['image_file']) . '\')"' : '' ?>>
                <?php if (empty($p['image_file'])) echo $p['type'] === 'video' ? '▶ VIDEO' : ($p['type'] === 'news' ? 'NEWS' : 'PHOTO'); ?>
              </div>
              <div class="meta">
                <span class="tag"><?= e(ucfirst($p['type'])) ?></span>
                <h4><?= e($p['title'] ?: ($p['body'] ?: '(untitled)')) ?></h4>
                <small><?= e(niceDate($p['created_at'])) ?></small>
              </div>
              <form method="post" onsubmit="return confirm('Delete this post?')">
                <input type="hidden" name="action" value="delete">
                <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
function setType(btn){
  document.querySelectorAll('.type-tabs button').forEach(b=>b.classList.remove('active'));
  btn.classList.add('active');
  var t = btn.getAttribute('data-type');
  document.getElementById('typeInput').value = t;

  var imageField = document.getElementById('imageField');
  var videoUrlField = document.getElementById('videoUrlField');
  var videoFileField = document.getElementById('videoFileField');
  var titleLabel = document.getElementById('titleLabel');
  var bodyLabel = document.getElementById('bodyLabel');
  var imageLabel = document.getElementById('imageLabel');

  // reset
  imageField.classList.add('hide');
  videoUrlField.classList.add('hide');
  videoFileField.classList.add('hide');

  if (t === 'news'){
    imageField.classList.remove('hide');
    titleLabel.textContent = 'Headline';
    bodyLabel.textContent = 'Story';
    imageLabel.innerHTML = 'Photo <span style="color:var(--muted);text-transform:none;letter-spacing:0">(optional)</span>';
  } else if (t === 'photo'){
    imageField.classList.remove('hide');
    titleLabel.textContent = 'Caption';
    bodyLabel.textContent = 'Description (optional)';
    imageLabel.innerHTML = 'Photo <span style="color:var(--danger);text-transform:none;letter-spacing:0">(required)</span>';
  } else {
    videoUrlField.classList.remove('hide');
    videoFileField.classList.remove('hide');
    titleLabel.textContent = 'Title';
    bodyLabel.textContent = 'Description (optional)';
  }
}
</script>
<?php include __DIR__ . '/includes/footer.php'; ?>
