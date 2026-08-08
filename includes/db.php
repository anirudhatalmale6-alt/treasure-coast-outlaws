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

    return $pdo;
}
