<?php
/**
 * Instagram feed via the official Instagram API (graph.instagram.com).
 *
 * - Pulls the latest posts for the connected account and caches them to a file
 *   for IG_CACHE_TTL seconds (fast pages, and resilient if the API hiccups).
 * - Keeps the long-lived access token alive by refreshing it automatically
 *   once it's more than IG_TOKEN_REFRESH_AFTER old (long-lived tokens last ~60
 *   days; refreshing resets the clock, so it never expires while the site is
 *   used).
 *
 * The token + cache live in includes/cache/ which is kept out of the public
 * code and blocked from the web. The bootstrap token comes from IG_ACCESS_TOKEN
 * in config.php.
 */

require_once __DIR__ . '/config.php';

if (!defined('IG_CACHE_DIR'))          define('IG_CACHE_DIR', __DIR__ . '/cache');
if (!defined('IG_CACHE_TTL'))          define('IG_CACHE_TTL', 1800);             // 30 min
if (!defined('IG_TOKEN_REFRESH_AFTER'))define('IG_TOKEN_REFRESH_AFTER', 864000); // 10 days
if (!defined('IG_FEED_LIMIT'))         define('IG_FEED_LIMIT', 9);

function igStatePath(): string { return IG_CACHE_DIR . '/ig_state.php'; }
function igFeedPath():  string { return IG_CACHE_DIR . '/ig_feed.json'; }

function igEnsureDir(): void {
    if (!is_dir(IG_CACHE_DIR)) @mkdir(IG_CACHE_DIR, 0775, true);
}

/** Current token + when it was obtained. Falls back to the config token. */
function igLoadState(): array {
    $p = igStatePath();
    if (is_file($p)) {
        $s = include $p;
        if (is_array($s) && !empty($s['token'])) return $s;
    }
    $tok = defined('IG_ACCESS_TOKEN') ? IG_ACCESS_TOKEN : '';
    return ['token' => $tok, 'obtained_at' => 0];
}

function igSaveState(string $token, int $obtainedAt): void {
    igEnsureDir();
    $php = "<?php return " . var_export(['token' => $token, 'obtained_at' => $obtainedAt], true) . ";\n";
    @file_put_contents(igStatePath(), $php, LOCK_EX);
}

/** Simple GET returning [httpCode, body]. */
function igHttpGet(string $url): array {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 12,
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $body = curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return [$code, $body];
}

/** Refresh the long-lived token if it's getting old. Mutates + persists state. */
function igRefreshTokenIfNeeded(array &$state): void {
    if (empty($state['token'])) return;
    $age = time() - (int)$state['obtained_at'];
    if ((int)$state['obtained_at'] > 0 && $age < IG_TOKEN_REFRESH_AFTER) return;

    $url = 'https://graph.instagram.com/refresh_access_token?grant_type=ig_refresh_token&access_token=' . urlencode($state['token']);
    [$code, $body] = igHttpGet($url);
    if ($code === 200 && $body) {
        $j = json_decode($body, true);
        if (!empty($j['access_token'])) {
            $state['token']       = $j['access_token'];
            $state['obtained_at'] = time();
            igSaveState($state['token'], $state['obtained_at']);
            return;
        }
    }
    // Couldn't refresh (e.g. token <24h old on first run) — stamp the time so we
    // don't retry on every page load.
    if ((int)$state['obtained_at'] === 0) {
        $state['obtained_at'] = time();
        igSaveState($state['token'], $state['obtained_at']);
    }
}

/**
 * Return the latest Instagram posts (array of items with id, caption,
 * media_type, media_url, permalink, thumbnail_url, timestamp). Cached.
 */
function igGetPosts(): array {
    $fp = igFeedPath();

    // Fresh cache? Serve it.
    if (is_file($fp) && (time() - filemtime($fp) < IG_CACHE_TTL)) {
        $c = json_decode((string)file_get_contents($fp), true);
        if (is_array($c)) return $c;
    }

    $state = igLoadState();
    if (empty($state['token'])) return [];

    igRefreshTokenIfNeeded($state);

    $fields = 'id,caption,media_type,media_url,permalink,thumbnail_url,timestamp';
    $url = 'https://graph.instagram.com/me/media?fields=' . $fields
         . '&limit=' . IG_FEED_LIMIT . '&access_token=' . urlencode($state['token']);
    [$code, $body] = igHttpGet($url);

    if ($code === 200 && $body) {
        $j = json_decode($body, true);
        if (isset($j['data']) && is_array($j['data'])) {
            igEnsureDir();
            @file_put_contents($fp, json_encode($j['data']), LOCK_EX);
            return $j['data'];
        }
    }

    // API failed — serve stale cache if we have any, else nothing.
    if (is_file($fp)) {
        $c = json_decode((string)file_get_contents($fp), true);
        if (is_array($c)) return $c;
    }
    return [];
}

/** The image to show for a post (videos/reels expose a thumbnail). */
function igPostImage(array $post): string {
    if (($post['media_type'] ?? '') === 'VIDEO' && !empty($post['thumbnail_url'])) {
        return $post['thumbnail_url'];
    }
    return $post['media_url'] ?? ($post['thumbnail_url'] ?? '');
}

/** A short, single-paragraph excerpt of a caption. */
function igExcerpt(?string $caption, int $len = 140): string {
    $caption = trim((string)$caption);
    if ($caption === '') return '';
    $caption = preg_replace('/\s+/', ' ', $caption);
    if (mb_strlen($caption) <= $len) return $caption;
    return mb_substr($caption, 0, $len - 1) . '…';
}
