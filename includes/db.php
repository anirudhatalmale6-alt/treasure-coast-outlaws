<?php
/**
 * Database layer — MySQL (via PDO).
 *
 * Connection details come from includes/config.php. Create the database on your
 * DB host and import the included `database.sql` once; after that this just
 * connects. (It also creates the table automatically if it's missing, so the
 * site is self-healing.)
 */

require_once __DIR__ . '/config.php';

function getDB(): PDO {
    static $pdo = null;
    if ($pdo !== null) return $pdo;

    $dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
                   DB_HOST, DB_PORT, DB_NAME);
    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => true,
        ]);
    } catch (PDOException $ex) {
        http_response_code(500);
        exit('Database connection failed. Please check the DB settings in includes/config.php. (' . htmlspecialchars($ex->getMessage()) . ')');
    }

    // Self-heal: make sure the tables exist (matches database.sql).
    $pdo->exec("CREATE TABLE IF NOT EXISTS posts (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        type        VARCHAR(10)  NOT NULL DEFAULT 'news',   -- news | photo | video
        title       VARCHAR(255) NOT NULL DEFAULT '',
        body        TEXT         NULL,                       -- the text / caption / story
        image_file  VARCHAR(255) NULL,                       -- uploaded image filename
        video_file  VARCHAR(255) NULL,                       -- uploaded video filename (mp4)
        video_url   VARCHAR(500) NULL,                       -- OR a YouTube / Vimeo link
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_type (type),
        INDEX idx_created (created_at)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    $pdo->exec("CREATE TABLE IF NOT EXISTS players (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        name        VARCHAR(120) NOT NULL,
        number      VARCHAR(5)   NULL,                       -- jersey number (text, allows '00')
        position    VARCHAR(40)  NULL,                       -- e.g. Pitcher, Shortstop
        bats        VARCHAR(10)  NULL,                       -- R | L | S (switch)
        throws      VARCHAR(10)  NULL,                       -- R | L
        photo_file  VARCHAR(255) NULL,                       -- optional headshot
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Games — schedule + results.
    $pdo->exec("CREATE TABLE IF NOT EXISTS games (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        opponent    VARCHAR(120) NOT NULL,
        game_date   DATE         NULL,
        game_time   VARCHAR(20)  NULL,
        home_away   VARCHAR(4)   NOT NULL DEFAULT 'home',    -- home | away
        location    VARCHAR(160) NULL,
        our_score   INT          NULL,
        opp_score   INT          NULL,
        status      VARCHAR(10)  NOT NULL DEFAULT 'scheduled', -- scheduled | final
        notes       TEXT         NULL,
        mvp_player_id INT        NULL,                        -- Outlaw of the Game (players.id)
        mvp_note    VARCHAR(255) NULL,                        -- why they earned it
        created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_date (game_date)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Per-game player batting stats (a row also means the player is in that
    // game's line-up). AVG is computed, not stored.
    $pdo->exec("CREATE TABLE IF NOT EXISTS game_stats (
        id            INT AUTO_INCREMENT PRIMARY KEY,
        game_id       INT NOT NULL,
        player_id     INT NOT NULL,
        batting_order INT NULL,
        position      VARCHAR(20) NULL,
        ab      INT NOT NULL DEFAULT 0,
        runs    INT NOT NULL DEFAULT 0,
        hits    INT NOT NULL DEFAULT 0,
        doubles INT NOT NULL DEFAULT 0,
        triples INT NOT NULL DEFAULT 0,
        hr      INT NOT NULL DEFAULT 0,
        rbi     INT NOT NULL DEFAULT 0,
        bb      INT NOT NULL DEFAULT 0,
        so      INT NOT NULL DEFAULT 0,
        sb      INT NOT NULL DEFAULT 0,
        UNIQUE KEY uniq_game_player (game_id, player_id),
        INDEX idx_game (game_id),
        INDEX idx_player (player_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

    // Older installs: add the "Outlaw of the Game" columns if they're missing.
    $have = $pdo->query("SHOW COLUMNS FROM games LIKE 'mvp_player_id'")->fetch();
    if (!$have) {
        $pdo->exec("ALTER TABLE games ADD COLUMN mvp_player_id INT NULL,
                                      ADD COLUMN mvp_note VARCHAR(255) NULL");
    }

    return $pdo;
}
