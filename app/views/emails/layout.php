<?php
/**
 * Branded HTML email shell. Email clients ignore stylesheets, so every style is inline and the layout uses tables.
 * @var string $preheader  Short preview text shown in the inbox list
 * @var string $content    Inner HTML
 */
$site = setting('site_name', 'AivraSol');
$phone = setting('phone');
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="color-scheme" content="light">
<title><?= e($site) ?></title>
</head>
<body style="margin:0;padding:0;background:#EBECED;">
<div style="display:none;max-height:0;overflow:hidden;opacity:0;"><?= e($preheader ?? '') ?></div>
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" border="0" style="background:#EBECED;">
  <tr><td align="center" style="padding:28px 12px;">
    <table role="presentation" width="600" cellpadding="0" cellspacing="0" border="0" style="width:100%;max-width:600px;background:#FFFFFF;border-radius:8px;overflow:hidden;">
      <tr><td style="padding:26px 32px 22px;border-bottom:4px solid #FF5B35;">
        <a href="<?= e(absolute_url()) ?>" style="text-decoration:none;"><img src="<?= e(absolute_url('assets/img/logo-email.png')) ?>" width="168" height="32" alt="<?= e($site) ?>" style="display:block;border:0;height:32px;width:168px;"></a>
      </td></tr>
      <tr><td style="padding:32px 32px 12px;font-family:Arial,Helvetica,sans-serif;font-size:15px;line-height:24px;color:#333F48;">
        <?= $content ?>
      </td></tr>
      <tr><td style="padding:22px 32px 26px;background:#F6F7F8;font-family:Arial,Helvetica,sans-serif;font-size:13px;line-height:20px;color:#5F666B;">
        <strong style="color:#191F24;"><?= e($site) ?></strong> · <?= e(setting('site_tagline')) ?><br>
        <a href="mailto:<?= e(setting('email_info')) ?>" style="color:#B8380F;text-decoration:none;"><?= e(setting('email_info')) ?></a>
        <?php if ($phone !== ''): ?> · <?= e($phone) ?><?php endif; ?>
        · <a href="<?= e(absolute_url()) ?>" style="color:#B8380F;text-decoration:none;"><?= e(preg_replace('#^https?://#', '', base_url())) ?></a>
      </td></tr>
    </table>
  </td></tr>
</table>
</body>
</html>
