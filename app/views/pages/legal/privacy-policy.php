<?php include __DIR__ . '/_wrap_start.php'; $info = setting('email_info'); ?>
<p class="lead">This policy explains what personal data <?= e(setting('site_name', 'AivraSol')) ?> collects through this website, why we collect it and how we keep it safe.</p>
<h2>1. Who we are</h2>
<p><?= e(setting('site_name', 'AivraSol')) ?> is a software development company. For any privacy question, email <a href="mailto:<?= e($info) ?>"><?= e($info) ?></a>.</p>
<h2>2. What we collect</h2>
<ul>
<li><strong>Contact and quote forms:</strong> your name, email, and optionally your company, phone number and message.</li>
<li><strong>Job applications:</strong> your name, contact details, profile link, cover note and CV.</li>
<li><strong>Technical data:</strong> a one-way coded version of your IP address (we do not store the address itself) and your browser type, used to stop spam and abuse.</li>
<li><strong>Analytics:</strong> only if you allow analytics cookies, anonymous statistics about how pages are used.</li>
</ul>
<h2>3. Why we use it</h2>
<ul>
<li>To reply to your enquiry and prepare quotes or proposals.</li>
<li>To review job applications and contact candidates.</li>
<li>To protect the website from spam, fraud and attacks.</li>
<li>To improve the website, if you allow analytics.</li>
</ul>
<h2>4. How long we keep it</h2>
<p>We keep enquiries for up to 24 months after our last contact with you. We keep job applications for up to 12 months unless you ask us to delete them sooner. Security records are deleted automatically after 24 hours.</p>
<h2>5. Who we share it with</h2>
<p>We do not sell your data. We only share it with trusted service providers who help us run the website and email, such as our hosting and email providers, and only as needed to provide their service.</p>
<h2>6. How we protect it</h2>
<p>The website uses encrypted connections (HTTPS), protected forms, access controls on our admin system and secure storage for uploaded files. Only authorised staff can view your data.</p>
<h2>7. Your rights</h2>
<p>You can ask to see, correct or delete your personal data, or object to how we use it. Email <a href="mailto:<?= e($info) ?>"><?= e($info) ?></a> and we will reply within 30 days.</p>
<h2>8. Cookies</h2>
<p>See our <a href="<?= e(url('cookie-policy')) ?>">cookie policy</a> for details. You can change your choice at any time with the cookie button at the bottom left of every page.</p>
<h2>9. Changes</h2>
<p>We may update this policy from time to time. The date at the top shows the latest version.</p>
<?php include __DIR__ . '/_wrap_end.php'; ?>
