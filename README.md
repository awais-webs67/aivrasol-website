# AivraSol Website

Company website and admin panel for AivraSol, built with PHP 8 and MySQL. No framework and no build step: upload, import the database, fill in one config file, and it runs.

## What is included

| Area | Details |
|---|---|
| Public pages | Home, 6 service pages, 6 hire-a-developer pages, services and hire overviews, portfolio with case studies, pricing with quote form, blog with categories and RSS, careers with job pages and CV upload, about, contact, privacy, terms, cookie policy, 404 and 500 pages |
| Features | Mega menus, hamburger menu on phones, free consultation pop-up, service tabs, project and review sliders, quote cards, FAQ, cookie consent (analytics and ads off by default), back-to-top button |
| Google and AI search | Sitemap with dates, robots.txt that welcomes Google, Bing and AI crawlers (ChatGPT, Claude, Perplexity, Gemini), `llms.txt` and `llms-full.txt` for AI assistants, schema.org data (company, website, breadcrumbs, services, articles, FAQs, job posts), IndexNow pings to Bing, Search Console and Bing verification, an indexing on/off switch |
| Ads | Google Ads conversion tag and Meta Pixel, loaded only after a visitor accepts marketing cookies (Google Consent Mode v2). Every form sent counts as a lead conversion |
| Admin panel (`/admin`) | Dashboard per role, leads inbox, job applications, website content editors, blog editor, settings, email settings, team and roles, backups |
| Team roles | Admin, HR, Sales, Project manager, Content editor and Employee. Each person sees only their part of the panel. Invitations by email, password reset links, forced password change for temporary passwords |
| Employees | Staff records, private photos, ID card PDF (front and back, with QR verification), experience letter PDF with your template, signature and stamp, letter numbers, withdraw and restore, public verification page, employee self-service |
| Client portal (`/portal`) | Clients sign in to see project progress, updates, chat with the team (with file sharing), budget, invoices, payments, remaining budget and amount due. Staff manage it from Admin, Clients and Client projects |
| Email campaigns | Contacts with CSV import, ready-made templates (cold intro, follow-up, newsletter, announcement), personalisation, sending limits per hour and day, sending window, random gaps, one-click unsubscribe, bounce handling, automatic pause after errors, optional separate mailbox for cold email |
| Emails | Branded alert to your team for every form, automatic reply to the visitor, SMTP from the admin panel or `app/config.php`, test button and email log |
| Backups | One-click full backup (database and all files) or database only, daily automatic database backup, and a restore script for moving hosts |
| Security | Prepared SQL statements, strict Content Security Policy, CSRF tokens, honeypot and timing checks, rate limits, login lockout, Argon2id or bcrypt passwords, secure sessions, file type checks, private storage for CVs, photos, signatures and portal files, encrypted ID numbers and SMTP passwords, hashed visitor IPs, hardened `.htaccess` |

## Who can open what

| Role | Can open |
|---|---|
| Admin | Everything, including settings, email, team and roles, letters and ID card settings, backups |
| HR | Dashboard, job applications, job posts, employees, ID cards and experience letters |
| Sales | Dashboard, leads, clients, email campaigns, contacts and email templates |
| Project manager | Dashboard, clients, client projects, updates, chat, invoices and payments |
| Content editor | Dashboard, website content and blog |
| Employee | Their own profile, ID card and letters |

Clients never use the team panel. They have their own sign-in at `/portal`.

## Requirements

- PHP 8.1 or newer with `pdo_mysql`, `mbstring`, `fileinfo`, `gd` (with WebP), `openssl`, `zlib` and `zip` (zip is only needed for full backups; on XAMPP remove the `;` before `extension=zip` in php.ini). All are on by default on cPanel hosts
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
| Offices | Office cards on the Contact page, "Where we work" on the About page, the footer, and Google's business data. Head office is shown first |
| Settings | Company name, hero text and photo, contact details, numbers, social links, analytics, and which home page sections show |
| Email | Who receives enquiries and applications, how emails are sent, and the automatic reply texts |

In a pricing plan, write `[hire-roles]` in an options list to show every live "Hire a developer" page automatically. Every plan question and the contact form also offer an "Other" choice: picking it opens a text box, and what the visitor types is saved with the lead.

## Cron job (needed for campaigns and daily backups)

In cPanel open **Cron Jobs**, choose **Once Per Five Minutes** and enter (use your real path):

```
php /home/YOUR_CPANEL_USER/public_html/bin/cron.php >/dev/null 2>&1
```

If your host has no cron feature, Admin, Email campaigns shows a private address you can give to a free service such as cron-job.org. Without either, campaign emails only go out while a campaign page is open.

## Getting found on Google and in AI answers

1. In Admin, Settings, keep **Let search engines and AI tools index the website** ticked, and write the **Company summary**.
2. Verify the site in **Google Search Console** and **Bing Webmaster Tools** (paste the codes in Settings), then submit `/sitemap.xml` in both. Bing also feeds ChatGPT search and Copilot.
3. Create a **Google Business Profile** for each office, with the same name, address and phone as on the website.
4. List the company on LinkedIn, Clutch, GoodFirms and DesignRush with a link back to the site. AI assistants learn about companies from sources like these.
5. Publish a useful blog post at least twice a month, and ask happy clients for reviews.

## Ads (Google and Meta)

Add the **Google Ads tag ID** (AW-...) and the **lead conversion label**, and the **Meta Pixel ID**, in Admin, Settings. They load only after a visitor accepts marketing cookies, and a lead is counted each time a form is sent.

## Email campaigns without getting blocked

No tool can promise that a mailbox will never be suspended, but these settings keep the risk low:

- Set up SPF, DKIM and DMARC (cPanel, Email Deliverability, Repair).
- For cold outreach, use a second domain and its own mailbox (Admin, Email campaigns, Sending settings), so the main domain stays clean.
- Start slowly: about 20 emails a day in week one, 40 in week two, then up to 100 to 150 a day. The defaults are 30 an hour and 150 a day, 09:00 to 18:00 on weekdays.
- Keep cold emails short and plain, with one link and no attachments. Every email includes an unsubscribe link and the one-click unsubscribe headers Gmail and Outlook require.

## Backups and moving to a new host

Admin, Backups makes a **full backup**: one ZIP with the database and every uploaded file. With the cron job on, the database is also backed up every day (the last 7 are kept). Download a copy regularly.

To move hosts:

1. Make a full backup and download it.
2. On the new host, deploy the code, create an empty database, and copy `app/config.php` with the **same `app_key`** (it unlocks saved passwords and ID numbers). Update `base_url` and the database details if they changed.
3. Upload the ZIP to `storage/backups/` and run `php database/restore.php storage/backups/FILE.zip`, then `php database/install.php`.
4. Add the cron job, point the domain to the new host and check the site.

Everything uses relative paths and settings stored in the database, so nothing else needs changing.

## Open to visitors everywhere

The site does not block any country or IP range. Rate limits only slow down repeated form posts from one address, and they are generous enough for shared mobile networks. Behind Cloudflare, the real visitor IP is detected automatically. Fonts and scripts are self-hosted, so the site still loads where Google services are slow or blocked.

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
  admin/        Admin routes and screens: content, staff, clients, campaigns, backups
  lib/          Database, security, mail, uploads, PDFs, portal, campaigns, SEO, backups
  resources/    Logos used inside PDFs
  vendor/       FPDF (PDF files) and a QR code generator, both free to use
  views/        Page templates, partials, admin and portal screens
  config.php    Your settings (create from config.sample.php)
bin/cron.php    Background tasks (campaigns, cleanup, daily backup)
database/       schema.sql, seed.sql, data files, migrations, install.php, restore.php
public/         Web root: index.php, assets, uploads
storage/        Logs, sessions, CVs, private files and backups (never public)
```

## Testing done before handover

- Public pages at 6 screen widths (360 to 1440 px) and every admin and portal page at 360 px: no horizontal scrolling, no console errors, no broken images
- 30 browser tests for the public site, plus test suites for roles and passwords (37), SEO and ads consent (58), employees and documents (47), the client portal (43) and email campaigns (37)
- Access checks: every role blocked from pages outside its role, clients unable to see other clients' projects, messages or files, employees unable to see other employees' documents
- Security tests: CSRF, bots, rate limits, login lockout, uploads disguised as PDFs, SQL injection, stored XSS in blog posts and chat, unsubscribe and verification tokens, private folders under Apache
- Backup and restore tested into a fresh database: every table and file came back identical
- Lighthouse (mobile): Performance 99 to 100, Best Practices 100, SEO 100, Accessibility 95 to 97
