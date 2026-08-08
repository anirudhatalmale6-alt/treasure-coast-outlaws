-- ============================================================================
--  Treasure Coast Outlaws — database setup
--  Run this ONCE on your MySQL database host (e.g. through phpMyAdmin's
--  "Import" tab, or the MySQL command line) to create the table the site uses.
-- ============================================================================

-- If your host already gave you an empty database, skip the two lines below and
-- just run the CREATE TABLE. Otherwise, uncomment them to create the database:
--
-- CREATE DATABASE IF NOT EXISTS treasure_coast_outlaws
--   CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
-- USE treasure_coast_outlaws;

-- One table holds every post. `type` is one of: news | photo | video.
CREATE TABLE IF NOT EXISTS posts (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    type        VARCHAR(10)  NOT NULL DEFAULT 'news',   -- news | photo | video
    title       VARCHAR(255) NOT NULL DEFAULT '',       -- headline / caption / title
    body        TEXT         NULL,                       -- the text / story / description
    image_file  VARCHAR(255) NULL,                       -- uploaded image filename
    video_file  VARCHAR(255) NULL,                       -- uploaded video filename (mp4/webm/mov)
    video_url   VARCHAR(500) NULL,                       -- OR a YouTube / Vimeo link
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_type (type),
    INDEX idx_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- The team roster (shown on the Roster page).
CREATE TABLE IF NOT EXISTS players (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(120) NOT NULL,
    number      VARCHAR(5)   NULL,                       -- jersey number (text, allows '00')
    position    VARCHAR(40)  NULL,                       -- e.g. Pitcher, Shortstop
    bats        VARCHAR(10)  NULL,                       -- R | L | S (switch)
    throws      VARCHAR(10)  NULL,                       -- R | L
    photo_file  VARCHAR(255) NULL,                       -- optional headshot filename
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Games — schedule + results (shown on the Schedule page).
CREATE TABLE IF NOT EXISTS games (
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
    created_at  DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_date (game_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Per-game player batting stats. A row also means the player was in the
-- line-up for that game. Batting average is computed from these, not stored.
CREATE TABLE IF NOT EXISTS game_stats (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    game_id       INT NOT NULL,
    player_id     INT NOT NULL,
    batting_order INT NULL,
    position      VARCHAR(20) NULL,
    ab      INT NOT NULL DEFAULT 0,   -- at bats
    runs    INT NOT NULL DEFAULT 0,
    hits    INT NOT NULL DEFAULT 0,
    doubles INT NOT NULL DEFAULT 0,
    triples INT NOT NULL DEFAULT 0,
    hr      INT NOT NULL DEFAULT 0,   -- home runs
    rbi     INT NOT NULL DEFAULT 0,
    bb      INT NOT NULL DEFAULT 0,   -- walks
    so      INT NOT NULL DEFAULT 0,   -- strikeouts
    sb      INT NOT NULL DEFAULT 0,   -- stolen bases
    UNIQUE KEY uniq_game_player (game_id, player_id),
    INDEX idx_game (game_id),
    INDEX idx_player (player_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
