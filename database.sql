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
