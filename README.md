# Treasure Coast Outlaws — Team Website

A simple, self-contained website for the Treasure Coast Outlaws baseball team:

- **Home page** — a dark, athletic landing page with the team emblem, plus
  auto-updating **News**, **Photo Gallery** and **Videos** sections.
- **Admin page** — a password-protected control room where you post News,
  Photos and Videos. Anything you post shows up on the home page instantly.

Runs on plain PHP with a MySQL database. The site files and the database can
live on different hosts — the app just connects to your MySQL host over the
details you put in `includes/config.php`.

---

## What's in here

```
index.php            The public home page
admin.php            Login + post manager (News / Photos / Videos)
database.sql         ← import this ONCE on your MySQL host to create the table
includes/
  config.php         ← set DB connection + admin PASSWORD here
  db.php             connects to MySQL (also self-creates the table if missing)
  functions.php      helpers (uploads, video embedding, etc.)
  header.php/footer.php   shared layout
assets/
  css/style.css      the theme
  img/logo.png       the team emblem (transparent) + logo.jpeg original
uploads/             where posted photos & videos are stored
```

---

## Setup (about 3 minutes)

1. **Create the database.** On your MySQL host, create a database (any name),
   then import the included **`database.sql`** — in phpMyAdmin use the *Import*
   tab, or run it from the command line. That creates the single `posts` table
   the site needs. Note down the host, database name, username and password.

2. **Upload** the site files to your web host (into `public_html`, or a
   subfolder like `public_html/outlaws`).

3. **Fill in `includes/config.php`:**
   ```php
   define('DB_HOST', 'mysql.yourprovider.com'); // your DB host
   define('DB_PORT', '3306');
   define('DB_NAME', 'treasure_coast_outlaws'); // your database name
   define('DB_USER', 'your_db_user');
   define('DB_PASS', 'your_db_password');

   define('ADMIN_PASSWORD', 'Outlaws2026!');    // <- change this to your own
   ```

4. **Make the `uploads/` folder writable** (permissions `755` or `775`) so
   posted photos and videos can be saved.

5. Visit your site — the home page is live, and `/admin.php` is your control
   room.

**Requirements:** PHP 7.4+ with PDO MySQL (standard on virtually every host),
and a MySQL/MariaDB database. The site files and the database can be on
different hosting providers.

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

- To back up your content, back up your MySQL database plus the `uploads/`
  folder (that's where the actual photos and video files are stored).
- The `uploads/` folder includes an `.htaccess` that stops uploaded files from
  being executed as code (a safety measure).
