<?php
/**
 * Shared helpers — escaping, auth, uploads, video embedding, formatting.
 */

require_once __DIR__ . '/db.php';

/** Escape output for safe HTML. */
function e(?string $s): string {
    return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');
}

/** Is the current visitor logged in as admin? */
function isAdmin(): bool {
    return !empty($_SESSION['is_admin']);
}

/** Send the visitor to the login screen if they aren't an admin. */
function requireAdmin(): void {
    if (!isAdmin()) {
        header('Location: admin.php');
        exit;
    }
}

/** Pretty date like "Aug 8, 2026". */
function niceDate(?string $sqlDate): string {
    if (!$sqlDate) return '';
    $ts = strtotime($sqlDate);
    return $ts ? date('M j, Y', $ts) : '';
}

/**
 * Handle an uploaded file from $_FILES[$field]. Returns the saved filename
 * (relative, inside /uploads) or null if nothing was uploaded. Throws a
 * RuntimeException on a real error (bad type / too big).
 *
 * $kind is 'image' or 'video' — it decides which extensions are allowed.
 */
function handleUpload(string $field, string $kind): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $f = $_FILES[$field];
    if ($f['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload failed (error code ' . $f['error'] . '). The file may be larger than the server allows.');
    }
    if ($f['size'] > MAX_UPLOAD_BYTES) {
        throw new RuntimeException('That file is too large.');
    }

    $allowed = $kind === 'video'
        ? ['mp4' => 'video/mp4', 'webm' => 'video/webm', 'mov' => 'video/quicktime', 'm4v' => 'video/x-m4v']
        : ['jpg' => 'image/jpeg', 'jpeg' => 'image/jpeg', 'png' => 'image/png', 'gif' => 'image/gif', 'webp' => 'image/webp'];

    $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    if (!isset($allowed[$ext])) {
        throw new RuntimeException('That file type isn\'t allowed. Please use ' . implode(', ', array_keys($allowed)) . '.');
    }

    if (!is_dir(UPLOAD_DIR)) {
        @mkdir(UPLOAD_DIR, 0775, true);
    }

    $name = $kind . '_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $dest = UPLOAD_DIR . '/' . $name;
    if (!move_uploaded_file($f['tmp_name'], $dest)) {
        throw new RuntimeException('Could not save the uploaded file. Check folder permissions on /uploads.');
    }
    return $name;
}

/**
 * Turn a YouTube or Vimeo watch URL into an embeddable player URL.
 * Returns the original URL if it isn't recognised (used as a plain link).
 */
function videoEmbedUrl(string $url): ?string {
    $url = trim($url);
    if ($url === '') return null;

    // YouTube: youtu.be/ID, youtube.com/watch?v=ID, /shorts/ID, /embed/ID
    if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|shorts/|embed/))([A-Za-z0-9_-]{6,})~', $url, $m)) {
        return 'https://www.youtube.com/embed/' . $m[1];
    }
    // Vimeo: vimeo.com/12345678
    if (preg_match('~vimeo\.com/(?:video/)?(\d+)~', $url, $m)) {
        return 'https://player.vimeo.com/video/' . $m[1];
    }
    return $url; // unknown provider — return as-is
}

/** Fetch posts of a given type (or all), newest first. */
function getPosts(?string $type = null, int $limit = 100): array {
    $pdo = getDB();
    if ($type) {
        $stmt = $pdo->prepare("SELECT * FROM posts WHERE type = :t ORDER BY created_at DESC, id DESC LIMIT :lim");
        $stmt->bindValue(':t', $type);
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
    } else {
        $stmt = $pdo->prepare("SELECT * FROM posts ORDER BY created_at DESC, id DESC LIMIT :lim");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

/** Fetch highlights — photos AND videos together, newest first. */
function getHighlights(int $limit = 60): array {
    $stmt = getDB()->prepare(
        "SELECT * FROM posts WHERE type IN ('photo','video')
         ORDER BY created_at DESC, id DESC LIMIT :lim");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** Fetch the roster, ordered by jersey number (numeric) then name. */
function getPlayers(int $limit = 500): array {
    $stmt = getDB()->prepare(
        "SELECT * FROM players
         ORDER BY (number IS NULL OR number = ''),   -- numbered players first
                  CAST(NULLIF(number,'') AS UNSIGNED),
                  name
         LIMIT :lim");
    $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

/** The batting/throwing hand options (value => label shown in the admin). */
function battingOptions(): array { return ['R' => 'Right', 'L' => 'Left', 'S' => 'Switch']; }
function throwingOptions(): array { return ['R' => 'Right', 'L' => 'Left']; }

/** Full label for a stored bats/throws code (e.g. 'S' => 'Switch'). */
function handLabel(?string $code): string {
    $map = ['R' => 'Right', 'L' => 'Left', 'S' => 'Switch'];
    $code = strtoupper(trim((string)$code));
    return $map[$code] ?? '';
}

/** The list of positions offered in the admin dropdown. */
function positionOptions(): array {
    return ['Pitcher','Catcher','First Base','Second Base','Third Base','Shortstop',
            'Left Field','Center Field','Right Field','Designated Hitter',
            'Infielder','Outfielder','Utility'];
}

/* ── Games & stats ───────────────────────────────────────────────────────── */

/** The batting-stat columns we track (db column => short label). */
function battingStatCols(): array {
    return ['ab'=>'AB','runs'=>'R','hits'=>'H','doubles'=>'2B','triples'=>'3B',
            'hr'=>'HR','rbi'=>'RBI','bb'=>'BB','so'=>'SO','sb'=>'SB'];
}

/** All games. $order 'asc' (schedule, soonest first) or 'desc' (recent first). */
function getGames(string $order = 'asc'): array {
    $dir = strtolower($order) === 'desc' ? 'DESC' : 'ASC';
    // NULL dates sort last either way.
    return getDB()->query(
        "SELECT * FROM games
         ORDER BY (game_date IS NULL), game_date $dir, id $dir")->fetchAll();
}

function getGame(int $id): ?array {
    $stmt = getDB()->prepare("SELECT * FROM games WHERE id = :id");
    $stmt->execute([':id' => $id]);
    $g = $stmt->fetch();
    return $g ?: null;
}

/** Line-up + stats for a game, joined with player name/number, in batting order. */
function getGameLineup(int $gameId): array {
    $stmt = getDB()->prepare(
        "SELECT gs.*, p.name, p.number
         FROM game_stats gs JOIN players p ON p.id = gs.player_id
         WHERE gs.game_id = :g
         ORDER BY (gs.batting_order IS NULL), gs.batting_order, p.name");
    $stmt->execute([':g' => $gameId]);
    return $stmt->fetchAll();
}

/** Roster players NOT yet in a given game's line-up (for the add dropdown). */
function getPlayersNotInGame(int $gameId): array {
    $stmt = getDB()->prepare(
        "SELECT * FROM players
         WHERE id NOT IN (SELECT player_id FROM game_stats WHERE game_id = :g)
         ORDER BY (number IS NULL OR number=''), CAST(NULLIF(number,'') AS UNSIGNED), name");
    $stmt->execute([':g' => $gameId]);
    return $stmt->fetchAll();
}

/** Season batting totals per player (only players who have appeared). */
function getSeasonBatting(): array {
    return getDB()->query(
        "SELECT p.id, p.name, p.number,
                COUNT(DISTINCT gs.game_id) AS gp,
                SUM(gs.ab) ab, SUM(gs.runs) runs, SUM(gs.hits) hits,
                SUM(gs.doubles) doubles, SUM(gs.triples) triples, SUM(gs.hr) hr,
                SUM(gs.rbi) rbi, SUM(gs.bb) bb, SUM(gs.so) so, SUM(gs.sb) sb
         FROM game_stats gs JOIN players p ON p.id = gs.player_id
         GROUP BY p.id, p.name, p.number
         ORDER BY (SUM(gs.ab)=0), (SUM(gs.hits)/NULLIF(SUM(gs.ab),0)) DESC, SUM(gs.hits) DESC")
        ->fetchAll();
}

/** Batting average formatted like .333 (or .000). */
function battingAvg($hits, $ab): string {
    $ab = (int)$ab;
    if ($ab <= 0) return '.000';
    $avg = (int)$hits / $ab;
    $s = number_format($avg, 3);          // e.g. 0.333
    return ltrim($s, '0');                // -> .333  (1.000 stays 1.000)
}

/** "vs" for home games, "@" for away. */
function gameVs(array $g): string {
    return ($g['home_away'] ?? 'home') === 'away' ? '@' : 'vs';
}

/** Result letter W/L/T for a final game, or '' if not final/no scores. */
function gameResult(array $g): string {
    if (($g['status'] ?? '') !== 'final' || $g['our_score'] === null || $g['opp_score'] === null) return '';
    $o = (int)$g['our_score']; $p = (int)$g['opp_score'];
    return $o > $p ? 'W' : ($o < $p ? 'L' : 'T');
}
