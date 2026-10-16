<?php
declare(strict_types=1);

/**
 * Step-by-step help shown behind the "How this works" button on each admin screen.
 * Each guide: title, intro, sections of [h => heading, p => text, list => items, code => lines to copy].
 */
function admin_guide(string $template, array $data): ?array
{
    $site = setting('site_name', 'AivraSol');
    $root = str_replace('\\', '/', ROOT_PATH);
    $onWindows = PHP_OS_FAMILY === 'Windows';
    $cronLine = 'php ' . ($onWindows ? '/home/YOUR_CPANEL_USER/public_html' : $root) . '/bin/cron.php >/dev/null 2>&1';
    $cronSection = [
        'h' => 'Turn on the background sender (cron job)',
        'p' => 'Campaign emails and daily backups run in the background every 5 minutes. Set this up once on the live server.',
        'list' => [
            'In cPanel, open Cron Jobs.',
            'Under "Common Settings" choose "Once Per Five Minutes (*/5 * * * *)".',
            'Paste the command below in the "Command" box and click Add New Cron Job.',
            $onWindows ? 'You are on a Windows test computer (XAMPP), which has no cron. Replace YOUR_CPANEL_USER with your cPanel username on the live server. If your files are in a subfolder, add it to the path.' : 'Check the path matches where the website files are. Ask your host if PHP is at a different path, for example /usr/local/bin/php.',
            'Come back here after 10 minutes. "Background sender: running" means it works.',
        ],
        'code' => [$cronLine],
    ];
    if (Auth::can('settings')) {
        require_once APP_PATH . '/lib/Campaigns.php';
        $cronSection['list'][] = 'No Cron Jobs in your hosting? Create a free account at cron-job.org and make a job that opens the private address below every 5 minutes. Keep this address secret.';
        $cronSection['code'][] = absolute_url('cron/' . Campaigns::cronToken());
    }

    $guides = [
        'dashboard' => [
            'title' => 'How the dashboard works',
            'intro' => 'This is your starting page. It only shows what your role can open.',
            'sections' => [
                ['h' => 'Numbers at the top', 'list' => ['New leads: enquiries nobody has opened yet. Click to see them.', 'Leads in last 30 days: every enquiry except spam.', 'New applications and open jobs: shown to HR and admins.']],
                ['h' => 'Launch checklist (admins)', 'p' => 'Each red line is something to finish before or soon after launch. Click a line to go to the right page. It turns green by itself when done.'],
                ['h' => 'Run a full system check', 'p' => 'Open System check in the menu (admins) to test the database, folders, email, PDFs, cron and security in one click, with a saved log.'],
            ],
        ],
        'leads' => [
            'title' => 'How leads work',
            'intro' => 'Every form on the website (contact, free consultation, quote, hire a developer) creates a lead here, and your team gets an email.',
            'sections' => [
                ['h' => 'Step 1: Open a new lead', 'p' => 'Click the name. You see everything the visitor sent, including the plan and options they chose.'],
                ['h' => 'Step 2: Reply', 'p' => 'Click "Reply by email". Your email program opens with their address filled in. Replies usually win the client when sent within one hour.'],
                ['h' => 'Step 3: Keep the status up to date', 'list' => ['New: not handled yet.', 'Contacted: you replied.', 'Qualified: real budget and need.', 'Won or Lost: final result.', 'Spam: hidden from the list and reports.'], 'p' => 'Add private notes, for example the call date or the budget they mentioned.'],
                ['h' => 'Step 4: Turn a won lead into a client', 'p' => 'Open the lead and click "Create client". Their details are filled in, the lead is marked Won, and they get a client portal invitation.'],
                ['h' => 'Export', 'p' => 'Use Export CSV to open the list in Excel or Google Sheets. The search and filters you set are applied to the export.'],
            ],
        ],
        'lead' => 'leads',
        'applications' => [
            'title' => 'How job applications work',
            'intro' => 'Applications come from the Careers page. CVs are stored privately and never appear on the website.',
            'sections' => [
                ['h' => 'Review', 'list' => ['Open an application to read the cover note and download the CV.', 'Set the status: Reviewing, Interview, Offer, Hired or Rejected.', 'Add private notes after each interview.']],
                ['h' => 'Hired someone?', 'p' => 'Go to Employees, click "Add employee", and fill in their details. From there you can make their ID card and give them a login.'],
            ],
        ],
        'application' => 'applications',
        'settings' => [
            'title' => 'How settings work',
            'intro' => 'Everything here changes the live website as soon as you click Save settings.',
            'sections' => [
                ['h' => 'Brand and home page', 'p' => 'Company name is used everywhere: page titles, emails, ID cards, letters and Google. The hero heading uses | to split it into two lines.'],
                ['h' => 'Contact details and offices', 'p' => 'Phone, WhatsApp and emails show in the footer, the contact page and Google data. Each office can have its own phone, WhatsApp and email in Website content, Offices.'],
                ['h' => 'Social links and the WhatsApp button', 'list' => ['Paste the full link of each social page (starting with https://). Only filled ones appear as icons in the footer.', 'The floating WhatsApp button uses the WhatsApp number in Contact details. Write the message that is filled in when someone taps it, or untick the box to hide the button.']],
                ['h' => 'Get verified on Google', 'list' => [
                    'Open search.google.com/search-console and click Add property. Choose "URL prefix" and enter ' . rtrim(absolute_url(), '/') . '.',
                    'Choose the "HTML tag" method and copy the whole tag.',
                    'Paste it in "Google Search Console code" here and save.',
                    'Go back to Search Console and click Verify.',
                    'In Search Console open Sitemaps and submit: sitemap.xml',
                ]],
                ['h' => 'Get verified on Bing (also feeds ChatGPT search and Copilot)', 'list' => [
                    'Open bing.com/webmasters and sign in. Choose "Import from Google Search Console" (fastest) or add the site by hand.',
                    'If adding by hand, choose the meta tag method, paste the tag in "Bing Webmaster Tools code" here, save, then click Verify in Bing.',
                    'Submit the sitemap in Bing too.',
                ]],
                ['h' => 'Company summary for AI assistants', 'p' => 'Write two or three plain sentences: what you do, for whom, and where. It goes into ' . absolute_url('llms.txt') . ' and Google business data, which AI tools read.'],
                ['h' => 'Google Ads', 'list' => [
                    'In Google Ads open Goals, then Conversions, then New conversion action, then Website. Create a "Submit lead form" action.',
                    'Choose "Use Google tag" set up manually. You will see a tag ID like AW-123456789 and a conversion label like AbC-D_efG123.',
                    'Paste both here and save. A lead is counted each time a website form is sent.',
                ]],
                ['h' => 'Meta (Facebook and Instagram) Pixel', 'list' => [
                    'In Meta Events Manager, click Connect data sources, then Web, and create a Pixel.',
                    'Copy the Pixel ID (numbers only) and paste it here.',
                    'You do not need to add any code. The website adds the Pixel and sends a Lead event when a form is sent.',
                ]],
                ['h' => 'Privacy', 'p' => 'Analytics, Google Ads and Meta only load after a visitor accepts cookies, as the law requires in the UK, EU and UAE. The cookie policy page lists them automatically.'],
            ],
        ],
        'email' => [
            'title' => 'How to set up email',
            'intro' => 'The website sends alerts to your team, automatic replies to visitors, invitations, password links and portal notices. Set this up first, then use the test button.',
            'sections' => [
                ['h' => 'Step 1: Create a mailbox in cPanel', 'list' => ['cPanel, Email Accounts, Create. For example no-reply@your-domain or hello@your-domain.', 'Click "Connect Devices" next to it. Note the outgoing server, the SMTP port (usually 465) and the username (the full email address).']],
                ['h' => 'Step 2: Fill in the sending settings here', 'list' => ['Choose "Use the settings below".', 'Send from (email): the mailbox you created. Send from (name): ' . $site . '.', 'SMTP server: the outgoing server, for example mail.your-domain.com.', 'Port 465 with SSL, or 587 with TLS.', 'Username and password of that mailbox. The password is stored encrypted.']],
                ['h' => 'Step 3: Who gets the emails', 'p' => 'Add one or more addresses for enquiries and for job applications, one per line.'],
                ['h' => 'Step 4: Test', 'p' => 'Enter your own email and click Send test email. Check the inbox and the spam folder. The email log at the bottom shows every send and any error.'],
                ['h' => 'Step 5: Stay out of spam', 'p' => 'In cPanel open Email Deliverability and click Repair next to SPF and DKIM. Without these, emails often land in spam.'],
                ['h' => 'Automatic replies', 'p' => 'Edit the subject and text for each form. {name} becomes the visitor\'s first name and {site_name} your company name.'],
            ],
        ],
        'users' => [
            'title' => 'How team accounts and roles work',
            'intro' => 'Give each person their own login. Their role decides what they can open.',
            'sections' => [
                ['h' => 'Roles', 'list' => array_map(fn ($r) => $r['label'] . ': ' . $r['about'] . '.', array_values(Auth::ROLES))],
                ['h' => 'Add someone (best way)', 'list' => ['Fill in name, email and role.', 'Keep "Email them a link" ticked and click Add team member.', 'They get an email, choose their own password and can sign in. The link works for 48 hours.']],
                ['h' => 'Or give a temporary password', 'p' => 'Untick the email option and type a temporary password. Tell it to them in person. They must change it the first time they sign in.'],
                ['h' => 'Manage', 'list' => ['Change a role with the dropdown and Save.', 'Manage, Email a reset link: when someone forgot their password.', 'Manage, Disable account: they are signed out at once. Use this when someone leaves.', 'You cannot change or disable your own account, and the last admin cannot be removed.']],
                ['h' => 'Recent activity', 'p' => 'Every sign-in, change and download is recorded at the bottom for security.'],
            ],
        ],
        'profile' => [
            'title' => 'Your profile',
            'sections' => [
                ['h' => 'Password', 'p' => 'Use at least 10 characters with letters and numbers. Changing it signs you out on every other device.'],
                ['h' => 'Employees', 'p' => 'If HR linked your account to your staff record, you can download your ID card and your letters here.'],
            ],
        ],
        'employees' => [
            'title' => 'How employees, ID cards and letters work',
            'intro' => 'Keep one record per team member. From it you make their ID card, experience letter and login.',
            'sections' => [
                ['h' => 'Step 1 (admin, once): set up signatures', 'p' => 'Open Letters and ID cards. Add the signatory name and title, upload the signature and the company stamp. Without a signature, letters cannot be issued.'],
                ['h' => 'Step 2: Add an employee', 'list' => ['Click Add employee. Fill in name, job title, department and joining date.', 'Choose the pronoun used in letters (he, she or they).', 'Upload a clear head-and-shoulders photo for the ID card. It is cropped to 4:5 and stored privately.', 'National ID is optional and stored encrypted.', 'The employee ID (for example ' . (function_exists('setting') ? strtoupper(setting('doc_code_prefix', 'AVS')) : 'AVS') . '-0001) is given automatically.']],
                ['h' => 'Step 3: ID card', 'p' => 'Open the employee and click "ID card PDF". It is the standard card size (54 x 86 mm), front and back. Print it on PVC card stock or ask a print shop. The QR code on the front opens a page that shows if the person still works here.'],
                ['h' => 'Step 4: Experience letter', 'list' => ['Click "Experience letter". Pick the last working day.', 'The text is filled in from your template. Change it for this person if needed.', 'Click Issue letter. It gets a reference number like AVS-EXP-2026-0001 and its own QR code.', 'Download the PDF from "Letters issued". You can withdraw a letter later; the QR page will then say it is no longer valid.']],
                ['h' => 'Step 5: Give them a login (optional)', 'p' => 'In "Sign-in for this employee" enter their email and click Create login. They receive a link to choose a password. They will only see their own card and letters.'],
                ['h' => 'When someone leaves', 'list' => ['Set the status to Resigned or Left and add the last working day.', 'Turn their login off.', 'The QR on their old ID card will now show "No longer employed".']],
            ],
        ],
        'employee-edit' => 'employees',
        'employee-letter' => 'employees',
        'documents-settings' => [
            'title' => 'How to set up letters and ID cards',
            'sections' => [
                ['h' => 'Step 1: Signatory', 'p' => 'Name and title of the person who signs, for example the CEO. They appear under the signature on letters and on the back of ID cards.'],
                ['h' => 'Step 2: Signature image', 'list' => ['Sign on plain white paper with a dark pen.', 'Take a straight, well-lit photo or scan it.', 'For the cleanest look, remove the white background with a free tool such as remove.bg and save as PNG.', 'Upload it here. A chequered pattern behind the preview means the background is transparent.']],
                ['h' => 'Step 3: Company stamp (optional)', 'p' => 'Upload a round seal as a PNG with a transparent background. It sits next to the signature on letters.'],
                ['h' => 'Step 4: Letter template', 'p' => 'Write the standard text. Words in curly brackets are filled in for each person. Keep {name}, {designation}, {join_date}, {end_date} and {duration}. Leave a blank line between paragraphs.'],
                ['h' => 'Step 5: Check the design', 'p' => 'Click "Preview a sample letter" and "Preview a sample ID card" at the top. They use a made-up employee so you can check everything before issuing real ones.'],
                ['h' => 'Good to know', 'list' => ['Changing the signature updates new cards and letters. Letters issued before keep the signature they were signed with.', 'QR codes use the website address from app/config.php (base_url). On the live server they show your real domain automatically.']],
            ],
        ],
        'clients' => [
            'title' => 'How clients and the client portal work',
            'intro' => 'Each client gets a private portal at ' . absolute_url('portal') . ' to see their projects, chat with your team and check payments.',
            'sections' => [
                ['h' => 'Step 1: Add the client', 'list' => ['Click Add client, or open a won lead and click "Create client".', 'The email is their portal sign-in.', 'Keep "Email a portal invitation" ticked. They receive a link to choose a password (valid for 7 days).']],
                ['h' => 'Step 2: Create their project', 'p' => 'Open the client and click New project. Add the budget, currency, dates and project manager.'],
                ['h' => 'Step 3: Keep them updated', 'p' => 'Post updates, reply in chat and add invoices and payments on the project page. The client sees all of it in the portal.'],
                ['h' => 'Access', 'list' => ['Clients only see their own projects. They can never see other clients.', 'Turn portal access off when a contract ends. They are signed out at once.', '"Email a password reset link" helps a client who forgot their password.']],
            ],
        ],
        'client-edit' => 'clients',
        'projects' => [
            'title' => 'How client projects work',
            'intro' => 'Everything on a project page is visible to that client in their portal, except private notes on the client record.',
            'sections' => [
                ['h' => 'Progress and status', 'p' => 'Set the status and percentage in Project details. Posting an update can also change the percentage.'],
                ['h' => 'Updates', 'p' => 'Write what was done this week in plain words. Tick "Email the client" so they know to check the portal.'],
                ['h' => 'Chat', 'list' => ['Messages appear for the client within a few seconds.', 'Files up to 10 MB can be shared: PDF, images, Word, Excel, PowerPoint, text and ZIP.', 'The client gets at most one email every 15 minutes about new messages, so a busy chat does not flood their inbox.', 'A number next to Client projects in the menu means unread client messages.']],
                ['h' => 'Money: what each number means', 'list' => ['Agreed budget: the total price of the project.', 'Invoice: an amount you asked the client to pay.', 'Payment received: money that arrived. Refund: money you returned.', 'Paid so far = payments minus refunds.', 'Remaining budget = budget minus paid.', 'Due now = invoiced minus paid (what the client owes today).']],
            ],
        ],
        'project-edit' => 'projects',
        'campaigns' => [
            'title' => 'How email campaigns work',
            'intro' => 'Send personal emails to many people, slowly and safely, so your mailbox is not blocked.',
            'sections' => [
                ['h' => 'Step 1 (admin, once): set your limits', 'p' => 'In Sending settings, start low: 30 per hour and 150 per day, 9:00 to 18:00 on weekdays. A new mailbox should send about 20 a day in week one and 40 in week two before going higher.'],
                ['h' => 'Step 2 (recommended): use a separate mailbox for cold email', 'list' => ['Buy a second domain, for example ' . strtolower(preg_replace('/\s+/', '', $site)) . '.co, and create a mailbox on it in cPanel.', 'Fill in its SMTP details in Sending settings.', 'If cold emails ever get blocked, your main domain and normal company email stay safe.']],
                $cronSection,
                ['h' => 'Step 4: Add contacts', 'p' => 'Open Contacts. Import a CSV (from Excel or Google Sheets, "Download as CSV") with an email column, or add website leads in one click. Give each list a tag, for example uk-retail.'],
                ['h' => 'Step 5: Create the campaign', 'list' => ['Click New campaign and pick a template.', 'Choose the audience tag.', 'Edit the subject and text. {first_name} and {company} are filled in for each person. {{Hi|Hello}} picks a random word so every email is slightly different.', 'Save, read the preview, then click "Send me a test".']],
                ['h' => 'Step 6: Start', 'p' => 'Click Start campaign. Emails go out one by one with random gaps, inside your window and under your limits. Pause or cancel at any time.'],
                ['h' => 'Built-in protection', 'list' => ['Every email has an unsubscribe link and the one-click unsubscribe headers Gmail and Outlook require.', 'People who unsubscribe or bounce are never emailed again, even if deleted and imported later.', 'If 5 emails in a row fail, all campaigns pause to protect the mailbox.', 'The same person is not emailed twice within the "days before emailing again" setting.']],
                ['h' => 'Stay out of spam', 'list' => ['cPanel, Email Deliverability: click Repair on SPF and DKIM for the sending domain. Add a DMARC record too.', 'Keep cold emails short, personal and plain, with one link and no attachments.', 'Only email people with a real business reason, and remove anyone who asks.', 'No tool can promise a mailbox will never be suspended. Going slowly is what keeps it safe.']],
            ],
        ],
        'campaign-edit' => 'campaigns',
        'contacts' => [
            'title' => 'How contacts work',
            'sections' => [
                ['h' => 'Import a CSV', 'list' => ['In Excel or Google Sheets, put column names in the first row: email, first_name, last_name, company, website, country, tags.', 'Only email is required. A single "name" column also works.', 'Save or download as CSV, choose the file here, tick the confirmation and click Import.', 'People already in the list are updated, never duplicated.']],
                ['h' => 'Tags', 'p' => 'Tags group contacts, for example uk-retail or oct-2026. A campaign can be sent to one tag.'],
                ['h' => 'Statuses', 'list' => ['Subscribed: can receive campaigns.', 'Unsubscribed: asked to stop. Only an admin can re-subscribe someone, and only if they asked.', 'Bounced: the address does not exist. Never emailed again.']],
            ],
        ],
        'backups' => [
            'title' => 'How backups work',
            'sections' => [
                ['h' => 'Make a backup', 'list' => ['Full backup: one ZIP with the database and all uploaded files (images, staff photos, signatures, CVs, portal files).', 'Database only: smaller, without files.', 'Click Download and keep a copy on your computer or Google Drive. Do this at least once a month.']],
                ['h' => 'Automatic backups', 'p' => 'With the cron job running, the database is backed up every day and the last 7 are kept.'],
                ['h' => 'On XAMPP', 'p' => 'Full backups need the zip add-on. Open C:\\xampp\\php\\php.ini, remove the ; before extension=zip, save, and restart Apache.'],
                ['h' => 'Moving to a new host', 'list' => ['Make a full backup here and download it.', 'On the new host, deploy the code and create an empty database.', 'Copy app/config.php with the same app_key (it unlocks saved passwords and ID numbers). Update base_url and the database details.', 'Upload the ZIP to storage/backups and run the two commands below in the cPanel Terminal.', 'Add the cron job again and point the domain to the new host.'], 'code' => ['php database/restore.php storage/backups/FILE-NAME.zip', 'php database/install.php']],
            ],
        ],
        'system' => [
            'title' => 'How the system check works',
            'sections' => [
                ['h' => 'What it tests', 'list' => ['PHP version and add-ons.', 'Database connection, tables and updates.', 'Folders the website must be able to write to.', 'Security keys, website address and HTTPS.', 'Encryption, PDF making and email settings (and a real test email if you tick the box).', 'Background sender (cron), backups, search indexing and that private folders are blocked from the web.']],
                ['h' => 'Reading the results', 'list' => ['Green: fine.', 'Amber: works, but worth fixing.', 'Red: must be fixed. The "How to fix" text says what to do.']],
                ['h' => 'Log', 'p' => 'Every run is saved with the date and who ran it. Run it after every update and after moving hosts.'],
            ],
        ],
    ];
    if (str_starts_with($template, 'content')) {
        $key = (string) ($data['key'] ?? '');
        $tips = [
            'offerings' => ['Type "Service" shows under Services; "Hire a developer" shows under Hire Developers and in the hire pricing plan.', 'Features: one per line as Title | Description.', 'Untick "Show on website" to hide a page without deleting it.'],
            'projects' => ['Upload a plain screenshot. No need to crop or add borders: it is placed in a device frame on a backdrop made from its own colours.', 'Choose the frame in "Show the screenshot in": Browser for websites, Desktop app for Windows or Mac software, Tablet or Mobile for apps. Auto picks one from the image shape.', 'Add a phone screenshot too and Auto shows a laptop with a phone in front.', 'Background colour: Auto gives each project a different soft colour from the brand palette. Pick one yourself to match the project, or "Colours from the screenshot" for a blurred backdrop.', 'After saving, the preview at the top of the page shows exactly how it looks.', 'Untick "Sample content" once a project is real. Samples show on the launch checklist until replaced.', 'Feature on home page puts it in the home page slider.'],
            'posts' => ['Write normally. A blank line starts a new paragraph.', '## Heading makes a section (it also appears in "On this page"). ### makes a smaller heading.', '- item makes a list. 1. item makes a numbered list. **bold** and *italic* work.', '[link text](/pricing) links to your own page. [link text](https://...) links to another site.', 'A post with a future date stays hidden until that day. Aim for two good posts a month.', 'Search engines are told about new and changed posts automatically.'],
            'plans' => ['Group "Build a project" or "Hire developers" decides where it shows on the Pricing page.', 'Write [hire-roles] in an options list to show every live hire page automatically.', 'Every question also gets an "Other" choice with a text box.'],
            'jobs' => ['Open jobs show on Careers and are marked up so Google Jobs can list them.', 'Location "Remote" marks it as a remote job for Google.'],
            'faqs' => ['Scope "general" shows on the home page, "pricing" on Pricing and hire pages, "careers" on Careers.', 'Use a page address (for example web-development) to show a question only on that service page.'],
            'offices' => ['Give each office its own phone, WhatsApp number and email. They appear as small Call, WhatsApp and Email buttons on its card on the Contact and About pages.', 'The head office is shown first everywhere.', 'Offices appear on Contact, About, the footer, letters, ID cards and in Google data.'],
            'testimonials' => ['Use real reviews with the client\'s permission. The reviews section stays hidden until you add one.'],
            'email-templates' => ['Templates are starting points for campaigns. Editing a template does not change campaigns already created.', 'Plain layout looks like a personal email and is best for cold outreach.'],
        ];
        return [
            'title' => 'How to edit ' . strtolower((string) ($data['res']['label'] ?? 'content')),
            'intro' => 'Changes show on the website as soon as you save.',
            'sections' => [
                ['h' => 'Add, edit or hide', 'list' => ['Click New to add, or a title to edit.', 'Fields with * are required.', '"Web address" becomes the page link. Use lowercase words with dashes.', 'Untick "Show on website" (or Live) to hide without deleting.', 'Order: smaller numbers show first.']],
                ['h' => 'Tips for this section', 'list' => $tips[$key] ?? ['Keep text short and clear. Check the page on your phone after saving.']],
            ],
        ];
    }
    $g = $guides[$template] ?? null;
    return is_string($g) ? $guides[$g] : $g;
}
