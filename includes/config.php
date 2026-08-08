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

// ── Paths (usually no need to change) ───────────────────────────────────────
define('BASE_DIR', dirname(__DIR__));               // project root
define('DATA_DIR', BASE_DIR . '/data');             // sqlite db lives here
define('UPLOAD_DIR', BASE_DIR . '/uploads');        // uploaded images/videos
define('UPLOAD_URL', 'uploads');                    // web path to uploads

// Max upload size hint (bytes) — 64 MB. Your host's php.ini may cap lower.
define('MAX_UPLOAD_BYTES', 64 * 1024 * 1024);

// Start a session for the admin login.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
