<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<p class="small muted">Everything here updates the live website as soon as you save. Email settings have their own page: <a href="<?= e(url('admin/email')) ?>">Email</a>.</p>
<form method="post" class="settings-form" enctype="multipart/form-data">
  <?= csrf_field() ?>
  <?php foreach ($schema as $group => $fields): ?>
  <fieldset class="card form-grid cols-2">
    <legend><?= e($group) ?></legend>
    <?php foreach ($fields as $key => $def): [$label, $type] = $def; $help = $def[2] ?? ''; $val = $posted !== null && $type !== 'image' ? (string) ($posted[$key] ?? '') : setting($key, $type === 'bool' ? '1' : ''); $err = $errors[$key] ?? null; ?>
    <?php if ($type === 'bool'): ?>
    <label class="check span-2"><input type="checkbox" name="<?= e($key) ?>" value="1"<?= ($posted !== null ? isset($posted[$key]) : $val === '1') ? ' checked' : '' ?>> <span><?= e($label) ?><?php if ($help !== ''): ?><br><span class="small muted"><?= e($help) ?></span><?php endif; ?></span></label>
    <?php continue; endif; ?>
    <div class="field<?= in_array($type, ['textarea', 'image'], true) ? ' span-2' : '' ?>">
      <label for="s-<?= e($key) ?>"><span><?= e($label) ?></span></label>
      <?php if ($type === 'textarea'): ?>
      <textarea class="textarea" id="s-<?= e($key) ?>" name="<?= e($key) ?>" maxlength="2000"<?= $err ? ' aria-invalid="true"' : '' ?>><?= e($val) ?></textarea>
      <?php elseif ($type === 'image'): ?>
      <?php if ($val !== ''): ?><img class="thumb" src="<?= e(url($val)) ?>" alt="" width="240" height="160"><label class="check"><input type="checkbox" name="<?= e($key) ?>_remove" value="1"> <span>Remove and use the default</span></label><?php endif; ?>
      <input class="file-input" id="s-<?= e($key) ?>" type="file" name="<?= e($key) ?>" accept="image/jpeg,image/png,image/webp">
      <?php else: ?>
      <input class="input" id="s-<?= e($key) ?>" type="<?= $type === 'email' ? 'email' : ($type === 'url' ? 'url' : 'text') ?>" name="<?= e($key) ?>" value="<?= e($val) ?>" maxlength="2000"<?= $err ? ' aria-invalid="true"' : '' ?>>
      <?php endif; ?>
      <?php if ($help !== ''): ?><span class="small muted"><?= e($help) ?></span><?php endif; ?>
      <?php if ($err): ?><span class="field-error"><?= e($err) ?></span><?php endif; ?>
    </div>
    <?php endforeach; ?>
  </fieldset>
  <?php endforeach; ?>
  <div class="sticky-save"><button class="btn" type="submit">Save settings</button></div>
</form>
