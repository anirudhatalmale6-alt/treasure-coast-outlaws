<?php
/**
 * Treasure Coast Outlaws — site configuration.
 *
 * Edit the values below to suit your hosting. The only thing you really need
 * to change is ADMIN_PASSWORD (pick something only you know).
 */

// ── Admin login ─────────────────────────────────────────────────────────────
// The password you type on the admin login page (admin.php).
// CHANGE THIS to your own secret before going live.
define('ADMIN_PASSWORD', 'Outlaws2026!');

// ── Site basics ─────────────────────────────────────────────────────────────
define('SITE_NAME', 'Treasure Coast Outlaws');
define('SITE_TAGLINE', 'Outlaws Baseball');

// ── Instagram ───────────────────────────────────────────────────────────────
define('IG_HANDLE', 'treasurecoastoutlaws');
define('IG_URL', 'https://www.instagram.com/treasurecoastoutlaws');
// Long-lived Instagram API access token (bootstrap). The site refreshes and
// caches it automatically after this. Kept server-side only, never in the
// public code repo.
define('IG_ACCESS_TOKEN', '');

// ── Database ────────────────────────────────────────────────────────────────
// The site uses MySQL so the database can live on a separate hosting provider.
// 1) Create the database on your DB host and import the included `database.sql`
//    (it creates the one table the site needs).
// 2) Fill in the connection details below with what your DB host gives you.
define('DB_HOST', 'localhost');                 // e.g. mysql.yourprovider.com
define('DB_PORT', '3306');                      // usually 3306
define('DB_NAME', 'treasure_coast_outlaws');    // your database name
define('DB_USER', 'CHANGE_ME');                 // your database username
define('DB_PASS', 'CHANGE_ME');                 // your database password

// ── Paths (usually no need to change) ───────────────────────────────────────
define('BASE_DIR', dirname(__DIR__));               // project root
define('UPLOAD_DIR', BASE_DIR . '/uploads');        // uploaded images/videos
define('UPLOAD_URL', 'uploads');                    // web path to uploads

// Max upload size hint (bytes) — 64 MB. Your host's php.ini may cap lower.
define('MAX_UPLOAD_BYTES', 64 * 1024 * 1024);

// Start a session for the admin login.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
