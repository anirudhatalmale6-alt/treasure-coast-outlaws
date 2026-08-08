<?php
require_once __DIR__ . '/includes/functions.php';

$gameId = (int)($_GET['id'] ?? 0);
$game   = $gameId ? getGame($gameId) : null;

if (!$game) {
    $pageTitle = 'Game';
    include __DIR__ . '/includes/header.php';
    echo '<section class="block" style="padding-top:60px"><div class="wrap">'
       . '<div class="empty">That game could not be found. '
       . '<a href="schedule" style="color:var(--silver)">See the full schedule &#8594;</a></div></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$lineup = getGameLineup($gameId);
$res    = gameResult($game);

// "Outlaw of the Game" (if the admin picked one)
$mvp      = !empty($game['mvp_player_id']) ? getPlayer((int)$game['mvp_player_id']) : null;
$mvpStats = $mvp ? getPlayerGameStats($gameId, (int)$mvp['id']) : null;

// Team totals for the line-up table footer.
$tot = array_fill_keys(array_keys(battingStatCols()), 0);
foreach ($lineup as $r) foreach ($tot as $k => $v) $tot[$k] += (int)$r[$k];

$pageTitle = gameVs($game) . ' ' . $game['opponent'];
include __DIR__ . '/includes/header.php';
?>

<a id="top"></a>
<section class="block" style="padding-top:52px">
  <div class="wrap">
    <p style="margin:0 0 18px"><a href="schedule" style="color:var(--steel)">&#8592; Schedule</a></p>

    <!-- Scoreboard -->
    <div class="game-hero">
      <div class="gh-teams">
        <span class="gh-vs"><?= e(gameVs($game)) ?></span>
        <h1 class="gh-opp"><?= e($game['opponent']) ?></h1>
      </div>
      <div class="gh-meta">
        <?= $game['game_date'] ? e(date('l, F j, Y', strtotime($game['game_date']))) : 'Date TBD' ?>
        <?php if ($game['game_time']): ?> · <?= e($game['game_time']) ?><?php endif; ?>
        <?php if ($game['location']): ?> · <?= e($game['location']) ?><?php endif; ?>
        · <?= ($game['home_away'] ?? 'home') === 'away' ? 'Away' : 'Home' ?>
      </div>
      <?php if ($res !== ''): ?>
        <div class="gh-score">
          <span class="game-res <?= strtolower($res) ?>"><?= $res ?></span>
          <span class="gh-nums"><?= (int)$game['our_score'] ?> &ndash; <?= (int)$game['opp_score'] ?></span>
        </div>
      <?php else: ?>
        <div class="gh-upcoming">Upcoming</div>
      <?php endif; ?>
      <?php if (!empty($game['notes'])): ?>
        <p class="gh-notes"><?= e($game['notes']) ?></p>
      <?php endif; ?>
    </div>

    <!-- Outlaw of the Game -->
    <?php if ($mvp): ?>
      <div class="mvp-card">
        <div class="mvp-badge">&#9733; Outlaw of the Game</div>
        <div class="mvp-body">
          <div class="mvp-photo<?= empty($mvp['photo_file']) ? ' noimg' : '' ?>"
               <?= !empty($mvp['photo_file']) ? 'style="background-image:url(\'' . UPLOAD_URL . '/' . e($mvp['photo_file']) . '\')"' : '' ?>>
            <?php if (empty($mvp['photo_file'])): ?>
              <span><?= ($mvp['number'] ?? '') !== '' ? e($mvp['number']) : e(strtoupper(substr($mvp['name'],0,2))) ?></span>
            <?php endif; ?>
          </div>
          <div class="mvp-info">
            <h3><?= ($mvp['number'] ?? '') !== '' ? '<span class="pnum">#'.e($mvp['number']).'</span> ' : '' ?><?= e($mvp['name']) ?></h3>
            <?php if (!empty($mvp['position'])): ?><div class="mvp-pos"><?= e($mvp['position']) ?></div><?php endif; ?>
            <?php if (!empty($game['mvp_note'])): ?><p class="mvp-note"><?= e($game['mvp_note']) ?></p><?php endif; ?>
            <?php if ($mvpStats): ?>
              <div class="mvp-line">
                <?= (int)$mvpStats['hits'] ?>-for-<?= (int)$mvpStats['ab'] ?>
                <?php if ((int)$mvpStats['hr']  > 0): ?> · <?= (int)$mvpStats['hr'] ?> HR<?php endif; ?>
                <?php if ((int)$mvpStats['rbi'] > 0): ?> · <?= (int)$mvpStats['rbi'] ?> RBI<?php endif; ?>
                <?php if ((int)$mvpStats['runs']> 0): ?> · <?= (int)$mvpStats['runs'] ?> R<?php endif; ?>
                <?php if ((int)$mvpStats['sb']  > 0): ?> · <?= (int)$mvpStats['sb'] ?> SB<?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>
    <?php endif; ?>

    <!-- Box score -->
    <div class="sec-head" style="margin-top:38px">
      <div>
        <span class="section-label">Box Score</span>
        <h2 style="font-size:1.7rem">Batting</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$lineup): ?>
      <div class="empty">Stats for this game haven't been posted yet.</div>
    <?php else: ?>
      <div class="stats-table-wrap">
        <table class="stats-table season">
          <thead>
            <tr>
              <th class="pl">Player</th>
              <th>Pos</th>
              <?php foreach (battingStatCols() as $lbl): ?><th><?= e($lbl) ?></th><?php endforeach; ?>
              <th>AVG</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($lineup as $r): ?>
              <tr<?= ($mvp && (int)$r['player_id'] === (int)$mvp['id']) ? ' class="is-mvp"' : '' ?>>
                <td class="pl">
                  <?= ($r['number'] ?? '') !== '' && $r['number'] !== null ? '<span class="pnum">#'.e($r['number']).'</span> ' : '' ?><?= e($r['name']) ?>
                  <?= ($mvp && (int)$r['player_id'] === (int)$mvp['id']) ? ' <span class="mvp-star" title="Outlaw of the Game">&#9733;</span>' : '' ?>
                </td>
                <td><?= e($r['position'] ?: '—') ?></td>
                <?php foreach (array_keys(battingStatCols()) as $c): ?><td><?= (int)$r[$c] ?></td><?php endforeach; ?>
                <td class="avg"><?= e(battingAvg($r['hits'], $r['ab'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td class="pl"><strong>Totals</strong></td>
              <td>—</td>
              <?php foreach (array_keys(battingStatCols()) as $c): ?><td><strong><?= (int)$tot[$c] ?></strong></td><?php endforeach; ?>
              <td class="avg"><?= e(battingAvg($tot['hits'], $tot['ab'])) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>

    <p style="margin-top:30px">
      <a class="btn btn-ghost" href="schedule">&#8592; Schedule</a>
      <a class="btn btn-ghost" href="stats" style="margin-left:8px">Season Stats &#8594;</a>
    </p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
