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
    header('Location: admin');
    exit;
}

/* ── Login ───────────────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
    if (hash_equals(ADMIN_PASSWORD, (string)($_POST['password'] ?? ''))) {
        $_SESSION['is_admin'] = true;
        header('Location: admin');
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

/* ── Add a player to the roster ──────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create-player' && isAdmin()) {
    try {
        $name     = trim($_POST['name'] ?? '');
        $number   = trim($_POST['number'] ?? '');
        $position = trim($_POST['position'] ?? '');
        $bats     = strtoupper(trim($_POST['bats'] ?? ''));
        $throws   = strtoupper(trim($_POST['throws'] ?? ''));

        if ($name === '') throw new RuntimeException('Please enter the player\'s name.');
        if ($number !== '' && !preg_match('/^\d{1,3}$/', $number)) {
            throw new RuntimeException('Jersey number should be digits only (e.g. 7 or 00).');
        }
        if (!in_array($bats, ['', 'R', 'L', 'S'], true))   $bats = '';
        if (!in_array($throws, ['', 'R', 'L'], true))      $throws = '';

        $photoFile = handleUpload('photo', 'image');   // optional headshot

        getDB()->prepare("INSERT INTO players (name, number, position, bats, throws, photo_file)
                          VALUES (:n,:num,:pos,:b,:t,:ph)")
               ->execute([
                   ':n'   => $name,
                   ':num' => $number !== '' ? $number : null,
                   ':pos' => $position !== '' ? $position : null,
                   ':b'   => $bats !== '' ? $bats : null,
                   ':t'   => $throws !== '' ? $throws : null,
                   ':ph'  => $photoFile,
               ]);
        $flash = ['type' => 'ok', 'msg' => $name . ' added to the roster.'];
    } catch (Throwable $ex) {
        $flash = ['type' => 'err', 'msg' => $ex->getMessage()];
    }
}

/* ── Remove a player ─────────────────────────────────────────────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete-player' && isAdmin()) {
    $id = (int)($_POST['id'] ?? 0);
    $stmt = getDB()->prepare("SELECT * FROM players WHERE id = :id");
    $stmt->execute([':id' => $id]);
    if ($row = $stmt->fetch()) {
        if (!empty($row['photo_file']) && is_file(UPLOAD_DIR . '/' . $row['photo_file'])) {
            @unlink(UPLOAD_DIR . '/' . $row['photo_file']);
        }
        getDB()->prepare("DELETE FROM players WHERE id = :id")->execute([':id' => $id]);
        $flash = ['type' => 'ok', 'msg' => 'Player removed.'];
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
$players  = getPlayers();
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
        <a class="btn btn-ghost btn-sm" href="./" target="_blank">View Site &#8599;</a>
        <a class="btn btn-ghost btn-sm" href="?logout=1">Log Out</a>
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

    <!-- ── Roster manager ─────────────────────────────────────────────── -->
    <div class="panel" style="margin-top:26px">
      <h3>Roster</h3>
      <p style="color:var(--steel);margin:-8px 0 18px;font-size:.92rem">Add players here — they show up on the Roster page. A headshot is optional.</p>

      <form method="post" enctype="multipart/form-data" class="roster-form">
        <input type="hidden" name="action" value="create-player">
        <div class="rf-grid">
          <div class="field">
            <label for="pl-name">Name</label>
            <input type="text" id="pl-name" name="name" required placeholder="Player name">
          </div>
          <div class="field">
            <label for="pl-number">Number</label>
            <input type="text" id="pl-number" name="number" inputmode="numeric" placeholder="e.g. 7">
          </div>
          <div class="field">
            <label for="pl-position">Position</label>
            <select id="pl-position" name="position">
              <option value="">— select —</option>
              <?php foreach (positionOptions() as $pos): ?>
                <option value="<?= e($pos) ?>"><?= e($pos) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="pl-bats">Bats</label>
            <select id="pl-bats" name="bats">
              <option value="">—</option>
              <?php foreach (battingOptions() as $v => $lbl): ?>
                <option value="<?= e($v) ?>"><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="pl-throws">Throws</label>
            <select id="pl-throws" name="throws">
              <option value="">—</option>
              <?php foreach (throwingOptions() as $v => $lbl): ?>
                <option value="<?= e($v) ?>"><?= e($lbl) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field">
            <label for="pl-photo">Headshot <span style="color:var(--muted);text-transform:none;letter-spacing:0">(optional)</span></label>
            <input type="file" id="pl-photo" name="photo" accept="image/*">
          </div>
        </div>
        <button class="btn btn-primary" type="submit">Add Player</button>
      </form>

      <h4 style="margin:26px 0 14px;font-family:'Oswald',sans-serif;text-transform:uppercase;letter-spacing:1px;color:#fff">
        Current Roster <span style="color:var(--muted);font-weight:400">(<?= count($players) ?>)</span>
      </h4>
      <?php if (!$players): ?>
        <div class="empty">No players yet. Add your first one above.</div>
      <?php else: ?>
        <div class="roster-admin">
          <?php foreach ($players as $pl): ?>
            <div class="post-row">
              <div class="pv" <?= !empty($pl['photo_file']) ? 'style="background-image:url(\'' . UPLOAD_URL . '/' . e($pl['photo_file']) . '\')"' : '' ?>>
                <?= empty($pl['photo_file']) ? '#' . e($pl['number'] ?: '—') : '' ?>
              </div>
              <div class="meta">
                <span class="tag"><?= $pl['number'] !== null && $pl['number'] !== '' ? '#' . e($pl['number']) : 'No #' ?><?= $pl['position'] ? ' · ' . e($pl['position']) : '' ?></span>
                <h4><?= e($pl['name']) ?></h4>
                <small>
                  <?php
                    $bt = [];
                    if ($pl['bats'])   $bt[] = 'Bats ' . e(handLabel($pl['bats']));
                    if ($pl['throws']) $bt[] = 'Throws ' . e(handLabel($pl['throws']));
                    echo $bt ? implode(' · ', $bt) : '&nbsp;';
                  ?>
                </small>
              </div>
              <form method="post" onsubmit="return confirm('Remove this player?')">
                <input type="hidden" name="action" value="delete-player">
                <input type="hidden" name="id" value="<?= (int)$pl['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
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
