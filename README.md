# Treasure Coast Outlaws — Team Website

A simple, self-contained website for the Treasure Coast Outlaws baseball team:

- **Home page** — a dark, athletic landing page with the team emblem, plus
  auto-updating **News**, **Photo Gallery** and **Videos** sections.
- **Admin page** — a password-protected control room where you post News,
  Photos and Videos. Anything you post shows up on the home page instantly.

No external database or paid services required. Everything runs on plain PHP
with a tiny built-in SQLite file, so it works on almost any web host
(DreamHost, cPanel, etc.).

---

## What's in here

```
index.php            The public home page
admin.php            Login + post manager (News / Photos / Videos)
includes/
  config.php         ← set your admin PASSWORD here
  db.php             database (auto-creates data/tco.sqlite on first run)
  functions.php      helpers (uploads, video embedding, etc.)
  header.php/footer.php   shared layout
assets/
  css/style.css      the theme
  img/logo.png       the team emblem (transparent) + logo.jpeg original
uploads/             where posted photos & videos are stored
data/                where the SQLite database lives (auto-created)
```

---

## Setup (about 2 minutes)

1. **Upload** the whole folder to your web host (into `public_html`, or a
   subfolder like `public_html/outlaws`).

2. **Set your admin password.** Open `includes/config.php` and change:
   ```php
   define('ADMIN_PASSWORD', 'Outlaws2026!');   // <- change this
   ```

3. **Make two folders writable** so posts and uploads can be saved. In your
   host's file manager set permissions to `755` (or `775`) on:
   - `data/`
   - `uploads/`

4. Visit your site — that's it. The home page is live, and `/admin.php` is your
   control room.

**Requirements:** PHP 7.4 or newer with PDO SQLite (standard on DreamHost and
virtually every shared host).

---

## Using the admin

Go to **`yoursite.com/admin.php`** and log in with your password.

- **News** — a headline + story, with an optional photo.
- **Photo** — upload a picture with a caption (shows in the gallery).
- **Video** — either paste a **YouTube or Vimeo link** (easiest) or upload a
  video file.

Every item appears on the home page right away. Use **Delete** on any item to
remove it (its uploaded file is deleted too).

---

## Notes

- The database is a single file: `data/tco.sqlite`. To back up your site's
  content, just save that file plus the `uploads/` folder.
- `data/` and `uploads/` include `.htaccess` files that block the database from
  being downloaded and stop uploaded files from being executed as code.
