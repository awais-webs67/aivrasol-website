<?php $details = $lead['details'] ? (json_decode($lead['details'], true) ?: []) : []; ?>
<p><a class="link-arrow small" href="<?= e(url('admin/leads')) ?>">← Back to leads</a></p>
<div class="admin-grid">
  <section class="card">
    <div class="card-head"><h2><?= e($lead['name']) ?></h2><span class="status status-<?= e($lead['status']) ?>"><?= e(LEAD_STATUSES[$lead['status']]) ?></span></div>
    <dl class="dl">
      <dt>Type</dt><dd><?= e(LEAD_TYPES[$lead['type']]) ?></dd>
      <dt>Email</dt><dd><a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a></dd>
      <?php if ($lead['phone'] !== ''): ?><dt>Phone</dt><dd><a href="<?= e(phone_href($lead['phone'])) ?>"><?= e($lead['phone']) ?></a> · <a href="<?= e(whatsapp_href($lead['phone'])) ?>" target="_blank" rel="noopener">WhatsApp</a></dd><?php endif; ?>
      <?php if ($lead['company'] !== ''): ?><dt>Company</dt><dd><?= e($lead['company']) ?></dd><?php endif; ?>
      <?php foreach ($details as $k => $v): ?><dt><?= e(ucfirst((string) $k)) ?></dt><dd><?= e(is_scalar($v) ? (string) $v : '') ?></dd><?php endforeach; ?>
      <dt>Received</dt><dd><?= e(date('j F Y, H:i', strtotime($lead['created_at'] . ' UTC'))) ?> UTC</dd>
      <dt>From page</dt><dd><?= e($lead['source_page']) ?></dd>
    </dl>
    <?php if ($lead['message']): ?><h3>Message</h3><div class="message"><?= nl2br(e($lead['message'])) ?></div><?php endif; ?>
    <p class="btn-group"><a class="btn btn-sm" href="mailto:<?= e($lead['email']) ?>?subject=<?= e(rawurlencode('Re: your enquiry to ' . setting('site_name', 'AivraSol'))) ?>"><?= icon('mail') ?>Reply by email</a></p>
  </section>
  <section class="card">
    <h2>Update</h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <label class="field"><span>Status</span><select class="select" name="status"><?php foreach (LEAD_STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $lead['status'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span>Private notes</span><textarea class="textarea" name="notes" maxlength="5000"><?= e($lead['notes'] ?? '') ?></textarea></label>
      <button class="btn" type="submit">Save</button>
    </form>
    <form method="post" class="danger-zone" data-confirm="Delete this lead for good?">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete lead</button>
    </form>
  </section>
</div>
