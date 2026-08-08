<?php
/**
 * Database layer — a single SQLite file (data/tco.sqlite).
 *
 * SQLite needs no server or setup: the file is created automatically the first
 * time the site runs. All posts (news, photos, videos) live in one table.
 */

require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    if (!is_dir(DATA_DIR)) {
        @mkdir(DATA_DIR, 0775, true);
    }

    $pdo = new PDO('sqlite:' . DATA_DIR . '/tco.sqlite');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

    // One table holds every kind of post. `type` is news | photo | video.
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id           INTEGER PRIMARY KEY AUTOINCREMENT,
        type         TEXT NOT NULL DEFAULT 'news',   -- news | photo | video
        title        TEXT NOT NULL DEFAULT '',
        body         TEXT NOT NULL DEFAULT '',        -- the text/caption
        image_file   TEXT NULL,                       -- uploaded image filename
        video_file   TEXT NULL,                       -- uploaded video filename (mp4)
        video_url    TEXT NULL,                       -- OR a YouTube/Vimeo link
        created_at   TEXT NOT NULL DEFAULT (datetime('now'))
    )");

    return $pdo;
}
