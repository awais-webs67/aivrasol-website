<?php
/** @var ?array $emp @var ?array $doc @var bool $limited */
$site = setting('site_name', 'AivraSol');
$d = $doc ? (json_decode($doc['data'], true) ?: []) : [];
?>
<section class="section verify">
  <div class="container verify-wrap">
    <p class="project-meta"><?= e($site) ?> verification</p>
    <?php if ($limited): ?>
    <h1 class="display-2">Please try again shortly</h1>
    <p class="lead">Too many checks from your network in a few minutes. Wait a little and scan the code again.</p>
    <?php elseif ($emp): $current = in_array($emp['status'], ['active', 'on_leave'], true); ?>
    <div class="verify-badge <?= $current ? 'is-ok' : 'is-warn' ?>"><?= icon($current ? 'check' : 'close') ?><span><?= $current ? 'Current employee' : 'No longer employed' ?></span></div>
    <h1 class="display-2"><?= e($emp['full_name']) ?></h1>
    <dl class="dl verify-dl">
      <dt>Employee ID</dt><dd><?= e($emp['employee_code']) ?></dd>
      <dt>Job title</dt><dd><?= e($emp['designation']) ?></dd>
      <?php if ($emp['department'] !== ''): ?><dt>Department</dt><dd><?= e($emp['department']) ?></dd><?php endif; ?>
      <dt>With <?= e($site) ?> since</dt><dd><?= e(date('F Y', strtotime($emp['join_date']))) ?></dd>
      <?php if (!$current && $emp['end_date']): ?><dt>Until</dt><dd><?= e(date('F Y', strtotime($emp['end_date']))) ?></dd><?php endif; ?>
    </dl>
    <p class="small muted">This page confirms the ID card was issued by <?= e($site) ?>. If the details do not match the card, please contact us.</p>
    <?php elseif ($doc): $valid = $doc['revoked_at'] === null; ?>
    <div class="verify-badge <?= $valid ? 'is-ok' : 'is-warn' ?>"><?= icon($valid ? 'check' : 'close') ?><span><?= $valid ? 'Genuine and valid' : 'Withdrawn: no longer valid' ?></span></div>
    <h1 class="display-2"><?= e($doc['title']) ?></h1>
    <dl class="dl verify-dl">
      <dt>Reference</dt><dd><?= e($doc['ref_no']) ?></dd>
      <dt>Issued to</dt><dd><?= e((string) ($d['name'] ?? '')) ?> (<?= e($doc['employee_code']) ?>)</dd>
      <dt>Job title</dt><dd><?= e((string) ($d['designation'] ?? '')) ?></dd>
      <?php if (!empty($d['join_date']) && !empty($d['end_date'])): ?><dt>Employment</dt><dd><?= e(date('j F Y', strtotime($d['join_date']))) ?> to <?= e(date('j F Y', strtotime($d['end_date']))) ?></dd><?php endif; ?>
      <dt>Date issued</dt><dd><?= e(date('j F Y', strtotime($doc['issued_at']))) ?></dd>
      <?php if (!empty($d['signatory_name'])): ?><dt>Signed by</dt><dd><?= e($d['signatory_name']) ?><?= !empty($d['signatory_title']) ? ', ' . e($d['signatory_title']) : '' ?></dd><?php endif; ?>
    </dl>
    <p class="small muted">Compare these details with the letter you were given. If anything differs, the letter may have been changed. Contact us to confirm.</p>
    <?php else: ?>
    <div class="verify-badge is-warn"><?= icon('close') ?><span>Not found</span></div>
    <h1 class="display-2">We could not find this document</h1>
    <p class="lead">The link or code is not one we issued. If you think this is a mistake, please contact us.</p>
    <?php endif; ?>
    <p><a class="btn btn-outline" href="mailto:<?= e(setting('email_hr') ?: setting('email_info')) ?>">Contact <?= e($site) ?> HR</a></p>
  </div>
</section>
