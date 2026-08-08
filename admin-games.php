<?php
/**
 * Admin — Games & Stats manager.
 *  • Create/edit games (schedule + final scores)
 *  • Set each game's line-up (players from the roster, batting order + position)
 *  • Enter each player's batting stats for the game
 * Season totals are calculated automatically for the public Stats page.
 */
require_once __DIR__ . '/includes/functions.php';

if (!isAdmin()) { header('Location: admin'); exit; }

/** Set a one-time flash message and redirect (Post/Redirect/Get). */
function goFlash(string $type, string $msg, string $to): void {
    $_SESSION['games_flash'] = ['type' => $type, 'msg' => $msg];
    header('Location: ' . $to);
    exit;
}

$pdo    = getDB();
$STATS  = array_keys(battingStatCols());   // ab, runs, hits, ...

/* ─────────────── actions ─────────────── */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create-game') {
        $opp = trim($_POST['opponent'] ?? '');
        if ($opp === '') goFlash('err', 'Please enter an opponent.', 'admin-games');
        $stmt = $pdo->prepare("INSERT INTO games (opponent, game_date, game_time, home_away, location)
                               VALUES (:o,:d,:t,:h,:l)");
        $stmt->execute([
            ':o' => $opp,
            ':d' => ($_POST['game_date'] ?? '') !== '' ? $_POST['game_date'] : null,
            ':t' => trim($_POST['game_time'] ?? '') ?: null,
            ':h' => ($_POST['home_away'] ?? 'home') === 'away' ? 'away' : 'home',
            ':l' => trim($_POST['location'] ?? '') ?: null,
        ]);
        goFlash('ok', 'Game created. Now set the line-up below.', 'admin-games?game=' . (int)$pdo->lastInsertId());
    }

    if ($action === 'update-game') {
        $id = (int)($_POST['id'] ?? 0);
        $opp = trim($_POST['opponent'] ?? '');
        if (!$id || $opp === '') goFlash('err', 'Opponent is required.', 'admin-games?game=' . $id);
        $stmt = $pdo->prepare("UPDATE games SET opponent=:o, game_date=:d, game_time=:t, home_away=:h,
                               location=:l, our_score=:us, opp_score=:os, status=:s, notes=:n,
                               mvp_player_id=:mvp, mvp_note=:mvpn WHERE id=:id");
        $stmt->execute([
            ':o'  => $opp,
            ':d'  => ($_POST['game_date'] ?? '') !== '' ? $_POST['game_date'] : null,
            ':t'  => trim($_POST['game_time'] ?? '') ?: null,
            ':h'  => ($_POST['home_away'] ?? 'home') === 'away' ? 'away' : 'home',
            ':l'  => trim($_POST['location'] ?? '') ?: null,
            ':us' => ($_POST['our_score'] ?? '') !== '' ? (int)$_POST['our_score'] : null,
            ':os' => ($_POST['opp_score'] ?? '') !== '' ? (int)$_POST['opp_score'] : null,
            ':s'  => ($_POST['status'] ?? 'scheduled') === 'final' ? 'final' : 'scheduled',
            ':n'  => trim($_POST['notes'] ?? '') ?: null,
            ':mvp'  => ($_POST['mvp_player_id'] ?? '') !== '' ? (int)$_POST['mvp_player_id'] : null,
            ':mvpn' => trim($_POST['mvp_note'] ?? '') ?: null,
            ':id' => $id,
        ]);
        goFlash('ok', 'Game saved.', 'admin-games?game=' . $id);
    }

    if ($action === 'delete-game') {
        $id = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM game_stats WHERE game_id=:id")->execute([':id' => $id]);
        $pdo->prepare("DELETE FROM games WHERE id=:id")->execute([':id' => $id]);
        goFlash('ok', 'Game deleted.', 'admin-games');
    }

    if ($action === 'add-lineup') {
        $gid = (int)($_POST['game_id'] ?? 0);
        $pid = (int)($_POST['player_id'] ?? 0);
        if ($gid && $pid) {
            $stmt = $pdo->prepare("INSERT IGNORE INTO game_stats (game_id, player_id, batting_order, position)
                                   VALUES (:g,:p,:bo,:pos)");
            $stmt->execute([
                ':g' => $gid, ':p' => $pid,
                ':bo'  => ($_POST['batting_order'] ?? '') !== '' ? (int)$_POST['batting_order'] : null,
                ':pos' => trim($_POST['position'] ?? '') ?: null,
            ]);
            goFlash('ok', 'Player added to the line-up.', 'admin-games?game=' . $gid);
        }
        goFlash('err', 'Could not add player.', 'admin-games?game=' . $gid);
    }

    if ($action === 'save-stats') {
        $gid = (int)($_POST['game_id'] ?? 0);

        // Remove a single player from the line-up?
        if (!empty($_POST['do_remove'])) {
            $pid = (int)$_POST['do_remove'];
            $pdo->prepare("DELETE FROM game_stats WHERE game_id=:g AND player_id=:p")
                ->execute([':g' => $gid, ':p' => $pid]);
            goFlash('ok', 'Player removed from the line-up.', 'admin-games?game=' . $gid);
        }

        // Otherwise save everyone's stats.
        $rows = $_POST['stats'] ?? [];
        $set  = 'batting_order=:bo, position=:pos, '
              . implode(', ', array_map(fn($c) => "$c=:$c", $STATS));
        $stmt = $pdo->prepare("UPDATE game_stats SET $set WHERE game_id=:g AND player_id=:p");
        foreach ($rows as $pid => $vals) {
            $params = [
                ':g'   => $gid,
                ':p'   => (int)$pid,
                ':bo'  => ($vals['batting_order'] ?? '') !== '' ? (int)$vals['batting_order'] : null,
                ':pos' => trim($vals['position'] ?? '') ?: null,
            ];
            foreach ($STATS as $c) $params[":$c"] = max(0, (int)($vals[$c] ?? 0));
            $stmt->execute($params);
        }
        goFlash('ok', 'Stats saved.', 'admin-games?game=' . $gid);
    }
}

$flash = $_SESSION['games_flash'] ?? null;
unset($_SESSION['games_flash']);

$gameId = (int)($_GET['game'] ?? 0);
$game   = $gameId ? getGame($gameId) : null;

$pageTitle = 'Games & Stats';
include __DIR__ . '/includes/header.php';
?>
<div class="admin-shell">
  <div class="wrap">
    <div class="admin-head">
      <div>
        <span class="section-label">Control Room</span>
        <h2 style="margin:0">Games &amp; Stats</h2>
      </div>
      <div style="display:flex;gap:10px">
        <a class="btn btn-ghost btn-sm" href="admin">&#8592; Posts &amp; Roster</a>
        <a class="btn btn-ghost btn-sm" href="schedule" target="_blank">View Schedule &#8599;</a>
        <a class="btn btn-ghost btn-sm" href="admin?logout=1">Log Out</a>
      </div>
    </div>

    <?php if ($flash): ?><div class="flash <?= $flash['type'] ?>"><?= e($flash['msg']) ?></div><?php endif; ?>

<?php if (!$game): /* ===================== GAMES LIST ===================== */
    $games = getGames('desc');
?>
    <div class="admin-cols">
      <!-- New game -->
      <div class="panel">
        <h3>New Game</h3>
        <form method="post">
          <input type="hidden" name="action" value="create-game">
          <div class="field"><label>Opponent</label><input type="text" name="opponent" required placeholder="e.g. Vero Beach Sharks"></div>
          <div class="cf-row">
            <div class="field"><label>Date</label><input type="date" name="game_date"></div>
            <div class="field"><label>Time</label><input type="text" name="game_time" placeholder="e.g. 7:00 PM"></div>
          </div>
          <div class="cf-row">
            <div class="field"><label>Home / Away</label>
              <select name="home_away"><option value="home">Home</option><option value="away">Away</option></select>
            </div>
            <div class="field"><label>Location</label><input type="text" name="location" placeholder="Field / city"></div>
          </div>
          <button class="btn btn-primary" type="submit" style="width:100%;justify-content:center">Create Game</button>
        </form>
      </div>

      <!-- Games list -->
      <div class="panel">
        <h3>Games <span style="color:var(--muted);font-weight:400">(<?= count($games) ?>)</span></h3>
        <?php if (!$games): ?>
          <div class="empty">No games yet. Create your first one on the left.</div>
        <?php else: ?>
          <?php foreach ($games as $g): $res = gameResult($g); ?>
            <div class="post-row">
              <div class="meta">
                <span class="tag"><?= $g['status']==='final' ? 'Final' : 'Scheduled' ?><?= $g['game_date'] ? ' · ' . e(niceDate($g['game_date'])) : '' ?></span>
                <h4><?= e(gameVs($g)) ?> <?= e($g['opponent']) ?>
                  <?php if ($res !== ''): ?>
                    <span class="game-res <?= strtolower($res) ?>"><?= $res ?> <?= (int)$g['our_score'] ?>-<?= (int)$g['opp_score'] ?></span>
                  <?php endif; ?>
                </h4>
              </div>
              <a class="btn btn-ghost btn-sm" href="admin-games?game=<?= (int)$g['id'] ?>">Manage</a>
              <form method="post" onsubmit="return confirm('Delete this game and its stats?')">
                <input type="hidden" name="action" value="delete-game">
                <input type="hidden" name="id" value="<?= (int)$g['id'] ?>">
                <button class="btn btn-danger btn-sm" type="submit">Delete</button>
              </form>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

<?php else: /* ===================== ONE GAME: lineup + stats ===================== */
    $lineup   = getGameLineup($gameId);
    $available = getPlayersNotInGame($gameId);
?>
    <p style="margin:-12px 0 20px"><a href="admin-games" style="color:var(--steel)">&#8592; All games</a></p>

    <!-- Edit game -->
    <div class="panel" style="margin-bottom:22px">
      <h3><?= e(gameVs($game)) ?> <?= e($game['opponent']) ?></h3>
      <form method="post">
        <input type="hidden" name="action" value="update-game">
        <input type="hidden" name="id" value="<?= (int)$game['id'] ?>">
        <div class="cf-row">
          <div class="field"><label>Opponent</label><input type="text" name="opponent" required value="<?= e($game['opponent']) ?>"></div>
          <div class="field"><label>Home / Away</label>
            <select name="home_away">
              <option value="home" <?= $game['home_away']!=='away'?'selected':'' ?>>Home</option>
              <option value="away" <?= $game['home_away']==='away'?'selected':'' ?>>Away</option>
            </select>
          </div>
        </div>
        <div class="cf-row">
          <div class="field"><label>Date</label><input type="date" name="game_date" value="<?= e($game['game_date']) ?>"></div>
          <div class="field"><label>Time</label><input type="text" name="game_time" value="<?= e($game['game_time']) ?>" placeholder="e.g. 7:00 PM"></div>
        </div>
        <div class="field"><label>Location</label><input type="text" name="location" value="<?= e($game['location']) ?>"></div>
        <div class="cf-row">
          <div class="field"><label>Status</label>
            <select name="status">
              <option value="scheduled" <?= $game['status']!=='final'?'selected':'' ?>>Scheduled</option>
              <option value="final" <?= $game['status']==='final'?'selected':'' ?>>Final</option>
            </select>
          </div>
          <div class="field"><label>Score (Us / Them)</label>
            <div style="display:flex;gap:8px">
              <input type="number" name="our_score" value="<?= $game['our_score']===null?'':(int)$game['our_score'] ?>" placeholder="Us" style="width:50%">
              <input type="number" name="opp_score" value="<?= $game['opp_score']===null?'':(int)$game['opp_score'] ?>" placeholder="Them" style="width:50%">
            </div>
          </div>
        </div>
        <div class="field"><label>Notes (optional)</label><textarea name="notes" style="min-height:70px"><?= e($game['notes']) ?></textarea></div>

        <!-- Outlaw of the Game -->
        <div class="mvp-pick">
          <div class="mvp-pick-head">&#9733; Outlaw of the Game</div>
          <div class="cf-row">
            <div class="field">
              <label>Player</label>
              <select name="mvp_player_id">
                <option value="">— none yet —</option>
                <?php
                  // Prefer this game's line-up; fall back to the whole roster.
                  $mvpChoices = getGameLineup($gameId);
                  if (!$mvpChoices) $mvpChoices = getPlayers();
                  foreach ($mvpChoices as $c):
                      $cid = (int)($c['player_id'] ?? $c['id']);
                ?>
                  <option value="<?= $cid ?>" <?= (int)$game['mvp_player_id'] === $cid ? 'selected' : '' ?>>
                    <?= ($c['number'] ?? '') !== '' && $c['number'] !== null ? '#'.e($c['number']).' ' : '' ?><?= e($c['name']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <div class="hint">Players in this game's line-up.</div>
            </div>
            <div class="field">
              <label>Why (optional)</label>
              <input type="text" name="mvp_note" maxlength="255" value="<?= e($game['mvp_note']) ?>" placeholder="e.g. 3-for-4 with a home run">
            </div>
          </div>
        </div>

        <button class="btn btn-primary" type="submit">Save Game</button>
      </form>
    </div>

    <!-- Add to line-up -->
    <div class="panel" style="margin-bottom:22px">
      <h3>Add to Line-up</h3>
      <?php if (!$available): ?>
        <p style="color:var(--steel);margin:0">Every rostered player is already in this line-up<?= getPlayers() ? '.' : ' — add players on the Roster first.' ?></p>
      <?php else: ?>
        <form method="post" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end">
          <input type="hidden" name="action" value="add-lineup">
          <input type="hidden" name="game_id" value="<?= (int)$gameId ?>">
          <div class="field" style="margin:0;flex:2;min-width:180px">
            <label>Player</label>
            <select name="player_id" required>
              <option value="">— choose player —</option>
              <?php foreach ($available as $pl): ?>
                <option value="<?= (int)$pl['id'] ?>"><?= $pl['number']!==''&&$pl['number']!==null ? '#'.e($pl['number']).' ' : '' ?><?= e($pl['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="field" style="margin:0;width:90px"><label>Order</label><input type="number" name="batting_order" min="1" max="99" placeholder="#"></div>
          <div class="field" style="margin:0;flex:1;min-width:140px"><label>Position</label>
            <select name="position"><option value="">—</option>
              <?php foreach (positionOptions() as $pos): ?><option value="<?= e($pos) ?>"><?= e($pos) ?></option><?php endforeach; ?>
            </select>
          </div>
          <button class="btn btn-primary" type="submit">Add</button>
        </form>
      <?php endif; ?>
    </div>

    <!-- Line-up + stats -->
    <div class="panel">
      <h3>Line-up &amp; Batting Stats</h3>
      <?php if (!$lineup): ?>
        <div class="empty">No players in the line-up yet. Add them above.</div>
      <?php else: ?>
        <form method="post">
          <input type="hidden" name="action" value="save-stats">
          <input type="hidden" name="game_id" value="<?= (int)$gameId ?>">
          <div class="stats-table-wrap">
            <table class="stats-table">
              <thead>
                <tr>
                  <th>#</th><th>Player</th><th>Pos</th>
                  <?php foreach (battingStatCols() as $lbl): ?><th><?= e($lbl) ?></th><?php endforeach; ?>
                  <th></th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($lineup as $row): $pid=(int)$row['player_id']; ?>
                  <tr>
                    <td><input type="number" name="stats[<?= $pid ?>][batting_order]" value="<?= $row['batting_order']===null?'':(int)$row['batting_order'] ?>" min="1" max="99" class="si sm"></td>
                    <td class="pname"><?= $row['number']!==''&&$row['number']!==null ? '<span class="pnum">#'.e($row['number']).'</span> ' : '' ?><?= e($row['name']) ?></td>
                    <td>
                      <select name="stats[<?= $pid ?>][position]" class="si">
                        <option value="">—</option>
                        <?php foreach (positionOptions() as $pos): ?>
                          <option value="<?= e($pos) ?>" <?= $row['position']===$pos?'selected':'' ?>><?= e($pos) ?></option>
                        <?php endforeach; ?>
                      </select>
                    </td>
                    <?php foreach (array_keys(battingStatCols()) as $c): ?>
                      <td><input type="number" name="stats[<?= $pid ?>][<?= $c ?>]" value="<?= (int)$row[$c] ?>" min="0" class="si sm"></td>
                    <?php endforeach; ?>
                    <td><button type="submit" name="do_remove" value="<?= $pid ?>" class="btn btn-danger btn-sm" formnovalidate onclick="return confirm('Remove from line-up?')">&times;</button></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <div style="margin-top:16px"><button class="btn btn-primary" type="submit">Save Stats</button></div>
        </form>
      <?php endif; ?>
    </div>
<?php endif; ?>
  </div>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
