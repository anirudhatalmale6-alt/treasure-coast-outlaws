<?php
require_once __DIR__ . '/includes/functions.php';

$players = getPlayers();

$pageTitle = 'Roster';
include __DIR__ . '/includes/header.php';
?>

<a id="top"></a>
<section class="block" style="padding-top:56px">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">The Lineup</span>
        <h2>Team Roster</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$players): ?>
      <div class="empty">The roster hasn't been posted yet. Check back soon.</div>
    <?php else: ?>
      <div class="roster-grid">
        <?php foreach ($players as $pl): ?>
          <article class="player-card">
            <div class="pc-photo<?= empty($pl['photo_file']) ? ' noimg' : '' ?>"
                 <?= !empty($pl['photo_file']) ? 'style="background-image:url(\'' . UPLOAD_URL . '/' . e($pl['photo_file']) . '\')"' : '' ?>>
              <?php if (($pl['number'] ?? '') !== ''): ?>
                <span class="pc-number">#<?= e($pl['number']) ?></span>
              <?php endif; ?>
              <?php if (empty($pl['photo_file'])): ?>
                <span class="pc-bignum"><?= ($pl['number'] ?? '') !== '' ? e($pl['number']) : e(strtoupper(substr($pl['name'],0,2))) ?></span>
              <?php endif; ?>
            </div>
            <div class="pc-body">
              <h3 class="pc-name"><?= e($pl['name']) ?></h3>
              <?php if (!empty($pl['position'])): ?>
                <div class="pc-pos"><?= e($pl['position']) ?></div>
              <?php endif; ?>
              <?php if ($pl['bats'] || $pl['throws']): ?>
                <div class="pc-bt">
                  <?php if ($pl['bats']): ?>
                    <span><b>B</b><?= e(strtoupper($pl['bats'])) ?></span>
                  <?php endif; ?>
                  <?php if ($pl['throws']): ?>
                    <span><b>T</b><?= e(strtoupper($pl['throws'])) ?></span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
