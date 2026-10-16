<?php $cronSeen = setting('cron_last_seen'); ?>
<div class="admin-grid">
  <section class="card">
    <h2>Make a backup</h2>
    <p class="muted">A full backup is one ZIP file with the database and every uploaded file: website images, staff photos, signatures, CVs and client portal files. Use it to move to a new host or to recover after a problem.</p>
    <div class="btn-group">
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="full"><button class="btn btn-sm" type="submit"><?= icon('download') ?>Full backup</button></form>
      <form method="post"><?= csrf_field() ?><input type="hidden" name="action" value="database"><button class="btn btn-sm btn-outline" type="submit">Database only</button></form>
    </div>
    <h3 class="mt">Saved backups</h3>
    <?php if (!$backups): ?>
    <p class="muted">No backups yet.</p>
    <?php else: ?>
    <div class="table-wrap"><table class="table">
      <thead><tr><th>Backup</th><th>Size</th><th><span class="sr-only">Actions</span></th></tr></thead>
      <tbody><?php foreach ($backups as $b): ?><tr>
        <td><?= e($b['kind']) ?><br><span class="small muted"><?= e(gmdate('j M Y, H:i', $b['time'])) ?> UTC</span></td>
        <td class="nowrap small"><?= e(Portal::size((int) $b['size'])) ?></td>
        <td class="nowrap"><a class="btn btn-sm btn-outline" href="<?= e(url('admin/backups/download') . '?name=' . rawurlencode($b['name'])) ?>"><?= icon('download') ?>Download</a>
          <form method="post" class="inline-form" data-confirm="Delete this backup?"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="name" value="<?= e($b['name']) ?>"><button class="btn btn-sm btn-outline" type="submit" aria-label="Delete backup"><?= icon('trash') ?></button></form></td>
      </tr><?php endforeach; ?></tbody>
    </table></div>
    <?php endif; ?>
  </section>
  <section class="card">
    <h2>Automatic backups</h2>
    <form method="post" class="form-grid">
      <?= csrf_field() ?><input type="hidden" name="action" value="settings">
      <label class="check"><input type="checkbox" name="backup_auto" value="1"<?= setting('backup_auto', '1') === '1' ? ' checked' : '' ?>> <span>Back up the database every day (needs the cron job from Email campaigns)</span></label>
      <label class="field"><span>How many backups of each kind to keep</span><input class="input" type="number" name="backup_keep" min="2" max="60" value="<?= e(setting('backup_keep', '7')) ?>"></label>
      <button class="btn btn-sm" type="submit">Save</button>
    </form>
    <p class="small muted">Cron last ran: <?= $cronSeen !== '' ? e($cronSeen) . ' UTC' : 'never' ?>. Backups stay on this server, so download one regularly (for example monthly) and keep it in Google Drive or on your computer.</p>
    <h3 class="mt">Moving to a new host</h3>
    <ol class="small">
      <li>Here, click <strong>Full backup</strong> and download the ZIP.</li>
      <li>On the new host, deploy the code from GitHub (or upload it), create an empty database and fill in <code>app/config.php</code> with the same <code>app_key</code> as before (it unlocks saved passwords and ID numbers).</li>
      <li>Upload the ZIP to <code>storage/backups/</code> and run <code>php database/restore.php storage/backups/FILE.zip</code>, then <code>php database/install.php</code>.</li>
      <li>Add the cron job again, point the domain to the new host, and check the site. Nothing else changes.</li>
    </ol>
  </section>
</div>
