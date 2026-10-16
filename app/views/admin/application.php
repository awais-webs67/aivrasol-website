<p><a class="link-arrow small" href="<?= e(url('admin/applications')) ?>">← Back to applications</a></p>
<div class="admin-grid">
  <section class="card">
    <div class="card-head"><h2><?= e($app['name']) ?></h2><span class="status status-<?= e($app['status']) ?>"><?= e(APP_STATUSES[$app['status']]) ?></span></div>
    <dl class="dl">
      <dt>Position</dt><dd><?= e($app['position']) ?></dd>
      <dt>Email</dt><dd><a href="mailto:<?= e($app['email']) ?>"><?= e($app['email']) ?></a></dd>
      <dt>Phone</dt><dd><?= e($app['phone']) ?></dd>
      <?php if ($app['profile_url'] !== '' && preg_match('#^https?://#i', $app['profile_url'])): ?><dt>Profile</dt><dd><a href="<?= e($app['profile_url']) ?>" target="_blank" rel="noopener noreferrer"><?= e($app['profile_url']) ?></a></dd><?php endif; ?>
      <dt>Received</dt><dd><?= e(date('j F Y, H:i', strtotime($app['created_at'] . ' UTC'))) ?> UTC</dd>
    </dl>
    <?php if ($app['message']): ?><h3>Cover note</h3><div class="message"><?= nl2br(e($app['message'])) ?></div><?php endif; ?>
    <?php if ($app['cv_file'] !== ''): ?><p><a class="btn btn-sm" href="<?= e(url('admin/applications/' . $app['id'] . '/cv')) ?>"><?= icon('download') ?>Download CV (<?= e($app['cv_original']) ?>)</a></p><?php endif; ?>
  </section>
  <section class="card">
    <h2>Update</h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?>
      <label class="field"><span>Status</span><select class="select" name="status"><?php foreach (APP_STATUSES as $k => $v): ?><option value="<?= e($k) ?>"<?= $app['status'] === $k ? ' selected' : '' ?>><?= e($v) ?></option><?php endforeach; ?></select></label>
      <label class="field"><span>Private notes</span><textarea class="textarea" name="notes" maxlength="5000"><?= e($app['notes'] ?? '') ?></textarea></label>
      <button class="btn" type="submit">Save</button>
    </form>
    <form method="post" class="danger-zone" data-confirm="Delete this application and its CV for good?">
      <?= csrf_field() ?><input type="hidden" name="action" value="delete">
      <button class="btn btn-sm btn-danger" type="submit"><?= icon('trash') ?>Delete application and CV</button>
    </form>
  </section>
</div>
