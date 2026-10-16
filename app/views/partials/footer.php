<?php
$offices = Content::offices();
$socials = Content::socials();
$phone = setting('phone');
$whatsapp = setting('whatsapp');
?>
<footer class="site-footer">
  <div class="container">
    <div class="footer-top">
      <div class="footer-contact">
        <h2 class="footer-title">Get in touch<br>with us today!</h2>
        <span class="bar"></span>
        <p>For business enquiries, email <a href="mailto:<?= e(setting('email_info')) ?>"><?= e(setting('email_info')) ?></a></p>
        <p>For jobs and internships, email <a href="mailto:<?= e(setting('email_hr')) ?>"><?= e(setting('email_hr')) ?></a></p>
        <?php if ($phone !== ''): ?><p>Call us on <a href="<?= e(phone_href($phone)) ?>"><?= e($phone) ?></a></p><?php endif; ?>
      </div>
      <div class="footer-cols">
        <div class="footer-col">
          <h3>Hire Developers</h3>
          <ul>
            <?php foreach (Content::offerings('hire') as $h): ?>
            <li><a href="<?= e(url('hire/' . $h['slug'])) ?>"><?= e($h['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="footer-col">
          <h3>Services</h3>
          <ul>
            <?php foreach (Content::offerings('service') as $s): ?>
            <li><a href="<?= e(url('services/' . $s['slug'])) ?>"><?= e($s['title']) ?></a></li>
            <?php endforeach; ?>
          </ul>
        </div>
        <div class="footer-col">
          <h3>Company</h3>
          <ul>
            <li><a href="<?= e(url('about')) ?>">About us</a></li>
            <li><a href="<?= e(url('portfolio')) ?>">Portfolio</a></li>
            <li><a href="<?= e(url('pricing')) ?>">Pricing</a></li>
            <li><a href="<?= e(url('careers')) ?>">Careers</a></li>
            <li><a href="<?= e(url('contact')) ?>">Contact us</a></li>
          </ul>
        </div>
      </div>
    </div>
    <?php if ($offices || $socials || $whatsapp !== '' || setting('office_hours') !== ''): ?>
    <div class="footer-mid">
      <?php foreach ($offices as $o): ?>
      <div><h3><?= e($o['title']) ?></h3><p><?= e($o['text']) ?></p></div>
      <?php endforeach; ?>
      <?php if (setting('office_hours') !== ''): ?>
      <div><h3>Office hours</h3><p><?= e(setting('office_hours')) ?></p></div>
      <?php endif; ?>
      <?php if ($whatsapp !== ''): ?>
      <div><h3>WhatsApp</h3><p><a href="<?= e(whatsapp_href($whatsapp)) ?>" rel="noopener" target="_blank"><?= e($whatsapp) ?></a></p></div>
      <?php endif; ?>
      <?php if ($socials): ?>
      <div>
        <h3>Follow us</h3>
        <ul class="socials">
          <?php foreach ($socials as $s): ?>
          <li><a href="<?= e($s['url']) ?>" rel="noopener" target="_blank" aria-label="<?= e($s['label']) ?>"><?= icon($s['icon']) ?></a></li>
          <?php endforeach; ?>
        </ul>
      </div>
      <?php endif; ?>
    </div>
    <?php endif; ?>
    <div class="footer-bottom">
      <p class="small">© <?= date('Y') ?> <?= e(setting('site_name', 'AivraSol')) ?>. All rights reserved.</p>
      <p class="small"><a href="<?= e(url('privacy-policy')) ?>">Privacy</a> · <a href="<?= e(url('terms')) ?>">Terms</a> · <a href="<?= e(url('cookie-policy')) ?>">Cookies</a> · <button type="button" data-cookie-open>Cookie settings</button></p>
    </div>
  </div>
</footer>
