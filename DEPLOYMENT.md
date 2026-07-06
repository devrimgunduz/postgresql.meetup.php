# postgresql.istanbul – Deployment Guide

## Stack

- **OS**: Any modern Linux (AlmaLinux 9 / RHEL 9 / Debian 12 / Ubuntu 24.04)
- **Web server**: Nginx
- **PHP**: 8.1+ with `pdo_pgsql`, `bcrypt` (bundled in PHP core)
- **Database**: PostgreSQL 14+

---

## 1. PostgreSQL setup

```sql
createuser -P pgistanbul           -- set a strong password
createdb -O pgistanbul pgistanbul
psql -U pgistanbul pgistanbul < sql/schema.sql
```

---

## 2. Directory layout on server

```
/var/www/postgresql.istanbul/
├── public/        ← document root (index.php, previous.php)
├── admin/         ← admin panel PHP files
├── includes/      ← config.php, auth.php, meetups.php
├── assets/        ← css, js, img (served directly by nginx)
├── cron/          ← archive.php
└── sql/           ← schema.sql (not web-accessible)
```

Deploy with:
```bash
rsync -av --exclude='.git' ./ user@server:/var/www/postgresql.istanbul/
```

---

## 3. Environment variables (recommended)

Set in `/etc/environment` or in the PHP-FPM pool config (`/etc/php/8.2/fpm/pool.d/pgistanbul.conf`):

```ini
env[PGHOST]     = localhost
env[PGPORT]     = 5432
env[PGDATABASE] = pgistanbul
env[PGUSER]     = pgistanbul
env[PGPASSWORD] = <your-password>
```

Or edit `includes/config.php` directly (keep it outside the web root if possible).

---

## 4. Apache

Required modules: `mod_rewrite`, `mod_ssl`, `mod_headers`.

**Debian / Ubuntu:**
```bash
a2enmod rewrite ssl headers
cp apache.conf /etc/apache2/sites-available/postgresql.istanbul.conf
a2ensite postgresql.istanbul
apache2ctl configtest && systemctl reload apache2
```

**RHEL / AlmaLinux / Rocky Linux:**
```bash
# mod_rewrite, mod_ssl, mod_headers are enabled by default
cp apache.conf /etc/httpd/conf.d/postgresql.istanbul.conf
# Update log paths in the conf: /var/log/httpd/ instead of /var/log/apache2/
apachectl configtest && systemctl reload httpd
```

PHP is handled by `mod_php` or `php-fpm` via `proxy_fcgi` — whichever you already have configured. No changes needed in `apache.conf` for that; Apache picks it up automatically via the existing PHP handler.

---

## 5. TLS – Let's Encrypt

```bash
certbot --nginx -d postgresql.istanbul -d www.postgresql.istanbul
```

---

## 6. Auto-archiving cron

```bash
crontab -u www-data -e
# paste the line from crontab.txt
```

Auto-archiving also fires on every public page load (via `auto_archive()`), so the cron is a belt-and-suspenders fallback.

---

## 7. First login

Default credentials (set in schema.sql seed):

- **Username**: `admin`
- **Password**: `changeme`

**Change the password immediately** via Admin → Users → Change Password.

The bcrypt hash in schema.sql is for `changeme`. If you want to pre-seed a different password:

```php
php -r "echo password_hash('yourpassword', PASSWORD_BCRYPT);"
```

---

## 8. File permissions

```bash
chown -R www-data:www-data /var/www/postgresql.istanbul
chmod -R 750 /var/www/postgresql.istanbul
chmod -R 640 /var/www/postgresql.istanbul/includes/config.php
```

---

## 9. Typical admin workflow

1. Go to `https://postgresql.istanbul/admin/`
2. **New Meetup** → fill in date, venue EN/TR, address, map URL, registration URL
3. **Manage Talks** → add speakers and talk details (EN + TR)
4. Set status to **Published** when ready to go live
5. The meetup appears on the homepage automatically
6. After the event date + 6 hours, it moves to **Past** automatically (or you can flip it manually)

---

## Speaker photo uploads

Speaker photos are uploaded as files (JPG/PNG, max 5 MB) and stored on disk under:

```
/var/www/html/postgresql.istanbul/public/uploads/speakers/
```

Make sure this directory is writable by the web server user:

```bash
mkdir -p /var/www/html/postgresql.istanbul/public/uploads/speakers
chown -R apache:apache /var/www/html/postgresql.istanbul/public/uploads
chmod 755 /var/www/html/postgresql.istanbul/public/uploads/speakers
```

PHP's upload limits must be large enough — check `/etc/php.ini`:

```ini
upload_max_filesize = 8M
post_max_size = 8M
```

Reload PHP-FPM after changing these:

```bash
systemctl reload php-fpm
```

When a speaker photo is replaced or removed via the admin panel, the old file is automatically deleted from disk.

---

## Slide deck uploads

Speakers' slides (PDF only, max 25 MB) are uploaded via the admin panel and stored under:

```
/var/www/html/postgresql.istanbul/public/uploads/slides/
```

Make sure this directory is writable:

```bash
mkdir -p /var/www/html/postgresql.istanbul/public/uploads/slides
chown -R apache:apache /var/www/html/postgresql.istanbul/public/uploads
chmod 755 /var/www/html/postgresql.istanbul/public/uploads/slides
```

A "Download Slides" link/button appears automatically on the public meetup page for
any talk that has slides attached — both the upcoming meetup and past meetups.

Run the schema migration to add the new column:

```sql
ALTER TABLE talks ADD COLUMN IF NOT EXISTS slides_url TEXT;
```

---

## Local configuration overrides (config_local.php)

To keep real credentials and server-specific settings out of git, create
`includes/config_local.php` on each server — it's automatically loaded by
`config.php` if present, and is gitignored.

```bash
cp includes/config_local.php.example includes/config_local.php
nano includes/config_local.php   # fill in real DB password, etc.
chmod 640 includes/config_local.php
```

Any variable you set there (`$db_host`, `$db_pass`, `$session_ttl`, etc.)
overrides the corresponding default in `config.php`. Settings you don't
override keep using the default or environment-variable value.
