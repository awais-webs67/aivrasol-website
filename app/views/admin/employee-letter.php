<?php /** @var array $emp @var string $end @var string $body @var string $title @var array $errors @var bool $ready */ ?>
<p><a class="link-arrow small" href="<?= e(url('admin/employees/' . $emp['id'])) ?>">← Back to <?= e($emp['full_name']) ?></a></p>
<?php foreach ($errors as $err): ?><p class="form-status is-error" role="alert"><?= e($err) ?></p><?php endforeach; ?>
<?php if (!$ready): ?><p class="form-status is-error" role="alert">Add the signatory name and signature first<?= Auth::can('settings', $user) ? '' : ' (ask an admin)' ?>, so letters are signed. <?php if (Auth::can('settings', $user)): ?><a href="<?= e(url('admin/documents')) ?>">Open Letters and ID cards settings</a><?php endif; ?></p><?php endif; ?>
<form method="post" class="card form-grid cols-2">
  <?= csrf_field() ?>
  <label class="field"><span>Letter title</span><input class="input" type="text" name="title" value="<?= e($title) ?>" maxlength="60" required></label>
  <label class="field"><span>Last working day</span><input class="input" type="date" name="end_date" value="<?= e($end) ?>" min="<?= e($emp['join_date']) ?>" required></label>
  <div class="field span-2">
    <label for="l-body"><span>Letter text</span></label>
    <textarea class="textarea editor" id="l-body" name="body" maxlength="6000"><?= e($body) ?></textarea>
    <span class="small muted">Filled in from your template. You can edit it for this person before issuing. Leave a blank line between paragraphs.</span>
  </div>
  <div class="span-2 btn-group">
    <button class="btn" type="submit" name="action" value="issue"<?= $ready ? '' : ' disabled' ?>>Issue letter</button>
    <button class="btn btn-outline" type="submit" name="action" value="preview">Refill from template</button>
  </div>
  <p class="span-2 small muted">Issuing gives the letter a reference number and a QR code that anyone can scan to check it is genuine. You can withdraw it later.</p>
</form>
