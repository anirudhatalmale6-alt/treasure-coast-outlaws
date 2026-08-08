<?php
require_once __DIR__ . '/includes/functions.php';

$stats = getSeasonBatting();

$pageTitle = 'Stats';
include __DIR__ . '/includes/header.php';
?>

<a id="top"></a>
<section class="block" style="padding-top:56px">
  <div class="wrap">
    <div class="sec-head">
      <div>
        <span class="section-label">By the Numbers</span>
        <h2>Season Stats</h2>
        <div class="divider"></div>
      </div>
    </div>

    <?php if (!$stats): ?>
      <div class="empty">No stats yet — they'll show up here once games have been played.</div>
    <?php else: ?>
      <p style="color:var(--steel);margin:-8px 0 20px;font-size:.92rem">Team batting — sorted by average.</p>
      <div class="stats-table-wrap">
        <table class="stats-table season">
          <thead>
            <tr>
              <th class="pl">Player</th>
              <th>GP</th>
              <?php foreach (battingStatCols() as $lbl): ?><th><?= e($lbl) ?></th><?php endforeach; ?>
              <th>AVG</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($stats as $s): ?>
              <tr>
                <td class="pl"><?= $s['number']!==''&&$s['number']!==null ? '<span class="pnum">#'.e($s['number']).'</span> ' : '' ?><?= e($s['name']) ?></td>
                <td><?= (int)$s['gp'] ?></td>
                <?php foreach (array_keys(battingStatCols()) as $c): ?><td><?= (int)$s[$c] ?></td><?php endforeach; ?>
                <td class="avg"><?= e(battingAvg($s['hits'], $s['ab'])) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>

    <p style="margin-top:30px"><a class="btn btn-ghost" href="schedule">&#8592; Schedule</a></p>
  </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
