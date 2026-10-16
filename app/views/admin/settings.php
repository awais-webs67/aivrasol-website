<?php if ($errors): ?><p class="form-status is-error" role="alert">Please fix the highlighted fields.</p><?php endif; ?>
<form method="post" class="settings-form">
  <?= csrf_field() ?>
  <?php foreach ($schema as $group => $fields): ?>
  <fieldset class="card form-grid cols-2">
    <legend><?= e($group) ?></legend>
    <?php foreach ($fields as $key => $def): [$label, $type] = $def; $help = $def[2] ?? ''; $val = $posted !== null ? (string) ($posted[$key] ?? '') : setting($key); $err = $errors[$key] ?? null; ?>
    <div class="field<?= $type === 'textarea' ? ' span-2' : '' ?>">
      <label for="s-<?= e($key) ?>"><span><?= e($label) ?></span></label>
      <?php if ($type === 'textarea'): ?>
      <textarea class="textarea" id="s-<?= e($key) ?>" name="<?= e($key) ?>" maxlength="2000"<?= $err ? ' aria-invalid="true"' : '' ?>><?= e($val) ?></textarea>
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
