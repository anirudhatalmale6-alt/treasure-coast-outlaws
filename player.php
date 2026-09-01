<?php
/**
 * Public player profile — /player?id=N
 *
 * Reached by clicking a card on the Roster, a name in a box score, or a name on
 * the Season Stats page. Everything on it is optional: a player with no photo,
 * no profile details and no games played still renders a clean page rather than
 * a wall of empty sections.
 */

require_once __DIR__ . '/includes/functions.php';

$playerId = (int)($_GET['id'] ?? 0);
$player   = $playerId ? getPlayer($playerId) : null;

if (!$player) {
    $pageTitle = 'Player';
    include __DIR__ . '/includes/header.php';
    echo '<section class="block" style="padding-top:60px"><div class="wrap">'
       . '<div class="empty">That player could not be found. '
       . '<a href="roster" style="color:var(--silver)">See the full roster &#8594;</a></div></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$details = playerProfileFields($player);
$season  = getPlayerSeasonTotals($playerId);
$log     = getPlayerGameLog($playerId);
$nb      = playerNeighbours($playerId);

// How many times he's been Outlaw of the Game — worth a badge on the profile.
$mvpCount = 0;
foreach ($log as $g) {
    if ((int)($g['mvp_player_id'] ?? 0) === $playerId) $mvpCount++;
}

$hasNum    = ($player['number'] ?? '') !== '' && $player['number'] !== null;
$initials  = strtoupper(substr($player['name'], 0, 2));

$pageTitle = $player['name'];
include __DIR__ . '/includes/header.php';
?>

<a id="top"></a>
<section class="block" style="padding-top:52px">
  <div class="wrap">
    <p style="margin:0 0 18px"><a href="roster" style="color:var(--steel)">&#8592; Roster</a></p>

    <!-- ── Profile header ───────────────────────────────────────────────── -->
    <div class="pp-hero">
      <div class="pp-photo<?= empty($player['photo_file']) ? ' noimg' : '' ?>"
           <?= !empty($player['photo_file']) ? 'style="background-image:url(\'' . UPLOAD_URL . '/' . e($player['photo_file']) . '\')"' : '' ?>>
        <?php if (empty($player['photo_file'])): ?>
          <span class="pp-bignum"><?= $hasNum ? e($player['number']) : e($initials) ?></span>
        <?php endif; ?>
      </div>

      <div class="pp-head">
        <?php if ($hasNum): ?>
          <div class="pp-num">#<?= e($player['number']) ?></div>
        <?php endif; ?>
        <h1 class="pp-name"><?= e($player['name']) ?></h1>
        <?php if (!empty($player['position'])): ?>
          <div class="pp-pos"><?= e($player['position']) ?></div>
        <?php endif; ?>

        <?php if ($mvpCount > 0): ?>
          <div class="pp-mvp">&#9733; Outlaw of the Game &times; <?= (int)$mvpCount ?></div>
        <?php endif; ?>

        <?php if ($details): ?>
          <div class="pp-details">
            <?php foreach ($details as $label => $value): ?>
              <div class="pp-detail">
                <span class="ppd-label"><?= e($label) ?></span>
                <span class="ppd-value"><?= e($value) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
      </div>
    </div>

    <!-- ── Bio ──────────────────────────────────────────────────────────── -->
    <?php if (!empty($player['bio'])): ?>
      <div class="pp-bio">
        <span class="section-label">About</span>
        <p><?= nl2br(e($player['bio'])) ?></p>
      </div>
    <?php endif; ?>

    <!-- ── Season line ──────────────────────────────────────────────────── -->
    <div class="sec-head" style="margin-top:38px">
      <div>
        <span class="section-label">Season</span>
        <h2 style="font-size:1.7rem">Batting</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$season): ?>
      <div class="empty">No stats yet for <?= e($player['name']) ?> — they'll appear here once he's played a game.</div>
    <?php else: ?>
      <?php
        // The headline numbers, in the order a scoreboard would show them.
        $tiles = [
            'AVG' => battingAvg($season['hits'], $season['ab']),
            'G'   => (int)$season['gp'],
            'AB'  => (int)$season['ab'],
            'H'   => (int)$season['hits'],
            'R'   => (int)$season['runs'],
            'HR'  => (int)$season['hr'],
            'RBI' => (int)$season['rbi'],
            'SB'  => (int)$season['sb'],
        ];
      ?>
      <div class="pp-tiles">
        <?php foreach ($tiles as $lbl => $val): ?>
          <div class="pp-tile">
            <div class="ppt-val"><?= e((string)$val) ?></div>
            <div class="ppt-lbl"><?= e($lbl) ?></div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- ── Game log ───────────────────────────────────────────────────── -->
      <h3 class="pp-loghead">Game Log</h3>
      <div class="stats-table-wrap">
        <table class="stats-table season">
          <thead>
            <tr>
              <th class="pl">Game</th>
              <th>Result</th>
              <?php foreach (battingStatCols() as $lbl): ?><th><?= e($lbl) ?></th><?php endforeach; ?>
              <th>AVG</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($log as $r): ?>
              <?php $res = gameResult($r); ?>
              <tr<?= (int)($r['mvp_player_id'] ?? 0) === $playerId ? ' class="is-mvp"' : '' ?>>
                <td class="pl">
                  <a class="pp-gamelink" href="game?id=<?= (int)$r['game_id'] ?>">
                    <?= e(gameVs($r)) ?> <?= e($r['opponent']) ?>
                  </a>
                  <?= (int)($r['mvp_player_id'] ?? 0) === $playerId
                        ? ' <span class="mvp-star" title="Outlaw of the Game">&#9733;</span>' : '' ?>
                  <span class="pp-gamedate"><?= e(niceDate($r['game_date'])) ?></span>
                </td>
                <td>
                  <?php if ($res !== ''): ?>
                    <span class="game-res <?= strtolower($res) ?>"><?= $res ?></span>
                    <span class="pp-gs"><?= (int)$r['our_score'] ?>&ndash;<?= (int)$r['opp_score'] ?></span>
                  <?php else: ?>
                    &mdash;
                  <?php endif; ?>
                </td>
                <?php foreach (array_keys(battingStatCols()) as $c): ?><td><?= (int)$r[$c] ?></td><?php endforeach; ?>
                <td class="avg"><?= e(battingAvg($r['hits'], $r['ab'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
          <tfoot>
            <tr>
              <td class="pl"><strong>Season Totals</strong></td>
              <td>&mdash;</td>
              <?php foreach (array_keys(battingStatCols()) as $c): ?><td><strong><?= (int)$season[$c] ?></strong></td><?php endforeach; ?>
              <td class="avg"><?= e(battingAvg($season['hits'], $season['ab'])) ?></td>
            </tr>
          </tfoot>
        </table>
      </div>
    <?php endif; ?>

    <!-- ── Move through the roster ──────────────────────────────────────── -->
    <div class="pp-nav">
      <?php if ($nb['prev']): ?>
        <a class="btn btn-ghost" href="player?id=<?= (int)$nb['prev']['id'] ?>">&#8592; <?= e($nb['prev']['name']) ?></a>
      <?php else: ?><span></span><?php endif; ?>

      <a class="btn btn-ghost" href="roster">Full Roster</a>

      <?php if ($nb['next']): ?>
        <a class="btn btn-ghost" href="player?id=<?= (int)$nb['next']['id'] ?>"><?= e($nb['next']['name']) ?> &#8594;</a>
      <?php else: ?><span></span><?php endif; ?>
    </div>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
