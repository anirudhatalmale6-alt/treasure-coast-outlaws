<?php
require_once __DIR__ . '/includes/functions.php';

$all      = getGames('asc');
$upcoming = array_values(array_filter($all, fn($g) => $g['status'] !== 'final'));
$results  = array_reverse(array_values(array_filter($all, fn($g) => $g['status'] === 'final')));

$pageTitle = 'Schedule';
include __DIR__ . '/includes/header.php';

/** Render one game row. */
function gameRow(array $g): void {
    $res = gameResult($g);
    ?>
    <div class="game-row">
      <div class="game-date">
        <?php if ($g['game_date']): ?>
          <span class="gd-day"><?= e(date('D', strtotime($g['game_date']))) ?></span>
          <span class="gd-num"><?= e(date('M j', strtotime($g['game_date']))) ?></span>
        <?php else: ?>
          <span class="gd-num">TBD</span>
        <?php endif; ?>
      </div>
      <div class="game-main">
        <div class="game-opp"><span class="game-vs"><?= e(gameVs($g)) ?></span> <?= e($g['opponent']) ?></div>
        <div class="game-sub">
          <?= ($g['home_away'] ?? 'home') === 'away' ? 'Away' : 'Home' ?>
          <?php if ($g['game_time']): ?> · <?= e($g['game_time']) ?><?php endif; ?>
          <?php if ($g['location']): ?> · <?= e($g['location']) ?><?php endif; ?>
        </div>
      </div>
      <?php if ($res !== ''): ?>
        <div class="game-score">
          <span class="game-res <?= strtolower($res) ?>"><?= $res ?></span>
          <span class="gs-num"><?= (int)$g['our_score'] ?>&ndash;<?= (int)$g['opp_score'] ?></span>
        </div>
      <?php endif; ?>
    </div>
    <?php
}
?>

<a id="top"></a>
<section class="block" style="padding-top:56px">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">Season</span>
        <h2>Schedule</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$all): ?>
      <div class="empty">The schedule hasn't been posted yet. Check back soon.</div>
    <?php else: ?>

      <?php if ($upcoming): ?>
        <h3 class="games-subhead">Upcoming</h3>
        <div class="games-list">
          <?php foreach ($upcoming as $g) gameRow($g); ?>
        </div>
      <?php endif; ?>

      <?php if ($results): ?>
        <h3 class="games-subhead" style="margin-top:38px">Results</h3>
        <div class="games-list">
          <?php foreach ($results as $g) gameRow($g); ?>
        </div>
      <?php endif; ?>

    <?php endif; ?>

    <p style="margin-top:34px"><a class="btn btn-ghost" href="stats">Season Stats &#8594;</a></p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
