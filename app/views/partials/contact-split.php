<?php
$personName = setting('contact_person_name');
$personRole = setting('contact_person_role', 'Head of Sales');
$personEmail = setting('contact_person_email', setting('email_sales', setting('email_info')));
$phone = setting('phone');
$whatsapp = setting('whatsapp');
$initials = $personName !== '' ? mb_strtoupper(implode('', array_map(fn ($w) => mb_substr($w, 0, 1), array_slice(preg_split('/\s+/', $personName) ?: [], 0, 2)))) : 'AS';
?>
<section class="container" id="get-in-touch">
  <div class="contact-split">
    <div class="known">
      <h2 class="display-2">Do you want to get to know us?</h2>
      <span class="bar"></span>
      <div class="person">
        <span class="avatar" aria-hidden="true"><?= e($initials) ?></span>
        <div>
          <span class="tag"><?= e($personRole) ?></span>
          <strong><?= e($personName !== '' ? $personName : 'Our sales team') ?></strong>
        </div>
      </div>
      <ul class="contact-lines">
        <li><?= icon('mail') ?><a href="mailto:<?= e($personEmail) ?>"><?= e($personEmail) ?></a></li>
        <?php if ($phone !== ''): ?><li><?= icon('call') ?><a href="<?= e(phone_href($phone)) ?>"><?= e($phone) ?></a></li><?php endif; ?>
        <?php if ($whatsapp !== ''): ?><li><?= icon('whatsapp') ?><a href="<?= e(whatsapp_href($whatsapp)) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a></li><?php endif; ?>
        <?php if (setting('office_hours') !== ''): ?><li><?= icon('clock') ?><span><?= e(setting('office_hours')) ?></span></li><?php endif; ?>
        <?php if (setting('booking_url') !== ''): ?><li><?= icon('chat') ?><a href="<?= e(setting('booking_url')) ?>" target="_blank" rel="noopener">Book a call in our calendar</a></li><?php endif; ?>
      </ul>
    </div>
    <div class="ask">
      <h2 class="display-2">Do you have any questions?</h2>
      <span class="bar"></span>
      <p>Share your details and our consultation team will reply within one business day.</p>
      <?= partial('contact-form', ['interest' => $interest ?? null, 'idPrefix' => 'split']) ?>
    </div>
  </div>
</section>
