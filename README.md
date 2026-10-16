# AivraSol Website

Company website and admin panel for AivraSol, built with PHP 8 and MySQL. No framework and no build step: upload, import the database, fill in one config file, and it runs.

## What is included

| Area | Details |
|---|---|
| Public pages | Home, 6 service pages, 6 hire-a-developer pages, services and hire overviews, portfolio with case studies, pricing with quote form, careers with job pages and CV upload, about, contact, privacy, terms, cookie policy, 404 and 500 pages |
| Features | Mega menus, hamburger menu on phones, free consultation pop-up, service tabs, project and review sliders, quote cards, FAQ, cookie consent (analytics off by default), back-to-top button, sitemap.xml, robots.txt, Open Graph images, schema.org data |
| Admin panel (`/admin`) | Dashboard with launch checklist, leads inbox (status, notes, search, CSV export), job applications (CV download), editors for services, hire pages, projects, testimonials, pricing plans, jobs and FAQs, site settings (hero photo, contact photo, home page sections on or off), email settings, admin users with activity log, password change |
| Emails | Branded alert to your team for every form (to as many addresses as you like), branded automatic reply to the visitor, editable reply texts, SMTP settings from the admin panel or `app/config.php`, test button and email log |
| Security | Prepared SQL statements, strict Content Security Policy, CSRF tokens, honeypot and timing checks, rate limits, login lockout, Argon2id or bcrypt passwords, secure sessions, file type checks, private CV storage, hashed visitor IPs, hardened `.htaccess` |

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (with WebP) and `openssl`
- MySQL 8 or MariaDB 10.6+
- Apache with `mod_rewrite` (cPanel)
- An SSL certificate (free Let's Encrypt is fine)

## Run on your computer with XAMPP (Windows)

1. Install XAMPP from apachefriends.org and start **Apache** and **MySQL** in the XAMPP Control Panel.
2. Copy this project folder to `C:\xampp\htdocs\aivrasol`.
3. Fill in `app/config.php` (next step), then open a terminal in the project folder and run `C:\xampp\php\php.exe database/install.php`. It creates the database, the tables and the starter content. (Or import `database/schema.sql` and then `database/seed.sql` in phpMyAdmin.)
4. Copy `app/config.sample.php` to `app/config.php` and set:
   - `'env' => 'development'`
   - `'base_url' => 'http://localhost/aivrasol'`
   - database `user` => `root`, `password` => `''` (XAMPP default)
   - any long random text for `app_key` and `setup_token`
5. Open `http://localhost/aivrasol`. Create your admin account at `http://localhost/aivrasol/admin/setup`.

Emails will not send on XAMPP. Leads are still saved and appear in the admin panel.

## Update an existing site

After pulling a new version (`git pull`), run the installer again. It only adds what is new and keeps all your content:

```
php database/install.php
```

On XAMPP: `C:\xampp\php\php.exe database/install.php`. The database user needs ALTER and CREATE rights for updates (XAMPP's `root` and the cPanel main user both have them).

## How the admin panel controls the website

| You change in admin | What updates on the website |
|---|---|
| Services & hire pages: add, edit, hide or reorder | Menus, home page tabs, footer, the page itself, contact form choices, sitemap, and the developer list in hire pricing |
| Pricing plans | Pricing page (grouped as "Build a project" or "Hire developers") and the home page (plans marked "Show on home page"). Each hire page links to the hiring plans with its role preselected |
| Portfolio projects, testimonials, jobs, FAQs | Their sections and pages. Empty sections stay hidden |
| Settings | Company name, hero text and photo, contact details, offices, numbers, social links, analytics, and which home page sections show |
| Email | Who receives enquiries and applications, how emails are sent, and the automatic reply texts |

In a pricing plan, write `[hire-roles]` in an options list to show every live "Hire a developer" page automatically. Every plan question and the contact form also offer an "Other" choice: picking it opens a text box, and what the visitor types is saved with the lead.

## Deploy from GitHub with cPanel (recommended)

1. In cPanel, open **Git Version Control** and click **Create**.
2. Turn on **Clone a Repository**, paste this repository's clone URL, and set the repository path to `/home/YOUR_CPANEL_USER/repositories/aivrasol`.
3. After it clones, open **Manage**, go to **Pull or Deploy**, and click **Deploy HEAD Commit**. The `.cpanel.yml` file copies the site into `public_html`.
4. Then do steps 2 to 8 below (database, config, admin account) once.
5. For future updates: push to GitHub, then in cPanel click **Update from Remote** and **Deploy HEAD Commit**. Your `app/config.php`, uploaded images and CVs are never overwritten.

For a private repository, add the SSH key from cPanel (**SSH Access**, Manage Keys) as a read-only **Deploy key** in the GitHub repository settings, and clone with the SSH URL.

## Manual install on cPanel

1. **Upload** the whole `aivrasol` folder. The best setup is pointing the domain's document root at the `public` folder. If your host does not allow that, upload everything into `public_html`; the root `.htaccess` sends visitors into `public` and blocks the private folders.
2. **Create a database** in cPanel (MySQL Databases). Create a user and give it only `SELECT, INSERT, UPDATE, DELETE` on that database.
3. **Import** `database/schema.sql` and then `database/seed.sql` with phpMyAdmin. (The import needs a user with CREATE rights; use the cPanel main user for the import only.)
4. **Configure**: copy `app/config.sample.php` to `app/config.php` and fill in:
   - `base_url`: `https://www.aivrasol.com`
   - the database name, user and password
   - `app_key`: a 64-character random string
   - `setup_token`: a long random string
   - the `mail` section with your SMTP details (for example your hosting email account) so lead alerts arrive
5. **Folder permissions**: `storage/` and `public/uploads/` must be writable by PHP (usually 755 for folders, 644 for files).
6. **Create your admin account**: open `https://www.aivrasol.com/admin/setup`, enter the setup token and your details. This page switches itself off after the first admin is created.
7. **Turn on HTTPS redirect**: in `public/.htaccess`, remove the `#` in front of the three "Force HTTPS" lines.
8. **Sign in** at `/admin`, work through the launch checklist on the dashboard, and fill in Settings.

To generate the random strings, run this on any computer with PHP:

```
php -r "echo bin2hex(random_bytes(32));"
```

## Before you go live

These items also appear on the admin dashboard.

- Replace the 6 sample portfolio projects with your real work (Admin, Portfolio projects). They are marked "Sample".
- Add real client testimonials. The reviews section stays hidden until you add at least one.
- Add your phone, WhatsApp, office addresses and sales contact (Admin, Settings).
- Add company numbers such as projects delivered and team size. The numbers strip stays hidden until you do.
- Add prices to the pricing plans if you want to show "From $..." labels (optional).
- Add your Google Analytics 4 ID in Settings if you use it. It only loads after a visitor accepts analytics cookies.
- Submit `https://www.aivrasol.com/sitemap.xml` in Google Search Console.
- Have a lawyer review the privacy, terms and cookie pages for your country.
- The hero photo is the image you supplied. If it was made from another company's photo, replace it with your own office or team photo before launch.

## Brand colours and fonts

| Token | Value | Use |
|---|---|---|
| Brand orange | `#FF5B35` | Buttons, bars, icons, active states |
| Deep orange | `#B8380F` | Small orange text and links |
| Slate | `#333F48` | Headings, dark bands |
| Ink | `#191F24` | Strong text, menu |
| Body grey | `#5F666B` | Paragraphs |
| Section grey | `#EBECED` | Form bands |
| Fonts | Bebas Neue, Inter | Self-hosted in `public/assets/fonts` |

Buttons use the brand orange with white text, matching the reference design. That pairing has a contrast ratio of 3.1, below the WCAG AA level of 4.5 for normal text. To meet AA, change `--btn-bg` at the top of `public/assets/css/app.css` to `#E32B00`.

## Folder structure

```
app/            PHP code (never public)
  admin/        Admin routes, handlers and editable content types
  lib/          Database, security, validation, mail, uploads, router
  views/        Page templates, partials and admin screens
  config.php    Your settings (create from config.sample.php)
database/       schema.sql and seed.sql
public/         Web root: index.php, assets, uploads
storage/        Logs, sessions and CVs (never public)
```

## Testing done before handover

- 15 pages at 6 screen widths (360 to 1440 px): no horizontal scrolling, no console errors, no broken images
- 30 browser tests covering menus, hamburger, tabs, sliders, pop-ups, cookie consent, all forms, CV upload and the no-JavaScript fallback
- Security tests: missing or fake CSRF tokens, bots, forged timestamps, invalid input, email header injection, open redirects, rate limits, PHP disguised as PDF, SQL injection in admin search, stored XSS, login lockout, setup token, private folders under Apache
- Lighthouse (mobile): Performance 99 to 100, Best Practices 100, SEO 100, Accessibility 95 to 97 (the remaining points are the brand-orange button contrast described above)
